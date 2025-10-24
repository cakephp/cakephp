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
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Utility\Security;
use Cake\Utility\Text;
use Cake\View\Helper;
use function Cake\Core\h;

/**
 * Text helper library.
 *
 * Text manipulations: Highlight, excerpt, truncate, strip of links, convert email addresses to mailto: links...
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @method string excerpt(string $text, string $phrase, int $radius = 100, string $ending = '…') See Text::excerpt()
 * @method string highlight(string $text, array|string $phrase, array $options = []) See Text::highlight()
 * @method string slug(string $string, array|string $options = []) See Text::slug()
 * @method string tail(string $text, int $length = 100, array $options = []) See Text::tail()
 * @method string toList(array $list, ?string $and = null, string $separator = ', ') See Text::toList()
 * @method string truncate(string $text, int $length = 100, array $options = []) See Text::truncate()
 * @link https://book.cakephp.org/5/en/views/helpers/text.html
 * @see \Cake\Utility\Text
 */
class TextHelper extends Helper
{
    /**
     * helpers
     *
     * @var array
     */
    protected array $helpers = ['Html'];

    /**
     * An array of hashes and their contents.
     * Used when inserting links into text.
     *
     * @var array<string, array>
     */
    protected array $_placeholders = [];

    /**
     * Call methods from String utility class
     *
     * @param string $method Method to invoke
     * @param array $params Array of params for the method.
     * @return mixed Whatever is returned by called method, or false on failure
     */
    public function __call(string $method, array $params): mixed
    {
        return Text::{$method}(...$params);
    }

    /**
     * Adds links (<a href=....) to a given text, by finding text that begins with
     * strings like http:// and ftp://.
     *
     * ### Options
     *
     * - `escape` Control HTML escaping of input. Defaults to true.
     * - `stripProtocol` Strips http:// and https:// from the beginning of the link. Default off.
     * - `maxLength` The maximum length of the link label. Default off.
     * - `ellipsis` The string to append to the end of the link label. Defaults to UTF8 version.
     *
     * @param string $text Text
     * @param array<string, mixed> $options Array of HTML options, and options listed above.
     * @return string The text with links
     * @link https://book.cakephp.org/5/en/views/helpers/text.html#linking-urls
     */
    public function autoLinkUrls(string $text, array $options = []): string
    {
        $this->_placeholders = [];
        $options += ['escape' => true];

        // phpcs:disable Generic.Files.LineLength
        $pattern = '/(?:(?<!href="|src="|">)
            (?>
                (
                    (?<left>[\[<(]) # left paren,brace
                    (?>
                        # Lax match URL
                        (?<url>(?:https?|ftp|nntp):\/\/[\p{L}0-9.\-_:]+(?:[\/?][\p{L}0-9.\-_:\/?=&>\[\]\(\)\#\@\+~!;,%]+[^-_:?>\[\(\@\+~!;<,.%\s])?)
                        (?<right>[\])>]) # right paren,brace
                    )
                )
                |
                (?<url_bare>(?P>url)) # A bare URL. Use subroutine
            )
            )/ixu';
        // phpcs:enable Generic.Files.LineLength

        $text = (string)preg_replace_callback(
            $pattern,
            [&$this, '_insertPlaceHolder'],
            $text,
        );
        // phpcs:disable Generic.Files.LineLength
        $text = preg_replace_callback(
            '#(?<!href="|">)(?<!\b[[:punct:]])(?<!http://|https://|ftp://|nntp://)www\.[^\s\n\%\ <]+[^\s<\n\%\,\.\ ](?<!\))#i',
            [&$this, '_insertPlaceHolder'],
            $text,
        );
        // phpcs:enable Generic.Files.LineLength
        if ($options['escape']) {
            $text = h($text);
        }

        return $this->_linkUrls($text, $options);
    }

    /**
     * Saves the placeholder for a string, for later use. This gets around double
     * escaping content in URL's.
     *
     * @param array $matches An array of regexp matches.
     * @return string Replaced values.
     */
    protected function _insertPlaceHolder(array $matches): string
    {
        $match = $matches[0];
        $envelope = ['', ''];
        if (isset($matches['url'])) {
            $match = $matches['url'];
            $envelope = [$matches['left'], $matches['right']];
        }
        if (isset($matches['url_bare'])) {
            $match = $matches['url_bare'];
        }
        $key = hash_hmac('sha1', $match, Security::getSalt());
        $this->_placeholders[$key] = [
            'content' => $match,
            'envelope' => $envelope,
        ];

        return $key;
    }

    /**
     * Replace placeholders with links.
     *
     * @param string $text The text to operate on.
     * @param array<string, mixed> $htmlOptions The options for the generated links.
     * @return string The text with links inserted.
     */
    protected function _linkUrls(string $text, array $htmlOptions): string
    {
        $replace = [];
        foreach ($this->_placeholders as $hash => $content) {
            $link = $content['content'];
            $url = $content['content'];
            $envelope = $content['envelope'];
            if (!preg_match('#^[a-z]+\://#i', $url)) {
                $url = 'http://' . $url;
            }

            $linkOptions = $htmlOptions;
            unset($htmlOptions['maxLength'], $htmlOptions['stripProtocol'], $htmlOptions['ellipsis']);
            $link = $this->_prepareLinkLabel($link, $linkOptions);

            $replace[$hash] = $envelope[0] . $this->Html->link($link, $url, $htmlOptions) . $envelope[1];
        }

        return strtr($text, $replace);
    }

    /**
     * Prepares the link label.
     *
     * @param string $name Link label.
     * @param array<string, mixed> $options The options for the generated link label.
     * @return string Modified link label.
     */
    protected function _prepareLinkLabel(string $name, array $options): string
    {
        if (isset($options['stripProtocol']) && $options['stripProtocol'] === true) {
            $name = (string)preg_replace('(^https?://)', '', $name);
        }
        if (!empty($options['maxLength'])) {
            $ellipsis = $options['ellipsis'] ?? '…';
            $length = $options['maxLength'] - mb_strlen($ellipsis);
            if (mb_strlen($name) > $length) {
                $name = mb_substr($name, 0, $length);
                $name .= $ellipsis;
            }
        }

        return $name;
    }

    /**
     * Links email addresses
     *
     * @param string $text The text to operate on
     * @param array<string, mixed> $options An array of options to use for the HTML.
     * @return string
     * @see \Cake\View\Helper\TextHelper::autoLinkEmails()
     */
    protected function _linkEmails(string $text, array $options): string
    {
        $replace = [];
        foreach ($this->_placeholders as $hash => $content) {
            $url = $content['content'];
            $envelope = $content['envelope'];
            $replace[$hash] = $envelope[0] . $this->Html->link($url, 'mailto:' . $url, $options) . $envelope[1];
        }

        return strtr($text, $replace);
    }

    /**
     * Adds email links (<a href="mailto:....") to a given text.
     *
     * ### Options
     *
     * - `escape` Control HTML escaping of input. Defaults to true.
     *
     * @param string $text Text
     * @param array<string, mixed> $options Array of HTML options, and options listed above.
     * @return string The text with links
     * @link https://book.cakephp.org/5/en/views/helpers/text.html#linking-email-addresses
     */
    public function autoLinkEmails(string $text, array $options = []): string
    {
        $options += ['escape' => true];
        $this->_placeholders = [];

        $atom = '[\p{L}0-9!#$%&\'*+\/=?^_`{|}~-]';
        $text = preg_replace_callback(
            '/(?<=\s|^|\(|\>|\;)(' . $atom . '*(?:\.' . $atom . '+)*@[\p{L}0-9-]+(?:\.[\p{L}0-9-]+)+)/ui',
            [&$this, '_insertPlaceholder'],
            $text,
        );
        if ($options['escape']) {
            $text = h($text);
        }

        return $this->_linkEmails($text, $options);
    }

    /**
     * Convert all links and email addresses to HTML links.
     *
     * ### Options
     *
     * - `escape` Control HTML escaping of input. Defaults to true.
     *
     * @param string $text Text
     * @param array<string, mixed> $options Array of HTML options, and options listed above.
     * @return string The text with links
     * @link https://book.cakephp.org/5/en/views/helpers/text.html#linking-both-urls-and-email-addresses
     */
    public function autoLink(string $text, array $options = []): string
    {
        $text = $this->autoLinkUrls($text, $options);

        return $this->autoLinkEmails($text, ['escape' => false] + $options);
    }

    /**
     * Formats paragraphs around given text for all line breaks
     *  <br> added for single line return
     *  <p> added for double line return
     *
     * @param string|null $text Text
     * @return string The text with proper <p> and <br> tags
     * @link https://book.cakephp.org/5/en/views/helpers/text.html#converting-text-into-paragraphs
     */
    public function autoParagraph(?string $text): string
    {
        $text ??= '';
        if (trim($text) !== '') {
            $text = (string)preg_replace('|<br[^>]*>\s*<br[^>]*>|i', "\n\n", $text . "\n");
            $text = (string)preg_replace("/\n\n+/", "\n\n", str_replace(["\r\n", "\r"], "\n", $text));
            $texts = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $text = '';
            foreach ($texts as $txt) {
                $text .= '<p>' . nl2br(trim($txt, "\n")) . "</p>\n";
            }
            $text = (string)preg_replace('|<p>\s*</p>|', '', $text);
        }

        return $text;
    }

    /**
     * Event listeners.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [];
    }
}
