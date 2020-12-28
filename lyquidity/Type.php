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

use lyquidity\XPath2\FalseValue;
use lyquidity\XPath2\TrueValue;
use lyquidity\XPath2\Value\DecimalValue;
use lyquidity\XPath2\Value\YearMonthDurationValue;
use lyquidity\XPath2\Value\DayTimeDurationValue;
use lyquidity\XPath2\Value\DurationValue;
use lyquidity\XPath2\Value\UntypedAtomic;
use lyquidity\XPath2\Value\GYearMonthValue;
use lyquidity\XPath2\Value\GYearValue;
use lyquidity\XPath2\Value\GMonthValue;
use lyquidity\XPath2\Value\GMonthDayValue;
use lyquidity\XPath2\Value\GDayValue;
use lyquidity\XPath2\XPath2Item;
use lyquidity\XPath2\Value\Long;
use lyquidity\xml\schema\SchemaTypes;
use lyquidity\xml\TypeCode;

/**
 * A list of the valid supported PHP types
 */
class Type
{
	const int = "int";
	const long = "long";
	const bool = "bool";
	const string = "string";
	const float = "float";
	const double = "double";
	const decimal = "decimal";
	const assocArray = "array";
	const datetime = "DateTime";
	const dateTimeInterval = "DateTimeInterval";
	const object = "object";
	const null_ = "null";
	// The C# implementation creates a class 'Integer' (which should have been called 'Number' because it
	// encapsulates all numeric types).  Integer is needed by C# because its a typed language but not in
	// PHP because there are no explicit numeric types.  This simulates 'Integer'; to make porting easier.
	const integer = "integer";

	const int16 = "int16";
	const int32 = "int32";
	const int64 = "int64";
	const uint16 = "uint16";
	const uint32 = "uint32";
	const uint64 = "uint64";
	const short = "short";
	const char = "char";
	const sbyte = "sbyte";
	const byte = "byte";
	const time = "time";
	const date = "date";

	const union = "union";

	/**
	 * Name passed from the constructor
	 * @var string
	 */
	private $name;

	/**
	 * Type code passed from the constructor
	 * @var TypeCode
	 */
	private $typeCode;

	/**
	 * Create a type instance
	 */
	public function __construct() {}

	/**
	 * Create a type instance
	 * @param Type $value
	 */
	public static function FromValue( $value )
	{
		return Type::getTypeValueFromObject( $value );
	}

	/**
	 * Constructor
	 * @param string $name
	 * @param int $typeCode
	 */
	public static function FromNameAndTypeCode( $name, $typeCode )
	{
		$type = new Type();
		$type->name = $name;
		$type->typeCode = $typeCode;
		return $type;
	}

	/**
	 * Return the type code of this type
	 * @return TypeCode
	 */
	public function getTypeCode()
	{
		return $this->typeCode;
	}

	/**
	 * Get the type name
	 * @return string
	 */
	public function getTypeName()
	{
		return $this->name;
	}

	/**
	 * Get the type fullname
	 * @return string
	 */
	public function getFullName()
	{
		return $this->name;
	}

	/**
	 * Generate a type value from a variable
	 * @param mixed $value
	 * @return Type
	 */
	public static function getTypeValueFromObject( $value )
	{
		if ( $value instanceof XPath2Item )
		{
			return $value->getValueType();
		}

		if ( is_null( $value ) )
		{
			return Types::$NullType;
		}

		if ( is_array( $value ) )
		{
			return Types::$ArrayType;
		}

		if ( $value instanceof \DateTime )
		{
			return Types::$DateTimeValueType;
		}

		if ( is_bool( $value ) || $value instanceof FalseValue || $value instanceof TrueValue )
		{
			return Types::$BooleanType;
		}

		if ( is_string( $value ) )
		{
			return Types::$StringType;
		}

		if ( is_object( $value ) )
		{
			// if ( $value instanceof DateTimeInterval )
			// {
			// 	return Types::$DateTimeIntervalType;
			// }

			if ( $value instanceof DecimalValue )
			{
				return Types::$DecimalType;
			}

			if ( $value instanceof Long )
			{
				return Types::$LongType;
			}

			if ( $value instanceof DayTimeDurationValue )
			{
				return Types::$DayTimeDurationValueType;
			}

			if ( $value instanceof YearMonthDurationValue )
			{
				return Types::$YearMonthDurationValueType;
			}

			if ( $value instanceof UntypedAtomic )
			{
				return Types::$UntypedAtomicType;
			}

			if ( $value instanceof DurationValue )
			{
				return Types::$DurationValueType;
			}

			if ( $value instanceof GYearMonthValue )
			{
				return Types::$GYearMonthValueType;
			}

			if ( $value instanceof GYearValue )
			{
				return Types::$GYearValueType;
			}

			if ( $value instanceof GMonthValue )
			{
				return Types::$GMonthValueType;
			}

			if ( $value instanceof GMonthDayValue )
			{
				return Types::$GMonthDayValueType;
			}

			if ( $value instanceof GDayValue )
			{
				return Types::$GDayValueType;
			}

			if ( $value instanceof \lyquidity\XPath2\Value\QNameValue )
			{
				return Types::$QNameValueType;
			}

			if ( $value instanceof \lyquidity\XPath2\Value\HexBinaryValue )
			{
				return Types::$HexBinaryValueType;
			}

			if ( $value instanceof \lyquidity\XPath2\Value\Base64BinaryValue )
			{
				return Types::$Base64BinaryValueType;
			}

			if ( $value instanceof \lyquidity\XPath2\Value\IDREFSValue )
			{
				return Types::$IDREFSValueType;
			}

			if ( $value instanceof \lyquidity\XPath2\Value\NMTOKENSValue )
			{
				return Types::$NMTOKENSValueType;
			}

			if ( $value instanceof \lyquidity\XPath2\Value\ENTITIESValue )
			{
				return Types::$ENTITIESValueType;
			}

			$className = get_class( $value );

			if ( $className == Types::$NullType->getFullName() )
			{
				return Types::$NullType;
			}

			if ( $className == Types::$AnyUriValueType->getFullName() )
			{
				return Types::$AnyUriValueType;
			}

			if ( $className == Types::$UNION->getFullName() )
			{
				return Types::$UNION;
			}

			else if ( $className == Types::$BoolType->getFullName() )
				return Types::$BoolType;
			else if ( $className == Types::$BooleanType->getFullName() )
				return Types::$BooleanType;
			else if ( $className == Types::$CharType->getFullName() )
				return Types::$CharType;
			else if ( $className == Types::$SByteType->getFullName() )
				return Types::$SByteType;
			else if ( $className == Types::$ByteType->getFullName() )
				return Types::$ByteType;
			else if ( $className == Types::$Int16Type->getFullName() )
				return Types::$Int16Type;
			else if ( $className == Types::$ShortType->getFullName() )
				return Types::$ShortType;
			else if ( $className == Types::$UInt16Type->getFullName() )
				return Types::$UInt16Type;
			else if ( $className == Types::$Int32Type->getFullName() )
				return Types::$Int32Type;
			else if ( $className == Types::$IntType->getFullName() )
				return Types::$IntType;
			else if ( $className == Types::$UInt32Type->getFullName() )
				return Types::$UInt32Type;
			else if ( $className == Types::$Int64Type->getFullName() )
				return Types::$Int64Type;
			else if ( $className == Types::$LongType->getFullName() )
				return Types::$LongType;
			else if ( $className == Types::$UInt64Type->getFullName() )
				return Types::$UInt64Type;
			else if ( $className == Types::$FloatType->getFullName() )
				return Types::$FloatType;
			else if ( $className == Types::$DoubleType->getFullName() )
				return Types::$DoubleType;
			else if ( $className == Types::$DateTimeIntervalType->getFullName() )
				return Types::$DateTimeIntervalType;
			else if ( $className == Types::$DecimalType->getFullName() )
				return Types::$DecimalType;
			else if ( $className == Types::$TimeValueType->getFullName() )
				return Types::$TimeValueType;
			else if ( $className == Types::$DateValueType->getFullName() )
				return Types::$DateValueType;
			else if ( $className == Types::$DateTimeValueType->getFullName() )
				return Types::$DateTimeValueType;
			else if ( $className == Types::$StringType->getFullName() )
				return Types::$StringType;
			else if ( $className == Types::$ArrayType->getFullName() )
				return Types::$ArrayType;
			else if ( $className == Types::$ObjectType->getFullName() )
				return Types::$ObjectType;
			else if ( $className == Types::$ArrayType->getFullName() )
				return Types::$ArrayType;
			else if ( $className == Types::$IntegerType->getFullName() )
				return Types::$IntegerType;

			return Type::FromNameAndTypeCode( $className, TypeCode::Object );
		}

		if ( is_double( $value ) || NAN . "" == strtoupper( $value ) )
		{
			return Types::$DoubleType;
		}

		if ( is_int( $value ) )
		{
			return Types::$IntType;
		}

	}

	/**
	 * Get a TypeCode value for a Type instance
	 * @param Type $type
	 * @return TypeCode
	 */
	public static function getTypeCodeFromType( $type )
	{
		return $type->getTypeCode();
	}

	/**
	 * Convert an Xml type to a PHP type
	 *
	 * @param $xmlType $type An array representing an XmlType
	 * @return Type
	 */
	public static function XmlTypeToType( $xmlType )
	{
		if ( strpos( $xmlType, "xs:" ) === 0 )
		{
			$xmlType = str_replace( "xs:", "xs:", $xmlType );
		}

		$types = SchemaTypes::getInstance();
		$atom = $types->getAtomicType( $xmlType );

		switch( $atom )
		{
			case "xs:decimal":
			case "xsd:decimal":
				return Types::$DecimalType;

			case "xs:double":
			case "xsd:double":
				return Types::$DoubleType;

			case "xs:float":
			case "xsd:float":
				return Types::$FloatType;

			case "xs:QName":
			case "xsd:QName":
				return Types::$QNameValueType;

			case "xs:anyURI":
			case "xsd:anyURI":
				return Types::$AnyUriValueType;

			case "xs:string":
			case "xs:NOTATION":
			case "xs:normalizedString":
			case "xs:token":
			case "xs:language":
			case "xs:Name":
			case "xs:NCName":
			case "xs:NMTOKEN":
			case "xs:ID":
			case "xs:IDREF":
			case "xs:ENTITY":
			case "xsd:string":
			case "xsd:NOTATION":
			case "xsd:normalizedString":
			case "xsd:token":
			case "xsd:language":
			case "xsd:Name":
			case "xsd:NCName":
			case "xsd:NMTOKEN":
			case "xsd:ID":
			case "xsd:IDREF":
			case "xsd:ENTITY":
				return Types::$StringType;

			case "xs:gYearMonth":
			case "xsd:gYearMonth":
				return Types::$GYearMonthValueType;

			case "xs:gYear":
			case "xsd:gYear":
				return Types::$GYearValueType;

			case "xs:gMonthDay":
			case "xsd:gMonthDay":
				return Types::$GMonthDayValueType;

			case "xs:gDay":
			case "xsd:gDay":
				return Types::$GDayValueType;

			case "xs:gMonth":
			case "xsd:gMonth":
				return Types::$GMonthValueType;

			case "xs:hexBinary":
			case "xsd:hexBinary":
				return Types::$HexBinaryValueType;

			case "xs:base64Binary":
			case "xsd:base64Binary":
				return Types::$Base64BinaryValueType;

			case "xs:long":
			case "xsd:long":
				return Types::$LongType;

			case "xs:integer":
			case "xs:nonPositiveInteger":
			case "xs:nonNegativeInteger":
			case "xs:positiveInteger":
			case "xs:negativeInteger":
			case "xs:int":
			case "xsd:integer":
			case "xsd:nonPositiveInteger":
			case "xsd:nonNegativeInteger":
			case "xsd:positiveInteger":
			case "xsd:negativeInteger":
			case "xsd:int":
				return Types::$IntType;

			case "xs:short":
			case "xsd:short":
				return Types::$ShortType;

			case "xs:byte":
			case "xsd:byte":
				return Types::$ByteType;

			case "xs:unsignedLong":
			case "xsd:unsignedLong":
				return Types::$UInt64Type;

			case "xs:unsignedInt":
			case "xsd:unsignedInt":
				return Types::$UInt32Type;

			case "xs:unsignedShort":
			case "xsd:unsignedShort":
				return Types::$UInt16Type;

			case "xs:unsignedByte":
			case "xsd:unsignedByte":
				return Types::$ByteType;

			case "xs:boolean":
			case "xsd:boolean":
				return Types::$BooleanType;

			case "xs:duration":
			case "xsd:duration":
				return Types::$DateTimeIntervalType;

			case "xs:dateTime":
			case "xsd:dateTime":
				return Types::$DateTimeValueType;

			case "xs:time":
			case "xsd:time":
				return Types::$TimeValueType;

			case "xs:date":
			case "xsd:date":
				return Types::$DateValueType;

			case "xs:IDREFS":
			case "xsd:IDREFS":
				return Types::$IDREFSValueType;

			case "xs:ENTITIES":
			case "xsd:ENTITIES":
				return Types::$ENTITIESValueType;

			case "xs:NMTOKENS":
			case "xsd:NMTOKENS":
				return Types::$NMTOKENSValueType;

			case "xs:anyType":
			case "xsd:anyType":
				return Types::$StringType;

			case "xs:UNION":
			case "xs:Union":
			case "xs:union":
			case "xsd:UNION":
			case "xsd:Union":
			case "xsd:union":
				return Types::$UNION;

			default:
				return false; // Let the caller decide if the failure to determine a type is an error.
		}
	}

	/**
	 * Test Type functions
	 */
	public static function test()
	{
		$bool = Types::$BooleanType;
		$type = Type::FromValue( 1 );
		if ( $type == Types::$IntType )
		{
			echo "Equals int\n";
		}

		if ( $type != Types::$Int64Type )
		{
			echo "Does not equal int64\n";
		}

		$type = Type::FromValue( true );
		if ( $type == $bool )
		{
			echo "Equals boolean\n";
		}

		$type = Type::XmlTypeToType( "xs:decimal" );
		if ( $type == Types::$DecimalType )
		{
			echo "Equals decimal\n";
		}

		$type = Type::getTypeValueFromObject( new \DateTime() );
	}
}

