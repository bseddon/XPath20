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
use lyquidity\XPath2\XPath2Item;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\XPath2\Undefined;
use lyquidity\XPath2\AST\AbstractNode;

/**
 * ExprIterator (private final)
 */
class ExprIterator extends XPath2NodeIterator implements \Iterator
{
	/**
	 * Provider pased to fromNodes or fromOwner
	 * @var IContextProvider $provider
	 */
	private $provider;

	/**
	 * Copy of the datapool passed to fromNodes or fromOwner
	 * @var object[] $dataPool
	 */
	private $dataPool;

	/**
	 * Copy of the nodes passed to fromNodes or taken from owner
	 * @var array $nodes An array of AbstractNode instances
	 */
	private $nodes;

	/**
	 * childIter
	 * @var XPath2NodeIterator $childIter
	 */
	private $childIter;

	/**
	 * index
	 * @var int $index = 0
	 */
	private $index = 0;

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Constructor
	 * @param array $nodes AbstractNode[]
	 * @param IContextProvider $provider
	 * @param array $dataPool object[]
	 */
	public function fromNodes( $nodes, $provider, $dataPool )
	{
		$result = new ExprIterator();
		$result->nodes = $nodes;
		$result->provider = $provider;
		$result->dataPool = $dataPool;
		return $result;
	}

	/**
	 * Constructor
	 * @param ExprNode $owner
	 * @param IContextProvider $provider
	 * @param array $dataPool object[]
	 */
	public static function fromOwner( $owner, $provider, $dataPool )
	{
		$result = new ExprIterator();
		$result->nodes = $owner->toArray();
		$result->provider = $provider;
		$result->dataPool = $dataPool;
		return $result;
	}

	/**
	 * Clone
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		return ExprIterator::fromNodes( $this->nodes, $this->provider, $this->dataPool );
	}

	/**
	 * CreateBufferedIterator
	 * @return XPath2NodeIterator
	 */
	public function CreateBufferedIterator()
	{
		return BufferedNodeIterator::fromSource( $this );
		// return new BufferedNodeIterator( $this );
	}

	/**
	 * NextItem
	 * @return XPathItem
	 */
	protected function NextItem()
	{
		while (true)
		{
			if ( ! is_null( $this->childIter ) )
			{
				if ( $this->childIter->MoveNext() )
					return $this->childIter->getCurrent();
				else
					$this->childIter = null;
			}

			if ( $this->index == count( $this->nodes ) )
				return null;

			$res = $this->nodes[ $this->index++ ]->Execute( $this->provider, $this->dataPool );
			if ( $res instanceof Undefined ) continue;

			if ( $res instanceof XPath2NodeIterator )
			{
				// BMS 2018-02-28 Seems like this is necessary because if the iterator has been used before the index will be wrong
				$res->Reset();
				$this->childIter = $res;
			}
			else
			{
				if ( $res instanceof XPathItem )
					return $res;

				return XPath2Item::fromValue( $res );
			}
		}
	}

	/**
	 * Allow the iterators to be reset
	 */
	public function Reset()
	{
		parent::Reset();
		if ( ! is_null( $this->childIter ) )
		{
			$this->childIter = null;
		}
		$this->index = 0;
	}

	// public function rewind()
	// {
	//	$this->Reset();
	//	parent::rewind();
	// }


}

