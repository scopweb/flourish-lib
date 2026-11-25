# Flourish PHP Framework

**Flourish** is a PHP "unframework" — a collection of independent classes that can be used individually or together to rapidly develop web applications and APIs.

This is the modernized version maintained by `scopweb`, targeting **PHP 8.5.13**, the version running on the production server.

| | |
|---|---|
| Production server | **PHP 8.5.13** |
| Also verified on | PHP 8.4 |
| Minimum required | PHP 8.5.13 (`composer.json`, `.php-version`, `config.platform.php`) |

The version is pinned in three places so that it cannot drift unnoticed:

- `composer.json` → `"require": {"php": ">=8.5.13"}`
- `composer.json` → `"config": {"platform": {"php": "8.5.13"}}`
- `.php-version` → `8.5.13` (read by asdf, Herd and similar tools)

The CI workflow (`.github/workflows/tests.yml`) runs the whole test suite against
PHP 8.5.13 and PHP 8.4 on every push.

## PHP 8.5 Ready

- Zero deprecations: the test suite fails if PHP raises a single deprecation, warning or notice.
- No dependency on the extensions that PHP removed (`mysql`, `sqlite` v2).
- Modern syntax throughout: variadic parameters, `null`/`true`/`false`, typed properties, enums, readonly and first class callables.
- `#[\Deprecated]` marks the legacy APIs, and the library never calls them itself.

### What the PHP 8.5 migration changed

| Area | Change |
|---|---|
| Casts | `(boolean)`/`(integer)` → `(bool)`/`(int)` — the non-canonical casts are deprecated in 8.5 |
| Multibyte | `utf8_decode()` → `mb_strlen()` / `lengthWithoutMbString()` — deprecated since 8.2 |
| Serialization | `__sleep()`/`__wakeup()` → `__serialize()`/`__unserialize()`, old ones kept for compatibility |
| cURL / GD | `curl_close()` and `imagedestroy()` removed — deprecated in 8.5 |
| PDO | `PDO::MYSQL_ATTR_DIRECT_QUERY` → `Pdo\Mysql::ATTR_DIRECT_QUERY` |
| Database | The `mysql` and `sqlite` v2 extensions are gone; the drivers are MySQLi and PDO |
| Return types | `#[\ReturnTypeWillChange]` on the `ArrayAccess`/`Iterator`/`Countable` methods |

## Installation

```bash
git clone https://github.com/scopweb/flourish-lib.git
cd flourish-lib
composer install
```

The library has **no runtime dependencies**. Composer is only needed for the autoloader.

## Quick Start

```php
<?php
require_once 'vendor/autoload.php';

// Database connection (auto-detects MySQLi or PDO)
$db = new fDatabase('mysql', 'database_name', 'username', 'password', 'host');

// Query with parameter binding
$result = $db->query('SELECT * FROM users WHERE status = %s AND age > %i', 'active', 18);

foreach ($result as $row) {
    echo $row['name'] . "\n";
}
```

### Query builder

```php
fDB::setDatabase($db);

$users = fDB::table('users')
    ->join('profiles', 'users.id', '=', 'profiles.user_id')
    ->where('users.active', '=', 1)
    ->orderBy('users.name')
    ->get()
    ->fetchAllRows();
```

## Testing

```bash
php test/run_all_tests.php
```

Every test file runs in its own process, so a fatal error in one test cannot hide
the results of the others. The run fails if any test fails **or** if PHP raises a
deprecation, warning or notice while the tests run.

The suite includes a live SQLite database test (`test/php85_compatibility_test.php`)
that exercises `fDatabase`, the SQL translation layer and the query builder
against a real database — no external server needed.

## Architecture

### Core
- **fCore**: debugging, error handling, core utilities
- **fException**: exception hierarchy (`fUnexpectedException`, `fConnectivityException`, …)

### Database
- **fDatabase**: database abstraction layer with MySQLi/PDO support
- **fResult** / **fStatement** / **fUnbufferedResult**: results, prepared statements, unbuffered results
- **fSchema**: schema introspection (MySQL, PostgreSQL, SQLite, Oracle, SQL Server)
- **fSQLTranslation** / **fSQLSchemaTranslation**: SQL dialect translation

### ORM
- **fActiveRecord**, **fORM**, **fRecordSet**, **fORMColumn**, **fORMRelated**, …

### Utilities
- **fEmail** / **fSMTP** / **fMailbox**, **fDate** / **fTime** / **fTimestamp**,
  **fValidation**, **fText** / **fGrammar** / **fUTF8**, **fFile** / **fDirectory** /
  **fFilesystem** / **fUpload** / **fImage**, **fCryptography**, **fCache**, **fJSON**,
  **fXML**, **fURL**, **fHTML**, **fLoader**, **fPagination**

### Modern additions
- **querybuilder/**: `fQueryBuilder`, `fDB`, `fRawExpression`
- **enums/**: `DatabaseType`, `MimeType`, `ImageFormat`, `EmailEncoding`, `MongoDbType`, `JsonToken`
- **container/** + **providers/** + **interfaces/**: dependency injection

## Database drivers

| Type | Drivers |
|---|---|
| `mysql` | `mysqli`, `pdo_mysql` |
| `postgresql` | `pgsql`, `pdo_pgsql` |
| `sqlite` | `pdo_sqlite` |
| `oracle` | `oci8`, `pdo_oci` |
| `mssql` | `sqlsrv` |

Placeholders: `%s` string, `%i` integer, `%f` float, `%b` boolean, `%d` date,
`%t` time, `%p` timestamp, `%l` blob, `%r` identifier.

## Security

- SQL injection protection through automatic parameter escaping
- XSS prevention with built-in output escaping utilities
- CSRF protection with form token generation
- Secure password hashing with bcrypt
- Comprehensive input validation library

## Documentation

- Complete API reference in the `documentacion/` folder
- Original documentation at <http://flourishlib.com>
- Development notes and the migration contract in `CLAUDE.md` and `MIGRATION_PHP85.md`

## License

MIT — see the [LICENSE](LICENSE) file.

## Credits

Original Flourish Framework by Will Bond, modernization by scopweb.
