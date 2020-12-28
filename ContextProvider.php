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

namespace lyquidity\XPath2;

use lyquidity\xml\xpath\XPathItem;

/**
 * ContextProvider (internal final)
 */
class ContextProvider extends \ArrayIterator implements IContextProvider
{
	/**
	 * Reference to the iterator passed into the constructor function 'fromIterator'
	 * @var XPath2NodeIterator $m_iter
	 */
	private  $m_iter;

	/**
	 * Used for debugging because it can get confusing to know which provider is being processed
	 * @var string
	 */
	public $name;

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Constructor
	 * @param object $value
	 */
	public static function fromValue( $value )
	{
		$result = new ContextProvider();
		$result->m_iter = XPath2NodeIterator::Create( $value );
		return $result;
	}

	/**
	 * Constructor
	 * @param XPath2NodeIterator $iter
	 */
	public static function fromIterator( &$iter )
	{
		$result = new ContextProvider();
		$result->m_iter =& $iter;
		return $result;
	}

	/**
	 * Return the internal iterator
	 * @return XPath2NodeIterator
	 */
	public function getIterator()
	{
		return $this->m_iter;
	}

	/**
	 * MoveNext
	 * @return bool
	 */
	public function MoveNext()
	{
		return $this->m_iter->MoveNext();
	}

	/**
	 * Get the current iterator value
	 * @return XPathItem $Context
	 */
	public function getContext()
	{
		return $this->m_iter->getCurrent();
	}

	/**
	 * Return the current position index
	 * @return int $CurrentPosition
	 */
	public function getCurrentPosition()
	{
		return $this->m_iter->getCurrentPosition() + 1;
	}

	/**
	 * Get the previous position index
	 * @var int $LastPosition
	 */
	public function getLastPosition()
	{
		return $this->m_iter->getCount();
	}
}
