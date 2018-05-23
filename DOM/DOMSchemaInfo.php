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

use \lyquidity\xml\MS\IXmlSchemaInfo;
use \lyquidity\xml\MS\XmlSchemaContentType;
use \lyquidity\xml\MS\XmlSchemaValidity;
use lyquidity\xml\exceptions\NotSupportedException;

/**
 * Represents the post-schema-validation infoset of a validated XML node.
 */
class DOMSchemaInfo implements IXmlSchemaInfo
{
	/**
	 * ContentType
	 * @var XmlSchemaContentType
	 */
	private $ContentType = null;

	/**
	 * IsDefault
	 * @var bool
	 */
	private $IsDefault = false;

	/**
	 * IsNil
	 * @var bool
	 */
	private $IsNil = false;

	/**
	 * IsUnionType
	 * @var bool
	 */
	private $IsUnionType = false;

	/**
	 * MemberType
	 * @var  XmlSchemaSimpleType
	 */
	private $MemberType = null;

	/**
	 * SchemaAttribute
	 * @var bool
	 */
	private $SchemaAttribute = null;

	/**
	 *SchemaElement
	 * @var bool
	 */
	private $SchemaElement = null;

	/**
	 * SchemaType
	 * @var bool
	 */
	private $SchemaType = null;

	/**
	 * Validity
	 * @var bool
	 */
	private $Validity;

	public function __construct( $domNode )
	{
		$this->ContentType = XmlSchemaContentType::EmptyNode;
		$this->Validity = XmlSchemaValidity::NotKnown;

		if ( $domNode instanceof \DOMDocument ) return;

		$this->SchemaType = DOMSchemaType::GetBuiltInSimpleTypeByQName( SchemaTypes::getInstance()->getTypeForDOMNode( $domNode ) );
		// $this->SchemaType = new DOMSchemaType( $domNode );
	}

	/**
	 * Allow the caller to refer to values as properties
	 * @param string $name
	 */
	public function __get ( $name )
	{
		switch ( $name )
		{
			case "ContentType":
				return $this->getContentType();

			case "IsDefault":
				return $this->getIsDefault();

			case "IsNil":
				return $this->getIsNil();

			case "IsUnionType":
				return $this->getIsUnionType();

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
	 * Gets the XmlSchemaContentType object that corresponds to the content type of this validated XML node.
	 *
	 * @return XmlSchemaContentType
	 */
	public function getContentType()
	{
		return $this->ContentType;
	}

	/**
	 * Gets a value indicating if this validated XML node was set as the result of a
	 * default being applied during XML Schema Definition Language (XSD) schema validation.
	 *
	 * @return bool	true if this validated XML node was set as the result of a default being applied
	 *				during schema validation; otherwise, false.
	 */
	public function getIsDefault()
	{
		return $this->IsDefault;
	}

	/**
	 * Gets a value indicating if the value for this validated XML node is nil.
	 *
	 * @return bool true if the value for this validated XML node is nil; otherwise, false.
	 */
	public function getIsNil()
	{
		return $this->IsNil;
	}

	/**
	 * @return bool
	 */
	public function getIsUnionType()
	{
		return $this->IsUnionType;
	}

	/**
	 * Gets the dynamic schema type for this validated XML node.
	 *
	 * @return XmlSchemaSimpleType	An XmlSchemaSimpleType object that represents the dynamic schema type for this validated XML node.
	 */
	public function getMemberType()
	{
		return $this->MemberType;
	}

	/**
	 * Gets the compiled XmlSchemaAttribute that corresponds to this validated XML node.
	 *
	 * @return XmlSchemaAttribute  An XmlSchemaAttribute that corresponds to this validated XML node.
	 */
	public function getSchemaAttribute()
	{
		return $this->SchemaAttribute;
	}

	/**
	 * Gets the compiled XmlSchemaElement that corresponds to this validated XML node.
	 *
	 * @return XmlSchemaElement An XmlSchemaElement that corresponds to this validated XML node.
	 */
	public function getSchemaElement()
	{
		return $this->SchemaElement;
	}

	/**
	 * Gets the static XML Schema Definition Language (XSD) schema type of this validated XML node.
	 *
	 * @return XmlSchemaType An XmlSchemaType of this validated XML node.
	 */
	public function getSchemaType()
	{
		return $this->SchemaType;
	}

	/**
	 * Gets the XmlSchemaValidity value of this validated XML node.
	 *
	 * @return XmlSchemaValidity An XmlSchemaValidity value of this validated XML node.
	 */
	public function getValidity()
	{
		return $this->Validity;
	}

}
