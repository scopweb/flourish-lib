<?php
/**
 * Flourish Framework - Compatibility Test Suite
 *
 * Runs every test file in this directory, each one in its own isolated PHP
 * process so that a fatal error in one file cannot hide the results of the
 * others.
 *
 * Execute with:  php test/run_all_tests.php
 *
 * The run fails if any test fails or if PHP raises a single deprecation,
 * warning or notice while the tests are running.
 *
 * @package Flourish Tests
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * Runs the whole test suite
 */
class FlourishTestRunner
{
	/** @var array  The results of every test file */
	private $files = array();

	/** @var float  The time the run started */
	private $startTime;

	/**
	 * Starts the runner
	 */
	public function __construct()
	{
		$this->startTime = microtime(true);
	}

	/**
	 * Discovers the test files to run
	 *
	 * @return array  The paths of the test files
	 */
	private function discoverTestFiles()
	{
		$files = array();
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
		foreach ($iterator as $file) {
			if (!$file->isFile()) {
				continue;
			}
			$name = $file->getFilename();
			if (substr($name, -9) !== '_test.php') {
				continue;
			}
			if (in_array($name, array('run_all_tests.php', 'run_single.php'), true)) {
				continue;
			}
			$files[] = $file->getPathname();
		}

		sort($files);
		return $files;
	}

	/**
	 * Runs a single test file in a separate process
	 *
	 * @param  string $file The path of the test file
	 * @return array  The result of the test file
	 */
	private function runTestFile($file)
	{
		$command = escapeshellarg(PHP_BINARY) . ' -d error_reporting=-1 -d display_errors=1 ' .
			escapeshellarg(__DIR__ . '/run_single.php') . ' ' . escapeshellarg($file);

		$descriptors = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w')
		);

		$pipes = array();
		$process = proc_open($command, $descriptors, $pipes);
		if (!is_resource($process)) {
			return array('file' => basename($file), 'exit_code' => -1, 'output' => 'Could not start the test process');
		}

		fclose($pipes[0]);
		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$exitCode = proc_close($process);

		return array(
			'file'      => basename($file),
			'exit_code' => $exitCode,
			'output'    => $stdout . $stderr
		);
	}

	/**
	 * Runs every test file
	 */
	public function runAllTests()
	{
		echo "Flourish compatibility test suite\n";
		echo str_repeat('=', 60) . "\n";
		echo 'PHP Version: ' . PHP_VERSION . "\n";
		echo 'PHP Binary:  ' . PHP_BINARY . "\n";
		echo 'Start Time:  ' . date('Y-m-d H:i:s') . "\n\n";

		$testFiles = $this->discoverTestFiles();
		if (!$testFiles) {
			echo "No test files found.\n";
			return 1;
		}

		$failedFiles = 0;
		foreach ($testFiles as $testFile) {
			echo "Running " . basename($testFile) . "\n";
			$result = $this->runTestFile($testFile);
			$this->files[] = $result;
			echo $result['output'];
			if ($result['exit_code'] !== 0) {
				$failedFiles++;
				echo ">>> " . basename($testFile) . " FAILED (exit code " . $result['exit_code'] . ")\n";
			}
			else {
				echo ">>> " . basename($testFile) . " OK\n";
			}
			echo "\n";
		}

		return $this->generateReport($failedFiles);
	}

	/**
	 * Prints the final report and returns the exit code
	 *
	 * @param  int $failedFiles The number of failed test files
	 * @return int  The exit code
	 */
	private function generateReport($failedFiles)
	{
		$duration = round(microtime(true) - $this->startTime, 2);

		echo str_repeat('=', 60) . "\n";
		echo "TEST SUMMARY\n";
		echo str_repeat('=', 60) . "\n";
		echo 'Test files: ' . count($this->files) . "\n";
		echo 'Failed files: ' . $failedFiles . "\n";
		echo "Duration: {$duration}s\n";

		if ($failedFiles === 0) {
			echo "\nALL TEST FILES PASSED on PHP " . PHP_VERSION . "\n";
		}
		else {
			echo "\nSOME TEST FILES FAILED on PHP " . PHP_VERSION . "\n";
		}

		$this->saveJsonReport();

		return $failedFiles === 0 ? 0 : 1;
	}

	/**
	 * Saves the report as JSON
	 */
	private function saveJsonReport()
	{
		$reportDirectory = __DIR__ . '/reports';
		if (!is_dir($reportDirectory)) {
			mkdir($reportDirectory, 0755, true);
		}

		$report = array(
			'timestamp'    => date('c'),
			'php_version'  => PHP_VERSION,
			'php_binary'   => PHP_BINARY,
			'test_files'   => count($this->files),
			'failed_files' => count(array_filter($this->files, function($file) {
				return $file['exit_code'] !== 0;
			})),
			'files'        => $this->files
		);

		file_put_contents($reportDirectory . '/test_results.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	}
}

$runner = new FlourishTestRunner();
exit($runner->runAllTests());
