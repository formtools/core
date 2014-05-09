// our namespace for the Manage Form functions and vars
var mf_ns = {};
mf_ns.num_multi_page_form_pages = null;
mf_ns.url_verification_overridden = false;


/**
 * This function is called in the Add Form process, and on the Edit Form -> main tab. It dynamically
 * adds rows to the "Form URLs" section, letting the user add as many page URLs as their form contains.
 */
mf_ns.add_multi_page_form_page = function(f)
{
  var tbody = $("multi_page_form_url_table").getElementsByTagName("tbody")[0];

  var currRow = ++mf_ns.num_multi_page_form_pages;

  var r = document.createElement("tr");
  r.setAttribute("id", "row_" + currRow);

  // [1] label
  var td1 = document.createElement("td");
  td1.setAttribute("width", "70");
  td1.className = "bold";
  td1.appendChild(document.createTextNode(g.messages["word_page"] + " " + currRow));

  // [2] URL text field
  var td2 = document.createElement("td");
  var inp = "<input type=\"text\" name=\"form_url_" + currRow + "\" id=\"form_url_" + currRow + "\" style=\"width:98%\" "
          + "onkeyup=\"mf_ns.unverify_url_field(this.value, '', " + currRow + ")\" />";
  td2.innerHTML = inp;

  // [3] Verify URL button
  var td3 = document.createElement("td");
  td3.setAttribute("width", "60");
  var inp = document.createElement("input");
  inp.setAttribute("type", "button");
  inp.setAttribute("value", g.messages["phrase_verify_url"]);
  inp.setAttribute("id", "form_url_" + currRow + "_button");
  $(inp).addClassName("light_grey");
  inp.onclick = ft.verify_url.bind(this, "form_url_" + currRow, currRow);
  td3.appendChild(inp);

  // add the table data cells to the row
  r.appendChild(td1);
  r.appendChild(td2);
  r.appendChild(td3);

  // add the row to the table
  tbody.appendChild(r);

  // update the page count
  $("num_pages_in_multi_page_form").value = currRow;

  // append a hidden field, marking this new field as NOT verified
  var inp = document.createElement("input");
  inp.setAttribute("type", "hidden");
  inp.setAttribute("id", "form_url_" + currRow + "_verified");
  inp.setAttribute("value", "no");
  f.appendChild(inp);
}


/**
 * Used on the Add Form step 2 page and on the edit form main tab. It's called whenever the user changes
 * the access type; hiding / showing the custom client list.
 */
mf_ns.toggle_access_type = function(access_type)
{
  switch (access_type)
  {
    case "admin":
      $("custom_clients").hide();
      break;
    case "public":
	    $("custom_clients").hide();
	    break;
    case "private":
	    $("custom_clients").show();
	    break;
	}
}


/**
 * Used on Add Form step 2 & Edit Form Main tab. This confirms all URLs entered by the user have been verified.
 * It works for the main form URL, additional form URLs for multi-page forms and the redirect URL.
 */
mf_ns.check_urls_verified = function()
{
  var errors = [];
  if ($("form_url_1_verified").value != "yes")
    errors.push([$("form_url"), g.messages["validation_urls_not_verified"]]);

  if ($("is_multi_page_form").checked)
  {
    var num_multi_page_forms = $("num_pages_in_multi_page_form").value;
    for (var i=2; i<=num_multi_page_forms; i++)
    {
      // ignore any of the multi page form rows that haven't been filled in
      if ($("form_url_" + i).value.strip() == "")
        continue;

		  if ($("form_url_" + i + "_verified").value != "yes")
		    errors.push([$("form_url_" + i), g.messages["validation_urls_not_verified"]]);
    }
  }

  // if the redirect URL has been supplied, ensure its been verified too
  var redirect_url = $("redirect_url").value.strip();
  if (redirect_url != "")
  {
	  if ($("form_url_redirect_verified").value != "yes")
	    errors.push([$("form_url_redirect_verified"), g.messages["validation_urls_not_verified"]]);
  }

  if (errors.length)
    return errors;

  return true;
}


mf_ns.bypass_url_verification = function()
{
  mf_ns._toggle_verification_button(1, "verified")
	$("form_url_1_verified").value = "yes";

  for (var i=2; i<=mf_ns.num_multi_page_form_pages; i++)
  {
    mf_ns._toggle_verification_button (i, "verified")
	  $("form_url_" + i + "_verified").value = "yes";
  }

  mf_ns._toggle_verification_button("redirect", "verified")
	$("form_url_redirect_verified").value = "yes";

  // resubmit the form
  rsv.validate($("add_form"), rules);

  return false;
}


/**
 * This is called whenever the content of a form field changes. It's used to unverify a field, requiring it to
 * be manually re-verified by the user. The function takes three parameters: ,
 *
 * @param string val the URL field value
 * @param string original_val the original URL field value (used when editing an existing URL field). This is
 *     empty for new field values
 * @param integer $form_page
 */
mf_ns.unverify_url_field = function(val, original_val, form_page)
{
  if (val.strip() == "")
    mf_ns._toggle_verification_button(form_page, "no_value");
  else
  {
    // if there was an original field value, see if this field is the SAME. If so, don't
    // unverify the field
    if (original_val.strip() != "")
    {
      if (val == original_val)
        mf_ns._toggle_verification_button(form_page, "verified");
      else
        mf_ns._toggle_verification_button(form_page, "not_verified");
    }
    else
      mf_ns._toggle_verification_button(form_page, "not_verified");
  }
}

mf_ns._toggle_verification_button = function(form_page, field_status)
{
  switch (field_status)
  {
    case "no_value":
	    $("form_url_" + form_page + "_verified").value = "no";
	    $("form_url_" + form_page + "_button").removeClassName("red");
	    $("form_url_" + form_page + "_button").removeClassName("green");
	    $("form_url_" + form_page + "_button").addClassName("light_grey");
	    $("form_url_" + form_page + "_button").value = g.messages["phrase_verify_url"];
		  break;
    case "not_verified":
      $("form_url_" + form_page + "_verified").value = "no";
	    $("form_url_" + form_page + "_button").removeClassName("light_grey");
	    $("form_url_" + form_page + "_button").removeClassName("green");
	    $("form_url_" + form_page + "_button").addClassName("red");
	    $("form_url_" + form_page + "_button").value = g.messages["phrase_verify_url"];
	    break;
	  case "verified":
	    $("form_url_" + form_page + "_verified").value = "yes";
			$("form_url_" + form_page + "_button").removeClassName("light_grey");
			$("form_url_" + form_page + "_button").removeClassName("red");
			$("form_url_" + form_page + "_button").addClassName("green");
		  $("form_url_" + form_page + "_button").value = g.messages["word_verified"];
	    break;
  }
}

/**
 * Hides/shows the additional URL fields to let the user enter ALL pages of a multi-page form.
 */
mf_ns.toggle_multi_page_form_fields = function(is_multi_page_form)
{
  if (is_multi_page_form)
  {
    if (mf_ns.num_multi_page_form_pages == 1)
      mf_ns.add_multi_page_form_page($("add_form"));

    $("multi_page_form_urls").show();
  }
  else
    $("multi_page_form_urls").hide();
}
