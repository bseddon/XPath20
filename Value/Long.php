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
 * @version 0.9
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
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\exceptions\InvalidCastException;
use lyquidity\xml\exceptions\NotSupportedException;
use lyquidity\XPath2\XPath2Exception;
use lyquidity\xml\interfaces\IFormatProvider;

/**
 * Long
 */
class /* struct */ Long extends Integer implements IComparable, IConvertable, IEquatable, IXmlSchemaType
{
	/**
	 * CLASSNAME
	 * @var string
	 */
	public static $CLASSNAME = "lyquidity\XPath2\Value\Long";

	/**
	 * @var int ProxyValueCode
	 */
	const ProxyValueCode = 4;

	/**
	 * Unsigned
	 * @var bool $unsigned
	 */
	public $unsigned = false;

	/**
	 * Constructor
	 * @param object $value
	 * @throws NotSupportedException if the argument is not numeric or is not an Interger instance
	 */
	public function __construct( $value )
	{
		parent::__construct( $value );
	}

	/**
	 * Create an instance
	 * @param object $value
	 * @param bool $unsigned
	 * @return Long
	 * @throws ArgumentException if the argument is not numeric or is not an Interger instance
	 */
	public static function FromValue( $value, $unsigned = false )
	{
		if ( is_object( $value ) || ( is_string( $value ) && ! is_numeric( $value ) ) || strpos( $value, "." ) !== false )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, "integer" ) );
		}

		$decimal = DecimalValue::FromValue( $value );

		if (
			( $unsigned && (
					$value < 0 ||
					$decimal->CompareTo( "18446744073709551615" ) == 1
				)
			) ||
			( ! $unsigned && (
					$decimal->CompareTo( (int)0x8000000000000000 ) == -1 ||
					$decimal->CompareTo( (int)0x7fffffffffffffff ) == 1
				)
			)
		)
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, "integer" ) );
		}

		// if ( is_string( $value ) && ! is_numeric( $value ) ||
		// 	 ( ! $unsigned && (
		// 	 		$value < 0 ||
		// 	 		DecimalValue::FromValue( $value )->CompareTo( "18446744073709551615" ) == 1
		// 	 	)
		// 	 ) ||
		// 	 ( ! $unsigned && (
		// 	 		DecimalValue::FromValue( $value )->CompareTo( (int)0x8000000000000000 ) == -1 ||
		// 	 		DecimalValue::FromValue( $value )->CompareTo( (int)0x7fffffffffffffff ) == 1
		// 	 	)
		// 	 )
		// )
		// {
		// 	throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, "integer" ) );
		// }

		// if ( is_numeric( $value ) )
		// {
		// 	$value = round( $value );
		// }

		return new Long( $value );
	}

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$Long;
	}

	/**
	 * Returns the contained value
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * Equals
	 * @param object $obj
	 * @return bool
	 */
	public function Equals( $obj )
	{
		return $obj instanceof Integer
			? $this->_value == $obj->getValue()
			: false;
	}

	/**
	 * ToIntegerString
	 * @param Integer $value
	 * @return string
	 */
	public static function ToIntegerString( $value )
	{
		if ( ! $value instanceof Integer )
		{
			throw new NotSupportedException( "The type '$value' cannot be Convert::d to a type Integer" );
		}

	    return $value->ToString();
	}

	/**
	 * IsDerivedSubtype
	 * @param object $value
	 * @return bool
	 */
	public static function IsDerivedSubtype( $value )
	{
		if ( $value instanceof Integer )
	        return true;

	    switch ( Type::FromValue( $value )->getTypeCode() )
	    {
	        case TypeCode::SByte:
	        case TypeCode::Int16:
	        case TypeCode::Int32:
	        case TypeCode::Int64:
	        case TypeCode::Byte:
	        case TypeCode::UInt16:
	        case TypeCode::UInt32:
	        case TypeCode::UInt64:
	            return true;

	        default:
	            return false;
	    }
	}

	/**
	 * ToInteger
	 * @param object $value
	 * @param IFormatProvider $provider
	 * @return Integer
	 */
	public static function ToInteger( $value, $provider = null )
	{
	     $int = $value + 0;
	     if ( is_null( $int ) ) throw new InvalidCastException("Unable to convert value '$value' to type Integer" );

	     return Long::FromValue( $int );
	}

	/**
	 * GetTypeCode
	 * @return TypeCode
	 */
	public function GetTypeCode()
	{
	    return TypeCode::Int64;
	}

	/**
	 * ToInt64
	 * @param IFormatProvider $provider
	 * @return long
	 */
	public function ToInt64( $provider )
	{
	    return $this->_value;
	}

	/**
	 * Unit tests
	 */
	public static function tests()
	{
	}

}

?>
