/* Admin UI helpers. */
(function () {
  'use strict';
  var d = document;
  // Show only the target field that matches the chosen link type
  d.querySelectorAll('[data-ref-switch]').forEach(function (sel) {
    var form = sel.closest('form');
    function sync() { form.querySelectorAll('[data-ref]').forEach(function (r) { r.hidden = r.dataset.ref !== sel.value; r.style.display = r.hidden ? 'none' : ''; }); }
    sel.addEventListener('change', sync); sync();
  });
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
