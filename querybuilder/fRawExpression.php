<?php
/**
 * Raw SQL Expression for Query Builder
 * 
 * Allows embedding raw SQL expressions in Query Builder queries while
 * maintaining awareness that the content is intentionally unescaped.
 * 
 * @since PHP 8.4
 */

/**
 * Represents a raw SQL expression that should not be escaped
 * 
 * This class is used to embed literal SQL expressions in Query Builder
 * queries when automatic escaping is not desired.
 */
class fRawExpression
{
    private string $expression;
    
    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }
    
    /**
     * Get the raw SQL expression
     * 
     * @return string Raw SQL expression
     */
    public function getValue(): string
    {
        return $this->expression;
    }
    
    /**
     * Convert to string
     * 
     * @return string Raw SQL expression
     */
    public function __toString(): string
    {
        return $this->expression;
    }
    
    /**
     * Check if a value is a raw expression
     * 
     * @param mixed $value Value to check
     * @return bool True if raw expression
     */
    public static function isRaw(mixed $value): bool
    {
        return $value instanceof self;
    }
}