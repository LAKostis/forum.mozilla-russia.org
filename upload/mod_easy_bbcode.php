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
							<input type="button" value="URL" name="Url" onclick="insert_text('[url]','[/url]')" />
							<input type="button" value="E-Mail" name="EMAIL" onclick="insert_text('[email]','[/email]')" />
							<input type="button" value="Img" name="Img" onclick="insert_text('[img]','[/img]')" />
							<input type="button" value="Quote" name="Quote" onclick="insert_text('[quote]','[/quote]')" />
							<input type="button" value="Quote User" name="Quote" onclick="insert_text('[quote=USER]','[/quote]')" />
							<input type="button" value="Code" name="Code" onclick="insert_text('[code]','[/code]')" />
							<input type="button" value="Line" name="HR" onclick="insert_text('[hr /]','')" />
							<input type="button" value="User Agent" name="UA" onclick="insert_text('::::&nbsp;<?php echo get_user_ua() ?>','')" />
							<input id="additional-more" type="button" value="<?php echo $lang_common['Show More'] ?>" name="More" onclick="toggleAdditional();" />
							<input id="additional-less" type="button" value="<?php echo $lang_common['Show Less'] ?>" name="More" onclick="toggleAdditional();" style="display:none" />
						</div>
						<div class="inform" style="display: none;" id="additional" >
						<table style="border: 0;">
							<tr>
								<td style="border: 0;">
						        	<fieldset style="padding: 8px;">
							    		<legend>Текст</legend>
											<input type="button" value="Strikethrough" name="S" onclick="insert_text('[s]','[/s]')" />
											<input type="button" value="Highlight" name="H" onclick="insert_text('[h]','[/h]')" />
											<input type="button" value="Text Align" name="ALIGN" onclick="insert_text('[align=ALIGNMENT]','[/align]')" />
											<input type="button" value="Custom CSS" name="STYLE" onclick="insert_text('[style=STYLE]','[/style]')" />
									</fieldset>
								</td>
								<td style="border: 0;">
						        	<fieldset style="padding: 8px;">
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
						        	<fieldset style="padding: 8px;">
							    		<legend>Шрифт</legend>
											<input type="button" value="Font Face" name="FONT" onclick="insert_text('[font=FACE]','[/font]')" />
											<input type="button" value="Font Color" name="COLOR" onclick="insert_text('[color=#RRGGBB]','[/color]')" />
											<input type="button" value=" Super " name="SUP" onclick="insert_text('[sup]','[/sup]')" />
											<input type="button" value=" Sub " name="SUB" onclick="insert_text('[sub]','[/sub]')" />
											<input type="button" value=" PRE " name="PRE" onclick="insert_text('[pre]','[/pre]')" />
									</fieldset>
								</td>
								<td style="border: 0;">
						        	<fieldset style="padding: 8px;">
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
						<div style="padding-top: 4px; margin: 5px">
							<span style="margin-right: 25px">
<?php

require_once PUN_ROOT.'include/parser.php';

$smiley_index = array();
$smiley_count = 0;

for ($i = 0, $l = count($smiley_text); $i < $l; ++$i)
{
	if(in_array($smiley_img[$i], $smiley_index))
		continue;

	if ($smiley_count == $smiley_limit)
	{
		echo "\t\t\t\t\t\t\t".'<span id="smiley-more" style="padding-left:5px;white-space:nowrap"><a href="#" onclick="moreSmiles();return false;">'.$lang_common['Show More'].'</a></span><span id="smileys" style="display:none">';
		$smiley_limit = -1;
	}
	echo "\t\t\t\t\t\t\t".'<a href="#" onclick="insert_text(\''.$smiley_text[$i].'\',\'\');return false;">'.(($pun_config['o_smilies'] == '1' && $pun_user['show_smilies'] == '1') ? '<img src="img/smilies/'.$smiley_img[$i].'" alt="'.$smiley_text[$i].'" title="'.$smiley_text[$i].'"/>' : $smiley_text[$i]).'</a>'."\n";

	$smiley_index[] = $smiley_img[$i];
	$smiley_count++;
}

if ($smiley_limit == -1)
	echo "\t\t\t\t\t\t\t".'</span><span id="smiley-less" style="padding-left:5px;display:none;white-space:nowrap"><a href="#" onclick="moreSmiles();return false;">'.$lang_common['Show Less'].'</a></span>';

?>

</span>
<span>

<?php

$browser_index = array();
$browser_count = 0;

for ($i = 0, $l = count($browser_text); $i < $l; ++$i)
{
	if(in_array($browser_img[$i], $browser_index))
		continue;

	if ($browser_count == $browser_limit)
	{
		echo "\t\t\t\t\t\t\t".'<span id="browser-more" style="padding-left:5px;white-space:nowrap"><a href="#" onclick="moreBrowser();return false;">'.$lang_common['Show More'].'</a></span><span id="browsers" style="display:none">';
		$browser_limit = -1;
	}
	echo "\t\t\t\t\t\t\t".'<a href="#" onclick="insert_text(\''.$browser_text[$i].'\',\'\');return false;">'.(($pun_config['o_smilies'] == '1' && $pun_user['show_smilies'] == '1') ? '<img src="img/browsers/'.$browser_img[$i].'" alt="'.$browser_text[$i].'" title="'.$browser_text[$i].'"/>' : $browser_text[$i]).'</a>'."\n";

	$browser_index[] = $browser_img[$i];
	$browser_count++;
}

if ($browser_limit == -1)
	echo "\t\t\t\t\t\t\t".'</span><span id="browser-less" style="padding-left:5px;display:none;white-space:nowrap"><a href="#" onclick="moreBrowser();return false;">'.$lang_common['Show Less'].'</a></span>';



?>
							</span>
						</div>
