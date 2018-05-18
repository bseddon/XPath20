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

use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\xml\xpath\XPathNavigator;

/**
 * AttributeNodeIterator (final)
 */
class AttributeNodeIterator extends SequentialAxisNodeIterator implements \Iterator
{
	public static $CLASSNAME ="lyquidity\XPath2\Iterator\AttributeNodeIterator";

	/**
	 * When true attributes if the xs:id type will not be included in the output (default: false)
	 * @var bool
	 */
	public $ignoreIdAttributes = false;

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
		$result = new AttributeNodeIterator();
		$result->fromAxisNodeIteratorParts( $context, $nodeTest, null, $iter );
		return $result;
	}

	/**
	 * CloneInstance
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		$result = new AttributeNodeIterator();
		$result->AssignFrom( $this );
		$result->ignoreIdAttributes = $this->ignoreIdAttributes;
		return $result;
	}

	/**
	 * Initialize the iterator
	 * {@inheritDoc}
	 * @see \lyquidity\XPath2\XPath2NodeIterator::Init()
	 */
	public function Init()
	{

	}

	/**
	 * Return an iterator foreach()
	 * @return AttributeNodeIterator
	 */
	public function getIterator()
	{
		return $this;
	}

	/**
	 * Check whether to skip this node
	 * @param XPathNavigator $nav
	 * @return boolean
	 */
	private function skip( $nav )
	{
		// TODO this test should be based on the xs:id type
		return $this->ignoreIdAttributes && $nav->getLocalName() == 'id';
	}

	/**
	 * MoveToFirst
	 * @param XPathNavigator $nav
	 * @return bool
	 */
	protected function MoveToFirst( $nav )
	{
		$result = $nav->MoveToFirstAttribute();

		if ( $result && $this->skip( $nav ) )
		{
			return $this->MoveToNext( $nav );
		}

		return $result;
	}

	/**
	 * MoveToNext
	 * @param XPathNavigator $nav
	 * @return bool
	 */
	protected function MoveToNext( $nav )
	{
		$result = $nav->MoveToNextAttribute();

		if ( $result && $this->skip( $nav ) )
		{
			return $this->MoveToNext( $nav );
		}

		return $result;
	}

}



?>
