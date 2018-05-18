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

use \lyquidity\XPath2\lyquidity\Type;
use \lyquidity\xml\MS\XmlTypeCode;
use \lyquidity\xml\MS\XmlTokenizedType;
use \lyquidity\xml\MS\XmlSchemaDatatypeVariety;
use \lyquidity\xml\MS\XmlSchemaDatatype;
use \lyquidity\xml\schema\SchemaTypes;

/**
 * Create a DOMSchemaDatatype instance
 */
class DOMSchemaDatatype extends XmlSchemaDatatype
{
	/**
	 * Initializes a new instance of the XmlSchemaDatatype class.
	 * @param string $XmlType
	 */
	public function __construct( $XmlType )
	{
		if ( is_null( $XmlType ) )
		{
			$this->TypeCode = XmlTypeCode::UntypedAtomic;
			$this->TokenizedType = XmlTokenizedType::None;
			$this->simpleType = true;
		}
		else
		{
			$this->SimpleType = SchemaTypes::getInstance()->resolvesToBaseType( $XmlType, array( "xs:anySimpleType", "xsd:anySimpleType" ) );

			// Could get and pass the atomic type to each of these functions
			$this->TypeCode = XmlTypeCode::TypeCodeForXmlType( $XmlType );
			$this->TokenizedType = XmlTokenizedType::TokenizedTypeFromXmlType( $XmlType );
			$this->ValueType = Type::XmlTypeToType( $XmlType );
		}

		$this->Variety = XmlSchemaDatatypeVariety::Atomic;

		// These are only needed for debugging
		$this->TypeCodeName = XmlTypeCode::getCodeName( $this->TypeCode );
		if ( $this->TypeCodeName === false ) $this->TypeCodeName = "Unknown type code: {$this->TypeCode}";

		$this->TokenizedTypeName = XmlTokenizedType::getTokenName( $this->TokenizedType );
		if ( $this->TokenizedTypeName === false ) $this->TokenizedTypeName = "Unknown token type: {$this->TokenizedType}";

		if ( $this->SimpleType )
		{
			$this->VarietyName = XmlSchemaDatatypeVariety::getVarietyName( $this->Variety );
			if ( $this->VarietyName === false ) $this->VarietyName = "Unknown variety code: {$this->Variety}";
		}
	}

	/**
	 * When overridden in a derived class, gets the type for the string as specified
	 * in the World Wide Web Consortium (W3C) XML 1.0 specification.
	 *
	 * @var XmlTokenizedType $TokenizedType An XmlTokenizedType (enum) value for the string.
	 */
	public $TokenizedType = null;

	/**
	 * Gets the XmlTypeCode value for the simple type.
	 *
	 * @var XmlTypeCode $TypeCode The XmlTypeCode (enum) value for the simple type.
	 */
	public $TypeCode = null;

	/**
	 * When overridden in a derived class, gets the PHP Runtime type of the item.
	 *
	 * @var Type ValueType The PHP engine type of the item.
	 */
	public $ValueType = null;

	/**
	 * Gets the XmlSchemaDatatypeVariety value for the simple type.
	 *
	 * @var XmlSchemaDatatypeVariety $Variety The XmlSchemaDatatypeVariety (enum) value for the simple type.
	 */
	public $Variety = null;

	/**
	 * True if the type is simple
	 * @var bool
	 */
	public $SimpleType = false;

	/**
	 * Test functions for this class
	 * @param \XBRL_Instance $instance
	 */
	public static function Test( $instance )
	{
		$root = dom_import_simplexml( $instance->getInstanceXml() );

		echo get_class($root) . "\n";
		$node = $root->firstChild;

		/**
		 * @var SchemaTypes $types
		 */
		$types = SchemaTypes::getInstance();

		while ( ($node = $node->nextSibling) != null )
		{
			if ( $node instanceof \DOMElement )
			{
				// echo "{$node->localName} ";
				try
				{
					$defaultNamespace = $instance->getNamespaceForPrefix("");
					$type = $types->getTypeForDOMNode( $node, $defaultNamespace );

					$datatype = new \lyquidity\XPath2\DOM\DOMSchemaDatatype( $type );
					$tokenType = $datatype->TokenizedType;
					$typeCode = $datatype->TypeCode;
					$valueType = $datatype->ValueType;
					$variety = $datatype->Variety;
					$simple = $datatype->SimpleType ? "Simple" : "Complex";

					echo "{$node->localName}: $valueType ($simple)\n";

				}
				catch (\Exception $ex)
				{
					echo "\nError ({$node->localName}): {$ex->getMessage()}\n";
				}
			}

		}

	}
}

