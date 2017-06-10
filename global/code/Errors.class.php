<?php

/**
 * This was added in 3.0.0 to standardize the error handling. It was a bit ad hoc before that.
 *
 * Type of errors:
 *
 *   UNKNOWN errors:
 *     - majorError()    - a DB connection couldn't be made, the database is invalid (missing tables, etc.) or something
 *                         is fundamentally wrong. Just outputs whatever string is needed.
 *     - queryError()    - a connection was made but a core query (i.e. a query that the app depends on) failed. Outputs
 *                         details about the error (class, file, line number & error message).
 *
 *   KNOWN errors:
 *     - showErrorCode() - a query failed for a known reason and we have an error code to help them out. This outputs
 *                         the error code details plus a link to the docs for people to get further help.
 *
 * Other (less important) errors are handled individually by the calling code. They can do whatever they need to display
 * the error without breaking the user's flow, like showing a message in the current page.
 */


// -------------------------------------------------------------------------------------------------


namespace FormTools;


class Errors
{

    // see: https://docs.formtools.org/api/error_codes/
    public static $CODES = array(
        "100" => 100,
        "101" => 101,
        // 102, 103, 104, 105 removed in Form Tools 3
        "106" => 106
    );

    public static $headerHTML =<<< END
<!DOCTYPE>
<html>
<head>
  <title>Error</title>
  <style type="text/css">
  h1 {
    margin: 0px 0px 16px 0px;
  }
  body {
    background-color: #f9f9f9;
    text-align: center;
    font-family: verdana;
    font-size: 11pt;
    line-height: 22px;
  }
  div {
    border-radius: 15px;
    border: 1px solid #999999;
    padding: 40px;
    background-color: white;
    width: 800px;
    text-align: left;
    margin: 30px auto;
    word-wrap: break-word;
  }
  </style>
</head>
<body>
<div>
END;
    public static $footerHTML = "</div></body></html>";


    /**
     * This should be used on all database queries that we think should _always_ work. It completely halts the
     * process and shows an Uh-oh page. This was added in Form Tools 3. Before that, the vast majority of database
     * queries weren't error handled.
     */
    public static function queryError($class, $file, $line, $error)
    {
        $header = self::$headerHTML;
        $footer = self::$footerHTML;

        echo <<< END
  {$header}
  <h1>Uh-oh.</h1>
  <p>
    The system encountered a serious database error. This should not occur. Please report these details
    in the Form Tools forums.
  </p>
  <ul>
    <li>Class: {$class}</li>
    <li>File: {$file}</li>
    <li>Line: {$line}</li>
    <li>Error: {$error}</li>
  </ul>
  {$footer}
END;
    }

    /**
     * This is used for major errors, like no DB connection. All it does is output the error string with no other
     * dependencies - not even language strings.
     *
     * @param string $error
     */
    public static function majorError($error) {
        $header = self::$headerHTML;
        $footer = self::$footerHTML;

        echo <<< END
  {$header}
  <h1>Uh-oh.</h1>
  {$error}
  {$footer}
END;
    }


    public static function showErrorCode($error_code, $debugging = "")
    {
        Themes::displayPage("error.tpl", array(
            "message_type" => "error",
            "error_code" => $error_code,
            "debugging" => $debugging
        ));
        exit;
    }

}
