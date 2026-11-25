<?php
/**
 * JSON token enumeration
 *
 * Type safe representation of the tokens handled by the fJSON class. The
 * values of the cases are exactly the same as the legacy `fJSON::J_*`
 * constants, so both APIs can be used side by side while the code is migrated.
 *
 * @package    Flourish
 * @link       http://flourishlib.com/fJSON
 *
 */
enum JsonToken: int
{
	case ArrayOpen  = 0;
	case ArrayComma = 1;
	case ArrayClose = 2;
	case ObjectOpen = 3;
	case Key        = 4;
	case Colon      = 5;
	case ObjectComma = 6;
	case ObjectClose = 7;
	case Integer    = 8;
	case Float      = 9;
	case True       = 10;
	case False      = 11;
	case Null       = 12;
	case String     = 13;

	/**
	 * Checks if the token is part of the structure of the document
	 *
	 * Structural tokens are the ones that describe the shape of the document
	 * (brackets, braces, commas, colons and keys), as opposed to the tokens
	 * that carry a value.
	 *
	 * @return boolean  If the token is structural
	 */
	public function isStructural(): bool
	{
		return match ($this) {
			self::ArrayOpen, self::ArrayComma, self::ArrayClose,
			self::ObjectOpen, self::Key, self::Colon,
			self::ObjectComma, self::ObjectClose => true,
			default => false,
		};
	}

	/**
	 * Checks if the token carries a value
	 *
	 * @return boolean  If the token is a value token
	 */
	public function isValue(): bool
	{
		return match ($this) {
			self::Integer, self::Float, self::True, self::False,
			self::Null, self::String => true,
			default => false,
		};
	}

	/**
	 * Checks if the token opens a structure
	 *
	 * @return boolean  If the token opens an array or an object
	 */
	public function isOpeningToken(): bool
	{
		return match ($this) {
			self::ArrayOpen, self::ObjectOpen => true,
			default => false,
		};
	}

	/**
	 * Checks if the token closes a structure
	 *
	 * @return boolean  If the token closes an array or an object
	 */
	public function isClosingToken(): bool
	{
		return match ($this) {
			self::ArrayClose, self::ObjectClose => true,
			default => false,
		};
	}

	/**
	 * Gets the token that closes the structure opened by this one
	 *
	 * @return JsonToken|null  The matching closing token, or NULL if there is none
	 */
	public function getMatchingClosingToken(): ?self
	{
		return match ($this) {
			self::ArrayOpen => self::ArrayClose,
			self::ObjectOpen => self::ObjectClose,
			default => null,
		};
	}

	/**
	 * Gets a human readable name for the token
	 *
	 * @return string  The name of the token
	 */
	public function getDisplayName(): string
	{
		return match ($this) {
			self::ArrayOpen => '[',
			self::ArrayComma => ',',
			self::ArrayClose => ']',
			self::ObjectOpen => '{',
			self::Key => 'key',
			self::Colon => ':',
			self::ObjectComma => ',',
			self::ObjectClose => '}',
			self::Integer => 'integer',
			self::Float => 'float',
			self::True => 'true',
			self::False => 'false',
			self::Null => 'null',
			self::String => 'string',
		};
	}

	/**
	 * Gets the legacy constant that corresponds to this token
	 *
	 * @return integer  The value of the matching fJSON::J_* constant
	 */
	public function toConstant(): int
	{
		return $this->value;
	}

	/**
	 * Creates a token from a legacy constant
	 *
	 * @param  integer  $value  The value of a fJSON::J_* constant
	 * @return JsonToken|null  The matching token, or NULL if there is none
	 */
	public static function fromConstant(int $value): ?self
	{
		return self::tryFrom($value);
	}
}
