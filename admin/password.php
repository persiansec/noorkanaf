<?php
require __DIR__ . '/_layout.php';
admin_required();
$me = row('SELECT * FROM admins WHERE id=?', [(int)$_SESSION['aid']]);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $cur = (string)($_POST['current'] ?? ''); $user = trim((string)($_POST['username'] ?? '')); $new = (string)($_POST['new'] ?? '');
    if (!password_verify($cur, $me['pass'])) flash('رمز فعلی اشتباه است.', 'err');
    elseif (!preg_match('/^[A-Za-z0-9_.\-]{3,32}$/', $user)) flash('نام کاربری باید ۳ تا ۳۲ کاراکتر انگلیسی/عدد باشد.', 'err');
    elseif ($new !== '' && (mb_strlen($new) < 10 || $new !== (string)($_POST['new2'] ?? ''))) flash('رمز جدید باید حداقل ۱۰ کاراکتر و مطابق تکرار آن باشد.', 'err');
    else {
        try {
            q('UPDATE admins SET username=?, pass=? WHERE id=?', [$user, $new !== '' ? password_hash($new, PASSWORD_DEFAULT) : $me['pass'], $me['id']]);
            session_regenerate_id(true);
            flash('اطلاعات حساب ذخیره شد ✅');
        } catch (PDOException $x) { flash('این نام کاربری قبلاً استفاده شده است.', 'err'); }
    }
    redirect('password.php');
}
admin_head('حساب کاربری', 'password'); ?>
<form method="post" class="card form" autocomplete="off"><?= csrf_field() ?>
  <div class="fld"><label>نام کاربری</label><input name="username" value="<?= e($me['username']) ?>" dir="ltr" required></div>
  <div class="fld"><label>رمز فعلی <i>*</i></label><input type="password" name="current" required autocomplete="current-password"></div>
  <div class="fld"><label>رمز جدید (خالی = بدون تغییر)</label><input type="password" name="new" minlength="10" autocomplete="new-password"><small>حداقل ۱۰ کاراکتر</small></div>
  <div class="fld"><label>تکرار رمز جدید</label><input type="password" name="new2" autocomplete="new-password"></div>
  <div class="actions"><button class="btn">💾 ذخیره</button></div>
</form>
<?php admin_foot();
