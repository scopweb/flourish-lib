<?php
/**
 * Database Credentials Example
 * 
 * Copy this file to database_credentials.php and update with your actual database settings.
 * The database_credentials.php file is ignored by Git for security.
 */

// Example database configuration
$db_config = [
    'host' => 'localhost',
    'database' => 'flourish_test', 
    'username' => 'flourish_user',
    'password' => 'your_secure_password_here',
    'port' => 3306
];

// Alternative: Use environment variables (recommended for production)
// Set these in your system environment or .env file:
// DB_HOST=localhost
// DB_DATABASE=flourish_test  
// DB_USERNAME=flourish_user
// DB_PASSWORD=your_secure_password_here

// Example of reading from environment variables:
/*
$db_config = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'database' => getenv('DB_DATABASE') ?: 'flourish_test', 
    'username' => getenv('DB_USERNAME') ?: 'flourish_user',
    'password' => getenv('DB_PASSWORD') ?: 'your_secure_password_here'
];
*/