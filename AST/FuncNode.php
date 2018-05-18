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

use lyquidity\xml\MS\XmlReservedNs;
use lyquidity\XPath2\XPath2ResultType;
use lyquidity\XPath2\XPathFunctionDef;
use lyquidity\XPath2\FunctionTable;
use lyquidity\XPath2\Proxy\ValueProxy;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\IContextProvider;
use lyquidity\XPath2\XPath2Exception;

/**
 * FuncNode (final)
 */
class FuncNode extends AbstractNode
{
	/**
	 * @var String $_name
	 */
	private $_name;

	/**
	 * @var String $_ns
	 */
	private $_ns;

	/**
	 * @var XPathFunctionDef $_func
	 */
	private $_func;

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param string $name
	 * @param string $ns
	 */
	public function __construct( $context )
	{
		parent::__construct( $context );
	}

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param string $name
	 * @param string $ns
	 */
	public static function withoutArgs( $context, $name, $ns, $raise = true )
	{
		$result = new FuncNode( $context );
		// TODO When the function table is implemented
		$result->_func = FunctionTable::getInstance()->Bind( $name, $ns, 0 );
		if ( is_null( $result->_func ) )
		{
			if ( ! $raise ) return null;
			throw XPath2Exception::withErrorCodeAndParams( "XPST0017", Resources::XPST0017, array( $name, 0, $ns ) );
		}
		$result->_name = $name;
		$result->_ns = $ns;
		return $result;
	}

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param string $name
	 * @param string $ns
	 * @param array $args
	 */
	public static function withArgs( $context, $name, $ns, $args, $raise = true )
	{
		$result = new FuncNode( $context );
		$result->_func = FunctionTable::getInstance()->Bind( $name, $ns, count( $args ) );
		if ( is_null( $result->_func ) )
		{
			 if ( ! $raise ) return null;
			throw XPath2Exception::withErrorCodeAndParams( "XPST0017", Resources::XPST0017, array( $name, 0, $ns ) );
		}
		$result->_name = $name;
		$result->_ns = $ns;
		$result->AddRange( $args );
		return $result;
	}

	/**
	 * Bind
	 * @return void
	 */
	public function Bind()
	{
		if ( $this->getCount() > 0)
		{
			if ( $this->getAbstractNode(0) instanceof PathExprNode )
			{
				/**
				 * @var PathExprNode $pathExpr
				 */
				$pathExpr = $this->getAbstractNode(0);
				if ( in_array( $this->_name, FuncNode::$s_aggregates ) && $this->_ns == XmlReservedNs::xQueryFunc )
				{
					$pathExpr->Unordered = true;
				}
			}
		}
		parent::Bind();
	}

	/**
	 * IsContextSensitive
	 * @return bool
	 */
	public function IsContextSensitive()
	{
		return ( $this->getCount() == 0 && in_array( $this->_name, FuncNode::$s_contextDs ) && $this->_ns == XmlReservedNs::xQueryFunc)  ||
				parent::IsContextSensitive();
	}

	/**
	 * Execute
	 * @param IContextProvider $provider
	 * @param object[] $dataPool
	 * @return object
	 */
	public function Execute( $provider, $dataPool )
	{
		$args = array();
		for ( $k = 0; $k < $this->getCount(); $k++)
			$args[ $k ] = ValueProxy::Unwrap( $this->getAbstractNode( $k )->Execute( $provider, $dataPool ) );
		return $this->_func->Invoke( $this->getContext(), $provider, $args );
	}

	/**
	 * GetReturnType
	 * @param object[] $dataPool
	 * @return XPath2ResultType
	 */
	public function GetReturnType( $dataPool )
	{
		if ( $this->_func->ResultType == XPath2ResultType::Any && $this->getCount() > 0 )
		{
			/**
			 * @var XPath2ResultType $resType
			 */
			$resType = $this->getAbstractNode(0)->GetItemType( $dataPool );
			if ( $resType == XPath2ResultType::NodeSet )
				return XPath2ResultType::Any;
			return $resType;
		}
		return $this->_func->ResultType;
	}

	/**
	 * GetItemType
	 * @param object[] $dataPool
	 * @return XPath2ResultType
	 */
	public function GetItemType( $dataPool )
	{
		if ( $this->_func->Name == "string-to-codepoints" )
			return XPath2ResultType::Number;
		return parent::GetItemType( $dataPool );
	}

	/**
	 * @var array $s_aggregates
	 */
	private static $s_aggregates;

	/**
	 * @var array $s_contextDs
	 */
	private static  $s_contextDs;

	/**
	 * Constructor
	 */
	static function __static()
	{
		self::$s_aggregates = array( "sum", "count", "avg", "min", "max",  "distinct-values", "empty", "exits" );
		self::$s_contextDs = array( "name", "local-name", "namespace-uri", "base-uri", "position", "last", "string-length", "normalize-space", "number" );
	}

}

FuncNode::__static();

?>
