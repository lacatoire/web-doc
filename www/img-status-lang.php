<?php

require_once __DIR__ . '/../include/init.inc.php';
require_once __DIR__ . '/../include/lib_revcheck.inc.php';
require_once __DIR__ . '/../include/lib_proj_lang.inc.php';

$idx = new SQLite3(SQLITE_DIR . 'status.sqlite');

$available_langs = revcheck_available_languages($idx);

$lang = $_GET['lang'];

if (!in_array($lang, $available_langs)) {
    die("Information for $lang language does not exist.");
} else {
    generate_image($lang, $idx);
}

function generate_image($lang, $idx) {
    global $LANGUAGES;

    $stats = get_lang_stats($idx, $lang);

    $up_to_date = $stats['TranslatedOk']['total'] ?? 0;
    //
    $outdated = $stats['TranslatedOld']['total'] ?? 0;
    //
    $missing = $stats['Untranslated']['total'] ?? 0;
    //
    $no_tag = $stats['RevTagProblem']['total'] ?? 0;

    $data = array(
        $up_to_date,
        $outdated,
        $missing,
        $no_tag
    );

    $percent = array();
    $total = array_sum($data); // Total ammount in EN manual (to calculate percentage values)
    $total_files_lang = $total - $missing; // Total ammount of files in translation

    foreach ($data as $value) {
        $percent[] = round($value * 100 / $total);
    }

    $legend = array($percent[0] . '% up to date ('.$up_to_date.')', $percent[1] . '% outdated ('.$outdated.')', $percent[2] . '% missing ('.$missing.')', $percent[3] . '% without EN-Revision ('.$no_tag.')');
    $title = 'Details for '.$LANGUAGES[$lang].' PHP Manual';
    $colors = ['#61A9F3', '#F381B9', '#61E3A9', '#85ED82'];

    $W = 680; $H = 300; $cx = 140; $cy = 165; $ro = 110; $ri = 64;

    header('Content-Type: image/svg+xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$W.' '.$H.'" width="'.$W.'" height="'.$H.'" role="img" aria-label="'.htmlspecialchars($title).'">';
    echo '<style>text{font:11px sans-serif;fill:#000}.t{font:bold 14px sans-serif}.s{fill:#8b0000}path{stroke:#fff;stroke-width:2}</style>';
    echo '<text class="t" x="20" y="28">'.htmlspecialchars($title).'</text>';
    echo '<text class="s" x="20" y="46">(Total: '.$total_files_lang.' files)</text>';

    $angle = -M_PI / 2;
    foreach ($data as $i => $value) {
        if ($value <= 0) continue;
        $sweep = ($value / $total) * 2 * M_PI;
        $end = $angle + $sweep;
        $sx1 = $cx + $ro * cos($angle); $sy1 = $cy + $ro * sin($angle);
        $ex1 = $cx + $ro * cos($end);   $ey1 = $cy + $ro * sin($end);
        $sx2 = $cx + $ri * cos($end);   $sy2 = $cy + $ri * sin($end);
        $ex2 = $cx + $ri * cos($angle); $ey2 = $cy + $ri * sin($angle);
        $large = ($end - $angle) > M_PI ? 1 : 0;
        $d = sprintf('M %.2f %.2f A %d %d 0 %d 1 %.2f %.2f L %.2f %.2f A %d %d 0 %d 0 %.2f %.2f Z',
            $sx1, $sy1, $ro, $ro, $large, $ex1, $ey1, $sx2, $sy2, $ri, $ri, $large, $ex2, $ey2);
        echo '<path d="'.$d.'" fill="'.$colors[$i].'"><title>'.htmlspecialchars($legend[$i]).'</title></path>';
        $angle = $end;
    }

    $lx = 290; $ly = 110;
    foreach ($legend as $i => $line) {
        $y = $ly + $i * 26;
        echo '<rect x="'.$lx.'" y="'.($y - 11).'" width="14" height="14" rx="2" fill="'.$colors[$i].'"/>';
        echo '<text x="'.($lx + 22).'" y="'.$y.'">'.htmlspecialchars($line).'</text>';
    }

    echo '<text x="'.($W - 18).'" y="'.($H - 8).'" text-anchor="end">'.date('m/d/Y').'</text>';
    echo '</svg>';
}
