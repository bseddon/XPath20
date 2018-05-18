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

namespace lyquidity\XPath2\Value;

use lyquidity\xml\TypeCode;
use \lyquidity\XPath2\lyquidity\Type;
use lyquidity\xml\interfaces\IComparable;
use \lyquidity\xml\interfaces\IConvertable;
use lyquidity\xml\interfaces\IEquatable;
use \lyquidity\XPath2\lyquidity\Convert;
use lyquidity\XPath2\SequenceType;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\exceptions\InvalidCastException;
use lyquidity\xml\exceptions\ArgumentException;
use lyquidity\xml\exceptions\NotSupportedException;
use lyquidity\XPath2\XPath2Exception;

/**
 * Integer
 */
class /* struct */ Integer implements IComparable, IConvertable, IEquatable, IXmlSchemaType
{
	public static $CLASSNAME = "lyquidity\\XPath2\\Value\\Integer";

	/**
	 * @var int ProxyValueCode
	 */
	const ProxyValueCode = 4;

	/**
	 * @var int $_value
	 */
	protected   $_value;

	/**
	 * Constructor
	 * @param object $value
	 * @throws NotSupportedException if the argument is not numeric or is not an Interger instance
	 */
	public function __construct( $value )
	{
		if ( is_numeric( $value ) )
		{
			$this->_value = $value + 0;
			return;
		}

		if ( $value instanceof Integer )
		{
			$this->_value = $value->getValue();
			return;
		}

		if ( ( is_numeric( $value ) && is_nan( $value ) ) || ( is_string( $value ) && strtoupper( $value ) == "NAN" ) )
		{
			$this->_value = NAN;
			return;
		}

		if ( ( is_numeric( $value ) && is_infinite( $value ) ) || ( is_string( $value ) && in_array( strtoupper( $value ), array( "INF", "-INF" ) ) ) )
		{
			$this->_value = INF;
			return;
		}

		throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, "integer" ) );
	}

	/**
	 * Create an instance
	 * @param object $value
	 * @throws ArgumentException if the argument is not numeric or is not an Interger instance
	 */
	public static function FromValue( $value )
	{
		if ( is_string( $value ) && ( ! is_numeric( $value ) || ( ! is_int( $value + 0 ) && is_double( $value + 0 ) ) ) )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, "integer" ) );
		}

		if ( is_numeric( $value ) )
		{
			if ( is_infinite( $value ) )
				throw XPath2Exception::withErrorCodeAndParams( "FOAR0001", Resources::FOAR0001, array( $value, "integer" ) );

			if ( $value > PHP_INT_MAX || $value < PHP_INT_MIN )
				throw XPath2Exception::withErrorCodeAndParams( "FOAR0002", Resources::FOAR0002, array( $value, "integer" ) );
		}

		return new Integer( $value );
	}

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$Integer;
	}

	/**
	 * Returns the contained value
	 */
	public function getValue()
	{
		return intval( $this->_value );
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
	 * @return Integer
	 */
	public static function ToInteger( $value, $provider = null )
	{
		if ( $value instanceof Integer)
		{
			return $value;
		}

		if ( is_int( $value ) )
		{
			$int = $value;
		}
		else
		{
			$int = Convert::ToDouble( $value, $provider );
			if ( is_null( $int ) ) throw new InvalidCastException("Unable to convert value '$value' to type Integer" );
		}

		return Integer::FromValue( $int );
	}

	/**
	 * GetTypeCode
	 * @return TypeCode
	 */
	public function GetTypeCode()
	{
	    return TypeCode::Int32;
	}

	/**
	 * ToBoolean
	 * @param IFormatProvider $provider
	 * @return bool
	 */
	public function ToBoolean( $provider )
	{
	    return Convert::ToBoolean( $this->_value, $provider );
	}

	/**
	 * ToByte
	 * @param IFormatProvider $provider
	 * @return byte
	 */
	public function ToByte( $provider )
	{
	    return Convert::ToByte( $this->_value, $provider );
	}

	/**
	 * ToChar
	 * @param IFormatProvider $provider
	 * @return char
	 */
	public function ToChar( $provider )
	{
	    return Convert::ToChar( $this->_value, $provider );
	}

	/**
	 * ToDateTime
	 * @param IFormatProvider $provider
	 * @return DateTime
	 */
	public function ToDateTime( $provider )
	{
	    return Convert::ToDateTime( $this->_value, $provider );
	}

	/**
	 * ToDecimal
	 * @param IFormatProvider $provider
	 * @return DecimalValue
	 */
	public function ToDecimal( $provider )
	{
	    return Convert::ToDecimal( $this->_value, $provider );
	}

	/**
	 * ToDouble
	 * @param IFormatProvider $provider
	 * @return double
	 */
	public function ToDouble( $provider )
	{
	    return Convert::ToDouble( $this->_value, $provider );
	}

	/**
	 * ToInt16
	 * @param IFormatProvider $provider
	 * @return short
	 */
	public function ToInt16( $provider )
	{
	    return Convert::ToInt16( $this->_value, $provider );
	}

	/**
	 * ToInt
	 * @param IFormatProvider $provider
	 * @return int
	 */
	public function ToInt( $provider )
	{
		return Convert::ToInt32( $this->_value, $provider );
	}

	/**
	 * ToInt32
	 * @param IFormatProvider $provider
	 * @return int
	 */
	public function ToInt32( $provider )
	{
	    return Convert::ToInt32( $this->_value, $provider );
	}

	/**
	 * ToInt64
	 * @param IFormatProvider $provider
	 * @return long
	 */
	public function ToInt64( $provider )
	{
	    return Convert::ToInt64( $this->_value, $provider );
	}

	/**
	 * ToSByte
	 * @param IFormatProvider $provider
	 * @return sbyte
	 */
	public function ToSByte( $provider )
	{
	    return Convert::ToSByte( $this->_value, $provider );
	}

	/**
	 * ToSingle
	 * @param IFormatProvider $provider
	 * @return float
	 */
	public function ToSingle( $provider )
	{
	    return Convert::ToSingle( $this->_value, $provider );
	}

	/**
	 * ToString
	 * @param IFormatProvider $provider
	 * @return string
	 */
	public function ToString( $provider = null )
	{
	    return Convert::ToString( $this->_value, $provider );
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
	 * ToUInt16
	 * @param IFormatProvider $provider
	 * @return ushort
	 */
	public function ToUInt16( $provider )
	{
	    return Convert::ToUInt16( $this->_value, $provider );
	}

	/**
	 * ToUInt32
	 * @param IFormatProvider $provider
	 * @return uint
	 */
	public function ToUInt32( $provider )
	{
	    return Convert::ToUInt32( $this->_value, $provider );
	}

	/**
	 * ToUInt64
	 * @param IFormatProvider $provider
	 * @return ulong
	 */
	public function ToUInt64( $provider )
	{
	    return Convert::ToUInt64( $this->_value, $provider );
	}

	/**
	 * CompareTo
	 * @param object $obj
	 * @return int
	 */
	public function CompareTo( $obj )
	{
		if ( ! $obj instanceof Integer )
		{
			throw new ArgumentException( "The type '$obj' cannot be Converted to a type Integer" );
		}

		return ( $this->_value < $obj->getValue() ) ? -1 : (( $this->_value > $obj->getValue() ) ? 1 : 0);
	}

	public static function tests()
	{
		$integer1 = Integer::FromValue( 11.1 );
		$result = $integer1->ToString();

		$result = Integer::ToIntegerString( $integer1 );
		$result = Integer::IsDerivedSubtype( 12 );
		$integer2 = Integer::ToInteger( 14 );

		$result = $integer1->Equals( $integer1 );
		$result = $integer1->Equals( $integer2 );

		$result = $integer1->CompareTo( $integer1 );
		$result = $integer1->CompareTo( $integer2 );

		$result = $integer1->getValue();
		$result = $integer1->GetTypeCode();
		$provider = null;
		$result = $integer1->ToBoolean( $provider );
		$result = $integer1->ToByte($provider );
		$result = $integer1->ToChar($provider );
		$result = $integer1->ToDateTime($provider );
		$result = $integer1->ToDecimal($provider );
		$result = $integer1->ToDouble($provider );
		$result = $integer1->ToInt16($provider );
		$result = $integer1->ToInt32($provider );
		$result = $integer1->ToInt64($provider );
		$result = $integer1->ToSByte($provider );
		$result = $integer1->ToSingle( $provider );
		$type = SequenceType::WithTypeCode( XmlTypeCode::String )->ItemType;
		$result = $integer1->ToType( $type, $provider );
		$result = $integer1->ToUInt16( $provider );
		$result = $integer1->ToUInt32( $provider );
		$result = $integer1->ToUInt64( $provider );

	}

}



?>
