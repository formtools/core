var emails_ns = {}
emails_ns.num_recipients = null;

// the purpose of this is purely to provide a unique number for each dynamically added email recipient.
// It's initialized by the onload handler to be num_recipients+1
emails_ns.recipient_num = null;


/**
 * Selects an email pattern from the available email template dropdown.
 */
emails_ns.select_template = function(format_type, row)
{
  if (!row)
    return;

  if (format_type == "html")
    html_editor.setCode($(format_type + "_" + row).value);
  if (format_type == "text")
    text_editor.setCode($(format_type + "_" + row).value);
}


/**
 * Hides / shows the custom From, Reply-to and Recipient elements. These allow the administrator
 * to choose whoever they want should receive, etc. the emails.
 */
emails_ns.show_custom_email_field = function(target, val)
{
  if (val == "custom")
    $("custom_" + target).style.display = "block";
  else
    $("custom_" + target).style.display = "none";
}


/**
 * Adds a recipient for this email template.
 */
emails_ns.add_recipient = function(f)
{
  var recipient_target = $("recipient_options").value;
  if (recipient_target == "")
    return;

  var is_email_field = /^form_email_id/;
  var is_client      = /^client_account_id/;

  var rtype = recipient_target;
  if (is_email_field.test(recipient_target))
    rtype = "form_email_field";
  if (is_client.test(recipient_target))
    rtype = "client";

  var recipient_str = emails_ns._get_recipient_string(recipient_target);

  switch (rtype)
  {
    case "admin":
      emails_ns._add_recipient({
        full_display_string: recipient_str,
        recipient_type: $("recipient_type").value,
        recipient_user_type: "admin"
          });
      break;

    case "client":
      var info = recipient_target.match(/client_account_id_(\d+)/);
      var cid = info[1];
      emails_ns._add_recipient({
         full_display_string: recipient_str,
         client_id: cid,
         recipient_type: $("recipient_type").value,
         recipient_user_type: "client"
           });
      break;

    case "form_email_field":
      var info = recipient_target.match(/form_email_id_(\d+)/);
      var feid = info[1];
      emails_ns._add_recipient({
        full_display_string: g.messages["phrase_form_email_field_b_c"] + " " + recipient_str,
        recipient_type: $("recipient_type").value,
        recipient_user_type: "form_email_field",
        form_email_id: feid
          });
      break;

    case "custom":
      emails_ns.add_custom_recipient(f);
      break;

    default:
      alert("Unknown recipient type!");
      break;
  }
}


// get the recipient string (e.g. "Ben Keen <formtools@encorewebstudios.com>")
emails_ns._get_recipient_string = function(option_val)
{
  var dd = $("recipient_options");
  var recipient_str = null;
  for (var i=0; i<dd.options.length; i++)
  {
    if (dd.options[i].value == option_val)
    {
      recipient_str = dd.options[i].text;
      break;
    }
  }

  return recipient_str;
}


emails_ns.add_custom_recipient = function(f)
{
  // check at least the email is entered
  var rules = [
    "required,custom_recipient_email," + g.messages["validation_no_custom_recipient_email"],
    "valid_email,custom_recipient_email," + g.messages["validation_invalid_email"]
      ];

  if (rsv.validate(f, rules))
  {
    emails_ns._add_recipient({
      name: $("custom_recipient_name").value,
      email: $("custom_recipient_email").value,
      recipient_type: $("custom_recipient_type").value,
      recipient_user_type: "custom"
        });
  }
}


/**
 * Removes a recipient from the page. Note: it still requires an update to update the database.
 */
emails_ns.remove_recipient = function(num)
{
  emails_ns.num_recipients--;

  // if necessary, show the "No recipients" text
  if (emails_ns.num_recipients == 0)
    $("no_recipients").show();

  $("recipient_" + num).innerHTML = "";
  $("recipient_" + num).hide();

  return false;
}


/**
 * Helper function to add a new recipient to the page. It also creates and appends hidden fields
 * which are passed to the server on update.
 *
 * @param object info this object can contain any of the following properties:
 *             name  - the name of the person being added (custom recipient)
 *             email - the email of the person (custom recipients)
 *             full_display_string - the name + email (administrators & clients)
 *             recipient_type - "" (for main), "cc" or "bcc" (this field is required)
 *             client_id - the client ID (clients only)
 *             form_email_id - the form email ID (form email fields only)
 *             recipient_type - "client", "custom" or "admin" (required)
 */
emails_ns._add_recipient = function(info)
{
  $("no_recipients").hide();

  var num = ++emails_ns.recipient_num; // our unique num (always incremented)

  var recipient_type_str = "";
  if (info.recipient_type == "cc")
    recipient_type_str = "&nbsp;<b>[cc]</b>";
  else if (info.recipient_type == "bcc")
    recipient_type_str = "&nbsp;<b>[bcc]</b>";

  switch (info.recipient_user_type)
  {
    case "admin":
      var str = "<div id=\"recipient_" + num + "\">" + info.full_display_string.escapeHTML()
              + recipient_type_str
              + " &nbsp;<a href=\"#\" onclick=\"return emails_ns.remove_recipient(" + num + ")\">[x]</a>"
              + "<input type=\"hidden\" name=\"recipients[]\" value=\"" + num + "\" />"
              + "<input type=\"hidden\" name=\"recipient_" + num + "_user_type\" value=\"admin\" />"
              + "<input type=\"hidden\" id=\"recipient_" + num + "_type\" name=\"recipient_" + num + "_type\" value=\"" + info.recipient_type + "\" />"
              + "</div>";
      break;

    case "form_email_field":
      var str = "<div id=\"recipient_" + num + "\">" + info.full_display_string.escapeHTML()
              + recipient_type_str
              + " &nbsp;<a href=\"#\" onclick=\"return emails_ns.remove_recipient(" + num + ")\">[x]</a>"
              + "<input type=\"hidden\" name=\"recipients[]\" value=\"" + num + "\" />"
              + "<input type=\"hidden\" name=\"recipient_" + num + "_user_type\" value=\"form_email_field\" />"
              + "<input type=\"hidden\" id=\"recipient_" + num + "_type\" name=\"recipient_" + num + "_type\" value=\"" + info.recipient_type + "\" />"
              + "<input type=\"hidden\" name=\"recipient_" + num + "_form_email_id\" value=\"" + info.form_email_id + "\" />"
              + "</div>";
      break;

    case "client":
      var str = "<div id=\"recipient_" + num + "\">" + info.full_display_string.escapeHTML()
              + recipient_type_str
              + " &nbsp;<a href=\"#\" onclick=\"return emails_ns.remove_recipient(" + num + ")\">[x]</a>"
              + "<input type=\"hidden\" name=\"recipients[]\" value=\"" + num + "\" />"
              + "<input type=\"hidden\" name=\"recipient_" + num + "_user_type\" value=\"client\" />"
              + "<input type=\"hidden\" id=\"recipient_" + num + "_type\" name=\"recipient_" + num + "_type\" value=\"" + info.recipient_type + "\" />"
              + "<input type=\"hidden\" name=\"recipient_" + num + "_account_id\" value=\"" + info.client_id + "\" />"
              + "</div>";
      break;

    case "custom":
      if (info.name)
        recipient = info.name + " &lt;" + info.email + "&gt;";
      else
        recipient = info.email;

      var str = "<div id=\"recipient_" + num + "\">" + recipient
              + recipient_type_str
              + " &nbsp;<a href=\"#\" onclick=\"return emails_ns.remove_recipient(" + num + ")\">[x]</a>"
              + "<input type=\"hidden\" name=\"recipients[]\" value=\"" + num + "\" />"
              + "<input type=\"hidden\" name=\"recipient_" + num + "_user_type\" value=\"custom\" />"
              + "<input type=\"hidden\" id=\"recipient_" + num + "_type\" name=\"recipient_" + num + "_type\" value=\"" + info.recipient_type + "\" />"
              + "<input type=\"hidden\" name=\"recipient_" + num + "_name\" value=\"" + $("custom_recipient_name").value + "\" />"
              + "<input type=\"hidden\" name=\"recipient_" + num + "_email\" value=\"" + $("custom_recipient_email").value + "\" />"
              + "</div>";
      break;

    default:
      alert("Unknown recipient type!");
      break;
  }

  $("email_recipients").innerHTML += str;

  // increment the num_recipients count
  emails_ns.num_recipients++;
}


/**
 * Sends a test email, via Ajax.
 *
 * @param string the action to take: "send" or "display"
 */
emails_ns.send_test_email = function(f, action)
{
  // confirm all the fields are entered properly
  var rules = [];
  rules.push("required,test_email_recipient," + g.messages["validation_no_test_email_recipient"]);
  rules.push("valid_email,test_email_recipient," + g.messages["validation_valid_email"]);
  rules.push("if:test_email_data_source=submission_id,required,test_email_submission_id," + g.messages["validation_no_test_email_submission_id"]);

  if (rsv.validate(f, rules))
  {
    var query_str = "test_email_format=" + $("test_email_format").value
                  + "&test_email_recipient=" + $("test_email_recipient").value
                  + "&test_email_data_source=" + ft.get_checked_value(f.test_email_data_source)
                  + "&test_email_submission_id=" + f.test_email_submission_id.value

    if (action == "display")
    {
      emails_ns.log_activity(true);
      page_url = g.root_url + "/global/code/actions.php?action=display_test_email&" + query_str;
      new Ajax.Request(page_url, {
        method: 'get',
        onSuccess: emails_ns.display_test_email,
        onFailure: function() { alert("Couldn't load page: " + page_url); }
      });
    }
    else if (action == "send")
    {
      emails_ns.log_activity(true);
      page_url = g.root_url + "/global/code/actions.php?action=send_test_email&" + query_str;
      new Ajax.Request(page_url, {
        method: 'get',
        onSuccess: emails_ns.send_test_email_response,
        onFailure: function() { alert("Couldn't load page: " + page_url); }
      });
    }
  }

  return false;
}


/**
 * This function is the response function from an Ajax request to the server, requesting a test
 * email. It gets passed the entire email content via the single transport parameter as a JSON
 * array. It displays the text and/or HTML email in the page, for the user to examine.
 *
 * @param array The array has two indexes: [0] true/false, [1] if false, this is a string containing
 *     the error message. If true, this is an object containing the various elements of the email (subject,
 *     cc, bcc, etc) as properties.
 */
emails_ns.display_test_email = function(transport)
{
  emails_ns.log_activity(false);
  try {
    var response = transport.responseText.evalJSON(true);
  }
  catch (e)
  {
    alert("Error: " + e);
    return;
  }

  var success = response[0];

  if (success)
  {
    var email_info = response[1];

    var from     = email_info.from;
    var reply_to = email_info.reply_to;
    var subject  = email_info.subject.toString().unescapeHTML();

    var to       = email_info.to;
    var to_html = "";
    for (var i=0; i<to.length; i++)
      to_html += to[i].recipient_line + "<br />";

    var cc       = email_info.cc;
    var cc_html = "";
    for (var i=0; i<cc.length; i++)
      cc_html += cc[i].recipient_line + "<br />";

    var bcc      = email_info.bcc;
    var bcc_html = "";
    for (var i=0; i<bcc.length; i++)
      bcc_html += bcc[i].recipient_line + "<br />";

    // build the header table
    var table = "<table cellpadding=\"0\" cellspacing=\"1\"><tr>"
              + "<td width=\"100\" valign=\"top\">To:</td>"
              + "<td>" + to_html + "</td>"
              + "</tr>";

    if (cc_html)
      table += "<tr><td valign=\"top\">" + g.messages["word_cc_c"] + "</td><td>" + cc_html + "</td></tr>";

    if (bcc_html)
      table += "<tr><td valign=\"top\">" + g.messages["word_bcc_c"] + "</td><td>" + bcc_html + "</td></tr>";

    if (from && typeof from == "object" && typeof from.recipient_line != "undefined" && from.recipient_line != "")
      table += "<tr><td>" + g.messages["word_from_c"] + "</td><td>" + from.recipient_line + "</td></tr>";

    if (reply_to && typeof reply_to == "object" && typeof reply_to.recipient_line != "undefined" && reply_to.recipient_line != "")
      table += "<tr><td>" + g.messages["word_reply_to_c"] + "</td><td>" + reply_to.recipient_line	 + "</td></tr>";

    if (subject)
      table += "<tr><td>" + g.messages["word_subject_c"] + "</td><td>" + subject + "</td></tr>";

    if (email_info.html_content)
    {
      html_table = table + "<tr><td valign=\"top\">Content:</td><td>" + email_info.html_content + "</td></tr>";
      $("display_html_content").innerHTML = html_table;
      Effect.Appear("display_html");
    }
    else
    {
      $("display_html").hide();
      $("display_html_content").innerHTML = "";
    }

    if (email_info.text_content)
    {
      text_table = table + "<tr><td valign=\"top\">Content:</td><td>" + email_info.text_content.replace(/\n/g, "<br />") + "</td></tr>";
      $("display_text_content").innerHTML = text_table;
      Effect.Appear("display_text");
    }
    else
    {
      $("display_text").hide();
      $("display_text_content").innerHTML = "";
    }
  }
}


/**
 * Called after sending a test email. Displays the appropriate message.
 */
emails_ns.send_test_email_response = function(transport)
{
  emails_ns.log_activity(false);

  var json = transport.responseText.evalJSON();
  ft.display_message("ft_message", json.success, json.message);
}


/**
 * This is called whenever starting or ending any potentially lengthy JS operation. It hides/shows the
 * ajax loading icon.
 */
emails_ns.log_activity = function(is_busy)
{
  if (is_busy)
  {
    $("ajax_activity").show();
    $("ajax_no_activity").hide();
  }
  else
  {
    $("ajax_activity").hide();
    $("ajax_no_activity").show();
  }
}


emails_ns.delete_form_email_field_config = function(form_email_id)
{
  if (confirm(g.messages["confirm_delete_email_field_config"]))
  {
    window.location = "edit.php?page=email_settings&delete_form_email_id=" + form_email_id;
  }

  return false;
}
