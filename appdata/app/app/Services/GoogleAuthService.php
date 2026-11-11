<?php
declare(strict_types=1);

/**
 * Login/Daftar dengan Google (OpenID Connect, authorization code flow).
 * id_token diterima langsung dari endpoint token Google lewat TLS, sehingga klaimnya divalidasi
 * (iss, aud, exp, nonce, email_verified) tanpa verifikasi tanda tangan JWT (diizinkan spesifikasi OIDC).
 */
class GoogleAuthService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public static function aktif(): bool
    {
        return env('GOOGLE_CLIENT_ID') !== null && env('GOOGLE_CLIENT_SECRET') !== null && env('GOOGLE_REDIRECT_URI') !== null;
    }

    /** $mode: 'masuk' (login/daftar) atau 'tautkan' (hubungkan ke akun penyewa yang sedang login). */
    public function urlOtorisasi(string $mode): string
    {
        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));
        $_SESSION['g_oauth'] = ['state' => $state, 'nonce' => $nonce, 'mode' => $mode === 'tautkan' ? 'tautkan' : 'masuk', 't' => time()];
        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'nonce' => $nonce,
            'prompt' => 'select_account',
        ]);
    }

    /** @return array{sub: string, email: string, nama: string, mode: string} */
    public function verifikasiCallback(array $query): array
    {
        $sesi = $_SESSION['g_oauth'] ?? null;
        unset($_SESSION['g_oauth']);
        if (!is_array($sesi) || time() - (int)$sesi['t'] > 600) {
            throw new AppException('Sesi login Google kedaluwarsa. Silakan coba lagi.');
        }
        if (!hash_equals((string)$sesi['state'], (string)($query['state'] ?? ''))) {
            throw new AppException('Permintaan login Google tidak valid.');
        }
        $code = (string)($query['code'] ?? '');
        if ($code === '') {
            throw new AppException('Login Google dibatalkan.');
        }

        $tok = $this->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
            'grant_type' => 'authorization_code',
        ]);
        $c = $this->payload((string)($tok['id_token'] ?? ''));

        $aud = $c['aud'] ?? '';
        $audOk = is_array($aud) ? in_array(env('GOOGLE_CLIENT_ID'), $aud, true) : $aud === env('GOOGLE_CLIENT_ID');
        if (!in_array($c['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true)
            || !$audOk
            || (int)($c['exp'] ?? 0) < time()
            || !hash_equals((string)$sesi['nonce'], (string)($c['nonce'] ?? ''))) {
            throw new AppException('Token Google tidak valid.');
        }
        $verified = ($c['email_verified'] ?? false) === true || ($c['email_verified'] ?? '') === 'true';
        $sub = (string)($c['sub'] ?? '');
        $email = strtolower(trim((string)($c['email'] ?? '')));
        if (!$verified || $sub === '' || $email === '') {
            throw new AppException('Email akun Google belum terverifikasi.');
        }
        if (strlen($email) > 100) {
            throw new AppException('Alamat email terlalu panjang.');
        }
        return ['sub' => $sub, 'email' => $email, 'nama' => trim((string)($c['name'] ?? '')), 'mode' => (string)$sesi['mode']];
    }

    private function post(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            error_log('[Google] curl: ' . $err);
            throw new AppException('Tidak dapat menghubungi Google. Silakan coba lagi.');
        }
        $json = json_decode((string)$body, true);
        if (!is_array($json) || $http >= 400) {
            error_log('[Google] token error ' . $http . ': ' . substr((string)$body, 0, 300));
            throw new AppException('Login Google gagal. Silakan coba lagi.');
        }
        return $json;
    }

    private function payload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new AppException('Token Google tidak valid.');
        }
        $b64 = strtr($parts[1], '-_', '+/');
        $json = base64_decode($b64 . str_repeat('=', (4 - strlen($b64) % 4) % 4), true);
        $data = $json === false ? null : json_decode($json, true);
        if (!is_array($data)) {
            throw new AppException('Token Google tidak valid.');
        }
        return $data;
    }
}
