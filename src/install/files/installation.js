$(function () {

	$("#use_custom_cache_folder").bind("change", function (e) {
		if (e.target.checked) {
			$("#cache_folder_default").hide();
			$("#cache_folder_custom").show();
			$("#cache_folder_default_result").hide();
			$("#cache_folder_custom_result").show();
			$("#next").attr('disabled', true);

		} else {
			$("#cache_folder_default").show();
			$("#cache_folder_custom").hide();
			$("#cache_folder_default_result").show();
			$("#cache_folder_custom_result").hide();
			$("#next").removeAttr('disabled');
		}
	});

});