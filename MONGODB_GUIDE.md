# Flourish MongoDB Integration Guide

The Flourish Framework now includes comprehensive MongoDB support through the `fMongoDB` class, providing modern NoSQL database capabilities while maintaining the framework's signature simplicity and security.

## Quick Start

### Installation Requirements

```bash
# Install MongoDB PHP extension
pecl install mongodb

# Add to php.ini
extension=mongodb

# Optional: Install MongoDB PHP Library for advanced features
composer require mongodb/mongodb
```

### Basic Connection

```php
<?php
require_once 'lib/fMongoDB.php';

// Basic connection
$mongodb = new fMongoDB('mongodb://localhost:27017', 'myapp');

// With authentication
$mongodb = new fMongoDB(
    'mongodb://username:password@localhost:27017',
    'myapp',
    ['authSource' => 'admin']
);

// Collection access
$users = $mongodb->selectCollection('users');
?>
```

## Document Operations

### Insert Documents

```php
// Insert single document
$result = $users->insertOne([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'profile' => [
        'age' => 30,
        'department' => 'Engineering'
    ],
    'tags' => ['developer', 'php', 'mongodb'],
    'created_at' => new MongoDB\BSON\UTCDateTime()
]);

echo "Inserted ID: " . $result->getInsertedId();

// Insert multiple documents
$result = $users->insertMany([
    ['name' => 'Alice Smith', 'email' => 'alice@example.com', 'department' => 'Design'],
    ['name' => 'Bob Johnson', 'email' => 'bob@example.com', 'department' => 'Marketing'],
    ['name' => 'Carol Wilson', 'email' => 'carol@example.com', 'department' => 'Engineering']
]);

echo "Inserted " . $result->getInsertedCount() . " documents";
```

### Query Documents

```php
// Find single document
$user = $users->findOne(['email' => 'john@example.com']);

if ($user) {
    echo "Found user: " . $user['name'];
}

// Find multiple documents
$engineers = $users->find(['profile.department' => 'Engineering']);

foreach ($engineers as $engineer) {
    echo $engineer['name'] . " - " . $engineer['email'] . "\n";
}

// Advanced queries
$activeUsers = $users->find([
    'status' => 'active',
    'profile.age' => ['$gte' => 18, '$lte' => 65],
    'tags' => ['$in' => ['developer', 'designer']]
]);

// Query with options
$recentUsers = $users->find(
    ['created_at' => ['$gte' => new MongoDB\BSON\UTCDateTime(strtotime('-7 days') * 1000)]],
    [
        'sort' => ['created_at' => -1],
        'limit' => 10,
        'projection' => ['name' => 1, 'email' => 1, 'created_at' => 1]
    ]
);
```

### Update Documents

```php
// Update single document
$result = $users->updateOne(
    ['email' => 'john@example.com'],
    [
        '$set' => ['profile.age' => 31, 'updated_at' => new MongoDB\BSON\UTCDateTime()],
        '$addToSet' => ['tags' => 'senior']
    ]
);

echo "Modified " . $result->getModifiedCount() . " document";

// Update multiple documents
$result = $users->updateMany(
    ['profile.department' => 'Engineering'],
    ['$set' => ['profile.team' => 'Backend']]
);

// Upsert (insert if not exists)
$result = $users->updateOne(
    ['email' => 'new@example.com'],
    [
        '$set' => ['name' => 'New User', 'created_at' => new MongoDB\BSON\UTCDateTime()],
        '$setOnInsert' => ['profile' => ['department' => 'Unknown']]
    ],
    ['upsert' => true]
);
```

### Delete Documents

```php
// Delete single document
$result = $users->deleteOne(['email' => 'old@example.com']);
echo "Deleted " . $result->getDeletedCount() . " document";

// Delete multiple documents
$result = $users->deleteMany([
    'status' => 'inactive',
    'last_login' => ['$lt' => new MongoDB\BSON\UTCDateTime(strtotime('-1 year') * 1000)]
]);

echo "Deleted " . $result->getDeletedCount() . " documents";
```

## Aggregation Pipelines

MongoDB's aggregation framework allows for powerful data processing:

```php
// User statistics by department
$stats = $users->aggregate([
    ['$match' => ['status' => 'active']],
    ['$group' => [
        '_id' => '$profile.department',
        'count' => ['$sum' => 1],
        'avgAge' => ['$avg' => '$profile.age'],
        'maxAge' => ['$max' => '$profile.age'],
        'minAge' => ['$min' => '$profile.age']
    ]],
    ['$sort' => ['count' => -1]]
]);

foreach ($stats as $stat) {
    echo "Department: " . $stat['_id'] . "\n";
    echo "Count: " . $stat['count'] . "\n";
    echo "Average Age: " . round($stat['avgAge'], 1) . "\n\n";
}

// Complex aggregation with multiple stages
$pipeline = [
    // Stage 1: Filter active users
    ['$match' => ['status' => 'active']],
    
    // Stage 2: Add computed fields
    ['$addFields' => [
        'fullName' => ['$concat' => ['$firstName', ' ', '$lastName']],
        'ageGroup' => [
            '$switch' => [
                'branches' => [
                    ['case' => ['$lt' => ['$profile.age', 30]], 'then' => 'Young'],
                    ['case' => ['$lt' => ['$profile.age', 50]], 'then' => 'Middle'],
                ],
                'default' => 'Senior'
            ]
        ]
    ]],
    
    // Stage 3: Group by age group
    ['$group' => [
        '_id' => '$ageGroup',
        'users' => ['$push' => [
            'name' => '$fullName',
            'email' => '$email',
            'age' => '$profile.age'
        ]],
        'count' => ['$sum' => 1]
    ]],
    
    // Stage 4: Sort by count
    ['$sort' => ['count' => -1]]
];

$ageGroupStats = $users->aggregate($pipeline);
```

## Type Safety with MongoDbType Enum

```php
// Type detection and validation
$value = ['nested' => 'document'];
$type = MongoDbType::detectFromPhpValue($value);

if ($type === MongoDbType::Object) {
    echo "This is a MongoDB document";
}

// Validate BSON types
if (MongoDbType::String->isScalarType()) {
    echo "String is a scalar type";
}

if (MongoDbType::Array->isStructural()) {
    echo "Array is a structural type";
}

// Convert between PHP and BSON
$objectId = MongoDbType::ObjectId->convertFromPhp('507f1f77bcf86cd799439011');
// Returns: MongoDB\BSON\ObjectId instance
```

## Indexing

```php
// Create indexes for better performance
$users->createIndex(['email' => 1], ['unique' => true]);
$users->createIndex(['profile.department' => 1, 'status' => 1]);
$users->createIndex(['tags' => 1]); // Works with arrays
$users->createIndex(['name' => 'text', 'profile.bio' => 'text']); // Text search

// Create TTL index (documents auto-expire)
$sessions = $mongodb->selectCollection('sessions');
$sessions->createIndex(['createdAt' => 1], ['expireAfterSeconds' => 3600]);

// List indexes
$indexes = $users->listIndexes();
foreach ($indexes as $index) {
    echo "Index: " . $index['name'] . "\n";
}
```

## Working with GridFS (Large Files)

```php
// Store large files
$bucket = $mongodb->selectGridFSBucket();

// Upload file
$stream = fopen('/path/to/largefile.pdf', 'rb');
$fileId = $bucket->uploadFromStream('document.pdf', $stream, [
    'metadata' => [
        'uploaded_by' => 'user123',
        'content_type' => 'application/pdf',
        'tags' => ['document', 'important']
    ]
]);

// Download file
$downloadStream = $bucket->openDownloadStream($fileId);
$contents = stream_get_contents($downloadStream);

// Find files
$files = $bucket->find(['metadata.tags' => 'document']);
foreach ($files as $file) {
    echo "File: " . $file['filename'] . " (" . $file['length'] . " bytes)\n";
}
```

## Error Handling

```php
use MongoDB\Exception\Exception as MongoDBException;

try {
    $result = $users->insertOne(['name' => 'Test User']);
    echo "Insert successful";
} catch (MongoDBException $e) {
    echo "MongoDB Error: " . $e->getMessage();
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage();
}

// Bulk operations with error handling
try {
    $bulk = [
        ['insertOne' => [['name' => 'User 1', 'email' => 'user1@example.com']]],
        ['insertOne' => [['name' => 'User 2', 'email' => 'user2@example.com']]],
        ['updateOne' => [
            ['email' => 'existing@example.com'],
            ['$set' => ['updated_at' => new MongoDB\BSON\UTCDateTime()]]
        ]]
    ];
    
    $result = $users->bulkWrite($bulk);
    echo "Bulk operation successful";
} catch (MongoDB\Exception\BulkWriteException $e) {
    echo "Bulk write error: " . $e->getMessage();
    
    // Handle partial failures
    $writeResult = $e->getWriteResult();
    echo "Inserted: " . $writeResult->getInsertedCount();
    echo "Modified: " . $writeResult->getModifiedCount();
}
```

## Best Practices

### 1. Connection Management

```php
// Singleton pattern for connection reuse
class MongoManager {
    private static ?fMongoDB $instance = null;
    
    public static function getInstance(): fMongoDB {
        if (self::$instance === null) {
            self::$instance = new fMongoDB(
                $_ENV['MONGODB_URI'] ?? 'mongodb://localhost:27017',
                $_ENV['MONGODB_DATABASE'] ?? 'app'
            );
        }
        return self::$instance;
    }
}

// Usage
$mongodb = MongoManager::getInstance();
```

### 2. Data Validation

```php
// Validate before insertion
function validateUser(array $data): array {
    $errors = [];
    
    if (empty($data['name'])) {
        $errors[] = 'Name is required';
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (isset($data['age']) && ($data['age'] < 13 || $data['age'] > 120)) {
        $errors[] = 'Age must be between 13 and 120';
    }
    
    return $errors;
}

$userData = ['name' => 'John', 'email' => 'john@example.com', 'age' => 30];
$errors = validateUser($userData);

if (empty($errors)) {
    $users->insertOne($userData);
} else {
    echo "Validation errors: " . implode(', ', $errors);
}
```

### 3. Performance Tips

```php
// Use projections to limit returned fields
$users = $collection->find(
    ['status' => 'active'],
    ['projection' => ['name' => 1, 'email' => 1]] // Only return name and email
);

// Use indexes for sorting
$collection->createIndex(['created_at' => -1]);
$recent = $collection->find([], ['sort' => ['created_at' => -1], 'limit' => 10]);

// Batch operations for better performance
$bulk = [];
for ($i = 0; $i < 1000; $i++) {
    $bulk[] = ['insertOne' => [['batch_id' => $i, 'data' => "Item $i"]]];
}
$collection->bulkWrite($bulk);
```

## Integration with Flourish Framework

The MongoDB integration follows Flourish conventions:

```php
// Error handling follows Flourish patterns
try {
    $mongodb = new fMongoDB('mongodb://localhost:27017', 'myapp');
} catch (fConnectivityException $e) {
    echo "Connection failed: " . $e->getMessage();
} catch (fEnvironmentException $e) {
    echo "Environment issue: " . $e->getMessage();
}

// Debugging support
fCore::enableDebugging(true);
$result = $users->find(['status' => 'active']);
// Debugging information will be displayed
```

This comprehensive MongoDB integration provides Flourish applications with modern NoSQL capabilities while maintaining the framework's philosophy of simplicity and security.