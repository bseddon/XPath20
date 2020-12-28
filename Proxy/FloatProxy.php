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

namespace lyquidity\XPath2\Proxy;

use lyquidity\xml\TypeCode;
use \lyquidity\XPath2\lyquidity\Convert;
use lyquidity\XPath2\lyquidity\Type;
use lyquidity\XPath2\Value\DateTimeValue;
use lyquidity\XPath2\Value\Integer;
use lyquidity\XPath2\SequenceType;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IFormatProvider;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\Value\DecimalValue;
use lyquidity\XPath2\XPath2Item;
use lyquidity\xml\MS\XmlSchemaType;
use lyquidity\XPath2\XPath2Exception;
use lyquidity\XPath2\Value\Long;

/**
 * Float (internal final)
 */
class FloatProxy extends ValueProxy implements IXmlSchemaType
{


	/**
	 * Value
	 * @var float $_value
	 */
	private $_value;

	/**
	 * Constructor
	 * @param float $value
	 */
	public function __construct( $value )
	{
		if ( $value != 0 && ( abs( $value ) > pow( 2, 128 ) || abs( $value ) < pow( 2, -126 ) ) )
		{
			$this->_value = $value > 0 ? INF : -INF;
		}
		else
		{
			$this->_value = $value;
		}
	}

	/**
	 * GetValueCode
	 * @return int
	 */
	public function GetValueCode()
	{
	    return FloatProxyFactory::Code;
	}

	/**
	 * Get the value
	 * @return XPath2Item
	 */
	public function getValue()
	{
		return XPath2Item::fromValueAndType( $this->_value, XmlSchema::$Float );
		return $this->_value;
	}

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$Float;
	}

	/**
	 * IsNaN
	 * @return bool
	 */
	public function IsNaN()
	{
	    return is_nan( $this->_value );
	}

	/**
	 * IsNumeric
	 * @return bool
	 */
	public function getIsNumeric()
	{
	    return true;
	}

	/**
	 * Convert an XPath2Item value to a native float or return the original value
	 * @param FloatProxy $val
	 * @return float
	 */
	private function getOtherValue( $val )
	{
		/**
		 * @var XPath2Item $otherValue
		 */
		$otherValue = $val->getValue();
		return $otherValue instanceof XPath2Item
			? $otherValue->getValue()
			: $otherValue;
	}

	/**
	 * Eq
	 * @param ValueProxy $val
	 * @return bool
	 */
	protected function Eq( $val )
	{
		return  $this->_value == $this->getOtherValue( $val );
	}

	/**
	 * Gt
	 * @param ValueProxy $val
	 * @return bool
	 */
	protected function Gt( $val )
	{
	    return  $this->_value > $this->getOtherValue( $val );
	}

	/**
	 * Promote
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Promote( $val )
	{
	    return new FloatProxy( Convert::ToSingle( $val ) );
	}

	/**
	 * Neg
	 * @return ValueProxy
	 */
	protected function Neg()
	{
	    return new FloatProxy( -$this->_value );
	}

	/**
	 * Add
	 * @param FloatProxy $val
	 * @return FloatProxy
	 */
	protected function Add( $val )
	{
	    return new FloatProxy( $this->_value + $this->getOtherValue( $val ) );
	}

	/**
	 * Sub
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Sub( $val )
	{
	    return new FloatProxy( $this->_value - $this->getOtherValue( $val ) );
	}

	/**
	 * Mul
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Mul( $val )
	{
	    return new FloatProxy( $this->_value * $this->getOtherValue( $val ) );
	}

	/**
	 * Div
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Div( $val )
	{
		$val = $this->getOtherValue( $val );
		if ( $val == 0 )
		{
			return new FloatProxy( $this->_value >= 0 ? INF : -INF );
		}

	    return new FloatProxy( $this->_value / $val );
	}

	/**
	 * IDiv
	 * @param ValueProxy $val
	 * @return Integer
	 */
	protected function IDiv( $val )
	{
		$val = $this->getOtherValue( $val );

		if ( is_infinite( $this->_value ) && $val == 0 || $val == 0 )
		{
			throw XPath2Exception::withErrorCode( "FOAR0001", Resources::FOAR0001 );
		}

		if ( is_infinite( $this->_value ) || is_nan( $this->_value ) || is_nan( $val ) )
		{
			throw XPath2Exception::withErrorCode( "FOAR0002", Resources::FOAR0002 );
		}

	    return Integer::FromValue( round( $this->_value / $val ) );
	}

	/**
	 * Mod
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Mod( $val )
	{
		$val = $this->getOtherValue( $val );

		if ( is_infinite( $this->_value ) || is_nan( $this->_value ) || is_nan( $val ) || $val == 0 )
		{
			return new FloatProxy( NAN );
		}

		if ( is_infinite( $val ) )
		{
			return $this;
		}

		if ( is_infinite( $val ) )
		{
			return $val;
		}

		if ( $this->_value == 0 )
		{
			return $this;
		}

		$denominator = DecimalValue::FromFloat( $val );
		$result = DecimalValue::FromFloat( $this->_value )->Mod( $denominator );
		$result = $result->ToFloat( null );
		return new FloatProxy( $result );
	}

	/**
	 * GetTypeCode
	 * @return TypeCode
	 */
	public function GetTypeCode()
	{
	    return TypeCode::Float;
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
	 * @return ByteProxy
	 */
	public function ToByte( $provider )
	{
	    return Convert::ToByte( $this->_value, $provider );
	}

	/**
	 * ToChar
	 * @param IFormatProvider $provider
	 * @return string
	 */
	public function ToChar( $provider )
	{
	    return Convert::ToChar( $this->_value, $provider );
	}

	/**
	 * ToDateTime
	 * @param IFormatProvider $provider
	 * @return DateTimeValue
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
	 * @return ShortProxy
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
	 * @return Long
	 */
	public function ToInt64( $provider )
	{
	    return Convert::ToInt64( $this->_value, $provider );
	}

	/**
	 * ToSByte
	 * @param IFormatProvider $provider
	 * @return SByteProxy
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
	 * @return UShortProxy
	 */
	public function ToUInt16( $provider )
	{
	    return Convert::ToUInt16( $this->_value, $provider );
	}

	/**
	 * ToUInt32
	 * @param IFormatProvider $provider
	 * @return UIntProxy
	 */
	public function ToUInt32( $provider )
	{
	    return Convert::ToUInt32( $this->_value, $provider );
	}

	/**
	 * ToUInt64
	 * @param IFormatProvider $provider
	 * @return ULongProxy
	 */
	public function ToUInt64( $provider )
	{
	    return Convert::ToUInt64( $this->_value, $provider );
	}

	/**
	 * Unit tests
	 */
	public static function tests()
	{
		$execute = function( $callback )
		{
			try
			{
				return $callback();
			}
			catch( \Exception $ex )
			{
				$class = get_class();
				echo "Error: $class {$ex->getMessage()}\n";
			}

			return null;
		};

		$byte1 = new FloatProxy( 10 );
		$byte2 = new FloatProxy( 20 );
		$result = $execute( function() use( $byte1, $byte2 ) { return $byte1->GetValueCode(); } );
		$result = $execute( function() use( $byte1, $byte2 ) { return $byte1->getValue(); } );
		$result = $execute( function() use( $byte1, $byte2 ) { return $byte1->Eq( $byte1 ); } );
		$result = $execute( function() use( $byte1, $byte2 ) { return $byte1->Eq( $byte2 ); } );
		$result = $execute( function() use( $byte1, $byte2 ) { $out = null; $result = $byte1->TryEq( $byte1, $out ); return $out; } );
		$result = $execute( function() use( $byte1, $byte2 ) { $out = null; $result = $byte1->TryEq( $byte2, $out ); return $out; } );
		$result = $execute( function() use( $byte1, $byte2 ) { return $byte1->Gt( $byte2 ); } );
		$result = $execute( function() use( $byte1, $byte2 ) { return $byte2->Gt( $byte1 ); } );
		$result = $execute( function() use( $byte1, $byte2 ) { $out = null; $result = $byte1->TryGt( $byte2, $out ); return $out; } );
		$result = $execute( function() use( $byte1, $byte2 ) { $out = null; $result = $byte2->TryGt( $byte1, $out ); return $out; } );
		$result = $execute( function() use( $byte1, $byte2 ) { return $byte1->Promote( $byte2 ); } );
		$result = $execute( function() use( $byte1, $byte2 ) { return $byte1->Neg(); } );
		$result = $execute( function() use( $byte1, $byte2 ) { return $byte1->Add( $byte2 ); } );
		$result = $execute( function() use( $byte1, $byte2 ) { return $byte1->Sub( $byte2 ); } );
		$result = $execute( function() use( $byte1, $byte2 ) { return $byte1->Mul( $byte2 ); } );
		$result = $execute( function() use( $byte1, $byte2 ) { return $byte1->Div( $byte2 ); } );
		$result = $execute( function() use( $byte1, $byte2 ) { return $byte1->IDiv( $byte2 ); } );
		$result = $execute( function() use( $byte1, $byte2 ) { return $byte1->Mod( $byte2 ); } );

		$provider = null;
		$result = $byte1->ToBoolean( $provider );
		$result = $byte1->ToByte( $provider );
		$result = $byte1->ToChar( $provider );
		$result = $byte1->ToDateTime( $provider );
		$result = $byte1->ToDecimal( $provider );
		$result = $byte1->ToDouble( $provider );
		$result = $byte1->ToInt16( $provider );
		$result = $byte1->ToInt( $provider );
		$result = $byte1->ToInt32( $provider );
		$result = $byte1->ToInt64( $provider );
		$result = $byte1->ToSByte( $provider );
		$result = $byte1->ToSingle( $provider );
		$result = $byte1->ToString( $provider );
		$type = SequenceType::WithTypeCode( XmlTypeCode::String )->ItemType;
		$result = $byte1->ToType( $type, $provider );
		$result = $byte1->ToUInt16( $provider );
		$result = $byte1->ToUInt32( $provider );

	}
}



?>
