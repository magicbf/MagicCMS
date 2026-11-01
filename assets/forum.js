/* Forum editor: BBCode toolbar + live preview. */
(function () {
  'use strict';
  if (window.__mcmsForum) return; window.__mcmsForum = true;
  var csrf = document.body.dataset.csrf;
  document.querySelectorAll('[data-editor]').forEach(function (ed) {
    var ta = ed.querySelector('textarea'), prev = ed.querySelector('.editor-preview'), pbtn = ed.querySelector('.editor-preview-btn');
    function wrap(tag) {
      var s = ta.selectionStart, e = ta.selectionEnd, sel = ta.value.slice(s, e), open = '[' + tag + ']', close = '[/' + tag + ']', ins;
      if (tag === 'url') {
        var u = prompt('URL (https://…)', 'https://'); if (!u) return; open = '[url=' + u + ']'; sel = sel || u;
      } else if (tag === 'img') {
        var i = prompt('Image URL (https://…)', 'https://'); if (!i) return; open = '[img]'; sel = i;
      } else if (tag === 'list') {
        sel = sel ? sel.split('\n').map(function (l) { return '[*]' + l; }).join('\n') : '[*]Item 1\n[*]Item 2'; ins = '[list]\n' + sel + '\n[/list]';
      }
      ins = ins || open + sel + close;
      ta.setRangeText(ins, s, e, 'end'); ta.focus();
    }
    ed.querySelectorAll('[data-bb]').forEach(function (b) { b.addEventListener('click', function () { wrap(b.dataset.bb); }); });
    ta.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && ['b', 'i', 'u'].indexOf(e.key) > -1) { e.preventDefault(); wrap(e.key); }
    });
    if (pbtn) pbtn.addEventListener('click', function () {
      if (!prev.hidden) { prev.hidden = true; ta.hidden = false; pbtn.textContent = pbtn.dataset.label; return; }
      pbtn.dataset.label = pbtn.textContent;
      var fd = new FormData(); fd.append('body', ta.value);
      fetch(pbtn.dataset.previewUrl, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf, 'X-Requested-With': 'fetch', 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (j) { if (j.ok) { prev.innerHTML = j.html; prev.hidden = false; ta.hidden = true; pbtn.textContent = '✎ Edit'; } });
    });
  });
})();
