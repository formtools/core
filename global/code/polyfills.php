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


if (!function_exists('http_response_code')) {
	function http_response_code($code = null)
	{
		static $defaultCode = 200;
		if (null != $code) {
			switch ($code) {
				case 100: $text = 'Continue'; break;                        // RFC2616
				case 101: $text = 'Switching Protocols'; break;             // RFC2616
				case 102: $text = 'Processing'; break;                      // RFC2518
				case 200: $text = 'OK'; break;                              // RFC2616
				case 201: $text = 'Created'; break;                         // RFC2616
				case 202: $text = 'Accepted'; break;                        // RFC2616
				case 203: $text = 'Non-Authoritative Information'; break;   // RFC2616
				case 204: $text = 'No Content'; break;                      // RFC2616
				case 205: $text = 'Reset Content'; break;                   // RFC2616
				case 206: $text = 'Partial Content'; break;                 // RFC2616
				case 207: $text = 'Multi-Status'; break;                    // RFC4918
				case 208: $text = 'Already Reported'; break;                // RFC5842
				case 226: $text = 'IM Used'; break;                         // RFC3229
				case 300: $text = 'Multiple Choices'; break;                // RFC2616
				case 301: $text = 'Moved Permanently'; break;               // RFC2616
				case 302: $text = 'Found'; break;                           // RFC2616
				case 303: $text = 'See Other'; break;                       // RFC2616
				case 304: $text = 'Not Modified'; break;                    // RFC2616
				case 305: $text = 'Use Proxy'; break;                       // RFC2616
				case 306: $text = 'Reserved'; break;                        // RFC2616
				case 307: $text = 'Temporary Redirect'; break;              // RFC2616
				case 308: $text = 'Permanent Redirect'; break;              // RFC-reschke-http-status-308-07
				case 400: $text = 'Bad Request'; break;                     // RFC2616
				case 401: $text = 'Unauthorized'; break;                    // RFC2616
				case 402: $text = 'Payment Required'; break;                // RFC2616
				case 403: $text = 'Forbidden'; break;                       // RFC2616
				case 404: $text = 'Not Found'; break;                       // RFC2616
				case 405: $text = 'Method Not Allowed'; break;              // RFC2616
				case 406: $text = 'Not Acceptable'; break;                  // RFC2616
				case 407: $text = 'Proxy Authentication Required'; break;   // RFC2616
				case 408: $text = 'Request Timeout'; break;                 // RFC2616
				case 409: $text = 'Conflict'; break;                        // RFC2616
				case 410: $text = 'Gone'; break;                            // RFC2616
				case 411: $text = 'Length Required'; break;                 // RFC2616
				case 412: $text = 'Precondition Failed'; break;             // RFC2616
				case 413: $text = 'Request Entity Too Large'; break;        // RFC2616
				case 414: $text = 'Request-URI Too Long'; break;            // RFC2616
				case 415: $text = 'Unsupported Media Type'; break;          // RFC2616
				case 416: $text = 'Requested Range Not Satisfiable'; break; // RFC2616
				case 417: $text = 'Expectation Failed'; break;              // RFC2616
				case 422: $text = 'Unprocessable Entity'; break;            // RFC4918
				case 423: $text = 'Locked'; break;                          // RFC4918
				case 424: $text = 'Failed Dependency'; break;               // RFC4918
				case 426: $text = 'Upgrade Required'; break;                // RFC2817
				case 428: $text = 'Precondition Required'; break;           // RFC6585
				case 429: $text = 'Too Many Requests'; break;               // RFC6585
				case 431: $text = 'Request Header Fields Too Large'; break; // RFC6585
				case 500: $text = 'Internal Server Error'; break;           // RFC2616
				case 501: $text = 'Not Implemented'; break;                 // RFC2616
				case 502: $text = 'Bad Gateway'; break;                     // RFC2616
				case 503: $text = 'Service Unavailable'; break;             // RFC2616
				case 504: $text = 'Gateway Timeout'; break;                 // RFC2616
				case 505: $text = 'HTTP Version Not Supported'; break;      // RFC2616
				case 506: $text = 'Variant Also Negotiates'; break;         // RFC2295
				case 507: $text = 'Insufficient Storage'; break;            // RFC4918
				case 508: $text = 'Loop Detected'; break;                   // RFC5842
				case 510: $text = 'Not Extended'; break;                    // RFC2774
				case 511: $text = 'Network Authentication Required'; break; // RFC6585

				default:
					$code = 500;
					$text = 'Internal Server Error';
			}
			$defaultCode = $code;
			$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
			header($protocol . ' ' . $code . ' ' . $text);
		}
		return $defaultCode;
	}
}