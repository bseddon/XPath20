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

use \lyquidity\XPath2\DOM\DOMSchemaDatatype;
use \lyquidity\xml\MS\XmlSchemaSimpleType;
use \lyquidity\xml\MS\XmlSchemaDerivationMethod;
use lyquidity\xml\QName;

/**
 * Represents the simpleType element for simple content from XML Schema as specified
 * by the World Wide Web Consortium (W3C). This class defines a simple type. Simple
 * types can specify information and constraints for the value of attributes or
 * elements with text-only content.
 */
class DOMSchemaSimpleType extends XmlSchemaSimpleType
{
	/**
	 * Initializes a new instance of the System.Xml.Schema.XmlSchemaSimpleType class.
	 * @param QName $qualifiedName
	 * @param DOMSchemaDatatype $datatype
	 */
	public function __construct( $qualifiedName, $datatype )
	{
		$this->Datatype = $datatype;
		$this->TypeCode = $datatype->TypeCode;
		$this->QualifiedName = $qualifiedName;
		$this->Name = $qualifiedName->localName;
		$this->DerivedBy = XmlSchemaDerivationMethod::Restriction;
	}
}
