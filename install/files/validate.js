$(function() {
  $("#verify_license_keys").bind("click", install_ns.verify_license_keys);
});

var install_ns = {};
install_ns.has_invalid_key = false;

install_ns.check_license_key_entered = function() {
  if ($("#key_section1").val().length == 4 && $("#key_section2").val().length == 4 && $("#key_section3").val().length == 4) {
    var btn = get_verify_button()
    $(btn).attr("disabled","").removeClass("ui-state-disabled");
  } else {
    var btn = get_verify_button();
    $(btn).attr("disabled","disabled").addClass("ui-state-disabled");
  }
}


install_ns.verify_license_keys = function() {
  var num_modules = parseInt($("#num_premium_modules").val(), 10);

  // check that all module keys have been entered. They can't proceed until everything's been entered.
  var is_missing = false;
  install_ns.has_invalid_key = false;
  install_ns.keys = [];
  for (var i=1; i<=num_modules; i++) {
    var s1 = $("#key_section1_" + i).val();
    var s2 = $("#key_section2_" + i).val();
    var s3 = $("#key_section3_" + i).val();
    if (s1.length != 4 || s2.length != 4 || s3.length != 4) {
      is_missing = true;
    } else {
      install_ns.keys.push({
        module_folder: $("#module_folder_" + i).val(),
        row:           i,
        key:           s1 + "-" + s2 + "-" + s3,
        processed:     false
      })
    }
  }
  if (is_missing) {
    ft.create_dialog({
      title:      g.messages["word_error"],
      popup_type: "error",
      content:    g.messages["validation_incomplete_license_keys"],
      width:      500,
      buttons: [{
        text: g.messages["word_close"],
        click: function() { $(this).dialog("close"); }
      }]
    });
  } else {
    $("#verify_license_key_loading").show();
    install_ns.verify_next_key();
  }
}

install_ns.verify_next_key = function() {
  var next_unverified_key = null;
  for (var i=0; i<install_ns.keys.length; i++) {
    if (!install_ns.keys[i].processed) {
      next_unverified_key = install_ns.keys[i];
      break;
    }
  }
  if (next_unverified_key == null) {
    $("#verify_license_key_loading").hide();
    if (install_ns.has_invalid_key) {
      ft.create_dialog({
        title:      g.messages["word_error"],
        popup_type: "error",
        content:    g.messages["notify_invalid_license_keys"],
        width:      500,
        buttons: [{
          text: g.messages["word_close"],
          click: function() { $(this).dialog("close"); }
        }]
      });
    } else {
      // all is well! Store the info for use when registering the modules on step 6
      for (var i=0; i<install_ns.keys.length; i++) {
        $("#k_" + install_ns.keys[i].row).val(install_ns.keys[i].key);
        $("#ek_" + install_ns.keys[i].row).val(install_ns.keys[i].ek);
      }
      $("#verify_license_keys").hide();
      $("#skip_step").attr("value", g.messages["word_continue"]).attr("name", "track_license_keys");
    }
  } else {
    var key           = install_ns.keys[i].key;
    var module_folder = install_ns.keys[i].module_folder;
    $.ajax({
      url:  "http://modules.formtools.org/validate.php",
      data: {
        k: key,
        m: module_folder
      },
      dataType: "jsonp",
      jsonp:    "callback"
    });
  }
}


function mm_install_module_response(info) {

  // mark the last un-processed key as processed
  var premium_module_info = null;
  for (var i=0; i<install_ns.keys.length; i++) {
    if (!install_ns.keys[i].processed) {
      install_ns.keys[i].processed = true;
      if (typeof info.ek != "undefined") {
        install_ns.keys[i].ek = info.ek;
      }
      premium_module_info = install_ns.keys[i];
      break;
    }
  }

  if (!info.s) {
    if (info.e == 2) {
      $("#pmvr_" + premium_module_info.row).removeClass("pass").addClass("fail").html(g.messages["word_invalid"].toUpperCase());
      install_ns.has_invalid_key = true;
    }
  } else {
    $("#pmvr_" + premium_module_info.row).removeClass("fail").addClass("pass").html(g.messages["word_verified"].toUpperCase());
  }

  install_ns.verify_next_key();
}


