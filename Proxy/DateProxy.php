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

use lyquidity\XPath2\Value\DateValue;
use lyquidity\XPath2\SequenceType;
use lyquidity\xml\MS\XmlTypeCardinality;
use lyquidity\XPath2\Value\DayTimeDurationValue;
use lyquidity\XPath2\Value\YearMonthDurationValue;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\xml\exceptions\NotImplementedException;
use lyquidity\XPath2\XPath2Exception;

/**
 * Proxy (internal)
 */
class DateProxy extends ValueProxy implements IXmlSchemaType
{


	/**
	 * Value
	 * @var DateValue $_value
	 */
	private  $_value;

	/**
	 * Constructor
	 * @param DateValue $value
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
	    return DateProxyFactory::Code;
	}

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$Date;
	}

	/**
	 * Get the value
	 * @return DateValue 
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
	    if ( $val->GetValueCode() != DateProxyFactory::Code )
	        throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
				array(
					"op:eq",
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->_value), XmlTypeCardinality::One),
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val->getValue()), XmlTypeCardinality::One)
				)
			);
	    return  $this->_value->Equals( $val->getValue() );
	}

	/**
	 * Gt
	 * @param ValueProxy $val
	 * @return bool
	 */
	protected function Gt( $val )
	{
	    if ( $val->GetValueCode() != DateProxyFactory::Code )
	        throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
				array(
					"op:gt",
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->_value ), XmlTypeCardinality::One ),
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val->getValue() ), XmlTypeCardinality::One )
				)
			);
	    return  $this->_value->CompareTo( $val->getValue() ) > 0;
	}

	/**
	 * Promote
	 * @param ValueProxy $value
	 * @return ValueProxy
	 */
	protected function Promote($value)
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
	 * @param ValueProxy $value
	 * @return ValueProxy
	 */
	protected function Add( $value )
	{
	    switch ( $value->GetValueCode() )
	    {
	        case YearMonthDurationProxyFactory::Code:
	        	/**
	        	 * @var YearMonthDurationProxy $value
	        	 */
	        	// Clone the DateValue contained in the proxy or the original is changed
	        	$clone = new DateValue( $this->_value->S, clone $this->_value->Value, ! $this->_value->IsLocal );
	        	return new DateProxy( DateValue::AddYearMonthDuration( $clone, $value->getValue() ) );

	        case DayTimeDurationProxyFactory::Code:
	        	/**
	        	 * @var DayTimeDurationProxy $value
	        	 */
	        	// Clone the DateValue contained in the proxy or the original is changed
	        	$clone = new DateValue( $this->_value->S, clone $this->_value->Value, ! $this->_value->IsLocal );
	        	return new DateProxy( DateValue::AddDayTimeDuration( $clone, $value->getValue() ) );

	        default:
	            throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
					array(
						"op:add",
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->_value ), XmlTypeCardinality::One ),
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value->getValue() ), XmlTypeCardinality::One )
					)
				);
	    }
	}

	/**
	 * Sub
	 * @param ValueProxy $value
	 * @return ValueProxy
	 */
	protected function Sub( $value )
	{
	    switch ( $value->GetValueCode() )
	    {
	        case DateProxyFactory::Code:
	            return new DayTimeDurationProxy( DateValue::Sub( $this->_value, /* DateValue */ $value->getValue() ) );

	        case YearMonthDurationProxyFactory::Code:
	        	/**
	        	 * @var YearMonthDurationProxy $value
	        	 */
	        	// Clone the DateValue contained in the proxy or the original is changed
	        	$clone = new DateValue( $this->_value->S, clone $this->_value->Value );
	        	$value->getValue()->Value->invert = ! $value->getValue()->Value->invert;
	        	return new DateProxy( DateValue::AddYearMonthDuration( $clone, $value->getValue() ) );

	        case DayTimeDurationProxyFactory::Code:
	        	/**
	        	 * @var DayTimeDurationProxy $value
	        	 */
	        	// Clone the DateValue contained in the proxy or the original is changed
	        	$clone = new DateValue( $this->_value->S, clone $this->_value->Value );
	        	$value->getValue()->Value->invert = ! $value->getValue()->Value->invert;
	        	return new DateProxy( DateValue::AddDayTimeDuration( $clone, $value->getValue() ) );

	        default:
	            throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
					array(
						"op:sub",
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->_value ), XmlTypeCardinality::One ),
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value->getValue() ), XmlTypeCardinality::One )
					)
				);
	    }
	}

	/**
	 * Mul
	 * @param ValueProxy $value
	 * @return ValueProxy
	 */
	protected function Mul( $value )
	{
	    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
			array(
				"op:mul",
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->_value ), XmlTypeCardinality::One ),
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value->getValue() ), XmlTypeCardinality::One )
			)
		);
	}

	/**
	 * Div
	 * @param ValueProxy $value
	 * @return ValueProxy
	 */
	protected function Div( $value )
	{
	    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
			array(
				"op:div",
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->_value ), XmlTypeCardinality::One ),
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value->getValue() ), XmlTypeCardinality::One )
			)
		);
	}

	/**
	 * IDiv
	 * @param ValueProxy $value
	 * @return Integer
	 */
	protected function IDiv($value)
	{
	    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
			array(
				"op:idiv",
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->_value), XmlTypeCardinality::One ),
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value->getValue() ), XmlTypeCardinality::One )
			)
		);
	}

	/**
	 * Mod
	 * @param ValueProxy $value
	 * @return ValueProxy
	 */
	protected function Mod( $value )
	{
	    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
			array(
				"op:mod",
				SequenceType::WithTypeCodeAndCardinality(SequenceType::GetXmlTypeCodeFromObject(  $this->_value), XmlTypeCardinality::One ),
				SequenceType::WithTypeCodeAndCardinality(SequenceType::GetXmlTypeCodeFromObject( $value->getValue() ), XmlTypeCardinality::One )
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

		$int1 = new DateProxy( DateValue::Parse( "2009-10-11" ) );
		$int2 = new DateProxy( DateValue::Parse( "2009-10-12" ) );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->GetValueCode(); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->getValue(); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Eq( $int1 ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Eq( $int2 ); } );
		$result = $execute( function() use( $int1, $int2 ) { $out = null; $result = $int1->TryEq( $int1, $out ); return $out; } );
		$result = $execute( function() use( $int1, $int2 ) { $out = null; $result = $int1->TryEq( $int2, $out ); return $out; } );
		$result = $execute( function() use( $int1, $int2 ) { return $int2->Gt( $int1 ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Gt( $int2 ); } );
		$result = $execute( function() use( $int1, $int2 ) { $out = null; $result = $int1->TryGt( $int2, $out ); return $out; } );
		$result = $execute( function() use( $int1, $int2 ) { $out = null; $result = $int2->TryGt( $int1, $out ); return $out; } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Promote( $int2 ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Neg(); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Add( new YearMonthDurationProxy( YearMonthDurationValue::Parse("P1Y" ) ) ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Add( new DayTimeDurationProxy( DayTimeDurationValue::Parse("P1D" ) ) ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Sub( new YearMonthDurationProxy( YearMonthDurationValue::Parse("P1Y" ) ) ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Sub( new DayTimeDurationProxy( DayTimeDurationValue::Parse("P1D" ) ) ); } );
		$result = $execute( function() use( $int1, $int2 ) { return $int1->Sub( new DateProxy( DateValue::Parse("2008-01-01" ) ) ); } );
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
