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

use lyquidity\xml\xpath\XPathNodeType;
use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\xml\xpath\XPathItem;

/**
 * FollowingNodeIterator (final)
 */
class FollowingNodeIterator extends AxisNodeIterator implements \Iterator
{
	/**
	 * Kind of the type test
	 * @var XPathNodeType $kind
	 */
	private $kind;

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param object $nodeTest
	 * @param XPath2NodeIterator $iter
	 */
	public static function fromNodeTest( $context, $nodeTest, $iter )
	{
		$result = new FollowingNodeIterator();
		$result->fromAxisNodeIteratorParts( $context, $nodeTest, null, $iter );
		$result->fromFollowingNodeIteratorParts();
		return $result;
	}

	/**
	 * Supports constructing an instance
	 */
	protected function fromFollowingNodeIteratorParts()
	{
		if ( is_null( $this->typeTest ) )
		{
			if ( is_null( $this->nameTest ) )
				$this->kind = XPathNodeType::All;
			else
				$this->kind = XPathNodeType::Element;
		}
		else
			$this->kind = $this->typeTest->GetNodeKind();
	}

	/**
	 * CloneInstance
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		$result = new FollowingNodeIterator();
		$result->AssignFrom( $this );
		$result->kind = $this->kind;
		return $result;
	}

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
					return null;
			}
			$this->accept = $this->curr->MoveToFollowing( $this->kind );
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

}

?>
