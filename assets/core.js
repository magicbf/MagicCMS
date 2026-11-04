/* MagicCMS core behaviour (vanilla JS, no dependencies). */
(function () {
  'use strict';
  var d = document, root = d.documentElement, body = d.body;
  var csrf = body.dataset.csrf, base = (body.dataset.base || '/').replace(/\/$/, '');
  var $ = function (s, c) { return (c || d).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || d).querySelectorAll(s)); };

  function post(url, data) {
    var fd = new FormData();
    Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
    return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin',
      headers: { 'X-CSRF-Token': csrf, 'X-Requested-With': 'fetch', 'Accept': 'application/json' } }).then(function (r) { return r.json().catch(function () { return { ok: false }; }); });
  }

  /* ---- a logo that fails to load falls back to the default mark instead of leaving a blank/broken image ---- */
  $$('img[data-fallback]').forEach(function (img) {
    function fix() {
      if (img.dataset.fellBack) return;
      img.dataset.fellBack = '1'; img.classList.remove('brand-logo'); img.classList.add('brand-mark');
      img.setAttribute('width', '64'); img.src = img.dataset.fallback;
    }
    img.addEventListener('error', fix);
    if (img.complete && img.naturalWidth === 0 && img.getAttribute('src')) fix();
  });

  /* ---- light / dark toggle ---- */
  var mt = $('.mode-toggle');
  if (mt) mt.addEventListener('click', function () {
    var next = root.dataset.theme === 'light' ? 'dark' : 'light';
    root.dataset.theme = next; root.dataset.themeSetting = next;
    post(base + '/appearance/mode', { mode: next });
  });
  if (window.matchMedia) matchMedia('(prefers-color-scheme: light)').addEventListener('change', function (e) {
    if (root.dataset.themeSetting === 'auto') root.dataset.theme = e.matches ? 'light' : 'dark';
  });

  /* ---- sticky menu bar: show the mini logo once the tall header has scrolled away ---- */
  var sentinel = $('.nav-sentinel'), bar = $('.site-navbar');
  if (sentinel && bar && 'IntersectionObserver' in window) {
    new IntersectionObserver(function (en) { bar.classList.toggle('is-stuck', !en[0].isIntersecting); }, { threshold: 0 }).observe(sentinel);
  }

  /* ---- phone menu button (lives in the header's action row, next to Login / light-dark) ---- */
  $$('.hdr-menu').forEach(function (b) {
    b.addEventListener('click', function () {
      var nav = $('.site-nav:not(.nav-vertical)'); if (!nav) return;
      var open = nav.classList.toggle('is-open');
      b.setAttribute('aria-expanded', open); b.classList.toggle('is-open', open);
      var bu = $('.nav-burger', nav); if (bu) bu.setAttribute('aria-expanded', open);
    });
  });

  /* ---- nav burger + touch dropdowns ---- */
  $$('.nav-burger').forEach(function (b) {
    b.addEventListener('click', function () {
      var nav = b.closest('.site-nav'), open = nav.classList.toggle('is-open');
      b.setAttribute('aria-expanded', open);
    });
  });
  $$('.nav-item.has-sub > .nav-btn').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var li = a.parentNode;
      if (!li.classList.contains('is-open') && (matchMedia('(hover: none)').matches || innerWidth < 900)) {
        e.preventDefault(); $$('.nav-item.is-open').forEach(function (o) { o.classList.remove('is-open'); }); li.classList.add('is-open');
      }
    });
  });
  d.addEventListener('click', function (e) { if (!e.target.closest('.nav-item')) $$('.nav-item.is-open').forEach(function (o) { o.classList.remove('is-open'); }); });

  /* ---- collapsible panels (remembered per browser) ---- */
  var store = {}; try { store = JSON.parse(localStorage.getItem('mcms_collapsed') || '{}'); } catch (e) {}
  $$('.panel[data-panel-id]').forEach(function (p) {
    var id = p.dataset.panelId, btn = $('.panel-toggle', p);
    function set(c) { p.classList.toggle('is-collapsed', c); btn && btn.setAttribute('aria-expanded', !c); }
    // phones/tablets: panels stack under the content – the first two stay open, the rest start collapsed (until the visitor chooses)
    var col = p.parentNode, stacked = matchMedia('(max-width:1099.98px)').matches && col && /col-(left|right)/.test(col.className);
    var idx = stacked ? Array.prototype.indexOf.call(col.children, p) : 0;
    set(id in store ? !!store[id] : (stacked && idx >= 2));
    function toggle() {
      var c = !p.classList.contains('is-collapsed'); set(c); store[id] = c;
      try { localStorage.setItem('mcms_collapsed', JSON.stringify(store)); } catch (e) {}
    }
    btn && btn.addEventListener('click', toggle);
    // on small screens the whole title bar is the tap target
    var head = $('.panel-title', p);
    head && head.addEventListener('click', function (e) {
      if (e.target.closest('a,button,input,select,textarea') || !matchMedia('(max-width:1099.98px)').matches) return;
      toggle();
    });
  });

  /* ---- confirm dialogs ---- */
  d.addEventListener('click', function (e) {
    var el = e.target.closest('[data-confirm]');
    if (el && !confirm(el.dataset.confirm)) e.preventDefault();
  });

  /* ---- shoutbox ---- */
  $$('[data-chat]').forEach(function (box) {
    var log = $('.chat-log', box), form = $('.chat-form', box), input = form && $('.chat-input', form), empty = $('.chat-empty', box),
        pill = $('.chat-new', box), counter = $('.chat-count', box), me = (box.dataset.me || '').toLowerCase();
    var last = 0, delay = (parseInt(box.dataset.interval, 10) || 5) * 1000, timer, unseen = 0, lastUser = null, lastTs = 0, lastDay = '', first = true;
    var STAFF = { superadmin: 1, admin: 1, moderator: 1 };

    /* message text → safe DOM: emoji/flags, links and @mentions are built from text nodes, never from HTML */
    function decorate(frag, mine) {
      var hit = false;
      Array.prototype.slice.call(frag.childNodes).forEach(function (n) {
        if (n.nodeType !== 3) return;
        var parts = n.nodeValue.split(/(https?:\/\/[^\s<>"]+|@[A-Za-z0-9_.\-]{2,32})/g);
        if (parts.length < 2) return;
        var f = d.createDocumentFragment();
        parts.forEach(function (p) {
          if (/^https?:\/\//i.test(p)) { var a = d.createElement('a'); a.href = p; a.rel = 'nofollow ugc noopener'; a.target = '_blank'; a.className = 'chat-link'; a.textContent = p.length > 38 ? p.slice(0, 36) + '…' : p; f.appendChild(a); }
          else if (/^@[A-Za-z0-9_.\-]{2,32}$/.test(p)) { var s = d.createElement('span'); s.className = 'chat-at'; s.textContent = p; if (me && p.slice(1).toLowerCase() === me) { s.className += ' is-me'; hit = true; } f.appendChild(s); }
          else if (p) f.appendChild(d.createTextNode(p));
        });
        frag.replaceChild(f, n);
      });
      return hit;
    }

    function render(m) {
      if (empty) { empty.remove(); empty = null; }
      if (m.day !== lastDay) { var dv = d.createElement('div'); dv.className = 'chat-day'; dv.innerHTML = '<span></span>'; dv.firstChild.textContent = m.day_label; log.appendChild(dv); lastDay = m.day; lastUser = null; }
      var follow = lastUser === m.uid && m.ts - lastTs < 300;
      var row = d.createElement('div'); row.className = 'chat-msg' + (m.me ? ' is-me' : '') + (follow ? ' is-follow' : ''); row.dataset.id = m.id;
      var img = d.createElement('img'); img.className = 'avatar avatar-xs'; img.src = m.avatar_url; img.alt = ''; img.width = img.height = 30;
      var bub = d.createElement('div'); bub.className = 'chat-bub';
      var head = d.createElement('div'); head.className = 'chat-head';
      var who = d.createElement('a'); who.className = 'chat-user role-' + m.role; who.href = m.profile; who.textContent = m.username; head.appendChild(who);
      if (STAFF[m.role]) { var rt = d.createElement('span'); rt.className = 'chat-role role-' + m.role; rt.textContent = m.role_short || m.role_name; rt.title = m.role_name; head.appendChild(rt); }
      (m.droles || []).forEach(function (r) {
        var c = d.createElement('span'); c.className = 'drole drole-' + (/^(soft|solid|outline|glow)$/.test(r.style) ? r.style : 'soft'); c.title = r.name;
        c.style.setProperty('--rc', /^#[0-9a-f]{6}$/i.test(r.color) ? r.color : '#888'); c.style.setProperty('--rt', /^#[0-9a-f]{6}$/i.test(r.text) ? r.text : '#fff');
        if (r.icon) { var ic = d.createElement('i'); ic.textContent = r.icon; c.appendChild(ic); }
        c.appendChild(d.createTextNode(r.name)); head.appendChild(c);
      });
      var t = d.createElement('time'); t.textContent = m.time; head.appendChild(t);
      var txt = d.createElement('div'); txt.className = 'chat-text';   // text nodes only: never HTML
      var frag = window.MCMSEmoji ? window.MCMSEmoji.toNodes(m.message) : d.createDocumentFragment();
      if (!window.MCMSEmoji) frag.appendChild(d.createTextNode(m.message));
      if (decorate(frag, m.me) && !m.me) row.className += ' is-mention';
      txt.appendChild(frag);
      bub.appendChild(head); bub.appendChild(txt);
      row.appendChild(img); row.appendChild(bub);
      if (m.can_delete) {   // always-visible "Edit / Remove" text links (staff only) – no hover-only button
        var tools = d.createElement('div'); tools.className = 'chat-tools';
        function act(label, fn) { var b = d.createElement('button'); b.type = 'button'; b.className = 'chat-act'; b.textContent = label; b.addEventListener('click', fn); return b; }
        function showTools() { tools.textContent = ''; tools.appendChild(act('Edit', startEdit)); tools.appendChild(d.createTextNode(' / ')); tools.appendChild(act('Remove', askRemove)); }
        function askRemove() {
          tools.textContent = ''; tools.appendChild(d.createTextNode('Remove this message? '));
          tools.appendChild(act('Yes, remove', function () { post(box.dataset.delete + m.id).then(function (j) { if (j.ok) row.remove(); else showTools(); }); }));
          tools.appendChild(d.createTextNode(' / ')); tools.appendChild(act('Cancel', showTools));
        }
        function startEdit() {
          var inp = d.createElement('input'); inp.type = 'text'; inp.className = 'chat-edit'; inp.maxLength = 300; inp.value = m.message;
          txt.hidden = true; bub.insertBefore(inp, tools); tools.textContent = '';
          function stop() { inp.remove(); txt.hidden = false; showTools(); }
          tools.appendChild(act('Save', function () {
            var v = inp.value.trim(); if (!v) return;
            post(box.dataset.edit + m.id, { message: v }).then(function (j) {
              if (!j.ok) { tools.dataset.err = j.error || 'Could not save'; inp.title = tools.dataset.err; return; }
              m.message = j.message; txt.textContent = ''; var fr = window.MCMSEmoji ? window.MCMSEmoji.toNodes(m.message) : d.createDocumentFragment();
              if (!window.MCMSEmoji) fr.appendChild(d.createTextNode(m.message)); decorate(fr, m.me); txt.appendChild(fr); stop();
            });
          }));
          tools.appendChild(d.createTextNode(' / ')); tools.appendChild(act('Cancel', stop));
          inp.addEventListener('keydown', function (e) { if (e.key === 'Escape') stop(); if (e.key === 'Enter') { e.preventDefault(); tools.querySelector('.chat-act').click(); } });
          inp.focus();
        }
        showTools(); bub.appendChild(tools);
      }
      if (!first) row.className += ' is-new';
      log.appendChild(row); lastUser = m.uid; lastTs = m.ts;
    }
    function toBottom() { log.scrollTop = log.scrollHeight; unseen = 0; if (pill) pill.hidden = true; }
    function poll() {
      fetch(box.dataset.endpoint + '?after=' + last, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (!j.ok) return;
          var stick = first || log.scrollTop + log.clientHeight >= log.scrollHeight - 40, mine = false, fresh = 0;
          j.messages.forEach(function (m) { render(m); last = Math.max(last, m.id); if (!first && !m.me) fresh++; if (m.me) mine = true; });
          if (j.meta) { var c = $('[data-chatters]', box), tdy = $('[data-today]', box); if (c) c.textContent = j.meta.chatters; if (tdy) tdy.textContent = j.meta.today; }
          if (j.messages.length) { if (stick || mine) toBottom(); else if (fresh && pill) { unseen += fresh; pill.hidden = false; $('span', pill).textContent = unseen + ' ' + (unseen === 1 ? 'new message' : 'new messages'); } }
          first = false;
        }).catch(function () {}).then(schedule);
    }
    function schedule() { clearTimeout(timer); timer = setTimeout(function () { d.hidden ? schedule() : poll(); }, delay); }
    if (pill) pill.addEventListener('click', toBottom);
    log.addEventListener('scroll', function () { if (log.scrollTop + log.clientHeight >= log.scrollHeight - 40 && pill) { pill.hidden = true; unseen = 0; } });
    if (input && counter) input.addEventListener('input', function () { var left = 300 - input.value.length; counter.textContent = left; counter.classList.toggle('is-low', left < 40); counter.classList.toggle('is-on', input.value.length > 0); });
    if (form) form.addEventListener('submit', function (e) {
      e.preventDefault(); var v = input.value.trim(); if (!v) return; input.disabled = true;
      post(box.dataset.post, { message: v }).then(function (j) {
        input.disabled = false; if (j.ok) { input.value = ''; if (counter) { counter.textContent = '300'; counter.classList.remove('is-low', 'is-on'); } poll(); } else if (j.error) { input.placeholder = j.error; }
        input.focus();
      });
    });
    poll();
  });
})();
