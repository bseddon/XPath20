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

/**
 * XPathFunctionDef (public)
 */
class XPathFunctionDef
{
	/**
	 * Name
	 * @var string $Name
	 */
	public $Name;

	/**
	 * Delegate
	 * @var callable $Delegate
	 */
	public $Delegate;

	/**
	 * ResultType
	 * @var XPath2ResultType $ResultType
	 */
	public $ResultType;

	/**
	 * Constructor
	 * @param String $name
	 * @param callable $_delegate
	 * @param XPath2ResultType $resultType
	 */
	public  function __construct( $name, $_delegate, $resultType )
	{
		$this->Name = $name;
		$this->Delegate = $_delegate;
		$this->ResultType = $resultType;
	}

	/**
	 * Invoke
	 * @param XPath2Context $context
	 * @param IContextProvider $provider
	 * @param array $args
	 * @return object
	 */
	public function Invoke( $context, $provider, $args )
	{
		return is_null( $this->Delegate )
			? null
			: call_user_func( $this->Delegate, $context, $provider, $args );
	}

}
