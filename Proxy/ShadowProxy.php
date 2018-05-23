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

use lyquidity\XPath2\SequenceType;
use lyquidity\XPath2\Properties\Resources;
use \lyquidity\XPath2\lyquidity\Convert;
use lyquidity\xml\MS\XmlTypeCardinality;
use lyquidity\XPath2\Value\YearMonthDurationValue;
use lyquidity\XPath2\Value\DayTimeDurationValue;
use lyquidity\xml\exceptions\NotImplementedException;
use lyquidity\XPath2\XPath2Exception;

/**
 * ShadowProxy (internal final)
 */
class ShadowProxy extends ValueProxy
{
	/**
	 * Value
	 * @var object $_value
	 */
	private  $_value;

	/**
	 * ValueCode
	 * @var int $_valueCode
	 */
	private  $_valueCode;

	/**
	 * Is numeric
	 * @var bool $_isNumeric
	 */
	private  $_isNumeric;

	/**
	 * Constructor
	 * @param ValueProxy $proxy
	 */
	public  function __construct( $proxy )
	{
		$this->_value = $proxy->getValue();
		$this->_valueCode = $proxy->GetValueCode();
		$this->_isNumeric = $proxy->getIsNumeric();
	}

	/**
	 * GetValueCode
	 * @return int
	 */
	public function GetValueCode()
	{
		return $this->_valueCode;
	}

	/**
	 * getIsNumeric
	 * @return bool
	 */
	public function getIsNumeric()
	{
		return $this->_isNumeric;
	}

	/**
	 * Get the value
	 * @return object
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * Eq
	 * @param ValueProxy $val
	 * @return bool
	 */
	protected function Eq( $val )
	{
		throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
			array(
				"op:eq",
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->getValue() ), XmlTypeCardinality::One ),
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val->Value ), XmlTypeCardinality::One )
			)
		);
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
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->getValue() ), XmlTypeCardinality::One ),
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
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->getValue()), XmlTypeCardinality::One )
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
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->getValue() ), XmlTypeCardinality::One ),
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
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->getValue() ), XmlTypeCardinality::One ),
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
		switch ( $val->GetValueCode() )
		{
		    case YearMonthDurationProxyFactory::Code:

		        return new YearMonthDurationProxy( YearMonthDurationValue::Multiply( /* YearMonthDurationValue */ $val->getValue(), Convert::ToDouble( $this->_value ) ) );

		    case DayTimeDurationProxyFactory::Code:

		        return new DayTimeDurationProxy( DayTimeDurationValue::Multiply( /* DayTimeDurationValue */ $val->getValue(), Convert::ToDouble( $this->_value ) ) );

		    default:
		        throw XPath2Exception::withErrorCodeAndParams("XPTY0004", Resources::BinaryOperatorNotDefined,
					array(
						"op:mul",
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->getValue() ), XmlTypeCardinality::One ),
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val->getValue() ), XmlTypeCardinality::One )
					)
				);
		}
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
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->getValue() ), XmlTypeCardinality::One ),
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
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->getValue() ), XmlTypeCardinality::One ),
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
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->getValue() ), XmlTypeCardinality::One ),
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val->getValue() ), XmlTypeCardinality::One )
			)
		);
	}

	/**
	 * Unit tests
	 */
	public static function tests()
	{}

}



?>
