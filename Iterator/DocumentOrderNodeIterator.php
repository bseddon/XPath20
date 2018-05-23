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

use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\XPath2\CoreFuncs;
use lyquidity\XPath2\DOM\DOMXPathItem;
use lyquidity\XPath2\XPathComparer;
use lyquidity\XPath2\XPath2Exception;

/**
 * DocumentOrderNodeIterator (final)
 *
 * This is a fairly faithful reproduction of the original but it should be modified to use
 * the index of the itemset and only add new items if the $nav->getUnderlyingObject()->getNodePath()
 * is unique (does not already exist).  This will eliminate the need for a sort and the NextItem
 * function can be simplified to return the element of the $itemSet array.
 */
class DocumentOrderNodeIterator extends XPath2NodeIterator implements \Iterator
{
	/**
	 * Array passed into the constructor
	 * @var array $itemSet was ItemSet
	 */
	private $itemSet;

	/**
	 * Copy of the last node processed
	 * @var XPathNavigator $lastNode
	 */
	private $lastNode;

	/**
	 * Current node depth
	 * @var int $index
	 */
	private $index = 0;

	/**
	 * allowSameElementRepeat
	 * @var bool
	 */
	public $allowSameElementRepeat = false;

	/**
	 * Constructor
	 */
	public  function __construct()
	{}

	/**
	 * fromItemset
	 * @param array $itemSet
	 * @param bool $allowSameElementRepeat
	 */
	public static function fromItemset( $itemSet, $allowSameElementRepeat = false )
	{
		$result = new DocumentOrderNodeIterator();
		$result->allowSameElementRepeat = $allowSameElementRepeat;
		$result->itemSet = array_values( $itemSet );
		return $result;
	}

	/**
	 * fromBaseIter
	 * @param XPath2NodeIterator $baseIter
	 * @param string $allowSameElementRepeat
	 * @return \lyquidity\XPath2\Iterator\DocumentOrderNodeIterator
	 */
	public static function fromBaseIter( $baseIter, $allowSameElementRepeat = false )
	{
		$result = new DocumentOrderNodeIterator();
		$result->allowSameElementRepeat = $allowSameElementRepeat;
		$result->fromDocumentOrderNodeIteratorParts( $baseIter );
		return $result;
	}

	/**
	 * fromDocumentOrderNodeIteratorParts
	 * @param XPath2NodeIterator $baseIter
	 */
	protected function fromDocumentOrderNodeIteratorParts( $baseIter )
	{
		$isNode = null;
		$this->itemSet = array();
		while ( $baseIter->MoveNext() )
		{
			// BMS 2018-01-08 Changed to be more careful about when getIsNode() is called
			//                Same change must be made to ElementOrderNodeIterator
			$current = $baseIter->getCurrent();

			if ( is_null( $isNode ) )
			{
				$isNode = $current instanceof XPathNavigator && $current->getIsNode();
			}
			else
			{
				if ( ( ! $isNode && $current instanceof XPathNavigator ) || ( $isNode && $current->getIsNode() != $isNode ) )
				{
					throw XPath2Exception::withErrorCodeAndParam( "XPTY0018", Resources::XPTY0018, $baseIter->getCurrent()->getValue() );
				}
			}

			/**
			 * @var DOMXPathItem $x
			 */
			$x = CoreFuncs::CloneInstance( $baseIter->getCurrent() );
			// $line = $x->getLineNo();
			$this->itemSet[] = $x;
		}

		if ( ! is_null( $isNode ) && $isNode && count( $this->itemSet ) > 1 )
		{
			$comparer = new XPathComparer();
			$result = usort( $this->itemSet, array( $comparer, "Compare" ) );
			// array_multisort( array_keys( $this->itemSet ), SORT_NATURAL| SORT_FLAG_CASE, $this->itemSet );
		}
	}

	/**
	 * Constructor
	 * @param DocumentOrderNodeIterator $src
	 */
	private function AssignFrom( $src )
	{
		$this->index = $src->index;
		$this->itemSet = $src->itemSet;
		$this->lastNode = $src->lastNode;
		$this->allowSameElementRepeat = $src->allowSameElementRepeat;
	}

	/**
	 * Clone
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		$result = new DocumentOrderNodeIterator();
		$result->AssignFrom( $this );
		return $result;
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
	 * NextItem
	 * @return XPathItem
	 */
	protected function NextItem()
	{
		while ( $this->index < count( $this->itemSet ) )
		{
			/**
			 * @var XPathItem $item
			 */
			$item = $this->itemSet[ $this->index++ ];

			if ( $item instanceof XPathNavigator )
			{
				/**
				 * @var XPathNavigator $node
				 */
				$node = $item;

				if ( ! $this->allowSameElementRepeat && ! is_null( $this->lastNode ) )
				{
					if ( $this->lastNode->IsSamePosition( $node ) )
					{
						continue;
					}
				}
				$this->lastNode = $node->CloneInstance();
			}
			return $item;
		}
		return null;
	}

	/**
	 * Allow the iterators to be reset
	 */
	public function Reset()
	{
		$this->index = 0;
		$this->lastNode = null;
		parent::Reset();
	}
}



?>
