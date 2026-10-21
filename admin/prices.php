<?php
require __DIR__ . '/_layout.php';
admin_required();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $n = 0; $bad = 0;
    foreach ((array)($_POST['s'] ?? []) as $sid => $d) {
        $s = row('SELECT * FROM subproducts WHERE id=?', [(int)$sid]);
        if (!$s || !is_array($d)) continue;
        $type = ($d['type'] ?? '') === 'direct' ? 'direct' : 'call';
        $price = $type === 'direct' ? money_val($d['price'] ?? '') : 0;
        if ($type === 'direct' && $price <= 0) { $bad++; continue; }
        if ($s['price_type'] !== $type || (int)$s['price'] !== $price) {
            q('UPDATE subproducts SET price_type=?, price=?, updated_at=? WHERE id=?', [$type, $price, time(), $s['id']]);
            $n++;
        }
    }
    flash(fa_num($n) . ' قیمت به‌روزرسانی شد.' . ($bad ? ' (' . fa_num($bad) . ' مورد بدون مبلغ نادیده گرفته شد)' : ''), $bad ? 'err' : 'ok');
    redirect('prices.php');
}
$list = rows('SELECT s.*, p.name AS pname, c.name AS cname FROM subproducts s JOIN products p ON p.id=s.product_id JOIN categories c ON c.id=p.category_id ORDER BY c.sort, p.sort, p.id, s.sort, s.id');
admin_head('ویرایش سریع قیمت‌ها', 'prices'); ?>
<p class="muted">همه قیمت‌ها را یک‌جا ویرایش کنید. زیر هر مبلغ، معادل حروفی آن نمایش داده می‌شود. فقط ردیف‌هایی که تغییر کرده‌اند ثبت می‌شوند.</p>
<form method="post"><?= csrf_field() ?>
<div class="card tbl-wrap"><table><thead><tr><th>دسته</th><th>محصول</th><th>زیرمحصول</th><th>نوع قیمت</th><th>مبلغ (تومان)</th><th>آخرین تغییر</th></tr></thead><tbody>
<?php foreach ($list as $s): ?>
<tr class="<?= $s['active'] ? '' : 'off' ?>"><td><?= e($s['cname']) ?></td><td><?= e($s['pname']) ?></td><td><?= e($s['name']) ?> <small class="muted"><?= e($s['unit']) ?></small></td>
<td><select name="s[<?= $s['id'] ?>][type]" class="ptype"><option value="direct" <?= $s['price_type'] === 'direct' ? 'selected' : '' ?>>قیمت مستقیم</option><option value="call" <?= $s['price_type'] === 'call' ? 'selected' : '' ?>>تماس بگیرید</option></select></td>
<td><?= price_input('s[' . $s['id'] . '][price]', $s['price']) ?></td><td class="muted"><?= e(fa_date((int)$s['updated_at'])) ?></td></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="6" class="empty">زیرمحصولی ثبت نشده است.</td></tr><?php endif; ?>
</tbody></table></div>
<div class="actions sticky"><button class="btn">💾 ذخیره قیمت‌ها</button></div></form>
<?php admin_foot();
