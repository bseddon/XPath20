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

use lyquidity\xml\MS\XmlSchemaElement;
use lyquidity\xml\MS\XmlSchemaDerivationMethod;
use lyquidity\xml\MS\XmlSchemaForm;
use lyquidity\xml\QName;
use lyquidity\xml\schema\SchemaTypes;

/**
 * Represents the element element from XML Schema as specified by the World Wide
 * Web Consortium (W3C). This class is the base class for all particle types and
 * is used to describe an element in an XML document.
 */
class DOMSchemaElement extends XmlSchemaElement
{
	/**
	 * The qualified name of the element
	 * @param QName|string $name
	 */
	public static function fromQName( $name )
	{
		$types = SchemaTypes::getInstance();

		if ( $name instanceof QName )
		{
			// If prefix is empty try looking it up
			if ( empty( $name->prefix ) )
			{
				$prefix = $types->getPrefixForNamespace( $name->namespaceURI );
				if ( $prefix ) $name->prefix = $prefix;
			}
			$qname = "{$name->prefix}:{$name->localName}";
		}
		else if ( is_string( $name ) )
		{
			$qname = $name;
		}
		else
		{
			throw new \InvalidArgumentException( "$qname must be string or a QName instance" );
		}

		$element = $types->getElement( $qname );

		if ( ! $element )
		{
			return null;
		}

		$result = new DOMSchemaElement();
		$result->QualifiedName = $name;
		$type = is_array( $element['types'][0] ) && isset( $element['types'][0]['parent'] )
			? $element['types'][0]['parent']
			: $element['types'][0];
		// if ( is_array( $element['types'][0] ) )
		// {
		// 	$x = 1;
		// }
		$elementType = is_array( $type ) ? false : $types->getType( $type );
		$result->ElementSchemaType = $elementType;
		$result->SchemaTypeName = $elementType['name'] ?? null;
		$result->SubstitutionGroup = isset( $element['substitutionGroup'] ) ? $element['substitutionGroup'] : $type;
		// error_log( __CLASS__ . " need to populate the DOMSchemaElement instance" );
		return $result;
	}

	/**
	 * Initializes a new instance of the XmlSchemaElement class.
	 */
	public function __construct() {}

	/**
	 * Gets or sets a Block derivation.
	 *
	 * @var XmlSchemaDerivationMethod $Block
	 * The attribute used to block a type derivation. Default value is XmlSchemaDerivationMethod.None.Optional.
	 */
	public $Block = XmlSchemaDerivationMethod::None;

	/**
	 * Gets the post-compilation value of the Block property.
	 *
	 * @var XmlSchemaDerivationMethod $BlockResolved
	 * The post-compilation value of the Block property. The default is the BlockDefault
	 * value on the schema element.
	 */
	public $BlockResolved;

	/**
	 * Gets the collection of constraints on the element.
	 *
	 * @var array $Constraints
	 * 		The collection of constraints.
	 * 		[XmlElement("unique", typeof(XmlSchemaUnique))]
	 * 		[XmlElement("key", typeof(XmlSchemaKey))]
	 * 		[XmlElement("keyref", typeof(XmlSchemaKeyref))]
	 */
	public $Constraints = array();

	/**
	 * Gets or sets the default value of the element if its content is a simple type
	 * or content of the element is textOnly.
	 *
	 * @var string $DefaultValue
	 * The default value for the element. The default is a null reference.Optional.
	 */
	public $DefaultValue = null;

	/**
	 * Gets an XmlSchemaType object representing the type of the element
	 * based on the XmlSchemaElement.SchemaType or XmlSchemaElement.SchemaTypeName
	 * values of the element.
	 *
	 * @var XmlSchemaType $ElementSchemaType An XmlSchemaType object.
	 */
	public $ElementSchemaType = null;

	/**
	 * Gets or sets the Final property to indicate that no further derivations are allowed.
	 *
	 * @var XmlSchemaDerivationMethod $Final
	 * The Final property. The default is XmlSchemaDerivationMethod.None.Optional.
	 */
	public $Final = XmlSchemaDerivationMethod::None;

	/**
	 * Gets the post-compilation value of the Final property.
	 *
	 * @var XmlSchemaDerivationMethod $FinalResolved
	 * The post-compilation value of the Final property. Default value is the FinalDefault
	 * value on the schema element.
	 */
	public $FinalResolved = XmlSchemaDerivationMethod::None;

	/**
	 * Gets or sets the fixed value.
	 *
	 * @var string $FixedValue
	 * The fixed value that is predetermined and unchangeable. The default is a null
	 * reference.Optional.
	 */
	public $FixedValue;

	/**
	 * Gets or sets the form for the element.
	 *
	 * @var XmlSchemaForm $Form
	 * The form for the element. The default is the XmlSchema.ElementFormDefault
	 * value.Optional.
	 */
	public $Form = XmlSchemaForm::None;

	/**
	 * Gets or sets information to indicate if the element can be used in an instance
	 * document.
	 *
	 * @var bool $IsAbstract
	 * If true, the element cannot appear in the instance document. The default is false.Optional.
	 */
	public $IsAbstract = false;

	/**
	 * Gets or sets information that indicates if xsi:nil can occur in the instance
	 * data. Indicates if an explicit nil value can be assigned to the element.
	 *
	 * @var bool $IsNillable
	 * If nillable is true, this enables an instance of the element to have the nil
	 * attribute set to true. The nil attribute is defined as part of the XML Schema
	 * namespace for instances. The default is false.Optional.
	 */
	public $IsNillable = false;

	/**
	 * Gets or sets the name of the element.
	 *
	 * @var string $Name
	 * The name of the element. The default is String.Empty.
	 */
	public $Name = "";

	/**
	 * Gets the actual qualified name for the given element.
	 *
	 * @var QName $QualifiedName
	 * The qualified name of the element. The post-compilation value of the QualifiedName property.
	 */
	public $QualifiedName = null;

	/**
	 * Gets or sets the reference name of an element declared in this schema (or another
	 * schema indicated by the specified namespace).
	 *
	 * @var QName $RefName
	 * The reference name of the element.
	 */
	public $RefName = null;

	/**
	 * Gets or sets the type of the element. This can either be a complex type or a
	 * simple type.
	 *
	 * @var XmlSchemaType $SchemaType
	 * The type of the element.
	 * [XmlElement("simpleType", typeof(XmlSchemaSimpleType))]
	 * [XmlElement("complexType", typeof(XmlSchemaComplexType))]
	 */
	public $SchemaType = null;

	/**
	 * Gets or sets the name of a built-in data type defined in this schema or another
	 * schema indicated by the specified namespace.
	 *
	 * @var XmlQualifiedName $SchemaTypeName
	 * The name of the built-in data type.
	 */
	public $SchemaTypeName = null;

	/**
	 * Gets or sets the name of an element that is being substituted by this element.
	 *
	 * @var XmlQualifiedName $SubstitutionGroup
	 * The qualified name of an element that is being substituted by this element.Optional.
	 */
	public $SubstitutionGroup = null;
}
