<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require __DIR__ . '/../../../../../tests/Functional/bootstrap.php';

function fixture_path(string $relPath): string {
    return __DIR__ . '/../fixtures/' . $relPath;
}

function fixture_content(string $relPath): string {
    return file_get_contents(fixture_path($relPath));
}
