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
use lyquidity\XPath2\DOM\DOMXPathItem;
use lyquidity\xml\MS\XmlQualifiedNameTest;
use lyquidity\XPath2\CoreFuncs;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\xpath\XPathNavigator;
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
class ElementOrderNodeIterator extends XPath2NodeIterator implements \Iterator
{
	public static $CLASSNAME ="lyquidity\XPath2\Iterator\ElementOrderNodeIterator";

	/**
	 * @var array $itemSet was ItemSet
	 */
	private $itemSet;

	/**
	 * @var XPathNavigator $lastNode
	 */
	private $lastNode;

	/**
	 * The index of the element
	 * @var integer $elementIndex
	 */
	private $elementIndex = 0;

	/**
	 * The index of the item within an element
	 * @var int $index
	 */
	private $index = 0;

	/**
	 * Constructor
	 */
	public  function __construct()
	{}

	/**
	 * fromBaseIter
	 * @param XPath2NodeIterator $baseIter
	 */
	public static function fromNavigator( $context, $nav )
	{
		$nodeTest = XmlQualifiedNameTest::create();
		$iter = ChildNodeIterator::fromNodeTest( $context, $nodeTest, XPath2NodeIterator::Create( $nav ) );

		$result = new ElementOrderNodeIterator();
		$result->fromElementOrderNodeIteratorParts( $iter );
		return $result;
	}

	/**
	 * fromDocumentOrderNodeIteratorParts
	 * @param XPath2NodeIterator $baseIter
	 */
	protected function fromElementOrderNodeIteratorParts( $baseIter )
	{
		$isNode = null;
		$this->itemSet = array();
		while ( $baseIter->MoveNext() )
		{
			// BMS 2018-01-08 Changed to be more careful about when getIsNode() is called
			//                Same change must be made to DocumentOrderNodeIterator
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
			 * @var DOMXPathItem $clone
			 */
			$clone = CoreFuncs::CloneInstance( $baseIter->getCurrent() );
			$localName = $clone->getLocalName();

			$this->itemSet[ $localName ][] = $clone;
		}

		if ( ! is_null( $isNode ) && $isNode )
		{
			if ( count( $this->itemSet ) > 1 )
			{
				ksort( $this->itemSet );
			}

			foreach ( $this->itemSet as $elementName => $element )
			{
				if ( count( $element ) < 2 ) continue;

				$comparer = new XPathComparer();
				$result = usort( $this->itemSet[ $elementName ], function( $a, $b )
				{
					return strcasecmp( $a->getValue(), $b->getValue() );
				} );
			}

		}
	}

	/**
	 * Constructor
	 * @param ElementOrderNodeIterator $src
	 */
	private function AssignFrom( $src )
	{
		$this->elementIndex = $src->elementIndex;
		$this->index = $src->index;
		$this->itemSet = $src->itemSet;
		$this->lastNode = $src->lastNode;
	}

	/**
	 * Clone
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		$result = new ElementOrderNodeIterator();
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
		$keys = array_keys( $this->itemSet );

		while ( $this->elementIndex < count( $keys ) )
		{
			/**
			 * @var array $elements
			 */
			$elements = $this->itemSet[ $keys[ $this->elementIndex ] ];

			while ( $this->index < count( $elements ) )
			{
				/**
				 * @var XPathItem $item
				 */
				$item = $elements[ $this->index++ ] ;

				if ( $item instanceof XPathNavigator )
				{
					/**
					 * @var XPathNavigator $node
					 */
					$node = $item;

					if ( ! is_null( $this->lastNode ) )
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

			$this->elementIndex++;
			$this->index = 0;
			$this->lastNode = null;
		}
		return null;
	}

	/**
	 * Allow the iterators to be reset
	 */
	public function Reset()
	{
		$this->elementIndex = 0;
		$this->index = 0;
		$this->lastNode = null;
		parent::Reset();
	}

}
