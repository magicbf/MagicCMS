<footer class="site-footer">
  <div class="container footer-inner">
    <span><?= e(str_replace('{year}', gmdate('Y'), (string) setting('site.footer', ''))) ?></span>
    <nav class="footer-nav" aria-label="Footer"><?php foreach (MagicCMS\Core\Nav::tree('footer') as $i): ?><a href="<?= e($i['url']) ?>"><?= e($i['label']) ?></a><?php endforeach ?></nav>
    <span class="powered">Powered by <a href="https://github.com/magicbf/MagicCMS" rel="noopener">MagicCMS</a> <?= e(MCMS_VERSION) ?></span>
  </div>
</footer>
