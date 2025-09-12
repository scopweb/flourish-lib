<?php
/**
 * MongoDB data types enumeration
 * 
 * Provides type safety for MongoDB BSON data types and operations.
 * Each type includes validation and conversion methods.
 * 
 * @since PHP 8.4
 */
enum MongoDbType: string 
{
    /**
     * Double (floating point)
     */
    case Double = 'double';
    
    /**
     * String
     */
    case String = 'string';
    
    /**
     * Object (document)
     */
    case Object = 'object';
    
    /**
     * Array
     */
    case Array = 'array';
    
    /**
     * Binary data
     */
    case BinData = 'binData';
    
    /**
     * ObjectId
     */
    case ObjectId = 'objectId';
    
    /**
     * Boolean
     */
    case Bool = 'bool';
    
    /**
     * Date
     */
    case Date = 'date';
    
    /**
     * Null
     */
    case Null = 'null';
    
    /**
     * Regular Expression
     */
    case Regex = 'regex';
    
    /**
     * JavaScript code
     */
    case Javascript = 'javascript';
    
    /**
     * 32-bit integer
     */
    case Int = 'int';
    
    /**
     * Timestamp
     */
    case Timestamp = 'timestamp';
    
    /**
     * 64-bit integer
     */
    case Long = 'long';
    
    /**
     * Decimal128
     */
    case Decimal = 'decimal';

    /**
     * Check if this type is numeric
     * 
     * @return bool True if type is numeric
     */
    public function isNumeric(): bool
    {
        return match($this) {
            self::Double, self::Int, self::Long, self::Decimal => true,
            default => false
        };
    }

    /**
     * Check if this type can be indexed
     * 
     * @return bool True if type can be indexed
     */
    public function isIndexable(): bool
    {
        return match($this) {
            self::String, self::Double, self::Int, self::Long, 
            self::Bool, self::Date, self::ObjectId => true,
            default => false
        };
    }

    /**
     * Check if this type is a container type
     * 
     * @return bool True if type contains other values
     */
    public function isContainer(): bool
    {
        return match($this) {
            self::Object, self::Array => true,
            default => false
        };
    }

    /**
     * Get the MongoDB query operator for this type
     * 
     * @return string MongoDB $type operator value
     */
    public function getTypeOperator(): string|int
    {
        return match($this) {
            self::Double => 1,
            self::String => 2,
            self::Object => 3,
            self::Array => 4,
            self::BinData => 5,
            self::ObjectId => 7,
            self::Bool => 8,
            self::Date => 9,
            self::Null => 10,
            self::Regex => 11,
            self::Javascript => 13,
            self::Int => 16,
            self::Timestamp => 17,
            self::Long => 18,
            self::Decimal => 19
        };
    }

    /**
     * Get PHP equivalent type
     * 
     * @return string PHP type name
     */
    public function getPhpType(): string
    {
        return match($this) {
            self::Double => 'float',
            self::String => 'string',
            self::Object => 'array',
            self::Array => 'array',
            self::BinData => 'string',
            self::ObjectId => 'string',
            self::Bool => 'bool',
            self::Date => 'DateTime',
            self::Null => 'null',
            self::Regex => 'string',
            self::Javascript => 'string',
            self::Int => 'int',
            self::Timestamp => 'int',
            self::Long => 'int',
            self::Decimal => 'string'
        };
    }

    /**
     * Check if PHP value matches this MongoDB type
     * 
     * @param mixed $value Value to check
     * @return bool True if value matches type
     */
    public function validatePhpValue(mixed $value): bool
    {
        return match($this) {
            self::Double => is_float($value) || is_numeric($value),
            self::String => is_string($value),
            self::Object => is_array($value) && !array_is_list($value),
            self::Array => is_array($value) && array_is_list($value),
            self::BinData => is_string($value),
            self::ObjectId => is_string($value) && strlen($value) === 24,
            self::Bool => is_bool($value),
            self::Date => $value instanceof DateTime,
            self::Null => $value === null,
            self::Regex => is_string($value),
            self::Javascript => is_string($value),
            self::Int => is_int($value) && $value >= -2147483648 && $value <= 2147483647,
            self::Timestamp => is_int($value),
            self::Long => is_int($value),
            self::Decimal => is_string($value) || is_numeric($value)
        };
    }

    /**
     * Get recommended usage for this type
     * 
     * @return string Usage recommendation
     */
    public function getRecommendedUsage(): string
    {
        return match($this) {
            self::Double => 'Floating point numbers, scientific calculations',
            self::String => 'Text data, names, descriptions',
            self::Object => 'Nested documents, complex structures',
            self::Array => 'Lists, collections of values',
            self::BinData => 'Binary files, images, attachments',
            self::ObjectId => 'Primary keys, document references',
            self::Bool => 'True/false values, flags',
            self::Date => 'Timestamps, dates, time-based data',
            self::Null => 'Missing or undefined values',
            self::Regex => 'Pattern matching, search queries',
            self::Javascript => 'Server-side code execution',
            self::Int => '32-bit integers, counters, small numbers',
            self::Timestamp => 'MongoDB internal timestamps',
            self::Long => '64-bit integers, large numbers',
            self::Decimal => 'Precise decimal numbers, currency'
        };
    }

    /**
     * Get all numeric types
     * 
     * @return array Array of numeric MongoDbType cases
     */
    public static function getNumericTypes(): array
    {
        return array_filter(self::cases(), fn($type) => $type->isNumeric());
    }

    /**
     * Get all indexable types
     * 
     * @return array Array of indexable MongoDbType cases
     */
    public static function getIndexableTypes(): array
    {
        return array_filter(self::cases(), fn($type) => $type->isIndexable());
    }

    /**
     * Create MongoDbType from MongoDB $type operator value
     * 
     * @param string|int $operator MongoDB type operator
     * @return MongoDbType|null MongoDbType instance or null if not found
     */
    public static function fromTypeOperator(string|int $operator): ?self
    {
        foreach (self::cases() as $type) {
            if ($type->getTypeOperator() === $operator) {
                return $type;
            }
        }
        return null;
    }

    /**
     * Detect MongoDB type from PHP value
     * 
     * @param mixed $value PHP value to analyze
     * @return MongoDbType Best matching MongoDB type
     */
    public static function detectFromPhpValue(mixed $value): self
    {
        return match(true) {
            $value === null => self::Null,
            is_bool($value) => self::Bool,
            is_int($value) => self::Int,
            is_float($value) => self::Double,
            is_string($value) => self::String,
            $value instanceof DateTime => self::Date,
            is_array($value) && array_is_list($value) => self::Array,
            is_array($value) => self::Object,
            default => self::String
        };
    }
}