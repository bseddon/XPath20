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

use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\Proxy\ValueProxy;
use lyquidity\XPath2\AST\ArithmeticBinaryOperatorNode;
use lyquidity\XPath2\AST\PathStep;
use lyquidity\XPath2\AST\PathExprNode;
use lyquidity\xml\MS\XmlTypeCardinality;
use lyquidity\XPath2\AST\AbstractNode;
use lyquidity\XPath2\AST\AtomizedBinaryOperatorNode;
use lyquidity\XPath2\AST\UnaryOperatorNode;
use lyquidity\XPath2\AST\AtomizedUnaryOperatorNode;
use lyquidity\XPath2\parser\yyException;
use lyquidity\XPath2\AST\ExprNode;
use lyquidity\XPath2\AST\ForNode;
use lyquidity\XPath2\AST\IfNode;
use lyquidity\XPath2\AST\OrExprNode;
use lyquidity\XPath2\AST\AndExprNode;
use lyquidity\XPath2\AST\BinaryOperatorNode;
use lyquidity\XPath2\AST\SingletonBinaryOperatorNode;
use lyquidity\XPath2\AST\OrderedBinaryOperatorNode;
use lyquidity\XPath2\AST\FilterExprNode;
use lyquidity\XPath2\AST\VarRefNode;
use lyquidity\XPath2\AST\ContextItemNode;
use lyquidity\xml\MS\XmlReservedNs;
use lyquidity\XPath2\AST\FuncNode;
use lyquidity\xml\MS\XmlSchemaAttribute;
use lyquidity\xml\MS\XmlSchemaObject;
use lyquidity\xml\MS\XmlQualifiedNameTest;
use lyquidity\xml\MS\XmlSchemaElement;
use lyquidity\xml\MS\XmlSchemaType;
use lyquidity\XPath2\AST\XPath2ExprType;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\AST\ValueNode;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity;
use lyquidity\XPath2\AST\RangeNode;
use lyquidity\XPath2\parser\yyDebugSimple;
use lyquidity\xml\QName;

/**
 * XPath2Parser ( internal )
 */
class XPath2Parser
{
	/**
	 * Context
	 * @var XPath2Context $context
	 */
	private $context;

	/**
	 * When true log messages will be written to the current log target
	 * @var bool
	 */
	private $enableLogging = false;

	/**
	 * Holds a reference to the logging engine
	 * @var lyquidity\Log
	 */
	private $log;

	/**
	 * Constructor
	 * @param XPath2Context $context
	 */
	public function __construct( $context )
	{
		$this->errorText = array();
		$this->context = $context;
	}

	/**
	 * Parse the query
	 * @param Tokenizer::$tok
	 * @return object
	 */
	public function yyparseSafe ( $tok )
	{
		try
		{
			return $this->yyparse( $tok, null );
		}
		catch( XPath2Exception $ex )
		{
			throw $ex;
		}
		catch( \Exception $ex )
		{
			$errorMsg = implode( "", $this->errorText );
			throw XPath2Exception::withErrorCode( "XPST0003", "{$errorMsg} at line {$tok->LineNo} pos {$tok->ColNo}" );
		}
	}

	/**
	 * Parse the query
	 * @param Tokenizer::$tok
	 * @param object $yyDebug
	 * @return object
	 */
	public function yyparseSafeDebug ( $tok, $yyDebug )
	{
		try
		{
			return $this->yyparseYyd ( $tok, $yyDebug );
		}
		catch( XPath2Exception $ex )
		{
			throw $ex;
		}
		catch( \Exception $ex )
		{
			$errorMsg = implode( "", $this->errorText );
			throw XPath2Exception::withErrorCode( "XPST0003", "{$errorMsg} at line {$tok->LineNo} pos {$tok->ColNo}" );
		}
	}

	/**
	 * Parse the query
	 * @param Tokenizer::$tok
	 * @return object
	 */
	public function yyparseDebug ( $tok )
	{
		return $this->yyparseSafeDebug ( $tok, new yyDebugSimple() );
	}

	/**
	 * Gets the current logging state
	 * @return bool
	 */
	public function getLoggingState()
	{
		return $this->enableLogging;
	}

	/**
	 * Disables logging
	 */
	public function disableLogging()
	{
		$this->enableLogging = false;
	}

	/**
	 * Enables logging
	 */
	public function enableLogging()
	{
		$this->enableLogging = true;
		$this->log("Logging enabled");
	}

	/**
	 * Log a message to the current log target
	 * @param string $message
	 */
	public function log( $message )
	{
		if ( ! $this->enableLogging ) return;
		if ( ! isset( $this->log ) ) $this->log = \lyquidity\XPath2\lyquidity\Log::getInstance();
		$this->log->info( $message );
	}

	/**
	 * errorText
	 * @var array $errorText = null
	 */
	public $errorText = array();

	/**
	 * yyerror
	 * @param string $message
	 * @param string[] $expected
	 * @return void
	 */
	public function yyerror( $message, $expected = null )
	{
		if ( ! is_null( $this->errorText ) && ! is_null( $expected ) && count( $expected ) > 0 )
		{
			$errorMsg = $message . ", expecting";
			for ( $n = 0; $n < count( $expected ); ++ $n )
				$errorMsg .= " " . $expected[ $n ];
			$this->errorText[] = "$errorMsg\n";
		} else
			$this->errorText[] = "$message\n";
	}

	//t  protected yydebug.yyDebug debug;
	/**
	 * @var int yyFinal = 7
	 */
	protected static  $yyFinal = 7;

	//t  public static  string [] yyRule = {
	//t    "$accept : Expr",
	//t    "Expr : ExprSingle",
	//t    "Expr : Expr ',' ExprSingle",
	//t    "ExprSingle : FORExpr",
	//t    "ExprSingle : QuantifiedExpr",
	//t    "ExprSingle : IfExpr",
	//t    "ExprSingle : OrExpr",
	//t    "FORExpr : SimpleForClause RETURN ExprSingle",
	//t    "SimpleForClause : FOR ForClauseBody",
	//t    "ForClauseBody : ForClauseOperator",
	//t    "ForClauseBody : ForClauseBody ',' ForClauseOperator",
	//t    "ForClauseOperator : '$' VarName IN ExprSingle",
	//t    "QuantifiedExpr : SOME QuantifiedExprBody SATISFIES ExprSingle",
	//t    "QuantifiedExpr : EVERY QuantifiedExprBody SATISFIES ExprSingle",
	//t    "QuantifiedExprBody : QuantifiedExprOper",
	//t    "QuantifiedExprBody : QuantifiedExprBody ',' QuantifiedExprOper",
	//t    "QuantifiedExprOper : '$' VarName IN ExprSingle",
	//t    "IfExpr : IF '( ' Expr ' )' THEN ExprSingle ELSE ExprSingle",
	//t    "OrExpr : AndExpr",
	//t    "OrExpr : OrExpr OR AndExpr",
	//t    "AndExpr : ComparisonExpr",
	//t    "AndExpr : AndExpr AND ComparisonExpr",
	//t    "ComparisonExpr : RangeExpr",
	//t    "ComparisonExpr : ValueComp",
	//t    "ComparisonExpr : GeneralComp",
	//t    "ComparisonExpr : NodeComp",
	//t    "GeneralComp : RangeExpr '=' RangeExpr",
	//t    "GeneralComp : RangeExpr '!' '=' RangeExpr",
	//t    "GeneralComp : RangeExpr '<' RangeExpr",
	//t    "GeneralComp : RangeExpr '<' '=' RangeExpr",
	//t    "GeneralComp : RangeExpr '>' RangeExpr",
	//t    "GeneralComp : RangeExpr '>' '=' RangeExpr",
	//t    "ValueComp : RangeExpr EQ RangeExpr",
	//t    "ValueComp : RangeExpr NE RangeExpr",
	//t    "ValueComp : RangeExpr LT RangeExpr",
	//t    "ValueComp : RangeExpr LE RangeExpr",
	//t    "ValueComp : RangeExpr GT RangeExpr",
	//t    "ValueComp : RangeExpr GE RangeExpr",
	//t    "NodeComp : RangeExpr IS RangeExpr",
	//t    "NodeComp : RangeExpr '<' '<' RangeExpr",
	//t    "NodeComp : RangeExpr '>' '>' RangeExpr",
	//t    "RangeExpr : AdditiveExpr",
	//t    "RangeExpr : AdditiveExpr TO AdditiveExpr",
	//t    "AdditiveExpr : MultiplicativeExpr",
	//t    "AdditiveExpr : AdditiveExpr '+' MultiplicativeExpr",
	//t    "AdditiveExpr : AdditiveExpr '-' MultiplicativeExpr",
	//t    "MultiplicativeExpr : UnionExpr",
	//t    "MultiplicativeExpr : MultiplicativeExpr ML UnionExpr",
	//t    "MultiplicativeExpr : MultiplicativeExpr DIV UnionExpr",
	//t    "MultiplicativeExpr : MultiplicativeExpr IDIV UnionExpr",
	//t    "MultiplicativeExpr : MultiplicativeExpr MOD UnionExpr",
	//t    "UnionExpr : IntersectExceptExpr",
	//t    "UnionExpr : UnionExpr UNION IntersectExceptExpr",
	//t    "UnionExpr : UnionExpr '|' IntersectExceptExpr",
	//t    "IntersectExceptExpr : InstanceofExpr",
	//t    "IntersectExceptExpr : IntersectExceptExpr INTERSECT InstanceofExpr",
	//t    "IntersectExceptExpr : IntersectExceptExpr EXCEPT InstanceofExpr",
	//t    "InstanceofExpr : TreatExpr",
	//t    "InstanceofExpr : TreatExpr INSTANCE_OF SequenceType",
	//t    "TreatExpr : CastableExpr",
	//t    "TreatExpr : CastableExpr TREAT_AS SequenceType",
	//t    "CastableExpr : CastExpr",
	//t    "CastableExpr : CastExpr CASTABLE_AS SingleType",
	//t    "CastExpr : UnaryExpr",
	//t    "CastExpr : UnaryExpr CAST_AS SingleType",
	//t    "UnaryExpr : UnaryOperator ValueExpr",
	//t    "UnaryOperator :",
	//t    "UnaryOperator : '+' UnaryOperator",
	//t    "UnaryOperator : '-' UnaryOperator",
	//t    "ValueExpr : PathExpr",
	//t    "PathExpr : '/'",
	//t    "PathExpr : '/' RelativePathExpr",
	//t    "PathExpr : DOUBLE_SLASH RelativePathExpr",
	//t    "PathExpr : RelativePathExpr",
	//t    "RelativePathExpr : StepExpr",
	//t    "RelativePathExpr : RelativePathExpr '/' StepExpr",
	//t    "RelativePathExpr : RelativePathExpr DOUBLE_SLASH StepExpr",
	//t    "StepExpr : AxisStep",
	//t    "StepExpr : FilterExpr",
	//t    "AxisStep : ForwardStep",
	//t    "AxisStep : ForwardStep PredicateList",
	//t    "AxisStep : ReverseStep",
	//t    "AxisStep : ReverseStep PredicateList",
	//t    "ForwardStep : AXIS_CHILD NodeTest",
	//t    "ForwardStep : AXIS_DESCENDANT NodeTest",
	//t    "ForwardStep : AXIS_ATTRIBUTE NodeTest",
	//t    "ForwardStep : AXIS_SELF NodeTest",
	//t    "ForwardStep : AXIS_DESCENDANT_OR_SELF NodeTest",
	//t    "ForwardStep : AXIS_FOLLOWING_SIBLING NodeTest",
	//t    "ForwardStep : AXIS_FOLLOWING NodeTest",
	//t    "ForwardStep : AXIS_NAMESPACE NodeTest",
	//t    "ForwardStep : AbbrevForwardStep",
	//t    "AbbrevForwardStep : '@' NodeTest",
	//t    "AbbrevForwardStep : NodeTest",
	//t    "ReverseStep : AXIS_PARENT NodeTest",
	//t    "ReverseStep : AXIS_ANCESTOR NodeTest",
	//t    "ReverseStep : AXIS_PRECEDING_SIBLING NodeTest",
	//t    "ReverseStep : AXIS_PRECEDING NodeTest",
	//t    "ReverseStep : AXIS_ANCESTOR_OR_SELF NodeTest",
	//t    "ReverseStep : AbbrevReverseStep",
	//t    "AbbrevReverseStep : DOUBLE_PERIOD",
	//t    "NodeTest : KindTest",
	//t    "NodeTest : NameTest",
	//t    "NameTest : QName",
	//t    "NameTest : Wildcard",
	//t    "Wildcard : '*'",
	//t    "Wildcard : NCName ':' '*'",
	//t    "Wildcard : '*' ':' NCName",
	//t    "FilterExpr : PrimaryExpr",
	//t    "FilterExpr : PrimaryExpr PredicateList",
	//t    "PredicateList : Predicate",
	//t    "PredicateList : PredicateList Predicate",
	//t    "Predicate : '[ ' Expr ' ]'",
	//t    "PrimaryExpr : Literal",
	//t    "PrimaryExpr : VarRef",
	//t    "PrimaryExpr : ParenthesizedExpr",
	//t    "PrimaryExpr : ContextItemExpr",
	//t    "PrimaryExpr : FunctionCall",
	//t    "Literal : NumericLiteral",
	//t    "Literal : StringLiteral",
	//t    "NumericLiteral : IntegerLiteral",
	//t    "NumericLiteral : DecimalLiteral",
	//t    "NumericLiteral : DoubleLiteral",
	//t    "VarRef : '$' VarName",
	//t    "ParenthesizedExpr : '( ' ' )'",
	//t    "ParenthesizedExpr : '( ' Expr ' )'",
	//t    "ContextItemExpr : '.'",
	//t    "FunctionCall : QName '( ' ' )'",
	//t    "FunctionCall : QName '( ' Args ' )'",
	//t    "Args : ExprSingle",
	//t    "Args : Args ',' ExprSingle",
	//t    "SingleType : AtomicType",
	//t    "SingleType : AtomicType '?'",
	//t    "SequenceType : ItemType",
	//t    "SequenceType : ItemType Indicator1",
	//t    "SequenceType : ItemType Indicator2",
	//t    "SequenceType : ItemType Indicator3",
	//t    "SequenceType : EMPTY_SEQUENCE",
	//t    "ItemType : AtomicType",
	//t    "ItemType : KindTest",
	//t    "ItemType : ITEM",
	//t    "AtomicType : QName",
	//t    "KindTest : DocumentTest",
	//t    "KindTest : ElementTest",
	//t    "KindTest : AttributeTest",
	//t    "KindTest : SchemaElementTest",
	//t    "KindTest : SchemaAttributeTest",
	//t    "KindTest : PITest",
	//t    "KindTest : CommentTest",
	//t    "KindTest : TextTest",
	//t    "KindTest : AnyKindTest",
	//t    "AnyKindTest : NODE '( ' ' )'",
	//t    "DocumentTest : DOCUMENT_NODE '( ' ' )'",
	//t    "DocumentTest : DOCUMENT_NODE '( ' ElementTest ' )'",
	//t    "DocumentTest : DOCUMENT_NODE '( ' SchemaElementTest ' )'",
	//t    "TextTest : TEXT '( ' ' )'",
	//t    "CommentTest : COMMENT '( ' ' )'",
	//t    "PITest : PROCESSING_INSTRUCTION '( ' ' )'",
	//t    "PITest : PROCESSING_INSTRUCTION '( ' NCName ' )'",
	//t    "PITest : PROCESSING_INSTRUCTION '( ' StringLiteral ' )'",
	//t    "ElementTest : ELEMENT '( ' ' )'",
	//t    "ElementTest : ELEMENT '( ' ElementNameOrWildcard ' )'",
	//t    "ElementTest : ELEMENT '( ' ElementNameOrWildcard ',' TypeName ' )'",
	//t    "ElementTest : ELEMENT '( ' ElementNameOrWildcard ',' TypeName '?' ' )'",
	//t    "ElementNameOrWildcard : ElementName",
	//t    "ElementNameOrWildcard : '*'",
	//t    "AttributeTest : ATTRIBUTE '( ' ' )'",
	//t    "AttributeTest : ATTRIBUTE '( ' AttributeOrWildcard ' )'",
	//t    "AttributeTest : ATTRIBUTE '( ' AttributeOrWildcard ',' TypeName ' )'",
	//t    "AttributeOrWildcard : AttributeName",
	//t    "AttributeOrWildcard : '*'",
	//t    "SchemaElementTest : SCHEMA_ELEMENT '( ' ElementName ' )'",
	//t    "SchemaAttributeTest : SCHEMA_ATTRIBUTE '( ' AttributeName ' )'",
	//t    "AttributeName : QName",
	//t    "ElementName : QName",
	//t    "TypeName : QName",
	//t	};
	/**
	 * @var string[] $yyName = {
    "end-of-file",null,null,null,null,null,null,null,null,null,null,null,
    null,null,null,null,null,null,null,null,null,null,null,null,null,null,
    null,null,null,null,null,null,null,"'!'",null,null,"'$'",null,null,
    null,"'( '","' )'","'*'","'+'","','","'-'","'.'","'/'",null,null,null,
    null,null,null,null,null,null,null,"':'",null,"'<'","'='","'>'","'?'",
    "'@'",null,null,null,null,null,null,null,null,null,null,null,null,
    null,null,null,null,null,null,null,null,null,null,null,null,null,null,
    "'[ '",null,"' ]'",null,null,null,null,null,null,null,null,null,null,
    null,null,null,null,null,null,null,null,null,null,null,null,null,null,
    null,null,null,null,null,null,"'|'",null,null,null,null,null,null,
    null,null,null,null,null,null,null,null,null,null,null,null,null,null,
    null,null,null,null,null,null,null,null,null,null,null,null,null,null,
    null,null,null,null,null,null,null,null,null,null,null,null,null,null,
    null,null,null,null,null,null,null,null,null,null,null,null,null,null,
    null,null,null,null,null,null,null,null,null,null,null,null,null,null,
    null,null,null,null,null,null,null,null,null,null,null,null,null,null,
    null,null,null,null,null,null,null,null,null,null,null,null,null,null,
    null,null,null,null,null,null,null,null,null,null,null,null,null,null,
    null,null,null,null,null,null,null,null,null,null,null,null,null,null,
    "StringLiteral","IntegerLiteral","DecimalLiteral","DoubleLiteral",
    "NCName","QName","VarName","FOR","IN","IF","THEN","ELSE","SOME",
    "EVERY","SATISFIES","RETURN","AND","OR","TO","DOCUMENT","ELEMENT",
    "ATTRIBUTE","TEXT","COMMENT","PROCESSING_INSTRUCTION","ML","DIV",
    "IDIV","MOD","UNION","EXCEPT","INTERSECT","INSTANCE_OF","TREAT_AS",
    "CASTABLE_AS","CAST_AS","EQ","NE","LT","GT","GE","LE","IS","NODE",
    "DOUBLE_PERIOD","DOUBLE_SLASH","EMPTY_SEQUENCE","ITEM","AXIS_CHILD",
    "AXIS_DESCENDANT","AXIS_ATTRIBUTE","AXIS_SELF",
    "AXIS_DESCENDANT_OR_SELF","AXIS_FOLLOWING_SIBLING","AXIS_FOLLOWING",
    "AXIS_PARENT","AXIS_ANCESTOR","AXIS_PRECEDING_SIBLING",
    "AXIS_PRECEDING","AXIS_ANCESTOR_OR_SELF","AXIS_NAMESPACE",
    "Indicator1","Indicator2","Indicator3","DOCUMENT_NODE",
    "SCHEMA_ELEMENT","SCHEMA_ATTRIBUTE",
	}
	 */
	protected static $yyName = array(
		"end-of-file",null,null,null,null,null,null,null,null,null,null,null,
		null,null,null,null,null,null,null,null,null,null,null,null,null,null,
		null,null,null,null,null,null,null,"'!'",null,null,"'$'",null,null,
		null,"'( '","' )'","'*'","'+'","','","'-'","'.'","'/'",null,null,null,
		null,null,null,null,null,null,null,"':'",null,"'<'","'='","'>'","'?'",
		"'@'",null,null,null,null,null,null,null,null,null,null,null,null,
		null,null,null,null,null,null,null,null,null,null,null,null,null,null,
		"'[ '",null,"' ]'",null,null,null,null,null,null,null,null,null,null,
		null,null,null,null,null,null,null,null,null,null,null,null,null,null,
		null,null,null,null,null,null,"'|'",null,null,null,null,null,null,
		null,null,null,null,null,null,null,null,null,null,null,null,null,null,
		null,null,null,null,null,null,null,null,null,null,null,null,null,null,
		null,null,null,null,null,null,null,null,null,null,null,null,null,null,
		null,null,null,null,null,null,null,null,null,null,null,null,null,null,
		null,null,null,null,null,null,null,null,null,null,null,null,null,null,
		null,null,null,null,null,null,null,null,null,null,null,null,null,null,
		null,null,null,null,null,null,null,null,null,null,null,null,null,null,
		null,null,null,null,null,null,null,null,null,null,null,null,null,null,
		null,null,null,null,null,null,null,null,null,null,null,null,null,null,
		"StringLiteral","IntegerLiteral","DecimalLiteral","DoubleLiteral",
		"NCName","QName","VarName","FOR","IN","IF","THEN","ELSE","SOME",
		"EVERY","SATISFIES","RETURN","AND","OR","TO","DOCUMENT","ELEMENT",
		"ATTRIBUTE","TEXT","COMMENT","PROCESSING_INSTRUCTION","ML","DIV",
		"IDIV","MOD","UNION","EXCEPT","INTERSECT","INSTANCE_OF","TREAT_AS",
		"CASTABLE_AS","CAST_AS","EQ","NE","LT","GT","GE","LE","IS","NODE",
		"DOUBLE_PERIOD","DOUBLE_SLASH","EMPTY_SEQUENCE","ITEM","AXIS_CHILD",
		"AXIS_DESCENDANT","AXIS_ATTRIBUTE","AXIS_SELF",
		"AXIS_DESCENDANT_OR_SELF","AXIS_FOLLOWING_SIBLING","AXIS_FOLLOWING",
		"AXIS_PARENT","AXIS_ANCESTOR","AXIS_PRECEDING_SIBLING",
		"AXIS_PRECEDING","AXIS_ANCESTOR_OR_SELF","AXIS_NAMESPACE",
		"Indicator1","Indicator2","Indicator3","DOCUMENT_NODE",
		"SCHEMA_ELEMENT","SCHEMA_ATTRIBUTE",
	);

	/**
	 * yyname
	 * @param int $token
	 * @return string
	 */
	public static function yyname( $token )
	{
		if ( $token < 0 ||  $token > count( XPath2Parser::$yyName ) ) return "[ illegal ]";
		$name = null;
		if ( ( $name = XPath2Parser::$yyName[ $token ] ) != null ) return $name;
		return "[ unknown ]";
	}

	/** computes list of expected tokens on error by tracing the tables.
		@param int $state for which to compute the list.
		@return array list of token names.
	*/
	protected function yyExpecting ( $state )
	{
		$token = 0;
		$n = 0;
		$len = 0;
		$ok = array_fill( 0, count( XPath2Parser::$yyName ), false );

		if ( ( $n = XPath2Parser::$yySindex[ $state ] ) != 0 )
		{
			for ( $token = $n < 0 ? -$n : 0; ( $token < count( XPath2Parser::$yyName ) && ( $n + $token < count( XPath2Parser::$yyTable ) ) ); ++ $token )
			{
				if ( XPath2Parser::$yyCheck[ $n + $token ] == $token && !$ok[ $token ] && ! is_null( XPath2Parser::$yyName[ $token ] ) )
				{
					++ $len;
					$ok[ $token ] = true;
				}
			}
		}

		if ( ( $n = XPath2Parser::$yyRindex[ $state ] ) != 0 )
		{
			for ( $token = $n < 0 ? -$n : 0; ( $token < count( XPath2Parser::$yyName ) ) && ( $n + $token < count( XPath2Parser::$yyTable ) ); ++ $token )
			{
				if ( XPath2Parser::$yyCheck[ $n + $token ] == $token && ! $ok[ $token ] && ! is_null( XPath2Parser::$yyName[ $token ] ) )
				{
					++ $len;
					$ok[ $token ] = true;
				}
			}
		}

	/**
	 * @var array[ string ] $result
	 */
		$result = array_fill( 0, $len, null );
		for ( $n = $token = 0; $n < $len;  ++ $token )
			if ( $ok[ $token ] ) $result[ $n++ ] = XPath2Parser::$yyName[ $token ];
		return $result;
	}

	/**
	 * The generated parser, with debugging messages.
	 * Maintains a state and a value stack, currently with fixed maximum size.
	 * @param XPath2Parser.yyInput $yyLex scanner.
	 * @param mixed $yyd debug message writer implementing yyDebug, or null.
	 * @return int result of the last reduction, if any.
	 * @throws \lyquidity\XPath2\parser\yyException on irrecoverable parse error.
	 */
	public function yyparseYyd ( $yyLex, $yyd )
	{
		// $this->debug = yyd;
		return $this->yyparse( $yyLex );
	}

	/**
	 * Initial size and increment of the state/value stack [ default 256 ].
	 * This is not final so that it can be overwritten outside of invocations
	 * of yyparse().
	 * @var int yyMax
	 */
	protected $yyMax;

	/**
	 * Executed at the beginning of a reduce action.
	 * Used as $$ = yyDefault( $1 ), prior to the user-specified action, if any.
	 * Can be overwritten to provide deep copy, etc.
	 * @param object $first value for $1, or null.
	 * @return object
	 */
	protected function yyDefault( $first )
	{
		return $first;
	}

	/** the generated parser.
	 *	Maintains a state and a value stack, currently with fixed maximum size.
	 *	@param XPath2Parser.yyInput $yyLex scanner.
	 *	@return int Result of the last reduction, if any.
	 *	@throws \lyquidity\XPath2\parser\yyException on irrecoverable parse error.
	 */
	public function yyparse ( $yyLex )
	{
		if ( $this->yyMax <= 0 ) $this->yyMax = 256;			// initial size
		/**
		 * @var int yyState
		 */
		$yyState = 0;											// state stack ptr
		$yyStates = array_fill( 0, $this->yyMax, 0 );			// state stack
		$yyVal = null;											// value stack ptr
		/**
		 * @var array[ int ] $yyVals
		 */
		$yyVals = array_fill( 0, $this->yyMax, 0 );				// value stack
		$yyToken = -1;											// current input
		$yyErrorFlag = 0;										// #tks to shift

		$options = array();

		$yyTop = 0;
		goto skip;

		yyLoop:
		$yyTop++;

		skip:

		for ( ;; ++ $yyTop )
		{
			if ( $yyTop >= count( $yyStates ) )			// dynamically increase
			{
				$i = array_fill( 0, $this->yyMax, 0 );
				$yyStates = array_merge( $yyStates, $i );
				$o = array_fill( 0, $this->yyMax, 0 );
				$yyVals = array_merge( $yyVals, $i );
			}

			$yyStates[ $yyTop ] = $yyState;
			$yyVals[ $yyTop ] = $yyVal;
			// if ( isset( $this->debug ) && $this->debug != null ) $this->debug->push( $yyState, $yyVal );

			yyDiscarded:
			for ( ;; )
			{	// discarding a token does not change stack
				$yyN = XPath2Parser::$yyDefRed[ $yyState ];
				if ( ( $yyN == 0 ) ) // else [ default ] reduce ( yyN )
				{
					if ( $yyToken < 0 )
					{
						$yyToken = $yyLex->advance() ? $yyLex->token() : 0;
						if ( isset( $this->debug ) && ! is_null( $this->debug ) )
							$this->debug->lex( $yyState, $yyToken, $this->yyname( $yyToken ), $yyLex->value() );
					}

					if ( ( $yyN = XPath2Parser::$yySindex[ $yyState ] ) != 0 && ( ( $yyN += $yyToken ) >= 0 )
						&& ( $yyN < count( XPath2Parser::$yyTable ) ) && ( XPath2Parser::$yyCheck[ $yyN ] == $yyToken ) )
					{
						if ( isset( $this->debug ) && ! is_null( $this->debug ) )
							$this->debug->shift( $yyState, XPath2Parser::$yyTable[ $yyN ], $yyErrorFlag - 1 );

						$yyState = XPath2Parser::$yyTable[ $yyN ];		// shift to yyN
						$yyVal = $yyLex->value();
						$yyToken = -1;
						if ( $yyErrorFlag > 0 ) -- $yyErrorFlag;
						goto yyLoop;
					}

					if ( ( $yyN = XPath2Parser::$yyRindex[ $yyState ] ) != 0 && ( $yyN += $yyToken ) >= 0
						&& $yyN < count( XPath2Parser::$yyTable ) && XPath2Parser::$yyCheck[ $yyN ] == $yyToken )
						$yyN = XPath2Parser::$yyTable[ $yyN ];			// reduce ( yyN )
					else
					{
						switch ( $yyErrorFlag )
						{
							case 0:
								$this->yyerror( "syntax error", $this->yyExpecting( $yyState ) );
								if ( isset( $this->debug ) && ! is_null( $this->debug ) ) $this->debug->error( "syntax error" );
								// goto case 1;
							case 1: case 2:
								$yyErrorFlag = 3;
								do
								{
									if ( ( $yyN = XPath2Parser::$yySindex[ $yyStates[ $yyTop ] ] ) != 0
										&& ( $yyN += Token::yyErrorCode ) >= 0 && $yyN < count( XPath2Parser::$yyTable )
										&& XPath2Parser::$yyCheck[ $yyN ] == Token::yyErrorCode )
									{
										if ( isset( $this->debug ) && ! is_null( $this->debug ) )
										$this->debug->shift( $yyStates[ $yyTop ], XPath2Parser::$yyTable[ $yyN ], 3 );
										$yyState = XPath2Parser::$yyTable[ $yyN ];
										$yyVal = $yyLex->value();
										goto yyLoop;
									}
									if ( isset( $this->debug ) && ! is_null( $this->debug ) ) $this->debug->pop( $yyStates[ $yyTop ] );
								}
								while ( --$yyTop >= 0 );

								if ( isset( $this->debug ) && ! is_null( $this->debug ) ) $this->debug->reject();
								throw new yyException( "irrecoverable syntax error. " . implode( "", $this->errorText ) );

							case 3:
								if ( $yyToken == 0 )
								{
									if ( isset( $this->debug ) && ! is_null( $this->debug ) ) $this->debug->reject();
									throw new yyException( "irrecoverable syntax error at end-of-file" );
								}
								if ( isset( $this->debug ) && ! is_null( $this->debug ) )
									$this->debug->discard( $yyState, $yyToken, $this->yyname( $yyToken ), $yyLex->value() );
								$yyToken = -1;
								goto yyDiscarded;		// leave stack alone
						}
					}
				}

				$yyV = $yyTop + 1 - XPath2Parser::$yyLen[ $yyN ];

				if ( isset( $this->debug ) && ! is_null( $this->debug ) )
				{
					// $this->debug->reduce( $yyState, $yyStates[ $yyV - 1 ], $yyN, XPath2Parser::$yyRule[ $yyN ], XPath2Parser::$yyLen[ $yyN ] );
				}

				$yyVal = $this->yyDefault( $yyV > $yyTop ? null : $yyVals[ $yyV ] );

				// This is used to record the processing order
				$options[] = $yyN;

				// These variables are used for PHP 5.x support.  PHP 7.x is much better about passing variables to lambda functions.
				$me = $this;
				/**
				 * @var XPath2Context $context
				 */
				$context = $this->context;

				// Handy test to be able to focus on $yyN values that represent case numbers
				if ( in_array( $yyN, array(
						1,2,7,8,10,11,12,13,15,16,17,19,21,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,42,44,45,47,48,49,50,52,
						53,55,56,58,60,62,64,65,66,67,68,70,71,72,73,75,76,80,82,83,84,85,86,87,88,89,90,92,93,94,95,96,97,98,100,
						103,105,106,107,109,110,111,112,114,116,123,124,125,127,128,129,130,132,134,135,136,137,140,141,151,152,
						153,154,155,156,157,158,159,160,161,162,163,164,165,166,167,168,169,170,171,172,
					) )
				)
				{
					$x = 1;
				}

				switch( $yyN )
				{
					case 1:

						$this->log("AbstractNode 1");
						$yyVal = AbstractNode::Create( $context, $yyVals[ 0+$yyTop ] );

						break;

					case 2:

						$this->log("ExprNode 2");
						/**
						 * @var ExprNode $expr
						 */
						$expr = $yyVals[ -2+$yyTop ] instanceof ExprNode
							? $yyVals[ -2+$yyTop ]
							:  new ExprNode( $context, $yyVals[ -2+$yyTop ] );
						$expr->Add( $yyVals[ 0+$yyTop ] );
						$yyVal = $expr;

						break;

					case 7:

						$this->log("ForNode 7");
						/**
						 * @var ForNode $node
						 */
						$node = $yyVals[ -2+$yyTop ];
						$node->AddTail( $yyVals[ 0+$yyTop ] );
						$yyVal = $node;

						break;

					case 8:

						$this->log("Get top val 8");
						$yyVal = $yyVals[ 0+$yyTop ];

						break;

					case 10:

						$this->log("ForNode 10");
						/*
						 * ForNode BMS 2017-07-09 Changed from Add to AddTail because multiple 'for' params are being
						 * added to the same for node such as for $foo in 1, $foo in 3, $moo in 5 return $moo + $foo
						 */
						$yyVals[ -2+$yyTop ]->AddTail( $yyVals[ 0+$yyTop ] );
						$yyVal = $yyVals[ -2+$yyTop ];

						break;

					case 11:

						$this->log("ForNode 11");
						$yyVal = new ForNode( $context, /* Tokenizer::VarName */ $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ] );

						break;

					case 12:

						$this->log("UnaryOperatorNode 12");
						/**
						 * @var ForNode $node
						 */
						$node = $yyVals[ -2+$yyTop ];
						$node->AddTail( $yyVals[ 0+$yyTop ] );
						$callback = function( $provider, $arg )
						{
							return CoreFuncs::Some( $arg );
						};
						$yyVal = new UnaryOperatorNode( $context, $callback, $node, XPath2ResultType::Boolean );

						break;

					case 13:

						$this->log("UnaryOperatorNode 13");
						/**
						 * @var ForNode $node
						 */
						$node = $yyVals[ -2+$yyTop ];
						$node->AddTail( $yyVals[ 0+$yyTop ] );
						$callback = function( $provider, $arg )
						{
							return CoreFuncs::Every( $arg );
						};
						$yyVal = new UnaryOperatorNode( $context, $callback, $node, XPath2ResultType::Boolean );

						break;

					case 15:

						$this->log("Move top val 15");
						$yyVals[ -2+$yyTop ]->Add( $yyVals[ 0+$yyTop ] );
						$yyVal = $yyVals[ -2+$yyTop ];

						break;

					case 16:

						$this->log("ForNode 16");
						$yyVal = new ForNode( $context, /* Tokenizer::VarName */$yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ] );

						break;

					case 17:

						$this->log("IfNode 17");
						$yyVal = new IfNode( $context, $yyVals[ -5+$yyTop ], $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ] );

						break;

					case 19:

						$this->log("OrExprNode 19");
						$yyVal = new OrExprNode( $context, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ] );

						break;
					case 21:

						$this->log("AndExprNode 21");
						$yyVal = new AndExprNode( $context, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ] );

						break;

					case 26:

						$this->log("BinaryOperatorNode GeneralEQ 26");
						$callback = function( $provider, $arg1, $arg2 ) use( $context )
						{
							return CoreFuncs::GeneralEQ( $context, $arg1, $arg2 );
						};
						$yyVal = new BinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::Boolean
						);

						break;

					case 27:

						$this->log("BinaryOperatorNode GeneralNE 27");
						$callback = function( $provider, $arg1, $arg2 ) use( $context )
						{
							return CoreFuncs::GeneralNE( $context, $arg1, $arg2 );
						};
						$yyVal = new BinaryOperatorNode( $context, $callback, $yyVals[ -3+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::Boolean );

						break;

					case 28:

						$this->log("BinaryOperatorNode GeneralLT 28");
						$callback = function( $provider, $arg1, $arg2 ) use( $context )
						{
							return CoreFuncs::GeneralLT( $context, $arg1, $arg2 );
						};
						$yyVal = new BinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::Boolean );

						break;

					case 29:

						$this->log("BinaryOperatorNode GeneralLE 29");
						$callback = function( $provider, $arg1, $arg2 ) use( $context )
						{
							return CoreFuncs::GeneralLE( $context, $arg1, $arg2 );
						};
						$yyVal = new BinaryOperatorNode( $context, $callback, $yyVals[ -3+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::Boolean );

						break;

					case 30:

						$this->log("BinaryOperatorNode GeneralGT 30");
						$callback = function( $provider, $arg1, $arg2 ) use( $context )
						{
							return CoreFuncs::GeneralGT( $context, $arg1, $arg2 );
						};
						$yyVal = new BinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::Boolean );

						break;

					case 31:

						$this->log("BinaryOperatorNode GeneralGE 31");
						$callback = function( $provider, $arg1, $arg2 ) use( $context )
						{
							return CoreFuncs::GeneralGE( $context, $arg1, $arg2 );
						};
						$yyVal = new BinaryOperatorNode( $context, $callback, $yyVals[ -3+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::Boolean );

						break;

					case 32:

						$this->log("OperatorEq 32");
						$callback = function( $provider, $arg1, $arg2 )
						{
							return CoreFuncs::OperatorEq( $arg1, $arg2 );
						};
						$yyVal = new AtomizedBinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::Boolean );

						break;

					case 33:

						$this->log("Not OperatorEq 33");
						$callback = function( $provider, $arg1, $arg2 )
						{
							return CoreFuncs::Not( CoreFuncs::OperatorEq( $arg1, $arg2 ) );
						};
						$yyVal = new AtomizedBinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::Boolean );

						break;

					case 34:

						$this->log("OperatorGt 34");
						$callback = function( $provider, $arg1, $arg2 )
						{
							return CoreFuncs::OperatorGt( $arg2, $arg1 );
						};
						$yyVal = new AtomizedBinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::Boolean );

						break;

					case 35:

						$this->log("OperatorGt OperatorEq 35");
						$callback = function( $provider, $arg1, $arg2 )
						{
							return CoreFuncs::OperatorGt( $arg2, $arg1 ) instanceof TrueValue || CoreFuncs::OperatorEq( $arg1, $arg2 ) instanceof TrueValue ? CoreFuncs::$True : CoreFuncs::$False;
						};
						$yyVal = new AtomizedBinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::Boolean );

						break;

					case 36:

						$this->log("OperatorGt 36");
						$callback = function( $provider, $arg1, $arg2 )
						{
							return CoreFuncs::OperatorGt( $arg1, $arg2 );
						};
						$yyVal = new AtomizedBinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::Boolean );

						break;

					case 37:

						$this->log("OperatorGt OperatorEq 37");
						$callback = function( $provider, $arg1, $arg2 )
						{
							return CoreFuncs::OperatorGt( $arg1, $arg2 ) instanceof TrueValue || CoreFuncs::OperatorEq( $arg1, $arg2 ) instanceof TrueValue ? CoreFuncs::$True : CoreFuncs::$False;
						};
						$yyVal = new AtomizedBinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::Boolean );

						break;

					case 38:

						$this->log("SameNode 38");
						$callback = function( $provider, $arg1, $arg2 )
						{
							return CoreFuncs::SameNode( $arg1, $arg2 );
						};
						$yyVal = new SingletonBinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::Boolean );

						break;

					case 39:

						$this->log("PrecedingNode 39");
						$callback = function( $provider, $arg1, $arg2 )
						{
							return CoreFuncs::PrecedingNode( $arg1, $arg2 );
						};
						$yyVal = new SingletonBinaryOperatorNode( $context, $callback, $yyVals[ -3+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::Boolean );

						break;

					case 40:

						$this->log("FollowingNode 40");
						$callback = function( $provider, $arg1, $arg2 )
						{
							return CoreFuncs::FollowingNode( $arg1, $arg2 );
						};
						$yyVal = new SingletonBinaryOperatorNode( $context, $callback, $yyVals[ -3+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::Boolean );

						break;

					case 42:

						$this->log("Plus 42");
						$yyVal = new RangeNode( $context, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ] );

						break;

					case 44:

						$this->log("?? 44");
						$callback = function( $provider, $arg1, $arg2 )
						{
							return ValueProxy::OperatorPlus( ValueProxy::Create( $arg1 ), ValueProxy::Create( $arg2 ) );
						};
						$yyVal = new ArithmeticBinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], array( ArithmeticBinaryOperatorNode::$CLASSNAME, "AdditionResult" ) );
						break;

					case 45:

						$this->log("Minus 45");
						$callback = function( $provider, $arg1, $arg2 )
						{
							return ValueProxy::OperatorSubtract( ValueProxy::Create( $arg1 ), ValueProxy::Create( $arg2 ) );
						};
						$yyVal = new ArithmeticBinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], array( ArithmeticBinaryOperatorNode::$CLASSNAME, "SubtractionResult" ) );

						break;

					case 47:

						$this->log("Multiply 47");
						$callback = function( $provider, $arg1, $arg2 )
						{
							return ValueProxy::OperatorMultiply( ValueProxy::Create( $arg1 ), ValueProxy::Create( $arg2 ) );
						};
						$yyVal = new ArithmeticBinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], array( ArithmeticBinaryOperatorNode::$CLASSNAME, "MultiplyResult" ) );

						break;

					case 48:

						$this->log("Divide 48");
						$callback = function( $provider, $arg1, $arg2 )
						{
							return ValueProxy::OperatorDivide( ValueProxy::Create( $arg1 ), ValueProxy::Create( $arg2 ) );
						};
						$yyVal = new ArithmeticBinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], array( ArithmeticBinaryOperatorNode::$CLASSNAME, "DivisionResult" ) );

						break;

					case 49:

						$this->log("Int Divide 49");
						$callback = function( $provider, $arg1, $arg2 )
						{
							 return ValueProxy::op_IntegerDivide( ValueProxy::Create( $arg1 ), ValueProxy::Create( $arg2 ));
						};
						$yyVal = new ArithmeticBinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], null );

						break;

					case 50:

						$this->log("Mod 50");
						$callback = function( $provider, $arg1, $arg2 )
						{
							return ValueProxy::OperatorMod( ValueProxy::Create( $arg1 ), ValueProxy::Create( $arg2 ) );
						};
						$yyVal = new ArithmeticBinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], null );

						break;

					case 52:

						$this->log("Union 52");
						$callback = function( $provider, $arg1, $arg2 ) use( $context )
						{
							return CoreFuncs::Union( $context, $arg1, $arg2 );
						};
						$yyVal = new OrderedBinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::NodeSet );

						break;

					case 53:

						$this->log("Union 53");
						$callback = function( $provider, $arg1, $arg2 ) use( $context )
						{
							return CoreFuncs::Union( $context, $arg1, $arg2 );
						};
						$yyVal = new OrderedBinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::NodeSet );

						break;

					case 55:

						$this->log("Intersect 55");
						$callback = function( $provider, $arg1, $arg2 ) use( $context )
						{
							return CoreFuncs::Intersect( $context, $arg1, $arg2 );
						};
						$yyVal = new OrderedBinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::NodeSet );

						break;

					case 56:

						$this->log("Except 56");
						$callback = function( $provider, $arg1, $arg2 ) use( $context )
						{
							return CoreFuncs::Except( $context, $arg1, $arg2 );
						};
						$yyVal = new BinaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], $yyVals[ 0+$yyTop ], XPath2ResultType::NodeSet );

						break;

					case 58:

						$this->log("InstanceOf 58");
						/**
						 * @var SequenceType $destType
						 */
						$destType = $yyVals[ 0+$yyTop ];
						$callback = function( $provider, $arg ) use( $context, $destType )
						{
							return CoreFuncs::InstanceOfInstance( $context, $arg, $destType );
						};
						$yyVal = new UnaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], XPath2ResultType::Boolean );

						break;

					case 60:

						$this->log("TreatAs 60");
						/**
						 * @var SequenceType $destType
						 */
						$destType = $yyVals[ 0+$yyTop ];
						$callback = function( $provider, $arg ) use( $context, $destType )
						{
							return CoreFuncs::TreatAs( $context, $arg, $destType );
						};
						$yyVal = new UnaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], CoreFuncs::GetXPath2ResultTypeFromSequenceType( $destType ) );

						break;

					case 62:

						$this->log("CastableExpr 62");
						/**
						 * @var SequenceType $destType
						 */
						$destType = $yyVals[ 0+$yyTop ];
						/**
						 * @var ValueNode $value
						 */
						$value = $yyVals[ -2+$yyTop ];
						/**
						 * @var bool $isString
						 */
						$isString = is_string( $yyVals[ -2+$yyTop ] ) || ( is_null( $value ) && is_string( $value->Content ) );
						if ( is_null( $destType ) )
						   throw XPath2Exception::withErrorCodeAndParam( "XPST0051",Resources::XPST0051, "xs:untyped" );
						if ( $destType->SchemaType == XmlSchema::$AnyType )
						   throw XPath2Exception::withErrorCodeAndParam( "XPST0051", Resources::XPST0051, "xs:anyType" );
						if ( $destType->SchemaType == XmlSchema::$AnySimpleType )
						   throw XPath2Exception::withErrorCodeAndParam( "XPST0051", Resources::XPST0051, "xs:anySimpleType" );
						if ( $destType->TypeCode == XmlTypeCode::AnyAtomicType )
						   throw XPath2Exception::withErrorCodeAndParam( "XPST0051", Resources::XPST0051, "xs:anyAtomicType" );
						if ( $destType->TypeCode == XmlTypeCode::Notation )
						   throw XPath2Exception::withErrorCodeAndParam( "XPST0080", Resources::XPST0080, $destType );
						if (  $destType->Cardinality == XmlTypeCardinality::ZeroOrMore || $destType->Cardinality == XmlTypeCardinality::OneOrMore )
						   throw XPath2Exception::withErrorCodeAndParam( "XPST0080", Resources::XPST0080, $destType );
						$callback = function( $provider, $arg ) use( $context, $destType, $isString )
						{
							return CoreFuncs::Castable( $context, $arg, $destType, $isString );
						};
						$yyVal = new UnaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], XPath2ResultType::Boolean );

						break;

					case 64:

						$this->log("CastExpr 64");
						/**
						 * @var SequenceType $destType
						 */
						$destType = $yyVals[ 0+$yyTop ];
						/**
						 * @var ValueNode $value
						 */
						$value = $yyVals[ -2+$yyTop ];
						/**
						 * @var bool $isString
						 */
						// BMS Changed line 978 and 980.  XmlSchema is SchemaTypes
						$isString = is_string( $yyVals[ -2+$yyTop ] ) || ( is_null( $value ) && is_string( $value->Content ) );
						if ( is_null( $destType ) )
						   throw XPath2Exception::withErrorCodeAndParam( "XPST0051", Resources::XPST0051, "xs:untyped" );
						if ( $destType->SchemaType == XmlSchema::$AnyType )
						   throw XPath2Exception::withErrorCodeAndParam( "XPST0051", Resources::XPST0051, "xs:anyType" );
						if ( $destType->SchemaType == XmlSchema::$AnySimpleType )
						   throw XPath2Exception::withErrorCodeAndParam( "XPST0051", Resources::XPST0051, "xs:anySimpleType" );
						if ( $destType->TypeCode == XmlTypeCode::AnyAtomicType )
						   throw XPath2Exception::withErrorCodeAndParam( "XPST0051", Resources::XPST0051, "xs:anyAtomicType" );
						if ( $destType->TypeCode == XmlTypeCode::Notation )
						   throw XPath2Exception::withErrorCodeAndParam( "XPST0080", Resources::XPST0080, $destType );
						if ( $destType->Cardinality == XmlTypeCardinality::ZeroOrMore || $destType->Cardinality == XmlTypeCardinality::OneOrMore )
						   throw XPath2Exception::withErrorCodeAndParam( "XPST0080", Resources::XPST0080, $destType );

						$callback = function( $provider, $arg ) use( $context, $destType, $isString )
						{
							return CoreFuncs::CastTo( $context, $arg, $destType, $isString );
						};
						$yyVal = new UnaryOperatorNode( $context, $callback, $yyVals[ -2+$yyTop ], CoreFuncs::GetXPath2ResultTypeFromSequenceType( $destType ) );

						break;

					case 65:

						$this->log("Number 65");
						if ( ! is_null( $yyVals[ -1+$yyTop ] ) )
						{
							if ( $yyVals[ -1+$yyTop ] instanceof TrueValue )
							{
								$callback = function( $provider, $arg )
								{
									/***
									 *
									 * @var ValueProxy $value
									 */
									$value = ValueProxy::Create( $arg );
									if ( ! $value->getIsNumeric() )
										throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( $arg, "Number" ) );
									$value = ValueProxy::OperatorMinus( $value );
									return $value;
								};
								$yyVal = new AtomizedUnaryOperatorNode( $context, $callback, $yyVals[ 0+$yyTop ], XPath2ResultType::Number );
							}
							else
							{
								$callback = function( $provider, $arg )
								{
									/**
									 *
									 * @var ValueProxy $result
									 */
									$value = ValueProxy::Create( $arg );
									if ( ! $value->getIsNumeric() )
										throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", Resources::XPTY0004, array( $arg, "Number" ) );
									return $value;
								};
								$yyVal = new AtomizedUnaryOperatorNode( $context, $callback, $yyVals[ 0+$yyTop ], XPath2ResultType::Number );
							}
						}
						else
						{
							$yyVal = $yyVals[ 0+$yyTop ];
						}

						break;

					case 66:

						$this->log("Null 66"); //
						$yyVal = null;

						break;

					case 67:

						$this->log("False 67");
						if ( is_null( $yyVals[ 0+$yyTop ] ) )
							$yyVal = CoreFuncs::$False;
						else
							$yyVal = $yyVals[ 0+$yyTop ];

						break;

					case 68:

						$this->log("Boolean 68");
						if ( is_null( $yyVals[ 0+$yyTop ] ) || $yyVals[ 0+$yyTop ] instanceof FalseValue )
							$yyVal = CoreFuncs::$True;
						else
							$yyVal = CoreFuncs::$False;

						break;

					case 70:

						$this->log("UnaryOperatorNode 70");
						$callback = function( $provider, $arg )
						{
							return XPath2NodeIterator::Create( CoreFuncs::GetRoot( $arg ) );
						};
						$yyVal = new UnaryOperatorNode( $context, $callback, new ContextItemNode( $context ), XPath2ResultType::NodeSet );

						break;

					case 71:

						$this->log("PathExprNode 71");
						/**
						 * @var PathExprNode $yyVal
						 */
						$yyVal = $yyVals[ 0+$yyTop ]
							? new PathExprNode( $context, /* PathStep */ $yyVals[ 0+$yyTop ] )
							: $yyVals[ 0+$yyTop ];

						// BMS 2018-01-05
						// Added so the PathExprNode knows the expression should be evaluated
						// from the root not the current context item position
						$yyVal->startPathFromRoot = true;

						break;

					case 72:

						$this->log("PathStep 72");
						/**
						 * @var PathStep $descendantOrSelf
						 */
						$descendantOrSelf = PathStep::fromNodeType( SequenceTypes::$Node, XPath2ExprType::DescendantOrSelf );
						$descendantOrSelf->AddLast( PathStep::Create( $context, $yyVals[ 0+$yyTop ] ));
						$yyVal = new PathExprNode( $context, $descendantOrSelf );

						// BMS 2018-01-05
						// Added so the PathExprNode knows the expression should be evaluated
						// from the root not the current context item position
						$yyVal->startPathFromRoot = true;

						break;

					case 73:

						$this->log("PathExprNode 73");
						/**
						 * @var PathStep $yyVal
						 */
						$yyVal = $yyVals[ 0+$yyTop ] instanceof PathStep
							? new PathExprNode( $context, /* PathStep */ $yyVals[ 0+$yyTop ] )
							: $yyVals[ 0+$yyTop ];

						break;

					case 75:

						$this->log("PathStep 75");
						/**
						 * @var PathStep $relativePathExpr
						 */
						$relativePathExpr = PathStep::Create( $context, $yyVals[ -2+$yyTop ] );
						$relativePathExpr->AddLast( PathStep::Create( $context, $yyVals[ 0+$yyTop ] ));
						$yyVal = $relativePathExpr;

						break;

					case 76:

						$this->log("relativePathExpr 76");
						/**
						 * @var PathStep $relativePathExpr
						 */
						$relativePathExpr = PathStep::Create( $context, $yyVals[ -2+$yyTop ] );
						/**
						 * @var PathStep $descendantOrSelf
						 */
						$descendantOrSelf = PathStep::fromNodeType( SequenceTypes::$Node, XPath2ExprType::DescendantOrSelf );
						$relativePathExpr->AddLast( $descendantOrSelf );
						$relativePathExpr->AddLast( PathStep::Create( $context, $yyVals[ 0+$yyTop ] ));
						$yyVal = $relativePathExpr;

						break;

					case 80:

						$this->log("CreateFilter 80");
						$yyVal = PathStep::CreateFilter( $context, $yyVals[ -1+$yyTop ], /* List<Object> */ $yyVals[ 0+$yyTop ] );

						break;

					case 82:

						$this->log("CreateFilter 82");
						$yyVal = PathStep::CreateFilter( $context, $yyVals[ -1+$yyTop ], /* List<Object> */ $yyVals[ 0+$yyTop ] );

						break;

					case 83:

						$this->log("PathStep Child 83");
						$yyVal = PathStep::fromNodeType( $yyVals[ 0+$yyTop ], XPath2ExprType::Child );

						break;

					case 84:

						$this->log("PathStep Descendant 84");
						$yyVal = PathStep::fromNodeType( $yyVals[ 0+$yyTop ], XPath2ExprType::Descendant );

						break;

					case 85:

						$this->log("PathStep Attribute 85");
						$yyVal = PathStep::fromNodeType( $yyVals[ 0+$yyTop ], XPath2ExprType::Attribute );

						break;

					case 86:

						$this->log("PathStep Self 86");
						$yyVal = PathStep::fromNodeType( $yyVals[ 0+$yyTop ], XPath2ExprType::Self );

						break;

					case 87:

						$this->log("PathStep DescendantOrSelf 87");
						$yyVal = PathStep::fromNodeType( $yyVals[ 0+$yyTop ], XPath2ExprType::DescendantOrSelf );

						break;

					case 88:

						$this->log("PathStep FollowingSibling 88");
						$yyVal = PathStep::fromNodeType( $yyVals[ 0+$yyTop ], XPath2ExprType::FollowingSibling );

						break;

					case 89:

						$this->log("PathStep Following 89");
						$yyVal = PathStep::fromNodeType( $yyVals[ 0+$yyTop ], XPath2ExprType::Following );

						break;

					case 90:

						$this->log("PathStep NamespaceUri 90");
						$yyVal = PathStep::fromNodeType( $yyVals[ 0+$yyTop ], XPath2ExprType::NamespaceUri );

						break;

					case 92:

						$this->log("PathStep Attribute 92");
						$yyVal = PathStep::fromNodeType( $yyVals[ 0+$yyTop ], XPath2ExprType::Attribute );

						break;

					case 93:

						$this->log("PathStep Child 93");
						$yyVal = PathStep::fromNodeType( $yyVals[ 0+$yyTop ], XPath2ExprType::Child );

						break;

					case 94:

						$this->log("PathStep Parent");
						$yyVal = PathStep::fromNodeType( $yyVals[ 0+$yyTop ], XPath2ExprType::Parent );

						break;

					case 95:

						$this->log("PathStep Ancestor 95");
						$yyVal = PathStep::fromNodeType( $yyVals[ 0+$yyTop ], XPath2ExprType::Ancestor );

						break;

					case 96:

						$this->log("PathStep PrecedingSibling 96");
						$yyVal = PathStep::fromNodeType( $yyVals[ 0+$yyTop ], XPath2ExprType::PrecedingSibling );

						break;

					case 97:

						$this->log("PathStep Preceding 97");
						$yyVal = PathStep::fromNodeType( $yyVals[ 0+$yyTop ], XPath2ExprType::Preceding );

						break;

					case 98:

						$this->log("PathStep AncestorOrSelf 98");
						$yyVal = PathStep::fromNodeType( $yyVals[ 0+$yyTop ], XPath2ExprType::AncestorOrSelf );

						break;

					case 100:

						$this->log("PathStep Parent 100");
						$yyVal = PathStep::fromType( XPath2ExprType::Parent );

						break;

					case 103:

						$this->log("XmlQualifiedName 103");
						/**
						 * @var QName $qualifiedName
						 */
						$qualifiedName = \lyquidity\xml\qname( $yyVals[ 0+$yyTop ], $context->NamespaceManager->getNamespaces() );
						if ( is_null( $qualifiedName ) )
						{
							throw XPath2Exception::withErrorCodeAndParam( "XPST0003", Resources::XPST0003, "Invalid Qualified name '{$yyVals[ 0+$yyTop ]}'");
						}
						// $qualifiedName = QNameParser.Parse( $yyVals[ 0+$yyTop ], $context->NamespaceManager, "", $context->NameTable );
						// $yyVal = XmlQualifiedNameTest::Create( $qualifiedName->localName, empty( $qualifiedName->prefix ) ? $context->NamespaceManager->getDefaultNamespace() : $qualifiedName->namespaceURI );
						$yyVal = XmlQualifiedNameTest::Create( $qualifiedName->localName, empty( $qualifiedName->prefix ) ? null : $qualifiedName->namespaceURI );

						break;

					case 105:

						$this->log("XmlQualifiedNameTest 105");
						$yyVal = XmlQualifiedNameTest::Create( null, null );

						break;

					case 106:

						$this->log("XmlQualifiedNameTest 106");
						/**
						 * @var string $ncname
						 */
						$ncname = $yyVals[ -2+$yyTop ];
						/**
						 * @var string $ns
						 */
						$ns = $context->NamespaceManager->lookupNamespace( $ncname );
						if ( ! $ns )
							  throw XPath2Exception::withErrorCodeAndParam( "XPST0081", Resources::XPST0081, $ncname );
						$yyVal = XmlQualifiedNameTest::Create( null, $ns );

						break;

					case 107:

						$this->log("XmlQualifiedNameTest 107");
						$yyVal = XmlQualifiedNameTest::Create( $context->NameTable->Add( $yyVals[ 0+$yyTop ] ), null );

						break;

					case 109:

						$this->log("FilterExprNode 109");
						$yyVal = new FilterExprNode( $context, $yyVals[ -1+$yyTop ], /* List<Object> */ $yyVals[ 0+$yyTop ] );

						break;

					case 110:

						$this->log("Top 110");
						/**
						 * @var List<Object> $nodes
						 */
						$nodes = array();
						$nodes[] = $yyVals[ 0+$yyTop ];
						$yyVal = $nodes;

						break;

					case 111:

						$this->log("Top 111");
						/**
						 * @var array $nodes
						 */
						$nodes = /* List<Object> */ $yyVals[ -1+$yyTop ];
						$nodes[] = $yyVals[ 0+$yyTop ] ;
						$yyVal = $nodes;

						break;

					case 112:

						$this->log("Top 112");
						$yyVal = $yyVals[ -1+$yyTop ];

						break;

					case 114:

						$this->log("VarRefNode 114");
						$yyVal = new VarRefNode( $context, /* Tokenizer::VarName */ $yyVals[ 0+$yyTop ] );

						break;

					case 116:

						$this->log("ContextItemNode 116");
						$yyVal = new ContextItemNode( $context );

						break;

					case 123:

						$this->log("Top 123");
						$yyVal = $yyVals[ 0+$yyTop ];

						break;

					case 124:

						$this->log("ValueNode for empty sequence");
						$yyVal = new ValueNode( $context, Undefined::getValue() );

						break;

					case 125:

						$this->log("Top 125");
						$yyVal = $yyVals[ -1+$yyTop ];

						break;

					case 127:

						$this->log("XmlQualifiedName 127");
						/**
						 * @var QName $identity
						 */
						$identity = \lyquidity\xml\qname( $yyVals[ -2+$yyTop ], $context->NamespaceManager->getNamespaces() );
						if ( is_null( $identity ) )
						{
							throw XPath2Exception::withErrorCodeAndParam( "XPST0003", Resources::XPST0003, "Invalid Qualified name '{$yyVals[ -2+$yyTop ]}'");
						}
						/**
						 * @var string $ns
						 */
						$ns = $identity->namespaceURI;
						if ( empty( $identity->namespaceURI ) || empty( $identity->prefix ) )
						{
							$ns = XmlReservedNs::xQueryFunc;
						}
						$yyVal = FuncNode::withoutArgs( $context, $identity->localName, $ns );

						break;

					case 128:

						$this->log("XmlQualifiedName 128");
						/**
						 * @var QName $identity
						 */
						$identity = \lyquidity\xml\qname( $yyVals[ -3+$yyTop ], $context->NamespaceManager->getNamespaces() );
						if ( is_null( $identity ) )
						{
							throw XPath2Exception::withErrorCodeAndParam( "XPST0003", Resources::XPST0003, "Invalid Qualified name '{$yyVals[ -3+$yyTop ]}'");
						}
						if ( ! $identity->prefix )
						{
							$identity->prefix = "fn";
							$identity->namespaceURI = XmlReservedNs::xQueryFunc;
						}
						/**
						 * @var string $ns
						 */
						$ns = $identity->namespaceURI;
						if ( empty( $identity->namespaceURI ) )
						{
							$ns = XmlReservedNs::xQueryFunc;
						}
						/**
						 * @var array $args
						 */
						$args = /* List<Object> */ $yyVals[ -1+$yyTop ];

						// Look for a function first
						$yyVal = FuncNode::withArgs( $context, $identity->localName, $ns, /* List<Object> */ $args, false );
						/**
						 * @var XmlSchemaObject $schemaType
						 */
						$schemaType;
						if ( is_null( $yyVal ) )
						{
							if ( count( $args ) == 1 && CoreFuncs::TryProcessTypeName( $context, new \lyquidity\xml\qname( $identity->prefix, $ns, $identity->localName ), false, /* out */ $schemaType ))
							{
								/**
								 * @var SequenceType $seqtype
								 */
								// $seqtype = new SequenceType( /* XmlSchemaSimpleType */ $schemaType, XmlTypeCardinality::One, null );
								$seqtype = SequenceType::WithSchemaTypeWithCardinality( $schemaType, XmlTypeCardinality::One );
								if ( is_null( $seqtype ) )
									throw XPath2Exception::withErrorCodeAndParam( "XPST0051", Resources::XPST0051, "untyped" );

								if ( $seqtype->TypeCode == XmlTypeCode::Notation )
									throw XPath2Exception::withErrorCodeAndParam( "XPST0051", Resources::XPST0051, "NOTATION" );

								$callback = function( $provider, $arg ) use( $context, $seqtype )
								{
									return CoreFuncs::CastToItem( $context, $arg, $seqtype );
								};
								$yyVal = new UnaryOperatorNode( $context, $callback, $args[0], CoreFuncs::GetXPath2ResultTypeFromSequenceType( $seqtype ));
							}
							else
								throw XPath2Exception::withErrorCodeAndParams( "XPST0017", Resources::XPST0017, array( $identity->prefix, $identity->localName, $identity->namespaceURI ) );
						}

						break;

					case 129:

						$this->log("List 129");
						/**
						 * @var List<Object> $list
						 */
						$list = array();
						$list[] = $yyVals[ 0+$yyTop ];
						$yyVal = $list;

						break;

					case 130:

						$this->log("List 130");
						/**
						 * @var array $list
						 */
						$list = $yyVals[ -2+$yyTop ];
						$list[] = $yyVals[ 0+$yyTop ];
						$yyVal = $list;

						break;

					case 132:

						$this->log("Cardinality ZeroOrOne 132");
						/**
						 * @var SequenceType $type
						 */
						$type = $yyVals[ -1+$yyTop ];
						$type->Cardinality = XmlTypeCardinality::ZeroOrOne;
						$yyVal = $type;

						break;

					case 134:

						$this->log("Cardinality ZeroOrMore 134");
						/**
						 * @var SequenceType $type
						 */
						$type = $yyVals[ -1+$yyTop ];
						$type->Cardinality = XmlTypeCardinality::ZeroOrMore;
						$yyVal = $type;

						break;

					case 135:

						$this->log("Cardinality OneOrMore 135");
						/**
						 * @var SequenceType $type
						 */
						$type = $yyVals[ -1+$yyTop ];
						$type->Cardinality = XmlTypeCardinality::OneOrMore;
						$yyVal = $type;

						break;

					case 136:

						$this->log("Cardinality ZeroOrOne 136");
						/**
						 * @var SequenceType $type
						 */
						$type = $yyVals[ -1+$yyTop ];
						$type->Cardinality = XmlTypeCardinality::ZeroOrOne;
						$yyVal = $type;

						break;

					case 137:

						$this->log("SequenceType 137");
						$yyVal = SequenceTypes::$Void;

						break;

					case 140:

						$this->log("SequenceType 140");
						// $yyVal = new SequenceType( XmlTypeCode::Item );
						$yyVal = SequenceType::WithTypeCode( XmlTypeCode::Item );

						break;

					case 141:

						$this->log("SequenceType 141");
						/**
						 * @var XmlSchemaType xmlType
						 */
						$xmlType = null;
						CoreFuncs::TryProcessTypeName( $context, ( string )$yyVals[ 0+$yyTop ], true, /* out */ $xmlType );
						if ( is_null( $xmlType ) )
						{
							throw new \InvalidArgumentException( "\$xmlType is in XPath.php case 141" );
						}
						// $yyVal = new SequenceType( $xmlType, XmlTypeCardinality::One, null );
						$yyVal = SequenceType::WithSchemaTypeWithCardinality( $xmlType, XmlTypeCardinality::One );

						break;

					case 151:

						$this->log("SequenceType 151");
						$yyVal = SequenceTypes::$Node;

						break;

					case 152:

						$this->log("SequenceType 152");
						$yyVal = SequenceTypes::$Document;

						break;

					case 153:

						$this->log("SequenceType 153");
						/**
						 * @var SequenceType $type
						 */
						$type = $yyVals[ -1+$yyTop ];
						$type->TypeCode = XmlTypeCode::Document;

						break;

					case 154:

						$this->log("Document 154");
						/**
						 * @var SequenceType $type
						 */
						$type = $yyVals[ -1+$yyTop ];
						$type->TypeCode = XmlTypeCode::Document;

						break;

					case 155:

						$this->log("Text 155");
						$yyVal = SequenceTypes::$Text;

						break;

					case 156:

						$this->log("Comment 156");
						$yyVal = SequenceTypes::$Comment;

						break;

					case 157:

						$this->log("");
						$yyVal = SequenceTypes::$ProcessingInstruction;

						break;

					case 158:

						$this->log("ProcessingInstruction 158");
						/**
						 * @var XmlQualifiedNameTest $nameTest
						 */
						$nameTest = XmlQualifiedNameTest::Create( (string)$yyVals[ -1+$yyTop ], null );
						// $yyVal = new SequenceType( XmlTypeCode::ProcessingInstruction, $nameTest );
						$yyVal = SequenceType::WithTypeCodeWithQNameTest( XmlTypeCode::ProcessingInstruction, $nameTest );

						break;

					case 159:

						$this->log("ProcessingInstruction 159");
						/**
						 * @var XmlQualifiedNameTest $nameTest
						 */
						$nameTest = XmlQualifiedNameTest::Create( (string)$yyVals[ -1+$yyTop ], null );
						// $yyVal = new SequenceType( XmlTypeCode::ProcessingInstruction, $nameTest );
						$yyVal = SequenceType::WithTypeCodeWithQNameTest( XmlTypeCode::ProcessingInstruction, $nameTest );

						break;

					case 160:

						$this->log("Element 160");
						$yyVal = SequenceTypes::$Element;

						break;

					case 161:

						$this->log("Element 161");
						// $yyVal = new SequenceType( XmlTypeCode::Element, /* XmlQualifiedNameTest */ $yyVals[ -1+$yyTop ] );
						$yyVal = SequenceType::WithTypeCodeWithQNameTest( XmlTypeCode::Element, $yyVals[ -1+$yyTop ] );

						break;

					case 162:

						$this->log("SequenceType 162");
						/**
						 * @var XmlSchemaType xmlType
						 */
						$xmlType = null;
						CoreFuncs::TryProcessTypeName( $context, ( string )$yyVals[ -1+$yyTop ], true, /* out */ $xmlType );
						// $yyVal = new SequenceType( XmlTypeCode::Element, /* XmlQualifiedNameTest */ $yyVals[ -3+$yyTop ], xmlType, false );
						$yyVal = SequenceType::WithTypeCodeWithQNameTestWithSchemaTypeWithIsOptional( XmlTypeCode::Element, /* XmlQualifiedNameTest */ $yyVals[ -3+$yyTop ], /*XmlSchemaType*/ $xmlType, false );

						break;

					case 163:

						$this->log("SequenceType 163");
						/**
						 * @var XmlSchemaType xmlType
						 */
						$xmlType = null;
						CoreFuncs::TryProcessTypeName( $context, ( string )$yyVals[ -2+$yyTop ], true, /* out */ $xmlType );
						// $yyVal = new SequenceType( XmlTypeCode::Element, /* XmlQualifiedNameTest */ $yyVals[ -4+$yyTop ], $xmlType, true );
						$yyVal = SequenceType::WithTypeCodeWithQNameTestWithSchemaTypeWithIsOptional( XmlTypeCode::Element, /* XmlQualifiedNameTest */ $yyVals[ -4+$yyTop ], /*XmlSchemaType*/ $xmlType, false );

						break;

					case 164:

						$this->log("XmlQualifiedNameTest 164");
						$qname = \lyquidity\xml\qname( (string )$yyVals[ 0+$yyTop ], $context->NamespaceManager->getNamespaces() );
						if ( is_null( $qname ) )
						{
							throw XPath2Exception::withErrorCodeAndParam( "XPST0003", Resources::XPST0003, "Invalid Qualified name '{$yyVals[ 0+$yyTop ]}'");
						}
						// $yyVal = XmlQualifiedNameTest::Create( $qname->localName, empty( $qname->prefix ) ? $context->NamespaceManager->getDefaultNamespace() : $qname->namespaceURI );
						$yyVal = XmlQualifiedNameTest::Create( $qname->localName, empty( $qname->prefix ) ? null : $qname->namespaceURI );

						break;
					case 165:

						$this->log("XmlQualifiedNameTest 165");
						$yyVal = XmlQualifiedNameTest::Create( null, null );

						break;

					case 166:

						$this->log("SequenceType 166");
						$yyVal = SequenceTypes::$Attribute;

						break;

					case 167:

						$this->log("SequenceType 167");
						// $yyVal = new SequenceType( XmlTypeCode::Attribute, /* XmlQualifiedNameTest */ $yyVals[ -1+$yyTop ] );
						$yyVal = SequenceType::WithTypeCodeWithQNameTest( XmlTypeCode::Attribute, /* XmlQualifiedNameTest */ $yyVals[ -1+$yyTop ] );

						break;

					case 168:

						$this->log("SequenceType 168");
						/**
						 * @var XmlSchemaType xmlType
						 */
						$xmlType = null;
						CoreFuncs::TryProcessTypeName( $context, ( string )$yyVals[ -1+$yyTop ], true, /* out */ $xmlType );
						// $yyVal = new SequenceType( XmlTypeCode::Attribute, /* XmlQualifiedNameTest */ $yyVals[ -3+$yyTop ], xmlType );
						$yyVal = SequenceType::WithTypeCodeWithQNameTestWithSchemaType( XmlTypeCode::Attribute, /* XmlQualifiedNameTest */ $yyVals[ -3+$yyTop ], /* XmlSchemaType */ $xmlType );

						break;

					case 169:

						$this->log("XmlQualifiedNameTest 169");
						$qname = \lyquidity\xml\qname(  (string )$yyVals[ 0+$yyTop ], $context->NamespaceManager->getNamespaces() );
						if ( is_null( $qname ) )
						{
							throw XPath2Exception::withErrorCodeAndParam( "XPST0003", Resources::XPST0003, "Invalid Qualified name '{$yyVals[ 0+$yyTop ]}'");
						}
						// $yyVal = XmlQualifiedNameTest::Create( $qname );
						// $yyVal = XmlQualifiedNameTest::Create( $qname->localName, empty( $qname->prefix ) ? $context->NamespaceManager->getDefaultNamespace() : $qname->namespaceURI );
						$yyVal = XmlQualifiedNameTest::Create( $qname->localName, empty( $qname->prefix ) ? null : $qname->namespaceURI );

						break;

					case 170:

						$this->log("XmlQualifiedNameTest 170");
						$yyVal = XmlQualifiedNameTest::Create( null, null );

						break;

					case 171:

						$this->log("XmlQualifiedName 171");
						/**
						 * @var QName $qname
						 */
						$qname = \lyquidity\xml\qname( $yyVals[ -1+$yyTop ], $context->NamespaceManager->getNamespaces() );
						if ( is_null( $qname ) )
						{
							throw XPath2Exception::withErrorCodeAndParam( "XPST0003", Resources::XPST0003, "Invalid Qualified name '{$yyVals[ -1+$yyTop ]}'");
						}
						/**
						 * @var XmlSchemaElement $schemaElement
						 */
						$schemaElement = /* XmlSchemaElement */ $context->SchemaSet->getGlobalElement( $qname );
						if ( is_null( $schemaElement ) )
						{
							throw XPath2Exception::withErrorCodeAndParam( "XPST0008", Resources::XPST0008, $qname );
						}
						// $yyVal = new SequenceType( $schemaElement );
						$yyVal = SequenceType::WithElement( $schemaElement );

						break;

					case 172:

						$this->log("XmlQualifiedName 172");
						/**
						 * @var QName $qname
						 */
						$qname = \lyquidity\xml\qname( $yyVals[ -1+$yyTop ], $context->NamespaceManager->getNamespaces() );
						if ( is_null( $qname ) )
						{
							throw XPath2Exception::withErrorCodeAndParam( "XPST0003", Resources::XPST0003, "Invalid Qualified name '{$yyVals[ -1+$yyTop ]}'");
						}
						/**
						 * @var XmlSchemaAttribute $schemaAttribute
						 */
						$schemaAttribute = /* XmlSchemaAttribute */ $context->SchemaSet->getGlobalAttribute( $qname );
						if ( is_null( $schemaAttribute ) )
						{
							throw XPath2Exception::withErrorCodeAndParam( "XPST0008", Resources::XPST0008, $qname );
						}
						// $yyVal = new SequenceType( $schemaAttribute );
						$yyVal = SequenceType::WithAttribute( $schemaAttribute );

						break;
				}

				unset( $me );
				unset( $context );

				$yyTop -= XPath2Parser::$yyLen[ $yyN ];
				$yyState = $yyStates[ $yyTop ];
				$yyM = XPath2Parser::$yyLhs[ $yyN ];

				if ( $yyState == 0 && $yyM == 0 )
				{
					//t	if ( isset( $this->debug ) && $this->debug != null ) $this->debug->shift( 0, $yyFinal );
					$yyState = XPath2Parser::$yyFinal;
					if ( $yyToken < 0 )
					{
						$yyToken = $yyLex->advance() ? $yyLex->token() : 0;
						//t if ( isset( $this->debug ) && $this->debug != null )
						//t	$this->debug.lex( $yyState, $yyToken, $this->yyname( $yyToken ), $yyLex.value() );
					}

					if ( $yyToken == 0 )
					{
						//t if ( isset( $this->debug ) && $this->debug != null ) $this->debug.accept( $yyVal );
						return $yyVal;
					}

					goto yyLoop;
				}
				if ( ( ( $yyN = XPath2Parser::$yyGindex[ $yyM ] ) != 0 ) && ( ( $yyN += $yyState ) >= 0 )
					 && ( $yyN < count( XPath2Parser::$yyTable ) ) && ( XPath2Parser::$yyCheck[ $yyN ] == $yyState ) )
					$yyState = XPath2Parser::$yyTable[ $yyN ];
				else
					$yyState = XPath2Parser::$yyDgoto[ $yyM ];

				//t if ( isset( $this->debug ) && $this->debug != null ) $this->debug->shift( XPath2Parser::$yyStates[ $yyTop ], $yyState );
				goto yyLoop;
			}
		}
	}

	/**
	 * @var int [] $yyLhs  = {					   -1,
		0,    0,    1,    1,    1,    1,    2,    6,    7,    7,
		8,    3,    3,    9,    9,   10,    4,    5,    5,   11,
	   11,   12,   12,   12,   12,   15,   15,   15,   15,   15,
	   15,   14,   14,   14,   14,   14,   14,   16,   16,   16,
	   13,   13,   17,   17,   17,   18,   18,   18,   18,   18,
	   19,   19,   19,   20,   20,   20,   21,   21,   22,   22,
	   24,   24,   25,   25,   27,   28,   28,   28,   29,   30,
	   30,   30,   30,   31,   31,   31,   32,   32,   33,   33,
	   33,   33,   35,   35,   35,   35,   35,   35,   35,   35,
	   35,   39,   39,   37,   37,   37,   37,   37,   37,   40,
	   38,   38,   42,   42,   43,   43,   43,   34,   34,   36,
	   36,   45,   44,   44,   44,   44,   44,   46,   46,   51,
	   51,   51,   47,   48,   48,   49,   50,   50,   52,   52,
	   26,   26,   23,   23,   23,   23,   23,   54,   54,   54,
	   53,   41,   41,   41,   41,   41,   41,   41,   41,   41,
	   63,   55,   55,   55,   62,   61,   60,   60,   60,   56,
	   56,   56,   56,   64,   64,   57,   57,   57,   67,   67,
	   58,   59,   68,   66,   65,
		}
	 */
	static  $yyLhs  = array(					   -1,
		0,    0,    1,    1,    1,    1,    2,    6,    7,    7,
		8,    3,    3,    9,    9,   10,    4,    5,    5,   11,
	   11,   12,   12,   12,   12,   15,   15,   15,   15,   15,
	   15,   14,   14,   14,   14,   14,   14,   16,   16,   16,
	   13,   13,   17,   17,   17,   18,   18,   18,   18,   18,
	   19,   19,   19,   20,   20,   20,   21,   21,   22,   22,
	   24,   24,   25,   25,   27,   28,   28,   28,   29,   30,
	   30,   30,   30,   31,   31,   31,   32,   32,   33,   33,
	   33,   33,   35,   35,   35,   35,   35,   35,   35,   35,
	   35,   39,   39,   37,   37,   37,   37,   37,   37,   40,
	   38,   38,   42,   42,   43,   43,   43,   34,   34,   36,
	   36,   45,   44,   44,   44,   44,   44,   46,   46,   51,
	   51,   51,   47,   48,   48,   49,   50,   50,   52,   52,
	   26,   26,   23,   23,   23,   23,   23,   54,   54,   54,
	   53,   41,   41,   41,   41,   41,   41,   41,   41,   41,
	   63,   55,   55,   55,   62,   61,   60,   60,   60,   56,
	   56,   56,   56,   64,   64,   57,   57,   57,   67,   67,
	   58,   59,   68,   66,   65,
	);

	/**
	 * @var int [] $yyLen = {					2,
		1,    3,    1,    1,    1,    1,    3,    2,    1,    3,
		4,    4,    4,    1,    3,    4,    8,    1,    3,    1,
		3,    1,    1,    1,    1,    3,    4,    3,    4,    3,
		4,    3,    3,    3,    3,    3,    3,    3,    4,    4,
		1,    3,    1,    3,    3,    1,    3,    3,    3,    3,
		1,    3,    3,    1,    3,    3,    1,    3,    1,    3,
		1,    3,    1,    3,    2,    0,    2,    2,    1,    1,
		2,    2,    1,    1,    3,    3,    1,    1,    1,    2,
		1,    2,    2,    2,    2,    2,    2,    2,    2,    2,
		1,    2,    1,    2,    2,    2,    2,    2,    1,    1,
		1,    1,    1,    1,    1,    3,    3,    1,    2,    1,
		2,    3,    1,    1,    1,    1,    1,    1,    1,    1,
		1,    1,    2,    2,    3,    1,    3,    4,    1,    3,
		1,    2,    1,    2,    2,    2,    1,    1,    1,    1,
		1,    1,    1,    1,    1,    1,    1,    1,    1,    1,
		3,    3,    4,    4,    3,    3,    3,    4,    4,    3,
		4,    6,    7,    1,    1,    3,    4,    6,    1,    1,
		4,    4,    1,    1,    1,
		}
	 */
	static  $yyLen = array(					2,
		1,    3,    1,    1,    1,    1,    3,    2,    1,    3,
		4,    4,    4,    1,    3,    4,    8,    1,    3,    1,
		3,    1,    1,    1,    1,    3,    4,    3,    4,    3,
		4,    3,    3,    3,    3,    3,    3,    3,    4,    4,
		1,    3,    1,    3,    3,    1,    3,    3,    3,    3,
		1,    3,    3,    1,    3,    3,    1,    3,    1,    3,
		1,    3,    1,    3,    2,    0,    2,    2,    1,    1,
		2,    2,    1,    1,    3,    3,    1,    1,    1,    2,
		1,    2,    2,    2,    2,    2,    2,    2,    2,    2,
		1,    2,    1,    2,    2,    2,    2,    2,    1,    1,
		1,    1,    1,    1,    1,    3,    3,    1,    2,    1,
		2,    3,    1,    1,    1,    1,    1,    1,    1,    1,
		1,    1,    2,    2,    3,    1,    3,    4,    1,    3,
		1,    2,    1,    2,    2,    2,    1,    1,    1,    1,
		1,    1,    1,    1,    1,    1,    1,    1,    1,    1,
		3,    3,    4,    4,    3,    3,    3,    4,    4,    3,
		4,    6,    7,    1,    1,    3,    4,    6,    1,    1,
		4,    4,    1,    1,    1,
	);

	/**
	 * @var int [] $yyDefRed = {					 0,
		0,    0,    0,    0,    0,    0,    0,    1,    3,    4,
		5,    0,    0,    0,   20,    0,   23,   24,   25,    0,
		0,    0,    0,   54,    0,    0,    0,    0,    0,    0,
		0,    9,    0,    0,    0,   14,    0,   67,   68,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,  119,
	  120,  121,  122,    0,    0,    0,    0,    0,    0,    0,
		0,  100,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,  126,   65,   69,    0,   74,   77,
	   78,    0,    0,   93,   91,   99,  101,  102,  104,    0,
	  113,  114,  115,  116,  117,  118,  142,  143,  144,  145,
	  146,  147,  148,  149,  150,    0,    0,    0,    0,    0,
		0,    0,    2,    0,    7,   21,   32,   33,   34,   36,
	   37,   35,   38,   26,    0,    0,    0,   28,    0,    0,
	   30,    0,    0,    0,    0,    0,    0,    0,    0,    0,
	   56,   55,  141,  137,  140,   58,  139,  138,    0,   60,
	   62,    0,   64,    0,    0,    0,    0,    0,    0,    0,
		0,    0,  103,   83,   84,   85,   86,   87,   88,   89,
	   94,   95,   96,   97,   98,   90,    0,    0,    0,  123,
	  124,    0,    0,   92,    0,    0,    0,    0,    0,  110,
		0,    0,    0,   10,    0,    0,   12,   15,   13,   27,
	   29,   39,   31,   40,  134,  135,  136,  132,  106,  127,
	  129,    0,  174,  160,  165,    0,  164,  173,  166,  170,
		0,  169,  155,  156,    0,    0,  157,  151,  152,    0,
		0,    0,    0,  125,  107,   76,   75,    0,  111,   11,
		0,   16,    0,  128,    0,  161,    0,  167,  159,  158,
	  153,  154,  171,  172,  112,    0,  130,  175,    0,    0,
		0,  162,    0,  168,   17,  163,
		}
	 */
	static  $yyDefRed = array(					 0,
		0,    0,    0,    0,    0,    0,    0,    1,    3,    4,
		5,    0,    0,    0,   20,    0,   23,   24,   25,    0,
		0,    0,    0,   54,    0,    0,    0,    0,    0,    0,
		0,    9,    0,    0,    0,   14,    0,   67,   68,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,  119,
	  120,  121,  122,    0,    0,    0,    0,    0,    0,    0,
		0,  100,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,  126,   65,   69,    0,   74,   77,
	   78,    0,    0,   93,   91,   99,  101,  102,  104,    0,
	  113,  114,  115,  116,  117,  118,  142,  143,  144,  145,
	  146,  147,  148,  149,  150,    0,    0,    0,    0,    0,
		0,    0,    2,    0,    7,   21,   32,   33,   34,   36,
	   37,   35,   38,   26,    0,    0,    0,   28,    0,    0,
	   30,    0,    0,    0,    0,    0,    0,    0,    0,    0,
	   56,   55,  141,  137,  140,   58,  139,  138,    0,   60,
	   62,    0,   64,    0,    0,    0,    0,    0,    0,    0,
		0,    0,  103,   83,   84,   85,   86,   87,   88,   89,
	   94,   95,   96,   97,   98,   90,    0,    0,    0,  123,
	  124,    0,    0,   92,    0,    0,    0,    0,    0,  110,
		0,    0,    0,   10,    0,    0,   12,   15,   13,   27,
	   29,   39,   31,   40,  134,  135,  136,  132,  106,  127,
	  129,    0,  174,  160,  165,    0,  164,  173,  166,  170,
		0,  169,  155,  156,    0,    0,  157,  151,  152,    0,
		0,    0,    0,  125,  107,   76,   75,    0,  111,   11,
		0,   16,    0,  128,    0,  161,    0,  167,  159,  158,
	  153,  154,  171,  172,  112,    0,  130,  175,    0,    0,
		0,  162,    0,  168,   17,  163,
	);

	/**
	 * @var int [] $yyDgoto  = {					  7,
		8,    9,   10,   11,   12,   13,   31,   32,   35,   36,
	   14,   15,   16,   17,   18,   19,   20,   21,   22,   23,
	   24,   25,  176,   26,   27,  181,   28,   29,  106,  107,
	  108,  109,  110,  111,  112,  219,  113,  114,  115,  116,
	  117,  118,  119,  120,  220,  121,  122,  123,  124,  125,
	  126,  242,  178,  179,  127,  128,  129,  130,  131,  132,
	  133,  134,  135,  246,  289,  247,  251,  252,
		}
	 */
	protected static  $yyDgoto  = array(					  7,
		8,    9,   10,   11,   12,   13,   31,   32,   35,   36,
	   14,   15,   16,   17,   18,   19,   20,   21,   22,   23,
	   24,   25,  176,   26,   27,  181,   28,   29,  106,  107,
	  108,  109,  110,  111,  112,  219,  113,  114,  115,  116,
	  117,  118,  119,  120,  220,  121,  122,  123,  124,  125,
	  126,  242,  178,  179,  127,  128,  129,  130,  131,  132,
	  133,  134,  135,  246,  289,  247,  251,  252,
	);

	/**
	 * @var int [] $yySindex = {				-16,
	   11,   14,   24,   24,   31,   31,   34,    0,    0,    0,
		0, -190, -184, -172,    0, 1280,    0,    0,    0,  -40,
	 -232, -115, -165,    0, -182, -185, -174, -168, 1934, -137,
	   84,    0,  -16, -133,  -20,    0,  -19,    0,    0,  -16,
	   31,  -16,   31,   31,   31,   31,   31,   31,   31,   31,
	   31,   77,  -28,  -31,   31,   31,   31,   31,   31,   31,
	   31,   31,   31,   31,   31, -213, -213, -122, -122,    0,
		0,    0,    0,   90,  102,  112,  119,  123,  125,  129,
	  130,    0, 2112,  222,  222,  222,  222,  222,  222,  222,
	  222,  222,  222,  222,  222,  222,  132,  133,  135,  -87,
	  -37, 2112,  222,  120,    0,    0,    0,  -45,    0,    0,
		0,   88,   88,    0,    0,    0,    0,    0,    0,   88,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,  -85,   11,  -18,  -84,  -16,
	   24,  -16,    0, -172,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,   31,   31,   31,    0,   31,   31,
		0,   50, -232, -232, -115, -115, -115, -115, -165, -165,
		0,    0,    0,    0,    0,    0,    0,    0, -199,    0,
		0,  124,    0,  147,  -23,   -4,   -2,  142,  149,    2,
	  150,  -45,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,  -41,  -69,  -66,    0,
		0,   41,  -45,    0,  -63, 2112, 2112,  -16,   88,    0,
	   88,   88,  -16,    0,  -61,  -16,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,   45,    0,    0,    0,   58,    0,    0,    0,    0,
	   59,    0,    0,    0,  159,  163,    0,    0,    0,  164,
	  166,  167,  168,    0,    0,    0,    0,  -34,    0,    0,
	  -16,    0,  -16,    0,  -51,    0,  -51,    0,    0,    0,
		0,    0,    0,    0,    0,  -56,    0,    0,  -22,  172,
	  -16,    0,  173,    0,    0,    0,
		}
	 */
	protected static  $yySindex = array(				-16,
	   11,   14,   24,   24,   31,   31,   34,    0,    0,    0,
		0, -190, -184, -172,    0, 1280,    0,    0,    0,  -40,
	 -232, -115, -165,    0, -182, -185, -174, -168, 1934, -137,
	   84,    0,  -16, -133,  -20,    0,  -19,    0,    0,  -16,
	   31,  -16,   31,   31,   31,   31,   31,   31,   31,   31,
	   31,   77,  -28,  -31,   31,   31,   31,   31,   31,   31,
	   31,   31,   31,   31,   31, -213, -213, -122, -122,    0,
		0,    0,    0,   90,  102,  112,  119,  123,  125,  129,
	  130,    0, 2112,  222,  222,  222,  222,  222,  222,  222,
	  222,  222,  222,  222,  222,  222,  132,  133,  135,  -87,
	  -37, 2112,  222,  120,    0,    0,    0,  -45,    0,    0,
		0,   88,   88,    0,    0,    0,    0,    0,    0,   88,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,  -85,   11,  -18,  -84,  -16,
	   24,  -16,    0, -172,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,   31,   31,   31,    0,   31,   31,
		0,   50, -232, -232, -115, -115, -115, -115, -165, -165,
		0,    0,    0,    0,    0,    0,    0,    0, -199,    0,
		0,  124,    0,  147,  -23,   -4,   -2,  142,  149,    2,
	  150,  -45,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,  -41,  -69,  -66,    0,
		0,   41,  -45,    0,  -63, 2112, 2112,  -16,   88,    0,
	   88,   88,  -16,    0,  -61,  -16,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,   45,    0,    0,    0,   58,    0,    0,    0,    0,
	   59,    0,    0,    0,  159,  163,    0,    0,    0,  164,
	  166,  167,  168,    0,    0,    0,    0,  -34,    0,    0,
	  -16,    0,  -16,    0,  -51,    0,  -51,    0,    0,    0,
		0,    0,    0,    0,    0,  -56,    0,    0,  -22,  172,
	  -16,    0,  173,    0,    0,    0,
	);

	/**
	 * @var int [] $yyRindex = {			   2009,
		0,    0,    0,    0, 2009, 2009,    0,    0,    0,    0,
		0,  453,    0, 1098,    0,  667,    0,    0,    0, 1873,
	 1792, 1432, 1330,    0, 1063,  989,  955,  887,    0,    0,
	  -57,    0, 2009,    0,    0,    0,    0,    0,    0, 2009,
	 2009, 2009, 2009, 2009, 2009, 2009, 2009, 2009, 2009, 2009,
	 2009,    0, 2009, 2009, 2009, 2009, 2009, 2009, 2009, 2009,
	 2009, 2009, 2009, 2009, 2009,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    1,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
	 2009,  515,    0,   36,    0,    0,    0,  549,    0,    0,
		0,   71,  106,    0,    0,    0,    0,    0,    0,  141,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0, 2009,
		0, 2009,    0, 1109,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0, 2009, 2009, 2009,    0, 2009, 2009,
		0, 1907, 1826, 1839, 1466, 1500, 1547, 1745, 1364, 1398,
		0,    0,    0,    0,    0,    0,    0,    0, 1023,    0,
		0,  921,    0,    0, 2009,    0,    0,    0,    0,    0,
		0,  583,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,  620,    0,    0,    0,    0, 2009,  177,    0,
	  444,  480, 2009,    0,    0, 2009,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
	 2009,    0, 2009,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
	 2009,    0,    0,    0,    0,    0,
		}
	 */
	protected static  $yyRindex = array(			   2009,
		0,    0,    0,    0, 2009, 2009,    0,    0,    0,    0,
		0,  453,    0, 1098,    0,  667,    0,    0,    0, 1873,
	 1792, 1432, 1330,    0, 1063,  989,  955,  887,    0,    0,
	  -57,    0, 2009,    0,    0,    0,    0,    0,    0, 2009,
	 2009, 2009, 2009, 2009, 2009, 2009, 2009, 2009, 2009, 2009,
	 2009,    0, 2009, 2009, 2009, 2009, 2009, 2009, 2009, 2009,
	 2009, 2009, 2009, 2009, 2009,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    1,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
	 2009,  515,    0,   36,    0,    0,    0,  549,    0,    0,
		0,   71,  106,    0,    0,    0,    0,    0,    0,  141,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0, 2009,
		0, 2009,    0, 1109,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0, 2009, 2009, 2009,    0, 2009, 2009,
		0, 1907, 1826, 1839, 1466, 1500, 1547, 1745, 1364, 1398,
		0,    0,    0,    0,    0,    0,    0,    0, 1023,    0,
		0,  921,    0,    0, 2009,    0,    0,    0,    0,    0,
		0,  583,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,  620,    0,    0,    0,    0, 2009,  177,    0,
	  444,  480, 2009,    0,    0, 2009,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
	 2009,    0, 2009,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
	 2009,    0,    0,    0,    0,    0,
	);

	/**
	 * @var int [] $yyGindex = {				-26,
	  -29,    0,    0,    0,    0,    0,    0,   80,  212,   82,
	  178,  182, 1876,    0,    0,    0,  171,   78,   -3,   74,
	   79,    0,  161,    0,    0,  162,    0,  140,    0,    0,
	  -67,  -62,    0,    0,    0,  -92,    0,  599,    0,    0,
	   91,    0,    0,    0, -149,    0,    0,    0,    0,    0,
		0,    0,   93,    0,    0,   33,    0,   38,    0,    0,
		0,    0,    0,    0,  -21,   47,    0,   40,
		}
	 */
	protected static  $yyGindex = array(				-26,
	  -29,    0,    0,    0,    0,    0,    0,   80,  212,   82,
	  178,  182, 1876,    0,    0,    0,  171,   78,   -3,   74,
	   79,    0,  161,    0,    0,  162,    0,  140,    0,    0,
	  -67,  -62,    0,    0,    0,  -92,    0,  599,    0,    0,
	   91,    0,    0,    0, -149,    0,    0,    0,    0,    0,
		0,    0,   93,    0,    0,   33,    0,   38,    0,    0,
		0,    0,    0,    0,  -21,   47,    0,   40,
	);

	/**
	 * @var int[] $yyTable = {					259,
	  103,  217,   56,  211,   57,    5,  138,    6,   63,   40,
	  143,    5,  145,    6,    5,  192,    6,  240,  292,    5,
	  221,    6,  225,  141,  141,   40,    5,  222,    6,  159,
	  160,  157,  156,  103,  213,  105,  244,  245,  249,  250,
	  293,  103,  257,  103,  103,  103,   30,  103,  173,   58,
	   59,   60,   61,   33,  165,  166,  167,  168,  285,   34,
	  103,  103,  103,   76,   77,   78,   79,   80,  105,  269,
	   79,  269,  269,    5,  212,    6,  105,   40,  105,  105,
	  105,  264,  105,   41,   40,  274,   81,   42,  273,  174,
	  175,  103,   56,  103,   57,  105,  105,  105,  276,  278,
	   43,  275,  277,   79,   67,   81,   66,   97,   98,   99,
	  227,   79,  229,   79,   79,   79,   68,   79,  235,  236,
	  237,   64,   65,   69,  103,  136,  105,  137,  105,  139,
	   79,   79,   79,  163,  164,  169,  170,  155,   81,  173,
	  108,  185,  171,  172,   38,   39,   81,  184,   81,   81,
	   81,  186,   81,  266,  267,  241,  177,  177,  187,  105,
	  182,  182,  188,   79,  189,   81,   81,   81,  190,  191,
	   62,  207,  208,  108,  209,  210,   80,  215,  218,  223,
	  226,  108,  253,  108,  108,  108,  238,  108,  239,  254,
	  258,  268,  243,  270,   79,  248,  272,  265,   81,  279,
	  108,  108,  108,  280,  281,  271,  282,  283,  284,   80,
	  288,  291,  294,  296,    8,   37,  224,   80,  144,   80,
	   80,   80,  228,   80,  146,  162,    1,  180,    2,   81,
	  183,    3,    4,  108,   55,   76,   80,   80,   80,  260,
		1,  286,    2,  287,  261,    3,    4,    1,  263,    2,
	  140,  142,    3,    4,  262,  290,  216,  243,  255,  248,
		0,  295,  256,  104,  108,    0,    0,    0,  103,   80,
		0,  103,  103,  103,  103,  103,    0,    0,    0,    0,
	   98,    0,  103,  103,  103,  103,  103,  103,  103,  103,
	  103,  103,  103,  103,  103,  103,  103,  103,  103,  103,
	   80,    0,  103,  105,    0,    0,  105,  105,  105,  105,
	  105,    0,    0,    0,    0,    0,    0,  105,  105,  105,
	  105,  105,  105,  105,  105,  105,  105,  105,  105,  105,
	  105,  105,  105,  105,  105,    0,    0,  105,   79,    0,
		0,   79,   79,   79,   79,   79,    0,    0,    0,    0,
		0,    0,   79,   79,   79,   79,   79,   79,   79,   79,
	   79,   79,   79,   79,   79,   79,   79,   79,   79,   79,
		0,    0,   79,   81,    0,    0,   81,   81,   81,   81,
	   81,    0,    0,    0,    0,    0,    0,   81,   81,   81,
	   81,   81,   81,   81,   81,   81,   81,   81,   81,   81,
	   81,   81,   81,   81,   81,    0,    0,   81,  108,    0,
		0,  108,  108,  108,  108,  108,    0,    0,    0,    0,
		0,    0,  108,  108,  108,  108,  108,  108,  108,  108,
	  108,  108,  108,  108,  108,  108,  108,  108,  108,  108,
		0,    0,  108,   82,   80,    0,    0,   80,   80,   80,
	   80,   80,    6,    0,    0,    0,    0,    0,   80,   80,
	   80,   80,   80,   80,   80,   80,   80,   80,   80,   80,
	   80,   80,   80,   80,   80,   80,   82,    0,   80,  109,
		0,    0,   74,  193,   82,    0,   82,   82,   82,    0,
	   82,    0,    0,    6,    0,    0,    6,    0,   76,   77,
	   78,   79,   80,   82,   82,   82,    0,    0,    0,    0,
		0,    0,  109,    0,   70,    0,    0,    0,    0,    0,
	  109,   81,  109,  109,  109,    0,  109,    0,    0,    0,
		0,    0,    0,    0,    0,    0,   82,    0,    0,  109,
	  109,  109,   97,   98,   99,    6,    0,   70,   73,    0,
		0,    0,    0,    0,    0,   70,    0,   70,   70,   70,
		0,    0,    0,    0,    0,    0,    0,   82,    0,    0,
		0,    0,  109,    0,   70,   70,   70,    0,    0,    0,
		0,   73,   72,    0,    0,    0,    0,    0,    0,   73,
		0,   73,   73,   73,    0,    0,    0,    0,    0,    0,
		0,    0,    0,  109,    0,    0,    0,   70,   73,   73,
	   73,    0,    0,    0,    0,   72,    0,    0,    0,   71,
		0,    0,    0,   72,    0,   72,   72,   72,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,   70,    0,
		0,   73,   72,   72,   72,    0,    0,    0,    0,    0,
		0,    0,   71,    0,    0,    0,    0,    0,    0,    0,
	   71,    0,   71,   71,   71,    0,   22,    0,    0,    0,
		0,    0,   73,    0,    0,   72,    0,    0,    0,   71,
	   71,   71,  194,  195,  196,  197,  198,  199,  200,  201,
	  202,  203,  204,  205,  206,    0,    0,    0,    0,    0,
		0,  214,    0,    0,    0,    0,   72,   22,    0,    0,
	   22,   82,   71,    0,   82,   82,   82,   82,   82,    0,
		6,    0,    0,    6,    6,   82,   82,   82,   82,   82,
	   82,   82,   82,   82,   82,   82,   82,   82,   82,   82,
	   82,   82,   82,   71,    0,   82,    0,  109,    0,    0,
	  109,  109,  109,  109,  109,    0,    0,    0,    0,   22,
		0,  109,  109,  109,  109,  109,  109,  109,  109,  109,
	  109,  109,  109,  109,  109,  109,  109,  109,  109,    0,
		0,  109,   70,    0,    0,   70,   70,   70,   70,   70,
		0,    0,    0,    0,    0,    0,   70,   70,   70,   70,
	   70,   70,   70,   70,   70,   70,   70,   70,   70,   70,
	   70,   70,   70,   70,    0,    0,   73,    0,    0,   73,
	   73,   73,   73,   73,    0,    0,    0,    0,    0,    0,
	   73,   73,   73,   73,   73,   73,   73,   73,   73,   73,
	   73,   73,   73,   73,   73,   73,   73,   73,    0,    0,
	   72,    0,    0,   72,   72,   72,   72,   72,    0,    0,
		0,    0,    0,    0,   72,   72,   72,   72,   72,   72,
	   72,   72,   72,   72,   72,   72,   72,   72,   72,   72,
	   72,   72,    0,    0,    0,    0,   63,   71,    0,    0,
	   71,   71,   71,   71,   71,    0,    0,    0,    0,    0,
		0,   71,   71,   71,   71,   71,   71,   71,   71,   71,
	   71,   71,   71,   71,   71,   71,   71,   71,   71,   63,
	  131,    0,    0,    0,    0,    0,    0,   63,    0,   63,
	   63,   63,    0,    0,   22,    0,    0,   22,   22,   22,
	   22,    0,    0,    0,    0,    0,   63,   63,   63,    0,
		0,    0,    0,  131,   61,    0,    0,    0,    0,    0,
		0,  131,    0,  131,  131,  131,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,   63,
	  131,  131,  131,    0,    0,    0,    0,   61,   59,    0,
		0,    0,    0,    0,    0,   61,    0,   61,   61,   61,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
	   63,    0,    0,  131,   61,   61,   61,    0,    0,    0,
		0,   59,  133,    0,    0,    0,    0,    0,    0,   59,
		0,   59,   59,   59,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,  131,    0,    0,   61,   59,   59,
	   59,    0,    0,    0,    0,  133,    0,    0,    0,    0,
		0,    0,   57,  133,    0,  133,  133,  133,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,   61,    0,
		0,   59,  133,  133,  133,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,   57,    0,   18,    0,    0,
		0,    0,    0,   57,    0,   57,   57,   57,   19,    0,
		0,    0,   59,    0,    0,  133,    0,    0,    0,    0,
		0,    0,   57,   57,   57,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,   18,    0,
		0,   18,    0,    0,    0,    0,  133,    0,    0,   19,
		0,    0,   19,    0,   63,   57,    0,   63,   63,   63,
	   63,   63,    0,    0,    0,    0,    0,    0,   63,   63,
	   63,   63,   63,   63,   63,   63,   63,   63,    0,   63,
	   63,   63,   63,   63,   63,   63,   57,    0,  131,    0,
	   18,  131,  131,  131,  131,  131,    0,    0,    0,    0,
		0,   19,  131,  131,  131,  131,  131,  131,  131,  131,
	  131,  131,    0,  131,  131,  131,  131,  131,  131,  131,
		0,    0,   61,    0,    0,   61,   61,   61,   61,   61,
		0,    0,    0,    0,    0,    0,   61,   61,   61,   61,
	   61,   61,   61,   61,   61,    0,    0,   61,   61,   61,
	   61,   61,   61,   61,    0,    0,   59,    0,    0,   59,
	   59,   59,   59,   59,    0,    0,    0,    0,    0,    0,
	   59,   59,   59,   59,   59,   59,   59,   59,    0,    0,
		0,   59,   59,   59,   59,   59,   59,   59,    0,    0,
	  133,    0,    0,  133,  133,  133,  133,  133,    0,    0,
		0,    0,    0,    0,  133,  133,  133,  133,  133,  133,
	  133,  133,   52,    0,    0,  133,  133,  133,  133,  133,
	  133,  133,    0,    0,    0,    0,    0,    0,    0,   51,
	   57,    0,    0,   57,   57,   57,   57,   57,    0,   53,
	   51,   54,    0,    0,   57,   57,   57,   57,   57,   57,
	   57,    0,    0,    0,    0,   57,   57,   57,   57,   57,
	   57,   57,   51,   52,    0,   18,    0,    0,   18,   18,
	   51,   18,   51,   51,   51,    0,   19,    0,    0,   19,
	   19,    0,   19,    0,    0,    0,    0,    0,    0,   51,
	   51,   51,    0,    0,    0,    0,   52,   53,    0,    0,
		0,    0,    0,    0,   52,    0,   52,   52,   52,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,   51,   52,   52,   52,    0,    0,    0,    0,
	   53,   46,    0,    0,    0,    0,    0,    0,   53,    0,
	   53,   53,   53,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,   51,    0,    0,   52,   53,   53,   53,
		0,    0,    0,    0,   46,   47,    0,    0,    0,    0,
		0,    0,   46,    0,   46,   46,   46,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,   52,    0,    0,
	   53,   46,   46,   46,    0,    0,    0,    0,   47,   48,
		0,    0,    0,    0,    0,    0,   47,    0,   47,   47,
	   47,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,   53,    0,    0,   46,   47,   47,   47,    0,    0,
		0,    0,   48,    0,    0,    0,    0,    0,    0,    0,
	   48,    0,   48,   48,   48,    0,   49,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,   47,   48,
	   48,   48,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,   44,   45,   46,   47,   48,   49,   50,   49,
		0,    0,    0,    0,    0,    0,    0,   49,    0,   49,
	   49,   49,   48,    0,    0,    0,    0,   51,    0,    0,
	   51,   51,   51,   51,   51,    0,   49,   49,   49,    0,
		0,   51,   51,   51,   51,   51,    0,    0,    0,    0,
		0,    0,   51,   51,   51,   51,   51,   51,   51,    0,
		0,   52,    0,    0,   52,   52,   52,   52,   52,   49,
		0,    0,    0,    0,    0,   52,   52,   52,   52,   52,
		0,    0,    0,    0,    0,    0,   52,   52,   52,   52,
	   52,   52,   52,    0,    0,   53,    0,    0,   53,   53,
	   53,   53,   53,    0,    0,    0,    0,    0,    0,   53,
	   53,   53,   53,   53,    0,    0,    0,    0,    0,    0,
	   53,   53,   53,   53,   53,   53,   53,    0,    0,   46,
		0,    0,   46,   46,   46,   46,   46,    0,    0,    0,
		0,    0,    0,   46,   46,   46,   46,    0,    0,    0,
		0,    0,    0,    0,   46,   46,   46,   46,   46,   46,
	   46,    0,    0,   47,    0,    0,   47,   47,   47,   47,
	   47,    0,    0,    0,   50,    0,    0,   47,   47,   47,
	   47,    0,    0,    0,    0,    0,    0,    0,   47,   47,
	   47,   47,   47,   47,   47,    0,    0,   48,    0,    0,
	   48,   48,   48,   48,   48,    0,    0,   50,    0,    0,
		0,   48,   48,   48,   48,   50,    0,   50,   50,   50,
		0,   43,   48,   48,   48,   48,   48,   48,   48,    0,
		0,    0,    0,    0,   50,   50,   50,    0,    0,    0,
		0,    0,    0,    0,   49,    0,    0,   49,   49,   49,
	   49,   49,    0,    0,   43,   44,    0,    0,   49,   49,
	   49,   49,   43,    0,   43,   43,   43,   50,   45,   49,
	   49,   49,   49,   49,   49,   49,    0,    0,    0,    0,
		0,   43,   43,   43,    0,    0,    0,    0,   44,    0,
		0,    0,    0,    0,    0,    0,   44,    0,   44,   44,
	   44,   45,   41,    0,    0,    0,    0,    0,    0,   45,
		0,   45,   45,   45,   43,   44,   44,   44,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,   45,   45,
	   45,    0,    0,    0,    0,   41,   42,    0,    0,    0,
		0,    0,    0,   41,    0,    0,   41,    0,   44,  147,
	  148,  149,  150,  151,  152,  153,  154,    0,  158,  161,
		0,   45,   41,   41,   41,    0,    0,    0,    0,   42,
		0,    0,    0,    0,    0,    0,    0,   42,    0,    0,
	   42,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,   41,   42,   42,   42,  100,
		0,    0,    0,  101,    0,  104,    0,    0,    0,  105,
	  102,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,  103,    0,   42,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,   50,    0,    0,   50,   50,   50,   50,   50,
		0,    0,    0,    0,    0,    0,   50,   50,   50,   50,
	  230,  231,  232,    0,  233,  234,    0,   50,   50,   50,
	   50,   50,   50,   50,   66,    0,    0,    0,   66,    0,
	   66,    0,    0,    0,   66,   66,    0,    0,    0,   43,
		0,    0,   43,   43,   43,   43,   43,    0,    0,    0,
		0,    0,   66,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,   43,   43,   43,   43,   43,   43,
	   43,    0,    0,   44,    0,    0,   44,   44,   44,   44,
	   44,    0,    0,    0,    0,    0,   45,    0,    0,   45,
	   45,   45,   45,   45,    0,    0,    0,    0,   44,   44,
	   44,   44,   44,   44,   44,    0,    0,    0,    0,    0,
		0,   45,   45,   45,   45,   45,   45,   45,    0,    0,
	   41,    0,    0,   41,   41,   41,   41,  100,    0,    0,
		0,  101,    0,  104,    0,    0,    0,  105,    0,    0,
		0,    0,    0,    0,    0,   41,   41,   41,   41,   41,
	   41,   41,    0,    0,   42,  103,    0,   42,   42,   42,
	   42,    0,    0,    0,    0,    0,    0,    0,    0,    0,
	   70,   71,   72,   73,   74,   75,    0,    0,    0,   42,
	   42,   42,   42,   42,   42,   42,    0,    0,    0,    0,
	   76,   77,   78,   79,   80,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,   81,   82,   83,    0,    0,   84,   85,
	   86,   87,   88,   89,   90,   91,   92,   93,   94,   95,
	   96,    0,    0,    0,   97,   98,   99,    0,    0,    0,
		0,    0,    0,    0,    0,   66,   66,   66,   66,   66,
	   66,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,   66,   66,   66,   66,   66,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,   66,   66,
	   66,    0,    0,   66,   66,   66,   66,   66,   66,   66,
	   66,   66,   66,   66,   66,   66,    0,    0,    0,   66,
	   66,   66,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,   70,   71,
	   72,   73,   74,   75,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,   76,   77,
	   78,   79,   80,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,   81,   82,    0,    0,    0,   84,   85,   86,   87,
	   88,   89,   90,   91,   92,   93,   94,   95,   96,    0,
		0,    0,   97,   98,   99,
		}
	 */
	protected static  $yyTable = array(					259,
	  103,  217,   56,  211,   57,    5,  138,    6,   63,   40,
	  143,    5,  145,    6,    5,  192,    6,  240,  292,    5,
	  221,    6,  225,  141,  141,   40,    5,  222,    6,  159,
	  160,  157,  156,  103,  213,  105,  244,  245,  249,  250,
	  293,  103,  257,  103,  103,  103,   30,  103,  173,   58,
	   59,   60,   61,   33,  165,  166,  167,  168,  285,   34,
	  103,  103,  103,   76,   77,   78,   79,   80,  105,  269,
	   79,  269,  269,    5,  212,    6,  105,   40,  105,  105,
	  105,  264,  105,   41,   40,  274,   81,   42,  273,  174,
	  175,  103,   56,  103,   57,  105,  105,  105,  276,  278,
	   43,  275,  277,   79,   67,   81,   66,   97,   98,   99,
	  227,   79,  229,   79,   79,   79,   68,   79,  235,  236,
	  237,   64,   65,   69,  103,  136,  105,  137,  105,  139,
	   79,   79,   79,  163,  164,  169,  170,  155,   81,  173,
	  108,  185,  171,  172,   38,   39,   81,  184,   81,   81,
	   81,  186,   81,  266,  267,  241,  177,  177,  187,  105,
	  182,  182,  188,   79,  189,   81,   81,   81,  190,  191,
	   62,  207,  208,  108,  209,  210,   80,  215,  218,  223,
	  226,  108,  253,  108,  108,  108,  238,  108,  239,  254,
	  258,  268,  243,  270,   79,  248,  272,  265,   81,  279,
	  108,  108,  108,  280,  281,  271,  282,  283,  284,   80,
	  288,  291,  294,  296,    8,   37,  224,   80,  144,   80,
	   80,   80,  228,   80,  146,  162,    1,  180,    2,   81,
	  183,    3,    4,  108,   55,   76,   80,   80,   80,  260,
		1,  286,    2,  287,  261,    3,    4,    1,  263,    2,
	  140,  142,    3,    4,  262,  290,  216,  243,  255,  248,
		0,  295,  256,  104,  108,    0,    0,    0,  103,   80,
		0,  103,  103,  103,  103,  103,    0,    0,    0,    0,
	   98,    0,  103,  103,  103,  103,  103,  103,  103,  103,
	  103,  103,  103,  103,  103,  103,  103,  103,  103,  103,
	   80,    0,  103,  105,    0,    0,  105,  105,  105,  105,
	  105,    0,    0,    0,    0,    0,    0,  105,  105,  105,
	  105,  105,  105,  105,  105,  105,  105,  105,  105,  105,
	  105,  105,  105,  105,  105,    0,    0,  105,   79,    0,
		0,   79,   79,   79,   79,   79,    0,    0,    0,    0,
		0,    0,   79,   79,   79,   79,   79,   79,   79,   79,
	   79,   79,   79,   79,   79,   79,   79,   79,   79,   79,
		0,    0,   79,   81,    0,    0,   81,   81,   81,   81,
	   81,    0,    0,    0,    0,    0,    0,   81,   81,   81,
	   81,   81,   81,   81,   81,   81,   81,   81,   81,   81,
	   81,   81,   81,   81,   81,    0,    0,   81,  108,    0,
		0,  108,  108,  108,  108,  108,    0,    0,    0,    0,
		0,    0,  108,  108,  108,  108,  108,  108,  108,  108,
	  108,  108,  108,  108,  108,  108,  108,  108,  108,  108,
		0,    0,  108,   82,   80,    0,    0,   80,   80,   80,
	   80,   80,    6,    0,    0,    0,    0,    0,   80,   80,
	   80,   80,   80,   80,   80,   80,   80,   80,   80,   80,
	   80,   80,   80,   80,   80,   80,   82,    0,   80,  109,
		0,    0,   74,  193,   82,    0,   82,   82,   82,    0,
	   82,    0,    0,    6,    0,    0,    6,    0,   76,   77,
	   78,   79,   80,   82,   82,   82,    0,    0,    0,    0,
		0,    0,  109,    0,   70,    0,    0,    0,    0,    0,
	  109,   81,  109,  109,  109,    0,  109,    0,    0,    0,
		0,    0,    0,    0,    0,    0,   82,    0,    0,  109,
	  109,  109,   97,   98,   99,    6,    0,   70,   73,    0,
		0,    0,    0,    0,    0,   70,    0,   70,   70,   70,
		0,    0,    0,    0,    0,    0,    0,   82,    0,    0,
		0,    0,  109,    0,   70,   70,   70,    0,    0,    0,
		0,   73,   72,    0,    0,    0,    0,    0,    0,   73,
		0,   73,   73,   73,    0,    0,    0,    0,    0,    0,
		0,    0,    0,  109,    0,    0,    0,   70,   73,   73,
	   73,    0,    0,    0,    0,   72,    0,    0,    0,   71,
		0,    0,    0,   72,    0,   72,   72,   72,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,   70,    0,
		0,   73,   72,   72,   72,    0,    0,    0,    0,    0,
		0,    0,   71,    0,    0,    0,    0,    0,    0,    0,
	   71,    0,   71,   71,   71,    0,   22,    0,    0,    0,
		0,    0,   73,    0,    0,   72,    0,    0,    0,   71,
	   71,   71,  194,  195,  196,  197,  198,  199,  200,  201,
	  202,  203,  204,  205,  206,    0,    0,    0,    0,    0,
		0,  214,    0,    0,    0,    0,   72,   22,    0,    0,
	   22,   82,   71,    0,   82,   82,   82,   82,   82,    0,
		6,    0,    0,    6,    6,   82,   82,   82,   82,   82,
	   82,   82,   82,   82,   82,   82,   82,   82,   82,   82,
	   82,   82,   82,   71,    0,   82,    0,  109,    0,    0,
	  109,  109,  109,  109,  109,    0,    0,    0,    0,   22,
		0,  109,  109,  109,  109,  109,  109,  109,  109,  109,
	  109,  109,  109,  109,  109,  109,  109,  109,  109,    0,
		0,  109,   70,    0,    0,   70,   70,   70,   70,   70,
		0,    0,    0,    0,    0,    0,   70,   70,   70,   70,
	   70,   70,   70,   70,   70,   70,   70,   70,   70,   70,
	   70,   70,   70,   70,    0,    0,   73,    0,    0,   73,
	   73,   73,   73,   73,    0,    0,    0,    0,    0,    0,
	   73,   73,   73,   73,   73,   73,   73,   73,   73,   73,
	   73,   73,   73,   73,   73,   73,   73,   73,    0,    0,
	   72,    0,    0,   72,   72,   72,   72,   72,    0,    0,
		0,    0,    0,    0,   72,   72,   72,   72,   72,   72,
	   72,   72,   72,   72,   72,   72,   72,   72,   72,   72,
	   72,   72,    0,    0,    0,    0,   63,   71,    0,    0,
	   71,   71,   71,   71,   71,    0,    0,    0,    0,    0,
		0,   71,   71,   71,   71,   71,   71,   71,   71,   71,
	   71,   71,   71,   71,   71,   71,   71,   71,   71,   63,
	  131,    0,    0,    0,    0,    0,    0,   63,    0,   63,
	   63,   63,    0,    0,   22,    0,    0,   22,   22,   22,
	   22,    0,    0,    0,    0,    0,   63,   63,   63,    0,
		0,    0,    0,  131,   61,    0,    0,    0,    0,    0,
		0,  131,    0,  131,  131,  131,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,   63,
	  131,  131,  131,    0,    0,    0,    0,   61,   59,    0,
		0,    0,    0,    0,    0,   61,    0,   61,   61,   61,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
	   63,    0,    0,  131,   61,   61,   61,    0,    0,    0,
		0,   59,  133,    0,    0,    0,    0,    0,    0,   59,
		0,   59,   59,   59,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,  131,    0,    0,   61,   59,   59,
	   59,    0,    0,    0,    0,  133,    0,    0,    0,    0,
		0,    0,   57,  133,    0,  133,  133,  133,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,   61,    0,
		0,   59,  133,  133,  133,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,   57,    0,   18,    0,    0,
		0,    0,    0,   57,    0,   57,   57,   57,   19,    0,
		0,    0,   59,    0,    0,  133,    0,    0,    0,    0,
		0,    0,   57,   57,   57,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,   18,    0,
		0,   18,    0,    0,    0,    0,  133,    0,    0,   19,
		0,    0,   19,    0,   63,   57,    0,   63,   63,   63,
	   63,   63,    0,    0,    0,    0,    0,    0,   63,   63,
	   63,   63,   63,   63,   63,   63,   63,   63,    0,   63,
	   63,   63,   63,   63,   63,   63,   57,    0,  131,    0,
	   18,  131,  131,  131,  131,  131,    0,    0,    0,    0,
		0,   19,  131,  131,  131,  131,  131,  131,  131,  131,
	  131,  131,    0,  131,  131,  131,  131,  131,  131,  131,
		0,    0,   61,    0,    0,   61,   61,   61,   61,   61,
		0,    0,    0,    0,    0,    0,   61,   61,   61,   61,
	   61,   61,   61,   61,   61,    0,    0,   61,   61,   61,
	   61,   61,   61,   61,    0,    0,   59,    0,    0,   59,
	   59,   59,   59,   59,    0,    0,    0,    0,    0,    0,
	   59,   59,   59,   59,   59,   59,   59,   59,    0,    0,
		0,   59,   59,   59,   59,   59,   59,   59,    0,    0,
	  133,    0,    0,  133,  133,  133,  133,  133,    0,    0,
		0,    0,    0,    0,  133,  133,  133,  133,  133,  133,
	  133,  133,   52,    0,    0,  133,  133,  133,  133,  133,
	  133,  133,    0,    0,    0,    0,    0,    0,    0,   51,
	   57,    0,    0,   57,   57,   57,   57,   57,    0,   53,
	   51,   54,    0,    0,   57,   57,   57,   57,   57,   57,
	   57,    0,    0,    0,    0,   57,   57,   57,   57,   57,
	   57,   57,   51,   52,    0,   18,    0,    0,   18,   18,
	   51,   18,   51,   51,   51,    0,   19,    0,    0,   19,
	   19,    0,   19,    0,    0,    0,    0,    0,    0,   51,
	   51,   51,    0,    0,    0,    0,   52,   53,    0,    0,
		0,    0,    0,    0,   52,    0,   52,   52,   52,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,   51,   52,   52,   52,    0,    0,    0,    0,
	   53,   46,    0,    0,    0,    0,    0,    0,   53,    0,
	   53,   53,   53,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,   51,    0,    0,   52,   53,   53,   53,
		0,    0,    0,    0,   46,   47,    0,    0,    0,    0,
		0,    0,   46,    0,   46,   46,   46,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,   52,    0,    0,
	   53,   46,   46,   46,    0,    0,    0,    0,   47,   48,
		0,    0,    0,    0,    0,    0,   47,    0,   47,   47,
	   47,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,   53,    0,    0,   46,   47,   47,   47,    0,    0,
		0,    0,   48,    0,    0,    0,    0,    0,    0,    0,
	   48,    0,   48,   48,   48,    0,   49,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,   47,   48,
	   48,   48,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,   44,   45,   46,   47,   48,   49,   50,   49,
		0,    0,    0,    0,    0,    0,    0,   49,    0,   49,
	   49,   49,   48,    0,    0,    0,    0,   51,    0,    0,
	   51,   51,   51,   51,   51,    0,   49,   49,   49,    0,
		0,   51,   51,   51,   51,   51,    0,    0,    0,    0,
		0,    0,   51,   51,   51,   51,   51,   51,   51,    0,
		0,   52,    0,    0,   52,   52,   52,   52,   52,   49,
		0,    0,    0,    0,    0,   52,   52,   52,   52,   52,
		0,    0,    0,    0,    0,    0,   52,   52,   52,   52,
	   52,   52,   52,    0,    0,   53,    0,    0,   53,   53,
	   53,   53,   53,    0,    0,    0,    0,    0,    0,   53,
	   53,   53,   53,   53,    0,    0,    0,    0,    0,    0,
	   53,   53,   53,   53,   53,   53,   53,    0,    0,   46,
		0,    0,   46,   46,   46,   46,   46,    0,    0,    0,
		0,    0,    0,   46,   46,   46,   46,    0,    0,    0,
		0,    0,    0,    0,   46,   46,   46,   46,   46,   46,
	   46,    0,    0,   47,    0,    0,   47,   47,   47,   47,
	   47,    0,    0,    0,   50,    0,    0,   47,   47,   47,
	   47,    0,    0,    0,    0,    0,    0,    0,   47,   47,
	   47,   47,   47,   47,   47,    0,    0,   48,    0,    0,
	   48,   48,   48,   48,   48,    0,    0,   50,    0,    0,
		0,   48,   48,   48,   48,   50,    0,   50,   50,   50,
		0,   43,   48,   48,   48,   48,   48,   48,   48,    0,
		0,    0,    0,    0,   50,   50,   50,    0,    0,    0,
		0,    0,    0,    0,   49,    0,    0,   49,   49,   49,
	   49,   49,    0,    0,   43,   44,    0,    0,   49,   49,
	   49,   49,   43,    0,   43,   43,   43,   50,   45,   49,
	   49,   49,   49,   49,   49,   49,    0,    0,    0,    0,
		0,   43,   43,   43,    0,    0,    0,    0,   44,    0,
		0,    0,    0,    0,    0,    0,   44,    0,   44,   44,
	   44,   45,   41,    0,    0,    0,    0,    0,    0,   45,
		0,   45,   45,   45,   43,   44,   44,   44,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,   45,   45,
	   45,    0,    0,    0,    0,   41,   42,    0,    0,    0,
		0,    0,    0,   41,    0,    0,   41,    0,   44,  147,
	  148,  149,  150,  151,  152,  153,  154,    0,  158,  161,
		0,   45,   41,   41,   41,    0,    0,    0,    0,   42,
		0,    0,    0,    0,    0,    0,    0,   42,    0,    0,
	   42,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,   41,   42,   42,   42,  100,
		0,    0,    0,  101,    0,  104,    0,    0,    0,  105,
	  102,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,  103,    0,   42,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,   50,    0,    0,   50,   50,   50,   50,   50,
		0,    0,    0,    0,    0,    0,   50,   50,   50,   50,
	  230,  231,  232,    0,  233,  234,    0,   50,   50,   50,
	   50,   50,   50,   50,   66,    0,    0,    0,   66,    0,
	   66,    0,    0,    0,   66,   66,    0,    0,    0,   43,
		0,    0,   43,   43,   43,   43,   43,    0,    0,    0,
		0,    0,   66,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,   43,   43,   43,   43,   43,   43,
	   43,    0,    0,   44,    0,    0,   44,   44,   44,   44,
	   44,    0,    0,    0,    0,    0,   45,    0,    0,   45,
	   45,   45,   45,   45,    0,    0,    0,    0,   44,   44,
	   44,   44,   44,   44,   44,    0,    0,    0,    0,    0,
		0,   45,   45,   45,   45,   45,   45,   45,    0,    0,
	   41,    0,    0,   41,   41,   41,   41,  100,    0,    0,
		0,  101,    0,  104,    0,    0,    0,  105,    0,    0,
		0,    0,    0,    0,    0,   41,   41,   41,   41,   41,
	   41,   41,    0,    0,   42,  103,    0,   42,   42,   42,
	   42,    0,    0,    0,    0,    0,    0,    0,    0,    0,
	   70,   71,   72,   73,   74,   75,    0,    0,    0,   42,
	   42,   42,   42,   42,   42,   42,    0,    0,    0,    0,
	   76,   77,   78,   79,   80,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,   81,   82,   83,    0,    0,   84,   85,
	   86,   87,   88,   89,   90,   91,   92,   93,   94,   95,
	   96,    0,    0,    0,   97,   98,   99,    0,    0,    0,
		0,    0,    0,    0,    0,   66,   66,   66,   66,   66,
	   66,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,   66,   66,   66,   66,   66,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,   66,   66,
	   66,    0,    0,   66,   66,   66,   66,   66,   66,   66,
	   66,   66,   66,   66,   66,   66,    0,    0,    0,   66,
	   66,   66,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,   70,   71,
	   72,   73,   74,   75,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,   76,   77,
	   78,   79,   80,    0,    0,    0,    0,    0,    0,    0,
		0,    0,    0,    0,    0,    0,    0,    0,    0,    0,
		0,   81,   82,    0,    0,    0,   84,   85,   86,   87,
	   88,   89,   90,   91,   92,   93,   94,   95,   96,    0,
		0,    0,   97,   98,   99,
	);

	/**
	 * @var int [] $yyCheck = {					 41,
		0,   47,   43,   41,   45,   43,   33,   45,  124,   44,
	   40,   43,   42,   45,   43,   83,   45,   41,   41,   43,
	  113,   45,   41,   44,   44,   44,   43,  120,   45,   61,
	   62,   60,   61,   33,  102,    0,   41,   42,   41,   42,
	   63,   41,   41,   43,   44,   45,   36,   47,  262,  282,
	  283,  284,  285,   40,   58,   59,   60,   61,   93,   36,
	   60,   61,   62,  277,  278,  279,  280,  281,   33,  219,
		0,  221,  222,   43,  101,   45,   41,   44,   43,   44,
	   45,   41,   47,  274,   44,   41,  300,  272,   44,  303,
	  304,   91,   43,   93,   45,   60,   61,   62,   41,   41,
	  273,   44,   44,   33,  290,    0,  289,  321,  322,  323,
	  140,   41,  142,   43,   44,   45,  291,   47,  318,  319,
	  320,  287,  288,  292,  124,  263,   91,   44,   93,  263,
	   60,   61,   62,   56,   57,   62,   63,   61,   33,  262,
		0,   40,   64,   65,    5,    6,   41,   58,   43,   44,
	   45,   40,   47,  216,  217,  185,   66,   67,   40,  124,
	   68,   69,   40,   93,   40,   60,   61,   62,   40,   40,
	  286,   40,   40,   33,   40,  263,    0,   58,   91,  265,
	  265,   41,   41,   43,   44,   45,   63,   47,   42,   41,
	   41,  218,  262,  223,  124,  262,  226,  261,   93,   41,
	   60,   61,   62,   41,   41,  267,   41,   41,   41,   33,
	  262,  268,   41,   41,  272,    4,  137,   41,   41,   43,
	   44,   45,  141,   47,   43,   55,  264,   67,  266,  124,
	   69,  269,  270,   93,  275,  277,   60,   61,   62,  207,
	  264,  271,  266,  273,  207,  269,  270,  264,  209,  266,
	  271,  271,  269,  270,  208,  277,  302,  262,  257,  262,
	   -1,  291,  261,   42,  124,   -1,   -1,   -1,  268,   93,
	   -1,  271,  272,  273,  274,  275,   -1,   -1,   -1,   -1,
	  322,   -1,  282,  283,  284,  285,  286,  287,  288,  289,
	  290,  291,  292,  293,  294,  295,  296,  297,  298,  299,
	  124,   -1,  302,  268,   -1,   -1,  271,  272,  273,  274,
	  275,   -1,   -1,   -1,   -1,   -1,   -1,  282,  283,  284,
	  285,  286,  287,  288,  289,  290,  291,  292,  293,  294,
	  295,  296,  297,  298,  299,   -1,   -1,  302,  268,   -1,
	   -1,  271,  272,  273,  274,  275,   -1,   -1,   -1,   -1,
	   -1,   -1,  282,  283,  284,  285,  286,  287,  288,  289,
	  290,  291,  292,  293,  294,  295,  296,  297,  298,  299,
	   -1,   -1,  302,  268,   -1,   -1,  271,  272,  273,  274,
	  275,   -1,   -1,   -1,   -1,   -1,   -1,  282,  283,  284,
	  285,  286,  287,  288,  289,  290,  291,  292,  293,  294,
	  295,  296,  297,  298,  299,   -1,   -1,  302,  268,   -1,
	   -1,  271,  272,  273,  274,  275,   -1,   -1,   -1,   -1,
	   -1,   -1,  282,  283,  284,  285,  286,  287,  288,  289,
	  290,  291,  292,  293,  294,  295,  296,  297,  298,  299,
	   -1,   -1,  302,    0,  268,   -1,   -1,  271,  272,  273,
	  274,  275,    0,   -1,   -1,   -1,   -1,   -1,  282,  283,
	  284,  285,  286,  287,  288,  289,  290,  291,  292,  293,
	  294,  295,  296,  297,  298,  299,   33,   -1,  302,    0,
	   -1,   -1,  261,  262,   41,   -1,   43,   44,   45,   -1,
	   47,   -1,   -1,   41,   -1,   -1,   44,   -1,  277,  278,
	  279,  280,  281,   60,   61,   62,   -1,   -1,   -1,   -1,
	   -1,   -1,   33,   -1,    0,   -1,   -1,   -1,   -1,   -1,
	   41,  300,   43,   44,   45,   -1,   47,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   93,   -1,   -1,   60,
	   61,   62,  321,  322,  323,   93,   -1,   33,    0,   -1,
	   -1,   -1,   -1,   -1,   -1,   41,   -1,   43,   44,   45,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,  124,   -1,   -1,
	   -1,   -1,   93,   -1,   60,   61,   62,   -1,   -1,   -1,
	   -1,   33,    0,   -1,   -1,   -1,   -1,   -1,   -1,   41,
	   -1,   43,   44,   45,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,  124,   -1,   -1,   -1,   93,   60,   61,
	   62,   -1,   -1,   -1,   -1,   33,   -1,   -1,   -1,    0,
	   -1,   -1,   -1,   41,   -1,   43,   44,   45,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,  124,   -1,
	   -1,   93,   60,   61,   62,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   33,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   41,   -1,   43,   44,   45,   -1,    0,   -1,   -1,   -1,
	   -1,   -1,  124,   -1,   -1,   93,   -1,   -1,   -1,   60,
	   61,   62,   84,   85,   86,   87,   88,   89,   90,   91,
	   92,   93,   94,   95,   96,   -1,   -1,   -1,   -1,   -1,
	   -1,  103,   -1,   -1,   -1,   -1,  124,   41,   -1,   -1,
	   44,  268,   93,   -1,  271,  272,  273,  274,  275,   -1,
	  268,   -1,   -1,  271,  272,  282,  283,  284,  285,  286,
	  287,  288,  289,  290,  291,  292,  293,  294,  295,  296,
	  297,  298,  299,  124,   -1,  302,   -1,  268,   -1,   -1,
	  271,  272,  273,  274,  275,   -1,   -1,   -1,   -1,   93,
	   -1,  282,  283,  284,  285,  286,  287,  288,  289,  290,
	  291,  292,  293,  294,  295,  296,  297,  298,  299,   -1,
	   -1,  302,  268,   -1,   -1,  271,  272,  273,  274,  275,
	   -1,   -1,   -1,   -1,   -1,   -1,  282,  283,  284,  285,
	  286,  287,  288,  289,  290,  291,  292,  293,  294,  295,
	  296,  297,  298,  299,   -1,   -1,  268,   -1,   -1,  271,
	  272,  273,  274,  275,   -1,   -1,   -1,   -1,   -1,   -1,
	  282,  283,  284,  285,  286,  287,  288,  289,  290,  291,
	  292,  293,  294,  295,  296,  297,  298,  299,   -1,   -1,
	  268,   -1,   -1,  271,  272,  273,  274,  275,   -1,   -1,
	   -1,   -1,   -1,   -1,  282,  283,  284,  285,  286,  287,
	  288,  289,  290,  291,  292,  293,  294,  295,  296,  297,
	  298,  299,   -1,   -1,   -1,   -1,    0,  268,   -1,   -1,
	  271,  272,  273,  274,  275,   -1,   -1,   -1,   -1,   -1,
	   -1,  282,  283,  284,  285,  286,  287,  288,  289,  290,
	  291,  292,  293,  294,  295,  296,  297,  298,  299,   33,
		0,   -1,   -1,   -1,   -1,   -1,   -1,   41,   -1,   43,
	   44,   45,   -1,   -1,  268,   -1,   -1,  271,  272,  273,
	  274,   -1,   -1,   -1,   -1,   -1,   60,   61,   62,   -1,
	   -1,   -1,   -1,   33,    0,   -1,   -1,   -1,   -1,   -1,
	   -1,   41,   -1,   43,   44,   45,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   93,
	   60,   61,   62,   -1,   -1,   -1,   -1,   33,    0,   -1,
	   -1,   -1,   -1,   -1,   -1,   41,   -1,   43,   44,   45,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	  124,   -1,   -1,   93,   60,   61,   62,   -1,   -1,   -1,
	   -1,   33,    0,   -1,   -1,   -1,   -1,   -1,   -1,   41,
	   -1,   43,   44,   45,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,  124,   -1,   -1,   93,   60,   61,
	   62,   -1,   -1,   -1,   -1,   33,   -1,   -1,   -1,   -1,
	   -1,   -1,    0,   41,   -1,   43,   44,   45,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,  124,   -1,
	   -1,   93,   60,   61,   62,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   33,   -1,    0,   -1,   -1,
	   -1,   -1,   -1,   41,   -1,   43,   44,   45,    0,   -1,
	   -1,   -1,  124,   -1,   -1,   93,   -1,   -1,   -1,   -1,
	   -1,   -1,   60,   61,   62,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   41,   -1,
	   -1,   44,   -1,   -1,   -1,   -1,  124,   -1,   -1,   41,
	   -1,   -1,   44,   -1,  268,   93,   -1,  271,  272,  273,
	  274,  275,   -1,   -1,   -1,   -1,   -1,   -1,  282,  283,
	  284,  285,  286,  287,  288,  289,  290,  291,   -1,  293,
	  294,  295,  296,  297,  298,  299,  124,   -1,  268,   -1,
	   93,  271,  272,  273,  274,  275,   -1,   -1,   -1,   -1,
	   -1,   93,  282,  283,  284,  285,  286,  287,  288,  289,
	  290,  291,   -1,  293,  294,  295,  296,  297,  298,  299,
	   -1,   -1,  268,   -1,   -1,  271,  272,  273,  274,  275,
	   -1,   -1,   -1,   -1,   -1,   -1,  282,  283,  284,  285,
	  286,  287,  288,  289,  290,   -1,   -1,  293,  294,  295,
	  296,  297,  298,  299,   -1,   -1,  268,   -1,   -1,  271,
	  272,  273,  274,  275,   -1,   -1,   -1,   -1,   -1,   -1,
	  282,  283,  284,  285,  286,  287,  288,  289,   -1,   -1,
	   -1,  293,  294,  295,  296,  297,  298,  299,   -1,   -1,
	  268,   -1,   -1,  271,  272,  273,  274,  275,   -1,   -1,
	   -1,   -1,   -1,   -1,  282,  283,  284,  285,  286,  287,
	  288,  289,   33,   -1,   -1,  293,  294,  295,  296,  297,
	  298,  299,   -1,   -1,   -1,   -1,   -1,   -1,   -1,    0,
	  268,   -1,   -1,  271,  272,  273,  274,  275,   -1,   60,
	   61,   62,   -1,   -1,  282,  283,  284,  285,  286,  287,
	  288,   -1,   -1,   -1,   -1,  293,  294,  295,  296,  297,
	  298,  299,   33,    0,   -1,  268,   -1,   -1,  271,  272,
	   41,  274,   43,   44,   45,   -1,  268,   -1,   -1,  271,
	  272,   -1,  274,   -1,   -1,   -1,   -1,   -1,   -1,   60,
	   61,   62,   -1,   -1,   -1,   -1,   33,    0,   -1,   -1,
	   -1,   -1,   -1,   -1,   41,   -1,   43,   44,   45,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   93,   60,   61,   62,   -1,   -1,   -1,   -1,
	   33,    0,   -1,   -1,   -1,   -1,   -1,   -1,   41,   -1,
	   43,   44,   45,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,  124,   -1,   -1,   93,   60,   61,   62,
	   -1,   -1,   -1,   -1,   33,    0,   -1,   -1,   -1,   -1,
	   -1,   -1,   41,   -1,   43,   44,   45,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,  124,   -1,   -1,
	   93,   60,   61,   62,   -1,   -1,   -1,   -1,   33,    0,
	   -1,   -1,   -1,   -1,   -1,   -1,   41,   -1,   43,   44,
	   45,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,  124,   -1,   -1,   93,   60,   61,   62,   -1,   -1,
	   -1,   -1,   33,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   41,   -1,   43,   44,   45,   -1,    0,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   93,   60,
	   61,   62,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,  293,  294,  295,  296,  297,  298,  299,   33,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   41,   -1,   43,
	   44,   45,   93,   -1,   -1,   -1,   -1,  268,   -1,   -1,
	  271,  272,  273,  274,  275,   -1,   60,   61,   62,   -1,
	   -1,  282,  283,  284,  285,  286,   -1,   -1,   -1,   -1,
	   -1,   -1,  293,  294,  295,  296,  297,  298,  299,   -1,
	   -1,  268,   -1,   -1,  271,  272,  273,  274,  275,   93,
	   -1,   -1,   -1,   -1,   -1,  282,  283,  284,  285,  286,
	   -1,   -1,   -1,   -1,   -1,   -1,  293,  294,  295,  296,
	  297,  298,  299,   -1,   -1,  268,   -1,   -1,  271,  272,
	  273,  274,  275,   -1,   -1,   -1,   -1,   -1,   -1,  282,
	  283,  284,  285,  286,   -1,   -1,   -1,   -1,   -1,   -1,
	  293,  294,  295,  296,  297,  298,  299,   -1,   -1,  268,
	   -1,   -1,  271,  272,  273,  274,  275,   -1,   -1,   -1,
	   -1,   -1,   -1,  282,  283,  284,  285,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,  293,  294,  295,  296,  297,  298,
	  299,   -1,   -1,  268,   -1,   -1,  271,  272,  273,  274,
	  275,   -1,   -1,   -1,    0,   -1,   -1,  282,  283,  284,
	  285,   -1,   -1,   -1,   -1,   -1,   -1,   -1,  293,  294,
	  295,  296,  297,  298,  299,   -1,   -1,  268,   -1,   -1,
	  271,  272,  273,  274,  275,   -1,   -1,   33,   -1,   -1,
	   -1,  282,  283,  284,  285,   41,   -1,   43,   44,   45,
	   -1,    0,  293,  294,  295,  296,  297,  298,  299,   -1,
	   -1,   -1,   -1,   -1,   60,   61,   62,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,  268,   -1,   -1,  271,  272,  273,
	  274,  275,   -1,   -1,   33,    0,   -1,   -1,  282,  283,
	  284,  285,   41,   -1,   43,   44,   45,   93,    0,  293,
	  294,  295,  296,  297,  298,  299,   -1,   -1,   -1,   -1,
	   -1,   60,   61,   62,   -1,   -1,   -1,   -1,   33,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   41,   -1,   43,   44,
	   45,   33,    0,   -1,   -1,   -1,   -1,   -1,   -1,   41,
	   -1,   43,   44,   45,   93,   60,   61,   62,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   60,   61,
	   62,   -1,   -1,   -1,   -1,   33,    0,   -1,   -1,   -1,
	   -1,   -1,   -1,   41,   -1,   -1,   44,   -1,   93,   44,
	   45,   46,   47,   48,   49,   50,   51,   -1,   53,   54,
	   -1,   93,   60,   61,   62,   -1,   -1,   -1,   -1,   33,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   41,   -1,   -1,
	   44,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   93,   60,   61,   62,   36,
	   -1,   -1,   -1,   40,   -1,   42,   -1,   -1,   -1,   46,
	   47,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   64,   -1,   93,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,  268,   -1,   -1,  271,  272,  273,  274,  275,
	   -1,   -1,   -1,   -1,   -1,   -1,  282,  283,  284,  285,
	  155,  156,  157,   -1,  159,  160,   -1,  293,  294,  295,
	  296,  297,  298,  299,   36,   -1,   -1,   -1,   40,   -1,
	   42,   -1,   -1,   -1,   46,   47,   -1,   -1,   -1,  268,
	   -1,   -1,  271,  272,  273,  274,  275,   -1,   -1,   -1,
	   -1,   -1,   64,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,  293,  294,  295,  296,  297,  298,
	  299,   -1,   -1,  268,   -1,   -1,  271,  272,  273,  274,
	  275,   -1,   -1,   -1,   -1,   -1,  268,   -1,   -1,  271,
	  272,  273,  274,  275,   -1,   -1,   -1,   -1,  293,  294,
	  295,  296,  297,  298,  299,   -1,   -1,   -1,   -1,   -1,
	   -1,  293,  294,  295,  296,  297,  298,  299,   -1,   -1,
	  268,   -1,   -1,  271,  272,  273,  274,   36,   -1,   -1,
	   -1,   40,   -1,   42,   -1,   -1,   -1,   46,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,  293,  294,  295,  296,  297,
	  298,  299,   -1,   -1,  268,   64,   -1,  271,  272,  273,
	  274,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	  257,  258,  259,  260,  261,  262,   -1,   -1,   -1,  293,
	  294,  295,  296,  297,  298,  299,   -1,   -1,   -1,   -1,
	  277,  278,  279,  280,  281,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,  300,  301,  302,   -1,   -1,  305,  306,
	  307,  308,  309,  310,  311,  312,  313,  314,  315,  316,
	  317,   -1,   -1,   -1,  321,  322,  323,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,  257,  258,  259,  260,  261,
	  262,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,  277,  278,  279,  280,  281,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,  300,  301,
	  302,   -1,   -1,  305,  306,  307,  308,  309,  310,  311,
	  312,  313,  314,  315,  316,  317,   -1,   -1,   -1,  321,
	  322,  323,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,  257,  258,
	  259,  260,  261,  262,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,  277,  278,
	  279,  280,  281,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,  300,  301,   -1,   -1,   -1,  305,  306,  307,  308,
	  309,  310,  311,  312,  313,  314,  315,  316,  317,   -1,
	   -1,   -1,  321,  322,  323,
		}
	 */
	protected static  $yyCheck = array(					 41,
		0,   47,   43,   41,   45,   43,   33,   45,  124,   44,
	   40,   43,   42,   45,   43,   83,   45,   41,   41,   43,
	  113,   45,   41,   44,   44,   44,   43,  120,   45,   61,
	   62,   60,   61,   33,  102,    0,   41,   42,   41,   42,
	   63,   41,   41,   43,   44,   45,   36,   47,  262,  282,
	  283,  284,  285,   40,   58,   59,   60,   61,   93,   36,
	   60,   61,   62,  277,  278,  279,  280,  281,   33,  219,
		0,  221,  222,   43,  101,   45,   41,   44,   43,   44,
	   45,   41,   47,  274,   44,   41,  300,  272,   44,  303,
	  304,   91,   43,   93,   45,   60,   61,   62,   41,   41,
	  273,   44,   44,   33,  290,    0,  289,  321,  322,  323,
	  140,   41,  142,   43,   44,   45,  291,   47,  318,  319,
	  320,  287,  288,  292,  124,  263,   91,   44,   93,  263,
	   60,   61,   62,   56,   57,   62,   63,   61,   33,  262,
		0,   40,   64,   65,    5,    6,   41,   58,   43,   44,
	   45,   40,   47,  216,  217,  185,   66,   67,   40,  124,
	   68,   69,   40,   93,   40,   60,   61,   62,   40,   40,
	  286,   40,   40,   33,   40,  263,    0,   58,   91,  265,
	  265,   41,   41,   43,   44,   45,   63,   47,   42,   41,
	   41,  218,  262,  223,  124,  262,  226,  261,   93,   41,
	   60,   61,   62,   41,   41,  267,   41,   41,   41,   33,
	  262,  268,   41,   41,  272,    4,  137,   41,   41,   43,
	   44,   45,  141,   47,   43,   55,  264,   67,  266,  124,
	   69,  269,  270,   93,  275,  277,   60,   61,   62,  207,
	  264,  271,  266,  273,  207,  269,  270,  264,  209,  266,
	  271,  271,  269,  270,  208,  277,  302,  262,  257,  262,
	   -1,  291,  261,   42,  124,   -1,   -1,   -1,  268,   93,
	   -1,  271,  272,  273,  274,  275,   -1,   -1,   -1,   -1,
	  322,   -1,  282,  283,  284,  285,  286,  287,  288,  289,
	  290,  291,  292,  293,  294,  295,  296,  297,  298,  299,
	  124,   -1,  302,  268,   -1,   -1,  271,  272,  273,  274,
	  275,   -1,   -1,   -1,   -1,   -1,   -1,  282,  283,  284,
	  285,  286,  287,  288,  289,  290,  291,  292,  293,  294,
	  295,  296,  297,  298,  299,   -1,   -1,  302,  268,   -1,
	   -1,  271,  272,  273,  274,  275,   -1,   -1,   -1,   -1,
	   -1,   -1,  282,  283,  284,  285,  286,  287,  288,  289,
	  290,  291,  292,  293,  294,  295,  296,  297,  298,  299,
	   -1,   -1,  302,  268,   -1,   -1,  271,  272,  273,  274,
	  275,   -1,   -1,   -1,   -1,   -1,   -1,  282,  283,  284,
	  285,  286,  287,  288,  289,  290,  291,  292,  293,  294,
	  295,  296,  297,  298,  299,   -1,   -1,  302,  268,   -1,
	   -1,  271,  272,  273,  274,  275,   -1,   -1,   -1,   -1,
	   -1,   -1,  282,  283,  284,  285,  286,  287,  288,  289,
	  290,  291,  292,  293,  294,  295,  296,  297,  298,  299,
	   -1,   -1,  302,    0,  268,   -1,   -1,  271,  272,  273,
	  274,  275,    0,   -1,   -1,   -1,   -1,   -1,  282,  283,
	  284,  285,  286,  287,  288,  289,  290,  291,  292,  293,
	  294,  295,  296,  297,  298,  299,   33,   -1,  302,    0,
	   -1,   -1,  261,  262,   41,   -1,   43,   44,   45,   -1,
	   47,   -1,   -1,   41,   -1,   -1,   44,   -1,  277,  278,
	  279,  280,  281,   60,   61,   62,   -1,   -1,   -1,   -1,
	   -1,   -1,   33,   -1,    0,   -1,   -1,   -1,   -1,   -1,
	   41,  300,   43,   44,   45,   -1,   47,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   93,   -1,   -1,   60,
	   61,   62,  321,  322,  323,   93,   -1,   33,    0,   -1,
	   -1,   -1,   -1,   -1,   -1,   41,   -1,   43,   44,   45,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,  124,   -1,   -1,
	   -1,   -1,   93,   -1,   60,   61,   62,   -1,   -1,   -1,
	   -1,   33,    0,   -1,   -1,   -1,   -1,   -1,   -1,   41,
	   -1,   43,   44,   45,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,  124,   -1,   -1,   -1,   93,   60,   61,
	   62,   -1,   -1,   -1,   -1,   33,   -1,   -1,   -1,    0,
	   -1,   -1,   -1,   41,   -1,   43,   44,   45,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,  124,   -1,
	   -1,   93,   60,   61,   62,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   33,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   41,   -1,   43,   44,   45,   -1,    0,   -1,   -1,   -1,
	   -1,   -1,  124,   -1,   -1,   93,   -1,   -1,   -1,   60,
	   61,   62,   84,   85,   86,   87,   88,   89,   90,   91,
	   92,   93,   94,   95,   96,   -1,   -1,   -1,   -1,   -1,
	   -1,  103,   -1,   -1,   -1,   -1,  124,   41,   -1,   -1,
	   44,  268,   93,   -1,  271,  272,  273,  274,  275,   -1,
	  268,   -1,   -1,  271,  272,  282,  283,  284,  285,  286,
	  287,  288,  289,  290,  291,  292,  293,  294,  295,  296,
	  297,  298,  299,  124,   -1,  302,   -1,  268,   -1,   -1,
	  271,  272,  273,  274,  275,   -1,   -1,   -1,   -1,   93,
	   -1,  282,  283,  284,  285,  286,  287,  288,  289,  290,
	  291,  292,  293,  294,  295,  296,  297,  298,  299,   -1,
	   -1,  302,  268,   -1,   -1,  271,  272,  273,  274,  275,
	   -1,   -1,   -1,   -1,   -1,   -1,  282,  283,  284,  285,
	  286,  287,  288,  289,  290,  291,  292,  293,  294,  295,
	  296,  297,  298,  299,   -1,   -1,  268,   -1,   -1,  271,
	  272,  273,  274,  275,   -1,   -1,   -1,   -1,   -1,   -1,
	  282,  283,  284,  285,  286,  287,  288,  289,  290,  291,
	  292,  293,  294,  295,  296,  297,  298,  299,   -1,   -1,
	  268,   -1,   -1,  271,  272,  273,  274,  275,   -1,   -1,
	   -1,   -1,   -1,   -1,  282,  283,  284,  285,  286,  287,
	  288,  289,  290,  291,  292,  293,  294,  295,  296,  297,
	  298,  299,   -1,   -1,   -1,   -1,    0,  268,   -1,   -1,
	  271,  272,  273,  274,  275,   -1,   -1,   -1,   -1,   -1,
	   -1,  282,  283,  284,  285,  286,  287,  288,  289,  290,
	  291,  292,  293,  294,  295,  296,  297,  298,  299,   33,
		0,   -1,   -1,   -1,   -1,   -1,   -1,   41,   -1,   43,
	   44,   45,   -1,   -1,  268,   -1,   -1,  271,  272,  273,
	  274,   -1,   -1,   -1,   -1,   -1,   60,   61,   62,   -1,
	   -1,   -1,   -1,   33,    0,   -1,   -1,   -1,   -1,   -1,
	   -1,   41,   -1,   43,   44,   45,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   93,
	   60,   61,   62,   -1,   -1,   -1,   -1,   33,    0,   -1,
	   -1,   -1,   -1,   -1,   -1,   41,   -1,   43,   44,   45,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	  124,   -1,   -1,   93,   60,   61,   62,   -1,   -1,   -1,
	   -1,   33,    0,   -1,   -1,   -1,   -1,   -1,   -1,   41,
	   -1,   43,   44,   45,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,  124,   -1,   -1,   93,   60,   61,
	   62,   -1,   -1,   -1,   -1,   33,   -1,   -1,   -1,   -1,
	   -1,   -1,    0,   41,   -1,   43,   44,   45,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,  124,   -1,
	   -1,   93,   60,   61,   62,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   33,   -1,    0,   -1,   -1,
	   -1,   -1,   -1,   41,   -1,   43,   44,   45,    0,   -1,
	   -1,   -1,  124,   -1,   -1,   93,   -1,   -1,   -1,   -1,
	   -1,   -1,   60,   61,   62,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   41,   -1,
	   -1,   44,   -1,   -1,   -1,   -1,  124,   -1,   -1,   41,
	   -1,   -1,   44,   -1,  268,   93,   -1,  271,  272,  273,
	  274,  275,   -1,   -1,   -1,   -1,   -1,   -1,  282,  283,
	  284,  285,  286,  287,  288,  289,  290,  291,   -1,  293,
	  294,  295,  296,  297,  298,  299,  124,   -1,  268,   -1,
	   93,  271,  272,  273,  274,  275,   -1,   -1,   -1,   -1,
	   -1,   93,  282,  283,  284,  285,  286,  287,  288,  289,
	  290,  291,   -1,  293,  294,  295,  296,  297,  298,  299,
	   -1,   -1,  268,   -1,   -1,  271,  272,  273,  274,  275,
	   -1,   -1,   -1,   -1,   -1,   -1,  282,  283,  284,  285,
	  286,  287,  288,  289,  290,   -1,   -1,  293,  294,  295,
	  296,  297,  298,  299,   -1,   -1,  268,   -1,   -1,  271,
	  272,  273,  274,  275,   -1,   -1,   -1,   -1,   -1,   -1,
	  282,  283,  284,  285,  286,  287,  288,  289,   -1,   -1,
	   -1,  293,  294,  295,  296,  297,  298,  299,   -1,   -1,
	  268,   -1,   -1,  271,  272,  273,  274,  275,   -1,   -1,
	   -1,   -1,   -1,   -1,  282,  283,  284,  285,  286,  287,
	  288,  289,   33,   -1,   -1,  293,  294,  295,  296,  297,
	  298,  299,   -1,   -1,   -1,   -1,   -1,   -1,   -1,    0,
	  268,   -1,   -1,  271,  272,  273,  274,  275,   -1,   60,
	   61,   62,   -1,   -1,  282,  283,  284,  285,  286,  287,
	  288,   -1,   -1,   -1,   -1,  293,  294,  295,  296,  297,
	  298,  299,   33,    0,   -1,  268,   -1,   -1,  271,  272,
	   41,  274,   43,   44,   45,   -1,  268,   -1,   -1,  271,
	  272,   -1,  274,   -1,   -1,   -1,   -1,   -1,   -1,   60,
	   61,   62,   -1,   -1,   -1,   -1,   33,    0,   -1,   -1,
	   -1,   -1,   -1,   -1,   41,   -1,   43,   44,   45,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   93,   60,   61,   62,   -1,   -1,   -1,   -1,
	   33,    0,   -1,   -1,   -1,   -1,   -1,   -1,   41,   -1,
	   43,   44,   45,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,  124,   -1,   -1,   93,   60,   61,   62,
	   -1,   -1,   -1,   -1,   33,    0,   -1,   -1,   -1,   -1,
	   -1,   -1,   41,   -1,   43,   44,   45,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,  124,   -1,   -1,
	   93,   60,   61,   62,   -1,   -1,   -1,   -1,   33,    0,
	   -1,   -1,   -1,   -1,   -1,   -1,   41,   -1,   43,   44,
	   45,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,  124,   -1,   -1,   93,   60,   61,   62,   -1,   -1,
	   -1,   -1,   33,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   41,   -1,   43,   44,   45,   -1,    0,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   93,   60,
	   61,   62,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,  293,  294,  295,  296,  297,  298,  299,   33,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   41,   -1,   43,
	   44,   45,   93,   -1,   -1,   -1,   -1,  268,   -1,   -1,
	  271,  272,  273,  274,  275,   -1,   60,   61,   62,   -1,
	   -1,  282,  283,  284,  285,  286,   -1,   -1,   -1,   -1,
	   -1,   -1,  293,  294,  295,  296,  297,  298,  299,   -1,
	   -1,  268,   -1,   -1,  271,  272,  273,  274,  275,   93,
	   -1,   -1,   -1,   -1,   -1,  282,  283,  284,  285,  286,
	   -1,   -1,   -1,   -1,   -1,   -1,  293,  294,  295,  296,
	  297,  298,  299,   -1,   -1,  268,   -1,   -1,  271,  272,
	  273,  274,  275,   -1,   -1,   -1,   -1,   -1,   -1,  282,
	  283,  284,  285,  286,   -1,   -1,   -1,   -1,   -1,   -1,
	  293,  294,  295,  296,  297,  298,  299,   -1,   -1,  268,
	   -1,   -1,  271,  272,  273,  274,  275,   -1,   -1,   -1,
	   -1,   -1,   -1,  282,  283,  284,  285,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,  293,  294,  295,  296,  297,  298,
	  299,   -1,   -1,  268,   -1,   -1,  271,  272,  273,  274,
	  275,   -1,   -1,   -1,    0,   -1,   -1,  282,  283,  284,
	  285,   -1,   -1,   -1,   -1,   -1,   -1,   -1,  293,  294,
	  295,  296,  297,  298,  299,   -1,   -1,  268,   -1,   -1,
	  271,  272,  273,  274,  275,   -1,   -1,   33,   -1,   -1,
	   -1,  282,  283,  284,  285,   41,   -1,   43,   44,   45,
	   -1,    0,  293,  294,  295,  296,  297,  298,  299,   -1,
	   -1,   -1,   -1,   -1,   60,   61,   62,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,  268,   -1,   -1,  271,  272,  273,
	  274,  275,   -1,   -1,   33,    0,   -1,   -1,  282,  283,
	  284,  285,   41,   -1,   43,   44,   45,   93,    0,  293,
	  294,  295,  296,  297,  298,  299,   -1,   -1,   -1,   -1,
	   -1,   60,   61,   62,   -1,   -1,   -1,   -1,   33,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   41,   -1,   43,   44,
	   45,   33,    0,   -1,   -1,   -1,   -1,   -1,   -1,   41,
	   -1,   43,   44,   45,   93,   60,   61,   62,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   60,   61,
	   62,   -1,   -1,   -1,   -1,   33,    0,   -1,   -1,   -1,
	   -1,   -1,   -1,   41,   -1,   -1,   44,   -1,   93,   44,
	   45,   46,   47,   48,   49,   50,   51,   -1,   53,   54,
	   -1,   93,   60,   61,   62,   -1,   -1,   -1,   -1,   33,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   41,   -1,   -1,
	   44,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   93,   60,   61,   62,   36,
	   -1,   -1,   -1,   40,   -1,   42,   -1,   -1,   -1,   46,
	   47,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   64,   -1,   93,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,  268,   -1,   -1,  271,  272,  273,  274,  275,
	   -1,   -1,   -1,   -1,   -1,   -1,  282,  283,  284,  285,
	  155,  156,  157,   -1,  159,  160,   -1,  293,  294,  295,
	  296,  297,  298,  299,   36,   -1,   -1,   -1,   40,   -1,
	   42,   -1,   -1,   -1,   46,   47,   -1,   -1,   -1,  268,
	   -1,   -1,  271,  272,  273,  274,  275,   -1,   -1,   -1,
	   -1,   -1,   64,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,  293,  294,  295,  296,  297,  298,
	  299,   -1,   -1,  268,   -1,   -1,  271,  272,  273,  274,
	  275,   -1,   -1,   -1,   -1,   -1,  268,   -1,   -1,  271,
	  272,  273,  274,  275,   -1,   -1,   -1,   -1,  293,  294,
	  295,  296,  297,  298,  299,   -1,   -1,   -1,   -1,   -1,
	   -1,  293,  294,  295,  296,  297,  298,  299,   -1,   -1,
	  268,   -1,   -1,  271,  272,  273,  274,   36,   -1,   -1,
	   -1,   40,   -1,   42,   -1,   -1,   -1,   46,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,  293,  294,  295,  296,  297,
	  298,  299,   -1,   -1,  268,   64,   -1,  271,  272,  273,
	  274,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	  257,  258,  259,  260,  261,  262,   -1,   -1,   -1,  293,
	  294,  295,  296,  297,  298,  299,   -1,   -1,   -1,   -1,
	  277,  278,  279,  280,  281,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,  300,  301,  302,   -1,   -1,  305,  306,
	  307,  308,  309,  310,  311,  312,  313,  314,  315,  316,
	  317,   -1,   -1,   -1,  321,  322,  323,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,  257,  258,  259,  260,  261,
	  262,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,  277,  278,  279,  280,  281,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,  300,  301,
	  302,   -1,   -1,  305,  306,  307,  308,  309,  310,  311,
	  312,  313,  314,  315,  316,  317,   -1,   -1,   -1,  321,
	  322,  323,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,  257,  258,
	  259,  260,  261,  262,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,  277,  278,
	  279,  280,  281,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,   -1,
	   -1,  300,  301,   -1,   -1,   -1,  305,  306,  307,  308,
	  309,  310,  311,  312,  313,  314,  315,  316,  317,   -1,
	   -1,   -1,  321,  322,  323,
	);

}

?>
