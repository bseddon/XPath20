<?php
/**
 * XPath 2.0 for PHP
 *  _					   _	 _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *	 |___/	  |_|					 |___/
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
 * along with this program.  If not, see <http: *www.gnu.org/licenses/>.
 *
 */

namespace lyquidity\XPath2\DOM;

use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\QName;
use lyquidity\xml\exceptions\XmlSchemaException;
use lyquidity\xml\exceptions\ArgumentNullException;
use lyquidity\xml\exceptions\NotSupportedException;

/**
 * Contains a cache of XML Schema definition language (XSD) schemas.
 */
class XmlSchemaSet
{
	/**
	 * Holds the nametable passed to the constructor
	 * @var XmlNameTable
	 */
	private $nameTable = null;

	/**
	 * A list of schemas added to this set
	 * @var array
	 */
	private $schemas = array();

	/**
	 * A list of global attributes.  Not used.  In practice the global attributes can be found in lyquidity\xml\SchemaTypes
	 * @var array
	 */
	private $globalAttributes = array();

	/**
	 * A list of global elements.  Not used.  In practice the global elements can be found in lyquidity\xml\SchemaTypes
	 * @var array
	 */
	private $globalElements = array();

	/**
	 * A list of global types.  Not used.  In practice the global types can be found in lyquidity\xml\SchemaTypes
	 * @var array
	 */
	private $globalTypes = array();

	/**
	 * Initializes a new instance of the XmlSchemaSet class with the specified System.Xml.XmlNameTable.
	 *
	 * @param XmlNameTable $nameTable The System.Xml.XmlNameTable object to use.
	 * @throws
	 *   \lyquidity\xml\exceptions\ArgumentNullException  The System.Xml.XmlNameTable object passed as a parameter is null.
	 */
	public function __construct( $nameTable )
	{
		if ( is_null( $nameTable ) ) throw new \lyquidity\xml\exceptions\ArgumentNullException();
		$this->nameTable = $nameTable;
	}

	/**
	 * Gets the XmlSchemaCompilationSettings for the XmlSchemaSet.
	 * Currently this is not suppported.
	 *
	 * @return XmlSchemaCompilationSettings		The XmlSchemaCompilationSettings for the XmlSchemaSet.
	 *											The default is an XmlSchemaCompilationSettings instance with
	 * 											the XmlSchemaCompilationSettings.EnableUpaCheck property set to true.
	 * @throws
	 *   NotSupportedException
	 */
	public function getCompilationSettings() { throw new NotSupportedException( "XmlSchemaSet::Reprocess is not supported" ); }

	/**
	 * Gets or sets the XmlSchemaCompilationSettings for the XmlSchemaSet.
	 * Currently this is not suppported.
	 *
	 * @param XmlSchemaCompilationSettings	$compilationSettings	The XmlSchemaCompilationSettings for the XmlSchemaSet.
	 *																The default is an XmlSchemaCompilationSettings instance with
	 * 																the XmlSchemaCompilationSettings.EnableUpaCheck property set to true.
	 * @return void
	 * @throws
	 *   NotSupportedException
	 */
	public function setCompilationSettings( $compilationSettings ) { throw new NotSupportedException( "XmlSchemaSet::Reprocess is not supported" ); }

	/**
	 * Gets the number of logical XML Schema definition language (XSD) schemas in the XmlSchemaSet.
	 *
	 * @return int The number of logical schemas in the XmlSchemaSet.
	 */
	public function getCount() { return count( $this->schemas ); }

	/**
	 * Gets all the global attributes in all the XML Schema definition language (XSD) schemas in the XmlSchemaSet.
	 *
	 * @return array An array of XmlSchemaObject instances representing the collection of global attributes.
	 */
	public function getGlobalAttributes() { return $this->globalAttributes; }

	/**
	 * Gets all the global elements in all the XML Schema definition language (XSD)
	 * schemas in the XmlSchemaSet.
	 *
	 * @return array An array of XmlSchemaObject instances representing the collection of global elements.
	 */
	public function getGlobalElements() { return $this->globalElements; }

	/**
	 * Gets all of the global simple and complex types in all the XML Schema definition language (XSD) schemas in the XmlSchemaSet.
	 *
	 * @return array An array of XmlSchemaObject instances representing the collection of global types.
	 */
	public function getGlobalTypes() { return $this->globalTypes; }

	/**
	 * Gets a specific global simple or complex type from all the XML Schema definition language (XSD) schemas in the XmlSchemaSet.
	 * @param QName $qualifiedName
	 * @return XmlSchemaElement A specific global element.
	 */
	public function getGlobalElement( $qualifiedName )
	{
		return null;
	}

	/**
	 * Gets a specific global simple or complex type from all the XML Schema definition language (XSD) schemas in the XmlSchemaSet.
	 * @param QName $qualifiedName
	 * @return XmlSchemaType A specific global type.
	 */
	public function getGlobalType( $qualifiedName )
	{
		return null;
	}

	/**
	 * Gets a specific global attribute from all the XML Schema definition language (XSD) schemas in the XmlSchemaSet.
	 * @param QName $qualifiedName
	 * @return XmlSchemaAttribute A specific global attribute.
	 */
	public function getGlobalAttribute( $qualifiedName )
	{
		return null;
	}

	/**
	 * Gets a value that indicates whether the XML Schema definition language (XSD) schemas in the XmlSchemaSet have been compiled.
	 *
	 * @return bool true if the schemas in the XmlSchemaSet have been compiled since the last time a schema was added or removed from the XmlSchemaSet; otherwise, false.
	 * @throws
	 *   NotSupportedException
	 */
	public function getIsCompiled() { throw new NotSupportedException( "XmlSchemaSet::Reprocess is not supported" ); }

	/**
	 * Gets the default System.Xml.XmlNameTable used by the XmlSchemaSet when loading new XML Schema definition language (XSD) schemas.
	 *
	 * @return XmlNameTable
	 * A table of atomized string objects.
	 */
	public function getNameTable() { return $this->nameTable; }

	/**
	 * Adds the given XmlSchema to the XmlSchemaSet.
	 *
	 * @param XmlSchema $schema  The XmlSchema object to add to the XmlSchemaSet.
	 * @return XmlSchema  An XmlSchema object if the schema is valid otherwise, an XmlSchemaException is thrown.
	 * @throws
	 *   XmlSchemaException  A schema in the XmlSchemaSet is not valid.
	 *   ArgumentNullException The URL passed as a parameter is null or System.String.Empty.
	 */
	public function AddSchema( $schema )
	{
		if ( is_null( $schema ) ) throw new \lyquidity\xml\exceptions\ArgumentNullException();
		if ( ! $schema instanceof XmlSchema ) throw new XmlSchemaException( "The parameter is not a valid XmlSchema instance" );

		$this->schemas[ $schema->getTargetNamespace() ] = $schema;
	}

	/**
	 * Adds all the XML Schema definition language (XSD) schemas in the given XmlSchemaSet to the XmlSchemaSet.
	 *
	 * @param XmlSchemaSet $schemas The XmlSchemaSet object.
	 * @return void
	 * @throws
	 *   XmlSchemaException  A schema in the XmlSchemaSet is not valid.
	 *   ArgumentNullException The URL passed as a parameter is null or System.String.Empty.
	 */
	public function AddSchemaSet( $schemas )
	{
		if ( is_null( $schema ) ) throw new \lyquidity\xml\exceptions\ArgumentNullException();

		if ( ! $schemas instanceof XmlSchemaSet ) throw new XmlSchemaException( "Parameter is not an XmlSchemaSet instance" );

		foreach ( $schemas->schemas as $schema )
		{
			if ( ! $schema instanceof XmlSchema ) throw new XmlSchemaException( "Parameter does contains an invalid XmlSchema instance" );
		}

		$this->schemas += $schemas->schemas;
	}

	/**
	 * Adds the XML Schema definition language (XSD) schema at the URL specified to the XmlSchemaSet.
	 * Currently this is not suppported.
	 *
	 * @param string $targetNamespace The schema targetNamespace property.  If null all schemas are returned
	 * @param string $schemaUri The URL that specifies the schema to load.
	 * @return XmlSchema  An XmlSchema object if the schema is valid otherwise, an XmlSchemaException is thrown.
	 *
	 * @throws
	 *   XmlSchemaException The schema is not valid.
	 *   ArgumentNullException The URL passed as a parameter is null or System.String.Empty.
	 *   NotSupportedException
	 */
	public function AddNamespace( $targetNamespace, $schemaUri ) { throw new NotSupportedException( "XmlSchemaSet::Reprocess is not supported" ); }

	/**
	 * Compiles the XML Schema definition language (XSD) schemas added to the XmlSchemaSet into one logical schema.
	 * Currently this is not suppported.
	 *
	 * @throws
	 *   XmlSchemaException An error occurred when validating and compiling the schemas in the XmlSchemaSet.
	 *   NotSupportedException
	 */
	public function Compile() { throw new NotSupportedException( "XmlSchemaSet::Reprocess is not supported" ); }

	/**
	 * Indicates whether the specified XML Schema definition language (XSD) XmlSchema
	 * object is in the XmlSchemaSet.
	 *
	 * @param XmlSchema $schema  The XmlSchema object.
	 * @return bool true if the XmlSchema object is in the XmlSchemaSet; otherwise, false.
	 * @throws
	 *   \lyquidity\xml\exceptions\ArgumentNullException The XmlSchemaSet passed as a parameter is null.
	 */
	public function ContainsSchema( $schema)
	{
		if ( is_null( $schema ) ) throw new ArgumentNullException();
		if ( ! $schema instanceof XmlSchema ) throw new XmlSchemaException( "The parameter is not an XmlSchema instance" );

		return in_array( $schema, $this->schemas );
	}

	/**
	 * Indicates whether an XML Schema definition language (XSD) schema with the specified target namespace URI is in the XmlSchemaSet.
	 *
	 * @param string $targetNamespace The schema targetNamespace property.  If null all schemas are returned
	 * @return bool true if a schema with the specified target namespace URI is in the XmlSchemaSet; otherwise, false.
	 */
	public function ContainsNamespace( $targetNamespace )
	{
		return isset( $this->schemas[ $targetNamespace ] );
	}

	/**
	 * Copies all the XmlSchema objects from the XmlSchemaSet to the given array, starting at the given index.
	 * Currently this is not suppported.
	 *
	 * @param array $schemas The array to copy the objects to.
	 * @param int $index The index in the array where copying will begin.
	 * @throws
	 *   NotSupportedException
	 */
	public function CopyTo( $schemas, $index ) { throw new NotSupportedException( "XmlSchemaSet::Reprocess is not supported" ); }

	/**
	 * Removes the specified XML Schema definition language (XSD) schema from the XmlSchemaSet.
	 * Currently this is not suppported.
	 *
	 * @param XmlSchema $schema The XmlSchema object to remove from the XmlSchemaSet.
	 * @return XmlSchema The XmlSchema object removed from the XmlSchemaSet or null if the schema was not found in the XmlSchemaSet.
	 *
	 * @throws
	 *   XmlSchemaException  The schema is not valid.
	 *   ArgumentNullException XmlSchema object passed as a parameter is null.
	 *   NotSupportedException
	 */
	public function Remove( $schema ) { throw new NotSupportedException( "XmlSchemaSet::Reprocess is not supported" ); }

	/**
	 * Removes the specified XML Schema definition language (XSD) schema and all the schemas it imports from the XmlSchemaSet.
	 * Currently this is not suppported.
	 *
	 * @param XmlSchema $schemaToRemove  The XmlSchema object to remove from the XmlSchemaSet.
	 * @return bool  true if the XmlSchema object and all its imports were successfully removed; otherwise, false.
	 * @throws
	 *   ArgumentException  The XmlSchema object passed as a parameter does not already exists XmlSchemaSet.
	 *   NotSupportedException
	 */
	public function RemoveRecursive( $schemaToRemove ) { throw new NotSupportedException( "XmlSchemaSet::Reprocess is not supported" ); }

	/**
	 * Reprocesses an XML Schema definition language (XSD) schema that already exists in the XmlSchemaSet.
	 * Currently this is not suppported.
	 *
	 * @param XmlSchema $schema  The schema to reprocess.
	 * @return XmlSchema if the schema is a valid schema. Otherwise, an XmlSchemaException is thrown.
	 * @throws
	 *   XmlSchemaException  The schema is not valid.
	 *   ArgumentNullException XmlSchema object passed as a parameter is null.
	 *   ArgumentException  The XmlSchema object passed as a parameter does not already exists XmlSchemaSet.
	 *   NotSupportedException
	 */
	public function Reprocess( $schema ) { throw new NotSupportedException( "XmlSchemaSet::Reprocess is not supported" ); }

	/**
	 * Returns a collection of all the XML Schema definition language (XSD) schemas
	 * in the XmlSchemaSet that belong to the given namespace.
	 *
	 * @param string $targetNamespace The schema targetNamespace property.  If null all schemas are returned
	 *
	 * @return array An array containing all the schemas that have been added to the XmlSchemaSet that belong to the given namespace.
	 * 				 If no schemas have been added to the XmlSchemaSet, an empty array is returned.
	 */
	public function Schemas( $targetNamespace = null )
	{
		return $this->schemas;
	}

}
