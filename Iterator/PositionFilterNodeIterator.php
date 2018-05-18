<?php
/**
 * XPath 2.0 for PHP
 *  _					   _	 _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *	     |___/	  |_|					  |___/
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
use lyquidity\xml\xpath\XPathItem;

/**
 * PositionFilterNodeIterator (final)
 */
class PositionFilterNodeIterator extends XPath2NodeIterator implements \Iterator
{
	/**
	 * @var XPath2NodeIterator $iter
	 */
	private  $iter;

	/**
	 * @var int $position
	 */
	private  $position;

	/**
	 * Constructor
	 * @param int $pos
	 * @param XPath2NodeIterator $baseIter
	 */
	public function __construct( $pos, $baseIter )
	{
		$this->iter = $baseIter;
		$this->position = $pos;
	}

	/**
	 * Clone
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		return new PositionFilterNodeIterator( $this->position, $this->iter->CloneInstance() );
	}

	/**
	 * CreateBufferedIterator
	 * @return XPath2NodeIterator
	 */
	public function CreateBufferedIterator()
	{
		return new BufferedNodeIterator( $this->CloneInstance() );
	}

	/**
	 * NextItem
	 * @return XPathItem
	 */
	protected function NextItem()
	{
		while ( $this->iter->MoveNext() )
		{
		    if ( $this->iter->getSequentialPosition() == $this->position )
		    {
		    	$this->iter->ResetSequentialPosition();
		        return $this->iter->getCurrent();
		    }
		}
		return null;
	}

	/**
	 * Allow the iterators to be reset
	 */
	public function Reset()
	{
		parent::Reset();
		if ( is_null( $this->iter ) ) return;
		$this->iter->Reset();
	}

}


?>
