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

use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\XPath2ResultType;
use lyquidity\XPath2\IContextProvider;
use lyquidity\XPath2\CoreFuncs;
use lyquidity\xml\exceptions\DivideByZeroException;
use lyquidity\XPath2\XPath2Exception;
use lyquidity\XPath2\Undefined;

/**
 * ArithmeticUnaryOperatorNode (private)
 */
class ArithmeticUnaryOperatorNode extends AtomizedUnaryOperatorNode
{
	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param \Closure $action
	 * @param object $node
	 */
	public function __construct( $context, $action, $node )
	{
		parent::__construct( $context, $action, $node, XPath2ResultType::Number );
	}

	/**
	 * Execute
	 * @param IContextProvider $provider
	 * @param object[] $dataPool
	 * @return object
	 */
	public function Execute( $provider, $dataPool )
	{
		try
		{
			$value = CoreFuncs::CastToNumber1( $this->getContext(), CoreFuncs::Atomize( $this->getAbstractNode(0)->Execute( $provider, $dataPool ) ) );
			if ( ! $value instanceof Undefined )
				return call_user_func( $this->_unaryOper, $provider, $value );
			return Undefined::getValue();
		}
		catch ( DivideByZeroException $ex )
		{
			throw XPath2Exception::withErrorCode( "", $ex->getMessage(), $ex );
		}
		catch (\OverflowException $ex)
		{
			throw XPath2Exception::withErrorCode( "", $ex->getMessage(), $ex );
		}
	}

}



?>
