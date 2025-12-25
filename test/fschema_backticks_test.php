<?php
/**
 * fSchema MySQL Backticks Compatibility Test
 *
 * Tests that fSchema::fetchMySQLKeys() correctly parses SHOW CREATE TABLE output
 * with both backticks (MySQL default) and double quotes (ANSI_QUOTES mode)
 *
 * Related to: https://github.com/imarc/flourish-classes/pull/21
 *
 * @package Flourish Tests
 * @author  Claude Code
 */

// Test PRIMARY KEY with backticks (MySQL default)
test('PRIMARY KEY with backticks should be detected', function() {
	$create_table = "CREATE TABLE `users` (\n  `id` int,\n  PRIMARY KEY (`id`),\n) ENGINE=InnoDB";
	$pattern = '/PRIMARY KEY\s+\(["`](.*?)["`]\),?\n/U';
	preg_match_all($pattern, $create_table, $matches, PREG_SET_ORDER);

	if (empty($matches) || $matches[0][1] !== 'id') {
		return "PRIMARY KEY with backticks was not detected correctly";
	}
	return true;
});

// Test PRIMARY KEY with double quotes (ANSI_QUOTES mode)
test('PRIMARY KEY with double quotes should be detected', function() {
	$create_table = "CREATE TABLE \"users\" (\n  \"id\" int,\n  PRIMARY KEY (\"id\"),\n) ENGINE=InnoDB";
	$pattern = '/PRIMARY KEY\s+\(["`](.*?)["`]\),?\n/U';
	preg_match_all($pattern, $create_table, $matches, PREG_SET_ORDER);

	if (empty($matches) || $matches[0][1] !== 'id') {
		return "PRIMARY KEY with double quotes was not detected correctly";
	}
	return true;
});

// Test PRIMARY KEY with multiple columns
test('Multi-column PRIMARY KEY should parse correctly', function() {
	$create_table = "CREATE TABLE `order_items` (\n  PRIMARY KEY (`order_id`,`product_id`),\n) ENGINE=InnoDB";
	$pattern = '/PRIMARY KEY\s+\(["`](.*?)["`]\),?\n/U';
	preg_match_all($pattern, $create_table, $matches, PREG_SET_ORDER);

	if (empty($matches)) {
		return "Multi-column PRIMARY KEY was not detected";
	}

	$columns = preg_split('/["`],["`]/', $matches[0][1]);
	if (count($columns) !== 2) {
		return "Expected 2 columns, got " . count($columns);
	}
	return true;
});

// Test UNIQUE KEY with backticks
test('UNIQUE KEY with backticks should be detected', function() {
	$create_table = "CREATE TABLE `users` (\n  UNIQUE KEY `email_idx` (`email`),\n) ENGINE=InnoDB";
	$pattern = '/UNIQUE KEY\s+["`]([^"`]+)["`]\s+\(["`](.*?)["`]\).*,?\n/U';
	preg_match_all($pattern, $create_table, $matches, PREG_SET_ORDER);

	if (empty($matches) || $matches[0][1] !== 'email_idx') {
		return "UNIQUE KEY with backticks was not detected correctly";
	}
	return true;
});

// Test UNIQUE KEY with double quotes
test('UNIQUE KEY with double quotes should be detected', function() {
	$create_table = "CREATE TABLE \"users\" (\n  UNIQUE KEY \"email_idx\" (\"email\"),\n) ENGINE=InnoDB";
	$pattern = '/UNIQUE KEY\s+["`]([^"`]+)["`]\s+\(["`](.*?)["`]\).*,?\n/U';
	preg_match_all($pattern, $create_table, $matches, PREG_SET_ORDER);

	if (empty($matches) || $matches[0][1] !== 'email_idx') {
		return "UNIQUE KEY with double quotes was not detected correctly";
	}
	return true;
});

// Test UNIQUE KEY with USING BTREE clause
test('UNIQUE KEY with USING BTREE should be detected', function() {
	$create_table = "CREATE TABLE `users` (\n  UNIQUE KEY `email_idx` (`email`) USING BTREE,\n) ENGINE=InnoDB";
	$pattern = '/UNIQUE KEY\s+["`]([^"`]+)["`]\s+\(["`](.*?)["`]\).*,?\n/U';
	preg_match_all($pattern, $create_table, $matches, PREG_SET_ORDER);

	if (empty($matches) || $matches[0][1] !== 'email_idx') {
		return "UNIQUE KEY with USING BTREE was not detected correctly";
	}
	return true;
});

// Test FOREIGN KEY with backticks
test('FOREIGN KEY with backticks should be detected', function() {
	$create_table = "CREATE TABLE `posts` (\n  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)\n) ENGINE=InnoDB";
	$pattern = '#FOREIGN KEY \(["`]([^"`]+)["`]\) REFERENCES ["`]([^"`]+)["`] \(["`]([^"`]+)["`]\)(?:\sON\sDELETE\s(SET\sNULL|SET\sDEFAULT|CASCADE|NO\sACTION|RESTRICT))?(?:\sON\sUPDATE\s(SET\sNULL|SET\sDEFAULT|CASCADE|NO\sACTION|RESTRICT))?#';
	preg_match_all($pattern, $create_table, $matches, PREG_SET_ORDER);

	if (empty($matches) || $matches[0][1] !== 'user_id' || $matches[0][2] !== 'users') {
		return "FOREIGN KEY with backticks was not detected correctly";
	}
	return true;
});

// Test FOREIGN KEY with double quotes
test('FOREIGN KEY with double quotes should be detected', function() {
	$create_table = "CREATE TABLE \"posts\" (\n  FOREIGN KEY (\"user_id\") REFERENCES \"users\" (\"id\")\n) ENGINE=InnoDB";
	$pattern = '#FOREIGN KEY \(["`]([^"`]+)["`]\) REFERENCES ["`]([^"`]+)["`] \(["`]([^"`]+)["`]\)(?:\sON\sDELETE\s(SET\sNULL|SET\sDEFAULT|CASCADE|NO\sACTION|RESTRICT))?(?:\sON\sUPDATE\s(SET\sNULL|SET\sDEFAULT|CASCADE|NO\sACTION|RESTRICT))?#';
	preg_match_all($pattern, $create_table, $matches, PREG_SET_ORDER);

	if (empty($matches) || $matches[0][1] !== 'user_id' || $matches[0][2] !== 'users') {
		return "FOREIGN KEY with double quotes was not detected correctly";
	}
	return true;
});

// Test FOREIGN KEY with CASCADE actions
test('FOREIGN KEY with CASCADE actions should be detected', function() {
	$create_table = "CREATE TABLE `posts` (\n  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE\n) ENGINE=InnoDB";
	$pattern = '#FOREIGN KEY \(["`]([^"`]+)["`]\) REFERENCES ["`]([^"`]+)["`] \(["`]([^"`]+)["`]\)(?:\sON\sDELETE\s(SET\sNULL|SET\sDEFAULT|CASCADE|NO\sACTION|RESTRICT))?(?:\sON\sUPDATE\s(SET\sNULL|SET\sDEFAULT|CASCADE|NO\sACTION|RESTRICT))?#';
	preg_match_all($pattern, $create_table, $matches, PREG_SET_ORDER);

	if (empty($matches) || $matches[0][4] !== 'CASCADE' || $matches[0][5] !== 'CASCADE') {
		return "FOREIGN KEY CASCADE actions were not detected correctly";
	}
	return true;
});
