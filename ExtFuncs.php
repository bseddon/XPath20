<?php
/**
 * XPath 2.0 for PHP
 *  _				       _	 _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *	     |___/    |_|				     |___/
 *
 * @author Bill Seddon
 * @version 0.9
 * @Copyright ( C ) 2017 Lyquidity Solutions Limited
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * ( at your option ) any later version.
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

namespace lyquidity\XPath2;

use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\XPath2\Value\AnyUriValue;
use lyquidity\xml\xpath\XPathNodeType;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\MS\XmlSchemaSimpleType;
use lyquidity\XPath2\Iterator\NodeIterator;
use lyquidity\XPath2\Value\DurationValue;
use lyquidity\XPath2\Value\Integer;
use lyquidity\XPath2\Value\DateTimeValue;
use lyquidity\XPath2\Value\DayTimeDurationValue;
use lyquidity\XPath2\Value\DateValue;
use lyquidity\XPath2\Value\TimeValue;
use lyquidity\XPath2\Iterator\EmptyIterator;
use lyquidity\XPath2\ExtFuncs\DistinctComparer;
use lyquidity\XPath2\Proxy\ValueProxy;
use lyquidity\XPath2\Value\YearMonthDurationValue;
use lyquidity\xml\MS\XmlNamespaceManager;
use lyquidity\xml\xpath\XPathNamespaceScope;
use lyquidity\xml\MS\XmlNamespaceScope;
use lyquidity\XPath2\Value\QNameValue;
use lyquidity\xml\MS\XmlReservedNs;
use \lyquidity\XPath2\lyquidity\Convert;
use lyquidity\xml\MS\XmlTypeCardinality;
use lyquidity\XPath2\Value\DecimalValue;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\XPath2\Value\Long;
use lyquidity\XPath2\Iterator\ExprIterator;
use lyquidity\XPath2\AST\ValueNode;
use lyquidity\XPath2\Value\UntypedAtomic;
use lyquidity\XPath2\Iterator\IDFilterNodeIterator;
use lyquidity\XPath2\Proxy\YearMonthDurationProxy;
use lyquidity\XPath2\Proxy\DayTimeDurationProxy;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\XPath2NodeIterator\SingleIterator;
use lyquidity\xml\MS\XmlQualifiedNameTest;
use lyquidity\XPath2\Iterator\AttributeNodeIterator;
use lyquidity\XPath2\DOM\DOMXPathNavigator;
use lyquidity\xml\QName;
use lyquidity\xml\schema\SchemaTypes;
use lyquidity\xml\exceptions\ArgumentException;
use lyquidity\xml\exceptions\InvalidCastException;
use lyquidity\xml\exceptions\UriFormatException;

/**
 * ExtFuncs ( public static )
 */
class ExtFuncs
{
	/**
	 * GetName
	 * @param IContextProvider $provider
	 * @return string
	 */
	public static function GetNameWithProvider( $provider )
	{
		return GetName( CoreFuncs::NodeValue( CoreFuncs::ContextNode( $provider ) ) );
	}

	/**
	 * GetName
	 * @param XPathNavigator $nav
	 * @return string
	 */
	public static function GetName( $nav )
	{
		if ( is_null( $nav ) || $nav instanceof Undefined )
		    return "";
		return $nav->getName();
	}

	/**
	 * GetNodeName
	 * @param XPath2Context $context
	 * @param XPathNavigator $nav
	 * @return object
	 */
	public static function GetNodeName( $context, $nav )
	{
		if ( ! is_null( $nav ) )
		{
		    if ( $nav->getNodeType() == XPathNodeType::Element || $nav->getNodeType() == XPathNodeType::Attribute )
		    {
				return QNameValue::fromQName( new \lyquidity\xml\qname( $nav->Prefix, $nav->NamespaceURI, $nav->LocalName ) );
		    }
		    else if ( $nav->getNodeType() == XPathNodeType::ProcessingInstruction || $nav->getNodeType() == XPathNodeType::NamespaceURI )
		    {
			   return QNameValue::fromQName( new \lyquidity\xml\qname( "", "", $nav->Name ) );
		    }
		}
		return Undefined::getValue();
	}

	/**
	 * GetID
	 * @param XPath2Context $context
	 * @param IContextProvider $provider
	 * @param string $arg
	 * @param XPathNavigator $node
	 * @param bool $type False = ID, True - IDREF
	 * @return string
	 */
	public static function GetID( $context, $provider, $arg, $node, $type )
	{
		if ( $arg instanceof Undefined )
		{
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
				"empty sequence",
				"xs:string",
			) );
		}

		if ( is_string( $arg ) )
		{
			if ( strlen( $arg ) == 0 )
			{
				return Undefined::getValue();
			}

			$arg = CoreFuncs::NormalizeSpace( $arg );
			// $arg = explode( " ", $arg );
			$arg = ExtFuncs::Tokenize( $arg, " " );
		}

		if ( ! $node instanceof XPathNavigator )
		{
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
				"Node",
				SequenceType::WithTypeCode( SequenceType::GetXmlTypeCodeFromObject( $node ) ),
			) );
		}

		if ( ! $node->getIsNode() )
		{
			$x = 1;
		}

		$iter = XPath2NodeIterator::Create( $node );
		if ( ( $x = $iter->getCount() ) == 0 )
		{
			$x = 1;
		}

		if ( ! $node->MoveToRoot() )
		{
			$x = 1;
		}

		if ( $arg instanceof XPath2Item )
		{
			$arg = SingleIterator::Create( $arg );
		}

		// if ( strlen( $arg ) == 0 ) return Undefined::getValue();
		$iter = IDFilterNodeIterator::fromNodeTest( $context, $arg, $node, $type );
		return $iter;
	}

	/**
	 * CollectionsZeroParam
	 * @param XPath2Context $context
	 * @param IContextProvider $provider
	 * @return string
	 */
	public static function CollectionsZeroParam( $context, $provider )
	{
		// Uses the default collection if one is defined.
		if ( ! isset( $context->defaultCollection ) || empty( $context->defaultCollection ) )
		{
			throw XPath2Exception::withErrorCodeAndParam( "FODC0004", Resources::FODC0004, "default collection" );
		}

		return ExtFuncs::Collections( $context, $provider, $context->defaultCollection );
	}

	/**
	 * Collections
	 * @param XPath2Context $context
	 * @param IContextProvider $provider
	 * @param string $collection A uri representing an existing collection
	 * @return string
	 */
	public static function Collections( $context, $provider, $collection )
	{
		if ( ! ExtFuncs::validateUri( $collection ) || ! isset( $context->availableCollections[ $collection ] ) )
		{
			throw XPath2Exception::withErrorCodeAndParam( "FODC0004", Resources::FODC0004, $collection );
		}

		return $context->availableCollections[ $collection ];
	}

	/**
	 * GetLocalName
	 * @param IContextProvider $provider
	 * @return string
	 */
	public static function GetLocalNameWithProvider( $provider )
	{
		return ExtFuncs::GetLocalName( CoreFuncs::NodeValue( CoreFuncs::ContextNode( $provider ) ) );
	}

	/**
	 * GetLocalName
	 * @param XPathNavigator $nav
	 * @return string
	 */
	public static function GetLocalName( $nav )
	{
		if ( is_null( $nav ) )
		    return "";
		return $nav->LocalName;
	}

	/**
	 * GetNamespaceUri
	 * @param IContextProvider $provider
	 * @return object
	 */
	public static function GetNamespaceUriWithProvider( $provider )
	{
		return ExtFuncs::GetNamespaceUri( CoreFuncs::NodeValue( CoreFuncs::ContextNode( $provider ) ) );
	}

	/**
	 * GetNamespaceUri
	 * @param XPathNavigator $nav
	 * @return object
	 */
	public static function GetNamespaceUri( $nav )
	{
		if ( is_null( $nav ) )
		    return new AnyUriValue( "" );
		return new AnyUriValue( $nav->getNamespaceURI() );
	}

	/**
	 * GetNilled
	 * @param XPath2Context $context
	 * @param XPathNavigator $nav
	 * @return object
	 */
	public static function GetNilled( $context, $nav )
	{
		if ( is_null( $nav ) )
		{
			return Undefined::getValue();
		}

		if ( $nav->getNodeType() != XPathNodeType::Element )
		{
			return Undefined::getValue();
		}

		// Look for an xsi:nil and ignore the element if there is one
		$nodeTest = XmlQualifiedNameTest::create( "nil", SCHEMA_INSTANCE_NAMESPACE );
		$attributes = AttributeNodeIterator::fromNodeTest( $context, $nodeTest, XPath2NodeIterator::Create( $nav ) );
		if ( $attributes->MoveNext() )
		{
			// There is an attrubute.  It is true?
			if ( filter_var( $attributes->getCurrent()->getValue(), FILTER_VALIDATE_BOOLEAN ) )
			{
				return CoreFuncs::$True;
			}
		}

		return false;
	}

	/**
	 * GetBaseUri
	 * @param XPath2Context $context
	 * @param XPathNavigator $nav
	 * @return object
	 */
	public static function GetBaseUri( $context, $nav )
	{
		if ( is_null( $nav ) )
		    return Undefined::getValue();

		if ( !( $nav->getNodeType() == XPathNodeType::Element ||
			 $nav->getNodeType() == XPathNodeType::Attribute ||
			 $nav->getNodeType() == XPathNodeType::Root ||
			 $nav->getNodeType() == XPathNodeType::NamespaceURI ) )
		    return Undefined::getValue();

		$nav = $nav->CloneInstance();
		$uri = array();
		do
		{
		    $baseUri = $nav->getBaseURI();
		    if ( $baseUri != "" )
			   $uri[] = $baseUri;
		}
		while ( $nav->MoveToParent() );

		$res = is_null( $context->RunningContext->BaseUri )
			? null
			: $context->RunningContext->BaseUri;

		for ( $k = count( $uri ) - 1; $k >= 0; $k-- )
		{
		    $res = is_null( $res )
		    	? uri[k]
		    	: SchemaTypes::resolve_path( $res, $uri[k] );
		}

		return is_null( $res )
			? Undefined::getValue()
			: new AnyUriValue( $res );
	}

	/**
	 * GetBaseUri
	 * @param XPath2Context $context
	 * @param IContextProvider $provider
	 * @return object
	 */
	public static function GetBaseUriWithProvider( $context, $provider )
	{
		return ExtFuncs::GetBaseUri( $context, CoreFuncs::NodeValue( CoreFuncs::ContextNode( $provider ) ) );
	}

	/**
	 * DocumentUri
	 * @param XPathNavigator $nav
	 * @return object
	 */
	public static function DocumentUri( $nav )
	{
		if ( is_null( $nav ) )
		    return Undefined::getValue();

		if ( $nav->getNodeType() != XPathNodeType::Root || $nav->getBaseURI() == "" )
		    return Undefined::getValue();

		return new AnyUriValue( $nav->getBaseURI() );
	}

	/**
	 * WriteTrace
	 * @param XPath2Context $context
	 * @param XPath2NodeIterator $iter
	 * @param string $label
	 * @return XPath2NodeIterator
	 */
	public static function WriteTraceWithLabel( $context, $iter, $label )
	{
		$sb = array();

		/**
		 * @var XPathItem $item
		 */
		foreach ( $iter as $item )
		{
			if ( $item instanceof XPath2NodeIterator )
			{
				$sb[] = ExtFuncs::StringJoin( $item, " " );
			}
		    else if ( $item->getIsNode() )
		    {
				/**
				 * @var XPathNavigator $nav
				 */
		    	$nav = $item;
				$sb[] = $nav->OuterXml;
		    }
		    else
		    {
		    	$sb[] = $item->getValue();
		    }

		}

		error_log( ( empty( $label ) ? "" : "$label " ) . implode( ", ", $sb ) );

		// if ( $item instanceof ExprIterator )
		// {
		// 	return $item;
		// }

		$iter->Reset();
		return $iter;

		$mapped = array_map( function( $item ) { return new ValueNode( null, $item ); }, $sb );
		return ExprIterator::fromNodes( $mapped, null, null );
	}

	/**
	 * WriteTrace
	 * @param XPath2Context $context
	 * @param XPath2NodeIterator $iter
	 * @return XPath2NodeIterator
	 */
	public static function WriteTrace( $context, $iter )
	{
		return ExtFuncs::WriteTraceWithLabel( $context, $iter, "" );
	}

	/**
	 * AtomizeIterator
	 * @param XPath2NodeIterator $iter
	 * @return IEnumerable
	 */
	private static function AtomizeIterator( $iter )
	{
		/**
		 * @var XPathNavigator $item
		 */
		foreach ( $iter as $item )
		{
			if ( $item->getIsNode() )
			{
				if ( $item->getNodeType() == XPathNodeType::Comment )
				{
					continue;
				}

				if ( $item->getNodeType() == XPathNodeType::Element )
				{
					$child = $item->CloneInstance();
					$result = $child->MoveToFirstChild();

					$items = array();

					if ( $child->getNodeType() == XPathNodeType::Text )
					{
						$items[] = $item->getTypedValue();
					}
					else
					{
						while ( $result )
						{
							$data = ExtFuncs::GetData( XPath2NodeIterator::Create( $child->CloneInstance() ) );
							if ( $data->MoveNext() )
							{
								$items[] = $data->getCurrent(); // ->getValue();
							}

							$result = $child->MoveToNext();
						}
					}

					if ( ! count( $items ) )
					{
						yield Undefined::getValue();
					}
					else if ( count( $items ) == 1 )
					{
						yield $items[0];
					}
					else
					{
						yield XPath2Item::fromValueAndType( implode( "", $items ), XmlSchema::$String );
					}

					return false;
				}
				else
				if ( is_null( $item->getTypedValue() ) )
				{
			    	if ( $item instanceof XPathNavigator )
					{
						/**
						 * @var XPathNavigator $nav
						 */
						$nav = $item;
						if ( ! is_null( $nav->getSchemaInfo() ) &&
							 ! is_null( $nav->getSchemaInfo()->getSchemaType() ) &&
							 ! ( $nav->getSchemaInfo()->getSchemaType() instanceof XmlSchemaSimpleType ) )
						{
							throw XPath2Exception::withErrorCodeAndParam( "FOTY0012", Resources::FOTY0012, new XmlQualifiedName( $nav->LocalName, $nav->NamespaceURI, false ) );
						}
					}
				}
			}

		    yield $item instanceof XPath2Item
		    	? $item
		    	: XPath2Item::fromValue( $item->GetTypedValue() );
		}
	}

	/**
	 * GetData
	 * @param XPath2NodeIterator $iter
	 * @return XPath2NodeIterator
	 */
	public static function GetData( $iter )
	{
		return new NodeIterator( function() use( $iter ) { return ExtFuncs::AtomizeIterator( $iter ); } );
	}

	/**
	 * Concat
	 * @param XPath2Context $context
	 * @param array $args
	 * @return string
	 */
	public static function Concat( $context, $args )
	{
		$sb = array();
		if ( count( $args ) < 2 )
		    throw XPath2Exception::withErrorCodeAndParams( "XPST0017", Resources::XPST0017,
				array(
					"concat",
					count( $args ),
					XmlReservedNs::xQueryFunc,
				)
			);

		foreach ( $args as $arg )
			if ( ! $arg instanceof Undefined )
				$sb[] = CoreFuncs::Atomize( CoreFuncs::StringValue( $context, $arg ) );

		$result = implode( "", $sb );
		return XPath2Item::fromValueAndType( $result, XmlSchema::$String );
	}

	/**
	 * StringJoin
	 * @param XPath2NodeIterator $iter
	 * @param object $s
	 * @return object
	 */
	public static function StringJoin( $iter, $s )
	{
		if ( $s instanceof Undefined )
		{
			return Undefined::getValue();
		}

		$str = $s . "";
		$sb = array();
		$types = SchemaTypes::getInstance();

		/**
		 * @var XPathItem $item
		 */
		foreach ( $iter as $item )
		{
	    	if ( $item instanceof XPathNavigator )
			{
				/**
				 * @var XPathNavigator $nav
				 */
				$nav = $item;
				if ( ! is_null( $nav->getSchemaInfo() ) )
				{
					if ( ! is_null( $nav->getSchemaInfo()->getSchemaType() ) )
					{
						if ( ! ( $nav->getSchemaInfo()->getSchemaType() instanceof XmlSchemaSimpleType ) )
						{
							throw XPath2Exception::withErrorCodeAndParam( "FOTY0012", Resources::FOTY0012 . ". The node cannot be used as an argument to the string-join function.", new XmlQualifiedNameTest( $nav->LocalName, $nav->NamespaceURI, false ) );
						}
					}

					$qname = $nav->getSchemaInfo()->getSchemaType()->QualifiedName;
					$type = "{$qname->prefix}:{$qname->localName}";

					if ( ! $types->resolvesToBaseType( $type, array( 'xs:string', 'xsd:string' ) ) )
					{
						throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( $type, 'xs:string' ) );
					}
				}
			}

	    	$sb[] = $item->getValue();
		}

		return implode( $str, $sb );
	}

	/**
	 * Substring
	 * @param object $item
	 * @param double $startingLoc
	 * @return string
	 */
	public static function Substring( $item, $startingLoc )
	{
		if ( $item instanceof Undefined )
		{
			return "";
		}

		$value = $item instanceof XPath2Item
			? (string)$item
			: $value = $item . "";

		$pos = $startingLoc instanceof XPath2Item
			? $startingLoc->getTypedValue()
			: $startingLoc;

		$pos = intval( Round( $pos ) ) - 1;
		if ( $pos <= 0 )
		{
			$pos = 0;
		}
		if ( $pos < strlen( $value ) )
		{
			return substr( $value, $pos );
		}
		else
		{
			return "";
		}
	}

	/**
	 * Substring
	 * @param object $item
	 * @param double $startingLoc
	 * @param double $length
	 * @return string
	 */
	public static function SubstringWithLength( $item, $startingLoc, $length )
	{
		if ( $item instanceof Undefined )
		{
			return "";
		}

		$value = (string)$item; // . "";

		if ( $startingLoc instanceof XPath2Item )
		{
			$startingLoc = $startingLoc->getTypedValue();
		}

		if ( $length instanceof XPath2Item )
		{
			$length = $length->getTypedValue();
		}

		if ( $startingLoc == INF ||  $startingLoc == -INF || is_nan( $startingLoc ) || $length == -INF || is_nan( $length ) )
		{
			return "";
		}

		$pos = intval( Round( $startingLoc ) ) - 1;
		$len;
		if ( $length == INF )
		    $len = PHP_INT_MAX;
		else
		    $len = intval( Round( $length ) );

		if ( $pos < 0 )
		{
		    $len = $len + $pos;
		    $pos = 0;
		}

		if ( $pos < strlen( $value ) )
		{
		    if ( $pos + $len > strlen( $value ) )
			   $len = strlen( $value ) - $pos;

		    if ( $len > 0 )
			   return substr( $value, $pos, $len );
		}
		return "";
	}

	/**
	 * StringLength
	 * @param object $source
	 * @return int
	 */
	public static function StringLength( $source )
	{
		if ( $source instanceof Undefined )
		    return 0;
		return strlen( $source . "" );
	}

	/**
	 * StringLength
	 * @param XPath2Context $context
	 * @param IContextProvider $provider
	 * @return int
	 */
	public static function StringLengthWithProvider( $context, $provider )
	{
		return ExtFuncs::StringLength( CoreFuncs::StringValue( $context, CoreFuncs::Atomize( CoreFuncs::ContextNode( $provider ) ) ) );
	}

	/**
	 * NormalizeSpace
	 * @param XPath2Context $context
	 * @param IContextProvider $provider
	 * @return string
	 */
	public static function NormalizeSpace( $context, $provider )
	{
		return CoreFuncs::NormalizeSpace( CoreFuncs::StringValue( $context, CoreFuncs::Atomize( CoreFuncs::ContextNode( $provider ) ) ) );
	}

	/**
	 * NormalizeUnicode
	 * @param object $arg
	 * @param string $form
	 * @return string
	 */
	public static function NormalizeUnicodeWithForm( $arg, $form )
	{
		if ( $arg instanceof Undefined )
		    return "";

		$value = $arg . "";
		$form = trim( $form );

		// normalizer_normalize( $value );

		if ( strcasecmp( $form,"NFC" ) == 0 )
		    return normalizer_normalize( $value, \Normalizer::FORM_C );

		if ( strcasecmp( $form,"NFD" ) == 0 )
			return normalizer_normalize( $value, \Normalizer::FORM_D );

		if ( strcasecmp( $form,"NFKC" ) == 0 )
			return normalizer_normalize( $value, \Normalizer::FORM_KC );

		if ( strcasecmp( $form,"NFKD" ) == 0 )
			return normalizer_normalize( $value, \Normalizer::FORM_KD );

		if ( strlen( $form ) != 0 )
		    throw XPath2Exception::withErrorCodeAndParam( "FOCH0003", Resources::UnsupportedNormalizationForm, $form );

		return $value;
	}

	/**
	 * NormalizeUnicode
	 * @param object $arg
	 * @return string
	 */
	public static function NormalizeUnicode( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return "";
		}

		$value = $arg . "";
		return normalizer_normalize( $arg, \Normalizer::FORM_C );
	}

	/**
	 * UpperCase
	 * @param object $value
	 * @return string
	 */
	public static function UpperCase( $value )
	{
		if ( $value instanceof Undefined )
		    return "";
		return strtoupper( $value . "" );
	}

	/**
	 * LowerCase
	 * @param object $value
	 * @return string
	 */
	public static function LowerCase( $value )
	{
		if ( $value instanceof Undefined )
		    return "";
		return strtolower( $value . "" );
	}

	/**
	 * Translate
	 * @param object $item
	 * @param string $mapString
	 * @param string $transString
	 * @return string
	 */
	public static function Translate( $item, $mapString, $transString )
	{
		if ( $item instanceof Undefined )
		    return "";

		$value = $item . "";
		$result = "";
		for ( $i = 0; $i < strlen( $value ); $i++ )
		{
			if ( ( $mapPos = strpos( $mapString, $value[ $i ] ) ) === false )
			{
				$result .= $value[ $i ];
				continue;
			}

			if ( $mapPos > strlen( $transString ) - 1 ) continue;

			$result .= $transString[ $mapPos ];
		}
		return $result;
	}

	/**
	 * EncodeForUri
	 * @param object $value
	 * @return string
	 */
	public static function EncodeForUri( $value )
	{
		if ( $value instanceof Undefined )
		    return "";

		return rawurlencode( $value . "" );
	}

	/**
	 * IriToUri
	 * @param object $item
	 * @return string
	 */
	public static function IriToUri( $item )
	{
		if ( $item instanceof Undefined )
		    return "";

		$string = $item . "";

		// This code is taken from https://github.com/rmccue/Requests/blob/master/library/Requests/IRI.php
		if ( ! is_string( $string ) ) {
			return "";
		}
		static $non_ascii;
		if ( ! $non_ascii )
		{
			$non_ascii = implode( '', range( "\x80", "\xFF" ) );
			$non_ascii .= "<> \"{}|\^`\n";
		}
		$position = 0;
		$strlen = strlen( $string );
		while ( ( $position += strcspn( $string, $non_ascii, $position ) ) < $strlen )
		{
			$string = substr_replace( $string, sprintf( '%%%02X', ord( $string[ $position ] ) ), $position, 1 );
			$position += 3;
			$strlen += 2;
		}

		return $string;
	}

	/**
	 * EscapeHtmlUri
	 * @param object $item
	 * @return string
	 */
	public static function EscapeHtmlUri( $item )
	{
		if ( $item instanceof Undefined )
		    return "";

	    $value = $item . "";
	    $result = "";

	    for ( $i = 0; $i < strlen( $value ); $i++ )
	    {
			$num = ord( $value[ $i ] );

		    if ( $num >= 0x20 && $num < 0x7f )
			   $result .= chr( $num );
		    else
		    {
			   $num2 = $num / 0x10;
			   $num3 = $num % 0x10;
			   $ch =   $num2 >= 10 ? chr( 0x41 + ( $num2 - 10 ) ) : chr( 0x30 + $num2 );
			   $ch2 =  $num3 >= 10 ? chr( 0x41 + ( $num3 - 10 ) ) : chr( 0x30 + $num3 );
			   $result .= '%';
			   $result .= $ch;
			   $result .= $ch2;
		    }
		}
		return $result;
	}

	/**
	 * Contains
	 * @param object $arg1
	 * @param object $arg2
	 * @return bool
	 */
	public static function Contains( $arg1, $arg2 )
	{
		$str = $arg1 instanceof Undefined
		    ? ""
		    : $arg1 . "";

		$substr = $arg2 instanceof Undefined
			? ""
			: $arg2 . "";

		return strpos( $str, $substr ) !== false;
	}

	/**
	 * Contains
	 * @param XPath2Context $context
	 * @param object $arg1
	 * @param object $arg2
	 * @param string $collation
	 * @return bool
	 */
	public static function ContainsAndCollation( $context, $arg1, $arg2, $collation )
	{
		// CultureInfo culture = $context->RunningContext->GetCulture( $collation );
		return ExtFuncs::Contains( $arg1, $arg2 );
	}

	/**
	 * StartsWith
	 * @param object $arg1
	 * @param object $arg2
	 * @return bool
	 */
	public static function StartsWith( $arg1, $arg2 )
	{
		$str;
		if ( $arg1 instanceof Undefined )
		    $str = "";
		else
		    $str = $arg1 . "";

		$substr;
		if ( $arg2 instanceof Undefined )
		    $substr = "";
		else
		    $substr = $arg2 . "";

		// See the specification for this rule
		if ( strlen( $substr ) == 0 ) return true;

		return SchemaTypes::startsWith( $str, $substr );
	}

	/**
	 * StartsWith
	 * @param XPath2Context $context
	 * @param object $arg1
	 * @param object $arg2
	 * @param string $collation
	 * @return bool
	 */
	public static function StartsWithCollation( $context, $arg1, $arg2, $collation )
	{
		// CultureInfo culture = $context->RunningContext->GetCulture( $collation );
		return ExtFuncs::StartsWith( $arg1, $arg2 );
	}

	/**
	 * EndsWith
	 * @param object $arg1
	 * @param object $arg2
	 * @return bool
	 */
	public static function EndsWith( $arg1, $arg2 )
	{
		$str;
		if ( $arg1 instanceof Undefined )
		    $str = "";
		else
		    $str = $arg1 . "";

		$substr;
		if ( $arg2 instanceof Undefined )
		    $substr = "";
		else
		    $substr = $arg2 . "";

		if ( strlen( $substr ) == 0 ) return true;

		return SchemaTypes::endsWith( $str, $substr );
	}

	/**
	 * EndsWith
	 * @param XPath2Context $context
	 * @param object $arg1
	 * @param object $arg2
	 * @param string $collation
	 * @return bool
	 */
	public static function EndsWithAndCollation( $context, $arg1, $arg2, $collation )
	{
		// CultureInfo culture = $context->RunningContext->GetCulture( $collation );
		return ExtFuncs::EndsWith( $arg1, $arg2 );
	}

	/**
	 * SubstringBefore
	 * @param object $arg1
	 * @param object $arg2
	 * @return string
	 */
	public static function SubstringBefore( $arg1, $arg2 )
	{
		$str = $arg1 instanceof Undefined
		    ? ""
		    : $arg1 ."";

		$substr = $arg2 instanceof Undefined
		    ? ""
		    : $arg2 . "";

		if ( strlen( $str . $substr ) === 0 ) return "";

		$index = strpos( $str, $substr );
		return $index === false
			? ""
			: substr( $str, 0, $index );
	}

	/**
	 * SubstringBefore
	 * @param XPath2Context $context
	 * @param object $arg1
	 * @param object $arg2
	 * @param string $collation
	 * @return string
	 */
	public static function SubstringBeforeAndCollation( $context, $arg1, $arg2, $collation )
	{
		// CultureInfo culture = $context->RunningContext->GetCulture( $collation );
		return ExtFuncs::SubstringBefore( $arg1, $arg2 );
	}

	/**
	 * SubstringAfter
	 * @param object $arg1
	 * @param object $arg2
	 * @return string
	 */
	public static function SubstringAfter( $arg1, $arg2 )
	{
		$str = $arg1 instanceof Undefined
		    ? ""
		    : $arg1 . "";

		$substr = $arg2 instanceof Undefined
		    ? ""
		    : $arg2 . "";

	    if ( strlen( $str . $substr ) === 0 ) return "";

		$index = strpos( $str, $substr );
		return $index === false
			? ""
		    : substr( $str, $index + strlen( $substr ) );
	}

	/**
	 * SubstringAfter
	 * @param XPath2Context $context
	 * @param object $arg1
	 * @param object $arg2
	 * @param string $collation
	 * @return string
	 */
	public static function SubstringAfterAndCollation( $context, $arg1, $arg2, $collation )
	{
		// CultureInfo culture = $context->RunningContext->GetCulture( $collation );
		return ExtFuncs::SubstringAfter( $arg1, $arg2 );
	}

	/**
	 * IsValidReplacementString
	 * @param string $str
	 * @return bool
	 */
	private static function IsValidReplacementString( $str )
	{
		// $charArr = explode( "", $str);
		for ( $k = 0; $k < strlen( $str ); $k++ )
		{
		    if ( $str[ $k ] == '\\' )
		    {
			   if ( $k < strlen( $str ) - 1 && ( $str[ $k + 1 ] == '\\' || $str[ $k + 1 ] == '$' ) )
				  $k++;
			   else
				  return false;
		    }
		    if ( $str[ $k ] == '$' )
		    {
			   if ( $k < strlen( $str ) - 1 && is_numeric( $str[ $k + 1 ] ) )
				  $k++;
			   else
				  return false;
		    }
		}
		return true;
	}

	/**
	 * UnescapeReplacementString
	 * @param string $str
	 * @return String
	 */
	private static function UnescapeReplacementString( $str )
	{
		$result = "";
		$charArr = str_split ( $str );

		for ( $k = 0; $k < count( $charArr ); $k++ )
		{
			if ( $charArr[ $k ] == '\\' )
			{
				if ( $k == count( $charArr ) - 1 )
					throw XPath2Exception::withErrorCodeAndParam( "FORX0004", Resources::FORX0004, $str );

					switch ( $charArr[ $k + 1 ] )
					{
						case 'n':
							$result .= "\n";
							break;

						case 'r':
							$result .="\r";
							break;

						case 't':
							$result .= "\t";
							break;

						case 'a':
							$result .= "\a";
							break;

						case '\\':
							$result .= "\\";
							break;

						default:
							throw XPath2Exception::withErrorCodeAndParam( "FORX0004", Resources::FORX0004, $str );
					}
					$k++;
			}
			else
				$result .= $charArr[ $k ];
		}
		return $result;
	}

	/**
	 * Check and modify the regex pattern so it suits the requirements of the PCRE
	 * @param string $pattern
	 * @param string $flagString
	 * @return string
	 */
	private static function PrepareRegexPattern( $pattern, $flagString )
	{

		// Check the pattern does not include a back-reference in a character class
		$result = preg_match( "/\[.*?\\\\\d+.*?\]/", $pattern );
		if ( $result )
		{
			throw XPath2Exception::withErrorCodeAndParam( "FORX0002", Resources::InvalidRegularExpr, "$pattern/$flagString" );
		}

		// If one of the flags is x then need to remove whitespace not in character classes
		// PCRE will not remove escaped spaces when the x flag is used but XPath expects them to be removed
		if ( strpos( $flagString, "x" ) !== false )
		{
			$results = preg_match_all( "/((?<expr>[^[]*)(?<chars>\[.*?\])?)/", $pattern, $matches );
			for ( $i = 0; $i < $results; $i++ )
			{
				$match = $matches['expr'][ $i ];
				if ( empty( $match) ) continue;
				$result = preg_replace( "/((?![\r\n])\s)+/", "", $match );
				if ( $match == $result ) continue;
				$pattern = str_replace( $match, $result, $pattern );
			}
		}

		// If there is a back-reference need to add an empty comment to separate the
		// back-reference number from any following digit(s) and repeat for all back-references
		// $groups = substr_count( $pattern, "(" );
		// This pattern counts the parentheses that are not escaped by just one backslash (two is OK)
		$groups = preg_match_all( '/\\\\\\\\\(|\((?<!\\\\\()/', $pattern );
		if ( $groups )
		{
			// Get a list of all the back reference numbers
			$result = preg_match_all( "/\\\\(\d(\d?))*/", $pattern, $matches );
			if ( $result )
			{
				// $result2 = preg_match_all( "/\((([^()]+)|(?R))*\)/", $pattern, $parens );

				// Grab the valid values
				$values = array();
				foreach ( array_filter( $matches[1] ) as $key => $match )
				{
					$potentialValues = array();
					while ( strlen( $match ) > 0 )
					{
						$potentialValues[] = $match;
						$match = substr( $match, 0, strlen( $match ) - 1 );
					}

					sort( $potentialValues );
					$error = false;
					while ( count( $potentialValues ) )
					{
						$value = array_pop( $potentialValues );

						// Find the match(es) in the string and count the number of opening parentheses until that point
						$pos = strpos( $pattern, $value );

						// $matchgroupsOpen = substr_count( $pattern, "(", 0, $pos );
						// This horrible list of backslashes occurs because the regex escape char is \ and the same for PHP.
						// So every real backslash has to be quadrupled. Here's what the regex will look like outside PHP
						// 	/\\\\\(|\((?<!\\\()/
						$matchgroupsOpen = preg_match_all( '/\\\\\\\\\(|\((?<!\\\\\()/', substr( $pattern, 0, $pos ) );

						// If there are no groups throw an exception
						$error = ! $matchgroupsOpen || $matchgroupsOpen < $value;

						if ( ! $error )
						{
							// $matchgroupsClosed = substr_count( $pattern, ")", 0, $pos );
							$matchgroupsClosed = preg_match_all( '/\\\\\\\\\)|\)(?<!\\\\\))/', substr( $pattern, 0, $pos ) );

							// If reference is in a capture group its an error
							$error = ! $matchgroupsClosed || $matchgroupsClosed != $matchgroupsOpen || $matchgroupsClosed < $value;

							if ( $error ) continue;
							// This is the longest number
							$values[] = $value;
							break;
						}
					}

					if ( $error )
					{
						throw XPath2Exception::withErrorCode( "FORX0002", Resources::FORX0002 );
					}
				}

				// Make sure the back-reference numbers are valid given the list of capture groups available
				$validValues = array_intersect( $values, range( 1, $groups ) );

				// Sort them in descending order so the longest number is handled first.
				// This way \1 and \11 are handled correctly.
				// BMS Not sure this is necessary.
				rsort( $validValues );
				foreach ( $validValues as $value )
				{
					// Replace the number but only if the number is not already followed by an empty comment
					$pattern = preg_replace( "/\\\\($value)(?!\d?\(\?\#\))/", "\\\\$1(?#)", $pattern );
				}
			}
		}

		// Check to see if there are any character classes that specify character subtraction
		// If so, convert to a negative lookahead
		$results = preg_match_all( "/\[(?<chars>((.*)?(.-.))+)-(?<sub>\[(.*|.*?(.-.))+\])\]/", $pattern, $matches );
		if ( $results )
		{
			for ( $i = 0; $i < $results; $i++ )
			{
				$chars = $matches['chars'][ $i ];
				$sub = $matches['sub'][ $i ];
				$x = "(?!$sub)[$chars]";

				$pattern = str_replace( $matches[ $i ], $x, $pattern );
			}
		}

		// Replace common unicode character classes
		$pattern = str_replace( "{IsBasic", "{", $pattern );

		return $pattern;
	}

	/**
	 * Check the flag string contents
	 * @param string $flagString
	 * @return string
	 */
	private static function PrepareRegexFlags( $flagString )
	{
		// The flag string can only contain s,m,i and/or x
		$result = preg_match( "/[^smix]+/", $flagString);
		if ( $result )
		{
			throw XPath2Exception::withErrorCodeAndParam( "FORX0001", Resources::InvalidRegularExpr, "$flagString" );
		}

		return $flagString;
	}

	/**
	 * Matches
	 * @param object $arg1
	 * @param object $arg2
	 * @param string $flagString
	 * @return bool
	 */
	public static function MatchesWithFlags( $arg1, $arg2, $flagString )
	{
		$input = $arg1 instanceof Undefined
		    ? ""
		    : $arg1 . "";

		if ( $arg2 instanceof Undefined )
		{
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "empty-sequence()", "xs:string in fn:matches" ) );
		}

		$flagString = ExtFuncs::PrepareRegexFlags( $flagString );
		$pattern = ExtFuncs::PrepareRegexPattern( $arg2 . "", $flagString );

		// Replace the handler because the status test handler traps any error and terminates the session
		$previousHandler = set_error_handler(null);
		$result = @preg_match( "/$pattern/$flagString", $input );
		set_error_handler( $previousHandler );
		if ( $result === false )
		{
			throw XPath2Exception::withErrorCodeAndParam( "FORX0002", Resources::InvalidRegularExpr, "$pattern/$flagString" );
		}
		return $result ? CoreFuncs::$True : CoreFuncs::$False;
	}

	/**
	 * Matches
	 * @param object $arg1
	 * @param object $arg2
	 * @return bool
	 */
	public static function Matches( $arg1, $arg2 )
	{
		return ExtFuncs::MatchesWithFlags( $arg1, $arg2, "" );
	}

	/**
	 * Replace
	 * @param object $arg1
	 * @param object $arg2
	 * @param string $replacement
	 * @param string $flagString
	 * @return string
	 */
	public static function ReplaceWithFlags( $arg1, $arg2, $replacement, $flagString )
	{
		$input = $arg1 instanceof Undefined
		    ? ""
		    : $arg1 . "";

		if ( $arg2 instanceof Undefined )
		    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "empty-sequence()", "xs:string in fn:replace" ) );

	    $flagString = ExtFuncs::PrepareRegexFlags( $flagString );
	    $pattern = ExtFuncs::PrepareRegexPattern( $arg2 . "", $flagString );

		if ( ! ExtFuncs::IsValidReplacementString( $replacement ) )
		    throw XPath2Exception::withErrorCodeAndParam( "FORX0004", Resources::FORX0004, $replacement );

		if (  preg_match( "/$pattern/", "" ) )
		{
			throw XPath2Exception::withErrorCodeAndParam( "FORX0003", Resources::FORX0003, $pattern );
		}

		return preg_replace( "/$pattern/$flagString", ExtFuncs::UnescapeReplacementString( $replacement ), $input );
	}

	/**
	 * Replace
	 * @param object $arg1
	 * @param object $arg2
	 * @param string $replacement
	 * @return string
	 */
	public static function Replace( $arg1, $arg2, $replacement )
	{
		return ExtFuncs::ReplaceWithFlags( $arg1, $arg2, $replacement, "" );
	}

	/**
	 * StringEnumerator
	 * @param string $input
	 * @param string $exclude
	 * @param string $flags
	 * @return IEnumerable
	 */
	private static function StringEnumerator( $input, $exclude, $flags )
	{
		$strings = preg_split( "/$exclude/$flags", $input );

		foreach ( $strings as $string )
		{
		    if ( /* $string != "" && */ ! preg_match( "/$exclude/", $string, $flags ) )
			   yield XPath2Item::fromValue( $string );
		}
	}

	/**
	 * Tokenize
	 * @param object $arg1
	 * @param object $arg2
	 * @param string $flagString
	 * @return XPath2NodeIterator
	 */
	public static function TokenizeWithFlags( $arg1, $arg2, $flagString )
	{
		if ( $arg1 instanceof Undefined || empty( $arg1 ) )
		{
			return EmptyIterator::$Shared;
		}

		$input = $arg1 . "";

		if ( $arg2 instanceof Undefined )
		{
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( "empty-sequence()", "xs:string in fn:tokenize" ) );
		}

	    $flagString = ExtFuncs::PrepareRegexFlags( $flagString );
	    $pattern = ExtFuncs::PrepareRegexPattern( $arg2 . "", $flagString );

		if (  preg_match( "/$pattern/", "" ) )
		{
			throw XPath2Exception::withErrorCodeAndParam( "FORX0003", Resources::FORX0003, $pattern );
		}

		return new NodeIterator( function() use( $input, $pattern, $flagString )
		{
			return ExtFuncs::StringEnumerator( $input, $pattern, $flagString );
		} );
	}

	/**
	 * Tokenize
	 * @param object $arg1
	 * @param object $arg2
	 * @return XPath2NodeIterator
	 */
	public static function Tokenize( $arg1, $arg2 )
	{
		return ExtFuncs::TokenizeWithFlags( $arg1, $arg2, "" );
	}

	/**
	 * YearsFromDuration
	 * @param object $arg
	 * @return Integer
	 */
	public static function YearsFromDuration( $arg )
	{
		if ( $arg instanceof Undefined )
		    return Undefined::getValue();

		/**
		 * @var DurationValue $duration
		 */
		$duration = $arg;
		return Integer::FromValue( $duration->getYears() * ( $duration->getInverted() ? -1 : 1 ) );
	}

	/**
	 * MonthsFromDuration
	 * @param object $arg
	 * @return Integer
	 */
	public static function MonthsFromDuration( $arg )
	{
		if ( $arg instanceof Undefined )
		    return Undefined::getValue();

	    /**
	     * @var DurationValue $duration
	     */
	    $duration = $arg;

	    return Integer::FromValue( $duration->getMonths() * ( $duration->getInverted() ? -1 : 1 ) );
	}

	/**
	 * DaysFromDuration
	 * @param object $arg
	 * @return Integer
	 */
	public static function DaysFromDuration( $arg )
	{
		if ( $arg instanceof Undefined )
			return Undefined::getValue();

		/**
		 * @var DurationValue $duration
		 */
		$duration = $arg;

		$days = $duration->getDays() * ( $duration->getInverted() ? -1 : 1 );
		return Integer::FromValue( $days );
	}

	/**
	 * HoursFromDuration
	 * @param object $arg
	 * @return Integer
	 */
	public static function HoursFromDuration( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		/**
		 * @var DurationValue $duration
		 */
		$duration = $arg;

		return Integer::FromValue( $duration->getHours() * ( $duration->getInverted() ? -1 : 1 ) );
	}

	/**
	 * MinutesFromDuration
	 * @param object $arg
	 * @return Integer
	 */
	public static function MinutesFromDuration( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		/**
		 * @var DurationValue $duration
		 */
		$duration = $arg;

		return Integer::FromValue( $duration->getMinutes() * ( $duration->getInverted() ? -1 : 1 ) );
	}

	/**
	 * SecondsFromDuration
	 * @param object $arg
	 * @return DecimalValue
	 */
	public static function SecondsFromDuration( $arg )
	{
		if ( $arg instanceof Undefined )
			return Undefined::getValue();

		/**
		 * @var DurationValue $duration
		 */
		$duration = $arg;

		return DecimalValue::FromValue( $duration->getSeconds() * ( $duration->getInverted() ? -1 : 1 ) );
	}

	/**
	 * YearFromDateTime
	 * @param object $arg
	 * @return int
	 */
	public static function YearFromDateTime( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

	    if ( ! $arg instanceof DateTimeValue )
	    {
	    	return Undefined::getValue();
	    }

    	/**
    	 * @var DateTimeValue $dateTime
    	 */
    	$dateTime = $arg;
    	return $dateTime->Value->format( "Y" ) * ( $dateTime->S ? -1 : 1 );
	}

	/**
	 * MonthFromDateTime
	 * @param object $arg
	 * @return int
	 */
	public static function MonthFromDateTime( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		if ( ! $arg instanceof DateTimeValue )
		{
			return Undefined::getValue();
		}

		/**
		 * @var DateTimeValue $dateTime
		 */
		$dateTime = $arg;
		return $dateTime->Value->format( "m" ) * ( $dateTime->S ? 1 : 1 );
	}

	/**
	 * DayFromDateTime
	 * @param object $arg
	 * @return int
	 */
	public static function DayFromDateTime( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		if ( ! $arg instanceof DateTimeValue )
		{
			return Undefined::getValue();
		}

		/**
		 * @var DateTimeValue $dateTime
		 */
		$dateTime = $arg;
		return $dateTime->Value->format( "d" ) * ( $dateTime->S ? -1 : 1 );
	}

	/**
	 * HoursFromDateTime
	 * @param object $arg
	 * @return int
	 */
	public static function HoursFromDateTime( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		if ( ! $arg instanceof DateTimeValue )
		{
			return Undefined::getValue();
		}

		/**
		 * @var DateTimeValue $dateTime
		 */
		$dateTime = $arg;
		return $dateTime->Value->format( "H" ) * ( $dateTime->S ? -1 : 1 );
	}

	/**
	 * MinutesFromDateTime
	 * @param object $arg
	 * @return int
	 */
	public static function MinutesFromDateTime( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		if ( ! $arg instanceof DateTimeValue )
		{
			return Undefined::getValue();
		}

		/**
		 * @var DateTimeValue $dateTime
		 */
		$dateTime = $arg;
		return $dateTime->Value->format( "i" ) * ( $dateTime->S ? -1 : 1 );
	}

	/**
	 * SecondsFromDateTime
	 * @param object $arg
	 * @return DecimalValue
	 */
	public static function SecondsFromDateTime( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		if ( ! $arg instanceof DateTimeValue )
		{
			return Undefined::getValue();
		}

		/**
		 * @var DateTimeValue $dateTime
		 */
		$dateTime = $arg;
		return DecimalValue::FromValue( ( $dateTime->Value->format( "s" ) + ( @isset( $dateTime->Value->microseconds ) ? "0." . $dateTime->Value->microseconds : 0 ) ) * ( $dateTime->S ? -1 : 1 ) );
	}

	/**
	 * TimezoneFromDateTime
	 * @param object $arg
	 * @return object
	 */
	public static function TimezoneFromDateTime( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		if ( ! $arg instanceof DateTimeValue )
		{
			return Undefined::getValue();
		}

		/**
		 * @var DateTimeValue $dateTime
		 */
		$dateTime = $arg;

		if ( $dateTime->IsLocal )
			return Undefined::getValue();

		return $dateTime->TimezoneToInterval();
	}

	/**
	 * YearFromDate
	 * @param object $arg
	 * @return int
	 */
	public static function YearFromDate( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		if ( ! $arg instanceof DateValue )
		{
			return Undefined::getValue();
		}

		/**
		 * @var DateValue $date
		 */
		$date = $arg;
		return $date->Value->format( "Y" ) * ( $date->S ? -1 : 1 );
	}

	/**
	 * MonthFromDate
	 * @param object $arg
	 * @return int
	 */
	public static function MonthFromDate( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		if ( ! $arg instanceof DateValue )
		{
			return Undefined::getValue();
		}

		/**
		 * @var DateValue $date
		 */
		$date = $arg;
		return $date->Value->format( "m" ) * ( $date->S ? -1 : 1 );
	}

	/**
	 * DayFromDate
	 * @param object $arg
	 * @return int
	 */
	public static function DayFromDate( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		if ( ! $arg instanceof DateValue )
		{
			return Undefined::getValue();
		}

		/**
		 * @var DateValue $date
		 */
		$date = $arg;
		return $date->Value->format( "d" ) * ( $date->S ? -1 : 1 );
	}

	/**
	 * TimezoneFromDate
	 * @param object $arg
	 * @return object
	 */
	public static function TimezoneFromDate( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		if ( ! $arg instanceof DateValue )
		{
			return Undefined::getValue();
		}

		/**
		 * @var DateValue $date
		 */
		$date = $arg;

		if ( $date->IsLocal )
			return Undefined::getValue();

		return $date->TimezoneToInterval();

		return DayTimeDurationValue::Parse( $date->Value->format( "P" ) );
	}

	/**
	 * HoursFromTime
	 * @param object $arg
	 * @return int
	 */
	public static function HoursFromTime( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		if ( ! $arg instanceof TimeValue )
		{
			return Undefined::getValue();
		}

		/**
		 * @var TimeValue $time
		 */
		$time = $arg;
		return $time->Value->format( "H" ) + 0;
	}

	/**
	 * MinutesFromTime
	 * @param object $arg
	 * @return int
	 */
	public static function MinutesFromTime( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		if ( ! $arg instanceof TimeValue )
		{
			return Undefined::getValue();
		}

		/**
		 * @var TimeValue $time
		 */
		$time = $arg;
		return $time->Value->format( "i" ) + 0;
	}

	/**
	 * SecondsFromTime
	 * @param object $arg
	 * @return object
	 */
	public static function SecondsFromTime( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		if ( ! $arg instanceof TimeValue )
		{
			return Undefined::getValue();
		}

		/**
		 * @var TimeValue $time
		 */
		$time = $arg;
		// return $time->Value->format( "s" ) + 0;
		return DecimalValue::FromValue( ( $time->Value->format( "s" ) + ( @isset( $time->Value->microseconds ) ? "0." . $time->Value->microseconds : 0 ) ) );
	}

	/**
	 * TimezoneFromTime
	 * @param object $arg
	 * @return object
	 */
	public static function TimezoneFromTime( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		if ( ! $arg instanceof TimeValue )
		{
			return Undefined::getValue();
		}

		/**
		 * @var TimeValue $time
		 */
		$time = $arg;

		if ( $time->IsLocal )
		{
			return Undefined::getValue();
		}

		return $time->TimezoneToInterval();

		// $offset = $time->Value->getOffset();
		// $invert = $offset >= 0 ? "+" : "-";
		// $offset = abs( $offset );
		// $timezone = DayTimeDurationValue::Parse( "PT{$offset}S" );
		// $timezone->Value->invert = $invert;
        //
		// return $timezone;
	}

	/**
	 * AdjustDateTimeToTimezone
	 * @param object $arg
	 * @return DateTimeValue
	 */
	public static function AdjustDateTimeToTimezone( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		/**
		 * @var DateTimeValue $dateTime
		 */
		$dateTime = $arg;
		// if ( $dateTime->IsLocal ) return $dateTime;

		$dt = clone $dateTime->Value;
		// $dt->IsLocal = true;
		$dtz = new \DateTimeZone( date_default_timezone_get() );
		$dt->setTimezone( $dtz );
		$dateTime = DateTimeValue::fromDate( $dateTime->S,  $dt, false );
		$dateTime->IsLocal = false;
		return $dateTime;
	}

	/**
	 * AdjustDateTimeToTimezone
	 * @param object $arg
	 * @param object $tz
	 * @return DateTimeValue
	 */
	public static function AdjustDateTimeToTimezoneWithTimezone( $arg, $tz )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

	    /**
	     * @var DateTimeValue $dateTime
	     */
	    $dateTime = $arg;

		if ( $tz instanceof Undefined )
		{
		    $dt = DateTimeValue::fromDate( $dateTime->S, $dateTime->Value, false );
		    $dt->IsLocal = true;
		    return $dt;
		}

	    /**
	     * @var DayTimeDurationValue $duration
	     */
	    $duration = $tz;
		if ( $duration->getHours() > 14 || ( $duration->getHours() == 14 && ( $duration->getMinutes() > 0 || $duration->getSeconds() > 0 ) ) )
		{
			throw XPath2Exception::withErrorCodeAndParam( "FODT0003", Resources::FODT0003, $duration->ToString() );
		}

	    /**
	     * @var \DateTimeZone $dtz
	     */
	   	// Create a Timezone instance from the duration.  'R' is the sign
	    $dtz = new \DateTimeZone( $duration->Value->format( "%R%H:%I" ) );
	    try
		{
	    	if ( $dateTime->IsLocal )
	    	{
	    		$dt = new \DateTime( $dateTime->Value->format( "Y-m-d\TH:i:s" ), $dtz );
				return DateTimeValue::fromDate( $dateTime->S, $dt );
	    	}
	    	else
	    	{
	    		$dt = new \DateTime( $dateTime->Value->format( "Y-m-d\TH:i:sP" ) );
				$dt->setTimezone( $dtz );
				return DateTimeValue::fromDate( $dateTime->S, $dt );
	    	}
		}
		catch ( ArgumentException $ex )
		{
		    throw XPath2Exception::withErrorCodeAndParam( "FODT0003", Resources::FODT0003, $duration->ToString() );
		}
	}

	/**
	 * AdjustDateToTimezone
	 * @param object $arg
	 * @return DateValue
	 */
	public static function AdjustDateToTimezone( $arg )
	{
		if ( $arg instanceof Undefined )
			return Undefined::getValue();

		/**
		 * @var DateValue $date
		 */
		$date = $arg;
		if ( $date->IsLocal ) return $date;

		$dt = clone $date->Value;
		$dtz = new \DateTimeZone( date_default_timezone_get() );
		$dt->setTimezone( $dtz );
		$dateTime = DateValue::fromDate( $date->S,  $dt, false );
		$dateTime->IsLocal = false;
		return $dateTime;

	}

	/**
	 * AdjustDateToTimezone
	 * @param object $arg
	 * @param object $tz
	 * @return DateValue
	 */
	public static function AdjustDateToTimezoneWithTimezone( $arg, $tz )
	{
		if ( $arg instanceof Undefined )
			return Undefined::getValue();

		/**
		 * @var DateValue $date
		 */
		$date = $arg;

		if ( $tz instanceof Undefined )
		{
			$dt = DateValue::Parse( ( $date->S ? "-" : "" ) . $date->Value->format( "Y-m-d" ) );
			$dt->IsLocal = true;
			return $dt;
		}

		/**
		 * @var DayTimeDurationValue $duration
		 */
		$duration = $tz;
		if ( $duration->getHours() > 14 || ( $duration->getHours() == 14 && ( $duration->getMinutes() > 0 || $duration->getSeconds() > 0 ) ) )
		{
			throw XPath2Exception::withErrorCodeAndParam( "FODT0003", Resources::FODT0003, $duration->ToString() );
		}

		/**
		 * @var \DateTimeZone $dtz
		 */
		// Create a Timezone instance from the duration.  'R' is the sign
		$dtz = new \DateTimeZone( $duration->Value->format( "%R%H:%I" ) );

		try
		{
			// Create a new DateTValue instance
			if ( $date->IsLocal )
			{
				$dt = new \DateTime( $date->Value->format( "Y-m-d\TH:i:s" ), $dtz );
				return DateValue::fromDate( $date->S, $dt );
			}
			else
			{
				$dt = new \DateTime( $date->Value->format( "Y-m-d\TH:i:sP" ) );
				$dt->setTimezone( $dtz );
				return DateValue::fromDate( $date->S, $dt );
			}
		}
		catch ( ArgumentException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParam( "FODT0003", Resources::FODT0003, $duration->ToString() );
		}

	}

	/**
	 * AdjustTimeToTimezone
	 * @param object $arg
	 * @return object
	 */
	public static function AdjustTimeToTimezone( $arg )
	{
		if ( $arg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		/**
		 * @var TimeValue $time
		 */
		$time = $arg;
		// if ( $time->IsLocal ) return $time;

		$dt = clone $time->Value;
		$dtz = new \DateTimeZone( date_default_timezone_get() );
		$dt->setTimezone( $dtz );
		$dateTime = new TimeValue( $dt );
		$dateTime->IsLocal = false;
		return $dateTime;
	}

	/**
	 * AdjustTimeToTimezone
	 * @param object $arg
	 * @param object $tz
	 * @return object
	 */
	public static function AdjustTimeToTimezoneWithTimezone( $arg, $tz )
	{
		if ( $arg instanceof Undefined )
			return Undefined::getValue();

		/**
		 * @var TimeValue $time
		 */
		$time = $arg;

		if ( $tz instanceof Undefined )
		{
			$dt = TimeValue::Parse( $time->Value->format( "H:i:s" ) );
			$dt->IsLocal = true;
			return $dt;
		}

		/**
		 * @var DayTimeDurationValue $duration
		 */
		$duration = $tz;
		if ( $duration->getHours() > 14 || ( $duration->getHours() == 14 && ( $duration->getMinutes() > 0 || $duration->getSeconds() > 0 ) ) )
		{
			throw XPath2Exception::withErrorCodeAndParam( "FODT0003", Resources::FODT0003, $duration->ToString() );
		}

		/**
		 * @var \DateTimeZone $dtz
		 */
		// Create a Timezone instance from the duration.  'R' is the sign
		$dtz = new \DateTimeZone( $duration->Value->format( "%R%H:I%" ) );

		try
		{
			// Create a new DateTValue instance
			if ( $time->IsLocal )
			{
				$dt = new \DateTime( $time->Value->format( "H:i:s" ), $dtz );
				return new TimeValue( $dt );
			}
			else
			{
				$dt = new \DateTime( $time->Value->format( "H:i:sP" ) );
				$dt->setTimezone( $dtz );
				return new TimeValue( $dt );
			}

		}
		catch ( ArgumentException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParam( "FODT0003", Resources::FODT0003, $duration->ToString() );
		}
	}

	/**
	 * GetAbs
	 * @param object $value
	 * @return object
	 */
	public static function GetAbs( $value )
	{
		$xmlType = null;

		if ( $value instanceof XPath2Item )
		{
			$xmlType = $value->getSchemaType();
			$value = $value->getTypedValue();
		}

		if ( $value instanceof Undefined )
		{
			return $value;
		}

		if ( $value instanceof DecimalValue )
		{
			return $value->getAbs();
		}

		if ( is_double( $value ) )
		{
			return XPath2Item::fromValueAndType( abs( $value ), is_null( $xmlType ) ? XmlSchema::$Double : $xmlType );
		}

		if ( $value instanceof Long )
		{
			return new Long( abs( $value->getValue() ) );
		}

		if ( Integer::IsDerivedSubtype( $value ) )
		{
			$value = Integer::ToInteger( $value );
		}

		if ( $value instanceof Integer )
		{
			/**
			 * @var Integer $integer
			 */
			$integer = $value;
		    return Integer::FromValue( abs( $integer->getValue() ) );
		}
		else
		    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value ), XmlTypeCardinality::One ),
					"xs:float | xs:double | xs:decimal | xs:integer in fn:abs()"
				)
			);
	}

	/**
	 * GetCeiling
	 * @param object $value
	 * @return object
	 */
	public static function GetCeiling( $value )
	{
		$xmlType = null;

		if ( $value instanceof XPath2Item )
		{
			$xmlType = $value->getSchemaType();
			$value = $value->getTypedValue();
		}

		if ( $value instanceof Undefined )
		{
			return $value;
		}

		if ( $value instanceof DecimalValue )
		{
			return $value->getCeil();
		}

		if ( is_double( $value ) )
		{
			return XPath2Item::fromValueAndType( ceil( $value ), is_null( $xmlType ) ? XmlSchema::$Double : $xmlType );
		}

		if ( $value instanceof Long )
		{
			return $value;
		}

		if ( Integer::IsDerivedSubtype( $value ) )
		{
			$value = Integer::ToInteger( $value );
		}

	    if ( $value instanceof Integer )
	    {
	    	return $value;
	    }
		else
		    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value ), XmlTypeCardinality::One ),
					"xs:float | xs:double | xs:decimal | xs:integer in fn:ceiling()"
				)
			);
	}

	/**
	 * GetFloor
	 * @param object $value
	 * @return object
	 */
	public static function GetFloor( $value )
	{
		$xmlType = null;

		if ( $value instanceof XPath2Item )
		{
			$xmlType = $value->getSchemaType();
			$value = $value->getTypedValue();
		}

		if ( $value instanceof Undefined )
		{
			return $value;
		}

		if ( $value instanceof DecimalValue )
		{
			return $value->getFloor();
		}

		if ( is_double( $value ) )
		{
			return XPath2Item::fromValueAndType( floor( $value ), is_null( $xmlType ) ? XmlSchema::$Double : $xmlType );
		}

		if ( $value instanceof Long )
		{
			return $value;
		}

		if ( Integer::IsDerivedSubtype( $value ) )
		{
			$value = Integer::ToInteger( $value );
		}

	    if ( $value instanceof Integer )
	    {
	    	return $value;
	    }
		else
		    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value ), XmlTypeCardinality::One ),
					"xs:float | xs:double | xs:decimal | xs:integer in fn:floor()"
				)
			);
	}

	/**
	 * GetRound
	 * @param object $value
	 * @return object
	 */
	public static function GetRound( $value )
	{
		$xmlType = null;

		if ( $value instanceof XPath2Item )
		{
			$xmlType = $value->getSchemaType();
			$value = $value->getTypedValue();
		}

		if ( $value instanceof Undefined )
		{
			return $value;
		}

		if ( $value instanceof DecimalValue )
		{
			return $value->getRound( 0 );
		}

		if ( is_double( $value ) )
		{
			return XPath2Item::fromValueAndType( round( $value, 0, $value > 0 ? PHP_ROUND_HALF_UP : PHP_ROUND_HALF_DOWN ), is_null( $xmlType ) ? XmlSchema::$Double : $xmlType );
		}

		if ( $value instanceof Long )
		{
			return $value;
		}

		if ( Integer::IsDerivedSubtype( $value ) )
		{
			$value = Integer::ToInteger( $value );
		}

	    if ( $value instanceof Integer )
	    {
	    	return $value;
	    }
		else
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value ), XmlTypeCardinality::One ),
					"xs:float | xs:double | xs:decimal | xs:integer in fn:round()"
				)
			);
	}

	/**
	 * GetRoundHalfToEven - Should be changed to call GetRoundHalfToEvenWithPrecision( 0 )
	 * @param object $value
	 * @return object
	 */
	public static function GetRoundHalfToEven( $value )
	{
		$xmlType = null;

		if ( $value instanceof XPath2Item )
		{
			$xmlType = $value->getSchemaType();
			$value = $value->getTypedValue();
		}

		if ( $value instanceof Undefined )
		{
			return $value;
		}

		if ( $value instanceof DecimalValue )
		{
			return $value->getRound( 0, PHP_ROUND_HALF_EVEN );
		}

		if ( is_double( $value ) )
		{
			return XPath2Item::fromValueAndType( round( $value, 0, PHP_ROUND_HALF_EVEN ), is_null( $xmlType ) ? XmlSchema::$Double : $xmlType );
		}

		if ( $value instanceof Long )
		{
			return $value;
		}

		if ( Integer::IsDerivedSubtype( $value ) )
		{
			$value = Integer::ToInteger( $value );
		}

    	if ( $value instanceof Integer )
    	{
    		return $value;
    	}
		else
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value ), XmlTypeCardinality::One ),
					"xs:float | xs:double | xs:decimal | xs:integer in fn:round-half-to-even()"
				)
			);
	}

	/**
	 * GetRoundHalfToEvenWithPrecision
	 * @param object $value
	 * @param object $prec
	 * @return object
	 */
	public static function GetRoundHalfToEvenWithPrecision( $value, $prec )
	{
		$xmlType = null;

		if ( $value instanceof XPath2Item )
		{
			$xmlType = $value->getSchemaType();
			$value = $value->getTypedValue();
		}

		if ( $value instanceof Undefined || $prec instanceof Undefined )
		{
			return Undefined::getValue();
		}

		$p = ( $prec instanceof Integer || $prec instanceof XPath2Item ? $prec->getValue() : $prec ) + 0;

		if ( $value instanceof DecimalValue )
		{
			return $value->getRound( $p, PHP_ROUND_HALF_EVEN );
		}

		if ( is_double( $value ) )
		{
			return XPath2Item::fromValueAndType( round( $value, $p, PHP_ROUND_HALF_EVEN ), is_null( $xmlType ) ? XmlSchema::$Double : $xmlType );
		}

		if ( $value instanceof Long )
		{
			return $value;
		}

		if ( Integer::IsDerivedSubtype( $value ) )
		{
			$value = Integer::ToInteger( $value );
		}

		if ( $value instanceof XPath2Item )
		{
			$value = $value->getTypedValue();
		}

		if ( $value instanceof Integer )
		{
			/**
			 * @var Integer $integer
			 */
			$integer = $value;
			return Integer::FromValue( round( $integer->getValue(), $p, PHP_ROUND_HALF_EVEN ) );
		}
		else
		   throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value ), XmlTypeCardinality::One ),
					"xs:float | xs:double | xs:decimal | xs:integer in fn:round-half-to-even()"
				)
			);
	}

	/**
	 * Compare
	 * @param XPath2Context $context
	 * @param object $a
	 * @param object $b
	 * @param string $collation
	 * @return object
	 */
	public static function CompareWithCollation( $context, $a, $b, $collation )
	{
		if ( $a instanceof Undefined || $b instanceof Undefined )
		{
			return Undefined::getValue();
		}

		if ( $collation == XmlReservedNs::collationCodepoint )
		{
			$s1 = \normalizer_normalize( $a, \Normalizer::FORM_C );
			$s2 = \normalizer_normalize( $b, \Normalizer::FORM_C );

			return strcmp( $s1, $s2 );
		}
		else
		{
			$s1 = $a . "";
			$s2 = $b . "";

			$result = setlocale( LC_COLLATE, $collation );
			if ( ! $result )
			{
				throw XPath2Exception::withErrorCodeAndParam( "FOCH0002", Resources::FOCH0002, $collation );
			}
			return strcoll( $s1, $s2 );
		}
	}

	/**
	 * Compare
	 * @param XPath2Context $context
	 * @param object $a
	 * @param object $b
	 * @return object
	 */
	public static function Compare( $context, $a, $b )
	{
		return ExtFuncs::CompareWithCollation( $context, $a, $b, "C" );
	}

	/**
	 * CodepointEqual
	 * @param object $a
	 * @param object $b
	 * @return object
	 */
	public static function CodepointEqual( $a, $b )
	{
		if ( $a instanceof Undefined || $b instanceof Undefined )
		{
			return Undefined::getValue();
		}

	    $s1 = $a . "";
	    $s2 = $b . "";
		return strcmp( $s1, $s2 ) == 0;
	}

	/**
	 * EmptySequence
	 * @param XPath2NodeIterator $iter
	 * @return bool
	 */
	public static function EmptySequence( $iter )
	{
		/**
		 * @var XPath2NodeIterator $probe
		 */
		$probe = $iter->CloneInstance();
		return $probe->MoveNext()
			? CoreFuncs::$False
			: CoreFuncs::$True;
	}

	/**
	 * ExistsSequence
	 * @param XPath2NodeIterator $iter
	 * @return bool
	 */
	public static function ExistsSequence( $iter )
	{
		// BMS 2018-02-12 This return 'true' or 'false' when CoreFuncs::$True or CoreFuncs::$False
		// return ExtFuncs::EmptySequence( $iter ) instanceof CoreFuncs::$False;
		return CoreFuncs::Not( ExtFuncs::EmptySequence( $iter ) );
	}

	/**
	 * ReverseIterator
	 * @param array $list
	 * @return IEnumerable
	 */
	private static function ReverseIterator( $list )
	{
		for ( $i = count( $list ) -1; $i >= 0; $i-- )
		{
		    yield $list[ $i ];
		}
	}

	/**
	 * ReverseSequence
	 * @param XPath2NodeIterator $iter
	 * @return XPath2NodeIterator
	 */
	public static function ReverseSequence( $iter )
	{
		$list = array();
		/**
		 * @var XPathItem $item
		 */
		foreach ( $iter as $item )
		    $list[] = CoreFuncs::CloneInstance( $item );
		return new NodeIterator( function() use( $list ) { return ExtFuncs::ReverseIterator( $list ); } );
	}

	/**
	 * IndexOfIterator
	 * @param XPath2NodeIterator $iter
	 * @param object $value
	 * @param string $collation
	 * @return IEnumerable
	 */
	private static function IndexOfIterator( $iter, $value, $collation = null )
	{
		$pos = 1;
		if ( $value instanceof UntypedAtomic || $value instanceof AnyUriValue )
		{
			$value = $value . "";
		}

		/**
		 * @var XPathItem $item
		 */
		foreach( $iter as $item )
		{
		    $res;
		    $curr = $item instanceof XPathNavigator
		    	? $item->GetTypedValue()
		    	: $item;

		    if ( $curr instanceof UntypedAtomic || $curr instanceof AnyUriValue )
		    {
		    	$curr = (string)$curr . "";
		    }

		    // BMS 2017-09-13 This test is needed by one of the XFI tests
		    // but don't want it to raise an exception if there is no match
		    if ( CoreFuncs::OperatorEq( $curr, $value, false ) instanceof CoreFuncs::$True )
		    // This test is needed by fn-indexof-mix-args-021 in SeqIndexOfFunc.xml of
		    // /MinimalConformance/Functions/SeqFunc/GeneralSeqFunc
		    // if ( ValueProxy::EqValues( $curr, $value, $res ) && $res )
		    {
		    	yield XPath2Item::fromValue( $pos );
		    }

		    $pos++;
		}
	}

	/**
	 * IndexOfSequence
	 * @param XPath2NodeIterator $iter
	 * @param object $value
	 * @return XPath2NodeIterator
	 */
	public static function IndexOfSequence( $iter, $value )
	{
		 return new NodeIterator( function() use( $iter, $value ) { return ExtFuncs::IndexOfIterator( $iter, $value ); } );
	}

	/**
	 * IndexOfSequence
	 * @param XPath2Context $context
	 * @param XPath2NodeIterator $iter
	 * @param object $value
	 * @param string $collation
	 * @return XPath2NodeIterator
	 */
	public static function IndexOfSequenceWithCollation( $context, $iter, $value, $collation )
	{
		if ( ! is_null( $collation ) && $collation != XmlReservedNs::collationCodepoint )
		{
			if ( ! setlocale( LC_COLLATE, $collation ) )
			{
				throw XPath2Exception::withErrorCodeAndParam( "FOCH0002", Resources::FOCH0002, $collation );
			}
		}

		return new NodeIterator( function() use( $iter, $value, $collation ) { return ExtFuncs::IndexOfIterator( $iter, $value, $collation ); } );
	}

	/**
	 * RemoveIterator
	 * @param XPath2NodeIterator $iter
	 * @param int $index
	 * @return IEnumerable
	 */
	private static function RemoveIterator( $iter, $index )
	{
		$pos = 1;

		/**
		 * @var XPathItem $item
		 */
		foreach ( $iter as $item )
		{
		    if ( $index != $pos )
			   yield $item;
		    $pos++;
		}
	}

	/**
	 * Remove
	 * @param XPath2NodeIterator $iter
	 * @param int $index
	 * @return XPath2NodeIterator
	 */
	public static function Remove( $iter, $index )
	{
		return new NodeIterator( function() use( $iter, $index ) { return ExtFuncs::RemoveIterator( $iter->CloneInstance(), $index ); } );
	}

	/**
	 * InsertIterator
	 * @param XPath2NodeIterator $iter
	 * @param int $index
	 * @param XPath2NodeIterator $iter2
	 * @return IEnumerable
	 */
	private static function InsertIterator( $iter, $index, $iter2 )
	{
		$pos = 1;
		if ( $index < $pos )
		{
		    /**
		     * @var XPathItem $item2
		     */
		    foreach ( $iter2 as $item2 )
			   yield $item2;
		}
		/**
		 * @var XPathItem $item
		 */
		foreach ( $iter as $item )
		{
		    if ( $index == $pos )
			   /**
			    * @var XPathItem $item2
			    */
			   foreach ( $iter2 as $item2 )
				  yield $item2;
		    yield $item;
		    $pos++;
		}
		if ( $pos <= $index )
		{
		    /**
		     * @var XPathItem $item2
		     */
		    foreach ( $iter2 as $item2 )
			   yield $item2;
		}
	}

	/**
	 * InsertBefore
	 * @param XPath2NodeIterator $iter
	 * @param int $index
	 * @param XPath2NodeIterator $iter2
	 * @return XPath2NodeIterator
	 */
	public static function InsertBefore( $iter, $index, $iter2 )
	{
		return new NodeIterator( function() use( $iter, $index, $iter2 ) { return ExtFuncs::InsertIterator( $iter->CloneInstance(), $index, $iter2->CloneInstance() ); } );
	}

	/**
	 * SubsequenceIterator
	 * @param XPath2NodeIterator $iter
	 * @param double $startingLoc
	 * @param double $length
	 * @return IEnumerable
	 */
	private static function SubsequenceIterator( $iter, $startingLoc, $length )
	{
		if ( $startingLoc instanceof XPath2Item )
		{
			$startingLoc = $startingLoc->getTypedValue();
		}
		if ( $length instanceof XPath2Item )
		{
			$length = $length->getTypedValue();
		}

		$startingLoc = floor( $startingLoc );
		$length = floor( $length );

		if ( $startingLoc < 1 )
		{
		    $length = $length + $startingLoc - 1;
		    $startingLoc = 1;
		}

		$pos = 1;

		/**
		 * @var XPathItem $item
		 */
		foreach ( $iter as $item )
		{
		    if ( $startingLoc <= $pos )
		    {
			   if ( $length <= 0 )
				  break;
			   yield $item;
			   $length--;
		    }
		    $pos++;
		}
	}

	/**
	 * Subsequence
	 * @param XPath2NodeIterator $iter
	 * @param double $startingLoc
	 * @return XPath2NodeIterator
	 */
	public static function Subsequence( $iter, $startingLoc )
	{
		return ExtFuncs::SubsequenceWithLength( $iter, $startingLoc, INF );
	}

	/**
	 * Subsequence
	 * @param XPath2NodeIterator $iter
	 * @param double $startingLoc
	 * @param double $length
	 * @return XPath2NodeIterator
	 */
	public static function SubsequenceWithLength( $iter, $startingLoc, $length )
	{
		if ( $startingLoc instanceof XPath2Item )
		{
			$startingLoc = $startingLoc->getTypedValue();
		}

		$startingLoc = round( $startingLoc );

		if ( $length instanceof XPath2Item )
		{
			$length = $length->getTypedValue();
		}

		$length = round( $length );

		if ( $startingLoc == INF || $startingLoc == -INF || is_nan( $startingLoc ) || is_nan( $length ) )
		{
			return EmptyIterator::$Shared;
		}
		return new NodeIterator( function() use( $iter, $startingLoc, $length ) { return ExtFuncs::SubsequenceIterator( $iter->CloneInstance(), $startingLoc, $length ); } );
	}

	/**
	 * Unordered
	 * @param XPath2NodeIterator $iter
	 * @return XPath2NodeIterator
	 */
	public static function Unordered( $iter )
	{
		return $iter->CloneInstance();
	}

	/**
	 * ZeroOrOne
	 * @param XPath2NodeIterator $iter
	 * @return object
	 */
	public static function ZeroOrOne( $iter )
	{
		/**
		 * @var XPath2NodeIterator $probe
		 */
		$probe = $iter->CloneInstance();
		if ( $probe->MoveNext() )
		{
			$res = $probe->getCurrent()->getIsNode()
				? $probe->getCurrent()->CloneInstance()
		    	: $probe->getCurrent()->GetTypedValue();

		    if ( $probe->MoveNext() )
			   throw XPath2Exception::withErrorCode( "FORG0003", Resources::FORG0003 );

		    return $res;
		}
		return Undefined::getValue();
	}

	/**
	 * OneOrMore
	 * @param XPath2NodeIterator $iter
	 * @return XPath2NodeIterator
	 */
	public static function OneOrMore( $iter )
	{
		$iter = $iter->CreateBufferedIterator();
		/**
		 * @var XPath2NodeIterator $probe
		 */
		$probe = $iter->CloneInstance();
		if ( ! $probe->MoveNext() )
		    throw XPath2Exception::withErrorCode( "FORG0004", Resources::FORG0004 );

		return $iter;
	}

	/**
	 * ExactlyOne
	 * @param XPath2NodeIterator $iter
	 * @return object
	 */
	public static function ExactlyOne( $iter )
	{
		/**
		 * @var XPath2NodeIterator $probe
		 */
		$probe = $iter->CloneInstance();

		if ( ! $probe->MoveNext() )
		    throw XPath2Exception::withErrorCode( "FORG0005", Resources::FORG0005 );

		$res = $probe->getCurrent()->getIsNode()
		    ? $probe->getCurrent()->CloneInstance()
		    : $probe->getCurrent()->GetTypedValue();

		if ( $probe->MoveNext() )
		    throw XPath2Exception::withErrorCode( "FORG0005", Resources::FORG0005 );

		return $res;
	}

	/**
	 * DistinctIterator
	 * @param XPath2NodeIterator $iter
	 * @param string $collation
	 * @return IEnumerable
	 */
	private static function DistinctIterator( $iter, $collation = null )
	{
		$dict = array();
		$comparer = new DistinctComparer( $collation );

		$iter = $iter->CloneInstance();
		$iter->Reset();
		while ( $iter->MoveNext() )
		{
			/**
			 * @var XPathItem $item
			 */
		    $item = $iter->getCurrent();
		    if ( strlen( (string)$item->getValue() ) )
		    {
				$value = $item instanceof DOMXPathNavigator || $item instanceof XPath2Item
					? $item->GetTypedValue()
					: $item;

				$found = false;
				foreach ( $dict as $item )
				{
					if ( $comparer->Compare( $item, $value ) == 0 )
					{
						$found = true;
						break;
					}
				}
				if ( ! $found )
				{
					yield $item instanceof XPath2Item
						? $item
						: XPath2Item::fromValue( $value );
					$dict[] = $value;
				}
			}
		}
	}

	/**
	 * DistinctValues
	 * @param XPath2NodeIterator $iter
	 * @return XPath2NodeIterator
	 */
	public static function DistinctValues( $iter )
	{
		return new NodeIterator( function() use( $iter ) { return ExtFuncs::DistinctIterator( $iter ); } );
	}

	/**
	 * DistinctValues
	 * @param XPath2Context $context
	 * @param XPath2NodeIterator $iter
	 * @param string $collation
	 * @return XPath2NodeIterator
	 */
	public static function DistinctValuesWithCollation( $context, $iter, $collation )
	{
		if ( ! is_null( $collation ) && $collation != XmlReservedNs::collationCodepoint )
		{
			if ( ! setlocale( LC_COLLATE, $collation ) )
			{
				throw XPath2Exception::withErrorCodeAndParam( "FOCH0002", Resources::FOCH0002, $collation );
			}
		}

		return new NodeIterator( function() use( $iter, $collation ) { return ExtFuncs::DistinctIterator( $iter, $collation ); } );
	}

	/**
	 * DeepEqual
	 * @param XPath2Context $context
	 * @param XPath2NodeIterator $iter1
	 * @param XPath2NodeIterator $iter2
	 * @return bool
	 */
	public static function DeepEqual( $context, $iter1, $iter2 )
	{
		/**
		 * @var TreeComparer $comparer
		 */
		$comparer = new TreeComparer( $context );
		return $comparer->DeepEqualByIterator( $iter1, $iter2 );
	}

	/**
	 * DeepEqual
	 * @param XPath2Context $context
	 * @param XPath2NodeIterator $iter1
	 * @param XPath2NodeIterator $iter2
	 * @param string $collation
	 * @return bool
	 */
	public static function DeepEqualWithCollation( $context, $iter1, $iter2, $collation )
	{
		// TreeComparer comparer = new TreeComparer( $context->RunningContext->GetCulture( $collation ) );
		/**
		 * @var TreeComparer $comparer
		 */
		$comparer = new TreeComparer( $context, $collation );
		return $comparer->DeepEqualByIterator( $iter1, $iter2 );
	}

	/**
	 * CountValues
	 * @param XPath2NodeIterator $iter
	 * @return int
	 */
	public static function CountValues( $iter )
	{
		$iter->Reset();
		return $iter->getCount();
	}

	/**
	 * MaxValue
	 * @param XPath2Context $context
	 * @param XPath2NodeIterator $iter
	 * @param string $collation
	 * @return object
	 */
	public static function MaxValueWithCollation( $context, $iter, $collation )
	{
		if ( ! is_null( $collation ) && $collation != XmlReservedNs::collationCodepoint )
		{
			if ( ! setlocale( LC_COLLATE, $collation ) )
			{
				throw XPath2Exception::withErrorCodeAndParam( "FOCH0002", Resources::FOCH0002, $collation );
			}
		}

		//  CultureInfo culture = $context->RunningContext->GetCulture( $collation );
		/**
		 * @var ValueProxy $acc
		 */
		$acc = null;
		foreach ( $iter as $item )
		{
			// This probably needs changing.  If the $item is an XPathNavigator then the
			// value will be assumed to be numeric but it could be text
			$curr = CoreFuncs::CastToNumber1( $context, $item instanceof XPathNavigator ? $item->GetTypedValue() : $item );
			if ( $curr->getTypedValue() instanceof AnyUriValue )
			{
				$curr = $curr->getTypedValue() . "";
			}
			else if ( $curr->getSchemaType()->TypeCode == XmlTypeCode::Duration || is_object( $curr) && get_class( $curr ) == DurationValue::$CLASSNAME )
			{
				throw XPath2Exception::withErrorCodeAndParams( "FORG0006", Resources::FORG0006,
					array(
						"fn:max()",
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $item ), XmlTypeCardinality::One )
					)
				);
			}

			try
			{
				$acc = is_null( $acc )
					? ValueProxy::Create( $curr )
					: ValueProxy::Max( $acc, ValueProxy::Create( $curr ) );
			}
			catch ( \Exception $ex )
			{
				if ( $ex instanceof ArgumentException ||
					 $ex instanceof XPath2Exception && $ex->ErrorCode == "XPTY0004" ||
					 $ex instanceof InvalidCastException
				)
				{
					$ex = XPath2Exception::withErrorCodeAndParams( "FORG0006", Resources::FORG0006,
						array(
							"fn:max()",
							SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $item ), XmlTypeCardinality::One )
						)
					);
				}

		    	throw $ex;
			}
		}

		return is_null( $acc )
			? Undefined::getValue()
			: $acc; // ->getValue();
	}

	/**
	 * MaxValue
	 * @param XPath2Context $context
	 * @param XPath2NodeIterator $iter
	 * @return object
	 */
	public static function MaxValue( $context, $iter )
	{
		return ExtFuncs::MaxValueWithCollation( $context, $iter, null );
	}

	/**
	 * MinValue
	 * @param XPath2Context $context
	 * @param XPath2NodeIterator $iter
	 * @param string $collation
	 * @return object
	 */
	public static function MinValueWithCollation( $context, $iter, $collation )
	{
		if ( ! is_null( $collation ) && $collation != XmlReservedNs::collationCodepoint )
		{
			if ( ! setlocale( LC_COLLATE, $collation ) )
			{
				throw XPath2Exception::withErrorCodeAndParam( "FOCH0002", Resources::FOCH0002, $collation );
			}
		}

		/**
		 * @var ValueProxy $acc
		 */
		$acc = null;
		foreach ( $iter as $item )
		{
			// This probably needs changing.  If the $item is an XPathNavigator then the
			// value will be assumed to be numeric but it could be text
			$curr = CoreFuncs::CastToNumber1( $context, $item instanceof XPathNavigator ? $item->GetTypedValue() : $item );
			if ( $curr->GetTypedValue() instanceof AnyUriValue )
			{
				$curr = $curr->GetTypedValue() . "";
			}
			else if ( $curr->getSchemaType()->TypeCode == XmlTypeCode::Duration || is_object( $curr) && get_class( $curr ) == DurationValue::$CLASSNAME )
			{
				throw XPath2Exception::withErrorCodeAndParams( "FORG0006", Resources::FORG0006,
					array(
						"fn:min()",
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $curr ), XmlTypeCardinality::One )
					)
				);
			}

			try
			{
				$acc = is_null( $acc )
					? ValueProxy::Create( $curr )
					: ValueProxy::Min( $acc, ValueProxy::Create( $curr ) );
			}
			catch ( \Exception $ex )
			{
				if ( $ex instanceof ArgumentException ||
					 $ex instanceof InvalidCastException ||
					 ( $ex instanceof XPath2Exception && $ex->ErrorCode == "XPTY0004" )
				)
				{
					$ex = XPath2Exception::withErrorCodeAndParams( "FORG0006", Resources::FORG0006,
						array(
							"fn:min",
							SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $curr ), XmlTypeCardinality::One )
						)
					);
				}

				throw $ex;
			}
		}

		return is_null( $acc )
			? Undefined::getValue()
			: $acc; // ->getValue();
	}

	/**
	 * MinValue
	 * @param XPath2Context $context
	 * @param XPath2NodeIterator $iter
	 * @return object
	 */
	public static function MinValue( $context, $iter )
	{
		return ExtFuncs::MinValueWithCollation( $context, $iter, null );
	}

	/**
	 * SumValue
	 * @param XPath2Context $context
	 * @param XPath2NodeIterator $iter
	 * @return object
	 */
	public static function SumValue( $context, $iter )
	{
		return ExtFuncs::SumValueWithZero( $context, $iter, 0 );
	}

	/**
	 * SumValue
	 * @param XPath2Context $context
	 * @param XPath2NodeIterator $iter
	 * @param DayTimeDurationValue $zero
	 * @return object
	 */
	public static function SumValueWithZero( $context, $iter, $zero )
	{
		try
		{
			$zero = $zero instanceof DayTimeDurationValue || $zero instanceof YearMonthDurationValue || $zero instanceof DurationValue
				? $zero
				: CoreFuncs::CastToNumber1( $context, $zero );
		}
		catch ( \Exception $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0006", Resources::FORG0006,
				array(
					"fn:sum()",
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $item->GetTypedValue() ), XmlTypeCardinality::One )
				)
			);
		}

		/**
		 * @var ValueProxy $acc
		 */
		$acc = null;
		/**
		 * @var XPathItem $item
		 */
		foreach ( $iter as $item )
		{
			$value = $item->getTypedValue();

			if ( is_object( $value ) && get_class( $value ) == DurationValue::$CLASSNAME )
			{
				throw XPath2Exception::withErrorCodeAndParams( "FORG0006", Resources::FORG0006,
					array(
						"fn:sum()",
						SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value ), XmlTypeCardinality::One )
					)
				);
			}

			/**
			 * @var ValueProxy $acc
			 */
			$arg;
		    try
		    {
		    	// $value = $value instanceof DayTimeDurationValue || $value instanceof YearMonthDurationValue || $value instanceof DurationValue
		    	// 	? $value->getSeconds()
		    	// 	: CoreFuncs::CastToNumber1( $context, $value );
		    	// $arg = ValueProxy::Create( CoreFuncs::CastToNumber1( $context, $value ) );
		    	$x = $item instanceof XPathNavigator ? XPath2Item::fromValue( $value ) : $item;
		    	$arg = ValueProxy::Create( CoreFuncs::CastToNumber1( $context, $x ) );
				if ( ! ( $arg->getIsNumeric() || $arg->getValue() instanceof YearMonthDurationValue || $arg->getValue() instanceof DayTimeDurationValue ) )
				{
					throw XPath2Exception::withErrorCodeAndParams( "FORG0006", Resources::FORG0006,
						array(
							"fn:sum()",
							SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $item->GetTypedValue() ), XmlTypeCardinality::One )
						)
					);
				}

				if ( ! $arg->getValue() instanceof Integer && Integer::IsDerivedSubtype( $arg->getValue() ) )
				{
					$arg = Integer::FromValue( Convert::ToDouble( $arg ) );
				}

				$acc = is_null( $acc )
					? $arg
					: ValueProxy::OperatorPlus( $acc, $arg );
		    }
		    catch ( \Exception $ex )
		    {
		    	if ( ! isset( $arg ) )
		    	{
		    		$arg = $item;
		    	}
		    	if ( $ex instanceof ArgumentException ||
					 $ex instanceof InvalidCastException ||
					 ( $ex instanceof XPath2Exception && $ex->ErrorCode == "XPTY0004" )
				)
		    	{
		    		$ex = XPath2Exception::withErrorCodeAndParams( "FORG0006", Resources::FORG0006,
						array(
							"fn:sum()",
							SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $arg ), XmlTypeCardinality::One )
						)
					);
				}

		    	throw $ex;
		    }

		}

		return ! is_null( $acc ) ? $acc->getValue() : $zero;
	}

	/**
	 * AvgValue
	 * @param XPath2Context $context
	 * @param XPath2NodeIterator $iter
	 * @return object
	 */
	public static function AvgValue( $context, $iter )
	{
		/**
		 * @var ValueProxy $acc
		 */
		$acc = null;
		$count = 0;
		/**
		 * @var XPathItem $item
		 */
		foreach ( $iter as $item )
		{
			if ( $item instanceof XPathNavigator )
			{
				try
				{
					$result = $item->getValueAsDouble();
					$item = XPath2Item::fromValueAndType( $result, XmlSchema::$Double );
				}
				catch( \Exception $ex )
				{
					throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( "DOM node", "number" ) );
				}
			}

			/**
			 * @var ValueProxy $acc
			 */
			$arg;
		    try
		    {
				$arg = ValueProxy::Create( CoreFuncs::CastToNumber1( $context, $item ) );
				if ( ! ( $arg->getIsNumeric() || $arg instanceof YearMonthDurationProxy || $arg instanceof DayTimeDurationProxy ) )
				{
					throw XPath2Exception::withErrorCodeAndParams( "FORG0006", Resources::FORG0006,
						array(
							"fn:avg()",
							SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $item->GetTypedValue() ), XmlTypeCardinality::One )
						)
					);
				}

				if ( ! $arg->getValue() instanceof Integer && Integer::IsDerivedSubtype( $arg->getValue() ) )
				{
					$arg = Integer::FromValue( Convert::ToDouble( $arg ) );
				}

				$acc = is_null( $acc )
					? ValueProxy::Create( $arg )
					: ValueProxy::OperatorPlus( $acc, $arg );

				$count += 1;
			}
			catch ( \Exception $ex )
			{
				if ( $ex instanceof ArgumentException ||
					 $ex instanceof InvalidCastException ||
					 ( $ex instanceof XPath2Exception && $ex->ErrorCode == "XPTY0004" )
				)
				{
					if ( ! isset( $arg ) )
					{
						$arg = $item;
					}
					$ex = XPath2Exception::withErrorCodeAndParams( "FORG0006", Resources::FORG0006,
						array(
							"fn:avg()",
							SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $arg ), XmlTypeCardinality::One )
						)
					);
				}

				throw $ex;
			}
		}

		if ( is_null( $acc ) )
		{
			return Undefined::getValue();
		}

		if ( $acc->IsNaN() )
		{
			return $acc;
		}

		return  ValueProxy::OperatorDivide( ValueProxy::Create( $acc ), ValueProxy::Create( $count ) ); // ->getValue();
	}

	/**
	 * CreateDateTime
	 * @param object $dateArg
	 * @param object $timeArg
	 * @return object
	 */
	public static function CreateDateTime( $dateArg, $timeArg )
	{
		if ( $dateArg instanceof Undefined || $timeArg instanceof Undefined )
		{
			return Undefined::getValue();
		}

		if ( $dateArg instanceof DOMXPathNavigator )
		{
			$value = $dateArg->getValue();
			// Get the type of the element
			$dateArg = strpos( $value, "T") === false
				? DateValue::Parse( $value )
				: DateTimeValue::Parse( $value );
		}
		/**
		 * @var DateValue $date
		 */
		$date = $dateArg;

		/**
		 * @var TimeValue $time
		 */
		$time = $timeArg instanceof DOMXPathNavigator
			? TimeValue::Parse( $timeArg->getValue() )
			: $timeArg;

		$microseconds = empty( $time->Value->microseconds ) ? "" : "." . $time->Value->microseconds;
		$offsetChar = "";
		if ( ! $date->IsLocal || ! $time->IsLocal )
		{
			$value = $date->IsLocal
				? $time
				: $date;

			/**
			 * @var \DateTimeZone $dtz
			 */
			$dtz = $value->Value->getTimezone();

			$offsetChar = $dtz->getName() == "Z"
				? "\Z"
				: "P";
			$offsetChar = $dtz->getName();

		    if ( ! $date->IsLocal && ! $time->IsLocal )
		    {
			   if ( $date->Value->getOffset() != $time->Value->getOffset() )
				  throw XPath2Exception::withErrorCode( "FORG0008", Resources::FORG0008 );
			   // $dtz = $date->Value->getTimezone();
		    }
		}

		// Allow for the time to have been entered as 24:00:00 so as a DateTime object it will be 1T00:00:00
		// $timeDays = $time->Value->format("z");
		// if ( $timeDays )
		// {
		//	$date = DateTimeValue::AddDayTimeDuration( $date, DayTimeDurationValue::Parse("P{$timeDays}D") );
		// }

		// $dtv = DateTimeValue::Parse( $date->Value->format("Y-m-d") . "T" . $time->Value->format("H:i:s$microseconds$offsetChar") );
		$dtv = DateTimeValue::Parse( $date->Value->format("Y-m-d") . "T" . $time->Value->format("H:i:s$microseconds") . $offsetChar );
		// BMS 2018-03-23 Changed to this.  Handles test case 48230 V-05.
		// 				  The XPath 2.0 specification is clear that these two are equivalent and event give an example:
		//						fn:dateTime(xs:date('2018-03-23'),xs:time('00:00:00'))
		//						fn:dateTime(xs:date('2018-03-23'),xs:time('24:00:00'))
		//				  That is, '00:00:00' and '24:00:00' are synonyms for midnight at the beginning of the day.
		//				  But the XBRL test uses '24:00:00' as midnight of the end of the day.
		global $use_xbrl_functions;
		if ( $use_xbrl_functions && $time->Value->getTimestamp() == 82800 )
		{
			$dtv->Value->add( new \DateInterval("P1D") );
		}
		$dtv->S = $date->S;

		return $dtv;
	}

	/**
	 * GetCurrentDateTime
	 * @param XPath2Context $context
	 * @return DateTimeValue
	 */
	public static function GetCurrentDateTime( $context )
	{
		$res = DateTimeValue::fromDate( false, new \DateTime( "@" . $context->RunningContext->now ) );
		$res->Value->microseconds = 0;
		$res->IsLocal = false;
		return $res;
	}

	/**
	 * GetCurrentDate
	 * @param XPath2Context $context
	 * @return DateValue
	 */
	public static function GetCurrentDate( $context )
	{
		$res = DateValue::fromDate( false, new \DateTime( "@" . $context->RunningContext->now ) );
		$res->IsLocal = false;
		$res->Value->microseconds = 0;
		return $res;
	}

	/**
	 * GetCurrentTime
	 * @param XPath2Context $context
	 * @return TimeValue
	 */
	public static function GetCurrentTime( $context )
	{
		$now =  new \DateTime();
		$now->microseconds = 0;
		$tv = new TimeValue( $now );
		$tv->IsLocal = false;
		return $tv;
	}

	/**
	 * ScanLocalNamespaces
	 * @param XmlNamespaceManager $nsmgr
	 * @param XPathNavigator $node
	 * @param bool $recursive
	 * @return void
	 */
	public static function ScanLocalNamespaces( $nsmgr, $node, $recursive )
	{
		if ( $node->getNodeType() != XPathNodeType::Element )
		{
			if ( $recursive )
			{
				/**
				 * @var XPathNavigator $parent
				 */
				$parent = $node->CloneInstance();
				if ( $parent->MoveToParent() )
				{
					ExtFuncs::ScanLocalNamespaces( $nsmgr, $parent, $recursive );
					return;
				}
			}
		}

		$defaultNS = false;
		$prefix = $node->getPrefix();
		$ns = $node->getNamespaceURI();
		$nsmgr->PushScope();
		if ( $node->MoveToFirstNamespace( XPathNamespaceScope::All ) )
		{
			do
			{
				$nsmgr->addNamespace( $node->getName(), $node->getValue() );
				if ( $node->getName() == $prefix )
				{
					$defaultNS = true;
				}
			}
			while ( $node->MoveToNextNamespace( XPathNamespaceScope::All ) );
		}
		if ( ! $defaultNS && $ns != "" )
		{
			$nsmgr->AddNamespace( $prefix, $ns );
		}

		return;

	}

	/**
	 * PrefixEnumerator
	 * @param XPathNavigator $nav
	 * @return IEnumerable
	 */
	private static function PrefixEnumerator( $nav )
	{
		/**
		 * @var XmlNamespaceManager $nsmgr
		 */
		$$nsmgr = new XmlNamespaceManager();
		ExtFuncs::ScanLocalNamespaces( $nsmgr, $nav->CloneInstance(), false );

		foreach ( $nsmgr->getNamespacesInScope( XmlNamespaceScope::All ) as $prefix => $ns )
		    yield XPath2Item::fromValue( $prefix );
	}

	/**
	 * GetInScopePrefixes
	 * @param XPathNavigator $nav
	 * @return XPath2NodeIterator
	 */
	public static function GetInScopePrefixes( $nav )
	{
		return new NodeIterator( function() use( $nav ) { return ExtFuncs::PrefixEnumerator( $nav ); } );
	}

	/**
	 * GetNamespaceUriForPrefix
	 * @param XPath2Context $context
	 * @param object $prefix
	 * @param XPathNavigator $nav
	 * @return object
	 */
	public static function GetNamespaceUriForPrefix( $context, $prefix, $nav )
	{
		$ns  = "";
		if ( $prefix instanceof  Undefined || $prefix . "" == "" )
		{
			$ns = $nav->getNamespaceURI();
		}
		else
		{
			/**
			 * @var XmlNamespaceManager $nsmgr
			 */
		    $nsmgr = new XmlNamespaceManager();
		    ExtFuncs::ScanLocalNamespaces( $nsmgr, $nav->CloneInstance(), false );
		    $ns = $nsmgr->lookupNamespace( $prefix . "" );
		}
		return is_null( $ns )
			? Undefined::getValue()
			: new AnyUriValue( $ns );
	}

	/**
	 * ResolveQName
	 * @param XPath2Context $context
	 * @param object $qname
	 * @param XPathNavigator $nav
	 * @return object
	 */
	public static function ResolveQName( $context, $qname, $nav )
	{
		if ( $qname instanceof Undefined )
		{
			return Undefined::getValue();
		}

	    /**
	     * @var XmlNamespaceManager $nsmgr
	     */
		$nsmgr = new XmlNamespaceManager( $context->NameTable );
		ExtFuncs::ScanLocalNamespaces( $nsmgr, $nav->CloneInstance(), true );
		// $qNameValue = QNameValue::fromQName( \lyquidity\xml\qname( $qname . "", $nsmgr->getNamespaces() ) );
		$qNameValue = QNameValue::fromNCName( $qname, $nsmgr );
		return XPath2Item::fromValue( $qNameValue );
	}

	/**
	 * CreateQName
	 * @param XPath2Context $context
	 * @param object $ns
	 * @param string $qname
	 * @return QNameValue
	 */
	public static function CreateQName( $context, $ns, $qname )
	{
		if ( $ns instanceof Undefined )
		{
			$ns = "";
		}

		$ns = trim( $ns );
		$qname = trim( $qname );

		if ( empty( $ns ) && strpos( $qname, ":" ) )
		{
			throw XPath2Exception::withErrorCodeAndParam( "FOCA0002", Resources::FOCA0002, $qname );
		}

		// $qn = \lyquidity\xml\qname( $ns, $qname );
		if ( strpos( $qname, ":") !== false )
		{
			$parts = explode( ":", $qname );
			$qn = new \lyquidity\xml\qname( $parts[0], $ns, $parts[1] );
		}
		else
		{
			$prefix = $context->NamespaceManager->lookupPrefix( $ns );
			$qn = new \lyquidity\xml\qname( $prefix ? $prefix : "", $ns, $qname );
		}
		return QNameValue::fromQName( $qn );
	}

	/**
	 * PrefixFromQName
	 * @param object $qname
	 * @return object
	 */
	public static function PrefixFromQName( $qname )
	{
		if ( $qname instanceof Undefined )
		{
			return $qname;
		}

		if ( ! $qname instanceof QNameValue )
		{
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $qname ), XmlTypeCardinality::One ),
					"QName"
				)
			);
		}

		/**
		 * @var QNameValue $qnameValue
		 */
		$qnameValue = $qname;

		return $qnameValue->Prefix == ""
			? Undefined::getValue()
			: $qnameValue->Prefix;
	}

	/**
	 * LocalNameFromQName
	 * @param object $qname
	 * @return object
	 */
	public static function LocalNameFromQName( $qname )
	{
		if ( $qname instanceof Undefined )
		{
			return $qname;
		}

		if ( ! $qname instanceof QNameValue )
		{
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $qname ), XmlTypeCardinality::One ),
					"QName"
				)
			);
		}

		/**
		 * @var QNameValue $qnameValue
		 */
		$qnameValue = $qname;

		return $qnameValue->LocalName == ""
			? Undefined::getValue()
			: $qnameValue->LocalName;
	}

	/**
	 * NamespaceUriFromQName
	 * @param object $qname
	 * @return object
	 */
	public static function NamespaceUriFromQName( $qname )
	{
		if ( $qname instanceof Undefined )
		{
			return $qname;
		}

		if ( ! $qname instanceof QNameValue )
		{
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $qname ), XmlTypeCardinality::One ),
					"QName"
				)
			);
		}

		/**
		 * @var QNameValue $qnameValue
		 */
		$qnameValue = $qname;

		return $qnameValue->NamespaceUri == ""
			? Undefined::getValue()
			: new AnyUriValue( $qnameValue->NamespaceUri );
	}

	/**
	 * StringToCodepoint
	 * @param object $text
	 * @return XPath2NodeIterator
	 */
	public static function StringToCodepoint( $text )
	{
		if ( $text instanceof Undefined )
		{
			return EmptyIterator::$Shared;
		}

		return new NodeIterator( function() use( $text ) { return CoreFuncs::CodepointIterator( $text . "" ); } );
	}

	/**
	 * CodepointToString
	 * @param XPath2NodeIterator $iter
	 * @return string
	 */
	public static function CodepointToString( $iter )
	{
		$sb = "";

		$utf8 = function ( $num )
		{
			if($num<=0x7F)       return chr($num);
			if($num<=0x7FF)      return chr(($num>>6)+192).chr(($num&63)+128);
			if($num<=0xFFFF)     return chr(($num>>12)+224).chr((($num>>6)&63)+128).chr(($num&63)+128);
			if($num<=0x1FFFFF)   return chr(($num>>18)+240).chr((($num>>12)&63)+128).chr((($num>>6)&63)+128).chr(($num&63)+128);
			return '';
		};

		// These codepoint ranges are defined in the XML 1.1 spec
		$validCodepoint = function( $codepoint )
		{
			return $codepoint >= 0x0001 && $codepoint <= 0xD7FF ||
				$codepoint >= 0xE000 && $codepoint <= 0xFFFD ||
				$codepoint >= 0x10000 && $codepoint <= 0x10FFFF;
		};

		/**
		 * @var XPathItem $item
		 */
		foreach ( $iter as $item )
		{
		    $codepoint = $item->getValueAsInt();

			if ( ! $validCodepoint( $codepoint ) )
			{
		    	throw XPath2Exception::withErrorCodeAndParam( "FOCH0001", Resources::FOCH0001, dechex( $codepoint ) );
			}

			$sb .= $utf8( $codepoint );
		}

		return $sb;
	}

	/**
	 * DefaultCollation
	 * @param XPath2Context $context
	 * @return string
	 */
	public static function DefaultCollation( $context )
	{
		return XmlReservedNs::collationCodepoint;
	}

	/**
	 * ResolveUri
	 * @param XPath2Context $context
	 * @param object $relative
	 * @return object
	 */
	public static function ResolveUriFromContext( $context, $relative )
	{
		if ( $relative instanceof Undefined )
		    return Undefined::getValue();

		$rel = $relative . "";
		if ( is_null( $context->RunningContext->BaseUri ) )
		    throw XPath2Exception::withErrorCode( "FONS0005", Resources::FONS0005 );
		try
		{
		    return new AnyUriValue( SchemaTypes::resolve_path( $context->RunningContext->getBaseUri(), $rel ) );
		}
		catch ( UriFormatException $ex )
		{
		    throw XPath2Exception::withErrorCode( "FORG0009", Resources::FORG0009 );
		}
	}

	/**
	 * InternetCombineUrl
	 * @param string $absolute
	 * @param string $relative
	 * @return string
	 */
	private static function InternetCombineUrl( $absolute, $relative )
	{
		if ( $relative == "" ) return $absolute;

		$p = parse_url( $relative );

		if( isset( $p["scheme"] ) ) return $relative;

		extract( parse_url( $absolute ) );

		$path = dirname( $path );
		if ( $path = "\\" ) $path = "/";

		if ( $relative[0] == '/' )
		{
			$cparts = array_filter( explode( "/", $relative ) );
		}
		else
		{
			$aparts = array_filter(explode( "/", $path ) );
			$rparts = array_filter(explode( "/", $relative ) );
			$cparts = array_merge( $aparts, $rparts );
			foreach( $cparts as $i => $part )
			{
				if( $part == '.')
				{
					$cparts[$i] = null;
				}

				if($part == '..')
				{
					$cparts[$i - 1] = null;
					$cparts[$i] = null;
				}
			}
			$cparts = array_filter( $cparts );
		}

		$path = implode( "/", $cparts );
		$url = "";
		if ( $scheme )
		{
			$url = "$scheme://";
		}
		if ( isset( $user ) )
		{
			$url .= "$user";
			if( $pass )
			{
				$url .= ":$pass";
			}
			$url .= "@";
		}

		if( $host )
		{
			$url .= "$host/";
		}

		$url .= $path;

		return $url;
	}

	/**
	 * Validate URL
	 * @param string $uri
	 * @param bool $requireSheme (default: false)
	 * @return boolean
	 */
	public static function validateUri( $uri, $requireSheme = false )
	{
		if ( strlen( $uri ) == 0 ) return true;
		if ( $uri[0] == ":" ) return false;
		if ( $requireSheme && ! preg_match( "/^(?:ftp|https?|feed|file):/", $uri ) ) return false;
		if ( substr_count( $uri, "#" ) > 1 ) return false;
		if ( preg_match( "/(?!%[0-9])%/", $uri ) ) return false;
		return true;
	}

	/**
	 * isAbsoluteUrl
	 * @param string $url
	 * @return boolean
	 */
	public static function isAbsoluteUrl( $url )
	{
		$pattern =	"/^(?:ftp|https?|feed|file)?:?\/{2,3}/xi";

		return (bool) preg_match( $pattern, $url );
	}

	/**
	 * ResolveUri
	 * @param object $relative
	 * @param object $baseUri
	 * @return object
	 */
	public static function ResolveUri( $relative, $baseUri )
	{
		if ( $relative instanceof Undefined )
		{
			return Undefined::getValue();
		}

		$rel = CoreFuncs::NormalizeSpace( $relative . "" );

		if ( ExtFuncs::isAbsoluteUrl( $rel ) )
		{
			return $rel;
		}

		if ( ! ExtFuncs::validateUri( $rel ) )
		{
			throw XPath2Exception::withErrorCodeAndParam( "FORG0002", Resources::FORG0002, $rel );
		}

		if ( $baseUri instanceof Undefined )
		{
			return Undefined::getValue();
		}

		$bsUri = CoreFuncs::NormalizeSpace( $baseUri . "" );

		if ( ! ExtFuncs::isAbsoluteUrl( $bsUri ) || ! ExtFuncs::validateUri( $bsUri ) )
		{
			throw XPath2Exception::withErrorCodeAndParam( "FORG0002", Resources::FORG0002, $rel );
		}

		if ( $bsUri == "" )
		{
			return new AnyUriValue( $rel );
		}

		try
		{
			$url = ExtFuncs::InternetCombineUrl( $bsUri, $rel );
			if ( ! ExtFuncs::validateUri( $url ) )
			// if ( ! filter_var( $url, FILTER_VALIDATE_URL ) )
			{
				throw XPath2Exception::withErrorCode( "FORG0009", Resources::FORG0009 );
			}
			return new AnyUriValue( $url );
		}
		catch ( UriFormatException $ex )
		{
		    throw XPath2Exception::withErrorCode( "FORG0009", Resources::FORG0009 );
		}
	}

	/**
	 * StaticBaseUri
	 * @param XPath2Context $context
	 * @return object
	 */
	public static function StaticBaseUri( $context )
	{
		if ( is_null( $context->RunningContext->BaseUri ) )
		    return Undefined::getValue();

		return new AnyUriValue( $context->RunningContext->BaseUri );
	}

	/**
	 * ImplicitTimezone
	 * @param XPath2Context $context
	 * @return DayTimeDurationValue
	 */
	public static function ImplicitTimezone( $context )
	{
		return DateTimeValue::fromDate( false, new \DateTime( "@" . $context->RunningContext->now ) )->TimezoneToInterval();
	}

	/**
	 * NodeLang
	 * @param IContextProvider $provider
	 * @param object $testLang
	 * @return bool
	 */
	public static function NodeLangByProvider( $provider, $testLang )
	{
		return ExtFuncs::NodeLang( $testLang, CoreFuncs::ContextNode( $provider ) );
	}

	/**
	 * NodeLang
	 * @param object $testLang
	 * @param object $node
	 * @return bool
	 */
	public static function NodeLang( $testLang, $node )
	{
		if ( $node instanceof Undefined )
		{
			return false;
		}

		if ( ! $node instanceof XPathNavigator )
		{
		    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $node ), XmlTypeCardinality::ZeroOrOne ),
					"$node()? in fn:lang()"
				)
			);
		}

	    /**
	     * @var XPathNavigator $nav
	     */
		$nav = $node;

		$xmlLang = $nav->getXmlLang();
		if ( $xmlLang == "" )
		{
			return false;
		}

		$lang = ( $testLang instanceof Undefined )
			? ""
			: $testLang . "";

		if ( strcasecmp( $xmlLang, $lang ) == 0 )
		{
			return true;
		}

		$index = strpos( $xmlLang, '-' );

		return $index
			? strcasecmp( substr( $xmlLang, 0, $index ), $lang ) == 0
			: false;
	}

	/**
	 * CurrentPosition
	 * @param IContextProvider $provider
	 * @return int
	 */
	public static function CurrentPosition( $provider )
	{
		return $provider->getCurrentPosition();
	}

	/**
	 * LastPosition
	 * @param IContextProvider $provider
	 * @return int
	 */
	public static function LastPosition( $provider )
	{
		return $provider->getLastPosition();
	}

	/**
	 * Unit tests
	 * @param object $instance
	 */
	public static function tests( $instance )
	{

	}
}


?>
