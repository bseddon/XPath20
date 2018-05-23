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
use lyquidity\xml\MS\XmlReservedNs;

/**
 * XPath2RunningContext (public)
 */
class XPath2RunningContext
{
	/**
	 * now
	 * @var DateTime $now
	 */
	public $now;

	/**
	 * Constructor
	 */
	public  function __construct()
	{
		$this->now = time();
		$this->NameBinder = new NameBinder();
		$this->DefaultCulture = \Collator::create( null );
		// $this->DefaultCulture->NumberFormat->CurrencyGroupSeparator = "";
		// $this->DefaultCulture->NumberFormat->NumberGroupSeparator = "";
		$this->IsOrdered = true;
	}

	/**
	 * GetCulture
	 * @param string $locale
	 * @return CultureInfo
	 */
	public function GetCulture( $locale )
	{
		if ( is_null( $locale ) || empty( $locale ) || $locale == XmlReservedNs::CollationCodepoint )
		{
			return null;
		}

		try
		{
			return Collator::create( $locale );
		}
		catch ( \Exception $ex )
		{
			throw new XPath2Exception( "XQST0076", Resources::XQST0076, $locale );
		}
	}

	/**
	 * DefaultCulture
	 * @var \Collator $DefaultCulture
	 */
	public $DefaultCulture;

	/**
	 * BaseUri
	 * @var String $BaseUri
	 */
	public $BaseUri;

	/**
	 * IsOrdered
	 * @var bool $IsOrdered
	 */
	public $IsOrdered;

	/**
	 * NameBinder
	 * @var NameBinder $NameBinder
	 */
	public $NameBinder;

}
