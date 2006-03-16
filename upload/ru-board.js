function copyQ() { 
	txt='' 
		if (window.getSelection) {txt=window.getSelection()} 
		else if (document.selection) {txt=document.selection.createRange().text;} 
		txt='[quote]'+txt+'[/quote]\n'
} 

function pasteQ(){
	if (txt!='' && document.forms['post']['req_message']) 
		insert_text(txt,'','1'); 
} 

function pasteN(text){ 
	if (text!='' && document.forms['post']['req_message'])
		insert_text("[b]" + text, '[/b]\n','1');
}

<!-- menu folder js begin //-->
<!--
function ToggleAll(checked) {
	for(i=0; i < document.forms['post'].elements.length; i++) {
		var item=document.forms['post'].elements[i];
		if (item.name == "delete_messages[]")
		{
			item.checked=checked;
		};
	}
}
//-->
<!-- menu folder js end //-->
