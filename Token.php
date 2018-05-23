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

// %token constants
/**
 * Token ( public )
 */
class Token
{
	/**
	 * @var int StringLiteral = 257
	 */
	const StringLiteral = 257;

	/**
	 * @var int IntegerLiteral = 258
	 */
	const IntegerLiteral = 258;

	/**
	 * @var int DecimalLiteral = 259
	 */
	const DecimalLiteral = 259;

	/**
	 * @var int DoubleLiteral = 260
	 */
	const DoubleLiteral = 260;

	/**
	 * @var int NCName = 261
	 */
	const NCName = 261;

	/**
	 * @var int QName = 262
	 */
	const QName = 262;

	/**
	 * @var int VarName = 263
	 */
	const VarName = 263;

	/**
	 * @var int FOR = 264
	 */
	const FOR_TOKEN = 264;

	/**
	 * @var int IN = 265
	 */
	const IN = 265;

	/**
	 * @var int IF = 266
	 */
	const IF_TOKEN = 266;

	/**
	 * @var int THEN = 267
	 */
	const THEN = 267;

	/**
	 * @var int ELSE = 268
	 */
	const ELSE_TOKEN = 268;

	/**
	 * @var int SOME = 269
	 */
	const SOME = 269;

	/**
	 * @var int EVERY = 270
	 */
	const EVERY = 270;

	/**
	 * @var int SATISFIES = 271
	 */
	const SATISFIES = 271;

	/**
	 * @var int RETURN = 272
	 */
	const RETURN_TOKEN = 272;

	/**
	 * @var int AND = 273
	 */
	const AND_TOKEN = 273;

	/**
	 * @var int OR = 274
	 */
	const OR_TOKEN = 274;

	/**
	 * @var int TO = 275
	 */
	const TO = 275;

	/**
	 * @var int DOCUMENT = 276
	 */
	const DOCUMENT = 276;

	/**
	 * @var int ELEMENT = 277
	 */
	const ELEMENT = 277;

	/**
	 * @var int ATTRIBUTE = 278
	 */
	const ATTRIBUTE = 278;

	/**
	 * @var int TEXT = 279
	 */
	const TEXT = 279;

	/**
	 * @var int COMMENT = 280
	 */
	const COMMENT = 280;

	/**
	 * @var int PROCESSING_INSTRUCTION = 281
	 */
	const PROCESSING_INSTRUCTION = 281;

	/**
	 * @var int ML = 282
	 */
	const ML = 282;

	/**
	 * @var int DIV = 283
	 */
	const DIV = 283;

	/**
	 * @var int IDIV = 284
	 */
	const IDIV = 284;

	/**
	 * @var int MOD = 285
	 */
	const MOD = 285;

	/**
	 * @var int UNION = 286
	 */
	const UNION = 286;

	/**
	 * @var int EXCEPT = 287
	 */
	const EXCEPT = 287;

	/**
	 * @var int INTERSECT = 288
	 */
	const INTERSECT = 288;

	/**
	 * @var int INSTANCE_OF = 289
	 */
	const INSTANCE_OF = 289;

	/**
	 * @var int TREAT_AS = 290
	 */
	const TREAT_AS = 290;

	/**
	 * @var int CASTABLE_AS = 291
	 */
	const CASTABLE_AS = 291;

	/**
	 * @var int CAST_AS = 292
	 */
	const CAST_AS = 292;

	/**
	 * @var int EQ = 293
	 */
	const EQ = 293;

	/**
	 * @var int NE = 294
	 */
	const NE = 294;

	/**
	 * @var int LT = 295
	 */
	const LT = 295;

	/**
	 * @var int GT = 296
	 */
	const GT = 296;

	/**
	 * @var int GE = 297
	 */
	const GE = 297;

	/**
	 * @var int LE = 298
	 */
	const LE = 298;

	/**
	 * @var int IS = 299
	 */
	const IS = 299;

	/**
	 * @var int NODE = 300
	 */
	const NODE = 300;

	/**
	 * @var int DOUBLE_PERIOD = 301
	 */
	const DOUBLE_PERIOD = 301;

	/**
	 * @var int DOUBLE_SLASH = 302
	 */
	const DOUBLE_SLASH = 302;

	/**
	 * @var int EMPTY_SEQUENCE = 303
	 */
	const EMPTY_SEQUENCE = 303;

	/**
	 * @var int ITEM = 304
	 */
	const ITEM = 304;

	/**
	 * @var int AXIS_CHILD = 305
	 */
	const AXIS_CHILD = 305;

	/**
	 * @var int AXIS_DESCENDANT = 306
	 */
	const AXIS_DESCENDANT = 306;

	/**
	 * @var int AXIS_ATTRIBUTE = 307
	 */
	const AXIS_ATTRIBUTE = 307;

	/**
	 * @var int AXIS_SELF = 308
	 */
	const AXIS_SELF = 308;

	/**
	 * @var int AXIS_DESCENDANT_OR_SELF = 309
	 */
	const AXIS_DESCENDANT_OR_SELF = 309;

	/**
	 * @var int AXIS_FOLLOWING_SIBLING = 310
	 */
	const AXIS_FOLLOWING_SIBLING = 310;

	/**
	 * @var int AXIS_FOLLOWING = 311
	 */
	const AXIS_FOLLOWING = 311;

	/**
	 * @var int AXIS_PARENT = 312
	 */
	const AXIS_PARENT = 312;

	/**
	 * @var int AXIS_ANCESTOR = 313
	 */
	const AXIS_ANCESTOR = 313;

	/**
	 * @var int AXIS_PRECEDING_SIBLING = 314
	 */
	const AXIS_PRECEDING_SIBLING = 314;

	/**
	 * @var int AXIS_PRECEDING = 315
	 */
	const AXIS_PRECEDING = 315;

	/**
	 * @var int AXIS_ANCESTOR_OR_SELF = 316
	 */
	const AXIS_ANCESTOR_OR_SELF = 316;

	/**
	 * @var int AXIS_NAMESPACE = 317
	 */
	const AXIS_NAMESPACE = 317;

	/**
	 * @var int Indicator1 = 318
	 */
	const Indicator1 = 318;

	/**
	 * @var int Indicator2 = 319
	 */
	const Indicator2 = 319;

	/**
	 * @var int Indicator3 = 320
	 */
	const Indicator3 = 320;

	/**
	 * @var int DOCUMENT_NODE = 321
	 */
	const DOCUMENT_NODE = 321;

	/**
	 * @var int SCHEMA_ELEMENT = 322
	 */
	const SCHEMA_ELEMENT = 322;

	/**
	 * @var int SCHEMA_ATTRIBUTE = 323
	 */
	const SCHEMA_ATTRIBUTE = 323;

	/**
	 * @var int yyErrorCode = 256
	 */
	const yyErrorCode = 256;

	/**
	 * Get a function name for a token value
	 * @param string $tokenValue
	 * @return string
	 */
	public static function getTokenName( $tokenValue )
	{
		// $oClass = new \ReflectionClass( "\lyquidity\XPath2\Token" );
		$oClass = new \ReflectionClass( __CLASS__ );
		foreach ( $oClass->getConstants() as $key => $value )
		{
			if ( $value == $tokenValue ) return $key;
		}

		return chr( $tokenValue );
	}
}
