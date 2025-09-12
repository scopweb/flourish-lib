# Flourish Query Builder - Developer Guide

The Flourish Query Builder provides a modern, fluent interface for building database queries while maintaining full backward compatibility with existing Flourish applications.

## Quick Start

### Basic Setup

```php
// Option 1: Using existing fDatabase instance
$db = new fDatabase('mysql', 'database_name', 'username', 'password', 'host');

// Option 2: Using static facade (recommended for new projects)
fDB::setDatabase($db);

// Now you can use either approach:
$users = $db->table('users')->where('active', '=', 1)->get();
// OR
$users = fDB::table('users')->where('active', '=', 1)->get();
```

## Basic Queries

### SELECT Queries

```php
// Basic SELECT
$users = $db->table('users')->get();

// SELECT specific columns
$users = $db->table('users')
            ->select(['id', 'name', 'email'])
            ->get();

// SELECT with WHERE clause
$activeUsers = $db->table('users')
                  ->where('active', '=', 1)
                  ->get();

// Multiple WHERE conditions
$users = $db->table('users')
            ->where('active', '=', 1)
            ->where('age', '>', 18)
            ->get();

// WHERE with OR
$users = $db->table('users')
            ->where('active', '=', 1)
            ->orWhere('admin', '=', 1)
            ->get();
```

### INSERT Queries

```php
// Basic INSERT
$db->table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30
]);

// Using static facade
fDB::insertInto('users', [
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'age' => 25
]);
```

### UPDATE Queries

```php
// Basic UPDATE
$db->table('users')
   ->where('id', '=', 1)
   ->update([
       'name' => 'John Smith',
       'email' => 'johnsmith@example.com'
   ]);

// Using static facade
fDB::updateTable('users', ['active' => 0])
   ->where('last_login', '<', '2023-01-01')
   ->execute();
```

### DELETE Queries

```php
// Basic DELETE
$db->table('users')
   ->where('active', '=', 0)
   ->delete();

// Using static facade
fDB::deleteFrom('users')
   ->where('created_at', '<', '2020-01-01')
   ->execute();
```

## Advanced WHERE Clauses

### whereIn / whereNotIn

```php
$users = $db->table('users')
            ->whereIn('id', [1, 2, 3, 4, 5])
            ->get();

$users = $db->table('users')
            ->whereNotIn('status', ['banned', 'suspended'])
            ->get();
```

### whereNull / whereNotNull

```php
$users = $db->table('users')
            ->whereNull('deleted_at')
            ->get();

$profiles = $db->table('profiles')
               ->whereNotNull('bio')
               ->get();
```

### whereBetween

```php
$products = $db->table('products')
               ->whereBetween('price', 10, 100)
               ->get();
```

### whereLike

```php
$users = $db->table('users')
            ->whereLike('name', '%John%')
            ->get();
```

### whereExists / whereNotExists

```php
// Users who have orders
$users = $db->table('users')
            ->whereExists(function($query) {
                $query->table('orders')->where('orders.user_id', '=', 'users.id');
            })
            ->get();

// Users who have no orders
$users = $db->table('users')
            ->whereNotExists(function($query) {
                $query->table('orders')->where('orders.user_id', '=', 'users.id');
            })
            ->get();
```

### Raw WHERE clauses

```php
$users = $db->table('users')
            ->whereRaw('YEAR(created_at) = YEAR(NOW())')
            ->get();
```

## JOIN Operations

### Basic JOINs

```php
// INNER JOIN
$users = $db->table('users')
            ->join('profiles', 'users.id', '=', 'profiles.user_id')
            ->select(['users.name', 'profiles.bio'])
            ->get();

// LEFT JOIN
$users = $db->table('users')
            ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
            ->select(['users.name', 'COUNT(orders.id) as order_count'])
            ->groupBy('users.id')
            ->get();

// RIGHT JOIN
$orders = $db->table('orders')
             ->rightJoin('users', 'orders.user_id', '=', 'users.id')
             ->get();

// CROSS JOIN
$combinations = $db->table('colors')
                   ->crossJoin('sizes')
                   ->get();
```

### Multiple JOINs

```php
$data = $db->table('users')
           ->join('profiles', 'users.id', '=', 'profiles.user_id')
           ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
           ->leftJoin('order_items', 'orders.id', '=', 'order_items.order_id')
           ->select([
               'users.name',
               'profiles.bio',
               'COUNT(DISTINCT orders.id) as order_count',
               'COUNT(order_items.id) as item_count'
           ])
           ->groupBy('users.id')
           ->get();
```

## Raw SQL Expressions

### Using Raw Expressions

```php
// Raw expressions in SELECT
$stats = $db->table('users')
            ->select([
                $db->raw('COUNT(*) as total_users'),
                $db->raw('AVG(age) as average_age'),
                $db->raw('MAX(created_at) as latest_registration')
            ])
            ->first();

// Using static facade
$stats = fDB::table('orders')
            ->select([
                fDB::raw('DATE(created_at) as order_date'),
                fDB::raw('COUNT(*) as daily_orders'),
                fDB::raw('SUM(total) as daily_revenue')
            ])
            ->groupBy(fDB::raw('DATE(created_at)'))
            ->get();
```

## Ordering and Limiting

### ORDER BY

```php
// Basic ordering
$users = $db->table('users')
            ->orderBy('name', 'ASC')
            ->get();

// Multiple ORDER BY
$users = $db->table('users')
            ->orderBy('created_at', 'DESC')
            ->orderBy('name', 'ASC')
            ->get();
```

### LIMIT and OFFSET

```php
// Pagination
$users = $db->table('users')
            ->orderBy('id')
            ->limit(10)
            ->offset(20)
            ->get();

// Get first record
$user = $db->table('users')
           ->where('active', '=', 1)
           ->first();
```

## Grouping and Aggregation

### GROUP BY and HAVING

```php
$statistics = $db->table('orders')
                 ->select([
                     'user_id',
                     $db->raw('COUNT(*) as order_count'),
                     $db->raw('SUM(total) as total_spent')
                 ])
                 ->groupBy('user_id')
                 ->having('order_count', '>', 5)
                 ->get();
```

### Aggregate Functions (Static Facade)

```php
// Count records
$userCount = fDB::count('users');
$activeUsers = fDB::count('users')->where('active', '=', 1);

// Other aggregates
$maxPrice = fDB::max('products', 'price');
$minPrice = fDB::min('products', 'price');
$avgAge = fDB::avg('users', 'age');
$totalRevenue = fDB::sum('orders', 'total');
```

## Pagination

### Built-in Pagination

```php
$result = $db->table('users')
             ->where('active', '=', 1)
             ->paginate(1, 15); // Page 1, 15 items per page

// Result structure:
// [
//     'data' => [...], // Array of records
//     'total' => 150,  // Total record count
//     'current_page' => 1,
//     'per_page' => 15,
//     'last_page' => 10,
//     'from' => 1,
//     'to' => 15
// ]
```

## Complex Queries

### Advanced Example

```php
$report = $db->table('users')
             ->select([
                 'users.id',
                 'users.name',
                 'users.email',
                 'profiles.bio',
                 $db->raw('COUNT(DISTINCT orders.id) as order_count'),
                 $db->raw('COALESCE(SUM(orders.total), 0) as total_spent'),
                 $db->raw('MAX(orders.created_at) as last_order_date')
             ])
             ->join('profiles', 'users.id', '=', 'profiles.user_id')
             ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
             ->where('users.active', '=', 1)
             ->where('users.created_at', '>=', '2023-01-01')
             ->whereNotNull('profiles.bio')
             ->groupBy('users.id')
             ->having('order_count', '>', 0)
             ->orderBy('total_spent', 'DESC')
             ->limit(50)
             ->get();
```

## Transactions

### Using Transactions

```php
// Method 1: Manual transaction control
fDB::beginTransaction();
try {
    fDB::insertInto('users', ['name' => 'John', 'email' => 'john@example.com']);
    fDB::insertInto('profiles', ['user_id' => fDB::lastInsertId(), 'bio' => 'Developer']);
    fDB::commit();
} catch (Exception $e) {
    fDB::rollback();
    throw $e;
}

// Method 2: Automatic transaction (recommended)
$result = fDB::transaction(function($db) {
    $db->table('users')->insert(['name' => 'Jane', 'email' => 'jane@example.com']);
    $db->table('profiles')->insert(['user_id' => 123, 'bio' => 'Designer']);
    return 'Transaction completed';
});
```

## SQL Injection Protection

The Query Builder automatically protects against SQL injection by using parameter binding:

```php
// Safe - parameters are automatically bound
$users = $db->table('users')
            ->where('email', '=', $_POST['email'])  // Automatically escaped
            ->get();

// The malicious input is safely handled:
$maliciousInput = "'; DROP TABLE users; --";
$users = $db->table('users')
            ->where('name', '=', $maliciousInput)  // Becomes parameter binding
            ->get();
// Generated SQL: SELECT * FROM users WHERE name = %s
// Binding: ["'; DROP TABLE users; --"]
```

## Backward Compatibility

The Query Builder maintains 100% backward compatibility with existing Flourish code:

```php
// Old way (still works)
$result = $db->query("SELECT * FROM users WHERE active = %i", 1);

// New way (enhanced DX)
$result = $db->table('users')->where('active', '=', 1)->get();

// Both approaches can be mixed in the same application
```

## Debugging

### View Generated SQL

```php
// View the SQL that would be generated
$sql = $db->table('users')
          ->where('active', '=', 1)
          ->where('age', '>', 18)
          ->toSql();

echo $sql; // SELECT * FROM users WHERE active = %i AND age > %i

// View parameter bindings
$query = $db->table('users')->where('name', '=', 'John');
$bindings = $query->getBindings();
print_r($bindings); // ['John']
```

## Best Practices

### 1. Use Static Facade for New Projects
```php
// Preferred for new development
fDB::setDatabase($database);
$users = fDB::table('users')->where('active', '=', 1)->get();
```

### 2. Always Use Parameter Binding
```php
// Good
$users = $db->table('users')->where('email', '=', $userEmail)->get();

// Avoid (use only when necessary)
$users = $db->table('users')->whereRaw("email = '$userEmail'")->get();
```

### 3. Use Transactions for Multiple Operations
```php
fDB::transaction(function($db) {
    // Multiple related database operations here
    $db->table('orders')->insert([...]);
    $db->table('order_items')->insert([...]);
});
```

### 4. Leverage Pagination for Large Results
```php
// Instead of loading all records
$users = fDB::table('users')->paginate(1, 50);
```

## Migration Guide

### From Raw SQL to Query Builder

```php
// Before
$sql = "SELECT u.name, p.bio FROM users u 
        JOIN profiles p ON u.id = p.user_id 
        WHERE u.active = %i 
        ORDER BY u.name ASC";
$result = $db->query($sql, 1);

// After
$result = $db->table('users')
             ->join('profiles', 'users.id', '=', 'profiles.user_id')
             ->select(['users.name', 'profiles.bio'])
             ->where('users.active', '=', 1)
             ->orderBy('users.name', 'ASC')
             ->get();
```

The Query Builder provides a modern, secure, and maintainable way to interact with your database while preserving all the power and flexibility of the original Flourish framework.