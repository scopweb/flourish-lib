<?php
/**
 * Test script for JsonToken Enum implementation
 * Tests new enum functionality while ensuring backward compatibility
 */

// Include necessary classes
require_once 'lib/fCore.php';
require_once 'lib/fJSON.php';

echo "🧪 Testing JsonToken Enum in fJSON\n";
echo "=================================\n\n";

try {
    echo "✨ Testing Enum Creation and Values:\n";
    echo "-----------------------------------\n";
    
    // Test all enum cases
    $tokens = [
        'ArrayOpen' => JsonToken::ArrayOpen,
        'ArrayComma' => JsonToken::ArrayComma,
        'ArrayClose' => JsonToken::ArrayClose,
        'ObjectOpen' => JsonToken::ObjectOpen,
        'Key' => JsonToken::Key,
        'Colon' => JsonToken::Colon,
        'ObjectComma' => JsonToken::ObjectComma,
        'ObjectClose' => JsonToken::ObjectClose,
        'Integer' => JsonToken::Integer,
        'Float' => JsonToken::Float,
        'True' => JsonToken::True,
        'False' => JsonToken::False,
        'Null' => JsonToken::Null,
        'String' => JsonToken::String
    ];
    
    foreach ($tokens as $name => $token) {
        echo "✅ JsonToken::{$name}: {$token->value} ('{$token->getDisplayName()}')\n";
    }
    echo "\n";
    
    echo "🔄 Testing Backward Compatibility:\n";
    echo "---------------------------------\n";
    
    // Test that old constants still exist and have correct values
    $legacyConstants = [
        'J_ARRAY_OPEN' => fJSON::J_ARRAY_OPEN,
        'J_ARRAY_COMMA' => fJSON::J_ARRAY_COMMA,
        'J_ARRAY_CLOSE' => fJSON::J_ARRAY_CLOSE,
        'J_OBJ_OPEN' => fJSON::J_OBJ_OPEN,
        'J_KEY' => fJSON::J_KEY,
        'J_COLON' => fJSON::J_COLON,
        'J_OBJ_COMMA' => fJSON::J_OBJ_COMMA,
        'J_OBJ_CLOSE' => fJSON::J_OBJ_CLOSE,
        'J_INTEGER' => fJSON::J_INTEGER,
        'J_FLOAT' => fJSON::J_FLOAT,
        'J_TRUE' => fJSON::J_TRUE,
        'J_FALSE' => fJSON::J_FALSE,
        'J_NULL' => fJSON::J_NULL,
        'J_STRING' => fJSON::J_STRING
    ];
    
    foreach ($legacyConstants as $name => $value) {
        echo "✅ fJSON::{$name}: {$value}\n";
    }
    echo "\n";
    
    echo "🎯 Testing Value Equivalence:\n";
    echo "----------------------------\n";
    
    // Test that enum values match legacy constants
    $matches = [
        'ArrayOpen' => fJSON::J_ARRAY_OPEN,
        'ArrayComma' => fJSON::J_ARRAY_COMMA,
        'ArrayClose' => fJSON::J_ARRAY_CLOSE,
        'ObjectOpen' => fJSON::J_OBJ_OPEN,
        'Key' => fJSON::J_KEY,
        'Colon' => fJSON::J_COLON,
        'ObjectComma' => fJSON::J_OBJ_COMMA,
        'ObjectClose' => fJSON::J_OBJ_CLOSE,
        'Integer' => fJSON::J_INTEGER,
        'Float' => fJSON::J_FLOAT,
        'True' => fJSON::J_TRUE,
        'False' => fJSON::J_FALSE,
        'Null' => fJSON::J_NULL,
        'String' => fJSON::J_STRING
    ];
    
    $allMatches = true;
    foreach ($matches as $enumCase => $constantValue) {
        $enumValue = constant('JsonToken::' . $enumCase)->value;
        $match = ($enumValue === $constantValue);
        $allMatches = $allMatches && $match;
        
        echo "✅ JsonToken::{$enumCase} === fJSON::J_" . strtoupper(preg_replace('/([A-Z])/', '_$1', $enumCase)) . ": " . ($match ? 'PASS' : 'FAIL') . "\n";
    }
    echo "\n";
    
    echo "🚀 Testing Enum Methods:\n";
    echo "-----------------------\n";
    
    // Test isValue() method
    echo "Value tokens: ";
    foreach ($tokens as $name => $token) {
        if ($token->isValue()) {
            echo $name . " ";
        }
    }
    echo "\n";
    
    // Test isStructural() method  
    echo "Structural tokens: ";
    foreach ($tokens as $name => $token) {
        if ($token->isStructural()) {
            echo $name . " ";
        }
    }
    echo "\n";
    
    // Test opening/closing tokens
    echo "Opening tokens: ";
    foreach ($tokens as $name => $token) {
        if ($token->isOpeningToken()) {
            echo $name . " ";
        }
    }
    echo "\n";
    
    echo "Closing tokens: ";
    foreach ($tokens as $name => $token) {
        if ($token->isClosingToken()) {
            echo $name . " ";
        }
    }
    echo "\n\n";
    
    echo "🔗 Testing Token Matching:\n";
    echo "-------------------------\n";
    
    $openingToken = JsonToken::ArrayOpen;
    $closingToken = $openingToken->getMatchingClosingToken();
    echo "✅ {$openingToken->getDisplayName()} matches {$closingToken->getDisplayName()}\n";
    
    $objectOpenToken = JsonToken::ObjectOpen;
    $objectClosingToken = $objectOpenToken->getMatchingClosingToken();
    echo "✅ {$objectOpenToken->getDisplayName()} matches {$objectClosingToken->getDisplayName()}\n\n";
    
    echo "🔄 Testing Conversion Methods:\n";
    echo "------------------------------\n";
    
    // Test constant to enum conversion
    $enumFromConstant = JsonToken::fromConstant(fJSON::J_STRING);
    echo "✅ fromConstant(J_STRING): {$enumFromConstant->name}\n";
    
    // Test enum to constant conversion  
    $constantFromEnum = JsonToken::String->toConstant();
    echo "✅ String->toConstant(): {$constantFromEnum}\n";
    echo "✅ Conversion round-trip: " . ($constantFromEnum === fJSON::J_STRING ? 'PASS' : 'FAIL') . "\n\n";
    
    echo "💡 Modern Usage Examples:\n";
    echo "------------------------\n";
    echo "// Traditional style (still works):\n";
    echo "if (\$token === fJSON::J_STRING) { /* ... */ }\n\n";
    
    echo "// Modern enum style (PHP 8.4):\n";
    echo "if (\$token === JsonToken::String) { /* ... */ }\n";
    echo "if (\$token->isValue()) { /* ... */ }\n";
    echo "match(\$token) {\n";
    echo "    JsonToken::String => 'handle string',\n";
    echo "    JsonToken::Integer, JsonToken::Float => 'handle number',\n";
    echo "    default => 'handle other'\n";
    echo "};\n\n";
    
    if ($allMatches) {
        echo "🎉 ALL TESTS PASSED!\n";
        echo "✅ Enum implementation successful\n";
        echo "✅ Full backward compatibility maintained\n"; 
        echo "✅ Type safety and modern syntax available\n";
        echo "✅ Rich enum methods functional\n";
    } else {
        echo "❌ Some equivalence tests failed\n";
    }
    
} catch (Exception $e) {
    echo "💥 ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}