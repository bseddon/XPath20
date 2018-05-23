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

namespace lyquidity\XPath2\AST;

use lyquidity\XPath2\XPath2ResultType;
use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\IContextProvider;

// internal delegate object UnaryOperator(IContextProvider provider, object arg);

/**
 * UnaryOperatorNode (private)
 */
class UnaryOperatorNode extends AbstractNode
{
	/**
	 * The action to perform (set in the constructor)
	 * @var UnaryOperatorNode $_unaryOper
	 */
	protected $_unaryOper;

	/**
	 * The result type (set in the constructor)
	 * @var XPath2ResultType $_resultType
	 */
	private $_resultType;

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param UnaryOperatorNode $action
	 * @param object $node
	 * @param XPath2ResultType $resultType
	 */
	public function __construct( $context, $action, $node, $resultType )
	{
		parent::__construct( $context );

		$this->_unaryOper = $action;
		$this->_resultType = $resultType;

		$this->Add( $node );
	}

	/**
	 * Execute
	 * @param IContextProvider $provider
	 * @param object[] $dataPool
	 * @return object
	 */
	public function Execute( $provider, $dataPool )
	{
		return call_user_func( $this->_unaryOper, $provider, $this->getAbstractNode(0)->Execute( $provider, $dataPool ) );
	}

	/**
	 * GetReturnType
	 * @param array $dataPool
	 * @return XPath2ResultType
	 */
	public function GetReturnType($dataPool)
	{
		return $this->_resultType;
	}

}



?>
