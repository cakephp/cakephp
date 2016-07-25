<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Network;

use Cake\Core\Configure;
use Cake\Filesystem\File;
use Cake\Network\Exception\NotFoundException;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Cake Response is responsible for managing the response text, status and headers of a HTTP response.
 *
 * By default controllers will use this class to render their response. If you are going to use
 * a custom response class it should subclass this object in order to ensure compatibility.
 *
 */
class Response
{

    /**
     * Holds HTTP response statuses
     *
     * @var array
     */
    protected $_statusCodes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'Unsupported Version'
    ];

    /**
     * Holds type key to mime type mappings for known mime types.
     *
     * @var array
     */
    protected $_mimeTypes = [
        'html' => ['text/html', '*/*'],
        'json' => 'application/json',
        'xml' => ['application/xml', 'text/xml'],
        'xhtml' => ['application/xhtml+xml', 'application/xhtml', 'text/xhtml'],
        'webp' => 'image/webp',
        'rss' => 'application/rss+xml',
        'ai' => 'application/postscript',
        'bcpio' => 'application/x-bcpio',
        'bin' => 'application/octet-stream',
        'ccad' => 'application/clariscad',
        'cdf' => 'application/x-netcdf',
        'class' => 'application/octet-stream',
        'cpio' => 'application/x-cpio',
        'cpt' => 'application/mac-compactpro',
        'csh' => 'application/x-csh',
        'csv' => ['text/csv', 'application/vnd.ms-excel'],
        'dcr' => 'application/x-director',
        'dir' => 'application/x-director',
        'dms' => 'application/octet-stream',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'drw' => 'application/drafting',
        'dvi' => 'application/x-dvi',
        'dwg' => 'application/acad',
        'dxf' => 'application/dxf',
        'dxr' => 'application/x-director',
        'eot' => 'application/vnd.ms-fontobject',
        'eps' => 'application/postscript',
        'exe' => 'application/octet-stream',
        'ez' => 'application/andrew-inset',
        'flv' => 'video/x-flv',
        'gtar' => 'application/x-gtar',
        'gz' => 'application/x-gzip',
        'bz2' => 'application/x-bzip',
        '7z' => 'application/x-7z-compressed',
        'hdf' => 'application/x-hdf',
        'hqx' => 'application/mac-binhex40',
        'ico' => 'image/x-icon',
        'ips' => 'application/x-ipscript',
        'ipx' => 'application/x-ipix',
        'js' => 'application/javascript',
        'jsonapi' => 'application/vnd.api+json',
        'latex' => 'application/x-latex',
        'lha' => 'application/octet-stream',
        'lsp' => 'application/x-lisp',
        'lzh' => 'application/octet-stream',
        'man' => 'application/x-troff-man',
        'me' => 'application/x-troff-me',
        'mif' => 'application/vnd.mif',
        'ms' => 'application/x-troff-ms',
        'nc' => 'application/x-netcdf',
        'oda' => 'application/oda',
        'otf' => 'font/otf',
        'pdf' => 'application/pdf',
        'pgn' => 'application/x-chess-pgn',
        'pot' => 'application/vnd.ms-powerpoint',
        'pps' => 'application/vnd.ms-powerpoint',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'ppz' => 'application/vnd.ms-powerpoint',
        'pre' => 'application/x-freelance',
        'prt' => 'application/pro_eng',
        'ps' => 'application/postscript',
        'roff' => 'application/x-troff',
        'scm' => 'application/x-lotusscreencam',
        'set' => 'application/set',
        'sh' => 'application/x-sh',
        'shar' => 'application/x-shar',
        'sit' => 'application/x-stuffit',
        'skd' => 'application/x-koan',
        'skm' => 'application/x-koan',
        'skp' => 'application/x-koan',
        'skt' => 'application/x-koan',
        'smi' => 'application/smil',
        'smil' => 'application/smil',
        'sol' => 'application/solids',
        'spl' => 'application/x-futuresplash',
        'src' => 'application/x-wais-source',
        'step' => 'application/STEP',
        'stl' => 'application/SLA',
        'stp' => 'application/STEP',
        'sv4cpio' => 'application/x-sv4cpio',
        'sv4crc' => 'application/x-sv4crc',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        'swf' => 'application/x-shockwave-flash',
        't' => 'application/x-troff',
        'tar' => 'application/x-tar',
        'tcl' => 'application/x-tcl',
        'tex' => 'application/x-tex',
        'texi' => 'application/x-texinfo',
        'texinfo' => 'application/x-texinfo',
        'tr' => 'application/x-troff',
        'tsp' => 'application/dsptype',
        'ttc' => 'font/ttf',
        'ttf' => 'font/ttf',
        'unv' => 'application/i-deas',
        'ustar' => 'application/x-ustar',
        'vcd' => 'application/x-cdlink',
        'vda' => 'application/vda',
        'xlc' => 'application/vnd.ms-excel',
        'xll' => 'application/vnd.ms-excel',
        'xlm' => 'application/vnd.ms-excel',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xlw' => 'application/vnd.ms-excel',
        'zip' => 'application/zip',
        'aif' => 'audio/x-aiff',
        'aifc' => 'audio/x-aiff',
        'aiff' => 'audio/x-aiff',
        'au' => 'audio/basic',
        'kar' => 'audio/midi',
        'mid' => 'audio/midi',
        'midi' => 'audio/midi',
        'mp2' => 'audio/mpeg',
        'mp3' => 'audio/mpeg',
        'mpga' => 'audio/mpeg',
        'ogg' => 'audio/ogg',
        'oga' => 'audio/ogg',
        'spx' => 'audio/ogg',
        'ra' => 'audio/x-realaudio',
        'ram' => 'audio/x-pn-realaudio',
        'rm' => 'audio/x-pn-realaudio',
        'rpm' => 'audio/x-pn-realaudio-plugin',
        'snd' => 'audio/basic',
        'tsi' => 'audio/TSP-audio',
        'wav' => 'audio/x-wav',
        'aac' => 'audio/aac',
        'asc' => 'text/plain',
        'c' => 'text/plain',
        'cc' => 'text/plain',
        'css' => 'text/css',
        'etx' => 'text/x-setext',
        'f' => 'text/plain',
        'f90' => 'text/plain',
        'h' => 'text/plain',
        'hh' => 'text/plain',
        'htm' => ['text/html', '*/*'],
        'ics' => 'text/calendar',
        'm' => 'text/plain',
        'rtf' => 'text/rtf',
        'rtx' => 'text/richtext',
        'sgm' => 'text/sgml',
        'sgml' => 'text/sgml',
        'tsv' => 'text/tab-separated-values',
        'tpl' => 'text/template',
        'txt' => 'text/plain',
        'text' => 'text/plain',
        'avi' => 'video/x-msvideo',
        'fli' => 'video/x-fli',
        'mov' => 'video/quicktime',
        'movie' => 'video/x-sgi-movie',
        'mpe' => 'video/mpeg',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'qt' => 'video/quicktime',
        'viv' => 'video/vnd.vivo',
        'vivo' => 'video/vnd.vivo',
        'ogv' => 'video/ogg',
        'webm' => 'video/webm',
        'mp4' => 'video/mp4',
        'm4v' => 'video/mp4',
        'f4v' => 'video/mp4',
        'f4p' => 'video/mp4',
        'm4a' => 'audio/mp4',
        'f4a' => 'audio/mp4',
        'f4b' => 'audio/mp4',
        'gif' => 'image/gif',
        'ief' => 'image/ief',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'pbm' => 'image/x-portable-bitmap',
        'pgm' => 'image/x-portable-graymap',
        'png' => 'image/png',
        'pnm' => 'image/x-portable-anymap',
        'ppm' => 'image/x-portable-pixmap',
        'ras' => 'image/cmu-raster',
        'rgb' => 'image/x-rgb',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'xbm' => 'image/x-xbitmap',
        'xpm' => 'image/x-xpixmap',
        'xwd' => 'image/x-xwindowdump',
        'ice' => 'x-conference/x-cooltalk',
        'iges' => 'model/iges',
        'igs' => 'model/iges',
        'mesh' => 'model/mesh',
        'msh' => 'model/mesh',
        'silo' => 'model/mesh',
        'vrml' => 'model/vrml',
        'wrl' => 'model/vrml',
        'mime' => 'www/mime',
        'pdb' => 'chemical/x-pdb',
        'xyz' => 'chemical/x-pdb',
        'javascript' => 'application/javascript',
        'form' => 'application/x-www-form-urlencoded',
        'file' => 'multipart/form-data',
        'xhtml-mobile' => 'application/vnd.wap.xhtml+xml',
        'atom' => 'application/atom+xml',
        'amf' => 'application/x-amf',
        'wap' => ['text/vnd.wap.wml', 'text/vnd.wap.wmlscript', 'image/vnd.wap.wbmp'],
        'wml' => 'text/vnd.wap.wml',
        'wmlscript' => 'text/vnd.wap.wmlscript',
        'wbmp' => 'image/vnd.wap.wbmp',
        'woff' => 'application/x-font-woff',
        'appcache' => 'text/cache-manifest',
        'manifest' => 'text/cache-manifest',
        'htc' => 'text/x-component',
        'rdf' => 'application/xml',
        'crx' => 'application/x-chrome-extension',
        'oex' => 'application/x-opera-extension',
        'xpi' => 'application/x-xpinstall',
        'safariextz' => 'application/octet-stream',
        'webapp' => 'application/x-web-app-manifest+json',
        'vcf' => 'text/x-vcard',
        'vtt' => 'text/vtt',
        'mkv' => 'video/x-matroska',
        'pkpass' => 'application/vnd.apple.pkpass',
        'ajax' => 'text/html'
    ];

    /**
     * Protocol header to send to the client
     *
     * @var string
     */
    protected $_protocol = 'HTTP/1.1';

    /**
     * Status code to send to the client
     *
     * @var int
     */
    protected $_status = 200;

    /**
     * Content type to send. This can be an 'extension' that will be transformed using the $_mimetypes array
     * or a complete mime-type
     *
     * @var string
     */
    protected $_contentType = 'text/html';

    /**
     * Buffer list of headers
     *
     * @var array
     */
    protected $_headers = [];

    /**
     * Buffer string or callable for response message
     *
     * @var string|callable
     */
    protected $_body = null;

    /**
     * File object for file to be read out as response
     *
     * @var \Cake\Filesystem\File
     */
    protected $_file = null;

    /**
     * File range. Used for requesting ranges of files.
     *
     * @var array
     */
    protected $_fileRange = [];

    /**
     * The charset the response body is encoded with
     *
     * @var string
     */
    protected $_charset = 'UTF-8';

    /**
     * Holds all the cache directives that will be converted
     * into headers when sending the request
     *
     * @var array
     */
    protected $_cacheDirectives = [];

    /**
     * Holds cookies to be sent to the client
     *
     * @var array
     */
    protected $_cookies = [];

    /**
     * Constructor
     *
     * @param array $options list of parameters to setup the response. Possible values are:
     *  - body: the response text that should be sent to the client
     *  - statusCodes: additional allowable response codes
     *  - status: the HTTP status code to respond with
     *  - type: a complete mime-type string or an extension mapped in this class
     *  - charset: the charset for the response body
     */
    public function __construct(array $options = [])
    {
        if (isset($options['body'])) {
            $this->body($options['body']);
        }
        if (isset($options['statusCodes'])) {
            $this->httpCodes($options['statusCodes']);
        }
        if (isset($options['status'])) {
            $this->statusCode($options['status']);
        }
        if (isset($options['type'])) {
            $this->type($options['type']);
        }
        if (!isset($options['charset'])) {
            $options['charset'] = Configure::read('App.encoding');
        }
        $this->charset($options['charset']);
    }

    /**
     * Sends the complete response to the client including headers and message body.
     * Will echo out the content in the response body.
     *
     * @return void
     */
    public function send()
    {
        if (isset($this->_headers['Location']) && $this->_status === 200) {
            $this->statusCode(302);
        }

        $this->_setContent();
        $this->sendHeaders();

        if ($this->_file) {
            $this->_sendFile($this->_file, $this->_fileRange);
            $this->_file = $this->_fileRange = null;
        } else {
            $this->_sendContent($this->_body);
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * Sends the HTTP headers and cookies.
     *
     * @return void
     */
    public function sendHeaders()
    {
        if (headers_sent()) {
            return;
        }

        $codeMessage = $this->_statusCodes[$this->_status];
        $this->_setCookies();
        $this->_sendHeader("{$this->_protocol} {$this->_status} {$codeMessage}");
        $this->_setContentType();

        foreach ($this->_headers as $header => $values) {
            foreach ((array)$values as $value) {
                $this->_sendHeader($header, $value);
            }
        }
    }

    /**
     * Sets the cookies that have been added via Cake\Network\Response::cookie() before any
     * other output is sent to the client. Will set the cookies in the order they
     * have been set.
     *
     * @return void
     */
    protected function _setCookies()
    {
        foreach ($this->_cookies as $name => $c) {
            setcookie(
                $name,
                $c['value'],
                $c['expire'],
                $c['path'],
                $c['domain'],
                $c['secure'],
                $c['httpOnly']
            );
        }
    }

    /**
     * Formats the Content-Type header based on the configured contentType and charset
     * the charset will only be set in the header if the response is of type text/*
     *
     * @return void
     */
    protected function _setContentType()
    {
        if (in_array($this->_status, [304, 204])) {
            return;
        }
        $whitelist = [
            'application/javascript', 'application/json', 'application/xml', 'application/rss+xml'
        ];

        $charset = false;
        if ($this->_charset &&
            (strpos($this->_contentType, 'text/') === 0 || in_array($this->_contentType, $whitelist))
        ) {
            $charset = true;
        }

        if ($charset) {
            $this->header('Content-Type', "{$this->_contentType}; charset={$this->_charset}");
        } else {
            $this->header('Content-Type', "{$this->_contentType}");
        }
    }

    /**
     * Sets the response body to an empty text if the status code is 204 or 304
     *
     * @return void
     */
    protected function _setContent()
    {
        if (in_array($this->_status, [304, 204])) {
            $this->body('');
        }
    }

    /**
     * Sends a header to the client.
     *
     * @param string $name the header name
     * @param string|null $value the header value
     * @return void
     */
    protected function _sendHeader($name, $value = null)
    {
        if ($value === null) {
            header($name);
        } else {
            header("{$name}: {$value}");
        }
    }

    /**
     * Sends a content string to the client.
     *
     * If the content is a callable, it is invoked. The callable should either
     * return a string or output content directly and have no return value.
     *
     * @param string|callable $content String to send as response body or callable
     *  which returns/outputs content.
     * @return void
     */
    protected function _sendContent($content)
    {
        if (!is_string($content) && is_callable($content)) {
            $content = $content();
        }

        echo $content;
    }

    /**
     * Buffers a header string to be sent
     * Returns the complete list of buffered headers
     *
     * ### Single header
     * ```
     * header('Location', 'http://example.com');
     * ```
     *
     * ### Multiple headers
     * ```
     * header(['Location' => 'http://example.com', 'X-Extra' => 'My header']);
     * ```
     *
     * ### String header
     * ```
     * header('WWW-Authenticate: Negotiate');
     * ```
     *
     * ### Array of string headers
     * ```
     * header(['WWW-Authenticate: Negotiate', 'Content-type: application/pdf']);
     * ```
     *
     * Multiple calls for setting the same header name will have the same effect as setting the header once
     * with the last value sent for it
     * ```
     * header('WWW-Authenticate: Negotiate');
     * header('WWW-Authenticate: Not-Negotiate');
     * ```
     * will have the same effect as only doing
     * ```
     * header('WWW-Authenticate: Not-Negotiate');
     * ```
     *
     * @param string|array|null $header An array of header strings or a single header string
     *  - an associative array of "header name" => "header value" is also accepted
     *  - an array of string headers is also accepted
     * @param string|array|null $value The header value(s)
     * @return array List of headers to be sent
     */
    public function header($header = null, $value = null)
    {
        if ($header === null) {
            return $this->_headers;
        }
        $headers = is_array($header) ? $header : [$header => $value];
        foreach ($headers as $header => $value) {
            if (is_numeric($header)) {
                list($header, $value) = [$value, null];
            }
            if ($value === null) {
                list($header, $value) = explode(':', $header, 2);
            }
            $this->_headers[$header] = is_array($value) ? array_map('trim', $value) : trim($value);
        }

        return $this->_headers;
    }

    /**
     * Accessor for the location header.
     *
     * Get/Set the Location header value.
     *
     * @param null|string $url Either null to get the current location, or a string to set one.
     * @return string|null When setting the location null will be returned. When reading the location
     *    a string of the current location header value (if any) will be returned.
     */
    public function location($url = null)
    {
        if ($url === null) {
            $headers = $this->header();

            return isset($headers['Location']) ? $headers['Location'] : null;
        }
        $this->header('Location', $url);

        return null;
    }

    /**
     * Buffers the response message to be sent
     * if $content is null the current buffer is returned
     *
     * @param string|callable|null $content the string or callable message to be sent
     * @return string Current message buffer if $content param is passed as null
     */
    public function body($content = null)
    {
        if ($content === null) {
            return $this->_body;
        }

        return $this->_body = $content;
    }

    /**
     * Sets the HTTP status code to be sent
     * if $code is null the current code is returned
     *
     * @param int|null $code the HTTP status code
     * @return int Current status code
     * @throws \InvalidArgumentException When an unknown status code is reached.
     */
    public function statusCode($code = null)
    {
        if ($code === null) {
            return $this->_status;
        }
        if (!isset($this->_statusCodes[$code])) {
            throw new InvalidArgumentException('Unknown status code');
        }

        return $this->_status = $code;
    }

    /**
     * Queries & sets valid HTTP response codes & messages.
     *
     * @param int|array|null $code If $code is an integer, then the corresponding code/message is
     *        returned if it exists, null if it does not exist. If $code is an array, then the
     *        keys are used as codes and the values as messages to add to the default HTTP
     *        codes. The codes must be integers greater than 99 and less than 1000. Keep in
     *        mind that the HTTP specification outlines that status codes begin with a digit
     *        between 1 and 5, which defines the class of response the client is to expect.
     *        Example:
     *
     *        httpCodes(404); // returns [404 => 'Not Found']
     *
     *        httpCodes([
     *            381 => 'Unicorn Moved',
     *            555 => 'Unexpected Minotaur'
     *        ]); // sets these new values, and returns true
     *
     *        httpCodes([
     *            0 => 'Nothing Here',
     *            -1 => 'Reverse Infinity',
     *            12345 => 'Universal Password',
     *            'Hello' => 'World'
     *        ]); // throws an exception due to invalid codes
     *
     *        For more on HTTP status codes see: http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html#sec6.1
     *
     * @return mixed Associative array of the HTTP codes as keys, and the message
     *    strings as values, or null of the given $code does not exist.
     * @throws \InvalidArgumentException If an attempt is made to add an invalid status code
     */
    public function httpCodes($code = null)
    {
        if (empty($code)) {
            return $this->_statusCodes;
        }
        if (is_array($code)) {
            $codes = array_keys($code);
            $min = min($codes);
            if (!is_int($min) || $min < 100 || max($codes) > 999) {
                throw new InvalidArgumentException('Invalid status code');
            }
            $this->_statusCodes = $code + $this->_statusCodes;

            return true;
        }
        if (!isset($this->_statusCodes[$code])) {
            return null;
        }

        return [$code => $this->_statusCodes[$code]];
    }

    /**
     * Sets the response content type. It can be either a file extension
     * which will be mapped internally to a mime-type or a string representing a mime-type
     * if $contentType is null the current content type is returned
     * if $contentType is an associative array, content type definitions will be stored/replaced
     *
     * ### Setting the content type
     *
     * ```
     * type('jpg');
     * ```
     *
     * ### Returning the current content type
     *
     * ```
     * type();
     * ```
     *
     * ### Storing content type definitions
     *
     * ```
     * type(['keynote' => 'application/keynote', 'bat' => 'application/bat']);
     * ```
     *
     * ### Replacing a content type definition
     *
     * ```
     * type(['jpg' => 'text/plain']);
     * ```
     *
     * @param string|null $contentType Content type key.
     * @return mixed Current content type or false if supplied an invalid content type
     */
    public function type($contentType = null)
    {
        if ($contentType === null) {
            return $this->_contentType;
        }
        if (is_array($contentType)) {
            foreach ($contentType as $type => $definition) {
                $this->_mimeTypes[$type] = $definition;
            }

            return $this->_contentType;
        }
        if (isset($this->_mimeTypes[$contentType])) {
            $contentType = $this->_mimeTypes[$contentType];
            $contentType = is_array($contentType) ? current($contentType) : $contentType;
        }
        if (strpos($contentType, '/') === false) {
            return false;
        }

        return $this->_contentType = $contentType;
    }

    /**
     * Returns the mime type definition for an alias
     *
     * e.g `getMimeType('pdf'); // returns 'application/pdf'`
     *
     * @param string $alias the content type alias to map
     * @return mixed String mapped mime type or false if $alias is not mapped
     */
    public function getMimeType($alias)
    {
        if (isset($this->_mimeTypes[$alias])) {
            return $this->_mimeTypes[$alias];
        }

        return false;
    }

    /**
     * Maps a content-type back to an alias
     *
     * e.g `mapType('application/pdf'); // returns 'pdf'`
     *
     * @param string|array $ctype Either a string content type to map, or an array of types.
     * @return string|array|null Aliases for the types provided.
     */
    public function mapType($ctype)
    {
        if (is_array($ctype)) {
            return array_map([$this, 'mapType'], $ctype);
        }

        foreach ($this->_mimeTypes as $alias => $types) {
            if (in_array($ctype, (array)$types)) {
                return $alias;
            }
        }

        return null;
    }

    /**
     * Sets the response charset
     * if $charset is null the current charset is returned
     *
     * @param string|null $charset Character set string.
     * @return string Current charset
     */
    public function charset($charset = null)
    {
        if ($charset === null) {
            return $this->_charset;
        }

        return $this->_charset = $charset;
    }

    /**
     * Sets the correct headers to instruct the client to not cache the response
     *
     * @return void
     */
    public function disableCache()
    {
        $this->header([
            'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
            'Last-Modified' => gmdate("D, d M Y H:i:s") . " GMT",
            'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0'
        ]);
    }

    /**
     * Sets the correct headers to instruct the client to cache the response.
     *
     * @param string $since a valid time since the response text has not been modified
     * @param string $time a valid time for cache expiry
     * @return void
     */
    public function cache($since, $time = '+1 day')
    {
        if (!is_int($time)) {
            $time = strtotime($time);
        }
        $this->header([
            'Date' => gmdate("D, j M Y G:i:s ", time()) . 'GMT'
        ]);
        $this->modified($since);
        $this->expires($time);
        $this->sharable(true);
        $this->maxAge($time - time());
    }

    /**
     * Sets whether a response is eligible to be cached by intermediate proxies
     * This method controls the `public` or `private` directive in the Cache-Control
     * header
     *
     * @param bool|null $public If set to true, the Cache-Control header will be set as public
     *   if set to false, the response will be set to private
     *   if no value is provided, it will return whether the response is sharable or not
     * @param int|null $time time in seconds after which the response should no longer be considered fresh
     * @return bool|null
     */
    public function sharable($public = null, $time = null)
    {
        if ($public === null) {
            $public = array_key_exists('public', $this->_cacheDirectives);
            $private = array_key_exists('private', $this->_cacheDirectives);
            $noCache = array_key_exists('no-cache', $this->_cacheDirectives);
            if (!$public && !$private && !$noCache) {
                return null;
            }
            $sharable = $public || !($private || $noCache);

            return $sharable;
        }
        if ($public) {
            $this->_cacheDirectives['public'] = true;
            unset($this->_cacheDirectives['private']);
        } else {
            $this->_cacheDirectives['private'] = true;
            unset($this->_cacheDirectives['public']);
        }

        $this->maxAge($time);
        if (!$time) {
            $this->_setCacheControl();
        }

        return (bool)$public;
    }

    /**
     * Sets the Cache-Control s-maxage directive.
     * The max-age is the number of seconds after which the response should no longer be considered
     * a good candidate to be fetched from a shared cache (like in a proxy server).
     * If called with no parameters, this function will return the current max-age value if any
     *
     * @param int|null $seconds if null, the method will return the current s-maxage value
     * @return int|null
     */
    public function sharedMaxAge($seconds = null)
    {
        if ($seconds !== null) {
            $this->_cacheDirectives['s-maxage'] = $seconds;
            $this->_setCacheControl();
        }
        if (isset($this->_cacheDirectives['s-maxage'])) {
            return $this->_cacheDirectives['s-maxage'];
        }

        return null;
    }

    /**
     * Sets the Cache-Control max-age directive.
     * The max-age is the number of seconds after which the response should no longer be considered
     * a good candidate to be fetched from the local (client) cache.
     * If called with no parameters, this function will return the current max-age value if any
     *
     * @param int|null $seconds if null, the method will return the current max-age value
     * @return int|null
     */
    public function maxAge($seconds = null)
    {
        if ($seconds !== null) {
            $this->_cacheDirectives['max-age'] = $seconds;
            $this->_setCacheControl();
        }
        if (isset($this->_cacheDirectives['max-age'])) {
            return $this->_cacheDirectives['max-age'];
        }

        return null;
    }

    /**
     * Sets the Cache-Control must-revalidate directive.
     * must-revalidate indicates that the response should not be served
     * stale by a cache under any circumstance without first revalidating
     * with the origin.
     * If called with no parameters, this function will return whether must-revalidate is present.
     *
     * @param bool|null $enable if null, the method will return the current
     *   must-revalidate value. If boolean sets or unsets the directive.
     * @return bool
     */
    public function mustRevalidate($enable = null)
    {
        if ($enable !== null) {
            if ($enable) {
                $this->_cacheDirectives['must-revalidate'] = true;
            } else {
                unset($this->_cacheDirectives['must-revalidate']);
            }
            $this->_setCacheControl();
        }

        return array_key_exists('must-revalidate', $this->_cacheDirectives);
    }

    /**
     * Helper method to generate a valid Cache-Control header from the options set
     * in other methods
     *
     * @return void
     */
    protected function _setCacheControl()
    {
        $control = '';
        foreach ($this->_cacheDirectives as $key => $val) {
            $control .= $val === true ? $key : sprintf('%s=%s', $key, $val);
            $control .= ', ';
        }
        $control = rtrim($control, ', ');
        $this->header('Cache-Control', $control);
    }

    /**
     * Sets the Expires header for the response by taking an expiration time
     * If called with no parameters it will return the current Expires value
     *
     * ### Examples:
     *
     * `$response->expires('now')` Will Expire the response cache now
     * `$response->expires(new DateTime('+1 day'))` Will set the expiration in next 24 hours
     * `$response->expires()` Will return the current expiration header value
     *
     * @param string|\DateTime|null $time Valid time string or \DateTime instance.
     * @return string|null
     */
    public function expires($time = null)
    {
        if ($time !== null) {
            $date = $this->_getUTCDate($time);
            $this->_headers['Expires'] = $date->format('D, j M Y H:i:s') . ' GMT';
        }
        if (isset($this->_headers['Expires'])) {
            return $this->_headers['Expires'];
        }

        return null;
    }

    /**
     * Sets the Last-Modified header for the response by taking a modification time
     * If called with no parameters it will return the current Last-Modified value
     *
     * ### Examples:
     *
     * `$response->modified('now')` Will set the Last-Modified to the current time
     * `$response->modified(new DateTime('+1 day'))` Will set the modification date in the past 24 hours
     * `$response->modified()` Will return the current Last-Modified header value
     *
     * @param string|\DateTime|null $time Valid time string or \DateTime instance.
     * @return string|null
     */
    public function modified($time = null)
    {
        if ($time !== null) {
            $date = $this->_getUTCDate($time);
            $this->_headers['Last-Modified'] = $date->format('D, j M Y H:i:s') . ' GMT';
        }
        if (isset($this->_headers['Last-Modified'])) {
            return $this->_headers['Last-Modified'];
        }

        return null;
    }

    /**
     * Sets the response as Not Modified by removing any body contents
     * setting the status code to "304 Not Modified" and removing all
     * conflicting headers
     *
     * @return void
     */
    public function notModified()
    {
        $this->statusCode(304);
        $this->body('');
        $remove = [
            'Allow',
            'Content-Encoding',
            'Content-Language',
            'Content-Length',
            'Content-MD5',
            'Content-Type',
            'Last-Modified'
        ];
        foreach ($remove as $header) {
            unset($this->_headers[$header]);
        }
    }

    /**
     * Sets the Vary header for the response, if an array is passed,
     * values will be imploded into a comma separated string. If no
     * parameters are passed, then an array with the current Vary header
     * value is returned
     *
     * @param string|array|null $cacheVariances A single Vary string or an array
     *   containing the list for variances.
     * @return array|null
     */
    public function vary($cacheVariances = null)
    {
        if ($cacheVariances !== null) {
            $cacheVariances = (array)$cacheVariances;
            $this->_headers['Vary'] = implode(', ', $cacheVariances);
        }
        if (isset($this->_headers['Vary'])) {
            return explode(', ', $this->_headers['Vary']);
        }

        return null;
    }

    /**
     * Sets the response Etag, Etags are a strong indicative that a response
     * can be cached by a HTTP client. A bad way of generating Etags is
     * creating a hash of the response output, instead generate a unique
     * hash of the unique components that identifies a request, such as a
     * modification time, a resource Id, and anything else you consider it
     * makes it unique.
     *
     * Second parameter is used to instruct clients that the content has
     * changed, but semantically, it can be used as the same thing. Think
     * for instance of a page with a hit counter, two different page views
     * are equivalent, but they differ by a few bytes. This leaves off to
     * the Client the decision of using or not the cached page.
     *
     * If no parameters are passed, current Etag header is returned.
     *
     * @param string|null $hash The unique hash that identifies this response
     * @param bool $weak Whether the response is semantically the same as
     *   other with the same hash or not
     * @return string|null
     */
    public function etag($hash = null, $weak = false)
    {
        if ($hash !== null) {
            $this->_headers['Etag'] = sprintf('%s"%s"', ($weak) ? 'W/' : null, $hash);
        }
        if (isset($this->_headers['Etag'])) {
            return $this->_headers['Etag'];
        }

        return null;
    }

    /**
     * Returns a DateTime object initialized at the $time param and using UTC
     * as timezone
     *
     * @param string|int|\DateTime|null $time Valid time string or \DateTime instance.
     * @return \DateTime
     */
    protected function _getUTCDate($time = null)
    {
        if ($time instanceof DateTime) {
            $result = clone $time;
        } elseif (is_int($time)) {
            $result = new DateTime(date('Y-m-d H:i:s', $time));
        } else {
            $result = new DateTime($time);
        }
        $result->setTimeZone(new DateTimeZone('UTC'));

        return $result;
    }

    /**
     * Sets the correct output buffering handler to send a compressed response. Responses will
     * be compressed with zlib, if the extension is available.
     *
     * @return bool false if client does not accept compressed responses or no handler is available, true otherwise
     */
    public function compress()
    {
        $compressionEnabled = ini_get("zlib.output_compression") !== '1' &&
            extension_loaded("zlib") &&
            (strpos(env('HTTP_ACCEPT_ENCODING'), 'gzip') !== false);

        return $compressionEnabled && ob_start('ob_gzhandler');
    }

    /**
     * Returns whether the resulting output will be compressed by PHP
     *
     * @return bool
     */
    public function outputCompressed()
    {
        return strpos(env('HTTP_ACCEPT_ENCODING'), 'gzip') !== false
            && (ini_get("zlib.output_compression") === '1' || in_array('ob_gzhandler', ob_list_handlers()));
    }

    /**
     * Sets the correct headers to instruct the browser to download the response as a file.
     *
     * @param string $filename The name of the file as the browser will download the response
     * @return void
     */
    public function download($filename)
    {
        $this->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Sets the protocol to be used when sending the response. Defaults to HTTP/1.1
     * If called with no arguments, it will return the current configured protocol
     *
     * @param string|null $protocol Protocol to be used for sending response.
     * @return string Protocol currently set
     */
    public function protocol($protocol = null)
    {
        if ($protocol !== null) {
            $this->_protocol = $protocol;
        }

        return $this->_protocol;
    }

    /**
     * Sets the Content-Length header for the response
     * If called with no arguments returns the last Content-Length set
     *
     * @param int|null $bytes Number of bytes
     * @return int|null
     */
    public function length($bytes = null)
    {
        if ($bytes !== null) {
            $this->_headers['Content-Length'] = $bytes;
        }
        if (isset($this->_headers['Content-Length'])) {
            return $this->_headers['Content-Length'];
        }

        return null;
    }

    /**
     * Checks whether a response has not been modified according to the 'If-None-Match'
     * (Etags) and 'If-Modified-Since' (last modification date) request
     * headers. If the response is detected to be not modified, it
     * is marked as so accordingly so the client can be informed of that.
     *
     * In order to mark a response as not modified, you need to set at least
     * the Last-Modified etag response header before calling this method. Otherwise
     * a comparison will not be possible.
     *
     * @param \Cake\Network\Request $request Request object
     * @return bool Whether the response was marked as not modified or not.
     */
    public function checkNotModified(Request $request)
    {
        $etags = preg_split('/\s*,\s*/', $request->header('If-None-Match'), null, PREG_SPLIT_NO_EMPTY);
        $modifiedSince = $request->header('If-Modified-Since');
        if ($responseTag = $this->etag()) {
            $etagMatches = in_array('*', $etags) || in_array($responseTag, $etags);
        }
        if ($modifiedSince) {
            $timeMatches = strtotime($this->modified()) === strtotime($modifiedSince);
        }
        $checks = compact('etagMatches', 'timeMatches');
        if (empty($checks)) {
            return false;
        }
        $notModified = !in_array(false, $checks, true);
        if ($notModified) {
            $this->notModified();
        }

        return $notModified;
    }

    /**
     * String conversion. Fetches the response body as a string.
     * Does *not* send headers.
     * If body is a callable, a blank string is returned.
     *
     * @return string
     */
    public function __toString()
    {
        if (!is_string($this->_body) && is_callable($this->_body)) {
            return '';
        }

        return (string)$this->_body;
    }

    /**
     * Getter/Setter for cookie configs
     *
     * This method acts as a setter/getter depending on the type of the argument.
     * If the method is called with no arguments, it returns all configurations.
     *
     * If the method is called with a string as argument, it returns either the
     * given configuration if it is set, or null, if it's not set.
     *
     * If the method is called with an array as argument, it will set the cookie
     * configuration to the cookie container.
     *
     *  ### Options (when setting a configuration)
     *  - name: The Cookie name
     *  - value: Value of the cookie
     *  - expire: Time the cookie expires in
     *  - path: Path the cookie applies to
     *  - domain: Domain the cookie is for.
     *  - secure: Is the cookie https?
     *  - httpOnly: Is the cookie available in the client?
     *
     * ### Examples
     *
     * ### Getting all cookies
     *
     * `$this->cookie()`
     *
     * ### Getting a certain cookie configuration
     *
     * `$this->cookie('MyCookie')`
     *
     * ### Setting a cookie configuration
     *
     * `$this->cookie((array) $options)`
     *
     * @param array|null $options Either null to get all cookies, string for a specific cookie
     *  or array to set cookie.
     * @return mixed
     */
    public function cookie($options = null)
    {
        if ($options === null) {
            return $this->_cookies;
        }

        if (is_string($options)) {
            if (!isset($this->_cookies[$options])) {
                return null;
            }

            return $this->_cookies[$options];
        }

        $defaults = [
            'name' => 'CakeCookie[default]',
            'value' => '',
            'expire' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => false
        ];
        $options += $defaults;

        $this->_cookies[$options['name']] = $options;
    }

    /**
     * Setup access for origin and methods on cross origin requests
     *
     * This method allow multiple ways to setup the domains, see the examples
     *
     * ### Full URI
     * ```
     * cors($request, 'http://www.cakephp.org');
     * ```
     *
     * ### URI with wildcard
     * ```
     * cors($request, 'http://*.cakephp.org');
     * ```
     *
     * ### Ignoring the requested protocol
     * ```
     * cors($request, 'www.cakephp.org');
     * ```
     *
     * ### Any URI
     * ```
     * cors($request, '*');
     * ```
     *
     * ### Whitelist of URIs
     * ```
     * cors($request, ['http://www.cakephp.org', '*.google.com', 'https://myproject.github.io']);
     * ```
     *
     * *Note* The `$allowedDomains`, `$allowedMethods`, `$allowedHeaders` parameters are deprecated.
     * Instead the builder object should be used.
     *
     * @param \Cake\Network\Request $request Request object
     * @param string|array $allowedDomains List of allowed domains, see method description for more details
     * @param string|array $allowedMethods List of HTTP verbs allowed
     * @param string|array $allowedHeaders List of HTTP headers allowed
     * @return \Cake\Network\CorsBuilder A builder object the provides a fluent interface for defining
     *   additional CORS headers.
     */
    public function cors(Request $request, $allowedDomains = [], $allowedMethods = [], $allowedHeaders = [])
    {
        $origin = $request->header('Origin');
        $ssl = $request->is('ssl');
        $builder = new CorsBuilder($this, $origin, $ssl);
        if (!$origin) {
            return $builder;
        }
        if (empty($allowedDomains) && empty($allowedMethods) && empty($allowedHeaders)) {
            return $builder;
        }

        $builder->allowOrigin($allowedDomains)
            ->allowMethods((array)$allowedMethods)
            ->allowHeaders((array)$allowedHeaders)
            ->build();

        return $builder;
    }

    /**
     * Setup for display or download the given file.
     *
     * If $_SERVER['HTTP_RANGE'] is set a slice of the file will be
     * returned instead of the entire file.
     *
     * ### Options keys
     *
     * - name: Alternate download name
     * - download: If `true` sets download header and forces file to be downloaded rather than displayed in browser
     *
     * @param string $path Path to file. If the path is not an absolute path that resolves
     *   to a file, `APP` will be prepended to the path.
     * @param array $options Options See above.
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException
     */
    public function file($path, array $options = [])
    {
        $options += [
            'name' => null,
            'download' => null
        ];

        if (strpos($path, '../') !== false || strpos($path, '..\\') !== false) {
            throw new NotFoundException('The requested file contains `..` and will not be read.');
        }

        if (!is_file($path)) {
            $path = APP . $path;
        }

        $file = new File($path);
        if (!$file->exists() || !$file->readable()) {
            if (Configure::read('debug')) {
                throw new NotFoundException(sprintf('The requested file %s was not found or not readable', $path));
            }
            throw new NotFoundException(__d('cake', 'The requested file was not found'));
        }

        $extension = strtolower($file->ext());
        $download = $options['download'];
        if ((!$extension || $this->type($extension) === false) && $download === null) {
            $download = true;
        }

        $fileSize = $file->size();
        if ($download) {
            $agent = env('HTTP_USER_AGENT');

            if (preg_match('%Opera(/| )([0-9].[0-9]{1,2})%', $agent)) {
                $contentType = 'application/octet-stream';
            } elseif (preg_match('/MSIE ([0-9].[0-9]{1,2})/', $agent)) {
                $contentType = 'application/force-download';
            }

            if (!empty($contentType)) {
                $this->type($contentType);
            }
            if ($options['name'] === null) {
                $name = $file->name;
            } else {
                $name = $options['name'];
            }
            $this->download($name);
            $this->header('Content-Transfer-Encoding', 'binary');
        }

        $this->header('Accept-Ranges', 'bytes');
        $httpRange = env('HTTP_RANGE');
        if (isset($httpRange)) {
            $this->_fileRange($file, $httpRange);
        } else {
            $this->header('Content-Length', $fileSize);
        }

        $this->_file = $file;
    }

    /**
     * Get the current file if one exists.
     *
     * @return \Cake\Filesystem\File|null The file to use in the response or null
     */
    public function getFile()
    {
        return $this->_file;
    }

    /**
     * Apply a file range to a file and set the end offset.
     *
     * If an invalid range is requested a 416 Status code will be used
     * in the response.
     *
     * @param \Cake\Filesystem\File $file The file to set a range on.
     * @param string $httpRange The range to use.
     * @return void
     */
    protected function _fileRange($file, $httpRange)
    {
        $fileSize = $file->size();
        $lastByte = $fileSize - 1;
        $start = 0;
        $end = $lastByte;

        preg_match('/^bytes\s*=\s*(\d+)?\s*-\s*(\d+)?$/', $httpRange, $matches);
        if ($matches) {
            $start = $matches[1];
            $end = isset($matches[2]) ? $matches[2] : '';
        }

        if ($start === '') {
            $start = $fileSize - $end;
            $end = $lastByte;
        }
        if ($end === '') {
            $end = $lastByte;
        }

        if ($start > $end || $end > $lastByte || $start > $lastByte) {
            $this->statusCode(416);
            $this->header([
                'Content-Range' => 'bytes 0-' . $lastByte . '/' . $fileSize
            ]);

            return;
        }

        $this->header([
            'Content-Length' => $end - $start + 1,
            'Content-Range' => 'bytes ' . $start . '-' . $end . '/' . $fileSize
        ]);

        $this->statusCode(206);
        $this->_fileRange = [$start, $end];
    }

    /**
     * Reads out a file, and echos the content to the client.
     *
     * @param \Cake\Filesystem\File $file File object
     * @param array $range The range to read out of the file.
     * @return bool True is whole file is echoed successfully or false if client connection is lost in between
     */
    protected function _sendFile($file, $range)
    {
        $compress = $this->outputCompressed();
        ob_implicit_flush(true);

        $file->open('rb');

        $end = $start = false;
        if ($range) {
            list($start, $end) = $range;
        }
        if ($start !== false) {
            $file->offset($start);
        }

        $bufferSize = 8192;
        set_time_limit(0);
        session_write_close();
        while (!feof($file->handle)) {
            if (!$this->_isActive()) {
                $file->close();

                return false;
            }
            $offset = $file->offset();
            if ($end && $offset >= $end) {
                break;
            }
            if ($end && $offset + $bufferSize >= $end) {
                $bufferSize = $end - $offset + 1;
            }
            echo fread($file->handle, $bufferSize);
        }
        $file->close();

        return true;
    }

    /**
     * Returns true if connection is still active
     *
     * @return bool
     */
    protected function _isActive()
    {
        return connection_status() === CONNECTION_NORMAL && !connection_aborted();
    }

    /**
     * Clears the contents of the topmost output buffer and discards them
     *
     * @return bool
     * @deprecated 3.2.4 This function is not needed anymore
     */
    protected function _clearBuffer()
    {
        //@codingStandardsIgnoreStart
        return @ob_end_clean();
        //@codingStandardsIgnoreEnd
    }

    /**
     * Flushes the contents of the output buffer
     *
     * @return void
     * @deprecated 3.2.4 This function is not needed anymore
     */
    protected function _flushBuffer()
    {
        //@codingStandardsIgnoreStart
        @flush();
        if (ob_get_level()) {
            @ob_flush();
        }
        //@codingStandardsIgnoreEnd
    }

    /**
     * Stop execution of the current script. Wraps exit() making
     * testing easier.
     *
     * @param int|string $status See http://php.net/exit for values
     * @return void
     */
    public function stop($status = 0)
    {
        exit($status);
    }
}
