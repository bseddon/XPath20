<?php
/**
 * XPath 2.0 for PHP
 *  _					   _	 _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *		 |___/	  |_|					 |___/
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

namespace lyquidity\XPath2\Iterator;

use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\AST\AbstractNode;
use lyquidity\XPath2\NodeProvider;

/**
 * ExprNodeIterator (final)
 */
class ExprNodeIterator extends XPath2NodeIterator
{
	/**
	 * Context passed to the constructors fromParts or fromExprNodeIteratorParts
	 * @var XPath2Context $context
	 */
	private $context;

	/**
	 * Nde passed to the constructors fromParts or fromExprNodeIteratorParts
	 * @var AbstractNode $node
	 */
	private $node;

	/**
	 * Datapool passed to the constructors fromParts or fromExprNodeIteratorParts
	 * @var array $dataPool
	 */
	private $dataPool;

	/**
	 * baseIter passed to the constructors fromParts or fromExprNodeIteratorParts
	 * @var XPath2NodeIterator $baseIter
	 */
	private $baseIter;

	/**
	 * Current iter
	 * @var XPath2NodeIterator $iter
	 */
	private $iter;

	/**
	 * Current position
	 * @var int $sequentialPosition
	 */
	private $sequentialPosition;

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * fromParts
	 * @param XPath2Context $context
	 * @param AbstractNode $node
	 * @param array $dataPool object[]
	 * @param XPath2NodeIterator $baseIter
	 */
	public static function fromParts( $context, $node, $dataPool, $baseIter )
	{
		$result = new ExprNodeIterator();
		$result->fromExprNodeIteratorParts( $context, $node, $dataPool, $baseIter );
		return $result;
	}

	/**
	 * fromExprNodeIteratorParts
	 * @param XPath2Context $context
	 * @param AbstractNode $node
	 * @param array $dataPool object[]
	 * @param XPath2NodeIterator $baseIter
	 */
	protected function fromExprNodeIteratorParts( $context, $node, $dataPool, $baseIter )
	{
		$this->context = $context;
		$this->node = $node;
		$this->dataPool = $dataPool;
		$this->baseIter = $baseIter;
	}

	/**
	 * AssignFrom
	 * @param AxisNodeIterator $src
	 * @return void
	 */
	protected function AssignFrom( $src )
	{
		$context = $this->context;
		$node = $this->node;
		$dataPool = $this->dataPool;
		$baseIter = $this->baseIter;
		$iter = $this->iter->CloneInstance();
		$sequentialPosition = $this->sequentialPosition;

	}

	/**
	 * CloneInstance
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		/**
		 * @var ExprNodeIterator $res
		 */
		$res = new ExprNodeIterator();
		$res->AssignFrom( $this );
		return $res;
	}

	/**
	 * CreateBufferedIterator
	 * @return XPath2NodeIterator
	 */
	public function CreateBufferedIterator()
	{
		return new BufferedNodeIterator( $this );
	}

	/**
	 * NextItem
	 * @return XPathItem
	 */
	protected function NextItem()
	{
		while ( true )
		{
			if ( is_null( $this->iter ) )
			{
				if ( ! $this->baseIter->MoveNext() )
					return null;
				$this->sequentialPosition = 0;
				$this->iter = XPath2NodeIterator::Create( $this->node->Execute( new NodeProvider( $this->baseIter->getCurrent() ), $this->dataPool ) );
			}
			if ( $this->iter->MoveNext() )
			{
				$this->sequentialPosition++;
				return $this->iter->getCurrent();
			}
			$this->iter = null;
		}
	}

	/**
	 * getSequentialPosition
	 * @return int
	 */
	public function getSequentialPosition()
	{
		return $this->sequentialPosition;
	}

	/**
	 * Allow the iterators to be reset
	 */
	public function Reset()
	{
		parent::Reset();
		if ( is_null( $this->iter ) ) return;
		// $this->iter->Reset();
	}

	/**
	 * ResetSequentialPosition
	 * @return void
	 */
	public function ResetSequentialPosition()
	{
		$this->iter = null;
		$this->sequentialPosition = 0;
	}

}



?>
