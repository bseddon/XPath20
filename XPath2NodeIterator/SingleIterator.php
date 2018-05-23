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

namespace lyquidity\XPath2\XPath2NodeIterator;

use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\XPath2\DOM\DOMXPathNavigator;

/**
 * SingleIterator (public)
 */
class SingleIterator extends XPath2NodeIterator implements \Iterator
{
	/**
	 * _item
	 * @var XPathItem $_item
	 */
	private $_item;

	/**
	 * Constructor
	 * @param XPathItem $item
	 */
	public function __construct( $item )
	{
		$this->_item = $item;
	}

	/**
	 * Clone
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		return new SingleIterator( $this->_item );
	}

	/**
	 * Return flag indicating whehther this is a single iterator (always true)
	 * @return bool $IsSingleIterator
	 */
	public function getIsSingleIterator()
	{
		return true;
	}

	/**
	 * NextItem
	 * @return XPathItem
	 */
	protected function NextItem()
	{
		if ( $this->getCurrentPosition() == -1 )
			return $this->_item;
		return null;
	}

	/**
	 * CreateBufferedIterator
	 * @return XPath2NodeIterator
	 */
	public function CreateBufferedIterator()
	{
		return $this->CloneInstance();
	}

	/**
	 * Allow the iterators to be reset
	 */
	public function Reset()
	{
		parent::Reset();
		if ( is_null( $this->_item ) ) return;
		/**
		 * @var DOMXPathNavigator $x
		 */
		// $this->_item->MoveToRoot();
		// $this->_item->MoveToParent();
	}
}

