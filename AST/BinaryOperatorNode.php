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

use lyquidity\XPath2\XPath2ResultType;
use lyquidity\XPath2\XPath2Context;
use lyquidity\xml\MS\XmlNamespaceManager;

// internal delegate object BinaryOperator(IContextProvider provider, object arg1, object arg2);

/**
 * BinaryOperatorNode (private)
 */
class BinaryOperatorNode extends AbstractNode
{
	public static $CLASSNAME = "lyquidity\XPath2\AST\BinaryOperatorNode";

	/**
	 * @var BinaryOperator $_binaryOper
	 */
	protected $_binaryOper = null;

	/**
	 * @var XPath2ResultType $_resultType
	 */
	private $_resultType;

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param BinaryOperatorNode $action (provider, arg1, arg2)
	 * @param object $node1
	 * @param object $node2
	 * @param XPath2ResultType $resultType
	 */
	public function __construct( $context, $action, $node1, $node2, $resultType )
	{
		parent::__construct( $context );

		$this->_binaryOper = $action;
		$this->_resultType = $resultType;

		$this->Add( $node1 );
		$this->Add( $node2 );
	}

	/**
	 * Execute
	 * @param IContextProvider $provider
	 * @param object[] $dataPool
	 * @return object
	 */
	public function Execute( $provider, $dataPool )
	{
		return call_user_func(
			$this->_binaryOper,
			$provider,
			$this->getAbstractNode(0)->Execute( $provider, $dataPool ),
			$this->getAbstractNode(1)->Execute( $provider, $dataPool )
		);
	}

	/**
	 * GetReturnType
	 * @param object[] $dataPool
	 * @return XPath2ResultType
	 */
	public function GetReturnType( $dataPool )
	{
		return $this->_resultType;
	}

}

function TestBinaryOperatorNode()
{
	$nsManager = new XmlNamespaceManager();
	$context = new XPath2Context( $nsManager );
	$node = new BinaryOperatorNode( $context, function( $provider, $arg1, $arg2 ) {}, 1, 2, XPath2ResultType::Number );

	echo "Number of children: {$node->getCount()}\n";
	foreach ( $node as $child )
	{
		echo "{$child->getContent()}\n";
	}

	$node->Bind();
	$node->TraverseSubtree( function( $node ) { echo "{$node->getContent()}\n"; } );
	$node->IsContextSensitive();

	$valueNode = new ValueNode( $context, "xxx" );
	$node->Add( $valueNode );

}


?>
