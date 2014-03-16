/* 
 * wp-greet javascript for using smilies with tinymce
 */
function smile(smile) {
	var tedit = null;
	var itext = "<img class='wpg_smile' alt='' src='" + smile + "' />";

	if (typeof tinyMCE != "undefined")
		tedit = tinyMCE.get('message');

	if (tedit == null || tedit.isHidden() == true) {
		tarea = document.getElementById(textid);
		insert_text(itext, tarea);
	} else if ((tedit.isHidden() == false) && window.tinyMCE) {
		window.tinyMCE.execInstanceCommand('message', 'mceInsertContent',
				false, itext);
	}
}