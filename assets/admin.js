/* Admin UI helpers. */
(function () {
  'use strict';
  var d = document;
  // Sidebar drawer on small screens
  d.querySelectorAll('[data-adm-open]').forEach(function (b) { b.addEventListener('click', function () { d.body.classList.add('adm-open'); }); });
  d.querySelectorAll('[data-adm-close]').forEach(function (b) { b.addEventListener('click', function () { d.body.classList.remove('adm-open'); }); });
  d.addEventListener('keydown', function (e) { if (e.key === 'Escape') d.body.classList.remove('adm-open'); });
  // "Jump to…" quick finder over the sidebar links (Ctrl/⌘+K, arrows, Enter)
  (function () {
    var box = d.querySelector('[data-adm-find]'); if (!box) return;
    var inp = box.querySelector('input'), list = box.querySelector('.adm-find-list'), idx = -1;
    var items = [].map.call(d.querySelectorAll('.adm-nav .adm-link'), function (a) {
      return { href: a.getAttribute('href'), label: a.textContent.trim(), group: a.closest('.adm-group').querySelector('.adm-group-t').textContent.trim() };
    });
    function render() {
      var q = inp.value.trim().toLowerCase(); list.innerHTML = ''; idx = -1;
      var hits = items.filter(function (i) { return !q || i.label.toLowerCase().indexOf(q) > -1 || i.group.toLowerCase().indexOf(q) > -1; }).slice(0, 8);
      hits.forEach(function (i) { var li = d.createElement('li'), a = d.createElement('a'); a.href = i.href; a.innerHTML = '<b></b><small></small>'; a.firstChild.textContent = i.label; a.lastChild.textContent = i.group; li.appendChild(a); list.appendChild(li); });
      list.hidden = !hits.length;
    }
    function mark(n) { var as = list.querySelectorAll('a'); if (!as.length) return; idx = (n + as.length) % as.length; as.forEach(function (a, k) { a.classList.toggle('is-sel', k === idx); }); }
    inp.addEventListener('focus', render); inp.addEventListener('input', render);
    inp.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); mark(idx + 1); } else if (e.key === 'ArrowUp') { e.preventDefault(); mark(idx - 1); }
      else if (e.key === 'Enter') { var a = list.querySelectorAll('a')[Math.max(idx, 0)]; if (a) { e.preventDefault(); location.href = a.href; } }
      else if (e.key === 'Escape') { inp.blur(); list.hidden = true; }
    });
    d.addEventListener('click', function (e) { if (!box.contains(e.target)) list.hidden = true; });
    d.addEventListener('keydown', function (e) { if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); inp.focus(); inp.select(); } });
  })();
  // Show only the target field that matches the chosen link type
  d.querySelectorAll('[data-ref-switch]').forEach(function (sel) {
    var form = sel.closest('fieldset') || sel.closest('form');
    function sync() { form.querySelectorAll('[data-ref]').forEach(function (r) { var show = r.dataset.ref.split(' ').indexOf(sel.value) > -1; r.hidden = !show; r.style.display = show ? '' : 'none'; }); }
    sel.addEventListener('change', sync); sync();
  });
  // Range → <output> (CSP forbids inline handlers)
  d.querySelectorAll('input[data-out]').forEach(function (r) { var o = d.getElementById(r.dataset.out); r.addEventListener('input', function () { if (o) o.textContent = r.value; }); });
  // Live colour-scheme preview
  var f = d.querySelector('form[data-presets]');
  if (f) {
    var presets = JSON.parse(f.dataset.presets), map = { primary: '--c-primary', primary_hi: '--c-primary-hi', primary_lo: '--c-primary-lo', accent: '--c-accent', on_primary: '--c-text-on-primary' };
    function apply(c) { Object.keys(map).forEach(function (k) { if (c[k]) d.documentElement.style.setProperty(map[k], c[k]); }); }
    f.querySelectorAll('input[name=scheme]').forEach(function (r) {
      r.addEventListener('change', function () {
        if (r.value === 'custom') { f.querySelectorAll('input[type=color]').forEach(function (c) { d.documentElement.style.setProperty(c.dataset.token, c.value); }); return; }
        var p = presets[r.value]; apply(p);
        Object.keys(p).forEach(function (k) { var i = f.querySelector('[name=c_' + k + ']'); if (i) i.value = p[k]; });
      });
    });
    f.querySelectorAll('input[type=color]').forEach(function (c) {
      c.addEventListener('input', function () { f.querySelector('input[value=custom]').checked = true; d.documentElement.style.setProperty(c.dataset.token, c.value); });
    });
    var rg = f.querySelector('input[type=range]'), rv = d.getElementById('rv');
    if (rg) rg.addEventListener('input', function () {
      var v = +rg.value, s = d.documentElement.style; s.setProperty('--radius', v + 'px'); s.setProperty('--radius-sm', Math.floor(v / 2) + 'px'); s.setProperty('--radius-lg', Math.floor(v * 1.5) + 'px'); if (rv) rv.textContent = v;
    });
  }
})();
