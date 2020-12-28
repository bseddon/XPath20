<?php
/**
 * XPath 2.0 for PHP
 *  _					   _	 _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *	     |___/	  |_|					 |___/
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

namespace lyquidity\XPath2\Value;

use lyquidity\xml\MS\XmlNamespaceManager;
use lyquidity\xml\MS\XmlSchemaType;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\SequenceType;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\xml\QName;
use lyquidity\xml\schema\SchemaTypes;
use lyquidity\xml\exceptions\InvalidCastException;
use lyquidity\xml\exceptions\XmlException;
use lyquidity\XPath2\XPath2Exception;

/**
 * QNameValue (public)
 */
class QNameValue implements IXmlConvertable, IXmlSchemaType
{
	/**
	 * CLASSNAME
	 * @var string
	 */
	public static $CLASSNAME = "lyquidity\XPath2\Value\QNameValue";

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->Prefix = $this->LocalName = $this->NamespaceUri = "";
	}

	/**
	 * Constructor
	 * @param string $prefix
	 * @param string $localName
	 * @param string $ns
	 * @param XmlNamespaceManager $resolver
	 */
	public static function fromParts( $prefix, $localName, $ns, $resolver )
	{
		if ( is_null( $localName ) )
		{
			throw new \Exception("$localName");
		}

		if ( is_null( $ns ) )
		{
			throw new \Exception("$ns");
		}

		if ( is_null( $resolver ) )
		{
			throw new \Exception("$resolver");
		}

		if ($prefix != "" && $ns == "")
		{
			throw XPath2Exception::withErrorCodeAndParam("FOCA0002", Resources::FOCA0002, "{$prefix}:{$localName}" );
		}

		try
		{
			$pattern = NameValue::$ncName;

			// if ( ! preg_match( "/^[\p{L}_][\p{L}\p{N}_\-.]*$/u", $localName ) ) // NCName Start char + NCName
			if ( ! preg_match( "/^$pattern$/u", $localName ) ) // NCName Start char + NCName
			{
				throw new \InvalidArgumentException();
			}
			if ( empty( $prefix ) )
			{
				$prefix = $resolver->lookupPrefix( $ns );
				if ( ! $prefix ) $prefix = "";
			}
			$qname = new QNameValue();
			$qname->Prefix = $prefix;
			$qname->LocalName = $localName;
			$qname->NamespaceUri = $ns;
			return $qname;
		}
		catch( XmlException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $localName, "xs:QName" ) );
		}

	}

	/**
	 * Parse
	 * @param string $qname
	 * @param XmlNamespaceManager $resolver
	 * @return QNameValue
	 */
	public static function fromNCName( $qname, $resolver )
	{
		return QNameValue::fromNCNameAndDefault( $qname, $resolver, $resolver->DefaultNamespace );
	}

	/**
	 * Parse
	 * @param string $qname
	 * @param XmlNamespaceManager $resolver
	 * @param string $defaultNs
	 * @return QNameValue
	 */
	public static function fromNCNameAndDefault( $qname, $resolver, $defaultNs )
	{
		$qn = \lyquidity\xml\qname( $qname, $resolver->getNamespaces() );
		if ( ! $qn )
		{
			if ( strpos( $qname, ":" ) )
			{
				$prefix = strstr( $qname, ":", true );

				// Look to see if the default namespace is relevant
				$types = SchemaTypes::getInstance();
				$schemas = $types->getProcessedSchemas();
				if ( isset( $schemas[ $prefix ] ) )
				{
					$qn = \lyquidity\xml\qname( $qname, $schemas );
				}
				else
				{
					$namespace = $resolver->lookupNamespace( $prefix );
					if ( ! $namespace )
					{
						throw XPath2Exception::withErrorCodeAndParams( "XPST0081", Resources::XPST0081, array( $qname, "xs:QName" ) );
					}
				}
			}

			if ( ! $qn )
			{
				throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $qname, "xs:QName" ) );
			}
		}

		if ( empty( $qn->prefix ) )
		{
			return QNameValue::fromParts( "", $qn->localName, $defaultNs, $resolver );
		}

		if ( empty( $qn->namespaceURI ) )
		{
			throw XPath2Exception::withErrorCodeAndParam( "XPST0081", Resources::XPST0081, $qn->prefix );
		}

		$pattern = NameValue::$ncName;
		if ( ! preg_match( "/" . NameValue::$ncName . "/u", $qn->localName ) )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $qname, "xs:QName" ) );
		}

		// The prefix may defined with a local namespace:
		//
		// <el xmlns:z="http://example.com">z:qn</el>
		//
		// This qn will not be comparable with another qn with another definition in the parent
		//
		// <root xmlns:t="http://example.com">
		//   <el>t:qn</el>
		// <root>
		// To make the q names comparable make sure the prefix is that of the namespace defined in the parent if there is one
		// This can be done by removing the prefix from the list of namespaces.  If there is a parent namespace it will still
		// be in the list and can be located and the prefix changed accordingly.

		$namespaces = $resolver->getNamespaces();
		unset( $namespaces[ $qn->prefix ] );
		foreach ( $namespaces as $prefix => $namespace )
		{
			if ( $namespace == $qn->namespaceURI )
			{
				$qn->prefix = $prefix;
				break;
			}
		}
		return QNameValue::fromParts( $qn->prefix, $qn->localName, $qn->namespaceURI, $resolver );
	}

	/**
	 * Create a QNameValue from a QName
	 * @param QName $qn
	 */
	public static function fromQName( $qn )
	{
		$qname = new QNameValue();
		$qname->LocalName = $qn->localName;
		$qname->Prefix = $qn->prefix;
		$qname->NamespaceUri = $qn->namespaceURI;

		if ( strlen( $qn->localName ) == 0 ||
			 ! preg_match( "/^" . NameValue::$ncName . "$/u", $qn->localName ) ||
			 ( strlen( $qn->prefix ) != 0 && ! preg_match( "/^" . NameValue::$ncName . "$/u", $qn->prefix ) )
		)
		{
			throw XPath2Exception::withErrorCodeAndParam( "FOCA0002", Resources::FOCA0002, $qn->__toString() );
		}

		return $qname;
	}

	/**
	 * Create a QNameValue instance fron an XPathNavigator instance
	 * @param XPathNavigator $node
	 * @return QNameValue
	 */
	public static function fromXPathNavigator( $node )
	{
		return QNameValue::fromQName( new \lyquidity\xml\qname( $node->getPrefix(), $node->getNamespaceURI(), $node->getLocalName() ) );
	}

	/**
	 * Prefix
	 * @var String $Prefix
	 */
	public $Prefix;

	/**
	 * LocalName
	 * @var String $LocalName
	 */
	public $LocalName;

	/**
	 * NamespaceUri
	 * @var String $NamespaceUri
	 */
	public $NamespaceUri;

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$QName;
	}

	/**
	 * Returns the contained value
	 * @return QNameValue
	 */
	public function getValue()
	{
		return $this->ToString();
	}

	/**
	 * Return true is the local name is empty
	 * @return bool
	 */
	public function getIsEmpty()
	{
		return $this->LocalName != "";
	}

	/**
	 * ToQualifiedName
	 * @return QName
	 */
	public function ToQualifiedName()
	{
		return new \lyquidity\xml\qname( $this->Prefix, $this->NamespaceUri, $this->LocalName );
	}

	/**
	 * Returns the qname using clark notation ({mynamespace}elementname)
	 * @return string
	 */
	public function ToClarkNotation()
	{
		return $this->ToQualifiedName()->clarkNotation();
	}

	/**
	 * ToString
	 * @return string
	 */
	public function ToString()
	{
		$result = $this->LocalName;

		// if ( ! empty( $this->NamespaceUri ) )
		// {
		// 	$result = "{{$this->NamespaceUri}}$result";
		// }
		// return $result;

		if ( ! empty( $this->Prefix ) )
		{
			$result = $this->Prefix . ':' . $result;
		}
		return $result;
	}

	/**
	 * Return a stringified version of the object
	 * @return string
	 */
	public function __toString()
	{
		return $this->ToString();
	}

	/**
	 * Equals
	 * @param object $obj
	 * @return bool
	 */
	public function Equals( $obj )
	{
		if ( ! $obj instanceof QNameValue )
		{
			return false;
		}

		/**
		 * @var QNameValue $other
		 */
		$other = $obj;
		// // BMS 2017-09-13 The xfi test fact-dimension-s-equal2 V18 requires this comparison
		// return (string)$this == (string)$other;
		return $this->LocalName == $other->LocalName && $this->NamespaceUri == $other->NamespaceUri;
	}

	/**
	 * ValueAs
	 * @param SequenceType $type
	 * @param XmlNamespaceManager $nsmgr
	 * @return object
	 */
	function ValueAs( $type, $nsmgr )
	{
		switch ( $type->TypeCode )
		{
			case XmlTypeCode::AnyAtomicType:
			case XmlTypeCode::QName:

				return $this;

			case XmlTypeCode::String:

				return $this->ToString();

			case XmlTypeCode::UntypedAtomic:

				return new UntypedAtomic( $this->ToString() );

			default:

				throw new InvalidCastException();
		}
	}

	/**
	 * Unit tests
	 */
	public static function tests()
	{
		$resolver = new XmlNamespaceManager();
		$resolver->addNamespace( "xx", "xxNamespace" );
		$resolver->addNamespace( "yy", "yyNamespace" );
		$resolver->addNamespace( "", "defaultNamespace" );

		$qname1 = QNameValue::fromParts( "xx", "xxlocalname", "xxNamespace", $resolver );
		$qname2 = QNameValue::fromNCName( "xx:xxlocalname", $resolver );
		$qname3 = QNameValue::fromNCNameAndDefault( "zzlocalname", $resolver, $resolver->getDefaultNamespace() );

		$result = $qname3->getIsEmpty();
		$result = $qname1->ToQualifiedName();
		$result = $qname3->ToQualifiedName();
		$result = $qname1->ToString();
		$result = $qname3->ToString();
		$result = $qname1->Equals( $qname3 );
		$result = $qname3->Equals( $qname3 );
		$type = SequenceType::WithTypeCode( XmlTypeCode::QName );
		$result = $qname3->ValueAs( $type, $resolver );
	}

}

?>
