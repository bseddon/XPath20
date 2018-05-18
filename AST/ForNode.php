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

use lyquidity\XPath2\VarName;
use lyquidity\XPath2\Iterator\ForIterator;
use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\XPath2\XPath2ResultType;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\XPath2\NameBinder\ReferenceLink;
use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\IContextProvider;
use lyquidity\XPath2\Undefined;
use lyquidity\xml\QName;

/**
 * ForNode (final)
 */
class ForNode extends AbstractNode
{
	/**
	 * @var VarName $_varName
	 */
	private $_varName;

	/**
	 * @var ReferenceLink $_varRef
	 */
	private $_varRef;

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param VarName $varName
	 * @param object $expr
	 */
	public  function __construct( $context, $varName, $expr )
	{
		parent::__construct( $context );
		$this->_varName = $varName;
		$this->Add( $expr );
	}

	/**
	 * AddTail
	 * @param object $expr
	 * @return void
	 */
	public function AddTail( $expr )
	{
		if ( $this->getCount() == 1 )
			$this->Add( $expr );
		else
			$this->getAbstractNode(1)->AddTail( $expr );
	}

	/**
	 * Get the VarName as a QName
	 */
	public function getQNVarName()
	{
		return \lyquidity\xml\qname( $this->_varName->ToString(), $this->getContext()->NamespaceManager->getNamespaces(), true );
	}

	/**
	 * Bind
	 * @return void
	 */
	public function Bind()
	{
		$this->getAbstractNode(0)->Bind();
		/**
		 * @var QName $qname
		 */
		$qname = $this->getQNVarName();
		$this->_varRef = $this->getContext()->RunningContext->NameBinder->PushVar( $qname );
		$this->getAbstractNode(1)->Bind();
		$this->getContext()->RunningContext->NameBinder->PopVar();
	}

	/**
	 * Execute
	 * @param IContextProvider $provider
	 * @param object[] $dataPool
	 * @return object
	 */
	public function Execute( $provider, $dataPool )
	{
		return new ForIterator( $this, $provider, $dataPool, XPath2NodeIterator::Create( $this->getAbstractNode(0)->Execute( $provider, $dataPool ) ) );
	}

	/**
	 * GetReturnType
	 * @param object[] $dataPool
	 * @return XPath2ResultType
	 */
	public function GetReturnType( $dataPool )
	{
		return XPath2ResultType::NodeSet;
	}

	/**
	 * MoveNext
	 * @param IContextProvider $provider
	 * @param object[] $dataPool
	 * @param XPathItem $curr
	 * @param object $res
	 * @return bool
	 */
	public function MoveNext( $provider, $dataPool, $curr, &$res )
	{
		if ( ! $curr instanceof XPathItem || $curr->getIsNode() )
			$this->_varRef->Set( $dataPool, $curr );
		else
			// BMS 2018-03-02 Don't think it is necessary to convert the XPath2Item
			//				  to its type at this stage becaUse of the changed made
			//				  to handle XPath2Item more natively
			$this->_varRef->Set( $dataPool, $curr /* ->getTypedValue() */ );

		$res = $this->getAbstractNode(1)->Execute( $provider, $dataPool );
		if ( ! $res instanceof Undefined )
			return true;
		return false;
	}
}

?>
