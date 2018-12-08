var mt = {
	uninstall_module_dialog: $("<div></div>")
};

mt.uninstall_theme = function (theme_id) {
	ft.create_dialog({
		dialog: mt.uninstall_module_dialog,
		title: g.messages["phrase_please_confirm"],
		content: g.messages["confirm_uninstall_theme"],
		popup_type: "warning",
		buttons: [{
			text: g.messages["word_yes"],
			click: function () {
				window.location = "index.php?uninstall=" + theme_id;
			},
		},
		{
			text: g.messages["word_no"],
			click: function () {
				$(this).dialog("close");
			}
		}]
	});
	return false;
};