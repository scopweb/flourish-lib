<?php
/**
 * Flourish test bootstrap
 *
 * Provides:
 *  - A PSR-0-ish class autoloader for lib/, enums/, container/, interfaces/,
 *    querybuilder/ and providers/
 *  - A strict diagnostics collector that turns every deprecation, warning and
 *    notice raised during the test run into a reported failure
 *  - The global test() helper used by every test file
 *
 * @package Flourish Tests
 */

if (!defined('FLOURISH_ROOT')) {
	define('FLOURISH_ROOT', dirname(__DIR__));
}

if (!defined('FLOURISH_TEST_ROOT')) {
	define('FLOURISH_TEST_ROOT', __DIR__);
}

/**
 * Collects every diagnostic (deprecation, warning, notice) raised by PHP
 */
class FlourishDiagnostics
{
	/** @var array  The diagnostics collected during the run */
	public static $items = array();

	/** @var bool  Whether the run should fail when diagnostics are found */
	public static $strict = true;

	/** @var bool  Whether the handler is installed */
	private static $installed = false;

	/**
	 * Installs the diagnostics handler
	 *
	 * @return void
	 */
	public static function install()
	{
		if (self::$installed) {
			return;
		}
		self::$installed = true;
		error_reporting(E_ALL);
		ini_set('display_errors', '0');
		set_error_handler(array('FlourishDiagnostics', 'handle'));
	}

	/**
	 * Handles a PHP error
	 *
	 * @param  int    $errno   The error number
	 * @param  string $errstr  The error message
	 * @param  string $errfile The file the error occurred in
	 * @param  int    $errline The line the error occurred on
	 * @return bool  TRUE to suppress PHP's default handling
	 */
	public static function handle($errno, $errstr, $errfile = '', $errline = 0)
	{
		if (!(error_reporting() & $errno)) {
			return false;
		}

		self::$items[] = array(
			'level'   => $errno,
			'type'    => self::getTypeName($errno),
			'message' => $errstr,
			'file'    => $errfile,
			'line'    => $errline
		);

		// Returning TRUE suppresses the default PHP error output, the
		// diagnostics are reported by the test runner instead.
		return true;
	}

	/**
	 * Gets a human readable name for an error level
	 *
	 * @param  int $errno The error number
	 * @return string  The name of the error level
	 */
	public static function getTypeName($errno)
	{
		$names = array(
			E_ERROR             => 'E_ERROR',
			E_WARNING           => 'E_WARNING',
			E_PARSE             => 'E_PARSE',
			E_NOTICE            => 'E_NOTICE',
			E_CORE_ERROR        => 'E_CORE_ERROR',
			E_CORE_WARNING      => 'E_CORE_WARNING',
			E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
			E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
			E_USER_ERROR        => 'E_USER_ERROR',
			E_USER_WARNING      => 'E_USER_WARNING',
			E_USER_NOTICE       => 'E_USER_NOTICE',
			E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
			E_DEPRECATED        => 'E_DEPRECATED',
			E_USER_DEPRECATED   => 'E_USER_DEPRECATED'
		);

		return isset($names[$errno]) ? $names[$errno] : 'E_UNKNOWN';
	}

	/**
	 * Gets the diagnostics that must fail a production run
	 *
	 * @return array  The deprecations, warnings and notices
	 */
	public static function getBlocking()
	{
		return array_values(array_filter(self::$items, function($item) {
			return in_array($item['type'], array('E_DEPRECATED', 'E_USER_DEPRECATED', 'E_WARNING', 'E_USER_WARNING', 'E_NOTICE', 'E_USER_NOTICE'), true);
		}));
	}
}

/**
 * Builds the map of class names to the file that declares them
 *
 * A file can declare more than one type, so the map cannot be built by
 * guessing the file name from the class name.
 *
 * @return array  The map of class names to file paths
 */
function flourish_build_class_map()
{
	$map = array();
	$directories = array('lib', 'enums', 'container', 'interfaces', 'querybuilder', 'providers');

	foreach ($directories as $directory) {
		$path = FLOURISH_ROOT . '/' . $directory;
		if (!is_dir($path)) {
			continue;
		}
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
		foreach ($iterator as $file) {
			if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
				continue;
			}
			$source = file_get_contents($file->getPathname());
			if (preg_match_all('/^\s*(?:final\s+|abstract\s+)?(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $source, $matches)) {
				foreach ($matches[1] as $name) {
					if (!isset($map[$name])) {
						$map[$name] = $file->getPathname();
					}
				}
			}
		}
	}

	return $map;
}

$GLOBALS['flourish_class_map'] = flourish_build_class_map();

/**
 * Autoloads Flourish classes from the source directories
 *
 * @param  string $class The class to load
 * @return bool  TRUE if the class was found
 */
function flourish_autoload($class)
{
	if (strpos($class, '\\') !== false) {
		$class = str_replace('\\', '/', $class);
	}

	$directories = array('lib', 'enums', 'container', 'interfaces', 'querybuilder', 'providers');
	foreach ($directories as $directory) {
		$file = FLOURISH_ROOT . '/' . $directory . '/' . $class . '.php';
		if (file_exists($file)) {
			require_once $file;
			return true;
		}
	}

	if (isset($GLOBALS['flourish_class_map'][$class])) {
		require_once $GLOBALS['flourish_class_map'][$class];
		return true;
	}

	return false;
}

spl_autoload_register('flourish_autoload');

FlourishDiagnostics::install();

$GLOBALS['flourish_test_results'] = array();

/**
 * Runs a single test
 *
 * @param  string   $description The description of the test
 * @param  callable $callable    The test to run
 * @return mixed  TRUE when the test passed, otherwise the failure message
 */
function test($description, $callable)
{
	try {
		$result = $callable();
	}
	catch (Throwable $e) {
		$result = get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine();
	}

	if ($result === true) {
		$GLOBALS['flourish_test_results'][] = array('test' => $description, 'result' => 'PASS', 'message' => '');
		echo "  [PASS] $description\n";
	}
	else {
		$message = is_string($result) ? $result : 'Unknown failure';
		$GLOBALS['flourish_test_results'][] = array('test' => $description, 'result' => 'FAIL', 'message' => $message);
		echo "  [FAIL] $description\n         -> $message\n";
	}

	return $result;
}

/**
 * Marks a test as skipped
 *
 * @param  string $description The description of the test
 * @param  string $reason      Why the test was skipped
 * @return bool  Always TRUE
 */
function skip($description, $reason)
{
	$GLOBALS['flourish_test_results'][] = array('test' => $description, 'result' => 'SKIP', 'message' => $reason);
	echo "  [SKIP] $description\n         -> $reason\n";
	return true;
}

/**
 * Prints the summary of the current test file and returns the exit code
 *
 * @param  string $label The label of the test file
 * @return int  The exit code, 0 when everything passed
 */
function flourish_test_summary($label = '')
{
	$results = $GLOBALS['flourish_test_results'];
	$passed = 0;
	$failed = 0;
	$skipped = 0;
	foreach ($results as $result) {
		if ($result['result'] === 'PASS') {
			$passed++;
		}
		elseif ($result['result'] === 'SKIP') {
			$skipped++;
		}
		else {
			$failed++;
		}
	}

	$diagnostics = FlourishDiagnostics::getBlocking();
	$diagnosticsCount = count($diagnostics);

	echo "\n";
	echo str_repeat('-', 60) . "\n";
	echo "FILE: " . ($label !== '' ? $label : '(inline)') . "\n";
	echo "Tests: " . count($results) . " | Passed: $passed | Failed: $failed | Skipped: $skipped\n";
	echo "Diagnostics (deprecations/warnings/notices): $diagnosticsCount\n";

	if ($diagnosticsCount > 0) {
		echo "\nDiagnostics:\n";
		foreach ($diagnostics as $diagnostic) {
			$file = $diagnostic['file'];
			if ($file !== '' && strpos($file, FLOURISH_ROOT) === 0) {
				$file = substr($file, strlen(FLOURISH_ROOT) + 1);
			}
			echo "  - {$diagnostic['type']}: {$diagnostic['message']}\n    at $file:{$diagnostic['line']}\n";
		}
	}

	return ($failed === 0 && $diagnosticsCount === 0) ? 0 : 1;
}
