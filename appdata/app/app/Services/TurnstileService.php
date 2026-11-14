<?php
declare(strict_types=1);

class TurnstileService
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public static function aktif(): bool
    {
        return env('CLOUDFLARE_TURNSTILE_SITE_KEY') !== null && env('CLOUDFLARE_TURNSTILE_SECRET_KEY') !== null;
    }

    public static function siteKey(): ?string
    {
        return env('CLOUDFLARE_TURNSTILE_SITE_KEY');
    }

    /**
     * Validasi response token Cloudflare Turnstile.
     * Jika konfigurasi belum diisi, loloskan pengecekan.
     */
    public function pastikanValid(?string $token): void
    {
        if (!self::aktif()) {
            return;
        }

        $token = trim((string)$token);
        if ($token === '') {
            throw new AppException('Mohon selesaikan verifikasi captcha.');
        }

        $secret = env('CLOUDFLARE_TURNSTILE_SECRET_KEY');
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR'])[0] : null)
            ?? ($_SERVER['REMOTE_ADDR'] ?? null);

        $payload = [
            'secret' => $secret,
            'response' => $token,
        ];
        if ($ip !== null && $ip !== '') {
            $payload['remoteip'] = trim((string)$ip);
        }

        $ch = curl_init(self::VERIFY_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            error_log('[Turnstile] curl error: ' . $err);
            throw new AppException('Tidak dapat memverifikasi captcha. Silakan coba lagi.');
        }

        $res = json_decode((string)$body, true);
        if (!is_array($res) || empty($res['success'])) {
            $errCodes = isset($res['error-codes']) && is_array($res['error-codes']) ? implode(', ', $res['error-codes']) : 'invalid';
            error_log('[Turnstile] verification failed: ' . $errCodes);
            throw new AppException('Verifikasi captcha tidak valid atau kedaluwarsa. Silakan coba lagi.');
        }
    }
}
