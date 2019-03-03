<?php

if (!function_exists("mb_strtoupper"))
{
    /**
     * A fallback function for servers that don't include the mbstring PHP extension. Note:
     * this function is NOT multi-byte; it can't be emulated without the extension. However,
     * this will at least allow the user to use Form Tools without errors.
     *
     * @param string $str
     * @return string the uppercased string
     */
    function mb_strtoupper($str)
    {
        return strtoupper($str);
    }
}


if (!function_exists("mb_strtoupper"))
{
    /**
     * A fallback function for servers that don't include the mbstring PHP extension. Note:
     * this function is NOT multi-byte; it can't be emulated without the extension. However,
     * this will at least allow the user to use Form Tools without errors.
     *
     * @param string $str
     * @return string the uppercased string
     */
    function mb_strtoupper($str)
    {
        return strtoupper($str);
    }
}

if (!function_exists("mb_strtolower"))
{
    /**
     * A fallback function for servers that don't include the mbstring PHP extension. Note:
     * this function is NOT multi-byte; it can't be emulated without the extension. However,
     * this will at least allow the user to use Form Tools without errors.
     *
     * @param string $str
     * @return string the uppercased string
     */
    function mb_strtolower($str)
    {
        return strtolower($str);
    }
}

if (!function_exists("mb_strlen"))
{
    /**
     * A fallback function for servers that don't include the mbstring PHP extension. Note:
     * this function is NOT multi-byte; it can't be emulated without the extension. However,
     * this will at least allow the user to use Form Tools without errors.
     *
     * @param string $str
     * @return string the length of the string
     */
    function mb_strlen($str)
    {
        return strlen($str);
    }
}

if (!function_exists("mb_substr"))
{
    /**
     * A fallback function for servers that don't include the mbstring PHP extension. Note:
     * this function is NOT multi-byte; it can't be emulated without the extension. However,
     * this will at least allow the user to use Form Tools without errors.
     *
     * @param string $str
     * @return string the length of the string
     */
    function mb_substr($str, $start, $length)
    {
        return substr($str, $start, $length);
    }
}

if (!function_exists("htmlspecialchars_decode"))
{
    function htmlspecialchars_decode($string, $style=ENT_COMPAT)
    {
        $translation = array_flip(get_html_translation_table(HTML_SPECIALCHARS, $style));
        if ($style === ENT_QUOTES)
            $translation['&#039;'] = '\'';

        return strtr($string, $translation);
    }
}

if (!function_exists('mime_content_type'))
{
    function mime_content_type($filename)
    {
        $mime_types = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $ext = strtolower(array_pop(explode('.', $filename)));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        } else {
            return 'application/octet-stream';
        }
    }
}
