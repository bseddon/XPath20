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

namespace lyquidity\XPath2\Iterator\ChildOverDescendantsNodeIterator;

use lyquidity\xml\MS\XmlQualifiedNameTest;
use lyquidity\XPath2\SequenceTypes;
use lyquidity\XPath2\SequenceType;

/**
 * NodeTest (public)
 */
class NodeTest
{
	/**
	 * The name test to apply
	 * @var XmlQualifiedNameTest $nameTest
	 */
	public  $nameTest;

	/**
	 * The sequence type test to apply
	 * @var SequenceType $typeTest
	 */
	public  $typeTest;

	/**
	 * Constructor
	 * @param object $test
	 */
	public  function __construct( $test )
	{
		if ( $test instanceof XmlQualifiedNameTest )
		{
			$this->nameTest = $test;
		}
		else if ( $test instanceof SequenceType && $test != SequenceTypes::$Node)
		{
			$this->typeTest = $test;
		}
	}

}

