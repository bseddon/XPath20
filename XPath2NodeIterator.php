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

use lyquidity\XPath2\XPath2NodeIterator\Enumerator;
use lyquidity\XPath2\XPath2NodeIterator\SingleIterator;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\xml\interfaces\ICloneable;
use lyquidity\xml\interfaces\IEnumerable;
use lyquidity\xml\interfaces\IEnumerator;
use lyquidity\XPath2\Iterator\EmptyIterator;
use lyquidity\XPath2\Iterator\ExprIterator;
use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\xml\xpath\XPathNodeType;
use lyquidity\XPath2\DOM\DOMXPathNavigator;
use lyquidity\xml\exceptions\InvalidOperationException;

/**
 * XPath2NodeIterator (public abstract)
 */
class XPath2NodeIterator implements ICloneable, IEnumerable  //, \Iterator
{
	/**
	 * Used for debugging because it can become confusing which iterator is being processed
	 * @var string
	 */
	public $name;

	/**
	 * count
	 * @var int $count = -1
	 */
	protected $count = -1;

	/**
	 * curr
	 * @var XPathItem $curr
	 */
	private $curr;

	/**
	 * pos
	 * @var int $pos
	 */
	private $pos;

	/**
	 * iteratorStarted
	 * @var bool $iteratorStarted
	 */
	private $iteratorStarted;

	/**
	 * iteratorFinished
	 * @var bool $iteratorFinished
	 */
	private $iteratorFinished;

	/**
	 * Constructor
	 */
	public  function __construct() {}

	/**
	 * Clone
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance() {}

	/**
	 * Count the number of nodes of a specific type (default: all)
	 * @return int
	 */
	public function getCount()
	{
		if ( $this->count == -1 )
		{
			$this->count = $this->getCountType();
		}

		return $this->count;
	}

	/**
	 * Count the number of nodes of a specific type (default: all)
	 * @param XPathNodeType $kind
	 * @return int
	 */
	public function getCountType( $kind = XPathNodeType::All )
	{
		$count = 0;

		/**
		 * @var XPath2NodeIterator $iter
		 */
		$iter = $this->CloneInstance();
		$iter->Reset();
		while ( $iter->MoveNext() )
		{
			$result = $iter->getCurrent();
			if ( $result instanceof ExprIterator )
			{
				$count += $result->getCount( $kind );
			}
			else
			{
				if ( $kind == XPathNodeType::All || $kind == $result->getNodeType() )
				{
					$count++;
				}
			}
		}

		return $count;
	}

	/**
	 * Check to see if the iterator is empty
	 * @returns bool
	 */
	public function getIsEmpty()
	{
		/**
		 * @var XPath2NodeIterator $iter
		 */
		$iter = $this->CloneInstance();
		return ! $iter->MoveNext();
	}

	/**
	 * Check to see if the iterator has just one item
	 * @return bool
	 */
	public function getIsSingleIterator()
	{
		/**
		 * @var XPath2NodeIterator $iter
		 */
		$iter = $this->CloneInstance();
		// $iter->named = "Original";
		$part1 = $iter->MoveNext();
		if ( $part1 )
		{
			$part2 = ! $iter->MoveNext();
			if ( $part2 ) return true;
		}

		return false;
	}

	/**
	 * check to see if the iterator is a range (always false)
	 * @return bool
	 */
	public function getIsRange()
	{
		return false;
	}

	/**
	 * Get the current item
	 * @return XPathNavigator
	 */
	public function getCurrent()
	{
		if ( ! $this->iteratorStarted )
		{
			throw new InvalidOperationException();
		}
		return $this->curr;
	}

	/**
	 * Get the current position
	 * @return int
	 */
	public function getCurrentPosition()
	{
		if ( ! $this->iteratorStarted )
			throw new InvalidOperationException();
		return $this->pos;
	}

	/**
	 * Get the sequential position
	 * @return int
	 */
	public function getSequentialPosition()
	{
		return $this->getCurrentPosition() + 1;
	}

	/**
	 * ResetSequentialPosition
	 * @return void
	 */
	public function ResetSequentialPosition()
	{
		return;
	}

	/**
	 * Has the iterator started
	 * @return bool
	 */
	public function getIsStarted()
	{
		return $this->iteratorStarted;
	}

	/**
	 * Find out if the iterator has finished (reached the end)
	 * @return bool
	 */
	public function getIsFinished()
	{
		return $this->iteratorFinished;
	}

	/**
	 * MoveNext
	 * @return bool
	 */
	public function MoveNext()
	{
		if ( ! $this->iteratorStarted )
		{
			$this->Init();
			$this->pos = -1;
			$this->iteratorStarted = true;
		}

		/**
		 * @var XPathItem $item
		 */
		$item = $this->NextItem();
		if ( ! is_null( $item ) )
		{
			$this->pos++;
			$this->curr = $item;
			return true;
		}

		$this->iteratorFinished = true;
		return false;
	}

	/**
	 * Allow the iterators to be reset
	 */
	public function Reset()
	{
		$this->iteratorStarted = false;
		$this->iteratorFinished  = false;
		$this->pos = -1;
	}

	/**
	 * ToList
	 * @return array List<XPathItem>
	 */
	public function ToList()
	{
		/**
		 * @var XPath2NodeIterator $iter
		 */
		$iter = $this->CloneInstance();

		/**
		 * @var \Traversable $iter List<XPathItem>
		 */
		return iterator_to_array( $iter );
	}

	/**
	 * CreateBufferedIterator
	 * @return XPath2NodeIterator
	 */
	public function CreateBufferedIterator()
	{}

	/**
	 * ToString
	 * @return string
	 */
	public function ToString()
	{
		$sb = array_reduce( $this->ToList(), function( $carry, /** @var XPath2Item $item */ $item )
		{
			$carry[] = $item->ToString();
			return $carry;
		}, array() );
		$s = implode( ", ", $sb );
		return $s;

		$sb = array();
		foreach ( $this->ToList() as $node )
		{
			$sb[] = is_object( $node ) ? $node->ToString() : $node;
		}

		$s = implode( ", ", $sb );

		return empty( $s)
			? "<empty>"
			: $s;
	}

	/**
	 * Init
	 * @return void
	 */
	protected function Init()
	{}

	/**
	 * NextItem
	 * @return XPathItem
	 */
	protected function NextItem()
	{}

	/**
	 * Create
	 * @param object $value
	 * @return XPath2NodeIterator
	 */
	public static function Create( $value )
	{
		if ( $value instanceof Undefined )
		{
			return EmptyIterator::$Shared;
		}

		if ( $value instanceof XPath2NodeIterator )
		{
			/**
			 * @var XPath2NodeIterator $iter
			 */
			$iter = $value;
			return $iter->CloneInstance();
		}

		$item = $value instanceof XPathItem
			? $value
			: XPath2Item::fromValue( $value );

		return new SingleIterator( $item );
	}

	/**
	 * Clone
	 * @return object
	 */
	// function CloneInstance()
	// {
	// 	return $this->CloneInstance();
    // }

	/**
	 * GetEnumerator
	 * @return IEnumerator
	 */
	public function GetEnumerator()
	{
		return new Enumerator( $this );
	}

	/**
	 * unit tests
	 * @param \XBRL_Instance $instance
	 */
	public static function tests( $instance )
	{
		$nav = new DOM\DOMXPathNavigator( $instance->getInstanceXml() );
		$provider = new NodeProvider( $nav );
		$nav2 = $provider->getContext();

		$iter = XPath2NodeIterator::Create( $nav );
		$result = $iter->getIsSingleIterator();
	}

	// These functions implement iterator

	/**
	 * Rewind the iterator
	 * @return boolean
	 */
	public function rewind()
	{
		// BMS 2017-07-07 This was originally in the commented.
		// Uncommenting now because it appear to be needed in
		// /MinimalConformance/Expressions/Operators/SeqOp test fn-union-node-args-001
		// $this->pos = -1;
		// $this->iteratorStarted = false;
		// $this->iteratorFinished = false;
		$this->Reset();
		return $this->MoveNext();
	}

	/**
	 * Get the current value
	 * @return \lyquidity\xml\xpath\XPathNavigator
	 */
	public function current()
	{
		return $this->getCurrent();
	}

	/**
	 * Get the current key
	 * @return number
	 */
	public function key()
	{
		return $this->pos;
	}

	/**
	 * Move to the next iterator. Return true if the iterator has moved or false.
	 * @return boolean
	 */
	public function next()
	{
		$result = $this->MoveNext();
		return $result;
	}

	/**
	 * Find out if the iterator is valid (started and not finished)s
	 * @return boolean
	 */
	public function valid()
	{
		return ! is_null( $this->iteratorFinished ) && ! $this->iteratorFinished; // $this->pos >= 0 && $this->pos < $this->count;
	}

}

?>
