<?php
// Safe PHP-based file modification for Davidsons UI

$file = 'ffl-bro-enhanced-pro.php';
$content = file_get_contents($file);

// 1. Add davidsons to all_distributors array
$content = str_replace(
    "array('lipseys', 'rsr_group', 'orion')",
    "array('lipseys', 'rsr_group', 'orion', 'davidsons')",
    $content
);

// 2. Add davidsons to load_distributors function
$content = str_replace(
    "'orion' => array('name' => 'Orion', 'status' => 'ready')",
    "'orion' => array('name' => 'Orion', 'status' => 'ready'),\n            'davidsons' => array('name' => 'Davidsons', 'status' => 'ready')",
    $content
);

// 3. Add davidsons to display names (find the pattern)
if (strpos($content, "'orion' => 'Orion'") !== false) {
    $content = str_replace(
        "'orion' => 'Orion'",
        "'orion' => 'Orion',\n            'davidsons' => 'Davidsons'",
        $content
    );
}

// Write the modified content back
file_put_contents($file, $content);
echo "Davidsons UI modifications applied!\n";
