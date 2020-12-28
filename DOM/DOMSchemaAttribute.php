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

use lyquidity\xml\MS\XmlSchemaAttribute;
use lyquidity\xml\MS\XmlSchemaSimpleType;
use lyquidity\xml\QName;
use lyquidity\xml\schema\SchemaTypes;
use lyquidity\xml\MS\XmlSchemaForm;
use lyquidity\xml\MS\XmlSchemaUse;

/**
 * Represents the attribute element from the XML Schema as specified by the World
 * Wide Web Consortium (W3C). Attributes provide additional information for other
 * document elements. The attribute tag is nested between the tags of a document's
 * element for the schema. The XML document displays attributes as named items in
 * the opening tag of an element.
 */
class DOMSchemaAttribute extends XmlSchemaAttribute
{
	/**
	 * The qualified name of the attribute
	 * @param QName|string $name
	 */
	public static function fromQName( $name )
	{
		if ( $name instanceof QName )
		{
			$name = "{$name->prefix}:{$name->localName}";
		}
		if ( ! is_string( $name ) )
		{
			throw new \InvalidArgumentException( "$name must be string or a QName instanxe" );
		}

		$types = SchemaTypes::getInstance();
		$attribute = $types->getElement( $name );

		if ( ! $attribute )
		{
			return null;
		}

		$result = new DOMSchemaAttribute();
		error_log( __CLASS__ . " need to populate the DOMSchemaAttribute instance" );
		return $result;
	}

	/**
	 * Initializes a new instance of the XmlSchemaAttribute class.
	 */
	public function __construct() {}

	/**
	 * Gets an XmlSchemaSimpleType object representing the type of the attribute based on the XmlSchemaAttribute.SchemaType or
	 * XmlSchemaAttribute.SchemaTypeName of the attribute.
	 *
	 * @var XmlSchemaSimpleType $AttributeSchemaType An XmlSchemaSimpleType object.
	 */
	public $AttributeSchemaType;

	/**
	 * Gets or sets the default value for the attribute.
	 *
	 * @var string $DefaultValue The default value for the attribute. The default is a null reference.Optional.
	 */
	public $DefaultValue = null;

	/**
	 * Gets or sets the fixed value for the attribute.
	 *
	 * @var string $FixedValue The fixed value for the attribute. The default is null.Optional.
	 */
	public $FixedValue = null;

	/**
	 * Gets or sets the form for the attribute.
	 *
	 * @var XmlSchemaForm $Form One of the XmlSchemaForm values. The default is the value of
	 *     						the AttributeFormDefault of the schema element containing
	 *     						the attribute.Optional.
	 */
	public $Form = XmlSchemaForm::None;

	/**
	 * Gets or sets the name of the attribute.
	 *
	 * @var string $Name The name of the attribute.
	 */
	public $Name;

	/**
	 * Gets the qualified name for the attribute.
	 *
	 * @var QName $QualifiedName The post-compilation value of the QualifiedName property.
	 */
	public $QualifiedName;

	/**
	 * Gets or sets the name of an attribute declared in this schema (or another schema indicated by the specified namespace).
	 *
	 * @var QName $RefName The name of the attribute declared.
	 */
	public $RefName;

	/**
	 * Gets or sets the attribute type to a simple type.
	 *
	 * @var XmlSchemaSimpleType $SchemaType The simple type defined in this schema.
	 */
	public $SchemaType = null;

	/**
	 * Gets or sets the name of the simple type defined in this schema (or another schema indicated by the specified namespace).
	 *
	 * @var QName $SchemaTypeName The name of the simple type.
	 */
	public $SchemaTypeName;

	/**
	 * Gets or sets information about how the attribute is used.
	 *
	 * @var XmlSchemaUse One of the following values: None, Prohibited, Optional, or Required. The default is Optional.Optional.
	 */
	public $Use = XmlSchemaUse::None;
}
