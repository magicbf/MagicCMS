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

  /* ---- side drawers (small screens) ---- */
  var overlay = $('.drawer-overlay');
  function closeDrawers() { $$('.col-left,.col-right').forEach(function (c) { c.classList.remove('is-open'); }); if (overlay) overlay.classList.remove('is-open'); }
  $$('.drawer-toggle').forEach(function (b) {
    b.addEventListener('click', function () {
      var el = $('#drawer-' + b.dataset.drawer); if (!el) return;
      var open = !el.classList.contains('is-open'); closeDrawers();
      if (open) { el.classList.add('is-open'); overlay && overlay.classList.add('is-open'); }
    });
  });
  if (overlay) overlay.addEventListener('click', closeDrawers);
  d.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDrawers(); });

  /* ---- collapsible panels (remembered per browser) ---- */
  var store = {}; try { store = JSON.parse(localStorage.getItem('mcms_collapsed') || '{}'); } catch (e) {}
  $$('.panel[data-panel-id]').forEach(function (p) {
    var id = p.dataset.panelId, btn = $('.panel-toggle', p);
    function set(c) { p.classList.toggle('is-collapsed', c); btn && btn.setAttribute('aria-expanded', !c); }
    set(!!store[id]);
    btn && btn.addEventListener('click', function () {
      var c = !p.classList.contains('is-collapsed'); set(c); store[id] = c;
      try { localStorage.setItem('mcms_collapsed', JSON.stringify(store)); } catch (e) {}
    });
  });

  /* ---- confirm dialogs ---- */
  d.addEventListener('click', function (e) {
    var el = e.target.closest('[data-confirm]');
    if (el && !confirm(el.dataset.confirm)) e.preventDefault();
  });

  /* ---- shoutbox ---- */
  $$('[data-chat]').forEach(function (box) {
    var log = $('.chat-log', box), form = $('.chat-form', box), input = form && $('.chat-input', form);
    var last = 0, delay = (parseInt(box.dataset.interval, 10) || 5) * 1000, timer, canDel = false;
    function render(m) {
      var row = d.createElement('div'); row.className = 'chat-msg'; row.dataset.id = m.id;
      var img = d.createElement('img'); img.className = 'avatar avatar-xs'; img.src = m.avatar_url; img.alt = ''; img.width = img.height = 24;
      var who = d.createElement('a'); who.className = 'chat-user role-' + m.role; who.href = m.profile; who.textContent = m.username;
      var t = d.createElement('time'); t.textContent = m.time;
      var txt = d.createElement('span'); txt.className = 'chat-text';   // text nodes only: never HTML
      if (window.MCMSEmoji) txt.appendChild(window.MCMSEmoji.toNodes(m.message)); else txt.textContent = m.message;
      row.appendChild(img); row.appendChild(who); row.appendChild(t); row.appendChild(txt);
      if (m.can_delete) {
        var x = d.createElement('button'); x.className = 'chat-del'; x.type = 'button'; x.textContent = '✕'; x.setAttribute('aria-label', 'Delete');
        x.addEventListener('click', function () { post(box.dataset.delete + m.id).then(function () { row.remove(); }); });
        row.appendChild(x);
      }
      log.appendChild(row);
    }
    function poll() {
      fetch(box.dataset.endpoint + '?after=' + last, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (!j.ok) return; canDel = !!j.can_delete;
          var stick = log.scrollTop + log.clientHeight >= log.scrollHeight - 30;
          j.messages.forEach(function (m) { render(m); last = Math.max(last, m.id); });
          if (j.messages.length && (stick || last === j.messages[j.messages.length - 1].id)) log.scrollTop = log.scrollHeight;
        }).catch(function () {}).then(schedule);
    }
    function schedule() { clearTimeout(timer); timer = setTimeout(function () { d.hidden ? schedule() : poll(); }, delay); }
    if (form) form.addEventListener('submit', function (e) {
      e.preventDefault(); var v = input.value.trim(); if (!v) return; input.disabled = true;
      post(box.dataset.post, { message: v }).then(function (j) {
        input.disabled = false; if (j.ok) { input.value = ''; poll(); } else if (j.error) { input.placeholder = j.error; }
        input.focus();
      });
    });
    poll();
  });
})();
