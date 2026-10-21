(function () {
  'use strict';
  /* تأیید حذف */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-confirm]');
    if (b && !confirm(b.dataset.confirm)) e.preventDefault();
  });
  var burger = document.getElementById('burger');
  if (burger) burger.addEventListener('click', function () { document.getElementById('side').classList.toggle('open'); });

  /* عدد به حروف فارسی */
  var ones = ['', 'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه', 'ده', 'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده'];
  var tens = ['', '', 'بیست', 'سی', 'چهل', 'پنجاه', 'شصت', 'هفتاد', 'هشتاد', 'نود'];
  var hund = ['', 'صد', 'دویست', 'سیصد', 'چهارصد', 'پانصد', 'ششصد', 'هفتصد', 'هشتصد', 'نهصد'];
  var scale = ['', 'هزار', 'میلیون', 'میلیارد', 'هزار میلیارد'];
  function w3(n) {
    var p = [], h = Math.floor(n / 100), r = n % 100;
    if (h) p.push(hund[h]);
    if (r) p.push(r < 20 ? ones[r] : tens[Math.floor(r / 10)] + (r % 10 ? ' و ' + ones[r % 10] : ''));
    return p.join(' و ');
  }
  function words(n) {
    if (!n) return '';
    var parts = [], i = 0;
    while (n > 0 && i < scale.length) {
      var c = n % 1000;
      if (c) parts.unshift(w3(c) + (scale[i] ? ' ' + scale[i] : ''));
      n = Math.floor(n / 1000); i++;
    }
    return parts.join(' و ') + ' تومان';
  }
  function toEn(s) { return s.replace(/[۰-۹]/g, function (d) { return d.charCodeAt(0) - 1776; }).replace(/[٠-٩]/g, function (d) { return d.charCodeAt(0) - 1632; }); }

  function wire(input) {
    var out = input.parentNode.querySelector('.words');
    function upd() {
      var digits = toEn(input.value).replace(/\D/g, '').slice(0, 13);
      input.value = digits ? Number(digits).toLocaleString('en-US') : '';
      if (out) out.textContent = words(Number(digits));
    }
    input.addEventListener('input', upd); upd();
    var sel = input.closest('tr') ? input.closest('tr').querySelector('.ptype') : (input.form && input.form.querySelector('.ptype'));
    if (sel) {
      var sync = function () { input.disabled = sel.value === 'call'; if (sel.value === 'call' && out) out.textContent = 'بدون قیمت — «تماس بگیرید»'; else upd(); };
      sel.addEventListener('change', sync); sync();
    }
  }
  [].forEach.call(document.querySelectorAll('.price-in'), wire);
})();
