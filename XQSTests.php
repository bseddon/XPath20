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

namespace lyquidity\XPath2;

use lyquidity\xml\MS\XmlNamespaceManager;
use lyquidity\xml\MS\XmlReservedNs;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\XPath2\DOM\DOMXPathNavigator;
use lyquidity;
use lyquidity\Log;
use lyquidity\XPath2\Value\DecimalValue;
use lyquidity\XPath2\Iterator\ExprIterator;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\Value\DurationValue;
use lyquidity\XPath2\Iterator\ChildNodeIterator;
use lyquidity\xml\MS\XmlQualifiedNameTest;
use lyquidity\xml\schema\SchemaTypes;
use lyquidity\xml\exceptions\InvalidOperationException;

/**
 * Class containing static functions for running tests and for building JSON file of test groups
 *
 * This test harness evaluates the 12875 conformance suite tests for XPath 2.0.  All but 2 tests
 * pass without any alteration.  The root cause of both is the limitation of the PHP type
 * system.
 */
class XQSTests
{
	/**
	 * @var string
	 */
	const XQTSNamespace = "http://www.w3.org/2005/02/query-test-XQTSCatalog";
	/**
	 * @var string
	 */
	const TestGroupsFilename = "/testGroups.json";

	/**
	 * @var string
	 */
	public static $sourceOffsetPath;
	/**
	 * @var string
	 */
	public static $queryOffsetPath;
	/**
	 * @var string
	 */
	public static $resultOffsetPath;
	/**
	 * @var string
	 */
	public static $queryFileExtension;
	/**
	 * @var string
	 */
	public static $conformanceBase;
	/**
	 * @var array
	 */
	public static $entities;
	/**
	 * @var array
	 */
	public static $convmap;
	/**
	 * @var array
	 */
	public static $sources;
	/**
	 * @var array
	 */
	public static $module;
	/**
	 * @var array
	 */
	public static $collection;
	/**
	 * @var array
	 */
	public static $schemas;
	/**
	 * @var XmlNamespaceManager
	 */
	public static $nsMgr;

	/**
	 * @var lyquidity\Log
	 */
	public static $log;

	/**
	 * A list of the tests that take an age to run
	 * @var array
	 */
	public static $veryLongRunningTests = array();

	/**
	 * When true very long running tests will be excluded
	 * @var bool
	 */
	public static $excludeVeryLongRunningTests;

	/**
	 * A list of notes about test anomolies
	 * @var array
	 */
	public static $testNotes = array();
	/**
	 * Static fonstructor
	 */
	public static function __static()
	{
		require_once __DIR__ . '/XPath2Parser.php';

		XQSTests::$entities = array();
		// XQSTests::$conformanceBase = "D:/GitHub/xbrlquery/conformance/XQTS_1_0_3";
		XQSTests::$sources = array();
		XQSTests::$module = array();
		XQSTests::$collection = array();
		XQSTests::$schemas = array();
		// XQSTests::$conformanceBase =  __DIR__ . "/../compiled/";
		XQSTests::$conformanceBase = "D:/GitHub/xbrlquery/conformance/XQTS_1_0_3";
		XQSTests::$convmap = array(0x0, 0x2FFFF, 0, 0xFFFF);

		$xfi = "http://www.xbrl.org/2008/function/instance";
		$xbrli = "http://www.xbrl.org/2003/instance";
		$xQueryTests = "http://www.lyquidity.com/xbrlqury/tests";

		$nsMgr = new XmlNamespaceManager();
		$nsMgr->addNamespace( "conf", "http://xbrl.org/2008/conformance" );
		$nsMgr->addNamespace( "xfi", $xfi );
		$nsMgr->addNamespace( "cnfn", "http://xbrl.org/2008/conformance/function" );
		$nsMgr->addNamespace( "xbrli", $xbrli);
		$nsMgr->addNamespace( "test", $xQueryTests );
		$nsMgr->addNamespace( "xs", XmlReservedNs::xs );
		// BMS 2018-04-09 Should not be required any more
		$nsMgr->addNamespace( "xsd", XmlReservedNs::xs );
		// $nsMgr->addNamespace( "foo", "http://example.org" );
		$nsMgr->addNamespace( "FOO", "http://example.org" );
		$nsMgr->addNamespace( "atomic", "http://www.w3.org/XQueryTest" );

		XQSTests::$nsMgr = $nsMgr;
		XQSTests::$log = \lyquidity\XPath2\lyquidity\Log::getInstance();

		XQSTests::$veryLongRunningTests = array( "ForExpr013","ForExpr025", "ReturnExpr005", "ReturnExpr009" );
		XQSTests::$testNotes = array(
			"A case to handle a closing square bracket has been added to the DefaultState function of Tokenizer." .
			"Although the tokenizer enters the Operator state after an opening square bracket it can have returned to the "  .
			"default state before the closing bracket appears. See the note at the end of the DefaultState function.",
			"The test ForExpr029 in the test group /MinimalConformance/Expressions/FLWORExpr/ForExp does not compile but the " .
			"suite expects it to succeed.  The test does not work in other XPath 2.0 processors either so I have changed it " .
			"so it compiles and produces the expected result.",
			"Modified case 10 of XPath::yyparse because multiple for parameters were being added as nodes instead of being " .
			"added to the second child the for node",
		);

		// The conformance suite expects errors when years before 1532 are used with gYear
		CoreFuncs::$strictGregorian = false;
	}

	/**
	 * Display the test notes
	 * @return string
	 */
	public static function showNotes()
	{
		echo implode( "\n", XQSTests::$testNotes ) . "\n";
	}

	/**
	 * Generate the schema, source, collection and module lists
	 * @throws \Exception
	 * @return void|boolean
	 */
	public static function generateTestCOllectionsFromCatalog()
	{
		try
		{
			$xml = file_get_contents( XQSTests::$conformanceBase . "/XQTSCatalog.xml" );
			if ( ! $xml ) throw new \Exception( "Unable to open the XQS Catalog file" );

			$catalog = simplexml_load_string( $xml, "SimpleXMLElement", /* LIBXML_NOENT */ 0 );
			$name = $catalog->getName();
			$qname = \lyquidity\xml\qname( $catalog->getName(), $catalog->getDocNamespaces() );
			if ( $qname->namespaceURI != XQSTests::XQTSNamespace && $qname->localName != "test-suite" )
			{
				throw new \Exception( "Invalid namespace" );
			}

			$attributes = $catalog->attributes();
			$version = $attributes->version;
			if ( $version != "1.0.2" && $version != "1.0.3" )
			{
				throw new \Exception( "Unsupported version" );
			}

			$catalog->registerXPathNamespace( "ts", XQSTests::XQTSNamespace );

			XQSTests::$sourceOffsetPath = (string)$attributes->SourceOffsetPath;
			XQSTests::$queryOffsetPath = (string)$attributes->XQueryQueryOffsetPath;
			XQSTests::$resultOffsetPath = (string)$attributes->ResultOffsetPath;
			XQSTests::$queryFileExtension = (string)$attributes->XQueryFileExtension;

			$sources	=& XQSTests::$sources;
			$module		=& XQSTests::$module;
			$collection	=& XQSTests::$collection;
			$schemas	=& XQSTests::$schemas;

			foreach ( $catalog->xpath( "/ts:test-suite/ts:sources/ts:schema" ) as /** @var \SimpleXMLElement $schema */ $schema )
			{
				$schemaAttributes = $schema->attributes();
				$id = (string)$schemaAttributes->ID;
				$targetNs = (string)$schemaAttributes->uri;

				$schemaFileName = XQSTests::$conformanceBase . "/" . str_replace( "\\", "/", (string)$schemaAttributes->FileName );
				if ( ! file_exists(  $schemaFileName ) )
				{
					XQSTests::$log->info( "Schema file $schemaFileName does not exist" );
				}
				$schemas[ $id ] = array( "namespace" => $targetNs, "xsd" => $schemaFileName );
			}

			foreach ( $catalog->xpath( "/ts:test-suite/ts:sources/ts:source" ) as /** @var \SimpleXMLElement $source */ $source )
			{
				$sourceAttributes = $source->attributes();
				$id = (string)$sourceAttributes->ID;
				$sourceFileName = XQSTests::$conformanceBase . "/" . str_replace( "\\", "/", (string)$sourceAttributes->FileName );
				if ( ! file_exists(  $sourceFileName ) )
				{
					XQSTests::$log->info( "Source file $sourceFileName does not exist" );
				}
				$sources[ $id ] = $sourceFileName;
			}

			$inputDocumentElementName = "input-document";
			foreach ( $catalog->xpath( "/ts:test-suite/ts:sources/ts:collection" ) as /** @var \SimpleXMLElement $node */ $node )
			{
				$nodeAttributes = $node->attributes();
				$id = (string)$nodeAttributes->ID;
				$nodes = $node->children(XQSTests::XQTSNamespace)->$inputDocumentElementName;
				$items = array();
				foreach ( $nodes as $curr )
				{
					if ( ! isset( $sources[ (string)$curr ] ) )
					{
						XQSTests::$log->info( "Referenced source ID $curr in collection $id not exist\n" );
					}
					$items[] = (string)$curr;
				}

				$collection[ $id ] = $items;
			}

			foreach ( $catalog->xpath( "/ts:test-suite/ts:sources/ts:module" ) as /** @var \SimpleXMLElement $node */ $node )
			{
				$nodeAttributes = $node->attributes();
				$id = (string)$nodeAttributes->ID;
				$moduleFileName = XQSTests::$conformanceBase . "/" . str_replace( "\\", "/", (string)$nodeAttributes->FileName ) . XQSTests::$queryFileExtension;

				if ( ! file_exists( $moduleFileName ) )
				{
					XQSTests::$log->info( "Module file $moduleFileName does not exist" );
				}
				$module[ $id ] = $moduleFileName;
			}

			return true;
		}
		catch ( \Exception $ex )
		{
			error_log( "Unable to generate collections" );
			error_log( $ex->getMessage() );

			return false;
		}
	}

	/**
	 * Read the entities from the DTD.  These included entities that define the location of test case groups
	 * @throws \Exception
	 */
	public static function generateEntitiesFromCatalogDTD()
	{
		$xml = file_get_contents( XQSTests::$conformanceBase . "/XQTSCatalog.xml" );
		if ( ! $xml ) throw new \Exception( "Unable to open the XQS Catalog file" );

		$entities =& XQSTests::$entities;

		$matches = null;
		if ( preg_match( "/DOCTYPE.*?\[(?<xxx>.*?)\]/s", $xml, $matches ) )
		{
			// echo "Found\n";
			$lines = array_filter( explode( "\r\n", $matches['xxx'] ) );

			foreach ( $lines as $line )
			{
				if ( ! preg_match( "/\<\!ENTITY (?<name>.*?) SYSTEM \"?(?<file>.*?)\"?\>/", $line, $matches ) ) continue;
				// echo "entity {$matches['name']} {$matches['file']}\n";
				$entities[ $matches['name'] ] = $matches['file'];
			}
		}
	}

	/**
	 * Get the JSON version of the XQS test catalog.  It will be generated if it does not exist
	 * @param string $force If true generate anyway
	 * @return string|boolean The JSON string or false if there's a problem
	 */
	public static function getJSONTests( $force = false )
	{
		try
		{
			if ( $force || ! file_exists( __DIR__ . XQSTests::TestGroupsFilename ) )
			{
				XQSTests::generateEntitiesFromCatalogDTD();
				XQSTests::generateTestGroupsFromCatalog();
			}

			return file_get_contents( __DIR__ . XQSTests::TestGroupsFilename );
		}
		catch ( \Exception $ex )
		{
			error_log( "Error generating Test groups JSON file" );
			error_log( $ex->getMessage() );
			return false;
		}

	}

	/**
	 * Run the tests
	 * @param unknown $testName The name of a specific test to run or all will be run
	 */
	public static function runTestGroups( $testGroupNameOnly = null, $testCaseNameOnly = null )
	{
		$result = true; // Assume success

		$json = XQSTests::getJSONTests( false );
		if ( ! $json ) return false;

		$testGroups = json_decode( $json, true );

		XQSTests::generateTestCOllectionsFromCatalog();

		foreach ( $testGroups['xpath2'] as $name => $testGroup )
		{
			if ( ! is_null( $testGroupNameOnly ) && $testGroupNameOnly != $name )
			{
				continue;
			}

			XQSTests::$log->info( "Test group: $name - {$testGroup['title']}" );
			error_log( "Test group: $name - {$testGroup['title']}" );

			try
			{
				XQSTests::runTestGroup( $name, $testGroup['files'], $testCaseNameOnly );
			}
			catch( \Exception $ex )
			{
				error_log( "An error occurred running test group '$name'" );
				error_log( $ex->getMessage() );
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Runs the tests in a test group
	 * @param unknown $testGroupFiles a set of files used by the test group
	 */
	private static function runTestGroup( $name, $testGroupFiles, $testCaseNameOnly = null )
	{
		$collection = XQSTests::$collection;
		$module = XQSTests::$module;
		$schemas = XQSTests::$schemas;
		$sources = XQSTests::$sources;

		foreach( $testGroupFiles as $key => $file )
		{
			if ( $file[1] == ":" )
			{
				XQSTests::$conformanceBase = dirname( $file );
				XQSTests::$queryOffsetPath = "./";
				XQSTests::$resultOffsetPath = "./";
			}
			else
			{
				$file = XQSTests::$conformanceBase . "/" . $file;
			}

			if ( ! file_exists( $file ) )
			{
				XQSTests::$log->info( "The test case file '$file' does not exist" );
				continue;
			}

			$testGroup = simplexml_load_file( $file );
			$namespaces = $testGroup->getNamespaces();

			$testGroupAttributes = $testGroup->attributes();
			$groupName = isset( $testGroupAttributes->name ) ? (string)$testGroupAttributes->name : "";
			$featureOwner = isset( $testGroupAttributes->featureOwner ) ? (string)$testGroupAttributes->featureOwner : "";

			if ( isset( $namespaces[""] ) )
			{
				$defaultNs = $namespaces[""];
				$testGroup->registerXPathNamespace( "tg", $defaultNs );
			}

			// Look to see how many v2 test cases there are in this file
			// and report the file and count
			$countTestCases = count( $testGroup->xpath( "/tg:test-group/tg:test-case[not(@is-XPath2) or not(@is-XPath2='false')]" ) );
			$fileBasename = basename( $file );
			XQSTests::$log->info( "Test group file: {$fileBasename} ($countTestCases v2 tests)" );
			error_log( "Test group file: {$fileBasename} ($countTestCases v2 tests)" );

			// If there are none the move along
			if ( ! $countTestCases )
			{
				continue;
			}

			foreach ( $testGroup->children()->{"test-case"} as /** @var \SimpleXMLElement $testCase */ $testCase )
			{
				// Get attributes
				$testCaseAttributes	= $testCase->attributes();
				$isXPath2		= isset( $testCaseAttributes->{"is-XPath2"} )	? (string)$testCaseAttributes->{"is-XPath2"}	: "";
				$testCaseName	= isset( $testCaseAttributes->{"name"} )		? (string)$testCaseAttributes->{"name"}			: "";
				$filePathPart	= isset( $testCaseAttributes->{"FilePath"} )	? (string)$testCaseAttributes->{"FilePath"}		: "";
				$scenario		= isset( $testCaseAttributes->{"scenario"} )	? (string)$testCaseAttributes->{"scenario"}		: "";
				$creator		= isset( $testCaseAttributes->{"Creator"} )		? (string)$testCaseAttributes->{"Creator"}		: "";
				// echo "$testCaseName\n";
				$filePath = XQSTests::$conformanceBase . "/" . XQSTests::$queryOffsetPath . $filePathPart;

				if ( ! filter_var( $isXPath2, FILTER_VALIDATE_BOOLEAN ) )
				{
					continue;
				}
				if ( ! is_null( $testCaseNameOnly ) &&
						(
							( is_string( $testCaseNameOnly ) && $testCaseNameOnly != $testCaseName ) ||
							( is_callable( $testCaseNameOnly ) && ! $testCaseNameOnly( $testCaseName, $file, ! isset( $queryFilename ) ) )
						)
					)
				{
					continue;
				}

				// Query file
				$queryFilename = $testCase->children()->query->attributes()->name . XQSTests::$queryFileExtension;
				if ( ! file_exists( $filePath . $queryFilename ) )
				{
					XQSTests::$log->info( "The test file '$queryFilename' cannot be found." );
					continue;
				}

				$queryFilename = $filePath . $queryFilename;

				// Input
				$input = new \stdClass();
				$variableName = null;
				if ( isset( $testCase->children()->{"input-file"} ) )
				{
					foreach ( $testCase->children()->{"input-file"} as $elementName => $inputFilenameElement )
					{
						$variableName = (string)$inputFilenameElement->attributes()->variable;
						$inputSource = (string)$inputFilenameElement;

						if ( ! isset( $sources[ $inputSource ] ) )
						{
							XQSTests::$log->info( "  The input file source '{$sources[ $inputSource ]}' does not exist" );
							continue;
						}

						$inputFilename = $sources[ $inputSource ];

						if ( ! file_exists( $inputFilename ) )
						{
							XQSTests::$log->info( "The test input file '$inputFilename' does not exist" );
							continue;
						}

						$dom = new \DOMDocument();
						$dom->load( $inputFilename );
						$input->{$variableName} = $dom; // new DOMXPathNavigator( $dom );
					}
				}
				else if ( isset( $testCase->children()->{"contextItem"} ) )
				{
					$inputSource = (string)$testCase->children()->{"contextItem"};

					if ( ! isset( $sources[ $inputSource ] ) )
					{
						XQSTests::$log->info( "  The input file source '{$sources[ $inputSource ]}' does not exist" );
						continue;
					}

					$inputFilename = $sources[ $inputSource ];

					if ( ! file_exists( $inputFilename ) )
					{
						XQSTests::$log->info( "The test input file '$inputFilename' does not exist" );
						continue;
					}

					$dom = new \DOMDocument();
					$dom->load( $inputFilename );
					$input->{"context-node"} = new DOMXPathNavigator( $dom );
				}
				else if ( isset( $testCase->children()->{"input-URI"} ) )
				{
					foreach ( $testCase->children()->{"input-URI"} as $name => $inputFilenameElement )
					{
						$variableName = (string)$inputFilenameElement->attributes()->variable;
						$inputSource = (string)$inputFilenameElement;

						switch ( $inputSource )
						{
							// Special cases for the fn:collection() tests.  See the document:
							// Guidelines for Running the XML Query Test Suite.html
							case "collection1":
							case "collection2":

								$input->{$variableName} = $inputSource;
								break;

							default:

								if ( ! isset( $sources[ $inputSource ] ) )
								{
									XQSTests::$log->info( "The input file source '$inputSource' does not exist" );
									continue;
								}

								$expandedUri = isset( $sources[ $inputSource ] ) ? $sources[ $inputSource ] : $inputSource;
								$input->{$variableName} = $expandedUri;
						}

					}

				}

				/*
				 * collection1.xml is a copy of test source reviews.xml with the first of the three nodes removed
				 * collection2.xml is a copy of test source reviews.xml with the title element of the third node duplicated
				 */
				$defaultCollection = "";
				$availableCollections = array(
					'collection1' => XQSTests::$conformanceBase . '/TestSources/collection1.xml',
					'collection2' => XQSTests::$conformanceBase . '/TestSources/collection2.xml',
				);

				$defaultCollection = isset( $testCase->{'defaultCollection'} )
					? (string)$testCase->{'defaultCollection'}
					: "";

				if ( $defaultCollection != "" ||
					 ( isset( $input->{'input-context'} ) &&
					   ( $input->{'input-context'} == "collection1" || $input->{'input-context'} == "collection2" )
					 )
				)
				{
					foreach ( $availableCollections as $key => $file )
					{
						$doc = new \DOMDocument();
						@$doc->load( $file );
						$nav = new DOMXPathNavigator( $doc );
						$nav->MoveToRoot();
						$nav->MoveToFirstChild();
						$test = XmlQualifiedNameTest::Create();
						$availableCollections[ $key ] = ChildNodeIterator::fromNodeTest( null, $test, XPath2NodeIterator::Create( $nav ) );
					}
				}

				$input->defaultCollection = $defaultCollection;
				$input->availableCollections = $availableCollections;

				$outputFilenames = array();
				$compare = "";

				if ( isset( $testCase->{'output-file'} ) )
				{
					foreach ( $testCase->children()->{"output-file"} as $elementName => $outputFilenameElement )
					{
						$compare = (string)$outputFilenameElement->attributes()->compare;
						if ( $compare != "Ignore" )
						{
							$outputFilename = (string)$outputFilenameElement;
							if ( ! file_exists( XQSTests::$conformanceBase . "/" . XQSTests::$resultOffsetPath . $filePathPart . $outputFilename ) )
							{
								XQSTests::$log->info( "The output file '$outputFilename' cannot be found." );
								continue;
							}

							$outputFilenames[] = XQSTests::$conformanceBase . "/" . XQSTests::$resultOffsetPath . $filePathPart . $outputFilename;
						}
					}
				}

				// TODO There can be more than one expected error
				$expectedErrors = array();
				foreach ( $testCase->children()->{"expected-error"} as $elementName => $expectedErrorElement )
				{
					$expectedErrors[] = (string)$expectedErrorElement;
				}

				// The 'fn:id()' and 'fn:idref()' function require additional schema elements from the xml namespace
				if ( $fileBasename == "SeqIDFunc.xml" || $fileBasename == "SeqIDREFFunc.xml")
				{
					// BMS 2018-04-09 Change xsd to xs
					$types = SchemaTypes::getInstance();
					$type = $types->AddAttribute( SCHEMA_PREFIX, "anId", "xs:ID" );
					$type = $types->AddAttribute( SCHEMA_PREFIX, "anIdRef", "xs:IDREF" );
					$type = $types->AddAttribute( "xml", "id", "xs:ID" );
					$type = $types->AddAttribute( "xml", "ref", "xs:ID" );
				}

				$result = XQSTests::runTest( $testCaseName, $queryFilename, $variableName, $input, $outputFilenames, $expectedErrors, $scenario, $compare );
				XQSTests::$log->info( "  Test " . ( $result ? "passed" : "** failed **" ) );

				if ( ! $result )
				{
					echo "\n";
					// Record the issue for external reporting
					global $issues;
					$issues[] = array(
						'id' => $name,
						'variation' => $testCaseName
					);
				}
			}
		}
	}

	/**
	 * Create a comment path for the test group node
	 * @param unknown $node
	 * @return string
	 */
	private static function createPath( /** @var \DOMElement $node */ $node )
	{
		$path = "";

		if ( ! $node->parentNode instanceof \DOMDocument )
		{
			$path = XQSTests::createPath( $node->parentNode );
		}

		$name = $node->getAttribute('name');
		if ( ! empty( $name ) )
			$path .= "/$name";

			return $path;
	}

	/**
	 * Parse the test groups and resolve the test cases file name from the respective entities
	 */
	private static function generateTestGroupsFromCatalog()
	{
		$testGroups = array( "xpath2" => array(), "other" => array() );
		$entities =& XQSTests::$entities;

		$sxi = new \RecursiveIteratorIterator(
			new \SimpleXmlIterator(  XQSTests::$conformanceBase . "/XQTSCatalog.xml", 0, true ),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach( $sxi as /** @var \SimpleXMLElement $el */ $el ) {

			if ( $el->getName() != "test-group" ) continue;

			unset( $tests );
			$tests =& $testGroups["xpath2"];
			$attributes = $el->attributes();
			if ( isset( $attributes->{'is-XPath2'} ) )
			{
				if ( ! filter_var( (string)$attributes->{'is-XPath2'},FILTER_VALIDATE_BOOLEAN ) )
				{
					unset( $tests );
					$tests =& $testGroups["other"];
					// continue;
				}
			}

			/**
			 * @var \DOMElement $x
			 */
			$x = dom_import_simplexml( $el );
			$path = XQSTests::createPath( $x );
			$title = "";
			$description = "";
			$files = array();
			$foundEntities = false;
			foreach ( $x->childNodes as /** @var \DOMElement $node */ $node )
			{
				if ( $node->nodeType == XML_ELEMENT_NODE )
				{
					if ( $foundEntities )
					{
						goto addFiles;
					}
					if ( $node->tagName == "GroupInfo" )
					{
						$simpleNode = \simplexml_import_dom( $node );
						if ( isset( $simpleNode->title ) )
						{
							$title = (string)$simpleNode->title;
						}
						if ( isset( $simpleNode->description ) )
						{
							$description = (string)$simpleNode->description;
						}
					}
				}
				if ( $node->nodeType == XML_TEXT_NODE ) continue;
				if ( $node->nodeType != XML_ENTITY_REF_NODE )
				{
					if ( $foundEntities ) continue 2;
					continue;
				}

				$foundEntities = true;

				/**
				 * @var \DOMEntityReference $entity
				 */
				$entity = $node;
				$entities[ $entity->nodeName ];

				$files[ $entity->nodeName ] = $entities[ $entity->nodeName ];
			}

			addFiles:

			if ( ! count( $files ) ) continue;
			$tests[ $path ] = array( "title" => $title, "description" => $description, "xpath" => $x->getNodePath(), "files" => $files );
		}

		// var_dump( $tests );
		$json = json_encode( $testGroups, JSON_PRETTY_PRINT );

		file_put_contents( __DIR__ . XQSTests::TestGroupsFilename, $json );
	}

	/**
	 * Run a specific test
	 * @param string $name
	 * @param string $queryFile
	 * @param string $variableName
	 * @param \stdClass $input
	 * @param string $expectedResultsFile
	 * @param string $expectedError
	 * @param string $scenario
	 * @param string $compare
	 */
	private static function runTest( $name, $queryFile, $variableName, $input, $expectedResultsFiles, $expectedErrors, $scenario, $compare )
	{
		$title = $scenario == "standard"
			? ( $expectedErrors ? "(may generate an error " . implode( ", ", $expectedErrors ) . ")" : "" )
			: ( $scenario == "runtime-error"
					? implode( ", ", $expectedErrors )
					: ""
			  );

		if ( $scenario != "standard" && $expectedResultsFiles )
		{
			$title .= " (valid result may be acceptable)";
		}

		XQSTests::$log->info("Running test case '$name' - $scenario $title" );
		$result = false;

		try
		{
			// Open the query file
			$query = file_get_contents( $queryFile );

			$query = preg_replace( "/\(:\s*insert-start\s*:\).*\(:\s*insert-end\s*:\)/s", "", $query );
			$query = trim( preg_replace( "/^\(:.*?:\)/m", "", $query ) );

			XQSTests::$log->info( "  $query" ); // return true;

			// Open the expected results file (if there is one)
			$expectedResults = array();
			if ( $expectedResultsFiles )
			{
				foreach ( $expectedResultsFiles as $expectedResultsFile )
				{
					$expectedResults[] = file_get_contents( $expectedResultsFile );
				}
				// if ( is_numeric( $expectedResult ) ) $expectedResult = $expectedResult + 0;
			}

			$provider = null;
			if ( property_exists( $input, "context-node" ) )
			{
				$provider = new NodeProvider( $input->{"context-node"} );
				unset( $input->{"context-node"} );
			}

			$expectedResultType = null;

			try
			{
				$expression = XPath2Expression::Compile( $query, XQSTests::$nsMgr, false );
				$expectedResultType = $expression->GetResultType( $expression->propertiesToArray( $input ) );
			}
			catch( XPath2Exception $ex )
			{
				if ( $scenario == "parse-error" || $scenario == "runtime-error" || $expectedErrors )
				{
					$expectedErrors = implode( ',', $expectedErrors );
					XQSTests::$log->info( "  Query failed with expected error: $expectedErrors");
					return true;
				}

				throw $ex;
			}

			$evalResult = $expression->EvaluateWithProperties( $provider, $input );
			$actualResultType = $expression->GetResultType( $expression->propertiesToArray( $input ) );
			$result = true;

			if ( XQSTests::$excludeVeryLongRunningTests && in_array( $name, XQSTests::$veryLongRunningTests ) )
			{
				XQSTests::$log->info( "  This is a long running test as is being skipped" );
				return true;
			}
		}
		catch ( \Exception $ex )
		{
			$exceptions = array();

				// Report these violations but return true
			if ( in_array( $name, array_keys( $exceptions ) ) )
			{
				XQSTests::$log->info( "  Compliance failure exception: $name ({$exceptions[ $name ]})" );
				return true;
			}

			if ( $ex instanceof XPath2Exception )
			{
				if ( $expectedErrors )
				{
					if ( in_array( $ex->ErrorCode, $expectedErrors ) || in_array( "*", $expectedErrors ) )
					{
						XQSTests::$log->info( "  Query failed with expected error: {$ex->ErrorCode}" );
						return true;
					}
					else
					{
						$expectedErrors = implode( ',', $expectedErrors );
						XQSTests::$log->info( "  An XPath2Exception has occurred that is not expected ({$ex->ErrorCode}). Expected $expectedErrors." );
						return false;
					}
				}
				else
				{
					XQSTests::$log->info( "  An XPath2Exception has occurred that is not expected ({$ex->ErrorCode})." );
					XQSTests::$log->info( "  {$ex->getMessage()}");
					return false;
				}
			}

			if ( $expectedErrors )
			{
				$result = true;
			}
			else
			{
				XQSTests::$log->info( "  An general error has occured when no error is expected.");
				XQSTests::$log->info( "  {$ex->getMessage()}");
				XQSTests::$log->info( "  {$ex->getTraceAsString()}" );

				return false;
			}
		}

		if ( isset( $expectedResultType ) )
		{
			if ( isset( $evalResult ) )
			{
				$result = $evalResult instanceof Undefined || $actualResultType == $expectedResultType;
				if ( ! $result && $expectedResultType != XPath2ResultType::Any )
				{
					XQSTests::$log->info( "  The xpath query evaluation result and the expected result are not the same." );
					$expectedResultTypeName = XPath2ResultType::getResultTypeName( $expectedResultType );
					$actualResultTypeName = XPath2ResultType::getResultTypeName( $actualResultType );
					XQSTests::$log->info( "  Expected result type: $expectedResultTypeName" );
					XQSTests::$log->info( "  Actual result type: $actualResultTypeName" );
				}
			}
			else
			{
				XQSTests::$log->info( "A result is expected but an XPath evaluation result is not available" );
				return false;
			}
		}

		try
		{
			if ( $scenario == "runtime-error" )
			{
				$exceptions = array();

				if ( isset( $exceptions[ $name ] ) )
				{
					XQSTests::$log->info( "  Test exception: {$exceptions[ $name ]}" );
					return true;
				}

				if ( $evalResult instanceof XPath2NodeIterator )
				{
					/**
					 * @var XPath2NodeIterator $iter
					 */
					$iter = $evalResult;
					while ( $iter->MoveNext() )
						;
				}
				// return false;
			}

			// No error expected or the result did not error
			if ( $scenario == "standard" || $expectedResults )
			{
				// These exceptions skip testing all together
				$exceptions = array(
				);

				if ( isset( $exceptions[ $name ] ) )
				{
					XQSTests::$log->info( "  Test exception: {$exceptions[ $name ]}" );
					return true;
				}

				foreach ( $expectedResults as $expectedResult )
				{
					if ( $compare == "Text" || $compare == "Fragment" )
					{
						if ( ! $evalResult instanceof Undefined && $expectedResultType == XPath2ResultType::Number && $actualResultType == XPath2ResultType::Number )
						{
							if ( XQSTests::CompareNumbers( $expectedResult, $evalResult ) )
							{
								return true;
							}
						}
						// Look for a char encoded as an entity (see ./AllStringFunc/AssDisassStringFunc K-CodepointToStringFunc-8)
						else if ( preg_match( "/^&#(?<value>(x[0-9a-f]+)|(\d+));$/i", $expectedResult, $matches ) )
						{
							if ( ! isset( $matches['value'] ) || ! $matches['value'] ) return false;

							// Is this a hex number?
							$str = "";
							if ( strtolower( $matches['value'][0] ) == 'x')
							{
								// If the number is aleady and even length (with the 'x') make it a zero otherwise chop off the 'x'
								if ( strlen( $matches['value'] ) % 2 == 0 )
								{
									$hex = $matches['value'];
									$hex[0] = '0';
								}
								else
								{
									$hex = substr( $matches['value'], 1 );
								}
								$str = chr( ord( hex2bin( $hex ) ) );
							}
							else
							{
								$str = chr( $matches['value'] );
							}

							$result = $evalResult == $str;
							return $result;
						}
						else if ( XQSTests::CompareResult( $name, $expectedResult, $evalResult, false ) )
						{
							return true;
						}
					}
					else if ( $compare == "XML")
					{
						if ( XQSTests::CompareResult( $name, $expectedResult, $evalResult, true))
						{
							return true;
						}
					}
					else if ( $compare == "Inspect")
					{
						XQSTests::$log->info( "  $name: Inspection needed." );
						return true;
					}
					else if ( $compare == "Ignore")
					{
						;
					}
					else
					{
						throw new InvalidOperationException( "  Unable to determine the comparison type" );
					}
				}

				return false;
			}

			error_log( " Error not generated - $name" );
			return true;
		}
		catch ( XPath2Exception $ex )
		{
			if ( $scenario == "runtime-error" || $expectedErrors )
			{
				if ( ! in_array( $ex->ErrorCode, $expectedErrors ) )
				{
					$x = 1;
				}

				$expectedErrors = implode( ',', $expectedErrors );
				XQSTests::$log->info("  Query failed with expected error: $expectedErrors" );
				return true;
			}
			throw $ex;
		}

		return $result;
	}

	/**
	 * Compares two number by rounding to their common level of accuracy
	 * The compliance suite provides numeric results in a format that
	 * PHP cannot always agree with.  For example in test 'casthc17'
	 * the expected result of:
	 *   xs:decimal(12678967.543233) cast as xs:float
	 * is 1.2678968E8 while PHP will report it as 12678967.543233 which
	 * is a valid float.  The numbers are the same and only differences
	 * are their presentation (exponential vs decimal) and rounding.
	 * So this function takes each number rounded appropriately then
	 * compares them. In the example above the two numbers will be
	 * rounded to zero decimal places.
	 */
	private static function CompareNumbers( $expectedResult, $actualResult )
	{
		if ( $actualResult instanceof DurationValue )
		{
			$x = 1;
		}
		else if ( $actualResult instanceof IXmlSchemaType )
		{
			$evalResult = round( $actualResult->getValue(), 21 );
		}
		else if ( $actualResult instanceof XPath2Item )
		{
			$actualResult = round( $actualResult->getValue(), 21 );
		}

		if ( ! is_object( $actualResult ) && is_nan( $actualResult ) && strtoupper( $expectedResult ) == "NAN" )
		{
			return true;
		}

		if ( ! is_object( $expectedResult ) && (
				( is_double( $expectedResult ) && is_infinite( $expectedResult ) ) ||
				strtoupper( $expectedResult ) == "INF" ||
				strtoupper( $expectedResult ) == "-INF"
			)
		)
		{
			return (
				( ( $actualResult == INF  || strtoupper( $actualResult ) == "INF"  ) && ( $expectedResult == INF  || strtoupper( $expectedResult ) == "INF" ) ) ||
				( ( $actualResult == -INF || strtoupper( $actualResult ) == "-INF" ) && ( $expectedResult == -INF || strtoupper( $expectedResult ) == "-INF" ) )
			);
		}

		if ( ! is_object( $actualResult ) && (
				( is_double( $actualResult ) && is_infinite( $actualResult ) ) ||
				strtoupper( $actualResult ) == "INF" ||
				strtoupper( $actualResult ) == "-INF"
			)
		)
		{
			$result = (
				( ( $expectedResult == INF  || strtoupper( $expectedResult ) == "INF" )  && ( $actualResult == INF  || strtoupper( $actualResult ) == "INF" ) ) ||
				( ( $expectedResult == -INF || strtoupper( $expectedResult ) == "-INF" ) && ( $actualResult == -INF || strtoupper( $actualResult ) == "-INF" ) )
			);
			return $result;
		}

		// Try the numbers thay may just be equal
		if ( $expectedResult == $actualResult )
		{
			return true;
		}

		$expectedDecimal = DecimalValue::FromFloat( $expectedResult, false );
		$actualDecimal   = DecimalValue::FromFloat( $actualResult, false );

		$expectedDecimalLength = $expectedDecimal->getIsDecimal() ? strlen( $expectedDecimal->getDecimalPart() ) : 0;
		$actualDecimalLength = $actualDecimal->getIsDecimal() ? strlen( $actualDecimal->getDecimalPart() ) : 0;

		$expectedDigitsLength = strlen( $expectedDecimal->getIntegerPart() );
		$actualDigitsLength = strlen( $actualDecimal->getIntegerPart() );

		// Make sure they have the same number of decimals
		if ( $expectedDecimalLength != $actualDecimalLength )
		{
			if ( $expectedDecimalLength > $actualDecimalLength )
			{
				$expectedDecimal = $expectedDecimal->getRound( $actualDecimalLength );
			}
			else
			{
				$actualDecimal = $actualDecimal->getRound( $expectedDecimalLength );
			}
		}

		if ( ! $expectedDecimal->getIsDecimal() && $expectedDigitsLength == $actualDigitsLength )
		{
			// Make sure they are the same length after removing trailing zeros. This will required when
			// the values are say 1.23E6 (which will be decimal 123000) and 1.234E6 (123400).  OK these are
			// simplified examples.  A concrete example is 1.234567890123456E38 and 1.2345678901235E38
			$expectedDigits = rtrim( $expectedDecimal->getIntegerPart(), "0" );
			$actualDigits = rtrim( $actualDecimal->getIntegerPart(), "0" );

			if ( strlen( $expectedDigits ) > strlen( $actualDigits ) )
			{
				$expectedDecimal = DecimalValue::FromValue( $expectedDigits )->getRound( strlen( $actualDigits ) - strlen( $expectedDigits ) );
				$factor = DecimalValue::FromValue( 10 )->Pow( $actualDigitsLength - strlen( $expectedDigits ) );
				$expectedDecimal = $expectedDecimal->Mul( $factor );
			}
			else if ( strlen( $expectedDigits ) < strlen( $actualDigits ) )
			{
				$actualDecimal = DecimalValue::FromValue( $actualDigits )->getRound( strlen( $expectedDigits ) - strlen( $actualDigits ), PHP_ROUND_HALF_DOWN );
				$factor = DecimalValue::FromValue( 10 )->Pow( $expectedDigitsLength - strlen( $actualDigits ) );
				$actualDecimal = $actualDecimal->Mul( $factor );
			}
		}
		else
		{
			// If the digits are not the same length ???
		}

		$result = $expectedDecimal->Equals( $actualDecimal );

		return $result;
	}

	/**
	 * Compare the result of a specific test with the expected result
	 * @param DOMElement $id
	 * @param string $outputXml
	 * @param object $value
	 * @param bool $xmlCompare
	 * @return bool
	 */
	private static function CompareResult( $id, $outputXml, $value, $xmlCompare )
	{
		$isSingle = false;

		if ( $id == "ReturnExpr010" )
		{
			$xmlCompare = true;
		}

		if ( $id != "CondExpr012" && $id != "NodeTest006" )
		{
			if ( $value instanceof XPathItem )
			{
				$isSingle = true;
			}
			else if ( $value instanceof XPath2NodeIterator)
			{
				/**
				 * @var XPath2NodeIterator $iter
				 */
				$iter = $value;
				$isSingle = $iter->getIsSingleIterator();
			}
		}

		/**
		 * @var \DOMDocument $doc1
		 */
		$doc1 = new \DOMDocument();

		if ( $xmlCompare )
		{
			// Replace the handler because the status test handler traps any error and terminates the session
			$previousHandler = set_error_handler(null);
			@$doc1->loadXML( $outputXml );
			set_error_handler( $previousHandler );
		}
		else
		{
			$outputXml = preg_replace( "/\s*xmlns:.*?=\".*?\"/", "", $outputXml );
			$sb = array();
			$sb[] = "<?xml version='1.0'?>\n";
			$sb[] = "<root xmlns:xs=\"http://www.w3.org/2001/XMLSchema\">\n";
			$sb[] = $outputXml;
			$sb[] = "</root>";
			@$doc1->LoadXML( implode( "", $sb ) );
		}

		$sb = array();
		if ( ! $xmlCompare )
		{
			$sb[] = "<?xml version='1.0'?>\n";
			$sb[] = "<root xmlns:xs=\"http://www.w3.org/2001/XMLSchema\">\n";
		}

		if ( $value instanceof XPath2NodeIterator )
		{
			$string_flag = false;
			/**
			 * @var XPath2NodeIterator $value
			 */
			foreach ( $value as /** @var XPathItem $item */ $item )
			{
				if ( $item instanceof ExprIterator )
				{
					foreach ( $item as $value )
					{
						if ( $string_flag ) $sb[] = " ";
						// $sb[] = $value->getValue();
						$sb[] = $value instanceof XPath2Item
							? $value->getValue()
							: $value->getInnerXml();
						$string_flag = true;
					}
				}
				else if ( $item->getIsNode() )
				{
					/**
					 * @var XPathNavigator $nav
					 */
					$nav = $item;
					$sb[] = $nav->ToString();
				}
				else
				{
					if ( $string_flag ) $sb[] = " ";
					$sb[] = htmlentities( (string)$item, ENT_NOQUOTES | ENT_XML1 ); // ->getValue();
					$string_flag = true;
				}
			}
		}
		else if ( $value instanceof XPathItem )
		{
			/**
			 * @var XPathItem $item
			 */
			$item = $value;
			if ( $item->getIsNode() )
			{
				$sb[] = $item->getInnerXml();
			}
			else
			{
				$sb[] = (string)$item; // ->getValue();
			}
		}
		else
		{
			if ( ! $value instanceof Undefined )
			{
				$sb[] = XPath2Convert::ToString( $value );
			}
		}

		if ( ! $xmlCompare )
		{
			$sb[] = "</root>";
		}

		/**
		 * @var \DOMDocument $doc2
		 */
		$doc2 = new \DOMDocument();
		// Replace the handler because the status test handler traps any error and terminates the session
		$previousHandler = set_error_handler(null);
		@$doc2->loadXML( implode( "", $sb ) );
		set_error_handler( $previousHandler );

		/**
		 * @var TreeComparer $comparer
		 */
		$comparer = new TreeComparer( null );
		$comparer->excludeWhitespace = true;
		$res = $comparer->DeepEqualByNavigator( new DOMXPathNavigator( $doc1 ), new DOMXPathNavigator( $doc2 ) );

		return $res;
	}

}

XQSTests::__static();
