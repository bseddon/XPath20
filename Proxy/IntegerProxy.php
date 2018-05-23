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
use lyquidity\XPath2\Value\Integer;
use lyquidity\XPath2\SequenceType;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\Value\DecimalValue;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\xml\interfaces\IFormatProvider;

/**
 * IntegerProxy (internal final)
 */
class IntegerProxy extends ValueProxy implements IXmlSchemaType
{


	/**
	 * Value
	 * @var Integer $_value
	 */
	private $_value;

	/**
	 * Constructor
	 * @param Integer $value
	 */
	public function __construct( $value )
	{
		if ( is_numeric( $value ) ) $value = Integer::FromValue( $value );
		$this->_value = $value;
	}

	/**
	 * GetValueCode
	 * @return int
	 */
	public function GetValueCode()
	{
	    return IntegerProxyFactory::Code;
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
	 * Get the value
	 * @return Integer
	 */
	public function getValue()
	{
		return  $this->_value;
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
	 * Eq
	 * @param ValueProxy $val
	 * @return bool
	 */
	protected function Eq( $val )
	{
		if  (! $val->getIsNumeric() ) return false;
		return  $this->ToInt( null ) == $val->ToInt( null );
	}

	/**
	 * Gt
	 * @param ValueProxy $val
	 * @return bool
	 */
	protected function Gt( $val )
	{
	    return  $this->_value->getValue() > $val->ToInt( null );
	}

	/**
	 * Promote
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Promote( $val )
	{
	    return new IntegerProxy( Convert::ToDouble( $val->getValue() ) );
	}

	/**
	 * Neg
	 * @return ValueProxy
	 */
	protected function Neg()
	{
		$value = $this->_value;
		if ( $value instanceof Integer ) $value = $value->getValue();
	    return new IntegerProxy( Integer::FromValue( -$value ) );
	}

	/**
	 * Add
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Add( $val )
	{
		if  (! $val->getIsNumeric() ) return $this;
		$value2 = $val->ToInt( null );
		$integer = ValueProxy::Unwrap( $this );
	    return new IntegerProxy( $integer->getValue() + $value2 );
	}

	/**
	 * Sub
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Sub( $val )
	{
		if  (! $val->getIsNumeric() ) return $this;
		$value2 = $val->ToInt( null );
		$integer = ValueProxy::Unwrap( $this );
		return new IntegerProxy( $integer->getValue() - $value2 );
	}

	/**
	 * Mul
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Mul( $val )
	{
		if  (! $val->getIsNumeric() ) return $this;

		$value1 = ValueProxy::Unwrap( $this );
		if ( $value1 instanceof Integer ) $value1 = $value1->getValue();
		$value2 = ValueProxy::Unwrap( $val );
		if ( $value2 instanceof Integer ) $value2 = $value2->getValue();
		return new IntegerProxy( Integer::FromValue( $value1 * $value2 ) );
	}

	/**
	 * Div
	 * @param ValueProxy $val
	 * @return DecimalValue
	 */
	protected function Div( $val )
	{
		if  (! $val->getIsNumeric() ) return $this;
		$value1 = $this->ToDecimal( null );
		$value2 = $val->ToDecimal( null );
		return $value1->Div( $value2 );
	}

	/**
	 * IDiv
	 * @param ValueProxy $val
	 * @return Int
	 */
	protected function IDiv( $val )
	{
		if  (! $val->getIsNumeric() ) return $this;
		$value2 = $val->ToDouble( null );
		return Integer::FromValue( $this->ToDecimal( null )->Div( $val->ToDecimal( null ) )->getIntegerPart() );
	}

	/**
	 * Mod
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Mod( $val )
	{
		if  (! $val->getIsNumeric() ) return $this;
		$mod = Integer::FromValue( $this->ToDecimal( null )->Mod( $val->ToDecimal( null ) )->getIntegerPart() );
		return $mod;
		// return new IntegerProxy( $this->ToDouble( null ) % $value2 );
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
		$proxyValue = ValueProxy::Unwrap( $this );
		$value = is_object( $proxyValue ) ? ( $proxyValue instanceof Integer ? $proxyValue->getValue() : $proxyValue->Value ) : $proxyValue ;

	    return Convert::ToDouble( $value, $provider );
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
	 * Calls ToInt32
	 * @param IFormatProvider $provider
	 * @return int
	 */
	public function ToInt( $provider )
	{
		return $this->ToInt32( $provider );
	}

	/**
	 * ToInt32
	 * @param IFormatProvider $provider
	 * @return int
	 */
	public function ToInt32( $provider )
	{
		$proxyValue = ValueProxy::Unwrap( $this );
		$value = is_object( $proxyValue ) ? ( $proxyValue instanceof Integer ? $proxyValue->getValue() : $proxyValue->Value ) : $proxyValue ;

	    return Convert::ToInt32( $value, $provider );
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
	 * ToType
	 * @param Type $conversionType
	 * @param IFormatProvider $provider
	 * @return object
	 */
	public function ToType($conversionType, $provider )
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

		$byte1 = new IntegerProxy( 10 );
		$byte2 = new IntegerProxy( 20 );
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
