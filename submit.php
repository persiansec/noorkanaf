<?php
require __DIR__ . '/inc/core.php';
send_headers(true);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(bool $ok, string $msg): never { echo json_encode(['ok' => $ok, 'msg' => $msg], JSON_UNESCAPED_UNICODE); exit; }
function clean(string $k, int $max): string {
    $v = (string)($_POST[$k] ?? '');
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $v) ?? '';
    return mb_substr(trim($v), 0, $max);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); out(false, 'درخواست نامعتبر است.'); }
if (!form_token_ok((string)($_POST['token'] ?? ''))) out(false, 'اعتبار فرم تمام شده است؛ لطفاً صفحه را رفرش کنید.');
if (clean('website', 50) !== '') out(true, 'ثبت شد.');   // ربات (honeypot)

$type = clean('type', 20);
try {
    if ($type === 'contact' || $type === 'inquiry') {
        if (throttle_count('msg', 3600) >= 5) out(false, 'تعداد درخواست‌های شما زیاد است؛ لطفاً کمی بعد یا با تلفن تماس بگیرید.');
        $name = clean('name', 80);
        $phone = preg_replace('/\D/', '', en_digits(clean('phone', 30)));
        if (mb_strlen($name) < 2) out(false, 'نام خود را وارد کنید.');
        if (strlen($phone) < 7 || strlen($phone) > 15) out(false, 'شماره تماس معتبر وارد کنید.');
        if ($type === 'contact') {
            $subjects = array_filter(array_map('trim', explode("\n", setting('contact_subjects'))));
            $subject = clean('subject', 80);
            if (!in_array($subject, $subjects, true)) $subject = 'سایر موارد';
            $body = clean('body', 2000); $items = '';
        } else {
            $subject = 'لیست استعلام'; $body = clean('body', 500); $items = clean('items', 3000);
            if ($items === '') out(false, 'لیست استعلام خالی است.');
        }
        q('INSERT INTO messages(name,phone,subject,body,items,is_read,created_at) VALUES(?,?,?,?,?,0,?)', [$name, $phone, $subject, $body, $items, time()]);
        throttle_hit('msg');
        out(true, 'درخواست شما ثبت شد؛ به‌زودی با شما تماس می‌گیریم ✅');
    }
    if ($type === 'comment') {
        if (throttle_count('cmt', 3600) >= 3) out(false, 'تعداد نظرات ثبت‌شده از سوی شما زیاد است؛ بعداً تلاش کنید.');
        $name = clean('name', 60); $body = clean('body', 1000); $role = clean('role', 80);
        $rating = max(1, min(5, (int)en_digits(clean('rating', 2))));
        $pid = (int)clean('product_id', 10);
        if ($pid && !val('SELECT id FROM products WHERE id=?', [$pid])) $pid = 0;
        if (mb_strlen($name) < 2) out(false, 'نام خود را وارد کنید.');
        if (mb_strlen($body) < 5) out(false, 'متن نظر خیلی کوتاه است.');
        q('INSERT INTO comments(product_id,name,role,body,rating,status,created_at) VALUES(?,?,?,?,?,0,?)', [$pid ?: null, $name, $role, $body, $rating, time()]);
        throttle_hit('cmt');
        out(true, 'نظر شما ثبت شد و پس از تأیید نمایش داده می‌شود. سپاسگزاریم 🙏');
    }
} catch (Throwable $t) {
    out(false, DEBUG ? $t->getMessage() : 'خطای سرور؛ لطفاً دوباره تلاش کنید.');
}
out(false, 'درخواست نامعتبر است.');
