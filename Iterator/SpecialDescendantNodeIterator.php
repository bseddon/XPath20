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
use lyquidity\xml\xpath\XPathItem;
use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\XPath2NodeIterator;

/**
 * SpecialDescendantNodeIterator (final)
 */
class SpecialDescendantNodeIterator extends AxisNodeIterator implements \Iterator
{
	/**
	 * The kind from the node test
	 * @var XPathNodeType $kind
	 */
	private  $kind;

	/**
	 * Constructor
	 */
	public  function __construct() {}

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param object $nodeTest
	 * @param bool $matchSelf
	 * @param XPath2NodeIterator $iter
	 */
	public static function fromNodeTest( $context, $nodeTest, $matchSelf, $iter )
	{
		$result = new SpecialDescendantNodeIterator();
		$result->__construct( $context, $nodeTest, $matchSelf, $iter );
		$result->kind = XPathNodeType::All;
		if ( ! is_null( $this->nameTest ) || ( ! is_null( $this->typeTest ) && $this->typeTest->GetNodeKind() == XPathNodeType::Element ) )
			$this->kind = XPathNodeType::Element;
		return $result;
	}

	/**
	 * Clone
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		$result = new SpecialDescendantNodeIterator();
		$result->AssignFrom( $this );
		$result->kind = $this->kind;
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
		if (! $this->accept)
		{
			if ( ! $this->MoveNextIter() )
				return null;
			if ( $this->matchSelf && $this->TestItem() )
			{
				$this->sequentialPosition++;
				return $this->curr;
			}
		}

		MoveToFirstChild:
		if ( $this->curr->MoveToChild( $this->kind ) )
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
		if ( ! $this->curr->MoveToNext( $this->kind ) )
		{
			$this->curr->MoveToParent();
			$this->depth--;
			goto MoveToNext;
		}

		TestItem:
		if ( ! $this->TestItem() )
			goto MoveToFirstChild;

		$this->sequentialPosition++;
		return $this->curr;
	}

}

?>
