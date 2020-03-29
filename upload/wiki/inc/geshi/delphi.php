<?php
/*************************************************************************************
 * delphi.php
 * ----------
 * Author: Járja Norbert (jnorbi@vipmail.hu)
 * Copyright: (c) 2004 Járja Norbert, Nigel McNie (http://qbnz.com/highlighter)
 * Release Version: 1.0.1
 * CVS Revision Version: $Revision: 1.2 $
 * Date Started: 2004/07/26
 * Last Modified: $Date: 2006/06/07 21:33:09 $
 *
 * Delphi (Object Pascal) language file for GeSHi.
 *
 * CHANGES
 * -------
 * 2004/11/27 (1.0.1)
 *  -  Added support for multiple object splitters
 * 2004/10/27 (1.0.0)
 *   -  First Release
 *
 * TODO (updated 2004/11/27)
 * -------------------------
 *
 *************************************************************************************
 *
 *   This file is part of GeSHi.
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
	'LANG_NAME' => 'Delphi',
	'COMMENT_SINGLE' => [1 => '//'],
	'COMMENT_MULTI' => ['(*' => '*)', '{' => '}'],
	'CASE_KEYWORDS' => 0,
	'QUOTEMARKS' => ["'", '"'],
	'ESCAPE_CHAR' => '',
	'KEYWORDS' => [
		1 => [
			'And', 'Array', 'As', 'Begin', 'Case', 'Class', 'Constructor', 'Destructor', 'Div', 'Do', 'DownTo', 'Else',
			'End', 'Except', 'File', 'Finally', 'For', 'Function', 'Goto', 'If', 'Implementation', 'In', 'Inherited', 'Interface',
			'Is', 'Mod', 'Not', 'Object', 'Of', 'On', 'Or', 'Packed', 'Procedure', 'Program', 'Property', 'Raise', 'Record',
			'Repeat', 'Set', 'Shl', 'Shr', 'Then', 'ThreadVar', 'To', 'Try', 'Unit', 'Until', 'Uses', 'While', 'With', 'Xor'
			],
		2 => [
			'nil', 'false', 'true', 'var', 'type', 'const'
			],
		3 => [
			'Abs', 'Addr', 'AnsiCompareStr', 'AnsiCompareText', 'AnsiContainsStr', 'AnsiEndsStr', 'AnsiIndexStr', 'AnsiLeftStr',
			'AnsiLowerCase', 'AnsiMatchStr', 'AnsiMidStr', 'AnsiPos', 'AnsiReplaceStr', 'AnsiReverseString', 'AnsiRightStr',
			'AnsiStartsStr', 'AnsiUpperCase', 'ArcCos', 'ArcSin', 'ArcTan', 'Assigned', 'BeginThread', 'Bounds', 'CelsiusToFahrenheit',
			'ChangeFileExt', 'Chr', 'CompareStr', 'CompareText', 'Concat', 'Convert', 'Copy', 'Cos', 'CreateDir', 'CurrToStr',
			'CurrToStrF', 'Date', 'DateTimeToFileDate', 'DateTimeToStr', 'DateToStr', 'DayOfTheMonth', 'DayOfTheWeek', 'DayOfTheYear',
			'DayOfWeek', 'DaysBetween', 'DaysInAMonth', 'DaysInAYear', 'DaySpan', 'DegToRad', 'DeleteFile', 'DiskFree', 'DiskSize',
			'DupeString', 'EncodeDate', 'EncodeDateTime', 'EncodeTime', 'EndOfADay', 'EndOfAMonth', 'Eof', 'Eoln', 'Exp', 'ExtractFileDir',
			'ExtractFileDrive', 'ExtractFileExt', 'ExtractFileName', 'ExtractFilePath', 'FahrenheitToCelsius', 'FileAge',
			'FileDateToDateTime', 'FileExists', 'FilePos', 'FileSearch', 'FileSetDate', 'FileSize', 'FindClose', 'FindCmdLineSwitch',
			'FindFirst', 'FindNext', 'FloatToStr', 'FloatToStrF', 'Format', 'FormatCurr', 'FormatDateTime', 'FormatFloat', 'Frac',
			'GetCurrentDir', 'GetLastError', 'GetMem', 'High', 'IncDay', 'IncMinute', 'IncMonth', 'IncYear', 'InputBox',
			'InputQuery', 'Int', 'IntToHex', 'IntToStr', 'IOResult', 'IsInfinite', 'IsLeapYear', 'IsMultiThread', 'IsNaN',
			'LastDelimiter', 'Length', 'Ln', 'Lo', 'Log10', 'Low', 'LowerCase', 'Max', 'Mean', 'MessageDlg', 'MessageDlgPos',
			'MonthOfTheYear', 'Now', 'Odd', 'Ord', 'ParamCount', 'ParamStr', 'Pi', 'Point', 'PointsEqual', 'Pos', 'Pred',
			'Printer', 'PromptForFileName', 'PtInRect', 'RadToDeg', 'Random', 'RandomRange', 'RecodeDate', 'RecodeTime', 'Rect',
			'RemoveDir', 'RenameFile', 'Round', 'SeekEof', 'SeekEoln', 'SelectDirectory', 'SetCurrentDir', 'Sin', 'SizeOf',
			'Slice', 'Sqr', 'Sqrt', 'StringOfChar', 'StringReplace', 'StringToWideChar', 'StrToCurr', 'StrToDate', 'StrToDateTime',
			'StrToFloat', 'StrToInt', 'StrToInt64', 'StrToInt64Def', 'StrToIntDef', 'StrToTime', 'StuffString', 'Succ', 'Sum', 'Tan',
			'Time', 'TimeToStr', 'Tomorrow', 'Trunc', 'UpCase', 'UpperCase', 'VarType', 'WideCharToString', 'WrapText', 'Yesterday',

			'Append', 'AppendStr', 'Assign', 'AssignFile', 'AssignPrn', 'Beep', 'BlockRead', 'BlockWrite', 'Break',
			'ChDir', 'Close', 'CloseFile', 'Continue', 'DateTimeToString', 'Dec', 'DecodeDate', 'DecodeDateTime',
			'DecodeTime', 'Delete', 'Dispose', 'EndThread', 'Erase', 'Exclude', 'Exit', 'FillChar', 'Flush', 'FreeAndNil',
			'FreeMem', 'GetDir', 'GetLocaleFormatSettings', 'Halt', 'Inc', 'Include', 'Insert', 'MkDir', 'Move', 'New',
			'ProcessPath', 'Randomize', 'Read', 'ReadLn', 'ReallocMem', 'Rename', 'ReplaceDate', 'ReplaceTime',
			'Reset', 'ReWrite', 'RmDir', 'RunError', 'Seek', 'SetLength', 'SetString', 'ShowMessage', 'ShowMessageFmt',
			'ShowMessagePos', 'Str', 'Truncate', 'Val', 'Write', 'WriteLn'
			],
		4 => [
			'AnsiChar', 'AnsiString', 'Boolean', 'Byte', 'Cardinal', 'Char', 'Comp', 'Currency', 'Double', 'Extended',
			'Int64', 'Integer', 'LongInt', 'LongWord', 'PAnsiChar', 'PAnsiString', 'PChar', 'PCurrency', 'PDateTime',
			'PExtended', 'PInt64', 'Pointer', 'PShortString', 'PString', 'PVariant', 'PWideChar', 'PWideString',
			'Real', 'Real48', 'ShortInt', 'ShortString', 'Single', 'SmallInt', 'String', 'TBits', 'TConvType', 'TDateTime',
			'Text', 'TextFile', 'TFloatFormat', 'TFormatSettings', 'TList', 'TObject', 'TOpenDialog', 'TPoint',
			'TPrintDialog', 'TRect', 'TReplaceFlags', 'TSaveDialog', 'TSearchRec', 'TStringList', 'TSysCharSet',
			'TThreadFunc', 'Variant', 'WideChar', 'WideString', 'Word'
			],
		],
	'CASE_SENSITIVE' => [
		GESHI_COMMENTS => true,
		1 => false,
		2 => false,
		3 => false,
		4 => false,
		],
	'STYLES' => [
		'KEYWORDS' => [
			1 => 'color: #000000; font-weight: bold;',
			2 => 'color: #000000; font-weight: bold;',
			3 => 'color: #000066;',
			4 => 'color: #993333;'
			],
		'COMMENTS' => [
			1 => 'color: #808080; font-style: italic;',
			'MULTI' => 'color: #808080; font-style: italic;'
			],
		'ESCAPE_CHAR' => [
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
			1 => 'color: #006600;'
			],
		'REGEXPS' => [
			],
		'SYMBOLS' => [
			0 => 'color: #66cc66;'
			],
		'SCRIPT' => [
			]
		],
	'URLS' => [
		1 => '',
		2 => '',
		3 => '',
		4 => ''
		],
	'OOLANG' => true,
	'OBJECT_SPLITTERS' => [
		1 => '.'
		],
	'REGEXPS' => [
		],
	'STRICT_MODE_APPLIES' => GESHI_NEVER,
	'SCRIPT_DELIMITERS' => [
		],
	'HIGHLIGHT_STRICT_BLOCK' => [
		]
];

?>
