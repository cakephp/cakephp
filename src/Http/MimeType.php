<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use finfo;

/**
 * MimeType Class
 *
 * Handles MIME type operations and provides functionality for working with
 * Multipurpose Internet Mail Extensions (MIME) types in the application.
 * This class is responsible for MIME type detection and mapping file extensions
 * to their corresponding MIME types.
 */
class MimeType
{
    /**
     * Array of MIME type mappings.
     *
     * Associates file extensions with their corresponding MIME type(s).
     * Each key is a file extension (without the dot) and the value is an array
     * of one or more valid MIME types for that extension.
     *
     * Common MIME types included:
     * - Web formats (html, json, xml)
     * - Image formats (webp)
     * - Feed formats (rss)
     * - Application formats (ai, bin, csv, etc.)
     *
     * Some extensions may map to multiple MIME types, with the first type in the array
     * being the preferred/default type.
     *
     * @var array<string, array<string>>
     */
    protected static array $mimeTypes = [
        'html' => ['text/html', '*/*'],
        'json' => ['application/json'],
        'xml' => ['application/xml', 'text/xml'],
        'xhtml' => ['application/xhtml+xml', 'application/xhtml', 'text/xhtml'],
        'webp' => ['image/webp'],
        'rss' => ['application/rss+xml'],
        'ai' => ['application/postscript'],
        'bcpio' => ['application/x-bcpio'],
        'bin' => ['application/octet-stream'],
        'ccad' => ['application/clariscad'],
        'cdf' => ['application/x-netcdf'],
        'class' => ['application/octet-stream'],
        'cpio' => ['application/x-cpio'],
        'cpt' => ['application/mac-compactpro'],
        'csh' => ['application/x-csh'],
        'csv' => ['text/csv', 'application/vnd.ms-excel'],
        'dcr' => ['application/x-director'],
        'dir' => ['application/x-director'],
        'dms' => ['application/octet-stream'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'drw' => ['application/drafting'],
        'dvi' => ['application/x-dvi'],
        'dwg' => ['application/acad'],
        'dxf' => ['application/dxf'],
        'dxr' => ['application/x-director'],
        'eot' => ['application/vnd.ms-fontobject'],
        'eps' => ['application/postscript'],
        'exe' => ['application/octet-stream'],
        'ez' => ['application/andrew-inset'],
        'flv' => ['video/x-flv'],
        'gtar' => ['application/x-gtar'],
        'gz' => ['application/x-gzip'],
        'bz2' => ['application/x-bzip'],
        '7z' => ['application/x-7z-compressed'],
        'hal' => ['application/hal+xml', 'application/vnd.hal+xml'],
        'haljson' => ['application/hal+json', 'application/vnd.hal+json'],
        'halxml' => ['application/hal+xml', 'application/vnd.hal+xml'],
        'hdf' => ['application/x-hdf'],
        'hqx' => ['application/mac-binhex40'],
        'ico' => ['image/x-icon'],
        'ips' => ['application/x-ipscript'],
        'ipx' => ['application/x-ipix'],
        'js' => ['application/javascript'],
        'cjs' => ['application/javascript'],
        'mjs' => ['application/javascript'],
        'jsonapi' => ['application/vnd.api+json'],
        'latex' => ['application/x-latex'],
        'jsonld' => ['application/ld+json'],
        'kml' => ['application/vnd.google-earth.kml+xml'],
        'kmz' => ['application/vnd.google-earth.kmz'],
        'lha' => ['application/octet-stream'],
        'lsp' => ['application/x-lisp'],
        'lzh' => ['application/octet-stream'],
        'man' => ['application/x-troff-man'],
        'me' => ['application/x-troff-me'],
        'mif' => ['application/vnd.mif'],
        'ms' => ['application/x-troff-ms'],
        'nc' => ['application/x-netcdf'],
        'oda' => ['application/oda'],
        'otf' => ['font/otf'],
        'pdf' => ['application/pdf'],
        'pgn' => ['application/x-chess-pgn'],
        'pot' => ['application/vnd.ms-powerpoint'],
        'pps' => ['application/vnd.ms-powerpoint'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'ppz' => ['application/vnd.ms-powerpoint'],
        'pre' => ['application/x-freelance'],
        'prt' => ['application/pro_eng'],
        'ps' => ['application/postscript'],
        'roff' => ['application/x-troff'],
        'scm' => ['application/x-lotusscreencam'],
        'set' => ['application/set'],
        'sh' => ['application/x-sh'],
        'shar' => ['application/x-shar'],
        'sit' => ['application/x-stuffit'],
        'skd' => ['application/x-koan'],
        'skm' => ['application/x-koan'],
        'skp' => ['application/x-koan'],
        'skt' => ['application/x-koan'],
        'smi' => ['application/smil'],
        'smil' => ['application/smil'],
        'sol' => ['application/solids'],
        'spl' => ['application/x-futuresplash'],
        'src' => ['application/x-wais-source'],
        'step' => ['application/STEP'],
        'stl' => ['application/SLA'],
        'stp' => ['application/STEP'],
        'sv4cpio' => ['application/x-sv4cpio'],
        'sv4crc' => ['application/x-sv4crc'],
        'svg' => ['image/svg+xml'],
        'svgz' => ['image/svg+xml'],
        'swf' => ['application/x-shockwave-flash'],
        't' => ['application/x-troff'],
        'tar' => ['application/x-tar'],
        'tcl' => ['application/x-tcl'],
        'tex' => ['application/x-tex'],
        'texi' => ['application/x-texinfo'],
        'texinfo' => ['application/x-texinfo'],
        'tr' => ['application/x-troff'],
        'tsp' => ['application/dsptype'],
        'ttc' => ['font/ttf'],
        'ttf' => ['font/ttf'],
        'unv' => ['application/i-deas'],
        'ustar' => ['application/x-ustar'],
        'vcd' => ['application/x-cdlink'],
        'vda' => ['application/vda'],
        'xlc' => ['application/vnd.ms-excel'],
        'xll' => ['application/vnd.ms-excel'],
        'xlm' => ['application/vnd.ms-excel'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'xlsm' => ['application/vnd.ms-excel.sheet.macroEnabled.12'],
        'xlw' => ['application/vnd.ms-excel'],
        'zip' => ['application/zip'],
        'aif' => ['audio/x-aiff'],
        'aifc' => ['audio/x-aiff'],
        'aiff' => ['audio/x-aiff'],
        'au' => ['audio/basic'],
        'kar' => ['audio/midi'],
        'mid' => ['audio/midi'],
        'midi' => ['audio/midi'],
        'mp2' => ['audio/mpeg'],
        'mp3' => ['audio/mpeg'],
        'mpga' => ['audio/mpeg'],
        'ogg' => ['audio/ogg'],
        'oga' => ['audio/ogg'],
        'spx' => ['audio/ogg'],
        'ra' => ['audio/x-realaudio'],
        'ram' => ['audio/x-pn-realaudio'],
        'rm' => ['audio/x-pn-realaudio'],
        'rpm' => ['audio/x-pn-realaudio-plugin'],
        'snd' => ['audio/basic'],
        'tsi' => ['audio/TSP-audio'],
        'wav' => ['audio/x-wav'],
        'aac' => ['audio/aac'],
        'asc' => ['text/plain'],
        'c' => ['text/plain'],
        'cc' => ['text/plain'],
        'css' => ['text/css'],
        'etx' => ['text/x-setext'],
        'f' => ['text/plain'],
        'f90' => ['text/plain'],
        'h' => ['text/plain'],
        'hh' => ['text/plain'],
        'htm' => ['text/html', '*/*'],
        'ics' => ['text/calendar'],
        'm' => ['text/plain'],
        'rtf' => ['text/rtf'],
        'rtx' => ['text/richtext'],
        'sgm' => ['text/sgml'],
        'sgml' => ['text/sgml'],
        'tsv' => ['text/tab-separated-values'],
        'tpl' => ['text/template'],
        'txt' => ['text/plain'],
        'text' => ['text/plain'],
        'avi' => ['video/x-msvideo'],
        'fli' => ['video/x-fli'],
        'mov' => ['video/quicktime'],
        'movie' => ['video/x-sgi-movie'],
        'mpe' => ['video/mpeg'],
        'mpeg' => ['video/mpeg'],
        'mpg' => ['video/mpeg'],
        'qt' => ['video/quicktime'],
        'viv' => ['video/vnd.vivo'],
        'vivo' => ['video/vnd.vivo'],
        'ogv' => ['video/ogg'],
        'webm' => ['video/webm'],
        'mp4' => ['video/mp4'],
        'm4v' => ['video/mp4'],
        'f4v' => ['video/mp4'],
        'f4p' => ['video/mp4'],
        'm4a' => ['audio/mp4'],
        'f4a' => ['audio/mp4'],
        'f4b' => ['audio/mp4'],
        'gif' => ['image/gif'],
        'ief' => ['image/ief'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'jpe' => ['image/jpeg'],
        'pbm' => ['image/x-portable-bitmap'],
        'pgm' => ['image/x-portable-graymap'],
        'png' => ['image/png'],
        'pnm' => ['image/x-portable-anymap'],
        'ppm' => ['image/x-portable-pixmap'],
        'ras' => ['image/cmu-raster'],
        'rgb' => ['image/x-rgb'],
        'tif' => ['image/tiff'],
        'tiff' => ['image/tiff'],
        'xbm' => ['image/x-xbitmap'],
        'xpm' => ['image/x-xpixmap'],
        'xwd' => ['image/x-xwindowdump'],
        'psd' => [
            'application/photoshop',
            'application/psd',
            'image/psd',
            'image/x-photoshop',
            'image/photoshop',
            'zz-application/zz-winassoc-psd',
        ],
        'ice' => ['x-conference/x-cooltalk'],
        'iges' => ['model/iges'],
        'igs' => ['model/iges'],
        'mesh' => ['model/mesh'],
        'msh' => ['model/mesh'],
        'silo' => ['model/mesh'],
        'vrml' => ['model/vrml'],
        'wrl' => ['model/vrml'],
        'mime' => ['www/mime'],
        'pdb' => ['chemical/x-pdb'],
        'xyz' => ['chemical/x-pdb'],
        'javascript' => ['application/javascript'],
        'form' => ['application/x-www-form-urlencoded'],
        'file' => ['multipart/form-data'],
        'xhtml-mobile' => ['application/vnd.wap.xhtml+xml'],
        'atom' => ['application/atom+xml'],
        'amf' => ['application/x-amf'],
        'wap' => ['text/vnd.wap.wml', 'text/vnd.wap.wmlscript', 'image/vnd.wap.wbmp'],
        'wml' => ['text/vnd.wap.wml'],
        'wmlscript' => ['text/vnd.wap.wmlscript'],
        'wbmp' => ['image/vnd.wap.wbmp'],
        'woff' => ['application/x-font-woff'],
        'appcache' => ['text/cache-manifest'],
        'manifest' => ['text/cache-manifest'],
        'htc' => ['text/x-component'],
        'rdf' => ['application/xml'],
        'crx' => ['application/x-chrome-extension'],
        'oex' => ['application/x-opera-extension'],
        'xpi' => ['application/x-xpinstall'],
        'safariextz' => ['application/octet-stream'],
        'webapp' => ['application/x-web-app-manifest+json'],
        'vcf' => ['text/x-vcard'],
        'vtt' => ['text/vtt'],
        'mkv' => ['video/x-matroska'],
        'pkpass' => ['application/vnd.apple.pkpass'],
        'ajax' => ['text/html'],
        'bmp' => ['image/bmp'],
    ];

    /**
     * Get the MIME types associated with a given file extension.
     *
     * @param string|null $ext The file extension to look up. Use null to return the full list.
     * @return array|null An array of MIME types if found, or null if no MIME types are associated with the extension.
     */
    public static function getMimeTypes(?string $ext = null): ?array
    {
        if ($ext === null) {
            return static::$mimeTypes;
        }

        return static::$mimeTypes[$ext] ?? null;
    }

    /**
     * Get the MIME type based on the file extension.
     *
     * @param string $ext The file extension.
     * @param string|null $default The default MIME type to return if the extension is not found. Defaults to null.
     * @return string|null The MIME type corresponding to the file extension, or the default MIME type if not found.
     */
    public static function getMimeType(string $ext, ?string $default = null): ?string
    {
        return isset(static::$mimeTypes[$ext]) ? static::$mimeTypes[$ext][0] : null;
    }

    /**
     * Add new mime types for a given file extension.
     *
     * If the file extension already exists, the new mime types will be merged with the existing ones.
     *
     * @param string $ext The file extension to associate with the mime types.
     * @param array|string $mimeTypes The mime types to associate with the file extension.
     * @return void
     */
    public static function addMimeTypes(string $ext, array|string $mimeTypes): void
    {
        if (isset(static::$mimeTypes[$ext])) {
            static::$mimeTypes[$ext] = array_merge(static::$mimeTypes[$ext], (array)$mimeTypes);

            return;
        }

        static::$mimeTypes[$ext] = (array)$mimeTypes;
    }

    /**
     * Set MIME types for a given file extension.
     *
     * This will overwrite any existing MIME types for the file extension.
     *
     * @param string $ext The file extension.
     * @param array|string $mimeTypes The MIME types to associate with the file extension.
     * @return void
     */
    public static function setMimeTypes(string $ext, array|string $mimeTypes): void
    {
        static::$mimeTypes[$ext] = (array)$mimeTypes;
    }

    /**
     * Get the file extension associated with a given MIME type.
     *
     * @param string $mimeType The MIME type for which to get the file extension.
     * @return string|null The file extension associated with the MIME type, or null if no association is found.
     */
    public static function getExtension(string $mimeType): ?string
    {
        foreach (static::$mimeTypes as $ext => $types) {
            if (in_array($mimeType, $types, true)) {
                return $ext;
            }
        }

        return null;
    }

    /**
     * Get the MIME type for a given file path.
     *
     * If the MIME type is not mapped to an extension then it will attempt to determine the MIME type of the file using
     * the fileinfo extension.
     *
     * @param string $path The file path for which to get the MIME type.
     * @param string $default The default MIME type to return if the MIME type cannot be determined.
     * @return string The MIME type of the file, or the default MIME type if it cannot be determined.
     */
    public static function getMimeTypeForFile(string $path, string $default = 'application/octet-stream'): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (isset(static::$mimeTypes[$ext])) {
            return static::$mimeTypes[$ext][0];
        }

        $finfo = new finfo(FILEINFO_MIME);
        $mimeType = $finfo->file($path);

        return $mimeType === false ? $default : $mimeType;
    }
}
