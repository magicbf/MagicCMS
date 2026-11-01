/* MagicCMS emoji & flag picker + renderer for chat.
   - Any <input|textarea data-emoji> and any button[data-emoji-btn] (inside .editor) gets a picker.
   - Emoji are inserted as characters; flags as :flag_xx: (rendered server-side as real SVG images, so they work on Windows too).
   - window.MCMSEmoji.open(anchor, cb) lets other scripts (the visual editor) reuse the picker. */
(function () {
  'use strict';
  var d = document, data = null, loading = null, pop = null, RK = 'mcms.emoji.recent';
  var base = (d.body.dataset.base || '').replace(/\/+$/, '');
  var TEXT = { ':-)': '🙂', ':)': '🙂', '(:': '🙂', ':-D': '😄', ':D': '😄', 'xD': '😆', 'XD': '😆', ';-)': '😉', ';)': '😉', ':-(': '🙁', ':(': '🙁', ":'(": '😢', ':-P': '😛', ':P': '😛', ':p': '😛', ':-O': '😮', ':O': '😮', ':o': '😮', ':-|': '😐', ':|': '😐', '8)': '😎', 'B)': '😎', '<3': '❤️', '</3': '💔', ':*': '😘', ':/': '😕' };
  var FLAGBASE = (d.querySelector('link[href*="core.css"]') || { href: '' }).href.replace(/core\.css.*$/, '') + 'flags/';

  function load() {
    if (data) return Promise.resolve(data);
    return loading || (loading = fetch(base + '/emoji-data', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) { data = j; FLAGBASE = j.flagBase; return j; }));
  }
  function recent() { try { return JSON.parse(localStorage.getItem(RK) || '[]'); } catch (e) { return []; } }
  function remember(v) { try { var r = recent().filter(function (x) { return x !== v; }); r.unshift(v); localStorage.setItem(RK, JSON.stringify(r.slice(0, 24))); } catch (e) {} }
  function el(t, c, h) { var e = d.createElement(t); if (c) e.className = c; if (h != null) e.innerHTML = h; return e; }
  function flagImg(code, cls) { var i = d.createElement('img'); i.className = cls || 'flag'; i.src = FLAGBASE + code + '.svg'; i.alt = ''; i.width = 20; i.height = 15; i.loading = 'lazy'; return i; }

  /* ---------- chat rendering: text → nodes (never HTML) ---------- */
  var RI = /[\u{1F1E6}-\u{1F1FF}]{2}/u;
  function toNodes(text) {
    var frag = d.createDocumentFragment(), re = /:flag_([a-z\-]{2,6}):|([\u{1F1E6}-\u{1F1FF}]{2})|(^|\s)(:-\)|:\)|\(:|:-D|:D|xD|XD|;-\)|;\)|:-\(|:\(|:'\(|:-P|:P|:p|:-O|:O|:o|:-\||:\||8\)|B\)|<3|<\/3|:\*|:\/)(?=$|\s|[.,!?)])/gu, last = 0, m;
    while ((m = re.exec(text))) {
      if (m.index > last) frag.appendChild(d.createTextNode(text.slice(last, m.index)));
      if (m[1]) { var im = flagImg(m[1]); im.addEventListener('error', function () { this.replaceWith(d.createTextNode(':flag_' + m[1] + ':')); }); frag.appendChild(im); }
      else if (m[2]) { var cp = Array.from(m[2]).map(function (c) { return c.codePointAt(0) - 0x1F1E6 + 97; }), code = String.fromCharCode(cp[0], cp[1]), f = flagImg(code), raw = m[2]; f.addEventListener('error', function () { this.replaceWith(d.createTextNode(raw)); }); frag.appendChild(f); }
      else { frag.appendChild(d.createTextNode(m[3] + (TEXT[m[4]] || m[4]))); }
      last = re.lastIndex;
    }
    if (last < text.length) frag.appendChild(d.createTextNode(text.slice(last)));
    return frag;
  }

  /* ---------- picker ---------- */
  function close() { if (pop) { pop.remove(); pop = null; d.removeEventListener('mousedown', outside, true); d.removeEventListener('keydown', onKey, true); } }
  function outside(e) { if (pop && !pop.contains(e.target) && !(pop._anchor && pop._anchor.contains(e.target))) close(); }
  function onKey(e) { if (e.key === 'Escape') { close(); } }

  function open(anchor, cb) {
    if (pop) { var same = pop._anchor === anchor; close(); if (same) return; }
    pop = el('div', 'emj'); pop._anchor = anchor; pop.setAttribute('role', 'dialog'); pop.setAttribute('aria-label', 'Smileys and flags');
    var search = el('input', 'emj-search'); search.type = 'search'; search.placeholder = 'Search smileys & flags…'; search.setAttribute('aria-label', 'Search');
    var tabs = el('div', 'emj-tabs'), grid = el('div', 'emj-grid'), foot = el('div', 'emj-foot', '&nbsp;');
    pop.appendChild(search); pop.appendChild(tabs); pop.appendChild(grid); pop.appendChild(foot); d.body.appendChild(pop);
    var r = anchor.getBoundingClientRect(), w = Math.min(360, innerWidth - 16);
    pop.style.width = w + 'px'; pop.style.left = Math.max(8, Math.min(innerWidth - w - 8, r.left)) + 'px';
    var below = innerHeight - r.bottom > 360 || r.top < 360; pop.style.top = below ? (r.bottom + scrollY + 6) + 'px' : (r.top + scrollY - 6 - 340) + 'px';
    setTimeout(function () { d.addEventListener('mousedown', outside, true); d.addEventListener('keydown', onKey, true); search.focus(); }, 0);
    grid.textContent = 'Loading…';
    load().then(function (D) {
      var cats = [{ id: 'recent', icon: '🕘', label: 'Recently used' }].concat(D.cats.map(function (c) { return { id: c.id, icon: c.icon, label: c.label, items: c.items }; }), [{ id: 'flags', icon: '🏁', label: 'Flags' }]), cur = 'smileys';
      function pick(kind, v) { remember(kind === 'flag' ? ':flag_' + v + ':' : v); close(); cb(kind, v); }
      function emojiBtn(ch, name) { var b = el('button', 'emj-e', ch); b.type = 'button'; b.title = name || ''; b.addEventListener('mouseenter', function () { foot.textContent = (name ? ':' + name + ': ' : '') + ch; }); b.addEventListener('click', function () { pick('emoji', ch); }); return b; }
      function flagBtn(code, name) { var b = el('button', 'emj-f'); b.type = 'button'; b.title = name; b.appendChild(flagImg(code, 'flag')); b.addEventListener('mouseenter', function () { foot.textContent = name + '  ·  :flag_' + code + ':'; }); b.addEventListener('click', function () { pick('flag', code); }); return b; }
      function show() {
        var q = search.value.trim().toLowerCase(); grid.innerHTML = '';
        Array.prototype.forEach.call(tabs.children, function (t) { t.classList.toggle('on', !q && t.dataset.id === cur); });
        if (q) {
          var n = 0; D.cats.forEach(function (c) { c.items.forEach(function (it) { if (n < 120 && (it[2].indexOf(q) > -1 || it[1].indexOf(q) > -1)) { grid.appendChild(emojiBtn(it[0], it[1])); n++; } }); });
          D.flags.forEach(function (f) { if (n < 160 && (f[1].toLowerCase().indexOf(q) > -1 || f[0] === q)) { grid.appendChild(flagBtn(f[0], f[1])); n++; } });
          if (!n) grid.appendChild(el('p', 'emj-none', 'Nothing found'));
          return;
        }
        if (cur === 'recent') {
          var rc = recent(); if (!rc.length) grid.appendChild(el('p', 'emj-none', 'Pick something – it will show up here.'));
          rc.forEach(function (v) { var m = v.match(/^:flag_([a-z\-]+):$/); if (m) { var nm = (D.flags.filter(function (f) { return f[0] === m[1]; })[0] || [])[1]; grid.appendChild(flagBtn(m[1], nm || m[1])); } else grid.appendChild(emojiBtn(v)); });
        } else if (cur === 'flags') { D.flags.forEach(function (f) { grid.appendChild(flagBtn(f[0], f[1])); }); }
        else { D.cats.filter(function (c) { return c.id === cur; })[0].items.forEach(function (it) { grid.appendChild(emojiBtn(it[0], it[1])); }); }
      }
      cats.forEach(function (c) { var t = el('button', 'emj-tab', c.icon); t.type = 'button'; t.dataset.id = c.id; t.title = c.label; t.setAttribute('aria-label', c.label); t.addEventListener('click', function () { cur = c.id; search.value = ''; show(); }); tabs.appendChild(t); });
      search.addEventListener('input', show); if (!recent().length) cur = 'smileys'; show();
    }).catch(function (err) { if (window.console) console.error(err); grid.textContent = 'Could not load the emoji list.'; });
  }

  /* insert into a plain text field at the caret */
  function insertAt(field, text) {
    var s = field.selectionStart, e = field.selectionEnd; if (s == null) { field.value += text; } else { field.value = field.value.slice(0, s) + text + field.value.slice(e); field.selectionStart = field.selectionEnd = s + text.length; }
    field.focus(); field.dispatchEvent(new Event('input', { bubbles: true }));
  }
  function attach(field, btn) {
    btn.addEventListener('click', function (e) { e.preventDefault(); open(btn, function (kind, v) { insertAt(field, kind === 'flag' ? ':flag_' + v + ': ' : v); }); });
  }
  d.querySelectorAll('.editor').forEach(function (ed) { var b = ed.querySelector('[data-emoji-btn]'), ta = ed.querySelector('textarea'); if (b && ta) attach(ta, b); });
  d.querySelectorAll('input[data-emoji],textarea[data-emoji]').forEach(function (f) {
    var wrap = el('span', 'emj-wrap'); f.parentNode.insertBefore(wrap, f); wrap.appendChild(f);
    var b = el('button', 'emj-open', '😀'); b.type = 'button'; b.title = 'Smileys & flags'; b.setAttribute('aria-label', 'Smileys & flags'); wrap.appendChild(b); attach(f, b);
  });
  window.MCMSEmoji = { open: open, toNodes: toNodes, load: load, flagImg: flagImg, flagBase: function () { return FLAGBASE; } };
  d.dispatchEvent(new Event('mcms:emoji-ready'));
})();
