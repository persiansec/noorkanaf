<?php
/** تبدیل تاریخ میلادی ⇄ شمسی (الگوریتم استاندارد jdf) */
function g2j(int $gy, int $gm, int $gd): array {
    $gdm = [0,31,59,90,120,151,181,212,243,273,304,334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $gdm[$gm - 1];
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) { $jy += intdiv($days - 1, 365); $days = ($days - 1) % 365; }
    if ($days < 186) { $jm = 1 + intdiv($days, 31); $jd = 1 + ($days % 31); }
    else { $jm = 7 + intdiv($days - 186, 30); $jd = 1 + (($days - 186) % 30); }
    return [$jy, $jm, $jd];
}
function j2g(int $jy, int $jm, int $jd): array {
    $jy += 1595;
    $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv(($jy % 33) + 3, 4) + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
    $gy = 400 * intdiv($days, 146097);
    $days %= 146097;
    if ($days > 36524) {
        $gy += 100 * intdiv(--$days, 36524);
        $days %= 36524;
        if ($days >= 365) $days++;
    }
    $gy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) { $gy += intdiv($days - 1, 365); $days = ($days - 1) % 365; }
    $gd = $days + 1;
    $sa = [0,31,(($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)) ? 29 : 28,31,30,31,30,31,31,30,31,30,31];
    for ($gm = 0; $gm < 13 && $gd > $sa[$gm]; $gm++) $gd -= $sa[$gm];
    return [$gy, $gm, $gd];
}
const J_MONTHS = ['','فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];

/** timestamp → «۲۴ مهر ۱۴۰۵» (با ساعت اختیاری) */
function fa_date(?int $ts, bool $time = false): string {
    if (!$ts) return '—';
    [$y, $m, $d] = g2j((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
    $s = "$d " . J_MONTHS[$m] . " $y";
    if ($time) $s .= ' — ' . date('H:i', $ts);
    return fa_digits($s);
}
/** timestamp → «1405/07/24» (برای فیلد ویرایش) */
function jalali_input(?int $ts): string {
    $ts = $ts ?: time();
    [$y, $m, $d] = g2j((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
    return sprintf('%04d/%02d/%02d', $y, $m, $d);
}
/** «1405/07/24» یا «۱۴۰۵-۷-۲۴» → timestamp (null اگر نامعتبر) */
function jalali_to_ts(string $s, ?int $keepTime = null): ?int {
    $s = en_digits(trim($s));
    if (!preg_match('~^(\d{4})[/\-.](\d{1,2})[/\-.](\d{1,2})$~', $s, $m)) return null;
    [$jy, $jm, $jd] = [(int)$m[1], (int)$m[2], (int)$m[3]];
    if ($jy < 1300 || $jy > 1600 || $jm < 1 || $jm > 12 || $jd < 1 || $jd > 31) return null;
    [$gy, $gm, $gd] = j2g($jy, $jm, $jd);
    $t = $keepTime ?: time();
    return mktime((int)date('H', $t), (int)date('i', $t), 0, $gm, $gd, $gy) ?: null;
}
function jalali_year(): int { return g2j((int)date('Y'), (int)date('n'), (int)date('j'))[0]; }
