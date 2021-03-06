<?php
/*************************************************************************************
 * bash.php
 * --------
 * Author: Andreas Gohr (andi@splitbrain.org)
 * Copyright: (c) 2004 Andreas Gohr, Nigel McNie (http://qbnz.com/highlighter)
 * Release Version: 1.0.2
 * CVS Revision Version: $Revision: 1.1 $
 * Date Started: 2004/08/20
 * Last Modified: $Date: 2006/03/16 17:54:52 $
 *
 * BASH language file for GeSHi.
 *
 * CHANGES
 * -------
 * 2004/11/27 (1.0.2)
 *  -  Added support for multiple object splitters
 * 2004/10/27 (1.0.1)
 *   -  Added support for URLs
 * 2004/08/20 (1.0.0)
 *   -  First Release
 *
 * TODO (updated 2004/11/27)
 * -------------------------
 * * Get symbols working
 * * Highlight builtin vars
 *
 *************************************************************************************
 *
 *     This file is part of GeSHi.
 *
 *   GeSHi is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 *   GeSHi is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with GeSHi; if not, write to the Free Software
 *   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 ************************************************************************************/

$language_data =  [
	'LANG_NAME' => 'Bash',
	'COMMENT_SINGLE' => [1 => '#'],
	'COMMENT_MULTI' => [],
	'CASE_KEYWORDS' => GESHI_CAPS_NO_CHANGE,
	'QUOTEMARKS' => ["'", '"'],
	'ESCAPE_CHAR' => '\\',
	'KEYWORDS' => [
		1 => [
			'case', 'do', 'done', 'elif', 'else', 'esac', 'fi', 'for', 'function',
			'if', 'in', 'select', 'then', 'until', 'while', 'time'
			],
		3 => [
			'source', 'alias', 'bg', 'bind', 'break', 'builtin', 'cd', 'command',
			'compgen', 'complete', 'continue', 'declare', 'typeset', 'dirs',
			'disown', 'echo', 'enable', 'eval', 'exec', 'exit', 'export', 'fc',
			'fg', 'getopts', 'hash', 'help', 'history', 'jobs', 'kill', 'let',
			'local', 'logout', 'popd', 'printf', 'pushd', 'pwd', 'read', 'readonly',
			'return', 'set', 'shift', 'shopt', 'suspend', 'test', 'times', 'trap',
			'type', 'ulimit', 'umask', 'unalias', 'unset', 'wait'
			]
		],
	'SYMBOLS' => [
		'(', ')', '[', ']', '!', '@', '%', '&', '*', '|', '/', '<', '>'
		],
	'CASE_SENSITIVE' => [
		GESHI_COMMENTS => false,
		1 => true,
		3 => true,
		],
	'STYLES' => [
		'KEYWORDS' => [
			1 => 'color: #b1b100;',
			3 => 'color: #000066;'
			],
		'COMMENTS' => [
			1 => 'color: #808080; font-style: italic;',
			],
		'ESCAPE_CHAR' => [
			0 => 'color: #000099; font-weight: bold;'
			],
		'BRACKETS' => [
			0 => 'color: #66cc66;'
			],
		'STRINGS' => [
			0 => 'color: #ff0000;'
			],
		'NUMBERS' => [
			0 => 'color: #cc66cc;'
			],
		'METHODS' => [
			],
		'SYMBOLS' => [
			0 => 'color: #66cc66;'
			],
		'REGEXPS' => [
			0 => 'color: #0000ff;',
			1 => 'color: #0000ff;',
			2 => 'color: #0000ff;'
			],
		'SCRIPT' => [
			]
		],
	'URLS' => [
		1 => '',
		3 => ''
		],
	'OOLANG' => false,
	'OBJECT_SPLITTERS' => [
		],
	'REGEXPS' => [
		0 => "\\$\\{[a-zA-Z_][a-zA-Z0-9_]*?\\}",
		1 => "\\$[a-zA-Z_][a-zA-Z0-9_]*",
		2 => "([a-zA-Z_][a-zA-Z0-9_]*)="
		],
	'STRICT_MODE_APPLIES' => GESHI_NEVER,
	'SCRIPT_DELIMITERS' => [
		],
	'HIGHLIGHT_STRICT_BLOCK' => [
		]
];

?>
