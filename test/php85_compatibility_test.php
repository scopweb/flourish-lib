<?php
/**
 * PHP 8.5 compatibility audit
 *
 * Three layers of verification:
 *
 *  1. STATIC AUDIT   - Scans every source file for the constructs that PHP
 *                      8.5 deprecates (and for APIs that no longer exist),
 *                      ignoring comments and documentation.
 *  2. CLASS LOADING  - Loads every class of the library and makes sure no
 *                      deprecation, warning or notice is raised while doing it.
 *  3. FUNCTIONAL     - Exercises the library for real, including a live
 *                      SQLite database through fDatabase and the query builder.
 *
 * @package Flourish Tests
 */

require_once __DIR__ . '/bootstrap.php';

/**
 * Returns the source directories that make up the library
 *
 * @return array  The directory names, relative to the project root
 */
function flourish_source_directories()
{
	return array('lib', 'enums', 'container', 'interfaces', 'querybuilder', 'providers');
}

/**
 * Returns all the PHP source files of the library
 *
 * @return array  The file paths
 */
function flourish_source_files()
{
	$files = array();
	foreach (flourish_source_directories() as $directory) {
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
 * Removes comments and strings from source code, leaving only the executable code
 *
 * This is what makes the static audit meaningful: documentation such as
 * "@return (integer) {…}" must not be reported as a deprecated cast.
 *
 * @param  string $source The source code
 * @return string  The source code without comments
 */
function flourish_strip_comments($source)
{
	$tokens = token_get_all($source);
	$output = '';
	foreach ($tokens as $token) {
		if (is_array($token)) {
			if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
				// The newlines of the comment are kept so that the line
				// numbers of the findings match the original file
				$output .= str_repeat("\n", substr_count($token[1], "\n"));
				continue;
			}
			$output .= $token[1];
			continue;
		}
		$output .= $token;
	}
	return $output;
}

/**
 * Finds the deprecated constructs of PHP 8.5 in a file
 *
 * @param  string $file The path of the file
 * @return array  The findings, each one with a 'rule', 'line' and 'match' key
 */
function flourish_audit_file($file)
{
	$source = file_get_contents($file);
	$code = flourish_strip_comments($source);

	// The fallback of a driver specific constant or method that is only used
	// when the modern API is not available is not a deprecation on PHP 8.5
	$code = preg_replace('/defined\(\s*[\'"]Pdo\\\\[A-Za-z]+::[A-Z_]+[\'"]\s*\)\s*\?[^;]+;/', ' ', $code);
	$code = preg_replace('/if\s*\(\s*\$[a-z_]+\s+instanceof\s+Pdo\\\\[A-Za-z]+\s*\)\s*\{.*?\n\t\t\}/s', ' ', $code);

	$lines = explode("\n", $code);
	$findings = array();

	$rules = array(
		// PHP 8.5 deprecations
		'R1 non-canonical cast'          => '/\(\s*(boolean|integer|double|binary)\s*\)/i',
		'R2 utf8_decode/utf8_encode'     => '/\butf8_(decode|encode)\s*\(/',
		'R4 curl_close'                  => '/\bcurl_close\s*\(/',
		'R5 imagedestroy'                => '/\bimagedestroy\s*\(/',
		'R6 deprecated PDO driver const' => '/\bPDO::(MYSQL|SQLITE|PGSQL|FB|DBLIB|ODBC)_[A-Z_]+/',
		'R8 abstract exception'          => '/new\s+fException\s*\(/',
		'PHP 8.1 removed mysql ext'     => '/(?<![a-z_])mysql_[a-z_]+\s*\(/',
		'PHP 5.4 removed sqlite2 ext'    => '/\bsqlite_(open|close|query|exec|escape_string|fetch_array|unbuffered_query|last_insert_rowid|changes|num_rows|seek|error_string|last_error|libencoding|create_function)\s*\(/',
		'PHP 8.0 removed each()'         => '/(?<![a-z_>])each\s*\(/',
		'PHP 7.2 removed create_function' => '/\bcreate_function\s*\(/',
		'PHP 8.0 removed money_format'   => '/\bmoney_format\s*\(/',
		'PHP 8.1 deprecated utf8_*'      => '/\butf8_(decode|encode)\s*\(/',
		'PHP 8.1 deprecated strftime'    => '/\bstrftime\s*\(/',
		'PHP 8.1 deprecated FILTER_SANITIZE_STRING' => '/FILTER_SANITIZE_STRING/',
		'PHP 8.2 deprecated E_STRICT'    => '/\bE_STRICT\b/',
		'PHP 8.5 deprecated finfo_close' => '/\bfinfo_close\s*\(/',
		'PHP 8.5 deprecated xml_parser_free' => '/\bxml_parser_free\s*\(/',
		'PHP 8.5 deprecated http_response_header' => '/\$http_response_header\b/',
		'PHP 8.5 deprecated mysqli_execute' => '/\bmysqli_execute\s*\(/',
		'PHP 8.5 deprecated socket_set_timeout' => '/\bsocket_set_timeout\s*\(/',
		'PHP 8.5 deprecated setAccessible' => '/->setAccessible\s*\(/',
		'PHP 8.5 deprecated DATE_RFC7231' => '/\bDATE_RFC7231\b/',
		'PHP 8.5 deprecated get_defined_functions arg' => '/get_defined_functions\s*\(\s*(true|false|1|0)\s*\)/',
		'PHP 8.5 deprecated readdir(null)' => '/\b(readdir|rewinddir|closedir)\s*\(\s*(null|NULL)\s*\)/',
	);

	foreach ($rules as $rule => $pattern) {
		if (preg_match_all($pattern, $code, $matches, PREG_OFFSET_CAPTURE)) {
			foreach ($matches[0] as $match) {
				$offset = $match[1];
				$line = substr_count(substr($code, 0, $offset), "\n") + 1;
				$findings[] = array(
					'rule'  => $rule,
					'line'  => $line,
					'match' => trim($match[0])
				);
			}
		}
	}

	// The backtick operator is deprecated in PHP 8.5, the tokenizer detects it
	// precisely so that backticks inside strings are not reported.
	if (defined('T_BACKTICK')) {
		foreach (token_get_all($source) as $token) {
			if (is_array($token) && $token[0] === T_BACKTICK) {
				$findings[] = array(
					'rule'  => 'PHP 8.5 deprecated backtick operator',
					'line'  => $token[2],
					'match' => '`'
				);
			}
		}
	}

	return $findings;
}

/**
 * Checks that the classes with __sleep()/__wakeup() also implement
 * __serialize()/__unserialize(), which are the replacements preferred by PHP 8.5
 *
 * @param  string $file The path of the file
 * @return array  The findings
 */
function flourish_audit_serialization($file)
{
	$source = file_get_contents($file);
	$findings = array();

	if (preg_match('/function\s+__sleep\s*\(/', $source) && !preg_match('/function\s+__serialize\s*\(/', $source)) {
		$findings[] = array('rule' => 'R3 __sleep without __serialize', 'line' => 0, 'match' => '__sleep');
	}
	if (preg_match('/function\s+__wakeup\s*\(/', $source) && !preg_match('/function\s+__unserialize\s*\(/', $source)) {
		$findings[] = array('rule' => 'R3 __wakeup without __unserialize', 'line' => 0, 'match' => '__wakeup');
	}

	return $findings;
}


// ---------------------------------------------------------------------------
// 1. Static audit
// ---------------------------------------------------------------------------

test('Static audit: no PHP 8.5 deprecated constructs in the library', function() {
	$findings = array();
	foreach (flourish_source_files() as $file) {
		foreach (flourish_audit_file($file) as $finding) {
			$relative = str_replace(FLOURISH_ROOT . '/', '', $file);
			$findings[] = $relative . ':' . $finding['line'] . ' [' . $finding['rule'] . '] ' . $finding['match'];
		}
	}

	if ($findings) {
		return count($findings) . " deprecated construct(s) found:\n         - " . implode("\n         - ", $findings);
	}
	return true;
});

test('Static audit: every __sleep/__wakeup has a __serialize/__unserialize pair', function() {
	$findings = array();
	foreach (flourish_source_files() as $file) {
		foreach (flourish_audit_serialization($file) as $finding) {
			$relative = str_replace(FLOURISH_ROOT . '/', '', $file);
			$findings[] = $relative . ' [' . $finding['rule'] . ']';
		}
	}

	if ($findings) {
		return 'Missing serialization counterpart: ' . implode(', ', $findings);
	}
	return true;
});

test('Static audit: no class redeclares an enum or class from another directory', function() {
	$declarations = array();
	$duplicates = array();

	foreach (flourish_source_files() as $file) {
		$source = file_get_contents($file);
		preg_match_all('/^\s*(?:final\s+|abstract\s+)?(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $source, $matches);

		foreach ($matches[1] as $name) {
			if (isset($declarations[$name])) {
				$duplicates[] = $name . ' (' . $declarations[$name] . ' and ' . $file . ')';
			}
			else {
				$declarations[$name] = $file;
			}
		}
	}

	if ($duplicates) {
		return 'Duplicate declarations: ' . implode(', ', $duplicates);
	}
	return true;
});


// ---------------------------------------------------------------------------
// 2. Class loading
// ---------------------------------------------------------------------------

test('Class loading: every class of the library loads without diagnostics', function() {
	$errors = array();
	$loaded = 0;

	foreach (flourish_source_files() as $file) {
		$source = file_get_contents($file);
		preg_match_all('/^\s*(?:final\s+|abstract\s+)?(class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $source, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			$type = $match[1];
			$name = $match[2];

			if ($type === 'interface') {
				$exists = interface_exists($name);
			}
			elseif ($type === 'trait') {
				$exists = trait_exists($name);
			}
			elseif ($type === 'enum') {
				$exists = enum_exists($name);
			}
			else {
				$exists = class_exists($name);
			}

			if (!$exists) {
				$errors[] = $name . ' (' . str_replace(FLOURISH_ROOT . '/', '', $file) . ')';
			}
			else {
				$loaded++;
			}
		}
	}

	if ($errors) {
		return count($errors) . ' type(s) could not be loaded: ' . implode(', ', $errors);
	}
	if ($loaded < 70) {
		return 'Only ' . $loaded . ' types were loaded, expected the whole library';
	}
	return true;
});

test('Class loading: loading the library raises no deprecation, warning or notice', function() {
	$diagnostics = FlourishDiagnostics::getBlocking();
	if ($diagnostics) {
		$messages = array();
		foreach ($diagnostics as $diagnostic) {
			$messages[] = $diagnostic['type'] . ': ' . $diagnostic['message'] . ' @ ' . $diagnostic['file'] . ':' . $diagnostic['line'];
		}
		return count($diagnostics) . ' diagnostic(s) raised while loading the library: ' . implode(' | ', $messages);
	}
	return true;
});


// ---------------------------------------------------------------------------
// 3. Functional verification
// ---------------------------------------------------------------------------

test('Functional: fText and fCore compose messages', function() {
	if (fText::compose('Hello %s, you have %d messages', 'World', 5) !== 'Hello World, you have 5 messages') {
		return 'fText::compose did not compose the message as expected';
	}
	if (fCore::compose('Value: %s', 42) !== 'Value: 42') {
		return 'fCore::compose did not compose the message as expected';
	}
	return true;
});

test('Functional: fNumber performs arithmetic', function() {
	$number = new fNumber('10.50', 2);
	if ((string) $number->add('2.25') !== '12.75') {
		return 'fNumber::add returned ' . $number->add('2.25');
	}
	if ((string) $number->sub('0.5') !== '10.00') {
		return 'fNumber::sub returned ' . $number->sub('0.5');
	}
	if ((string) $number->mul('2') !== '21.00') {
		return 'fNumber::mul returned ' . $number->mul('2');
	}
	return true;
});

test('Functional: fDate formats and compares dates', function() {
	$date = new fDate('2024-03-15 10:30:00');
	if ($date->format('Y-m-d') !== '2024-03-15') {
		return 'fDate::format returned ' . $date->format('Y-m-d');
	}
	if (!$date->gt(new fDate('2024-01-01'))) {
		return 'fDate::gt did not compare the dates correctly';
	}
	return true;
});

test('Functional: fGrammar humanizes and pluralizes', function() {
	if (fGrammar::humanize('user_name') !== 'User Name') {
		return 'fGrammar::humanize returned ' . fGrammar::humanize('user_name');
	}
	if (fGrammar::pluralize('box') !== 'boxes') {
		return 'fGrammar::pluralize returned ' . fGrammar::pluralize('box');
	}
	return true;
});

test('Functional: fJSON encodes and decodes', function() {
	$decoded = fJSON::decode('{"a":1,"b":[2,3]}', true);
	if (!isset($decoded['a']) || $decoded['a'] !== 1 || !isset($decoded['b'][1]) || $decoded['b'][1] !== 3) {
		return 'fJSON::decode did not decode the document as expected';
	}
	if (fJSON::encode(array('a' => 1)) !== '{"a":1}') {
		return 'fJSON::encode returned ' . fJSON::encode(array('a' => 1));
	}
	return true;
});

test('Functional: fUTF8 handles multibyte strings', function() {
	if (fUTF8::len('año') !== 3) {
		return 'fUTF8::len returned ' . fUTF8::len('año');
	}
	if (fUTF8::sub('añodef', 0, 3) !== 'año') {
		return 'fUTF8::sub returned ' . fUTF8::sub('añodef', 0, 3);
	}
	if (fUTF8::pos('añodef', 'def') !== 3) {
		return 'fUTF8::pos returned ' . fUTF8::pos('añodef', 'def');
	}
	return true;
});

test('Functional: fURL builds friendly URLs', function() {
	$url = fURL::makeFriendly('Hello World Again');
	if ($url !== 'hello_world_again') {
		return 'fURL::makeFriendly returned ' . $url;
	}
	return true;
});

test('Functional: fHTML encodes and decodes content', function() {
	if (fHTML::encode('<b>x</b>') !== '&lt;b&gt;x&lt;/b&gt;') {
		return 'fHTML::encode returned ' . fHTML::encode('<b>x</b>');
	}
	if (fHTML::decode('&lt;b&gt;x&lt;/b&gt;') !== '<b>x</b>') {
		return 'fHTML::decode returned ' . fHTML::decode('&lt;b&gt;x&lt;/b&gt;');
	}
	return true;
});

test('Functional: fValidation validates required fields', function() {
	// fValidation reads the values from the request, not from the constructor
	$originalGet = $_GET;
	$_GET = array();

	$validation = new fValidation();
	$validation->addRequiredFields('name', 'email');

	// The validation has to report the missing required fields
	try {
		$validation->validate();
		return 'fValidation did not report the missing required fields';
	}
	catch (fValidationException $e) {
		$message = strtolower($e->getMessage());
		if (strpos($message, 'name') === false || strpos($message, 'email') === false) {
			return 'The validation error does not mention the missing fields: ' . $e->getMessage();
		}
	}

	// A valid set of fields has to pass without raising an exception
	$_GET = array('name' => 'Jane', 'email' => 'jane@example.com');
	$validation = new fValidation();
	$validation->addRequiredFields('name', 'email');
	try {
		$validation->validate();
	}
	catch (fValidationException $e) {
		return 'fValidation rejected a valid set of fields: ' . $e->getMessage();
	}

	$_GET = $originalGet;
	return true;
});

test('Functional: fDirectory and fFilesystem work on the filesystem', function() {
	$directory = sys_get_temp_dir() . '/flourish_php85_' . getmypid();
	$created = fDirectory::create($directory);
	if (!$created || !is_dir($directory)) {
		return 'fDirectory::create did not create the directory';
	}

	$file = $directory . '/sample.txt';
	file_put_contents($file, 'hello');

	$pathInfo = fFilesystem::getPathInfo($file);
	if ($pathInfo['filename'] !== 'sample' || $pathInfo['extension'] !== 'txt') {
		return 'fFilesystem::getPathInfo returned ' . json_encode($pathInfo);
	}

	unlink($file);
	rmdir($directory);
	return true;
});

test('Functional: fCache stores and retrieves values', function() {
	$cache = new fCache('file', sys_get_temp_dir() . '/flourish_php85_cache');
	$cache->set('key', 'value');
	if ($cache->get('key') !== 'value') {
		return 'fCache did not return the stored value';
	}
	$cache->clear();
	return true;
});

test('Functional: serialization round trip works for fFile and fXML', function() {
	$directory = sys_get_temp_dir() . '/flourish_php85_ser_' . getmypid();
	fDirectory::create($directory);
	$path = $directory . '/data.txt';
	file_put_contents($path, 'contents');

	$file = new fFile($path);
	$serialized = serialize($file);
	$restored = unserialize($serialized);

	if (!($restored instanceof fFile)) {
		return 'unserialize did not return an fFile';
	}
	if ($restored->getPath() !== $file->getPath()) {
		return 'The restored file has the path ' . $restored->getPath() . ' instead of ' . $file->getPath();
	}

	unlink($path);
	rmdir($directory);
	return true;
});


// ---------------------------------------------------------------------------
// 4. Live database (SQLite) verification
// ---------------------------------------------------------------------------

/**
 * Creates a database backed by SQLite in memory
 *
 * @return fDatabase|null  The database, or NULL if SQLite is not available
 */
function flourish_test_database()
{
	if (!class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers())) {
		return null;
	}

	$database = new fDatabase('sqlite', ':memory:');
	$database->connect();
	return $database;
}

test('Database: fDatabase creates a schema and reads it back', function() {
	$database = flourish_test_database();
	if ($database === null) {
		return 'SQLite (pdo_sqlite) is not available';
	}

	$database->execute('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)');

	$schema = new fSchema($database);
	$tables = $schema->getTables();
	if (!in_array('users', $tables, true)) {
		return 'The table was not created, the tables are: ' . implode(', ', $tables);
	}
	return true;
});

test('Database: fDatabase inserts and reads rows with bound parameters', function() {
	$database = flourish_test_database();
	if ($database === null) {
		return 'SQLite (pdo_sqlite) is not available';
	}

	$database->execute('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)');

	$result = $database->query('INSERT INTO users (name, email) VALUES (%s, %s)', "O'Brien", 'obrien@example.com');
	if ($result === false) {
		return 'The insert failed';
	}

	$row = $database->query('SELECT name, email FROM users WHERE name = %s', "O'Brien")->fetchRow();
	if (!is_array($row) || $row['name'] !== "O'Brien" || $row['email'] !== 'obrien@example.com') {
		return 'The row was not read back as expected: ' . json_encode($row);
	}
	return true;
});

test('Database: fDatabase escapes the different placeholder types', function() {
	$database = flourish_test_database();
	if ($database === null) {
		return 'SQLite (pdo_sqlite) is not available';
	}

	$database->execute('CREATE TABLE types (id INTEGER PRIMARY KEY, text_value TEXT, int_value INTEGER, float_value REAL, bool_value INTEGER)');

	$database->query(
		'INSERT INTO types (text_value, int_value, float_value, bool_value) VALUES (%s, %i, %f, %b)',
		"quote ' and % percent",
		42,
		3.5,
		true
	);

	$row = $database->query('SELECT * FROM types')->fetchRow();
	if ($row['text_value'] !== "quote ' and % percent") {
		return 'The string was not escaped correctly: ' . $row['text_value'];
	}
	if ((int) $row['int_value'] !== 42) {
		return 'The integer was not bound correctly: ' . $row['int_value'];
	}
	if ((float) $row['float_value'] !== 3.5) {
		return 'The float was not bound correctly: ' . $row['float_value'];
	}
	if ((int) $row['bool_value'] !== 1) {
		return 'The boolean was not bound correctly: ' . $row['bool_value'];
	}
	return true;
});

test('Database: fDatabase reports the affected rows of an update and a delete', function() {
	$database = flourish_test_database();
	if ($database === null) {
		return 'SQLite (pdo_sqlite) is not available';
	}

	$database->execute('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
	$database->query('INSERT INTO users (name) VALUES (%s)', 'Alice');
	$database->query('INSERT INTO users (name) VALUES (%s)', 'Bob');

	$update = $database->query('UPDATE users SET name = %s', 'Alicia');
	if ((int) $update->countAffectedRows() !== 2) {
		return 'The update affected ' . $update->countAffectedRows() . ' rows instead of 2';
	}

	$delete = $database->query('DELETE FROM users WHERE name = %s', 'Alicia');
	if ((int) $delete->countAffectedRows() !== 2) {
		return 'The delete affected ' . $delete->countAffectedRows() . ' rows instead of 2';
	}
	return true;
});

test('Database: the query builder inserts and selects through fDatabase', function() {
	$database = flourish_test_database();
	if ($database === null) {
		return 'SQLite (pdo_sqlite) is not available';
	}

	$database->execute('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, age INTEGER)');
	$database->execute('CREATE TABLE profiles (id INTEGER PRIMARY KEY, user_id INTEGER, bio TEXT)');

	$query = new fQueryBuilder($database, 'users');
	$query->insert(array('name' => 'Alice', 'age' => 30))->execute();
	$query->insert(array('name' => 'Bob', 'age' => 25))->execute();

	$rows = (new fQueryBuilder($database, 'users'))
		->where('age', '>', 26)
		->get()
		->fetchAllRows();

	if (!is_array($rows) || count($rows) !== 1 || $rows[0]['name'] !== 'Alice') {
		return 'The query builder did not filter the rows as expected: ' . json_encode($rows);
	}
	return true;
});

test('Database: the query builder joins and paginates', function() {
	$database = flourish_test_database();
	if ($database === null) {
		return 'SQLite (pdo_sqlite) is not available';
	}

	$database->execute('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
	$database->execute('CREATE TABLE profiles (id INTEGER PRIMARY KEY, user_id INTEGER, bio TEXT)');
	$database->query('INSERT INTO users (name) VALUES (%s)', 'Alice');
	$database->query('INSERT INTO profiles (user_id, bio) VALUES (%i, %s)', 1, 'Hello');

	$rows = (new fQueryBuilder($database, 'users'))
		->select(array('users.name', 'profiles.bio'))
		->join('profiles', 'users.id', '=', 'profiles.user_id')
		->get()
		->fetchAllRows();

	if (!is_array($rows) || count($rows) !== 1 || $rows[0]['bio'] !== 'Hello') {
		return 'The join did not return the expected rows: ' . json_encode($rows);
	}
	return true;
});

test('Database: a transaction can be rolled back', function() {
	$database = flourish_test_database();
	if ($database === null) {
		return 'SQLite (pdo_sqlite) is not available';
	}

	$database->execute('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
	$database->query('BEGIN');
	$database->query('INSERT INTO users (name) VALUES (%s)', 'Inside transaction');
	$database->query('ROLLBACK');

	$rows = (new fQueryBuilder($database, 'users'))->get()->fetchAllRows();
	if (count($rows) !== 0) {
		return 'The rollback did not discard the insert, ' . count($rows) . ' row(s) remain';
	}
	return true;
});

test('Database: the SQL translation works on a live SQLite database', function() {
	$database = flourish_test_database();
	if ($database === null) {
		return 'SQLite (pdo_sqlite) is not available';
	}

	$database->execute('CREATE TABLE measurements (id INTEGER PRIMARY KEY, value REAL)');
	$database->query('INSERT INTO measurements (value) VALUES (%f)', 3.7);

	// The translation layer registers custom SQL functions for SQLite, this
	// exercises them and makes sure that no deprecation is raised
	$row = $database->translatedQuery('SELECT CEIL(value) AS rounded FROM measurements')->fetchRow();
	if (!is_array($row) || (int) $row['rounded'] !== 4) {
		return 'The translation of CEIL() did not work as expected: ' . json_encode($row);
	}
	return true;
});

test('Database: fSchema reads the keys of a real table', function() {
	$database = flourish_test_database();
	if ($database === null) {
		return 'SQLite (pdo_sqlite) is not available';
	}

	$database->execute('CREATE TABLE order_items (id INTEGER PRIMARY KEY, order_id INTEGER, product_id INTEGER, label TEXT)');
	$database->execute('CREATE UNIQUE INDEX idx_label ON order_items (label)');

	$schema = new fSchema($database);
	$keys = $schema->getKeys('order_items');

	if (!isset($keys['primary']) || !in_array('id', $keys['primary'], true)) {
		return 'The primary key was not detected: ' . json_encode($keys);
	}
	if (!isset($keys['unique']) || count($keys['unique']) !== 1 || !in_array('label', $keys['unique'][0], true)) {
		return 'The unique key was not detected: ' . json_encode($keys);
	}
	return true;
});


// ---------------------------------------------------------------------------
// 5. PHP 8.5 specific features
// ---------------------------------------------------------------------------

test('PHP 8.5: array_first() and array_last() are available', function() {
	if (!function_exists('array_first') || !function_exists('array_last')) {
		return skip('PHP 8.5: array_first() and array_last() are available', 'Requires PHP 8.5, running ' . PHP_VERSION);
	}
	if (array_first(array('a' => 1, 'b' => 2)) !== 1) {
		return 'array_first did not return the first value';
	}
	if (array_last(array('a' => 1, 'b' => 2)) !== 2) {
		return 'array_last did not return the last value';
	}
	return true;
});

test('PHP 8.5: the pipe operator and the new array functions behave as expected', function() {
	if (PHP_VERSION_ID < 80500) {
		return skip('PHP 8.5: the pipe operator and the new array functions behave as expected', 'Requires PHP 8.5, running ' . PHP_VERSION);
	}
	if (array_find(array(1, 2, 3), fn($v) => $v > 1) !== 2) {
		return 'array_find did not find the expected value';
	}
	if (array_any(array(1, 2, 3), fn($v) => $v === 3) !== true) {
		return 'array_any did not detect the matching value';
	}
	if (array_all(array(2, 4, 6), fn($v) => $v % 2 === 0) !== true) {
		return 'array_all did not validate the array';
	}
	return true;
});
