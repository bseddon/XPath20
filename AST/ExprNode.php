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

use lyquidity\XPath2\Iterator\ExprIterator;
use lyquidity\XPath2\XPath2ResultType;
use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\IContextProvider;

/**
 * ExprNode (final)
 */
class ExprNode extends AbstractNode
{
	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param object $node
	 */
	public function __construct( $context, $node )
	{
		parent::__construct( $context );
		$this->Add( $node );
	}

	/**
	 * Execute
	 * @param IContextProvider $provider
	 * @param object[] $dataPool
	 * @return object
	 */
	public function Execute($provider, $dataPool)
	{
		if ( $this->getCount() == 1 )
		    return $this->getAbstractNode(0)->Execute( $provider, $dataPool );
		return ExprIterator::fromOwner( $this, $provider, $dataPool );
	}

	/**
	 * GetReturnType
	 * @param object[] $dataPool
	 * @return XPath2ResultType
	 */
	public function GetReturnType( $dataPool )
	{
		if ( $this->getCount() == 1)
		    return $this->getAbstractNode(0)->GetReturnType( $dataPool );
		return XPath2ResultType::NodeSet;
	}

	/**
	 * GetItemType
	 * @param object[] $dataPool
	 * @return XPath2ResultType
	 */
	public function GetItemType( $dataPool )
	{
		/**
		 * @var XPath2ResultType $resType
		 */
		$resType = XPath2ResultType::Any;

		if ( ! $this->getCount() )
		{
			return $resType;
		}

		/**
		 * @var AbstractNode child
		 */
		foreach ( $this->getIterator() as $child )
		{
		    if ( ! $child->IsEmptySequence() )
		    {
				/**
				 * @var XPath2ResultType $resType
				 */
		        $itemType = $child->GetItemType( $dataPool );
		        if ( $itemType != $resType )
		        {
		            if ( $resType != XPath2ResultType::Any )
		                return XPath2ResultType::Any;
		            $resType = $itemType;
		        }
		    }
		}
		return $resType;
	}
}



?>
