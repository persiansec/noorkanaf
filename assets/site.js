(() => {
'use strict';
const D = JSON.parse(document.getElementById('app-data').textContent);
const $  = (s, c = document) => c.querySelector(s);
const $$ = (s, c = document) => [...c.querySelectorAll(s)];
const faNum = n => Number(n).toLocaleString('fa-IR');
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const norm = s => String(s || '').toLowerCase().replace(/ي/g, 'ی').replace(/ك/g, 'ک');
const P = new Map(D.products.map(p => [p.id, p]));
const store = { get(k){ try { return localStorage.getItem(k); } catch(e){ return null; } }, set(k,v){ try { localStorage.setItem(k,v); } catch(e){} } };
const stars = r => '★'.repeat(Math.round(r)) + '☆'.repeat(5 - Math.round(r));

/* ---------- Toast ---------- */
function toast(msg, type = 'info'){
  const el = document.createElement('div');
  el.className = 'toast ' + type;
  const ic = document.createElement('span'); ic.className = 'toast-icon'; ic.textContent = type === 'success' ? '✓' : (type === 'error' ? '!' : 'ℹ️');
  const tx = document.createElement('span'); tx.textContent = msg;
  el.append(ic, tx); $('#toastWrap').appendChild(el);
  setTimeout(() => { el.classList.add('out'); setTimeout(() => el.remove(), 350); }, 4200);
}

/* ---------- Theme ---------- */
const html = document.documentElement;
const savedTheme = store.get('kenaf-theme');
if (savedTheme) html.setAttribute('data-theme', savedTheme);
else if (matchMedia('(prefers-color-scheme: dark)').matches) html.setAttribute('data-theme', 'dark');
$('#themeBtn').addEventListener('click', () => {
  const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', next); store.set('kenaf-theme', next);
});

/* ---------- Top bar / header ---------- */
const tb = $('#topbar');
if (tb) $('#topbarClose').addEventListener('click', () => {
  tb.style.transition = 'opacity .3s, transform .3s'; tb.style.opacity = '0'; tb.style.transform = 'translateY(-100%)';
  setTimeout(() => tb.classList.add('hide'), 300);
});
const header = $('#header'), toTop = $('#toTop');
addEventListener('scroll', () => { header.classList.toggle('scrolled', scrollY > 20); toTop.classList.toggle('show', scrollY > 600); }, { passive: true });
toTop.addEventListener('click', () => scrollTo({ top: 0, behavior: 'smooth' }));

/* ---------- Carousel ---------- */
const slidesEl = $('#slides');
if (slidesEl){
  const total = $$('.slide').length, dotsEl = $('#dots'); let cur = 0, timer;
  if (dotsEl) for (let i = 0; i < total; i++){ const d = document.createElement('div'); d.className = 'dot' + (i ? '' : ' active'); d.addEventListener('click', () => go(i)); dotsEl.appendChild(d); }
  const paint = () => { slidesEl.style.transform = `translateX(${cur * 100}%)`; $$('.dot').forEach((d, i) => d.classList.toggle('active', i === cur)); };
  const go = i => { cur = (i + total) % total; paint(); restart(); };
  const restart = () => { clearInterval(timer); if (total > 1) timer = setInterval(() => go(cur + 1), 6000); };
  $$('[data-slide]').forEach(b => b.addEventListener('click', () => go(cur + +b.dataset.slide)));
  const car = $('#carousel'); car.addEventListener('mouseenter', () => clearInterval(timer)); car.addEventListener('mouseleave', restart);
  let tx = null;
  car.addEventListener('touchstart', e => { tx = e.touches[0].clientX; }, { passive: true });
  car.addEventListener('touchend', e => { if (tx === null) return; const dx = e.changedTouches[0].clientX - tx; if (Math.abs(dx) > 50) go(cur + (dx > 0 ? 1 : -1)); tx = null; }, { passive: true });
  document.addEventListener('keydown', e => {
    if (/input|textarea|select/i.test(e.target.tagName)) return;
    if (e.key === 'ArrowLeft') go(cur + 1); if (e.key === 'ArrowRight') go(cur - 1);
  });
  restart();
}

/* ---------- Filter & search ---------- */
let activeCat = 'all', query = '';
const cards = $$('.product-card'); cards.forEach(c => c.dataset.search = norm(c.dataset.search));
function applyFilter(){
  let n = 0;
  cards.forEach(c => { const ok = (activeCat === 'all' || c.dataset.cat === activeCat) && (!query || c.dataset.search.includes(query)); c.hidden = !ok; if (ok) n++; });
  $('#emptyState').hidden = n > 0;
  observeReveals();
}
function setCat(c){
  activeCat = String(c);
  $$('.chip').forEach(x => x.classList.toggle('active', x.dataset.filter === activeCat));
  $$('.cat-card').forEach(x => x.classList.toggle('active', x.dataset.cat === activeCat));
  applyFilter();
}
const toProducts = () => $('#products').scrollIntoView({ behavior: 'smooth' });
$('#filterBar').addEventListener('click', e => { const c = e.target.closest('.chip'); if (c) setCat(c.dataset.filter); });
$('#cats').addEventListener('click', e => { const c = e.target.closest('.cat-card'); if (c){ setCat(c.dataset.cat); toProducts(); } });
$$('[data-goto]').forEach(a => a.addEventListener('click', e => { e.preventDefault(); setCat(a.dataset.goto); toProducts(); }));

const sPanel = $('#searchPanel'), sInput = $('#searchInput');
function closeSearch(){ sPanel.classList.remove('open'); sInput.value = ''; query = ''; applyFilter(); }
$('#searchBtn').addEventListener('click', () => { if (sPanel.classList.contains('open')) closeSearch(); else { sPanel.classList.add('open'); setTimeout(() => sInput.focus(), 250); } });
$('#searchClose').addEventListener('click', closeSearch);
sInput.addEventListener('input', e => { query = norm(e.target.value.trim()); setCat('all'); if (query) toProducts(); });

/* ---------- Overlay / drawer / modal / menu ---------- */
const overlay = $('#overlay'), drawer = $('#drawer'), modal = $('#modal'), modalCard = $('#modalCard'), mobileMenu = $('#mobileMenu');
const unlock = () => { if (!drawer.classList.contains('open') && !modal.classList.contains('show') && !mobileMenu.classList.contains('open')) document.body.style.overflow = ''; };
function openDrawer(){ drawer.classList.add('open'); overlay.classList.add('show'); document.body.style.overflow = 'hidden'; }
function closeDrawer(){ drawer.classList.remove('open'); overlay.classList.remove('show'); unlock(); }
function closeModal(){ modal.classList.remove('show'); unlock(); }
function closeMenu(){ mobileMenu.classList.remove('open'); overlay.classList.remove('show'); unlock(); }
$('#inquiryBtn').addEventListener('click', openDrawer);
$('#drawerClose').addEventListener('click', closeDrawer);
overlay.addEventListener('click', () => { closeDrawer(); closeModal(); closeMenu(); });
modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
$('#menuBtn').addEventListener('click', () => { mobileMenu.classList.add('open'); overlay.classList.add('show'); document.body.style.overflow = 'hidden'; });
$('#menuClose').addEventListener('click', closeMenu);
$$('.mobile-menu a').forEach(a => a.addEventListener('click', closeMenu));
document.addEventListener('keydown', e => { if (e.key === 'Escape'){ closeSearch(); closeModal(); closeDrawer(); closeMenu(); } });

/* ---------- Network ---------- */
async function post(type, data){
  const fd = new FormData(); fd.append('type', type); fd.append('token', D.token);
  Object.entries(data).forEach(([k, v]) => fd.append(k, v));
  try { const r = await fetch(D.submit, { method: 'POST', body: fd, credentials: 'same-origin' }); return await r.json(); }
  catch(e){ return { ok: false, msg: 'ارتباط با سرور برقرار نشد.' }; }
}
async function send(btn, type, data, onOk){
  btn.disabled = true;
  const j = await post(type, data);
  btn.disabled = false;
  toast(j.msg || 'خطا', j.ok ? 'success' : 'error');
  if (j.ok) onOk();
}

/* ---------- Inquiry list ---------- */
let list = [];
try { list = (JSON.parse(store.get('kenaf-iq') || '[]') || []).filter(i => i && typeof i.key === 'string' && typeof i.name === 'string' && i.qty > 0).slice(0, 50); } catch(e){ list = []; }
const badge = $('#badge'), dBody = $('#drawerBody'), dFoot = $('#drawerFoot');

function addItem(p, v){
  const key = v ? 'v' + v.id : 'p' + p.id;
  const name = v && p.variants.length > 1 ? `${p.name} — ${v.name}` : p.name;
  const unit = v ? v.unit : '';
  const ex = list.find(i => i.key === key);
  if (ex) ex.qty++; else list.push({ key, name, unit, emoji: p.emoji, image: p.image, g1: p.g1, g2: p.g2, qty: 1 });
  renderList();
  badge.classList.remove('pop'); void badge.offsetWidth; badge.classList.add('pop');
  toast(`«${name}» به لیست استعلام اضافه شد`, 'success');
}
function renderList(){
  store.set('kenaf-iq', JSON.stringify(list));
  const n = list.reduce((s, i) => s + i.qty, 0);
  badge.textContent = faNum(n); badge.style.display = n ? 'grid' : 'none';
  $('#drawerCount').textContent = n ? `(${faNum(n)} قلم)` : '';
  if (!list.length){
    dBody.innerHTML = `<div class="cart-empty"><span class="big">🧾</span><b>لیست استعلام شما خالی است</b><small>محصولات موردنیاز را اضافه کنید تا کارشناسان ما قیمت دقیق را اعلام کنند.</small><button class="btn btn-primary" style="margin-top:20px" data-act="products">مشاهده محصولات</button></div>`;
    dFoot.style.display = 'none'; return;
  }
  dFoot.style.display = 'block';
  dBody.innerHTML = list.map(i => `<div class="cart-item">
    <div class="cart-thumb" style="background:linear-gradient(135deg,${esc(i.g1)},${esc(i.g2)})">${i.image ? `<img src="${esc(i.image)}" alt="">` : esc(i.emoji)}</div>
    <div class="cart-info"><b>${esc(i.name)}</b><small>${esc(i.unit)}</small>
      <div class="qty"><button data-qty="${esc(i.key)}" data-d="-1" aria-label="کاهش">−</button><span>${faNum(i.qty)}</span><button data-qty="${esc(i.key)}" data-d="1" aria-label="افزایش">+</button></div></div>
    <button class="remove-btn" data-rm="${esc(i.key)}" aria-label="حذف">✕</button></div>`).join('');
}
dBody.addEventListener('click', e => {
  const q = e.target.closest('[data-qty]'), r = e.target.closest('[data-rm]');
  if (q){ const it = list.find(i => i.key === q.dataset.qty); if (it){ it.qty = Math.min(999, it.qty + +q.dataset.d); if (it.qty <= 0) list = list.filter(i => i !== it); renderList(); } }
  if (r){ list = list.filter(i => i.key !== r.dataset.rm); renderList(); }
  if (e.target.closest('[data-act="products"]')){ closeDrawer(); setTimeout(toProducts, 250); }
});
$('#submitInquiry').addEventListener('click', function(){
  const name = $('#iqName').value.trim(), phone = $('#iqPhone').value.trim();
  if (!name || !phone){ toast('نام و شماره تماس را وارد کنید.', 'error'); return; }
  const items = list.map(i => `• ${i.name}${i.unit ? ' (' + i.unit + ')' : ''} × ${i.qty}`).join('\n');
  send(this, 'inquiry', { name, phone, items, website: $('#iqWeb').value }, () => { list = []; renderList(); closeDrawer(); });
});

/* ---------- Quick view ---------- */
function openQuick(id){
  const p = P.get(id); if (!p) return;
  const hasDirect = p.variants.some(v => v.type === 'direct');
  modalCard.innerHTML = `
    <div class="modal-hero" style="background:linear-gradient(135deg,${esc(p.g1)},${esc(p.g2)})">
      <button class="modal-close" data-act="close" aria-label="بستن"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
      ${p.image ? `<img class="mimg" src="${esc(p.image)}" alt="${esc(p.name)}">` : `<span style="position:relative;z-index:1">${esc(p.emoji)}</span>`}
    </div>
    <div class="modal-body">
      <span class="product-cat">${esc(p.catName)}</span><h3>${esc(p.name)}</h3>
      ${p.rating > 0 ? `<div class="rating" style="margin-top:4px"><span class="stars">${stars(p.rating)}</span><span>${esc(p.ratingText)} از ۵</span></div>` : ''}
      ${p.desc ? `<p class="modal-desc">${esc(p.desc)}</p>` : ''}
      ${p.specs.length ? `<div class="spec-grid">${p.specs.map(([k, v]) => `<div class="spec">${k ? `<small>${esc(k)}</small>` : ''}<b>${esc(v)}</b></div>`).join('')}</div>` : ''}
      ${p.variants.length ? `<h4 class="sub-h">مدل‌ها و قیمت‌ها</h4><div class="variants">${p.variants.map(v => `
        <div class="variant"><div><b>${esc(v.name)}</b><small>${esc(v.unit)}</small></div>
          <div>${v.type === 'direct' ? `<span class="price">${esc(v.priceText)}</span>` : `<a class="price-call" href="${esc(D.tel)}">📞 تماس بگیرید</a>`}</div>
          <button class="add-btn" data-addv="${v.id}" aria-label="افزودن به لیست استعلام"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></button></div>`).join('')}</div>
        ${hasDirect && p.updated ? `<p class="price-note">آخرین به‌روزرسانی قیمت: ${esc(p.updated)} — قیمت‌ها ممکن است در طول روز تغییر کند.</p>` : ''}` : ''}
      ${p.comments.length ? `<h4 class="sub-h">نظرات مشتریان</h4><div class="mcomments">${p.comments.map(c => `<div class="mcomment"><span class="stars">${stars(c.rating)}</span> <b>${esc(c.name)}</b> <small>${esc(c.date)}</small><p>${esc(c.body)}</p></div>`).join('')}</div>` : ''}
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        ${p.variants.length ? '' : `<button class="btn btn-primary" style="flex:1;min-width:180px" data-addp="${p.id}">افزودن به لیست استعلام</button>`}
        <a href="${esc(D.tel)}" class="btn btn-primary" style="flex:1;min-width:160px">📞 تماس برای سفارش</a>
        <button class="btn btn-outline" data-comment="${p.id}">✍️ ثبت نظر</button>
      </div>
    </div>`;
  modal.classList.add('show'); document.body.style.overflow = 'hidden';
}
modalCard.addEventListener('click', e => {
  if (e.target.closest('[data-act="close"]')) return closeModal();
  const av = e.target.closest('[data-addv]');
  if (av){ const p = [...P.values()].find(x => x.variants.some(v => v.id === +av.dataset.addv)); addItem(p, p.variants.find(v => v.id === +av.dataset.addv)); return; }
  const ap = e.target.closest('[data-addp]');
  if (ap){ addItem(P.get(+ap.dataset.addp), null); closeModal(); return; }
  const cm = e.target.closest('[data-comment]');
  if (cm) openComment(+cm.dataset.comment);
});
$('#productGrid').addEventListener('click', e => {
  if (e.target.closest('a')) return;
  const add = e.target.closest('[data-add]');
  if (add){ const p = P.get(+add.dataset.add); if (p.variants.length > 1) openQuick(p.id); else addItem(p, p.variants[0] || null); return; }
  const q = e.target.closest('[data-quick]');
  if (q) return openQuick(+q.dataset.quick);
  const c = e.target.closest('.product-card'); if (c) openQuick(+c.dataset.id);
});

/* ---------- Comment form ---------- */
function openComment(pid){
  const opts = ['<option value="0">— نظر عمومی —</option>'].concat([...P.values()].map(p => `<option value="${p.id}" ${p.id === pid ? 'selected' : ''}>${esc(p.name)}</option>`)).join('');
  modalCard.innerHTML = `<div class="modal-body"><h3>ثبت نظر شما</h3><p class="modal-desc">نظر شما پس از بررسی در سایت نمایش داده می‌شود.</p>
    <div class="form-row"><div class="field"><label>نام</label><input id="cName" maxlength="60"></div><div class="field"><label>شهر / شغل (اختیاری)</label><input id="cRole" maxlength="80"></div></div>
    <div class="form-row"><div class="field"><label>محصول</label><select id="cProd">${opts}</select></div>
      <div class="field"><label>امتیاز</label><select id="cRate"><option value="5">★★★★★</option><option value="4">★★★★☆</option><option value="3">★★★☆☆</option><option value="2">★★☆☆☆</option><option value="1">★☆☆☆☆</option></select></div></div>
    <div class="field"><label>متن نظر</label><textarea id="cBody" maxlength="1000"></textarea></div>
    <input id="cWeb" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
    <div style="display:flex;gap:10px"><button class="btn btn-primary" id="cSend" style="flex:1">ارسال نظر</button><button class="btn btn-outline" data-act="close">انصراف</button></div></div>`;
  modal.classList.add('show'); document.body.style.overflow = 'hidden';
  $('#cSend').addEventListener('click', function(){
    send(this, 'comment', { name: $('#cName').value, role: $('#cRole').value, product_id: $('#cProd').value, rating: $('#cRate').value, body: $('#cBody').value, website: $('#cWeb').value }, closeModal);
  });
}
$('#addComment').addEventListener('click', () => openComment(0));

/* ---------- Contact form ---------- */
$('#contactForm').addEventListener('submit', function(e){
  e.preventDefault();
  send($('button[type=submit]', this), 'contact', { name: $('#fname').value, phone: $('#fphone').value, subject: $('#fsubject').value, body: $('#fmsg').value, website: $('#fweb').value }, () => this.reset());
});

/* ---------- FAQ ---------- */
$$('.faq-item').forEach(item => item.querySelector('.faq-q').addEventListener('click', () => {
  const open = item.classList.contains('open'); $$('.faq-item').forEach(i => i.classList.remove('open')); if (!open) item.classList.add('open');
}));

/* ---------- Reveal / counters / nav ---------- */
const revealObs = new IntersectionObserver(es => es.forEach(en => { if (en.isIntersecting){ en.target.classList.add('in'); revealObs.unobserve(en.target); } }), { threshold: .12, rootMargin: '0px 0px -40px 0px' });
function observeReveals(){ $$('.reveal:not(.in)').forEach(el => revealObs.observe(el)); }

const statsBox = $('#statsBox');
if (statsBox){
  const so = new IntersectionObserver(es => es.forEach(en => {
    if (!en.isIntersecting) return;
    $$('.stat-num').forEach(el => {
      const target = +el.dataset.target, suffix = el.dataset.suffix || '', t0 = performance.now();
      const tick = now => { const p = Math.min((now - t0) / 1600, 1); el.textContent = faNum(Math.round(target * (1 - Math.pow(1 - p, 3)))) + suffix; if (p < 1) requestAnimationFrame(tick); };
      requestAnimationFrame(tick);
    });
    so.disconnect();
  }), { threshold: .4 });
  so.observe(statsBox);
}
const links = $$('.nav-links a');
const navObs = new IntersectionObserver(es => es.forEach(en => { if (en.isIntersecting) links.forEach(l => l.classList.toggle('active', l.getAttribute('href') === '#' + en.target.id)); }), { rootMargin: '-45% 0px -50% 0px' });
$$('section[id]').forEach(s => navObs.observe(s));

renderList(); observeReveals();
})();
