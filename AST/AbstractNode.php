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

use lyquidity\XPath2;
use lyquidity\XPath2\XPath2Context;
use lyquidity\xml\exceptions\IndexOutOfRangeException;
use lyquidity\xml\exceptions\InvalidOperationException;
use lyquidity\xml\exceptions\ArgumentException;

/**
 * AbstractNode (abstract)
 */
class AbstractNode implements \IteratorAggregate
{
	/**
	 * _parent = null
	 * @var AbstractNode $_parent = null
	 */
	private $_parent = null;

	/**
	 * _childs = List<AbstractNode>
	 * @var array $_childs = List<AbstractNode>
	 */
	private $_childs = array();

	/**
	 * _context = null
	 * @var XPath2Context $_context = null
	 */
	private $_context = null;

	/**
	 * Constructor
	 * @param XPath2Context $context
	 */
	public function __construct( $context )
	{
		$this->_context = $context;
	}

	/**
	 * getCount
	 * @var int $Count
	 */
	public function getCount()
	{
		if ( is_null( $this->_childs ) )
			return 0;

		return count( $this->_childs );
	}

	/**
	 * getParent
	 * @return AbstractNode
	 */
	public function getParent()
	{
		return $this->_parent;
	}

	/**
	 * setParent
	 * @param AbstractNode $parent
	 * @return void
	 */
	public function setParent( $parent )
	{
		$this->_parent = $parent;
	}

	/**
	 * getContext
	 * @return XPath2Context
	 */
	public function getContext()
	{
		return $this->_context;
	}

	/**
	 * getIterator
	 * {@inheritDoc}
	 * @see IteratorAggregate::getIterator()
	 */
	public function getIterator()
	{
		if ( is_null( $this->_childs ) )
		{
			throw new InvalidOperationException();
		}

		return new \ArrayIterator( $this->_childs );
	}

	/**
	 * Return an array from the iterator
	 */
	public function toArray()
	{
		return iterator_to_array( $this->getIterator(), false );
	}

	/**
	 * getIsLeaf
	 * @return bool
	 */
	public function getIsLeaf()
	{
		return is_null( $this->_childs );
	}

	/**
	 * getAbstractNode
	 * @param int $index
	 * @throws IndexOutOfRangeException
	 * @return AbstractNode
	 */
	public function getAbstractNode( $index )
	{
		if ( ! isset( $this->_childs[ $index ] ) )
			throw new IndexOutOfRangeException();
		return $this->_childs[ $index ];
	}

	/**
	 * setAbstractNode
	 * @param int $index
	 * @param AbstractNode $node
	 * @throws IndexOutOfRangeException
	 */
	public function setAbstractNode( $index, $node )
	{
		if ( is_null( $this->_childs ) )
		{
			throw new IndexOutOfRangeException();
		}

		$this->_childs[ $index ] = $node;
	}

	/**
	 * Add a node
	 * @param AbstractNode|object $node
	 * @return void
	 */
	public function Add( $node )
	{
		if ( ! $node instanceof AbstractNode )
		{
			$node = $this->Create( $this->_context, $node );
		}

		if ( ! is_null( $node->getParent() ) )
		{
			throw new ArgumentException( "\$node" );
		}

		$node->setParent( $this );

		if ( is_null( $this->_childs ) )
		{
			$this->_childs = array(); // new List<AbstractNode>();
		}

		$this->_childs[] = $node;
	}

	/**
	 * AddRange
	 * @param array $nodes
	 * @return void
	 */
	public function AddRange( $nodes )
	{
		foreach ( $nodes as $key => $node )
		{
			$this->Add( $node );
		}
	}

	/**
	 * Bind
	 * @return void
	 */
	public function Bind()
	{
		if ( ! is_null( $this->_childs ) )
		{
			foreach ( $this->_childs as $node)
			{
				$node->Bind();
			}
		}
	}

	/**
	 * Create
	 * @param XPath2Context $context
	 * @param object $value
	 * @return AbstractNode
	 */
	public static function Create( $context, $value )
	{
		if ( $value instanceof PathStep )
		{
			return new PathExprNode( $context, $value );
		}

		if ( $value instanceof AbstractNode )
		{
			return $value;
		}

		return new ValueNode( $context, $value );
	}

	/**
	 * TraverseSubtree
	 * @param Function $action
	 * @return void
	 */
	public function TraverseSubtree( $action )
	{
		if ( ! is_null( $this->_childs ) )
		{
			foreach ( $this->_childs as $node )
			{
				$action( $node );
				$node->TraverseSubtree( $action );
			}
		}
	}

	/**
	 * IsContextSensitive
	 * @return bool
	 */
	public function IsContextSensitive()
	{
		if ( ! is_null( $this->_childs ) )
		{
			foreach ( $this->_childs as $node )
			{
				if ( $node->IsContextSensitive() )
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Execute
	 * @param IContextProvider $provider
	 * @param object[] $dataPool
	 * @return object
	 */
	public function Execute( $provider, $dataPool )
	{}

	/**
	 * GetReturnType
	 * @param object[] $dataPool
	 * @return XPath2ResultType
	 */
	public function GetReturnType( $dataPool )
	{
		return XPath2ResultType::Any;
	}

	/**
	 * GetItemType
	 * @param object[] $dataPool
	 * @return XPath2ResultType
	 */
	public function GetItemType( $dataPool )
	{
		return $this->GetReturnType( $dataPool );
	}

	/**
	 * IsEmptySequence
	 * @return bool
	 */
	public function IsEmptySequence()
	{
		return false;
	}
}



?>
