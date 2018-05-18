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
 * along with this program.  If not, see <http: *www.gnu.org/licenses/>.
 *
 */

namespace lyquidity\XPath2\DOM;

use \lyquidity\xml\interfaces\ICloneable;
use \lyquidity\xml\MS\IXmlNamespaceResolver;
use \lyquidity\xml\xpath\XPathItem;
use \lyquidity\xml\xpath\XPathNavigator;
use \lyquidity\xml\xpath\XPathNodeType;
use \lyquidity\xml\xpath\XPathNamespaceScope;
use \lyquidity\xml\MS\IXmlSchemaInfo;
use lyquidity\xml\MS\XmlNamespaceManager;
use lyquidity\xml\MS\XmlNameTable;
use lyquidity\xml\xpath\XPathNavigatorEqualityComparer;
use lyquidity\xml\xpath\XPathNodeIterator;
use lyquidity\xml\MS\XmlNodeOrder;
use lyquidity\xml\MS\XmlReservedNs;
use lyquidity\xml\schema\SchemaTypes;
use lyquidity\xml\exceptions\NotSupportedException;

/**
 * Provides a cursor model for navigating XML data.
 */
class DOMXPathNavigator extends XPathNavigator implements XPathItem, ICloneable, IXmlNamespaceResolver
{
	public static $CLASSNAME = "lyquidity\XPath2\DOM\DOMXPathNavigator";

	/**
	 * This trait provides the implementation of the interface XPathItem
	 */
	use DOMXPathItemTrait;

	/**
	 * A reference to the schema types object for this item
	 * @var SchemaTypes $types
	 */
	protected $types;

	/**
	 * @var \DOMNode $domNode
	 */
	protected $domNode;

	/**
	 *
	 * @var IXmlNamespaceResolver
	 */
	protected $nsManager;

	/**
	 *
	 * @var object $nsTable
	 */
	protected $nsTable;

	/**
	 * An array mapping XML_... constants to XPathNodeType memberss
	 * @var array
	 */
	public static $nodeTypeMap = array();

	/**
	 * Static constructor
	 */
	static function __static()
	{

		DOMXPathNavigator::$nodeTypeMap = array(
			XML_ELEMENT_NODE => XPathNodeType::Element,
			XML_ATTRIBUTE_NODE => XPathNodeType::Attribute,
			XML_TEXT_NODE => XPathNodeType::Text,
			// XML_CDATA_SECTION_NODE => 4,
			// XML_ENTITY_REF_NODE => 5,
			// XML_ENTITY_NODE => 6,
			XML_PI_NODE => XPathNodeType::ProcessingInstruction,
			XML_COMMENT_NODE => XPathNodeType::Comment,
			XML_DOCUMENT_NODE => XPathNodeType::Root,
			// XML_DOCUMENT_TYPE_NODE => XPathNodeType::Element, // .NET does not consider this an element
			// XML_DOCUMENT_FRAG_NODE => 11,
			XML_NOTATION_NODE => 12,
			// XML_HTML_DOCUMENT_NODE => 13,
			// XML_DTD_NODE => 14,
			XML_ELEMENT_DECL_NODE => XPathNodeType::Element,
			XML_ATTRIBUTE_DECL_NODE => XPathNodeType::Attribute,
			// XML_ENTITY_DECL_NODE => 17,
			XML_NAMESPACE_DECL_NODE => XPathNodeType::NamespaceURI,
		);

	}

	/**
	 * Initializes a new instance of the DOMXPathNavigator class.
	 * @param \DOMNode|\SimpleXMLElement $domNode
	 * @param IXmlNamespaceResolver $nsManager Will be created and populated from SchemaTypes if be provided
	 * @param object $nsTable Will be created if not provided
	 */
	public function __construct( $domNode, $nsManager = null, $nsTable = null )
	{
		if ( $domNode instanceof \SimpleXMLElement )
		{
			$domNode = dom_import_simplexml( $domNode );
		}

		$this->types = SchemaTypes::getInstance();
		if ( is_null( $domNode) ) return;

		$this->domNode = $domNode;
		$this->nsManager = is_null( $nsManager ) ? XmlNamespaceManager::fromSchemaTypes( $this->types ) : $nsManager;
		$this->nsTable = is_null( $nsTable ) ? new XmlNameTable() : $nsTable;

		// if ( $this->domNode instanceof \DOMDocument ) $this->domNode = $this->domNode->documentElement;
	}

	/**
	 * Compares the position of the current XPathNavigator with the
	 * position of the XPathNavigator specified.
	 *
	 * @param XPathNavigator $other : The XPathNavigator to compare against.
	 * @param bool $useLineNo (Default: false)
	 * @return XmlNodeOrder An XmlNodeOrder value representing the comparative position of the two XPathNavigator objects.
	 */
	public function ComparePosition( $other, $useLineNo = false)
	{
		$thisDomNode = $this->getUnderlyingObject();
		$otherDomNode = $other->getUnderlyingObject();
		$doc1 = $thisDomNode->baseURI;
		$doc2 = $otherDomNode->baseURI;

		if ( strcasecmp( $doc1, $doc2 ) != 0 )
		// if ( spl_object_hash( $this->domNode->ownerDocument ) != spl_object_hash( $other->getUnderlyingObject()->ownerDocument ) )
			return XmlNodeOrder::Unknown;

		$thisPath = $thisDomNode->getNodePath();
		$otherPath = $otherDomNode->getNodePath();

		// Convert node names into a set positions
		// This is necessary because the getNodePath returns named nodes.
		// However there has to be a question mark over performance since
		// an XPath query is being performed for every segment of both the
		// 'this' path and the 'other' path.
		// In the case where a parent has named child nodes that are not
		// in a lexical order such as:
		// <a>
		//  <c/>
		//  <b/>
		// </a>
		// Then sorting on the path will result in a list that is not
		// document ordered. By changing the name for the correct position
		// in the parent the set of paths can be sorted.
		$convert = function( $xPath, $nodePath )
		{
			$parts = explode( "/", $nodePath );
			$length = 0;
			foreach ( $parts as $key => &$part )
			{
				$length++;
				if ( $part == "" ) continue;

				$length += strlen( $part );
				$result = $xPath->evaluate( "count(" . substr( $nodePath, 0, $length -1 ) . "/preceding-sibling::*) + 1" );
				$part = $result;
			}
			unset( $part );

			return implode( "/", $parts );
		};

		$xPath = new \DOMXPath( $thisDomNode instanceof \DOMDocument ? $thisDomNode : $thisDomNode->ownerDocument );
		$thisPath = $convert( $xPath, $thisPath );
		$xPath = new \DOMXPath( $otherDomNode instanceof \DOMDocument ? $otherDomNode : $otherDomNode->ownerDocument );
		$otherPath = $convert( $xPath, $otherPath );

		if ( $useLineNo )
		{
			$thisPath = $thisDomNode->getLineNo() . "-" . $thisPath;
			$otherPath = $otherDomNode->getLineNo() . "-" . $otherPath;
		}

		// Using strnatcasecmp so /xx[20] will follow /xx[3] which does not happen with other sort types
		$compare = strnatcasecmp( $thisPath, $otherPath );
		return $compare == 0
			? XmlNodeOrder::Same
			: ( $compare < 0 ? XmlNodeOrder::Before : XmlNodeOrder::After );
	}

	/**
	 * return the line number in the underlying XML document of the current node
	 */
	public function getLineNo()
	{
		if ( ! isset( $this->domNode ) || $this->domNode instanceof \DOMDocument )
		{
			return 0;
		}

		return $this->domNode->getLineNo();
	}

	/**
	 * Used
	 * Gets an IEqualityComparer used for equality comparison of XPathNavigator objects.
	 *
	 * @return IEqualityComparer An IEqualityComparer used for equality comparison of XPathNavigator objects.
	 */
	public static function getNavigatorComparer()
	{
		return new XPathNavigatorEqualityComparer();
	}

	/**
	 * Gets the value of the attribute with the specified local name and namespace URI.
	 *
	 * @param string $localName : The local name of the attribute.
	 * @param string $namespaceURI : The namespace URI of the attribute.
	 *
	 * @return string A string that contains the value of the specified attribute; Empty
	 *     			  if a matching attribute is not found, or if the XPathNavigator
	 *     			  is not positioned on an element node.
	 */
	public function GetAttribute( $localName, $namespaceURI )
	{
		if ( $this->domNode->nodeType != XML_ELEMENT_NODE )
		{
			return "";
		}

		foreach ( $this->domNode->attributes as /** @var \DOMAttr $attribute */ $attribute )
		{
			if ( $attribute->namespaceURI != $namespaceURI || $attribute->localName != $localName )
			{
				continue;
			}

			return (string)$attribute->nodeValue;
		}

		return "";
	}

	/**
	 * When overridden in a derived class, gets the base URI for the current node.
	 *
	 * @return string $BaseURI The location from which the node was loaded, or Empty if there is no value.
	 */
	public function getBaseURI()
	{
		if ( is_null( $this->domNode ) ) return null;
		return $this->domNode->baseURI;
	}

	/**
	 * Gets a value indicating whether the current node has any attributes.
	 *
	 * @var bool $HasAttributes
	 *     Returns true if the current node has attributes; returns false if the current
	 *     node has no attributes, or if the XPathNavigator is not positioned
	 *     on an element node.
	 */
	public function getHasAttributes()
	{
		if ( is_null( $this->domNode ) ) return false;
		return $this->domNode->hasAttributes();
	}

	/**
	 * Not used but implement
	 * Gets a value indicating whether the current node has any child nodes.
	 *
	 * @param XPathNodeType $type
	 * @return bool $HasChildren true if the current node has any child nodes; otherwise, false.
	 */
	public function getHasChildren( $type = null )
	{
		if ( is_null( $this->domNode ) ) return false;
		$hasAnyChildren = $this->domNode->hasChildNodes();
		if ( ! $hasAnyChildren || is_null( $type ) ) return $hasAnyChildren;

		$clone = $this->CloneInstance();
		return $clone->MoveToChild( $type );
	}

	/**
	 * Not used but implement
	 * Gets or sets the markup representing the child nodes of the current node.
	 *
	 * @return string A string that contains the markup of the child nodes of the current node.
	 */
	public function getInnerXml()
	{
		if ( is_null( $this->domNode ) ) return null;
		$owner = $this->domNode instanceof \DOMDocument ? $this->domNode : $this->domNode->ownerDocument;
		$xml = $owner->saveXml( $this->domNode, LIBXML_NOEMPTYTAG );
		$xml = ltrim( preg_replace( "/^<\?xml.*\?>/", "", $xml ) );
		$xml = ltrim( preg_replace( "/^<!DOCTYPE[^>[]*(\[[^]]*\])?>/s", "", $xml ) );
		return $xml;
	}

	/**
	 * Not used but implement
	 * When overridden in a derived class, gets a value indicating whether the current
	 * node is an empty element without an end element tag.
	 *
	 * @return bool true if the current node is an empty element; otherwise, false.
	 */
	public function getIsEmptyElement()
	{
		return ! $this->HasChildren();
	}

	/**
	 * When overridden in a derived class, gets the XPathNavigator.Name
	 * of the current node without any namespace prefix.
	 *
	 * @return string A string that contains the local name of the current node, or Empty
	 *     if the current node does not have a name (for example, text or comment nodes).
	 */
	public function getLocalName()
	{
		if ( is_null( $this->domNode ) ) return null;
		if ( $this->domNode instanceof \DOMNameSpaceNode )
		{
			return $this->domNode->localName;
		}
		else
		{
			return $this->domNode->nodeType == XPathNodeType::ProcessingInstruction
				? "{$this->domNode->nodeName}"
				: "{$this->domNode->localName}";
		}
	}

	/**
	 * When overridden in a derived class, gets the qualified name of the current node.
	 *
	 * @return string A string that contains the qualified XPathNavigator.Name of the
	 *     			  current node, or Empty if the current node does not have a name
	 * 				  (for example, text or comment nodes).
	 */
	public function getName()
	{
		if ( is_null( $this->domNode ) ) return null;

		if ( $this->domNode instanceof \DOMNameSpaceNode )
		{
			return $this->domNode->prefix;
		}
		else
		{
			return empty( $this->domNode->prefix )
				? "{$this->domNode->localName}"
				: "{$this->domNode->prefix}:{$this->domNode->localName}";
		}
	}

	/**
	 * When overridden in a derived class, gets the namespace URI of the current node.
	 *
	 * @return string  A string that contains the namespace URI of the current node,
	 *    			   or Empty if the current node has no namespace URI.
	 */
	public function getNamespaceURI()
	{
		if ( is_null( $this->domNode ) ) return null;
		return is_null( $this->domNode->namespaceURI ) ? "" : $this->domNode->namespaceURI;
	}

	/**
	 * When overridden in a derived class, gets the XmlNameTable of the XPathNavigator.
	 *
	 * @return XmlNameTable An XmlNameTable object enabling you to get the atomized
	 * 						version of a string within the XML document.
	 */
	public function getNameTable()
	{
		return $this->nsTable;
	}

	/**
	 * When overridden in a derived class, gets the XPathNodeType of the current node.
	 *
	 * @return XPathNodeType One of the XPathNodeType values representing the current node.
	 */
	public function getNodeType()
	{
		if ( is_null( $this->domNode ) ) return null;
		return isset(  DOMXPathNavigator::$nodeTypeMap[ $this->domNode->nodeType ] )
			? DOMXPathNavigator::$nodeTypeMap[ $this->domNode->nodeType ]
			: XPathNodeType::Element;
	}

	/**
	 * Gets or sets the markup representing the opening and closing tags of the current node and its child nodes.
	 *
	 * @return string A string that contains the markup representing the opening and closing
	 *    			  tags of the current node and its child nodes.
	 */
	public function getOuterXml()
	{
		if ( is_null( $this->domNode ) ) return "";
		return is_null( $this->domNode->parentNode )
			? $this->domNode->ownerDocument->saveXml( $this->domNode )
			: $this->domNode->ownerDocument->saveXml( $this->domNode->parentNode );
	}

	/**
	 * When overridden in a derived class, gets the namespace prefix associated with the current node.
	 *
	 * @return string A string that contains the namespace prefix associated with the current node.
	 */
	public function getPrefix()
	{
		if ( is_null( $this->domNode ) ) return null;
		return $this->domNode->prefix;
	}

	/**
	 * Gets the schema information that has been assigned to the current node as a result of schema validation.
	 *
	 * @return IXmlSchemaInfo An IXmlSchemaInfo object that contains the schema information
	 * 						  for the current node.
	 */
	public function getSchemaInfo()
	{
		if ( is_null( $this->domNode ) ) return null;

		switch( $this->domNode->nodeType )
		{
			case XML_ATTRIBUTE_NODE:
			case XML_ELEMENT_NODE:
				return new DOMName( $this->domNode );

			case XML_TEXT_NODE:
				return new DOMName( $this->domNode->parentNode );

			default:
				return new DOMSchemaInfo( $this->domNode );
		}
	}

	/**
	 * Not used but implement
	 * Used by XPathNavigator implementations which provide a "ized" XML view over a store,
	 * to provide access to underlying objects.
	 *
	 * @return \DOMNode The default is null.
	 */
	public function getUnderlyingObject()
	{
		return $this->domNode;
	}

	/**
	 * Not used but implement
	 * Gets the xml:lang scope for the current node.
	 *
	 * @return string $XmlLang
	 *     A string that contains the value of the xml:lang scope, or Empty
	 *     if the current node has no xml:lang scope value to return.
	 */
	public function getXmlLang()
	{
		// Take and work on a copy of the node so it can be manipulated
		$node = $this->domNode;

		while ( ! $node instanceof \DOMDocument )
		{
			if ( $node->hasAttributes() )
			{
				foreach ( $node->attributes as $name => $attrNode )
				{
					// This should return the lang attribute of the node if there is one.
					if ( $name != "lang" || $attrNode->localName != "lang" || $attrNode->namespaceURI != XmlReservedNs::xml ) continue;
					return $attrNode->nodeValue;
				}
			}

			// If not, check the parent recursively
			$node = $node->parentNode;
		}

		return "";
	}

	/**
	 * Allow the caller to refer to values as properties
	 * @param string $name
	 */
	public function __get( $name )
	{
		switch( $name )
		{
			case "BaseURI":
				return $this->getBaseURI();

			case "HasAttributes":
				return $this->getHasAttributes();

			case "HasChildren":
				return $this->getHasChildren();

			case "InnerXml":
				return $this->getInnerXml();

			case "IsEmptyElement":
				return $this->getIsEmptyElement();

			case "LocalName":
				return $this->getLocalName();

			case "Name":
				return $this->getName();

			case "NamespaceURI":
				return $this->getNamespaceURI();

			case "NameTable":
				return $this->getNameTable();

			case "NodeType":
				return $this->getNodeType();

			case "OuterXml":
				return $this->getOuterXml();

			case "Prefix":
				return $this->getPrefix();

			case "SchemaInfo":
				return $this->getSchemaInfo();

			case "UnderlyingObject":
				return $this->getUnderlyingObject();

			case "XmlLang":
				return $this->getXmlLang();

			default:
				if ( method_exists( $this, 'getAsProperties' ) )
					return $this->getAsProperties( $name );
				else
					throw new NotSupportedException( "Calls to '$name' are not supported." );

		}
	}

	/**
	 * When overridden in a derived class, creates a new XPathNavigator
	 * positioned at the same node as this XPathNavigator.
	 *
	 * @return XPathNavigator A new XPathNavigator positioned at the same node as this XPathNavigator.
	 */
	public function CloneInstance()
	{
		// Clone in this sense means be able to have a different current position so creating a new
		// DOMXPathNavigator instance should be sufficient. There is no need to clone the current node.
		return is_null( $this->domNode )
			? $this
			: new DOMXPathNavigator( $this->domNode, $this->nsManager, $this->nsTable );
	}

	/**
	 * When overridden in a derived class, determines whether the current XPathNavigator
	 * is at the same position as the specified XPathNavigator.
	 *
	 * @param XPathNavigator $other : The XPathNavigator to compare to this XPathNavigator.
	 *
	 * @return bool  true if the two XPathNavigator objects have the same position; otherwise, false.
	 */
	public function IsSamePosition( $other )
	{
		if ( is_null( $this->domNode ) || is_null( $other ) ) return false;
		return $this->domNode->isSameNode( $other->getUnderlyingObject() );
	}

	/**
	 * IsWhitespaceNode
	 * @return bool
	 */
	public function IsWhitespaceNode()
	{
		return	$this->getNodeType() == XPathNodeType::Text ||
				$this->getNodeType() == XPathNodeType::Whitespace ||
				$this->getNodeType() == XPathNodeType::SignificantWhitespace
					? preg_match( "/^\s+$/", $this->getValue() ) // Test for only whitespace
					: false;
	}

	/**
	 * When overridden in a derived class, moves the XPathNavigator
	 * to the same position as the specified XPathNavigator.
	 *
	 * @param XPathNavigator $other : The XPathNavigator positioned on the node that you want to move to.
	 *
	 * @return bool true if the XPathNavigator is successful moving to the same position as the specified
	 * 				XPathNavigator; otherwise, false. If false, the position of the XPathNavigator is unchanged.
	 */
	public function MoveTo( $other )
	{
		if ( is_null( $this->domNode ) || is_null( $other ) ) return false;

		// Replace the domNode with a clone of the one from $other
		$this->domNode = $other->getUnderlyingObject(); // ->cloneNode();
		return true;
	}

	/**
	 * Moves the XPathNavigator to the child node of the XPathNodeType specified.
	 *
	 * @param XPathNodeType $kind The XPathNodeType of the child node to move to.
	 * @return bool Returns true if the XPathNavigator is successful moving to the child node; otherwise, false.
	 *				If false, the position of the XPathNavigator is unchanged.
	 */
	public function MoveToChild( $kind )
	{
		if ( is_null( $this->domNode ) ||
			 is_null( $kind ) ||
			 ! property_exists( $this->domNode, 'firstChild') // ||
			 // ! $this->domNode->hasChildNodes()
		   ) return false;

		// Create a list of the valid DOM node types based on the $kind value
		$domNodeTypes = array_keys( array_filter(
			\lyquidity\XPath2\DOM\DOMXPathNavigator::$nodeTypeMap,
			function( $nodeType ) use( $kind )
			{
				return $kind == \lyquidity\xml\xpath\XPathNodeType::All || $nodeType == $kind;
			}
		) );

		// Take a copy of the dom node
		/** @var \DOMNode $next */
		$next = $this->domNode->firstChild;
		if ( is_null( $next ) ) return;

		do
		{
			// If the $next node type is valid then store the next node type as the new dom node and return true
			if ( in_array( $next->nodeType, $domNodeTypes ) )
			{
				$this->domNode = $next;
				return true;
			}
		}
		while( ! is_null( $next = $next->nextSibling ) );

		return false;
	}

	/**
	 * When overridden in a derived class, moves the XPathNavigator to the first attribute of the current node.
	 *
	 * @return bool	Returns true if the XPathNavigator is successful moving to the first attribute of the current node;
	 * 				otherwise, false. If false, the position of the XPathNavigator is unchanged.
	 */
	public function MoveToFirstAttribute()
	{
		// If the current node is already an attribute or there are no attributes return false
		if	( is_null( $this->domNode ) || $this->domNode instanceof \DOMAttr || ! $this->domNode->hasAttributes() ) return false;

		$this->domNode = $this->domNode->attributes->item(0);
		return true;
	}

	/**
	 * Moves the XPathNavigator to the attribute with the matching local name and namespace URI.
	 *
	 * @param string $localName : The local name of the attribute.
	 * @param string $namespaceURI : The namespace URI of the attribute; null for an empty namespace.
	 *
	 * @return bool Returns true if the XPathNavigator is successful moving to the attribute; otherwise, false.
	 * 				If false, the position of the XPathNavigator is unchanged.
	 */
	public function MoveToAttribute( $localName, $namespaceURI )
	{
		if ( ! $this->MoveToFirstAttribute() )
		{
			return false;
		}

		do
		{
			if ( $this->getLocalName() == $localName && ( ( is_null( $namespaceURI ) && empty( $this->getNamespaceURI() ) ) || $this->getNamespaceURI() == $namespaceURI ) )
			{
				return true;
			}
		} while ( $this->MoveToNextAttribute() );

		// If any attribute was found but not the desired attribute return to the parent
		$this->MoveToParent();

		return false;
	}

	/**
	 * When overridden in a derived class, moves the XPathNavigator to the first child node of the current node.
	 *
	 * @return bool	Returns true if the XPathNavigator is successful moving to the first child node of the current node;
	 * 				otherwise, false. If false, the position of the XPathNavigator is unchanged.
	 */
	public function MoveToFirstChild()
	{
		if	(
			is_null( $this->domNode ) ||
			( ! $this->domNode instanceof \DOMDocument && ! $this->domNode instanceof \DOMElement ) ||
			! $this->domNode->hasChildNodes()
		) return false;

		// if ( $this->domNode instanceof \DOMDocument )
		// {
		// 	$this->domNode = $this->domNode->documentElement;
		// 	return true;
		// }

		$this->domNode = $this->domNode->firstChild;
		return true;
	}

	/**
	 * When overridden in a derived class, moves the XPathNavigator to the first namespace node that matches the
	 * XPathNamespaceScope specified.
	 *
	 * @param XPathNamespaceScope namespaceScope : An XPathNamespaceScope value describing the namespace scope.
	 *
	 * @return bool	Returns true if the XPathNavigator is successful moving to the first namespace node; otherwise, false.
	 * 				If false, the position of the XPathNavigator is unchanged.
	 */
	public function MoveToFirstNamespace( $namespaceScope = XPathNamespaceScope::Local )
	{
		if	( is_null( $this->domNode ) ) return false;
		if ( $this->domNode->nodeType != XML_ELEMENT_NODE && $this->domNode->nodeType != XML_DOCUMENT_NODE ) return false;

		$expression = $namespaceScope == XPathNamespaceScope::Local
			? 'namespace::*[not(. = ../../namespace::*)]'
			: 'namespace::*';

		$xpath = new \DOMXPath( $this->domNode->nodeType == XML_DOCUMENT_NODE ? $this->domNode : $this->domNode->ownerDocument );
		$namespaces = $xpath->query( $expression, $this->domNode );

		if ( ! $namespaces || $namespaces->length == 0 ) return false;

		$this->domNode = $namespaces[ $namespaces->length - 1 ];
		return true;
	}

	/**
	 * Checks to determine if the current node is an ancestor of $target
	 * @param DOMXPathNavigator $target The node to determine if the current node is an ancestor
	 * @return bool
	 */
	public function isNodeAncestorOf( $target )
	{
		if ( $this->domNode instanceof \DOMDocument ) return false;
		if ( $target->getUnderlyingObject() instanceof \DOMDocument ) return false;

		// Clone so the target's position is not changed on exit
		$targetClone = $target->CloneInstance();
		$targetClone->MoveToParent();
		if ( $this->IsSamePosition( $targetClone ) ) return true;
		return $this->isNodeAncestorOf( $targetClone );
	}

	/**
	 * Moves the XPathNavigator to the first sibling node of the current node.
	 *
	 * @return bool Returns true if the XPathNavigator is successful moving to the first sibling node of the current node;
	 * 				false if there is no first sibling, or if the XPathNavigator is currently positioned on an attribute
	 *     			node. If the XPathNavigator is already positioned on the first sibling, XPathNavigator will return true
	 *     			and will not move its position.If XPathNavigator.MoveToFirst returns false because there is no first
	 *     			sibling, or if XPathNavigator is currently positioned on an attribute, the position of the XPathNavigator
	 *     			is unchanged.
	 */
	public function MoveToFirst()
	{
		if ( ! isset( $this->domNode ) || ! $this->getIsNode() ) return false;

		// The DOMDocument and document element are their own first siblings
		if ( $this->domNode instanceof \DOMDocument || $this->domNode->parentNode instanceof \DOMDocument ) return true;

		$this->domNode = $this->domNode->parentNode->firstChild;
		return true;
	}

	/**
	 * Moves the XPathNavigator to the following element of the XPathNodeType specified, to the boundary specified,
	 * in document order.
	 *
	 * @param XPathNodeType $kind : The XPathNodeType of the element. The XPathNodeType cannot be XPathNodeType.Attribute or
	 * 								XPathNodeType.Namespace.
	 * @param XPathNavigator $end : (optional) The XPathNavigator object positioned on the element boundary which the current
	 * 								XPathNavigator will not move past while searching for the following element.
	 *
	 * @return bool	true if the XPathNavigator moved successfully; otherwise false.
	 */
	public function MoveToFollowing( $kind, $end = null )
	{
		if	(	is_null( $this->domNode ) ||
				is_null( $kind ) ||
			  	$this->domNode instanceof \DOMAttr  ||
				$this->domNode instanceof \DOMNameSpaceNode
			) return false;

		if ( ! is_null( $end ) && $this->IsSamePosition( $end ) ) return false;

		if ( $this->domNode instanceof \DOMDocument )
		{
			$this->domNode = $this->domNode->documentElement;
			return true;
		}

		/**
		 * @var \DOMElement $node
		 */
		$node = $this->domNode;

		while( true )
		{
			if ( $node->hasChildNodes() )
			{
				$node = $node->firstChild;
			}
			else
			{
				while( true )
				{
					if ( is_null( $node->nextSibling ) )
					{
						if ( $node->parentNode instanceof \DOMDocument ) return false;

						$node = $node->parentNode;
					}
					else
					{
						$node = $node->nextSibling;
						if ( ! is_null( $end ) && $node->isSameNode( $end->getUnderlyingObject() ) ) return false;
						break;
					}
				}
			}

			if ( XPathNodeType::All == $kind || $node->nodeType == $kind ) break;
		}

		$this->domNode = $node;
		return true;
	}

	/**
	 * Moves the XPathNavigator to the next sibling node of the current
	 * node that matches the XPathNodeType specified.
	 *
	 * @param XPathNodeType $kind : (optional) The XPathNodeType of the sibling node to move to.
	 * @return bool true if the XPathNavigator is successful moving to the next sibling node; otherwise,
	 * 				false if there are no more siblings or if the XPathNavigator is currently positioned
	 * 				on an attribute node. If false, the position of the XPathNavigator is unchanged.
	 */
	public function MoveToNext( $kind = XPathNodeType::All )
	{
		if ( is_null( $this->domNode ) || is_null( $kind ) /* || ! $this->domNode instanceof \DOMElement */ ) return false;

		// Create a list of the valid DOM node types based on the $kind value
		$domNodeTypes = array_keys( array_filter(
			DOMXPathNavigator::$nodeTypeMap,
			function( $nodeType ) use( $kind )
			{
				return $kind == XPathNodeType::All || $nodeType == $kind;
			}
		) );

		// Take a copy of the dom node
		$next = $this->domNode;
		while( ! is_null( $next = $next->nextSibling ) )
		{
			// If the $next node type is valid then store the next node type as the new dom node and return true
			if ( in_array( $next->nodeType, $domNodeTypes ) )
			{
				$this->domNode = $next;
				return true;
			}
		}

		return false;
	}

	/**
	 * When overridden in a derived class, moves the XPathNavigator
	 * to the next attribute.
	 *
	 * @return bool	Returns true if the XPathNavigator is successful moving to the next attribute;
	 * 				false if there are no more attributes. If false, the position of the XPathNavigator is unchanged.
	 */
	public function MoveToNextAttribute()
	{
		if ( is_null( $this->domNode ) || ! $this->domNode instanceof \DOMAttr )
		{
			return false;
		}

		$next = $this->domNode->nextSibling;
		if ( is_null( $next ) ) return false;

		$this->domNode = $next;
		return true;

	}

	/**
	 * When overridden in a derived class, moves the XPathNavigator to the next namespace node matching the
	 * XPathNamespaceScope specified.
	 *
	 * @param XPathNamespaceScope $namespaceScope : (optional) An XPathNamespaceScope value describing the namespace scope.
	 * @return bool Returns true if the XPathNavigator is successful moving to the next namespace node; otherwise,
	 * 				false. If false, the position of the XPathNavigator is unchanged.
	 */
	public function MoveToNextNamespace( $namespaceScope = XPathNamespaceScope::Local )
	{
		if ( is_null( $this->domNode ) ) return false;
		if ( $this->domNode->nodeType != XML_NAMESPACE_DECL_NODE ) return false;

		$expression = $namespaceScope == XPathNamespaceScope::Local
			? 'namespace::*[not(. = ../../namespace::*)]'
			: 'namespace::*';

		$xpath = new \DOMXPath( $this->domNode->nodeType == XML_DOCUMENT_NODE ? $this->domNode : $this->domNode->ownerDocument );
		$namespaces = $xpath->query( $expression, $this->domNode->parentNode );

		if ( ! count( $namespaces ) ) return false;

		// Find the current node among the enties in the $namespaces list
		$current = -1;
		foreach ( $namespaces as $node )
		{
			$current++;
			// Can't use the ->isSameNode() function with a DOMNamespaceNode for some reason
			// So this concoction is used instead.
			if ( $this->domNode->parentNode->getNodePath()  . "/" . $this->domNode->nodeName == $node->parentNode->getNodePath()  . "/" . $node->nodeName )
			{
				break;
			}
		}

		if ( $current <= 0 ) return false;

		$current--;

		$this->domNode = $namespaces[ $current ];
		return true;
	}

	/**
	 * When overridden in a derived class, moves the XPathNavigator to the parent node of the current node.
	 *
	 * @return bool	Returns true if the XPathNavigator is successful moving to the parent node of the current node; otherwise,
	 * 				false. If false, the position of the XPathNavigator is unchanged.
	 */
	public function MoveToParent()
	{
		if ( is_null( $this->domNode ) ) return false;

		$parent = $this->domNode->parentNode;
		if ( is_null( $parent ) ) return false;

		$this->domNode = $parent;
		return true;
	}

	/**
	 * When overridden in a derived class, moves the XPathNavigator to the previous sibling node of the current node.
	 *
	 * @return bool	Returns true if the XPathNavigator is successful moving to the previous sibling node; otherwise,
	 * 				false if there is no previous sibling node or if the XPathNavigator is currently positioned on an
	 * 				attribute node. If false, the position of the XPathNavigator is unchanged.
	 */
	public function MoveToPrevious()
	{
		if ( is_null( $this->domNode ) || $this->domNode instanceof \DOMAttr ) return false;

		$previous = $this->domNode->previousSibling;
		if ( is_null( $previous ) ) return false;

		$this->domNode = $previous;
		return true;

	}

	/**
	 * Moves the XPathNavigator to the root node that the current node belongs to.
	 * @return void
	 */
	public function MoveToDocumentElement()
	{
		if ( is_null( $this->domNode ) ) return false;

		// Already at the root?
		$newNode =  $this->domNode->nodeType == XML_DOCUMENT_NODE
			? $this->domNode
			: $this->domNode->ownerDocument;

		$this->domNode = $newNode->documentElement;
		return true;
	}

	/**
	 * Moves the XPathNavigator to the root node that the current node belongs to.
	 * @return void
	 */
	public function MoveToRoot()
	{
		if ( is_null( $this->domNode ) ) return false;

		// Already at the root?
		if ( $this->domNode->nodeType == XML_DOCUMENT_NODE ) return true;

		$this->domNode = $this->domNode->ownerDocument;
		return true;
	}

	/**
	 * Not used
	 * Selects all the child nodes of the current node that have the local name and
	 * namespace URI specified.
	 *
	 * @param string $name : The local name of the child nodes.
	 * @param string $namespaceURI : The namespace URI of the child nodes.
	 *
	 * @return XPathNodeIterator An XPathNodeIterator that contains the selected nodes.
	 *
	 * @throws  \lyquidity\xml\exceptions\ArgumentNullException: null cannot be passed as a parameter.
	 */
	public function SelectChildrenByName( $name, $namespaceURI )
	{
		$clone = $this->CloneInstance();
		if ( ! $clone->getHasChildren() )
			return new XPathNodeIterator( null, null );

		$clone->MoveToFirstChild();
		return new XPathNodeIterator( $this, function( /** @var DOMXPathNavigator $nav */ $nav ) use( $name, $namespaceURI )
		{
			return $nav->getLocalName() == $name && ( empty( $namespace ) || $nav->getNamespaceURI() == $namespaceURI ) ;
		} );
	}

	/**
	 * Not used
	 * Selects all the child nodes of the current node that have the local name and
	 * namespace URI specified.
	 *
	 * @param XPathNodeType $name : The type of the child nodes to return.
	 *
	 * @return XPathNodeIterator An XPathNodeIterator that contains the selected nodes.
	 *
	 * @throws  \lyquidity\xml\exceptions\ArgumentNullException: null cannot be passed as a parameter.
	 */
	public function SelectChildrenByType( $type )
	{
		$clone = $this->CloneInstance();
		if ( ! $clone->getHasChildren() )
			return EmptyIterator::$Shared; // XPathNodeIterator( null, null );

			if ( ! $clone->MoveToFirstChild() )
				return EmptyIterator::$Shared; //XPathNodeIterator( null, null );

				return new XPathNodeIterator( $clone, function( /** @var DOMXPathNavigator $nav */ $nav ) use( $type )
				{
					$nodeType = $nav->getNodeType();
					return $type == XPathNodeType::All || $type == $nodeType;
				} );
	}

	/**
	 * Gets the text value of the current node.
	 *
	 * @return string A string that contains the text value of the current node.
	 */
	public function ToString()
	{
		if ( is_null( $this->domNode ) ) return "";
		if ( $this->domNode instanceof \DOMDocument )
		{
			$result = $this->domNode->saveXML( null );
			// Remove any initial PI
			$result = preg_replace( "/^<\?xml.*\?>\s/", "", $result );
			return $result;
		}
		else
		{
			// InnerXml?
			// return array_reduce(
			// 	iterator_to_array( $this->domNode->childNodes ),
			// 	function ( $carry, /** @var \DOMNode */ $child )
			// 	{
			// 		return $carry.$child->ownerDocument->saveXML( $child );
			// 	}
			// );
			$result = $this->domNode->ownerDocument->SaveXML( $this->domNode );
			return $result;
		}
	}

	/**
	 * Test functions for this class
	 * @param \XBRL_Instance $instance
	 */
	public static function Test( $instance )
	{
		$root = dom_import_simplexml( $instance->getInstanceXml() );

		echo get_class($root) . "\n";
		$node = $root->firstChild;
		while ( ($node = $node->nextSibling) != null )
		{
			if ( $node instanceof \DOMElement )
			{
				// echo "{$node->localName} ";
				try
				{
					$xpathNode = new \lyquidity\XPath2\DOM\DOMXPathNavigator( $node );
					$value = $xpathNode->getValue();
					$valueType = $xpathNode->getValueType();
					$typedValue = $xpathNode->getTypedValue();
					$xmlType = $xpathNode->getXmlType();
					$bool = $xpathNode->getValueAsBoolean();
					// $value = $xpathNode->ValueAs( Type::decimal );
					echo "{$node->localName}: $value\n";
				}
				catch (\Exception $ex)
				{
					echo "\nError ({$node->localName}): {$ex->getMessage()}\n";
				}
			}

		}
	}

	public static function TestNavigation()
	{
		$doc = new \DOMDocument();
		$doc->load( __DIR__ . "/../context-test-cases.xml" );
		echo get_class( $doc );
		echo "{$doc->localName}\n";

		echo "{$doc->baseURI}\n";
		echo "$doc->nodeType\n";
		/**
		 *
		 * @var DOMNode $result
		 */
		// $result = $doc->nextSibling; // Null
		// $result = $doc->firstChild; // PI
		$nav = new \lyquidity\XPath2\DOM\DOMXPathNavigator( $doc );
		$nav1 = $nav->CloneInstance();

		$result = XmlNodeOrder::toString( $nav->ComparePosition( $nav1 ) );
		$result = XmlNodeOrder::toString( $nav1->ComparePosition( $nav ) );

		$state = $nav->MoveToChild( XPathNodeType::Element ); // True
		$result = XmlNodeOrder::toString( $nav->ComparePosition( $nav1 ) );
		$result = XmlNodeOrder::toString( $nav1->ComparePosition( $nav ) );

		$state = $nav->MoveToChild( XPathNodeType::Element ); // True
		$result = XmlNodeOrder::toString( $nav->ComparePosition( $nav1 ) );
		$result = XmlNodeOrder::toString( $nav1->ComparePosition( $nav ) );

		$result = $nav1->MoveTo( $nav );
		$result = XmlNodeOrder::toString( $nav->ComparePosition( $nav1 ) );
		$result = XmlNodeOrder::toString( $nav1->ComparePosition( $nav ) );

		$result = $nav->MoveToParent();
		$result = XmlNodeOrder::toString( $nav->ComparePosition( $nav1 ) );
		$result = XmlNodeOrder::toString( $nav1->ComparePosition( $nav ) );

		$isNode = $nav->getIsNode();
		// $isNode = $nav->IsNode;
		$localName = $nav->getLocalName();
		$localName = $nav->LocalName;
		$state = $nav->MoveToFirstNamespace( XPathNamespaceScope::All ); // True
		$state = $nav->MoveToNextNamespace( XPathNamespaceScope::All ); // True
		$state = $nav->MoveToNextNamespace( XPathNamespaceScope::All ); // True
		$state = $nav->MoveToNextNamespace( XPathNamespaceScope::All ); // True
		$state = $nav->MoveToNextNamespace( XPathNamespaceScope::All ); // False
		$state = $nav->MoveToChild( XPathNodeType::Element ); // False
		$info = $nav->getSchemaInfo();
		$validity = $info->Validity;
		$state = $nav->MoveToParent(); // True
		$state = $nav->MoveToFirstAttribute(); // True
		$state = $nav->MoveToNextAttribute();// True
		$state = $nav->MoveToNextAttribute(); // False

		$state = $nav->MoveToChild( XPathNodeType::Element ); // False
		$state = $nav->MoveToParent(); // True
		$state = $nav->MoveToNext( XPathNodeType::Element ); // True
		$state = $nav->MoveToNext( XPathNodeType::Element ); // True
		$state = $nav->MoveToNext( XPathNodeType::Element ); // True
	}
}

DOMXPathNavigator::__static();
