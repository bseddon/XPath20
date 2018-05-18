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

namespace lyquidity\XPath2;

use lyquidity\XPath2\Iterator\ExprIterator;
use lyquidity\XPath2\Iterator\ChildNodeIterator;
use lyquidity\XPath2\Iterator\ElementOrderNodeIterator;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\xml\MS\XmlReservedNs;

/**
 * DEqualComparer (public)
 * A descendant of TreeComparer to implement s-equals2 comparsions.
 * See XBRL Dimensions 1.0 section 3.2
 * All non-numeric types are treated as string except booleans where "1" and "true" or "0" and "false" are equivalent
 */
class DEqualComparer extends TreeComparer
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
	public function DeepEqualByNavigatorx( $nav1, $nav2 )
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
	 * ItemEqual
	 * @param XPathItem $item1
	 * @param XPathItem $item2
	 * @return bool
	 */
	protected function ItemEqual( $item1, $item2 )
	{
		$xTypeCode = $item1->getSchemaType()->TypeCode;
		$yTypeCode = $item2->getSchemaType()->TypeCode;
		$types = array( XmlTypeCode::Decimal, XmlTypeCode::Float, XmlTypeCode::Double, XmlTypeCode::Integer );

		if ( $xTypeCode == XmlTypeCode::Boolean && $yTypeCode == XmlTypeCode::Boolean )
		{
			$x = $item1->GetTypedValue();
			$y = $item2->GetTypedValue();

			return $x == $y;
		}

		$xIsNumeric = in_array( $xTypeCode, $types );
		$yIsNumeric = in_array( $yTypeCode, $types );

		if ( $xIsNumeric != $yIsNumeric )
		{
			return false;
		}

		if ( ! $xIsNumeric )
		{
			$x = $item1->GetValue();
			$y = $item2->GetValue();

			if ( ! is_null( $this->collation ) && $this->collation == XmlReservedNs::collationCodepoint )
			{
				$x = \normalizer_normalize( $x, \Normalizer::FORM_C );
				$y = \normalizer_normalize( $y, \Normalizer::FORM_C );

				return strcmp( $x, $y );
			}
			else
			{
				return strcoll( $x, $y ) == 0;
			}
		}

		$x = $item1->GetTypedValue();
		$y = $item2->GetTypedValue();

		// Perform comparison of the typed values
		if ( is_double( $x ) && is_nan( $x ) && is_double( $y ) && is_nan( $y ) )
		{
			// BMS 2017-09-13 XFI requires that NaN == NaN returns false
			return false;
		}
		if ( is_double( $x ) && is_infinite( $x ) && is_double( $y ) && is_infinite( $y ) )
		{
			return true;
		}

		if ( $xTypeCode != $yTypeCode )
		{
			// Handle the exceptions
			$exceptions = (
				( $xTypeCode == XmlTypeCode::Integer && in_array( $yTypeCode, $types ) ) ||
				( $yTypeCode == XmlTypeCode::Integer && in_array( $xTypeCode, $types ) ) ||
				( $xTypeCode == XmlTypeCode::Decimal && in_array( $yTypeCode, $types ) ) ||
				( $yTypeCode == XmlTypeCode::Decimal && in_array( $xTypeCode, $types ) )
			);

			if ( ! $exceptions )
			{
				return false;
			}
		}

		if ( ! is_object( $x ) && ! is_object( $y ) )
		{
			if ( $x == $y )
			{
				return true;
			}
		}
		else if ( is_object( $x ) && is_object( $y ) && method_exists( $x, "Equals" ) && method_exists( $y, "Equals" ) )
		{
			if ( $x->Equals( $y ) )
			{
				return true;
			}
		}

		$res;
		$result = ValueProxy::EqValues( $x, $y, $res) && $res;
		return $result;

	}

	/**
	 * DeepEqualByIterator Alternative way to iterate over nodes in order-sensitive manner
	 * @param XPath2NodeIterator $iter1
	 * @param XPath2NodeIterator $iter2
	 * @return bool
	 */
	public function DeepEqualByIteratorx( $iter1, $iter2 )
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
