<?php
require __DIR__ . '/_layout.php';
admin_required();
$c = fn(string $t, string $w = '1') => (int)val("SELECT COUNT(*) FROM $t WHERE $w");
$cards = [
  ['📦', 'محصولات', $c('products'), 'crud.php?e=products'], ['🧩', 'زیرمحصولات', $c('subproducts'), 'prices.php'],
  ['✉️', 'پیام‌های خوانده‌نشده', $c('messages', 'is_read=0'), 'crud.php?e=messages'], ['💬', 'نظرات در انتظار تأیید', $c('comments', 'status=0'), 'crud.php?e=comments'],
  ['🖼️', 'اسلایدها', $c('slides'), 'crud.php?e=slides'], ['🗂️', 'دسته‌بندی‌ها', $c('categories'), 'crud.php?e=categories'],
];
$latest = rows('SELECT * FROM messages ORDER BY created_at DESC LIMIT 6');
admin_head('داشبورد', 'dashboard'); ?>
<div class="grid"><?php foreach ($cards as [$ic, $l, $n, $h]): ?>
  <a class="stat" href="<?= e($h) ?>"><span class="ic"><?= $ic ?></span><b><?= fa_num($n) ?></b><small><?= e($l) ?></small></a><?php endforeach; ?></div>
<section class="card"><h2>آخرین پیام‌ها و استعلام‌ها</h2>
<?php if (!$latest): ?><p class="muted">هنوز پیامی دریافت نشده است.</p><?php else: ?>
<div class="tbl-wrap"><table><thead><tr><th>نام</th><th>تلفن</th><th>موضوع</th><th>تاریخ</th><th></th></tr></thead><tbody>
<?php foreach ($latest as $m): ?><tr class="<?= $m['is_read'] ? '' : 'unread' ?>"><td><?= e($m['name']) ?></td>
<td dir="ltr" class="r"><a href="<?= e(tel_href($m['phone'])) ?>"><?= e($m['phone']) ?></a></td><td><?= e($m['subject']) ?></td><td><?= e(fa_date((int)$m['created_at'], true)) ?></td>
<td><a class="btn sm" href="crud.php?e=messages&id=<?= $m['id'] ?>">مشاهده</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="card"><h2>راهنمای سریع</h2><ul class="tips">
<li>قیمت روزانه را از «ویرایش سریع قیمت‌ها» تغییر دهید.</li>
<li>نظرهای ثبت‌شده توسط بازدیدکنندگان تا زمانی که تأیید نکنید در سایت نمایش داده نمی‌شوند.</li>
<li>برای هر محصول چند زیرمحصول (سایز/مدل) با قیمت مستقیم یا «تماس بگیرید» تعریف کنید.</li></ul></section>
<?php admin_foot();
