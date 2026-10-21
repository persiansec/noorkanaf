<?php
require __DIR__ . '/../inc/core.php';
send_headers(true);
admin_session();
ensure_admin();
$err = '';
$noAdmin = (int)val('SELECT COUNT(*) FROM admins') === 0;
if (!empty($_SESSION['aid'])) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (throttle_count('login', 900) >= 6) {
        $err = 'تعداد تلاش‌های ناموفق زیاد بوده است. ۱۵ دقیقه دیگر دوباره امتحان کنید.';
    } else {
        $u = trim((string)($_POST['username'] ?? ''));
        $p = (string)($_POST['password'] ?? '');
        $a = row('SELECT * FROM admins WHERE username=?', [$u]);
        $ok = password_verify($p, $a['pass'] ?? '$2y$10$usesomesillystringforsalt.invalidhashvaluexxxxxxxxxxxxxxxxxxx') && $a;
        if ($ok) {
            session_regenerate_id(true);
            $_SESSION = ['aid' => (int)$a['id'], 'last' => time(), 'csrf' => bin2hex(random_bytes(16))];
            q('DELETE FROM throttle WHERE ip=? AND kind=?', [client_ip(), 'login']);
            if (password_needs_rehash($a['pass'], PASSWORD_DEFAULT)) q('UPDATE admins SET pass=? WHERE id=?', [password_hash($p, PASSWORD_DEFAULT), $a['id']]);
            redirect('index.php');
        }
        throttle_hit('login');
        usleep(400000);
        $err = 'نام کاربری یا رمز عبور اشتباه است.';
    }
}
?><!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>ورود به پنل مدیریت</title><meta name="robots" content="noindex,nofollow">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/admin.css"></head>
<body class="login-body"><form class="login-card" method="post" autocomplete="off">
  <div class="mark big"><?= e(setting('logo_mark', 'K')) ?></div>
  <h1>ورود به پنل مدیریت</h1>
  <?php if ($noAdmin): ?>
    <div class="flash err">هنوز حساب مدیری ساخته نشده است. فایل <b>config.php</b> را باز کنید، مقدار <b>INITIAL_ADMIN_PASS</b> را تنظیم و سپس این صفحه را رفرش کنید.</div>
  <?php else: ?>
    <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <label>نام کاربری<input name="username" required autofocus autocomplete="username"></label>
    <label>رمز عبور<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn">ورود</button>
  <?php endif; ?>
</form></body></html>
