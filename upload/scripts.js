var txt = '', selected_id = null;

function copyQ(obj) {
	txt = window.getSelection ? window.getSelection().toString() : document.selection ? document.selection.createRange().text.toString() : '';
}

function pasteQ(nick, msg) {
	var message = document.getElementById('message' + msg);
	if (!txt && message)
		txt = message.textContent ? message.textContent : message.innerHTML;
	if (txt && document.forms['post']['req_message'])
		insertText('[quote=' + nick + ']' + trim(txt) + '[/quote]\n', '', true);
}

function pasteN(obj) {
	var nick = obj.textContent ? obj.textContent : (obj.innerHTML ? obj.innerHTML : null);
	if (nick && document.forms['post']['req_message'])
		insertText('[b]' + nick + '[/b]\n', '', true);
}

function setCaret(textObj) {
	if (textObj.createTextRange)
		textObj.caretPos = document.selection.createRange().duplicate();
}

function insertText(open, close, nofocus) {
	var msgfield = document.all ? document.all.req_message : document.forms['post'] ? document.forms['post']['req_message'] : document.forms['edit']['req_message'];
	var ss = msgfield.selectionStart, st = msgfield.scrollTop, sh = msgfield.scrollHeight;
	if (!ss && document.selection && msgfield.caretPos) {
		var text = open;
		if (close != '')
			text += document.selection.createRange().text;
		text += close;
		msgfield.caretPos.text = text;
	}
	else if (ss || ss == '0') {
		var se = msgfield.selectionEnd;
		var text = msgfield.value.substring(0, ss) + open, selection = msgfield.value.substring(ss, se);
		if (close != '')
			text += selection;
		text += close + msgfield.value.substring(se, msgfield.value.length);
		msgfield.value = text;
		se = close.length ? se : ss;
		if(close)
			msgfield.selectionStart = se + open.length - selection.length;
		msgfield.selectionEnd = se + open.length;
	}
	else
		msgfield.value += open + close;
	msgfield.scrollTop = st + msgfield.scrollHeight - sh;
	if(!nofocus)
		msgfield.focus();
}

function toggleAdditional(id) {
	document.getElementById('additional-more').style.display = document.getElementById('additional-more').style.display == 'none' ? 'inline' : 'none';
	document.getElementById('additional').style.display = document.getElementById('additional').style.display == 'none' ? 'inline' : 'none';
	document.getElementById('additional-less').style.display = document.getElementById('additional-less').style.display == 'none' ? 'inline' : 'none';
}

function moreSmiles() {
	document.getElementById('smiley-more').style.display = document.getElementById('smiley-more').style.display == 'none' ? 'inline' : 'none';
	document.getElementById('smileys').style.display = document.getElementById('smileys').style.display == 'none' ? 'inline' : 'none';
	document.getElementById('smiley-less').style.display = document.getElementById('smiley-less').style.display == 'none' ? 'inline' : 'none';
	if (document.getElementById('smileys').style.display == 'inline' && document.getElementById('browsers').style.display == 'inline')
		moreBrowser();
}

function moreBrowser() {
	document.getElementById('browser-more').style.display = document.getElementById('browser-more').style.display == 'none' ? 'inline' : 'none';
	document.getElementById('browsers').style.display = document.getElementById('browsers').style.display == 'none' ? 'inline' : 'none';
	document.getElementById('browser-less').style.display = document.getElementById('browser-less').style.display == 'none' ? 'inline' : 'none';
	if (document.getElementById('browsers').style.display == 'inline' && document.getElementById('smileys').style.display == 'inline')
		moreSmiles();
}

function toggleChildren(checked) {
	for(i=0; i < document.forms['actions'].elements.length; i++) {
		var item=document.forms['actions'].elements[i];
		if (item.name == 'delete_messages[]')
			item.checked=checked;
	}
}

function toggleSpoiler(obj) {
	obj.className = obj.className == 'spoiler-plus' ? 'spoiler-minus' : 'spoiler-plus';
	for(i=0; i < obj.parentNode.childNodes.length; i++) {
		var item=obj.parentNode.childNodes[i];
		if (item.className == 'spoiler-body')
		{
			item.style.display = item.style.display == 'block' ? 'none' : 'block';
			break;
		}
	}
}

function mailTo(s) {
	var n = 0;
	var r = '';
	for (var i = 0; i < s.length; i++)
	{
		n = s.charCodeAt(i);
		if (n>= 8364)
			n = 128;
		r += String.fromCharCode(n-(2));
	}
	location.href = r;
}

function incrementForm() {
	if(document.forms['post'])
		document.forms['post']['req_message'].rows += 15;
	else if(document.forms['edit'])
		document.forms['edit']['req_message'].rows += 15;
}

function decrementForm() {
	if(document.forms['post'] && document.forms['post']['req_message'].rows > 21)
		document.forms['post']['req_message'].rows -= 15;
	else if(document.forms['edit'] && document.forms['edit']['req_message'].rows > 21)
		document.forms['edit']['req_message'].rows -= 15;
}

function captchaReload() {
	document.getElementById("kcaptcha").src = "kcaptcha.php?" + Math.random();
	document.getElementById("req_image").value = "";
	document.getElementById("req_image").focus();
}

function toggleReports(group, obj) {
	for(i=0; i < document.forms['reports'].elements.length; i++) {
		var item=document.forms['reports'].elements[i];
		if (item.type=="checkbox" && item.name == 'zap_id['+group+'][]')
			item.checked=obj.checked;
	}
}

function googleSearch() {
	var mozInput = document.getElementById("google");
	var mozResult = document.getElementById("google-results");
	if (!mozInput || !mozResult)
		return;

	var mozContainer = document.getElementById("google-container");

	var mozWebSearch = new google.search.WebSearch();
	mozWebSearch.setUserDefinedLabel("Форум Mozilla Россия");
	mozWebSearch.setSiteRestriction("forum.mozilla-russia.org");
	mozWebSearch.setRestriction(google.search.Search.RESTRICT_SAFESEARCH, google.search.Search.SAFESEARCH_OFF);

	var mozSearcherOptions = new google.search.SearcherOptions();
	mozSearcherOptions.setRoot(mozResult);
	mozSearcherOptions.setExpandMode(google.search.SearchControl.EXPAND_MODE_OPEN);

	var mozDrawOptions = new google.search.DrawOptions();
	mozDrawOptions.setInput(mozInput);

	var mozSearchControl = new google.search.SearchControl();
	mozSearchControl.setSearchCompleteCallback(this, function() {
		mozContainer.style.display = mozInput.value ? "block" : "none";
	});
	mozSearchControl.addSearcher(mozWebSearch, mozSearcherOptions);
	mozSearchControl.setResultSetSize(google.search.Search.LARGE_RESULTSET);
	mozSearchControl.setTimeoutInterval(google.search.SearchControl.TIMEOUT_SHORT);
	mozSearchControl.setNoResultsString("Похожих тем не найдено");
	mozSearchControl.draw(null, mozDrawOptions);

	if (mozInput.value)
		mozSearchControl.execute(mozInput.value);
}

function trim(str) {
	return str.replace(/^\s+/g, "").replace(/\s+$/g, "");
}

function toggleSpamReport(obj) {
	document.getElementById('reason').style.display = !obj.checked ? 'block' : 'none';
}

function toggleSearch(obj, e) {
	if (e.target.className == 'label')
	{
		if (obj.className=='search' || obj.id=='search-submit')
		{
			obj.className = 'search more';
			document.getElementById('search-input').focus();
		}
		else
			obj.className = 'search';
		return false;
	}
	return true;
}

function toggleProjects(obj, e) {
	if (e.target.className == 'label')
	{
		obj.className = (obj.className=='projects' ? 'projects more' : 'projects');
		return false;
	}
	return true;
}

function searchGoogle()
{
	window.location.href = 'search.php?google=' + document.getElementById('search-input').value;
}

function codeSelect(obj)
{
	var pre = obj.parentNode.lastChild.firstChild;

	if (document.selection)
	{
		document.selection.empty();
		var range = document.body.createTextRange();
		range.moveToElementText(pre);
		range.select();
	}

	else if (window.getSelection)
	{
		window.getSelection().removeAllRanges()
		var range = document.createRange();
		range.selectNode(pre);
		window.getSelection().addRange(range);
	}

	return false;
}

function manualPage(msg, page, link)
{
	var oldpage = page;
	if((page = parseInt(prompt(msg + ":", page))) && oldpage != page)
		window.location.href = link + "&p=" + page;

	return false;
}

/*@cc_on
@if (@_win32 && @_jscript_version>4)

var minmax_elements;

function minmax_bind(el) {
	var em, ms;
	var st= el.style, cs= el.currentStyle;
	if (minmax_elements==window.undefined) {
		if (!document.body || !document.body.currentStyle) return;
		minmax_elements= new Array();
		window.attachEvent('onresize', minmax_delayout);
	}
	if (cs['max-width'])
		st['maxWidth']= cs['max-width'];
	ms= cs['maxWidth'];
	if (ms && ms!='auto' && ms!='none' && ms!='0' && ms!='') {
		st.minmaxWidth= cs.width;
		minmax_elements[minmax_elements.length]= el;
		minmax_delayout();
	}
}

var minmax_delaying = false;

function minmax_delayout() {
	if (minmax_delaying) return;
	minmax_delaying= true;
	window.setTimeout(minmax_layout, 0);
}

function minmax_stopdelaying() {
	minmax_delaying= false;
}

function minmax_layout() {
	window.setTimeout(minmax_stopdelaying, 100);
	var i, el, st, cs, optimal, inrange;
	for (i= minmax_elements.length; i-->0;) {
		el= minmax_elements[i]; st= el.style; cs= el.currentStyle;
		st.width= st.minmaxWidth; optimal= el.offsetWidth;
		inrange= true;
		if (inrange && cs.minWidth && cs.minWidth!='0' && cs.minWidth!='auto' && cs.minWidth!='') {
			st.width= cs.minWidth;
			inrange= (el.offsetWidth<optimal);
		}
		if (inrange && cs.maxWidth && cs.maxWidth!='none' && cs.maxWidth!='auto' && cs.maxWidth!='') {
			st.width= cs.maxWidth;
			inrange= (el.offsetWidth>optimal);
		}
		if (inrange) st.width= st.minmaxWidth;
	}
}

var minmax_SCANDELAY= 500;

function minmax_scan() {
	var el;
	for (var i= 0; i<document.all.length; i++) {
		el= document.all[i];
		if (!el.minmax_bound) {
			el.minmax_bound= true;
			minmax_bind(el);
		}
	}
}

var minmax_scanner;

function minmax_stop() {
	window.clearInterval(minmax_scanner);
	minmax_scan();
}

minmax_scan();
minmax_scanner= window.setInterval(minmax_scan, minmax_SCANDELAY);
window.attachEvent('onload', minmax_stop);

@end @*/
