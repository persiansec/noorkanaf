<?php
require __DIR__ . '/../inc/core.php';
require __DIR__ . '/../inc/entities.php';

function flash(string $msg, string $type = 'ok'): void { $_SESSION['flash'][] = [$type, $msg]; }

function admin_head(string $title, string $active = ''): void {
    send_headers(true);
    header('Cache-Control: no-store');
    $unread = (int)val('SELECT COUNT(*) FROM messages WHERE is_read=0');
    $pending = (int)val('SELECT COUNT(*) FROM comments WHERE status=0');
    $nav = [['index.php', 'dashboard', '🏠', 'داشبورد', 0], ['settings.php', 'settings', '⚙️', 'تنظیمات سایت', 0]];
    foreach (entities() as $k => $c) {
        $nav[] = ['crud.php?e=' . $k, $k, $c['icon'], $c['label'], $k === 'messages' ? $unread : ($k === 'comments' ? $pending : 0)];
        if ($k === 'products') $nav[] = ['prices.php', 'prices', '💰', 'ویرایش سریع قیمت‌ها', 0];
    }
    $nav[] = ['password.php', 'password', '🔑', 'حساب کاربری', 0];
    ?><!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> | پنل مدیریت</title><meta name="robots" content="noindex,nofollow">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/admin.css?v=<?= filemtime(ROOT . '/assets/admin.css') ?>">
</head><body>
<aside class="side" id="side">
  <div class="brand"><span class="mark"><?= e(setting('logo_mark', 'K')) ?></span> پنل مدیریت</div>
  <nav>
  <?php foreach ($nav as [$href, $key, $ic, $label, $badge]): ?>
    <a href="<?= e($href) ?>" class="<?= $active === $key ? 'on' : '' ?>"><span><?= $ic ?></span><?= e($label) ?>
      <?php if ($badge): ?><b class="bdg"><?= fa_num($badge) ?></b><?php endif; ?></a>
  <?php endforeach; ?>
  </nav>
  <div class="side-foot"><a href="<?= SITE_URL ?>/" target="_blank" rel="noopener">🌐 مشاهده سایت</a>
    <form method="post" action="logout.php"><?= csrf_field() ?><button>🚪 خروج</button></form></div>
</aside>
<main class="main">
  <header class="top"><button class="burger" id="burger" aria-label="منو">☰</button><h1><?= e($title) ?></h1>
    <span class="today">امروز: <?= e(fa_date(time())) ?></span></header>
  <?php foreach (($_SESSION['flash'] ?? []) as [$t, $m]): ?><div class="flash <?= e($t) ?>"><?= e($m) ?></div><?php endforeach; $_SESSION['flash'] = []; ?>
<?php }

function admin_foot(): void { ?>
</main>
<script src="<?= SITE_URL ?>/assets/admin.js?v=<?= filemtime(ROOT . '/assets/admin.js') ?>" defer></script>
</body></html>
<?php }

/** ورودی قیمت با پیش‌نمایش حروفی */
function price_input(string $name, $value): string {
    $v = (int)$value;
    return '<div class="price-wrap"><input type="text" inputmode="numeric" class="price-in" name="' . e($name) . '" value="' . ($v ? e(number_format($v)) : '') . '" autocomplete="off" placeholder="مثال: 120,000"><small class="words"></small></div>';
}
function money_val($s): int { return (int)preg_replace('/\D/', '', en_digits((string)$s)); }
