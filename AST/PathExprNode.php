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

namespace lyquidity\XPath2\AST;

use lyquidity\XPath2\SequenceTypes;
use lyquidity\XPath2\IContextProvider;
use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\XPath2\CoreFuncs;
use lyquidity\XPath2\Iterator\ChildOverDescendantsNodeIterator\NodeTest;
use lyquidity\XPath2\XPath2ResultType;
use lyquidity\XPath2\Iterator\DocumentOrderNodeIterator;
use lyquidity\XPath2\XPath2Context;

/**
 * PathExprNode (final)
 */
class PathExprNode extends AbstractNode
{
	public static $CLASSNAME = "lyquidity\XPath2\AST\PathExprNode";

	/**
	 * @var bool $_isOrderedSet
	 */
	private $_isOrderedSet;

	/**
	 * @var array $_path
	 */
	private $_path;

	/**
	 * @var bool $Unordered
	 */
	public $Unordered;

	/**
	 * @var bool $startFromRoot
	 */
	public $startPathFromRoot = false;

	/**
	 * @var PathStep $FirstStep
	 */
	public function getFirstStep()
	{
		return $this->_path[0];
	}

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param PathStep $pathStep
	 */
	public function __construct( $context, $pathStep )
	{
		parent::__construct( $context );

		/**
		 * @var array $path List<PathStep>
		 */
		$path = array();

		/**
		 * @var PathStep $curr
		 */
		for ( $curr = $pathStep; ! is_null( $curr ); $curr = isset( $curr->next ) ? $curr->next : null )
		{
			if ( isset( $curr->type ) && $curr->type == XPath2ExprType::Expr )
				$this->Add( $curr->node );
			$path[] = $curr;
		}

		if ( count( $path ) == 2 &&
			$path[0]->type == XPath2ExprType::DescendantOrSelf &&
			$path[0]->nodeTest == SequenceTypes::$Node &&
			$path[1]->type == XPath2ExprType::Child )
		{
			$this->_path = array( PathStep::fromNodeType( $path[1]->nodeTest, XPath2ExprType::Descendant ) );
		}
		else
		{
			/**
			 * @var bool $transform
			 */
			$transform = false;
			do
			{
				$transform = false;
				/**
				 * @var int $k
				 */
				for ( $k = 0; $k < count( $path ) - 2; $k++ )
				{
					if ( $path[ $k ]->type == XPath2ExprType::DescendantOrSelf )
					{
						/**
						 * @var int $s
						 */
						$s = $k + 1;
						/**
						 * @var array $nodeTest List<ChildOverDescendantsNodeIterator\NodeTest>
						 */
						$nodeTest = array();
						for (; $s < count( $path ); $s++)
						{
							if ( $path[ $s ]->type != XPath2ExprType::Child )
								break;
							$nodeTest[] = new NodeTest( $path[ $s ]->nodeTest );
						}
						if ( count( $nodeTest ) > 1)
						{
							/**
							 * @var int $n
							 */
							$n = count( $nodeTest ) + 1;
							while ( $n-- > 0 )
								array_splice( $path, $k, 1 );
							// The array around the pathstep should not be needed but the splice does not work without it.
							array_splice( $path, $k, 0, array( PathStep::fromNodeType( $nodeTest, XPath2ExprType::ChildOverDescendants ) ) );
							$transform = true;
							break;
						}
					}
				}
			} while ( $transform );
			$this->_path = $path;
		}
	}

	/**
	 * Bind
	 * @return void
	 */
	public function Bind()
	{
		parent::Bind();
		$this->_isOrderedSet = $this->IsOrderedSet();
	}

	/**
	 * IsContextSensitive
	 * @return bool
	 */
	public function IsContextSensitive()
	{
		if ( $this->_path[0]->type == XPath2ExprType::Expr )
			return $this->_path[0]->node->IsContextSensitive();
		return true;
	}

	/**
	 * IsOrderedSet
	 * @return bool
	 */
	private function IsOrderedSet()
	{
		/**
		 * @var int $k
		 */
		for ( $k = 0; $k < count( $this->_path ); $k++)
		{
			/**
			 * @var XPath2ExprType $exprType
			 */
			$exprType;
			if ( $this->_path[ $k ]->type == XPath2ExprType::Expr )
			{
				if ( $k == 0 ) continue;
				if ( ! $this->_path[ $k ]->node instanceof FilterExprNode )
					return false;

				/**
				 * @var FilterExprNode $filterExpr
				 */
				$filterExpr = $this->_path[ $k ]->node;

				if ( ! $this->_path[ $k ]->node instanceof PathExprNode )
					return false;

				/**
				 * @var PathExprNode $pathExpr
				 */
				$pathExpr = $filterExpr[0];
				$exprType = $pathExpr->_path[0]->type;
			}
			else
				$exprType = $this->_path[ $k ]->type;

			switch ( $exprType )
			{
				case XPath2ExprType::Expr:
				case XPath2ExprType::Parent:
				case XPath2ExprType::Ancestor:
				case XPath2ExprType::AncestorOrSelf:
				case XPath2ExprType::Preceding:
				case XPath2ExprType::PrecedingSibling:
					return false;

				case XPath2ExprType::Descendant:
				case XPath2ExprType::DescendantOrSelf:
				case XPath2ExprType::Following:
				case XPath2ExprType::ChildOverDescendants:

					if ( $k < count( $this->_path ) - 1)
					{
						/**
						 * @var int $s
						 */
						for ( $s = $k + 1; $s < count( $this->_path ); $s++ )
						{
							if ( $this->_path[ $s ]->type != XPath2ExprType::Attribute && $this->_path[ $s ]->type != XPath2ExprType::NamespaceUri)
								return false;
						}
					}
					break;
			}
		}
		return true;
	}

	/**
	 * Execute
	 * @param IContextProvider $provider
	 * @param object[] $dataPool
	 * @return object
	 */
	public function Execute( $provider, $dataPool )
	{
		/**
		 * @var bool $orderedSet
		 */
		$orderedSet = $this->_isOrderedSet;

		/**
		 * TODO BMS 2017-07-30 Not sure how to translate the use of XPathDocumentNavigator here
		 * @var bool $special
		 */
		$special = ! is_null( $provider ) && $provider->getContext() instanceof XPathDocumentNavigator;

		/**
		 * @var XPath2NodeIterator $tail
		 */
		$tail;

		if ( $this->_path[0]->type == XPath2ExprType::Expr )
		{
			$tail = XPath2NodeIterator::Create( $this->_path[0]->node->Execute( $provider, $dataPool ) );
			if ( ! $this->_path[0]->node instanceof OrderedBinaryOperatorNode )
				$orderedSet = $orderedSet & $tail->getIsSingleIterator();
		}
		else
		{
			// BMS 2017-01-05 This change is to make a new path start from the root regardless of the location of the context item
			//				  Without this a query like /xbrl does not work because the path is not reset to the root
			// $tail = $this->_path[0]->CreateWithIterator( $this->getContext(), $dataPool, XPath2NodeIterator::Create( CoreFuncs::ContextNode( $provider ) ), $special );

			$fromNode = $this->startPathFromRoot
				? CoreFuncs::GetRoot( CoreFuncs::NodeValue( CoreFuncs::ContextNode( $provider ) ) )
				: CoreFuncs::ContextNode( $provider );
			$tail = $this->_path[0]->CreateWithIterator( $this->getContext(), $dataPool, XPath2NodeIterator::Create( $fromNode ), $special );
		}

		/**
		 * @var int $k
		 */
		for ( $k = 1; $k < count( $this->_path ); $k++ )
		{
			$tail = $this->_path[ $k ]->CreateWithIterator( $this->getContext(), $dataPool, $tail, $special );
		}

		if ( ! $orderedSet )
		{
			return DocumentOrderNodeIterator::fromBaseIter( $tail );
		}

		return $tail;
	}

	/**
	 * GetReturnType
	 * @param object[] $dataPool
	 * @return XPath2ResultType
	 */
	public function GetReturnType( $dataPool )
	{
		return XPath2ResultType::NodeSet;
	}

	public static function tests()
	{
		echo "test";
	}
}



?>
