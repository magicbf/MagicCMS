<?php
/**
 * Build an update package of THIS checkout (for maintainers / GitHub releases):
 *   php tools/build-release.php [output-dir] ["release notes"]
 * Produces MagicCMS-<version>.zip (+ .sha256) containing only code folders and a release.json with per-file hashes.
 * config/, uploads/, cache/, backups/, setup.php and VCS files are never included.
 */
if (PHP_SAPI !== 'cli') {
    exit;
}
$root = dirname(__DIR__);
preg_match("/define\('MCMS_VERSION', '([^']+)'\)/", (string) file_get_contents($root . '/core/Bootstrap.php'), $m) or exit("Cannot read the version.\n");
$version = $m[1];
$out = rtrim($argv[1] ?? $root, '/');
$notes = $argv[2] ?? '';
$dirs = ['assets', 'core', 'docs', 'lang', 'migrations', 'modules', 'panels', 'plugins', 'themes', 'tools', 'views'];
$files = ['index.php', 'README.md', 'LICENSE', 'nginx.conf.sample'];
$ext = '/\.(php|css|js|json|md|txt|svg|png|jpe?g|gif|webp|ico|woff2?|sql|html|sample|ics)$/i';
$list = [];
foreach ($dirs as $d) {
    if (!is_dir("$root/$d")) {
        continue;
    }
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$d", FilesystemIterator::SKIP_DOTS)) as $f) {
        $rel = substr($f->getPathname(), strlen($root) + 1);
        if ($f->isFile() && !$f->isLink() && preg_match($ext, $rel) && !preg_match('#(^|/)\.#', $rel)) {
            $list[] = $rel;
        }
    }
}
foreach ($files as $f) {
    if (is_file("$root/$f")) {
        $list[] = $f;
    }
}
sort($list);
$hashes = [];
foreach ($list as $f) {
    $hashes[$f] = hash_file('sha256', "$root/$f");
}
$release = ['name' => 'MagicCMS', 'version' => $version, 'built' => gmdate('c'), 'min_php' => '8.1', 'requires_ext' => ['pdo_mysql', 'mbstring', 'gd'], 'notes' => $notes, 'files' => $hashes];
$zipPath = "$out/MagicCMS-$version.zip";
@unlink($zipPath);
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE) === true or exit("Cannot create $zipPath\n");
$zip->addFromString('release.json', json_encode($release, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
foreach ($list as $f) {
    $zip->addFile("$root/$f", $f);
}
$zip->close();
file_put_contents($zipPath . '.sha256', hash_file('sha256', $zipPath) . '  ' . basename($zipPath) . "\n");
echo "Built $zipPath (" . count($list) . " files, v$version)\nSHA-256: " . hash_file('sha256', $zipPath) . "\n";
