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

use Cake\View\HtmlStringable;
use InvalidArgumentException;
use function Cake\Core\h;

/**
 * Opt-in trusted HTML support that preserves the 5.x HtmlHelper signatures.
 */
class TrustedHtmlHelper extends HtmlHelper
{
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
     *   over value of `escape`.
     * - `confirm` JavaScript confirmation message.
     *
     * @param array|string $title The content to be wrapped by `<a>` tags.
     *   Can be an array if $url is null. If $url is null, $title will be used as both the URL and title.
     * @param array|string|null $url Cake-relative URL or array of URL parameters, or
     *   external URL (starts with http://)
     * @param array<string, mixed> $options Array of options and HTML attributes.
     * @return string An `<a>` element.
     * @link https://book.cakephp.org/5/en/views/helpers/html.html#creating-links
     */
    public function link(HtmlStringable|array|string $title, array|string|null $url = null, array $options = []): string
    {
        if ($title instanceof HtmlStringable && $url === null) {
            throw new InvalidArgumentException('An explicit URL is required for trusted HTML link content.');
        }

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

        if ($title instanceof HtmlStringable) {
            $title = (string)$title;
        } elseif ($escapeTitle === true) {
            $title = h($title);
        } elseif (is_string($escapeTitle)) {
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
     *   over value of `escape`.
     * - `confirm` JavaScript confirmation message.
     *
     * @param string $title The content to be wrapped by `<a>` tags.
     * @param string $path Cake-relative route path.
     * @param array $params An array specifying any additional parameters.
     *   Can be also any special parameters supported by `Router::url()`.
     * @param array<string, mixed> $options Array of options and HTML attributes.
     * @return string An `<a>` element.
     * @see \Cake\Routing\Router::pathUrl()
     * @link https://book.cakephp.org/5/en/views/helpers/html.html#creating-links-from-route-paths
     */
    public function linkFromPath(
        HtmlStringable|string $title,
        string $path,
        array $params = [],
        array $options = [],
    ): string {
        return $this->link($title, ['_path' => $path] + $params, $options);
    }

    /**
     * Returns a formatted block tag, i.e DIV, SPAN, P.
     *
     * ### Options
     *
     * - `escape` Whether the contents should be html_entity escaped.
     *
     * @param string $name Tag name.
     * @param string|null $text String content that will appear inside the HTML element.
     *   If null, only a start tag will be printed
     * @param array<string, mixed> $options Additional HTML attributes of the HTML tag, see above.
     * @return string The formatted tag element
     */
    public function tag(string $name, HtmlStringable|string|null $text = null, array $options = []): string
    {
        if (isset($options['escape']) && $options['escape']) {
            $text = $text instanceof HtmlStringable ? (string)$text : h($text);
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
     * - `escape` Whether the contents should be html_entity escaped.
     *
     * @param string|null $class CSS class name of the div element.
     * @param string|null $text String content that will appear inside the div element.
     *   If null, only a start tag will be printed
     * @param array<string, mixed> $options Additional HTML attributes of the DIV tag
     * @return string The formatted DIV element
     */
    public function div(?string $class = null, HtmlStringable|string|null $text = null, array $options = []): string
    {
        if ($class) {
            $options['class'] = $class;
        }

        return $this->tag('div', $text, $options);
    }

    /**
     * Returns a formatted P tag.
     *
     * ### Options
     *
     * - `escape` Whether the contents should be html_entity escaped.
     *
     * @param string|null $class CSS class name of the p element.
     * @param string|null $text String content that will appear inside the p element.
     * @param array<string, mixed> $options Additional HTML attributes of the P tag
     * @return string The formatted P element
     */
    public function para(?string $class, HtmlStringable|string|null $text, array $options = []): string
    {
        if (!empty($options['escape'])) {
            $text = $text instanceof HtmlStringable ? (string)$text : h($text);
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
}
