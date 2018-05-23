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
use lyquidity\xml\xpath\XPathItem;

/**
 * EmptyIterator (public)
 */
class EmptyIterator extends XPath2NodeIterator implements \Iterator
{
	/**
	 * Shared
	 * @var EmptyIterator $Shared = new EmptyIterator()
	 */
	public static $Shared;

	/**
	 * Constructor
	 */
	public  function __construct() {}

	/**
	 * Static constructor
	 */
	public static function __static()
	{
		EmptyIterator::$Shared = new EmptyIterator();
	}

	/**
	 * Clone
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		return $this;
	}

	/**
	 * getIsFinished
	 * @return bool
	 */
	public function getIsFinished()
	{
		return true;
	}

	/**
	 * NextItem
	 * @return XPathItem
	 */
	protected function NextItem()
	{
		return null;
	}

	/**
	 * CreateBufferedIterator
	 * @return XPath2NodeIterator
	 */
	public function CreateBufferedIterator()
	{
		return $this;
	}

}

EmptyIterator::__static();

?>
