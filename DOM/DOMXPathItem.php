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
 * along with this program.  If not, see <http: *www.gnu.org/licenses/>.
 *
*/

namespace lyquidity\XPath2\DOM;

// use lyquidity\xml\xpath\XPathItem;

class DOMXPathItem extends DOMXPathNavigator
{
	/**
	 * Test functions for this class
	 * @param \XBRL_Instance $instance
	 */
	public static function Test( $instance )
	{
		$root = dom_import_simplexml( $instance->getInstanceXml() );

		echo get_class($root) . "\n";
		$node = $root->firstChild;
		while ( ($node = $node->nextSibling) != null )
		{
			if ( $node instanceof \DOMElement )
			{
				// echo "{$node->localName} ";
				try
				{
					$xpathNode = new \lyquidity\XPath2\DOM\DOMXPathItem( $node );
					$value = $xpathNode->getValue();
					$valueType = $xpathNode->getValueType();
					$typedValue = $xpathNode->getTypedValue();
					$xmlType = $xpathNode->getXmlType();
					$bool = $xpathNode->getValueAsBoolean();
					// $value = $xpathNode->ValueAs( Type::decimal );
					echo "{$node->localName}: $value\n";
				}
				catch (\Exception $ex)
				{
					echo "\nError ({$node->localName}): {$ex->getMessage()}\n";
				}
			}

		}
	}
}
