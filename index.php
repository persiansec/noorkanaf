<?php
require __DIR__ . '/inc/core.php';
send_headers();

/* ───── بارگذاری داده‌ها ───── */
$S = 'setting';
$slides = rows('SELECT * FROM slides WHERE active=1 ORDER BY sort, id');
$features = rows('SELECT * FROM features WHERE active=1 ORDER BY sort, id');
$stats = rows('SELECT * FROM stats WHERE active=1 ORDER BY sort, id');
$aboutItems = rows('SELECT * FROM about_items WHERE active=1 ORDER BY sort, id');
$faqs = rows('SELECT * FROM faqs WHERE active=1 ORDER BY sort, id');
$cats = rows('SELECT * FROM categories WHERE active=1 ORDER BY sort, id');
$catName = array_column($cats, 'name', 'id');

$varsBy = [];
foreach (rows('SELECT * FROM subproducts WHERE active=1 ORDER BY sort, id') as $v) $varsBy[$v['product_id']][] = $v;
$comBy = [];
foreach (rows('SELECT * FROM comments WHERE status=1 AND product_id IS NOT NULL ORDER BY created_at DESC') as $c) $comBy[$c['product_id']][] = $c;

$products = [];
foreach (rows('SELECT * FROM products WHERE active=1 ORDER BY sort, id') as $p) {
    if (!isset($catName[$p['category_id']])) continue;
    $vs = [];
    $upd = 0;
    foreach ($varsBy[$p['id']] ?? [] as $v) {
        $direct = $v['price_type'] === 'direct' && $v['price'] > 0;
        if ($direct) $upd = max($upd, (int)$v['updated_at']);
        $vs[] = ['id' => (int)$v['id'], 'name' => $v['name'], 'unit' => $v['unit'], 'type' => $direct ? 'direct' : 'call',
                 'price' => $direct ? (int)$v['price'] : 0, 'priceText' => $direct ? fa_price((int)$v['price']) : ''];
    }
    $specs = [];
    foreach (preg_split('/\R/u', (string)$p['specs']) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $pos = mb_strpos($line, ':');
        $specs[] = $pos === false ? ['', $line] : [trim(mb_substr($line, 0, $pos)), trim(mb_substr($line, $pos + 1))];
    }
    $direct = array_filter(array_column($vs, 'price'));
    $products[] = [
        'id' => (int)$p['id'], 'name' => $p['name'], 'cat' => (int)$p['category_id'], 'catName' => $catName[$p['category_id']],
        'emoji' => $p['emoji'] ?: '📦', 'image' => $p['image'] ? SITE_URL . '/' . $p['image'] : '',
        'g1' => safe_color($p['g1']), 'g2' => safe_color($p['g2'], '#C7D2FE'),
        'tag' => $p['tag'], 'hot' => (int)$p['hot'], 'rating' => (float)$p['rating'], 'ratingText' => fa_rating((float)$p['rating']),
        'desc' => $p['description'], 'specs' => $specs, 'variants' => $vs,
        'min' => $direct ? min($direct) : 0, 'multi' => count($direct) > 1 && min($direct) !== max($direct),
        'updated' => $upd ? fa_date($upd) : '',
        'comments' => array_map(fn($c) => ['name' => $c['name'], 'role' => $c['role'], 'body' => $c['body'], 'rating' => (int)$c['rating'], 'date' => fa_date((int)$c['created_at'])], array_slice($comBy[$p['id']] ?? [], 0, 8)),
    ];
}
$countBy = array_count_values(array_column($products, 'cat'));
$testis = rows('SELECT c.*, p.name AS pname FROM comments c LEFT JOIN products p ON p.id=c.product_id WHERE c.status=1 ORDER BY c.created_at DESC LIMIT 6');

$phone = $S('phone'); $mobile = $S('mobile');
$tel = tel_href($phone);
$appData = ['products' => $products, 'tel' => $tel, 'token' => form_token(), 'submit' => SITE_URL . '/submit.php',
    'subjects' => array_values(array_filter(array_map('trim', explode("\n", $S('contact_subjects')))))];
$json = json_encode($appData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

function hl(string $s): string { return preg_replace('/\*([^*]+)\*/u', '<span class="grad">$1</span>', e($s)); }
function fa(string $s): string { return e(fa_digits($s)); }
$ARROW = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>';
$PHONE = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.5 2.8.6a2 2 0 0 1 1.7 2z"/></svg>';
$PLUS = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>';
$CLOSE = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>';
$SEND = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/></svg>';
$logo = '<span class="logo-mark">' . e($S('logo_mark', 'K')) . '</span><span class="logo-text">' . e($S('logo_a')) . '<em>' . e($S('logo_b')) . '</em></span>';
$ver = fn($f) => filemtime(__DIR__ . '/assets/' . $f);
?><!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($S('meta_title')) ?></title>
<meta name="description" content="<?= e($S('meta_desc')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/site.css?v=<?= $ver('site.css') ?>">
</head>
<body>

<?php if ($S('topbar_on') === '1' && ($S('topbar_1') || $S('topbar_2'))): ?>
<div class="topbar" id="topbar"><div class="container topbar-inner">
  <?php if ($S('topbar_1')): ?><span><?= e($S('topbar_1')) ?></span><?php endif; ?>
  <?php if ($S('topbar_1') && $S('topbar_2')): ?><span class="dot-sep">•</span><?php endif; ?>
  <?php if ($S('topbar_2')): ?><span><?= e($S('topbar_2')) ?></span><?php endif; ?>
  <button class="topbar-close" id="topbarClose" aria-label="بستن">✕</button>
</div></div>
<?php endif; ?>

<header class="header" id="header">
  <div class="container nav">
    <a href="#hero" class="logo"><?= $logo ?></a>
    <nav class="nav-links">
      <a href="#hero" class="active">خانه</a><a href="#categories">دسته‌بندی‌ها</a><a href="#products">محصولات</a>
      <a href="#about">درباره ما</a><a href="#faq">سوالات متداول</a><a href="#contact">تماس</a>
    </nav>
    <div class="nav-actions">
      <button class="icon-btn" id="themeBtn" aria-label="تغییر حالت روشن/تاریک">
        <svg class="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
        <svg class="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
      </button>
      <button class="icon-btn" id="searchBtn" aria-label="جست‌وجو"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg></button>
      <button class="icon-btn" id="inquiryBtn" aria-label="لیست استعلام"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 4 6v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6l-2-4z"/><path d="M4 6h16M16 10a4 4 0 0 1-8 0"/></svg><span class="badge" id="badge">۰</span></button>
      <button class="icon-btn menu-btn" id="menuBtn" aria-label="منو"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
    </div>
  </div>
  <div class="search-panel" id="searchPanel">
    <div class="search-box">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg>
      <input type="text" id="searchInput" placeholder="جست‌وجو در محصولات... مثلاً «پروفیل» یا «ورق ضدآب»">
      <button class="icon-btn" id="searchClose" aria-label="بستن جست‌وجو"><?= $CLOSE ?></button>
    </div>
    <p class="search-hint">برای بستن کلید <b>Esc</b> را بزنید</p>
  </div>
</header>

<aside class="mobile-menu" id="mobileMenu">
  <div class="mobile-head"><span class="logo"><?= $logo ?></span><button class="icon-btn" id="menuClose" aria-label="بستن"><?= $CLOSE ?></button></div>
  <a href="#hero">🏠 خانه</a><a href="#categories">🗂️ دسته‌بندی‌ها</a><a href="#products">📦 محصولات</a>
  <a href="#about">ℹ️ درباره ما</a><a href="#faq">❓ سوالات متداول</a><a href="#contact">📞 تماس با ما</a>
  <a href="<?= e($tel) ?>" class="btn btn-primary" style="margin-top:14px">تماس فوری: <?= fa($phone) ?></a>
</aside>

<main>
<?php if ($slides): ?>
<section class="hero" id="hero"><div class="container">
  <div class="carousel" id="carousel"><div class="slides" id="slides">
  <?php foreach ($slides as $s): $u1 = safe_url($s['btn1_link']); $u2 = safe_url($s['btn2_link']); ?>
    <div class="slide" data-v="<?= (int)$s['theme'] ?>">
      <div class="slide-inner">
        <?php if ($s['eyebrow']): ?><span class="slide-eyebrow"><?= e($s['eyebrow']) ?></span><?php endif; ?>
        <h2><?= hl($s['title']) ?></h2>
        <?php if ($s['body']): ?><p><?= e($s['body']) ?></p><?php endif; ?>
        <div class="slide-cta">
          <?php if ($u1 && $s['btn1_label']): ?><a href="<?= e($u1) ?>" class="btn btn-light"><?= e($s['btn1_label']) ?> <?= $ARROW ?></a><?php endif; ?>
          <?php if ($u2 && $s['btn2_label']): ?><a href="<?= e($u2) ?>" class="btn btn-ghost"><?= e($s['btn2_label']) ?></a><?php endif; ?>
        </div>
      </div>
      <div class="slide-art"><?= $s['image'] ? '<img src="' . SITE_URL . '/' . e($s['image']) . '" alt="' . e(strip_tags($s['title'])) . '">' : e($s['emoji']) ?></div>
    </div>
  <?php endforeach; ?>
  </div>
  <?php if (count($slides) > 1): ?>
    <button class="carousel-btn prev" data-slide="-1" aria-label="قبلی"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></button>
    <button class="carousel-btn next" data-slide="1" aria-label="بعدی"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></button>
    <div class="dots" id="dots"></div>
  <?php endif; ?>
  </div>
</div></section>
<?php else: ?><span id="hero"></span><?php endif; ?>

<?php if ($features): ?>
<div class="container"><div class="features">
  <?php foreach ($features as $i => $f): ?>
    <div class="feature-card reveal" style="transition-delay:<?= $i * .08 ?>s"><div class="feature-icon"><?= e($f['icon']) ?></div><h3><?= e($f['title']) ?></h3><p><?= e($f['body']) ?></p></div>
  <?php endforeach; ?>
</div></div>
<?php endif; ?>

<section class="section" id="categories"><div class="container">
  <div class="section-head reveal"><span class="eyebrow"><?= e($S('cat_eyebrow')) ?></span><h2><?= e($S('cat_title')) ?></h2><p><?= e($S('cat_text')) ?></p></div>
  <div class="cats" id="cats">
    <div class="cat-card active" data-cat="all"><span class="cat-emoji">🧰</span><span class="name">همه محصولات</span><span class="count"><?= fa_num(count($products)) ?> محصول</span></div>
    <?php foreach ($cats as $c): ?>
      <div class="cat-card" data-cat="<?= (int)$c['id'] ?>"><span class="cat-emoji"><?= e($c['emoji']) ?></span><span class="name"><?= e($c['name']) ?></span><span class="count"><?= fa_num($countBy[$c['id']] ?? 0) ?> محصول</span></div>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="section" id="products" style="padding-top:0"><div class="container">
  <div class="section-head reveal"><span class="eyebrow"><?= e($S('prod_eyebrow')) ?></span><h2><?= e($S('prod_title')) ?></h2><p><?= e($S('prod_text')) ?></p></div>
  <div class="filter-bar reveal" id="filterBar">
    <button class="chip active" data-filter="all">همه</button>
    <?php foreach ($cats as $c): ?><button class="chip" data-filter="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></button><?php endforeach; ?>
  </div>
  <div class="product-grid" id="productGrid">
  <?php foreach ($products as $i => $p): $nv = count($p['variants']); ?>
    <article class="product-card reveal" data-id="<?= $p['id'] ?>" data-cat="<?= $p['cat'] ?>" data-search="<?= e($p['name'] . ' ' . $p['catName'] . ' ' . $p['desc']) ?>" style="transition-delay:<?= min($i * .05, .4) ?>s">
      <div class="product-media" style="--g1:<?= e($p['g1']) ?>;--g2:<?= e($p['g2']) ?>">
        <?php if ($p['image']): ?><img class="pimg" src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy"><?php else: ?><span class="product-emoji"><?= e($p['emoji']) ?></span><?php endif; ?>
        <?php if ($p['tag']): ?><span class="tag <?= $p['hot'] ? 'hot' : '' ?>"><?= e($p['tag']) ?></span><?php endif; ?>
        <button class="quick-btn" data-quick="<?= $p['id'] ?>">مشاهده سریع</button>
      </div>
      <div class="product-body">
        <span class="product-cat"><?= e($p['catName']) ?></span>
        <h4><?= e($p['name']) ?></h4>
        <?php if ($p['rating'] > 0): ?><div class="rating"><span class="stars"><?= e(stars($p['rating'])) ?></span><span><?= e($p['ratingText']) ?></span></div><?php endif; ?>
        <p class="product-unit"><?= $nv > 1 ? fa_num($nv) . ' مدل / سایز' : e($p['variants'][0]['unit'] ?? '') ?></p>
        <div class="product-foot">
          <?php if ($p['min']): ?><span class="price"><?= $p['multi'] ? '<small>از</small> ' : '' ?><?= e(fa_price($p['min'])) ?></span>
          <?php else: ?><a href="<?= e($tel) ?>" class="price-call"><?= '📞' ?> تماس بگیرید</a><?php endif; ?>
          <button class="add-btn" data-add="<?= $p['id'] ?>" aria-label="افزودن به لیست استعلام"><?= $PLUS ?></button>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
  <div class="empty-state" id="emptyState" <?= $products ? 'hidden' : '' ?>><span class="big">🔍</span><b>محصولی با این مشخصات پیدا نشد</b><p style="margin-top:6px;font-size:.88rem">عبارت دیگری را جست‌وجو کنید یا دسته‌بندی را تغییر دهید.</p></div>
  </div>
</div></section>

<?php if ($stats): ?>
<div class="container"><div class="stats reveal" id="statsBox">
  <?php foreach ($stats as $st): ?><div class="stat"><span class="stat-num" data-target="<?= (int)$st['num'] ?>" data-suffix="<?= e($st['suffix']) ?>">۰</span><span class="stat-label"><?= e($st['label']) ?></span></div><?php endforeach; ?>
</div></div>
<?php endif; ?>

<section class="section" id="about"><div class="container about">
  <div class="about-visual reveal"><span class="big-emoji"><?= e($S('about_emoji')) ?></span>
    <div class="float-card"><span class="fc-icon">✅</span><div><b><?= e($S('about_card_title')) ?></b><small><?= e($S('about_card_text')) ?></small></div></div></div>
  <div class="about-content reveal" style="transition-delay:.1s">
    <span class="eyebrow"><?= e($S('about_eyebrow')) ?></span><h2><?= e($S('about_title')) ?></h2><p><?= nl2br(e($S('about_text'))) ?></p>
    <div class="about-list"><?php foreach ($aboutItems as $a): ?>
      <div class="about-item"><span class="about-check">✓</span><div><b><?= e($a['title']) ?></b><span><?= e($a['body']) ?></span></div></div><?php endforeach; ?></div>
    <?php if ($S('about_btn')): ?><a href="#contact" class="btn btn-primary"><?= e($S('about_btn')) ?> <?= $ARROW ?></a><?php endif; ?>
  </div>
</div></section>

<section class="section" id="comments" style="padding-top:0"><div class="container">
  <div class="section-head reveal"><span class="eyebrow"><?= e($S('testi_eyebrow')) ?></span><h2><?= e($S('testi_title')) ?></h2><p><?= e($S('testi_text')) ?></p></div>
  <?php $grads = ['#5B3DF5,#8B5CF6', '#F97316,#FF8A3D', '#16A34A,#22C55E', '#0EA5E9,#38BDF8', '#DB2777,#F472B6']; ?>
  <div class="testi-grid">
  <?php foreach ($testis as $i => $t): ?>
    <div class="testi reveal" style="transition-delay:<?= ($i % 3) * .08 ?>s"><span class="quote">”</span>
      <div class="stars-row"><?= e(stars((float)$t['rating'])) ?></div><p><?= e($t['body']) ?></p>
      <div class="testi-user"><div class="avatar" style="background:linear-gradient(135deg,<?= $grads[$i % 5] ?>)"><?= e(mb_substr($t['name'], 0, 1)) ?></div>
        <div><b><?= e($t['name']) ?></b><small><?= e($t['role']) ?><?= $t['pname'] ? ' • ' . e($t['pname']) : '' ?> • <?= e(fa_date((int)$t['created_at'])) ?></small></div></div></div>
  <?php endforeach; ?>
  </div>
  <div class="testi-actions"><button class="btn btn-outline" id="addComment">✍️ ثبت نظر شما</button></div>
</div></section>

<?php if ($faqs): ?>
<section class="section" id="faq" style="padding-top:0"><div class="container">
  <div class="section-head reveal"><span class="eyebrow"><?= e($S('faq_eyebrow')) ?></span><h2><?= e($S('faq_title')) ?></h2><p><?= e($S('faq_text')) ?></p></div>
  <div class="faq-wrap"><?php foreach ($faqs as $i => $f): ?>
    <div class="faq-item reveal" style="transition-delay:<?= $i * .05 ?>s"><button class="faq-q"><?= e($f['q']) ?><span class="faq-icon">+</span></button>
      <div class="faq-a"><div><p><?= nl2br(e($f['a'])) ?></p></div></div></div><?php endforeach; ?></div>
</div></section>
<?php else: ?><span id="faq"></span><?php endif; ?>

<section class="section" id="contact" style="padding-top:0"><div class="container">
  <div class="section-head reveal"><span class="eyebrow"><?= e($S('contact_eyebrow')) ?></span><h2><?= e($S('contact_title')) ?></h2><p><?= e($S('contact_text')) ?></p></div>
  <div class="contact-grid">
    <div class="contact-info reveal">
      <div class="info-card"><span class="info-icon">📞</span><div><b>تلفن فروش</b><a class="fa-num" href="<?= e($tel) ?>"><?= fa($phone) ?></a></div></div>
      <div class="info-card"><span class="info-icon">📱</span><div><b>موبایل / واتساپ</b><a class="fa-num" href="<?= e(tel_href($mobile)) ?>"><?= fa($mobile) ?></a></div></div>
      <div class="info-card"><span class="info-icon">📍</span><div><b>آدرس انبار</b><span><?= e($S('address')) ?></span>
        <?php if (safe_url($S('map_link'))): ?><a href="<?= e(safe_url($S('map_link'))) ?>" target="_blank" rel="noopener" style="color:var(--brand);font-size:.85rem">مسیریابی روی نقشه ←</a><?php endif; ?></div></div>
      <div class="info-card"><span class="info-icon">🕐</span><div><b>ساعات کاری</b><span><?= fa($S('hours')) ?></span></div></div>
    </div>
    <form class="form-card reveal" id="contactForm" style="transition-delay:.1s">
      <div class="form-row">
        <div class="field"><label for="fname">نام و نام خانوادگی</label><input type="text" id="fname" maxlength="80" placeholder="مثلاً: علی محمدی" required></div>
        <div class="field"><label for="fphone">شماره تماس</label><input type="tel" id="fphone" maxlength="20" placeholder="۰۹۱۲۳۴۵۶۷۸۹" required></div>
      </div>
      <div class="field"><label for="fsubject">موضوع درخواست</label><select id="fsubject"><?php foreach ($appData['subjects'] as $sj): ?><option><?= e($sj) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="fmsg">توضیحات</label><textarea id="fmsg" maxlength="2000" placeholder="محصولات موردنیاز، میزان تقریبی و شهر مقصد را بنویسید..."></textarea></div>
      <input type="text" id="fweb" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
      <button type="submit" class="btn btn-primary btn-block">ارسال درخواست <?= $SEND ?></button>
    </form>
  </div>
</div></section>
</main>

<footer><div class="container">
  <div class="foot-grid">
    <div class="foot-brand"><span class="logo"><?= $logo ?></span><p><?= e($S('footer_text')) ?></p>
      <div class="socials">
        <?php foreach (['instagram' => ['📷', 'اینستاگرام'], 'whatsapp' => ['💬', 'واتساپ'], 'telegram' => ['✈️', 'تلگرام'], 'linkedin' => ['🔗', 'لینکدین']] as $k => [$ic, $lb]):
          if (safe_url($S($k))): ?><a href="<?= e(safe_url($S($k))) ?>" class="social" aria-label="<?= $lb ?>" target="_blank" rel="noopener"><?= $ic ?></a><?php endif; endforeach; ?>
      </div></div>
    <div class="foot-col"><h4>دسترسی سریع</h4><ul><li><a href="#hero">خانه</a></li><li><a href="#products">محصولات</a></li><li><a href="#about">درباره ما</a></li><li><a href="#faq">سوالات متداول</a></li><li><a href="#contact">تماس با ما</a></li></ul></div>
    <div class="foot-col"><h4>دسته‌بندی‌ها</h4><ul><?php foreach ($cats as $c): ?><li><a href="#products" data-goto="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></a></li><?php endforeach; ?></ul></div>
    <div class="foot-col"><h4>اطلاعات تماس</h4><ul>
      <li><a href="<?= e($tel) ?>">📞 <?= fa($phone) ?></a></li><li><a href="<?= e(tel_href($mobile)) ?>">📱 <?= fa($mobile) ?></a></li>
      <li><a href="#contact">📍 <?= e($S('address_short')) ?></a></li><li><a href="#contact">🕐 <?= fa($S('hours_short')) ?></a></li></ul></div>
  </div>
  <div class="foot-bottom"><span><?= e(str_replace('{year}', fa_digits((string)jalali_year()), $S('footer_copy'))) ?></span><span><?= e($S('footer_credit')) ?></span></div>
</div></footer>

<div class="fab-stack">
  <a href="<?= e($tel) ?>" class="fab fab-call" aria-label="تماس تلفنی"><?= $PHONE ?></a>
  <button class="fab fab-top" id="toTop" aria-label="بازگشت به بالا"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg></button>
</div>

<div class="overlay" id="overlay"></div>
<aside class="drawer" id="drawer" aria-label="لیست استعلام">
  <div class="drawer-head"><h3>🧾 لیست استعلام <span id="drawerCount" style="color:var(--muted);font-weight:600;font-size:.85rem"></span></h3><button class="icon-btn" id="drawerClose" aria-label="بستن"><?= $CLOSE ?></button></div>
  <div class="drawer-body" id="drawerBody"></div>
  <div class="drawer-foot" id="drawerFoot" style="display:none">
    <div class="drawer-note">💡 خرید فقط تلفنی است. پس از ثبت لیست، کارشناسان ما با شما تماس می‌گیرند و قیمت دقیق روز را اعلام می‌کنند.</div>
    <div class="field"><input type="text" id="iqName" maxlength="80" placeholder="نام و نام خانوادگی"></div>
    <div class="field"><input type="tel" id="iqPhone" maxlength="20" placeholder="شماره تماس"></div>
    <input type="text" id="iqWeb" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
    <button class="btn btn-accent btn-block" id="submitInquiry">ثبت درخواست استعلام <?= $SEND ?></button>
  </div>
</aside>
<div class="modal" id="modal"><div class="modal-card" id="modalCard"></div></div>
<div class="toast-wrap" id="toastWrap"></div>

<script type="application/json" id="app-data"><?= $json ?></script>
<script src="<?= SITE_URL ?>/assets/site.js?v=<?= $ver('site.js') ?>" defer></script>
</body>
</html>
