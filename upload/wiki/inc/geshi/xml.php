<?php
/*************************************************************************************
 * xml.php
 * -------
 * Author: Nigel McNie (oracle.shinoda@gmail.com)
 * Copyright: (c) 2004 Nigel McNie (http://qbnz.com/highlighter/)
 * Release Version: 1.0.1
 * CVS Revision Version: $Revision: 1.1 $
 * Date Started: 2004/09/01
 * Last Modified: $Date: 2006/03/16 17:54:52 $
 *
 * XML language file for GeSHi. Based on the idea/file by Christian Weiske
 *
 * CHANGES
 * -------
 * 2004/11/27 (1.0.1)
 *   -  Added support for multiple object splitters
 * 2004/10/27 (1.0.0)
 *   -  First Release
 *
 * TODO (updated 2004/11/27)
 * -------------------------
 * * Check regexps work and correctly highlight XML stuff and nothing else
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
	'LANG_NAME' => 'HTML',
	'COMMENT_SINGLE' => [],
	'COMMENT_MULTI' => ['<!--' => '-->'],
	'CASE_KEYWORDS' => GESHI_CAPS_NO_CHANGE,
	'QUOTEMARKS' => ["'", '"'],
	'ESCAPE_CHAR' => '\\',
	'KEYWORDS' => [
		],
	'SYMBOLS' => [
		],
	'CASE_SENSITIVE' => [
		GESHI_COMMENTS => false,
		],
	'STYLES' => [
		'KEYWORDS' => [
			],
		'COMMENTS' => [
			'MULTI' => 'color: #808080; font-style: italic;'
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
		'SCRIPT' => [
			0 => 'color: #00bbdd;',
			1 => 'color: #ddbb00;',
			2 => 'color: #339933;',
			3 => 'color: #009900;'
			],
		'REGEXPS' => [
			0 => 'color: #000066;',
			1 => 'font-weight: bold; color: black;',
			2 => 'font-weight: bold; color: black;',
			]
		],
	'URLS' => [
		],
	'OOLANG' => false,
	'OBJECT_SPLITTERS' => [
		],
	'REGEXPS' => [
		0 => [
			GESHI_SEARCH => '(((xml:)?[a-z\-]+))(=)',
			GESHI_REPLACE => '\\1',
			GESHI_MODIFIERS => 'i',
			GESHI_BEFORE => '',
			GESHI_AFTER => '\\4'
			],
		1 => [
			GESHI_SEARCH => '(&lt;/?[a-z0-9]*(&gt;)?)',
			GESHI_REPLACE => '\\1',
			GESHI_MODIFIERS => 'i',
			GESHI_BEFORE => '',
			GESHI_AFTER => ''
			],
		2 => [
			GESHI_SEARCH => '((/)?&gt;)',
			GESHI_REPLACE => '\\1',
			GESHI_MODIFIERS => 'i',
			GESHI_BEFORE => '',
			GESHI_AFTER => ''
			]
		],
	'STRICT_MODE_APPLIES' => GESHI_ALWAYS,
	'SCRIPT_DELIMITERS' => [
		0 => [
			'<!DOCTYPE' => '>'
			],
		1 => [
			'&' => ';'
			],
		2 => [
			'<![CDATA[' => ']]>'
			],
		3 => [
			'<' => '>'
			]
	],
	'HIGHLIGHT_STRICT_BLOCK' => [
		0 => false,
		1 => false,
		2 => false,
		3 => true
		]
];

?>
