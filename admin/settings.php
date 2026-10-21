<?php
require __DIR__ . '/_layout.php';
admin_required();

/* گروه‌ها: عنوان => [کلید => [برچسب, نوع(text|textarea|url|bool), راهنما]] */
$groups = [
 '🏷️ هویت سایت' => [
   'logo_mark' => ['حرف/نماد لوگو', 'text'], 'logo_a' => ['نام فروشگاه (بخش اول)', 'text'], 'logo_b' => ['نام فروشگاه (بخش رنگی)', 'text'],
   'meta_title' => ['عنوان سایت (Title)', 'text'], 'meta_desc' => ['توضیح سایت برای گوگل', 'textarea']],
 '📢 نوار بالای سایت' => [
   'topbar_on' => ['نمایش نوار بالا', 'bool'], 'topbar_1' => ['متن اول', 'text'], 'topbar_2' => ['متن دوم', 'text']],
 '📞 تماس و آدرس' => [
   'phone' => ['تلفن فروش', 'text'], 'mobile' => ['موبایل / واتساپ', 'text'],
   'address' => ['آدرس کامل', 'textarea'], 'address_short' => ['آدرس کوتاه (فوتر)', 'text'],
   'hours' => ['ساعات کاری', 'text'], 'hours_short' => ['ساعات کاری (فوتر)', 'text'],
   'map_link' => ['لینک نقشه (اختیاری)', 'url', 'مثال: https://maps.app.goo.gl/…']],
 '🔗 شبکه‌های اجتماعی (خالی = نمایش داده نمی‌شود)' => [
   'instagram' => ['اینستاگرام', 'url'], 'whatsapp' => ['واتساپ', 'url', 'مثال: https://wa.me/989123456789'],
   'telegram' => ['تلگرام', 'url'], 'linkedin' => ['لینکدین', 'url']],
 '🗂️ عنوان بخش‌ها' => [
   'cat_eyebrow' => ['دسته‌بندی — برچسب', 'text'], 'cat_title' => ['دسته‌بندی — عنوان', 'text'], 'cat_text' => ['دسته‌بندی — توضیح', 'text'],
   'prod_eyebrow' => ['محصولات — برچسب', 'text'], 'prod_title' => ['محصولات — عنوان', 'text'], 'prod_text' => ['محصولات — توضیح', 'textarea'],
   'testi_eyebrow' => ['نظرات — برچسب', 'text'], 'testi_title' => ['نظرات — عنوان', 'text'], 'testi_text' => ['نظرات — توضیح', 'text'],
   'faq_eyebrow' => ['سوالات — برچسب', 'text'], 'faq_title' => ['سوالات — عنوان', 'text'], 'faq_text' => ['سوالات — توضیح', 'text']],
 'ℹ️ درباره ما' => [
   'about_eyebrow' => ['برچسب', 'text'], 'about_title' => ['عنوان', 'text'], 'about_text' => ['متن', 'textarea'],
   'about_emoji' => ['ایموجی بخش تصویر', 'text'], 'about_card_title' => ['کارت شناور — عنوان', 'text'], 'about_card_text' => ['کارت شناور — متن', 'text'],
   'about_btn' => ['متن دکمه', 'text']],
 '✉️ فرم تماس' => [
   'contact_eyebrow' => ['برچسب', 'text'], 'contact_title' => ['عنوان', 'text'], 'contact_text' => ['توضیح', 'text'],
   'contact_subjects' => ['گزینه‌های «موضوع» فرم', 'textarea', 'هر خط یک گزینه']],
 '🦶 فوتر' => [
   'footer_text' => ['متن معرفی', 'textarea'], 'footer_copy' => ['متن کپی‌رایت', 'text', 'عبارت {year} با سال شمسی جاری جایگزین می‌شود'],
   'footer_credit' => ['متن پایین فوتر', 'text']],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($groups as $g) foreach ($g as $k => $f) {
        $t = $f[1]; $v = $_POST[$k] ?? '';
        if ($t === 'bool') $v = isset($_POST[$k]) ? '1' : '0';
        elseif ($t === 'url') $v = safe_url((string)$v);
        else $v = mb_substr(trim((string)$v), 0, $t === 'textarea' ? 3000 : 400);
        set_setting($k, $v);
    }
    flash('تنظیمات ذخیره شد ✅');
    redirect('settings.php');
}
admin_head('تنظیمات سایت', 'settings'); ?>
<form method="post" class="form"><?= csrf_field() ?>
<?php foreach ($groups as $title => $g): ?>
  <section class="card"><h2><?= e($title) ?></h2>
  <?php foreach ($g as $k => $f): [$label, $t] = $f; $v = setting($k); ?>
    <?php if ($t === 'bool'): ?>
      <label class="chk"><input type="checkbox" name="<?= e($k) ?>" <?= $v === '1' ? 'checked' : '' ?>> <?= e($label) ?></label>
    <?php else: ?>
      <div class="fld"><label for="s_<?= e($k) ?>"><?= e($label) ?></label>
      <?php if ($t === 'textarea'): ?><textarea id="s_<?= e($k) ?>" name="<?= e($k) ?>" rows="3"><?= e($v) ?></textarea>
      <?php else: ?><input id="s_<?= e($k) ?>" name="<?= e($k) ?>" value="<?= e($v) ?>" <?= $t === 'url' ? 'dir="ltr"' : '' ?>><?php endif; ?>
      <?php if (!empty($f[2])): ?><small><?= e($f[2]) ?></small><?php endif; ?></div>
    <?php endif; ?>
  <?php endforeach; ?></section>
<?php endforeach; ?>
<div class="actions sticky"><button class="btn">💾 ذخیره همه تنظیمات</button></div>
</form>
<?php admin_foot();
