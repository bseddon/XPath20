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

namespace lyquidity\XPath2\AST;

use \lyquidity\XPath2;
use lyquidity\XPath2\CoreFuncs;
use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\IContextProvider;
use lyquidity\XPath2\XPath2ResultType;
use lyquidity\XPath2\Undefined;
use lyquidity\XPath2\XPath2Item;

/**
 * ValueNode (final)
 */
class ValueNode extends AbstractNode
{
	public static $CLASSNAME = "lyquidity\XPath2\AST\ValueNode";

	/**
	 * @var object $_content
	 */
	private $_content = null;

	/**
	 * @var object $Content
	 */
	public function getContent()
	{
		if ( ! isset( $this->_content ) ) return null;
		return $this->_content;
	}

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param object $content
	 */
	public function __construct( $context, $content )
	{
		parent::__construct( $context );
		$this->_content = $content;
	}

	/**
	 * Execute
	 * @param IContextProvider $provider
	 * @param object[] $dataPool
	 * @return object
	 */
	public function Execute( $provider, $dataPool )
	{
		// BMS 2017-12-28 Change to return an iterable type
		//                Seems like this should be OK but perhaps it will cause conformance problems
		// BMS 2018-01-24 Indeed it did cause conformance problems but changes have been made to fix
		//				  them and the XPath 2.0 minimal conformance tests are successfully passed.
		return $this->_content instanceof Undefined
			? $this->_content
			: XPath2Item::fromValue( $this->_content );
		return $this->getContent();
	}

	/**
	 * GetReturnType
	 * @param array $dataPool
	 * @return XPath2ResultType
	 */
	public function GetReturnType($dataPool)
	{
		return CoreFuncs::GetXPath2ResultTypeFromValue( $this->_content );
	}

	/**
	 * IsEmptySequence
	 * @return bool
	 */
	public function IsEmptySequence()
	{
		return $this->_content instanceof Undefined;
	}

}



?>
