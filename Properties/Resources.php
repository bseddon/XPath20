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

namespace lyquidity\XPath2\Properties;

/**
 * Defines string resources
 */
class Resources
{
	const BadCharRef = "Bad character ref constant &#{0}{1};";
	const ContextItemNotDefined = "The context item cannot be defined";
	const ExpectedAtomicValue = "Expected atomic value";
	const ExpectedBlockStart = "Expected {{ after {0} {1}";
	const ExpectedNCName = "Expected NCName after *:";
	const ExpectedVariablePrefix = "Expected prefix $ after {0}";
	const ExpectingCharAfterQName = "Expecting {0} after QName";
	const InvalidPITarget = "The target xml is invalid for processing instruction";
	const UnexpectedChar = "Unexpected char {0}";
	const UnexpectedEOF = "Unexpected end of file";
	const XPST0008 = "Qname {0} is not defined";
	const XPST0004 = "Expected type {0}";
	const XPST0081 = "The prefix '{0}' cannot be expanded into a namespace URI by using the statically known namespaces";
	const Sch_EnumFinished = "Enumeration finished";
	const Sch_EnumNotStarted = "Enumeration not started";
	const FileNotFound = "File {0} is not found in current path";
	const MoreThanOneItem = "More than one item in sequence in atomic expression";
	const GeneralXFIFailure = "The arguments are not valid for the function";

	const XPDY0002 = "The context item cannot be defined";
	const XPDY0050 = "Required item type of value in 'treat as' expression is {0} supplied value has item type {1}";
	const XPTY0019 = "The result of a step in path expression must not be an atomic value {0}";
	const XPTY0004 = "Dynamic type {0} of a value does not match a required type {1}";
	const XPST0017 = "The function '{0}'/{1} was not found in namespace '{2}'";
	const XQST0033 = "The module contains multiple bindings for the same namespace prefix {0}.";
	const XQST0049 = "The variable {0} is already defined in module";
	const XQST0066 = "The module contains more than one default element/type namespace declaration, or more than one default function namespace declaration.";
	const XQST0034 = "The function '{0}' in namespace '{1}' has already been declared with the same number of arguments";
	const XQST0045 = "The function '{0}' cannot be declared in the namespace '{1}'";
	const ExpectedModuleDecl = "Expected module declaration";
	const XQST0032 = "The module prolog contains more than one base URI declaration";
	const XQST0038 = "The module prolog contains more than one default collation declaration, or the value specified by a default collation declaration is not present in statically known collations";
	const XQST0065 = "The module prolog contains more than one ordering mode declaration";
	const XQST0067 = "The module prolog contains more than one construction declaration";
	const XQST0068 = "The module prolog contains more than one boundary-space declaration";
	const XQST0069 = "The module  prolog contains more than one empty order declaration";
	const FORG0003 = "Function 'zero-or-one' was called with a sequence containing more than one item";
	const FORG0004 = "Function 'one-or-more' was called with a sequence containing no items";
	const FORG0005 = "Function 'exactly-one' was called with a sequence containing zero or more than one item";
	const InvalidRegularExpressionFlags = "Invalid regular expression flags {0}";
	const UnsupportedNormalizationForm = "Unsupported normalization form {0}";
	const FODC0004 = "Invalid argument '{0}' to fn:collection";
	const FOCA0002 = "QName {0} has null namespace but non-empty prefix";
	const FOCA0003 = "Input value too large for integer.";
	const FONS0004 = "No namespace found for prefix";
	const InvalidFormat = "Input string {0} has invalid format for type {1}";
	const UnknownExternalVariable = "Variable  {0} is not defined as external";
	const XQST0059 = "Implementation is unable to process a schema or module import by finding a schema or module with the specified target namespace {0}.";
	const XQST0070 = "A namespace URI {0} is bound to the predefined prefix xmlns, or if a namespace URI other than http://www.w3.org/XML/1998/namespace is bound to the prefix xml, or if the prefix xml is bound to a namespace URI other than http://www.w3.org/XML/1998/namespace.";
	const XQST0088 = "The literal that specifies the target namespace in a module import or a module declaration is of zero length";
	const XQST0047 = "Multiple module imports in the same Prolog specify the same target namespace {0}";
	const XQST0073 = "The graph of module imports contains a cycle in file {0}";
	const XPTY0018 = "The result of the last step in a path expression contains both nodes and atomic values {0}";
	const FORG0006 = "Function '{0}' was called with invalid argument type {1}";
	const BinaryOperatorNotDefined = "Operator {0}  is not defined for arguments of type {1} and {2}";
	const UnaryOperatorNotDefined = "Operator {0}  is not defined for argument of type {1}";
	const FORG0001 = "The value '{0}' is an invalid argument for constructor/cast {1}";
	const FOAR0001 = "Division by zero";
	const FOAR0002 = "Numeric operation overflow/underflow";
	const FODT0003 = "Invalid timezone value {0}";
	const FORG0008 = "Both arguments to fn:dateTime have a specified timezone";
	const FOCA0005 = "NaN supplied as float/double value";
	const FODT0001 = "Overflow/underflow in date/time operation";
	const FODT0002 = "Overflow/underflow in duration operation";
	const XQST0009 = "This XPath 2.0 implementation does not support the Schema Aware Feature.";
	const XQST0016 = "This XPath 2.0 implementation does not support the Module Feature.";
	const XQST0022 = "The namespace bound to prefix '{0}' must be a URI literal.  Enclosed expressions are not permitted.";
	const XQST0075 = "This XPath 2.0 implementation does not support the Schema Aware Feature so cannot validate.";
	const XPST0003 = "Syntax error. {0}";
	const XPST0010 = "This XPath 2.0 implementation does not support the namespace axis";
	const XQST0040 = "The attribute '{0}' is a duplicate.  Attributes specified by a direct  element constructor must have distinct expanded qualified names.";
	const FOCH0001 = "Invalid XML character [x{0}]";
	const XQTY0024 = "Content sequence in an element constructor contains an attribute node '{0}' following a node that is not an attribute node";
	const XQDY0025 = "Element constructor '{0}' contains a duplicate attribute node '{1}'";
	const InvalidAttributeSequence = "The content sequence of a document node may not contain an attribute node";
	const XQDY0026 = "The result of the content expression of a computed processing instruction constructor contains the string \"?>\"";
	const XQDY0044 = "The node name of a computed attribute may not be 'xmlns' because attribute constructors cannot create namespaces";
	const XQDY0064 = "The computed processing instruction target cannot be equal to \"XML\" in any combination of upper or lower case";
	const XQDY0072 = "The result of the content expression of a computed comment constructor contains two adjacent hyphens or ends with a hyphen";
	const XQST0089 = "The bound variable '{0}' in a FLWOR expression must be distinct from the positional variable";
	const XQST0076 = "'{0}' does not identify a collation that is present in statically known collations";
	const XPST0051 = "The type name '{0}' has been used as an atomic type in a sequence type, but is not defined in the in-scope schema types as an atomic type";
	const XPST0080 = "Cannot cast to {0}. The target type of a cast or castable expression must be an atomic type that is in the in-scope schema types and is not xs:NOTATION or xs:anyAtomicType, optionally followed by the occurrence indicator '?'";
	const XQST0031 = "XQuery version '{0}' is not supported by this implementation";
	const XQST0055 = "The prolog must not contain more than one copy-namespaces declaration";
	const XQST0087 = "String literal '{0}' is not a valid encoding name";
	const FONS0005 = "Base-uri not defined in the static context";
	const FORG0009 = "Error in resolving a relative URI against a base URI in fn:resolve-uri";
	const ExternalVariableNotSet = "External variable {0} not set before run command";
	const XQST0039 = "Function declaration has more than one parameter with name '{0}'";
	const XQST0054 = "A {0} variable depends on itself";
	const ExpectedQNamePrefix = "The {0} qualified name '{1}' must have a prefix";
	const FOER0000 = "Unidentified error";
	const InvalidRegularExpr = "The regular expression '{0}' is invalid";
	const FODC0001 = "No context document";
	const XQTY0030 = "The argument of a validate expression does not evaluate to exactly one document or element node";
	const FOTY0012 = "Argument node {0} does not have a typed value";
	const XQST0058 = "Multiple schema imports specify the same target namespace {0}";
	const XPTY0004_CAST = "Only string literals can be cast to type {0}";
	const FORX0003 = "Regular expression {0} matches zero-length string";
	const FORX0004 = "Invalid replacement string {0}";
	const FOCH0002 = "Invalid collation '{0}'";
	const FORX0002 = "The back-reference(s) are not valid";
	const FORG0002 = "Invalid uri '{0}'";
	const FODC0002 = "The document '{0}' does not exist";
	const FODC0005 = "Invalid uri '{0}'";
	const XPST0005 = "empty-sequence() assign to static type";
	const XPTY0020 = "In an axis step, the context item is not a node";
}

?>