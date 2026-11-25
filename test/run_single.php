<?php
/**
 * Runs a single Flourish test file in an isolated process
 *
 * Usage: php test/run_single.php <test file>
 *
 * @package Flourish Tests
 */

require __DIR__ . '/bootstrap.php';

$file = isset($argv[1]) ? $argv[1] : '';
if ($file === '' || !file_exists($file)) {
	fwrite(STDERR, "Test file not found: $file\n");
	exit(2);
}

echo "Running " . basename($file) . " on PHP " . PHP_VERSION . "\n";

require $file;

exit(flourish_test_summary(basename($file)));
