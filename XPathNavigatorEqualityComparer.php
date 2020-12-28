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

use lyquidity\xml\interfaces\IEqualityComparer;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\xml\xpath\XPathNavigator;

/**
 * XPathNavigatorEqualityComparer (private)
 */
class XPathNavigatorEqualityComparer implements IEqualityComparer
{
	/**
	 * Equals
	 * @param XPathItem $x
	 * @param XPathItem $y
	 * @return bool
	 */
	public function Equals( $x, $y )
	{
		$nav1 = $x instanceof XPathNavigator
			? $x
			: null;
		$nav2 = $y instanceof XPathNavigator
			? $x
			: null;
		/**
		 * @var XPathNavigator $nav1
		 * @var XPathNavigator $nav2
		 */
		if ( ! is_null( $nav1 ) && ! is_null( $nav2 ) )
			return $nav1->IsSamePosition( $nav2 );
		else
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					"xs:anyAtomicType",
					"node()* in function op:union,op:intersect and op:except"
				)
			);
	}

	/**
	 * GetHashCode
	 * @param XPathItem $obj
	 * @return int
	 */
	public function GetHashCode( $obj )
	{
		if ( $obj->getIsNode() )
			return spl_object_hash( $obj );
		else
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					"xs:anyAtomicType",
					"node()* in function op:union,op:intersect and op:except"
				)
			);
	}

}

