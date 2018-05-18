<?php
/**
 * XPath 2.0 for PHP
 *  _					   _	 _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *	     |___/	  |_|					 |___/
 *
 * @author Bill Seddon
 * @version 0.1.1
 * @Copyright (C) 2017 Lyquidity Solutions Limited
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace lyquidity\XPath2\Value;

use lyquidity\xml\TypeCode;
use \lyquidity\XPath2\lyquidity\Type;
use lyquidity\xml\interfaces\IComparable;
use \lyquidity\xml\interfaces\IConvertable;
use lyquidity\xml\interfaces\IEquatable;
use \lyquidity\XPath2\lyquidity\Convert;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\TrueValue;
use lyquidity\XPath2\FalseValue;
use lyquidity\XPath2\Proxy\ValueProxy;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\XPath2Item;
use lyquidity\xml\exceptions\NotSupportedException;
use lyquidity\XPath2\XPath2Exception;

/**
 * Integer
 */
class DecimalValue implements IComparable, IConvertable, IEquatable, IXmlSchemaType
{
	public static $CLASSNAME = "lyquidity\XPath2\Value\DecimalValue";

	/**
	 * @var int ProxyValueCode
	 */
	const ProxyValueCode = 5;

	/**
	 * @var string Pattern
	 */
	const Pattern = "/^(?<sign>[-+])?(?<digits>\d*)(?:\.(?<decimals>\d*))?(?:[Ee](?<exponent>[+-]?\d+))?/";

	/**
	 * Decimal values are stored as string an operated on by BC Math functions
	 * @var string $_value
	 */
	private  $_value;

	/**
	 * Test whether the BCMath extension exists
	 */
	public static function supported()
	{
		return extension_loaded('bcmath');
	}

	/**
	 * Constructor
	 * @param object $value
	 * @throws NotSupportedException if the argument is not numeric or is not an Interger instance
	 */
	public function __construct( $value )
	{
		if ( is_string( $value ) )
		{
			$value = trim( $value );
		}

		if ( is_numeric( $value ) )
		{
			$this->_value = $value . "";
			$matched = preg_match( DecimalValue::Pattern, $this->_value, $matches );
			if ( ! $matched || isset( $matches['exponent'] ) )
				throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, "Decimal" ) );
		}
		else if ( is_bool( $value ) )
		{
			$this->_value = $value ? 1 : 0;
		}
		else if ( $value instanceof DecimalValue )
		{
			$this->_value = $value->getValue() . "";
		}
		else if ( $value instanceof Integer )
		{
			$this->_value = $value->getValue();
		}
		else if ( $value instanceof Long )
		{
			$this->_value = $value->getValue();
		}
		else if ( $value instanceof TrueValue )
		{
			$this->_value = 1;
		}
		else if ( $value instanceof FalseValue )
		{
			$this->_value = 0;
		}
		else if ( $value instanceof ValueProxy )
		{
			if ( ! ValueProxy::IsNumericValue( Type::FromValue( $value->getValue() ) ) )
			{
				$valueClass = is_object( $value ) ? get_class( $value ) : "unknown";
				throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, "Decimal" ) );
			}

			$this->_value = $value->ToDouble( null ) . "";
		}
		else if ( $value instanceof XPath2Item )
		{
			if ( ! ValueProxy::IsNumericValue( $value->getValueType() ) )
			{
				throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value->getSchemaType()->Name, "Decimal" ) );
			}

			$this->_value = $value->getTypedValue();
		}
		else
		{
			$valueClass = is_object( $value ) ? get_class( $value ) : "unknown";
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, "Decimal" ) );
		}

		$this->normalize();
	}

	/**
	 * @param float $value
	 * @param bool $detail (optional: default = true) Expand the number to the most digits
	 */
	public static function FromFloat( $value, $detail = true )
	{
		if ( $value instanceof XPath2Item )
		{
			$value = $value->getTypedValue();
		}
		if ( $value instanceof DecimalValue ) return $value;

		if ( ( ! is_numeric( $value ) ) || is_infinite( $value ) || is_nan( $value ) )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FOCA0002", Resources::FOCA0002, array( $value, "Decimal" ) );
		}

		$matched = preg_match( DecimalValue::Pattern, $detail ? sprintf( "%.018g", $value ) : $value, $matches );
		if ( ! $matched )
		{
			throw new \InvalidArgumentException( "The number '$value' cannot be parsed" );
		}

		$sign = isset( $matches['sign'] ) && $matches['sign'] == '-' ? "-" : "";
		$digits = isset( $matches['digits'] ) ? $matches['digits'] : "0";
		$decimals = isset( $matches['decimals'] ) ? "." . $matches['decimals'] : "";
		$exponent = isset( $matches['exponent'] ) ? $matches['exponent'] + 0 : false;
		$factor = str_pad( "1", abs( $exponent ) + 1, "0", STR_PAD_RIGHT );

		$decimal = new DecimalValue( "$sign$digits$decimals" );
		if ( $exponent )
		{
			if ( $exponent > 0 )
			{
				$result = $decimal->Mul( $factor );
			}
			else if ( $exponent && $exponent < 0 )
			{
				$result = $decimal->Div( $factor );
			}
			$decimal = $result;
		}

		return $decimal;
	}

	/**
	 * Create an instance
	 * @param object $value
	 * @throws ArgumentException if the argument is not numeric or is not an Interger instance
	 */
	public static function FromValue( $value )
	{
		return new DecimalValue( $value );
	}

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$Decimal;
	}

	/**
	 * Returns the contained value
	 * @return string
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * Calculate the scale of the value
	 * @param string $number the decimal number for which to find the scale
	 */
	private function scale( $number )
	{
		return $this->getIsDecimal( $number ) ? strlen( $number ) - strpos( $number, "." ) - 1 : 0;
	}

	/**
	 * Get the effective size of the decimal portion
	 * @return int The number of decimal places to accommodate
	 */
	public function getScale()
	{
		return $this->scale( $this->_value );
	}

	/**
	 * Compares this number with another number
	 * @param string $number
	 * @param int $scale
	 * @return int 0 if the number is the same as this number, 1 if this number is larger than the supplied number, -1 otherwise.
	 */
	public function Compare( $number, $scale = false )
	{
		if ( ! is_numeric( $number ) )
		{
			if ( ! $number instanceof DecimalValue && ! $number instanceof Integer )
			{
				return false;
			}

			$number = $number->getValue();
		}

		if ( $scale === false )
		{
			$scale = max( $this->getScale(), $this->scale( $number ) );
		}

		return bccomp( $this->_value, $number, $scale );
	}

	/**
	 * Equals
	 * @param object $number
	 * @param int $scale
	 * @return bool
	 */
	public function Equals( $number, $scale = false )
	{
		$result = $this->Compare( $number, $scale );
		if ( $result === false ) return false;
		return $result == 0;
	}

	/**
	 * Greater than
	 * @param object $number
	 * @param int $scale
	 * @return bool
	 */
	public function Gt( $number, $scale = false )
	{
		$result = $this->Compare( $number, $scale );
		if ( $result === false ) return false;
		return $result == 1;
	}

	/**
	 * Neg
	 * @return DecimalValue
	 */
	public function Neg()
	{
		return new DecimalValue( bcmul( $this->_value, -1, $this->getScale() ) );
	}

	/**
	 * Add
	 * @param ValueProxy $val
	 * @return DecimalValue
	 */
	public function Add( $val )
	{
		$value = $val instanceof DecimalValue ? $val : Convert::ToDecimal( $val );
		return new DecimalValue( bcadd( $this->_value, $value->getValue(), max( $this->getScale(), $this->scale( $value ) ) ) );
	}

	/**
	 * Sub
	 * @param ValueProxy $val
	 * @return DecimalValue
	 */
	public function Sub( $val )
	{
		$value = $val instanceof DecimalValue ? $val : Convert::ToDecimal( $val );
		return new DecimalValue( bcsub( $this->_value, $value->getValue(), max( $this->getScale(), $this->scale( $value ) ) ) );
	}

	/**
	 * Mul
	 * @param ValueProxy $val
	 * @return DecimalValue
	 */
	public function Mul( $val )
	{
		$value = $val instanceof DecimalValue ? $val : Convert::ToDecimal( $val );
		return new DecimalValue( bcmul( $this->_value, $value->getValue(), $this->getScale() + $this->scale( $value ) ) );
	}

	/**
	 * Div
	 * @param ValueProxy $val
	 * @return DecimalValue
	 */
	public function Div( $denominator )
	{
		$denominator = $denominator instanceof DecimalValue ? $denominator : Convert::ToDecimal( $denominator );
		$denominator = $denominator->getValue();

		if ( $denominator == 0 || is_nan( $denominator ) || ( is_string( $denominator ) && strtoupper( $denominator ) == "NAN" ) )
			throw XPath2Exception::withErrorCode( "FOAR0001", Resources::FOAR0001 );

		$numerator = $this->_value;
		if ( is_nan( $numerator ) || ( is_string( $numerator ) && in_array( strtoupper( $numerator ), array( "INF", "-INF", "NAN" ) ) ) )
			throw XPath2Exception::withErrorCode( "FOAR0001", Resources::FOAR0001 );

		return new DecimalValue( bcdiv( $numerator, $denominator, max( 21, strlen( $denominator ) + $this->getScale() ) ) );
	}

	/**
	 * Pow
	 * @param DecimalValue $exponent
	 * @return DecimalValue
	 */
	public function Pow( $exponent )
	{
		$exponent = $exponent instanceof DecimalValue ? $exponent : Convert::ToDecimal( $exponent );
		return new DecimalValue( bcpow( $this->getValue(), $exponent->getValue(), 21 ) );
	}

	/**
	 * Is the value zero?
	 * @return bool
	 */
	public function getIsZero()
	{
		return $this->_value == 0;
	}

	/**
	 * Return true if the number is NAN
	 * @return bool
	 */
	public function getIsNAN()
	{
		return is_nan( $this->_value ) || strtoupper( $this->_value ) == "NAN";
	}

	/**
	 * Return true if the value is infinite
	 * @return bool
	 */
	public function getIsInfinite()
	{
		return is_infinite( $this->_value ) || in_array( strtoupper( $this->_value ), array( "INF", "-INF", "NAN" ) );
	}

	/**
	 * Mod
	 * @param DecimalValue|int|float|string $denominator
	 * @return DecimalValue
	 */
	public function Mod( $denominator )
	{
		$denominator = $denominator instanceof DecimalValue ? $denominator : Convert::ToDecimal( $denominator );

		if ( $denominator->getIsZero() )
			throw XPath2Exception::withErrorCode( "FOAR0001", Resources::FOAR0001 );

		if ( $denominator->getIsNAN() )
			throw XPath2Exception::withErrorCode( "FOAR0002", Resources::FOAR0002 );

		if ( $this->getIsNAN() || $this->getIsInfinite() )
			throw XPath2Exception::withErrorCode( "FOAR0002", Resources::FOAR0002 );

		$power = 0;
		// bcmod does not handle decimals so if they exist the powers of the two numbers must be increased until there are no decimals
		if ( $this->getIsDecimal() )
		{
			$power = strlen( $this->getIntegerPart() );
		}

		if ( $denominator->getIsDecimal() )
		{
			$power = max( strlen( $denominator->getIntegerPart() ), $power );
		}

		$multiplier = new DecimalValue( 10 );
		$multiplier = $multiplier->Pow( new DecimalValue( $power ) );
		$numerator = $this->Mul( $multiplier );
		$denominator = $denominator->Mul( $multiplier );
		$mod = bcmod( $numerator->getIntegerPart(), $denominator->getIntegerPart() );

		// Now need to divide by the multiplier to restore the correct power
		$mod = new DecimalValue( $mod );
		$mod = $mod->Div( $multiplier );
		return $mod;
	}

	/**
	 * Remove spaces and any trailing zeros
	 */
	private function normalize( $number = null )
	{
		if ( is_null( $number ) ) $number =& $this->_value;

		// Handle the case of -0
		if ( $this->_value == "-0" ) $this->_value = "0";

		if ( strpos( $this->_value, '.' ) === false ) return;

		$number = trim( $number );
		$number = rtrim( $number, '0');
		$number = rtrim( $number, '.');
		if ( $number == "" || $number == "-" ) $number .= "0";

		// The leading zero may be missing
		if ( substr( $number, 0, 1 ) == "." ) $number = "0" . $number;
		if ( substr( $number, 0, 2 ) == "-." ) $number = str_replace( "-.", "-0.", $number );
		if ( substr( $number, 0, 2 ) == "+." ) $number = str_replace( "+.", "0.", $number );

		return $number;
	}

	/**
	 * getAbs
	 * @return DecimalValue
	 */
	public function getAbs()
	{
		return new DecimalValue( ltrim( $this->_value, "-" ) );
	}

	/**
	 * True if the number has a fractional part and it is not zero
	 * @return bool
	 */
	public function getIsDecimal( $number = false )
	{
		return	strpos( $number === false ? $this->_value : $number, "." ) !== false &&
				trim( substr( $number === false ? $this->_value : $number, strpos( $number === false ? $this->_value : $number, "." ) + 1 ), "0" );
	}

	/**
	 * True if the number has a fractional part
	 * @return bool
	 */
	public function getIsNegative()
	{
		return strlen( $this->_value ) > 0 && $this->_value[0] == "-";
	}

	/**
	 * Get the ceiling value of the decimal number
	 * @return DecimalValue
	 */
	public function getCeil()
	{
		if ( $this->getIsDecimal() === false )
		{
			return $this;
		}

		if ( $this->getIsNegative() === true )
		{
			return new DecimalValue( bcadd( $this->_value, '0', 0 ) );
		}

		return new DecimalValue( bcadd( $this->_value, '1', 0 ) );  // Add one and truncate
	}

	/**
	 * Get the number that is one
	 * @return DecimalValue
	 */
	public function getFloor( $scale = false )
	{
		if ( $this->getIsDecimal() === false )
		{
			return $this;
		}

		if ( $this->getIsNegative() === true )
		{
			return new DecimalValue( bcadd( $this->_value, '-1', 0 ) ); // Subtract 1 and truncate
		}

		return new DecimalValue( bcadd( $this->_value, '0', 0 ) );
	}

	/**
	 * getRound
	 * @param int $precision
	 * @param int $mode PHP_ROUND_HALF_UP (default), PHP_ROUND_HALF_DOWN, PHP_ROUND_HALF_EVEN, PHP_ROUND_HALF_ODD
	 * @return DecimalValue
	 */
	public function getRound( $precision = 0, $mode = PHP_ROUND_HALF_UP )
	{
		if ( $this->getIsDecimal() === false )
		{
			// return $this;
		}

		if ( $precision === false ) $precision = $this->scale( $this->_value );
		if ( is_null( $precision ) ) $precision = 0;
		// if( $precision < 0) $precision = 0;

		$factor = 1;

		// If the precision is negative create a factor
		if ( $precision < 0 )
		{
			$precision = abs( $precision );
			$factor = bcpow( 10, $precision, $precision );
			$precision = 0;
		}

		// Create a new decimal on which to perform rounding
		$rounder = $this->Div( $factor );

		$isHalf = false;
		$increment = "0";
		$number = $rounder->_value;
		$sign = "";

		if ( $mode == PHP_ROUND_HALF_EVEN || $mode == PHP_ROUND_HALF_ODD )
		{
			// These are only relevant if the precision rounds the least significant digit and that digit is 5.
			if ( $rounder->getDecimalIsHalf( $precision ) )
			{
				$isHalf = true;

				if ( $precision - 1 >= 0 )
				// if ( $this->getIntegerPart() != 0 )
				{
					if ( $rounder->getIsDecimalEven( $precision - 1) )
					{
						if ( $mode == PHP_ROUND_HALF_ODD )
						{
							$sign = $rounder->getIsNegative() ? "-" : "";
							// The amount to add or substract is 1 at the digit position one above the $scale position
							// If the scale is zero then the value change is 1 or -1.  If the scale is 1 then the
							// change is 0.1 or -0.1.
							$increment = $sign . bcpow( 10, "-$precision", $precision );
						}
					}
					else
					{
						if ( $mode == PHP_ROUND_HALF_EVEN )
						{
							$sign = $rounder->getIsNegative() ? "-" : "";
							// The amount to add or substract is 1 at the digit position one above the $scale position
							// If the scale is zero then the value change is 1 or -1.  If the scale is 1 then the
							// change is 0.1 or -0.1.
							$increment = $sign . bcpow( 10, "-$precision", $precision );

						}
					}
				}
				else
				{
					if ( $rounder->getIsIntegerEven( $precision ) )
					{
						if ( $mode == PHP_ROUND_HALF_ODD )
						{
							$sign = $rounder->getIsNegative() ? -1 : 1;
							$increment = 1 * $sign;
						}
					}
					else
					{
						if ( $mode == PHP_ROUND_HALF_EVEN )
						{
							$sign = $rounder->getIsNegative() ? -1 : 1;
							$increment = 1 * $sign;
						}
					}
				}

			}
		}

		if ( $rounder->getIsNegative() && $mode == PHP_ROUND_HALF_UP )
		{
			$mode = PHP_ROUND_HALF_DOWN;
		// 	$mode = $mode == PHP_ROUND_HALF_UP
		// 		? PHP_ROUND_HALF_DOWN
		// 		: PHP_ROUND_HALF_UP;
		}

		// Round half-up or half-down according to the $mode
		if ( ! $isHalf && $rounder->getIsCloserToNext( $precision, $mode ) )
		{
			$decimalPart = $rounder->getDecimalPart();
			if ( strlen( $decimalPart > $precision ) )
			{
				$sign = $rounder->getIsNegative() ? "-" : "";
				// The amount to add or substract is 1 at the digit position one above the $scale position
				// If the scale is zero then the value change is 1 or -1.  If the scale is 1 then the
				// change is 0.1 or -0.1.
				$increment = $sign . bcpow( 10, "-$precision", $precision );
			}
		}

		$rounder = DecimalValue::FromValue( bcadd( $rounder->getValue(), $increment, $precision ) );
		if ( $factor > 1 )
		{
			$rounder = $rounder->Mul( $factor );
		}

		return $rounder;
		// return new DecimalValue( $number );
	}

	/**
	 * GetTypeCode
	 * @return TypeCode
	 */
	public function GetTypeCode()
	{
		return TypeCode::Object;
	}

	/**
	 * @param $scale The scale to use for rounding.  The digit at this position will
	 * 				 be rounded in the decimal part of the number will be rounded.
	 * @return string
	 */
	private function roundDigit( $scale, $mode = PHP_ROUND_HALF_UP )
	{
		if ( $this->getIsCloserToNext( $scale, $mode ) )
		{
			return bcadd( $this->_value, $this->getIsNegative() ? -1 : 1, 0 );
		}

		return bcadd( $this->_value, '0', 0 );
	}

	/**
	 * Get the last digit of the number
	 * @param int $precision
	 * @return bool
	 */
	private function lastIntegerDigit( $precision = 0 )
	{
		$integer = $this->getIntegerPart();
		return $integer[ strlen( $integer ) + ( -1 + $precision ) ];
	}

	/**
	 * Return the part of the number after the decimal point
	 * @return string
	 */
	public function getDecimalPart()
	{
		if ( ! $this->getIsDecimal() ) return "0";
		return substr( $this->_value, strpos( $this->_value, "." ) + 1 );
	}

	/**
	 * Return the part of the number before the decimal point
	 * Does not taken into account the size of any fraction.
	 * @return string
	 */
	public function getIntegerPart()
	{
		if ( ! $this->getIsDecimal() ) return $this->_value;
		return substr( $this->_value, 0, strpos( $this->_value, "." ) );
	}

	/**
	 * Test to see if the digit at scale + 1 is a 5
	 * @return bool
	 */
	public function getDecimalIsHalf( $scale = false )
	{
		if ( ! $this->getIsDecimal() ) {
			return false;
		}

		if ( $scale === false ) $scale = $this->scale( $this->_value );
		if ( $scale < 0 ) $scale = $scale = 0;

		$decimalPart = $this->getDecimalPart();
		if ( $scale >= strlen( $decimalPart ) ) return false;

		// Get the portion of the decimal part affected by the scale value
		$decimalPart = substr( $decimalPart, $scale );
		return strlen( $decimalPart ) == 1 && $decimalPart == "5";
	}

	/**
	 * Is the integer at the precision point even.  The precision is the count from the *end* of the number
	 * @param int $precision
	 * @return bool
	 */
	public function getIsIntegerEven( $precision = 0)
	{
		return $this->lastIntegerDigit( $precision ) % 2 === 0;
	}

	/**
	 * @return bool
	 */
	public function getIsIntegerOdd()
	{
		return ! $this->getIsIntegerEven();
	}

	/**
	 * Returns true if the digit at the required level of precision is even
	 * @param string $precision
	 * @return bool
	 */
	public function getIsDecimalEven( $precision = false )
	{
		if ( ! $this->getIsDecimal() ) {
			return false;
		}

		if ( $precision === false ) $precision = $this->scale( $this->_value );
		if ( $precision < 0 ) $precision = $precision = 0;

		$decimalPart = $this->getDecimalPart();
		if ( $precision >= strlen( $decimalPart ) ) return false;

		// Get the portion of the decimal part affected by the scale value
		$decimalPart = substr( $decimalPart, $precision );
		return $decimalPart[0] % 2 === 0;
	}

	/**
	 * Returns true if the number at the scale position in the decimal part of the number is closer to the next power.
	 * @return bool
	 */
	public function getIsCloserToNext( $precision = false, $mode = PHP_ROUND_HALF_UP )
	{
		if ( ! $this->getIsDecimal() ) {
			return false;
		}

		if ( $precision === false ) $precision = $this->scale( $this->_value );
		if ( $precision < 0 ) $precision = $precision = 0;

		$decimalPart = $this->getDecimalPart();
		if ( $precision >= strlen( $decimalPart ) ) return false;

		// Get the portion of the decimal part affected by the scale value
		$decimalPart = substr( $decimalPart, $precision );
		$result = bccomp( "0." . $decimalPart, "0.5", max( $this->scale( $decimalPart ), 1 ) );
		return $mode == PHP_ROUND_HALF_DOWN ? $result == 1 : $result >= 0;
	}

	/**
	 * ToBoolean
	 * @param IFormatProvider $provider
	 * @return bool
	 */
	public function ToBoolean( $provider )
	{
		return ! $this->Equals( "0" );
	}

	/**
	 * Not
	 * ToByte
	 * @param IFormatProvider $provider
	 * @return byte
	 */
	public function ToByte( $provider )
	{
		throw new NotSupportedException();
	}

	/**
	 * ToChar
	 * @param IFormatProvider $provider
	 * @return char
	 */
	public function ToChar( $provider )
	{
		throw new NotSupportedException();
	}

	/**
	 * ToDateTime
	 * @param IFormatProvider $provider
	 * @return DateTime
	 */
	public function ToDateTime( $provider )
	{
		throw new NotSupportedException();
	}

	/**
	 * Not
	 * ToDecimal
	 * @param IFormatProvider $provider
	 * @return decimal
	 */
	public function ToDecimal( $provider )
	{
		return $this->getValue();
	}

	/**
	 * ToInt
	 * @param IFormatProvider $provider
	 * @return int
	 */
	public function ToInt( $provider )
	{
		return $this->getRound( 0 )->getValue() + 0;
	}

	/**
	 * ToInt32
	 * @param IFormatProvider $provider
	 * @return int
	 */
	public function ToInt32( $provider )
	{
		return $this->ToInt( $provider );
	}

	/**
	 * ToDouble
	 * @param IFormatProvider $provider
	 * @return double
	 */
	public function ToDouble( $provider )
	{
		return $this->_value + 0.0;
	}

	/**
	 * Not
	 * ToInt64
	 * @param IFormatProvider $provider
	 * @return long
	 */
	public function ToInt64( $provider )
	{
		return $this->ToInt( $provider );
	}

	/**
	 * Not
	 * ToSByte
	 * @param IFormatProvider $provider
	 * @return sbyte
	 */
	public function ToSByte( $provider )
	{
		return $this->ToInt( $provider );
	}

	/**
	 * ToSingle
	 * @param IFormatProvider $provider
	 * @return float
	 */
	public function ToFloat( $provider )
	{
		return $this->ToSingle( $provider );
	}

	/**
	 * ToSingle
	 * @param IFormatProvider $provider
	 * @return float
	 */
	public function ToSingle( $provider )
	{
		return floatval( $this->_value );

		// Expand the number to a string
		return sprintf( "%.8G", $this->_value );

		$matched = preg_match( DecimalValue::Pattern , $this->_value, $matches );
		if ( ! $matched )
		{
			throw new \InvalidArgumentException( "The number '{$this->_value}' cannot be parsed" );
		}

		$sign = isset( $matches['sign'] ) && $matches['sign'] == '-' ? -1 : 1;
		$digits = isset( $matches['digits'] ) ? $matches['digits'] : "";
		$decimals = isset( $matches['decimals'] ) ? $matches['decimals'] : "";
		$exponent = isset( $matches['exponent'] ) ? $matches['exponent'] + 0 : false;
		$mantissa = $digits . $decimals;

		while ( strlen( decbin( $mantissa ) ) > 24 )
		{
			$mantissa = round( $mantissa / 10 );
		}

		$number = "";
		$diff = strlen( $digits ) - strlen( $mantissa );
		if ( $diff > 0 )
		{
			$exponent += $diff;
			$number = $mantissa;
		}
		else if ( $diff == 0 )
		{
			$number = $mantissa;
		}
		else
		{
			$number = $digits . "." . substr( $mantissa, strlen( $digits ) );
		}

		if ( $exponent )
		{
			$number .= "E{$exponent}";
		}

		$number = $number + 0.0;

		if ( $number > 2**104 )
		{
			$number = $sign == -1 ? -INF : INF;
		}
		else if ( $number < 2**-149 )
		{
			$number = $sign == 1 ? 0 : -0;
		}

		$number = $number * $sign;
		return $number;
	}

	/**
	 * Create a binary representation of the current value
	 * @param string $decimal The number to be converted
	 * @return string
	 */
	private function dec2bin( $decimal )
	{
		bcscale(0);
		$binary = '';
		do
		{
			$binary = bcmod( $decimal, '2' ) . $binary;
			$decimal = bcdiv( $decimal, '2' );
		} while ( bccomp( $decimal, '0' ) );

		return( $binary );
	}

	/**
	 * ToSingle
	 * @param IFormatProvider $provider
	 * @return float
	 */
	public function ToDoubleString( $provider )
	{
		$sign = $this->getIsNegative();

		if ( bccomp( $this->_value, bcpow( 2, 204 ) ) > 0 )
		{
			return $sign ? -INF : INF;
		}
		else if ( bccomp( $this->getAbs()->getValue(), bcpow( 2, -149, 149 ) ) < 0 )
		{
			return $sign ? 0 : -0;
		}

		$abs = $this->getAbs();
		$decimals = rtrim( $this->getDecimalPart(), "0" );

		if ( bccomp( $abs->getValue(), 1 ) == -1 )
		{
			// Are there enough bits available to store the number?
			if ( $this->dec2bin( $decimals ) > 56 )
			{
				$exponent = 1;
				// First remove leading zeros which become part of the exponent
				while( $decimals[0] == 0 )
				{
					$exponent++;
					$decimals = substr( $decimals, 1 );
				}

				$number = $decimals[0] . "." . substr( $decimals, 1 ) . "E-" . $exponent;
			}
			else
			{
				return $this->_value;
			}
		}
		else
		{
			$digits = $this->getAbs()->getIntegerPart();

			$mantissa = rtrim( $digits . $decimals, "0" );
			$exponentRequired = strlen( $digits ) >= 20;
			while ( strlen( $this->dec2bin( $mantissa ) ) > 56 )
			{
				$mantissa = bcdiv( $mantissa, 10, 0 );
				$exponentRequired = true;
			}

			$number = $mantissa;
			if ( $exponentRequired )
			{
				$number = $mantissa[0] . "." . substr( $mantissa, 1 );
				$exponent = strlen( $digits ) -1;
				$number .= "E$exponent";
			}
			else
			{
				$number = rtrim( $digits . "." . implode( "", explode( $digits, $mantissa, 2) ), "." );
			}
		}

		if ( $sign )
		{
			$number = "-" . $number;
		}

		return $number;
	}

	/**
	 * Not
	 * ToString
	 * @param IFormatProvider $provider
	 * @return string
	 */
	public function ToString( $provider = null )
	{
		return $this->_value;
	}

	/**
	 * Return a stringified version of the object
	 * @return string
	 */
	public function __toString()
	{
		return $this->ToString();
	}

	/**
	 * Not
	 * ToType
	 * @param Type $conversionType
	 * @param IFormatProvider $provider
	 * @return object
	 */
	public function ToType( $conversionType, $provider )
	{
		return Convert::ChangeType( $this->_value, $conversionType, $provider );
	}

	/**
	 * Not
	 * ToUInt16
	 * @param IFormatProvider $provider
	 * @return ushort
	 */
	public function ToUInt16( $provider )
	{
		return $this->ToInt32( $provider );
	}

	/**
	 * Not
	 * ToUInt32
	 * @param IFormatProvider $provider
	 * @return uint
	 */
	public function ToUInt32( $provider )
	{
		return abs( $this->ToInt( $provider ) );
	}

	/**
	 * Not
	 * ToUInt64
	 * @param IFormatProvider $provider
	 * @return ulong
	 */
	public function ToUInt64( $provider )
	{
		return $this->ToUInt64( $provider );
	}

	/**
	 * CompareTo
	 * @param object $number
	 * @return int
	 */
	public function CompareTo( $number )
	{
		return $this->Compare( $number );
	}

	public static function tests()
	{
		$int = new DecimalValue( 10 );
		$decHalf = new DecimalValue( -10.54 );
		$decLo = new DecimalValue( 11.465 );
		$decHi = new DecimalValue( 10.62 );
		$decPoint5 = new DecimalValue( 0.5 );
		$decMinusPoint5 = new DecimalValue( -2.5 );

		$result = DecimalValue::FromValue( 12455.00 )->getRound( -2, PHP_ROUND_HALF_EVEN )->getValue();
		$result = DecimalValue::FromValue( 12450.00 )->getRound( -2, PHP_ROUND_HALF_EVEN )->getValue();
		$result = DecimalValue::FromValue( 35612.25 )->getRound( -2, PHP_ROUND_HALF_EVEN )->getValue();

		$result = $decHalf->getIsDecimalEven( 0 );
		$result = $decHalf->getIsDecimalEven( 1 );
		$result = $decHalf->getIsDecimalEven( 2 );
		$result = $decLo->getIsDecimalEven( 0 );
		$result = $decLo->getIsDecimalEven( 1 );
		$result = $decLo->getIsDecimalEven( 2 );

		$result = $decHalf->getDecimalIsHalf( 0 ); // False
		$result = $decLo->getDecimalIsHalf( 2 ); // true

		$result = $decPoint5->getRound( 0, PHP_ROUND_HALF_UP )->getValue(); // 1
		$result = $decPoint5->getRound( 0, PHP_ROUND_HALF_EVEN )->getValue(); // 0

		$result = $decPoint5->add( 1 )->getRound( 0, PHP_ROUND_HALF_UP )->getValue(); // 2
		$result = $decPoint5->add( 1 )->getRound( 0, PHP_ROUND_HALF_EVEN )->getValue(); // 2

		$result = $decPoint5->add( 2 )->getRound( 0, PHP_ROUND_HALF_UP )->getValue(); // 3
		$result = $decPoint5->add( 2 )->getRound( 0, PHP_ROUND_HALF_EVEN )->getValue(); // 2

		$result = $decPoint5->add( 3 )->getRound( 0, PHP_ROUND_HALF_UP )->getValue(); // 4
		$result = $decPoint5->add( 3 )->getRound( 0, PHP_ROUND_HALF_EVEN )->getValue(); // 4

		$result = $decPoint5->add( 4 )->getRound( 0, PHP_ROUND_HALF_UP )->getValue(); // 5
		$result = $decPoint5->add( 4 )->getRound( 0, PHP_ROUND_HALF_EVEN )->getValue(); // 4

		$result = $decMinusPoint5->getRound( 0, PHP_ROUND_HALF_UP )->getValue();
		$result = $decMinusPoint5->getRound( 0, PHP_ROUND_HALF_DOWN )->getValue();
		$result = $decMinusPoint5->getRound( 0, PHP_ROUND_HALF_EVEN )->getValue();

		$result = $decLo->getRound( 0 )->getValue(); // 11
		$result = $decHi->getRound( 0 )->getValue(); // 11
		$result = $decHalf->getRound( 0 )->getValue(); // -11
		$result = $decLo->getRound( 1 )->getValue(); // 11.5
		$result = $decHi->getRound( 1 )->getValue(); // 10.6
		$result = $decHalf->getRound( 1 )->getValue(); // -10.5
		$result = $decLo->getRound( 2 )->getValue(); // 11.47
		$result = $decHi->getRound( 2 )->getValue(); // 10.62
		$result = $decHalf->getRound( 2 )->getValue(); // -10.54
		$result = $decLo->getRound( 2, PHP_ROUND_HALF_DOWN )->getValue(); // 11.46
		$result = $decHalf->getRound( 1, PHP_ROUND_HALF_EVEN )->getValue(); // -10.5
		$result = $decLo->getRound( 1, PHP_ROUND_HALF_EVEN )->getValue(); // 11.5
		$result = $decLo->getRound( 1, PHP_ROUND_HALF_ODD )->getValue(); // 11.5
		$result = $decLo->getRound( 2, PHP_ROUND_HALF_EVEN )->getValue(); // 11.46
		$result = $decLo->getRound( 2, PHP_ROUND_HALF_ODD )->getValue(); // 11.47

		// These are the tests from the PHP manual on the round() function
		$result = ( new DecimalValue( 1.55 ) )->getRound( 1, PHP_ROUND_HALF_UP)->getValue();   //  1.6
		$result = ( new DecimalValue( 1.54 ) )->getRound( 1, PHP_ROUND_HALF_UP)->getValue();   //  1.5
		$result = ( new DecimalValue( -1.55 ) )->getRound( 1, PHP_ROUND_HALF_UP)->getValue();   // -1.6
		$result = ( new DecimalValue( -1.54 ) )->getRound( 1, PHP_ROUND_HALF_UP)->getValue();   // -1.5

		/* Using PHP_ROUND_HALF_DOWN with 1 decimal digit precision */
		$result = ( new DecimalValue( 1.55 ) )->getRound( 1, PHP_ROUND_HALF_DOWN)->getValue(); //  1.5
		$result = ( new DecimalValue( 1.54 ) )->getRound( 1, PHP_ROUND_HALF_DOWN)->getValue(); //  1.5
		$result = ( new DecimalValue( -1.55 ) )->getRound( 1, PHP_ROUND_HALF_DOWN)->getValue(); // -1.5
		$result = ( new DecimalValue( -1.54 ) )->getRound( 1, PHP_ROUND_HALF_DOWN)->getValue(); // -1.5

		/* Using PHP_ROUND_HALF_EVEN with 1 decimal digit precision */
		$result = ( new DecimalValue( 1.55 ) )->getRound( 1, PHP_ROUND_HALF_EVEN)->getValue(); //  1.6
		$result = ( new DecimalValue( 1.54 ) )->getRound( 1, PHP_ROUND_HALF_EVEN)->getValue(); //  1.5
		$result = ( new DecimalValue( -1.55 ) )->getRound( 1, PHP_ROUND_HALF_EVEN)->getValue(); // -1.6
		$result = ( new DecimalValue( -1.54 ) )->getRound( 1, PHP_ROUND_HALF_EVEN)->getValue(); // -1.5

		/* Using PHP_ROUND_HALF_ODD with 1 decimal digit precision */
		$result = ( new DecimalValue( 1.55 ) )->getRound( 1, PHP_ROUND_HALF_ODD)->getValue();  //  1.5
		$result = ( new DecimalValue( 1.54 ) )->getRound( 1, PHP_ROUND_HALF_ODD)->getValue();  //  1.5
		$result = ( new DecimalValue( -1.55 ) )->getRound( 1, PHP_ROUND_HALF_ODD)->getValue();  // -1.5
		$result = ( new DecimalValue( -1.54 ) )->getRound( 1, PHP_ROUND_HALF_ODD)->getValue();  // -1.5

		$result = $decLo->getIsCloserToNext(); // False
		$result = $decLo->getIsCloserToNext( 0 ); // False
		$result = $decLo->getIsCloserToNext( 1 ); // True
		$result = $decLo->getIsCloserToNext( 2 ); // True
		$result = $decLo->getIsCloserToNext( 3 ); // False
		$result = $decLo->getIsCloserToNext( -1 ); // False (effectively zero)
		$result = $decLo->getIsCloserToNext( 2, PHP_ROUND_HALF_DOWN ); // False

		$result = $decHalf->getIsCloserToNext(); // False
		$result = $decHalf->getIsCloserToNext( 0 ); // True
		$result = $decHalf->getIsCloserToNext( 1 ); // False
		$result = $decHalf->getIsCloserToNext( 2 ); // False
		$result = $decHalf->getIsCloserToNext( -1 ); // True (effectively zero)

		$result = $decHi->getIsCloserToNext(); // False
		$result = $decHi->getIsCloserToNext( 0 ); // True
		$result = $decHi->getIsCloserToNext( 1 ); // False
		$result = $decHi->getIsCloserToNext( 2 ); // False
		$result = $decHi->getIsCloserToNext( -1 ); // True (effectively zero)

		$result = $int->getIntegerPart();
		$result = $int->getDecimalPart();
		$result = $int->getIsDecimal();
		$result = $int->getIsNegative();
		$result = $int->getIsIntegerOdd();
		$result = $int->getDecimalIsHalf();
		$result = $int->getIsIntegerEven();
		$result = $int->getAbs();
		$result = $int->getCeil();
		$result = $int->getFloor();
		$result = $int->CompareTo( $decHalf );
		$result = $int->Equals( 10 ); // True
		$result = $int->Gt( 10.6 ); // False
		$result = $int->getIsCloserToNext();

		$result = $decHalf->getIntegerPart();
		$result = $decHalf->getDecimalPart();
		$result = $decHalf->getIsDecimal();
		$result = $decHalf->getIsNegative();
		$result = $decHalf->getIsIntegerOdd();
		$result = $decHalf->getDecimalIsHalf();
		$result = $decHalf->getIsIntegerEven();
		$result = $decHalf->getAbs();
		$result = $decHalf->getCeil();
		$result = $decHalf->getFloor();
		$result = $decHalf->CompareTo( $decHalf );
		$result = $decHalf->Equals( 10 ); // False
		$result = $decHalf->Gt( 10.6 ); // False
		$result = $decHalf->getIsCloserToNext();

		$result = $decLo->getIntegerPart();
		$result = $decLo->getDecimalPart();
		$result = $decLo->getIsDecimal();
		$result = $decLo->getIsNegative();
		$result = $decLo->getIsIntegerOdd();
		$result = $decLo->getDecimalIsHalf();
		$result = $decLo->getIsIntegerEven();
		$result = $decLo->getAbs();
		$result = $decLo->getCeil();
		$result = $decLo->getFloor();
		$result = $decLo->CompareTo( $decHalf );
		$result = $decLo->Equals( 10 ); // False
		$result = $decLo->Gt( 10.6 ); // True
		$result = $decLo->getIsCloserToNext();

		$result = $decHi->getIntegerPart();
		$result = $decHi->getDecimalPart();
		$result = $decHi->getIsDecimal();
		$result = $decHi->getIsNegative();
		$result = $decHi->getIsIntegerOdd();
		$result = $decHi->getDecimalIsHalf();
		$result = $decHi->getIsIntegerEven();
		$result = $decHi->getAbs();
		$result = $decHi->getCeil();
		$result = $decHi->getFloor();
		$result = $decHi->CompareTo( $decHalf );
		$result = $decHi->Equals( 10 ); // False
		$result = $decHi->Gt( 10.6 ); // True
		$result = $decHi->getIsCloserToNext();

	}
}