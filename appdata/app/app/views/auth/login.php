<style>
  .g-divider{display:flex;align-items:center;gap:10px;color:var(--muted);font-size:12px;margin:16px 0}
  .g-divider::before,.g-divider::after{content:"";flex:1;height:1px;background:var(--line)}
  .btn-google{display:flex;align-items:center;justify-content:center;gap:10px;padding:10px 16px;border-radius:999px;background:var(--surface-solid);color:var(--text);border:1px solid var(--line);text-decoration:none;font-weight:600;box-shadow:var(--shadow-sm)}
  .btn-google svg{width:18px;height:18px}
</style>
<div class="card auth-card">
  <h1>Masuk</h1>
  <p class="muted">Masuk dengan username/email dan password Anda.</p>
  <form method="post" action="/login" autocomplete="on">
    <?= csrf_field() ?>
    <div class="field">
      <label for="username">Username atau Email</label>
      <input type="text" id="username" name="username" value="<?= e(old('username')) ?>" required autofocus>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>
    <button class="btn" type="submit" style="width:100%">Masuk</button>
  </form>
  <?php if (!empty($googleAktif)): ?>
    <div class="g-divider"><span>atau</span></div>
    <a class="btn-google" href="/auth/google">
      <svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
      Masuk dengan Google
    </a>
  <?php endif; ?>
  <p class="small" style="margin-top:14px">Belum punya akun? <a href="/daftar">Daftar sebagai penyewa</a></p>
</div>
