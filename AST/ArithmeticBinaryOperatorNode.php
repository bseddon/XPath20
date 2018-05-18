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

use \lyquidity\XPath2\CoreFuncs;
use \lyquidity\XPath2\XPath2ResultType;
use \lyquidity\XPath2\Undefined;
use \lyquidity\XPath2\Proxy\ValueProxy;
use lyquidity\xml\MS\XmlNamespaceManager;
use lyquidity\XPath2\XPath2Context;
use lyquidity\xml\exceptions\DivideByZeroException;
use lyquidity\XPath2\XPath2Exception;

// internal delegate XPath2ResultType GetReturnTypeDelegate(XPath2ResultType resType1, XPath2ResultType resType2);

/**
 * ArithmeticBinaryOperatorNode (private)
 */
class ArithmeticBinaryOperatorNode extends AtomizedBinaryOperatorNode
{
	public static $CLASSNAME = "\lyquidity\XPath2\AST\ArithmeticBinaryOperatorNode";

	/**
	 * @var GetReturnTypeDelegate $_returnTypeDelegate
	 */
	private  $_returnTypeDelegate;

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param BinaryOperator $action
	 * @param object $node1
	 * @param object $node2
	 * @param GetReturnTypeDelegate $returnTypeDelegate (Default: null)
	 * @todo The last param should be ' = null ' but this causes all the arguments to be set to null!!
	 *       Obviously some problem with the PHP interpreter
	 */
	public  function __construct( $context, $action, $node1, $node2, $returnTypeDelegate )
	{
		parent::__construct( $context, $action, $node1, $node2, XPath2ResultType::Number );
		$this->_returnTypeDelegate = $returnTypeDelegate;
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
			// if ( ( $this->getAbstractNode(0) instanceof ExprNode && $this->getAbstractNode(0)->getCount() > 1 ) ||
			// 	 ( $this->getAbstractNode(1) instanceof ExprNode && $this->getAbstractNode(1)->getCount() > 1 )
			// )
			// 	throw XPath2Exception::withErrorCode( "XPTY0004", "One of the arguments is a sequence.  Arithmetic operations cannot be performed with a sequence." );

			$value1 = CoreFuncs::CastToNumber1( $this->getContext(), CoreFuncs::Atomize( $this->getAbstractNode(0)->Execute( $provider, $dataPool ) ) );
			if ( ! $value1 instanceof Undefined )
			{
				$value2 = CoreFuncs::CastToNumber1( $this->getContext(), CoreFuncs::Atomize( $this->getAbstractNode(1)->Execute( $provider, $dataPool ) ) );
				if ( ! $value2 instanceof Undefined )
				{
					return call_user_func( $this->_binaryOper, $provider, $value1, $value2 );
				}
			}
			return Undefined::getValue();
		}
		catch ( DivideByZeroException $ex )
		{
			throw XPath2Exception::withErrorCode( "", $ex->getMessage(), $ex );
		}
		catch ( \OverflowException $ex )
		{
			throw XPath2Exception::withErrorCode( "", $ex->getMessage(), $ex );
		}
	}

	/**
	 * GetReturnType
	 * @param object[] $dataPool
	 * @return XPath2ResultType
	 */
	public function GetReturnType( $dataPool )
	{
		if ( ! is_null( $this->_returnTypeDelegate ) )
		{
			/**
			 * @var XPath2ResultType $resType1
			 */
			$resType1 = $this->getAbstractNode(0)->GetReturnType( $dataPool );
			/**
			 * @var XPath2ResultType $resType2
			 */
			$resType2 = $this->getAbstractNode(1)->GetReturnType( $dataPool );
			return call_user_func( $this->_returnTypeDelegate, $resType1, $resType2 );
			// return $this->_returnTypeDelegate( $resType1, $resType2 );
		}
		return parent::GetReturnType( $dataPool );
	}

	public static function AdditionResultCall()
	{
		return array( __NAMESPACE__ . "\ArithmeticBinaryOperatorNode", "AdditionResult" );
	}

	/**
	 * AdditionResult
	 * @param XPath2ResultType $resType1
	 * @param XPath2ResultType $resType2
	 * @return XPath2ResultType
	 */
	public static function AdditionResult( $resType1, $resType2 )
	{
		if ( $resType1 == XPath2ResultType::Number || $resType2 == XPath2ResultType::Number )
			return XPath2ResultType::Number;

		if ( $resType1 == XPath2ResultType::DateTime && $resType2 == XPath2ResultType::Duration )
			return XPath2ResultType::DateTime;

		if ( $resType1 == XPath2ResultType::Duration )
		{
			if ($resType2 == XPath2ResultType::Duration)
				return XPath2ResultType::Duration;

			if ($resType2 == XPath2ResultType::DateTime)
				return XPath2ResultType::DateTime;
		}

		return XPath2ResultType::Any;
	}

	/**
	 * SubtractionResult
	 * @param XPath2ResultType $resType1
	 * @param XPath2ResultType $resType2
	 * @return XPath2ResultType
	 */
	public static function SubtractionResult( $resType1, $resType2 )
	{
		if ( $resType1 == XPath2ResultType::Number || $resType2 == XPath2ResultType::Number)
			return XPath2ResultType::Number;

		if ( $resType1 == XPath2ResultType::DateTime )
		{
			if ( $resType2 == XPath2ResultType::DateTime )
				return XPath2ResultType::Duration;

			if ( $resType2 == XPath2ResultType::Duration )
				return XPath2ResultType::DateTime;
		}

		if ( $resType1 == XPath2ResultType::Duration && $resType2 == XPath2ResultType::Duration )
			return XPath2ResultType::Duration;

		return XPath2ResultType::Any;
	}

	/**
	 * MultiplyResult
	 * @param XPath2ResultType $resType1
	 * @param XPath2ResultType $resType2
	 * @return XPath2ResultType
	 */
	public static function MultiplyResult( $resType1, $resType2 )
	{
		if ( ( $resType1 == XPath2ResultType::Duration && $resType2 == XPath2ResultType::Number ) ||
			 ( $resType1 == XPath2ResultType::Number && $resType2 == XPath2ResultType::Duration ) )
			return XPath2ResultType::Duration;

		if ( $resType1 == XPath2ResultType::Number && $resType2 == XPath2ResultType::Number )
			return XPath2ResultType::Number;

		return XPath2ResultType::Any;
	}

	/**
	 * DivisionResult
	 * @param XPath2ResultType $resType1
	 * @param XPath2ResultType $resType2
	 * @return XPath2ResultType
	 */
	public static function DivisionResult( $resType1, $resType2 )
	{
		if ($resType1 == XPath2ResultType::Duration && $resType2 == XPath2ResultType::Number)
			return XPath2ResultType::Duration;

		if ($resType1 == XPath2ResultType::Duration && $resType2 == XPath2ResultType::Duration)
			return XPath2ResultType::Number;

		if ($resType1 == XPath2ResultType::Number && $resType2 == XPath2ResultType::Number)
			return XPath2ResultType::Number;

		return XPath2ResultType::Any;
	}

	public static function tests()
	{
		$nsManager = new XmlNamespaceManager();
		$context = new XPath2Context( $nsManager );

		$callback = function( $provider, $arg1, $arg2 )
		{
			ValueProxy::Create( $arg1 ) + ValueProxy::Create( $arg2 );
		};
		$node = new ArithmeticBinaryOperatorNode( $context, $callback, 1, 2, array( ArithmeticBinaryOperatorNode::$CLASSNAME, "AdditionResult" ) );

		$result = $node->Execute( null, array() );
		$result = $node->GetReturnType( array() );

	}

}

?>
