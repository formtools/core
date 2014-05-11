$(function() {

  $("#search_form form").bind("submit", function() {
    if (!$("#status_enabled").attr("checked") && !$("#status_disabled").attr("checked")) {
      ft.display_message("ft_message", 0, g.messages["validation_modules_search_no_status"]);
      return false;
    }
  });

  $("#key_section1").bind("keyup", function(e) {
    if (this.value.length == 4 && $.inArray(e.keyCode, [9, 16, 37, 39, 224]) == -1) {
      $("#key_section2").focus();
    }
    this.value = this.value.toUpperCase();
    check_license_key_entered();
  });

  // allows the user to paste in the entire license key into the first field & it puts the appropriate values in the
  // different fields
  $("#key_section1").bind("paste", function(e) {
    $(this).removeAttr("maxlength");
    setTimeout(function() {
      var license_key = $.trim($("#key_section1").val());
      $("#key_section1").attr("maxlength", "4").val(license_key.substring(0, 4));
      if (license_key.length >= 14) {
        $("#key_section2").val(license_key.substring(5, 9));
        $("#key_section3").val(license_key.substring(10, 14));
        check_license_key_entered();
      }
    }, 100);
  });

  $("#key_section2").bind("keyup", function(e) {
    if (this.value.length == 4 && $.inArray(e.keyCode, [9, 16, 37, 39, 224]) == -1) {
      $("#key_section3").focus();
    }
    this.value = this.value.toUpperCase();
    check_license_key_entered();
  });
  $("#key_section3").bind("keyup", function() {
    this.value = this.value.toUpperCase();
    check_license_key_entered();
  });

  function check_license_key_entered() {
    if ($("#key_section1").val().length == 4 && $("#key_section2").val().length == 4 && $("#key_section3").val().length == 4) {
      var btn = get_verify_button()
      $(btn).attr("disabled","").removeClass("ui-state-disabled");
    } else {
      var btn = get_verify_button();
      $(btn).attr("disabled","disabled").addClass("ui-state-disabled");
    }
  }

  function get_verify_button() {
    var button = null;
    $("#premium_module_dialog").closest(".ui-dialog").find("button").each(function() {
      if ($(this).text() == g.messages["word_verify"]) {
        button = this;
      }
    });
    return button;
  }

  $(".is_premium").bind("click", function() {
    var module_folder = $(this).closest("td").find(".module_folder").val();
    mm.curr_module_id = $(this).closest("td").find(".module_id").val();
    ft.create_dialog({
      dialog:     $("#premium_module_dialog"),
      title:      g.messages["phrase_please_enter_license_key"],
      popup_type: "info",
      min_width:  520,
      open: function() {
        $("#key_section1").focus();
        check_license_key_entered();
      },
      buttons: [{
        text:     g.messages["word_verify"],
        disabled: true,
        click: function() {
          mm.key = $("#key_section1").val() + "-" + $("#key_section2").val() + "-" + $("#key_section3").val();
          ft.dialog_activity_icon($("#premium_module_dialog"), "show");
          $.ajax({
            url:  "http://modules.formtools.org/validate.php",
            data: {
              k: mm.key,
              m: module_folder
            },
            dataType: "jsonp",
            jsonp:    "callback"
          });
        }
      },
      {
        text:  g.messages["word_close"],
        click: function() { $(this).dialog("close"); }
      }]
    });
    return false;
  });
});


var mm = {
  uninstall_module_dialog: $("<div></div>"),
  curr_module_id: null,
  key: null
};

mm.uninstall_module = function(module_id) {
  ft.create_dialog({
    dialog:     mm.uninstall_module_dialog,
    title:      g.messages["phrase_please_confirm"],
    content:    g.messages["confirm_uninstall_module"],
    popup_type: "warning",
    buttons: [{
      text:  g.messages["word_yes"],
      click: function() {
        window.location = "index.php?uninstall=" + module_id;
      },
    },
    {
      text:  g.messages["word_no"],
      click: function() {
        $(this).dialog("close");
      }
    }]
  });
  return false;
}

// note the lack of namespace. On purpose!
function mm_install_module_response(info) {
  if (!info.s) {
    ft.dialog_activity_icon($("#premium_module_dialog"), "hide");
    if (info.e == 2) {
      $("#premium_module_dialog").dialog("close");
      ft.display_message("ft_message", 0, g.messages["notify_invalid_license_key"]);
    } else if (info.e == 3) {
      $("#premium_module_dialog").dialog("close");
      ft.display_message("ft_message", 0, g.messages["notify_license_key_no_longer_valid"]);
    } else {
      $("#premium_module_dialog").dialog("close");
      ft.display_message("ft_message", 0, g.messages["notify_unknown_error"]);
    }
  } else {
    $("#modules_form").append("<input type=\"hidden\" name=\"ek\" value=\"" + info.ek + "\" />"
        + "<input type=\"hidden\" name=\"install\" value=\"" + mm.curr_module_id + "\" />"
        + "<input type=\"hidden\" name=\"k\" value=\"" + mm.key + "\" />").submit();
  }
}
