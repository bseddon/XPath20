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

use lyquidity\XPath2\SequenceType;
use \lyquidity\XPath2\lyquidity\Convert;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\MS\XmlTypeCardinality;
use lyquidity\xml\TypeCode;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\XPath2Exception;

/**
 * StringProxy (internal)
 */
class StringProxy extends ValueProxy implements IXmlSchemaType
{
	public static $CLASSNAME = "lyquidity\XPath2\Proxy\StringProxy";

	/**
	 * @var String $_value
	 */
	private $_value;

	/**
	 * Constructor
	 * @param String $value
	 */
	public function __construct($value)
	{
		$this->_value = $value;
	}

	/**
	 * GetValueCode
	 * @return int
	 */
	public function GetValueCode()
	{
	    return StringProxyFactory::Code;
	}

	/**
	 * @var object $Value
	 */
	public function getValue()
	{
		return  $this->_value;
	}

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$String;
	}

	/**
	 * Eq
	 * @param ValueProxy $val
	 * @return bool
	 */
	protected function Eq( $val )
	{
	    return strcmp( $this->_value, $val->getValue() ) == 0;
	}

	/**
	 * Gt
	 * @param ValueProxy $val
	 * @return bool
	 */
	protected function Gt( $val )
	{
	    return strcmp( $this->_value, $val->getValue() ) > 0;
	}

	/**
	 * Promote
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Promote( $val )
	{
	    return new StringProxy( Convert::ToString( $val->getValue() ) );
	}

	/**
	 * Neg
	 * @return ValueProxy
	 */
	protected function Neg()
	{
	    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::UnaryOperatorNotDefined,
			array(
				"fn:unary-minus",
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->_value ), XmlTypeCardinality::One )
			)
		);
	}

	/**
	 * Add
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Add( $val )
	{
	    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
			array(
				"op:add",
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->_value ), XmlTypeCardinality::One ),
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val->getValue() ), XmlTypeCardinality::One )
			)
		);
	}

	/**
	 * Sub
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Sub( $val )
	{
	    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
			array(
				"op:sub",
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->_value ), XmlTypeCardinality::One ),
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val->getValue() ), XmlTypeCardinality::One )
			)
		);
	}

	/**
	 * Mul
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Mul( $val )
	{
	    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
			array(
				"op:mul",
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->_value ), XmlTypeCardinality::One ),
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val->getValue() ), XmlTypeCardinality::One )
			)
		);
	}

	/**
	 * Div
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Div( $val )
	{
	    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
			array(
				"op:div",
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->_value ), XmlTypeCardinality::One ),
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val->getValue() ), XmlTypeCardinality::One )
			)
		);
	}

	/**
	 * IDiv
	 * @param ValueProxy $val
	 * @return Integer
	 */
	protected function IDiv( $val )
	{
	    throw XPath2Exception::withErrorCodeAndParams("XPTY0004", Resources::BinaryOperatorNotDefined,
			array(
				"op:idiv",
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->_value ), XmlTypeCardinality::One ),
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val->getValue() ), XmlTypeCardinality::One )
			)
		);
	}

	/**
	 * Mod
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Mod( $val )
	{
	    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
			array(
				"op:mod",
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->_value ), XmlTypeCardinality::One ),
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val->getValue() ), XmlTypeCardinality::One )
			)
		);
	}

	/**
	 * GetTypeCode
	 * @return TypeCode
	 */
	public function GetTypeCode()
	{
	    return TypeCode::String;
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
	    return  Convert::ToDecimal( $this->_value, $provider );
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

		$string1 = new StringProxy( "XXX" );
		$string2 = new StringProxy( "YYY" );
		$promote = new DoubleProxy( 10 );

		$result = $execute( function() use( $string1, $string2 ) { return $string1->GetValueCode(); } );
		$result = $execute( function() use( $string1, $string2 ) { return $string1->getValue(); } );
		$result = $execute( function() use( $string1, $string2 ) { return $string1->Eq( $string1 ); } );
		$result = $execute( function() use( $string1, $string2 ) { return $string1->Eq( $string2 ); } );
		$result = $execute( function() use( $string1, $string2 ) { $out = null; $result = $string1->TryEq( $string1, $out ); return $out; } );
		$result = $execute( function() use( $string1, $string2 ) { $out = null; $result = $string1->TryEq( $string2, $out ); return $out; } );
		$result = $execute( function() use( $string1, $string2 ) { return $string1->Gt( $string2 ); } );
		$result = $execute( function() use( $string1, $string2 ) { return $string2->Gt( $string1 ); } );
		$result = $execute( function() use( $string1, $string2 ) { $out = null; $result = $string1->TryGt( $string2, $out ); return $out; } );
		$result = $execute( function() use( $string1, $string2 ) { $out = null; $result = $string2->TryGt( $string1, $out ); return $out; } );
		$result = $execute( function() use( $string1, $promote ) { return $string1->Promote( $promote ); } );
		$result = $execute( function() use( $string1, $string2 ) { return $string1->Neg(); } );
		$result = $execute( function() use( $string1, $string2 ) { return $string1->Add( $string2 ); } );
		$result = $execute( function() use( $string1, $string2 ) { return $string1->Sub( $string2 ); } );
		$result = $execute( function() use( $string1, $string2 ) { return $string1->Mul( $string2 ); } );
		$result = $execute( function() use( $string1, $string2 ) { return $string1->Div( $string2 ); } );
		$result = $execute( function() use( $string1, $string2 ) { return $string1->IDiv( $string2 ); } );
		$result = $execute( function() use( $string1, $string2 ) { return $string1->Mod( $string2 ); } );

		$provider = null;
		$result = $string1->ToBoolean( $provider );
		$result = $string1->ToByte( $provider );
		$result = $string1->ToChar( $provider );
		$result = $string1->ToDateTime( $provider );
		$result = $string1->ToDecimal( $provider );
		$result = $string1->ToDouble( $provider );
		$result = $string1->ToInt16( $provider );
		$result = $string1->ToInt( $provider );
		$result = $string1->ToInt32( $provider );
		$result = $string1->ToInt64( $provider );
		$result = $string1->ToSByte( $provider );
		$result = $string1->ToSingle( $provider );
		$result = $string1->ToString( $provider );
		$type = SequenceType::WithTypeCode( XmlTypeCode::String )->ItemType;
		$result = $string1->ToType( $type, $provider );
		$result = $string1->ToUInt16( $provider );
		$result = $string1->ToUInt32( $provider );

	}
}



?>
