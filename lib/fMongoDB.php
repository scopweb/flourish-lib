<?php
/**
 * MongoDB Database Abstraction Layer
 *
 * Modern NoSQL database interface for MongoDB with support for document
 * operations, aggregation pipelines, and flexible schema handling
 *
 * ✅ **PHP 8.4 COMPATIBLE** ✅
 * Uses official MongoDB PHP driver (mongodb extension)
 *
 * @copyright  Copyright (c) 2025 Claude Code Migration
 * @author     Claude Code Migration <noreply@anthropic.com>
 * @license    http://flourishlib.com/license
 * @package    Flourish
 * @link       http://flourishlib.com/fMongoDB
 * @version    1.0.0 - MongoDB Integration
 */

class fMongoDB
{
    /**
     * MongoDB connection URI
     *
     * @var string
     */
    private string $uri = 'mongodb://localhost:27017';

    /**
     * MongoDB client instance
     *
     * @var object
     */
    private mixed $client = null;

    /**
     * Current database
     *
     * @var object
     */
    private mixed $database = null;

    /**
     * Database name
     *
     * @var string
     */
    private string $databaseName = '';

    /**
     * Constructor
     *
     * @param string $uri           MongoDB connection URI
     * @param string $databaseName  Database name to connect to
     */
    public function __construct(string $uri = '', string $databaseName = '')
    {
        if (!empty($uri)) {
            $this->uri = $uri;
        }
        if (!empty($databaseName)) {
            $this->databaseName = $databaseName;
        }
    }

    /**
     * Connect to MongoDB
     *
     * @return self  For method chaining
     * @throws fConnectivityException  When connection fails
     */
    public function connect(): self
    {
        if (!extension_loaded('mongodb')) {
            throw new fConnectivityException('MongoDB PHP driver is not installed');
        }

        try {
            $this->client = new MongoDB\Client($this->uri);
            if (!empty($this->databaseName)) {
                $this->database = $this->client->selectDatabase($this->databaseName);
            }
        } catch (Exception $e) {
            throw new fConnectivityException('Failed to connect to MongoDB: ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * Select a database
     *
     * @param string $name  Database name
     * @return self         For method chaining
     */
    public function selectDatabase(string $name): self
    {
        $this->databaseName = $name;
        if ($this->client !== null) {
            $this->database = $this->client->selectDatabase($name);
        }
        return $this;
    }

    /**
     * Select a collection from the current database
     *
     * @param string $name  Collection name
     * @return mixed        Collection object
     * @throws fException   When database not selected
     */
    public function selectCollection(string $name): mixed
    {
        if ($this->database === null) {
            throw new fException('No database selected. Call selectDatabase() first.');
        }
        return $this->database->selectCollection($name);
    }

    /**
     * Insert a document
     *
     * @param string $collection   Collection name
     * @param array $document      Document to insert
     * @return mixed               Insert result
     */
    public function insertOne(string $collection, array $document): mixed
    {
        return $this->selectCollection($collection)->insertOne($document);
    }

    /**
     * Insert multiple documents
     *
     * @param string $collection   Collection name
     * @param array $documents     Documents to insert
     * @return mixed               Insert result
     */
    public function insertMany(string $collection, array $documents): mixed
    {
        return $this->selectCollection($collection)->insertMany($documents);
    }

    /**
     * Find documents
     *
     * @param string $collection  Collection name
     * @param array $filter       Filter criteria
     * @param array $options      Query options
     * @return mixed              Cursor with results
     */
    public function find(string $collection, array $filter = [], array $options = []): mixed
    {
        return $this->selectCollection($collection)->find($filter, $options);
    }

    /**
     * Find a single document
     *
     * @param string $collection  Collection name
     * @param array $filter       Filter criteria
     * @param array $options      Query options
     * @return mixed              First matching document or null
     */
    public function findOne(string $collection, array $filter = [], array $options = []): mixed
    {
        return $this->selectCollection($collection)->findOne($filter, $options);
    }

    /**
     * Find by ID
     *
     * @param string $collection  Collection name
     * @param mixed $id           Document ID
     * @return mixed              Document or null
     */
    public function findById(string $collection, mixed $id): mixed
    {
        $filter = ['_id' => $id instanceof MongoDB\BSON\ObjectId ? $id : new MongoDB\BSON\ObjectId($id)];
        return $this->findOne($collection, $filter);
    }

    /**
     * Update documents
     *
     * @param string $collection  Collection name
     * @param array $filter       Filter criteria
     * @param array $update       Update operations
     * @param array $options      Update options
     * @return mixed              Update result
     */
    public function updateMany(string $collection, array $filter, array $update, array $options = []): mixed
    {
        return $this->selectCollection($collection)->updateMany($filter, $update, $options);
    }

    /**
     * Update a single document
     *
     * @param string $collection  Collection name
     * @param array $filter       Filter criteria
     * @param array $update       Update operations
     * @param array $options      Update options
     * @return mixed              Update result
     */
    public function updateOne(string $collection, array $filter, array $update, array $options = []): mixed
    {
        return $this->selectCollection($collection)->updateOne($filter, $update, $options);
    }

    /**
     * Delete documents
     *
     * @param string $collection  Collection name
     * @param array $filter       Filter criteria
     * @param array $options      Delete options
     * @return mixed              Delete result
     */
    public function deleteMany(string $collection, array $filter, array $options = []): mixed
    {
        return $this->selectCollection($collection)->deleteMany($filter, $options);
    }

    /**
     * Delete a single document
     *
     * @param string $collection  Collection name
     * @param array $filter       Filter criteria
     * @param array $options      Delete options
     * @return mixed              Delete result
     */
    public function deleteOne(string $collection, array $filter, array $options = []): mixed
    {
        return $this->selectCollection($collection)->deleteOne($filter, $options);
    }

    /**
     * Run an aggregation pipeline
     *
     * @param string $collection  Collection name
     * @param array $pipeline     Aggregation pipeline stages
     * @param array $options      Aggregation options
     * @return mixed              Cursor with aggregation results
     */
    public function aggregate(string $collection, array $pipeline, array $options = []): mixed
    {
        return $this->selectCollection($collection)->aggregate($pipeline, $options);
    }

    /**
     * Count documents
     *
     * @param string $collection  Collection name
     * @param array $filter       Filter criteria
     * @param array $options      Count options
     * @return integer            Document count
     */
    public function countDocuments(string $collection, array $filter = [], array $options = []): int
    {
        return $this->selectCollection($collection)->countDocuments($filter, $options);
    }

    /**
     * Drop a collection
     *
     * @param string $collection  Collection name
     * @return mixed              Drop result
     */
    public function dropCollection(string $collection): mixed
    {
        return $this->database->dropCollection($collection);
    }

    /**
     * Get client
     *
     * @return object  MongoDB client
     */
    public function getClient(): mixed
    {
        return $this->client;
    }

    /**
     * Get database
     *
     * @return object  MongoDB database
     */
    public function getDatabase(): mixed
    {
        return $this->database;
    }

    /**
     * Disconnect from MongoDB
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->client !== null) {
            // MongoDB client handles cleanup
            $this->client = null;
            $this->database = null;
        }
    }
}

/**
 * Copyright (c) 2025 Claude Code Migration
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
