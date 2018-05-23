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

namespace lyquidity\XPath2\AST;

use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\XPath2ResultType;
use lyquidity\XPath2\IContextProvider;
use lyquidity\XPath2\CoreFuncs;
use lyquidity\XPath2\TrueValue;

/**
 * IfNode (final)
 */
class IfNode extends AbstractNode
{
	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param object $cond
	 * @param object $thenBranch
	 * @param object $elseBranch
	 */
	public function __construct( $context, $cond, $thenBranch, $elseBranch )
	{
		parent::__construct( $context );
		$this->Add( $cond );
		$this->Add( $thenBranch );
		$this->Add( $elseBranch );
	}

	/**
	 * Execute
	 * @param IContextProvider $provider
	 * @param object[] $dataPool
	 * @return object
	 */
	public function Execute( $provider, $dataPool )
	{
		if ( CoreFuncs::BooleanValue( $this->getAbstractNode(0)->Execute( $provider, $dataPool ) ) instanceof TrueValue )
			return $this->getAbstractNode(1)->Execute( $provider, $dataPool );
		else
			return $this->getAbstractNode(2)->Execute( $provider, $dataPool );
	}

	/**
	 * GetReturnType
	 * @param object[] $dataPool
	 * @return XPath2ResultType
	 */
	public function GetReturnType( $dataPool )
	{
		/**
		 * @var XPath2ResultType $res1
		 * @var XPath2ResultType $res2
		 */
		$res1 = $this->getAbstractNode(1)->GetReturnType( $dataPool );
		$res2 = $this->getAbstractNode(2)->GetReturnType( $dataPool );
		if ( $res1 == $res2 )
			return $res1;
		return XPath2ResultType::Any;
	}

}



?>
