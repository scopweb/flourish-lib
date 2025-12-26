<?php
/**
 * MongoDB BSON Data Types Enumeration
 *
 * Provides type safety for MongoDB document operations with support
 * for all major BSON types used in MongoDB 4.2+
 *
 * ✅ **PHP 8.4 COMPATIBLE** ✅
 * Native PHP 8.4 Enum with comprehensive BSON type definitions
 *
 * @package    Flourish
 * @author     Claude Code Migration <noreply@anthropic.com>
 * @license    http://flourishlib.com/license
 * @link       http://flourishlib.com/fMongoDB
 * @version    1.0.0 - MongoDB Integration Support
 */

enum MongoDbType: string
{
    // Numeric Types
    case Double = 'double';
    case Int32 = 'int32';
    case Int64 = 'int64';
    case Decimal128 = 'decimal128';

    // String and Text Types
    case String = 'string';
    case Symbol = 'symbol';
    case Code = 'code';
    case CodeWithScope = 'codeWithScope';

    // Binary Data
    case Binary = 'binary';
    case BinaryOld = 'binaryOld';
    case ObjectId = 'objectId';

    // Boolean and Null
    case Boolean = 'boolean';
    case Null = 'null';

    // Date and Time
    case Date = 'date';
    case Timestamp = 'timestamp';

    // Array and Object
    case Array = 'array';
    case Object = 'object';
    case EmbeddedDocument = 'embeddedDocument';

    // Special Types
    case Regex = 'regex';
    case DBPointer = 'dbPointer';
    case MinKey = 'minKey';
    case MaxKey = 'maxKey';
    case Undefined = 'undefined';

    /**
     * Check if the type is a numeric type
     *
     * Numeric types: Double, Int32, Int64, Decimal128
     *
     * @return boolean  True if type is numeric
     */
    public function isNumeric(): bool
    {
        return match ($this) {
            self::Double,
            self::Int32,
            self::Int64,
            self::Decimal128 => true,
            default => false,
        };
    }

    /**
     * Check if the type is a scalar (single-value) type
     *
     * Scalar types: Double, Int32, Int64, Decimal128, String, Symbol,
     * ObjectId, Boolean, Null, Date, Timestamp
     *
     * @return boolean  True if type is scalar
     */
    public function isScalarType(): bool
    {
        return match ($this) {
            self::Double,
            self::Int32,
            self::Int64,
            self::Decimal128,
            self::String,
            self::Symbol,
            self::ObjectId,
            self::Boolean,
            self::Null,
            self::Date,
            self::Timestamp => true,
            default => false,
        };
    }

    /**
     * Check if the type is a complex/container type
     *
     * Complex types: Array, Object, EmbeddedDocument
     *
     * @return boolean  True if type is complex
     */
    public function isComplexType(): bool
    {
        return match ($this) {
            self::Array,
            self::Object,
            self::EmbeddedDocument => true,
            default => false,
        };
    }

    /**
     * Check if the type is a container type (alias for isComplexType)
     *
     * Container types: Array, Object, EmbeddedDocument
     *
     * @return boolean  True if type is container
     */
    public function isContainer(): bool
    {
        return $this->isComplexType();
    }

    /**
     * Check if the type is a binary type
     *
     * Binary types: Binary, BinaryOld, ObjectId
     *
     * @return boolean  True if type is binary
     */
    public function isBinaryType(): bool
    {
        return match ($this) {
            self::Binary,
            self::BinaryOld,
            self::ObjectId => true,
            default => false,
        };
    }

    /**
     * Check if the type is code (executable) type
     *
     * Code types: Code, CodeWithScope
     *
     * @return boolean  True if type is code
     */
    public function isCodeType(): bool
    {
        return match ($this) {
            self::Code,
            self::CodeWithScope => true,
            default => false,
        };
    }

    /**
     * Check if the type can be indexed (used for database queries)
     *
     * Indexable types: String, Number types, ObjectId, Date, etc.
     *
     * @return boolean  True if type is indexable
     */
    public function isIndexable(): bool
    {
        return match ($this) {
            self::String,
            self::Double,
            self::Int32,
            self::Int64,
            self::Decimal128,
            self::ObjectId,
            self::Date,
            self::Timestamp,
            self::Boolean => true,
            default => false,
        };
    }

    /**
     * Get a human-readable name for the type
     *
     * @return string  Formatted type name (e.g., "Object ID" for ObjectId)
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::Double => 'Double (64-bit Float)',
            self::Int32 => 'Integer (32-bit)',
            self::Int64 => 'Integer (64-bit)',
            self::Decimal128 => 'Decimal128 (Arbitrary Precision)',
            self::String => 'String (UTF-8)',
            self::Symbol => 'Symbol (Deprecated)',
            self::Code => 'JavaScript Code',
            self::CodeWithScope => 'JavaScript Code with Scope',
            self::Binary => 'Binary Data',
            self::BinaryOld => 'Binary Data (Old Format)',
            self::ObjectId => 'Object ID',
            self::Boolean => 'Boolean',
            self::Null => 'Null',
            self::Date => 'DateTime',
            self::Timestamp => 'Timestamp',
            self::Array => 'Array',
            self::Object => 'Object/Document',
            self::EmbeddedDocument => 'Embedded Document',
            self::Regex => 'Regular Expression',
            self::DBPointer => 'Database Pointer (Deprecated)',
            self::MinKey => 'Min Key (Internal)',
            self::MaxKey => 'Max Key (Internal)',
            self::Undefined => 'Undefined (Deprecated)',
        };
    }

    /**
     * Get the BSON type code (numeric type identifier)
     *
     * @return integer  BSON type code
     */
    public function getBsonTypeCode(): int
    {
        return match ($this) {
            self::Double => 1,
            self::String => 2,
            self::Object => 3,
            self::Array => 4,
            self::Binary => 5,
            self::ObjectId => 7,
            self::Boolean => 8,
            self::Date => 9,
            self::Null => 10,
            self::Regex => 11,
            self::DBPointer => 12,
            self::Code => 13,
            self::Symbol => 14,
            self::CodeWithScope => 15,
            self::Int32 => 16,
            self::Timestamp => 17,
            self::Int64 => 18,
            self::Decimal128 => 19,
            self::MinKey => -1,
            self::MaxKey => 127,
            self::BinaryOld => 5, // Legacy, uses Binary type
            self::EmbeddedDocument => 3, // Treated as Object
            self::Undefined => 6, // Deprecated, included for completeness
        };
    }

    /**
     * Create a MongoDbType from BSON type code
     *
     * @param integer $code  The BSON type code
     * @return self|null     The matching type or null if not found
     */
    public static function fromBsonTypeCode(int $code): ?self
    {
        return match ($code) {
            1 => self::Double,
            2 => self::String,
            3 => self::Object,
            4 => self::Array,
            5 => self::Binary,
            6 => self::Undefined,
            7 => self::ObjectId,
            8 => self::Boolean,
            9 => self::Date,
            10 => self::Null,
            11 => self::Regex,
            12 => self::DBPointer,
            13 => self::Code,
            14 => self::Symbol,
            15 => self::CodeWithScope,
            16 => self::Int32,
            17 => self::Timestamp,
            18 => self::Int64,
            19 => self::Decimal128,
            -1 => self::MinKey,
            127 => self::MaxKey,
            default => null,
        };
    }

    /**
     * Alias for Int32 - get Int type
     *
     * @return self  The Int32 case
     */
    public static function Int(): self
    {
        return self::Int32;
    }

    /**
     * Get all scalar types
     *
     * @return array  Array of scalar MongoDbType cases
     */
    public static function getScalarTypes(): array
    {
        return [
            self::Double,
            self::Int32,
            self::Int64,
            self::Decimal128,
            self::String,
            self::Symbol,
            self::ObjectId,
            self::Boolean,
            self::Null,
            self::Date,
            self::Timestamp,
        ];
    }

    /**
     * Get all complex types
     *
     * @return array  Array of complex MongoDbType cases
     */
    public static function getComplexTypes(): array
    {
        return [
            self::Array,
            self::Object,
            self::EmbeddedDocument,
        ];
    }

    /**
     * Validate a PHP value against this BSON type
     *
     * @param mixed $value  The value to validate
     * @return boolean      True if value matches type
     */
    public function validatePhpValue(mixed $value): bool
    {
        return match ($this) {
            self::String => is_string($value),
            self::Double => is_float($value),
            self::Int32, self::Int64 => is_int($value),
            self::Boolean => is_bool($value),
            self::Null => $value === null,
            self::Array => is_array($value),
            self::Object, self::EmbeddedDocument => is_object($value) || is_array($value),
            self::Decimal128 => is_numeric($value),
            self::Date => $value instanceof DateTime || is_int($value),
            default => true, // Accept any value for other types
        };
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
