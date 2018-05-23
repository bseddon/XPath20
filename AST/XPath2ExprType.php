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

/**
 * Defines a list of expression types such as child, descendant, sibling, parent, etc.
 *
 */
class XPath2ExprType
{
	const Child = 0;
	const Descendant = 1;
	const Attribute = 2;
	const Self = 3;
	const DescendantOrSelf = 4;
	const FollowingSibling = 5;
	const Following = 6;
	const Parent = 7;
	const Ancestor = 8;
	const PrecedingSibling = 9;
	const Preceding = 10;
	const AncestorOrSelf = 11;
	const NamespaceUri = 12;
	const PositionFilter = 13;
	const ChildOverDescendants = 14;
	const Expr = 15;
}
