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

use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\Value\DurationValue;
use lyquidity\xml\MS\XmlTypeCardinality;
use lyquidity\XPath2\SequenceType;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\xml\exceptions\NotImplementedException;
use lyquidity\XPath2\XPath2Exception;
use lyquidity\xml\MS\XmlSchemaType;

/**
 * Proxy (internal)
 */
class DurationProxy extends ValueProxy implements IXmlSchemaType
{

	/**
	 * Value
	 * @var DurationValue $_value
	 */
	private $_value;

	/**
	 * Constructor
	 * @param DurationValue $value
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
	    return DurationProxyFactory::Code;
	}

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$Duration;
	}

	/**
	 * Get the value
	 * @return object
	 */
	public function getValue()
	{
		return  $this->_value;
	}

	/**
	 * Eq
	 * @param ValueProxy $val
	 * @return bool
	 */
	protected function Eq( $val )
	{
	    return  $this->_value->Equals( $val->getValue() );
	}

	/**
	 * Gt
	 * @param ValueProxy $val
	 * @return bool
	 */
	protected function Gt( $val )
	{
	    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
			array(
				"op:gt",
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->_value), XmlTypeCardinality::One ),
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val->getValue() ), XmlTypeCardinality::One )
			)
		);
	}

	/**
	 * Promote
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Promote( $val )
	{
	    throw new NotImplementedException();
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
	    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
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

		$int1 = new DurationProxy( DurationValue::Parse( "P1D" ) );
		$int2 = new DurationProxy( DurationValue::Parse( "P2D" ) );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->GetValueCode(); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->getValue(); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Eq( $int1 ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Eq( $int2 ); } );
		$result = $execute( function() use( $int1, $int2 ) { $out = null; $result = $int1->TryEq( $int1, $out ); return $out; } );
		$result = $execute( function() use( $int1, $int2 ) { return $int2->Gt( $int1 ); } );
		$result = $execute( function() use( $int1, $int2 ) { $out = null; $result = $int1->TryGt( $int2, $out ); return $out; } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Promote( $int2 ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Neg(); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Add( new DurationProxy( DurationValue::Parse("P1D" ) ) ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Sub( new DurationProxy( DurationValue::Parse("P1D" ) ) ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Mul( new DoubleProxy( 2 ) ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Div( new DoubleProxy( 2 ) ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->IDiv( new IntProxy( 2 ) ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Mod( new IntProxy( 2 ) ); } );

		$provider = null;
		$result = $int1->ToBoolean( $provider );
		// $result = $int1->ToByte( $provider );
		$result = $int1->ToChar( $provider );
		$result = $int1->ToDateTime( $provider );
		$result = $int1->ToDecimal( $provider );
		$result = $int1->ToDouble( $provider );
		// $result = $int1->ToInt16( $provider );
		$result = $int1->ToInt( $provider );
		// $result = $int1->ToInt32( $provider );
		// $result = $int1->ToInt64( $provider );
		// $result = $int1->ToSByte( $provider );
		// $result = $int1->ToSingle( $provider );
		$result = $int1->ToString( $provider );
		// $type = SequenceType::WithTypeCode( XmlTypeCode::String )->ItemType;
		// $result = $int1->ToType( $type, $provider );
		// $result = $int1->ToUInt16( $provider );
		// $result = $int1->ToUInt32( $provider );
	}


}


?>
