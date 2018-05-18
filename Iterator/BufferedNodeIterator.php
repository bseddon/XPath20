<?php
/**
 * XPath 2.0 for PHP
 *  _					   _	 _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *	    |___/	  |_|					 |___/
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

/**
 * BufferedNodeIterator (public final)
 */
class BufferedNodeIterator extends XPath2NodeIterator implements \Iterator
{
	const CLASSNAME = "lyquidity\XPath2\BufferedNodeIterator";

	/**
	 * @var array $buffer
	 */
	private $buffer;

	/**
	 * @var XPath2NodeIterator $src
	 */
	private $src;

	/**
	 * Flag indicating whether the source is known to be empty
	 * @var bool
	 */
	private $empty = false;

	/**
	 * True when the buffer is known to be filled
	 * @var bool
	 */
	public $filled = false;

	/**
	 * Constructor
	 */
	public function __construct()
	{}

	/**
	 * Constructor
	 * @param XPath2NodeIterator $src
	 */
	public static function fromSource($src)
	{
		return BufferedNodeIterator::fromSourceWithClone( $src, true );
	}

	/**
	 * Constructor
	 * @param XPath2NodeIterator $src
	 * @param bool $clone
	 */
	public static function fromSourceWithClone( $src, $clone )
	{
		$result = new BufferedNodeIterator();
		$result->src = $clone ? $src->CloneInstance() : $src;
		$result->buffer = array();
		return $result;
	}

	/**
	 * getCount
	 * @return int
	 */
	public function getCount()
	{
		if ( $this->empty ) return 0;

		if ( $this->src->getIsFinished() )
		{
			return count( $this->buffer );
		}
		return parent::getCount();
	}

	/**
	 *  getIsSingleIterator
	 * @return bool
	 */
	public function getIsSingleIterator()
	{
		if ( count( $this->buffer ) > 1)
			return false;
		else
		{
			if ( $this->src->getIsFinished() && count( $this->buffer ) == 1)
			{
				return true;
			}
			return $this->getIsSingleIterator();
		}
	}

	/**
	 * Fill
	 * @return void
	 */
	public function Fill()
	{
		if ( $this->src->getIsFinished() ) return;

		foreach ( $this->src as $fact )
		{
			$this->buffer[] = $fact;
		}
		// while ( $this->src->MoveNext() )
		// {
		// 	$this->buffer[] = $this->src->getCurrent();
		// }

		$this->filled = true;
		$this->empty = ! count( $this->buffer );
	}

	/**
	 * Preload
	 * @param XPath2NodeIterator $baseIter
	 * @return BufferedNodeIterator
	 */
	public static function Preload( $baseIter )
	{
		/**
		 * @var BufferedNodeIterator $res
		 */
		$res = BufferedNodeIterator::fromSource( $baseIter );
		$res->Fill();
		return $res;
	}

	/**
	 * Clone
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		/**
		 * @var BufferedNodeIterator $res
		 */
		$clone = new BufferedNodeIterator();
		$clone->src = $this->src->CloneInstance();
		$clone->buffer = $this->buffer;
		$clone->empty = $this->empty;
		$clone->filled = $this->filled;
		return $clone;
	}

	/**
	 * NextItem
	 * @return XPathItem
	 */
	protected function NextItem()
	{
		if ( $this->empty ) return null;

		/**
		 * @var int $index
		 */
		$index = $this->getCurrentPosition() + 1;
		if ( $index < count( $this->buffer ) )
		{
			return $this->buffer[ $index ];
		}
		else if ( ! $this->filled )
		{
			if ( ! $this->src->getIsFinished() )
			{
				if ( $this->src->MoveNext() )
				{
					$current = $this->src->getCurrent();
					$this->buffer[] = $current;
					return $current;
				}
				else
				{
					$this->filled = true;
				}
			}

			if ( ! count( $this->buffer ) )
			{
				$this->empty = true;
			}
		}

		return null;
	}

	/**
	 * ResetSequentialPosition
	 * @return void
	 */
	public function ResetSequentialPosition()
	{
		if ( ! $this->src->getIsFinished() )
		{
			$this->src->ResetSequentialPosition();
		}
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
		// This is not needed because if the source is known to be empty, its always empty.
		// $this->empty = false;
		if ( is_null( $this->src ) ) return;
		$this->src->Reset();
	}

}



?>
