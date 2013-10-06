<?php

$path = $argv[1];
$outputPath = $argv[2];

function getSections($path) {
    $endpoints = file_get_contents($path);
    $endpoints = json_decode($endpoints, true);
    $sections = current($endpoints);

    $foo = array();

    foreach ($sections as $s) {
        $foo[strtolower($s['name'])] = $s['methods'];
    }

    return $foo;
}

$sections = getSections($path);

foreach (array_keys($sections) as $section) {
    $cmd = sprintf(
        'php convert.php %s | python -mjson.tool > %s',
        escapeshellarg($section),
        escapeshellarg($outputPath . '/' . preg_replace('~[^a-z0-9]~', '', $section) . '.json')
    );
    exec($cmd);
}
