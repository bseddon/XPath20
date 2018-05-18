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

use lyquidity\xml\xpath\XPathItem;
use lyquidity\XPath2\NameBinder\ReferenceLink;
use lyquidity\XPath2\AST\AbstractNode;
use lyquidity\XPath2\Proxy\ValueProxy;
use lyquidity\XPath2\Iterator\NodeIterator;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\Value\Integer;
use lyquidity\XPath2\Iterator\DocumentOrderNodeIterator;
use lyquidity\XPath2\Value\Long;
use lyquidity\XPath2\AST\VarRefNode;
use lyquidity\XPath2\AST\ForNode;
use lyquidity\xml\QName;
use lyquidity\xml\exceptions\ArgumentException;

/**
 * XPath2Expression (public)
 */
class XPath2Expression
{
	/**
	 * @var string $expr
	 */
	private  $expr;

	/**
	 * @var AbstractNode $exprTree
	 */
	private  $exprTree;

	/**
	 * @var XPath2Context $context
	 */
	private  $context;

	/**
	 * @var XPath2ResultType|null $resultType
	 */
	private  $resultType;

	/**
	 * Constructor
	 * @param string $expr
	 * @param AbstractNode $exprTree
	 * @param XPath2Context $context
	 */
	private function __construct( $expr, $exprTree, $context )
	{
		$this->expr = $expr;
		$this->exprTree = $exprTree;
		$this->context = $context;
	}

	/**
	 * Clone
	 * @return XPath2Expression
	 */
	public function CloneInstance()
	{
		return new XPath2Expression( $this->expr, $this->exprTree, $this->context );
	}

	/**
	 * Adds an object to the current context
	 * @param string $name
	 * @param mixed $object
	 */
	public function AddToContext( $name, $object )
	{
		if ( ! property_exists( $this, 'context' ) ) return;
		$this->context->{$name} = $object;
	}

	/**
	 * CreateIterator
	 * @param IEnumerable<Object> $en
	 * @return IEnumerable<XPathItem>
	 */
	private function CreateIterator( $en )
	{
		foreach ( $en as $item  )
			yield XPath2Item::fromValue( $item );
	}

	/**
	 * PrepareValue
	 * @param object $value
	 * @return object
	 */
	private function PrepareValue( $value )
	{
		if ( is_null( $value ) )
			return Undefined::getValue();

		if ( $value instanceof \DOMNode )
		{
			$nav = new DOM\DOMXPathNavigator( $value );
			return $nav;
		}

		if ( $value instanceof XPath2NodeIterator )
		{
			return $value;
		}

		if ( is_array( $value ) || $value instanceof \ArrayIterator )
		{
			return new NodeIterator( function() use( $value ) { return $this->CreateIterator( $value ); } );
		}

		return $value;
	}

	/**
	 * Compile
	 * @param string $xpath
	 * @return XPath2Expression
	 */
	public static function CompileSimple( $xpath )
	{
		return Compile( $xpath, null );
	}

	/**
	 * Evaluate
	 * @param string $xpath2
	 * @param object $arg
	 * @return object
	 */
	public static function EvaluateWithArg( $xpath2, $arg )
	{
		return XPath2Expression::EvaluateWithArgAndResolver( $xpath2, null, $arg );
	}

	/**
	 * Evaluate
	 * @param string $xpath2
	 * @param IXmlNamespaceResolver $nsResolver
	 * @param object $arg
	 * @return object
	 */
	public static function EvaluateWithArgAndResolver( $xpath2, $nsResolver, $arg )
	{
		return XPath2Expression::Compile( $xpath2, $nsResolver)->EvaluateWithProperties( null, $arg );
	}

	/**
	 * Evalute
	 * @param string $xpath2
	 * @param IXmlNamespaceResolver $nsResolver
	 * @param array $param
	 * @return object
	 */
	public static function EvaluateWithParamAndResolver( $xpath2, $nsResolver, $param )
	{
		return XPath2Expression::Compile( $xpath2, $nsResolver )->EvaluateWithVars( null, $param );
	}

	/**
	 * SelectValues
	 * @param string $xpath
	 * @param object $arg
	 * @return IEnumerable<Object>
	 */
	public static function SelectValuesWithArg($xpath, $arg)
	{
		return SelectValuesWithArgAndResolver($xpath, null, $arg);
	}

	/**
	 * SelectValues
	 * @param string $xpath
	 * @param IXmlNamespaceResolver $resolver
	 * @param object $arg
	 * @return IEnumerable<Object>
	 */
	public static function SelectValuesWithArgAndResolver( $xpath, $resolver, $arg )
	{
		/**
		 * @var XPath2NodeIterator $iter
		 */
		$iter = XPath2NodeIterator::Create( Compile( $xpath, $resolver )->EvaluateWithProperties( null, $arg ) );
		while ( $iter->MoveNext() )
			yield $iter->getCurrent()->GetTypedValue();
	}

	/**
	 * SelectValues
	 * @param string $xpath
	 * @param IDictionary<XmlQualifiedName, object> $param
	 * @return IEnumerable<Object>
	 */
	public static function SelectValuesWithParam( $xpath, $param )
	{
		return SelectValuesWithParamAndResolver( $xpath, null, $param );
	}

	/**
	 * SelectValues
	 * @param string $xpath
	 * @param IXmlNamespaceResolver $resolver
	 * @param IDictionary<XmlQualifiedName, object> $vars
	 * @return IEnumerable<Object>
	 */
	public static function SelectValuesWithParamAndResolver( $xpath, $resolver, $vars )
	{
		/**
		 * @var XPath2NodeIterator $iter
		 */
		$iter = XPath2NodeIterator::Create( Compile( $xpath, $resolver )->EvaluateWithVars( null, $vars ) );
		while ( $iter->MoveNext() )
		{
			yield $iter->getCurrent()->GetTypedValue();
		}
	}

	/**
	 * Compile
	 * @param string $xpath
	 * @param IXmlNamespaceResolver $resolver
	 * @param bool $enableLogging
	 * @return XPath2Expression
	 */
	public static function Compile( $xpath, $resolver, $enableLogging = false )
	{
		if ( is_null( $xpath ) )
		{
			throw new \lyquidity\xml\exceptions\ArgumentNullException("$xpath");
		}

		if ( $xpath == "" )
		{
			throw XPath2Exception::withErrorCodeAndParam( "XPST0003", Resources::XPST0003, "Empty $xpath expression" );
		}

		/**
		 * @var XPath2Context $content
		 */
		$context = new XPath2Context( $resolver );

		/**
		 * @var Tokenizer $tokenizer
		 */
		$tokenizer = new Tokenizer( $xpath );

		/**
		 * @var XPath2Parser $parser
		 */
		$parser = new XPath2Parser( $context );
		if ( $enableLogging ) $parser->enableLogging();

		/**
		 * @var AbstractNode $exprTree
		 */
		$exprTree = $parser->yyparseSafe( $tokenizer );
		return new XPath2Expression( $xpath, $exprTree, $context );
	}

	/**
	 * Evaluate
	 * @return object
	 */
	public function Evaluate()
	{
		return $this->EvaluateWithArg( null, null );
	}

	/**
	 * Return an array of parameter names
	 * @return QName[]
	 */
	public function getParameterQNames()
	{
		$result = array();
		$forQNames = array(); // Need to ignore variables that are defined for the 'for' clause

		$this->traverseNodes( $this->exprTree, function( $node ) use( &$result, &$forQNames )
		{
			if ( $node instanceof ForNode )
			{
				$forQNames[] = $node->getQNVarName();
			}
			else if ( $node instanceof VarRefNode )
			{
				$qname = $node->getQNVarName();
				if ( ! in_array( $qname, $forQNames ) )
				{
					$result[] = $qname;
				}
			}

			return true;
		} );

		return $result;
	}

	/**
	 * return a list of functions used in this expression excluding any that have a prefix in the $excludePrefixes array
	 * @param array $excludePrefixes
	 */
	public function getFunctionsUsed( $excludePrefixes = array() )
	{
		if ( is_string( $excludePrefixes ) )
		{
			$excludePrefixes = array( $excludePrefixes );
		}

		$result = $this->traverseNodes( $this->exprTree, function( $node ) use( &$result, $excludePrefixes )
		{
			// TODO Test the functions in this node and return false if any are found
			return true;
		} );

		return ! $result;
	}

	/**
	 * Checks to see is one or any of a collection of functions are used in an expression
	 * @param array $functions
	 */
	public function isFunctionUsed( $functions )
	{
		if ( is_string( $functions ) )
		{
			$functions = array( $functions );
		}

		$result = $this->traverseNodes( $this->exprTree, function( $node ) use( &$result, $functions )
		{
			// TODO Test the functions in this node and return false if any are found
			return true;
		} );

		return ! $result;
	}

	/**
	 *
	 * @param AbstractNode $node
	 * @param Function $callback
	 * @return boolean
	 */
	private function traverseNodes( $node, $callback )
	{
		if ( is_null( $callback ) || ! $callback( $node ) ) return false;

		if ( $node->getIsLeaf() ) return true;

		foreach ( $node->getIterator() as /** @var AbstractNode $node */ $child )
		{
			if ( ! $this->traverseNodes( $child, $callback ) ) return false;
		}

		return true;
	}

	/**
	 * BindExpr
	 * @param IDictionary<XmlQualifiedName, object> $vars
	 * @return object[]
	 */
	private function BindExpr( $vars )
	{
		/**
		 * @var XPath2RunningContext $runningContext
		 */
		$runningContext = new XPath2RunningContext();

		/**
		 * @var ReferenceLink[] $variables
		 */
		$variables = null;
		$variableValues = null;
		if ( ! is_null( $vars ) && ! empty( $vars ) )
		{
			/**
			 * @var array[ReferenceLink] $variables
			 */
			$variables = array(); // new ReferenceLink[$vars.Count];
			$variableValues = array(); // new object[$vars.Count];
			// $array = $vars; // new KeyValuePair<QName, object>[$vars.Count];
			$array = array();
			foreach ( $vars as $key => $value )
			{
				$variables[] = $runningContext->NameBinder->PushVar( $key );
				$variableValues[] = $value;
			}
		}

		$this->context->RunningContext = $runningContext;
		$this->context->availableCollections = isset( $vars['availableCollections'] ) ? $vars['availableCollections'] : array();
		$this->context->defaultCollection = isset( $vars['defaultCollection'] ) ? $vars['defaultCollection'] : null;

		$this->exprTree->Bind();

		/**
		 * @var object[] $datapool
		 */
		$dataPool = array(); // new object[ $runningContext->NameBinder->getLength() ];
		if ( ! is_null( $vars ) )
		{
			for ( $k = 0; $k < count( $variables ); $k++ )
			{
				$variables[ $k ]->Set( $dataPool, $this->PrepareValue( $variableValues[ $k ] ) );
			}
		}
		return $dataPool;
	}

	/**
	 * Evaluate
	 * @param IContextProvider $provider
	 * @param IDictionary<XmlQualifiedName, object> $vars
	 * @return object
	 */
	public function EvaluateWithVars( $provider, $vars )
	{
		/**
		 * @var object $res
		 */
		$res = $this->exprTree->Execute( $provider, $this->BindExpr( $vars ) );

		if ( $res instanceof DocumentOrderNodeIterator )
		{
			/**
			 * @var DocumentOrderNodeIterator $doni
			 */
			$doni = $res;
			if ( ( $x = $doni->getCount() ) )
			{
				$list = $doni->ToList();
				if ( $list[0] instanceof XPath2Item )
				{
					/**
					 *
					 * @var XPath2Item $item
					 */
					$item = $list[0];
					$value = $item->getTypedValue();
				}
			}
		}
		else if ( $res instanceof XPath2Item )
		{
			/**
			 * @var XPath2Item $item
			 */
			$item = $res;
			$this->resultType = $item->getXPath2ResultType();
			return  $item;
		}
		else if ( $res instanceof XPathItem )
		{
			/**
			 * @var XPathItem $item
			 */
			$item = $res;
			if ( ! $item->getIsNode() )
			{
				$res = $item->getTypedValue();
			}
		}
		else if ( $res instanceof ValueProxy )
		{
			/**
			 * @var ValueProxy $proxy
			 */
			$proxy = $res;
			$res = $proxy->getValue();
			// $res = $proxy->ToDouble( $provider );
		}

		$this->resultType = CoreFuncs::GetXPath2ResultTypeFromValue( $res );

		return $res instanceof Long
			? $res->getValue()
			: ( $res instanceof Integer
				? $res->Toint( $provider )
				: $res );

		// if ( $res instanceof ValueProxy )
		return $res instanceof ValueProxy
			? $res->getValue()
			: $res;
	}

	/**
	 * Convert a class instance to an array of key/value pairs
	 * @param object $props
	 */
	public function propertiesToArray( $props )
	{
		// IDictionary<XmlQualifiedName, object> vars = null;
		$vars = array();

		if ( ! is_null( $props ) )
		{
			if ( ! is_object( $props ) )
				throw new ArgumentException( "$props" );

			$reflection = new \ReflectionClass( $props );
			foreach ( $reflection->getProperties() as $name => $value )
			{
				$vars[ $value->getName() ] = $value->getValue( $props );
			}

			$reflection = new \ReflectionObject( $props );
			foreach ( $reflection->getProperties() as $name => $value )
			{
				/**
				 * @var \ReflectionProperty $value
				 */
				$vars[ $value->getName() ] = $value->getValue( $props );
			}
		}

		return $vars;
	}

	/**
	 * EvaluateWithProperties
	 * @param IContextProvider $provider
	 * @param object $props Object with property/value pairs
	 * @return object
	 */
	public function EvaluateWithProperties( $provider, $props )
	{
		return $this->EvaluateWithVars( $provider, $this->propertiesToArray( $props ) );
	}

	/**
	 * @var String $Expression
	 */
	public function getExpression()
	{
		return $expr;
	}

	/**
	 * GetResultType
	 * @param array $vars
	 * @return XPath2ResultType
	 */
	public function GetResultType($vars)
	{
		if ( is_null( $this->resultType ) )
			$this->resultType = $this->exprTree->GetReturnType( $this->BindExpr( $vars ) );
		return $this->resultType;
	}

}



?>
