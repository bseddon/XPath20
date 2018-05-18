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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace lyquidity\XPath2\Iterator;

use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\xml\xpath\XPathItem;

/**
 * SequentialAxisNodeIterator (abstract)
 */
abstract class SequentialAxisNodeIterator extends AxisNodeIterator
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	// /**
	//  * Constructor
	//  * @param XPath2Context $context
	//  * @param object $nodeTest
	//  * @param bool $matchSelf
	//  * @param XPath2NodeIterator $iter
	//  */
	// public static function fromNodeTest( $context, $nodeTest, $matchSelf, $iter )
	// {
	// 	$result = new SequentialAxisNodeIterator();
	// 	$result->__construct( $context, $nodeTest, $matchSelf, $iter );
	// 	return $result;
	// }

	/**
	 * @var bool $first = false
	 */
	private $first = false;

	/**
	 * MoveToFirst
	 * @param XPathNavigator $nav
	 * @return bool
	 */
	protected abstract function MoveToFirst( $nav );

	/**
	 * MoveToNext
	 * @param XPathNavigator $nav
	 * @return bool
	 */
	protected abstract function MoveToNext($nav);

	/**
	 * NextItem
	 * @return XPathItem
	 */
	protected function NextItem()
	{
		while (true)
		{
			if ( ! $this->accept )
			{
				if ( ! $this->MoveNextIter() )
				{
					return null;
				}
				$this->first = true;
				if ( $this->matchSelf && $this->TestItem() )
				{
					$this->sequentialPosition++;
					return $this->curr;
				}
			}
			if ( $this->first)
			{
				$this->accept = $this->MoveToFirst( $this->curr );
				$this->first = false;
			}
			else
			{
				$this->accept = $this->MoveToNext( $this->curr );
			}
			if ( $this->accept )
			{
				if ( $this->TestItem() )
				{
					$this->sequentialPosition++;
					return $this->curr;
				}
			}
		}
	}
	/**
	 * Allow the iterators to be reset
	 */
	public function Reset()
	{
		parent::Reset();
		$this->accept = false;
		$this->sequentialPosition = null;
		$this->first = false;
	}

}



?>
