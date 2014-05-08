<?php

/**
 * This file attempts to stabilize the PHP environment for Form Tools. It's called in the library.php
 * file for all server requests.
 *
 * Virtually all of this code was borrowed from Wordpress. See: http://wordpress.org/
 */

// -------------------------------------------------------------------------------------------------

// if allowed, try to crank up the memory limit
if (function_exists('memory_get_usage') && ((int)@ini_get('memory_limit') < 32))
  @ini_set('memory_limit', 32);

// try to fix empty PHP_SELF
if (empty($_SERVER['PHP_SELF']))
  $_SERVER['PHP_SELF'] = preg_replace("/(\?.*)?$/",'', $_SERVER["REQUEST_URI"]);


// try to fix REQUEST_URI for IIS
if (empty($_SERVER['REQUEST_URI']))
{
  // IIS Mod-Rewrite
  if (isset($_SERVER['HTTP_X_ORIGINAL_URL']))
    $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];

  // IIS Isapi_Rewrite
  else if (isset($_SERVER['HTTP_X_REWRITE_URL']))
    $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];

  else
  {
    // some IIS + PHP configurations puts the script-name in the path-info (no need to append it twice)
    if ( isset($_SERVER['PATH_INFO']) )
    {
      if ( $_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME'])
        $_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
      else
		$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
    }

    // append the query string if it exists and isn't null
    if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']))
      $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
  }
}

// fix for PHP as CGI hosts that set SCRIPT_FILENAME to something ending in php.cgi for all requests
if ( isset($_SERVER['SCRIPT_FILENAME']) && ( strpos($_SERVER['SCRIPT_FILENAME'], 'php.cgi') == strlen($_SERVER['SCRIPT_FILENAME']) - 7 ) )
	$_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];

// fix for PHP as CGI host
if (strpos($_SERVER['SCRIPT_NAME'], 'php.cgi') !== false)
	unset($_SERVER['PATH_INFO']);
