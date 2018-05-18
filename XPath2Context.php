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

namespace lyquidity\XPath2;

use lyquidity\XPath2\DOM\DOMSchemaSet;
use lyquidity\xml\MS\IXmlNamespaceResolver;
use lyquidity\xml\MS\XmlNameTable;
use lyquidity\XPath2\DOM\XmlSchemaSet;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\MS\XmlNamespaceManager;
use lyquidity\xml\MS\XmlReservedNs;
use lyquidity\xml\schema\SchemaTypes;
use lyquidity\xml\MS\XmlNamespaceScope;

/**
 * XPath2Context (public)
 */
class XPath2Context
{
	/**
	 * Constructor
	 * @param IXmlNamespaceResolver $nsManager
	 */
	public  function __construct( $nsManager )
	{
		$this->NameTable = new XmlNameTable();
		$this->NamespaceManager = is_null( $nsManager ) ? new XmlNamespaceManager( /* $this->NameTable */ ) : $nsManager;
		$this->SchemaSet = new DOMSchemaSet( $this->NameTable );

		if ( ! is_null( $nsManager ) )
		{
			foreach ( $nsManager->getNamespacesInScope( XmlNamespaceScope::ExcludeXml ) as $prefix => $ns )
			{
				$this->NamespaceManager->addNamespace( $prefix, $ns );
			}
		}

		if ( ! $this->NamespaceManager->hasNamespace("xs") )
		{
			$this->NamespaceManager->addNamespace("xs", XmlReservedNs::xs);
		}

		// BMS Hack because a mistake in XBRL_Types that makes xs: types present as xsd: types
		// BMS 2018-04-09 Should not be required any more
		if ( ! $this->NamespaceManager->hasNamespace("xsd") )
		{
			$this->NamespaceManager->addNamespace("xsd", XmlReservedNs::xs);
		}

		if ( ! $this->NamespaceManager->hasNamespace("xsi") )
		{
			$this->NamespaceManager->addNamespace("xsi", XmlReservedNs::xsi);
		}

		if ( ! $this->NamespaceManager->hasNamespace("fn") )
		{
			$this->NamespaceManager->addNamespace("fn", XmlReservedNs::xQueryFunc);
		}

		if ( ! $this->NamespaceManager->hasNamespace("op") )
		{
			$this->NamespaceManager->addNamespace("op", XmlReservedNs::xQueryOp);
		}

		if ( ! $this->NamespaceManager->hasNamespace("local") )
		{
			$this->NamespaceManager->addNamespace("local", XmlReservedNs::xQueryLocalFunc);
		}
	}

	/**
	 * @var XPath2RunningContext $RunningContext
	 */
	public  $RunningContext;

	/**
	 * @var XmlNameTable $NameTable
	 */
	public  $NameTable;

	/**
	 * @var XmlNamespaceManager $NamespaceManager
	 */
	public  $NamespaceManager;

	/**
	  * @var XmlSchemaSet $SchemaSet
	  */
	public  $SchemaSet;

	public static function tests( $instance )
	{
		$nsManager = new XmlNamespaceManager( SchemaTypes::getInstance()->getProcessedSchemas() );
		$context = new XPath2Context( $nsManager );

		$schemaSet = $context->SchemaSet->getGlobalType( XmlSchema::$YearMonthDuration->QualifiedName );
		$schemaSet = $context->SchemaSet->getGlobalType( XmlSchema::$YearMonthDuration->QualifiedName );
		$qname = \lyquidity\xml\qname( "xbrli:decimalItemType", SchemaTypes::getInstance()->getProcessedSchemas() );

		$schemaSet = $context->SchemaSet->getGlobalType( $qname );

	}
}


?>
