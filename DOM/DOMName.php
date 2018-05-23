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

use lyquidity\XPath2\DOM\DOMSchemaType;
use lyquidity\xml\MS\IXmlSchemaInfo;
use lyquidity\xml\MS\XmlSchemaValidity;
use lyquidity\xml\MS\XmlSchemaElement;
use lyquidity\xml\MS\XmlSchemaAttribute;
use lyquidity\xml\QName;
use lyquidity\xml\schema\SchemaTypes;
use lyquidity\xml\exceptions\NotSupportedException;

/**
 * Represents the post-schema-validation infoset of a validated XML node.
 */
class DOMName implements IXmlSchemaInfo
{
	/**
	 * The local name part of the qualified name
	 * @var string
	 */
	public $LocalName = "";
	/**
	 * The prefix part of the qualified name
	 * @var string
	 */
	public $Prefix = "";
	/**
	 * The namespace corresponding to the prefix
	 * @var string
	 */
	public $NamespaceURI = "";
	/**
	 * The qualified name
	 * @var string
	 */
	public $Name = "";
	/**
	 * A reference to the document of the node
	 * @var \DOMDocument
	 */
	public $OwnerDocument = "";

	/**
	 * A flag indicating if this validated XML node was set as the result of a default
	 * being applied during XML Schema Definition Language (XSD) schema validation
	 * @var bool
	 */
	private $IsDefault = false;

	/**
	 * A flag indicating if the value for this validated XML node is nil
	 * @var bool
	 */
	private $IsNil = false;

	/**
	 * The dynamic schema type for this validated XML node
	 * @var bool
	 */
	private $MemberType = null;

	/**
	 * The XmlSchemaAttribute that corresponds to this validated XML node
	 * @var XmlSchemaAttribute
	 */
	private $SchemaAttribute = null;

	/**
	 * The XmlSchemaElement that corresponds to this validated XML node.
	 * @var XmlSchemaElement
	 */
	private $SchemaElement = null;

	/**
	 * The static XML Schema Definition Language (XSD) schema type of this validated XML node
	 * @var DOMSchemaDatatype
	 */
	private $SchemaType = null;

	/**
	 * The validity state of the node (default: XmlSchemaValidity::NotKnown)
	 * @var XmlSchemaValidity
	 */
	private $Validity;

	/**
	 * Constructor for DOMName
	 * @param DOMNode $domNode
	 */
	public function __construct( $domNode )
	{
		$this->LocalName = $domNode->localName;
		$this->Prefix = $domNode->prefix;
		$this->NamespaceURI = $domNode->namespaceURI;
		$this->Name = ( ! empty( $this->Prefix ) ? $this->Prefix . ":" : "" ) . $domNode->localName;
		$this->OwnerDocument = $domNode->ownerDocument;
		$this->Validity = XmlSchemaValidity::NotKnown;
		$this->SchemaType = DOMSchemaType::getSchemaType( $domNode );
		$types = SchemaTypes::getInstance();
		$prefix = $types->getPrefixForNamespace( $domNode->namespaceURI );
		$this->SchemaElement = DOMSchemaElement::fromQName( new \lyquidity\xml\qname( $prefix ? $prefix : $domNode->prefix, $domNode->namespaceURI, $domNode->localName ) );
	}

	/**
	 * Allow the caller to refer to values as properties
	 * @param string $name
	 */
	public function __get ( $name )
	{
		switch ( $name )
		{
			case "IsDefault":
				return $this->getIsDefault();

			case "IsNil":
				return $this->getIsNil();

			case "MemberType":
				return $this->getMemberType();

			case "SchemaAttribute":
				return $this->getSchemaAttribute();

			case "SchemaElement":
				return $this->getSchemaElement();

			case "SchemaType":
				return $this->getSchemaType();

			case "Validity":
				return $this->getValidity();

			default:
				throw new NotSupportedException( "Calls to '$name' are not supported." );
		}
	}

	/**
	 * Gets a value indicating if this validated XML node was set as the result of a
	 * default being applied during XML Schema Definition Language (XSD) schema validation.
	 *
	 * @var bool	true if this validated XML node was set as the result of a default being applied
	 *				during schema validation; otherwise, false.
	 */
	public function getIsDefault()
	{
		return $this->IsDefault;
	}

	/**
	 * Gets a value indicating if the value for this validated XML node is nil.
	 *
	 * @var bool true if the value for this validated XML node is nil; otherwise, false.
	 */
	public function getIsNil()
	{
		return $this->IsNil;
	}

	/**
	 * Gets the dynamic schema type for this validated XML node.
	 *
	 * @var XmlSchemaSimpleType	An XmlSchemaSimpleType object that represents the dynamic schema type for this validated XML node.
	 */
	public function getMemberType()
	{
		return $this->MemberType;
	}

	/**
	 * Gets the compiled XmlSchemaAttribute that corresponds to this validated XML node.
	 *
	 * @var XmlSchemaAttribute  An XmlSchemaAttribute that corresponds to this validated XML node.
	 */
	public function getSchemaAttribute()
	{
		return $this->SchemaAttribute;
	}

	/**
	 * Gets the compiled XmlSchemaElement that corresponds to this validated XML node.
	 *
	 * @var XmlSchemaElement An XmlSchemaElement that corresponds to this validated XML node.
	 */
	public function getSchemaElement()
	{
		return $this->SchemaElement;
	}

	/**
	 * Gets the static XML Schema Definition Language (XSD) schema type of this validated XML node.
	 *
	 * @var XmlSchemaType An XmlSchemaType of this validated XML node.
	 */
	public function getSchemaType()
	{
		return $this->SchemaType;
	}

	/**
	 * Gets the XmlSchemaValidity value of this validated XML node.
	 *
	 * @var XmlSchemaValidity An XmlSchemaValidity value of this validated XML node.
	 */
	public function getValidity()
	{
		return $this->Validity;
	}
}
