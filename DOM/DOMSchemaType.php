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

namespace lyquidity\XPath2\DOM;

use \lyquidity\XPath2\DOM\DOMSchemaDatatype;
use \lyquidity\XPath2\DOM\DOMSchemaSimpleType;
use \lyquidity\XPath2\DOM\DOMSchemaComplexType;
use \lyquidity\xml\MS\XmlTypeCode;
use \lyquidity\xml\MS\XmlSchemaDerivationMethod;
use \lyquidity\xml\MS\XmlSchemaType;
use lyquidity\xml\QName;
use lyquidity\xml\schema\SchemaTypes;
use lyquidity\xml\schema\SchemaException;
use lyquidity\xml\exceptions\ArgumentNullException;
use lyquidity\xml\exceptions\NotSupportedException;

/**
 * Represents the complexType element from XML Schema as specified by the World
 * Wide Web Consortium (W3C). This class defines a complex type that determines
 * the set of attributes and content of an element.
 */
class DOMSchemaType extends XmlSchemaType
{

	public function __construct( $domNode )
	{
	}

	/**
	 * Create an instance for a type in the SchemaTypes types list if there is one
	 * @param QName $qualifiedName
	 */
	public static function fromSchemaType( $qname )
	{
		$types = SchemaTypes::getInstance();
		$type = $types->getType( "{$qname->prefix}:{$qname->localName}" );

		if ( ! $type ) return null;

		$datatype = new DOMSchemaDatatype( "{$qname->prefix}:{$qname->localName}" );
		$result = new DOMSchemaSimpleType( $qname, $datatype );
		return $result;
	}

	/**
	 * Returns an XmlSchemaComplexType that represents the built-in
	 * complex type of the complex type specified.
	 *
	 * @param XmlTypeCode $typeCode One of the XmlTypeCode values representing the complex type.
	 * @throws \InvalidArgumentException
	 * @return XmlSchemaComplexType The type that represents the built-in complex type.
	 */
	public static function GetBuiltInComplexTypeByTypeCode( $typeCode )
	{
		// The $typeCode must be valid (one of the items in XmlTypeCode)
		$type = XmlTypeCode::getTypeForCode( $typeCode );

		if ( $type === false)
		{
			throw new \InvalidArgumentException( "The typecode passed to DOMSchemaType::GetBuiltInComplexTypeByTypeCode is not valid" );
		}

		return DOMSchemaType::GetBuiltInComplexTypeByQName( $type );
	}

	/**
	 * Returns an XmlSchemaComplexType that represents the built-in
	 * complex type of the complex type specified by qualified name.
	 *
	 * @param QName|string $qualifiedName The QName of the complex type.
	 *
	 * @return The XmlSchemaComplexType that represents the built-in complex type.
	 *
	 * @except \lyquidity\xml\exceptions\ArgumentNullException The XmlQualifiedName parameter is null.
	 */
	public static function GetBuiltInComplexTypeByQName( $qualifiedName )
	{
		// The $qualifiedName cannot be null
		if ( is_null( $qualifiedName ) ) throw new \lyquidity\xml\exceptions\ArgumentNullException( "The QName passed to DOMSchemaType::GetBuiltInComplexTypeByQName cannot be null" );

		$type = "";
		if ( $qualifiedName instanceof QName )
		{
			$type = "{$qualifiedName->prefix}:{$qualifiedName->localName}";
		}
		else if ( is_string( $qualifiedName ) )
		{
			$type = $qualifiedName;
			$qualifiedName = \lyquidity\xml\qname( $qualifiedName, array( SCHEMA_PREFIX => SCHEMA_NAMESPACE ) + SchemaTypes::getInstance()->getProcessedSchemas() );
		}
		else
		{
			throw new \InvalidArgumentException( "The qualified name passed to DOMSchemaType::GetBuiltInSimpleTypeByQName MUST be a string or QName");
		}
		// The only valid built-in complex type is xs:anyType.
		if ( $type != "xs:anyType" )
		{
			// return null;
		}

		$dataType = new DOMSchemaDatatype( $type );
		return new DOMSchemaComplexType( $qualifiedName, $dataType );
	}

	/**
	 * USED in XPath2Item to infer the xml type (48)
	 *      in CoreFuncs CastToItem (989) and TryProcessTypeName (1598)
	 *      in SequenceType.Create  and to initialize global variable references
	 * Returns an XmlSchemaSimpleType that represents the built-in
	 * simple type of the simple type that is specified by the qualified name.
	 *
	 * @param QName $qualifiedName The QName of the simple type.
	 * @return XmlSchemaSimpleType that represents the built-in simple type.
	 * @throws \lyquidity\xml\exceptions\ArgumentNullException The XmlQualifiedName parameter is null.
	 */
	public static function GetBuiltInSimpleTypeByQName( $qualifiedName )
	{
		// The $qualifiedName cannot be null
		if ( is_null( $qualifiedName ) ) throw new \lyquidity\xml\exceptions\ArgumentNullException( "The QName passed to DOMSchemaType::GetBuiltInComplexTypeByQName cannot be null" );

		$type = "";
		if ( $qualifiedName instanceof QName )
		{
			$type = "{$qualifiedName->prefix}:{$qualifiedName->localName}";
		}
		else if ( is_string( $qualifiedName ) )
		{
			$type = $qualifiedName;
			$qualifiedName = \lyquidity\xml\qname( $qualifiedName, array( SCHEMA_PREFIX => SCHEMA_NAMESPACE ) + SchemaTypes::getInstance()->getProcessedSchemas() );
		}
		else
		{
			throw new \InvalidArgumentException( "The qualified name passed to DOMSchemaType::GetBuiltInSimpleTypeByQName MUST be a string or QName");
		}

		// Special case because at the moment the SchemaTypes class does not support union types
		if ( SchemaTypes::getInstance()->isUnionType( $type ) )
		{
			$type = 'xs:UNION';
		}

		// TODO Kludge alert!! The XBRL_Types class wrongly used the prefix 'xsd' instead of 'xs'
		//      This needs to be fixed but for now convert 'xs' to 'xsd'
		// BMS 2018-04-09 This can be retired
		// if ( strpos( $type, "xs:" ) === 0 ) $type = str_replace( "xs:", "xsd:", $type );

		// The only valid built-in complex type is xs:anyType.
		if ( $type == "xs:anyType" )
		{
			return null;
		}

		$dataType = new DOMSchemaDatatype( $type );
		if ( ! $dataType->SimpleType ) return null;

		return new DOMSchemaSimpleType( $qualifiedName, $dataType );
	}

	/**
	 * USED in XPath2Item to infer the xml type (48)
	 *      in CoreFuncs CastToItem (989) and TryProcessTypeName (1598)
	 *      in SequenceType.Create  and to initialize global variable references
	 * Returns an XmlSchemaSimpleType that represents the built-in
	 * simple type of the specified simple type.
	 *
	 * @param XmlTypeCode $typeCode One of the XmlTypeCode values representing the simple type.
	 *
	 * @return XmlSchemaSimpleType that represents the built-in simple type.
	 */
	public static function GetBuiltInSimpleTypeByTypecode( $typeCode )
	{
		// The $typeCode must be valid (one of the items in XmlTypeCode)
		if ( $typeCode == XmlTypeCode::None ) return null;
		$type = XmlTypeCode::getTypeForCode( $typeCode );

		if ( $type === false)
		{
			throw new \InvalidArgumentException( "The typecode passed to DOMSchemaType::GetBuiltInComplexTypeByTypeCode is not valid" );
		}

		$qname = \lyquidity\xml\qname( $type, array( SCHEMA_PREFIX => SCHEMA_NAMESPACE ) );
		return DOMSchemaType::GetBuiltInSimpleTypeByQName( $qname );
	}

	/**
	 * USED This is the same as SchemaTypes::resolvesToBaseType
	 *      Only the 'Empty' XmlSchemaDerivationMethod value is used (so any derivation method is OK)
	 * 		in SequenceType.Match
	 * 		(423/427) to test if the current sequence is a document whether the derived type is also the same document
	 * 		(457/461) to test if the current sequence is an element whether the derived type is also the same element
	 * 		(487/489) to test if the current sequence is an attribute whether the derived type is also the same attribute
	 * 		(609) to test if the current sequence item is the same sequence item
	 * 		in SequenceType.IsDerivedFrom (1203)
	 *
	 * Returns a value indicating if the derived schema type specified is derived from
	 * the base schema type specified
	 *
	 * @param XmlSchemaType $derivedType : The derived XmlSchemaType to test.
	 * @param XmlSchemaType $baseType The base XmlSchemaType to test the derived XmlSchemaTypeagainst.
	 * @param int $except One of the XmlSchemaDerivationMethod values representing a
	 *         type derivation method to exclude from testing.
	 * @return bool true if the derived type is derived from the base type; otherwise, false.
	 * @throws ArgumentNullException|NotSupportedException
	 */
	public static function IsDerivedFrom( $derivedType, $baseType, $except )
	{
		if ( is_null( $derivedType ) || is_null( $baseType ) )
		{
			throw new ArgumentNullException( "The derived type and base type passed to DOMSchemaType::IsDerivedFrom cannot be null" );
		}

		if ( $except != XmlSchemaDerivationMethod::Empty_ )
		{
			throw new NotSupportedException("Only the XmlSchemaDerivationMethod::Empty exception option is supported");
		}

		// If the $derivedType does not exist then one cannot be derived from the other
		$types = SchemaTypes::getInstance();
		$typeDerived = $types->getType( $derivedType->QualifiedName->localName, $derivedType->QualifiedName->prefix );
		if ( ! $typeDerived ) return false;

		$typeBase = $types->getType( $baseType->QualifiedName->localName, $baseType->QualifiedName->prefix );
		if ( ! $typeBase ) return false;

		$result = $types->resolvesToBaseType( "{$typeDerived['prefix']}:{$typeDerived['name']}", array( "{$typeBase['prefix']}:{$typeBase['name']}" ) );

		return $result;
	}

	/**
	 * Create a DOMSchemaType instance from a DOMNode
	 * @param DOMXPathItem $domNode
	 * @return XmlSchemaType
	 */
	public static function getSchemaType( $domNode )
	{
		try
		{
			$types = SchemaTypes::getInstance();
			$type = $types->getTypeForDOMNode( $domNode );
			if ( $type )
			{
				// TODO Kludge alert!! The XBRL_Types class wrongly used the prefix 'xsd' instead of 'xs'
				//      This needs to be fixed but for now convert 'xs' to 'xsd'
				// BMS 2018-04-09 This can be retired
				// if ( strpos( $type, "xs:" ) === 0 ) $type = str_replace( "xs:", "xsd:", $type );

				$resolves = SchemaTypes::getInstance()->resolvesToBaseType( $type, array( "xs:anySimpleType", "xsd:anySimpleType" ) );
				if ( $resolves )
				{
					return DOMSchemaType::GetBuiltInSimpleTypeByQName( $type );
				}
			}

			return XmlSchema::$AnyType;
		}
		catch ( SchemaException $ex )
		{}

		return XmlSchema::$UntypedAtomic;

	}

	public static function test()
	{
		try
		{
			$result = DOMSchemaType::GetBuiltInComplexTypeByTypeCode( XmlTypeCode::Boolean );
			$result = DOMSchemaType::GetBuiltInComplexTypeByQName( "xs:anyType" );
			$result = DOMSchemaType::GetBuiltInComplexTypeByQName( \lyquidity\xml\qname( "xs:anyType", array( SCHEMA_PREFIX => SCHEMA_NAMESPACE ) ) );

			$boolean = DOMSchemaType::GetBuiltInSimpleTypeByQName( "xs:boolean" );
			$any = DOMSchemaType::GetBuiltInSimpleTypeByQName( "xs:anyAtomicType" );
			$any = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::AnyAtomicType );
			$result = DOMSchemaType::GetBuiltInSimpleTypeByQName( "xs:xxx" );

			$language = DOMSchemaType::GetBuiltInSimpleTypeByQName( "xs:language" );
			$languageItem = DOMSchemaType::GetBuiltInSimpleTypeByQName( "xbrli:languageItemType" );

			$result = DOMSchemaType::IsDerivedFrom( $languageItem, $language, XmlSchemaDerivationMethod::Empty_ );
			$result = DOMSchemaType::IsDerivedFrom( $languageItem, $boolean, XmlSchemaDerivationMethod::Empty_ );
		}
		catch( \Exception $ex )
		{

		}
	}
}
