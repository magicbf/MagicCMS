/* Hero banner: autoplay, arrows, dots, swipe, keyboard, pause-on-hover and mouse parallax. */
(function () {
  'use strict';
  var reduced = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.querySelectorAll('[data-hero]').forEach(function (h) {
    var slides = [].slice.call(h.querySelectorAll('.hero-slide')), dots = [].slice.call(h.querySelectorAll('.hero-dot'));
    var iv = (parseInt(h.dataset.interval, 10) || 7) * 1000, auto = h.dataset.autoplay === '1' && !reduced, pauseOn = h.dataset.pause === '1';
    var idx = 0, timer = null, hold = false, videoOn = false;
    h.style.setProperty('--iv', iv + 'ms');

    function go(n) {
      if (slides.length < 2) return;
      n = (n + slides.length) % slides.length; if (n === idx) return;
      var prev = slides[idx], cur = slides[n];
      [].forEach.call(prev.querySelectorAll('.hero-video.is-playing'), stopVideo);
      prev.classList.remove('is-active'); prev.classList.add('is-leaving'); prev.setAttribute('aria-hidden', 'true');
      setTimeout(function () { prev.classList.remove('is-leaving'); }, 900);
      cur.classList.remove('is-leaving'); cur.classList.add('is-active'); cur.removeAttribute('aria-hidden');
      slides.forEach(function (s) { [].forEach.call(s.querySelectorAll('a'), function (a) { a.tabIndex = s === cur ? 0 : -1; }); });
      dots.forEach(function (d, i) { d.classList.toggle('is-active', i === n); });
      idx = n; schedule();
    }
    function schedule() { clearTimeout(timer); var paused = hold || videoOn; if (auto && !paused) timer = setTimeout(function () { go(idx + 1); }, iv); h.classList.toggle('is-paused', paused || !auto); }
    // YouTube loads only after a click (privacy: nothing is requested from YouTube before that), via the no-cookie domain
    function playVideo(box) {
      var id = box.dataset.yt; if (!/^[A-Za-z0-9_-]{11}$/.test(id) || box.classList.contains('is-playing')) return;
      var f = document.createElement('iframe');
      f.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0&modestbranding=1&playsinline=1';
      f.title = 'YouTube video'; f.className = 'hero-iframe'; f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen'; f.allowFullscreen = true;
      box.classList.add('is-playing'); box.appendChild(f); videoOn = true; schedule();
    }
    function stopVideo(box) { var f = box.querySelector('iframe'); if (f) f.remove(); box.classList.remove('is-playing'); videoOn = !!h.querySelector('.hero-video.is-playing'); schedule(); }
    h.addEventListener('click', function (e) { var b = e.target.closest('.hero-videobtn'); if (b) playVideo(b.closest('.hero-video')); });
    var nx = h.querySelector('.hero-arrow.next'), pv = h.querySelector('.hero-arrow.prev');
    nx && nx.addEventListener('click', function () { go(idx + 1); });
    pv && pv.addEventListener('click', function () { go(idx - 1); });
    dots.forEach(function (d, i) { d.addEventListener('click', function () { go(i); }); });
    h.tabIndex = slides.length > 1 ? 0 : -1;
    h.addEventListener('keydown', function (e) { if (e.key === 'ArrowRight') go(idx + 1); else if (e.key === 'ArrowLeft') go(idx - 1); });
    if (pauseOn) {
      ['mouseenter', 'focusin'].forEach(function (ev) { h.addEventListener(ev, function () { hold = true; schedule(); }); });
      ['mouseleave', 'focusout'].forEach(function (ev) { h.addEventListener(ev, function () { hold = false; schedule(); }); });
    }
    document.addEventListener('visibilitychange', function () { hold = document.hidden; schedule(); });

    var x0 = null;
    h.addEventListener('pointerdown', function (e) { if (e.pointerType !== 'mouse') x0 = e.clientX; });
    h.addEventListener('pointerup', function (e) { if (x0 === null) return; var dx = e.clientX - x0; x0 = null; if (Math.abs(dx) > 50) go(idx + (dx < 0 ? 1 : -1)); });

    if (h.dataset.parallax === '1' && !reduced && matchMedia('(pointer: fine)').matches) {
      h.addEventListener('mousemove', function (e) {
        var r = h.getBoundingClientRect();
        h.style.setProperty('--px', (((e.clientX - r.left) / r.width) - .5) * -18);
        h.style.setProperty('--py', (((e.clientY - r.top) / r.height) - .5) * -12);
      });
      h.addEventListener('mouseleave', function () { h.style.setProperty('--px', 0); h.style.setProperty('--py', 0); });
    }
    schedule();
  });
})();
