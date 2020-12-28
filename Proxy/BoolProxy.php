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
use lyquidity\xml\MS\XmlSchemaType;
use lyquidity\xml\MS\XmlTypeCardinality;
use \lyquidity\XPath2\lyquidity\Convert;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\CoreFuncs;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\XPath2Exception;

/**
 * Bool (internal final)
 */
class BoolProxy extends ValueProxy implements IXmlSchemaType
{
	/**
	 * Value
	 * @var bool $_value
	 */
	private $_value;

	/**
	 * Constructor
	 * @param bool $value
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
		return BoolProxyFactory::Code;
	}

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$Boolean;
	}

	/**
	 * Gets the value
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
		if ( ! $val instanceof BoolProxy ) return false;
		return  ( $this->_value instanceof CoreFuncs::$False && $val->getValue() instanceof CoreFuncs::$False ) ||
				( $this->_value instanceof CoreFuncs::$True && $val->getValue() instanceof CoreFuncs::$True ) ||
				( is_bool( $this->_value ) && is_bool( $val->getValue() ) && $this->_value == $val->getValue() );
		// return $this->_value == (bool)$val->_value;
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
	 * TryGt
	 * @param ValueProxy $val
	 * @param bool $res
	 * @return bool
	 */
	protected function TryGt( $val, &$res )
	{
		$res = $this->_value instanceof CoreFuncs::$True && $val->getValue() instanceof CoreFuncs::$False;
		return true;
	}

	/**
	 * Promote
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Promote( $val )
	{
		return new BoolProxy( Convert::ToBoolean( $val->getValue(), null ) );
	}

	/**
	 * Neg
	 * @return ValueProxy
	 */
	protected function Neg()
	{
		throw XPath2Exception::withErrorCodeAndParams("", Resources::UnaryOperatorNotDefined,
			array(
				"fn:unary-minus",
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->getValue() ), XmlTypeCardinality::One )
			)
		);
	}

	/**
	 * Add
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Add($val)
	{
		throw XPath2Exception::withErrorCodeAndParams("", Resources::BinaryOperatorNotDefined,
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
	protected function Sub($val)
	{
		throw XPath2Exception::withErrorCodeAndParams("", Resources::BinaryOperatorNotDefined,
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
	protected function Mul($val)
	{
		throw XPath2Exception::withErrorCodeAndParams("", Resources::BinaryOperatorNotDefined,
			array(
				"op:mul",
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->getValue() ), XmlTypeCardinality::One ),
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val->getValue() ), XmlTypeCardinality::One )
			)
		);
	}

	/**
	 * Div
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected function Div($val)
	{
		throw XPath2Exception::withErrorCodeAndParams("", Resources::BinaryOperatorNotDefined,
			array(
				"op:div",
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->getValue()  ), XmlTypeCardinality::One ),
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val->getValue()  ), XmlTypeCardinality::One )
			)
		);
	}

	/**
	 * IDiv
	 * @param ValueProxy $val
	 * @return Integer
	 */
	protected function IDiv($val)
    {
        throw XPath2Exception::withErrorCodeAndParams("", Resources::BinaryOperatorNotDefined,
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
	protected function Mod($val)
    {
		throw XPath2Exception::withErrorCodeAndParams("", Resources::BinaryOperatorNotDefined,
			array(
				"op:mod",
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $this->getValue() ), XmlTypeCardinality::One ),
				SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val->getValue() ), XmlTypeCardinality::One )
			)
		);
    }

    /**
     * Return a stringified version of the object
     * @return string
     */
    public function __toString()
    {
    	return $this->_value ? "true" : "false";
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

    	$boolTrue = new BoolProxy( true );
    	$boolFalse = new BoolProxy( false );
    	$result = $execute( function() use( $boolTrue, $boolFalse ) { return $boolTrue->GetValueCode(); } );
    	$result = $execute( function() use( $boolTrue, $boolFalse ) { return $boolTrue->getValue(); } );
    	$result = $execute( function() use( $boolTrue, $boolFalse ) { return $boolTrue->Eq( $boolFalse ); } );
    	$result = $execute( function() use( $boolTrue, $boolFalse ) { return $boolTrue->Gt( $boolFalse ); } );
    	$value = false;
    	$result = $execute( function() use( $boolTrue, $boolFalse ) { return  $boolTrue->TryGt( true, $boolFalse ); } );
    	$result = $execute( function() use( $boolTrue, $boolFalse ) { return $boolTrue->Promote( $boolFalse ); } );
    	$result = $execute( function() use( $boolTrue, $boolFalse ) { return $boolTrue->Neg(); } );
    	$result = $execute( function() use( $boolTrue, $boolFalse ) { return $boolTrue->Add( $boolFalse ); } );
    	$result = $execute( function() use( $boolTrue, $boolFalse ) { return $boolTrue->Sub( $boolFalse ); } );
    	$result = $execute( function() use( $boolTrue, $boolFalse ) { return $boolTrue->Mul( $boolFalse ); } );
    	$result = $execute( function() use( $boolTrue, $boolFalse ) { return $boolTrue->Div( $boolFalse ); } );
    	$result = $execute( function() use( $boolTrue, $boolFalse ) { return $boolTrue->IDiv( $boolFalse ); } );
    	$result = $execute( function() use( $boolTrue, $boolFalse ) { return $boolTrue->Mod( $boolFalse ); } );
    }

}



?>
