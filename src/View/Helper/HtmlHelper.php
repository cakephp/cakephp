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
 * @since         0.9.1
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;
use Cake\View\StringTemplateTrait;

/**
 * Html Helper class for easy use of HTML widgets.
 *
 * HtmlHelper encloses all methods needed while working with HTML pages.
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 * @link https://book.cakephp.org/4/en/views/helpers/html.html
 */
class HtmlHelper extends Helper
{
    use StringTemplateTrait;

    /**
     * List of helpers used by this helper
     *
     * @var array
     */
    protected $helpers = ['Url'];

    /**
     * Default config for this class
     *
     * @var array
     */
    protected $_defaultConfig = [
        'templates' => [
            'meta' => '<meta{{attrs}}/>',
            'metalink' => '<link href="{{url}}"{{attrs}}/>',
            'link' => '<a href="{{url}}"{{attrs}}>{{content}}</a>',
            'mailto' => '<a href="mailto:{{url}}"{{attrs}}>{{content}}</a>',
            'image' => '<img src="{{url}}"{{attrs}}/>',
            'tableheader' => '<th{{attrs}}>{{content}}</th>',
            'tableheaderrow' => '<tr{{attrs}}>{{content}}</tr>',
            'tablecell' => '<td{{attrs}}>{{content}}</td>',
            'tablerow' => '<tr{{attrs}}>{{content}}</tr>',
            'block' => '<div{{attrs}}>{{content}}</div>',
            'blockstart' => '<div{{attrs}}>',
            'blockend' => '</div>',
            'tag' => '<{{tag}}{{attrs}}>{{content}}</{{tag}}>',
            'tagstart' => '<{{tag}}{{attrs}}>',
            'tagend' => '</{{tag}}>',
            'tagselfclosing' => '<{{tag}}{{attrs}}/>',
            'para' => '<p{{attrs}}>{{content}}</p>',
            'parastart' => '<p{{attrs}}>',
            'css' => '<link rel="{{rel}}" href="{{url}}"{{attrs}}/>',
            'style' => '<style{{attrs}}>{{content}}</style>',
            'charset' => '<meta charset="{{charset}}"/>',
            'ul' => '<ul{{attrs}}>{{content}}</ul>',
            'ol' => '<ol{{attrs}}>{{content}}</ol>',
            'li' => '<li{{attrs}}>{{content}}</li>',
            'javascriptblock' => '<script{{attrs}}>{{content}}</script>',
            'javascriptstart' => '<script>',
            'javascriptlink' => '<script src="{{url}}"{{attrs}}></script>',
            'javascriptend' => '</script>',
            'confirmJs' => '{{confirm}}',
        ],
    ];

    /**
     * Names of script & css files that have been included once
     *
     * @var array
     */
    protected $_includedAssets = [];

    /**
     * Options for the currently opened script block buffer if any.
     *
     * @var array
     */
    protected $_scriptBlockOptions = [];

    /**
     * Creates a link to an external resource and handles basic meta tags
     *
     * Create a meta tag that is output inline:
     *
     * ```
     * $this->Html->meta('icon', 'favicon.ico');
     * ```
     *
     * Append the meta tag to custom view block "meta":
     *
     * ```
     * $this->Html->meta('description', 'A great page', ['block' => true]);
     * ```
     *
     * Append the meta tag to custom view block:
     *
     * ```
     * $this->Html->meta('description', 'A great page', ['block' => 'metaTags']);
     * ```
     *
     * Create a custom meta tag:
     *
     * ```
     * $this->Html->meta(['property' => 'og:site_name', 'content' => 'CakePHP']);
     * ```
     *
     * ### Options
     *
     * - `block` - Set to true to append output to view block "meta" or provide
     *   custom block name.
     *
     * @param string|array $type The title of the external resource, Or an array of attributes for a
     *   custom meta tag.
     * @param string|array|null $content The address of the external resource or string for content attribute
     * @param array $options Other attributes for the generated tag. If the type attribute is html,
     *    rss, atom, or icon, the mime-type is returned.
     * @return string|null A completed `<link />` element, or null if the element was sent to a block.
     * @link https://book.cakephp.org/4/en/views/helpers/html.html#creating-meta-tags
     */
    public function meta($type, $content = null, array $options = []): ?string
    {
        if (!is_array($type)) {
            $types = [
                'rss' => ['type' => 'application/rss+xml', 'rel' => 'alternate', 'title' => $type, 'link' => $content],
                'atom' => ['type' => 'application/atom+xml', 'title' => $type, 'link' => $content],
                'icon' => ['type' => 'image/x-icon', 'rel' => 'icon', 'link' => $content],
                'keywords' => ['name' => 'keywords', 'content' => $content],
                'description' => ['name' => 'description', 'content' => $content],
                'robots' => ['name' => 'robots', 'content' => $content],
                'viewport' => ['name' => 'viewport', 'content' => $content],
                'canonical' => ['rel' => 'canonical', 'link' => $content],
                'next' => ['rel' => 'next', 'link' => $content],
                'prev' => ['rel' => 'prev', 'link' => $content],
                'first' => ['rel' => 'first', 'link' => $content],
                'last' => ['rel' => 'last', 'link' => $content],
            ];

            if ($type === 'icon' && $content === null) {
                $types['icon']['link'] = 'favicon.ico';
            }

            if (isset($types[$type])) {
                $type = $types[$type];
            } elseif (!isset($options['type']) && $content !== null) {
                if (is_array($content) && isset($content['_ext'])) {
                    $type = $types[$content['_ext']];
                } else {
                    $type = ['name' => $type, 'content' => $content];
                }
            } elseif (isset($options['type'], $types[$options['type']])) {
                $type = $types[$options['type']];
                unset($options['type']);
            } else {
                $type = [];
            }
        }

        $options += $type + ['block' => null];
        $out = '';

        if (isset($options['link'])) {
            if (is_array($options['link'])) {
                $options['link'] = $this->Url->build($options['link']);
            } else {
                $options['link'] = $this->Url->assetUrl($options['link']);
            }
            if (isset($options['rel']) && $options['rel'] === 'icon') {
                $out = $this->formatTemplate('metalink', [
                    'url' => $options['link'],
                    'attrs' => $this->templater()->formatAttributes($options, ['block', 'link']),
                ]);
                $options['rel'] = 'shortcut icon';
            }
            $out .= $this->formatTemplate('metalink', [
                'url' => $options['link'],
                'attrs' => $this->templater()->formatAttributes($options, ['block', 'link']),
            ]);
        } else {
            $out = $this->formatTemplate('meta', [
                'attrs' => $this->templater()->formatAttributes($options, ['block', 'type']),
            ]);
        }

        if (empty($options['block'])) {
            return $out;
        }
        if ($options['block'] === true) {
            $options['block'] = __FUNCTION__;
        }
        $this->_View->append($options['block'], $out);

        return null;
    }

    /**
     * Returns a charset META-tag.
     *
     * @param string|null $charset The character set to be used in the meta tag. If empty,
     *  The App.encoding value will be used. Example: "utf-8".
     * @return string A meta tag containing the specified character set.
     * @link https://book.cakephp.org/4/en/views/helpers/html.html#creating-charset-tags
     */
    public function charset(?string $charset = null): string
    {
        if (empty($charset)) {
            $charset = strtolower((string)Configure::read('App.encoding'));
        }

        return $this->formatTemplate('charset', [
            'charset' => !empty($charset) ? $charset : 'utf-8',
        ]);
    }

    /**
     * Creates an HTML link.
     *
     * If $url starts with "http://" this is treated as an external link. Else,
     * it is treated as a path to controller/action and parsed with the
     * UrlHelper::build() method.
     *
     * If the $url is empty, $title is used instead.
     *
     * ### Options
     *
     * - `escape` Set to false to disable escaping of title and attributes.
     * - `escapeTitle` Set to false to disable escaping of title. Takes precedence
     *   over value of `escape`)
     * - `confirm` JavaScript confirmation message.
     *
     * @param string|array $title The content to be wrapped by `<a>` tags.
     *   Can be an array if $url is null. If $url is null, $title will be used as both the URL and title.
     * @param string|array|null $url Cake-relative URL or array of URL parameters, or
     *   external URL (starts with http://)
     * @param array $options Array of options and HTML attributes.
     * @return string An `<a />` element.
     * @link https://book.cakephp.org/4/en/views/helpers/html.html#creating-links
     */
    public function link($title, $url = null, array $options = []): string
    {
        $escapeTitle = true;
        if ($url !== null) {
            $url = $this->Url->build($url, $options);
            unset($options['fullBase']);
        } else {
            $url = $this->Url->build($title);
            $title = htmlspecialchars_decode($url, ENT_QUOTES);
            $title = h(urldecode($title));
            $escapeTitle = false;
        }

        if (isset($options['escapeTitle'])) {
            $escapeTitle = $options['escapeTitle'];
            unset($options['escapeTitle']);
        } elseif (isset($options['escape'])) {
            $escapeTitle = $options['escape'];
        }

        if ($escapeTitle === true) {
            $title = h($title);
        } elseif (is_string($escapeTitle)) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $title = htmlentities($title, ENT_QUOTES, $escapeTitle);
        }

        $templater = $this->templater();
        $confirmMessage = null;
        if (isset($options['confirm'])) {
            $confirmMessage = $options['confirm'];
            unset($options['confirm']);
        }
        if ($confirmMessage) {
            $confirm = $this->_confirm('return true;', 'return false;');
            $options['data-confirm-message'] = $confirmMessage;
            $options['onclick'] = $templater->format('confirmJs', [
                'confirmMessage' => h($confirmMessage),
                'confirm' => $confirm,
            ]);
        }

        return $templater->format('link', [
            'url' => $url,
            'attrs' => $templater->formatAttributes($options),
            'content' => $title,
        ]);
    }

    /**
     * Creates an HTML link from route path string.
     *
     * ### Options
     *
     * - `escape` Set to false to disable escaping of title and attributes.
     * - `escapeTitle` Set to false to disable escaping of title. Takes precedence
     *   over value of `escape`)
     * - `confirm` JavaScript confirmation message.
     *
     * @param string $title The content to be wrapped by `<a>` tags.
     * @param string $path Cake-relative route path.
     * @param array $params An array specifying any additional parameters.
     *   Can be also any special parameters supported by `Router::url()`.
     * @param array $options Array of options and HTML attributes.
     * @return string An `<a />` element.
     * @see \Cake\Routing\Router::pathUrl()
     * @link https://book.cakephp.org/3/en/views/helpers/html.html#creating-links
     */
    public function linkFromPath(string $title, string $path, array $params = [], array $options = []): string
    {
        return $this->link($title, ['_path' => $path] + $params, $options);
    }

    /**
     * Creates a link element for CSS stylesheets.
     *
     * ### Usage
     *
     * Include one CSS file:
     *
     * ```
     * echo $this->Html->css('styles.css');
     * ```
     *
     * Include multiple CSS files:
     *
     * ```
     * echo $this->Html->css(['one.css', 'two.css']);
     * ```
     *
     * Add the stylesheet to view block "css":
     *
     * ```
     * $this->Html->css('styles.css', ['block' => true]);
     * ```
     *
     * Add the stylesheet to a custom block:
     *
     * ```
     * $this->Html->css('styles.css', ['block' => 'layoutCss']);
     * ```
     *
     * ### Options
     *
     * - `block` Set to true to append output to view block "css" or provide
     *   custom block name.
     * - `once` Whether or not the css file should be checked for uniqueness. If true css
     *   files  will only be included once, use false to allow the same
     *   css to be included more than once per request.
     * - `plugin` False value will prevent parsing path as a plugin
     * - `rel` Defaults to 'stylesheet'. If equal to 'import' the stylesheet will be imported.
     * - `fullBase` If true the URL will get a full address for the css file.
     *
     * @param string|string[] $path The name of a CSS style sheet or an array containing names of
     *   CSS stylesheets. If `$path` is prefixed with '/', the path will be relative to the webroot
     *   of your application. Otherwise, the path will be relative to your CSS path, usually webroot/css.
     * @param array $options Array of options and HTML arguments.
     * @return string|null CSS `<link />` or `<style />` tag, depending on the type of link.
     * @link https://book.cakephp.org/4/en/views/helpers/html.html#linking-to-css-files
     */
    public function css($path, array $options = []): ?string
    {
        $options += ['once' => true, 'block' => null, 'rel' => 'stylesheet'];

        if (is_array($path)) {
            $out = '';
            foreach ($path as $i) {
                $out .= "\n\t" . (string)$this->css($i, $options);
            }
            if (empty($options['block'])) {
                return $out . "\n";
            }

            return null;
        }

        if (strpos($path, '//') !== false) {
            $url = $path;
        } else {
            $url = $this->Url->css($path, $options);
            $options = array_diff_key($options, ['fullBase' => null, 'pathPrefix' => null]);
        }

        if ($options['once'] && isset($this->_includedAssets[__METHOD__][$path])) {
            return null;
        }
        unset($options['once']);
        $this->_includedAssets[__METHOD__][$path] = true;
        $templater = $this->templater();

        if ($options['rel'] === 'import') {
            $out = $templater->format('style', [
                'attrs' => $templater->formatAttributes($options, ['rel', 'block']),
                'content' => '@import url(' . $url . ');',
            ]);
        } else {
            $out = $templater->format('css', [
                'rel' => $options['rel'],
                'url' => $url,
                'attrs' => $templater->formatAttributes($options, ['rel', 'block']),
            ]);
        }

        if (empty($options['block'])) {
            return $out;
        }
        if ($options['block'] === true) {
            $options['block'] = __FUNCTION__;
        }
        $this->_View->append($options['block'], $out);

        return null;
    }

    /**
     * Returns one or many `<script>` tags depending on the number of scripts given.
     *
     * If the filename is prefixed with "/", the path will be relative to the base path of your
     * application. Otherwise, the path will be relative to your JavaScript path, usually webroot/js.
     *
     * ### Usage
     *
     * Include one script file:
     *
     * ```
     * echo $this->Html->script('styles.js');
     * ```
     *
     * Include multiple script files:
     *
     * ```
     * echo $this->Html->script(['one.js', 'two.js']);
     * ```
     *
     * Add the script file to a custom block:
     *
     * ```
     * $this->Html->script('styles.js', ['block' => 'bodyScript']);
     * ```
     *
     * ### Options
     *
     * - `block` Set to true to append output to view block "script" or provide
     *   custom block name.
     * - `once` Whether or not the script should be checked for uniqueness. If true scripts will only be
     *   included once, use false to allow the same script to be included more than once per request.
     * - `plugin` False value will prevent parsing path as a plugin
     * - `fullBase` If true the url will get a full address for the script file.
     *
     * @param string|string[] $url String or array of javascript files to include
     * @param array $options Array of options, and html attributes see above.
     * @return string|null String of `<script />` tags or null if block is specified in options
     *   or if $once is true and the file has been included before.
     * @link https://book.cakephp.org/4/en/views/helpers/html.html#linking-to-javascript-files
     */
    public function script($url, array $options = []): ?string
    {
        $defaults = ['block' => null, 'once' => true];
        $options += $defaults;

        if (is_array($url)) {
            $out = '';
            foreach ($url as $i) {
                $out .= "\n\t" . (string)$this->script($i, $options);
            }
            if (empty($options['block'])) {
                return $out . "\n";
            }

            return null;
        }

        if (strpos($url, '//') === false) {
            $url = $this->Url->script($url, $options);
            $options = array_diff_key($options, ['fullBase' => null, 'pathPrefix' => null]);
        }
        if ($options['once'] && isset($this->_includedAssets[__METHOD__][$url])) {
            return null;
        }
        $this->_includedAssets[__METHOD__][$url] = true;

        $out = $this->formatTemplate('javascriptlink', [
            'url' => $url,
            'attrs' => $this->templater()->formatAttributes($options, ['block', 'once']),
        ]);

        if (empty($options['block'])) {
            return $out;
        }
        if ($options['block'] === true) {
            $options['block'] = __FUNCTION__;
        }
        $this->_View->append($options['block'], $out);

        return null;
    }

    /**
     * Wrap $script in a script tag.
     *
     * ### Options
     *
     * - `block` Set to true to append output to view block "script" or provide
     *   custom block name.
     *
     * @param string $script The script to wrap
     * @param array $options The options to use. Options not listed above will be
     *    treated as HTML attributes.
     * @return string|null String or null depending on the value of `$options['block']`
     * @link https://book.cakephp.org/4/en/views/helpers/html.html#creating-inline-javascript-blocks
     */
    public function scriptBlock(string $script, array $options = []): ?string
    {
        $options += ['block' => null];

        $out = $this->formatTemplate('javascriptblock', [
            'attrs' => $this->templater()->formatAttributes($options, ['block']),
            'content' => $script,
        ]);

        if (empty($options['block'])) {
            return $out;
        }
        if ($options['block'] === true) {
            $options['block'] = 'script';
        }
        $this->_View->append($options['block'], $out);

        return null;
    }

    /**
     * Begin a script block that captures output until HtmlHelper::scriptEnd()
     * is called. This capturing block will capture all output between the methods
     * and create a scriptBlock from it.
     *
     * ### Options
     *
     * - `block` Set to true to append output to view block "script" or provide
     *   custom block name.
     *
     * @param array $options Options for the code block.
     * @return void
     * @link https://book.cakephp.org/4/en/views/helpers/html.html#creating-inline-javascript-blocks
     */
    public function scriptStart(array $options = []): void
    {
        $this->_scriptBlockOptions = $options;
        ob_start();
    }

    /**
     * End a Buffered section of JavaScript capturing.
     * Generates a script tag inline or appends to specified view block depending on
     * the settings used when the scriptBlock was started
     *
     * @return string|null Depending on the settings of scriptStart() either a script tag or null
     * @link https://book.cakephp.org/4/en/views/helpers/html.html#creating-inline-javascript-blocks
     */
    public function scriptEnd(): ?string
    {
        $buffer = ob_get_clean();
        $options = $this->_scriptBlockOptions;
        $this->_scriptBlockOptions = [];

        return $this->scriptBlock($buffer, $options);
    }

    /**
     * Builds CSS style data from an array of CSS properties
     *
     * ### Usage:
     *
     * ```
     * echo $this->Html->style(['margin' => '10px', 'padding' => '10px'], true);
     *
     * // creates
     * 'margin:10px;padding:10px;'
     * ```
     *
     * @param array $data Style data array, keys will be used as property names, values as property values.
     * @param bool $oneLine Whether or not the style block should be displayed on one line.
     * @return string CSS styling data
     * @link https://book.cakephp.org/4/en/views/helpers/html.html#creating-css-programatically
     */
    public function style(array $data, bool $oneLine = true): string
    {
        $out = [];
        foreach ($data as $key => $value) {
            $out[] = $key . ':' . $value . ';';
        }
        if ($oneLine) {
            return implode(' ', $out);
        }

        return implode("\n", $out);
    }

    /**
     * Creates a formatted IMG element.
     *
     * This method will set an empty alt attribute if one is not supplied.
     *
     * ### Usage:
     *
     * Create a regular image:
     *
     * ```
     * echo $this->Html->image('cake_icon.png', ['alt' => 'CakePHP']);
     * ```
     *
     * Create an image link:
     *
     * ```
     * echo $this->Html->image('cake_icon.png', ['alt' => 'CakePHP', 'url' => 'https://cakephp.org']);
     * ```
     *
     * ### Options:
     *
     * - `url` If provided an image link will be generated and the link will point at
     *   `$options['url']`.
     * - `fullBase` If true the src attribute will get a full address for the image file.
     * - `plugin` False value will prevent parsing path as a plugin
     *
     * @param string|array $path Path to the image file, relative to the webroot/img/ directory.
     * @param array $options Array of HTML attributes. See above for special options.
     * @return string completed img tag
     * @link https://book.cakephp.org/4/en/views/helpers/html.html#linking-to-images
     */
    public function image($path, array $options = []): string
    {
        if (is_string($path)) {
            $path = $this->Url->image($path, $options);
        } else {
            $path = $this->Url->build($path, $options);
        }
        $options = array_diff_key($options, ['fullBase' => null, 'pathPrefix' => null]);

        if (!isset($options['alt'])) {
            $options['alt'] = '';
        }

        $url = false;
        if (!empty($options['url'])) {
            $url = $options['url'];
            unset($options['url']);
        }

        $templater = $this->templater();
        $image = $templater->format('image', [
            'url' => $path,
            'attrs' => $templater->formatAttributes($options),
        ]);

        if ($url) {
            return $templater->format('link', [
                'url' => $this->Url->build($url),
                'attrs' => null,
                'content' => $image,
            ]);
        }

        return $image;
    }

    /**
     * Returns a row of formatted and named TABLE headers.
     *
     * @param array $names Array of tablenames. Each tablename can be string, or array with name and an array with a set
     *     of attributes to its specific tag
     * @param array|null $trOptions HTML options for TR elements.
     * @param array|null $thOptions HTML options for TH elements.
     * @return string Completed table headers
     * @link https://book.cakephp.org/4/en/views/helpers/html.html#creating-table-headings
     */
    public function tableHeaders(array $names, ?array $trOptions = null, ?array $thOptions = null): string
    {
        $out = [];
        foreach ($names as $arg) {
            if (!is_array($arg)) {
                $content = $arg;
                $attrs = $thOptions;
            } elseif (isset($arg[0], $arg[1])) {
                $content = $arg[0];
                $attrs = $arg[1];
            } else {
                $content = key($arg);
                $attrs = current($arg);
            }

            $out[] = $this->formatTemplate('tableheader', [
                'attrs' => $this->templater()->formatAttributes($attrs),
                'content' => $content,
            ]);
        }

        return $this->tableRow(implode(' ', $out), (array)$trOptions);
    }

    /**
     * Returns a formatted string of table rows (TR's with TD's in them).
     *
     * @param array|string $data Array of table data
     * @param array|bool|null $oddTrOptions HTML options for odd TR elements if true useCount is used
     * @param array|bool|null $evenTrOptions HTML options for even TR elements
     * @param bool $useCount adds class "column-$i"
     * @param bool $continueOddEven If false, will use a non-static $count variable,
     *    so that the odd/even count is reset to zero just for that call.
     * @return string Formatted HTML
     * @link https://book.cakephp.org/4/en/views/helpers/html.html#creating-table-cells
     */
    public function tableCells(
        $data,
        $oddTrOptions = null,
        $evenTrOptions = null,
        bool $useCount = false,
        bool $continueOddEven = true
    ): string {
        if (!is_array($data)) {
            $data = [[$data]];
        } elseif (empty($data[0]) || !is_array($data[0])) {
            $data = [$data];
        }

        if ($oddTrOptions === true) {
            $useCount = true;
            $oddTrOptions = null;
        }

        if ($evenTrOptions === false) {
            $continueOddEven = false;
            $evenTrOptions = null;
        }

        if ($continueOddEven) {
            static $count = 0;
        } else {
            $count = 0;
        }

        $out = [];
        foreach ($data as $line) {
            $count++;
            $cellsOut = $this->_renderCells($line, $useCount);
            $opts = $count % 2 ? $oddTrOptions : $evenTrOptions;
            $out[] = $this->tableRow(implode(' ', $cellsOut), (array)$opts);
        }

        return implode("\n", $out);
    }

    /**
     * Renders cells for a row of a table.
     *
     * This is a helper method for tableCells(). Overload this method as you
     * need to change the behavior of the cell rendering.
     *
     * @param array $line Line data to render.
     * @param bool $useCount Renders the count into the row. Default is false.
     * @return string[]
     */
    protected function _renderCells(array $line, bool $useCount = false): array
    {
        $i = 0;
        $cellsOut = [];
        foreach ($line as $cell) {
            $cellOptions = [];

            if (is_array($cell)) {
                $cellOptions = $cell[1];
                $cell = $cell[0];
            }

            if ($useCount) {
                $i += 1;
                if (isset($cellOptions['class'])) {
                    $cellOptions['class'] .= ' column-' . $i;
                } else {
                    $cellOptions['class'] = 'column-' . $i;
                }
            }

            $cellsOut[] = $this->tableCell((string)$cell, $cellOptions);
        }

        return $cellsOut;
    }

    /**
     * Renders a single table row (A TR with attributes).
     *
     * @param string $content The content of the row.
     * @param array $options HTML attributes.
     * @return string
     */
    public function tableRow(string $content, array $options = []): string
    {
        return $this->formatTemplate('tablerow', [
            'attrs' => $this->templater()->formatAttributes($options),
            'content' => $content,
        ]);
    }

    /**
     * Renders a single table cell (A TD with attributes).
     *
     * @param string $content The content of the cell.
     * @param array $options HTML attributes.
     * @return string
     */
    public function tableCell(string $content, array $options = []): string
    {
        return $this->formatTemplate('tablecell', [
            'attrs' => $this->templater()->formatAttributes($options),
            'content' => $content,
        ]);
    }

    /**
     * Returns a formatted block tag, i.e DIV, SPAN, P.
     *
     * ### Options
     *
     * - `escape` Whether or not the contents should be html_entity escaped.
     *
     * @param string $name Tag name.
     * @param string|null $text String content that will appear inside the div element.
     *   If null, only a start tag will be printed
     * @param array $options Additional HTML attributes of the DIV tag, see above.
     * @return string The formatted tag element
     */
    public function tag(string $name, ?string $text = null, array $options = []): string
    {
        if (isset($options['escape']) && $options['escape']) {
            $text = h($text);
            unset($options['escape']);
        }
        if ($text === null) {
            $tag = 'tagstart';
        } else {
            $tag = 'tag';
        }

        return $this->formatTemplate($tag, [
            'attrs' => $this->templater()->formatAttributes($options),
            'tag' => $name,
            'content' => $text,
        ]);
    }

    /**
     * Returns a formatted DIV tag for HTML FORMs.
     *
     * ### Options
     *
     * - `escape` Whether or not the contents should be html_entity escaped.
     *
     * @param string|null $class CSS class name of the div element.
     * @param string|null $text String content that will appear inside the div element.
     *   If null, only a start tag will be printed
     * @param array $options Additional HTML attributes of the DIV tag
     * @return string The formatted DIV element
     */
    public function div(?string $class = null, ?string $text = null, array $options = []): string
    {
        if (!empty($class)) {
            $options['class'] = $class;
        }

        return $this->tag('div', $text, $options);
    }

    /**
     * Returns a formatted P tag.
     *
     * ### Options
     *
     * - `escape` Whether or not the contents should be html_entity escaped.
     *
     * @param string|null $class CSS class name of the p element.
     * @param string|null $text String content that will appear inside the p element.
     * @param array $options Additional HTML attributes of the P tag
     * @return string The formatted P element
     */
    public function para(?string $class, ?string $text, array $options = []): string
    {
        if (!empty($options['escape'])) {
            $text = h($text);
        }
        if ($class) {
            $options['class'] = $class;
        }
        $tag = 'para';
        if ($text === null) {
            $tag = 'parastart';
        }

        return $this->formatTemplate($tag, [
            'attrs' => $this->templater()->formatAttributes($options),
            'content' => $text,
        ]);
    }

    /**
     * Returns an audio/video element
     *
     * ### Usage
     *
     * Using an audio file:
     *
     * ```
     * echo $this->Html->media('audio.mp3', ['fullBase' => true]);
     * ```
     *
     * Outputs:
     *
     * ```
     * <video src="http://www.somehost.com/files/audio.mp3">Fallback text</video>
     * ```
     *
     * Using a video file:
     *
     * ```
     * echo $this->Html->media('video.mp4', ['text' => 'Fallback text']);
     * ```
     *
     * Outputs:
     *
     * ```
     * <video src="/files/video.mp4">Fallback text</video>
     * ```
     *
     * Using multiple video files:
     *
     * ```
     * echo $this->Html->media(
     *      ['video.mp4', ['src' => 'video.ogv', 'type' => "video/ogg; codecs='theora, vorbis'"]],
     *      ['tag' => 'video', 'autoplay']
     * );
     * ```
     *
     * Outputs:
     *
     * ```
     * <video autoplay="autoplay">
     *      <source src="/files/video.mp4" type="video/mp4"/>
     *      <source src="/files/video.ogv" type="video/ogv; codecs='theora, vorbis'"/>
     * </video>
     * ```
     *
     * ### Options
     *
     * - `tag` Type of media element to generate, either "audio" or "video".
     *  If tag is not provided it's guessed based on file's mime type.
     * - `text` Text to include inside the audio/video tag
     * - `pathPrefix` Path prefix to use for relative URLs, defaults to 'files/'
     * - `fullBase` If provided the src attribute will get a full address including domain name
     *
     * @param string|array $path Path to the video file, relative to the webroot/{$options['pathPrefix']} directory.
     *  Or an array where each item itself can be a path string or an associate array containing keys `src` and `type`
     * @param array $options Array of HTML attributes, and special options above.
     * @return string Generated media element
     */
    public function media($path, array $options = []): string
    {
        $options += [
            'tag' => null,
            'pathPrefix' => 'files/',
            'text' => '',
        ];

        if (!empty($options['tag'])) {
            $tag = $options['tag'];
        } else {
            $tag = null;
        }

        if (is_array($path)) {
            $sourceTags = '';
            foreach ($path as &$source) {
                if (is_string($source)) {
                    $source = [
                        'src' => $source,
                    ];
                }
                if (!isset($source['type'])) {
                    $ext = pathinfo($source['src'], PATHINFO_EXTENSION);
                    $source['type'] = $this->_View->getResponse()->getMimeType($ext);
                }
                $source['src'] = $this->Url->assetUrl($source['src'], $options);
                $sourceTags .= $this->formatTemplate('tagselfclosing', [
                    'tag' => 'source',
                    'attrs' => $this->templater()->formatAttributes($source),
                ]);
            }
            unset($source);
            $options['text'] = $sourceTags . $options['text'];
            unset($options['fullBase']);
        } else {
            if (empty($path) && !empty($options['src'])) {
                $path = $options['src'];
            }
            $options['src'] = $this->Url->assetUrl($path, $options);
        }

        if ($tag === null) {
            if (is_array($path)) {
                $mimeType = $path[0]['type'];
            } else {
                /** @var string $mimeType */
                $mimeType = $this->_View->getResponse()->getMimeType(pathinfo($path, PATHINFO_EXTENSION));
            }
            if (preg_match('#^video/#', $mimeType)) {
                $tag = 'video';
            } else {
                $tag = 'audio';
            }
        }

        if (isset($options['poster'])) {
            $options['poster'] = $this->Url->assetUrl(
                $options['poster'],
                ['pathPrefix' => Configure::read('App.imageBaseUrl')] + $options
            );
        }
        $text = $options['text'];

        $options = array_diff_key($options, [
            'tag' => null,
            'fullBase' => null,
            'pathPrefix' => null,
            'text' => null,
        ]);

        return $this->tag($tag, $text, $options);
    }

    /**
     * Build a nested list (UL/OL) out of an associative array.
     *
     * Options for $options:
     *
     * - `tag` - Type of list tag to use (ol/ul)
     *
     * Options for $itemOptions:
     *
     * - `even` - Class to use for even rows.
     * - `odd` - Class to use for odd rows.
     *
     * @param array $list Set of elements to list
     * @param array $options Options and additional HTML attributes of the list (ol/ul) tag.
     * @param array $itemOptions Options and additional HTML attributes of the list item (LI) tag.
     * @return string The nested list
     * @link https://book.cakephp.org/4/en/views/helpers/html.html#creating-nested-lists
     */
    public function nestedList(array $list, array $options = [], array $itemOptions = []): string
    {
        $options += ['tag' => 'ul'];
        $items = $this->_nestedListItem($list, $options, $itemOptions);

        return $this->formatTemplate($options['tag'], [
            'attrs' => $this->templater()->formatAttributes($options, ['tag']),
            'content' => $items,
        ]);
    }

    /**
     * Internal function to build a nested list (UL/OL) out of an associative array.
     *
     * @param array $items Set of elements to list.
     * @param array $options Additional HTML attributes of the list (ol/ul) tag.
     * @param array $itemOptions Options and additional HTML attributes of the list item (LI) tag.
     * @return string The nested list element
     * @see \Cake\View\Helper\HtmlHelper::nestedList()
     */
    protected function _nestedListItem(array $items, array $options, array $itemOptions): string
    {
        $out = '';

        $index = 1;
        foreach ($items as $key => $item) {
            if (is_array($item)) {
                $item = $key . $this->nestedList($item, $options, $itemOptions);
            }
            if (isset($itemOptions['even']) && $index % 2 === 0) {
                $itemOptions['class'] = $itemOptions['even'];
            } elseif (isset($itemOptions['odd']) && $index % 2 !== 0) {
                $itemOptions['class'] = $itemOptions['odd'];
            }
            $out .= $this->formatTemplate('li', [
                'attrs' => $this->templater()->formatAttributes($itemOptions, ['even', 'odd']),
                'content' => $item,
            ]);
            $index++;
        }

        return $out;
    }

    /**
     * Event listeners.
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return [];
    }
}
