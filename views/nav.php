<?php /** @var array $items @var string $class */ ?>
<nav class="<?= e($class ?? 'site-nav') ?>" aria-label="<?= e(t('Main')) ?>">
  <button class="nav-burger" aria-expanded="false" aria-label="<?= e(t('Menu')) ?>"><span></span><span></span><span></span></button>
  <ul class="nav-list">
  <?php foreach ($items as $i): ?>
    <li class="nav-item<?= $i['active'] ? ' is-active' : '' ?><?= $i['children'] ? ' has-sub' : '' ?><?= $i['hideMobile'] ? ' hide-mobile' : '' ?>">
      <a class="nav-btn" href="<?= e($i['url']) ?>"<?= $i['new_tab'] ? ' target="_blank" rel="noopener noreferrer"' : '' ?><?= $i['color'] ? ' style="--btn-color:' . e($i['color']) . '"' : '' ?><?= $i['active'] ? ' aria-current="page"' : '' ?>>
        <?php if ($i['icon'] !== ''): ?><i class="icon" aria-hidden="true"><?= e($i['icon']) ?></i><?php endif ?><span><?= e($i['label']) ?></span><?php if ($i['badge'] !== ''): ?><b class="nav-badge"><?= e($i['badge']) ?></b><?php endif ?>
      </a>
      <?php if ($i['children']): ?>
      <ul class="nav-sub">
        <?php foreach ($i['children'] as $c): ?>
        <li><a class="nav-btn nav-btn-sub<?= $c['active'] ? ' is-active' : '' ?>" href="<?= e($c['url']) ?>"<?= $c['new_tab'] ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?php if ($c['icon'] !== ''): ?><i class="icon" aria-hidden="true"><?= e($c['icon']) ?></i><?php endif ?><span><?= e($c['label']) ?></span></a></li>
        <?php endforeach ?>
      </ul>
      <?php endif ?>
    </li>
  <?php endforeach ?>
  </ul>
</nav>
