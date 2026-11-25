<?php
/**
 * Test for the PHP 8.4+ #[\Deprecated] attribute
 *
 * The policy enforced here is:
 *
 *  1. The attribute exists and can be read through reflection.
 *  2. A deprecated API states which replacement the developer must use.
 *  3. The library NEVER calls its own deprecated APIs, so a production
 *     application never receives deprecation notices caused by the library
 *     itself.
 *
 * @package Flourish Tests
 */

require_once __DIR__ . '/bootstrap.php';

/**
 * Returns the source files of the library
 *
 * @return array  The file paths
 */
function flourish_deprecation_source_files()
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
 * Finds the methods of the library that carry the #[\Deprecated] attribute
 *
 * @return array  The deprecated methods, keyed by class name
 */
function flourish_deprecated_methods()
{
	$deprecated = array();

	foreach (flourish_deprecation_source_files() as $file) {
		$source = file_get_contents($file);
		if (!preg_match('/^\s*(?:final\s+|abstract\s+)?class\s+([A-Za-z_][A-Za-z0-9_]*)/m', $source, $matches)) {
			continue;
		}
		$class = $matches[1];
		if (!class_exists($class)) {
			continue;
		}

		$reflection = new ReflectionClass($class);
		foreach ($reflection->getMethods() as $method) {
			$attributes = $method->getAttributes('Deprecated');
			if (!$attributes) {
				continue;
			}
			$deprecated[$class][] = array(
				'method'  => $method->getName(),
				'message' => $attributes[0]->getArguments()['message'] ?? '',
				'since'   => $attributes[0]->getArguments()['since'] ?? '',
				'file'    => $method->getFileName(),
				'line'    => $method->getStartLine()
			);
		}
	}

	return $deprecated;
}

test('Deprecated attribute: the attribute class is available', function() {
	if (!class_exists('Deprecated')) {
		return 'The Deprecated attribute is not available, this requires PHP 8.4 or newer';
	}
	return true;
});

test('Deprecated attribute: the deprecated APIs of the library are marked', function() {
	$deprecated = flourish_deprecated_methods();
	if (!$deprecated) {
		return 'No API of the library is marked as deprecated';
	}
	if (!isset($deprecated['fEmail'])) {
		return 'fEmail has no deprecated API marked';
	}

	$found = false;
	foreach ($deprecated['fEmail'] as $method) {
		if ($method['method'] === 'getSubmissionEmail') {
			$found = true;
			if (!str_contains($method['message'], 'property')) {
				return 'The deprecation message does not mention the replacement property: ' . $method['message'];
			}
			if ($method['since'] === '') {
				return 'The deprecation does not state the version it was deprecated in';
			}
		}
	}
	if (!$found) {
		return 'fEmail::getSubmissionEmail() is not marked as deprecated';
	}
	return true;
});

test('Deprecated attribute: the library never calls its own deprecated APIs', function() {
	$deprecated = flourish_deprecated_methods();
	if (!$deprecated) {
		return true;
	}

	$violations = array();
	$files = flourish_deprecation_source_files();

	foreach ($deprecated as $class => $methods) {
		foreach ($methods as $method) {
			$name = $method['method'];
			$definitionFile = $method['file'];

			foreach ($files as $file) {
				$source = file_get_contents($file);
				$tokens = token_get_all($source);

				foreach ($tokens as $index => $token) {
					if (!is_array($token) || $token[0] !== T_STRING || $token[1] !== $name) {
						continue;
					}
					// Look back for an object operator or a class operator
					$previous = null;
					for ($i = $index - 1; $i >= 0; $i--) {
						if (is_array($tokens[$i]) && in_array($tokens[$i][0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true)) {
							continue;
						}
						$previous = $tokens[$i];
						break;
					}
					if ($previous !== T_OBJECT_OPERATOR && $previous !== T_DOUBLE_COLON && $previous !== T_PAAMAYIM_NEKUDOTAYIM) {
						continue;
					}
					// The declaration itself is not a call
					if ($file === $definitionFile) {
						continue;
					}

					$relative = str_replace(FLOURISH_ROOT . '/', '', $file);
					$violations[] = $class . '::' . $name . ' is called in ' . $relative . ' on line ' . $token[2];
				}
			}
		}
	}

	if ($violations) {
		return "The library calls its own deprecated APIs:\n         - " . implode("\n         - ", $violations);
	}
	return true;
});

test('Deprecated attribute: the documentation of fResult reflects the current state', function() {
	$reflection = new ReflectionClass('fResult');
	$docComment = $reflection->getDocComment();

	if ($docComment === false) {
		return 'fResult has no documentation block';
	}
	if (str_contains($docComment, 'NOT compatible with PHP')) {
		return 'The documentation of fResult still claims that it is not compatible with PHP 8';
	}
	if (!str_contains($docComment, 'PHP 8')) {
		return 'The documentation of fResult does not mention the PHP 8 compatibility';
	}
	return true;
});
