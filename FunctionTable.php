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
 * @version 0.1.1
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

use lyquidity\xml\MS\XmlReservedNs;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\xml\MS\XmlTypeCardinality;
use lyquidity\xml\MS\XmlNamespaceManager;
use lyquidity\XPath2\AST\FuncNode;
use lyquidity\XPath2\Value\QNameValue;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\DOM\DOMXPathNavigator;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\XPath2\Value\LanguageValue;
use lyquidity\XPath2\Value\NameValue;
use lyquidity\XPath2\Value\NMTOKENValue;
use lyquidity\XPath2\Value\AnyUriValue;
use lyquidity\XPath2\Value\UntypedAtomic;
use lyquidity\xml\TypeCode;
use lyquidity\xml\QName;
use lyquidity\xml\schema\SchemaTypes;

// public delegate object XPathFunctionDelegate( XPath2$context $context, I$context$provider $provider, object[] $args );

/**
 * FunctionTable ( public )
 */
class FunctionTable
{
	/**
	 * @var Dictionary<FunctionDesc, XPathFunctionDef> $_funcTable
	 */
	private $_funcTable;

	/**
	 * Constructor
	 */
	private function __construct( )
	{
		$this->_funcTable = array( );

		$this->AddWithArity( XmlReservedNs::xQueryFunc, "collection", 0, XPath2ResultType::NodeSet,
			function( $context, $provider, $args )
			{
				return ExtFuncs::CollectionsZeroParam( $context, $provider );
			}
		);

		$this->AddWithArity( XmlReservedNs::xQueryFunc, "collection", 1, XPath2ResultType::NodeSet,
			function( $context, $provider, $args )
			{
				if ( $args[0] instanceof Undefined )
				{
					return ExtFuncs::CollectionsZeroParam( $context, $provider );
				}

				return ExtFuncs::Collections( $context, $provider, CoreFuncs::CastString( $context, $args[0] ) );
			}
		);

		$this->AddWithArity( XmlReservedNs::xQueryFunc, "doc-available", 1, XPath2ResultType::Boolean,
			function( $context, $provider, $args )
			{
				$isString = is_string( $args[0] ) ||
					// $args[0] instanceof AnyUriValue ||
					( $args[0] instanceof IXmlSchemaType &&
					  SchemaTypes::getInstance()->resolvesToBaseType( ($args[0])->getSchemaType()->QualifiedName, array( "xs:anyURI", "xsd:anyURI" ) )
					);

				if ( $args[0] instanceof XPath2Item )
				{
					$schemaType = ($args[0])->getSchemaType();
					if ( $schemaType->TypeCode == XmlTypeCode::AnyUri ||
						 $schemaType->TypeCode == XmlTypeCode::String ||
						SchemaTypes::getInstance()->resolvesToBaseType( $schemaType->QualifiedName, array( "xs:string", "xsd:string" ) )
					)
					{
						$isString = true;
					}
				}

				$value = is_object( $args[0] )
					? $args[0]->getValue()
					: $args[0];

				if ( $value instanceof Undefined )
				{
					return CoreFuncs::$False;
				}

				if ( ! $isString )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
							SequenceType::GetXmlTypeCodeFromObject( $value ),
							SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Int, XmlTypeCardinality::ZeroOrMore )
					) );
				}

				return file_exists( $value )
					? CoreFuncs::$True
					: CoreFuncs::$False;
			}
		);

		$this->AddWithArity( XmlReservedNs::xQueryFunc, "doc", 1, XPath2ResultType::Navigator,
			function( $context, $provider, $args ) {

				$value = is_object( $args[0] )
					? $args[0]->getValue()
					: $args[0];

				if ( $value instanceof Undefined )
				{
					return Undefined::getValue();
				}

				if ( ! ( is_string( $value ) || ( $value instanceof XPath2Item && $value->getTypeCode() == TypeCode::String ) ) )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
						SequenceType::GetXmlTypeCodeFromObject( $value ),
						SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Int, XmlTypeCardinality::ZeroOrMore )
					) );
				}

				// If its a string already this doesn't matter but if its an XPath2Item then it will be coerced to a string
				$value = (string)$value;

				// Is there a base definition?
				if ( property_exists( $context, 'base' ) )
				{
					$parts = explode( "/", $context->base );
					if ( $parts )
					{
						// This pair of instructions removes any existing terminating node
						// such as x/y/z so that only x/y remains.  /x/y/z/ will not be changed
						array_pop( $parts );
						array_push( $parts, "" );
					}
					$value = urldecode( ( $parts ? implode( "/", $parts ) : "" ) . $value );
				}

				if ( is_file( $value ) )
				{
					if ( ! file_exists( $value ) )
					{
						// Test 'fn-doc-3' in SeqDocFunc.xml does not work without throwing exception FODC0002
						throw XPath2Exception::withErrorCodeAndParams( "FODC0002", Resources::FODC0002, array(
							$value,
						) );
						// return Undefined::getValue();
					}

				}
				else
				{
					if ( $value[0] != ":" && ! ExtFuncs::isAbsoluteUrl( $value ) ) // It's relative
					{
						$base = ExtFuncs::StaticBaseUri( $context );

						if ( ! $base instanceof Undefined )
						{
							$value = ExtFuncs::ResolveUri( $value, $base );
						}
					}

					if ( ! ExtFuncs::validateUri( $value, false ) )
					{
						throw XPath2Exception::withErrorCodeAndParam( "FODC0005", Resources::FODC0005, $value );
					}
				}

				try
				{
					$dom = new \DOMDocument();
					// Replace the handler because the status test handler traps any error and terminates the session
					$previousHandler = set_error_handler( null );
					@$dom->load( $value );
					set_error_handler( $previousHandler );
					if ( is_null( $dom->documentElement ) )
					{
						throw XPath2Exception::withErrorCodeAndParam( "FODC0002", Resources::FODC0002, $value );
					}

					// Load any schema information that's available.
					// Currently only loading a separate schema file. Type information in the instance document is ignored.
					$types = SchemaTypes::getInstance();

					/**
					 * @var \DOMElement $docElement
					 */
					$docElement = $dom->documentElement;
					if ( $docElement->hasAttribute('xsi:schemaLocation') )
					{
						$schemaLocation = $docElement->getAttribute('xsi:schemaLocation');
						$parts = array_filter( preg_split( "/\s/s",  $schemaLocation ) );

						$namespace = "";
						foreach ( $parts as $part )
						{
							if ( empty( $namespace ) )
							{
								$namespace = trim( $part );
							}
							else
							{
								// Only load the schema if it is not already loaded
								$xsd = trim( $part );
								$prefix = $types->getPrefixForNamespace( $namespace );
								if ( ! $prefix )
								{
									$types->processSchema( $xsd, true );
									$prefix = $types->getPrefixForNamespace( $namespace );
								}
								$namespace = "";
							}
						}
					}
					return new DOMXPathNavigator( $dom );

				}
				catch ( XPath2Exception $ex )
				{
					throw $ex;
				}
				catch ( \Exception $ex )
				{
					return Undefined::getValue();
				}
			}
		);

		$this->AddWithArity( XmlReservedNs::xQueryFunc, "error", 0, XPath2ResultType::Error,
			function( $context, $provider, $args ) {
				// error_log( "XPath 2.0 error (no arguments)" );
				throw XPath2Exception::withErrorCode( "FOER0000", "XPath 2.0 error (No error message)" );
			}
		);

		$this->AddWithArity( XmlReservedNs::xQueryFunc, "error", 1, XPath2ResultType::Error,
			function( $context, $provider, $args )
			{
				$errorCode = "XPTY0004";
				try
				{
					/**
					 * @var \QNameValue $qname
					 */
					$qname = CoreFuncs::CastArg( $context, $args[0], SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::QName, XmlTypeCardinality::One ) );
					// error_log( "XPath 2.0 error (qname: {$qname->Prefix}:{$qname->LocalName})" );
					if ( $qname->NamespaceUri == XmlReservedNs::xqtErrors )
					{
						$errorCode = $qname->LocalName;
					}
				}
				catch( \Exception $ex  )
				{
					// Report the default error
					$qname = new QNameValue();
					$qname->Prefix = "";
					$qname->LocalName = $args[0];
					$qname->NamespaceUri = "";
				}
				throw XPath2Exception::withErrorCodeAndParams( "$errorCode", "XPath 2.0 error (qname: {0}:{1})", array( $qname->Prefix, $qname->LocalName ) );
			}
		);

		$this->AddWithArity( XmlReservedNs::xQueryFunc, "error", 2, XPath2ResultType::Error,
			function( $context, $provider, $args ) {
				/**
				 * @var QName $qname
				 */
				$qname = $args[0] instanceof Undefined
					? QNameValue::fromQName( new \lyquidity\xml\qname( "err", XmlReservedNs::xqtErrors, "FOER0000") )
					: CoreFuncs::CastArg( $context, $args[0], SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::QName, XmlTypeCardinality::One ) );

				$desc  = CoreFuncs::CastArg( $context, $args[1], SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::String, XmlTypeCardinality::One ) );
				// error_log( "XPath 2.0 error (qname: {$qname->Prefix}:{$qname->LocalName}; description: $desc)" );
				$errorCode = $qname->NamespaceUri == XmlReservedNs::xqtErrors
					? $qname->LocalName
					// BMS 2018-02-28
					: "{$qname->Prefix}:{$qname->LocalName}";
					// : "XPTY0004";
				throw XPath2Exception::withErrorCodeAndParams( $errorCode, "XPath 2.0 error (qname: {0}:{1}; description: {2})", array( $qname->Prefix, $qname->LocalName, $desc ) );
			}
		);

		$this->AddWithArity( XmlReservedNs::xQueryFunc, "error", 3, XPath2ResultType::Error,
			function( $context, $provider, $args ) {
				/**
				 * @var QName $qname
				 */
				$qname = $args[0] instanceof Undefined
					? QNameValue::fromQName( new \lyquidity\xml\qname( "err", XmlReservedNs::xqtErrors, "FOER0000") )
					: CoreFuncs::CastArg( $context, $args[0], SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::QName, XmlTypeCardinality::One ) );

				$desc  = CoreFuncs::CastArg( $context, $args[1], SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::String, XmlTypeCardinality::One ) );
				$object = XPath2Convert::ToString( $args[2] );
				error_log( "XPath 2.0 error (qname: {$qname->Prefix}:{$qname->LocalName}; description: $desc; with object - $object)" );
				$errorCode = $qname->NamespaceUri == XmlReservedNs::xqtErrors
					? $qname->LocalName
					: "XPTY0004";
				throw XPath2Exception::withErrorCodeAndParams( $errorCode, "XPath 2.0 error (qname: {0}:{1}; description: {2}; object: {3})", array( $qname->Prefix, $qname->LocalName, $desc, $object ) );
			}
		);

		$this->Add( XmlReservedNs::xs, "language", XPath2ResultType::String,
			function( $context, $provider, $args ) {

				if ( count( $args ) != 1 )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPST0017", Resources::XPST0017, array(
							"xs", "language", XmlReservedNs::xs
					) );
				}

				// $result = CoreFuncs::CastString( $context, $args[0] );
				$result = is_string( $args[0] ) || is_int( $args[0] ) || is_double( $args[0] ) ? (string)$args[0] : $args[0]->__toString();
				return new LanguageValue( $result );
				// return XPath2Item::fromValueAndType( $double, XmlSchema::$Double );
			}
		);

		$this->Add( XmlReservedNs::xs, "double", XPath2ResultType::Number,
			function( $context, $provider, $args ) {

				if ( count( $args ) != 1 )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPST0017", Resources::XPST0017, array(
						"xs", "double", XmlReservedNs::xs
					) );
				}

				try
				{
					$double = CoreFuncs::CastTo( $context, $args[0],SequenceTypes::$Double, true );
				}
				catch ( XPath2Exception $ex )
				{
					if ( $ex->ErrorCode != "FORG0006" )
					{
						throw $ex;
					}

					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
							SequenceType::GetXmlTypeCodeFromObject( $args[0] ),
							SequenceTypes::$Double,
					) );
				}

				return XPath2Item::fromValueAndType( $double, XmlSchema::$Double );
			}
		);

		$this->Add( XmlReservedNs::xs, "float", XPath2ResultType::Number,
			function( $context, $provider, $args ) {

				if ( count( $args ) != 1 )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPST0017", Resources::XPST0017, array(
							"xs", "float", XmlReservedNs::xs
					) );
				}

				try
				{
					$float = CoreFuncs::CastTo( $context, $args[0], SequenceTypes::$Float, true );
				}
				catch ( XPath2Exception $ex )
				{
					if ( $ex->ErrorCode != "FORG0006" )
					{
						throw $ex;
					}

					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
							SequenceType::GetXmlTypeCodeFromObject( $args[0] ),
							SequenceTypes::$Float,
					) );
				}

				return XPath2Item::fromValueAndType( $float, XmlSchema::$Float );
			}
			);

		$this->Add( XmlReservedNs::xs, "QName", XPath2ResultType::QName,
			function( $context, $provider, $args ) {

				if ( count( $args ) != 1 )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPST0017", Resources::XPST0017, array(
						"xs", "QName", XmlReservedNs::xs
					) );
				}

				try
				{
					/**
					 * @var XPath2Item $x
					 */
					if ( ! (
						is_string( $args[0] ) ||
						$args[0] instanceof Undefined ||
						$args[0] instanceof AnyUriValue ||
						$args[0] instanceof UntypedAtomic ||
						( $args[0] instanceof XPath2Item && $args[0]->getXPath2ResultType() == XPath2ResultType::String )
					) )
					{
						throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
							SequenceType::GetXmlTypeCodeFromObject( $args[0] ),
							SequenceTypes::$StringX,
						) );
					}
					$qName = CoreFuncs::CastString( $context, $args[0] );
				}
				catch ( XPath2Exception $ex )
				{
					if ( $ex->ErrorCode != "FORG0006" )
					{
						throw $ex;
					}

					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
						SequenceType::GetXmlTypeCodeFromObject( $args[0] ),
						SequenceTypes::$StringX,
					) );
				}

				if ( $qName == "" )
				{
					throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $args[0], "xs:QName" ) );
				}

				$parts = explode( ":", $qName );
				if ( count( $parts ) > 2 )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPST0017", Resources::XPST0017,  array(
						"xs", "QName", XmlReservedNs::xs
					) );
				}

				$prefix = count( $parts ) == 1 ? "" : $parts[0];
				// $localname = count( $parts ) == 1 ? $parts[0] : $parts[1];
				$namespaces = $context->NamespaceManager->getNamespaces();
				$ns = isset( $namespaces[ $prefix ] )
					? $namespaces[ $prefix ]
					: "";

				try {
					return ExtFuncs::CreateQName( $context, $ns, $qName );
				}
				catch( XPath2Exception $ex )
				{
					if ( $ex->ErrorCode != "FOCA0002" )
					{
						throw $ex;
					}

					throw XPath2Exception::withErrorCode( "FONS0004", Resources::FONS0004 );
				}
			}
		);

		// $this->AddWithArity( XmlReservedNs::xs, "QName", 2, XPath2ResultType::QName,
		// 	function( $context, $provider, $args ) {
		// 	}
		// );

		$this->AddWithArity( XmlReservedNs::xs, "dayTimeDuration", 1, XPath2ResultType::Duration,
			function( $context, $provider, $args ) {
				return CoreFuncs::CastArg( $context, $args[0], SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::DayTimeDuration, XmlTypeCardinality::One ) );
			}
		);

		$this->AddWithArity( XmlReservedNs::xs, "boolean", 1, XPath2ResultType::Boolean,
			function( $context, $provider, $args ) {

				if ( $args[0] instanceof IXmlSchemaType )
				{
					$args[0] = $args[0]->getValue();
				}

				$value = $args[0] instanceof XPath2Item
					? $args[0]->getTypedValue()
					: $args[0];

				// BMS 2018-01-23 This seems like duplication of the code above but since an XPath2Item instance
				//
				if ( $value instanceof IXmlSchemaType )
				{
					$value = $value->getValue();
				}

				if ( $value instanceof CoreFuncs::$False || $value instanceof CoreFuncs::$True )
				{
					return $value;
				}

				if ( is_numeric( $value ) )
				{
					return is_nan( $value ) || $value == 0
						? CoreFuncs::$False
						: CoreFuncs::$True;
				}

				if ( is_string( $value ) )
				{
					if ( trim( $value ) == "false" )
					{
						return CoreFuncs::$False;
					}

					if ( trim( $value ) == "true" )
					{
						return CoreFuncs::$True;
					}
				}

				if ( is_bool( $value ) )
				{
					return $value;
				}

				throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array(
					$value, "boolean",
				) );

				return ExtFuncs::CreateDateTime( $date, $time );
			}
		);

		$this->AddWithArity( XmlReservedNs::xs, "dateTime", 2, XPath2ResultType::DateTime,
			function( $context, $provider, $args ) {

				$date = null;
				$time = null;

				try
				{
					$date = CoreFuncs::CastArg( $context, $args[0], SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Date, XmlTypeCardinality::ZeroOrOne ) );
					$time = CoreFuncs::CastArg( $context, $args[1], SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Time, XmlTypeCardinality::ZeroOrOne ) );
				}
				catch( XPath2Exception $ex )
				{
					if ( $ex->ErrorCode == "FORG0001")
					{
						throw XPath2Exception::withErrorCodeAndParams( "XPST0017", Resources::XPST0017, array(
							"xs", "dateTime", XmlReservedNs::xs
						) );
					}

					throw $ex;
				}

				return ExtFuncs::CreateDateTime( $date, $time );
			}
		);
		$this->AddWithArity( XmlReservedNs::xs, "dateTime", 1, XPath2ResultType::DateTime,
			function( $context, $provider, $args ) {
				$dateTime = CoreFuncs::CastArg( $context, $args[0], SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::DateTime, XmlTypeCardinality::ZeroOrOne ) );
				return ExtFuncs::CreateDateTime(
					$dateTime, $dateTime
					// CoreFuncs::CastArg( $context, $args[0], SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::DateTime, XmlTypeCardinality::ZeroOrOne ) )
					// CoreFuncs::CastArg( $context, $args[0], SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Time, XmlTypeCardinality::ZeroOrOne ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xs, "dateTimex", 0, XPath2ResultType::DateTime,
			function( $context, $provider, $args ) {
				return ExtFuncs::CreateDateTime(
					CoreFuncs::CastArg( $context, null, SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Date, XmlTypeCardinality::ZeroOrOne ) ),
					CoreFuncs::CastArg( $context, null, SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Time, XmlTypeCardinality::ZeroOrOne ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "dateTime", 2, XPath2ResultType::DateTime,
			function( $context, $provider, $args ) {
				return ExtFuncs::CreateDateTime(
					CoreFuncs::CastArg( $context, $args[0], SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Date, XmlTypeCardinality::ZeroOrOne ) ),
					CoreFuncs::CastArg( $context, $args[1], SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Time, XmlTypeCardinality::ZeroOrOne ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "dateTimex", 1, XPath2ResultType::DateTime,
			function( $context, $provider, $args ) {
				$dateTime = CoreFuncs::CastArg( $context, $args[0], SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::DateTime, XmlTypeCardinality::ZeroOrOne ) );
				return ExtFuncs::CreateDateTime(
					$dateTime, $dateTime
					// CoreFuncs::CastArg( $context, $args[0], SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Date, XmlTypeCardinality::ZeroOrOne ) ),
					// CoreFuncs::CastArg( $context, $args[0], SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Time, XmlTypeCardinality::ZeroOrOne ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "dateTimex", 0, XPath2ResultType::DateTime,
			function( $context, $provider, $args ) {
				return ExtFuncs::CreateDateTime(
					CoreFuncs::CastArg( $context, null, SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Date, XmlTypeCardinality::ZeroOrOne ) ),
					CoreFuncs::CastArg( $context, null, SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Time, XmlTypeCardinality::ZeroOrOne ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "current-dateTime", 0, XPath2ResultType::DateTime,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetCurrentDateTime( $context );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "current-date", 0, XPath2ResultType::DateTime,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetCurrentDate( $context );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "current-time", 0, XPath2ResultType::DateTime,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetCurrentTime( $context );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "in-scope-prefixes", 1, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetInScopePrefixes( CoreFuncs::NodeValue( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "namespace-uri-for-prefix", 2, XPath2ResultType::AnyUri,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetNamespaceUriForPrefix( $context, CoreFuncs::Atomize( $args[0] ), CoreFuncs::NodeValue( $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "resolve-QName", 2, XPath2ResultType::QName,
			function( $context, $provider, $args ) {
				return ExtFuncs::ResolveQName( $context, CoreFuncs::Atomize( $args[0] ), CoreFuncs::NodeValue( $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "QName", 2, XPath2ResultType::QName,
			function( $context, $provider, $args ) {
				return ExtFuncs::CreateQName( $context, CoreFuncs::CastString( $context, $args[0] ),
					CoreFuncs::CastArg( $context, $args[1], SequenceType::WithTypeCode( XmlTypeCode::String ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "prefix-from-QName", 1, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return ExtFuncs::PrefixFromQName( CoreFuncs::Atomize( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "local-name-from-QName", 1, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return ExtFuncs::LocalNameFromQName( CoreFuncs::Atomize( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "namespace-uri-from-QName", 1, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return ExtFuncs::NamespaceUriFromQName( CoreFuncs::Atomize( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "string-to-codepoints", 1, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {

				if ( $args[0] instanceof XPath2NodeIterator )
				{
					$success = $args[0]->MoveNext();
					/**
					 * @var DOMXPathNavigator $current
					 */
					$current = $args[0]->getCurrent();
					$args[0] = $current->GetTypedValue();
				}

				if ( ! (
						is_string( $args[0] ) ||
						$args[0] instanceof Undefined ||
						( $args[0] instanceof XPath2Item && $args[0]->getTypeCode() == TypeCode::String )
					)
				)
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
							SequenceType::GetXmlTypeCodeFromObject( $args[0] ),
							SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Int, XmlTypeCardinality::ZeroOrMore )
					) );
				}
				return ExtFuncs::StringToCodepoint( CoreFuncs::CastString( $context, $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "codepoints-to-string", 1, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				if ( is_string( $args[0] ) || ( $args[0] instanceof XPath2Item && $args[0]->getTypeCode() == TypeCode::String ) )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
						SequenceType::GetXmlTypeCodeFromObject( $args[0] ),
						SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Int, XmlTypeCardinality::ZeroOrMore )
					) );
				}
				return ExtFuncs::CodepointToString( CoreFuncs::CastArg( $context, $args[0],
					SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Int, XmlTypeCardinality::ZeroOrMore ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "default-collation", 0, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return ExtFuncs::DefaultCollation( $context );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "resolve-uri", 2, XPath2ResultType::AnyUri,
			function( $context, $provider, $args ) {
				return ExtFuncs::ResolveUri( CoreFuncs::CastString( $context, $args[0] ), CoreFuncs::CastString( $context, $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "resolve-uri", 1, XPath2ResultType::AnyUri,
			function( $context, $provider, $args ) {
				return ExtFuncs::ResolveUri( $context, CoreFuncs::CastString( $context, $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "static-base-uri", 0, XPath2ResultType::AnyUri,
			function( $context, $provider, $args ) {
				return ExtFuncs::StaticBaseUri( $context );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "implicit-timezone", 0, XPath2ResultType::Duration,
			function( $context, $provider, $args ) {
				return ExtFuncs::ImplicitTimezone( $context );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "lang", 2, XPath2ResultType::Boolean,
			function( $context, $provider, $args ) {
				return ExtFuncs::NodeLang( CoreFuncs::CastString( $context, $args[0] ), CoreFuncs::NodeValue( $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "lang", 1, XPath2ResultType::Boolean,
			function( $context, $provider, $args ) {
				return ExtFuncs::NodeLangByProvider( $provider, CoreFuncs::CastString( $context, $args[0] ) );
			}
		);

		$this->AddWithArity( XmlReservedNs::xQueryFunc, "name", 1, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetName( CoreFuncs::NodeValue( $args[0], false ) );
			}
		);

		// Lower case 'name' is not valid
		$this->Add( XmlReservedNs::xs, "name", XPath2ResultType::String,
			function( $context, $provider, $args ) {
				throw XPath2Exception::withErrorCodeAndParams( "XPST0017", Resources::XPST0017, array(
						"xs", "Name", XmlReservedNs::xs
				) );
			}
		);

		$this->Add( XmlReservedNs::xs, "Name", XPath2ResultType::String,
			function( $context, $provider, $args ) {

				if ( count( $args ) != 1 )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPST0017", Resources::XPST0017, array(
						"xs", "Name", XmlReservedNs::xs
					) );
				}

				$result = CoreFuncs::CastString( $context, $args[0] );
				return new NameValue( $result );
			}
		);

		$this->Add( XmlReservedNs::xs, "NMTOKEN", XPath2ResultType::String,
			function( $context, $provider, $args ) {

				if ( count( $args ) != 1 )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPST0017", Resources::XPST0017, array(
						"xs", "NMTOKEN", XmlReservedNs::xs
					) );
				}

				$result = CoreFuncs::CastString( $context, $args[0] );
				return new NMTOKENValue( $result );
			}
		);

		$this->Add( XmlReservedNs::xs, "anyURI", XPath2ResultType::AnyUri,
			function( $context, $provider, $args ) {

				if ( count( $args ) != 1 )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPST0017", Resources::XPST0017, array(
							"xs", "anyURI", XmlReservedNs::xs
					) );
				}

				$result = CoreFuncs::CastString( $context, $args[0] );
				return new AnyUriValue( $result );
			}
		);

		$this->AddWithArity( XmlReservedNs::xQueryFunc, "name", 0, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetNameWithProvider( $provider );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "node-name", 1, XPath2ResultType::QName,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetNodeName( $context, CoreFuncs::NodeValue( $args[0], false ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "local-name", 1, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetLocalName( CoreFuncs::NodeValue( $args[0], false ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "local-name", 0, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetLocalNameWithProvider( $provider );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "namespace-uri", 1, XPath2ResultType::AnyUri,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetNamespaceUri( CoreFuncs::NodeValue( $args[0], false ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "namespace-uri", 0, XPath2ResultType::AnyUri,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetNamespaceUriWithProvider( $provider );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "nilled", 1, XPath2ResultType::Boolean,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetNilled( $context, CoreFuncs::NodeValue( $args[0], false ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "base-uri", 0, XPath2ResultType::AnyUri,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetBaseUriWithProvider( $context, $provider );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "base-uri", 1, XPath2ResultType::AnyUri,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetBaseUri( $context, CoreFuncs::NodeValue( $args[0], false ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "document-uri", 1, XPath2ResultType::AnyUri,
			function( $context, $provider, $args ) {
				return ExtFuncs::DocumentUri( CoreFuncs::NodeValue( $args[0], false ) );
			}
		);
		// $this->AddWithArity( XmlReservedNs::xQueryFunc, "trace", 1, XPath2ResultType::NodeSet,
		// 	function( $context, $provider, $args ) {
		// 		return ExtFuncs::WriteTrace( $context, XPath2NodeIterator::Create( $args[0] ) );
		// 	}
		// );
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "trace", 2, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {
				return ExtFuncs::WriteTraceWithLabel( $context, XPath2NodeIterator::Create( $args[0] ), CoreFuncs::CastArg( $context, $args[1], SequenceType::WithTypeCode( XmlTypeCode::String ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "data", 1, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetData( XPath2NodeIterator::Create( $args[0] ) );
			}
		);
		$this->Add( XmlReservedNs::xQueryFunc, "concat", XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return ExtFuncs::Concat( $context, $args );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "string-join", 2, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return ExtFuncs::StringJoin(
					XPath2NodeIterator::Create( $args[0] ),
					CoreFuncs::CastArg( $context, $args[1], SequenceType::WithTypeCode( XmlTypeCode::String ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "substring", 3, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return ExtFuncs::SubstringWithLength( CoreFuncs::CastString( $context, $args[0] ), CoreFuncs::Number( $context, $args[1] ), CoreFuncs::Number( $context, $args[2] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "substring", 2, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return ExtFuncs::Substring( CoreFuncs::CastString( $context, $args[0] ), CoreFuncs::Number( $context, $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "string-length", 0, XPath2ResultType::Number,
			function( $context, $provider, $args ) {
				return ExtFuncs::StringLengthWithProvider( $context, $provider );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "string-length", 1, XPath2ResultType::Number,
			function( $context, $provider, $args ) {
				return ExtFuncs::StringLength( CoreFuncs::CastString( $context, $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "normalize-space", 0, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return ExtFuncs::NormalizeSpace( $context, $provider );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "normalize-space", 1, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return CoreFuncs::NormalizeSpace( CoreFuncs::CastString( $context, $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "normalize-unicode", 1, XPath2ResultType::String,
			function( $context, $provider, $args ) {

				try
				{
					if ( ! (
						is_string( $args[0] ) ||
						$args[0] instanceof Undefined ||
						( $args[0] instanceof XPath2Item && $args[0]->getTypeCode() == TypeCode::String )
					) )
					{
						throw new \Exception();
					}
					return ExtFuncs::NormalizeUnicode( CoreFuncs::CastString( $context, $args[0] ) );
				}
				catch ( \Exception $ex )
				{
					if ( $ex instanceof XPath2Exception && $ex->ErrorCode != "FORG0006" )
					{
						throw $ex;
					}

					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
						SequenceType::GetXmlTypeCodeFromObject( $args[0] ),
						SequenceTypes::$StringX
					) );
				}
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "normalize-unicode", 2, XPath2ResultType::String,
			function( $context, $provider, $args ) {

				try
				{
					if ( ! (
						  is_string( $args[0] ) ||
						  $args[0] instanceof Undefined ||
						  ( $args[0] instanceof XPath2Item && $args[0]->getTypeCode() == TypeCode::String )
						) ||
						! ( is_string( $args[1] ) ||
						  ( $args[1] instanceof XPath2Item && $args[1]->getTypeCode() == TypeCode::String )
						)
					)
					{
						throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
							SequenceType::GetXmlTypeCodeFromObject( $args[0] ),
							SequenceTypes::$StringX
						) );
					}
					return ExtFuncs::NormalizeUnicodeWithForm( CoreFuncs::CastString( $context, $args[0] ), CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[1] ), SequenceType::WithTypeCode( XmlTypeCode::String ) ) );
				}
				catch ( \Exception $ex )
				{
					if ( $ex instanceof XPath2Exception && $ex->ErrorCode != "FORG0006" )
					{
						throw $ex;
					}

					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
						SequenceType::GetXmlTypeCodeFromObject( $args[0] ),
						SequenceTypes::$StringX
					) );
				}
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "upper-case", 1, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return ExtFuncs::UpperCase( CoreFuncs::CastString( $context, $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "lower-case", 1, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return ExtFuncs::LowerCase( CoreFuncs::CastString( $context, $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "translate", 3, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				if ( ! (
						$args[0] instanceof Undefined ||
						is_string( $args[0] ) ||
						( $args[0] instanceof XPath2Item && $args[0]->getTypeCode() == TypeCode::String )
					) ||
					! ( is_string( $args[1] ) ||
						( $args[1] instanceof XPath2Item && $args[1]->getTypeCode() == TypeCode::String )
					) ||
					! ( is_string( $args[2] ) ||
					    ( $args[2] instanceof XPath2Item && $args[2]->getTypeCode() == TypeCode::String )
					)
				)
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
						SequenceType::GetXmlTypeCodeFromObject( ! is_string( $args[0] ) ? $args[0] : ( ! is_string( $args[1] ) ? $args[1] : $args[2] ) ),
						SequenceTypes::$StringX
					) );
				}

				return ExtFuncs::Translate( CoreFuncs::CastString( $context, $args[0] ),
					CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[1] ), SequenceType::WithTypeCode( XmlTypeCode::String ) ),
					CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[2] ), SequenceType::WithTypeCode( XmlTypeCode::String ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "encode-for-uri", 1, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				if ( ! (
					  $args[0] instanceof Undefined ||
					  is_string( $args[0] ) ||
					  ( $args[0] instanceof XPath2Item && $args[0]->getTypeCode() == TypeCode::String )
					)
				)
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
						SequenceType::GetXmlTypeCodeFromObject( $args[0] ),
						SequenceTypes::$StringX
					) );
				}
				return ExtFuncs::EncodeForUri( CoreFuncs::CastString( $context, $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "iri-to-uri", 1, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				try
				{
					if ( ! (
							is_string( $args[0] ) ||
							$args[0] instanceof Undefined ||
							$args[0] instanceof AnyUriValue ||
							$args[0] instanceof UntypedAtomic ||
							( $args[0] instanceof XPath2Item && $args[0]->getTypeCode() == TypeCode::String )
						)
					)
					{
						throw new \Exception();
					}
					return ExtFuncs::IriToUri( CoreFuncs::CastString( $context, $args[0] ) );
				}
				catch ( \Exception $ex )
				{
					if ( $ex instanceof XPath2Exception && $ex->ErrorCode != "FORG0006" )
					{
						throw $ex;
					}

					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
						SequenceType::GetXmlTypeCodeFromObject( $args[0] ),
						SequenceTypes::$StringX
					) );
				}
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "escape-html-uri", 1, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				try
				{
					if ( ! (
							is_string( $args[0] ) ||
							$args[0] instanceof Undefined ||
							$args[0] instanceof AnyUriValue ||
							$args[0] instanceof UntypedAtomic ||
							( $args[0] instanceof XPath2Item && $args[0]->getTypeCode() == TypeCode::String )
						)
					)
					{
						throw new \Exception();
					}
					return ExtFuncs::EscapeHtmlUri( CoreFuncs::CastString( $context, $args[0] ) );
				}
				catch ( \Exception $ex )
				{
					if ( $ex instanceof XPath2Exception && $ex->ErrorCode != "FORG0006" )
					{
						throw $ex;
					}

					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
						SequenceType::GetXmlTypeCodeFromObject( $args[0] ),
						SequenceTypes::$StringX
					) );
				}
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "contains", 3, XPath2ResultType::Boolean,
			function( $context, $provider, $args ) {
				return ExtFuncs::Contains( $context, CoreFuncs::CastString( $context, $args[0] ),
					CoreFuncs::CastString( $context, $args[1] ),
					CoreFuncs::CastArg( $context, $args[2], SequenceType::WithTypeCode( XmlTypeCode::String ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "contains", 2, XPath2ResultType::Boolean, function( $context, $provider, $args ) {
			return ExtFuncs::Contains( CoreFuncs::CastString( $context, $args[0] ),
				CoreFuncs::CastString( $context, $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "starts-with", 3, XPath2ResultType::Boolean, function( $context, $provider, $args ) {
			return ExtFuncs::StartsWithCollation( $context, CoreFuncs::CastString( $context, $args[0] ),
				CoreFuncs::CastString( $context, $args[1] ),
				CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[2] ), SequenceType::WithTypeCode( XmlTypeCode::String ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "starts-with", 2, XPath2ResultType::Boolean, function( $context, $provider, $args ) {
			return ExtFuncs::StartsWith( CoreFuncs::CastString( $context, $args[0] ),
				CoreFuncs::CastString( $context, $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "ends-with", 3, XPath2ResultType::Boolean, function( $context, $provider, $args ) {
			return ExtFuncs::EndsWithAndCollation( $context, CoreFuncs::CastString( $context, $args[0] ),
				CoreFuncs::CastString( $context, $args[1] ),
				CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[2] ), SequenceType::WithTypeCode( XmlTypeCode::String ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "ends-with", 2, XPath2ResultType::Boolean, function( $context, $provider, $args ) {
			return ExtFuncs::EndsWith( CoreFuncs::CastString( $context, $args[0] ),
				CoreFuncs::CastString( $context, $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "substring-before", 3, XPath2ResultType::String, function( $context, $provider, $args ) {
			return ExtFuncs::SubstringBeforeAndCollation( $context, CoreFuncs::CastString( $context, $args[0] ),
				CoreFuncs::CastString( $context, $args[1] ),
				CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[2] ), SequenceType::WithTypeCode( XmlTypeCode::String ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "substring-before", 2, XPath2ResultType::String, function( $context, $provider, $args ) {
			return ExtFuncs::SubstringBefore( CoreFuncs::CastString( $context, $args[0] ),
				CoreFuncs::CastString( $context, $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "substring-after", 3, XPath2ResultType::String, function( $context, $provider, $args ) {
			return ExtFuncs::SubstringAfterAndCollation( $context, CoreFuncs::CastString( $context, $args[0] ),
				CoreFuncs::CastString( $context, $args[1] ),
				CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[2] ), SequenceType::WithTypeCode( XmlTypeCode::String ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "substring-after", 2, XPath2ResultType::String, function( $context, $provider, $args ) {
			return ExtFuncs::SubstringAfter( CoreFuncs::CastString( $context, $args[0] ),
				CoreFuncs::CastString( $context, $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "matches", 2, XPath2ResultType::Boolean, function( $context, $provider, $args ) {
			return ExtFuncs::Matches( CoreFuncs::CastString( $context, $args[0] ),
				CoreFuncs::CastString( $context, $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "matches", 3, XPath2ResultType::Boolean, function( $context, $provider, $args ) {
			return ExtFuncs::MatchesWithFlags( CoreFuncs::CastString( $context, $args[0] ),
				CoreFuncs::CastString( $context, $args[1] ),
				CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[2] ), SequenceType::WithTypeCode( XmlTypeCode::String ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "replace", 3, XPath2ResultType::String, function( $context, $provider, $args ) {
			return ExtFuncs::Replace( CoreFuncs::CastString( $context, $args[0] ),
				CoreFuncs::CastString( $context, $args[1] ),
				CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[2] ), SequenceType::WithTypeCode( XmlTypeCode::String ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "replace", 4, XPath2ResultType::String, function( $context, $provider, $args ) {
			return ExtFuncs::ReplaceWithFlags( CoreFuncs::CastString( $context, $args[0] ),
				CoreFuncs::CastString( $context, $args[1] ),
				CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[2] ), SequenceType::WithTypeCode( XmlTypeCode::String ) ),
				CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[3] ), SequenceType::WithTypeCode( XmlTypeCode::String ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "tokenize", 2, XPath2ResultType::NodeSet, function( $context, $provider, $args ) {
			return ExtFuncs::Tokenize( CoreFuncs::CastString( $context, $args[0] ),
				CoreFuncs::CastString( $context, $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "tokenize", 3, XPath2ResultType::NodeSet, function( $context, $provider, $args ) {
			return ExtFuncs::TokenizeWithFlags( CoreFuncs::CastString( $context, $args[0] ),
				CoreFuncs::CastString( $context, $args[1] ),
				CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[2] ), SequenceType::WithTypeCode( XmlTypeCode::String ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "years-from-duration", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::YearsFromDuration( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Duration, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "months-from-duration", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::MonthsFromDuration( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Duration, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "days-from-duration", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::DaysFromDuration( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Duration, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "hours-from-duration", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::HoursFromDuration( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Duration, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "minutes-from-duration", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::MinutesFromDuration( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Duration, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "seconds-from-duration", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::SecondsFromDuration( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Duration, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "year-from-dateTime", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::YearFromDateTime( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::DateTime, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "month-from-dateTime", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::MonthFromDateTime( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::DateTime, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "day-from-dateTime", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::DayFromDateTime( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::DateTime, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "hours-from-dateTime", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::HoursFromDateTime( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::DateTime, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "minutes-from-dateTime", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::MinutesFromDateTime( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::DateTime, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "seconds-from-dateTime", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::SecondsFromDateTime( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::DateTime, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "timezone-from-dateTime", 1, XPath2ResultType::Duration, function( $context, $provider, $args ) {
		   return ExtFuncs::TimezoneFromDateTime( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::DateTime, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "year-from-date", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::YearFromDate( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Date, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "month-from-date", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::MonthFromDate( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Date, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "day-from-date", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::DayFromDate( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Date, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "timezone-from-date", 1, XPath2ResultType::Duration, function( $context, $provider, $args ) {
			return ExtFuncs::TimezoneFromDate( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Date, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "hours-from-time", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::HoursFromTime( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Time, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "minutes-from-time", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::MinutesFromTime( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Time, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "seconds-from-time", 1, XPath2ResultType::Number, function( $context, $provider, $args ) {
			return ExtFuncs::SecondsFromTime( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Time, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "timezone-from-time", 1, XPath2ResultType::Duration, function( $context, $provider, $args ) {
			return ExtFuncs::TimezoneFromTime( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Time, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "adjust-dateTime-to-timezone", 2, XPath2ResultType::DateTime, function( $context, $provider, $args ) {
			return ExtFuncs::AdjustDateTimeToTimezoneWithTimezone(
					CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ), SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::DateTime, XmlTypeCardinality::ZeroOrOne ) ),
					CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[1] ), SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::DayTimeDuration, XmlTypeCardinality::ZeroOrOne ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "adjust-dateTime-to-timezone", 1, XPath2ResultType::DateTime, function( $context, $provider, $args ) {
			return ExtFuncs::AdjustDateTimeToTimezone(
					CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ), SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::DateTime, XmlTypeCardinality::ZeroOrOne ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "adjust-date-to-timezone", 2, XPath2ResultType::DateTime, function( $context, $provider, $args ) {
			return ExtFuncs::AdjustDateToTimezoneWithTimezone( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Date, XmlTypeCardinality::ZeroOrOne ) ),
				CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[1] ), SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::DayTimeDuration,
					 XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "adjust-date-to-timezone", 1, XPath2ResultType::DateTime, function( $context, $provider, $args ) {
			return ExtFuncs::AdjustDateToTimezone( CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ),
				SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Date, XmlTypeCardinality::ZeroOrOne ) ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "adjust-time-to-timezone", 2, XPath2ResultType::DateTime,
			function( $context, $provider, $args ) {
				return ExtFuncs::AdjustTimeToTimezoneWithTimezone(
					CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ), SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Time, XmlTypeCardinality::ZeroOrOne ) ),
					CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[1] ), SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::DayTimeDuration, XmlTypeCardinality::ZeroOrOne ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "adjust-time-to-timezone", 1, XPath2ResultType::DateTime,
			function( $context, $provider, $args ) {
				return ExtFuncs::AdjustTimeToTimezone(
					CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[0] ), SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Time, XmlTypeCardinality::ZeroOrOne ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "abs", 1, XPath2ResultType::Number,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetAbs( CoreFuncs::Atomize( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "ceiling", 1, XPath2ResultType::Number,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetCeiling( CoreFuncs::Atomize( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "floor", 1, XPath2ResultType::Number,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetFloor( CoreFuncs::Atomize( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "round", 1, XPath2ResultType::Number,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetRound( CoreFuncs::Atomize( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "round-half-to-even", 2, XPath2ResultType::Number,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetRoundHalfToEvenWithPrecision( CoreFuncs::Atomize( $args[0] ), CoreFuncs::Atomize( $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "round-half-to-even", 1, XPath2ResultType::Number,
			function( $context, $provider, $args ) {
				return ExtFuncs::GetRoundHalfToEven( CoreFuncs::Atomize( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "compare", 2, XPath2ResultType::Number,
			function( $context, $provider, $args ) {
				return ExtFuncs::Compare( $context, CoreFuncs::CastString( $context, $args[0] ), CoreFuncs::CastString( $context, $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "compare", 3, XPath2ResultType::Number,
			function( $context, $provider, $args ) {
				return ExtFuncs::CompareWithCollation( $context,
					CoreFuncs::CastString( $context, $args[0] ),
					CoreFuncs::CastString( $context, $args[1] ),
					CoreFuncs::CastArg( $context, $args[2], SequenceType::WithTypeCode( XmlTypeCode::String ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "codepoint-equal", 2, XPath2ResultType::Boolean,
			function( $context, $provider, $args )
			{
				$valid = function( $args )
				{
					foreach ( $args as $arg )
					{
						if ( ! (
							is_string( $arg ) ||
							$arg instanceof Undefined ||
							( $arg instanceof XPath2Item && $arg->getTypeCode() == TypeCode::String )
						) )
						{
							return false;
						}
					}

					return true;
				};

				if ( ! $valid( $args ) )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
							SequenceType::GetXmlTypeCodeFromObject( $args[0] ),
							SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Int, XmlTypeCardinality::ZeroOrMore )
					) );
				}
				return ExtFuncs::CodepointEqual( CoreFuncs::CastString( $context, $args[0] ), CoreFuncs::CastString( $context, $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "empty", 1, XPath2ResultType::Boolean,
			function( $context, $provider, $args ) {
				return ExtFuncs::EmptySequence( XPath2NodeIterator::Create( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "exists", 1, XPath2ResultType::Boolean,
			function( $context, $provider, $args ) {
				return ExtFuncs::ExistsSequence( XPath2NodeIterator::Create( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "reverse", 1, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {
				return ExtFuncs::ReverseSequence( XPath2NodeIterator::Create( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "index-of", 3, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {
				return ExtFuncs::IndexOfSequenceWithCollation( $context, XPath2NodeIterator::Create( $args[0] ),
					CoreFuncs::Atomize( $args[1] ),
					CoreFuncs::CastArg( $context, $args[2], SequenceType::WithTypeCode( XmlTypeCode::String ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "index-of", 2, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {
				return ExtFuncs::IndexOfSequence( XPath2NodeIterator::Create( $args[0] ), CoreFuncs::Atomize( $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "remove", 2, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {
				return ExtFuncs::Remove( XPath2NodeIterator::Create( $args[0] ), ( int )CoreFuncs::CastArg( $context, $args[1], SequenceTypes::$Int ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "insert-before", 3, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {
				return ExtFuncs::InsertBefore( XPath2NodeIterator::Create( $args[0] ),
					CoreFuncs::CastArg( $context, $args[1], SequenceTypes::$Int ),
					XPath2NodeIterator::Create( $args[2] )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "subsequence", 3, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {

				$startingLoc = CoreFuncs::Number( $context, $args[1] );
				if ( is_nan( $startingLoc->getTypedValue()) )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
						SequenceType::GetXmlTypeCodeFromObject( $startingLoc ),
						"xs:integer"
					) );
				}

				$length = CoreFuncs::Number( $context, $args[2] );
				if ( is_nan( $length->getTypedValue() ) )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
						SequenceType::GetXmlTypeCodeFromObject( $length ),
						"xs:integer"
					) );
				}

				return ExtFuncs::SubsequenceWithLength( XPath2NodeIterator::Create( $args[0] ),
					$startingLoc,
					$length
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "subsequence", 2, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {

				$startingLoc = CoreFuncs::Number( $context, $args[1] );
				if ( is_nan( $startingLoc->GetTypeCode() ) )
				{
					throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array(
							SequenceType::GetXmlTypeCodeFromObject( $startingLoc ),
							"xs:integer"
					) );
				}

				return ExtFuncs::Subsequence( XPath2NodeIterator::Create( $args[0] ), $startingLoc );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "unordered", 1, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {
				return ExtFuncs::Unordered( XPath2NodeIterator::Create( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "zero-or-one", 1, XPath2ResultType::Any,
			function( $context, $provider, $args ) {
				return ExtFuncs::ZeroOrOne( XPath2NodeIterator::Create( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "one-or-more", 1, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {
				return ExtFuncs::OneOrMore( XPath2NodeIterator::Create( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "exactly-one", 1, XPath2ResultType::Any,
			function( $context, $provider, $args ) {
				return ExtFuncs::ExactlyOne( XPath2NodeIterator::Create( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "distinct-values", 2, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {
				return ExtFuncs::DistinctValuesWithCollation( $context, XPath2NodeIterator::Create( $args[0] ),
					CoreFuncs::CastArg( $context, $args[1], SequenceType::WithTypeCode( XmlTypeCode::String ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "distinct-values", 1, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {
				return ExtFuncs::DistinctValues( XPath2NodeIterator::Create( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "deep-equal", 3, XPath2ResultType::Boolean,
			function( $context, $provider, $args ) {
				return ExtFuncs::DeepEqualWithCollation( $context,
					XPath2NodeIterator::Create( $args[0] ), XPath2NodeIterator::Create( $args[1] ),
					CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[2] ),
					SequenceType::WithTypeCode( XmlTypeCode::String ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "deep-equal", 2, XPath2ResultType::Boolean,
			function( $context, $provider, $args ) {
				return ExtFuncs::DeepEqual( $context, XPath2NodeIterator::Create( $args[0] ), XPath2NodeIterator::Create( $args[1] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "count", 1, XPath2ResultType::Number,
			function( $context, $provider, $args ) {
				return ExtFuncs::CountValues( XPath2NodeIterator::Create( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "max", 1, XPath2ResultType::Any,
			function( $context, $provider, $args ) {
				return ExtFuncs::MaxValue( $context, XPath2NodeIterator::Create( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "max", 2, XPath2ResultType::Any,
			function( $context, $provider, $args ) {
				return ExtFuncs::MaxValueWithCollation( $context,
					XPath2NodeIterator::Create( $args[0] ),
					CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[1] ),
					SequenceType::WithTypeCode( XmlTypeCode::String ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "min", 1, XPath2ResultType::Any,
			function( $context, $provider, $args ) {
				return ExtFuncs::MinValue( $context, XPath2NodeIterator::Create( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "min", 2, XPath2ResultType::Any,
			function( $context, $provider, $args ) {
				return ExtFuncs::MinValueWithCollation( $context,
					XPath2NodeIterator::Create( $args[0] ),
					CoreFuncs::CastArg( $context, CoreFuncs::Atomize( $args[1] ),
					SequenceType::WithTypeCode( XmlTypeCode::String ) )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "sum", 2, XPath2ResultType::Any,
			function( $context, $provider, $args ) {
				return ExtFuncs::SumValueWithZero( $context,
					XPath2NodeIterator::Create( $args[0] ),
					CoreFuncs::Atomize( $args[1] )
				);
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "sum", 1, XPath2ResultType::Any,
			function( $context, $provider, $args ) {
				return ExtFuncs::SumValue( $context, XPath2NodeIterator::Create( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "avg", 1, XPath2ResultType::Any,
			function( $context, $provider, $args ) {
				return ExtFuncs::AvgValue( $context, XPath2NodeIterator::Create( $args[0] ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "position", 0, XPath2ResultType::Number,
			function( $context, $provider, $args ) {
				return ExtFuncs::CurrentPosition( $provider );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "last", 0, XPath2ResultType::Number,
			function( $context, $provider, $args ) {
				return ExtFuncs::LastPosition( $provider );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "root", 1, XPath2ResultType::Navigator,
			function( $context, $provider, $args ) {
				return CoreFuncs::GetRoot( CoreFuncs::NodeValue( $args[0], false ) );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "boolean", 1, XPath2ResultType::Boolean,
			function( $context, $provider, $args ) {
				return CoreFuncs::BooleanValue( $args[0] );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "true", 0, XPath2ResultType::Boolean,
			function( $context, $provider, $args ) { return CoreFuncs::$True; }
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "false", 0, XPath2ResultType::Boolean,
			function( $context, $provider, $args ) {
				return CoreFuncs::$False;
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "not", 1, XPath2ResultType::Boolean,
			function( $context, $provider, $args ) {
				return CoreFuncs::Not( $args[0] );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "string", 1, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return CoreFuncs::StringValue( $context, $args[0] );
			}
		);

		$this->AddWithArity( XmlReservedNs::xQueryFunc, "string", 0, XPath2ResultType::String,
			function( $context, $provider, $args ) {
				return CoreFuncs::StringValue( $context, $provider );
			}
		);
		$this->AddWithArity( XmlReservedNs::xQueryFunc, "number", 0, XPath2ResultType::Number,
			function( $context, $provider, $args ) {
				return CoreFuncs::NumberWithProvider( $context, $provider );
			}
		);

		$this->AddWithArity( XmlReservedNs::xQueryFunc, "number", 1, XPath2ResultType::Number,
			function( $context, $provider, $args ) {
				return CoreFuncs::Number( $context, $args[0] );
			}
		);

		$this->AddWithArity( XmlReservedNs::xQueryFunc, "id", 1, XPath2ResultType::NodeSet,
			function( $context, $provider, $args )
			{
				$node = CoreFuncs::ContextNode( $provider );
				return ExtFuncs::GetID($context, $provider, $args[0], $node, false );
			}
		);

		$this->AddWithArity( XmlReservedNs::xQueryFunc, "id", 2, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {
				$node = CoreFuncs::NodeValue( $args[1] );
				return ExtFuncs::GetID($context, $provider, $args[0], $node, false );
			}
		);

		$this->AddWithArity( XmlReservedNs::xQueryFunc, "idref", 1, XPath2ResultType::NodeSet,
			function( $context, $provider, $args )
			{
				$node = CoreFuncs::ContextNode( $provider );
				return ExtFuncs::GetID($context, $provider, $args[0], $node, true );
			}
		);

		$this->AddWithArity( XmlReservedNs::xQueryFunc, "idref", 2, XPath2ResultType::NodeSet,
			function( $context, $provider, $args ) {
				$node = CoreFuncs::NodeValue( $args[1] );
				return ExtFuncs::GetID($context, $provider, $args[0], $node, true );
			}
		);

	}

	/**
	 * Bind
	 * @param string $name
	 * @param string $ns
	 * @param int $arity
	 * @return XPathFunctionDef
	 */
	public function Bind( $name, $ns, $arity )
	{
		/**
		 * @var XPathFunctionDef $res
		 */
		$res;
		// Also check if XPath 2010 function $namespace is used, and if yes, try older version.
		$namespaces = array_unique( array( $ns, XmlReservedNs::xPathFunc ) );
		foreach ( array( $ns /* , XmlReservedNs::xPathFunc */ ) as $ns )
		{
			if ( ! isset( $this->_funcTable[ $ns ][ $name ][ $arity ] ) )
			{
				if ( ! isset( $this->_funcTable[ $ns ][ $name ][ -1 ] ) ) continue;
				{
					$arity = -1;
				}
			}

			return $this->_funcTable[ $ns ][ $name ][ $arity ]['def'];
		}

		return null;
	}

	/**
	 * Add
	 * @param string $ns
	 * @param string $name
	 * @param XPath2ResultType $resultType
	 * @param XPathFunctionDelegate $action
	 * @return void
	 */
	public function Add( $ns, $name, $resultType, $action )
	{
		$this->AddWithArity( $ns, $name, -1, $resultType, $action );
	}

	/**
	 * Add
	 * @param string $ns
	 * @param string $name
	 * @param int $arity
	 * @param XPath2ResultType $resultType
	 * @param XPathFunctionDelegate $action
	 * @return void
	 */
	public function AddWithArity( $ns, $name, $arity, $resultType, $action )
	{
		if ( ! isset( $this->_funcTable[ $ns ] ) ) $this->_funcTable[ $ns ] = array( );
		if ( ! isset( $this->_funcTable[ $ns ][ $name ] ) ) $this->_funcTable[ $ns ][ $name ] = array( );
		if ( ! isset( $this->_funcTable[ $ns ][ $name ][ $arity ] ) ) $this->_funcTable[ $ns ][ $name ][ $arity ] = array( );

		$this->_funcTable[ $ns ][ $name ][ $arity ] = array( "desc" => new FunctionDesc( $name, $ns, $arity ), "def" => new XPathFunctionDef( $name, $action, $resultType ) );
	}

	/**
	 * @var FunctionTable $_inst = null
	 */
	private static $_inst = null;

	/**
	 * Get an instance of the current table
	 * @return FunctionTable
	 */
	public static function getInstance( )
	{
		if ( is_null( self::$_inst ) )
		{
			self::$_inst = new FunctionTable( );
		}

		return self::$_inst;
	}

	public static function tests( $instance )
	{
		$table = FunctionTable::getInstance();

		// $nav = null;
		$nav = new DOM\DOMXPathNavigator( $instance->getInstanceXml() );
		$provider = new NodeProvider( $nav );
		$nsManager = new XmlNamespaceManager( SchemaTypes::getInstance()->getProcessedSchemas() );
		$context = new XPath2Context( $nsManager );
		$dataPool = null;

		/**
		 * @var XPathFunctionDef $definition
		 */
		// $definition = $table->Bind( "current-dateTime", XmlReservedNs::xQueryFunc, 0 );
		// $definition->Invoke( $context, $provider, null);

		$funcNode = FuncNode::withoutArgs( $context, "current-dateTime", XmlReservedNs::xQueryFunc );
		$result = $funcNode->Execute( $provider, $dataPool );

	}

}

?>
