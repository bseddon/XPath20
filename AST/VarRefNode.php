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

use lyquidity\XPath2\IContextProvider;
use lyquidity\XPath2\NameBinder\ReferenceLink;
use lyquidity\XPath2\VarName;
use lyquidity\XPath2\CoreFuncs;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\Value\QNameValue;
use lyquidity\xml\QName;
use lyquidity\XPath2\XPath2Exception;

/**
 * VarRefNode (final)
 */
class VarRefNode extends AbstractNode
{
	/**
	 * The qname of the variable
	 * @var VarName $_varName
	 */
	private $_varName;

	/**
	 * ReferenceLink instance for the data pool
	 * @var ReferenceLink $_varRef
	 */
	private $_varRef;

	/**
	 * getVarRef
	 * @return ReferenceLink
	 */
	public function getVarRef()
	{
		return $this->_varRef;
	}

	/**
	 * getQNVarName
	 * @return QName
	 */
	public function getQNVarName()
	{
		/**
		 * @var QName $qname
		 */
		$qname = \lyquidity\xml\qname( $this->_varName->ToString(), $this->getContext()->NamespaceManager->getNamespaces() );
		$qnValue = QNameValue::fromNCName( $this->_varName->ToString(), $this->getContext()->NamespaceManager );
		return $qname;
	}
	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param VarName $varRef
	 */
	public  function __construct( $context, $varRef)
	{
		parent::__construct( $context );
		$this->_varName = $varRef;
	}

	/**
	 * Bind
	 * @return void
	 */
	public function Bind()
	{
		/**
		 * @var QName $qname
		 */
		$qname = \lyquidity\xml\qname( $this->_varName->ToString(), $this->getContext()->NamespaceManager->getNamespaces(), true );
		if ( is_null( $qname ) )
		{
			throw XPath2Exception::withErrorCodeAndParam( "XPST0081", Resources::XPST0081, $this->_varName->Prefix );
		}

		$this->_varRef = $this->getContext()->RunningContext->NameBinder->VarIndexByName( $qname );
	}

	/**
	 * Execute
	 * @param IContextProvider $provider
	 * @param array $dataPool
	 * @return object
	 */
	public function Execute( $provider, $dataPool )
	{
		return $this->_varRef->Get( $dataPool );
	}

	/**
	 * GetReturnType
	 * @param array $dataPool
	 * @return XPath2ResultType
	 */
	public function GetReturnType( $dataPool )
	{
		return CoreFuncs::GetXPath2ResultTypeFromValue( $this->_varRef->Get( $dataPool ) );
	}

}



?>
