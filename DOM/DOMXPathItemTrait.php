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
 * along with this program.  If not, see <http: *www.gnu.org/licenses/>.
 *
*/

namespace lyquidity\XPath2\DOM;

use lyquidity\xml\MS\IXmlNamespaceResolver;
use \lyquidity\xml\MS\XmlSchemaType;
use \lyquidity\XPath2\lyquidity\Type;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\XPath2\XPath2Convert;
use lyquidity\XPath2\Value\DecimalValue;
use lyquidity\xml\schema\SchemaTypes;
use lyquidity\xml\exceptions\InvalidOperationException;
use lyquidity\xml\exceptions\NotSupportedException;

/**
 * Implements the functions required to implement the XPathItem interface
 */
trait DOMXPathItemTrait // implements \lyquidity\xml\xpath\XPathItem
{
	/**
	 * Gets a value indicating whether the item represents an XPath node or an atomic value
	 * @return bool
	 */
	public function getIsNode()
	{
		return ! is_null( $this->domNode ) && isset( DOMXPathNavigator::$nodeTypeMap[ $this->domNode->nodeType ] );
	}

	/**
	 * Gets the current item as a boxed object of the most appropriate PHP type according to its schema type.
	 * @return object
	 */
	public function getTypedValue()
	{
		return XPath2Convert::GetTypedValue( $this );
		return $this->ValueAs( $this->getValueType() );
	}

	/**
	 * Gets the string value of the item
	 * @return string
	 */
	public function getValue()
	{
		if ( is_null( $this->domNode ) )
		{
			return "";
		}

		if ( $this->domNode instanceof \DOMNameSpaceNode )
		{
			/** @var \DOMNameSpaceNode $ns */
			$ns = $this->domNode;
			return $ns->nodeValue;
		}

		// BMS 2017-12-30 This used to return the textContent property.
		// BMS 2018-01-23 Updated to return the text content when the node is the document root
		return $this->domNode instanceof \DOMDocument
			? $this->domNode->textContent
			: $this->domNode->nodeValue;
	}

	/**
	 * Convert the value to a boolean
	 * @return bool
	 */
	public function getValueAsBoolean()
	{
		return filter_var( $this->getValue(), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Convert the value to a date time
	 * @return \DateTime
	 */
	public function getValueAsDateTime()
	{
		throw new NotSupportedException( "DOMXPathItem::getValueAsDateTime" );
	}

	/**
	 * Convert the value to a decimal
	 * @return double
	 */
	public function getValueAsDecimal()
	{
		if ( $this->getValue() instanceof DecimalValue ) return $this->getValue();
		throw new InvalidOperationException( "The value '{$this->getValue()}' is not a valid decimal" );
	}

	/**
	 * Convert the value to a double
	 * @return double
	 */
	public function getValueAsDouble()
	{
		if ( is_numeric( $this->getValue() ) ) return $this->getValue() + 0.0;
		throw new InvalidOperationException( "The value '{$this->getValue()}' is not a valid double" );
	}

	/**
	 * Convert the value to a integer
	 * @return int
	 */
	public function getValueAsInt()
	{
		if ( is_numeric( $this->getValue() ) ) return round( $this->getValue() + 0, 0 );
		throw new InvalidOperationException( "The value '{$this->getValue()}' is not a valid integer" );
	}

	/**
	 * Convert the value to a long
	 * @return int
	 */
	public function getValueAsLong()
	{
		return $this->getValueAsInt();
	}

	/**
	 * Gets the PHP runtime type of the item.
	 * @return Type $ValueType
	 */
	public function getValueType()
	{
		$xmlType = SchemaTypes::getInstance()->getTypeForDOMNode( $this->domNode );
		// $xmlType = $this->types->getType( $this->domNode->localName, $this->domNode->prefix );
		if ( $xmlType )
		{
			$type = Type::XmlTypeToType( $xmlType );
			if ( $type ) return $type;
		}
		return Type::string;
	}

	/**
	 * When overridden in a derived class, gets the XmlSchemaType for the item.
	 *
	 * @return XmlSchemaType The System.Xml.Schema.XmlSchemaType for the item.
	 */
	public function getXmlType()
	{
		if ( ! property_exists( $this->domNode, "schemaType" ) )
		{
			$effectiveNode = $this->domNode->nodeType == XML_TEXT_NODE ? $this->domNode->parentNode : $this->domNode;
			$this->domNode->schemaType = DOMSchemaType::getSchemaType( $effectiveNode );
		}
		return $this->domNode->schemaType;
	}

	/**
	 * Get the value as the type defined in $name
	 * @param string $name The name of the type to return
	 * @throws NotSupportedException
	 * @return boolean|object|string|\DateTime|number|int|\lyquidity\XPath2\lyquidity\Type|\lyquidity\xml\MS\XmlSchemaType
	 */
	public function getAsProperties( $name )
	{
		switch( $name )
		{
			case "IsNode":
				return $this->getIsNode();

			case "TypedValue":
				return $this->getTypedValue();

			case "Value":
				return $this->getValue();

			case "ValueAsBoolean":
				return $this->getValueAsBoolean();

			case "ValueAsDateTime":
				return $this->getValueAsDateTime();

			case "ValueAsDouble":
				return $this->getValueAsDouble();

			case "ValueAsDecimal":
				return $this->getValueAsDecimal();

			case "ValueAsInt":
				return $this-> getValueAsInt();

			case "ValueAsLong":
				return $this->getValueAsLong();

			case "ValueType":
				return $this->getValueType();

			case "XmlType":
				return $this->getXmlType();

			case "SchemaType":
				return $this->getXmlType();

			default:
				throw new NotSupportedException( "Calls to '$name' are not supported." );
		}
	}

	/**
	 * Get the schema type associated with the current node
	 * @return XmlSchemaType
	 */
	public function GetSchemaType()
	{
		return $this->getXmlType();
	}

	/**
	 * Get the value convert to the type in $returnType
	 * @param Type $returnType
	 * @param IXmlNamespaceResolver $nsResolver
	 *
	 * @return object
	 */
	public function ValueAs( $returnType, $nsResolver = null )
	{
		switch ( $returnType )
		{
			case Type::bool:
				return $this->getValueAsBoolean();
				break;

			case Type::int:
			case Type::long:
				return $this->getValueAsInt();
				break;

			case Type::datetime:
				return $this->getValueAsDateTime();
				break;

			case Type::decimal:
				return $this->getValueAsDecimal();

			case Type::double:
			case Type::float:
				return $this->getValueAsDouble();
				break;

			case Type::string:
				return $this->getValue();
				break;

			default:
				throw new NotSupportedException( "" );
		}
	}

}

