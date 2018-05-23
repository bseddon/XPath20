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

use lyquidity\xml\interfaces\IEnumerator;
use lyquidity\xml\exceptions\InvalidOperationException;

/**
 * Enumerator (private)
 */
class Enumerator implements IEnumerator
{
	/**
	 * current
	 * @var XPath2NodeIterator $current
	 */
	private $current;

	/**
	 * iterationStarted
	 * @var bool $iterationStarted
	 */
	private $iterationStarted;

	/**
	 * original
	 * @var XPath2NodeIterator $original
	 */
	private $original;

	/**
	 * Constructor
	 * @param XPath2NodeIterator $iter
	 */
	public function __construct( $iter )
	{
		$this->original = $iter->CloneInstane();
	}

	/**
	 * Get the current value of the iterator
	 * @return object
	 */
	public function getCurrent()
	{
		if ( ! $this->iterationStarted || is_null( $this->current ) )
			throw new InvalidOperationException();
		return $this->current->Current;
	}

	/**
	 * MoveNext
	 * @return bool
	 */
	public function MoveNext()
	{
		if ( ! $this->iterationStarted )
		{
			$this->current = $this->original->CloneInstane();
			$this->iterationStarted = true;
		}
		if ( ! is_null( $this-current ) && $this->current->MoveNext() )
		{
			return true;
		}
		$this->current = null;
		return false;
	}

	/**
	 * Reset
	 * @return void
	 */
	public function Reset()
	{
		$this->iterationStarted = false;
	}

	/**
	 * Dispose
	 * @return void
	 */
	public function Dispose()
	{
		return;
	}

}

