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

use lyquidity\XPath2\XPath2NodeIterator;

/**
 * NodeIterator (public final)
 */
class NodeIterator extends XPath2NodeIterator implements \Iterator // \IteratorAggregate
{
	/**
	 * Result of the callable
	 * @var \Iterator $iterator
	 */
	private $iterator;

	/**
	 * Holds the enumerable from the constructor
	 * @var callable $callable
	 */
	private $callable;

	/**
	 * Record if the iterator has been accessed yet
	 * @var string
	 */
	private $used = false;

	/**
	 * Constructor
	 * @param Function $enumerable
	 */
	public function __construct( $enumerable )
	{
		parent::__construct();
		$this->callable = $enumerable;
	}

	/**
	 * Clone
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		$result = new NodeIterator( $this->callable );
		return $result;
	}

	/**
	 * CreateBufferedIterator
	 * @return XPath2NodeIterator
	 */
	public function CreateBufferedIterator()
	{
		return BufferedNodeIterator::fromSource( $this );
	}

	/**
	 * Initializer called by the moveNext
	 */
	public function Init()
	{
		$this->iterator = call_user_func( $this->callable );
		$this->used = false;
	}

	/**
	 * NextItem This function is used if the caller uses 'MoveNext' on the iterator
	 * @return XPathItem
	 */
	protected function NextItem()
	{
		if ( $this->used ) $this->iterator->next();
		$this->used = true;
		return $this->iterator->current(); // getCurrent();
	}

	/**
	 * This function is used with the caller uses the 'foreach' syntax.
	 * {@inheritDoc}
	 * @see IteratorAggregate::getIterator()
	 */
	public function getIterator()
	{
		// return call_user_func( $this->callable );
		return $this->iterator;
	}

	/**
	 * Allow the iterators to be reset
	 */
	public function Reset()
	{
		parent::Reset();
	}

}



?>
