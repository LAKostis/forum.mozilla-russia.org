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
							<input type="button" value=" B " name="B" onclick="insertText('[b]','[/b]')" />
							<input type="button" value=" I " name="I" onclick="insertText('[i]','[/i]')" />
							<input type="button" value=" U " name="U" onclick="insertText('[u]','[/u]')" />
							<input type="button" value="URL" name="Url" onclick="insertText('[url]','[/url]')" />
							<input type="button" value="E-Mail" name="EMAIL" onclick="insertText('[email]','[/email]')" />
							<input type="button" value="Img" name="Img" onclick="insertText('[img]','[/img]')" />
							<input type="button" value="Quote" name="Quote" onclick="insertText('[quote]','[/quote]')" />
							<input type="button" value="Code" name="Code" onclick="insertText('[code]','[/code]')" />
							<input type="button" value="Line" name="HR" onclick="insertText('[hr /]','')" />
							<input type="button" value="Spoiler" name="Spoiler" onclick="insertText('[spoiler]','[/spoiler]')" />
							<input type="button" value="NoIndex" name="Spoiler" onclick="insertText('[noindex]','[/noindex]')" />
							<input type="button" value="UA" name="UA" onclick="insertText('::::&nbsp;<?php echo get_user_ua() ?>','')" />&nbsp;
							<input id="additional-more" type="button" value="<?php echo $lang_common['Show More'] ?>" name="More" onclick="toggleAdditional();" />
							<input id="additional-less" type="button" value="<?php echo $lang_common['Show Less'] ?>" name="More" onclick="toggleAdditional();" style="display:none" />
						</div>
						<div class="inform" style="display: none;" id="additional" >
						<table style="border: 0;">
							<tr>
								<td style="border: 0;">
									<fieldset style="padding: 8px;">
										<legend>Текст</legend>
											<input type="button" value="Strikethrough" name="S" onclick="insertText('[s]','[/s]')" />
											<input type="button" value="Highlight" name="H" onclick="insertText('[h]','[/h]')" />
											<input type="button" value="Text Align" name="ALIGN" onclick="insertText('[align=ALIGNMENT]','[/align]')" />
											<input type="button" value=" PRE " name="PRE" onclick="insertText('[pre]','[/pre]')" />
									</fieldset>
								</td>
								<td style="border: 0;">
									<fieldset style="padding: 8px;">
										<legend>Список</legend>
											<input type="button" value="Marker List" name="Marker List" onclick="insertText('[list]\n','\n[/list]')" />
											<input type="button" value="Marker Sub List" name="Marker Sub List" onclick="insertText('[list=s]\n','\n[/list]')" />
											<input type="button" value="Numeric List" name="Numeric List" onclick="insertText('[list=1]\n','\n[/list]')" />
											<input type="button" value="Word List" name="Word List" onclick="insertText('[list=a]\n','\n[/list]')" />
											<input type="button" value="List Item" name="OLI" onclick="insertText('[*]','')" /><br />
									</fieldset>
								</td>
							</tr>
							<tr>
								<td style="border: 0;">
									<fieldset style="padding: 8px;">
										<legend>Шрифт</legend>
											<input type="button" value="Font Face" name="FONT" onclick="insertText('[font=FACE]','[/font]')" />
											<input type="button" value="Font Color" name="COLOR" onclick="insertText('[color=#RRGGBB]','[/color]')" />
											<input type="button" value=" Super " name="SUP" onclick="insertText('[sup]','[/sup]')" />
											<input type="button" value=" Sub " name="SUB" onclick="insertText('[sub]','[/sub]')" />
									</fieldset>
								</td>
								<td style="border: 0;">
									<fieldset style="padding: 8px;">
										<legend>Таблица</legend>
											<input type="button" value=" Table " name="TABLE" onclick="insertText('[table]','[/table]')" />
											<input type="button" value="Table Caption" name="CAPTION" onclick="insertText('[caption]','[/caption]')" />
											<input type="button" value=" Row " name="TR" onclick="insertText('[tr]','[/tr]')" />
											<input type="button" value="Cell in Row" name="TD" onclick="insertText('[td]','[/td]')" />
									</fieldset>
								</td>
							</tr>
						</table>
						</div>
						<div style="padding-top: 4px; margin: 5px">
							<span style="margin-right: 25px">
<?php

require_once PUN_ROOT.'include/parser.php';

$smiley_index = [];
$smiley_count = 0;

for ($i = 0, $l = count($smiley_text); $i < $l; ++$i)
{
	if(in_array($smiley_img[$i], $smiley_index))
		continue;

	if ($smiley_count == $smiley_limit)
	{
		echo "\t\t\t\t\t\t\t\t".'<span id="smiley-more" style="padding-left:5px;white-space:nowrap"><a href="#" onclick="moreSmiles();return false;">'.$lang_common['Show More'].'</a></span><span id="smileys" style="display:none">';
		$smiley_limit = -1;
	}
	echo "\t\t\t\t\t\t\t\t".'<a href="#" onclick="insertText(\''.$smiley_text[$i].'\',\'\');return false;">'.($pun_config['o_smilies'] == '1' && $pun_user['show_smilies'] == '1' ? '<img src="img/smilies/'.$smiley_img[$i].'" alt="'.$smiley_text[$i].'" title="'.$smiley_text[$i].'" style="vertical-align:middle"/>' : $smiley_text[$i]).'</a>'."\n";

	$smiley_index[] = $smiley_img[$i];
	$smiley_count++;
}

if ($smiley_limit == -1)
	echo "\t\t\t\t\t\t\t\t".'</span><span id="smiley-less" style="padding-left:5px;display:none;white-space:nowrap"><a href="#" onclick="moreSmiles();return false;">'.$lang_common['Show Less'].'</a></span>';

?>

							</span>
							<span>
<?php

$browser_index = [];
$browser_count = 0;

for ($i = 0, $l = count($browser_text); $i < $l; ++$i)
{
	if(in_array($browser_img[$i], $browser_index))
		continue;

	if ($browser_count == $browser_limit)
	{
		echo "\t\t\t\t\t\t\t\t".'<span id="browser-more" style="padding-left:5px;white-space:nowrap"><a href="#" onclick="moreBrowser();return false;">'.$lang_common['Show More'].'</a></span><span id="browsers" style="display:none">';
		$browser_limit = -1;
	}
	echo "\t\t\t\t\t\t\t\t".'<a href="#" onclick="insertText(\''.$browser_text[$i].'\',\'\');return false;">'.($pun_config['o_smilies'] == '1' && $pun_user['show_smilies'] == '1' ? '<img src="img/browsers/'.$browser_img[$i].'" alt="'.$browser_text[$i].'" title="'.$browser_text[$i].'" style="vertical-align:middle"/>' : $browser_text[$i]).'</a>'."\n";

	$browser_index[] = $browser_img[$i];
	$browser_count++;
}

if ($browser_limit == -1)
	echo "\t\t\t\t\t\t\t\t\t".'</span><span id="browser-less" style="padding-left:5px;display:none;white-space:nowrap"><a href="#" onclick="moreBrowser();return false;">'.$lang_common['Show Less'].'</a></span>';



?>
							</span>
						</div>
