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

use \lyquidity\xml\xpath\XPathNavigator;
use \lyquidity\XPath2\Properties\Resources;
use \lyquidity\xml\interfaces\IComparer;
use \lyquidity\xml\MS\XmlNodeOrder;
use lyquidity\xml\exceptions\InvalidOperationException;

/**
 * XPathComparer
 */
class XPathComparer implements IComparer
{
	/**
	 * Constructor to specify of the line number is used
	 * @param bool $useLineNo
	 */
	public function __construct( $useLineNo = false )
	{
		$this->useLineNo = $useLineNo;
	}

	/**
	 * Holds the value from the constructor
	 * @var bool
	 */
	private $useLineNo;

	/**
	 * Compare
	 * @param XPathItem $x
	 * @param XPathItem $y
	 * @return int
	 */
	public function Compare( $x, $y )
	{
		$nav1 = $x instanceof XPathNavigator
			? $x
			: null;
		$nav2 = $y instanceof XPathNavigator
			? $y
			: null;
		/**
		 * @var XPathNavigator $nav1
		 * @var XPathNavigator $nav2
		 */
		if ( ! is_null( $nav1 ) && ! is_null( $nav2 ) )
		{
			switch ( $nav1->ComparePosition( $nav2, $this->useLineNo ) )
			{
				case XmlNodeOrder::Before:
					return -1;

				case XmlNodeOrder::After:
					return 1;

				case XmlNodeOrder::Same:
					return 0;

				default:

					// It appears to be correct to return -1 every time because $nav1
					// will always appear before $nav2 because that's the order in which
					// they are presented in the XPath query.
					return -1;

					/**
					 * Below is an implementation of the C# version which appears to be wrong
					 * because the value of spl_object_hash (or getHashCode in C# is random
					 * so the result here is likely to be random.
					 * @var XPathNavigator $root1
					 * @var XPathNavigator $root2
					 */
					$root1 = $nav1->CloneInstance();
					$root1->MoveToRoot();
					$root2 = $nav2->CloneInstance();
					$root2->MoveToRoot();

					$hashCode1 = spl_object_hash( $root1 );
					$hashCode2 = spl_object_hash( $root2 );

					if ( $hashCode1 < $hashCode2 )
					{
						return -1;
					}
					else if ( $hashCode1 > $hashCode2 )
					{
						return 1;
					}
					else
					{
						throw new InvalidOperationException();
					}
			}
		}
		else
			throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004,
				array(
					"xs:anyAtomicType",
					"node()* in function op:union,op:intersect and op:except"
				)
			);
	}

}

?>
