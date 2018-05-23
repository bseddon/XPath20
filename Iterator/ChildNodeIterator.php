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

use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\XPath2Exception;

/**
 * ChildNodeIterator (final)
 */
class ChildNodeIterator extends SequentialAxisNodeIterator implements \Iterator
{


	/**
	 * When true comments will not be included in the output (default: false)
	 * @var bool
	 */
	public $excludeComments = false;

	/**
	 * When true whitespace only nodes will not be included in the output (default: false)
	 * @var bool
	 */
	public $excludeWhitespace = false;

	/**
	 * Flag to indecate whether child comment nodes should be incuded (false) or included (true)
	 * @var bool
	 */
	public $commentsSkipped = false;

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param object $nodeTest
	 * @param XPath2NodeIterator $iter
	 * @return XPath2NodeIterator
	 */
	public static function fromNodeTest( $context, $nodeTest, $iter )
	{
		$result = new ChildNodeIterator();
		$result->fromAxisNodeIteratorParts( $context, $nodeTest, false, $iter );
		return $result;
	}

	/**
	 * CloneInstance
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		$result = new ChildNodeIterator();
		$result->AssignFrom( $this );
		$result->excludeComments = $this->excludeComments;
		$result->excludeWhitespace = $this->excludeWhitespace;
		return $result;
	}

	/**
	 * Tests whether $node is an instance of XPathNavigator and throws an exception if not
	 * @param object $node
	 */
	protected function IsNode( $node )
	{
		if ( ! $node instanceof XPathNavigator )
		{
			throw XPath2Exception::withErrorCodeAndParam( "XPTY0020", Resources::XPTY0019, $node->getValue() );
		}
	}

	/**
	 * Check whether to skip this node
	 * @param XPathNavigator $nav
	 * @return boolean
	 */
	private function skip( $nav )
	{
		if ( $this->excludeComments && $nav->getNodeType() == XML_COMMENT_NODE )
		{
			$this->commentsSkipped++;
		}
		return	( $this->excludeComments && $nav->getNodeType() == XML_COMMENT_NODE ) ||
				( $this->excludeWhitespace && $nav->IsWhitespaceNode() );
	}

	/**
	 * MoveToFirst
	 * @param XPathNavigator $nav
	 * @return bool
	 */
	protected function MoveToFirst( $nav )
	{
		$result = $nav->MoveToFirstChild();

		if ( $result )
		{
			if ( $this->skip( $nav ) )
			{
				return $this->MoveToNext( $nav );
			}
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
		$result = $nav->MoveToNext();

		if ( $result )
		{
			if ( $this->skip( $nav ) )
			{
				return $this->MoveToNext( $nav );
			}
		}

		return $result;
	}

	/**
	 * Gets the iterator for the Iterator interface
	 * @return \lyquidity\XPath2\Iterator\ChildNodeIterator
	 */
	public function getIterator()
	{
		return $this;
	}
}



?>
