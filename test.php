<?php

namespace lyquidity\XPath2;

use lyquidity\xml\schema\SchemaTypes;

define( 'UTILITY_LIBRARY_PATH', __DIR__ . '/../utilities/' );
define( 'XML_LIBRARY_PATH', __DIR__ . '/../xml/' );
define( 'XPATH20_LIBRARY_PATH',  __DIR__ . '/../XPath2/' );
define( 'LOG_LIBRARY_PATH', __DIR__ . '/../log/' );

global $compiled_taxonomy_name_prefix;
$compiled_taxonomy_name_prefix =  __DIR__ . "/../compiled/";

if ( PHP_INT_SIZE == 4 ) // x86
{
	echo "The XPath 2.0 conformance tests cannot succeed when running x86 version of PHP because " .
		 "some conformance tests will only succeed when an 8 bit integer is available.";
	return;
}

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/XQSTests.php';

XQSTests::$excludeVeryLongRunningTests = true;
// XQSTests::showNotes();

// Initialize the log instance
$log = \lyquidity\XPath2\lyquidity\Log::getInstance();
$log->setConsoleLog();
// Add this logger to the SchemaTypes instance
SchemaTypes::getInstance()->log = $log;

/**
 * This array is used to record any conformance warnings
 * @var array $issues
 */
global $issues;
$issues = array();

$terminate = false;

function terminate( $terminate )
{
	if ( $terminate ) exit;
}

/*    7 */ XQSTests::runTestGroups( "/MinimalConformance/OptionalFeatureErrors" /* , "combined-errors-4" , "K-CombinedErrorCodes-15" */ ); terminate( $terminate );
/*    0 */ XQSTests::runTestGroups( "/MinimalConformance/Basics" ); terminate( $terminate ); // Only non v2 tests
/*   21 */ XQSTests::runTestGroups( "/MinimalConformance/Basics/Types", function( $test, $file, $first ) { return true || $test == "sequence-type-10" || ! $first; } ); terminate( $terminate );
/*   48 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions", function( $test, $file, $first ) { return true || $test == "K-LogicExpr-22" || ! $first; } ); terminate( $terminate );
/*  130 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/PrimaryExpr", function( $test, $file, $first ) { return true || $test == "K-Literals-22" || ! $first ; } ); terminate( $terminate );
/*   43 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/PrimaryExpr/ContextExpr", function( $test, $file, $first ) { return true || $test == "externalcontextitem-9" || ! $first; } ); terminate( $terminate );
/*  242 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/PathExpr", function( $test, $file, $first ) { return true || basename( $file ) == "Axes.xml" && in_array( $test, array( "K2-Axes-38" ) ) || ! $first; } ); terminate( $terminate );
/*   79 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/PathExpr/NodeTestSection", function( $test, $file, $first ) { return true || $test == "K2-NameTest-38" || ! $first; } ); terminate( $terminate );
/*  118 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/SeqExpr", function( $test, $file, $first ) { return true || $test == "K-FilterExpr-75" || ! $first; } ); terminate( $terminate );
/*  120 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/SeqExpr/ConstructSeq", function( $test, $file, $first ) { return true || $test == "op-concatenate-mix-args-012" || ! $first; } ); terminate( $terminate );
/*  768 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/Operators/ArithExpr/NumericOpr", function( $test, $file, $first ) { return true || $test == 'K2-NumericDivide-15' || ! $first; } ); terminate( $terminate );
/*  288 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/Operators/ArithExpr/DurationArith", function( $test, $file, $first ) { return true || ( $test == "ReturnExpr006" /** && basename( $file ) == "dateTimesSubtract.xml" **/ ) || ! $first; } ); terminate( $terminate );
/*  312 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/Operators/ArithExpr/DurationDateTimeArith", function( $test, $file, $first ) { return true || ( $test == "op-subtract-dateTimes-yielding-DTD-19" || $test == "op-subtract-dates-yielding-DTD-19" /** && basename( $file ) == "DatesSubtract.xml" **/ ) || ! $first; } ); terminate( $terminate );
/*   42 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/Operators/CompExpr/ValComp", function( $test, $file, $first ) { return true || $test == "K-ValCompTypeChecking-20" || ! $first; } ); terminate( $terminate );
/*  422 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/Operators/CompExpr/ValComp/NumericComp", function( $test, $file, $first ) { return true || $test == "K-NumericGT-1" || ! $first; } ); terminate( $terminate );
/*  124 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/Operators/CompExpr/ValComp/BooleanOp", function( $test, $file, $first ) { return true || $test == "op-boolean-less-than2args-4" || ! $first; } ); terminate( $terminate );
/*  712 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/Operators/CompExpr/ValComp/DurationDateTimeOp", function( $test, $file, $first ) { return true || $test == "op-gYearMonth-equalNew-7" || ! $first; } ); terminate( $terminate );
/*   46 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/Operators/CompExpr/ValComp/QNameOp", function( $test, $file, $first ) { return true || $test == "fn-prefix-from-qname-3" || ! $first; } ); terminate( $terminate );
/*   56 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/Operators/CompExpr/ValComp/BinaryOp", function( $test, $file, $first ) { return true || $test == "K-Base64BinaryEQ-3" || ! $first; } ); terminate( $terminate );
/*   16 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/Operators/CompExpr/ValComp/StringComp", function( $test, $file, $first ) { return true || $test == "op-base64Binary-equal2args-1" || ! $first; } ); terminate( $terminate );
/*   40 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/Operators/CompExpr/ValComp/AnyURIComp", function( $test, $file, $first ) { return true || $test == "K2-AnyURILtGt-3" || ! $first; } ); terminate( $terminate );
/*  453 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/Operators/CompExpr/GenComprsn", function( $test, $file, $first ) { return true || $test == "K-GenCompLT-18" || ! $first; } ); terminate( $terminate );
/*   73 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/Operators/CompExpr/NodeComp", function( $test, $file, $first ) { return true || $test == "nodeexpression11" || ! $first; } ); terminate( $terminate );
/*   53 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/Operators/SeqOp", function( $test, $file, $first ) { return true || $test == "fn-union-node-args-003" || ! $first; } ); terminate( $terminate );
/*   43 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/FLWORExpr", function( $test, $file, $first ) { return true || $test == "ReturnExpr006" || ! $first; } ); terminate( $terminate );
/*   64 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/FLWORExpr/ForExpr", function( $test, $file, $first ) { return true || $test == "K-ForExprWithout-24" || ! $first; } ); terminate( $terminate );
/*  135 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/QuantExpr", function( $test, $file, $first ) { return true || $test == "instanceof110" || ! $first; } ); terminate( $terminate );
/* 3684 */ XQSTests::runTestGroups( "/MinimalConformance/Expressions/exprSeqTypes", function( $test, $file, $first ) { return true || ( $test == "K2-SeqExprCast-480" ) /* || ! $first */; } ); terminate( $terminate );
/*   91 */ XQSTests::runTestGroups( "/MinimalConformance/Functions", function( $test, $file, $first ) { return true || $test == "fn-error-3" || ! $first; } ); terminate( $terminate );
/*  123 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/AccessorFunc", function( $test, $file, $first ) { return true || $test == "fn-document-uri-19" || ! $first; } ); terminate( $terminate );
/*   50 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/ConstructFunc", function( $test, $file, $first ) { return true || $test == "K-DateTimeFunc-2" || ! $first; } ); terminate( $terminate );
/*  545 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/NumericFunc", function( $test, $file, $first ) { return true || $test == "fn-absdbl1args-1" || ! $first; } ); terminate( $terminate );
/*   80 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/AllStringFunc/AssDisassStringFunc", function( $test, $file, $first ) { return true || $test == "K-CodepointToStringFunc-8" || ! $first; } ); terminate( $terminate );
/*   66 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/AllStringFunc/CompStringFunc", function( $test, $file, $first ) { return true || $test == "fn-compare-1" || ! $first; } ); terminate( $terminate );
/*  446 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/AllStringFunc/GeneralStringFunc", function( $test, $file, $first ) { return true || $test == "fn-escape-html-uri1args-1" || ! $first; } ); terminate( $terminate );
/*  181 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/AllStringFunc/MatchStringFunc", function( $test, $file, $first ) { return true || $test == "fn-matches-25" || ! $first; } ); terminate( $terminate );
/*   29 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/URIFunc", function( $test, $file, $first ) { return true || $test == "fn-resolve-uri-24" || ! $first; } ); terminate( $terminate );
/*  118 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/BooleanFunc", function( $test, $file, $first ) { return true || $test == "fn-false-19" || ! $first; } ); terminate( $terminate );
/*  587 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/DurationDateTimeFunc/ComponentExtractionDDT", function( $test, $file, $first ) { return true || $test == "K-TimezoneFromDateFunc-7" || ! $first; } ); terminate( $terminate );
/*  108 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/DurationDateTimeFunc/TimezoneFunction", function( $test, $file, $first ) { return true || $test == "K-AdjTimeToTimezoneFunc-9" || ! $first; } ); terminate( $terminate );
/*   24 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/QNameFunc", function( $test, $file, $first ) { return true || $test == "K-LocalNameFromQNameFunc-4" || ! $first; } ); terminate( $terminate );
/*   16 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/QNameFunc/QNameConstructFunc", function( $test, $file, $first ) { return true || $test == "qName-1" || ! $first; } ); terminate( $terminate );
/*  129 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/NodeFunc", function( $test, $file, $first ) { return true || $test == "fn-numberflt1args-1" || ! $first; } ); terminate( $terminate );
/*  155 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/SeqFunc", function( $test, $file, $first ) { return true || $test == "fn-deep-equal-mix-args-011" || ! $first; } ); terminate( $terminate );
/*  558 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/SeqFunc/GeneralSeqFunc", function( $test, $file, $first ) { return true || $test == "fn-distinct-values-mixed-args-005" || ! $first; } ); terminate( $terminate );
/*  147 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/SeqFunc/CardinalitySeqFunc", function( $test, $file, $first ) { return true || $test == "K2-SeqOneOrMoreFunc-1" || ! $first; } ); terminate( $terminate );
/*  845 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/SeqFunc/AggregateSeqFunc", function( $test, $file, $first ) { return true || $test == "fn-sumflt3args-2" || ! $first; } ); terminate( $terminate );
/*   87 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/SeqFunc/NodeSeqFunc", function( $test, $file, $first ) { return ( true || $test == "fn-doc-30" || ! $first ); } ); terminate( $terminate );
/*  217 */ XQSTests::runTestGroups( "/MinimalConformance/Functions/ContextFunc", function( $test, $file, $first ) { return ( true || $test == "K-ContextLastFunc-29" || ! $first ); } ); terminate( $terminate );
/*  126 */ XQSTests::runTestGroups( "/Optional/FullAxis", function( $test, $file, $first ) { return ( true || $test == "ancestor-1" || ! $first ); } ); terminate( $terminate );
/*    3 */ XQSTests::runTestGroups( "/UseCase", function( $test, $file, $first ) { return ( true || $test == "seq-queries-results-q2" || ! $first ); } ); terminate( $terminate );
/*    1 */ XQSTests::runTestGroups( "/XQuery11", function( $test, $file, $first ) { return ( true || $test == "seq-queries-results-q2" || ! $first ); } ); terminate( $terminate );

// // Static analysis errors don't seem relevant in an XPath 2.0 only world
// // XQSTests::runTestGroups( "/Optional/StaticTyping/STPathExpr/STSteps", function( $test, $file, $first ) { return ( false || $test == "ST-Axes008" || ! $first ); } ); terminate( $terminate );
// // No XPath tests
// // XQSTests::runTestGroups( "/FunctX", function( $test, $file, $first ) { return ( true || $test == "seq-queries-results-q2" || ! $first ); } ); terminate( $terminate );

 global $result;
$result = array(
	'success' => ! $issues,
	'issues' => $issues
);

file_put_contents( basename( __FILE__, 'php' ) . 'json', json_encode( $result ) );

?>