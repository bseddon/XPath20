<?php
/**
 * XPath 2.0 for PHP
 *  _					   _	 _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *	    |___/	 |_|					 |___/
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

use lyquidity\xml\xpath\XPathItem;
use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\xml\exceptions\InvalidOperationException;

/**
 * NodeList
 * This class is only used by the extensions which are not being implemented because PHP does not have an extension mechanism
 */
class NodeList implements \IteratorAggregate
{
	/**
	 * @var array $_list List<XmlNode>
	 */
	private $_list;

	/**
	 * @var XPath2NodeIterator $_iter
	 */
	private $_iter;

	/**
	 * @var bool $_done
	 */
	private $_done;

	/**
	 * Constructor
	 * @param XPath2NodeIterator $iter
	 * @param XmlDocument $doc
	 */
	public function __construct( $iter, $doc )
	{
		$this->_list = array(); // new List<XmlNode>();
		$this->_iter = $iter;
	}

	/**
	 * @var int $Count
	 */
	public function getCount()
	{
		return count( $this->_list );
	}
	/**
	 * GetNode
	 * @param XPathItem $item
	 * @return \DOMNode
	 */
	private function GetNode( $item )
	{
		if ( $item->getIsNode() )
			return $this->ToXmlNode( $item );
		return null;
	}

	/**
	 * ToXmlNode
	 * @param XPathNavigator $nav
	 * @return DOMNode
	 */
	public static function ToXmlNode( $nav )
	{
		if ( $nav instanceof \DOMNode ) return $nav;

		if ( $nav instanceof XPathNavigator ) return $nav->getUnderlyingObject();

		return null;
	}

	/**
	 * getIterator
	 * {@inheritDoc}
	 * @see IteratorAggregate::getIterator()
	 */
	public function getIterator()
	{
		if ( is_null( $this->_list ) )
		{
			throw new InvalidOperationException();
		}

		return new \ArrayIterator( $this->_list );
	}

	/**
	 * Item
	 * @param int $index
	 * @return DOMNode
	 */
	public function Item( $index )
	{
		if ( $this->getCount() <= $index && ! $this->_done )
		{
			$count = $this->getCount();
			while ( ! $this->_done && ( $count <= $index))
			{
				if ( $this->_iter->MoveNext() )
				{
					/**
					 * @var XmlNode $node
					 */
					$node = $this->GetNode( $this->_iter->getCurrent() );
					if ( ! is_null( $node ) )
					{
						$this->_list[] = $node;
						$count++;
					}
				}
				else
					$this->_done = true;
			}
		}
		if ( $index >= 0 && count( $this->_list ) > $index )
			return $this->_list[ $index ];
		return null;
	}


}



?>
