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

namespace lyquidity\XPath2;

use lyquidity\XPath2\Iterator\ExprIterator;
use lyquidity\XPath2\Iterator\ChildNodeIterator;
use lyquidity\XPath2\Iterator\ElementOrderNodeIterator;
use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\xml\xpath\XPathNodeIterator;

/**
 * XEqualComparer (public)
 * A descendent of TreeComparer to implement x-equal comparisons
 */
class XEqualComparer extends TreeComparer
{
	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param string $collation
	 */
	public function __construct( $context, $collation = null )
	{
		$this->useValueCompare = true;
		parent::__construct( $context, $collation );
	}

	/**
	 * DeepEqualByNavigator Iterate over nodes in order-sensitive manner
	 * @param XPathNavigator $nav1
	 * @param XPathNavigator $nav2
	 * @return bool
	 */
	public function DeepEqualByNavigator( $nav1, $nav2 )
	{
		/**
		 * @var XPathNodeIterator $ni1
		 * @var XPathNodeIterator $ni2
		 */
		$ni1 = ElementOrderNodeIterator::fromNavigator( $this->context, $nav1 );
		$ni2 = ElementOrderNodeIterator::fromNavigator( $this->context, $nav2 );

		$count1 = $ni1->getCount();
		$count2 = $ni2->getCount();

		if ( $count1 != $count2 ) return false;

		if ( $count1 )
		{
			return $this->DeepEqualByIterator( $ni1, $ni2 );
		}
		else
		{
			$doni1 = ChildNodeIterator::fromNodeTest( $this->context, null, XPath2NodeIterator::Create( $nav1 ) );
			$doni2 = ChildNodeIterator::fromNodeTest( $this->context, null, XPath2NodeIterator::Create( $nav2 ) );

			return $this->DeepEqualByIterator( $doni1, $doni2 );

			return $this->NodeEqual( $nav1, $nav2 );
		}
	}

	/**
	 * DeepEqualByIterator Alternative way to iterate over nodes in order-sensitive manner
	 * @param XPath2NodeIterator $iter1
	 * @param XPath2NodeIterator $iter2
	 * @param bool $elementsOnly
	 * @return bool
	 */
	public function DeepEqualByIterator( $iter1, $iter2, $elementsOnly = false )
	{
		$iter1 = $iter1->CloneInstance();
		$iter2 = $iter2->CloneInstance();
		$flag1 = false;
		$flag2 = false;

		do
		{
			$flag1 = $iter1->MoveNext();
			$flag2 = $iter2->MoveNext();
			if ( $flag1 != $flag2 ) return false;

			if (! $flag1 && ! $flag2) return true;

			$iter1Current = $iter1->getCurrent();
			$iter2Current = $iter2->getCurrent();

			// If one of the iterators is a ForIterator and it returns an ExprIterator handle it
			if ( $iter1Current instanceof ExprIterator || $iter2Current instanceof ExprIterator )
			{
				if ( ! $iter1Current instanceof ExprIterator ) $iter1Current = $iter1;
				if ( ! $iter2Current instanceof ExprIterator ) $iter2Current = $iter2;
				return $this->DeepEqualByIterator( $iter1Current->CloneInstance(), $iter2Current->CloneInstance() );
			}

			if ( $iter1Current->getIsNode() != $iter2Current->getIsNode() )
			{
				return false;
			}
			else
			{
				if ( $iter1Current->getIsNode() && $iter2Current->getIsNode() )
				{
					if ( $elementsOnly && $iter1Current->getNodeType() != \lyquidity\xml\xpath\XPathNodeType::Element )
					{
						continue;
					}
					if ( ! $this->NodeEqual( $iter1Current, $iter2Current ) )
					{
						return false;
					}
				}
				else
				{
					if ( ! $this->ItemEqual( $iter1Current, $iter2Current ) )
					{
						return false;
					}
				}
			}
		}
		while ( true );
		return true;
	}
}

?>
