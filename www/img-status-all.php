<?php
require_once __DIR__ . '/../include/init.inc.php';
require_once __DIR__ . '/../include/lib_revcheck.inc.php';

$idx = new SQLite3(SQLITE_DIR . 'status.sqlite');

$language = revcheck_available_languages($idx);
sort($language);

foreach ($language as $lang) {
    $stats = get_lang_stats($idx, $lang);

    if (!$stats) die("No stats for $lang");

    $percent_tmp[] = round($stats['TranslatedOk']['total'] * 100 / $stats['total']['total']);
    $legend_tmp[] = $lang;
}

$percent = array_values($percent_tmp);
$legend = array_values($legend_tmp);

$colors = ['#9999CC', '#99CC99', '#CC9999'];
$bw = 44; $gap = 18; $left = 50; $right = 24;
$top = 60; $plot_h = 200; $bottom = 38;
$width  = max(600, $left + count($percent) * ($bw + $gap) - $gap + $right);
$height = $top + $plot_h + $bottom;
$base   = $top + $plot_h;

header('Content-Type: image/svg+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 <?= $width ?> <?= $height ?>" width="<?= $width ?>" height="<?= $height ?>" role="img" aria-label="PHP Translation Status">
  <style>text{font:11px sans-serif;fill:#000}.t{font:bold 14px sans-serif}.g{stroke:#efefef}.b{stroke:#bbccff}</style>
  <text class="t" x="0" y="22">PHP Translation Status</text>
  <text x="0" y="40">Files up to date per language</text>
<?php foreach ([0, 25, 50, 75, 100] as $tick): $y = $base - $plot_h * $tick / 100; ?>
  <line class="g" x1="<?= $left ?>" y1="<?= $y ?>" x2="<?= $width - $right + 8 ?>" y2="<?= $y ?>"/>
  <text x="<?= $left - 8 ?>" y="<?= $y + 4 ?>" text-anchor="end"><?= $tick ?>%</text>
<?php endforeach; ?>
  <line class="b" x1="<?= $left ?>" y1="<?= $base ?>" x2="<?= $width - $right + 8 ?>" y2="<?= $base ?>"/>
<?php foreach ($percent as $i => $p): $x = $left + $i * ($bw + $gap); $h = $plot_h * $p / 100; $by = $base - $h; $cx = $x + $bw / 2; ?>
  <rect x="<?= $x ?>" y="<?= $by ?>" width="<?= $bw ?>" height="<?= $h ?>" rx="3" fill="<?= $colors[$i % 3] ?>"><title><?= htmlspecialchars($legend[$i]) ?>: <?= $p ?>%</title></rect>
  <text x="<?= $cx ?>" y="<?= $by - 6 ?>" text-anchor="middle"><?= $p ?>%</text>
  <text x="<?= $cx ?>" y="<?= $base + 18 ?>" text-anchor="middle"><?= htmlspecialchars($legend[$i]) ?></text>
<?php endforeach; ?>
</svg>
