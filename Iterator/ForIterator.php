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

namespace lyquidity\XPath2\Iterator;

use lyquidity\XPath2\IContextProvider;
use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\XPath2\AST\ForNode;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\XPath2\XPath2Item;

/**
* ForIterator (private final)
*/
class ForIterator extends XPath2NodeIterator implements \Iterator
{
	/**
	 * Holds the owner from the constructor
	 * @var ForNode $owner
	 */
	private $owner;

	/**
	 * Holds the provider from the constructor
	 * @var IContextProvider $provider
	 */
	private $provider;

	/**
	 * Holds the datapool from the constructor
	 * @var array $dataPool
	 */
	private $dataPool;

	/**
	 * Holds the base iter from the constructor
	 * @var XPath2NodeIterator $baseIter
	 */
	private $baseIter;

	/**
	 * Clone of the base iter initialized in init()
	 * @var XPath2NodeIterator $iter
	 */
	private  $iter;

	/**
	 * Current child iterator
	 * @var XPath2NodeIterator $childIter
	 */
	private  $childIter;

	/**
	 * Constructor
	 * @param ForNode $owner
	 * @param IContextProvider $provider
	 * @param array $dataPool object[]
	 * @param XPath2NodeIterator $baseIter
	 */
	public function __construct( $owner, $provider, $dataPool, $baseIter )
	{
		$this->owner = $owner;
		$this->provider = $provider;
		$this->dataPool = $dataPool;
		$this->baseIter = $baseIter;
	}

	/**
	 * Clone
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		return new ForIterator( $this->owner, $this->provider, $this->dataPool, $this->baseIter );
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
	 * Init
	 * @return void
	 */
	protected function Init()
	{
		$this->iter = $this->baseIter->CloneInstance();
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
				if ( $this->childIter instanceof ExprIterator )
				{
					$result = $this->childIter;
					$this->childIter = null;
					return $result;
				}
				else
				{
					if ( $this->childIter->MoveNext() )
						return $this->childIter->getCurrent();
					else
						$this->childIter = null;
				}
			}
			if ( ! $this->iter->MoveNext() )
				return null;

			if ( $this->owner->MoveNext( $this->provider, $this->dataPool, $this->iter->getCurrent(), $res ) )
			{
				if ( $res instanceof XPath2NodeIterator )
				{
					$this->childIter = $res;
				}
				else
				{
					$this->childIter = null;
					return $res instanceof XPathItem
						? $res
						: XPath2Item::fromValue( $res );
				}
			}
		}
	}

	/**
	 * Return this iterator
	 * @return \lyquidity\XPath2\Iterator\ForIterator
	 */
	public function getIterator()
	{
		return $this;
	}

}
