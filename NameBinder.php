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

use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\NameBinder\ReferenceLink;
use lyquidity\XPath2\NameBinder\NameSlot;
use lyquidity\xml\QName;

/**
 * NameBinder (private)
 */
class NameBinder
{
	/**
	 * Current slot
	 * @var int $_slotIndex = 0
	 */
	private  $_slotIndex = 0;

	/**
	 * A list of slots
	 * @var array $_slots (List<NameSlot>)
	 */
	private  $_slots = array(); // new List<NameSlot>();

	/**
	 * Get the number of entries
	 * @return int
	 */
	public function getLength()
	{
		return $this->_slotIndex;
	}

	/**
	 * PushVar
	 * @param QName $name
	 * @return ReferenceLink
	 */
	public function PushVar( $name )
	{
		$id = $this->NewReference();
		$this->_slots[] = new NameSlot( $id, $name );
		return $id;
	}

	/**
	 * PopVar
	 * @return void
	 */
	public function PopVar()
	{
		unset( $this->_slots[ count( $this->_slots ) - 1 ] );
	}

	/**
	 * VarIndexByName
	 * @param QName $name
	 * @return ReferenceLink
	 */
	public function VarIndexByName( $name )
	{
		foreach ( array_reverse( $this->_slots, true ) as $k => /** @var NameSlot $nameSlot */ $nameSlot )
		{
			// if ( $nameSlot->name == $name->__toString() )
			if ( (string)$nameSlot->name == (string)$name )
			{
				return $nameSlot->id;
			}
		}
		throw XPath2Exception::withErrorCodeAndParam( "XPST0008", Resources::XPST0008, $name->__toString() );

		// for ( $k = count( $this->_slots ) - 1; $k >= 0; $k-- )
		// {
		// 	/** @var NameSlot $nameSlot */
		// 	$nameSlot = $this->_slots[ $k ];
		// 	if ( $nameSlot->name == $name->__toString() )
		// 	{
		// 		return $nameSlot->id;
		// 	}
		// }
		// throw XPath2Exception::withErrorCodeAndParam( "XPST0008", Resources::XPST0008, $name->__toString() );
	}

	/**
	 * NewReference
	 * @return ReferenceLink
	 */
	public function NewReference()
	{
		return new ReferenceLink( $this->_slotIndex++ );
	}
}

/**
 * Unit tests
 */
function Test()
{
	$nameBinder = new NameBinder();
	$qn = \lyquidity\xml\qname( "mynamespace", "mylocalname" );
	$id = $nameBinder->PushVar( $qn );
	$byName = $nameBinder->VarIndexByName( $qn );
	$nameBinder->PopVar();
}


?>
