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

namespace lyquidity\XPath2\DOM;

use \lyquidity\xml\MS\XmlReservedNs;
use \lyquidity\xml\MS\XmlSchemaType;
use \lyquidity\xml\MS\XmlTypeCode;
use \lyquidity\xml\QName;

/**
 * XmlSchema (public static)
 */
class XmlSchema
{
	/**
	 * The target namespace passed to the constructor
	 * @var string
	 */
	private $targetNamespace = "";

	/**
	 * Constructor
	 * @param string $targetNamespace
	 * @throws \lyquidity\xml\exceptions\ArgumentNullException If the namespace is empty
	 */
	public function __construct( $targetNamespace )
	{
		if ( empty( $targetNamespace ) ) throw  new \lyquidity\xml\exceptions\ArgumentNullException();

		$this->targetNamespace = $targetNamespace;
	}

	/**
	 * Get the target namespace for this schema
	 * @return string
	 */
	public function getTargetNamespace()
	{
		return $this->targetNamespace;
	}

	/**
	 * AnySimpleType
	 * @var XmlSchemaType $AnySimpleType = XmlSchemaType::GetBuiltInSimpleType( new \lyquidity\xml\qname( "xs", XmlReservedNs::xs, "anySimpleType" ) )
	 */
	public static $AnySimpleType;

	/**
	 * AnyType
	 * @var XmlSchemaType $AnyType = XmlSchemaType::GetBuiltInComplexType( new \lyquidity\xml\qname( "xs", XmlReservedNs::xs, "anyType" ) )
	 */
	public static $AnyType;

	/**
	 * AnyUri
	 * @var XmlSchemaType $AnyUri = XmlSchemaType::GetBuiltInComplexType( new \lyquidity\xml\qname( "xs", XmlReservedNs::xs, "anyUri" ) )
	 */
	public static $AnyUri;

	/**
	 * AnyAtomicType
	 * @var XmlSchemaType $AnyAtomicType = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::AnyAtomicType )
	 */
	public static $AnyAtomicType;

	/**
	 * UntypedAtomic
	 * @var XmlSchemaType $UntypedAtomic = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::UntypedAtomic )
	 */
	public static $UntypedAtomic;

	/**
	 * Boolean
	 * @var XmlSchemaType $Boolean = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::Boolean )
	 */
	public static $Boolean;

	/**
	 * Byte
	 * @var XmlSchemaType $Byte = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::Boolean )
	 */
	public static $Byte;

	/**
	 * Decimal
	 * @var XmlSchemaType $Decimal = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::Decimal )
	 */
	public static $Decimal;

	/**
	 * Double
	 * @var XmlSchemaType $Double = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::Double )
	 */
	public static $Double;

	/**
	 * Float
	 * @var XmlSchemaType $Float = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::Float )
	 */
	public static $Float;

	/**
	 * Integer
	 * @var XmlSchemaType $Integer = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::Integer )
	 */
	public static $Integer;

	/**
	 * Long
	 * @var XmlSchemaType $Long = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::Long )
	 */
	public static $Long;

	/**
	 * Short
	 * @var XmlSchemaType $Short = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::Short )
	 */
	public static $Short;

	/**
	 * UnsignedInt
	 * @var XmlSchemaType $UnsignedInt = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::UnsignedInt )
	 */
	public static $UnsignedInt;

	/**
	 * UnsignedLong
	 * @var XmlSchemaType $UnsignedLong = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::UnsignedLong )
	 */
	public static $UnsignedLong;

	/**
	 * UnsignedShort
	 * @var XmlSchemaType $UnsignedShort = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::UnsignedShort )
	 */
	public static $UnsignedShort;

	/**
	 * DateTime
	 * @var XmlSchemaType $DateTime = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::DateTime )
	 */
	public static $DateTime;

	/**
	 * Date
	 * @var XmlSchemaType $Date = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::Date )
	 */
	public static $Date;

	/**
	 * Time
	 * @var XmlSchemaType $Time = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::Time )
	 */
	public static $Time;

	/**
	 * Duration
	 * @var XmlSchemaType $Duration = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::Duration )
	 */
	public static $Duration;

	/**
	 * YearMonthDuration
	 * @var XmlSchemaType $YearMonthDuration = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::YearMonthDuration )
	 */
	public static $YearMonthDuration;

	/**
	 * DayTimeDuration
	 * @var XmlSchemaType $DayTimeDuration = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::DayTimeDuration )
	 */
	public static $DayTimeDuration;

	/**
	 * GYearMonth
	 * @var XmlSchemaType $GYearMonth = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::GYearMonth )
	 */
	public static $GYearMonth;

	/**
	 * GYear
	 * @var XmlSchemaType $GYear = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::GYear )
	 */
	public static $GYear;

	/**
	 * GDay
	 * @var XmlSchemaType $GDay = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::GDay )
	 */
	public static $GDay;

	/**
	 * GMonth
	 * @var XmlSchemaType $GMonth = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::GMonth )
	 */
	public static $GMonth;

	/**
	 * GMonthDay
	 * @var XmlSchemaType $GMonthDay = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::GMonthDay )
	 */
	public static $GMonthDay;

	/**
	 * QName
	 * @var XmlSchemaType $QName = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::QName )
	 */
	public static $QName;

	/**
	 * HexBinary
	 * @var XmlSchemaType $HexBinary = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::HexBinary )
	 */
	public static $HexBinary;

	/**
	 * @var XmlSchemaType $Base64Binary = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::Base64Binary )
	 */
	public static $Base64Binary;

	/**
	 * token
	 * @var XmlSchemaType $token = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::token )
	 */
	public static $Token;

	/**
	 * NMTOKEN
	 * @var XmlSchemaType $NMTOKEN = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::NmToken )
	 */
	public static $NMTOKEN;

	/**
	 * ID
	 * @var XmlSchemaType $ID = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::Id)
	 */
	public static $ID;

	/**
	 * IDREF
	 * @var XmlSchemaType $IDREF = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::IdRef )
	 */
	public static $IDREF;

	/**
	 * IDREFS
	 * @var XmlSchemaType $IDREFS = XmlSchemaType::GetBuiltInSimpleType( new \lyquidity\xml\qname( "xs", XmlReservedNs::xs, "IDREFS" ) )
	 */
	public static $IDREFS;

	/**
	 * NMTOKENS
	 * @var XmlSchemaType $NMTOKENS = XmlSchemaType::GetBuiltInSimpleType( "xs", new \lyquidity\xml\qname( "xs", XmlReservedNs::xs, "NMTOKENS" ) )
	 */
	public static $NMTOKENS;

	/**
	 * ENTITIES
	 * @var XmlSchemaType $ENTITIES = XmlSchemaType::GetBuiltInSimpleType( "xs", new \lyquidity\xml\qname( "xs", XmlReservedNs::xs, "ENTITIES" ) )
	 */
	public static $ENTITIES;

	/**
	 * ENTITY
	 * @var XmlSchemaType $ENTITY = XmlSchemaType::GetBuiltInSimpleType( "xs", new \lyquidity\xml\qname( "xs", XmlReservedNs::xs, "ENTITY" ) )
	 */
	public static $ENTITY;

	/**
	 * Name
	 * @var XmlSchemaType $Name = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::Name )
	 */
	public static $Name;

	/**
	 * NCName
	 * @var XmlSchemaType $NCName = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::NCName )
	 */
	public static $NCName;

	/**
	 * String
	 * @var XmlSchemaType $String = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::String )
	 */
	public static $String;

	/**
	 * Notation
	 * @var XmlSchemaType $Notation = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::Notation )
	 */
	public static $Notation;

	/**
	 * Language
	 * @var XmlSchemaType $Language = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::Language )
	 */
	public static $Language;

	/**
	 * NormalizedString
	 * @var XmlSchemaType $NormalizedString = XmlSchemaType::GetBuiltInSimpleType( XmlTypeCode::NormalizedString )
	 */
	public static $NormalizedString;

	/**
	 * Static constructor
	 */
	public static function __static()
	{
		// XmlTypeCode::getTypeForCode( XmlTypeCode::Boolean );
		XmlSchema::$AnySimpleType = DOMSchemaType::GetBuiltInSimpleTypeByQName( new \lyquidity\xml\qname( SCHEMA_PREFIX, XmlReservedNs::xs, "anySimpleType" ) );
		XmlSchema::$AnyType = DOMSchemaType::GetBuiltInComplexTypeByQName( new \lyquidity\xml\qname( SCHEMA_PREFIX, XmlReservedNs::xs, "anyType" ) );
		XmlSchema::$AnyUri = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::AnyUri );
		XmlSchema::$AnyAtomicType = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::AnyAtomicType );
		XmlSchema::$UntypedAtomic = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::UntypedAtomic );
		XmlSchema::$Boolean = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Boolean );
		XmlSchema::$Byte = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Byte );
		XmlSchema::$Integer = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Integer );
		XmlSchema::$Decimal = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Decimal );
		XmlSchema::$Double = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Double );
		XmlSchema::$Float = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Float );
		XmlSchema::$Long = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Long );
		XmlSchema::$Short = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Short );
		XmlSchema::$UnsignedLong = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::UnsignedLong );
		XmlSchema::$UnsignedInt = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::UnsignedInt );
		XmlSchema::$UnsignedShort = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::UnsignedShort );
		XmlSchema::$DateTime = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::DateTime );
		XmlSchema::$Date = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Date );
		XmlSchema::$Time = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Time );
		XmlSchema::$Duration = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Duration );
		XmlSchema::$YearMonthDuration = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::YearMonthDuration );
		XmlSchema::$DayTimeDuration = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::DayTimeDuration );
		XmlSchema::$GYearMonth = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::GYearMonth );
		XmlSchema::$GYear = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::GYear );
		XmlSchema::$GDay = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::GDay );
		XmlSchema::$GMonth = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::GMonth );
		XmlSchema::$GMonthDay = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::GMonthDay );
		XmlSchema::$QName = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::QName );
		XmlSchema::$HexBinary = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::HexBinary );
		XmlSchema::$Base64Binary = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Base64Binary );
		XmlSchema::$NMTOKEN = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::NmToken );
		XmlSchema::$IDREF = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Idref );
		XmlSchema::$ID = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Id );
		XmlSchema::$IDREFS = DOMSchemaType::GetBuiltInSimpleTypeByQName( new \lyquidity\xml\qname( SCHEMA_PREFIX, XmlReservedNs::xs, "IDREFS" ) );
		XmlSchema::$NMTOKENS = DOMSchemaType::GetBuiltInSimpleTypeByQName( new \lyquidity\xml\qname( SCHEMA_PREFIX, XmlReservedNs::xs, "NMTOKENS" ) );
		XmlSchema::$ENTITY = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Entity );
		XmlSchema::$ENTITIES = DOMSchemaType::GetBuiltInSimpleTypeByQName( new \lyquidity\xml\qname( SCHEMA_PREFIX, XmlReservedNs::xs, "ENTITIES" ) );
		XmlSchema::$Name = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Name );
		XmlSchema::$NCName = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::NCName );
		XmlSchema::$String = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::String );
		XmlSchema::$Token = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Token );
		XmlSchema::$Notation = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Notation );
		XmlSchema::$Language = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::Language );
		XmlSchema::$NormalizedString = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::NormalizedString );
	}

	/**
	 * Unit tests
	 */
	public static function Test()
	{
		$AnySimpleType = XmlSchema::$AnySimpleType;
		$AnyType = XmlSchema::$AnyType;
		$AnyAtomicType = XmlSchema::$AnyAtomicType;
		$UntypedAtomic = XmlSchema::$UntypedAtomic;
		$Integer = XmlSchema::$Integer;
		$DateTime = XmlSchema::$DateTime;
		$Date = XmlSchema::$Date;
		$Time = XmlSchema::$Time;
		$Duration = XmlSchema::$Duration;
		$YearMonthDuration = XmlSchema::$YearMonthDuration;
		$DayTimeDuration = XmlSchema::$DayTimeDuration;
		$GYearMonth = XmlSchema::$GYearMonth;
		$GYear = XmlSchema::$GYear;
		$GDay = XmlSchema::$GDay;
		$GMonth = XmlSchema::$GMonth;
		$GMonthDay = XmlSchema::$GMonthDay;
		$QName = XmlSchema::$QName;
		$HexBinary = XmlSchema::$HexBinary;
		$Base64Binary = XmlSchema::$Base64Binary;
		$ID = XmlSchema::$ID;
		$IDREF = XmlSchema::$IDREF;
		$NMTOKEN = XmlSchema::$NMTOKEN;
		$IDREFS = XmlSchema::$IDREFS;
		$NMTOKENS = XmlSchema::$NMTOKENS;
		$ENTITIES = XmlSchema::$ENTITIES;
		$Name = XmlSchema::$Name;
	}
}

XmlSchema::__static();

