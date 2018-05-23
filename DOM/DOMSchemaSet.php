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

use lyquidity\xml\MS\XmlSchemaType;
use lyquidity\xml\QName;
use lyquidity\xml\schema\SchemaTypes;

/**
 * Implements XmlSchemaSet for DOM instances
 */
class DOMSchemaSet extends XmlSchemaSet
{
	/**
	 * Initializes a new instance of the XmlSchemaSet class with the specified System.Xml.XmlNameTable.
	 *
	 * @param XmlNameTable $nameTable The System.Xml.XmlNameTable object to use.
	 * @throws
	 *   \lyquidity\xml\exceptions\ArgumentNullException  The System.Xml.XmlNameTable object passed as a parameter is null.
	 */
	public function __construct( $nameTable )
	{
		parent::__construct( $nameTable );

		$types = SchemaTypes::getInstance();
		foreach ( $types->getProcessedSchemas() as $prefix => $namespace )
		{
			$this->AddSchema( new XmlSchema( $namespace ) );
		}
	}

	/**
	 * Holds the schema cache
	 * @var array $schemaElementCache
	 */
	private $schemaElementCache = array();

	/**
	 * Holds the schema cache
	 * @var array $schemaTypeCache
	 */
	private $schemaTypeCache = array();

	/**
	 * Holds the schema cache
	 * @var array $schemaAttributeCache
	 */
	private $schemaAttributeCache = array();

	/**
	 * Gets a specific global simple or complex type from all the XML Schema definition language (XSD) schemas in the XmlSchemaSet.
	 * @param QName $qualifiedName
	 * @return XmlSchemaElement A specific global element.
	 */
	public function getGlobalElement( $qualifiedName )
	{
		if ( isset( $this->schemaElementCache[ $qualifiedName->__toString() ] ) )
		{
			return $this->schemaElementCache[ $qualifiedName->__toString() ];
		}

		$schemaElement = DOMSchemaElement::fromQName( $qualifiedName );
		$this->schemaElementCache[ $qualifiedName->__toString() ] = $schemaElement;
		return $schemaElement;
	}

	/**
	 * Gets a specific global simple or complex type from all the XML Schema definition language (XSD) schemas in the XmlSchemaSet.
	 * @param QName $qualifiedName
	 * @return XmlSchemaType A specific global type.
	 */
	public function getGlobalType( $qualifiedName )
	{
		if ( isset( $this->schemaTypeCache[ $qualifiedName->__toString() ] ) )
		{
			return $this->schemaTypeCache[ $qualifiedName->__toString() ];
		}

		$schemaType = DOMSchemaType::fromSchemaType( $qualifiedName );
		return $schemaType;
	}

	/**
	 * Gets a specific global attribute from all the XML Schema definition language (XSD) schemas in the XmlSchemaSet.
	 * @param QName $qualifiedName
	 * @return XmlSchemaAttribute A specific global attribute.
	 */
	public function getGlobalAttribute( $qualifiedName )
	{
		if ( isset( $this->schemaAttributeCache[ $qualifiedName->__toString() ] ) )
		{
			return $this->schemaAttributeCache[ $qualifiedName->__toString() ];
		}

		$schemaAttribute = DOMSchemaAttribute::fromQName( $qualifiedName );
		$this->schemaElementCache[ $qualifiedName->__toString() ] = $schemaAttribute;
		return $schemaAttribute;
	}

}