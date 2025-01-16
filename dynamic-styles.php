<?php

// Localiser WordPress correctement
$wordpress_path = $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
if (!file_exists($wordpress_path)) {
    die('Error: Cannot locate WordPress core.');
}

require_once $wordpress_path;

header("Content-Type: text/css");

$options = get_option('diy_colors');

function diy_validate_color($color, $default) {
    if (preg_match('/^#([A-Fa-f0-9]{3}){1,2}$|^rgba?\((\d{1,3},\s?){2,3}(\d?\.?\d+)?\)$/', $color)) {
        return $color;
    }
    return $default;
}

$color_base = diy_validate_color($options['diy_color_base'] ?? '', 'rgba(110, 93, 198, 1)');
$color_nicotine = diy_validate_color($options['diy_color_nicotine'] ?? '', 'rgba(170,26,140,0.61)');
$color_aroma = diy_validate_color($options['diy_color_aroma'] ?? '', 'rgba(191,0,0,1)');
?>

:root {
    --color-base: <?php echo esc_html($color_base); ?> !important;
    --color-nicotine: <?php echo esc_html($color_nicotine); ?> !important;
    --color-aroma: <?php echo esc_html($color_aroma); ?> !important;
}