<?php
/***********************************************************************

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)
  Copyright (C) 2005-2006  LAKostis (lakostis@mozilla-russia.org)

  This file is part of Russian Mozilla Team PunBB modification.

  PunBB is free software; you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 2 of the License,
  or (at your option) any later version.

  PunBB is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
  MA  02111-1307  USA

************************************************************************/


if (!isset($bbcode_form))
	$bbcode_form = 'post';
if (!isset($bbcode_field))
	$bbcode_field = 'req_message';

?>
						<div style="padding-top: 4px">
							<input type="button" value=" B " name="B" onclick="insert_text('[b]','[/b]')" /> 
							<input type="button" value=" I " name="I" onclick="insert_text('[i]','[/i]')" />
							<input type="button" value=" U " name="U" onclick="insert_text('[u]','[/u]')" />
							<input type="button" value="http://" name="Url" onclick="insert_text('[url]','[/url]')" />
							<input type="button" value="mailto:" name="EMAIL" onclick="insert_text('[email]','[/email]')" />
							<input type="button" value="Code" name="Code" onclick="insert_text('[code]','[/code]')" />
							<input type="button" value="Img" name="Img" onclick="insert_text('[img]','[/img]')" />
							<input type="button" value="Horiz. Line" name="HR" onclick="insert_text('[hr /]','')" />
							<input type="button" value="Quote" name="Quote" onclick="insert_text('[quote]','[/quote]')" />
							<input type="button" value="Quote User" name="Quote" onclick="insert_text('[quote= USER ]','[/quote]')" />
							<input type="button" value="Custom CSS" name="STYLE" onclick="insert_text('[style= STYLE]','[/style]')" />
							<?php echo "".'<input type="button" value="User Agent" name="UA" onclick="insert_text(\'::::&nbsp;'.get_user_ua().'\',\'\')" />'."\n"; ?>
							<input type="button" value="Show More" name="More" onclick="toggleSpan('q1');" />
						</div>
						<div class="inform" style="display: none;" id="q1Span" >
						<table style="border: 0;">
							<tr>
								<td style="border: 0;">
						        	<fieldset style="padding: 3px;">
							    		<legend>Текст</legend>
											<input type="button" value="Strikethrough" name="S" onclick="insert_text('[s]','[/s]')" />
											<input type="button" value="Highlight" name="H" onclick="insert_text('[h]','[/h]')" />
											<input type="button" value="Text Align" name="ALIGN" onclick="insert_text('[align= ALIGNMENT ]','[/align]')" />
									</fieldset>
								</td>
								<td style="border: 0;">
						        	<fieldset style="padding: 3px;">
							    		<legend>Список</legend>
											<input type="button" value=" UL " name="UL" onclick="insert_text('[ul]','[/ul]')" />
											<input type="button" value=" OL " name="OL" onclick="insert_text('[ol]','[/ol]')" />
											<input type="button" value="UL List Item" name="ULI" onclick="insert_text('[uli]','[/uli]')" />
											<input type="button" value="OL List Item" name="OLI" onclick="insert_text('[oli]','[/oli]')" /><br />
									</fieldset>
								</td>
							</tr>
							<tr>
								<td style="border: 0;">
						        	<fieldset style="padding: 3px;">
							    		<legend>Шрифт</legend>
											<input type="button" value="Font Face" name="FONT" onclick="insert_text('[font= FACE ]','[/font]')" />
											<input type="button" value="Font Color" name="COLOR" onclick="insert_text('[color=#RRGGBB]','[/color]')" />
											<input type="button" value=" Super " name="SUP" onclick="insert_text('[sup]','[/sup]')" />
											<input type="button" value=" Sub " name="SUB" onclick="insert_text('[sub]','[/sub]')" />
											<input type="button" value=" PRE " name="PRE" onclick="insert_text('[pre]','[/pre]')" />
									</fieldset>
								</td>
								<td style="border: 0;">
						        	<fieldset style="padding: 3px;">
							    		<legend>Таблица</legend>
											<input type="button" value=" Table " name="TABLE" onclick="insert_text('[table]','[/table]')" />
											<input type="button" value="Table Caption" name="CAPTION" onclick="insert_text('[caption]','[/caption]')" />
											<input type="button" value=" Row " name="TR" onclick="insert_text('[tr]','[/tr]')" />
											<input type="button" value="Cell in Row" name="TD" onclick="insert_text('[td]','[/td]')" />
									</fieldset>
								</td>
							</tr>
						</table>
						</div>							
						<div style="padding-top: 4px">
<?php

// Display the smiley set
require_once PUN_ROOT.'include/parser.php';

$smiley_dups = array();
$num_smilies = count($smiley_text);
$smtext = '';
for ($i = 0; $i < $num_smilies; ++$i)
{
	// Is there a smiley at the current index?
	if (!isset($smiley_text[$i]))
		continue;

	if (!in_array($smiley_img[$i], $smiley_dups))
		echo "\t\t\t\t\t\t\t".'<a href="#" onclick="insert_text(\''.$smiley_text[$i].'\',\'\');return false;">'.(($pun_config['o_smilies'] == '1' && $pun_user['show_smilies'] == '1') ? '<img src="img/smilies/'.$smiley_img[$i].'" alt="'.$smiley_text[$i].'"/>' : $smiley_text[$i]).'</a>'."\n";
	
	$smiley_dups[] = $smiley_img[$i];
}

?>
						</div>
