<?php
/**
 * Audit of the APIs that no longer exist in PHP 8, and execution of the code
 * paths that the rest of the suite never reaches.
 *
 * Why this file exists:
 *
 * The diagnostics collector only reports what PHP actually raises while the
 * test runs. A call to a function that was removed from PHP (such as
 * get_magic_quotes_gpc()) is a *fatal error*, not a deprecation, so it stays
 * invisible until somebody calls that exact method in production. The
 * fCookie::get(), fURL::removeFromQueryString() and fURL::replaceInQueryString()
 * methods were affected, and none of them is exercised by the other test
 * files.
 *
 * So this file does two things:
 *
 *  1. STATIC AUDIT  - Scans every source file with the tokenizer looking for
 *                      calls to functions that no longer exist in PHP 8,
 *                      ignoring comments, strings and method calls.
 *  2. EXECUTION     - Calls the methods that the rest of the suite never
 *                      reaches, so a missing function becomes a test failure
 *                      instead of a production fatal.
 *
 * @package Flourish Tests
 */

require_once __DIR__ . '/bootstrap.php';

/**
 * The functions that were removed from PHP and would raise a fatal error
 * if they were called.
 *
 * @return array  The function names, grouped by the PHP version that removed them
 */
function flourish_removed_functions()
{
	return array(
		'PHP 5.4 / 7.0' => array(
			'sqlite_open', 'sqlite_query', 'sqlite_exec', 'sqlite_close',
			'sqlite_fetch_array', 'sqlite_fetch_single', 'sqlite_escape_string',
			'sqlite_libencoding', 'sqlite_num_rows', 'sqlite_changes',
			'sqlite_last_insert_rowid', 'sqlite_error_string', 'sqlite_unbuffered_query',
			'sqlite_column', 'sqlite_seek', 'sqlite_rewind', 'sqlite_next',
			'sqlite_current', 'sqlite_popen', 'sqlite_has_feature'
		),
		'PHP 7.0' => array(
			'mysql_connect', 'mysql_query', 'mysql_real_escape_string',
			'mysql_escape_string', 'mysql_fetch_array', 'mysql_fetch_assoc',
			'mysql_fetch_row', 'mysql_fetch_object', 'mysql_num_rows',
			'mysql_affected_rows', 'mysql_insert_id', 'mysql_error',
			'mysql_errno', 'mysql_close', 'mysql_select_db', 'mysql_free_result',
			'mysql_list_dbs', 'mysql_list_tables', 'mysql_list_fields',
			'mysql_stat', 'mysql_get_client_info', 'mysql_pconnect',
			'mysql_unbuffered_query', 'mysql_set_charset', 'mysql_set_charset',
			'mysql_thread_id', 'mysql_db_query', 'mysql_tablename',
			'__autoload'
		),
		'PHP 7.2' => array(
			'mcrypt_module_open', 'mcrypt_generic', 'mcrypt_generic_init',
			'mcrypt_generic_deinit', 'mcrypt_module_close', 'mcrypt_create_iv',
			'mcrypt_enc_get_key_size', 'mcrypt_enc_get_iv_size',
			'mcrypt_list_algorithms', 'mcrypt_get_block_size',
			'mcrypt_get_iv_size', 'mcrypt_get_key_size'
		),
		'PHP 8.0' => array(
			'get_magic_quotes_gpc', 'get_magic_quotes_runtime',
			'set_magic_quotes_runtime', 'restore_include_path',
			'create_function', 'each', 'money_format', 'ezmlm_hash',
			'gzgetss', 'convert_cyr_string', 'hebrevc',
			'date_sunrise', 'date_sunset'
		),
		'deprecated, removed later' => array(
			'utf8_encode', 'utf8_decode'
		)
	);
}

/**
 * Returns every source file of the library
 *
 * @return array  The file paths
 */
function flourish_all_source_files()
{
	$files = array();
	foreach (array('lib', 'enums', 'container', 'interfaces', 'querybuilder', 'providers') as $directory) {
		$path = FLOURISH_ROOT . '/' . $directory;
		if (!is_dir($path)) {
			continue;
		}
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
		foreach ($iterator as $file) {
			if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
				$files[] = $file->getPathname();
			}
		}
	}
	sort($files);
	return $files;
}

/**
 * Finds the calls to removed functions in a file
 *
 * The tokenizer is used so that documentation, comments and string literals
 * are never reported, and so that method calls ($obj->foo() and Foo::foo())
 * are not confused with global function calls.
 *
 * @param  string $file    The path of the file
 * @param  array  $removed The names of the removed functions
 * @return array  The findings, each one with a 'name', 'line' and 'file' key
 */
function flourish_find_removed_calls($file, $removed)
{
	$tokens = token_get_all(file_get_contents($file));
	$count = count($tokens);
	$findings = array();

	for ($i = 0; $i < $count; $i++) {
		$token = $tokens[$i];
		if (!is_array($token) || $token[0] !== T_STRING) {
			continue;
		}

		$name = $token[1];
		if (!isset($removed[$name])) {
			continue;
		}

		// The next significant token must be an opening parenthesis,
		// otherwise this is a constant or a class name, not a call.
		$j = $i + 1;
		while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
			$j++;
		}
		if ($j >= $count || $tokens[$j] !== '(') {
			continue;
		}

		// The previous significant token decides whether this is a global
		// function call or a method call.
		$k = $i - 1;
		while ($k >= 0 && is_array($tokens[$k]) && in_array($tokens[$k][0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true)) {
			$k--;
		}
		if ($k >= 0 && is_array($tokens[$k])) {
			if (in_array($tokens[$k][0], array(T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NULLSAFE_OBJECT_OPERATOR), true)) {
				continue;
			}
			// A function declaration is not a call either.
			if ($tokens[$k][0] === T_FUNCTION) {
				continue;
			}
		}

		$findings[] = array(
			'name' => $name,
			'line' => $token[2],
			'file' => $file
		);
	}

	return $findings;
}


// ---------------------------------------------------------------------------
// 1. Static audit: no call to a removed function anywhere in the library
// ---------------------------------------------------------------------------

$allRemoved = array();
foreach (flourish_removed_functions() as $group => $names) {
	foreach ($names as $name) {
		$allRemoved[$name] = $group;
	}
}

$findings = array();
foreach (flourish_all_source_files() as $file) {
	foreach (flourish_find_removed_calls($file, $allRemoved) as $finding) {
		$findings[] = $finding;
	}
}

test('Static audit: the library never calls a function removed from PHP', function() use ($findings) {
	if (!$findings) {
		return true;
	}
	$messages = array();
	foreach ($findings as $finding) {
		$messages[] = $finding['name'] . '() at ' . str_replace(FLOURISH_ROOT . '/', '', $finding['file']) . ':' . $finding['line'];
	}
	return 'Calls to removed functions: ' . implode('; ', $messages);
});

test('Static audit: get_magic_quotes_gpc() is nowhere in the library', function() use ($findings) {
	$hits = array();
	foreach ($findings as $finding) {
		if ($finding['name'] === 'get_magic_quotes_gpc') {
			$hits[] = $finding['file'] . ':' . $finding['line'];
		}
	}
	return $hits ? implode(', ', $hits) : true;
});

test('Static audit: the mcrypt extension is nowhere in the library', function() use ($findings) {
	$hits = array();
	foreach ($findings as $finding) {
		if (strpos($finding['name'], 'mcrypt') === 0) {
			$hits[] = $finding['name'] . '() at ' . $finding['file'] . ':' . $finding['line'];
		}
	}
	return $hits ? implode(', ', $hits) : true;
});


// ---------------------------------------------------------------------------
// 2. Execution: the code paths that the rest of the suite never reaches
// ---------------------------------------------------------------------------

test('Execution: fCookie::get() returns the cookie value', function() {
	$_COOKIE['flourish_test_cookie'] = "O'Brien & Co";
	$value = fCookie::get('flourish_test_cookie');
	unset($_COOKIE['flourish_test_cookie']);
	return $value === "O'Brien & Co" ? true : 'Expected "O\'Brien & Co", got ' . var_export($value, true);
});

test('Execution: fCookie::get() returns the default value when absent', function() {
	$value = fCookie::get('flourish_cookie_that_does_not_exist', 'fallback');
	return $value === 'fallback' ? true : 'Expected "fallback", got ' . var_export($value, true);
});

test('Execution: fURL::removeFromQueryString() removes a parameter', function() {
	$_SERVER['REQUEST_URI'] = '/path?foo=bar&baz=qux';
	$value = fURL::removeFromQueryString('baz');
	$_SERVER['REQUEST_URI'] = '';
	return $value === '?foo=bar' ? true : 'Expected "?foo=bar", got ' . var_export($value, true);
});

test('Execution: fURL::replaceInQueryString() replaces a parameter', function() {
	$_SERVER['REQUEST_URI'] = '/path?foo=bar';
	$value = fURL::replaceInQueryString('foo', 'new');
	$_SERVER['REQUEST_URI'] = '';
	return $value === '?foo=new' ? true : 'Expected "?foo=new", got ' . var_export($value, true);
});

test('Execution: fURL::getQueryString() returns the query string', function() {
	$_SERVER['REQUEST_URI'] = '/path?a=1';
	$value = fURL::getQueryString();
	$_SERVER['REQUEST_URI'] = '';
	// Flourish returns the query string without the leading question mark.
	// The behaviour is unchanged from Flourish 0.9.0, it must not drift.
	return $value === 'a=1' ? true : 'Expected "a=1", got ' . var_export($value, true);
});

test('Execution: fURL::get() returns the path without the query string', function() {
	$_SERVER['REQUEST_URI'] = '/path/to/file?a=1';
	$value = fURL::get();
	$_SERVER['REQUEST_URI'] = '';
	return $value === '/path/to/file' ? true : 'Expected "/path/to/file", got ' . var_export($value, true);
});

test('Execution: fURL works from the command line, where REQUEST_URI does not exist', function() {
	// This is the situation of a cron job: the superglobal is not set, and
	// reading it without a guard raises a warning and a deprecation.
	unset($_SERVER['REQUEST_URI']);
	unset($_SERVER['SERVER_NAME']);

	$results = array(
		'get'                => fURL::get(),
		'getQueryString'    => fURL::getQueryString(),
		'getWithQueryString'=> fURL::getWithQueryString(),
		'getDomain'         => fURL::getDomain(),
		'removeFromQuery'   => fURL::removeFromQueryString('foo')
	);

	foreach ($results as $name => $value) {
		if (!is_string($value)) {
			return $name . ' did not return a string';
		}
	}

	return $results['get'] === '' && $results['getQueryString'] === ''
		? true
		: 'Expected empty strings from the command line, got ' . json_encode($results);
});

test('Execution: fCryptography::symmetricKeyEncrypt() encrypts and decrypts', function() {
	$plaintext = "El rapido zorro marron salta sobre el perro perezoso - 12345";
	$secret = 'a-secret-key-of-enough-length';

	$ciphertext = fCryptography::symmetricKeyEncrypt($plaintext, $secret);
	if (!is_string($ciphertext) || strpos($ciphertext, 'fCryptography::symmetric#') !== 0) {
		return 'Unexpected ciphertext format: ' . var_export($ciphertext, true);
	}

	$decrypted = fCryptography::symmetricKeyDecrypt($ciphertext, $secret);
	return $decrypted === $plaintext ? true : 'The round trip did not return the original plaintext';
});

test('Execution: fCryptography::symmetricKeyEncrypt() rejects a short secret key', function() {
	try {
		fCryptography::symmetricKeyEncrypt('data', 'short');
	}
	catch (fValidationException $e) {
		return true;
	}
	return 'A fValidationException was expected for a secret key shorter than 8 characters';
});

test('Execution: fCryptography::symmetricKeyDecrypt() detects a tampered ciphertext', function() {
	$ciphertext = fCryptography::symmetricKeyEncrypt('data', 'a-secret-key-of-enough-length');
	$tampered = substr($ciphertext, 0, -4) . 'AAAA';

	try {
		fCryptography::symmetricKeyDecrypt($tampered, 'a-secret-key-of-enough-length');
	}
	catch (fValidationException $e) {
		return true;
	}
	return 'A fValidationException was expected for a tampered ciphertext';
});

test('Execution: fCryptography::hashPassword() and ::checkPasswordHash() work together', function() {
	$hash = fCryptography::hashPassword('my-password');
	if (!fCryptography::checkPasswordHash('my-password', $hash)) {
		return 'The password did not match its own hash';
	}
	return fCryptography::checkPasswordHash('another-password', $hash) ? 'A wrong password matched the hash' : true;
});

test('Execution: fMoney reports the invalid currency in the exception message', function() {
	// The message must name the currency that was actually passed, not an
	// undefined variable.
	try {
		new fMoney(10, 'XXXX');
	}
	catch (fProgrammerException $e) {
		return strpos($e->getMessage(), 'XXXX') !== false
			? true
			: 'The exception message does not contain the invalid currency: ' . $e->getMessage();
	}
	return 'A fProgrammerException was expected for an invalid currency';
});


exit(flourish_test_summary(basename(__FILE__)));
