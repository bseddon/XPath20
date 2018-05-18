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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace lyquidity\XPath2;

abstract class XPathExpression
{
	/**
	 * @var string $Expression
	 */
	public $Expression;

	/**
	 * @var XPathResultType
	 */
	public $ReturnType;

	/**
	 * @param string $xpath
	 * @param IXmlNamespaceResolver $nsResolver
	 * @return XPathExpression
	 */
	public function Compile( $xpath, $nsResolver = null ) {}

	// /**
	//  * @param string $expr
	//  * @param IComparer $comparer
	//  * @return void
	//  */
	// public function AddSort( $expr, $comparer) {}

	// /**
	//  *
	//  * @param object $expr
	//  * @param XmlSortOrder $order
	//  * @param XmlCaseOrder $caseOrder
	//  * @param string $lang
	//  * @param XmlDataType $dataType
	//  */
	// public function AddSort( $expr, $order, $caseOrder, $lang, $dataType ) {}

	/**
	 * @return XPathExpression
	 */
	public function Clone() {}

	/**
	 *
	 * @param XmlNamespaceManager $nsManager
	 * @return void
	 */
	public function SetContext( $nsManager) {}

	// /**
	//  *
	//  * @param IXmlNamespaceResolver $nsResolver
	//  * @return void
	//  */
	// public function SetContext( $nsResolver) {}
}