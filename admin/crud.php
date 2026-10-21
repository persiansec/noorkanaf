<?php
require __DIR__ . '/_layout.php';
admin_required();

$ents = entities();
$e = (string)($_GET['e'] ?? '');
if (!isset($ents[$e])) { http_response_code(404); exit('بخش مورد نظر پیدا نشد.'); }
$cfg = $ents[$e]; $table = $cfg['table']; $fields = $cfg['fields'];
$self = 'crud.php?e=' . urlencode($e);

function field_opts(array $f): array { $o = $f[2] ?? []; return is_string($o) ? $o() : $o; }

/* ───────────── عملیات POST ───────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $back = $self;
    try {
        if ($act === 'delete') {
            $old = row("SELECT * FROM $table WHERE id=?", [$id]);
            if ($old) {
                if (!empty($cfg['guard']) && (int)val($cfg['guard'][0], [$id]) > 0) throw new RuntimeException($cfg['guard'][1]);
                foreach ($fields as $k => $f) if ($f[1] === 'image') delete_image($old[$k] ?? '');
                q("DELETE FROM $table WHERE id=?", [$id]);
                flash('«' . $cfg['single'] . '» حذف شد.');
            }
        } elseif ($act === 'toggle' && !empty($cfg['toggle'])) {
            $c = $cfg['toggle'][0];
            q("UPDATE $table SET $c = 1 - $c WHERE id=?", [$id]);
            flash('وضعیت تغییر کرد.');
        } elseif ($act === 'save') {
            $old = $id ? row("SELECT * FROM $table WHERE id=?", [$id]) : null;
            if ($id && !$old) throw new RuntimeException('رکورد پیدا نشد.');
            $data = [];
            foreach ($fields as $k => $f) {
                $t = $f[1]; $raw = $_POST[$k] ?? '';
                if ($t === 'text') $data[$k] = mb_substr(trim((string)$raw), 0, 300);
                elseif ($t === 'textarea') $data[$k] = mb_substr(trim((string)$raw), 0, 5000);
                elseif ($t === 'url') $data[$k] = safe_url((string)$raw);
                elseif ($t === 'number') $data[$k] = (int)en_digits(trim((string)$raw));
                elseif ($t === 'decimal') $data[$k] = max(0, min(5, (float)str_replace(['٫', '،', ','], '.', en_digits(trim((string)$raw)))));
                elseif ($t === 'bool') $data[$k] = isset($_POST[$k]) ? 1 : 0;
                elseif ($t === 'color') $data[$k] = safe_color((string)$raw, $old[$k] ?? ($cfg['defaults'][$k] ?? '#E0E7FF'));
                elseif ($t === 'jdate') $data[$k] = jalali_to_ts((string)$raw, $old[$k] ?? null) ?? ($old[$k] ?? time());
                elseif ($t === 'select') {
                    $o = field_opts($f); $v = (string)$raw;
                    if (!array_key_exists($v, $o)) $v = (string)($old[$k] ?? ($cfg['defaults'][$k] ?? ''));
                    $data[$k] = $v === '' ? null : (int)$v;
                }
            }
            foreach (($cfg['req'] ?? []) as $k) if (($data[$k] ?? '') === '' || $data[$k] === null) throw new RuntimeException('فیلد «' . $fields[$k][0] . '» الزامی است.');
            foreach ($fields as $k => $f) if ($f[1] === 'image') {
                $new = save_image($_FILES[$k] ?? ['error' => UPLOAD_ERR_NO_FILE]);
                if ($new) { $data[$k] = $new; delete_image($old[$k] ?? ''); }
                elseif (isset($_POST[$k . '__del'])) { $data[$k] = ''; delete_image($old[$k] ?? ''); }
                else $data[$k] = $old[$k] ?? '';
            }
            if ($id) {
                q("UPDATE $table SET " . implode(',', array_map(fn($k) => "$k=?", array_keys($data))) . ' WHERE id=?', [...array_values($data), $id]);
            } else {
                q("INSERT INTO $table (" . implode(',', array_keys($data)) . ') VALUES (' . implode(',', array_fill(0, count($data), '?')) . ')', array_values($data));
                $id = (int)db()->lastInsertId();
            }
            flash('ذخیره شد ✅');
            if (($cfg['extra'] ?? '') === 'variants') $back = $self . '&id=' . $id;
        } elseif ($act === 'sub_save' && $e === 'products') {
            $pid = $id; $sid = (int)($_POST['sid'] ?? 0);
            $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 200);
            $type = ($_POST['price_type'] ?? '') === 'direct' ? 'direct' : 'call';
            $price = money_val($_POST['price'] ?? '');
            $back = $self . '&id=' . $pid;
            if ($name === '') throw new RuntimeException('نام زیرمحصول الزامی است.');
            if ($type === 'direct' && $price <= 0) throw new RuntimeException('برای قیمت مستقیم، مبلغ را وارد کنید.');
            if ($type === 'call') $price = 0;
            $vals = [$name, mb_substr(trim((string)($_POST['unit'] ?? '')), 0, 100), $type, $price, (int)en_digits((string)($_POST['sort'] ?? '0')), isset($_POST['active']) ? 1 : 0];
            $prev = $sid ? row('SELECT * FROM subproducts WHERE id=? AND product_id=?', [$sid, $pid]) : null;
            if ($prev) {
                $changed = $prev['price'] != $price || $prev['price_type'] !== $type;
                q('UPDATE subproducts SET name=?, unit=?, price_type=?, price=?, sort=?, active=?, updated_at=? WHERE id=?', [...$vals, $changed ? time() : $prev['updated_at'], $sid]);
            } else {
                q('INSERT INTO subproducts(name,unit,price_type,price,sort,active,product_id,updated_at) VALUES(?,?,?,?,?,?,?,?)', [...$vals, $pid, time()]);
            }
            flash('زیرمحصول ذخیره شد ✅');
        } elseif ($act === 'sub_delete' && $e === 'products') {
            q('DELETE FROM subproducts WHERE id=? AND product_id=?', [(int)($_POST['sid'] ?? 0), $id]);
            flash('زیرمحصول حذف شد.');
            $back = $self . '&id=' . $id;
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'err');
        if ($id && $act !== 'delete') $back = $self . '&id=' . $id; elseif ($act === 'save') $back = $self . '&id=new';
    } catch (PDOException $ex) {
        flash(DEBUG ? $ex->getMessage() : 'خطا در ثبت اطلاعات؛ دوباره تلاش کنید.', 'err');
    }
    redirect($back);
}

/* ───────────── نمایش ───────────── */
$idParam = $_GET['id'] ?? null;
$thumb = function (array $r) use ($cfg): string {
    if (empty($cfg['thumb'])) return '';
    $img = in_array('image', $cfg['thumb'], true) ? ($r['image'] ?? '') : '';
    $em = $r[$cfg['thumb'][0]] ?? '';
    return '<td class="th">' . ($img ? '<img src="' . SITE_URL . '/' . e($img) . '" alt="">' : e($em)) . '</td>';
};

if ($idParam !== null) {
    $id = $idParam === 'new' ? 0 : (int)$idParam;
    $r = $id ? row("SELECT * FROM $table WHERE id=?", [$id]) : ($cfg['defaults'] ?? []);
    if ($id && !$r) { flash('رکورد پیدا نشد.', 'err'); redirect($self); }
    if ($id && !empty($cfg['autoread']) && !$r[$cfg['autoread']]) q("UPDATE $table SET {$cfg['autoread']}=1 WHERE id=?", [$id]);
    admin_head(($id ? 'ویرایش ' : 'افزودن ') . $cfg['single'], $e);
    ?>
    <form method="post" action="<?= e($self) ?>" enctype="multipart/form-data" class="card form">
      <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $id ?>">
      <?php foreach ($fields as $k => $f):
        [$label, $t] = $f; $v = $r[$k] ?? ''; $req = in_array($k, $cfg['req'] ?? [], true); ?>
        <?php if ($t === 'bool'): ?>
          <label class="chk"><input type="checkbox" name="<?= e($k) ?>" <?= $v ? 'checked' : '' ?>> <?= e($label) ?></label>
        <?php else: ?>
        <div class="fld"><label for="f_<?= e($k) ?>"><?= e($label) ?><?= $req ? ' <i>*</i>' : '' ?></label>
          <?php if ($t === 'textarea'): ?>
            <textarea id="f_<?= e($k) ?>" name="<?= e($k) ?>" rows="<?= $k === 'specs' ? 6 : 4 ?>"><?= e($v) ?></textarea>
          <?php elseif ($t === 'select'): $o = field_opts($f); ?>
            <select id="f_<?= e($k) ?>" name="<?= e($k) ?>"><?php foreach ($o as $ov => $ol): ?>
              <option value="<?= e($ov) ?>" <?= (string)$v === (string)$ov ? 'selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?></select>
          <?php elseif ($t === 'color'): ?>
            <input type="color" id="f_<?= e($k) ?>" name="<?= e($k) ?>" value="<?= e(safe_color((string)$v)) ?>" class="clr">
          <?php elseif ($t === 'image'): ?>
            <?php if ($v): ?><div class="prev"><img src="<?= SITE_URL . '/' . e($v) ?>" alt=""><label class="chk"><input type="checkbox" name="<?= e($k) ?>__del"> حذف تصویر</label></div><?php endif; ?>
            <input type="file" id="f_<?= e($k) ?>" name="<?= e($k) ?>" accept="image/jpeg,image/png,image/webp,image/gif">
          <?php elseif ($t === 'jdate'): ?>
            <input id="f_<?= e($k) ?>" name="<?= e($k) ?>" value="<?= e(jalali_input((int)$v ?: time())) ?>" dir="ltr" placeholder="1405/07/24">
          <?php else: ?>
            <input id="f_<?= e($k) ?>" name="<?= e($k) ?>" value="<?= e($v) ?>" <?= $req ? 'required' : '' ?> <?= in_array($t, ['url', 'number', 'decimal'], true) ? 'dir="ltr"' : '' ?>>
          <?php endif; ?>
          <?php if (!empty($f[2]) && is_string($f[2]) && $t !== 'select'): ?><small><?= e($f[2]) ?></small><?php endif; ?>
        </div>
        <?php endif; ?>
      <?php endforeach; ?>
      <div class="actions"><button class="btn">💾 ذخیره</button><a class="btn ghost" href="<?= e($self) ?>">بازگشت</a></div>
    </form>

    <?php if (($cfg['extra'] ?? '') === 'variants'): ?>
      <section class="card">
        <h2>زیرمحصولات و قیمت‌ها</h2>
        <?php if (!$id): ?>
          <p class="muted">ابتدا محصول را ذخیره کنید؛ سپس می‌توانید زیرمحصولات (مدل‌ها/سایزها) و قیمت آن‌ها را اضافه کنید.</p>
        <?php else:
          $subs = rows('SELECT * FROM subproducts WHERE product_id=? ORDER BY sort, id', [$id]); ?>
          <p class="muted">هر زیرمحصول یک مدل/سایز از این کالاست. نوع قیمت: «قیمت مستقیم» مبلغ را در سایت نشان می‌دهد؛ «تماس بگیرید» فقط دکمه تماس نمایش می‌دهد.</p>
          <div class="tbl-wrap"><table class="subs"><thead><tr><th>نام</th><th>واحد</th><th>نوع قیمت</th><th>مبلغ (تومان)</th><th>ترتیب</th><th>فعال</th><th>آخرین تغییر قیمت</th><th></th></tr></thead><tbody>
          <?php foreach (array_merge($subs, [['id' => 0, 'name' => '', 'unit' => '', 'price_type' => 'direct', 'price' => 0, 'sort' => (count($subs) + 1) * 10, 'active' => 1, 'updated_at' => 0]]) as $s): ?>
            <tr class="<?= $s['id'] ? '' : 'new' ?>">
              <form method="post" action="<?= e($self) ?>" id="sf<?= $s['id'] ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="sid" value="<?= $s['id'] ?>"></form>
              <td><input form="sf<?= $s['id'] ?>" name="name" value="<?= e($s['name']) ?>" placeholder="<?= $s['id'] ? '' : 'زیرمحصول جدید…' ?>"></td>
              <td><input form="sf<?= $s['id'] ?>" name="unit" value="<?= e($s['unit']) ?>" placeholder="مثال: شاخه ۳ متری"></td>
              <td><select form="sf<?= $s['id'] ?>" name="price_type" class="ptype"><option value="direct" <?= $s['price_type'] === 'direct' ? 'selected' : '' ?>>قیمت مستقیم</option><option value="call" <?= $s['price_type'] === 'call' ? 'selected' : '' ?>>تماس بگیرید</option></select></td>
              <td><?= str_replace('<input ', '<input form="sf' . $s['id'] . '" ', price_input('price', $s['price'])) ?></td>
              <td><input form="sf<?= $s['id'] ?>" name="sort" value="<?= (int)$s['sort'] ?>" class="sm" dir="ltr"></td>
              <td><input form="sf<?= $s['id'] ?>" type="checkbox" name="active" <?= $s['active'] ? 'checked' : '' ?>></td>
              <td class="muted"><?= $s['id'] ? e(fa_date((int)$s['updated_at'])) : '—' ?></td>
              <td class="nw">
                <button form="sf<?= $s['id'] ?>" name="action" value="sub_save" class="btn sm"><?= $s['id'] ? 'ذخیره' : '➕ افزودن' ?></button>
                <?php if ($s['id']): ?><button form="sf<?= $s['id'] ?>" name="action" value="sub_delete" class="btn sm danger" data-confirm="این زیرمحصول حذف شود؟">حذف</button><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody></table></div>
        <?php endif; ?>
      </section>
    <?php endif;
    admin_foot(); exit;
}

/* ───────────── لیست ───────────── */
$per = 50; $page = max(1, (int)($_GET['page'] ?? 1));
$total = (int)val("SELECT COUNT(*) FROM $table");
$list = rows(($cfg['list_sql'] ?? "SELECT * FROM $table") . ' ORDER BY ' . $cfg['order'] . " LIMIT $per OFFSET " . (($page - 1) * $per));
admin_head($cfg['label'], $e);
?>
<div class="bar"><span class="muted">مجموع: <?= fa_num($total) ?> مورد</span>
  <a class="btn" href="<?= e($self) ?>&id=new">➕ افزودن <?= e($cfg['single']) ?></a></div>
<div class="card tbl-wrap"><table>
  <thead><tr><?= !empty($cfg['thumb']) ? '<th></th>' : '' ?><?php foreach ($cfg['list'] as $c): ?><th><?= e($c[0]) ?></th><?php endforeach; ?><th></th></tr></thead>
  <tbody>
  <?php foreach ($list as $r): ?>
    <tr class="<?= isset($r['is_read']) && !$r['is_read'] ? 'unread' : '' ?>">
      <?= $thumb($r) ?>
      <?php foreach ($cfg['list'] as $k => $c): $fmt = $c[1] ?? ''; $v = $r[$k] ?? ''; ?>
        <td><?php
          if ($fmt === 'bool') echo $v ? '<span class="ok">✔</span>' : '<span class="no">—</span>';
          elseif ($fmt === 'date') echo e(fa_date((int)$v, true));
          elseif ($fmt === 'trunc') echo e(mb_strimwidth((string)$v, 0, 70, '…'));
          elseif ($fmt === 'stars') echo '<span class="stars">' . e(stars((float)$v)) . '</span>';
          elseif (is_numeric($v) && $k !== 'phone') echo e(str_replace('.', '٫', fa_digits((string)$v)));
          else echo e($v);
        ?></td>
      <?php endforeach; ?>
      <td class="nw">
        <?php if (!empty($cfg['toggle'])): ?>
          <form method="post" action="<?= e($self) ?>" class="inl"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button class="btn sm ghost"><?= e($r[$cfg['toggle'][0]] ? $cfg['toggle'][2] : $cfg['toggle'][1]) ?></button></form>
        <?php endif; ?>
        <a class="btn sm" href="<?= e($self) ?>&id=<?= $r['id'] ?>">ویرایش</a>
        <form method="post" action="<?= e($self) ?>" class="inl"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>">
          <button class="btn sm danger" data-confirm="آیا از حذف این <?= e($cfg['single']) ?> مطمئن هستید؟">حذف</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$list): ?><tr><td colspan="9" class="empty">هنوز موردی ثبت نشده است.</td></tr><?php endif; ?>
  </tbody></table></div>
<?php if ($total > $per): ?><div class="pager"><?php for ($i = 1; $i <= ceil($total / $per); $i++): ?><a class="<?= $i === $page ? 'on' : '' ?>" href="<?= e($self) ?>&page=<?= $i ?>"><?= fa_num($i) ?></a><?php endfor; ?></div><?php endif; ?>
<?php admin_foot();
