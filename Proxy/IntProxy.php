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
use lyquidity\xml\MS\XmlSchemaType;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\SequenceType;
use lyquidity\XPath2\Value\Integer;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\Value\DecimalValue;
use lyquidity\xml\interfaces\IFormatProvider;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\XPath2\XPath2Exception;
use lyquidity\XPath2\Value\DateTimeValue;

/**
 * Int (internal final)
 */
class IntProxy extends ValueProxy implements IXmlSchemaType
{
	/**
	 * Value
	 * @var int $_value
	 */
	private $_value;

	/**
	 * Constructor
	 * @param int $value
	 */
	public  function __construct( $value )
	{
		$this->_value = $value;
	}

	/**
	 * GetValueCode
	 * @return int
	 */
	public function GetValueCode()
	{
	    return IntProxyFactory::Code;
	}

	/**
	 * Get the value
	 * @return object
	 */
	public function getValue()
	{
		return intval( $this->_value );
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
		if ( ! $val instanceof IntProxy ) return false;
		return $this->_value == $val->getValue();
	}

	/**
	 * Gt
	 * @param ValueProxy $val
	 * @return bool
	 */
	protected function Gt( $val )
	{
		if ( ! $val instanceof IntProxy ) return false;
		return $this->_value > $val->getValue();
	}

	/**
	 * Promote
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Promote( $val )
	{
		if ( ! $val instanceof ValueProxy ) throw new \InvalidArgumentException();
		return new IntProxy( Convert::ToInt32( $val->getValue() ) );
	}

	/**
	 * Neg
	 * @return ValueProxy
	 */
	protected function Neg()
	{
	    return new IntProxy( -$this->_value );
	}

	/**
	 * Add
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Add( $val )
	{
		if ( ! $val instanceof ValueProxy ) throw new \InvalidArgumentException();
		return new IntProxy( $this->_value + $val->getValue() );
	}

	/**
	 * Sub
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Sub( $val )
	{
		if ( ! $val instanceof ValueProxy ) throw new \InvalidArgumentException();
		return new IntProxy( $this->_value - $val->getValue() );
	}

	/**
	 * Mul
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Mul( $val )
	{
		if ( ! $val instanceof ValueProxy ) throw new \InvalidArgumentException();
		return new IntProxy( $this->_value * $val->getValue() );
	}

	/**
	 * Div
	 * @param ValueProxy $val
	 * @return DecimalValue
	 */
	protected function Div( $val )
	{
		if ( ! $val instanceof ValueProxy ) throw new \InvalidArgumentException();
		$numerator = Convert::ToDouble($this->_value);
		$denominator = Convert::ToDouble( $val->getValue() );

		if ( $denominator == 0 )
			throw XPath2Exception::withErrorCode( "FOAR0001", Resources::FOAR0001 );

		return new DecimalProxy( $numerator / $denominator );
	}

	/**
	 * IDiv
	 * @param ValueProxy $val
	 * @return \lyquidity\XPath2\Value\Integer
	 */
	protected function IDiv( $val )
	{
		if ( ! $val instanceof IntProxy ) throw new \InvalidArgumentException();

		/**
		 * @var DecimalValue $result
		 */
		$result = $this->Div( $val );
		return Integer::FromValue( $result->getValue()->getIntegerPart() );
	}

	/**
	 * Mod
	 * @param ValueProxy $val
	 * @return IntProxy
	 */
	protected function Mod( $val )
	{
		if ( ! $val instanceof IntProxy ) throw new \InvalidArgumentException();

		$numerator = $this->_value;
		$denominator = $val->getValue();

		if ( $denominator == 0 )
			throw XPath2Exception::withErrorCode( "FOAR0001", Resources::FOAR0001 );

		if ( is_nan( $denominator ) )
			throw XPath2Exception::withErrorCode( "FOAR0002", Resources::FOAR0002 );

		if ( is_nan( $numerator ) || is_infinite( $numerator ) )
			throw XPath2Exception::withErrorCode( "FOAR0002", Resources::FOAR0002 );

		if ( $val->getValue() == 0 ) return NAN;
		return new IntProxy( ( $numerator < 0 && $denominator < 0 ? -1 : 1 ) * fmod( $this->_value, $val->getValue() ) );
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
	 * @return BoolProxy
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
	 * @return StringProxy
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
	public function ToDecimal($provider )
	{
	    return Convert::ToDecimal( $this->_value, $provider );
	}

	/**
	 * ToDouble
	 * @param IFormatProvider $provider
	 * @return DoubleProxy
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
	 * ToInt32
	 * @param IFormatProvider $provider
	 * @return IntegerProxy
	 */
	public function ToInt32( $provider )
	{
	    return Convert::ToInt32( $this->_value, $provider );
	}

	/**
	 * ToInt64
	 * @param IFormatProvider $provider
	 * @return LongProxy
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
	public function ToSByte($provider )
	{
	    return Convert::ToSByte( $this->_value, $provider );
	}

	/**
	 * ToSingle
	 * @param IFormatProvider $provider
	 * @return FloatProxy
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

		$int1 = new IntProxy( 10 );
		$int2 = new intProxy( 20 );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->GetValueCode(); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->getValue(); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Eq( $int1 ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Eq( $int2 ); } );
		$result = $execute( function() use( $int1, $int2 ) { $out = null; $result = $int1->TryEq( $int1, $out ); return $out; } );
		$result = $execute( function() use( $int1, $int2 ) { $out = null; $result = $int1->TryEq( $int2, $out ); return $out; } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Gt( $int2 ); } );
		$result = $execute( function() use( $int1, $int2 ) { $out = null; $result = $int1->TryGt( $int2, $out ); return $out; } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Promote( $int2 ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Neg(); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Add( $int2 ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Sub( $int2 ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Mul( $int2 ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Div( $int2 ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->IDiv( $int2 ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Mod( $int2 ); } );

		$provider = null;
		$result = $int1->ToBoolean( $provider );
		$result = $int1->ToByte( $provider );
		$result = $int1->ToChar( $provider );
		$result = $int1->ToDateTime( $provider );
		$result = $int1->ToDecimal( $provider );
		$result = $int1->ToDouble( $provider );
		$result = $int1->ToInt16( $provider );
		$result = $int1->ToInt( $provider );
		$result = $int1->ToInt32( $provider );
		$result = $int1->ToInt64( $provider );
		$result = $int1->ToSByte( $provider );
		$result = $int1->ToSingle( $provider );
		$result = $int1->ToString( $provider );
		$type = SequenceType::WithTypeCode( XmlTypeCode::String )->ItemType;
		$result = $int1->ToType( $type, $provider );
		$result = $int1->ToUInt16( $provider );
		$result = $int1->ToUInt32( $provider );

	}

}

?>
