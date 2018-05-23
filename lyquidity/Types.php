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

namespace lyquidity\XPath2\lyquidity;

use lyquidity\XPath2\Value\AnyUriValue;
use lyquidity\XPath2\Value\Base64BinaryValue;
use lyquidity\XPath2\Value\ENTITIESValue;
use lyquidity\XPath2\Value\GDayValue;
use lyquidity\XPath2\Value\GMonthDayValue;
use lyquidity\XPath2\Value\GMonthValue;
use lyquidity\XPath2\Value\GYearMonthValue;
use lyquidity\XPath2\Value\GYearValue;
use lyquidity\XPath2\Value\HexBinaryValue;
use lyquidity\XPath2\Value\IDREFSValue;
use lyquidity\XPath2\Value\NMTOKENSValue;
use lyquidity\XPath2\Value\QNameValue;
use lyquidity\XPath2\Value\UntypedAtomic;
use lyquidity\XPath2\Value\YearMonthDurationValue;
use lyquidity\XPath2\Value\Integer;
use lyquidity\XPath2\Value\Long;
use lyquidity\XPath2\Value\DateTimeValue;
use lyquidity\XPath2\Value\DateValue;
use lyquidity\XPath2\Value\DayTimeDurationValue;
use lyquidity\XPath2\Value\DecimalValue;
use lyquidity\XPath2\Value\DurationValue;
use lyquidity\xml\TypeCode;

/**
 * This class iplements a list of types
 */
class Types
{

	/**
	 * Static constructor
	 */
	public static function __static()
	{
		Types::$NullType					= Type::FromNameAndTypeCode( Type::null_, TypeCode::Null_ );
		Types::$ArrayType					= Type::FromNameAndTypeCode( Type::assocArray, TypeCode::AssocArray );
		Types::$BoolType					= Type::FromNameAndTypeCode( Type::bool, TypeCode::Boolean );
		Types::$BooleanType					= Types::$BoolType;
		Types::$ByteType					= Type::FromNameAndTypeCode( Type::byte, TypeCode::Int32 );
		Types::$CharType					= Type::FromNameAndTypeCode( Type::char, TypeCode::String );
		Types::$DateTimeIntervalType		= Type::FromNameAndTypeCode( Type::dateTimeInterval, TypeCode::DateTimeInterval );
		Types::$DateTimeValueType			= Type::FromNameAndTypeCode( DateTimeValue::$CLASSNAME, TypeCode::DateTime );
		Types::$DateValueType				= Type::FromNameAndTypeCode( DateValue::$CLASSNAME, TypeCode::DateTime );
		Types::$DayTimeDurationValueType	= Type::FromNameAndTypeCode( DayTimeDurationValue::$CLASSNAME, TypeCode::DateTimeInterval );
		// Types::$DecimalType				= Type::FromNameAndTypeCode( Type::decimal, TypeCode::Float );
		Types::$DecimalType					= Type::FromNameAndTypeCode( DecimalValue::$CLASSNAME, TypeCode::Decimal );
		Types::$DurationValueType			= Type::FromNameAndTypeCode( DurationValue::$CLASSNAME, TypeCode::DateTimeInterval );
		Types::$FloatType					= Type::FromNameAndTypeCode( Type::float, TypeCode::Float );
		Types::$DoubleType					= Type::FromNameAndTypeCode( Type::double, TypeCode::Float );
		// Types::$DoubleType					= Types::$FloatType;
		Types::$Int16Type					= Type::FromNameAndTypeCode( Type::int16, TypeCode::Int32 );
		Types::$Int32Type					= Type::FromNameAndTypeCode( Type::int32, TypeCode::Int32 );
		Types::$Int64Type					= Type::FromNameAndTypeCode( Type::int64, TypeCode::Int32 );
		Types::$IntType						= Types::$Int32Type;
		Types::$IntegerType					= Type::FromNameAndTypeCode( Integer::$CLASSNAME, TypeCode::Float );
		// Types::$LongType					= Types::$Int64Type;
		Types::$LongType					= Type::FromNameAndTypeCode( Long::$CLASSNAME, TypeCode::Int64 );
		Types::$ObjectType					= Type::FromNameAndTypeCode( Type::object, TypeCode::Object );
		Types::$SByteType					= Type::FromNameAndTypeCode( Type::sbyte, TypeCode::Int32 );
		Types::$ShortType					= Types::$Int16Type;
		Types::$StringType					= Type::FromNameAndTypeCode( Type::string, TypeCode::String );
		Types::$TimeValueType				= Type::FromNameAndTypeCode( "lyquidity\XPath2\Value\TimeValue", TypeCode::DateTime );
		Types::$UInt16Type					= Type::FromNameAndTypeCode( Type::uint16, TypeCode::Int32 );
		Types::$UInt32Type					= Type::FromNameAndTypeCode( Type::uint32, TypeCode::Int32 );
		Types::$UInt64Type					= Type::FromNameAndTypeCode( Type::uint64, TypeCode::Int32 );

		Types::$AnyUriValueType				= Type::FromNameAndTypeCode( AnyUriValue::$CLASSNAME, TypeCode::Object );
		Types::$Base64BinaryValueType		= Type::FromNameAndTypeCode( Base64BinaryValue::$CLASSNAME, TypeCode::Object );
		Types::$ENTITIESValueType			= Type::FromNameAndTypeCode( ENTITIESValue::$CLASSNAME, TypeCode::Object );
		Types::$GDayValueType				= Type::FromNameAndTypeCode( GDayValue::$CLASSNAME, TypeCode::DateTime );
		Types::$GMonthValueType				= Type::FromNameAndTypeCode( GMonthValue::$CLASSNAME, TypeCode::DateTime );
		Types::$GMonthDayValueType			= Type::FromNameAndTypeCode( GMonthDayValue::$CLASSNAME, TypeCode::DateTime );
		Types::$GYearMonthValueType			= Type::FromNameAndTypeCode( GYearMonthValue::$CLASSNAME, TypeCode::DateTime );
		Types::$GYearValueType				= Type::FromNameAndTypeCode( GYearValue::$CLASSNAME, TypeCode::DateTime );
		Types::$HexBinaryValueType			= Type::FromNameAndTypeCode( HexBinaryValue::$CLASSNAME, TypeCode::Object );
		Types::$IDREFSValueType				= Type::FromNameAndTypeCode( IDREFSValue::$CLASSNAME, TypeCode::Object );
		Types::$NMTOKENSValueType			= Type::FromNameAndTypeCode( NMTOKENSValue::$CLASSNAME, TypeCode::Object );
		Types::$QNameValueType				= Type::FromNameAndTypeCode( QNameValue::$CLASSNAME, TypeCode::Object );
		Types::$UntypedAtomicType			= Type::FromNameAndTypeCode( UntypedAtomic::$CLASSNAME, TypeCode::Object );
		Types::$YearMonthDurationValueType	= Type::FromNameAndTypeCode( YearMonthDurationValue::$CLASSNAME, TypeCode::DateTimeInterval );
		Types::$UNION						= Type::FromNameAndTypeCode( Type::union, TypeCode::UNION );
	}

	/**
	 * NullType
	 * @var Type
	 */
	public static $NullType;
	/**
	 * ArrayType
	 * @var Type
	 */
	public static $ArrayType;
	/**
	 * ByteType
	 * @var Type
	 */
	public static $ByteType;
	/**
	 * BoolType
	 * @var Type
	 */
	public static $BoolType;
	/**
	 * BooleanType
	 * @var Type
	 */
	public static $BooleanType;
	/**
	 * CharType
	 * @var Type
	 */
	public static $CharType;
	/**
	 * DecimalType
	 * @var Type
	 */
	public static $DecimalType;
	/**
	 * DoubleType
	 * @var Type
	 */
	public static $DoubleType;
	/**
	 * FloatType
	 * @var Type
	 */
	public static $FloatType;
	/**
	 * Int16Type
	 * @var Type
	 */
	public static $Int16Type;
	/**
	 * Int32Type
	 * @var Type
	 */
	public static $Int32Type;
	/**
	 * Int64Type
	 * @var Type
	 */
	public static $Int64Type;
	/**
	 * IntegerType
	 * @var Type
	 */
	public static $IntegerType;
	/**
	 * IntType
	 * @var Type
	 */
	public static $IntType;
	/**
	 * LongType
	 * @var Type
	 */
	public static $LongType;
	/**
	 * SByteType
	 * @var Type
	 */
	public static $SByteType;
	/**
	 * ShortType
	 * @var Type
	 */
	public static $ShortType;
	/**
	 * StringType
	 * @var Type
	 */
	public static $StringType;
	/**
	 * UInt16Type
	 * @var Type
	 */
	public static $UInt16Type;
	/**
	 * UInt32Type
	 * @var Type
	 */
	public static $UInt32Type;
	/**
	 * UInt64Type
	 * @var Type
	 */
	public static $UInt64Type;

	/**
	 * AnyUriValueType
	 * @var Type
	 */
	public static $AnyUriValueType;
	/**
	 * Base64BinaryValueType
	 * @var Type
	 */
	public static $Base64BinaryValueType;
	/**
	 * DateTimeIntervalType
	 * @var Type
	 */
	public static $DateTimeIntervalType;
	/**
	 * DateTimeValueType
	 * @var Type
	 */
	public static $DateTimeValueType;
	/**
	 * DateValueType
	 * @var Type
	 */
	public static $DateValueType;
	/**
	 * DayTimeDurationValueType
	 * @var Type
	 */
	public static $DayTimeDurationValueType;
	/**
	 * DurationValueType
	 * @var Type
	 */
	public static $DurationValueType;
	/**
	 * ENTITIESValueType
	 * @var Type
	 */
	public static $ENTITIESValueType;
	/**
	 * GDayValueType
	 * @var Type
	 */
	public static $GDayValueType;
	/**
	 * GMonthDayValueType
	 * @var Type
	 */
	public static $GMonthDayValueType;
	/**
	 * GMonthValueType
	 * @var Type
	 */
	public static $GMonthValueType;
	/**
	 * GYearMonthValueType
	 * @var Type
	 */
	public static $GYearMonthValueType;
	/**
	 * GYearValueType
	 * @var Type
	 */
	public static $GYearValueType;
	/**
	 * HexBinaryValueType
	 * @var Type
	 */
	public static $HexBinaryValueType;
	/**
	 * IDREFSValueType
	 * @var Type
	 */
	public static $IDREFSValueType;
	/**
	 * NMTOKENSValueType
	 * @var Type
	 */
	public static $NMTOKENSValueType;
	/**
	 * ObjectType
	 * @var Type
	 */
	public static $ObjectType;
	/**
	 * QNameValueType
	 * @var Type
	 */
	public static $QNameValueType;
	/**
	 * TimeValueType
	 * @var Type
	 */
	public static $TimeValueType;
	/**
	 * UntypedAtomicType
	 * @var Type
	 */
	public static $UntypedAtomicType;
	/**
	 * YearMonthDurationValueType
	 * @var Type
	 */
	public static $YearMonthDurationValueType;
	/**
	 * UNION
	 * @var Type
	 */
	public static $UNION;
}

Types::__static();
