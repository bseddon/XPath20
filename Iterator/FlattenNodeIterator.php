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

namespace lyquidity\XPath2\Iterator;

use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\XPath2\XPath2Item;

/**
* FlattenNodeIterator (private final)
* Produces a sequence of items from an iterator that may contain nested iterators
*/
class FlattenNodeIterator extends XPath2NodeIterator implements \Iterator
{
	/**
	 * @var XPath2NodeIterator $baseIter
	 */
	private $baseIter;

	/**
	 * @var XPath2NodeIterator $iter
	 */
	private  $iter;

	/**
	 * @var XPath2NodeIterator $childIter
	 */
	private  $childIter;

	/**
	 * Constructor
	 * @param XPath2NodeIterator $baseIter
	 */
	public function __construct( $baseIter )
	{
		$this->baseIter = $baseIter->CloneInstance();
	}

	/**
	 * Clone
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		return new FlattenNodeIterator( $this->baseIter );
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
				if ( $this->childIter->MoveNext() )
					return $this->childIter->getCurrent();
				else
					$this->childIter = null;
			}

			if ( $this->iter->MoveNext() )
			{
				$res = $this->iter->current();

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
			else
			{
				return null;
			}
		}
	}

	/**
	 * Returns the iterator to use in foreach calls
	 * @return FlattenNodeIterator
	 */
	public function getIterator()
	{
		return $this;
	}

}
