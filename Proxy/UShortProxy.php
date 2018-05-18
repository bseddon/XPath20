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

namespace lyquidity\XPath2\Proxy;

use lyquidity\xml\TypeCode;
use \lyquidity\XPath2\lyquidity\Convert;
use lyquidity\XPath2\SequenceType;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\Value\Integer;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;

/**
 * UShort (internal final)
 */
class UShortProxy extends ValueProxy implements IXmlSchemaType
{
	public static $CLASSNAME = "lyquidity\XPath2\Proxy\UShortProxy";

	/**
	 * @var ushort $_value
	 */
	private $_value;

	/**
	 * Constructor
	 * @param ushort $value
	 */
	public function __construct( $value )
	{
		$this->_value = $value;
	}

	/**
	 * GetValueCode
	 * @return int
	 */
	public function GetValueCode()
	{
	    return UShortProxyFactory::Code;
	}

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$UnsignedShort;
	}

	/**
	 * @var object $Value
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * getIsNumeric
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
	    return $this->_value == $val->getValue();
	}

	/**
	 * Gt
	 * @param ValueProxy $val
	 * @return bool
	 */
	protected function Gt( $val )
	{
	    return $this->_value > $val->getValue();
	}

	/**
	 * Promote
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Promote( $val )
	{
	    return new UShortProxy( Convert::ToUInt16($val->getValue() ) );
	}

	/**
	 * Neg
	 * @return ValueProxy
	 */
	protected function Neg()
	{
	    return new IntProxy( -$this->_value);
	}

	/**
	 * Add
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Add( $val )
	{
	    return new IntProxy( $this->_value + $val->getValue() );
	}

	/**
	 * Sub
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Sub( $val )
	{
	    return new IntProxy( $this->_value - $val->getValue() );
	}

	/**
	 * Mul
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Mul( $val )
	{
	    return new IntProxy( $this->_value * $val->getValue() );
	}

	/**
	 * Div
	 * @param ValueProxy $val
	 * @return DecimalValue
	 */
	protected function Div( $val )
	{
	    return new DecimalProxy( Convert::ToDouble( $this->_value) /  Convert::ToDouble( $val->getValue() ) );
	}

	/**
	 * IDiv
	 * @param ValueProxy $val
	 * @return Integer
	 */
	protected function IDiv( $val )
	{
	    return Integer::FromValue( floor( $this->_value /  Convert::ToUInt16( $val->getValue() ) ) );
	}

	/**
	 * Mod
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Mod( $val )
	{
	    return new IntProxy( $this->_value % $val->getValue() );
	}

	/**
	 * GetTypeCode
	 * @return TypeCode
	 */
	public function GetTypeCode()
	{
	    return TypeCode::UInt16;
	}

	/**
	 * ToBoolean
	 * @param IFormatProvider $provider
	 * @return bool
	 */
	public function ToBoolean( $provider )
	{
	    return  Convert::ToBoolean( $this->_value, $provider );
	}

	/**
	 * ToByte
	 * @param IFormatProvider $provider
	 * @return byte
	 */
	public function ToByte( $provider )
	{
	    return  Convert::ToByte( $this->_value, $provider );
	}

	/**
	 * ToChar
	 * @param IFormatProvider $provider
	 * @return char
	 */
	public function ToChar( $provider )
	{
	    return  Convert::ToChar( $this->_value, $provider );
	}

	/**
	 * ToDateTime
	 * @param IFormatProvider $provider
	 * @return DateTime
	 */
	public function ToDateTime( $provider )
	{
	    return  Convert::ToDateTime( $this->_value, $provider );
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
	    return  Convert::ToDouble( $this->_value, $provider );
	}

	/**
	 * ToInt16
	 * @param IFormatProvider $provider
	 * @return short
	 */
	public function ToInt16( $provider )
	{
	    return  Convert::ToInt16( $this->_value, $provider );
	}

	/**
	 * ToInt32
	 * @param IFormatProvider $provider
	 * @return int
	 */
	public function ToInt32( $provider )
	{
	    return  Convert::ToInt32( $this->_value, $provider );
	}

	/**
	 * ToInt64
	 * @param IFormatProvider $provider
	 * @return long
	 */
	public function ToInt64( $provider )
	{
	    return  Convert::ToInt64( $this->_value, $provider );
	}

	/**
	 * ToSByte
	 * @param IFormatProvider $provider
	 * @return sbyte
	 */
	public function ToSByte( $provider )
	{
	    return  Convert::ToSByte( $this->_value, $provider );
	}

	/**
	 * ToSingle
	 * @param IFormatProvider $provider
	 * @return float
	 */
	public function ToSingle( $provider )
	{
	    return  Convert::ToSingle( $this->_value, $provider );
	}

	/**
	 * ToString
	 * @param IFormatProvider $provider
	 * @return string
	 */
	public function ToString( $provider = null )
	{
	    return  Convert::ToString( $this->_value, $provider );
	}

	/**
	 * ToType
	 * @param Type $conversionType
	 * @param IFormatProvider $provider
	 * @return object
	 */
	public function ToType( $conversionType, $provider )
	{
	    return  Convert::ChangeType( $this->_value, $conversionType, $provider );
	}

	/**
	 * ToUInt16
	 * @param IFormatProvider $provider
	 * @return ushort
	 */
	public function ToUInt16( $provider )
	{
	    return  Convert::ToUInt16( $this->_value, $provider );
	}

	/**
	 * ToUInt32
	 * @param IFormatProvider $provider
	 * @return uint
	 */
	public function ToUInt32( $provider )
	{
	    return  Convert::ToUInt32( $this->_value, $provider );
	}

	/**
	 * ToUInt64
	 * @param IFormatProvider $provider
	 * @return ulong
	 */
	public function ToUInt64( $provider )
	{
	    return  Convert::ToUInt64( $this->_value, $provider );
	}

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

		$byte1 = new UShortProxy( 10 );
		$byte2 = new UShortProxy( 20 );
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
