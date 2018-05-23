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

use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\XPath2\Properties\Resources;

/**
 * NodeProvider (public)
 */
class NodeProvider implements IContextProvider
{
	/**
	 * Holds the item from the constructor
	 * @var XPathNavigator $item
	 */
	private $item;

	/**
	 * Constructor
	 * @param XPathNavigator $item
	 */
	public function __construct( $item )
	{
		if ( ! $item instanceof XPathNavigator )
		{
			throw XPath2Exception::withErrorCodeAndParam( "XPTY0019", Resources::XPTY0019, get_class( $item ) );
		}
		$this->item = $item;
	}

	/**
	 * Get the current item
	 * @return XPathNavigator
	 */
	public function getContext()
	{
		return $this->item;
	}

	/**
	 * Get the current position.  Always return 1.
	 * @return int
	 */
	public function getCurrentPosition()
	{
		return 1;
	}

	/**
	 * Get the last position.  Always returns 1.
	 * @return int
	 */
	public function getLastPosition()
	{
		return 1;
	}

	/**
	 * Unit tests
	 * @param \XBRL_Instance $instance
	 */
	public static function tests( $instance )
	{
		$nav = new DOM\DOMXPathNavigator( $instance->getInstanceXml() );
		$provider = new NodeProvider( $nav );
		$nav2 = $provider->getContext();
	}
}

?>
