{*
  forget_password.tpl
  -------------------

  This template is used to generate the "forgot your password?" email. It's sent in text format only.

  These placeholders have special meaning:
    $login_url  - the login URL for this user (i.e. with the ?id=X appended to the query string)
    $username   - the username
    $password   - the newly generated password

  Note: the language strings ($LANG.-----} are all stored in your language file /global/lang/. If you
  change the contents of that file, bear in mind that any time you upgrade Form Tools those changes
  will be overwritten.
*}
{$LANG.text_login_info}

{$LANG.phrase_login_panel_c} {$login_url}
{$LANG.word_username_c} {$username}
{$LANG.word_password_c} {$new_password}