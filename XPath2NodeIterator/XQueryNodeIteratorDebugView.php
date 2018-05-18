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

namespace lyquidity\XPath2\XPath2NodeIterator;

/**
 * XQueryNodeIteratorDebugView (internal)
 */
class XQueryNodeIteratorDebugView
{
	/**
	 * @var XPath2NodeIterator $iter
	 */
	private $iter;

	/**
	 * Constructor
	 * @param XPath2NodeIterator $iter
	 */
	public function __construct( $iter )
	{
		$this->iter = $iter->CloneInstance();
	}

	/**
	 * @var XPathItem[] $Items
	 */
	public function getItems()
	{
		/**
		 * @var array $res List<XPathItem>
		 */
		$res = array();
		foreach ( $this->iter as /** @var XPathItem $item */ $item )
		{
			if ( count( $res ) == 10)
				break;

			$res[] = $item->CloneInstance();
		}
		return $res;
	}

	/**
	 * @var XPathItem $Current
	 */
	public function getCurrent()
	{
		return $this->iter->curr;
	}

	/**
	 * @var int $CurrentPosition
	 */
	public function getCurrentPosition()
	{
		return $this->iter->pos;
	}

}
