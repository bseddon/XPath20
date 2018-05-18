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

use lyquidity\XPath2\Value\Integer;
use lyquidity\XPath2\ContextProvider;
use lyquidity\XPath2\Undefined;
use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\MS\XmlTypeCardinality;
use lyquidity\XPath2\CoreFuncs;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\XPath2\XPath2Item;
use lyquidity\XPath2\Proxy\ValueProxy;
use lyquidity\XPath2\TrueValue;
use lyquidity\XPath2\Iterator\NodeIterator;
use lyquidity\XPath2\XPath2ResultType;
use lyquidity\XPath2\SequenceType;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\XPath2Exception;

/**
 * FilterExprNode (final)
 */
class FilterExprNode extends AbstractNode
{
	/**
	 * @var bool $m_contextSensitive
	 */
	private $m_contextSensitive;

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param object $src
	 * @param array $nodes
	 */
	public function __construct( $context, $src, $nodes )
	{
		parent::__construct($context);

		$m_contextSensitive = false;
		$this->Add( $src );
		$this->AddRange( $nodes );
	}

	/**
	 * CreateEnumerator
	 * @param object[] $dataPool
	 * @param AbstractNode $expr
	 * @param XPath2NodeIterator $baseIter
	 * @return IEnumerable<XPathItem>
	 */
	private function CreateEnumerator( $dataPool, $expr, $baseIter )
	{
		/**
		 * @var XPath2NodeIterator $iter
		 */
		$iter = $baseIter->CloneInstance();

		if ( $expr instanceof ValueNode )
		{
			/**
			 * @var ValueNode $numexpr
			 */
			$numexpr = $expr;

			if ( is_numeric( $numexpr->getContent() ) || $numexpr->getContent() instanceof Integer )
			{
				/**
				 * @var Integer $pos
				 */
				// $pos = Integer::fromValue( $numexpr->getContent() );
				$pos = $numexpr->getContent(); // Convert::ToInt( $numexpr->getContent(), null );
				/**
				 * @var XPathItem $item
				 */
				foreach ( $iter as $item )
				{
					if ( $pos == 1)
					{
						yield $item;
						break;
					}
					else
						$pos--;
				}


				return;
			}
		}

		/**
		 * @var ContextProvider $provider
		 */
		$provider = ContextProvider::fromIterator( $iter );
		$res = Undefined::getValue();
		while ( $iter->MoveNext() )
		{
			if ( $this->m_contextSensitive || $res instanceof Undefined )
			{
				$res = $expr->Execute( $provider, $dataPool );
			}

			if ( $res instanceof Undefined )
			{
				if ( ! $this->m_contextSensitive )
				{
					break;
				}
				continue;
			}

			/**
			 * @var XPathItem $item
			 */
			$item = null;

			if ( $res instanceof XPath2NodeIterator )
			{
				/**
				 * @var XPath2NodeIterator $iter2
				 */
				$iter2 = $res->CloneInstance();

				if ( ! $iter2->MoveNext() )
				{
					continue;
				}

				/**
				 * @var XPathItem $item
				 */
				$item = CoreFuncs::CloneInstance( $iter2->getCurrent() );

				if ( ! $item->getIsNode() && $iter2->MoveNext() )
				{
				    throw XPath2Exception::withErrorCodeAndParams( "FORG0006", Resources::FORG0006,
						array(
							"fn:boolean()",
							SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::AnyAtomicType, XmlTypeCardinality::OneOrMore )
						)
					);
				}
			}
			else
			{
				$item = $res instanceof XPathItem
					? $item = $res
				    : $item = XPath2Item::fromValue( $res );
			}

			if ( $item->getIsNode() )
			{
				yield $iter->getCurrent();
			}
			else
			{
				if ( ValueProxy::IsNumericValue( $item->getValueType() ) )
				{
				    if ( CoreFuncs::OperatorEq( $iter->getCurrentPosition() + 1, $item->GetTypedValue() ) instanceof TrueValue)
				    {
				        yield $iter->getCurrent();
				        if ( ! $this->m_contextSensitive )
				        {
				        	break;
				        }
				    }
				}
				elseif ( CoreFuncs::GetBooleanValue( $item ) )
				{
					yield $iter->getCurrent();
				}
			}
		}
	}

	/**
	 * IsContextSensitive
	 * @return bool
	 */
	public function IsContextSensitive()
	{
		return $this->getAbstractNode(0)->IsContextSensitive();
	}

	/**
	 * Bind
	 * @return void
	 */
	public function Bind()
	{
		parent::Bind();
		$this->m_contextSensitive = $this->getAbstractNode(1)->IsContextSensitive();
	}

	/**
	 * Execute
	 * @param IContextProvider $provider
	 * @param object[] $dataPool
	 * @return object
	 */
	public function Execute( $provider, $dataPool )
	{
		/**
		 * @var XPath2NodeIterator $iter
		 */
		$iter = XPath2NodeIterator::Create( $this->getAbstractNode(0)->Execute( $provider, $dataPool ) );
		for ( $k = 1; $k < $this->getCount(); $k++)
		{
			// $iter = new NodeIterator( $this->CreateEnumerator( $dataPool, $this->getAbstractNode( $k ), $iter ) );
			$node = $this->getAbstractNode( $k );
			$iter = new NodeIterator( function() use( $dataPool, $node, $iter ) { return $this->CreateEnumerator( $dataPool, $node, $iter ); } );
		}
		return $iter;
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

}



?>
