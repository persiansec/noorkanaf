<?php
define('ROOT', dirname(__DIR__));
require ROOT . '/config.php';
require __DIR__ . '/jdate.php';

date_default_timezone_set('Asia/Tehran');
mb_internal_encoding('UTF-8');
ini_set('display_errors', DEBUG ? '1' : '0');
error_reporting(DEBUG ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE);

/* آدرس پایه سایت (کار می‌کند حتی اگر داخل زیرپوشه باشد) */
$__b = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if (substr($__b, -6) === '/admin') $__b = substr($__b, 0, -6);
define('SITE_URL', $__b);
function is_https(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

/* ───────── کمک‌کننده‌های عمومی ───────── */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function fa_digits(string $s): string { return strtr($s, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']); }
function en_digits(string $s): string {
    return strtr($s, ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
                      '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
}
/** ۱۲۰٬۰۰۰ تومان */
function fa_price(int $n): string { return fa_digits(number_format($n)) . ' تومان'; }
function fa_num($n): string { return fa_digits(number_format((float)$n, 0)); }
function fa_rating(float $r): string { return str_replace('.', '٫', fa_digits(number_format($r, 1))); }
function stars(float $r): string { $f = (int)round($r); return str_repeat('★', $f) . str_repeat('☆', 5 - $f); }
function tel_href(string $s): string { return 'tel:' . preg_replace('/[^\d+]/', '', en_digits($s)); }
/** فقط لینک‌های امن (جلوگیری از javascript:) */
function safe_url(string $u): string {
    $u = trim($u);
    return preg_match('~^(#[\w\-]*|/[^\s]*|https?://[^\s]+|tel:[\d+]+|mailto:[^\s]+)$~i', $u) ? $u : '';
}
function safe_color(string $c, string $d = '#E0E7FF'): string { return preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? $c : $d; }
function norm_fa(string $s): string { return mb_strtolower(strtr($s, ['ي'=>'ی','ك'=>'ک','ۀ'=>'ه'])); }
function redirect(string $to): never { header('Location: ' . $to); exit; }
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45); }

/* ───────── هدرهای امنیتی ───────── */
function send_headers(bool $noindex = false): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
    if (is_https()) header('Strict-Transport-Security: max-age=15552000');
    if ($noindex) header('X-Robots-Tag: noindex, nofollow');
}

/* ───────── پایگاه داده (SQLite) ───────── */
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dir = ROOT . '/data';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $found = glob($dir . '/kenaf_*.sqlite');
    $file = $found[0] ?? null;
    $fresh = !$file;
    if ($fresh) $file = $dir . '/kenaf_' . bin2hex(random_bytes(8)) . '.sqlite';
    $pdo = new PDO('sqlite:' . $file, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys=ON; PRAGMA busy_timeout=4000;');
    if ($fresh) { @chmod($file, 0600); install_db($pdo); }
    return $pdo;
}
function q(string $sql, array $p = []): PDOStatement { $st = db()->prepare($sql); $st->execute($p); return $st; }
function rows(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function row(string $sql, array $p = []): ?array { $r = q($sql, $p)->fetch(); return $r ?: null; }
function val(string $sql, array $p = []) { $r = q($sql, $p)->fetchColumn(); return $r === false ? null : $r; }

function install_db(PDO $db): void {
    $db->exec("
    CREATE TABLE settings(k TEXT PRIMARY KEY, v TEXT NOT NULL DEFAULT '');
    CREATE TABLE admins(id INTEGER PRIMARY KEY, username TEXT UNIQUE NOT NULL, pass TEXT NOT NULL);
    CREATE TABLE throttle(ip TEXT, kind TEXT, ts INTEGER);
    CREATE INDEX th_idx ON throttle(ip, kind, ts);
    CREATE TABLE slides(id INTEGER PRIMARY KEY, eyebrow TEXT DEFAULT '', title TEXT NOT NULL, body TEXT DEFAULT '',
      btn1_label TEXT DEFAULT '', btn1_link TEXT DEFAULT '', btn2_label TEXT DEFAULT '', btn2_link TEXT DEFAULT '',
      emoji TEXT DEFAULT '', image TEXT DEFAULT '', theme INTEGER DEFAULT 1, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1);
    CREATE TABLE categories(id INTEGER PRIMARY KEY, name TEXT NOT NULL, emoji TEXT DEFAULT '', sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1);
    CREATE TABLE products(id INTEGER PRIMARY KEY, category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE RESTRICT,
      name TEXT NOT NULL, emoji TEXT DEFAULT '📦', image TEXT DEFAULT '', g1 TEXT DEFAULT '#E0E7FF', g2 TEXT DEFAULT '#C7D2FE',
      tag TEXT DEFAULT '', hot INTEGER DEFAULT 0, rating REAL DEFAULT 5, description TEXT DEFAULT '', specs TEXT DEFAULT '',
      sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1);
    CREATE TABLE subproducts(id INTEGER PRIMARY KEY, product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
      name TEXT NOT NULL, unit TEXT DEFAULT '', price_type TEXT NOT NULL DEFAULT 'call', price INTEGER NOT NULL DEFAULT 0,
      sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1, updated_at INTEGER NOT NULL DEFAULT 0);
    CREATE INDEX sp_idx ON subproducts(product_id);
    CREATE TABLE comments(id INTEGER PRIMARY KEY, product_id INTEGER REFERENCES products(id) ON DELETE SET NULL,
      name TEXT NOT NULL, role TEXT DEFAULT '', body TEXT NOT NULL, rating INTEGER DEFAULT 5, status INTEGER DEFAULT 0, created_at INTEGER NOT NULL);
    CREATE TABLE messages(id INTEGER PRIMARY KEY, name TEXT NOT NULL, phone TEXT DEFAULT '', subject TEXT DEFAULT '',
      body TEXT DEFAULT '', items TEXT DEFAULT '', is_read INTEGER DEFAULT 0, created_at INTEGER NOT NULL);
    CREATE TABLE faqs(id INTEGER PRIMARY KEY, q TEXT NOT NULL, a TEXT NOT NULL, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1);
    CREATE TABLE features(id INTEGER PRIMARY KEY, icon TEXT DEFAULT '', title TEXT NOT NULL, body TEXT DEFAULT '', sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1);
    CREATE TABLE stats(id INTEGER PRIMARY KEY, num INTEGER DEFAULT 0, suffix TEXT DEFAULT '', label TEXT NOT NULL, sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1);
    CREATE TABLE about_items(id INTEGER PRIMARY KEY, title TEXT NOT NULL, body TEXT DEFAULT '', sort INTEGER DEFAULT 0, active INTEGER DEFAULT 1);
    ");
    $seed = json_decode((string)file_get_contents(__DIR__ . '/seed.json'), true) ?: [];
    $db->beginTransaction();
    $ins = fn(string $t, array $r) => $db->prepare("INSERT INTO $t (" . implode(',', array_keys($r)) . ') VALUES (' . implode(',', array_fill(0, count($r), '?')) . ')')->execute(array_values($r));
    foreach (($seed['settings'] ?? []) as $k => $v) $ins('settings', ['k' => $k, 'v' => $v]);
    $ins('settings', ['k' => 'app_secret', 'v' => bin2hex(random_bytes(32))]);
    foreach (['slides','features','stats','about_items','faqs'] as $t)
        foreach (($seed[$t] ?? []) as $i => $r) $ins($t, $r + ['sort' => $i + 1]);
    $slug = [];
    foreach (($seed['categories'] ?? []) as $i => [$s, $n, $em]) { $ins('categories', ['name' => $n, 'emoji' => $em, 'sort' => $i + 1]); $slug[$s] = (int)$db->lastInsertId(); }
    foreach (($seed['products'] ?? []) as $i => $p) {
        $vars = $p['variants']; $cat = $p['cat']; unset($p['variants'], $p['cat']);
        $ins('products', $p + ['category_id' => $slug[$cat], 'sort' => $i + 1]);
        $pid = (int)$db->lastInsertId();
        foreach ($vars as $j => $v) $ins('subproducts', ['product_id' => $pid, 'name' => $v['name'], 'unit' => $v['unit'], 'price_type' => $v['type'], 'price' => $v['price'], 'sort' => $j + 1, 'updated_at' => time()]);
    }
    foreach (($seed['comments'] ?? []) as $c) $ins('comments', $c + ['status' => 1, 'created_at' => time() - rand(5, 90) * 86400]);
    $db->commit();
}

/* ───────── تنظیمات ───────── */
function setting(string $k, string $d = ''): string {
    static $all = null;
    if ($all === null) $all = array_column(rows('SELECT k, v FROM settings'), 'v', 'k');
    return $all[$k] ?? $d;
}
function set_setting(string $k, string $v): void {
    q('INSERT INTO settings(k,v) VALUES(?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v', [$k, $v]);
}

/* ───────── محدودیت تعداد درخواست ───────── */
function throttle_count(string $kind, int $window): int {
    return (int)val('SELECT COUNT(*) FROM throttle WHERE ip=? AND kind=? AND ts>?', [client_ip(), $kind, time() - $window]);
}
function throttle_hit(string $kind): void {
    q('INSERT INTO throttle(ip,kind,ts) VALUES(?,?,?)', [client_ip(), $kind, time()]);
    if (random_int(1, 20) === 1) q('DELETE FROM throttle WHERE ts<?', [time() - 86400]);
}

/* ───────── توکن فرم‌های عمومی (بدون سشن) ───────── */
function form_token(): string {
    $ts = (string)time();
    return $ts . '.' . hash_hmac('sha256', $ts, setting('app_secret'));
}
function form_token_ok(string $t): bool {
    [$ts, $sig] = array_pad(explode('.', $t, 2), 2, '');
    if (!ctype_digit($ts) || !hash_equals(hash_hmac('sha256', $ts, setting('app_secret')), $sig)) return false;
    $age = time() - (int)$ts;
    return $age >= 2 && $age <= 43200;
}

/* ───────── سشن و احراز هویت مدیر ───────── */
function admin_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $path = ROOT . '/data/sessions';
    if (!is_dir($path)) @mkdir($path, 0700, true);
    if (is_dir($path) && is_writable($path)) session_save_path($path);
    session_name('kenaf_admin');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax']);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}
function admin_required(): void {
    admin_session();
    if (empty($_SESSION['aid']) || time() - (int)($_SESSION['last'] ?? 0) > 7200) {
        $_SESSION = [];
        redirect(SITE_URL . '/admin/login.php');
    }
    $_SESSION['last'] = time();
}
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    if (!hash_equals((string)($_SESSION['csrf'] ?? ''), (string)($_POST['_csrf'] ?? ''))) {
        http_response_code(419);
        exit('نشست منقضی شده است. صفحه را دوباره باز کنید.');
    }
}
function ensure_admin(): void {
    if ((int)val('SELECT COUNT(*) FROM admins') === 0 && INITIAL_ADMIN_PASS !== '') {
        q('INSERT INTO admins(username,pass) VALUES(?,?)', [INITIAL_ADMIN_USER, password_hash(INITIAL_ADMIN_PASS, PASSWORD_DEFAULT)]);
    }
}

/* ───────── آپلود تصویر ───────── */
function save_image(array $f): ?string {
    $err = $f['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_NO_FILE) return null;
    if ($err !== UPLOAD_ERR_OK) throw new RuntimeException('آپلود فایل ناموفق بود.');
    if ($f['size'] > 3 * 1024 * 1024) throw new RuntimeException('حجم تصویر نباید بیشتر از ۳ مگابایت باشد.');
    $info = @getimagesize($f['tmp_name']);
    $map = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    if (!$info || !isset($map[$info[2]])) throw new RuntimeException('فقط تصاویر JPG، PNG، WebP و GIF مجاز است.');
    $ext = $map[$info[2]];
    $rel = 'uploads/' . bin2hex(random_bytes(10)) . '.' . $ext;
    $dest = ROOT . '/' . $rel;
    $ok = false;
    // بازنویسی با GD (در صورت وجود): حذف داده‌های پنهان + کاهش حجم
    if ($ext !== 'gif' && function_exists('imagecreatefromstring')) {
        $im = @imagecreatefromstring((string)file_get_contents($f['tmp_name']));
        if ($im) {
            if (imagesx($im) > 1600 && ($sc = imagescale($im, 1600))) $im = $sc;
            if ($ext === 'png') { imagealphablending($im, false); imagesavealpha($im, true); $ok = imagepng($im, $dest, 6); }
            elseif ($ext === 'webp' && function_exists('imagewebp')) $ok = imagewebp($im, $dest, 85);
            elseif ($ext === 'jpg') $ok = imagejpeg($im, $dest, 86);
        }
    }
    if (!$ok && !move_uploaded_file($f['tmp_name'], $dest)) throw new RuntimeException('ذخیره تصویر ممکن نشد. دسترسی پوشه uploads را بررسی کنید.');
    @chmod($dest, 0644);
    return $rel;
}
function delete_image(?string $rel): void {
    if ($rel && preg_match('~^uploads/[a-f0-9]{20}\.(jpg|png|webp|gif)$~', $rel)) @unlink(ROOT . '/' . $rel);
}
