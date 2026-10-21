<?php
/**
 * تعریف بخش‌های قابل مدیریت (CRUD عمومی).
 * فیلد: 'کلید' => ['برچسب', 'نوع', گزینه‌ها/راهنما]
 * انواع: text, textarea, number, decimal, bool, select, url, color, image, jdate
 */
function categories_options(): array { return array_column(rows('SELECT id, name FROM categories ORDER BY sort, id'), 'name', 'id'); }
function products_options(): array { return ['' => '— نظر عمومی (بدون محصول) —'] + array_column(rows('SELECT id, name FROM products ORDER BY name'), 'name', 'id'); }

function entities(): array {
    $sort = ['ترتیب نمایش', 'number', 'عدد کوچک‌تر، بالاتر نمایش داده می‌شود'];
    $active = ['نمایش در سایت', 'bool'];
    return [
    'slides' => ['label' => 'اسلایدر صفحه اصلی', 'single' => 'اسلاید', 'icon' => '🖼️', 'table' => 'slides', 'order' => 'sort, id',
        'thumb' => ['emoji', 'image'], 'list' => ['title' => ['عنوان'], 'btn1_label' => ['دکمه اول'], 'sort' => ['ترتیب'], 'active' => ['وضعیت', 'bool']],
        'req' => ['title'], 'defaults' => ['theme' => 1, 'active' => 1, 'sort' => 10],
        'fields' => [
            'eyebrow' => ['متن کوچک بالای عنوان', 'text'],
            'title' => ['عنوان اصلی', 'text', 'کلمه رنگی را بین ستاره بگذارید. مثال: کناف *اورجینال* از انبار'],
            'body' => ['توضیح', 'textarea'],
            'btn1_label' => ['دکمه اول — متن', 'text'], 'btn1_link' => ['دکمه اول — لینک', 'url', 'مثال: #products یا tel:02112345678'],
            'btn2_label' => ['دکمه دوم — متن', 'text'], 'btn2_link' => ['دکمه دوم — لینک', 'url'],
            'emoji' => ['ایموجی', 'text', 'اگر تصویر آپلود کنید، تصویر نمایش داده می‌شود'],
            'image' => ['تصویر اسلاید', 'image'],
            'theme' => ['رنگ پس‌زمینه', 'select', [1 => 'بنفش', 2 => 'قرمز', 3 => 'سبزآبی', 4 => 'سبز', 5 => 'نارنجی']],
            'sort' => $sort, 'active' => $active]],

    'categories' => ['label' => 'دسته‌بندی‌ها', 'single' => 'دسته‌بندی', 'icon' => '🗂️', 'table' => 'categories', 'order' => 'sort, id',
        'thumb' => ['emoji'], 'list' => ['name' => ['نام'], 'sort' => ['ترتیب'], 'active' => ['وضعیت', 'bool']],
        'req' => ['name'], 'defaults' => ['active' => 1, 'sort' => 10],
        'guard' => ['SELECT COUNT(*) FROM products WHERE category_id=?', 'این دسته‌بندی محصول دارد؛ ابتدا محصولات را منتقل یا حذف کنید.'],
        'fields' => ['name' => ['نام دسته', 'text'], 'emoji' => ['ایموجی', 'text'], 'sort' => $sort, 'active' => $active]],

    'products' => ['label' => 'محصولات', 'single' => 'محصول', 'icon' => '📦', 'table' => 'products', 'order' => 'p.sort, p.id',
        'list_sql' => 'SELECT p.*, c.name AS cat, (SELECT COUNT(*) FROM subproducts s WHERE s.product_id=p.id) AS nvar FROM products p LEFT JOIN categories c ON c.id=p.category_id',
        'thumb' => ['emoji', 'image'], 'list' => ['name' => ['نام'], 'cat' => ['دسته'], 'nvar' => ['تعداد مدل‌ها'], 'rating' => ['امتیاز'], 'active' => ['وضعیت', 'bool']],
        'req' => ['name', 'category_id'], 'defaults' => ['active' => 1, 'sort' => 10, 'rating' => 5, 'emoji' => '📦', 'g1' => '#E0E7FF', 'g2' => '#C7D2FE'],
        'extra' => 'variants',
        'fields' => [
            'category_id' => ['دسته‌بندی', 'select', 'categories_options'],
            'name' => ['نام محصول', 'text'],
            'emoji' => ['ایموجی', 'text', 'اگر تصویر آپلود نکنید نمایش داده می‌شود'],
            'image' => ['تصویر محصول', 'image'],
            'g1' => ['رنگ پس‌زمینه ۱', 'color'], 'g2' => ['رنگ پس‌زمینه ۲', 'color'],
            'tag' => ['برچسب روی کارت', 'text', 'مثال: پرفروش، جدید، پیشنهاد ویژه'],
            'hot' => ['برچسب قرمز (ویژه)', 'bool'],
            'rating' => ['امتیاز (۰ تا ۵)', 'decimal', 'مثال: 4.5'],
            'description' => ['توضیحات', 'textarea'],
            'specs' => ['مشخصات فنی', 'textarea', 'هر خط یک مشخصه، به شکل «عنوان: مقدار». مثال: ضخامت: ۰.۶ میلی‌متر'],
            'sort' => $sort, 'active' => $active]],

    'comments' => ['label' => 'نظرات مشتریان', 'single' => 'نظر', 'icon' => '💬', 'table' => 'comments', 'order' => 'status, created_at DESC',
        'list' => ['name' => ['نام'], 'body' => ['متن نظر', 'trunc'], 'rating' => ['امتیاز', 'stars'], 'created_at' => ['تاریخ', 'date'], 'status' => ['تأیید', 'bool']],
        'req' => ['name', 'body'], 'defaults' => ['rating' => 5, 'status' => 1], 'toggle' => ['status', 'تأیید', 'لغو تأیید'],
        'fields' => [
            'name' => ['نام', 'text'], 'role' => ['عنوان / شهر', 'text', 'مثال: پیمانکار ساختمان — تهران'],
            'product_id' => ['مربوط به محصول', 'select', 'products_options'],
            'rating' => ['امتیاز', 'select', [5 => '۵ ستاره', 4 => '۴ ستاره', 3 => '۳ ستاره', 2 => '۲ ستاره', 1 => '۱ ستاره']],
            'body' => ['متن نظر', 'textarea'],
            'created_at' => ['تاریخ (شمسی)', 'jdate', 'مثال: 1405/07/24'],
            'status' => ['تأیید و نمایش در سایت', 'bool']]],

    'messages' => ['label' => 'پیام‌ها و استعلام‌ها', 'single' => 'پیام', 'icon' => '✉️', 'table' => 'messages', 'order' => 'is_read, created_at DESC',
        'list' => ['name' => ['نام'], 'phone' => ['تلفن'], 'subject' => ['موضوع'], 'created_at' => ['تاریخ', 'date'], 'is_read' => ['خوانده شده', 'bool']],
        'req' => ['name'], 'defaults' => ['is_read' => 1], 'toggle' => ['is_read', 'خوانده شد', 'خوانده‌نشده'], 'autoread' => 'is_read',
        'fields' => [
            'name' => ['نام', 'text'], 'phone' => ['شماره تماس', 'text'], 'subject' => ['موضوع', 'text'],
            'body' => ['متن پیام', 'textarea'], 'items' => ['اقلام استعلام', 'textarea'],
            'created_at' => ['تاریخ (شمسی)', 'jdate'], 'is_read' => ['خوانده شده', 'bool']]],

    'faqs' => ['label' => 'سوالات متداول', 'single' => 'سوال', 'icon' => '❓', 'table' => 'faqs', 'order' => 'sort, id',
        'list' => ['q' => ['سوال', 'trunc'], 'sort' => ['ترتیب'], 'active' => ['وضعیت', 'bool']],
        'req' => ['q', 'a'], 'defaults' => ['active' => 1, 'sort' => 10],
        'fields' => ['q' => ['سوال', 'text'], 'a' => ['پاسخ', 'textarea'], 'sort' => $sort, 'active' => $active]],

    'features' => ['label' => 'ویژگی‌های فروشگاه', 'single' => 'ویژگی', 'icon' => '⭐', 'table' => 'features', 'order' => 'sort, id',
        'thumb' => ['icon'], 'list' => ['title' => ['عنوان'], 'body' => ['توضیح', 'trunc'], 'active' => ['وضعیت', 'bool']],
        'req' => ['title'], 'defaults' => ['active' => 1, 'sort' => 10],
        'fields' => ['icon' => ['ایموجی', 'text'], 'title' => ['عنوان', 'text'], 'body' => ['توضیح کوتاه', 'text'], 'sort' => $sort, 'active' => $active]],

    'stats' => ['label' => 'آمار و ارقام', 'single' => 'آمار', 'icon' => '📊', 'table' => 'stats', 'order' => 'sort, id',
        'list' => ['num' => ['عدد'], 'label' => ['عنوان'], 'active' => ['وضعیت', 'bool']],
        'req' => ['label'], 'defaults' => ['active' => 1, 'sort' => 10],
        'fields' => ['num' => ['عدد', 'number'], 'suffix' => ['پسوند', 'text', 'مثال: ٪ یا +'], 'label' => ['عنوان', 'text'], 'sort' => $sort, 'active' => $active]],

    'about_items' => ['label' => 'نکات درباره ما', 'single' => 'نکته', 'icon' => '✅', 'table' => 'about_items', 'order' => 'sort, id',
        'list' => ['title' => ['عنوان'], 'body' => ['توضیح', 'trunc'], 'active' => ['وضعیت', 'bool']],
        'req' => ['title'], 'defaults' => ['active' => 1, 'sort' => 10],
        'fields' => ['title' => ['عنوان', 'text'], 'body' => ['توضیح', 'text'], 'sort' => $sort, 'active' => $active]],
    ];
}
