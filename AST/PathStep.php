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

namespace lyquidity\XPath2\AST;

use lyquidity\XPath2\Iterator\SpecialChildNodeIterator;
use lyquidity\XPath2\Iterator\AttributeNodeIterator;
use lyquidity\XPath2\Iterator\ChildNodeIterator;
use lyquidity\XPath2\Iterator\SpecialDescendantNodeIterator;
use lyquidity\XPath2\Iterator\DescendantNodeIterator;
use lyquidity\XPath2\Iterator\AncestorNodeIterator;
use lyquidity\XPath2\Iterator\FollowingNodeIterator;
use lyquidity\XPath2\Iterator\FollowingSiblingNodeIterator;
use lyquidity\XPath2\Iterator\ParentNodeIterator;
use lyquidity\XPath2\Iterator\PrecedingNodeIterator;
use lyquidity\XPath2\Iterator\PrecedingSiblingNodeIterator;
use lyquidity\XPath2\Iterator\NamespaceNodeIterator;
use lyquidity\XPath2\Iterator\SelfNodeIterator;
use lyquidity\XPath2\Iterator\ExprNodeIterator;
use lyquidity\XPath2\Iterator\PositionFilterNodeIterator;
use lyquidity\XPath2\Iterator\ChildOverDescendantsNodeIterator;
use lyquidity\XPath2\Value\Integer;
use lyquidity\XPath2\lyquidity\Convert;
use lyquidity\xml\exceptions\NotSupportedException;
use lyquidity\XPath2\SequenceTypes;
use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\XPath2NodeIterator;

/**
 * PathStep (private)
 */
class PathStep
{
	/**
	 * The query test expression to evaluate
	 * @var object $nodeTest
	 */
	public $nodeTest;

	/**
	 * The type of expression (child, descendant, parent, etc.)
	 * @var XPath2ExprType $type
	 */
	public $type;

	/**
	 * The current node or null
	 * @var AbstractNode $node
	 */
	public $node;

	/**
	 * The next expression to evaluate
	 * @var PathStep $next
	 */
	public $next;

	/**
	 * Gets the next path step
	 * @return \lyquidity\XPath2\AST\PathStep
	 */
	public function getNext()
	{
		return $this->next;
	}

	/**
	 * Get function as property
	 * @param string $name The name of the property value to retrieve
	 */
	public function __get( $name )
	{
		switch ( $name )
		{
			case "Next":
				return $this->next;

			default:
				throw new NotSupportedException();
		}
	}

	/**
	 * Constructor
	 */
	public function __construct()
	{}

	/**
	 * Constructor
	 * @param AbstractNode $node
	 */
	public static function fromNode( $node )
	{
		$result = new PathStep();
		$result->nodeTest = null;
		$result->type = XPath2ExprType::Expr;
		$result->node = $node;
		return $result;
	}

	/**
	 * Constructor
	 * @param object $nodeTest
	 * @param XPath2ExprType $type
	 */
	public static function fromNodeType( $nodeTest, $type )
	{
		$result = new PathStep();
		$result->nodeTest = $nodeTest;
		$result->type = $type;
		$result->node = null;
		return $result;
	}

	/**
	 * Constructor
	 * @param XPath2ExprType $type
	 */
	public static function fromType( $type )
	{
		return PathStep::fromNodeType( null, $type );
	}

	/**
	 * AddLast
	 * @param PathStep $pathStep
	 * @return void
	 */
	public function AddLast( $pathStep )
	{
		/**
		 * @var PathStep $last
		 */
		$last = $this;
		while ( ! is_null( $last->next ) )
			$last = $last->next;
		$last->next = $pathStep;
	}

	/**
	 * Create
	 * @param XPath2Context $context
	 * @param object[] $dataPool
	 * @param XPath2NodeIterator $baseIter
	 * @param bool $special
	 * @return XPath2NodeIterator
	 */
	public function CreateWithIterator( $context, $dataPool, $baseIter, $special )
	{
		switch ( $this->type )
		{
			case XPath2ExprType::Attribute:
				return AttributeNodeIterator::fromNodeTest( $context, $this->nodeTest, $baseIter );

			case XPath2ExprType::Child:

				if ( $special && $this->nodeTest != SequenceTypes::$Node )
					return new SpecialChildNodeIterator( $context, $this->nodeTest, $baseIter );

				return ChildNodeIterator::fromNodeTest( $context, $this->nodeTest, $baseIter );

			case XPath2ExprType::Descendant:

				if ( $special && $this->nodeTest != SequenceTypes::$Node )
					return new SpecialDescendantNodeIterator( $context, $this->nodeTest, false, $baseIter );

				return DescendantNodeIterator::fromNodeTest( $context, $this->nodeTest, false, $baseIter );

			case XPath2ExprType::DescendantOrSelf:

				if ( $special && $this->nodeTest != SequenceTypes::$Node )
					return SpecialDescendantNodeIterator::fromNodeTest( $context, $this->nodeTest, true, $baseIter );

				return DescendantNodeIterator::fromNodeTest( $context, $this->nodeTest, true, $baseIter );

			case XPath2ExprType::Ancestor:

				return AncestorNodeIterator::fromParts( $context, $this->nodeTest, false, $baseIter );

			case XPath2ExprType::AncestorOrSelf:

				return AncestorNodeIterator::fromParts( $context, $this->nodeTest, true, $baseIter );

			case XPath2ExprType::Following:

				return FollowingNodeIterator::fromNodeTest( $context, $this->nodeTest, $baseIter );

			case XPath2ExprType::FollowingSibling:

				return FollowingSiblingNodeIterator::fromNodeTest( $context, $this->nodeTest, $baseIter );

			case XPath2ExprType::Parent:

				return ParentNodeIterator::fromNodeTest( $context, $this->nodeTest, $baseIter );

			case XPath2ExprType::Preceding:

				return PrecedingNodeIterator::fromNodeTest( $context, $this->nodeTest, $baseIter );

			case XPath2ExprType::PrecedingSibling:

				return PrecedingSiblingNodeIterator::fromNodeTest( $context, $this->nodeTest, $baseIter );

			case XPath2ExprType::NamespaceUri:

				return NamespaceNodeIterator::fromNodeTest( $context, $this->nodeTest, $baseIter );

			case XPath2ExprType::Self:

				return SelfNodeIterator::fromNodeTest( $context, $this->nodeTest, $baseIter );

			case XPath2ExprType::Expr:

				return ExprNodeIterator::fromParts( $context, $this->node, $dataPool, $baseIter );

			case XPath2ExprType::PositionFilter:

				return new PositionFilterNodeIterator( Convert::ToInt32( $this->nodeTest ), $baseIter );

			case XPath2ExprType::ChildOverDescendants:

				return ChildOverDescendantsNodeIterator::fromParts( $context, /* ChildOverDescendantsNodeIterator.NodeTest[] */ $this->nodeTest, $baseIter );

			default:

				return null;
		}
	}

	/**
	 * Create
	 * @param XPath2Context $context
	 * @param object $node
	 * @return PathStep
	 */
	public static function Create( $context, $node )
	{
		if ( is_a( $node, __CLASS__ ) )
		{
			/**
			 * @var PathStep $res
			 */
			$res = $node;
			return $res;
		}

		if ( $node instanceof PathExprNode )
		{
			/**
			 * @var PathExprNode $pathExpr
			 */
			$pathExpr = $node;
			return $pathExpr->FirstStep;
		}

		return PathStep::fromNode( AbstractNode::Create( $context, $node ) );
	}

	/**
	 * CreateFilter
	 * @param XPath2Context $context
	 * @param object $node
	 * @param array $predicateList List<Object>
	 * @return PathStep
	 */
	public static function CreateFilter( $context, $node, $predicateList )
	{
		if ( count( $predicateList ) == 1 )
		{
			/**
			 * @var AbstractNode $predicate
			 */
			$predicate = AbstractNode::Create( $context, $predicateList[0] );
			if ( $predicate instanceof ValueNode )
			{
				/**
				 * @var ValueNode $numexpr
				 */
				$numexpr = $predicate;
				if ( is_int( $numexpr->getContent() ) || $numexpr->getContent() instanceof Integer )
				{
					/**
					 * @var PathStep $res
					 */
					$res = PathStep::Create( $context, $node );
					$res->AddLast( PathStep::fromNodeType( $numexpr->getContent(), XPath2ExprType::PositionFilter ) );
					return $res;
				}
			}
		}

		/**
		 * @var AbstractNode $filterExpr
		 */
		$filterExpr = new FilterExprNode( $context, $node, $predicateList );
		// return new PathStep( $filterExpr );
		return PathStep::fromNode( $filterExpr );
	}

}



?>
