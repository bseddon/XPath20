<?php
/**
 * XPath 2.0 for PHP
 *  _					   _	 _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *	     |___/	  |_|					  |___/
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
use lyquidity\XPath2\XPath2Context;
use lyquidity\xml\xpath\XPathNodeType;
use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\xml\xpath\XPathItem;

/**
 * PrecedingNodeIterator (final)
 */
class PrecedingNodeIterator extends AxisNodeIterator implements \Iterator
{
	/**
	 * Kind from the nodeTest
	 * @var XPathNodeType $kind
	 */
	private  $kind;

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
		$result = new PrecedingNodeIterator();
		$result->fromAxisNodeIteratorParts( $context, $nodeTest, null, $iter );
		$result->fromPrecedingNodeIteratorParts();
		return $result;
	}

	/**
	 * Supports creating an instance
	 */
	protected function fromPrecedingNodeIteratorParts()
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
		$result = new PrecedingNodeIterator();
		$result->AssignFrom( $this );
		$result->kind = $this->kind;
		return $result;
	}

	/**
	 * nav
	 * @var XPathNavigator $nav = null
	 */
	private  $nav = null;

	/**
	 * NextItem
	 * @return XPathItem
	 */
	protected function NextItem()
	{
		while (true)
		{
			if ( ! $this->accept)
			{
				if ( ! $this->MoveNextIter() )
					return null;
				$this->nav = $this->curr->CloneInstance();
				$this->curr->MoveToRoot();
			}
			$this->accept = $this->curr->MoveToFollowing( $this->kind, $this->nav );
			if ( $this->accept )
			{
				if ( $this->curr->isNodeAncestorOf( $this->nav ) ) continue;

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
