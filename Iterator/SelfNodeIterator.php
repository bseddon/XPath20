<?php
/**
 * XPath 2.0 for PHP
 *  _                      _     _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *       |___/    |_|                    |___/
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace lyquidity\XPath2\Iterator;

use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\xml\xpath\XPathNavigator;

/**
 * SelfNodeIterator (final)
 */
class SelfNodeIterator extends SequentialAxisNodeIterator implements \Iterator
{
	public static $CLASSNAME = "lyquidity\XPath2\Iterator\SelfNodeIterator";

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
		$result = new SelfNodeIterator();
		$result->fromAxisNodeIteratorParts( $context, $nodeTest, null, $iter );
		return $result;
	}

	/**
	 * Clone
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		$result = new SelfNodeIterator();
		$result->AssignFrom( $this );
		return $result;
	}

	/**
	 * MoveToFirst
	 * @param XPathNavigator $nav
	 * @return bool
	 */
	protected function MoveToFirst( $nav )
	{
		return true;
	}

	/**
	 * MoveToNext
	 * @param XPathNavigator $nav
	 * @return bool
	 */
	protected function MoveToNext( $nav  )
	{
		return false;
	}

	/**
	 * NextItem
	 * @return XPathItem
	 */
	protected function NextItem()
	{
		$result = parent::NextItem();
		if ( ! is_null( $result ) )
		{
			return $result;
		}

		// There are some tests (/Optional/StaticTyping/STPathExpr/STSteps)
		// that require an exception is thrown if the result is an empty sequence
		// However, this set of 20 or so tests do not seem to apply so this
		// error is not being thrown.
		// throw XPath2Exception::withErrorCode( "XPST0005", Resources::XPST0005 );
		return $result;
	}
}



?>
