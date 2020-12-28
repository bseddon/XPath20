<?php
/**
 * XPath 2.0 for PHP
 *  _					   _	 _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *	     |___/	  |_|					 |___/
 *
 * @author Bill Seddon
 * @version 0.9
 * @Copyright (C ) 2017 Lyquidity Solutions Limited
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option ) any later version.
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

namespace lyquidity\XPath2;

use lyquidity\XPath2\Proxy\ValueProxy;
use lyquidity\XPath2\Value\UntypedAtomic;
use lyquidity\XPath2\Value\AnyUriValue;
use \lyquidity\XPath2\lyquidity\Type;
use lyquidity\XPath2\Value\DurationValue;
use lyquidity\xml\interfaces\IComparable;
use lyquidity\xml\interfaces\IEnumerable;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\xml\MS\XmlTypeCardinality;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\DOM\XmlSchema;
use \lyquidity\XPath2\lyquidity\Types;
use \lyquidity\xml\interfaces\IConvertable;
use lyquidity\xml\TypeCode;
use \lyquidity\XPath2\lyquidity\Convert;
use lyquidity\XPath2\Iterator\EmptyIterator;
use lyquidity\xml\MS\XmlNodeOrder;
use lyquidity\XPath2\Value\Integer;
use lyquidity\XPath2\Value\Long;
use lyquidity\XPath2\Value\QNameValue;
use lyquidity\XPath2\Value\DateTimeValueBase;
use lyquidity\XPath2\Value\TimeValue;
use lyquidity\xml\MS\XmlReservedNs;
use lyquidity\xml\MS\XmlSchemaObject;
use lyquidity\xml\MS\XmlSchemaType;
use lyquidity\XPath2\DOM\DOMSchemaType;
use lyquidity\XPath2\Iterator\NodeIterator;
use lyquidity\xml\MS\XmlSchemaSimpleType;
use lyquidity\XPath2\Iterator\RangeIterator;
use lyquidity\XPath2\Value\DecimalValue;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\Value\LanguageValue;
use lyquidity\XPath2\Value\NMTOKENValue;
use lyquidity\XPath2\Value\NameValue;
use lyquidity\XPath2\Proxy\StringProxy;
use lyquidity\XPath2\Iterator\AxisNodeIterator;
use lyquidity\XPath2\Value\DateValue;
use lyquidity\XPath2\Value\DateTimeValue;
use lyquidity\XPath2\Value\TokenValue;
use lyquidity\XPath2\DOM\DOMXPathNavigator;
use lyquidity\xml\QName;
use lyquidity\xml\exceptions\InvalidCastException;
use lyquidity\xml\exceptions\FormatException;

/**
 * Base class for reference types
 */
class ReferenceType
{
	/**
	 * Function to return a string representation
	 * @return string
	 */
	public function ToString()
	{
		return $this->__toString();
	}
}

/**
 * Class to represent a False value result
 */
class FalseValue extends ReferenceType
{
	/**
	 * Magic function to return a string representation
	 * @return string
	 */
	public function __toString()
	{
		return "false";
	}

	/**
	 * Returns a typed value for this reference type
	 * @return boolean
	 * @desc BMS 2019-09-09 Pointed out by Tim Vandecasteele
	 * @see https://github.com/tim-vandecasteele/xbrl-experiment/commit/a9024c8f1368aa46e7dd2e5c070223c6b99ff08d
	 */
	public function getTypedValue()
	{
		return false;
	}
}

/**
 * Class to represent a True value result
 */
class TrueValue extends ReferenceType
{
	/**
	 * Magic function to return a string representation
	 * @return string
	 */
	public function __toString()
	{
		return "true";
	}

	/**
	 * Returns a typed value for this reference type
	 * @return boolean
	 */
	public function getTypedValue()
	{
		return true;
	}
}

/**
 * CoreFuncs (public static )
 */
class CoreFuncs
{
	/**
	 * True
	 * @var object $True = true
	 */
	public static $True = true;

	/**
	 * False
	 * @var object $False = false
	 */
	public static $False = false;

	/**
	 * When true years before 1532 will cause an error
	 * @var string
	 */
	public static $strictGregorian = false;

	/**
	 * Constructor
	 */
	static function __static()
	{
		CoreFuncs::$False = new FalseValue();
		CoreFuncs::$True = new TrueValue();

		$factories = array(
			new Proxy\ShortProxyFactory(),
			new Proxy\IntProxyFactory(),
			new Proxy\LongProxyFactory(),
			new Proxy\IntegerProxyFactory(),
			new Proxy\DecimalProxyFactory(),
			new Proxy\FloatProxyFactory(),
			new Proxy\DoubleProxyFactory(),
			new Proxy\StringProxyFactory(),
			new Proxy\SByteProxyFactory(),
			new Proxy\ByteProxyFactory(),
			new Proxy\UShortProxyFactory(),
			new Proxy\UIntProxyFactory(),
			new Proxy\LongProxyFactory(),
			new Proxy\ULongProxyFactory(),
			new Proxy\BoolProxyFactory(),
			new Proxy\DateTimeProxyFactory(),
			new Proxy\DateProxyFactory(),
			new Proxy\TimeProxyFactory(),
			new Proxy\DurationProxyFactory(),
			new Proxy\YearMonthDurationProxyFactory(),
			new Proxy\DayTimeDurationProxyFactory(),
			new Proxy\QNameProxyFactory(),
		);

		Proxy\ValueProxy::AddFactory( $factories );
	}

	/**
	 * OperatorEq
	 * @param object $arg1
	 * @param object $arg2
	 * @param bool $raiseExceptionOnMismatch
	 * @return object
	 */
	public static function OperatorEq( $arg1, $arg2, $raiseExceptionOnMismatch = true )
	{
		if ( is_object( $arg1 ) && is_object( $arg2 ) && spl_object_hash( $arg1 ) == spl_object_hash( $arg2 ) )
		{
			return CoreFuncs::$True;
		}

		if ( is_null( $arg1 ) )
		{
			$arg1 = CoreFuncs::$False;
		}

		if ( is_null( $arg2 ) )
		{
			$arg2 = CoreFuncs::$False;
		}

		$res;
		if ( ValueProxy::EqValues( $arg1, $arg2, $res ) )
		{
			return $res ? CoreFuncs::$True : CoreFuncs::$False;
		}

		if ( $arg1 instanceof XPath2Item )
		{
			$arg1 = $arg1->getTypedValue();
		}

		$a = $arg1;

		if ( $arg1 instanceof UntypedAtomic || $arg1 instanceof AnyUriValue || $arg1 instanceof StringProxy || $arg1 instanceof TokenValue )
		{
			$a = $arg1->ToString();
		}

		if ( $arg2 instanceof XPath2Item )
		{
			$arg2 = $arg2->getTypedValue();
		}

		$b = $arg2;

		if ( $arg2 instanceof UntypedAtomic || $arg2 instanceof AnyUriValue || $arg1 instanceof StringProxy || $arg2 instanceof TokenValue )
		{
			$b = $arg2->ToString();
		}

		// BMS 2017-09-03	This table indicates that anything can be converted to or from an untyped atomic
		// 					https://www.w3.org/TR/xpath-functions/#casting-from-primitive-to-primitive
		if ( $arg1 instanceof UntypedAtomic && ( $arg2 instanceof DateValue || $arg2 instanceof DateTimeValue ) )
		{
			$b = $arg2->ToString();
		}

		if ( $arg2 instanceof UntypedAtomic && ( $arg1 instanceof DateValue || $arg1 instanceof DateTimeValue ) )
		{
			$a = $arg1->ToString();
		}

		$typeA = Type::FromValue( $a );
		$typeB = Type::FromValue( $b );

		if ( Type::FromValue( $a ) == Type::FromValue( $b ) || ( $a instanceof DurationValue && $b instanceof DurationValue ) )
		{
			if ( is_string( $a ) )
			{
				if ( strcmp( $a, $b ) == 0 )
				{
					return CoreFuncs::$True;
				}
			}
			else if ( $a->Equals( $b ) )
			{
				return CoreFuncs::$True;
			}
		}
		// BMS 2017-12-27 Added this because at the time of writing using the XPath 2.0 'doc()' function does not
		//				  add a schema related to the imported file so all values are treated as untypedAtomic.
		//				  As a result, simple numeric comparisons fail.
		else if ( $arg1 instanceof UntypedAtomic && $arg2 instanceof UntypedAtomic && is_numeric( $a ) && is_numeric( $b ) )
		{
			return $a == $b;
		}

		// BMS 2019-09-09 Suggested by Tim Vandecasteele
		//				  https://github.com/tim-vandecasteele/xbrl-experiment/commit/b976b6cb01f9e2860adc2379448076119b1bef2e
		else if ( $typeA == Types::$BooleanType || $typeB == Types::$BooleanType )
		{
			// BMS 2020-08-13 Suggested by Tim Vandecasteele as a further correction.  His comment is:
			// 		"The earler version works with combination of multiple tests for example:
			//		(string($ModifiedClosingDateTaxPeriodFrom2017-07-26ForeignCompany) eq 'false') and (string($COVID19AidTemporaryAdjustmentPrepaymentsCalculation) eq 'true'))
			//		But doesn't work with single statements as it doesn't return a CoreFuncs::BooleanValue."

			// return CoreFuncs::BooleanValue($a) == CoreFuncs::BooleanValue($b);
			return CoreFuncs::BooleanValue(CoreFuncs::BooleanValue($a) == CoreFuncs::BooleanValue($b));
		}
		else if ( $raiseExceptionOnMismatch )
		{
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
				array(
					"op:eq",
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $arg1 ), XmlTypeCardinality::One ),
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $arg2 ), XmlTypeCardinality::One )
				)
			);
		}
		return CoreFuncs::$False;
	}

	/**
	 * OperatorGt
	 * @param object $arg1
	 * @param object $arg2
	 * @return object
	 */
	public static function OperatorGt( $arg1, $arg2 )
	{
		if ( is_object( $arg1 ) && is_object( $arg2 ) && spl_object_hash( $arg1 ) == spl_object_hash( $arg2 ) )
			return CoreFuncs::$False;

		if ( is_null( $arg1 ) )
			$arg1 = CoreFuncs::$False;
		if ( is_null( $arg2 ) )
			$arg2 = CoreFuncs::$False;

		$res;
		if ( ValueProxy::GtValues( $arg1, $arg2, $res ) )
			return $res ? CoreFuncs::$True : CoreFuncs::$False;

		if ( ( is_string( $arg1 ) || $arg1 instanceof IComparable || ( $arg1 instanceof XPath2Item && $arg1->getTypeCode() == TypeCode::String ) ) &&
			 ( is_string( $arg2 ) || $arg2 instanceof IComparable || ( $arg2 instanceof XPath2Item && $arg2->getTypeCode() == TypeCode::String ) ) )
		{
			$a = $arg1;
			$b = $arg2;
			if ( $arg1 instanceof UntypedAtomic || $arg1 instanceof AnyUriValue || $arg1 instanceof XPath2Item )
			{
				$a = $arg1->ToString();
			}
			if ( $arg2 instanceof UntypedAtomic || $arg2 instanceof AnyUriValue || $arg1 instanceof XPath2Item )
			{
				$b = $arg2->ToString();
			}

			if ( Type::FromValue( $a ) == Type::FromValue( $b ) )
			{
				if ( is_string( $a ) )
				{
					if ( strcmp( $a, $b ) > 0 ) return CoreFuncs::$True;
				}
				else if ( $a->CompareTo( $b ) > 0 )
				{
					return CoreFuncs::$True;
				}
			}
			else
				throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
					array(
						"op:gt",
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $arg1 ), XmlTypeCardinality::One ),
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $arg2 ), XmlTypeCardinality::One )
					)
				);
		}
		else
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::BinaryOperatorNotDefined,
				array(
					"op:gt",
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $arg1 ), XmlTypeCardinality::One ),
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $arg2 ), XmlTypeCardinality::One )
				)
			);
		return CoreFuncs::$False;
	}

	/**
	 * UnionOrderedIterator
	 * @param XPath2NodeIterator $iter1
	 * @param XPath2NodeIterator $iter2
	 * @return IEnumerable
	 */
	public static function UnionOrderedIterator( $iter1, $iter2 )
	{
		$set = array();

		$process = function( $iter ) use ( &$set )
		{
			/**
			 * @var XPathItem $item
			 */
			foreach ( $iter as $item )
			{
				if ( ! $item instanceof XPathNavigator )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
						array(
							"xs:anyAtomicType",
							"node()* in function op:union,op:intersect and op:except"
						)
					);
				}

				/**
				 * @var \DOMNode $domNode
				 */
				$domNode = $item->getUnderlyingObject();
				$hash = $domNode->getLineNo() . "-" . $domNode->getNodePath();
				if ( ! isset( $set[ $hash ] ) )
					$set[ $hash ] = $item->CloneInstance();
			}
		};

		$process( $iter1 );
		$process( $iter2 );

		if ( count( $set ) > 1 )
		{
			$comparer = new XPathComparer( true );
			uasort( $set, array($comparer, "Compare" ) );
		}

		foreach ( $set as $item )
			yield $item;

		return;

	}

	/**
	 * UnionUnorderedIterator
	 * @param XPath2NodeIterator $iter1
	 * @param XPath2NodeIterator $iter2
	 * @return IEnumerable
	 */
	public static function UnionUnorderedIterator( $iter1, $iter2 )
	{
		$set = array();

		foreach ( $iter1 as $item )
		{
			$hash = spl_object_hash( $item );

			if ( ! isset( $set[ $hash ] ) )
			{
				$set[ $hash ] = $item->CloneInstance();
				yield $item;
			}
		}

		foreach ( $iter2 as $item )
		{
			$hash = spl_object_hash( $item );

			if ( ! isset( $set[ $hash ] ) )
			{
				$set[ $hash ] = $item->CloneInstance();
				yield $item;
			}
		}
	}

	/**
	 * IntersectOrderedExceptIterator
	 * @param bool $except
	 * @param XPath2NodeIterator $iter1
	 * @param XPath2NodeIterator $iter2
	 * @return IEnumerable
	 */
	public static function IntersectOrderedExceptIterator( $except, $iter1, $iter2 )
	{
		// SortedDictionary<XPathItem, XPathItem> set = new SortedDictionary<XPathItem, XPathItem>( new XPathComparer() );
		// HashSet<XPathItem> hs = new HashSet<XPathItem>( new XPathNavigatorEqualityComparer() );

		$set = array();
		$hs = array();

		$process = function( &$iter, &$set )
		{
			/**
			 * @var XPathItem $item
			 */
			foreach ( $iter as $item )
			{
				if ( ! $item instanceof XPathNavigator )
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
						array(
							"xs:anyAtomicType",
							"node()* in function op:union,op:intersect and op:except"
						)
					);

				/**
				 * @var \DOMNode $domNode
				 */
				$domNode = $item->getUnderlyingObject();
				$hash = $domNode->getLineNo() . "-" . $domNode->getNodePath();
				if ( ! isset( $set[ $hash ] ) )
					$set[ $hash ] = $item->CloneInstance();
			}
		};

		$process( $iter1, $set );
		$process( $iter2, $hs );

		foreach ( $set as $hash => $item )
		{
			if ( $except )
			{
				if ( ! isset( $hs[ $hash ] ) )
					yield $item;
			}
			else
			{
				if ( isset( $hs[ $hash ] ) )
					yield $item;
			}
		}

		return;

		/**
		 * @var XPathItem $item
		 */
		foreach ( $iter1 as $item )
		{
			$hash = spl_object_hash( $item );

			if ( ! isset( $set[ $hash ] ) )
				$set[ $hash ] = $item->CloneInstance();
		}

		// foreach (XPathItem item in $iter1 )
		// 	if (!set.ContainsKey(item ))
		//		set.Add(item->CloneInstance(), null );

		foreach ( $iter2 as $item )
		{
			$hash = spl_object_hash( $item );

			if ( ! isset( $hs[ $hash ] ) )
				$set[ $hash ] = $item->CloneInstance();
		}

		// foreach (XPathItem item in $iter2 )
		// 	if (!hs.Contains(item ))
		// 		hs.Add(item->CloneInstance() );

		foreach ( $set as $hash => $item )
		{
			if ( $except )
			{
				if ( ! isset( $hs[ $hash ] ) )
					yield $item;
			}
			else
			{
				if ( isset( $hs[ $hash ] ) )
					yield $item;
			}
		}
	}

	/**
	 * IntersectUnorderedExceptIterator
	 * @param bool $except
	 * @param XPath2NodeIterator $iter1
	 * @param XPath2NodeIterator $iter2
	 * @return IEnumerable<XPathItem>
	 */
	public static function IntersectUnorderedExceptIterator( $except, $iter1, $iter2 )
	{
		// HashSet<XPathItem> hs = new HashSet<XPathItem>( new XPathNavigatorEqualityComparer() );
		$set = array();

		/**
		 * @var XPathItem $item
		 */
		foreach ( $iter1 as $item )
		{
			$hash = spl_object_hash( $item );

			if ( ! isset( $set[ $hash ] ) )
				$set[ $hash ] = $item->CloneInstance();
		}

		// foreach (XPathItem item in $iter1 )
		//	if (!hs.Contains(item ))
		//		hs.Add(item->CloneInstance() );

		if ( $except )
		{
			foreach ( $iter2 as $item )
			{
				$hash = spl_object_hash( $item );

				if ( isset( $set[ $hash ] ) ) unset( $set[ $hash ] );
			}
			// hs.ExceptWith( $iter2 );
		}
		else
		{
			$set2 = array();

			foreach ( $iter2 as $item )
			{
				$hash = spl_object_hash( $item );

				if ( isset( $set[ $hash ] ) ) $set2[ $hash ] = $set[ $hash ];
			}
			$set = $set2;
			unset( $set2 );
			// hs.IntersectWith( $iter2 );
		}

		foreach ( $set as $hash => $item )
			yield $item;
	}

	/**
	 * ConvertIterator
	 * @param XPath2NodeIterator $iter
	 * @param SequenceType $destType
	 * @param XPath2Context $context
	 * @return IEnumerable
	 */
	public static function ConvertIterator( $iter, $destType, $context )
	{
		$num = 0;
		/**
		 * @var SequenceType $itemType
		 */
		$itemType = SequenceType::FromSequenceType( $destType );
		$itemType->Cardinality = XmlTypeCardinality::One;

		/**
		 * @var XPathItem $item
		 */
		foreach ( $iter as $item  )
		{
			if ( $num == 1 )
			{
				if ( $destType->Cardinality == XmlTypeCardinality::ZeroOrOne || $destType->Cardinality == XmlTypeCardinality::One )
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "item()+", $destType ) );
			}

			yield $item->ChangeType( $itemType, $context );
			$num++;
		}

		if (num == 0 )
		{
			if ( $destType->Cardinality == XmlTypeCardinality::One || $destType->Cardinality == XmlTypeCardinality::OneOrMore )
				throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "item()?", $destType ) );
		}
	}

	/**
	 * ValueIterator
	 * @param XPath2NodeIterator $iter
	 * @param SequenceType $destType
	 * @param XPath2Context $context
	 * @return IEnumerable
	 */
	public static function ValueIterator( $iter, $destType, $context )
	{
		$num = 0;

		/**
		 * @var XPathItem $item
		 */
		foreach ( $iter as $item )
		{
			if ( $num == 1 )
			{
				if ( $destType->Cardinality == XmlTypeCardinality::ZeroOrOne || $destType->Cardinality == XmlTypeCardinality::One )
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "item()+", $destType ) );
			}

			if ( $destType->IsNode )
			{
				if ( ! $destType->Match( $item, $context ) )
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
						array(
							SequenceType::WithSchemaTypeWithCardinality( $item->GetSchemaType(), XmlTypeCardinality::OneOrMore ),
							$destType
						)
					);
				yield $item;
			}
			else
				yield XPath2Item::fromValue( XPath2Convert::ValueAs( $item->GetTypedValue(), $destType, $context->NameTable, $context->NamespaceManager ) );
			$num++;
		}
		if ( $num == 0 )
		{
			if ( $destType->Cardinality == XmlTypeCardinality::One || $destType->Cardinality == XmlTypeCardinality::OneOrMore )
				throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "item()?", $destType ) );
		}
	}

	/**
	 * TreatIterator
	 * @param XPath2NodeIterator $iter
	 * @param SequenceType $destType
	 * @param XPath2Context $context
	 * @return IEnumerable<XPathItem>
	 */
	public static function TreatIterator( $iter, $destType, $context )
	{
		$num = 0;
		/**
		 * @var XPathItem $item
		 */
		foreach ( $iter as $item )
		{
			if ( $num == 1 )
			{
				if ( $destType->Cardinality == XmlTypeCardinality::ZeroOrOne || $destType->Cardinality == XmlTypeCardinality::One )
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "item()+", $destType ) );
			}
			if ( $destType->IsNode )
			{
				if ( ! $destType->Match( $item, $context ) )
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
						array(
							SequenceType::WithSchemaTypeWithCardinality( $item->GetSchemaType(), XmlTypeCardinality::OneOrMore ),
							$destType
						)
					);
				yield $item;
			}
			else
			{
				yield XPath2Item::fromValueAndType( XPath2Convert::TreatValueAs( $item->GetTypedValue(), $destType ), $destType->SchemaType );
			}
			$num++;
		}

		if ( $num == 0 )
		{
			if ( $destType->Cardinality == XmlTypeCardinality::One || $destType->Cardinality == XmlTypeCardinality::OneOrMore )
				throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "item()?", $destType ) );
		}
	}

	/**
	 * CodepointIterator
	 * @param string $text
	 * @return IEnumerable<XPathItem>
	 */
	public static function CodepointIterator( $text )
	{
		$k = 0;
		while ( $k < strlen( $text ) )
		{
			$ord0 = ord( $text[ $k++ ] ); if ($ord0>=0   && $ord0<=127) { yield XPath2Item::fromValue( $ord0 ); continue; }
			$ord1 = ord( $text[ $k++ ] ); if ($ord0>=192 && $ord0<=223) { yield XPath2Item::fromValue( ( $ord0 - 192 ) * 64 + ( $ord1 - 128 ) ); continue; }
			$ord2 = ord( $text[ $k++ ] ); if ($ord0>=224 && $ord0<=239) { yield XPath2Item::fromValue( ( $ord0 - 224 ) * 4096 + ( $ord1 - 128 ) * 64 + ($ord2-128) ); continue; }
			$ord3 = ord( $text[ $k++ ] ); if ($ord0>=240 && $ord0<=247) { yield XPath2Item::fromValue( ( $ord0 - 240 ) * 262144 + ( $ord1 - 128 ) * 4096 + ($ord2-128)*64 + ($ord3-128) ); continue; }
			break;
		}
	}

	/**
	 * Clone
	 * @param XPathItem $item
	 * @return XPathItem
	 */
	public static function CloneInstance( $item )
	{
		if ( $item instanceof XPathNavigator )
		{
			/**
			 * @var XPathNavigator $nav
			 */
			$nav = $item;
			return $nav->CloneInstance();
		}
		return $item;
	}

	/**
	 * ChangeType
	 * @param XPathItem $item
	 * @param SequenceType $destType
	 * @param XPath2Context $context
	 * @return XPathItem
	 */
	public static function ChangeType( $item, $destType, $context )
	{
		if ( $destType->IsNode )
		{
			if ( ! $destType->Match( $item, $context ) )
				throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
					array(
						SequenceType::WithTypeCode( $item.GetSchemaType().TypeCode ),
						$destType
					)
				);
			return $item->CloneInstance();
		}
		else
		{
			if ( $destType->SchemaType == $item->GetSchemaType() )
				return CoreFuncs::CloneInstance( $item );
			else if ( $destType->TypeCode == XmlTypeCode::Item &&
					( $destType->Cardinality == XmlTypeCardinality::One || $destType->Cardinality == XmlTypeCardinality::ZeroOrOne ) )
				return $item->CloneInstance();
			else
			{
				if ( ! $destType->SchemaType instanceof XmlSchemaSimpleType )
					throw XPath2Exception::withErrorCodeAndParam( "XPST0051", Resources::XPST0051, "untyped" );

				/**
				 * @var XmlSchemaSimpleType $simpleType
				 */
				$simpleType = $destType->SchemaType;
				if ( $simpleType == XmlSchema::$AnySimpleType )
					throw XPath2Exception::withErrorCodeAndParam( "XPST0051", Resources::XPST0051, "xs:anySimpleType" );

				return XPath2Item::fromValueAndType( XPath2Convert::ChangeType( $item->getSchemaType(), $item->getTypedValue(), $destType, $context->NameTable, $context->NamespaceManager ), $destType->SchemaType );
			}
		}
	}

	/**
	 * NormalizeStringValue
	 * @param string $value
	 * @param bool $attr
	 * @param bool $raiseException
	 * @return string
	 */
	public static function NormalizeStringValue( $value, $attr, $raiseException )
	{
		// The original is hideously complicated where two simple regex patterns will do
		// The pattern matches the whitespace (space and tab). Line ends are normally
		// matched by the character class \s so they are excluded using a negative look ahead
		// If $attr is false the a negative lookup is used to ignore tab
		$pattern = $attr ? "/((?![\r\n])\s)+/" : "/((?![\r\n\t])\s)+/";
		$result = preg_replace( $pattern, " ", $value );
		// Here are the line end handling rules for XML 1.1
		//   the two-character sequence #xD #xA
		//   the two-character sequence #xD #x85
		//   the single character #x85
		//   the single character #x2028
		//   any #xD character that is not immediately followed by #xA or #x85.
		$pattern = "/(\r\n)|(\r\x85)|\x85|\x2038|(?!\n)\r/";
		$result = preg_replace( $pattern, "\n", $result );

		// Convert any entities to their character equivalents
		// Unicode 6.0 spec says: Three letterlike symbols have been given canonical equivalence
		// to regular letters: U+2126 OHM SIGN, U+212A KELVIN SIGN, and U+212B ANGSTROM SIGN.
		// In all three instances, the regular letter should be used. If text is normalized according
		// to Unicode Standard Annex #15, "Unicode Normalization Forms," these three characters will
		// be replaced by their regular equivalents.
		if ( strpos( $result, "&#x212A;" ) !== false )
		{
			$result = str_replace( "&#x212A;", "K", $result );
		}

		return html_entity_decode( $result, ENT_QUOTES | ENT_XML1 );
	}

	/**
	 * BooleanValue
	 * @param object $value
	 * @return object
	 */
	public static function BooleanValue( $value )
	{
		if ( is_null( $value ) || $value instanceof Undefined )
			return CoreFuncs::$False;

		if ( $value instanceof FalseValue || $value instanceof TrueValue )
			return $value;

		if ( CoreFuncs::GetBooleanValue( ValueProxy::Unwrap( $value ) ) )
			return CoreFuncs::$True;

		return CoreFuncs::$False;
	}

	/**
	 * GetBooleanValue
	 * @param object $value
	 * @return bool
	 */
	public static function GetBooleanValue( $value )
	{
		if ( $value instanceof XPath2Item )
		{
			return CoreFuncs::GetBooleanValue( $value->getTypedValue() );
		}

		/**
		 * @var XPathItem $item
		 */
		$item = null;
		$xmlType = null;

		if ( $value instanceof XPath2NodeIterator )
		{
			/**
			 * @var XPath2NodeIterator $iter
			 */
			$iter = $value;

			if ( ! $iter->MoveNext() )
				return false;

			$item = CoreFuncs::CloneInstance( $iter->getCurrent() );
			if ( $item->getIsNode() )
				return true;

			if ( $iter->MoveNext() )
			{
				throw XPath2Exception::withErrorCodeAndParams( "FORG0006", Resources::FORG0006,
					array(
						"fn:boolean()",
						SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::AnyAtomicType, XmlTypeCardinality::OneOrMore )
					)
				);
			}

			$xmlType = $item->GetSchemaType();

		}
		else if ( $value instanceof XPathItem )
		{
			$item = $value;
			$xmlType = $item->GetSchemaType();
		}

		if ( ! is_null( $item ) )
		{
			// switch ( $item->GetSchemaType()->TypeCode )
			switch ( $xmlType->TypeCode )
			{
				case XmlTypeCode::Boolean:
					return $item->getValueAsBoolean();

				case XmlTypeCode::String:
				case XmlTypeCode::AnyUri:
				case XmlTypeCode::UntypedAtomic:
					return $item->getValue() != "";

				case XmlTypeCode::Float:
				case XmlTypeCode::Double:
					return ! is_nan( $item->getValueAsDouble() ) && $item->getValueAsDouble() != 0.0;

				case XmlTypeCode::Decimal:
					return $item->ValueAs( Types::$DecimalType )->ToBoolean( null ) != 0;

				case XmlTypeCode::Integer:
				case XmlTypeCode::NonPositiveInteger:
				case XmlTypeCode::NegativeInteger:
				case XmlTypeCode::Long:
				case XmlTypeCode::Int:
				case XmlTypeCode::Short:
				case XmlTypeCode::Byte:
				case XmlTypeCode::UnsignedInt:
				case XmlTypeCode::UnsignedShort:
				case XmlTypeCode::UnsignedByte:
				case XmlTypeCode::NonNegativeInteger:
				case XmlTypeCode::UnsignedLong:
				case XmlTypeCode::PositiveInteger:
					return $item->getTypedValue() + 0.0;
				// BMS 2018-03-02	Added this for AnyType. This means that any thing
				//					which is an object that is not null will return true.
				case XmlTypeCode::AnyType:
					$value = $item->getTypedValue();
					if ( is_object( $value ) )
					{
						return true;
					}
					else if ( is_null( $value ) )
					{
						return false;
					}
				default:
					throw XPath2Exception::withErrorCodeAndParams( "FORG0006", Resources::FORG0006,
						array(
							"fn:boolean()",
							SequenceType::WithTypeCodeAndCardinality( $item->GetSchemaType()->TypeCode, XmlTypeCardinality::One )
						)
					);
			}
		}
		else
		{
			// if ( $value instanceof IConvertable )
			// 	return $value->ToBoolean( null );
            //
			// $type = Type::FromValue( $value );

			/**
			 * @var TypeCode $typeCode
			 */
			$typeCode = $value instanceof IConvertable
				? $value->GetTypeCode()
				: Type::FromValue( $value )->getTypeCode();

			switch ( $typeCode )
			{
				case TypeCode::Boolean:
					return Convert::ToBoolean( $value, null );

				case TypeCode::String:

					if ( is_numeric( $value ) )
					{
						return $value != NAN && $value != 0;
					}
					if ( is_string( $value ) )
					{
						return strlen( $value ) >  0;
					}

					$result = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
					if ( is_null( $result ) )
					{
						return is_string( $value ) ? ! empty( $value ) : false;
					}

					return $result;

					return is_numeric( $value )
						? ( $value != 0 )
						: (
							filter_var( $value, FILTER_VALIDATE_BOOLEAN )
								? true
								: ( is_string( $value ) ? ! empty( $value ) : false )
						);
					// return Convert::ToString( $value, null ) != "";

				case TypeCode::Single:
				case TypeCode::Double:
					$double = Convert::ToDouble( $value, null );
					return $double != 0.0 && ! is_nan( $double );

				default:

					if ( $value instanceof AnyUriValue || $value instanceof UntypedAtomic )
					{
						return $value->ToString() != "";
					}
					if ( ValueProxy::IsNumericValue( Type::FromValue( $value ) ) )
					{
						return Convert::ToDouble( $value ) != 0;
					}

					throw XPath2Exception::withErrorCodeAndParams( "FORG0006", Resources::FORG0006,
						array(
							"fn:boolean()",
							SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value ), XmlTypeCardinality::One )
						)
					);
			}
		}
	}

	/**
	 * NormalizeSpace
	 * @param object $item
	 * @return string
	 */
	public static function NormalizeSpace( $item )
	{
		if ( $item instanceof Undefined )
			return "";

		// Again the original has horrendous string string searching loops where a simple regex pattern will do
		// This pattern matches two or more whitespace characters but uses a negative lookahead to ignore line end characters
		// $result = preg_replace( "/((?![\r\n])\s){2,}/", " ", $item );
		$result = trim( preg_replace( "/\s{2,}/", " ", $item ) );
		return $result;
	}

	/**
	 * Atomize
	 * @param object $value
	 * @return object
	 */
	public static function Atomize( $value )
	{
		if ( $value instanceof XPath2Item )
		{
			/**
			 * @var XPathItem $item
			 */
			$item = $value;
			return $item;
			// return $item->getTypedValue();
		}

		if ( $value instanceof XPathItem )
		{
			/**
			 * @var XPathItem $item
			 */
			$item = $value;
			// BMS 2018-04-03 At some point this was changed to return getValue()
			//				  I think because an error was returned by one of the tests
			return $item->getTypedValue();

			// BMS 2018-04-03 Added the trim function so node content is trimmed if the XSD type requires
			$typeCode = $item->getSchemaType()->TypeCode;
			switch ( $typeCode )
			{

				case XmlTypeCode::Token:
				case XmlTypeCode::NmToken:
				case XmlTypeCode::Language:
				case XmlTypeCode::NCName:
				case XmlTypeCode::Id;
				case XmlTypeCode::Idref:
				case XmlTypeCode::Entity:
				case XmlTypeCode::Name:
				case XmlTypeCode::NormalizedString:

					return trim( $item->getValue() );

				default:

					return $item->getValue();
			}

			// // BMS 2018-04-01 Added to return the text information without surrounding whitespace (see test 0018 V-02)
			// return trim( $item->getTypedValue() );
		}

		if ( $value instanceof XPath2NodeIterator )
		{
			/**
			 * @var XPath2NodeIterator $iter
			 */
			$iter = $value;
			$iter = $iter->CloneInstance();
			if ( ! $iter->MoveNext() )
			{
				return Undefined::getValue();
			}

			$count = -1;

			do
			{
				// This test is to accommodate the 'text()' filter
				if ( $iter instanceof AxisNodeIterator )
				{
					/**
					 * @var SequenceType $destType
					 */
					$destType = $iter->getDestinationType();
					if ( ! is_null( $destType ) && $destType->TypeCode == XmlTypeCode::Text )
					{
						$res = $iter->getCurrent()->GetValue();
					}
					else
					{
						$res = $iter->getCurrent()->GetTypedValue();
					}
				}
				else
				{
					$res = $iter->getCurrent();
					if ( $res instanceof XPathNavigator )
					{
						$res = $res->GetTypedValue();
					}
				}

				$count++;
			} while ( $iter->MoveNext() );

			if ( $count )
			{
				throw XPath2Exception::withErrorCode( "XPTY0004", Resources::MoreThanOneItem );
			}

			return $res;
		}

		return $value;
	}

	/**
	 * NodeValue
	 * @param object $value
	 * @param bool $raise
	 * @return XPathNavigator
	 */
	public static function NodeValue( $value, $raise = false )
	{
		if ( $value instanceof Undefined )
		{
			if ( $raise )
			{
				throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "empty-sequence()", "item()" ) );
			}
			return null;
		}

		if ( $value instanceof XPath2NodeIterator )
		{
			/**
			 * @var XPath2NodeIterator $iter
			 */
			$iter = $value;
			$iter->Reset();
			$iter = $iter->CloneInstance();
			if ( ! $iter->MoveNext() )
			{
				if ( $raise )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "empty-sequence()", "item()" ) );
				}
				return null;
			}

			/**
			 * @var XPathItem  $res
			 */
			$res = $iter->getCurrent()->CloneInstance();
			if ( $iter-> MoveNext() )
			{
				throw XPath2Exception::withErrorCode( "XPDY0050", Resources::MoreThanOneItem );
			}

			if ( ! $res instanceof XPathNavigator )
			{
				throw XPath2Exception::withErrorCodeAndParam( "XPTY0004", Resources::XPST0004, "node()" );
			}

			return $res;
		}
		else
		{
			if ( ! $value instanceof XPathNavigator )
			{
				throw XPath2Exception::withErrorCodeAndParam( "XPTY0004", Resources::XPST0004, "node()" );
			}

			/**
			 * @var XPathNavigator $nav
			 */
			$nav = $value;
			return $nav->CloneInstance();
		}
	}

	/**
	 * Some
	 * @param object $expr
	 * @return object
	 */
	public static function Some( $expr )
	{
		if ( $expr instanceof XPath2NodeIterator )
		{
			/**
			 * @var XPath2NodeIterator $iter
			 */
			$iter = $expr;
			while ( $iter->MoveNext() )
			{
				$item = $iter->getCurrent();
				if ( CoreFuncs::BooleanValue( $item ) instanceof CoreFuncs::$True )
				// if ( XPath2Convert::ValueAs( $item, SequenceTypes::$Boolean, null, null ) instanceof CoreFuncs::$True )
				// if ( $item->getValueAsBoolean() )
				{
					return CoreFuncs::$True;
				}
			}
		}
		return CoreFuncs::$False;
	}

	/**
	 * Every
	 * @param object $expr
	 * @return object
	 */
	public static function Every( $expr )
	{
		if ( $expr instanceof XPath2NodeIterator )
		{
			/**
			 * @var XPath2NodeIterator $iter
			 */
			$iter = $expr;
			while ( $iter->MoveNext() )
			{
				$item = $iter->getCurrent();
				if ( CoreFuncs::BooleanValue( $item ) instanceof CoreFuncs::$False )
				// if ( XPath2Convert::ValueAs( $item, SequenceTypes::$Boolean, null, null ) instanceof CoreFuncs::$False )
				// if ( ! $iter->getCurrent()->getValueAsBoolean() )
				{
					return CoreFuncs::$False;
				}
			}
		}
		return CoreFuncs::$True;
	}

	/**
	 * CastTo
	 * @param XPath2Context $context
	 * @param object $value
	 * @param SequenceType $destType
	 * @param bool $isLiteral
	 * @return object
	 */
	public static function CastTo( $context, $value, $destType, $isLiteral )
	{
		if ( $destType == SequenceTypes::$Item )
			return $value;

		if ( $value instanceof Undefined )
		{
			if ( $destType->Cardinality == XmlTypeCardinality::ZeroOrMore )
				return EmptyIterator::$Shared;

			if ( $destType->TypeCode != XmlTypeCode::None && $destType->Cardinality != XmlTypeCardinality::ZeroOrOne )
				throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "empty-sequence()", $destType ) );

			return Undefined::getValue();
		}

		if ( $destType->Cardinality == XmlTypeCardinality::One || $destType->Cardinality == XmlTypeCardinality::ZeroOrOne )
		{
			/**
			 * @var XPathItem $res
			 */
			$res;
			if ( $value Instanceof XPath2NodeIterator )
			{
				/**
				 * @var XPath2NodeIterator $iter
				 */
				$iter = $value;
				$iter = $iter->CloneInstance();
				if ( ! $iter->MoveNext() )
				{
					if ( $destType->TypeCode != XmlTypeCode::None &&
						( $destType->Cardinality == XmlTypeCardinality::One || $destType->Cardinality == XmlTypeCardinality::OneOrMore ))
					{
						throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "empty-sequence()", $destType ) );
					}

					return Undefined::getValue();
				}

				if ( ! $isLiteral )
				{
					$typeCode = $iter->getCurrent()->getSchemaType()->TypeCode;
					if ( ( $destType->TypeCode == XmlTypeCode::QName && $typeCode != XmlTypeCode::QName ) ||
						 ( $destType->TypeCode == XmlTypeCode::Notation && $typeCode != XmlTypeCode::Notation ) )
					{
						throw XPath2Exception::withErrorCodeAndParam( "XPTY0004", Resources::XPTY0004_CAST, $destType );
					}
				}

				$res = CoreFuncs::ChangeType( $iter->getCurrent(), $destType, $context );

				if ( $iter->MoveNext() )
				{
					throw XPath2Exception::withErrorCode( "XPDY0050", Resources::MoreThanOneItem );
				}

				if ( $destType->IsNode )
				{
					return $res;
				}

				return $res->GetTypedValue();
			}

			/**
			 * @var XPathItem $item
			 */
			$item = $value instanceof XPathItem
				? $value
				: XPath2Item::fromValue( $value );

			if ( ! $isLiteral )
			{
				$typeCode = $item->getSchemaType()->TypeCode;
				if ( ( $destType->TypeCode == XmlTypeCode::QName && $typeCode != XmlTypeCode::QName ) ||
					 ( $destType->TypeCode == XmlTypeCode::Notation && $typeCode != XmlTypeCode::Notation ) )
					throw XPath2Exception::withErrorCodeAndParam( "XPTY0004", Resources::XPTY0004_CAST, $destType );
			}

			$res = CoreFuncs::ChangeType( $item, $destType, $context);
			// BMS 2017-07-12
			// if ( $destType->IsNode )
				return $res;

			return $res->GetTypedValue();
		}
		else
			return new NodeIterator( function() use( $value, $destType, $context ) { return CoreFuncs::ConvertIterator( XPath2NodeIterator::Create( $value ), $destType, $context ); } );
	}

	/**
	 * CastArg
	 * @param XPath2Context $context
	 * @param object $value
	 * @param SequenceType $destType
	 * @return object
	 */
	public static function CastArg( $context, $value, $destType )
	{
		if ( $destType == SequenceTypes::$Item )
		{
			return $value;
		}

		if ( $value instanceof Undefined )
		{
			if ( $destType->Cardinality == XmlTypeCardinality::ZeroOrMore )
			{
				return EmptyIterator::$Shared;
			}
			if ( $destType->TypeCode != XmlTypeCode::None && $destType->Cardinality != XmlTypeCardinality::ZeroOrOne )
			{
				throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "empty-sequence()", $destType ) );
			}

			return Undefined::getValue();
		}

		if ( $destType->Cardinality == XmlTypeCardinality::One || $destType->Cardinality == XmlTypeCardinality::ZeroOrOne )
		{
			$res = null;
			if ( $value instanceof XPath2NodeIterator )
			{
				/**
				 * @var XPath2NodeIterator $iter
				 */
				$iter = $value;
				$iter = $iter->CloneInstance();
				if ( ! $iter->MoveNext() )
				{
					if ( $destType->TypeCode != XmlTypeCode::None &&
						( $destType->Cardinality == XmlTypeCardinality::One || $destType->Cardinality == XmlTypeCardinality::OneOrMore ) )
						throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "empty-sequence()", $destType ) );
					return Undefined::getValue();
				}

				if ( $destType->IsNode )
				{
					if ( ! $destType->Match( $iter->getCurrent(), $context ) )
						throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
							array(
								SequenceType::WithSchemaTypeWithCardinality( $iter->getCurrent().GetSchemaType(), XmlTypeCardinality::OneOrMore ),
								$destType
							)
						);
					$res = $iter->getCurrent()->CloneInstance();
				}
				else
					$res = XPath2Convert::ValueAs( $iter->getCurrent()->GetTypedValue(), $destType, $context->NameTable, $context->NamespaceManager );

				if ( $iter->MoveNext() )
					throw XPath2Exception::withErrorCode( "XPDY0050", Resources::MoreThanOneItem );

				return $res;
			}
			else
			{
				if ( $value instanceof XPathItem && ! $value instanceof XPath2Item )
				{
					/**
					 * @var XPathItem $item
					 */
					$item = $value;
					if ( $item->getIsNode() )
					{
						if ( ! $destType->Match( $item, $context ) )
							throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
								array(
									SequenceType::WithSchemaTypeWithCardinality( $item->GetSchemaType(), XmlTypeCardinality::OneOrMore ),
									$destType
								)
							);
						return $item;
					}
					else
						return XPath2Convert::ValueAs( $item->getTypedValue(), $destType, $context->NameTable, $context->NamespaceManager );
				}
				return XPath2Convert::ValueAs( $value, $destType, $context->NameTable, $context->NamespaceManager );
			}
		}
		else
		{
			return new NodeIterator( function() use( $value, $destType, $context ) { return CoreFuncs::ValueIterator( XPath2NodeIterator::Create( $value ), $destType, $context ); } );
		}
	}

	/**
	 * TreatAs
	 * @param XPath2Context $context
	 * @param object $value
	 * @param SequenceType $destType
	 * @return object
	 */
	public static function TreatAs( $context, $value, $destType )
	{
		if ( $destType == SequenceTypes::$Item )
		{
			return $value;
		}

		if ( $value instanceof Undefined )
		{
			if ( $destType->Cardinality == XmlTypeCardinality::ZeroOrMore )
			{
				return EmptyIterator::$Shared;
			}

			if ( $destType->TypeCode != XmlTypeCode::None && $destType->Cardinality != XmlTypeCardinality::ZeroOrOne )
			{
				throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "empty-sequence()", $destType ) );
			}

			return Undefined::getValue();
		}

		if ( $destType->Cardinality == XmlTypeCardinality::One || $destType->Cardinality == XmlTypeCardinality::ZeroOrOne )
		{
			$res = null;
			if ( $value instanceof XPath2NodeIterator )
			{
				/**
				 * @var XPath2NodeIterator $iter
				 */
				$iter = $value;
				$iter = $iter->CloneInstance();
				if ( ! $iter->MoveNext() )
				{
					if ( $destType->TypeCode != XmlTypeCode::None &&
						( $destType->Cardinality == XmlTypeCardinality::One || $destType->Cardinality == XmlTypeCardinality::OneOrMore ) )
						throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "empty-sequence()", $destType ) );
					return Undefined::getValue();
				}

				if ( $destType->TypeCode == XmlTypeCode::None )
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
						array(
							SequenceType::WithSchemaTypeWithCardinality( $iter->getCurrent().GetSchemaType(), XmlTypeCardinality::OneOrMore ),
							"empty-sequence()"
						)
					);

				if ( $destType->IsNode )
				{
					if ( ! $destType->Match( $iter.getCurrent(), $context ))
						throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
							array(
								SequenceType::WithSchemaTypeWithCardinality( $iter->getCurrent().GetSchemaType(), XmlTypeCardinality::OneOrMore ),
								$destType
							)
						);
					$res = $iter->getCurrent()->CloneInstance();
				}
				else
					$res = XPath2Convert::TreatValueAs( $iter->getCurrent()->GetTypedValue(), $destType );
				if ( $iter->MoveNext() )
					throw XPath2Exception::withErrorCode( "XPDY0050", Resources::MoreThanOneItem );
				return $res;
			}
			else
			{

				if ( $value instanceof XPathItem )
				{
					/**
					 * @var XPathItem $item
					 */
					$item = $value;
					if ( $item->getIsNode() )
					{
						if ( ! $destType->Match( $item, $context ) )
							throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
								array(
									SequenceType::WithSchemaTypeWithCardinality( $item->GetSchemaType(), XmlTypeCardinality::OneOrMore ),
									$destType
								)
							);
						return $item;
					}
					else
						return XPath2Convert::TreatValueAs( $item->GetTypedValue(), $destType );
				}
				return XPath2Convert::TreatValueAs( $value, $destType );
			}
		}
		else
			return new NodeIterator( function() use( $value, $destType, $context ) { return CoreFuncs::TreatIterator( XPath2NodeIterator::Create( $value ), $destType, $context ); } );
	}

	/**
	 * CastToItem
	 * @param XPath2Context $context
	 * @param object $value
	 * @param SequenceType $destType
	 * @return object
	 */
	public static function CastToItem( $context, $value, $destType )
	{
		$xmlType = null;

		if ( is_null( $value ) )
		{
			$value = CoreFuncs::$False;
		}
		else
		{
			if ( $value instanceof XPath2Item || $value instanceof IXmlSchemaType )
			{
				$xmlType = $value->getSchemaType();

				// BMS 2017-07-12 Now values can return their type, the type equality can be tested early.
				if ( $destType->TypeCode == $xmlType->TypeCode )
				{
					return $value;
				}
			}

			$value = CoreFuncs::Atomize( $value );
			if ( $value instanceof Undefined )
			{
				if ( $destType->TypeCode == XmlTypeCode::String )
				{
					return "";
				}
				return $value;
			}
		}

		if ( is_null( $xmlType ) )
		{
			/**
			 * @var XmlTypeCode $typeCode
			 */
			$typeCode = SequenceType::GetXmlTypeCodeFromObject( ValueProxy::Unwrap( $value ) );
			/**
			 * @var XmlSchemaType $xmlType
			 */
			$xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( $typeCode );
		}
		else if ( $value instanceof ValueProxy )
		{
			$value = ValueProxy::Unwrap( $value );
		}

		return XPath2Convert::ChangeType( $xmlType, $value, $destType, $context->NameTable, $context->NamespaceManager );
	}

	/**
	 * InstanceOf
	 * @param XPath2Context $context
	 * @param object $value
	 * @param SequenceType $destType
	 * @return object
	 */
	public static function InstanceOfInstance( $context, $value, $destType )
	{
		if ( is_null( $value ) )
		{
			$value = CoreFuncs::$False;
		}

		if ( $value instanceof Undefined )
		{
			return $destType == SequenceTypes::$Void || $destType->Cardinality == XmlTypeCardinality::ZeroOrOne || $destType->Cardinality == XmlTypeCardinality::ZeroOrMore;
		}

		if ( $value instanceof XPath2NodeIterator )
		{
			/**
			 * @var XPath2NodeIterator $iter
			 */
			$iter = $value->CloneInstance();
			$num = 0;

			/**
			 * @var XPathItem $item
			 */
			foreach ( $iter as $item )
			{
				if ( $num == 1 )
				{
					if ( $destType->Cardinality == XmlTypeCardinality::ZeroOrOne || $destType->Cardinality == XmlTypeCardinality::One )
					{
						return CoreFuncs::$False;
					}
				}

				if ( ! $destType->Match( $item, $context ) )
				{
					return CoreFuncs::$False;
				}

				$num++;
			}

			if ($num == 0 )
			{
				if ( $destType->TypeCode != XmlTypeCode::None &&
					 ( $destType->Cardinality == XmlTypeCardinality::One || $destType->Cardinality == XmlTypeCardinality::OneOrMore ) )
				{
					return CoreFuncs::$False;
				}
			}

			return CoreFuncs::$True;
		}
		else
		{
			if ( $value instanceof ValueProxy )
			{
				$value = $value->getValue();
			}
			// BMS 2018-01-20 Added to allow DateTimeDuration to match duration type or a descendent
			if ( ( $destType->ItemType == Type::FromValue( $value ) ) ||
				 ( $destType->TypeCode == XmlTypeCode::Duration && $value instanceof DurationValue ) )
			{
				if ( $value instanceof XPath2Item )
				{
					$value = $value->getValue();
				}
				// Special case because of the limited PHP type system
				if ( $destType->TypeCode == XmlTypeCode::Int && ( $value < -2147483648 || $value > 2147483647 ) )
				{
					return CoreFuncs::$False;
				}
				return CoreFuncs::$True;
			}

			if (  $value instanceof XPathItem )
			{
				$item = $value;
			}
			else
			{
				$item = XPath2Item::fromValue( $value );
			}

			if ( $destType->Match( $item, $context ) )
			{
				return CoreFuncs::$True;
			}

			return CoreFuncs::$False;
		}
	}

	/**
	 * Castable
	 * @param XPath2Context $context
	 * @param object $value
	 * @param SequenceType $destType
	 * @param bool $isLiteral
	 * @return object
	 */
	public static function Castable( $context, $value, $destType, $isLiteral )
	{
		try
		{
			CoreFuncs::CastTo( $context, $value, $destType, $isLiteral );
			return CoreFuncs::$True;
		}
		catch ( XPath2Exception $ex )
		{
			return CoreFuncs::$False;
		}
	}

	/**
	 * SameNode
	 * @param object $a
	 * @param object $b
	 * @return object
	 */
	public static function SameNode( $a, $b )
	{
		/**
		 * @var XPathNavigator $nav1
		 */
		$nav1 = $a;

		/**
		 * @var XPathNavigator $nav1
		 */
		$nav2 = $b;

		$res = $nav1->ComparePosition( $nav2 );
		if( $res != XmlNodeOrder::Unknown )
			return $res == XmlNodeOrder::Same ? CoreFuncs::$True : CoreFuncs::$False;

		return $nav2->ComparePosition( $nav1 ) == XmlNodeOrder::Same ? CoreFuncs::$True : CoreFuncs::$False;
	}

	/**
	 * PrecedingNode
	 * @param object $a
	 * @param object $b
	 * @return object
	 */
	public static function PrecedingNode( $a, $b )
	{
		/**
		 * @var XPathNavigator $nav1
		 */
		$nav1 = $a;

		/**
		 * @var XPathNavigator $nav1
		 */
		$nav2 = $b;

		/**
		 * @var XPathComparer $comp
		 */
		$comp = new XPathComparer();
		return $comp->Compare( $nav1, $nav2 ) == -1 ? CoreFuncs::$True : CoreFuncs::$False;
	}

	/**
	 * FollowingNode
	 * @param object $a
	 * @param object $b
	 * @return object
	 */
	public static function FollowingNode( $a, $b )
	{
		/**
		 * @var XPathNavigator $nav1
		 */
		$nav1 = $a;

		/**
		 * @var XPathNavigator $nav1
		 */
		$nav2 = $b;

		/**
		 * @var XPathComparer $comp
		 */
		$comp = new XPathComparer();
		return $comp->Compare( $nav1, $nav2 ) ==  1 ? CoreFuncs::$True : CoreFuncs::$False;
	}

	/**
	 * MagnitudeRelationship
	 * @param XPath2Context $context
	 * @param XPathItem $item1
	 * @param XPathItem $item2
	 * @param object $x
	 * @param object $y
	 * @return void
	 */
	private static function MagnitudeRelationship( $context, $item1, $item2, &$x, &$y )
	{
		$x = $item1->GetTypedValue();
		$y = $item2->GetTypedValue();
		if ( $x instanceof UntypedAtomic )
		{
			if ( ValueProxy::IsNumericValue( Type::FromValue( $y ) ) )
				$x = Convert::ToDouble( $x, null );
			else
				if ( is_string( $y ) )
					$x = $x->ToString();
				else if ( ! ( $y instanceof UntypedAtomic ) )
					$x = CoreFuncs::ChangeType( $item1, SequenceType::WithTypeCode( $item2->GetSchemaType()->TypeCode ), $context )->GetTypedValue();
		}

		if ( $y instanceof UntypedAtomic )
		{
			if ( ValueProxy::IsNumericValue( Type::FromValue( $x ) ) )
				$y = Convert::ToDouble( $y, null );
			else
				if ( is_string( $x ) )
					$y = $y->ToString();
				else if ( ! ( $x instanceof UntypedAtomic ) )
					$y = CoreFuncs::ChangeType( $item2, SequenceType::WithTypeCode( $item1->GetSchemaType()->TypeCode ), $context )->GetTypedValue();
		}
	}

	/**
	 * GeneralEQ
	 * @param XPath2Context $context
	 * @param object $a
	 * @param object $b
	 * @return object
	 */
	public static function GeneralEQ( $context, $a, $b )
	{
		/**
		 * @var XPath2NodeIterator $iter1
		 */
		$iter1 = XPath2NodeIterator::Create( $a );
		/**
		 * @var XPath2NodeIterator $iter2
		 */
		$iter2 = XPath2NodeIterator::Create( $b );

		while ( $iter1->MoveNext() )
		{
			/**
			 * @var XPath2NodeIterator $iter
			 */
			$iter = $iter2->CloneInstance();
			while ( $iter->MoveNext() )
			{
				$x;
				$y;
				CoreFuncs::MagnitudeRelationship( $context, $iter1->getCurrent(), $iter->getCurrent(), $x, $y );
				if ( CoreFuncs::OperatorEq( $x, $y ) instanceof TrueValue )
					return CoreFuncs::$True;
			}
		}

		return CoreFuncs::$False;
	}

	/**
	 * GeneralGT
	 * @param XPath2Context $context
	 * @param object $a
	 * @param object $b
	 * @return object
	 */
	public static function GeneralGT( $context, $a, $b )
	{
		/**
		 * @var XPath2NodeIterator $iter1
		 */
		$iter1 = XPath2NodeIterator::Create( $a );
		/**
		 * @var XPath2NodeIterator $iter2
		 */
		$iter2 = XPath2NodeIterator::Create( $b );

		while ( $iter1->MoveNext() )
		{
			/**
			 * @var XPath2NodeIterator $iter
			 */
			$iter = $iter2->CloneInstance();
			while ( $iter->MoveNext() )
			{
				$x;
				$y;
				CoreFuncs::MagnitudeRelationship( $context, $iter1->getCurrent(), $iter->getCurrent(), $x, $y );
				if ( CoreFuncs::OperatorGt( $x, $y ) instanceof TrueValue )
					return CoreFuncs::$True;
			}
		}
		return CoreFuncs::$False;
	}

	/**
	 * GeneralNE
	 * @param XPath2Context $context
	 * @param object $a
	 * @param object $b
	 * @return object
	 */
	public static function GeneralNE( $context, $a, $b )
	{
		/**
		 * @var XPath2NodeIterator $iter1
		 */
		$iter1 = XPath2NodeIterator::Create( $a );
		/**
		 * @var XPath2NodeIterator $iter2
		 */
		$iter2 = XPath2NodeIterator::Create( $b );

		while ( $iter1->MoveNext() )
		{
			/**
			 * @var XPath2NodeIterator $iter
			 */
			$iter = $iter2->CloneInstance();
			while ( $iter->MoveNext() )
			{
				$x;
				$y;
				CoreFuncs::MagnitudeRelationship( $context, $iter1->getCurrent(), $iter->getCurrent(), $x, $y );
				if ( CoreFuncs::OperatorEq( $x, $y ) instanceof FalseValue )
					return CoreFuncs::$True;
			}
		}
		return CoreFuncs::$False;
	}

	/**
	 * GeneralGE
	 * @param XPath2Context $context
	 * @param object $a
	 * @param object $b
	 * @return object
	 */
	public static function GeneralGE( $context, $a, $b )
	{
		/**
		 * @var XPath2NodeIterator $iter1
		 */
		$iter1 = XPath2NodeIterator::Create( $a );
		/**
		 * @var XPath2NodeIterator $iter2
		 */
		$iter2 = XPath2NodeIterator::Create( $b );

		while ( $iter1->MoveNext() )
		{
			/**
			 * @var XPath2NodeIterator $iter
			 */
			$iter = $iter2->CloneInstance();
			while ( $iter->MoveNext() )
			{
				$x;
				$y;
				CoreFuncs::MagnitudeRelationship( $context, $iter1->getCurrent(), $iter->getCurrent(), $x, $y );
				if ( CoreFuncs::OperatorEq( $x, $y ) instanceof TrueValue || CoreFuncs::OperatorGt( $x, $y ) instanceof TrueValue )
					return CoreFuncs::$True;
			}
		}
		return CoreFuncs::$False;
	}

	/**
	 * GeneralLT
	 * @param XPath2Context $context
	 * @param object $a
	 * @param object $b
	 * @return object
	 */
	public static function GeneralLT( $context, $a, $b )
	{
		/**
		 * @var XPath2NodeIterator $iter1
		 */
		$iter1 = XPath2NodeIterator::Create( $a );
		/**
		 * @var XPath2NodeIterator $iter2
		 */
		$iter2 = XPath2NodeIterator::Create( $b );

		while ( $iter1->MoveNext() )
		{
			/**
			 * @var XPath2NodeIterator $iter
			 */
			$iter = $iter2->CloneInstance();
			while ( $iter->MoveNext() )
			{
				$x;
				$y;
				CoreFuncs::MagnitudeRelationship( $context, $iter1->getCurrent(), $iter->getCurrent(), $x, $y );
				if ( CoreFuncs::OperatorGt( $y, $x ) instanceof TrueValue )
					return CoreFuncs::$True;
			}
		}
		return CoreFuncs::$False;
	}

	/**
	 * GeneralLE
	 * @param XPath2Context $context
	 * @param object $a
	 * @param object $b
	 * @return object
	 */
	public static function GeneralLE( $context, $a, $b )
	{
		/**
		 * @var XPath2NodeIterator $iter1
		 */
		$iter1 = XPath2NodeIterator::Create( $a );
		/**
		 * @var XPath2NodeIterator $iter2
		 */
		$iter2 = XPath2NodeIterator::Create( $b );

		while ( $iter1->MoveNext() )
		{
			/**
			 * @var XPath2NodeIterator $iter
			 */
			$iter = $iter2->CloneInstance();
			while ( $iter->MoveNext() )
			{
				$x;
				$y;
				CoreFuncs::MagnitudeRelationship( $context, $iter1->getCurrent(), $iter->getCurrent(), $x, $y );
				if ( CoreFuncs::OperatorEq( $x, $y ) instanceof TrueValue || CoreFuncs::OperatorGt( $y, $x ) instanceof TrueValue )
					return CoreFuncs::$True;
			}
		}
		return CoreFuncs::$False;
	}

	/**
	 * GetRange
	 * @param object $arg1
	 * @param object $arg2
	 * @return XPath2NodeIterator
	 */
	public static function GetRange( $arg1, $arg2 )
	{
		$lo = CoreFuncs::Atomize( $arg1 );
		if ( $lo instanceof Undefined )
			return EmptyIterator::$Shared;

		if ( $lo instanceof UntypedAtomic )
		{
			$i = $lo->ToString();
			if ( ! is_numeric( $i ) )
				throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
					array(
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $lo ), XmlTypeCardinality::One ),
						"xs:integer in first argument op:range"
					)
				);
			$lo = $i + 0;
		}

		$high = CoreFuncs::Atomize( $arg2 );

		if ( $high instanceof Undefined )
			return EmptyIterator::$Shared;

		if ( $high instanceof UntypedAtomic )
		{
			$i = $high->ToString();
			if ( ! is_numeric( $i ) )
				throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
					array(
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $high ), XmlTypeCardinality::One ),
						"xs:integer in second argument op:range"
					)
				);
			$high = $i + 0;
		}

		if ( $lo instanceof ValueProxy )
		{
			/**
			 * @var ValueProxy $prx1
			 */
			$prx1 = $lo;
			$lo = $prx1->getValue();
		}

		if ( $lo instanceof XPath2Item )
		{
			/**
			 * @var ValueProxy $prx1
			 */
			$item = $lo;
			$lo = $item->getTypedValue();
		}

		if ( $high instanceof ValueProxy )
		{
			/**
			 * @var ValueProxy $prx2
			 */
			$prx2 = $high;
			$high = $prx2->getValue();
		}

		if ( $high instanceof XPath2Item )
		{
			/**
			 * @var ValueProxy $prx2
			 */
			$item = $high;
			$high = $item->getTypedValue();
		}

		if ( ! Integer::IsDerivedSubtype( $lo ) )
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $lo ), XmlTypeCardinality::One ),
					"xs:integer in first argument op:range"
				)
			);

		if ( ! Integer::IsDerivedSubtype( $high ) )
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $high ), XmlTypeCardinality::One ),
					"xs:integer in second argument op:range"
				)
			);

		return new RangeIterator( Convert::ToInt32( $lo ), Convert::ToInt32( $high ) );
	}

	/**
	 * Union
	 * @param XPath2Context $context
	 * @param object $a
	 * @param object $b
	 * @return XPath2NodeIterator
	 */
	public static function Union( $context, $a, $b )
	{
		/**
		 * @var XPath2NodeIterator $iter1
		 */
		$iter1 = XPath2NodeIterator::Create( $a );

		/**
		 * @var XPath2NodeIterator $iter2
		 */
		$iter2 = XPath2NodeIterator::Create( $b );

		if ( $context->RunningContext->IsOrdered )
		{
			return new NodeIterator( function() use( $iter1, $iter2 ) { return CoreFuncs::UnionOrderedIterator( $iter1, $iter2 ); } );
		}
		else
		{
			return new NodeIterator( function() use( $iter1, $iter2 ) { return CoreFuncs::UnionUnorderedIterator( $iter1, $iter2 ); } );
		}
	}

	/**
	 * Except
	 * @param XPath2Context $context
	 * @param object $a
	 * @param object $b
	 * @return XPath2NodeIterator
	 */
	public static function Except( $context, $a, $b )
	{
		/**
		 * @var XPath2NodeIterator $iter1
		 */
		$iter1 = XPath2NodeIterator::Create( $a );
		/**
		 * @var XPath2NodeIterator $iter2
		 */
		$iter2 = XPath2NodeIterator::Create( $b );

		if ( $context->RunningContext->IsOrdered )
			return new NodeIterator( function() use( $iter1, $iter2 ) { return CoreFuncs::IntersectOrderedExceptIterator( true, $iter1, $iter2 ); } );
		else
			return new NodeIterator( function() use( $iter1, $iter2 ) { return CoreFuncs::IntersectUnorderedExceptIterator( true, $iter1, $iter2 ); } );
	}

	/**
	 * Intersect
	 * @param XPath2Context $context
	 * @param object $a
	 * @param object $b
	 * @return XPath2NodeIterator
	 */
	public static function Intersect( $context, $a, $b )
	{
		/**
		 * @var XPath2NodeIterator $iter1
		 */
		$iter1 = XPath2NodeIterator::Create( $a );
		/**
		 * @var XPath2NodeIterator $iter2
		 */
		$iter2 = XPath2NodeIterator::Create( $b );

		if ( $context->RunningContext->IsOrdered )
			return new NodeIterator( function() use( $iter1, $iter2 ) { return CoreFuncs::IntersectOrderedExceptIterator( false, $iter1, $iter2 ); } );
		else
			return new NodeIterator( function() use( $iter1, $iter2 ) { return CoreFuncs::IntersectUnorderedExceptIterator( false, $iter1, $iter2 ); } );
	}

	/**
	 * ContextNode
	 * @param IContextProvider $provider
	 * @return XPathItem
	 */
	public static function ContextNode( $provider )
	{
		if ( is_null( $provider ) )
		{
			throw XPath2Exception::withErrorCode( "XPDY0002", Resources::XPDY0002 );
		}

		$item = $provider->getContext();
		if ( is_null( $item ) )
		{
			throw XPath2Exception::withErrorCode( "XPDY0002", Resources::XPDY0002 );
		}

		return $item;
	}

	/**
	 * GetXPath2ResultType
	 * @param SequenceType $sequenceType
	 * @return XPath2ResultType
	 */
	public static function GetXPath2ResultTypeFromSequenceType( $sequenceType )
	{
		if ( $sequenceType->Cardinality == XmlTypeCardinality::ZeroOrMore || $sequenceType->Cardinality == XmlTypeCardinality::OneOrMore )
			return XPath2ResultType::NodeSet;

		switch ( $sequenceType->TypeCode )
		{
			case XmlTypeCode::String:
				return XPath2ResultType::String;
			case XmlTypeCode::Time:
			case XmlTypeCode::Date:
			case XmlTypeCode::DateTime:
				return XPath2ResultType::DateTime;
			case XmlTypeCode::Boolean:
				return XPath2ResultType::Boolean;
			case XmlTypeCode::AnyUri:
				return XPath2ResultType::AnyUri;
			case XmlTypeCode::QName:
				return XPath2ResultType::QName;
			case XmlTypeCode::GDay:
			case XmlTypeCode::GMonth:
			case XmlTypeCode::GMonthDay:
			case XmlTypeCode::GYear:
			case XmlTypeCode::GYearMonth:
				return XPath2ResultType::DateTime;
			case XmlTypeCode::Duration:
			case XmlTypeCode::DayTimeDuration:
			case XmlTypeCode::YearMonthDuration:
				return XPath2ResultType::Duration;
			default:
				if ( SequenceType::TypeCodeIsNodeType( $sequenceType->TypeCode ))
					return XPath2ResultType::Navigator;
				if ( $sequenceType->getIsNumeric() )
					return XPath2ResultType::Number;
				return XPath2ResultType::Other;
		}
	}

	/**
	 * GetXPath2ResultType
	 * @param object $value
	 * @return XPath2ResultType
	 */
	public static function GetXPath2ResultTypeFromValue( $value )
	{
		if ( is_null( $value ) || $value instanceof Undefined )
			return XPath2ResultType::Any;

		if ( $value instanceof XPath2NodeIterator )
			return XPath2ResultType::NodeSet;

		if ( $value instanceof XPathItem )
		{
			/**
			 * @var XPathItem $item
			 */
			$item = $value;
			if ( $item->getIsNode() )
				return XPath2ResultType::Navigator;
			else
				$value = $item->getTypedValue();
		}
		elseif ( $value instanceof IXmlSchemaType )
		{
			if ( $value instanceof LanguageValue || $value instanceof NameValue || $value instanceof NMTOKENValue )
			{
				$value = $value->getValue();
			}
		}

		/**
		 * @var ValueProxy $proxy
		 */
		$proxy = $value instanceof ValueProxy ? $value : null;
		if ( ! is_null( $proxy ) )
			$value = $proxy->getValue();
		if ( $value instanceof AnyUriValue )
			return XPath2ResultType::AnyUri;
		if ( $value instanceof QNameValue )
			return XPath2ResultType::QName;
		if ( $value instanceof Integer )
			return XPath2ResultType::Number;
		if ( $value instanceof Long )
			return XPath2ResultType::Number;
		if ( $value instanceof \DateTime || $value instanceof DateTimeValueBase || $value instanceof TimeValue )
			return XPath2ResultType::DateTime;
		if ( $value instanceof DurationValue )
			return XPath2ResultType::Duration;
		if ( $value instanceof DecimalValue )
			return XPath2ResultType::Number;
		if ( is_string( $value ) )
			return XPath2ResultType::String;
		if ( is_numeric( $value ) )
			return XPath2ResultType::Number;

		$type = Type::FromValue( $value );
		switch ( $type->getTypeCode() )
		{
			case TypeCode::Boolean:
				return XPath2ResultType::Boolean;

			case TypeCode::Char:
			case TypeCode::String:
				return XPath2ResultType::String;

			case TypeCode::Byte:
			case TypeCode::SByte:
			case TypeCode::UInt16:
			case TypeCode::Int16:
			case TypeCode::UInt32:
			case TypeCode::Int32:
			case TypeCode::Int64:
			case TypeCode::UInt64:
			case TypeCode::Decimal:
			case TypeCode::Single:
			case TypeCode::Double:
				return XPath2ResultType::Number;

			default:
				return XPath2ResultType::Other;
		}
	}

	/**
	 * GetRoot
	 * @param object $node
	 * @return object
	 */
	public static function GetRoot( $node )
	{
		if ( is_null( $node ) )
		{
			return Undefined::getValue();
		}

		if ( $node instanceof IContextProvider )
		{
			$node = CoreFuncs::GetRoot( CoreFuncs::NodeValue( CoreFuncs::ContextNode( $provider ) ) );
		}

		if ( ! $node instanceof XPathNavigator )
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $node ), XmlTypeCardinality::ZeroOrOne ),
					"$node()? in fn:root()"
				)
			);

		/**
		 * @var XPathNavigator $nav
		 */
		$nav = $node;
		/**
		 * @var XPathNavigator $nav
		 */
		$curr = $nav->CloneInstance();
		$curr->MoveToRoot();
		return $curr;
	}

	/**
	 * Not
	 * @param object $value
	 * @return object
	 */
	public static function Not( $value )
	{
		if ( CoreFuncs::BooleanValue( $value ) instanceof FalseValue )
			return CoreFuncs::$True;
		return CoreFuncs::$False;
	}

	/**
	 * CastString
	 * @param XPath2Context $context
	 * @param object $value
	 * @return object
	 */
	public static function CastString( $context, $value )
	{
		return CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $value ), SequenceTypes::$StringX );
	}

	/**
	 * Number
	 * @param XPath2Context $context
	 * @param object $provider
	 * @return double
	 */
	public static function NumberWithProvider( $context, $provider )
	{
		$value = CoreFuncs::Atomize( CoreFuncs::ContextNode( $provider ) );

		return CoreFuncs::Number( $context, $value );
	}

	/**
	 * Number
	 * @param XPath2Context $context
	 * @param object $value
	 * @return XPath2Item
	 */
	public static function Number( $context, $value )
	{
		$result = null;

		if ( $value instanceof DOMXPathNavigator )
		{
			$value = $value->getTypedValue();
		}

		if ( $value instanceof Undefined )
		{
			$result = NAN;
		}
		else if ( is_numeric( $value ) )
		{
			$result = doubleval( $value );
		}
		else
		{
			try
			{
				$result = Convert::ChangeType( $value, Types::$DoubleType, null );

				if ( is_null( $result ) )
				{
					$result = NAN;
				}
			}
			catch ( \Exception $ex )
			{
				$result = NAN;
			}
		}

		return XPath2Item::fromValueAndType( $result, XmlSchema::$Double );
	}

	/**
	 * CastToNumber1
	 * @param XPath2Context $context
	 * @param object $value
	 * @return object
	 */
	public static function CastToNumber1( $context, $value )
	{
		try
		{
			if ( $value instanceof XPath2Item && $value->getSchemaType()->TypeCode == XmlTypeCode::UntypedAtomic )
			{
				$value = $value->getTypedValue();
			}

			if ( $value instanceof UntypedAtomic )
			{
				$result = Convert::ToDouble( $value->getValue(), null );
				if ( is_null( $result ) )
				{
					throw new InvalidCastException();
				}
				return XPath2Item::fromValueAndType( $result, XmlSchema::$Double );
			}
		}
		catch ( FormatException $ex)
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, "xs:double?" ) );
		}
		catch ( InvalidCastException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, "xs:double?" ) );
		}
		return $value;
	}

	/**
	 * StringValue
	 * @param XPath2Context $context
	 * @param object $value
	 * @return string
	 */
	public static function StringValue( $context, $value )
	{
		if ( is_string( $value ) )
		{
			return $value;
		}

		if ( $value instanceof IContextProvider )
		{
			$value = CoreFuncs::ContextNode( $value );
		}

		if ( $value instanceof Undefined )
		{
			return "";
		}

		if ( $value instanceof XPath2NodeIterator )
		{
			/**
			 * @var XPath2NodeIterator $iter
			 */
			$iter = $value;
			$iter = $iter->CloneInstance();
			if ( ! $iter->MoveNext() )
			{
				return "";
			}

			$res = $iter->getCurrent()->getValue();
			if ( $iter->MoveNext() )
			{
				throw XPath2Exception::withErrorCode( "XPTY0004", Resources::MoreThanOneItem );
			}

			return $res;
		}

		if ( $value instanceof XPath2Item )
		{
			/**
			 * @var XPathItem $item
			 */
			$item = $value;
			return (string)$item;
		}

		if ( $value instanceof XPathItem )
		{
			/**
			 * @var XPathItem $item
			 */
			// BMS 2018-04-03 At some point this was changed to return getValue()
			//				  I think because an error was returned by one of the tests
			// 				  See the note in atomize() with the same date for another example
			$item = $value;
			return $item->getTypedValue();
			return $item->getValue();

			// // Put here by mistake.  Should have gone in atomize()
			// // BMS 2018-04-03 Added the trim function so node content is trimmed if the XSD type requires
			// $typeCode = $item->getSchemaType()->TypeCode;
			// switch ( $typeCode )
			// {
            //
			// 	case XmlTypeCode::Token:
			// 	case XmlTypeCode::NmToken:
			// 	case XmlTypeCode::Language:
			// 	case XmlTypeCode::NCName:
			// 	case XmlTypeCode::Id;
			// 	case XmlTypeCode::Idref:
			// 	case XmlTypeCode::Entity:
			// 	case XmlTypeCode::Name:
			// 	case XmlTypeCode::NormalizedString:
            //
			// 		return trim( $item->getValue() );
            //
			// 	default:
            //
			// 		return $item->getValue();
			// }
		}

		return XPath2Convert::ToString( $value );
	}

	/**
	 * TryProcessTypeName
	 * @param XPath2Context $context
	 * @param QName $qualifiedName
	 * @param bool $raise
	 * @param XmlSchemaObject $schemaObject
	 * @return bool
	 */
	public static function TryProcessTypeName( $context, $qualifiedName, $raise, &$schemaObject )
	{
		if ( is_string( $qualifiedName ) )
		{
			// TODO Kludge alert!! The XBRL_Types class wrongly used the prefix 'xsd' instead of 'xs'
			//      This needs to be fixed but for now convert 'xs' to 'xsd'
			// BMS 2018-04-09 This can be retired
			// if ( strpos( $qualifiedName, "xs:" ) === 0 ) $qualifiedName = str_replace( "xs:", "xsd:", $qualifiedName );

			$qn = $qualifiedName;
			$qualifiedName = \lyquidity\xml\qname( $qualifiedName, $context->NamespaceManager->getNamespaces() );
			if ( is_null( $qualifiedName ) )
			{
				throw XPath2Exception::withErrorCodeAndParam( "XPST0081", Resources::XPST0081, $qn );
			}
		}

		$schemaObject = null;
		if ( $qualifiedName->localName == "anyAtomicType" && $qualifiedName->namespaceURI == XmlReservedNs::xs )
		{
			$schemaObject = XmlSchema::$AnyAtomicType;
			return true;
		}
		if ( $qualifiedName->localName == "untypedAtomic" && $qualifiedName->namespaceURI == XmlReservedNs::xs )
		{
			$schemaObject = XmlSchema::$UntypedAtomic;
			return true;
		}
		if ( $qualifiedName->localName == "anyType" && $qualifiedName->namespaceURI == XmlReservedNs::xs )
		{
			$schemaObject = XmlSchema::$AnyType;
			return true;
		}
		if ( $qualifiedName->localName == "untyped" && $qualifiedName->namespaceURI == XmlReservedNs::xs )
			return true;
		if ( $qualifiedName->localName == "yearMonthDuration" && $qualifiedName->namespaceURI == XmlReservedNs::xs )
		{
			$schemaObject = XmlSchema::$YearMonthDuration;
			return true;
		}
		if ( $qualifiedName->localName == "dayTimeDuration" && $qualifiedName->namespaceURI == XmlReservedNs::xs )
		{
			$schemaObject = XmlSchema::$DayTimeDuration;
			return true;
		}
		if ( $qualifiedName->localName == "date" && $qualifiedName->namespaceURI == XmlReservedNs::xs )
		{
			$schemaObject = XmlSchema::$Date;
			return true;
		}
		if ( $qualifiedName->localName == "dateTime" && $qualifiedName->namespaceURI == XmlReservedNs::xs )
		{
			$schemaObject = XmlSchema::$DateTime;
			return true;
		}
		if ( $qualifiedName->localName == "ID" && $qualifiedName->namespaceURI == XmlReservedNs::xs )
		{
			$schemaObject = XmlSchema::$ID;
			return true;
		}
		if ( $qualifiedName->localName == "IDREF" && $qualifiedName->namespaceURI == XmlReservedNs::xs )
		{
			$schemaObject = XmlSchema::$IDREF;
			return true;
		}
		if ( $qualifiedName->localName == "IDREFS" && $qualifiedName->namespaceURI == XmlReservedNs::xs )
		{
			$schemaObject = XmlSchema::$IDREFS;
			return true;
		}
		if ( $qualifiedName->localName == "NMTOKEN" && $qualifiedName->namespaceURI == XmlReservedNs::xs )
		{
			$schemaObject = XmlSchema::$NMTOKEN;
			return true;
		}
		if ( $qualifiedName->localName == "NMTOKENS" && $qualifiedName->namespaceURI == XmlReservedNs::xs )
		{
			$schemaObject = XmlSchema::$NMTOKENS;
			return true;
		}
		if ( $qualifiedName->localName == "Name" && $qualifiedName->namespaceURI == XmlReservedNs::xs )
		{
			$schemaObject = XmlSchema::$Name;
			return true;
		}
		if ( $qualifiedName->localName == "NCName" && $qualifiedName->namespaceURI == XmlReservedNs::xs )
		{
			$schemaObject = XmlSchema::$NCName;
			return true;
		}
		if ( $qualifiedName->namespaceURI == XmlReservedNs::xs )
		{
			$schemaObject = DOMSchemaType::GetBuiltInSimpleTypeByQName( $qualifiedName );
		}
		else
		{
			// TODO Need to sort this out
			$schemaObject = $context->SchemaSet->getGlobalType( $qualifiedName );
		}

		if ( is_null( $schemaObject ) && $raise )
		{
			throw XPath2Exception::withErrorCodeAndParam( "XPST0008", Resources::XPST0008, $qualifiedName );
		}

		return ! is_null( $schemaObject );
	}

}

CoreFuncs::__static();

?>