<div class="card auth-card">
  <h1>Masuk</h1>
  <p class="muted">Masuk dengan username dan password Anda.</p>
  <form method="post" action="/login" autocomplete="on">
    <?= csrf_field() ?>
    <div class="field">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" value="<?= e(old('username')) ?>" required autofocus>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>
    <button class="btn" type="submit" style="width:100%">Masuk</button>
  </form>
  <p class="small" style="margin-top:14px">Belum punya akun? <a href="/daftar">Daftar sebagai penyewa</a></p>
</div>
