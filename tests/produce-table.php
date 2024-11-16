<?php

$readme_file = file_get_contents(__DIR__ . '/../README.md.template');

// The directory holding all PHP versions and the extensions in them, are called
// "module-list". Technically I just called them extensions, but the word
// "module" is used both in the produced output, and the in the flag to the
// command itself (php -m).

$module_list = __DIR__ . '/../module-list';

// The directory will contain one txt file per PHP version. Find all of these
// first.
$file_list = glob($module_list . '/*.txt');

$extensions = [];
$php_versions = [];

// Now for each of these files, extract the PHP version from the filename.
foreach ($file_list as $file) {
    $version = basename($file, '.txt');
    $lines = file($file);
    foreach ($lines as $line) {
        $line = trim($line);
        // If the line is empty, skip it.
        if (empty($line)) {
            continue;
        }
        // If the line starts with a "[" it is a header, skip it.
        if (substr($line, 0, 1) === '[') {
            continue;
        }
        $extensions[$line] = true;
        if (empty($php_versions[$version])) {
            $php_versions[$version] = [];
        }
        $php_versions[$version][$line] = true;
    }
}

// Now let's produce the table.
$header = '| Name |';
$second_header_line = '| --- |';
foreach (array_keys($php_versions) as $php_version) {
    $header .= " $php_version |";
    $second_header_line .= ' --- |';
}

$rows = '';
foreach ($extensions as $extension => $true) {
    $rows .= "| $extension |";
    foreach ($php_versions as $php_version => $extensions) {
        $found = false;
        if (!empty($extensions[$extension])) {
            $rows .= ' ✅ |';
        } else {
            $rows .= ' ❌ |';
        }
    }
    $rows .= "\n";
}

$table = "$header\n$second_header_line\n$rows";

// Now replace the magic string "INSERT_TABLE_HERE" with the actual table.
$readme_file = str_replace('INSERT_TABLE_HERE', $table, $readme_file);
file_put_contents(__DIR__ . '/../README.md', $readme_file);