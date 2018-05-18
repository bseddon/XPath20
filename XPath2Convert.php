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

use lyquidity\xml\MS\XmlSchemaType;
use lyquidity\xml\MS\XmlNameTable;
use lyquidity\xml\MS\XmlNamespaceManager;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\Value\NotationValue;
use lyquidity\XPath2\Value\DurationValue;
use lyquidity\XPath2\Value\GDayValue;
use lyquidity\XPath2\Value\GMonthDayValue;
use lyquidity\XPath2\Value\GMonthValue;
use lyquidity\XPath2\Value\GYearValue;
use lyquidity\XPath2\Value\GYearMonthValue;
use lyquidity\XPath2\Value\TimeValue;
use lyquidity\XPath2\Value\DateValue;
use lyquidity\XPath2\Value\UntypedAtomic;
use lyquidity\XPath2\Value\Integer;
use lyquidity\XPath2\Value\DayTimeDurationValue;
use lyquidity\XPath2\Value\YearMonthDurationValue;
use lyquidity\XPath2\Value\HexBinaryValue;
use lyquidity\XPath2\Value\ENTITIESValue;
use lyquidity\XPath2\Value\NMTOKENSValue;
use lyquidity\XPath2\Value\IDREFSValue;
use \lyquidity\XPath2\lyquidity\Type;
use lyquidity\XPath2\Value\AnyUriValue;
use lyquidity\XPath2\Value\IXmlConvertable;
use \lyquidity\XPath2\lyquidity\Types;
use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\xml\MS\IXmlSchemaInfo;
use lyquidity\xml\xpath\XPathNodeType;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\MS\XmlTypeCardinality;
use lyquidity\XPath2\Value\DateTimeValue;
use lyquidity\XPath2\Value\QNameValue;
use \lyquidity\XPath2\lyquidity\Convert;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\XPath2\Value\DecimalValue;
use lyquidity\XPath2\Proxy\DecimalProxy;
use lyquidity\XPath2\Proxy\DoubleProxy;
use lyquidity\XPath2\Value\Base64BinaryValue;
use lyquidity\xml\TypeCode;
use lyquidity\XPath2\Value\Long;
use lyquidity\XPath2\Value\TokenValue;
use lyquidity\XPath2\Value\IDValue;
use lyquidity\XPath2\Value\ENTITYValue;
use lyquidity\XPath2\Value\LanguageValue;
use lyquidity\XPath2\Value\NameValue;
use lyquidity\XPath2\Value\NormalizedStringValue;
use lyquidity\XPath2\Value\NMTOKENValue;
use lyquidity\XPath2\Value\IDREFValue;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\xml\schema\SchemaTypes;
use lyquidity\xml\exceptions\InvalidCastException;
use lyquidity\xml\exceptions\XmlSchemaException;
use lyquidity\xml\exceptions\FormatException;

/**
 * XPath2Convert (public static)
 */
class XPath2Convert
{
	/**
	 * ToString
	 * @param object $value
	 * @return string
	 */
	public static function ToString( $value )
	{
		if ( is_string( $value ) )

			return $value;

		if ( is_null( $value ) )

			return "false";

		if ( is_bool( $value ) )

			return $value ? "true" : "false";

		if ( $value instanceof TrueValue )

			return "true";

		if ( $value instanceof FalseValue )

			return "false";

		if ( $value instanceof Integer )

			return $value->ToString( null );

		if ( $value instanceof DecimalValue )

			return $value->getRound( 18 )->ToString( null );

		if ( is_numeric( $value ) && ( is_int( $value ) || $value == PHP_INT_MIN ) ) // PHP does not seem to detect PHP_INT_MIN as an int

			return $value . "";

		if ( is_numeric( $value ) && is_nan( $value ) )
		{
			return "NaN";
		}

		if ( is_numeric( $value ) && is_double( $value ) )
		{
			// Using sprintf( "%e", $value ) is not good enough because it will
			// only produce number with a maximum of 7 significant digits
			/* if( preg_match( "/^(?<sign>-)?(?<zeros>0\.0*)/", $value, $m ) )
			{
				$zeroes = strlen( $m['zeros'] );
				$sign = strlen( $m['sign'] );
				return ( $sign ? "-" : "" ) . substr( $value, $zeroes + $sign, 1 ) . rtrim( "." . substr( $value, $zeroes + $sign + 1 ), "0." ) . "E-" . ( $zeroes-1 );
			}
			else
			 */
			if( preg_match( "/^(?<sign>-)?(?<digits>\d{7,})(?:\.(?<decimals>\d+))?/", $value, $m ) )
			{
				$zeroes = strlen( $m['digits'] );
				$sign = strlen( $m['sign'] );
				$d = rtrim( "." . substr( $m['digits'], 1 ) . ( isset( $m['decimals'] ) ? $m['decimals'] : "" ), "0." );
				if ( empty( $d ) ) $d = ".0";
				$x = ( $sign ? "-" : "" ) . substr( $value, 0 + $sign, 1 ) . $d . "E" . ( $zeroes - 1 );
				return $x;
			}

			return str_replace( "+", "", $value . "" );
		}

		if ( $value instanceof \DateInterval )
		{
			return ( new DurationValue( $value ) )->ToString();
			// return ( new YearMonthDurationValue( $value ) )->ToString();
			// return ( new DayTimeDurationValue( $value ) )->ToString();
		}

		if ( $value instanceof \DateTime )
		{
			$format = "";
			if ( property_exists( $value, "isDate" ) && $value->isDate ) $format .= "Y-m-d";
			if ( property_exists( $value, "isTime" ) && $value->isTime ) $format .= ( empty( $format ) ? "" :"\T" ) . "H:i:s";
			$format .= ( $value->getTimezone()->getName() == date_default_timezone_get() ? "" : "P" );
			return  $value->format( $format ); // DateTimeValue::fromDate( false, $value ) )->ToString();
		}

		return $value->ToString();
	}

	/**
	 * ChangeType
	 * @param XmlSchemaType $xmlType
	 * @param object $value
	 * @param SequenceType $type
	 * @param XmlNameTable $nameTable
	 * @param XmlNamespaceManager $nsmgr
	 * @return object
	 */
	public static function ChangeType( $xmlType, $value, $type, $nameTable, $nsmgr )
	{
		if ( $type->TypeCode == XmlTypeCode::AnyAtomicType || $xmlType->TypeCode == $type->TypeCode )
		{
			switch ( $xmlType->TypeCode )
			{
				case XmlTypeCode::Decimal:
					$value = $value instanceof XPath2Item ? $value->getValue() : $value;
					if ( $value instanceof DecimalProxy )
					{
						$value = $value->getValue();
					}
					if ( $value instanceof DecimalValue )
					{
						$value = $value->getValue();
					}
				case XmlTypeCode::Double:
				case XmlTypeCode::Float:

					$value = $value instanceof XPath2Item ? $value->getValue() : $value;
					if ( $value instanceof DoubleProxy )
					{
						return $value;
					}

					if ( is_string( $value ) && strtoupper( $value ) == "NAN" )
					{
						$value = NAN;
					}
					else
					{
						if ($xmlType->TypeCode == XmlTypeCode::Double )
						{
							$value = doubleval( $value );
						}
						else if ( $value == INF || $value == -INF )
						{
							$value = XPath2Item::fromValueAndType( $value, $type->SchemaType );
						}
						else
						{
							// Using ::FromFloat will convert 1.2E-3 to a decimal number
							$decimal = DecimalValue::FromFloat( $value );
							$value = XPath2Item::fromValueAndType( $decimal->ToFloat( null ), $type->SchemaType );
						}
					}
					break;
			}
			return $value;
		}
		try
		{
			switch ( $xmlType->TypeCode )
			{
				case XmlTypeCode::String:
				case XmlTypeCode::UntypedAtomic:

					if ( $value instanceof UntypedAtomic )
					{
						$value = $value->getValue();
					}

					switch ( $type->TypeCode )
					{
						case XmlTypeCode::UntypedAtomic:
							return new UntypedAtomic( $value );
						case XmlTypeCode::String:
							return $value; // ->ToString();
						case XmlTypeCode::DateTime:
							return DateTimeValue::Parse( $value );
						case XmlTypeCode::Date:
							return DateValue::Parse( $value );
						case XmlTypeCode::Time:
							return TimeValue::Parse( $value );
						case XmlTypeCode::GYearMonth:
							return GYearMonthValue::Parse( $value );
						case XmlTypeCode::GYear:
							return GYearValue::Parse( $value );
						case XmlTypeCode::GMonth:
							return GMonthValue::Parse( $value );
						case XmlTypeCode::GMonthDay:
							return GMonthDayValue::Parse( $value );
						case XmlTypeCode::GDay:
							return GDayValue::Parse( $value );
						case XmlTypeCode::Duration:
							return DurationValue::Parse( $value );
						case XmlTypeCode::Name:
							return new NameValue( $value );
						case XmlTypeCode::Token:
							return new TokenValue( $value );
						case XmlTypeCode::Language:
							return new LanguageValue( $value );
						case XmlTypeCode::NormalizedString:
							return new NormalizedStringValue( $value );
						case XmlTypeCode::Id:
							return new IDValue( $value );
						case XmlTypeCode::QName:
							if ($xmlType->TypeCode == XmlTypeCode::UntypedAtomic)
								throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
									array(
										SequenceType::WithTypeCodeAndCardinality( $xmlType->TypeCode, XmlTypeCardinality::One ),
										$type
									)
								);
							if ( is_string( $value ) && empty( $value ) )
							{
								throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, $type->ItemType->getTypeName() ) );
							}
							$qname = \lyquidity\xml\qname( is_object( $value ) ? $value->ToString() : $value, $nsmgr->getNamespaces() );
							if ( is_null( $qname ) )
							{
								throw XPath2Exception::withErrorCode( "FONS0004", Resources::FONS0004 );
							}
							return QNameValue::fromQName( $qname );
						case XmlTypeCode::Notation:
							return NotationValue::Parse( $value, $nsmgr );
						case XmlTypeCode::AnyUri:
							return new AnyUriValue( $value instanceof IXmlSchemaType ? $value->getValue() : $value );
						case XmlTypeCode::NCName:

							$value = trim( $value );
							$pattern = "/^" . NameValue::$ncName . "$/u";
							if ( ! preg_match( $pattern, $value, $matches ) )
							{
								throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, "xs:NCName" ) );
							}

							return XPath2Item::fromValueAndType( $value, $type->SchemaType );

						default:

							$text = is_object( $value ) ? $value->ToString() : $value;
							$text = trim( $value );
							// $res = $type->SchemaType->Datatype->ParseValue( $text, $nameTable, $nsmgr );
							$res = $text;

							// Validate the text input
							$error = false;
							switch ( $type->TypeCode )
							{
								case $type->TypeCode == XmlTypeCode::Boolean:
									$error = is_string( $text ) && strlen( $text ) == 0;
									break;

								case $type->TypeCode == XmlTypeCode::NonPositiveInteger:
									$error = ! is_numeric( $text ) || $text + 0 > 0 || is_double( $text + 0 );
									break;

								case $type->TypeCode == XmlTypeCode::NegativeInteger:
									$error = ! is_numeric( $text ) || $text + 0 >= 0 || is_double( $text + 0 );
									break;

								case $type->TypeCode == XmlTypeCode::PositiveInteger:
									$error = ! is_numeric( $text ) || $text + 0 <= 0 || is_double( $text + 0 );
									break;

								case $type->TypeCode == XmlTypeCode::UnsignedByte:
									$error = ! is_numeric( $text ) || $text + 0 < 0 || is_double( $text + 0 );
									break;

								case $type->TypeCode == XmlTypeCode::UnsignedShort:
									$error = ! is_numeric( $text ) || $text + 0 < 0 || is_double( $text + 0 );
									break;

								case $type->TypeCode == XmlTypeCode::UnsignedInt:
									$error = ! is_numeric( $text ) || $text + 0 < 0 || is_double( $text + 0 );
									break;

								case $type->TypeCode == XmlTypeCode::UnsignedLong:
									$error = ! is_numeric( $text ) || $text + 0 < 0;
									if ( ! $error )
									{
										// The long value can be bigger than PHP is able to cope with.
										// A large unsigned number will appear like a double in PHP
										$error = DecimalValue::FromValue( $text )->CompareTo( "18446744073709551615" ) == 1;
									}
									break;

								case $type->TypeCode == XmlTypeCode::NonNegativeInteger:
									$error = ! is_numeric( $text ) || $text + 0 < 0 || is_double( $text + 0 );
									break;

								case $type->TypeCode == XmlTypeCode::Byte:
									$error = ! is_numeric( $text ) || $text + 0 < -128 || $text + 0 > 0x7f || is_double( $text + 0 );
									break;

								case $type->TypeCode == XmlTypeCode::Short:
									$error = ! is_numeric( $text ) || $text + 0 < (int)0xffffffffffff8000 || $text + 0 > (int)0x7fff || is_double( $text + 0 );
									break;

								case $type->TypeCode == XmlTypeCode::Int:
									$error = ! is_numeric( $text ) || $text + 0 < (int)0xffffffff80000000 || $text + 0 > (int)0x7fffffff || is_double( $text + 0 );
									break;

								case $type->TypeCode == XmlTypeCode::Long:
									$error = ! is_numeric( $text ) || is_double( $text + 0 ) ||
											 DecimalValue::FromValue( $text )->CompareTo( (int)0x8000000000000000 ) == -1 ||
											 DecimalValue::FromValue( $text )->CompareTo( (int)0x7fffffffffffffff ) == 1;
									break;
								case XmlTypeCode::Double:
								case XmlTypeCode::Float:

									$error = ! preg_match( "/^((-|\+)?(\d*((?=\.\d)\.\d*)?((?i)E[-\+]?\d+((?=\.\d)\.\d*)?)?)|(-?INF)|NaN)$/", trim( $value ) );
									break;
							}

							if ( $error )
							{
								throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $text, $type->ItemType->getTypeName() ) );
							}

							switch ( $type->TypeCode )
							{
								case XmlTypeCode::Decimal:

									return new DecimalValue( $text );

								case XmlTypeCode::Double:
								case XmlTypeCode::Float:

									if ( is_string( $text ) )
									{
										if ( strtoupper( $text ) == "NAN" )
											return XPath2Item::fromValueAndType( NAN, $type->SchemaType );

										if ( strtoupper( $text ) == "INF" )
											return XPath2Item::fromValueAndType( INF, $type->SchemaType );
											// return INF;

										if ( strtoupper( $text ) == "-INF" )
											return XPath2Item::fromValueAndType( -INF, $type->SchemaType );
											// return -INF;
									}

									if ( is_numeric( $text ) ) return XPath2Item::fromValueAndType( doubleval( $text ), $type->SchemaType );
									throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, $type->ItemType->getTypeName() ) );
									break;

								case XmlTypeCode::Boolean:
									$result = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
									if ( is_null( $result ) )
									{
										throw new InvalidCastException("Cannot convert '$text' to boolean");
									}
									return $result ? CoreFuncs::$True : CoreFuncs::$False;
									// return $result;
								case XmlTypeCode::Integer:
								case XmlTypeCode::PositiveInteger:
								case XmlTypeCode::NegativeInteger:
								case XmlTypeCode::NonPositiveInteger:
								case XmlTypeCode::NonNegativeInteger:
								Case XmlTypeCode::Int:
								case XmlTypeCode::Short:
								case XmlTypeCode::Byte:
								case XmlTypeCode::UnsignedByte:
								case XmlTypeCode::UnsignedInt:
								case XmlTypeCode::UnsignedShort:
									return Integer::FromValue( $res /* Convert::ToDecimal( $res ) */ );

								case XmlTypeCode::Long:
								case XmlTypeCode::UnsignedLong:
									return Long::FromValue( $res, $type->TypeCode == XmlTypeCode::UnsignedLong );

								case XmlTypeCode::DayTimeDuration:
									return DayTimeDurationValue::Parse( $res );

								case XmlTypeCode::YearMonthDuration:
									return YearMonthDurationValue::Parse( $res );

								case XmlTypeCode::HexBinary:
									return HexBinaryValue::fromString( $res );

								case XmlTypeCode::Base64Binary:
									if ( SchemaTypes::endsWith( $res, "==" ) && ( strlen( $res ) < 3 || strpos( "AQgw", $res[ strlen( $res ) -3 ] ) == -1) )
										throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $res, $type->ItemType->getTypeName() ) );
									return Base64BinaryValue::fromString( $res );

								case XmlTypeCode::Idref:
									if ( $type->SchemaType == XmlSchema::$IDREF )
										return new IDREFValue( $res );
									if ( $type->SchemaType == XmlSchema::$IDREFS )
										return new IDREFSValue( $res );
									return $res;
								case XmlTypeCode::NmToken:
									if ( $type->SchemaType == XmlSchema::$NMTOKEN )
										return new NMTOKENValue( $res );
									if ( $type->SchemaType == XmlSchema::$NMTOKENS )
										return new NMTOKENSValue( $res );
									return $res;
								case XmlTypeCode::Entity:
									if ( $type->SchemaType->Name == "ENTITY" )
										return new ENTITYValue( $res );
									if ( $type->SchemaType == XmlSchema::$ENTITIES )
										return new ENTITIESValue( $res );
									return $res;

								default:
									return $res;
							}
					}

				case XmlTypeCode::Boolean:

					$result = ( is_bool( $value ) && $value ) || ( is_object( $value ) && $value instanceof TrueValue ) ? 1 : 0;

					// Validate the text input
					$error = false;
					switch ( $type->TypeCode)
					{
						case XmlTypeCode::NonPositiveInteger:
							$error = $result;
							break;

						case XmlTypeCode::NegativeInteger:
							$error = true;
							break;

						case XmlTypeCode::PositiveInteger:
							$error = ! $result;
							break;
					}

					if ( $error )
					{
						throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, $type->ItemType->getTypeName() ) );
					}

					switch ( $type->TypeCode)
					{
						case XmlTypeCode::NonPositiveInteger:
						case XmlTypeCode::NegativeInteger:
						case XmlTypeCode::Integer:
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
							return Integer::FromValue( $result );
							// return XPath2Convert::ChangeObjectType( $value, $type->ItemType );

						case XmlTypeCode::Decimal:
							return DecimalValue::FromValue( $result );

						case XmlTypeCode::Double:
						case XmlTypeCode::Float:
							return $result + 0.0;

						case XmlTypeCode::String:
							return $result ? "true" : "false";

						case XmlTypeCode::UntypedAtomic:
							return new UntypedAtomic( XPath2Convert::ToString( $value ) );

						case XmlTypeCode::Language:

							return XPath2Item::fromValueAndType( $value->ToString( null ), $type->SchemaType );
					}

					break;

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
				case XmlTypeCode::Decimal:
				case XmlTypeCode::Float:
				case XmlTypeCode::Double:

					if ( $value instanceof XPath2Item )
					{
						$value = $value->getTypedValue();
					}

					if ( $value instanceof IXmlSchemaType ) $value = $value->getValue();

					switch ( $type->TypeCode )
					{
						case XmlTypeCode::String:

							return XPath2Convert::ToString( $value );

						case XmlTypeCode::UntypedAtomic:

							return new UntypedAtomic( Convert::ToString( $value ) );

						case XmlTypeCode::Boolean:

							return CoreFuncs::BooleanValue( $value );

						case XmlTypeCode::Byte:
						case XmlTypeCode::Integer:
						case XmlTypeCode::Int:
						case XmlTypeCode::Long:
						case XmlTypeCode::NegativeInteger:
						case XmlTypeCode::NonNegativeInteger:
						case XmlTypeCode::NonPositiveInteger:
						case XmlTypeCode::PositiveInteger:
						case XmlTypeCode::Short:
						case XmlTypeCode::UnsignedByte:
						case XmlTypeCode::UnsignedInt:
						case XmlTypeCode::UnsignedLong:
						case XmlTypeCode::UnsignedShort:

							if ( $value instanceof XPath2Item )
							{
								$value = $value->getTypedValue();
							}

							if ( is_numeric( $value ) )
							{
								if ( is_infinite( $value ) || is_nan( $value ) )
								{
									throw XPath2Exception::withErrorCodeAndParams( "FOCA0002", Resources::FOCA0002, array( $value, "Integer" ) );
								}

								if ( $value > PHP_INT_MAX || $value < PHP_INT_MIN )
								{
									throw XPath2Exception::withErrorCodeAndParams( "FOCA0003", Resources::FOCA0003, array( $value, "Integer" ) );
								}
							}

							return XPath2Item::fromValueAndType( Integer::FromValue( Convert::ToInt( $value ) ), $type->SchemaType );

						case XmlTypeCode::Double:

							return Convert::ToDouble( $value, null );

						case XmlTypeCode::Float:

							switch ( $xmlType->TypeCode )
							{
								case XmlTypeCode::Byte:
								case XmlTypeCode::Integer:
								case XmlTypeCode::Int:
								case XmlTypeCode::Long:
								case XmlTypeCode::NegativeInteger:
								case XmlTypeCode::NonNegativeInteger:
								case XmlTypeCode::NonPositiveInteger:
								case XmlTypeCode::PositiveInteger:
								case XmlTypeCode::Short:
								case XmlTypeCode::UnsignedByte:
								case XmlTypeCode::UnsignedInt:
								case XmlTypeCode::UnsignedLong:
								case XmlTypeCode::UnsignedShort:

									return XPath2Item::fromValueAndType( is_numeric( $value ) ? $value + 0.0 : $value, $type->SchemaType );
							}

							if ( preg_match( "/^(?<sign>[+-])?INF/i", $value, $matches ) )
							{
								return ! empty( $matches['sign'] ) && $matches['sign'] == "-" ? -INF : INF;
							}

							if ( is_numeric( $value ) && is_nan( $value ) )
							{
								return $value;
							}

							// Using ::FromFloat will convert 1.2E-3 to a decimal number
							$decimal = DecimalValue::FromFloat( $value );
							return XPath2Item::fromValueAndType( $decimal->ToFloat( null ), $type->SchemaType );

						case XmlTypeCode::Decimal:
							if ( $xmlType->TypeCode == XmlTypeCode::Decimal ) return $value;
							if ( $xmlType->TypeCode == XmlTypeCode::Double ||
								 $xmlType->TypeCode == XmlTypeCode::Float
							)
								return DecimalValue::FromFloat( $value );

							// if ( $xmlType->TypeCode == XmlTypeCode::Integer || $xmlType->TypeCode == XmlTypeCode::Int )
							return new DecimalValue( $value );

						case XmlTypeCode::AnyUri:
							throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
								array(
									SequenceType::WithTypeCodeAndCardinality( $xmlType->TypeCode, XmlTypeCardinality::One ),
									$type
								)
							);
						case XmlTypeCode::YearMonthDuration:
						case XmlTypeCode::DayTimeDuration:
						case XmlTypeCode::Duration:
						case XmlTypeCode::DateTime:
						case XmlTypeCode::Time:
						case XmlTypeCode::Date:
						case XmlTypeCode::GDay:
						case XmlTypeCode::GMonth:
						case XmlTypeCode::GMonthDay:
						case XmlTypeCode::GYear:
						case XmlTypeCode::GYearMonth:
						case XmlTypeCode::Base64Binary:
						case XmlTypeCode::HexBinary:
						case XmlTypeCode::QName:

							throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( $value, "Duration" ) );

						case XmlTypeCode::Language:

							throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001,
								array(
									$value,
									"xs:language"
								)
							);

							// return XPath2Item::fromValueAndType( $value . "", $type->SchemaType );

						default:
							return XPath2Convert::ChangeObjectType( $value, $type->ItemType );
					}

				case XmlTypeCode::DateTime:
				case XmlTypeCode::Date:
				case XmlTypeCode::Time:
				case XmlTypeCode::GDay:
				case XmlTypeCode::GMonth:
				case XmlTypeCode::GMonthDay:
				case XmlTypeCode::GYear:
				case XmlTypeCode::GYearMonth:

					if ( $type->TypeCode == $xmlType->TypeCode ) return $value;

					switch ( $type->TypeCode )
					{
						case XmlTypeCode::DateTime:

							if ( in_array( $xmlType->TypeCode, array( XmlTypeCode::Date, XmlTypeCode::DateTime ) ) )
							{
								return $value instanceof \DateTime
									? DateTimeValue::fromDate( false, $value )
									: DateTimeValue::fromDate( $value->S, $value->getValue(), ! $value->IsLocal );
							}

							break;

						case XmlTypeCode::Date:

							if ( in_array( $xmlType->TypeCode, array( XmlTypeCode::Date, XmlTypeCode::DateTime ) ) )
							{
								return $value instanceof \DateTime
									? DateValue::fromDate( false, $value )
									: DateValue::fromDate( $value->S, $value->getValue(), ! $value->IsLocal );
							}

							break;

						case XmlTypeCode::Time:

							if ( in_array( $xmlType->TypeCode, array( XmlTypeCode::Time, XmlTypeCode::DateTime ) ) )
							// if ( $xmlType->TypeCode != XmlTypeCode::Date )
							{
								return $value instanceof \DateTime
									? new TimeValue( $value )
									: new TimeValue( $value->getValue(), ! $value->IsLocal );
							}

							break;

						case XmlTypeCode::GYear:

							if ( in_array( $xmlType->TypeCode, array( XmlTypeCode::Date, XmlTypeCode::DateTime ) ) )
							// if ( $xmlType->TypeCode != XmlTypeCode::Time )
							{
								return new GYearValue( $value->S, $value->getValue() );
							}

							break;

						case XmlTypeCode::GYearMonth:

							if ( in_array( $xmlType->TypeCode, array( XmlTypeCode::Date, XmlTypeCode::DateTime ) ) )
							// if ( $xmlType->TypeCode != XmlTypeCode::Time )
							{
								return new GYearMonthValue( $value->S, $value->getValue() );
							}

							break;

						case XmlTypeCode::GDay:

							if ( in_array( $xmlType->TypeCode, array( XmlTypeCode::Date, XmlTypeCode::DateTime ) ) )
							// if ( $xmlType->TypeCode != XmlTypeCode::Time )
							{
								return new GDayValue( $value->getValue() );
							}

							break;

						case XmlTypeCode::GMonth:

							if ( in_array( $xmlType->TypeCode, array( XmlTypeCode::Date, XmlTypeCode::DateTime ) ) )
							// if ( $xmlType->TypeCode != XmlTypeCode::Time )
							{
								return new GMonthValue( $value->getValue() );
							}

							break;

						case XmlTypeCode::GMonthDay:

							if ( in_array( $xmlType->TypeCode, array( XmlTypeCode::Date, XmlTypeCode::DateTime ) ) )
							// if ( $xmlType->TypeCode != XmlTypeCode::Time )
							{
								return new GMonthDayValue( $value->getValue() );
							}

							break;

						case XmlTypeCode::UntypedAtomic:

							return new UntypedAtomic( $value->ToString( null ) );

						case XmlTypeCode::String:

							return $value->ToString( null );

						case XmlTypeCode::Language:

							throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001,
								array(
									$value,
									"xs:language"
								)
							);
					}

					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
						array(
							SequenceType::WithTypeCodeAndCardinality( $xmlType->TypeCode, XmlTypeCardinality::One ),
							$type
						)
					);

				case XmlTypeCode::Duration:
				case XmlTypeCode::DayTimeDuration:
				case XmlTypeCode::YearMonthDuration:

					switch ( $type->TypeCode )
					{
						case XmlTypeCode::DayTimeDuration:
							return DayTimeDurationValue::FromDuration( $value );

						case XmlTypeCode::YearMonthDuration:
							return YearMonthDurationValue::FromDuration( $value );

						case XmlTypeCode::Duration:
							return new DurationValue( $value->getValue() );

						case XmlTypeCode::UntypedAtomic:
							return new UntypedAtomic( $value->ToString( null ) );

						case XmlTypeCode::String:
							return ( $value->ToString( null ) );

						case XmlTypeCode::Language:

							throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001,
								array(
									$value,
									"xs:language"
								)
							);

					}

					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
						array(
							SequenceType::WithTypeCodeAndCardinality( $xmlType->TypeCode, XmlTypeCardinality::One ),
							$type
						)
					);

				case XmlTypeCode::Base64Binary:

					if ( $type->TypeCode == $xmlType->TypeCode ) return $value;

					switch ( $type->TypeCode )
					{
						case XmlTypeCode::String:

							return $value->ToString();

						case XmlTypeCode::UntypedAtomic:

							return new UntypedAtomic( $value->ToString() );

						case XmlTypeCode::HexBinary:

							return new HexBinaryValue( $value->getValue() );

						case XmlTypeCode::Language:

							throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001,
								array(
									$value,
									"xs:language"
								)
							);
					}

					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
						array(
							SequenceType::WithTypeCodeAndCardinality( $xmlType->TypeCode, XmlTypeCardinality::One ),
							$type
						)
					);

					break;

				case XmlTypeCode::HexBinary:

					if ( $type->TypeCode == $xmlType->TypeCode ) return $value;

					switch ( $type->TypeCode )
					{
						case XmlTypeCode::String:

							return $value->ToString();

						case XmlTypeCode::UntypedAtomic:

							return new UntypedAtomic( $value->ToString() );

						case XmlTypeCode::Base64Binary:

							return new Base64BinaryValue( $value->getValue() );

						case XmlTypeCode::Language:

							throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001,
								array(
									$value,
									"xs:language"
								)
							);
					}

					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
						array(
							SequenceType::WithTypeCodeAndCardinality( $xmlType->TypeCode, XmlTypeCardinality::One ),
							$type
						)
					);

					break;

				case XmlTypeCode::AnyUri:

					if ( $type->TypeCode == $xmlType->TypeCode ) return $value;

					switch ( $type->TypeCode )
					{
						case XmlTypeCode::String:

							return $value->ToString();

						case XmlTypeCode::UntypedAtomic:

							return new UntypedAtomic( $value->ToString() );

					}

					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
						array(
							SequenceType::WithTypeCodeAndCardinality( $xmlType->TypeCode, XmlTypeCardinality::One ),
							$type
						)
					);

					break;

				case XmlTypeCode::QName:

					if ( $type->TypeCode == $xmlType->TypeCode ) return $value;

					switch ( $type->TypeCode )
					{
						case XmlTypeCode::String:

							return $value->ToString( null );

						case XmlTypeCode::UntypedAtomic:

							return new UntypedAtomic( $value->ToString( null ) );

						// case XmlTypeCode::Base64Binary:
						// case XmlTypeCode::HexBinary:
						case XmlTypeCode::Language:

							throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001,
								array(
									$value,
									"xs:language"
								)
							);

					}

					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
						array(
							SequenceType::WithTypeCodeAndCardinality( $xmlType->TypeCode, XmlTypeCardinality::One ),
							$type
						)
					);

				default:

					$convertable = $value instanceof IXmlConvertable;
					if ( $convertable )
					{
						/**
						 * @var IXmlConvertable $convert
						 */
						$convert = $value;
						return $convert->ValueAs( $type, $nsmgr );
					}
					if ( $type->TypeCode == XmlTypeCode::String)
						return XPath2Convert::ToString( $value);
					if ( $type->TypeCode == XmlTypeCode::UntypedAtomic)
						return new UntypedAtomic( XPath2Convert::ToString( $value ) );

					// Getting here is a dead end
					return $type->SchemaType->Datatype->ChangeType1( $value, $type->ValueType );
			}
		}
		catch ( XmlSchemaException $ex )
		{
			throw XPath2Exception::asDefault( $ex->getMessage(), $ex);
		}
		catch ( \InvalidArgumentException $ex )
		{
			throw XPath2Exception::asDefault( $ex->getMessage(), $ex);
		}
		catch ( InvalidCastException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001,
					array(
						SequenceType::WithTypeCodeAndCardinality( $xmlType->TypeCode, XmlTypeCardinality::One ),
						$type
					)
				);
		}
		catch ( FormatException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					SequenceType::WithTypeCodeAndCardinality( $xmlType->TypeCode, XmlTypeCardinality::One ),
					$type
				)
			);
		}
		throw XPath2Exception::withErrorCodeAndParams("XPTY0004", Resources::XPTY0004,
			array(
				SequenceType::WithTypeCodeAndCardinality( $xmlType->TypeCode, XmlTypeCardinality::One ),
				$type
			)
		);
	}

	/**
	 * ChangeObjectType
	 * @param object $value
	 * @param Type $returnType
	 * @return object
	 */
	public static function ChangeObjectType( $value, $returnType )
	{
		try
		{
			if ( $returnType->getTypeName() == Type::integer )
				return Integer::ToInteger( $value );
			if ( $returnType->getTypeName() == Type::object )
				return $value;
			return Convert::ChangeType( $value, $returnType, null );
		}
		catch ( FormatException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001,
				array(
					$value,
					SequenceType::WithTypeCodeAndCardinality(SequenceType::GetXmlTypeCodeFromType( $returnType ), XmlTypeCardinality::One )
				)
			);
		}
		catch ( \OverflowException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FOAR0002", Resources::FOAR0002,
				array(
					$value,
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromType( $returnType), XmlTypeCardinality::One )
				)
			);
		}
	}

	/**
	 * ValueAs
	 * @param object $value
	 * @param SequenceType $type
	 * @param XmlNameTable $nameTable
	 * @param XmlNamespaceManager $nsmgr
	 * @return object
	 */
	public static function ValueAs( $value, $type, $nameTable, $nsmgr )
	{
		if ( $value instanceof Undefined )
		{
			return $value;
		}
		if ( is_null( $value ) )
		{
			switch ( $type->TypeCode)
			{
				case XmlTypeCode::Date:
				case XmlTypeCode::DateTime:
					return DateTimeValue::today();
					break;

				case XmlTypeCode::Time:
					return TimeValue::Parse("00:00:00");
					break;
			}
			$value = CoreFuncs::$False;
		}
		if ( $type->TypeCode == XmlTypeCode::None)
			throw XPath2Exception::withErrorCodeAndParams("XPTY0004", Resources::XPTY0004,
				array(
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value ), XmlTypeCardinality::One ),
					"empty-sequence()"
				)
			);
		if ( $value instanceof XPath2Item )
		{
			/**
			 * @var XPath2Item $x
			 */
			$value = $value->getTypedValue();
		}

		$valueType = Type::FromValue( $value );
		// if ( $valueType->getTypeCode() != $type->ItemType->getTypeCode() )
		if ( $valueType->getTypeName() != $type->ItemType->getTypeName() )
		{
			if ( $value instanceof UntypedAtomic )
			{
				return XPath2Convert::ChangeType( XmlSchema::$UntypedAtomic, $value, $type, $nameTable, $nsmgr );
			}

			switch ( $type->TypeCode)
			{
				case XmlTypeCode::String:
					if ( is_object( $value ) )
					{
						// BMS 2018-02-13 Added conversion to string because of test 44210 V-01 which returns a Token
						return (string)$value;
					}
					else
					{
						return $value;
					}

					break;

				case XmlTypeCode::Decimal:
					return Convert::ToDecimal( $value );

				case XmlTypeCode::Float:
				case XmlTypeCode::Double:
					return Convert::ToDouble( $value );

				case XmlTypeCode::Integer:
					if ( Integer::IsDerivedSubtype( $value) )
						return Integer::ToInteger( $value);
					break;

					case XmlTypeCode::Long:
					case XmlTypeCode::Int:
					if ( is_numeric( $value ) && ! is_double( $value ) )
						return $value;
					if ( is_double( $value ) )
					{
						return round( $value );
					}
					if ( $value instanceof Integer )
					{
						/**
						 * @var Integer $integer
						 */
						$integer = $value;
						return round( $integer->getValue() );
					}
					break;

					case XmlTypeCode::Boolean:

						$result = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
						if ( ! is_null( $result ) )
						{
							return $result ? CoreFuncs::$True : CoreFuncs::$False;
						}

						switch( $valueType->getTypeCode() )
						{
							case TypeCode::Decimal:
							case TypeCode::Double:
							case TypeCode::Float:
							case TypeCode::Int16:
							case TypeCode::Int32:
							case TypeCode::Int64:
							case TypeCode::SByte:
							case TypeCode::Single:
							case TypeCode::UInt16:
							case TypeCode::UInt32:
							case TypeCode::UInt64:
							case TypeCode::String:
								return CoreFuncs::$False;

							case TypeCode::String:
								return empty( $value ) ? CoreFuncs::$False : CoreFuncs::$True;
						}
						break;
			}
			if ( $type->TypeCode == XmlTypeCode::QName && is_string( $value ) )
				return QNameValue::fromNCName( $value, $nsmgr );
			if ( $type->TypeCode == XmlTypeCode::AnyUri && is_string( $value ) )
				return new AnyUriValue( $value );
			if ( $type->TypeCode == XmlTypeCode::String && $value instanceof AnyUriValue )
				return $value->ToString();
			if ( $type->TypeCode == XmlTypeCode::Date && is_string( $value ) )
				return DateValue::Parse( $value );
			if ( $type->TypeCode == XmlTypeCode::DateTime && is_string( $value ) )
				return DateTimeValue::Parse( $value );
			if ( $type->TypeCode == XmlTypeCode::DateTime && $value instanceof DateValue )
				return DateTimeValue::fromDate( $value->S, $value->Value, ! $value->IsLocal );
			if ( $type->TypeCode == XmlTypeCode::Time &&  is_string( $value ) )
				return TimeValue::Parse( $value );
			if ( $type->TypeCode == XmlTypeCode::DayTimeDuration && is_string( $value ) )
				return DayTimeDurationValue::Parse( $value );
			if ( $type->TypeCode == XmlTypeCode::DayTimeDuration && $value instanceof DurationValue )
				return DayTimeDurationValue::FromDuration( $value );
			if ( $type->TypeCode == XmlTypeCode::YearMonthDuration && is_string( $value ) )
				return YearMonthDurationValue::Parse( $value );
			if ( $type->TypeCode == XmlTypeCode::YearMonthDuration && $value instanceof DurationValue )
				return YearMonthDurationValue::FromDuration( $value );
			if ( $type->TypeCode == XmlTypeCode::Duration &&
				( $value instanceof YearMonthDurationValue || $value instanceof DayTimeDurationValue ) )
				return $value;

			throw XPath2Exception::withErrorCodeAndParams( "FORG0006", Resources::FORG0006,
				array(
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value ), XmlTypeCardinality::One ),
					$type->SchemaType->Datatype->TypeCodeName
				)
			);

			// throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
			// 	array(
			// 		SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value ), XmlTypeCardinality::One ),
			// 		$type
			// 	)
			// );
		}
		return $value;
	}

	/**
	 * TreatValueAs
	 * @param object $value
	 * @param SequenceType $type
	 * @return object
	 */
	public static function TreatValueAs( $value, $type)
	{
		if ( $value instanceof Undefined )
		{
			return $value;
		}

		if ( is_null( $value ) )
		{
			$value = CoreFuncs::$False;
		}

		if ( $type->TypeCode == XmlTypeCode::None)
			throw XPath2Exception::withErrorCodeAndParams( "XPDY0050", Resources::XPDY0050,
				array(
					$type,
					"empty-sequence()"
				)
			);

		if ( Type::FromValue( $value ) != $type->ItemType && $type->ItemType != Types::$ObjectType )
		{
			if ( $type->ItemType == Types::$IntegerType )
			{
				return Integer::ToInteger( $value, null );
			}
			else if ( $type->ItemType == Types::$DecimalType )
			{
				return DecimalValue::FromValue( $value );
				return $value + 0.0;
			}
			throw XPath2Exception::withErrorCodeAndParams( "XPDY0050", Resources::XPDY0050,
				array(
					$type,
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value ), XmlTypeCardinality::One ),
				)
			);
		}
		return $value;
	}

	/**
	 * GetTypedValue
	 * @param XPathItem $item
	 * @return object
	 */
	public static function GetTypedValue( $item )
	{
		if ( ! $item instanceof XPathNavigator )
		{
			return $item->getTypedValue();
		}

		/**
		 * @var XPathNavigator $nav
		 */
		$nav = $item;
		/**
		 * @var IXmlSchemaInfo $schemaInfo
		 */
		$schemaInfo = $nav->getSchemaInfo();
		if ( is_null( $schemaInfo ) || is_null( $schemaInfo->getSchemaType() ) )
		{
			switch ( $nav->getNodeType() )
			{
				case XPathNodeType::Comment:
				case XPathNodeType::ProcessingInstruction:
				case XPathNodeType::NamespaceURI:
					return $nav->getTypedValue();
				default:
					return new UntypedAtomic( $nav->getValue() );
			}
		}

		// If it's a complex type then return the text of the element hierarchy
		if ( ! $schemaInfo->getSchemaType()->Datatype->SimpleType )
		{
			return $nav->getValue();
		}

		/**
		 * @var XmlTypeCode $typeCode
		 */
		$typeCode = $schemaInfo->getSchemaType()->TypeCode;
		if ( $typeCode == XmlTypeCode::AnyAtomicType && ! is_null( $schemaInfo->MemberType) )
		{
			$typeCode = $schemaInfo->getMemberType()->TypeCode;
		}

		$unionTypes = null;

		while (true)
		{
			try
			{
				switch ( $typeCode )
				{
					case XmlTypeCode::UntypedAtomic:
						return new UntypedAtomic( $nav->getValue() );
					case XmlTypeCode::Integer:
					case XmlTypeCode::PositiveInteger:
					case XmlTypeCode::NegativeInteger:
					case XmlTypeCode::NonPositiveInteger:
						return Integer::ToInteger( $nav->getValue(), null );
					case XmlTypeCode::Date:
						return DateValue::Parse( $nav->getValue() );
					case XmlTypeCode::DateTime:
						return DateTimeValue::Parse( $nav->getValue() );
					case XmlTypeCode::Time:
						return TimeValue::Parse( $nav->getValue() );
					case XmlTypeCode::Duration:
						return DurationValue::Parse( $nav->getValue() );
					case XmlTypeCode::DayTimeDuration:
						return new DayTimeDurationValue( $nav->getTypedValue() );
					case XmlTypeCode::YearMonthDuration:
						return new YearMonthDurationValue( $nav->getTypedValue() );
					case XmlTypeCode::GDay:
						return GDayValue::Parse( $nav->getValue() );
					case XmlTypeCode::GMonth:
						return GMonthValue::Parse( $nav->getValue() );
					case XmlTypeCode::GMonthDay:
						return GMonthDayValue::Parse( $nav->getValue() );
					case XmlTypeCode::GYear:
						return GYearValue::Parse( $nav->getValue() );
					case XmlTypeCode::GYearMonth:
						return GYearMonthValue::Parse( $nav->getValue() );
					case XmlTypeCode::QName:
					case XmlTypeCode::Notation:

						/**
						 * @var XmlNamespaceManager $nsmgr
						 */
						$nsmgr = new XmlNamespaceManager( $nav->getNameTable() );
						ExtFuncs::ScanLocalNamespaces( $nsmgr, $nav->CloneInstance(), true );
						if ( $schemaInfo->getSchemaType()->TypeCode == XmlTypeCode::Notation )
						{
							return NotationValue::Parse( $nav->getValue(), $nsmgr );
						}
						else
						{
							return QNameValue::fromNCName( trim( $nav->getValue() ), $nsmgr );
						}

					case XmlTypeCode::AnyUri:
						return new AnyUriValue( $nav->getValue() );
					case XmlTypeCode::HexBinary:
						return new HexBinaryValue( $nav->getTypedValue() );
					case XmlTypeCode::Base64Binary:
						return new Base64BinaryValue( $nav->getTypedValue() );
					case XmlTypeCode::Idref:
						if ( $schemaInfo->SchemaType == XmlSchema::$IDREFS )
							return new IDREFSValue( $nav->getTypedValue() );
						return $nav->getTypedValue();

					case XmlTypeCode::NmToken:
						if ( $schemaInfo->SchemaType == XmlSchema::$NMTOKENS )
							return new NMTOKENValue( $nav->getValue() );
						return $nav->getTypedValue() ;

					case XmlTypeCode::NMTOKENS:
						if ( $schemaInfo->SchemaType == XmlSchema::$NMTOKENS )
							return new NMTOKENSValue( explode(" ", $nav->getValue() ) );
						return $nav->getTypedValue() ;

					case XmlTypeCode::Entity:
						if ( $schemaInfo->SchemaType == XmlSchema::$ENTITIES )
							return new ENTITIESValue( $nav->getTypedValue() );
						return $nav->getTypedValue() ;

					case XmlTypeCode::Double:
					case XmlTypeCode::Float:
						if ( strcasecmp( $nav->getValue(), "NaN" ) == 0 )
						{
							return NAN;
						}
						return $nav->getValue() + 0;

					case XmlTypeCode::Decimal:
						return DecimalValue::FromValue( $nav->getValue() );

					case XmlTypeCode::String:
					// BMS 2018-04-02 Added token
					case XmlTypeCode::Token:
						return XPath2Item::fromValueAndType( trim( $nav->getValue() ), XmlSchema::$String );

					case XmlTypeCode::Boolean:
						$bool = filter_var( $nav->getValue(), FILTER_VALIDATE_BOOLEAN );
						return XPath2Item::fromValueAndType( $bool, XmlSchema::$Boolean );

					case XmlTypeCode::UNION:

						// Try the code of each of the simple types in turn
						if ( is_null( $unionTypes ) )
						{
							$unionTypes = SchemaTypes::getInstance()->getSimpleTypesFromUnion( $schemaInfo->getSchemaType()->QualifiedName );
						}

						if ( ! $unionTypes )
						{
							$typeCode = XmlTypeCode::UntypedAtomic;
							break;
						}

						$unionType = reset( $unionTypes );
						$unionTypeKey = key( $unionTypes );
						unset( $unionTypes[ $unionTypeKey ] );

						$typeCode = XmlTypeCode::TypeCodeForXmlType( $unionType );

						break;

					default:
						return new UntypedAtomic( $nav->getValue() );
						// return $nav->getTypedValue() ;
				}
			}
			catch ( \Exception $ex )
			{
				// Force a look at the next union type
				if ( ! is_null( $unionTypes ) )
				{
					if ( $unionTypes )
					{
						$typeCode = XmlTypeCode::UNION;
					}
				}
				else
				{
					throw $ex;
				}
			}
		}
	}

	/**
	 * GetSchemaType
	 * @param XPathItem $item
	 * @return XmlSchemaType
	 */
	public static function GetSchemaType( $item )
	{
		if ( $nav instanceof XPath2Item )
		{
			return $item->getSchemaType();
		}

		if ( $nav instanceof XPathNavigator )
		{
			/**
			 * @var XPathNavigator $nav
			 */
			$nav = $item;
			$xmlType = $nav->getSchemaType();
			if ( is_null( $xmlType ) )
			{
				return XmlSchema::$UntypedAtomic;
			}
			return $xmlType;
		}
		return $item->getSchemaType();
	}

	public static function tests()
	{
		XPath2Convert::ValueAs($value, $type, $nameTable, $nsmgr);
	}
}



?>
