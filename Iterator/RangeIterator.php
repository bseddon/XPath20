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
use lyquidity\XPath2\Value\Integer;

/**
 * RangeIterator (private)
 */
class RangeIterator extends XPath2NodeIterator implements \Iterator
{
	/**
	 * @var int $_min
	 */
	private $_min;

	/**
	 * @var int $_max
	 */
	private $_max;

	/**
	 * @var int $_index
	 */
	private $_index;

	/**
	 * Constructor
	 * @param int $min
	 * @param int $max
	 */
	public function __construct( $min, $max )
	{
		if ( $min instanceof Integer ) $min = $min->getValue();
		$this->_min = $min;
		if ( $max instanceof Integer ) $max = $max->getValue();
		$this->_max = $max;
	}

	/**
	 * Clone
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		return new RangeIterator( $this->_min, $this->_max );
	}

	/**
	 * @var int $Count
	 */
	public function getCount()
	{
		$c = $this->_max - $this->_min + 1;
		return floor( max( 0, $c ) );
	}

	/**
	 * Init
	 * @return void
	 */
	protected function Init()
	{
		$this->_index = $this->_min;
	}

	/**
	 * NextItem
	 * @return XPathItem
	 */
	protected function NextItem()
	{
		if ( $this->_index <= $this->_max )
			return XPath2Item::fromValue( $this->_index++ );
		return null;
	}

	/**
	 * CreateBufferedIterator
	 * @return XPath2NodeIterator
	 */
	public function CreateBufferedIterator()
	{
		return CloneInstance();
	}

	/**
	 * getIsRange
	 * @return bool
	 */
	public function getIsRange()
	{
		return true;
	}
}



?>
