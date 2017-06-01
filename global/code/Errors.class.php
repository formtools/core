<?php


class Errors
{
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
    -webkit-border-radius: 20px;
    -moz-border-radius: 20px;
    border-radius: 20px;
    border: 1px solid #666666;
    padding: 40px;
    background-color: white;
    width: 600px;
    text-align: left;
    margin: 30px auto;
    word-wrap: break-word;
  }
  </style>
</head>
<body>
<div class="error">
END;
    public static $footerHTML = "</div></body></html>";


    /**
     * This should be used on all database queries that we think should _always_ work. It completely halts the
     * process and shows an Uh-oh page. This was added in Form Tools 3. Before that, the vast majority of database
     * queries weren't error handled.
     */
    public static function handleDatabaseError($class, $file, $line, $error) {
        echo <<< END
  {self::$headerHTML}
  <h1>Uh-oh.</h1>
  <p>
    The system encountered a serious database error. This should not occur. Please report these details
  </p>
  <ul>
    <li>Class: {$class}</li>
    <li>File: {$file}</li>
    <li>Line: {$line}</li>
    <li>Error: {$error}</li>
  </ul>
  {$error}
  {self::$footerHTML}
END;

    }

    /**
     * This is used for major errors, especially when no database connection can be made. All it does is output
     * the error string with no other dependencies - not even language strings. This is always output in English.
     *
     * @param string $error
     */
    public static function displaySeriousError($error) {
        echo <<< END
  {self::$headerHTML}
  <h1>Uh-oh.</h1>
  {$error}
  {self::$footerHTML}
END;
    }

}
