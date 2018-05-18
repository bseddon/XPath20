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

namespace lyquidity\XPath2;

use lyquidity\xml\xpath\XPathItem;
use \lyquidity\xml\interfaces\IConvertable;
use \lyquidity\XPath2\lyquidity\Type;
use lyquidity\XPath2\DOM\DOMSchemaType;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\xml\MS\XmlSchemaType;
use lyquidity\XPath2\Value\TimeValue;
use \lyquidity\XPath2\lyquidity\Convert;
use lyquidity\XPath2\Value\UntypedAtomic;
use lyquidity\XPath2\Value\Integer;
use lyquidity\XPath2\Value\DateValue;
use lyquidity\XPath2\Value\DayTimeDurationValue;
use lyquidity\XPath2\Value\YearMonthDurationValue;
use lyquidity\XPath2\Value\DurationValue;
use lyquidity\XPath2\Value\GYearMonthValue;
use lyquidity\XPath2\Value\GYearValue;
use lyquidity\XPath2\Value\GDayValue;
use lyquidity\XPath2\Value\GMonthValue;
use lyquidity\XPath2\Value\GMonthDayValue;
use lyquidity\XPath2\Value\QNameValue;
use lyquidity\XPath2\Value\AnyUriValue;
use lyquidity\XPath2\Value\HexBinaryValue;
use lyquidity\XPath2\Value\Base64BinaryValue;
use lyquidity\XPath2\Value\NMTOKENSValue;
use lyquidity\XPath2\Value\IDREFSValue;
use lyquidity\XPath2\Value\ENTITIESValue;
use lyquidity\XPath2\Value\NotationValue;
use lyquidity\XPath2\Value\DateTimeValue;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\XPath2\Value\DecimalValue;
use lyquidity\XPath2\Value\Long;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\Proxy\ValueProxy;
use lyquidity\xml\exceptions\NotImplementedException;
use lyquidity\xml\exceptions\ArgumentException;

/**
 * XPath2Item (public final)
 */
class XPath2Item implements XPathItem, IConvertable
{
	/**
	 *
	 */
	public static $CLASSNAME = "lyquidity\XPath2\XPath2Item";

	/**
	 * @var object $_value
	 */
	private $_value;

	/**
	 * @var XmlSchemaType $_xmlType
	 */
	private $_xmlType;

	/**
	 * Constructor
	 */
	public  function __construct()
	{
		$this->_value = Undefined::getValue();
	}

	/**
	 * Constructor
	 * @param object $value
	 * @return XPath2Item
	 */
	public static function fromValue( $value )
	{
		if ( $value instanceof XPath2Item )
		{
			return $value;
		}

		$result = new XPath2Item();
		// $result->setRawValue( $value instanceof ValueProxy || $value instanceof IXmlSchemaType ? $value->getValue() : $value );
		$result->setRawValue( $value instanceof ValueProxy ? $value->getValue() : $value );

		if ( $value instanceof IXmlSchemaType )
		{
			// $result->setRawValue( $value->getValue() );
			// $result->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( $value->GetTypeCode() );
			$result->_xmlType = $value->getSchemaType();
		}
		else
		{
			// $result->setRawValue( $value );
			$result->InferXmlType();
		}
		return $result;
	}

	public function __toString()
	{
		// return "XPath2Item: " . $this->_value;
		return $this->ToStringFromValue();
	}

	/**
	 * Constructor
	 * @param object $value
	 * @param XmlSchemaType $xmlType
	 * @return XPath2Item
	 */
	public static function fromValueAndType( $value, $xmlType )
	{
		if ( $value instanceof XPath2Item )
		{
			$value->_xmlType = $xmlType;
			return $value;
		}

		$result = new XPath2Item();
		// $result->setRawValue( $value instanceof IXmlSchemaType ? $value->getValue() : $value );
		$result->setRawValue( $value instanceof ValueProxy ? $value->getValue() : $value );
		if ( is_null( $xmlType ) ) $xmlType = XmlSchema::$UntypedAtomic;
		$result->_xmlType = $xmlType;
		return $result;
	}

	/**
	 * InferXmlType
	 * @return void
	 */
	private function InferXmlType()
	{
		if ( ! is_object( $this->_value ) )
		{
			if ( is_string( $this->_value ) )
				$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::String );
			else if ( is_int( $this->_value ) )
				$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Integer );
			else if ( is_double( $this->_value ) )
				$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Double );
			else if ( is_numeric( $this->_value ) )
				$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Int );
			else if ( is_bool( $this->_value ) )
				$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Boolean );
			else if ( $this->_value instanceof FalseValue || $this->_value instanceof TrueValue )
				$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Boolean );
			// else if ( $this->_value is Int64)
			// 	$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Long );
			// else if ( $this->_value is Single)
			// 	$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Float );
			// else if ( $this->_value is Int16)
			// 	$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Short );
			// else if ( $this->_value is UInt16)
			// 	$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::UnsignedShort );
			// else if ( $this->_value is UInt32)
			// 	$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::UnsignedInt );
			// else if ( $this->_value is UInt64)
			// 	$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::UnsignedLong );
			// else if ( $this->_value is SByte)
			// 	$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Byte );
			// else if ( $this->_value is Byte)
			// 	$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::UnsignedByte );
		}
		else if ( $this->_value instanceof TrueValue || $this->_value instanceof FalseValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Boolean );
		else if ( $this->_value instanceof DecimalValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Decimal );
		else if ( $this->_value instanceof UntypedAtomic::$CLASSNAME )
			$this->_xmlType = XmlSchema::$UntypedAtomic;
		else if ( $this->_value instanceof Integer::$CLASSNAME )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Integer );
		else if ( $this->_value instanceof DateTimeValue::$CLASSNAME )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::DateTime );
		else if ( $this->_value instanceof DateValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Date );
		else if ( $this->_value instanceof TimeValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Time );
		else if ( $this->_value instanceof DayTimeDurationValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::DayTimeDuration );
		else if ( $this->_value instanceof YearMonthDurationValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::YearMonthDuration );
		else if ( $this->_value instanceof DurationValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Duration );
		else if ( $this->_value instanceof GYearMonthValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::GYearMonth );
		else if ( $this->_value instanceof GYearValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::GYear );
		else if ( $this->_value instanceof GDayValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::GDay );
		else if ( $this->_value instanceof GMonthValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::GMonth );
		else if ( $this->_value instanceof GMonthDayValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::GMonthDay );
		else if ( $this->_value instanceof QNameValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::QName );
		else if ( $this->_value instanceof AnyUriValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::AnyUri );
		else if ( $this->_value instanceof HexBinaryValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::HexBinary );
		else if ( $this->_value instanceof Base64BinaryValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Base64Binary );
		else if ( $this->_value instanceof NMTOKENSValue )
			$this->_xmlType = XmlSchema::$NMTOKENS;
		else if ( $this->_value instanceof IDREFSValue )
			$this->_xmlType = XmlSchema::$IDREFS;
		else if ( $this->_value instanceof ENTITIESValue )
			$this->_xmlType = XmlSchema::$ENTITIES;
		else if ( $this->_value instanceof NotationValue )
			$this->_xmlType = DOMSchemaType::GetBuiltInSimpleTypeByTypeCode( XmlTypeCode::Notation );
		else
		{
			$typeName = get_class( $this->_value );
			throw new ArgumentException("Unrecognized value type in XPath2Item instance ($typeName)" );
		}
	}

	/**
	 * ToStringFromValue
	 * @return string
	 */
	public function ToStringFromValue()
	{
		if ( is_null( $this->_value ) )
		{
			return "";
		}

		// return $this->getValue();

		switch ( $this->_xmlType->TypeCode )
		{
			case XmlTypeCode::String:

				return $this->_value . "";

			case XmlTypeCode::Double:

				if ( is_nan( $this->_value ) )
				{
					return "NaN";
				}

				if ( is_infinite( $this->_value ) )
				{
					return $this->_value > 0 ? INF . "" : -INF . "";
				}

				$maxDigits = 17;
				if ( preg_match( DecimalValue::Pattern, $this->_value, $matches ) )
				{
					if ( ! isset( $matches['exponent'] ) )
					{
						$maxDigits = min( @strlen( $matches['digits'] ) + ( isset( $matches['decimals'] ) ? @strlen( $matches['decimals'] ) : 0 ), $maxDigits );
					}
				}

				return str_replace( "+", "", sprintf( "%.{$maxDigits}G", $this->_value ) );

			case XmlTypeCode::Float:

				if ( is_nan( $this->_value ) )
				{
					return "NaN";
				}

				if ( is_infinite( $this->_value ) )
				{
					return $this->_value > 0 ? INF . "" : -INF . "";
				}

				$maxDigits = 8;
				if ( preg_match( DecimalValue::Pattern, $this->_value, $matches ) )
				{
					$maxDigits = min( @strlen( $matches['digits'] ) + ( isset( $matches['decimals'] ) ? @strlen( $matches['decimals'] ) : 0 ), $maxDigits );
				}

				return str_replace( "+", "", sprintf( "%.{$maxDigits}G", $this->_value ) );

		}

		return XPath2Convert::ToString( $this->_value );
	}

	/**
	 * setRawValue
	 * @param object $value
	 */
	public function setRawValue( $value )
	{
		if ( is_null( $value ) )
			$this->_value = CoreFuncs::$False;
		else
			$this->_value = $value;

		$this->_xmlType = null;
	}

	/**
	 * getIsNode
	 * @return bool $IsNode
	 */
	public function getIsNode()
	{
		return false;
	}

	/**
	 * getTypedValue
	 * @return object
	 */
	public function getTypedValue()
	{
		if ( $this->_value instanceof IXmlSchemaType )
		{
			return $this->_value;
			// return $this->_value->getValue();
		}
		else
		{
			return $this->_value;
		}
	}

	/**
	 * getValue
	 * @return string
	 */
	public function getValue()
	{
		// return $this->_value;

		switch ( $this->_xmlType->TypeCode )
		{
			case XmlTypeCode::String:

				return $this->_value;

			case XmlTypeCode::Double:

				if ( is_infinite( $this->_value ) )
				{
					return $this->_value > 0 ? INF : -INF;
				}

				return $this->_value;

			case XmlTypeCode::Float:

				if ( is_infinite( $this->_value ) )
				{
					return $this->_value > 0 ? INF : -INF;
				}

				return $this->_value;

		}

		return XPath2Convert::ToString( $this->_value );
	}

	/**
	 * getValue
	 * @return string
	 */
	public function getValueOld()
	{
		return $this->_value;

		switch ( $this->_xmlType->TypeCode )
		{
			case XmlTypeCode::String:

				return $this->_value;

			case XmlTypeCode::Double:

				if ( is_infinite( $this->_value ) )
				{
					return $this->_value > 0 ? INF : -INF;
				}

				// Can't do this because some conformance suite tests fail
				// as they expect a specifically formatted number.
				if ( ! is_string( $this->_value ) )
				{
					return $this->_value;
				}

				$maxDigits = 17;
				if ( preg_match( DecimalValue::Pattern, $this->_value, $matches ) )
				{
					if ( ! isset( $matches['exponent'] ) )
					{
						$maxDigits = min( @strlen( $matches['digits'] ) + @strlen( $matches['decimals'] ), $maxDigits );
					}
				}

				return str_replace( "+", "", sprintf( "%.{$maxDigits}G", $this->_value ) );

			case XmlTypeCode::Float:

				if ( is_infinite( $this->_value ) )
				{
					return $this->_value > 0 ? INF : -INF;
				}

				$maxDigits = 8;
				if ( preg_match( DecimalValue::Pattern, $this->_value, $matches ) )
				{
					// if ( isset( $matches['exponent'] ) )
					// {
					// 	return str_replace( "+", "", $this->_value );
					// }
					$maxDigits = min( @strlen( $matches['digits'] ) + @strlen( $matches['decimals'] ), $maxDigits );
				}

				return str_replace( "+", "", sprintf( "%.{$maxDigits}G", $this->_value ) );

		}

		return XPath2Convert::ToString( $this->_value );
	}

	/**
	 * GetXPath2ResultType
	 * @return XPath2ResultType
	 */
	public function getXPath2ResultType()
	{
		// Unused = XPath2ResultType::Error
		if ( is_null( $this->_value ) || $this->_value instanceof Undefined )
			return XmlTypeCode::Any;

		if ( $this->_value instanceof  XPath2NodeIterator )
			return XPath2ResultType::NodeSet;

		switch ( $this->_xmlType->TypeCode )
		{
			case XmlTypeCode::Decimal:
			case XmlTypeCode::Float:
			case XmlTypeCode::Double:
			case XmlTypeCode::Integer:
			case XmlTypeCode::NonPositiveInteger:
			case XmlTypeCode::NegativeInteger:
			case XmlTypeCode::Long:
			case XmlTypeCode::Int:
			case XmlTypeCode::Short:
			case XmlTypeCode::Byte:
			case XmlTypeCode::NonNegativeInteger:
			case XmlTypeCode::UnsignedLong:
			case XmlTypeCode::UnsignedInt:
			case XmlTypeCode::UnsignedShort:
			case XmlTypeCode::UnsignedByte:
			case XmlTypeCode::PositiveInteger:
				return XPath2ResultType::Number;

			case XmlTypeCode::String:
			case XmlTypeCode::NormalizedString:
			case XmlTypeCode::Notation:
			case XmlTypeCode::Token:
			case XmlTypeCode::Language:
			case XmlTypeCode::NmToken:
			case XmlTypeCode::Name:
			// case XmlTypeCode::NCName:
			case XmlTypeCode::Id:
			case XmlTypeCode::Idref:
			case XmlTypeCode::Entity:
				return XPath2ResultType::String;

			case XmlTypeCode::Item:
			case XmlTypeCode::Node:
			case XmlTypeCode::Document:
			case XmlTypeCode::Element:
			case XmlTypeCode::Attribute:
			case XmlTypeCode::Namespace_:
			case XmlTypeCode::ProcessingInstruction:
			case XmlTypeCode::Comment:
			case XmlTypeCode::Text:
				return XPath2ResultType::Navigator;

			case XmlTypeCode::Boolean:
				return XPath2ResultType::Boolean;

			case XmlTypeCode::AnyAtomicType:
			case XmlTypeCode::AnyType:
					return XPath2ResultType::Any;

			case XmlTypeCode::DateTime:
			case XmlTypeCode::Time:
			case XmlTypeCode::Date:
			case XmlTypeCode::GYearMonth:
			case XmlTypeCode::GYear:
			case XmlTypeCode::GMonthDay:
			case XmlTypeCode::GDay:
			case XmlTypeCode::GMonth:
						return XPath2ResultType::DateTime;

			case XmlTypeCode::Duration:
			case XmlTypeCode::YearMonthDuration:
			case XmlTypeCode::DayTimeDuration:
				return XPath2ResultType::Duration;

			case XmlTypeCode::AnyUri:
				return XPath2ResultType::AnyUri;

			case XmlTypeCode::QName:
				return XPath2ResultType::QName;

			case XmlTypeCode::None:
			case XmlTypeCode::UntypedAtomic:
			case XmlTypeCode::HexBinary:
			case XmlTypeCode::Base64Binary:
			case XmlTypeCode::NMTOKENS:
			case XmlTypeCode::IDREFS:
			case XmlTypeCode::ENTITIES:

			default:
				return XPath2ResultType::Other;
		}
	}

	/**
	 * ValueAs
	 * @param Type $returnType
	 * @param IXmlNamespaceResolver $nsResolver
	 * @return object
	 */
	public function ValueAs( $returnType, $nsResolver )
	{
		throw new NotImplementedException( "The XPath2Item::ValueAs function is not implemented" );
		// return XPath2Convert::ChangeType( $this->_value, $returnType );
	}

	/**
	 * getValueAsBoolean
	 * @return bool
	 */
	public function getValueAsBoolean()
	{
		return Convert::ToBoolean( $this->_value );
	}

	/**
	 * getValueAsDateTime
	 * @return DateTime
	 */
	public function getValueAsDateTime()
	{
		return Convert::ToDateTime( $this->_value );
	}

	/**
	 * getValueAsDouble
	 * @return double
	 */
	public function getValueAsDouble()
	{
		return Convert::ToDouble( $this->_value );
	}

	/**
	 * getValueAsInt
	 * @return int
	 */
	public function getValueAsInt()
	{
		return Convert::ToInt32( $this->_value );
	}

	/**
	 * getValueAsLong
	 * @return long
	 */
	public function getValueAsLong()
	{
		return Convert::ToInt64( $this->_value );
	}

	/**
	 * getValueType
	 * @return Type
	 */
	public function getValueType()
	{
		if ( $this->_value instanceof IXmlSchemaType )
		{
			return Type::FromValue( $this->_value );
		}
		else
		{
			$qn = $this->_xmlType->QualifiedName;
			$xmlType = $qn->prefix ? "{$qn->prefix}:{$qn->localName}" : "{$qn->localName}";
			return Type::XmlTypeToType( $xmlType );
		}

		return Type::FromValue( $this->_value );
	}

	/**
	 * Get the schema type for this item
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		if ( is_null( $this->_xmlType ) )
			$this->InferXmlType();
		return $this->_xmlType;
	}

	/**
	 * GetTypeCode
	 * @return TypeCode
	 */
	public function getTypeCode()
	{
		return Type::getTypeCodeFromType( $this->getValueType() );
	}

	/**
	 * ToBoolean
	 * @param IFormatProvider $provider
	 * @return bool
	 */
	public function ToBoolean( $provider )
	{
		return Convert::ToBoolean( $this->getTypedValue(), $provider );
	}

	/**
	 * ToByte
	 * @param IFormatProvider $provider
	 * @return byte
	 */
	public function ToByte( $provider )
	{
		return Convert::ToByte( $this->getTypedValue(), $provider );
	}

	/**
	 * ToChar
	 * @param IFormatProvider $provider
	 * @return char
	 */
	public function ToChar( $provider )
	{
		return Convert::ToChar( $this->getTypedValue(), $provider );
	}

	/**
	 * ToDateTime
	 * @param IFormatProvider $provider
	 * @return DateTime
	 */
	public function ToDateTime( $provider )
	{
		return Convert::ToDateTime( $this->getTypedValue(), $provider );
	}

	/**
	 * ToDecimal
	 * @param IFormatProvider $provider
	 * @return DecimalValue
	 */
	public function ToDecimal( $provider )
	{
		return Convert::ToDecimal( $this->getTypedValue(), $provider );
	}

	/**
	 * ToDouble
	 * @param IFormatProvider $provider
	 * @return double
	 */
	public function ToDouble( $provider )
	{
		return Convert::ToDouble( $this->getTypedValue(), $provider );
	}

	/**
	 * ToInt16
	 * @param IFormatProvider $provider
	 * @return short
	 */
	public function ToInt16( $provider )
	{
		return Convert::ToInt16( $this->getTypedValue(), $provider );
	}

	/**
	 * ToInt
	 * @param IFormatProvider $provider
	 * @return int
	 */
	public function ToInt( $provider )
	{
		return Convert::ToInt32( $this->getTypedValue(), $provider );
	}

	/**
	 * ToInt32
	 * @param IFormatProvider $provider
	 * @return int
	 */
	public function ToInt32( $provider )
	{
		return Convert::ToInt32( $this->getTypedValue(), $provider );
	}

	/**
	 * ToInt64
	 * @param IFormatProvider $provider
	 * @return long
	 */
	public function ToInt64( $provider )
	{
		return Convert::ToInt64( $this->getTypedValue(), $provider );
	}

	/**
	 * ToSByte
	 * @param IFormatProvider $provider
	 * @return sbyte
	 */
	public function ToSByte( $provider )
	{
		return Convert::ToSByte( $this->getTypedValue(), $provider );
	}

	/**
	 * ToSingle
	 * @param IFormatProvider $provider
	 * @return float
	 */
	public function ToSingle( $provider )
	{
		return Convert::ToSingle( $this->getTypedValue(), $provider );
	}

	/**
	 * ToString
	 * @param IFormatProvider $provider
	 * @return string
	 */
	public function ToString( $provider = null )
	{
		return $this->ToStringFromValue();
		// return Convert::ToString( $this->getTypedValue(), $provider );
	}

	/**
	 * ToType
	 * @param Type $conversionType
	 * @param IFormatProvider $provider
	 * @return object
	 */
	public function ToType($conversionType, $provider)
	{
		return Convert::ChangeType( $this->getTypedValue(), $conversionType, $provider );
	}

	/**
	 * ToUInt16
	 * @param IFormatProvider $provider
	 * @return ushort
	 */
	public function ToUInt16( $provider )
	{
		return Convert::ToUInt16( $this->getTypedValue(), $provider );
	}

	/**
	 * ToUInt32
	 * @param IFormatProvider $provider
	 * @return uint
	 */
	public function ToUInt32( $provider )
	{
		return Convert::ToUInt32( $this->getTypedValue(), $provider );
	}

	/**
	 * ToUInt64
	 * @param IFormatProvider $provider
	 * @return ulong
	 */
	public function ToUInt64( $provider )
	{
		return Convert::ToUInt64( $this->getTypedValue(), $provider );
	}

	public static function tests()
	{
		$item = XPath2Item::fromValue( TimeValue::Parse( "17:10:11" ) );
		$xmlType = $item->getSchemaType();
		$code = $item->GetTypeCode();
		$type = $item->getValueType();
	}
}

?>
