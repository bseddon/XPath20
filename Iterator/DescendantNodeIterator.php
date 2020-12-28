<?php
/**
 * XPath 2.0 for PHP
 *  _					   _	 _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *		 |___/	  |_|					 |___/
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

use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\xml\xpath\XPathItem;

/**
 * DescendantNodeIterator (final)
 */
class DescendantNodeIterator extends AxisNodeIterator implements \Iterator
{


	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param object $nodeTest
	 * @param bool $matchSelf
	 * @param XPath2NodeIterator $iter
	 */
	public static function fromNodeTest( $context, $nodeTest, $matchSelf, $iter )
	{
		$result = new DescendantNodeIterator();
		$result->fromAxisNodeIteratorParts( $context, $nodeTest, $matchSelf, $iter );
		return $result;
	}

	/**
	 * CloneInstance
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		$result = new DescendantNodeIterator();
		$result->AssignFrom( $this );
		return $result;
	}

	/**
	 * Current node depth
	 * @var int $depth
	 */
	private  $depth;

	/**
	 * NextItem
	 * @return XPathItem
	 */
	protected function NextItem()
	{
		MoveNextIter:
		if ( ! $this->accept)
		{
			if ( ! $this->MoveNextIter() )
			{
				return null;
			}
			if ( $this->matchSelf && $this->TestItem() )
			{
				$this->sequentialPosition++;
				return $this->curr;
			}
		}

		MoveToFirstChild:
		if ( $this->curr->MoveToFirstChild() )
		{
			$this->depth++;
			goto TestItem;
		}

		MoveToNext:
		if ( $this->depth == 0 )
		{
			$this->accept = false;
			goto MoveNextIter;
		}
		if ( ! $this->curr->MoveToNext() )
		{
			$this->curr->MoveToParent();
			$this->depth--;
			goto MoveToNext;
		}

		TestItem:
		if ( ! $this->TestItem() )
		{
			goto MoveToFirstChild;
		}
		$this->sequentialPosition++;
		return $this->curr;
	}

	/**
	 * Allow the iterators to be reset
	 */
	public function Reset()
	{
		parent::Reset();
		$this->depth = 0;
	}
}



?>
