<?php

/**
 * XPath 2.0 for PHP
 *  _                      _     _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *       |___/    |_|                    |___/
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

namespace lyquidity\XPath2\lyquidity;

use lyquidity\xml\interfaces\IFormatProvider;
use lyquidity\XPath2\Proxy\ValueProxy;
use lyquidity\XPath2\XPath2Item;
use lyquidity\XPath2\Value\DecimalValue;
use lyquidity\XPath2\Value\Integer;
use lyquidity\XPath2\Proxy\IntegerProxy;
use lyquidity\XPath2\Proxy\DecimalProxy;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\Value\UntypedAtomic;
use lyquidity\xml\exceptions\NotSupportedException;
use lyquidity\XPath2\XPath2Exception;

/**
 * Class to centralize
 */
class Convert
{
	/**
	 * Converts the value of this instance to an equivalent Boolean value using the
	 * specified culture-specific formatting information.
	 *
	 * @param object $value The value to convert
	 * @param IFormatProvider $provider An interface implementation that supplies culture-specific formatting information.
	 * @return bool A Boolean value equivalent to the value of this instance.
	 */
	public static function ToBoolean( $value, $provider = null )
	{
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Convert the value to a byte if the value is numeric
	 *
	 * @param object $value The value to convert
	 * @param IFormatProvider $provider An interface implementation that supplies culture-specific formatting information.
	 */
	public static function ToByte( $value, $provider = null )
	{
		if ( is_numeric( $value ) )
		{
			return (int)$value & 255;
		}

		return null;
	}

	/**
	 * Converts the value of this instance to an equivalent Unicode character using
	 * the specified culture-specific formatting information.
	 *
	 * @param object $value The value to convert
	 * @param IFormatProvider $provider An interface implementation that supplies culture-specific formatting information.
	 * @return char A Unicode character equivalent to the value of this instance.
	 */
	public static function ToChar( $value, $provider = null )
	{
		if ( $value instanceof \DateTime )
		{
			$value = Convert::ToString( $value, $provider );
			if ( is_null( $value ) ) return null;
		}

		if ( is_string( $value ) && strlen( $value ) )
		{
			return $value[0];
		}
		else if ( is_numeric( $value ) )
		{
			$ord = $value & 255;
			return chr( $ord );
		}

		return null;
	}

	/**
	 * ChangeType an alias for ToType
	 * @param Type $conversionType
	 * @param IFormatProvider $provider
	 * @return object
	 */
	public static function ChangeType( $value, $conversionType, $provider = null )
	{
		return Convert::ToType( $value, $conversionType, $provider );
	}

	/**
	 * Converts the value of this instance to an equivalent DateTime using the
	 * specified culture-specific formatting information.
	 *
	 * @param object $value The value to convert
	 * @param IFormatProvider $provider An interface implementation that supplies culture-specific formatting information.
	 * @return DateTime A DateTime instance equivalent to the value of this instance.
	 */
	public static function ToDateTime( $value, $provider = null )
	{
		if ( $value instanceof \DateTime ) return $value;

		if ( is_string( $value ) )
		{
			$timestamp = strtotime( $value );

			if ( $timestamp === false ) return null;

			/**
			 * @var \DateTime $dt
			 */
			$dt = new \DateTime();
			$dt->setTimestamp( $timestamp );

			return $dt;
		}

		$value = Convert::Toint( $value, $provider );
		if ( is_null( $value ) ) return null;

		$dt = new \DateTime();
		$dt->setTimestamp( $value );

		return $dt;
	}

	/**
	 * Converts the value of this instance to an equivalent System.Decimal number using
	 * the specified culture-specific formatting information.
	 *
	 * @param object $value The value to convert
	 * @param IFormatProvider $provide An implementation that supplies culture-specific formatting information.
	 * @return DecimalValue A Decimal number equivalent to the value of this instance.
	 */
	public static function ToDecimal( $value, $provider = null )
	{
		return is_double( $value )
			? DecimalValue::FromFloat( $value )
			: new DecimalValue( $value );
		// return Convert::ToDouble( $value, $provider );
	}

	/**
	 * Converts the value of this instance to an equivalent double-precision floating-point
	 * number using the specified culture-specific formatting information.
	 *
	 * @param object $value The value to convert
	 * @param IFormatProvider $provider An interface implementation that supplies culture-specific formatting information.
	 * @return double A double-precision floating-point number equivalent to the value of this instance.
	 */
	public static function ToDouble( $value, $provider = null )
	{
		if ( $value instanceof \DateTime )
		{
			return NAN; // $value->getTimestamp() + 0.0;
		}
		else if ( ( is_numeric( $value ) && $value == INF ) || ( is_string( $value ) && strtoupper( $value ) == "INF" ) )
		{
			return INF;
		}
		else if ( ( is_numeric( $value ) && $value == -INF ) || ( is_string( $value ) && strtoupper( $value ) == "-INF" ) )
		{
			return -INF;
		}
		if ( $x = ( is_numeric( $value ) && is_nan( $value ) ) || ( is_string( $value ) && strtoupper( $value ) == "NAN" ) )
		{
			return NAN;
		}
		else if ( $value instanceof DecimalValue )
		{
			return $value->ToDouble( $provider );
		}
		else if ( is_object( $value ) )
		{
			$text = "unknown";

			if ( $value instanceof ValueProxy )
			{
				/**
				 * @var ValueProxy $proxy
				 */
				$proxy = $value;
				return $proxy->ToDouble( $provider );
			}
			else if ( $value instanceof XPath2Item || $value instanceof Integer )
			{
				return $value->ToDouble( $provider );
			}
			else if ( $value instanceof UntypedAtomic )
			{
				$text = $value->__toString();
				if ( is_numeric( $text ) ) return $text + 0.0;
			}

			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $text, "xs:Double" ) );

		}
		else if ( is_numeric( trim( $value ) ) )
		{
			// This is an alternative multi-regional implementation to convert number strings
			// $locale = \Collator::create( null )->getLocale( \Locale::VALID_LOCALE );
			// $fmt = new \NumberFormatter( $locale, \NumberFormatter::DECIMAL );
			// $num = $fmt->parse( $this->Value );
			// if ( $num )
			// {
			// 	if ( $num == 0 ) $num = 0.0;
			// 	$this->_doubleValue = $num;
			// 	return true;
			// }

			return trim( $value ) + 0.0;
		}

		return null;
	}

	/**
	 * Converts the value of this instance to an equivalent System.String using the
	 * specified culture-specific formatting information.
	 *
	 * @param object $value The value to convert
	 * @param IFormatProvider $provider An interface implementation that supplies culture-specific formatting information.
	 * @return string A string instance equivalent to the value of this instance.
	 */
	public static function ToString( $value, $provider = null )
	{
		if ( $value instanceof XPath2Item )
		{
			return $value->getValue();
		}

		if ( $value instanceof \DateTime )
		{
			// Use locale information to create a formatted string
			$collator = \Collator::create( null );
			$locale = $collator->getLocale( \Locale::VALID_LOCALE );

			$dateTime = \IntlDateFormatter::create( $locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE );
			return $dateTime->format( $value );
		}
		return $value . "";
	}

	/**
	 * Converts the value of this instance to an equivalent integer
	 * using the specified culture-specific formatting information.
	 *
	 * @param object $value The value to convert
	 * @param IFormatProvider $provider An interface implementation that supplies culture-specific formatting information.
	 * @return int An 32-bit signed integer equivalent to the value of this instance.
	 */
	public static function ToInt( $value, $provider = null )
	{
		if ( $value instanceof ValueProxy )
		{
			return $value->ToInt( $provider );
		}

		if ( $value instanceof XPath2Item )
		{
			return Convert::ToInt( $value->getTypedValue(), $provider );
			// return $value->ToInt( $provider );
		}

		if ( is_numeric( $value ) )
		{
			if ( $value > PHP_INT_MAX ) return PHP_INT_MAX;
			if ( $value < PHP_INT_MIN ) return PHP_INT_MIN;
			return intval( $value + 0 );
		}

		if ( $value instanceof Integer || $value instanceof IntegerProxy || $value instanceof DecimalValue || $value instanceof DecimalProxy )
		{
			return $value->ToInt( $provider );
		}

		if ( $value instanceof \DateTime )
		{
			return $value->getTimestamp() + 0.0;
		}

		if ( $value instanceof DecimalValue )
		{
			return $value->ToInt( $provider );
		}

		return null;

	}

	/**
	 * ToInt16
	 * @param object $value The value to convert
	 * @param IFormatProvider $provider
	 * @return short
	 */
	public static function ToInt16( $value, $provider = null )
	{
		return Convert::Toint( $value, $provider );
	}

	/**
	 * ToInt32
	 * @param object $value The value to convert
	 * @param IFormatProvider $provider
	 * @return int
	 */
	public static function ToInt32( $value, $provider = null )
	{
		return Convert::Toint( $value, $provider );
	}

	/**
	 * ToInt64
	 * @param object $value The value to convert
	 * @param IFormatProvider $provider
	 * @return long
	 */
	public static function ToInt64( $value, $provider = null )
	{
		return Convert::Toint( $value, $provider );
	}

	/**
	 * ToSByte
	 * @param object $value The value to convert
	 * @param IFormatProvider $provider
	 * @return sbyte
	 */
	public static function ToSByte( $value, $provider = null )
	{
		return Convert::ToByte( $value, $provider );
	}

	/**
	 * ToSingle
	 * @param object $value The value to convert
	 * @param IFormatProvider $provider
	 * @return float
	 */
	public static function ToSingle( $value, $provider = null )
	{
		return Convert::ToDouble( $value, $provider );
	}

	/**
	 * ToType
	 * @param object $value
	 * @param Type $conversionType
	 * @param IFormatProvider $provider
	 * @return object
	 */
	public static function ToType( $value, $conversionType, $provider = null )
	{
		if ( is_null( $value ) ) return null;

		$valueType = Type::FromValue( $value );

		if ( $valueType->getFullName() ==  $conversionType->getFullName() )
			return $value;

		switch( $conversionType->getFullName() )
		{
			case Type::bool:
				return Convert::ToBoolean( $value, $provider );

			case Type::datetime:
				return Convert::ToDateTime( $value, $provider );

			case DecimalValue::$CLASSNAME:
			case Type::decimal:
				return Convert::ToDecimal( $value, $provider );

			case Type::double:
				return Convert::ToDouble( $value, $provider );

			case Type::float:
				return Convert::ToSingle( $value, $provider );

			case Type::int:
				return Convert::ToInt( $value, $provider );

			case Type::long:
				return Convert::ToInt64( $value, $provider );

			case Type::string:
				return Convert::ToString( $value, $provider );

			default:
				throw new NotSupportedException( "Conversion from {$valueType->getFullName()} to {$conversionType->getFullName()} not supported" );
		}

	}

	/**
	 * ToUInt16
	 * @param object $value The value to convert
	 * @param IFormatProvider $provider
	 * @return ushort
	 */
	public static function ToUInt16( $value, $provider = null )
	{
		$result = Convert::Toint( $value, $provider );
		if ( ! $result ) return null;
		return abs( $result );
	}

	/**
	 * ToUInt32
	 * @param object $value The value to convert
	 * @param IFormatProvider $provider
	 * @return uint
	 */
	public static function ToUInt32( $value, $provider = null )
	{
		$result = Convert::Toint( $value, $provider );
		if ( ! $result ) return null;
		return abs( $result );
	}

	/**
	 * ToUInt64
	 * @param object $value The value to convert
	 * @param IFormatProvider $provider
	 * @return ulong
	 */
	public static function ToUInt64( $value, $provider = null )
	{
		$result = Convert::Toint( $value, $provider );
		if ( ! $result ) return null;
		return abs( $result );
	}

	public static function tests()
	{
		$result = Convert::ToDecimal( "1", null ); // 1
		$result = Convert::ToDecimal( "1.1", null ); // 1.1
		$result = Convert::ToDecimal( "1.a", null ); // null
		$result = Convert::ToDecimal( "a", null ); // null
		$result = Convert::ToDecimal( 1.1, null ); // null
		$result = Convert::ToDecimal( null, null ); // null

		$result = Convert::ToInt( "1", null ); // 1
		$result = Convert::ToInt( "1.1", null ); // 1
		$result = Convert::ToInt( "1.a", null ); //null
		$result = Convert::ToInt( "a", null ); // null
		$result = Convert::ToInt( 1.1, null ); // 1
		$result = Convert::ToInt( null, null ); // null

		$result = Convert::ToDouble( "1", null ); // 1
		$result = Convert::ToDouble( "1.1", null ); // 1.1
		$result = Convert::ToDouble( "1.a", null ); // null
		$result = Convert::ToDouble( "a", null ); // null
		$result = Convert::ToDouble( 1.1, null ); // null
		$result = Convert::ToDouble( null, null ); // null

		$result = Convert::ToChar( "xxx", null ); // x
		$result = Convert::ToChar( "65.1", null ); // 6
		$result = Convert::ToChar( 65.1, null ); // A
		$result = Convert::ToChar( new \DateTime( null ), null ); // 5
		$result = Convert::ToChar( null, null ); // null

		$result = Convert::ToString( "xxx", null ); // xxx
		$result = Convert::ToString( "65.1", null ); // 65.1
		$result = Convert::ToString( 65.1, null ); // 65.1
		$result = Convert::ToString( new \DateTime( null ), null ); // 5/23/17
		$result = Convert::ToString( null, null ); // null

		$result = Convert::ToDateTime( "xxx", null ); // null
		$result = Convert::ToDateTime( "5/7/2017", null ); // DateTime
		$result = Convert::ToDateTime( 1700, null ); // DateTime
		$result = Convert::ToDateTime( new \DateTime( null ), null ); // DateTime
		$result = Convert::ToDateTime( null, null ); // null

		$result = Convert::ToBoolean( true, null ); // true
		$result = Convert::ToBoolean( false, null ); // false
		$result = Convert::ToBoolean( "true", null );  // true
		$result = Convert::ToBoolean( "false", null ); // false
		$result = Convert::ToBoolean( "1", null ); // true
		$result = Convert::ToBoolean( "0", null ); // false
		$result = Convert::ToBoolean( "1.1", null );  // false
		$result = Convert::ToBoolean( "1.a", null ); // false
		$result = Convert::ToBoolean( "a", null ); // false
		$result = Convert::ToBoolean( 1.1, null );  // false
		$result = Convert::ToBoolean( null, null ); // false
	}
}
