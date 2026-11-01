/* MagicCMS visual editor – dependency-free WYSIWYG for pages, news and HTML panels.
   Turns <textarea data-editor> into a rich editor; the server still sanitizes everything on save. */
(function () {
  'use strict';
  var d = document;
  var BLOCKS = 'P,H1,H2,H3,H4,H5,H6,LI,BLOCKQUOTE,PRE,DIV,TD,TH';
  var OK_TAGS = 'P,BR,B,STRONG,I,EM,U,S,H1,H2,H3,H4,H5,H6,UL,OL,LI,BLOCKQUOTE,CODE,PRE,A,IMG,HR,TABLE,THEAD,TBODY,TR,TH,TD,SPAN,DIV,SMALL,SUB,SUP,FIGURE,FIGCAPTION,MARK,IFRAME'.split(',');
  var DROP_TAGS = 'SCRIPT,STYLE,IFRAME_X,OBJECT,EMBED,FORM,INPUT,BUTTON,LINK,META,SVG,MATH,TEXTAREA,SELECT,NOSCRIPT,TEMPLATE,BASE'.split(',');
  var csrf = (d.body.dataset.csrf || ''), base = (d.body.dataset.base || '');
  var ic = function (p) { return '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + p + '</svg>'; };
  var ICONS = {
    undo: ic('<path d="M9 14L4 9l5-5"/><path d="M4 9h10a6 6 0 010 12h-3"/>'), redo: ic('<path d="M15 14l5-5-5-5"/><path d="M20 9H10a6 6 0 000 12h3"/>'),
    left: ic('<path d="M4 6h16M4 10h10M4 14h16M4 18h10"/>'), center: ic('<path d="M4 6h16M7 10h10M4 14h16M7 18h10"/>'), right: ic('<path d="M4 6h16M10 10h10M4 14h16M10 18h10"/>'),
    ul: ic('<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>'), ol: ic('<path d="M10 6h10M10 12h10M10 18h10M4 5l1.5-1V9M3.5 14.5h3L3.5 18h3"/>'),
    link: ic('<path d="M10 14a4 4 0 005.7 0l3-3a4 4 0 00-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 00-5.7 0l-3 3a4 4 0 005.7 5.7l1-1"/>'), unlink: ic('<path d="M10 14a4 4 0 005.7 0l3-3a4 4 0 00-5.7-5.7M14 10a4 4 0 00-5.7 0l-3 3a4 4 0 005.7 5.7M4 4l16 16"/>'),
    image: ic('<rect x="3" y="5" width="18" height="14" rx="2.4"/><circle cx="9" cy="10" r="1.6"/><path d="M3.5 17l5-4 4 3 3-2.5 5 3.5"/>'), video: ic('<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M10 9.5v5l4.5-2.5z"/>'),
    table: ic('<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M3 15h18M9 4v16M15 4v16"/>'), hr: ic('<path d="M4 12h16"/>'),
    callout: ic('<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M7 10h10M7 14h6"/>'), button: ic('<rect x="3" y="8" width="18" height="8" rx="4"/><path d="M8 12h8"/>'),
    clear: ic('<path d="M5 6h14M9 6l-1 12M15 6l1 12M10 3h4M4 20h16M18 4L6 20"/>'), code: ic('<path d="M8 8l-5 4 5 4M16 8l5 4-5 4M14 5l-4 14"/>'),
    full: ic('<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>'), quote: ic('<path d="M7 7h4v5c0 3-1 4-4 5M15 7h4v5c0 3-1 4-4 5"/>'), mark: ic('<path d="M4 20h16M7 15l7-9 4 3-6 9H8z"/>')
  };

  /* ---------- helpers ---------- */
  function el(tag, cls, html) { var e = d.createElement(tag); if (cls) e.className = cls; if (html != null) e.innerHTML = html; return e; }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function safeUrl(u) { u = String(u || '').trim(); if (/^[a-z][a-z0-9+.\-]*:/i.test(u) && !/^(https?|mailto):/i.test(u)) return ''; return u; }
  function ytId(u) { var m = String(u).match(/(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/))([A-Za-z0-9_-]{6,15})/); return m ? m[1] : ''; }

  /* client-side cleaner (used for paste + source toggle); the server sanitizer remains the authority */
  function clean(html) {
    var doc = new DOMParser().parseFromString('<div id="r">' + html + '</div>', 'text/html'), root = doc.getElementById('r');
    (function walk(n) {
      Array.prototype.slice.call(n.childNodes).forEach(function (c) {
        if (c.nodeType === 8) { n.removeChild(c); return; }
        if (c.nodeType !== 1) return;
        var t = c.tagName;
        if (DROP_TAGS.indexOf(t) > -1) { n.removeChild(c); return; }
        walk(c);
        if (t === 'IFRAME') { if (!ytId(c.getAttribute('src'))) n.removeChild(c); return; }
        if (OK_TAGS.indexOf(t) < 0) { while (c.firstChild) n.insertBefore(c.firstChild, c); n.removeChild(c); return; }
        Array.prototype.slice.call(c.attributes).forEach(function (a) {
          var k = a.name.toLowerCase(), keep = k === 'class' || (t === 'A' && /^(href|title|target|rel)$/.test(k)) || (t === 'IMG' && /^(src|alt|width|height)$/.test(k)) || ((t === 'TD' || t === 'TH') && /^(colspan|rowspan)$/.test(k)) || (t === 'IFRAME' && /^(src|class)$/.test(k));
          if (!keep || ((k === 'href' || k === 'src') && t !== 'IFRAME' && !safeUrl(a.value))) c.removeAttribute(a.name);
        });
        if (/^(SPAN|DIV)$/.test(t) && !c.getAttribute('class') && !c.attributes.length) { while (c.firstChild) n.insertBefore(c.firstChild, c); n.removeChild(c); }
      });
    })(root);
    return root.innerHTML;
  }

  /* ---------- modal dialog ---------- */
  function ask(title, fields, ok) {
    return new Promise(function (res) {
      var ov = el('div', 'ed-ov'), box = el('form', 'ed-dlg'); box.innerHTML = '<h3>' + esc(title) + '</h3>';
      fields.forEach(function (f) {
        var row = el('label', 'ed-fld'), inner = '<span>' + esc(f.label) + '</span>';
        if (f.type === 'select') inner += '<select name="' + f.name + '">' + f.options.map(function (o) { return '<option value="' + esc(o[0]) + '"' + (o[0] === f.value ? ' selected' : '') + '>' + esc(o[1]) + '</option>'; }).join('') + '</select>';
        else if (f.type === 'check') { row.className += ' ed-chk'; inner = '<input type="checkbox" name="' + f.name + '"' + (f.value ? ' checked' : '') + '><span>' + esc(f.label) + '</span>'; }
        else if (f.type === 'file') inner += '<input type="file" name="' + f.name + '" accept="image/*">';
        else inner += '<input type="' + (f.type || 'text') + '" name="' + f.name + '" value="' + esc(f.value || '') + '" placeholder="' + esc(f.ph || '') + '"' + (f.min ? ' min="' + f.min + '" max="' + f.max + '"' : '') + '>';
        row.innerHTML = inner; box.appendChild(row);
      });
      var act = el('div', 'ed-dlg-act'), b1 = el('button', 'btn btn-primary btn-sm', esc(ok || 'OK')), b2 = el('button', 'btn btn-ghost btn-sm', 'Cancel'); b1.type = 'submit'; b2.type = 'button'; act.appendChild(b2); act.appendChild(b1); box.appendChild(act); ov.appendChild(box); d.body.appendChild(ov);
      function close(v) { ov.remove(); res(v); }
      box.addEventListener('submit', function (e) { e.preventDefault(); var o = {}; fields.forEach(function (f) { var i = box.elements[f.name]; o[f.name] = f.type === 'check' ? i.checked : (f.type === 'file' ? i.files[0] : i.value); }); close(o); });
      b2.addEventListener('click', function () { close(null); }); ov.addEventListener('mousedown', function (e) { if (e.target === ov) close(null); });
      box.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(null); e.stopPropagation(); });
      var first = box.querySelector('input,select'); if (first) first.focus();
    });
  }

  function upload(file) {
    var fd = new FormData(); fd.append('image', file);
    return fetch(base + '/admin/editor/image', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf, 'X-Requested-With': 'fetch', 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); }).then(function (j) { if (!j.ok) throw new Error(j.error || 'Upload failed'); return j.url; });
  }

  /* ---------- editor ---------- */
  function init(ta) {
    var compact = ta.hasAttribute('data-editor-compact'), saved = null, srcMode = false, dirty = false;
    var wrap = el('div', 'ed' + (compact ? ' ed-compact' : '')), bar = el('div', 'ed-bar'), ctx = el('div', 'ed-ctx'), area = el('div', 'ed-area prose'), src = el('textarea', 'ed-src mono'), foot = el('div', 'ed-foot');
    area.contentEditable = 'true'; area.spellcheck = true; area.setAttribute('role', 'textbox'); area.setAttribute('aria-multiline', 'true'); area.setAttribute('aria-label', 'Content');
    src.hidden = true; src.spellcheck = false;
    ta.parentNode.insertBefore(wrap, ta); wrap.appendChild(bar); wrap.appendChild(ctx); wrap.appendChild(area); wrap.appendChild(src); wrap.appendChild(foot); ta.hidden = true; ta.style.display = 'none';
    area.innerHTML = clean(ta.value) || '<p><br></p>';
    try { d.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) {}

    function sel() { var s = getSelection(); return s.rangeCount ? s.getRangeAt(0) : null; }
    function inArea(n) { return n && area.contains(n); }
    function save() { var r = sel(); if (r && inArea(r.commonAncestorContainer)) saved = r.cloneRange(); }
    function restore() { area.focus(); if (saved) { var s = getSelection(); s.removeAllRanges(); s.addRange(saved); } }
    function closest(sel_) { var r = sel(); var n = r && r.commonAncestorContainer; if (n && n.nodeType === 3) n = n.parentNode; return n && inArea(n) ? n.closest(sel_) : null; }
    function block() { var n = closest(BLOCKS.toLowerCase()); while (n && n.parentNode !== area && n.tagName === 'LI') n = n.parentNode; return n; }
    function topBlock() { var n = closest('*'); while (n && n.parentNode !== area) n = n.parentNode; return n; }
    function changed() { dirty = true; sync(); }
    function html() { var h = clean(area.innerHTML).replace(/<p><br><\/p>/g, '').trim(); return /^(<p>\s*<\/p>)*$/.test(h) ? '' : h; }
    function sync() { ta.value = srcMode ? src.value : html(); var t = area.textContent.trim(); foot.textContent = (t ? t.split(/\s+/).length : 0) + ' words · ' + t.length + ' characters'; }
    function run(cmd, val) { restore(); d.execCommand(cmd, false, val || null); changed(); refresh(); }
    function insert(h) { restore(); d.execCommand('insertHTML', false, h); changed(); }
    function setBlock(tag) { restore(); d.execCommand('formatBlock', false, '<' + tag + '>'); changed(); refresh(); }
    function align(cls) { restore(); var b = block(); if (!b) return; ['align-left', 'align-center', 'align-right'].forEach(function (c) { b.classList.remove(c); }); if (cls !== 'align-left') b.classList.add(cls); if (!b.getAttribute('class')) b.removeAttribute('class'); changed(); refresh(); }

    /* toolbar definition: [id, icon/label, title, handler] ; 'sep' ; 'block' */
    var items = [
      ['undo', ICONS.undo, 'Undo (Ctrl+Z)', function () { run('undo'); }], ['redo', ICONS.redo, 'Redo (Ctrl+Y)', function () { run('redo'); }], 'sep', 'block',
      ['bold', '<b>B</b>', 'Bold (Ctrl+B)', function () { run('bold'); }], ['italic', '<i>I</i>', 'Italic (Ctrl+I)', function () { run('italic'); }], ['underline', '<u>U</u>', 'Underline (Ctrl+U)', function () { run('underline'); }], ['strikeThrough', '<s>S</s>', 'Strikethrough', function () { run('strikeThrough'); }],
      ['mark', ICONS.mark, 'Highlight', function () { restore(); var r = sel(); if (r && !r.collapsed) { var m = closest('mark'); if (m) { while (m.firstChild) m.parentNode.insertBefore(m.firstChild, m); m.remove(); } else insert('<mark>' + esc(r.toString()) + '</mark>'); changed(); } }], 'sep',
      ['left', ICONS.left, 'Align left', function () { align('align-left'); }], ['center', ICONS.center, 'Align centre', function () { align('align-center'); }], ['right', ICONS.right, 'Align right', function () { align('align-right'); }], 'sep',
      ['insertUnorderedList', ICONS.ul, 'Bulleted list', function () { run('insertUnorderedList'); }], ['insertOrderedList', ICONS.ol, 'Numbered list', function () { run('insertOrderedList'); }], ['quote', ICONS.quote, 'Quote', function () { setBlock(closest('blockquote') ? 'p' : 'blockquote'); }], 'sep',
      ['link', ICONS.link, 'Link (Ctrl+K)', doLink], ['unlink', ICONS.unlink, 'Remove link', function () { run('unlink'); }], ['image', ICONS.image, 'Image', doImage], ['video', ICONS.video, 'YouTube video', doVideo], ['table', ICONS.table, 'Table', doTable], 'sep',
      ['callout', ICONS.callout, 'Highlight box', doCallout], ['button', ICONS.button, 'Button link', doButton], ['hr', ICONS.hr, 'Divider', function () { insert('<hr><p><br></p>'); }], ['clear', ICONS.clear, 'Clear formatting', function () { restore(); d.execCommand('removeFormat'); var b = block(); if (b) { b.removeAttribute('class'); } changed(); }], 'spacer',
      ['source', ICONS.code, 'Edit HTML source', toggleSource], ['full', ICONS.full, 'Full screen', function () { wrap.classList.toggle('is-full'); d.body.classList.toggle('ed-lock', wrap.classList.contains('is-full')); }]
    ];
    var btns = {}, blockSel;
    items.forEach(function (it) {
      if (it === 'sep') { bar.appendChild(el('i', 'ed-sep')); return; }
      if (it === 'spacer') { bar.appendChild(el('i', 'ed-spacer')); return; }
      if (it === 'block') {
        blockSel = el('select', 'ed-block'); blockSel.title = 'Text style'; blockSel.setAttribute('aria-label', 'Text style');
        [['p', 'Paragraph'], ['h2', 'Heading 1'], ['h3', 'Heading 2'], ['h4', 'Heading 3'], ['pre', 'Code block']].forEach(function (o) { blockSel.appendChild(new Option(o[1], o[0])); });
        blockSel.addEventListener('mousedown', save); blockSel.addEventListener('change', function () { setBlock(blockSel.value); }); bar.appendChild(blockSel); return;
      }
      var b = el('button', 'ed-btn', it[1]); b.type = 'button'; b.title = it[2]; b.setAttribute('aria-label', it[2]); b.dataset.cmd = it[0];
      b.addEventListener('mousedown', function (e) { e.preventDefault(); save(); }); b.addEventListener('click', function () { if (!srcMode || it[0] === 'source' || it[0] === 'full') it[3](); }); bar.appendChild(b); btns[it[0]] = b;
    });

    function refresh() {
      if (srcMode) return;
      ['bold', 'italic', 'underline', 'strikeThrough', 'insertUnorderedList', 'insertOrderedList'].forEach(function (c) { try { btns[c].classList.toggle('on', d.queryCommandState(c)); } catch (e) {} });
      var b = block(), a = b && (b.classList.contains('align-center') ? 'center' : b.classList.contains('align-right') ? 'right' : 'left');
      ['left', 'center', 'right'].forEach(function (k) { btns[k].classList.toggle('on', a === k); });
      btns.quote.classList.toggle('on', !!closest('blockquote')); btns.link.classList.toggle('on', !!closest('a'));
      var tag = b ? b.tagName.toLowerCase() : 'p'; blockSel.value = /^(h2|h3|h4|pre)$/.test(tag) ? tag : (/^h[56]$/.test(tag) ? 'h4' : 'p');
      var t = closest('table'); ctx.className = 'ed-ctx' + (t ? ' on' : ''); if (t && !ctx.firstChild) tableCtx();
    }

    /* ---- actions ---- */
    function doLink() {
      var a = closest('a'), r = sel(), txt = r && !r.collapsed ? r.toString() : '';
      ask('Link', [{ name: 'url', label: 'Address', value: a ? a.getAttribute('href') : '', ph: 'https://…' }, { name: 'text', label: 'Text', value: a ? a.textContent : txt, ph: 'Link text' }, { name: 'tab', label: 'Open in a new tab', type: 'check', value: a ? a.target === '_blank' : false }]).then(function (v) {
        if (!v) { restore(); return; } var u = safeUrl(v.url); restore(); if (!u) return;
        if (a && !v.text) { a.setAttribute('href', u); } else { insert('<a href="' + esc(u) + '"' + (v.tab ? ' target="_blank" rel="noopener noreferrer"' : '') + '>' + esc(v.text || u) + '</a>'); }
        if (a) { a.setAttribute('href', u); if (v.tab) { a.target = '_blank'; a.rel = 'noopener noreferrer'; } else { a.removeAttribute('target'); } } changed();
      });
    }
    function putImage(url, alt, cls) { restore(); insert('<img src="' + esc(url) + '" alt="' + esc(alt || '') + '"' + (cls ? ' class="' + cls + '"' : '') + '>'); }
    function doImage() {
      ask('Image', [{ name: 'file', label: 'Upload a picture', type: 'file' }, { name: 'url', label: '…or paste an image address', ph: 'https://…' }, { name: 'alt', label: 'Description (for accessibility)' },
        { name: 'cls', label: 'Size & position', type: 'select', value: '', options: [['', 'Natural size'], ['img-wide', 'Full width'], ['img-center', 'Centred'], ['img-left', 'Float left'], ['img-right', 'Float right']] }], 'Insert').then(function (v) {
        if (!v) { restore(); return; }
        if (v.file) { foot.textContent = 'Uploading…'; upload(v.file).then(function (u) { putImage(u, v.alt, v.cls); }).catch(function (e) { alert(e.message); sync(); }); }
        else if (safeUrl(v.url)) putImage(safeUrl(v.url), v.alt, v.cls); else restore();
      });
    }
    function doVideo() {
      ask('YouTube video', [{ name: 'url', label: 'Video link', ph: 'https://www.youtube.com/watch?v=…' }], 'Insert').then(function (v) {
        restore(); if (!v) return; var id = ytId(v.url); if (!id) { alert('That does not look like a YouTube link.'); return; }
        insert('<p><iframe class="yt-embed" src="https://www.youtube-nocookie.com/embed/' + id + '" title="Video" allowfullscreen></iframe></p><p><br></p>');
      });
    }
    function doTable() {
      ask('Table', [{ name: 'r', label: 'Rows', type: 'number', value: '3', min: 1, max: 20 }, { name: 'c', label: 'Columns', type: 'number', value: '3', min: 1, max: 8 }, { name: 'h', label: 'First row is a header', type: 'check', value: true }], 'Insert').then(function (v) {
        restore(); if (!v) return; var R = Math.max(1, Math.min(20, +v.r || 3)), C = Math.max(1, Math.min(8, +v.c || 3)), h = '<table><tbody>';
        for (var i = 0; i < R; i++) { h += '<tr>'; for (var j = 0; j < C; j++) h += (v.h && i === 0 ? '<th>' : '<td>') + '<br>' + (v.h && i === 0 ? '</th>' : '</td>'); h += '</tr>'; }
        insert(h + '</tbody></table><p><br></p>');
      });
    }
    function tableCtx() {
      [['+ Row', 'row+'], ['− Row', 'row-'], ['+ Column', 'col+'], ['− Column', 'col-'], ['Delete table', 'del']].forEach(function (o) {
        var b = el('button', 'ed-cbtn', o[0]); b.type = 'button'; b.addEventListener('mousedown', function (e) { e.preventDefault(); save(); });
        b.addEventListener('click', function () {
          restore(); var cell = closest('td,th'), tr = closest('tr'), t = closest('table'); if (!t) return; var idx = cell ? cell.cellIndex : 0;
          if (o[1] === 'del') t.remove();
          else if (o[1] === 'row+' && tr) { var n = tr.cloneNode(true); Array.prototype.forEach.call(n.cells, function (c) { c.innerHTML = '<br>'; }); tr.parentNode.insertBefore(n, tr.nextSibling); }
          else if (o[1] === 'row-' && tr) { if (t.rows.length > 1) tr.remove(); else t.remove(); }
          else if (o[1] === 'col+') Array.prototype.forEach.call(t.rows, function (r) { var c = r.cells[idx], n = d.createElement(c ? c.tagName : 'td'); n.innerHTML = '<br>'; r.insertBefore(n, c ? c.nextSibling : null); });
          else if (o[1] === 'col-') { if (t.rows[0].cells.length > 1) Array.prototype.forEach.call(t.rows, function (r) { if (r.cells[idx]) r.deleteCell(idx); }); else t.remove(); }
          changed(); refresh();
        });
        ctx.appendChild(b);
      });
    }
    function doCallout() {
      restore(); var c = closest('div.callout');
      if (c) { while (c.firstChild) c.parentNode.insertBefore(c.firstChild, c); c.remove(); changed(); return; }
      var tb = topBlock(); if (!tb) return;
      ask('Highlight box', [{ name: 'k', label: 'Style', type: 'select', value: 'info', options: [['info', 'Info (blue)'], ['tip', 'Tip (green)'], ['warn', 'Warning (amber)'], ['danger', 'Important (red)'], ['accent', 'Accent']] }], 'Apply').then(function (v) {
        restore(); if (!v) return; tb = topBlock(); if (!tb) return; var w = d.createElement('div'); w.className = 'callout callout-' + v.k; tb.parentNode.insertBefore(w, tb); w.appendChild(tb); changed();
      });
    }
    function doButton() {
      ask('Button link', [{ name: 'text', label: 'Label', ph: 'Join us' }, { name: 'url', label: 'Address', ph: 'https://… or /p/join' }, { name: 'k', label: 'Style', type: 'select', value: 'btn-primary', options: [['btn-primary', 'Solid'], ['btn-ghost', 'Outline']] }], 'Insert').then(function (v) {
        restore(); if (!v || !safeUrl(v.url)) return; insert('<a class="btn ' + (v.k === 'btn-ghost' ? 'btn-ghost' : 'btn-primary') + '" href="' + esc(safeUrl(v.url)) + '">' + esc(v.text || 'Click here') + '</a>&nbsp;');
      });
    }
    function toggleSource() {
      srcMode = !srcMode; wrap.classList.toggle('is-src', srcMode); btns.source.classList.toggle('on', srcMode);
      if (srcMode) { src.value = html().replace(/(<\/(p|h[1-6]|ul|ol|li|blockquote|pre|table|tr|div|figure)>)/g, '$1\n').replace(/\n+$/, ''); src.hidden = false; area.hidden = true; src.focus(); }
      else { area.innerHTML = clean(src.value) || '<p><br></p>'; src.hidden = true; area.hidden = false; area.focus(); }
      sync();
    }

    /* ---- events ---- */
    ['keyup', 'mouseup', 'focus'].forEach(function (e) { area.addEventListener(e, function () { save(); refresh(); }); });
    d.addEventListener('selectionchange', function () { if (d.activeElement === area) { save(); refresh(); } });
    area.addEventListener('input', changed); src.addEventListener('input', function () { dirty = true; sync(); });
    area.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); save(); doLink(); }
      if (e.key === 'Tab' && closest('td,th')) { e.preventDefault(); var c = closest('td,th'), n = c.nextElementSibling || (c.parentNode.nextElementSibling && c.parentNode.nextElementSibling.cells[0]); if (n) { var r = d.createRange(); r.selectNodeContents(n); r.collapse(true); var s = getSelection(); s.removeAllRanges(); s.addRange(r); } }
    });
    area.addEventListener('paste', function (e) {
      var cd = e.clipboardData; if (!cd) return;
      var f = Array.prototype.filter.call(cd.files || [], function (x) { return /^image\//.test(x.type); })[0];
      if (f) { e.preventDefault(); save(); foot.textContent = 'Uploading…'; upload(f).then(function (u) { putImage(u, '', 'img-wide'); }).catch(function (er) { alert(er.message); sync(); }); return; }
      var h = cd.getData('text/html'); e.preventDefault();
      if (h) d.execCommand('insertHTML', false, clean(h.replace(/<!--[\s\S]*?-->/g, ''))); else d.execCommand('insertText', false, cd.getData('text/plain'));
    });
    area.addEventListener('dragover', function (e) { if (e.dataTransfer && Array.prototype.some.call(e.dataTransfer.types, function (t) { return t === 'Files'; })) e.preventDefault(); });
    area.addEventListener('drop', function (e) {
      var f = e.dataTransfer && Array.prototype.filter.call(e.dataTransfer.files, function (x) { return /^image\//.test(x.type); })[0]; if (!f) return;
      e.preventDefault(); var r = d.caretRangeFromPoint ? d.caretRangeFromPoint(e.clientX, e.clientY) : null; if (r) { var s = getSelection(); s.removeAllRanges(); s.addRange(r); save(); }
      foot.textContent = 'Uploading…'; upload(f).then(function (u) { putImage(u, '', 'img-wide'); }).catch(function (er) { alert(er.message); sync(); });
    });
    area.addEventListener('click', function (e) { if (e.target.tagName === 'A') e.preventDefault(); });
    var form = ta.form; if (form) { form.addEventListener('submit', function () { dirty = false; if (srcMode) area.innerHTML = clean(src.value); sync(); }); }
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    d.addEventListener('keydown', function (e) { if (e.key === 'Escape' && wrap.classList.contains('is-full') && !d.querySelector('.ed-ov')) { wrap.classList.remove('is-full'); d.body.classList.remove('ed-lock'); } });
    sync(); dirty = false;
  }
  d.querySelectorAll('textarea[data-editor]').forEach(init);
})();
