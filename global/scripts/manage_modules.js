$(function () {
	$("#search_form form").bind("submit", function () {
		if (!$("#status_enabled").attr("checked") && !$("#status_disabled").attr("checked")) {
			ft.display_message("ft_message", 0, g.messages["validation_modules_search_no_status"]);
			return false;
		}
	});
});


var mm = {
	uninstall_module_dialog: $("<div></div>"),
	curr_module_id: null,
	key: null
};

mm.uninstall_module = function (module_id) {
	ft.create_dialog({
		dialog: mm.uninstall_module_dialog,
		title: g.messages["phrase_please_confirm"],
		content: g.messages["confirm_uninstall_module"],
		popup_type: "warning",
		buttons: [{
			text: g.messages["word_yes"],
			click: function () {
				window.location = "index.php?uninstall=" + module_id;
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
}
