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

namespace lyquidity\XPath2;

$xmlSchemaPath = isset( $_ENV['XML_LIBRARY_PATH'] )
	? $_ENV['XML_LIBRARY_PATH']
	: ( defined( 'XML_LIBRARY_PATH' ) ? XML_LIBRARY_PATH : __DIR__ . "/../xml/" );

require_once $xmlSchemaPath . '/bootstrap.php';

// use lyquidity\xml\schema\SchemaTypes;

/**
 * Load a class by name
 * @param string $className
 */
function autoload( $className )
{
	$dirname = dirname( __FILE__ );

	// The namespace of all classes start with \lyquidity
	if ( strpos( $className, "lyquidity\\" ) === false ) return;

	$fileName  = '';
	$base = "lyquidity\\XPath2";

	// if ( $className == "$base\\YYParser" )
	// {
	// 	require_once __DIR__ . '/XPath.php';
	// 	return true;
	// }

	$className = ltrim( $className, '\\' );

	$namespace = '';
	if ( $lastNsPos = strrpos( $className, '\\' ) ) {
		 $namespace = substr( $className, 0, $lastNsPos );
		 $className = substr( $className, $lastNsPos + 1 );

		 $path = "";
		 // if ( $namespace == "lyquidity" )
		 // {
		 // 	$namespace = $base . "\\lyquidity";
		 // }

		 $namespace = str_replace( $base, "", $namespace );
		 $namespace  = str_replace( '\\', DIRECTORY_SEPARATOR, $namespace ) . DIRECTORY_SEPARATOR;
	}

	$fileName = "$dirname$namespace$className.php";

	if ( ! file_exists( $fileName ) ) return;

	require_once $fileName;
}

spl_autoload_register( '\\lyquidity\\XPath2\\autoload' );


