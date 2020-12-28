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

use \lyquidity\xml\interfaces\IConvertable;
use lyquidity\xml\interfaces\IFormatProvider;
use \lyquidity\XPath2\XPath2Exception;
use \lyquidity\xml\MS\XmlTypeCardinality;
use \lyquidity\XPath2\lyquidity\Convert;
use \lyquidity\xml\TypeCode;
use \lyquidity\XPath2\lyquidity\Type;
use \lyquidity\XPath2\SequenceType;
use \lyquidity\XPath2\lyquidity\Types;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\Value\DateTimeValue;
use lyquidity\XPath2\Value\DateValue;
use lyquidity\XPath2\Value\TimeValue;
use lyquidity\XPath2\Value\DayTimeDurationValue;
use lyquidity\XPath2\Value\YearMonthDurationValue;
use lyquidity\xml\MS\XmlSchemaType;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\XPath2Item;
use lyquidity\xml\exceptions\InvalidCastException;
use lyquidity\xml\exceptions\InvalidOperationException;
use lyquidity\xml\exceptions\KeyNotFoundException;
use lyquidity\XPath2\Value\Integer;

/**
 * ValueProxy (internal abstract)
 */
abstract class ValueProxy implements IConvertable, IXmlSchemaType
{
	/**
	 * CLASSNAME
	 * @var string
	 */
	public static $CLASSNAME = "lyquidity\XPath2\Proxy\ValueProxy";

	/**
	 * Max types
	 * @var int $TYPES_MAX = 24
	 */
	const TYPES_MAX = 24;

	/**
	 * value;
	 * @var object $value;
	 */
	protected $value;

	/**
	 * Get the wrapped value
	 * @return object
	 */
	public function getValue()
	{
		return  $this->value;
	}

	/**
	 * GetValueCode
	 * @return \lyquidity\xml\TypeCode|int
	 */
	public abstract function GetValueCode();

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$AnySimpleType;
	}

	/**
	 * Eq
	 * @param ValueProxy $val
	 * @return bool
	 */
	protected abstract function Eq( $val );

	/**
	 * TryEq
	 * @param ValueProxy $val
	 * @param bool $res
	 * @return bool
	 */
	protected function TryEq( $val, &$res )
	{
		$res = false;
		if ( ! $val instanceof ValueProxy ) return false;
		if ( $val->GetValueCode() != $this->GetValueCode() ) return false;

		$res = $this->Eq( $val );
		return true; // This SHOULD return true to indicate the comparison was successful
	}

	/**
	 * Gt
	 * @param ValueProxy $val
	 * @return bool
	 */
	protected abstract function Gt( $val );

	/**
	 * TryGt
	 * @param ValueProxy $val
	 * @param bool $res
	 * @return bool
	 */
	protected function TryGt( $val, &$res )
	{
		$res = false;
		if ( ! $val instanceof ValueProxy ) return false;
		if ( $val->GetValueCode() != $this->GetValueCode() ) return false;

		$res = $this->Gt( $val );
		return true; // This SHOULD return true to indicate the comparison was successful
	}

	/**
	 * Promote
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected abstract function Promote( $val );

	/**
	 * Neg
	 * @return ValueProxy
	 */
	protected abstract function Neg();

	/**
	 * Add
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected abstract function Add( $val );

	/**
	 * Sub
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected abstract function Sub( $val );

	/**
	 * Mul
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected abstract function Mul( $val );

	/**
	 * Div
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected abstract function Div( $val );

	/**
	 * IDiv
	 * @param ValueProxy $val
	 * @return Integer
	 */
	protected abstract function IDiv( $val );

	/**
	 * Mod
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	protected abstract function Mod( $val );

	/**
	 * Equals
	 * @param object $obj
	 * @return bool
	 */
	public function Equals( $obj )
	{
		if ( $obj instanceof ValueProxy )
		{
			$res = null;
			if ( self::Eq( $this, $obj, $res))
				return $res;
		}
		return false;
	}

	// /**
	//  * GetHashCode
	//  * @return int
	//  */
	// public function GetHashCode()
	// {
	// 	return Value->GetHashCode();
	// }

	/**
	 * IsNaN
	 * @return bool
	 */
	public function IsNaN()
	{
		return false;
	}

	/**
	 * getIsNumeric
	 * @return bool
	 */
	public function getIsNumeric()
	{
		return false;
	}

	/**
	 * Static constructoe
	 */
	public static function __static()
	{
		ValueProxy::$valueFactory = array();
		ValueProxy::$dynamicValueType = Type::FromNameAndTypeCode( ValueProxy::$CLASSNAME, TypeCode::Object );
	}

	/**
	 * dynamicValueType
	 * @var Type $dynamicValueType = typeof(ValueProxy)
	 */
	private static $dynamicValueType;

	/**
	 * valueFactory
	 * @var Dictionary<Type, ValueProxyFactory> $valueFactory
	 */
	protected static $valueFactory;

	/**
	 * @var int[,] $conv_t = null
	 */
	private static $conv_t = array();

	/**
	 * Create
	 * @param object $value
	 * @return ValueProxy
	 */
	public static function Create( $value )
	{
		if ( $value instanceof ValueProxy )
			return $value;
		try
		{
			if ( $value instanceof XPath2Item )
			{
				$valueType = $value->getValueType();
				$typeName = $valueType->getTypeName();
				$value = $value->getTypedValue();
			}
			else if ( $value instanceof IXmlSchemaType )
			{
				/**
				 * @var \lyquidity\XPath2\Value\AnyUriValue $value Not really - just need a proper class that has $CLASSNAME to hide from the type checker
				 */
				$typeName = $value::$CLASSNAME;
			}
			else
			{
				$typeName = Type::FromValue( $value )->getTypeName();
			}

			if ( ! isset( ValueProxy::$valueFactory[ $typeName ] ) )
			{
				throw new KeyNotFoundException();
			}

			return ValueProxy::$valueFactory[ $typeName ]->Create( $value );
		}
		catch ( KeyNotFoundException $ex )
		{
			$className = get_class( $value );
			if ( is_null( $value ) )
				throw XPath2Exception::withErrorCodeAndParam( "XPTY0004", "The type {0} is not registred in ValueProxy", $className);
			else
				throw new InvalidCastException( "The type {$className} is not registred in ValueProxy" );
		}
	}

	/**
	 * IsProxyType
	 * @param Type $type
	 * @return bool
	 */
	public static function IsProxyType( $type )
	{
		return is_subclass_of( $type->getFullName(), ValueProxy::$dynamicValueType );
	}

	/**
	 * AddFactory
	 * @param IEnumerable<ValueProxyFactory> $factories
	 * @return void
	 */
	public static function AddFactory( &$factories)
	{
		foreach ( $factories as /** @var ValueProxyFactory $curr */ $curr )
		{
			if ( count( ValueProxy::$valueFactory ) == ValueProxy::TYPES_MAX )
				throw new InvalidOperationException();

			ValueProxy::$valueFactory[ $curr->GetValueType()->getTypeName() ] = $curr;
		}
		ValueProxy::Bind();
	}

	/**
	 * Bind
	 * @return void
	 */
	private static function Bind()
	{
		ValueProxy::$conv_t = array_fill( 0, ValueProxy::TYPES_MAX, 0 );
		for ( $k = 0; $k < ValueProxy::TYPES_MAX; $k++ )
		{
			ValueProxy::$conv_t[ $k ] = array_fill( 0, ValueProxy::TYPES_MAX, -2 );
		}

		$list = array_values( ValueProxy::$valueFactory );

		foreach ( $list as /** @var ValueProxyFactory $curr */ $curr )
		{
			foreach ( $list as /** @var ValueProxyFactory $curr2 */ $curr2 )
			{
				if ( $curr == $curr2 )
					ValueProxy::$conv_t[ $curr->GetValueCode() ][ $curr2->GetValueCode() ] = 0;
				else
					ValueProxy::$conv_t[ $curr->GetValueCode() ][ $curr2->GetValueCode() ] = $curr->Compare( $curr2 );
			}
		}
	}

	/**
	 * IsNumericValue
	 * @param Type $type
	 * @return bool
	 */
	public static function IsNumericValue( $type )
	{
		if ( isset( ValueProxy::$valueFactory[ $type->getFullName() ] ) )
		{
			/**
			 * @var ValueProxyFactory $factory
			 */
			$factory = ValueProxy::$valueFactory[ $type->getFullName() ];
			return $factory->getIsNumeric();
		}

		return false;
	}

	/**
	 * GetType
	 * @param Type $type1
	 * @param Type $type2
	 * @return string
	 */
	public static function GetType( $type1, $type2 )
	{
		if ( is_subclass_of( $type1->getFullName(), ValueProxy::$dynamicValueType->getFullName() ) ||
			 is_subclass_of( $type2->getFullName(), ValueProxy::$dynamicValueType->getFullName() ) )
			return ValueProxy::$dynamicValueType;

		/**
		 * @var ValueProxyFactory $factory1
		 */
		$factory1 = isset( ValueProxy::$valueFactory[ $type1->getFullName() ] )
			? ValueProxy::$valueFactory[ $type1->getFullName() ]
			: null;

		/**
		 * @var ValueProxyFactory $factory2
		 */
		$factory2 = isset( ValueProxy::$valueFactory[ $type2->getFullName() ] )
			? ValueProxy::$valueFactory[ $type2->getFullName() ]
			: null;

		if ( is_null( $factory1 ) || is_null( $factory1 ) ) return Types::$ObjectType;

		switch ( ValueProxy::$conv_t[ $factory1->GetValueCode() ][ $factory2->GetValueCode() ] )
		{
			case -1:
				return $factory2->GetResultType();

			case 0:
			case 1:
				return $factory1->GetResultType();

			default:
				return Types::$ObjectType;
		}
	}

	/**
	 * Equals
	 * @param ValueProxy $val1
	 * @param ValueProxy $val2
	 * @return bool
	 */
	public static function EqualsValue( $val1, $val2 )
	{
		switch ( ValueProxy::$conv_t[ $val1->GetValueCode() ][ $val2->GetValueCode() ] )
		{
			case -1:
				return $val2->Promote( $val1 )->Eq( $val2 );

			case 0:
				return $val1->Eq( $val2 );

			case 1:
				return $val1->Eq( $val1->Promote( $val2 ) );

			default:

				if ( ValueProxy::$conv_t[ $val2->GetValueCode() ][ $val1->GetValueCode() ] == 1 )
				{
					return $val2->Promote( $val1 )->Eq( $val2 );
				}

				throw XPath2Exception::withErrorCodeAndParams( "", Resources::BinaryOperatorNotDefined,
					array(
						"op:eq",
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val1 ), XmlTypeCardinality::One ),
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val2 ), XmlTypeCardinality::One )
					)
				);
		}
	}

	/**
	 * Eq
	 * @param ValueProxy $val1
	 * @param ValueProxy $val2
	 * @param bool $res (out)
	 * @return bool
	 */
	public static function EqValues( $val1, $val2, &$res )
	{
		$res = false;

		if ( ! $val1 instanceof ValueProxy )
		{
			/**
			 * @var ValueProxyFactory $f
			 */
			$class = Type::FromValue( $val1 )->getFullName();
			$f = isset( ValueProxy::$valueFactory[ $class ] )
				? ValueProxy::$valueFactory[ $class ]
				: null;

			if ( is_null( $f ) ) return false;
			$val1 = $f->Create( $val1 instanceof XPath2Item ? $val1->getTypedValue() : $val1 );
		}

		if ( ! $val2 instanceof ValueProxy )
		{

			/**
			 * @var ValueProxyFactory $f
			 */
			$class = Type::FromValue( $val2 )->getFullName();
			$f = isset( ValueProxy::$valueFactory[ $class ] )
				? ValueProxy::$valueFactory[ $class ]
				: null;

			if ( is_null( $f ) ) return false;
			$val2 = $f->Create( $val2 instanceof XPath2Item ? $val2->getTypedValue() : $val2 );
		}

		switch ( ValueProxy::$conv_t[ $val1->GetValueCode() ][ $val2->GetValueCode() ] )
		{
			case -1:
				return $val2->Promote( $val1 )->TryEq( $val2, $res );

			case 0:
				return $val1->TryEq( $val2, $res );

			case 1:
				return $val1->TryEq( $val1->Promote( $val2 ), $res );

			default:
				if ( ValueProxy::$conv_t[ $val2->GetValueCode() ][ $val1->GetValueCode() ] == 1 )
					return $val2->Promote( $val1 )->TryEq( $val2, $res );
				return false;
		}
	}

	/**
	 * Performs the operation $val1 > $val2
	 * @param ValueProxy $val1
	 * @param ValueProxy $val2
	 * @return boolean
	 */
	public static function OperatorGreaterThan( ValueProxy $val1, ValueProxy $val2 )
	{
		switch ( ValueProxy::$conv_t[ $val1->GetValueCode() ][ $val2->GetValueCode() ] )
		{
			case -1:
				return  $val2->Promote( $val1)->Gt( $val2);

			case 0:
				return  $val1->Gt( $val2);

			case 1:
				return  $val1->Gt( $val1->Promote( $val2));

			default:
				{
					if ( ValueProxy::$conv_t[ $val2->GetValueCode() ][ $val1->GetValueCode() ] == 1 )
						return  $val2->Promote( $val1)->Gt( $val2 );

					throw XPath2Exception::withErrorCodeAndParams("", Resources::BinaryOperatorNotDefined,
						array(
							"op:gt",
							SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val1 ), XmlTypeCardinality::One ),
							SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val2 ), XmlTypeCardinality::One )
						)
					);
				}
		}
	}

	/**
	 * Gt
	 * @param ValueProxy $val1
	 * @param ValueProxy $val2
	 * @param bool $res (out)
	 * @return bool
	 */
	public static function GtValues( $val1, $val2, &$res )
	{
		$res = false;

		if ( ! $val1 instanceof ValueProxy )
		{
			/**
			 * @var ValueProxyFactory $f
			 */
			$f = isset( ValueProxy::$valueFactory[ Type::FromValue( $val1 )->getTypeName() ] )
				? ValueProxy::$valueFactory[ Type::FromValue( $val1 )->getTypeName() ]
				: null;

			if ( is_null( $f ) ) return false;
			$val1 = $f->Create( $val1 instanceof XPath2Item ? $val1->getTypedValue() : $val1 );
		}

		if ( ! $val2 instanceof ValueProxy )
		{
			/**
			 * @var ValueProxyFactory $f
			 */
			$f = isset( ValueProxy::$valueFactory[ Type::FromValue( $val2 )->getTypeName() ] )
				? ValueProxy::$valueFactory[ Type::FromValue( $val2 )->getTypeName() ]
				: null;

			if ( is_null( $f ) ) return false;
			$val2 = $f->Create( $val2 instanceof XPath2Item ? $val2->getTypedValue() : $val2 );
		}

		switch ( ValueProxy::$conv_t[$val1->GetValueCode() ][ $val2->GetValueCode()] )
		{
			case -1:
				return $val2->Promote( $val1 )->TryGt( $val2, $res );

			case 0:
				return $val1->TryGt( $val2, $res );

			case 1:
				return $val1->TryGt( $val1->Promote( $val2 ), $res );

			default:

				if ( ValueProxy::$conv_t[ $val2->GetValueCode() ][ $val1->GetValueCode() ] == 1 )
					return $val2->Promote( $val1 )->TryGt( $val2, $res );
				return false;
		}
	}

	/*
	public static function Operator <(ValueProxy  $val1, ValueProxy  $val2)
	{
		return ( $val2 >  $val1);
	}

	public static bool operator >=(ValueProxy  $val1, ValueProxy  $val2)
	{
		return (Equals( $val1,  $val2) ||  $val1 > $val2);
	}

	public static bool operator <=(ValueProxy  $val1, ValueProxy  $val2)
	{
		return (Equals( $val1,  $val2) ||  $val1 <  $val2);
	}
	*/

	/**
	 * Equivalent of the '+' operator
	 * @param ValueProxy $val1
	 * @param ValueProxy $val2
	 * @return \lyquidity\XPath2\Proxy\ValueProxy
	 */
	public static function OperatorPlus( $val1, $val2 )
	{
		if ( ! $val1 instanceof ValueProxy )
		{
			/**
			 * @var ValueProxyFactory $f
			 */
			$f = isset( ValueProxy::$valueFactory[ Type::FromValue( $val1 )->getTypeName() ] )
				? ValueProxy::$valueFactory[ Type::FromValue( $val1 )->getTypeName() ]
				: null;

			if ( is_null( $f ) ) return false;
			$val1 = $f->Create( $val1 instanceof XPath2Item ? $val1->getTypedValue() : $val1 );
		}

		if ( ! $val2 instanceof ValueProxy )
		{
			/**
			 * @var ValueProxyFactory $f
			 */
			$f = isset( ValueProxy::$valueFactory[ Type::FromValue( $val2 )->getTypeName() ] )
				? ValueProxy::$valueFactory[ Type::FromValue( $val2 )->getTypeName() ]
				: null;

			if ( is_null( $f ) ) return false;
			$val2 = $f->Create( $val2 );
		}

		switch ( ValueProxy::$conv_t[ $val1->GetValueCode() ][ $val2->GetValueCode() ] )
		{
			case -1:
				return  $val2->Promote( $val1 )->Add( $val2 );

			case 0:
				return  $val1->Add( $val2 );

			case 1:
				return  $val1->Add( $val1->Promote( $val2 ) );

			default:
				{
					if ( ValueProxy::$conv_t[ $val2->GetValueCode() ][ $val1->GetValueCode() ] == 1 )
						return $val2->Promote( $val1)->Add( $val2 );

					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
						array(
							"op:add",
							SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val1 ), XmlTypeCardinality::One ),
							SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val2 ), XmlTypeCardinality::One )
						)
					);
				}
		}
	}

	/**
	 * Negate the value
	 * @param ValueProxy $val
	 * @return ValueProxy
	 */
	public static function OperatorMinus( $val )
	{
		return $val->Neg();
	}

	/**
	 * Performs the function of performing the operation $val1 - $val2
	 * @param ValueProxy $val1
	 * @param ValueProxy $val2
	 * @return ValueProxy
	 */
	public static function OperatorSubtract( $val1, $val2 )
	{
		switch ( ValueProxy::$conv_t[ $val1->GetValueCode() ][ $val2->GetValueCode() ] )
		{
			case -1:
				return $val2->Promote( $val1 )->Sub( $val2 );

			case 0:
				return $val1->Sub( $val2 );

			case 1:
				return $val1->Sub( $val1->Promote( $val2 ) );

			default:

				if ( ValueProxy::$conv_t[ $val2->GetValueCode() ][ $val1->GetValueCode() ] == 1 )
					return $val2->Promote( $val1)->Sub( $val2 );
				throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
					array(
						"op:sub",
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val1 ), XmlTypeCardinality::One ),
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val2 ), XmlTypeCardinality::One )
					)
				);
		}
	}

	/**
	 * Performs the equivalent of $val1 * $val2
	 * @param ValueProxy $val1
	 * @param ValueProxy $val2
	 * @return \lyquidity\XPath2\Proxy\ValueProxy
	 */
	public static function OperatorMultiply( $val1, $val2 )
	{
		switch ( ValueProxy::$conv_t[ $val1->GetValueCode() ][ $val2->GetValueCode() ] )
		{
			case -1:
				return  $val2->Promote( $val1 )->Mul( $val2 );

			case 0:
				return  $val1->Mul( $val2 );

			case 1:
				return  $val1->Mul( $val1->Promote( $val2 ) );

			default:

				if ( ValueProxy::$conv_t[ $val2->GetValueCode() ][ $val1->GetValueCode() ] == 1 )
					return $val2->Promote( $val1)->Mul( $val2 );

				throw XPath2Exception::withErrorCodeAndParams("XPTY0004", Resources::BinaryOperatorNotDefined,
					array(
						"op:mul",
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val1 ), XmlTypeCardinality::One ),
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val2 ), XmlTypeCardinality::One )
					)
				);
		}
	}

	/**
	 * Performs the equivalent of $val1 / $val2
	 * @param ValueProxy $val1
	 * @param ValueProxy $val2
	 * @return ValueProxy
	 */
	public static function OperatorDivide( $val1, $val2 )
	{
		switch ( ValueProxy::$conv_t[ $val1->GetValueCode() ][ $val2->GetValueCode()] )
		{
			case -1:
				return  $val2->Promote( $val1)->Div( $val2 );

			case 0:
				return  $val1->Div( $val2 );

			case 1:
				return  $val1->Div( $val1->Promote( $val2 ) );

			default:
				{
					if ( ValueProxy::$conv_t[ $val2->GetValueCode() ][ $val1->GetValueCode() ] == 1)
						return  $val2->Promote( $val1 )->Div( $val2 );
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
						array(
							"op:div",
							SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject(  $val1 ), XmlTypeCardinality::One ),
							SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject(  $val2 ), XmlTypeCardinality::One )
						)
					);
				}
		}
	}

	/**
	 * Performs the operation $val1 % $val2
	 * @param ValueProxy $val1
	 * @param ValueProxy $val2
	 * @return \lyquidity\XPath2\Proxy\ValueProxy
	 */
	public static function OperatorMod( ValueProxy  $val1, ValueProxy  $val2 )
	{
		switch ( ValueProxy::$conv_t[ $val1->GetValueCode() ][ $val2->GetValueCode() ] )
		{
			case -1:
				return  $val2->Promote( $val1)->Mod( $val2 );

			case 0:
				return  $val1->Mod( $val2 );

			case 1:
				return  $val1->Mod( $val1->Promote( $val2 ) );

			default:

				if ( ValueProxy::$conv_t[ $val2->GetValueCode() ][ $val1->GetValueCode() ] == 1 )
					return  $val2->Promote( $val1)->Mod( $val2);

				throw XPath2Exception::withErrorCodeAndParams("XPTY0004", Resources::BinaryOperatorNotDefined,
					array(
						"op:mod",
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val1 ), XmlTypeCardinality::One ),
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val2 ), XmlTypeCardinality::One )
					)
				);
		}
	}

	/**
	 * op_IntegerDivide
	 * @param ValueProxy $val1
	 * @param ValueProxy $val2
	 * @return Integer
	 */
	public static function op_IntegerDivide( $val1, $val2 )
	{
		switch ( ValueProxy::$conv_t[ $val1->GetValueCode() ][ $val2->GetValueCode() ] )
		{
			case -1:
				return $val2->Promote( $val1 )->IDiv( $val2 );

			case 0:
				return $val1->IDiv( $val2 );

			case 1:
				return $val1->IDiv( $val1->Promote( $val2 ) );

			default:
				if ( ValueProxy::$conv_t[ $val2->GetValueCode() ][ $val1->GetValueCode() ] == 1 )
					return $val2->Promote( $val1 )->IDiv( $val2 );

				throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
					array(
						"op:idiv",
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val1 ), XmlTypeCardinality::One ),
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val2 ), XmlTypeCardinality::One )
					)
				);
		}
	}

	/**
	 * Max
	 * @param ValueProxy $val1
	 * @param ValueProxy $val2
	 * @return ValueProxy
	 */
	public static function Max( $val1, $val2 )
	{
		switch ( ValueProxy::$conv_t[ $val1->GetValueCode() ][ $val2->GetValueCode() ] )
		{
			case -1:
				$val1 = $val2->Promote( $val1 );
				break;

			case 0:
				break;

			case 1:
				$val2 = $val1->Promote( $val2 );
				break;

			default:
				{
					if ( ValueProxy::$conv_t[ $val2->GetValueCode() ][ $val1->GetValueCode() ] == 1 )
						$val1 = $val2->Promote( $val1 );
					else
						throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
							array(
								"op:gt",
								SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val1 ), XmlTypeCardinality::One ),
								SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val2 ), XmlTypeCardinality::One )
							)
						);
				}
				break;
		}
		if ($val2->IsNaN() || $val2->Gt($val1))
			return $val2;

		return $val1;
	}

	/**
	 * Min
	 * @param ValueProxy $val1
	 * @param ValueProxy $val2
	 * @return ValueProxy
	 */
	public static function Min( $val1, $val2 )
	{
		switch ( ValueProxy::$conv_t[ $val1->GetValueCode() ][ $val2->GetValueCode() ] )
		{
			case -1:
				$val1 = $val2->Promote($val1);
				break;

			case 0:
				break;

			case 1:
				$val2 = $val1->Promote($val2);
				break;

			default:

				if ( ValueProxy::$conv_t[ $val2->GetValueCode() ][ $val1->GetValueCode() ] == 1 )
				{
					$val1 = $val2->Promote( $val1 );
				}
				else
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
						array(
							"op:gt",
							SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val1 ), XmlTypeCardinality::One ),
							SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $val2 ), XmlTypeCardinality::One )
						)
					);
				}
				break;
		}
		if ( $val2->IsNaN() || $val1->Gt( $val2 ) )
			return $val2;
		return $val1;
	}

	/**
	 * Unwrap
	 * @param object $value
	 * @return object
	 */
	public static function Unwrap( $value )
	{
		return $value instanceof ValueProxy
			? $value->getValue()
			: $value;
	}

	/*
	public static implicit operator ValueProxy(sbyte value)
	{
		return new Proxy::SByteProxy(value);
	}


	public static implicit operator ValueProxy(byte value)
	{
		return new Proxy::ByteProxy(value);
	}


	public static implicit operator ValueProxy(short value)
	{
		return new Proxy::Short(value);
	}


	public static implicit operator ValueProxy(ushort value)
	{
		return new Proxy::UShort(value);
	}


	public static implicit operator ValueProxy(int value)
	{
		return new Proxy::Int(value);
	}


	public static implicit operator ValueProxy(uint value)
	{
		return new Proxy::UInt(value);
	}


	public static implicit operator ValueProxy(long value)
	{
		return new Proxy::Long(value);
	}


	public static implicit operator ValueProxy(ulong value)
	{
		return new Proxy::ULong(value);
	}


	public static implicit operator ValueProxy(decimal value)
	{
		return new Proxy::DecimalProxy(value);
	}


	public static implicit operator ValueProxy(Integer value)
	{
		return new Proxy::IntegerProxy(value);
	}


	public static implicit operator ValueProxy(float value)
	{
		return new Proxy::Float(value);
	}


	public static implicit operator ValueProxy(double value)
	{
		return new Proxy::DoubleProxy(value);
	}


	public static implicit operator ValueProxy(string value)
	{
		return new Proxy::StringProxy(value);
	}


	public static explicit operator SByte(ValueProxy dv)
	{
		return (SByte)dv->Value;
	}


	public static explicit operator Byte(ValueProxy dv)
	{
		return (Byte)dv->Value;
	}


	public static explicit operator Int16(ValueProxy dv)
	{
		return (Int16)dv->Value;
	}


	public static explicit operator UInt16(ValueProxy dv)
	{
		return (UInt16)dv->Value;
	}


	public static explicit operator Int32(ValueProxy dv)
	{
		return (Int32)dv->Value;
	}


	public static explicit operator UInt32(ValueProxy dv)
	{
		return (UInt32)dv->Value;
	}


	public static explicit operator Int64(ValueProxy dv)
	{
		return (Int64)dv->Value;
	}


	public static explicit operator UInt64(ValueProxy dv)
	{
		return (UInt64)dv->Value;
	}


	public static explicit operator Decimal(ValueProxy dv)
	{
		return (Decimal)dv->Value;
	}


	public static explicit operator Integer(ValueProxy dv)
	{
		return (Integer)dv->Value;
	}


	public static explicit operator Single(ValueProxy dv)
	{
		return (Single)dv->Value;
	}


	public static explicit operator Double(ValueProxy dv)
	{
		return (Double)dv->Value;
	}

	*/

	/**
	 * GetTypeCode
	 * @return TypeCode
	 */
	public function GetTypeCode()
	{
		return TypeCode::getTypeCodeFromObject( $this->getValue() );
	}

	/**
	 * ToBoolean
	 * @param IFormatProvider $provider
	 * @return bool
	 */
	public function ToBoolean( $provider )
	{
		return Convert::ToBoolean( $this->getValue(), $provider );
	}

	/**
	 * ToChar
	 * @param IFormatProvider $provider
	 * @return string
	 */
	public function ToChar( $provider )
	{
		return Convert::ToChar( $this->getValue(), $provider );
	}

	/**
	 * ToDateTime
	 * @param IFormatProvider $provider
	 * @return DateTimeProxy
	 */
	public function ToDateTime( $provider )
	{
		return Convert::ToDateTime( $this->getValue(), $provider );
	}

	/**
	 * ToDecimal
	 * @param IFormatProvider $provider
	 * @return DecimalProxy
	 */
	public function ToDecimal( $provider )
	{
		return Convert::ToDecimal( $this->getValue(), $provider );
	}

	/**
	 * ToDouble
	 * @param IFormatProvider $provider
	 * @return DoubleProxy
	 */
	public function ToDouble( $provider )
	{
		return Convert::ToDouble( $this->getValue(), $provider );
	}

	/**
	 * ToInt
	 * @param IFormatProvider $provider
	 * @return Integer
	 */
	public function ToInt( $provider )
	{
		return Convert::ToInt( $this->getValue(), $provider );
	}

	/**
	 * ToString
	 * @param IFormatProvider $provider
	 * @return int
	 */
	public function ToString( $provider = null )
	{
		return Convert::ToString( $this->getValue(), $provider );
	}

	/**
	 * Return a stringified version of the object
	 * @return string
	 */
	public function __toString()
	{
		return get_called_class() . " " . $this->ToString();
	}

	/**
	 * Unit tests
	 */
	public static function tests()
	{
		$proxy = ValueProxy::Create( 1 );
		$proxy = ValueProxy::Create( "xxx" );
		$proxy = ValueProxy::Create( 1.1 );
		$proxy = ValueProxy::Create( false );
		$proxy = ValueProxy::Create( DateTimeValue::Parse( "2017-05-30T17:10:11" ) );
		$proxy = ValueProxy::Create( DateValue::Parse( "2017-05-30" ) );
		$proxy = ValueProxy::Create( TimeValue::Parse( "17:10:11" ) );
		$proxy = ValueProxy::Create( DayTimeDurationValue::Parse( "P1D" ) );
		$proxy = ValueProxy::Create( YearMonthDurationValue::Parse( "P1Y" ) );
	}

}

ValueProxy::__static();

?>
