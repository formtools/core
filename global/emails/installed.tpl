{*
  installed_html.tpl
  ------------------

  This template is used to generate the "Congratulations" email for new installations. It's only
  sent in text format. Placeholders: $login_url, $username, $password
*}
{$LANG.text_ft_installed}

{$LANG.phrase_access_admin_account_c}
{$LANG.phrase_login_panel_c} {$login_url}
{$LANG.word_username_c} {$username}
{$LANG.word_password_c} {$password}

{$LANG.text_install_email_content_text}

{$LANG.phrase_have_fun}

- The Form Tools Team
http://www.formtools.org
