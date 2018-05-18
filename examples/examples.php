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

// Only load the bootstrap files directly if they have not been loaded by Composer already
$files = get_included_files();
$bootstrap = realpath( __DIR__ . '/../../xml/bootstrap.php' );
if ( ! in_array( $bootstrap, $files ) )
{
	require_once $bootstrap;
}
$bootstrap = realpath( __DIR__ . '/../bootstrap.php' );
if ( ! in_array( $bootstrap, $files ) )
{
	require_once $bootstrap;
}

use lyquidity\xml\MS\XmlNamespaceManager;
use lyquidity\XPath2\XPath2Expression;
use lyquidity\xml\schema\SchemaTypes;
use lyquidity\XPath2\DOM\DOMXPathNavigator;
use lyquidity\xml\xpath\XPathNodeType;
use lyquidity\XPath2\NodeProvider;
use lyquidity\XPath2\XPath2Exception;
use lyquidity\Log;
use lyquidity\XPath2\FunctionTable;
use lyquidity\XPath2\Iterator\DocumentOrderNodeIterator;
use lyquidity\XPath2\XPath2ResultType;
use lyquidity\xml\xpath\XPathNavigator;

// Setup the logger
$log = \lyquidity\XPath2\lyquidity\Log::getInstance();
$log->setConsoleLog();

// Add this logger to the SchemaTypes instance
SchemaTypes::getInstance()->log = $log;

staticExamples();
documentExamples( "/xbrli:xbrl/xbrli:context" );
customFunction( "/xbrli:xbrl/xbrli:context" );

/**
 * A collection of expression evaluation examples that do not involve an XML document
 */
function staticExamples()
{
	try
	{
		// Create a namespace manager to be used by the expression evaluator
		$nsMgr = new XmlNamespaceManager();
		$nsMgr->addNamespace( "v", "fv1" );
		$nsMgr->addNamespace( "xfi", "http://www.xbrl.org/2008/function/instance" );

		// The EvaluateWithArg static entry point will compile and evaluate an XPath expression
		// This expression returns a sequence that can be iterated over using a foreach()
		// function or using the built-in move functions.
		/** @var lyquidity\XPath2\Iterator\ExprIterator $seq1 */
		$seq1 = XPath2Expression::EvaluateWithArg( "(1,2)", null );
		$seq1->MoveNext();

		// An expression can take parameters. The parameters can be static values or the result of other expressions as in this case.
		$seq2 = XPath2Expression::EvaluateWithParamAndResolver( "(3,4,\$seq1[1])", null, array( 'seq1' => &$seq1 ) );
		$expression = XPath2Expression::Compile( "for \$x in \$seq1, \$y in \$seq2 return \$x + \$y", null );

		$args = array( 'seq1' => &$seq1, 'seq2' => &$seq2 );
		$result = $expression->EvaluateWithVars( null, $args );
		$x = $result instanceof lyquidity\XPath2\XPath2NodeIterator;
		$y = $result->getCount();
		foreach ( $result as $a => $b )
		{
			$c = 1;
		}

		// Expressions can be compiled independently of their evaluation.  This can be useful
		// when the same expression can be used with different arguments.
		// This will return an XPath2Expression instance.
		$expression = XPath2Expression::Compile( "for \$x in () return true()", null );

		// QNames can compared.  In this case the test is to see if the sequence on the right-hand side contains the QName on the left.
		// In this case it does not so a FalseValue instance is returned.
		$expression = XPath2Expression::Compile( "QName('http://xbrl.org/formula/conformance/example','ExplDim3') = (QName('http://xbrl.org/formula/conformance/example','ExplDim1'), QName('http://xbrl.org/formula/conformance/example','ExplDim2'))", null );
		$result = $expression->EvaluateWithVars( null, null );

		// Expressions can be nested and also use arguments.  For loop value variables look like externa parameter variables.
		// The getParameterQNames function can be used to retrieve a list of those variables that refer to external values.
		$expression = XPath2Expression::Compile( "for \$x in (1,2,3), \$z in (1,2,3) return \$x + \$y + \$z", null );
		$ref = $expression->getParameterQNames();
		// The expression is evaluated with an external value $y f 7.
		$result = $expression->EvaluateWithVars(null, array( 'y' => 7));
		foreach ( $result as $key => $x )
		{
			$y = 1;
		}

		// More example expressions
		$expression = XPath2Expression::Compile( "(1,2,3) + (4,5,6)", null );
		$expression = XPath2Expression::Compile( "(for \$a in (1+5), \$b in (2+4) return \$a eq \$b)[1] cast as xs:boolean", null );
		$expression = XPath2Expression::Compile( "(for \$a in (1+5), \$b in (\$a) return \$a eq \$b)[1] cast as xs:boolean", null );
		$result = $expression->EvaluateWithVars(null, null);
		$expression = XPath2Expression::Compile( "xs:string(number('1'))", null );
		$result = $expression->EvaluateWithVars(null, null);
		// $count = $result->getCount();
	}
	catch( Exception $ex )
	{
		echo "Error: " . $ex->getMessage();
	}
}

/**
 * These examples show how to evaluate an expression against an XML document
 */
function documentExamples( $query )
{
	try
	{
		// The document is loaded into a DOM navigator.  In this case a UK GAAP XBRL instance document.
		$dom = new DOMDocument();
		if ( ! $dom->load( __DIR__ . "/example.xbrl" ) )
		{
			echo "Oops! Failed to load.\n";
		}

		// Create a navigator and move to the appropriate node (usually the first child of the document.
		$nav = new DOMXPathNavigator( $dom );
		$nav->MoveToRoot();
		$nav->MoveToChild( XPathNodeType::Element );

		// Create a node provider so the expression can be evaluated against the document nodes.
		$provider = new NodeProvider( $nav );

		// If the query requires variables then they will appear in an indexed array
		$vars = array();

		// Create a namespace manager to be used by the expression evaluator
		$nsMgr = new XmlNamespaceManager();

		// Load the namespaces from the document.  $node is a DOMNameSpaceNode so extract the prefix from the node name
		$xpath = new DOMXPath( $dom );
		foreach( $xpath->query( 'namespace::*', $dom->documentElement ) as $node )
		{
			$nsMgr->addNamespace( str_replace( array( 'xmlns', ':' ), array( '', '' ), $node->nodeName ), (string)$node->nodeValue );
		}

		// This namespace is used by the custom function
		$nsMgr->addNamespace( "example", "http://www.example.com/functions" );

		// Compile an expression to retrieve a list of the context elements. This can be any valid XPath statement
		$expression = XPath2Expression::Compile( $query, $nsMgr );

		// Evaluate the expression
		$result = $expression->EvaluateWithVars( $provider, $vars );
		if ( $result )
		{
			echo "There are {$result->getCount()} context elements\n";

			// If there is a list of context then iterate over them.  In this case $result will be ChildNodeIterator
			foreach ( $result as $index => /** @var DOMXPathNavigator $node */ $node )
			{
				// $node will be a DOMXPathNavigator instance.  Just echoing something.
				echo "{$node->getLocalName()}\n";
			}
		}
	}
	catch( XPath2Exception $ex )
	{
		// This will catch XPath statement validation and evaluation issues
		Log::getInstance()->debug( $ex->getMessage() );
	}
	catch( Exception $ex )
	{
		// Other errors
		Log::getInstance()->debug( $ex->getMessage() );
	}
}

/**
 * This example shows how a custom function can be used in an XPath 2.0 query
 */
function customFunction()
{
	// A custom function needs a QName
	$ft = FunctionTable::getinstance();

	// This function does not take arguments
	$ft->AddWithArity( "http://www.example.com/functions", "getContexts", 0, XPath2ResultType::NodeSet,
		function( $context, $provider, $args )
		{
			// If the function expected arguments then $args will contain them

			// This implementation uses the DOMXPath class but it could be implemented in different ways.
			// For example, the navigator could be used to iterate over the children of the root element and select 'context' elements
			// Or the ChildOverDescendants class could be used to retrieve them.

			// The provider should be a navigator
			/**
			 * @var NodeProvider $provider
			 */
			$nav = $provider->getContext();
			if ( ! $nav instanceof XPathNavigator )
			{
				throw new Exception( "Invalid provider in getContexts function" );
			}

			// Make a clone so the provider location is unchanged
			$nav2 = $nav->CloneInstance();
			$nav2->MoveToRoot();

			$xpath = new DOMXPath( $nav2->getUnderlyingObject() );
			$domNodes = $xpath->query( "/xbrli:xbrl/xbrli:context" );

			$array = array();
			foreach ( $domNodes as $domNode )
			{
				$array[] = new DOMXPathNavigator( $domNode );
			}

			// The result will be an iterator
			$result = DocumentOrderNodeIterator::fromItemset( $array );

			return $result;
		}
	);

	// Call the documentExamples function passing a query that uses the getContexts function
	// The result will be the same as
	documentExamples( "example:getContexts()" );
}