# Flourish PHP Framework - BETA

**Flourish** is a PHP "unframework" — a collection of 64 independent classes that can be used individually or together to rapidly develop web applications and APIs.

## PHP 8.4 Compatible

This repository contains the fully migrated version of Flourish optimized for PHP 8.4:

### Key Improvements
- Modernized syntax using variadic parameters instead of func_get_args()
- Updated boolean constants to modern lowercase format
- Database classes rewritten to eliminate deprecated mysql_* functions
- Full MySQLi/PDO support with automatic extension detection
- Complete backward compatibility maintained

### Migration Results
- 64 classes updated for PHP 8.4 compatibility
- 32 comprehensive tests passing
- Production ready for modern PHP environments

## Quick Start

### Installation
```bash
git clone https://github.com/scopweb/flourish-lib.git
cd flourish-lib
```

### Basic Usage
```php
<?php
require_once 'lib/fCore.php';
require_once 'lib/fDatabase.php';

// Database connection (auto-detects MySQLi/PDO)
$db = new fDatabase('mysql', 'database_name', 'username', 'password', 'host');

// Query with parameter binding
$result = $db->query("SELECT * FROM users WHERE status = %s", 'active');

foreach ($result as $row) {
    echo $row['name'] . "\n";
}
?>
```

### PHP 8.4 Modernization Example

Old approach:
```php
public static function compose($message) {
    $args = array_slice(func_get_args(), 1);
    return call_user_func_array('sprintf', array($message) + $args);
}
```

Modern approach:
```php
public static function compose($message, ...$args) {
    return sprintf($message, ...$args);
}
```

## Testing

Run the test suite to verify PHP 8.4 compatibility:

```bash
php tests/run_all_tests.php
```

## Documentation

- Complete API reference available in the documentacion/ folder
- Original documentation at http://flourishlib.com
- Development notes in CLAUDE.md

## Framework Architecture

Flourish consists of 64 independent classes organized into logical groups:

### Core Classes
- **fCore**: Debugging, error handling, and core utilities
- **fException**: Enhanced exception handling hierarchy

### Database Classes
- **fDatabase**: Database abstraction layer with MySQLi/PDO support
- **fResult**: Buffered query results
- **fStatement**: Prepared statements (rewritten for modern extensions)
- **fUnbufferedResult**: Unbuffered query results (rewritten for modern extensions)

### ORM Classes  
- **fActiveRecord**: Base class for database records
- **fORM**: Object-relational mapping utilities
- **fRecordSet**: Collections of database records

### Utility Classes
- **fEmail**: Email composition and sending
- **fDate/fTime/fTimestamp**: Date and time manipulation  
- **fValidation**: Form and data validation
- **fText**: String manipulation utilities
- **fCryptography**: Security and encryption utilities

## Security Features

- SQL injection protection through automatic parameter escaping
- XSS prevention with built-in output escaping utilities  
- CSRF protection with form token generation
- Secure password hashing using modern bcrypt
- Comprehensive input validation library

## Use Cases

Flourish is ideal for:
- Rapid prototyping of web applications
- Legacy PHP application modernization
- Microservices with lightweight functionality
- Educational projects and learning modern PHP
- API development

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes with tests
4. Submit a pull request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Credits

Original Flourish Framework by Will Bond  
PHP 8.4+ modernization by scopweb

## Support

For issues and questions, please use [GitHub Issues](https://github.com/scopweb/flourish-lib/issues)