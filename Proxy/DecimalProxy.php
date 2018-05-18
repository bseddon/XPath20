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

// use \lyquidity\XPath2\lyquidity\Convert;
use lyquidity\XPath2\Value\Integer;
use lyquidity\XPath2\SequenceType;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\Value\DecimalValue;
use \lyquidity\XPath2\lyquidity\Convert;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;

/**
 * DecimalProxy (internal final)
 */
class DecimalProxy extends ValueProxy implements IXmlSchemaType
{
	public static $CLASSNAME = "lyquidity\XPath2\Proxy\DecimalProxy";

	/**
	 * @var DecimalValue $_value
	 */
	private $_value;

	/**
	 * Constructor
	 * @param DecimalValue $value
	 */
	public function __construct( $value )
	{
		if ( $value instanceof DecimalValue )
			$this->_value = $value;
		else if ( is_string( $value ) && is_numeric( $value ) )
			$this->_value = new DecimalValue( $value );
		else if ( is_numeric( $value ) )
			$this->_value = new DecimalValue( $value );
		else
			throw new \InvalidArgumentException( "The value passed to the DecimalProxy MUST be DecimalValue, a number or a numeric string" );
	}

	/**
	 * GetValueCode
	 * @return int
	 */
	public function GetValueCode()
	{
	    return DecimalProxyFactory::Code;
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
	 * @var DecimalValue $Value
	 */
	public function getValue()
	{
		return $this->_value;
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
	    return $this->_value->Equals( $val->_value );
	}

	/**
	 * Gt
	 * @param ValueProxy $val
	 * @return bool
	 */
	protected function Gt( $val )
	{
	    return $this->_value->Gt( $val->getValue() );
	}

	/**
	 * Promote
	 * @param ValueProxy $val
	 * @return DecimalProxy
	 */
	protected function Promote( $val )
	{
	    return $val instanceof DecimalProxy ? $val : new DecimalProxy( Convert::ToDecimal( $val, null ) );
	}

	/**
	 * Neg
	 * @return DecimalProxy
	 */
	protected function Neg()
	{
	    return new DecimalProxy( $this->_value->Neg() );
	}

	/**
	 * Add
	 * @param ValueProxy $val
	 * @return DecimalProxy
	 */
	protected function Add( $val )
	{
	    return new DecimalProxy( $this->_value->Add( $val->getValue() ) );
	}

	/**
	 * Sub
	 * @param ValueProxy $val
	 * @return DecimalProxy
	 */
	protected function Sub( $val )
	{
	    return new DecimalProxy( $this->_value->Sub( $val->getValue() ) );
	}

	/**
	 * Mul
	 * @param ValueProxy $val
	 * @return DecimalProxy
	 */
	protected function Mul( $val )
	{
	    return new DecimalProxy( $this->_value->Mul( $val->getValue() ) );
	}

	/**
	 * Div
	 * @param ValueProxy $val
	 * @return DecimalProxy
	 */
	protected function Div( $val )
	{
	    return new DecimalProxy( $this->_value->Div( $val->getValue() ) );
	}

	/**
	 * IDiv
	 * @param ValueProxy $val
	 * @return Integer
	 */
	protected function IDiv( $val )
	{
	    return Integer::FromValue( $this->Div( $val )->getValue()->getIntegerPart() );
	}

	/**
	 * Mod
	 * @param ValueProxy $val
	 * @return DecimalProxy
	 */
	protected function Mod( $val )
	{
	    return new DecimalProxy( $this->_value->Mod( $val->getValue() ) );
	}

	/**
	 * GetTypeCode
	 * @return TypeCode
	 */
	public function GetTypeCode()
	{
	    return TypeCode::Decimal;
	}

	/**
	 * ToBoolean
	 * @param IFormatProvider $provider
	 * @return bool
	 */
	public function ToBoolean( $provider )
	{
	    return $this->_value->ToBoolean( $provider );
	}

	/**
	 * ToByte
	 * @param IFormatProvider $provider
	 * @return byte
	 */
	public function ToByte( $provider )
	{
	    return $this->_value->ToByte( $provider );
	}

	/**
	 * ToChar
	 * @param IFormatProvider $provider
	 * @return char
	 */
	public function ToChar( $provider )
	{
	    return $this->_value->ToChar( $provider );
	}

	/**
	 * ToDateTime
	 * @param IFormatProvider $provider
	 * @return DateTime
	 */
	public function ToDateTime( $provider )
	{
	    return $this->_value->ToDateTime( $provider );
	}

	/**
	 * ToDecimal
	 * @param IFormatProvider $provider
	 * @return string
	 */
	public function ToDecimal( $provider )
	{
	    return $this->_value->getValue();
	}

	/**
	 * ToDouble
	 * @param IFormatProvider $provider
	 * @return double
	 */
	public function ToDouble( $provider )
	{
	    return $this->_value->ToDouble( $provider );
	}

	/**
	 * ToInt16
	 * @param IFormatProvider $provider
	 * @return short
	 */
	public function ToInt16( $provider )
	{
	    return $this->_value->Toint( $provider );
	}

	/**
	 * ToInt
	 * @param IFormatProvider $provider
	 * @return int
	 */
	public function ToInt( $provider )
	{
		return $this->_value->ToInt( $provider );
	}

	/**
	 * ToInt32
	 * @param IFormatProvider $provider
	 * @return int
	 */
	public function ToInt32( $provider )
	{
	    return $this->_value->ToInt32( $provider );
	}

	/**
	 * ToInt64
	 * @param IFormatProvider $provider
	 * @return long
	 */
	public function ToInt64( $provider )
	{
	    return $this->_value->ToInt64( $provider );
	}

	/**
	 * ToSByte
	 * @param IFormatProvider $provider
	 * @return sbyte
	 */
	public function ToSByte( $provider )
	{
	    return $this->_value->ToSByte( $provider );
	}

	/**
	 * ToSingle
	 * @param IFormatProvider $provider
	 * @return float
	 */
	public function ToSingle( $provider )
	{
	    return $this->_value->ToSingle( $provider );
	}

	/**
	 * ToString
	 * @param IFormatProvider $provider
	 * @return string
	 */
	public function ToString( $provider = null )
	{
	    return $this->_value->ToString( $provider );
	}

	/**
	 * ToType
	 * @param Type $conversionType
	 * @param IFormatProvider $provider
	 * @return object
	 */
	public function ToType( $conversionType, $provider )
	{
	    return $this->_value->ToType( $conversionType, $provider );
	}

	/**
	 * ToUInt16
	 * @param IFormatProvider $provider
	 * @return ushort
	 */
	public function ToUInt16( $provider )
	{
	    return $this->_value->ToUInt16( $provider );
	}

	/**
	 * ToUInt32
	 * @param IFormatProvider $provider
	 * @return uint
	 */
	public function ToUInt32( $provider )
	{
	    return $this->_value->ToUInt32( $provider );
	}

	/**
	 * ToUInt64
	 * @param IFormatProvider $provider
	 * @return ulong
	 */
	public function ToUInt64( $provider )
	{
	    return $this->_value->ToUInt64( $provider );
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

		$decimal1 = new DecimalProxy( "12" );
		$decimal1 = new DecimalProxy( new DecimalValue( "12" ) );
		// $decimal1 = new DecimalProxy( "ABC" );
		$decimal1 = new DecimalProxy( 10.00002 );
		$decimal2 = new DecimalProxy( 20.546 );
		$result = $execute( function() use( $decimal1, $decimal2 ) { return $decimal1->GetValueCode(); } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { return $decimal1->getValue(); } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { return $decimal1->Eq( $decimal1 ); } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { return $decimal1->Eq( $decimal2 ); } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { $out = null; $result = $decimal1->TryEq( $decimal1, $out ); return $out; } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { $out = null; $result = $decimal1->TryEq( $decimal2, $out ); return $out; } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { return $decimal1->Gt( $decimal2 ); } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { return $decimal2->Gt( $decimal1 ); } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { $out = null; $result = $decimal1->TryGt( $decimal2, $out ); return $out; } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { $out = null; $result = $decimal2->TryGt( $decimal1, $out ); return $out; } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { return $decimal1->Promote( $decimal2->getValue() ); } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { return $decimal1->Neg(); } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { return $decimal1->Add( $decimal2 ); } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { return $decimal1->Sub( $decimal2 ); } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { return $decimal1->Mul( $decimal2 ); } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { return $decimal1->Div( $decimal2 ); } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { return $decimal1->IDiv( $decimal2 ); } );
		$result = $execute( function() use( $decimal1, $decimal2 ) { return $decimal1->Mod( $decimal2 ); } );

		$provider = null;
		$result = $decimal1->ToBoolean( $provider );
		// $result = $decimal1->ToByte( $provider );
		// $result = $decimal1->ToChar( $provider );
		// $result = $decimal1->ToDateTime( $provider );
		$result = $decimal1->ToDecimal( $provider );
		$result = $decimal1->ToDouble( $provider );
		$result = $decimal1->ToInt16( $provider );
		$result = $decimal1->ToInt( $provider );
		$result = $decimal1->ToInt32( $provider );
		$result = $decimal1->ToInt64( $provider );
		$result = $decimal1->ToSByte( $provider );
		$result = $decimal1->ToSingle( $provider );
		$result = $decimal1->ToString( $provider );
		$type = SequenceType::WithTypeCode( XmlTypeCode::String )->ItemType;
		$result = $decimal1->ToType( $type, $provider );
		$result = $decimal1->ToUInt16( $provider );
		$result = $decimal1->ToUInt32( $provider );

	}
}



?>
