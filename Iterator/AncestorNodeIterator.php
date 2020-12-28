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

use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\XPath2\XPath2Context;

/**
 * AncestorNodeIterator (final)
 */
class AncestorNodeIterator extends SequentialAxisNodeIterator implements \Iterator
{
	/**
	 * Constructor
	 */
	public  function __construct()
	{}

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param object $nodeTest
	 * @param bool $matchSelf
	 * @param XPath2NodeIterator $iter
	 */
	public static function fromParts( $context, $nodeTest, $matchSelf, $iter )
	{
		$result = new AncestorNodeIterator();
		$result->fromAxisNodeIteratorParts( $context, $nodeTest, $matchSelf, $iter );
		return $result;
	}

	/**
	 * Constructor
	 * @param AxisNodeIterator $src
	 */
	private static function fromSource( $src )
	{
		$result = new AncestorNodeIterator();
		$result->AssignFrom( $src );
		return $result;
	}

	/**
	 * CloneInstance
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		return self::fromSource( $this );
	}

	/**
	 * MoveToFirst
	 * @param XPathNavigator $nav
	 * @return bool
	 */
	protected function MoveToFirst( $nav )
	{
		return $nav->MoveToParent();
	}

	/**
	 * MoveToNext
	 * @param XPathNavigator $nav
	 * @return bool
	 */
	protected function MoveToNext( $nav )
	{
		return $nav->MoveToParent();
	}

}



?>
