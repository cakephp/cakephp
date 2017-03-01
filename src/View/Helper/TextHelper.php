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
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Core\App;
use Cake\Core\Exception\Exception;
use Cake\View\Helper;
use Cake\View\View;

/**
 * Text helper library.
 *
 * Text manipulations: Highlight, excerpt, truncate, strip of links, convert email addresses to mailto: links...
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @link http://book.cakephp.org/3.0/en/views/helpers/text.html
 * @see \Cake\Utility\Text
 */
class TextHelper extends Helper
{

    /**
     * helpers
     *
     * @var array
     */
    public $helpers = ['Html'];

    /**
     * Default config for this class
     *
     * @var array
     */
    protected $_defaultConfig = [
        'engine' => 'Cake\Utility\Text'
    ];

    /**
     * An array of md5sums and their contents.
     * Used when inserting links into text.
     *
     * @var array
     */
    protected $_placeholders = [];

    /**
     * String utility instance
     *
     * @var \stdClass
     */
    protected $_engine;

    /**
     * Constructor
     *
     * ### Settings:
     *
     * - `engine` Class name to use to replace String functionality.
     *            The class needs to be placed in the `Utility` directory.
     *
     * @param \Cake\View\View $View the view object the helper is attached to.
     * @param array $config Settings array Settings array
     * @throws \Cake\Core\Exception\Exception when the engine class could not be found.
     */
    public function __construct(View $View, array $config = [])
    {
        parent::__construct($View, $config);

        $config = $this->_config;
        $engineClass = App::className($config['engine'], 'Utility');
        if ($engineClass) {
            $this->_engine = new $engineClass($config);
        } else {
            throw new Exception(sprintf('Class for %s could not be found', $config['engine']));
        }
    }

    /**
     * Call methods from String utility class
     *
     * @param string $method Method to invoke
     * @param array $params Array of params for the method.
     * @return mixed Whatever is returned by called method, or false on failure
     */
    public function __call($method, $params)
    {
        return call_user_func_array([$this->_engine, $method], $params);
    }

    /**
     * Adds links (<a href=....) to a given text, by finding text that begins with
     * strings like http:// and ftp://.
     *
     * ### Options
     *
     * - `escape` Control HTML escaping of input. Defaults to true.
     *
     * @param string $text Text
     * @param array $options Array of HTML options, and options listed above.
     * @return string The text with links
     * @link http://book.cakephp.org/3.0/en/views/helpers/text.html#linking-urls
     */
    public function autoLinkUrls($text, array $options = [])
    {
        $this->_placeholders = [];
        $options += ['escape' => true];

        $pattern = '/(?:(?<!href="|src="|">)
            (?>
                (
                    (?<left>[\[<(]) # left paren,brace
                    (?>
                        # Lax match URL
                        (?<url>(?:https?|ftp|nntp):\/\/[\p{L}0-9.\-_:]+(?:[\/?][\p{L}0-9.\-_:\/?=&>\[\]()#@\+]+)?)
                        (?<right>[\])>]) # right paren,brace
                    )
                )
                |
                (?<url_bare>(?P>url)) # A bare URL. Use subroutine
            )
            )/ixu';

        $text = preg_replace_callback(
            $pattern,
            [&$this, '_insertPlaceHolder'],
            $text
        );
        $text = preg_replace_callback(
            '#(?<!href="|">)(?<!\b[[:punct:]])(?<!http://|https://|ftp://|nntp://)www\.[^\s\n\%\ <]+[^\s<\n\%\,\.\ <](?<!\))#i',
            [&$this, '_insertPlaceHolder'],
            $text
        );
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
    protected function _insertPlaceHolder($matches)
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
        $key = md5($match);
        $this->_placeholders[$key] = [
            'content' => $match,
            'envelope' => $envelope
        ];

        return $key;
    }

    /**
     * Replace placeholders with links.
     *
     * @param string $text The text to operate on.
     * @param array $htmlOptions The options for the generated links.
     * @return string The text with links inserted.
     */
    protected function _linkUrls($text, $htmlOptions)
    {
        $replace = [];
        foreach ($this->_placeholders as $hash => $content) {
            $link = $url = $content['content'];
            $envelope = $content['envelope'];
            if (!preg_match('#^[a-z]+\://#i', $url)) {
                $url = 'http://' . $url;
            }
            $replace[$hash] = $envelope[0] . $this->Html->link($link, $url, $htmlOptions) . $envelope[1];
        }

        return strtr($text, $replace);
    }

    /**
     * Links email addresses
     *
     * @param string $text The text to operate on
     * @param array $options An array of options to use for the HTML.
     * @return string
     * @see \Cake\View\Helper\TextHelper::autoLinkEmails()
     */
    protected function _linkEmails($text, $options)
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
     * Adds email links (<a href="mailto:....) to a given text.
     *
     * ### Options
     *
     * - `escape` Control HTML escaping of input. Defaults to true.
     *
     * @param string $text Text
     * @param array $options Array of HTML options, and options listed above.
     * @return string The text with links
     * @link http://book.cakephp.org/3.0/en/views/helpers/text.html#linking-email-addresses
     */
    public function autoLinkEmails($text, array $options = [])
    {
        $options += ['escape' => true];
        $this->_placeholders = [];

        $atom = '[\p{L}0-9!#$%&\'*+\/=?^_`{|}~-]';
        $text = preg_replace_callback(
            '/(?<=\s|^|\(|\>|\;)(' . $atom . '*(?:\.' . $atom . '+)*@[\p{L}0-9-]+(?:\.[\p{L}0-9-]+)+)/ui',
            [&$this, '_insertPlaceholder'],
            $text
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
     * @param array $options Array of HTML options, and options listed above.
     * @return string The text with links
     * @link http://book.cakephp.org/3.0/en/views/helpers/text.html#linking-both-urls-and-email-addresses
     */
    public function autoLink($text, array $options = [])
    {
        $text = $this->autoLinkUrls($text, $options);

        return $this->autoLinkEmails($text, ['escape' => false] + $options);
    }

    /**
     * Highlights a given phrase in a text. You can specify any expression in highlighter that
     * may include the \1 expression to include the $phrase found.
     *
     * @param string $text Text to search the phrase in
     * @param string $phrase The phrase that will be searched
     * @param array $options An array of HTML attributes and options.
     * @return string The highlighted text
     * @see \Cake\Utility\Text::highlight()
     * @link http://book.cakephp.org/3.0/en/views/helpers/text.html#highlighting-substrings
     */
    public function highlight($text, $phrase, array $options = [])
    {
        return $this->_engine->highlight($text, $phrase, $options);
    }

    /**
     * Formats paragraphs around given text for all line breaks
     *  <br /> added for single line return
     *  <p> added for double line return
     *
     * @param string $text Text
     * @return string The text with proper <p> and <br /> tags
     * @link http://book.cakephp.org/3.0/en/views/helpers/text.html#converting-text-into-paragraphs
     */
    public function autoParagraph($text)
    {
        if (trim($text) !== '') {
            $text = preg_replace('|<br[^>]*>\s*<br[^>]*>|i', "\n\n", $text . "\n");
            $text = preg_replace("/\n\n+/", "\n\n", str_replace(["\r\n", "\r"], "\n", $text));
            $texts = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
            $text = '';
            foreach ($texts as $txt) {
                $text .= '<p>' . nl2br(trim($txt, "\n")) . "</p>\n";
            }
            $text = preg_replace('|<p>\s*</p>|', '', $text);
        }

        return $text;
    }

    /**
     * Strips given text of all links (<a href=....)
     *
     * @param string $text Text
     * @return string The text without links
     * @see \Cake\Utility\Text::stripLinks()
     * @link http://book.cakephp.org/3.0/en/views/helpers/text.html#removing-links
     */
    public function stripLinks($text)
    {
        return $this->_engine->stripLinks($text);
    }

    /**
     * Truncates text.
     *
     * Cuts a string to the length of $length and replaces the last characters
     * with the ellipsis if the text is longer than length.
     *
     * ### Options:
     *
     * - `ellipsis` Will be used as Ending and appended to the trimmed string
     * - `exact` If false, $text will not be cut mid-word
     * - `html` If true, HTML tags would be handled correctly
     *
     * @param string $text String to truncate.
     * @param int $length Length of returned string, including ellipsis.
     * @param array $options An array of HTML attributes and options.
     * @return string Trimmed string.
     * @see \Cake\Utility\Text::truncate()
     * @link http://book.cakephp.org/3.0/en/views/helpers/text.html#truncating-text
     */
    public function truncate($text, $length = 100, array $options = [])
    {
        return $this->_engine->truncate($text, $length, $options);
    }

    /**
     * Truncates text starting from the end.
     *
     * Cuts a string to the length of $length and replaces the first characters
     * with the ellipsis if the text is longer than length.
     *
     * ### Options:
     *
     * - `ellipsis` Will be used as Beginning and prepended to the trimmed string
     * - `exact` If false, $text will not be cut mid-word
     *
     * @param string $text String to truncate.
     * @param int $length Length of returned string, including ellipsis.
     * @param array $options An array of HTML attributes and options.
     * @return string Trimmed string.
     * @see \Cake\Utility\Text::tail()
     * @link http://book.cakephp.org/3.0/en/views/helpers/text.html#truncating-the-tail-of-a-string
     */
    public function tail($text, $length = 100, array $options = [])
    {
        return $this->_engine->tail($text, $length, $options);
    }

    /**
     * Extracts an excerpt from the text surrounding the phrase with a number of characters on each side
     * determined by radius.
     *
     * @param string $text String to search the phrase in
     * @param string $phrase Phrase that will be searched for
     * @param int $radius The amount of characters that will be returned on each side of the founded phrase
     * @param string $ending Ending that will be appended
     * @return string Modified string
     * @see \Cake\Utility\Text::excerpt()
     * @link http://book.cakephp.org/3.0/en/views/helpers/text.html#extracting-an-excerpt
     */
    public function excerpt($text, $phrase, $radius = 100, $ending = '...')
    {
        return $this->_engine->excerpt($text, $phrase, $radius, $ending);
    }

    /**
     * Creates a comma separated list where the last two items are joined with 'and', forming natural language.
     *
     * @param array $list The list to be joined.
     * @param string|null $and The word used to join the last and second last items together with. Defaults to 'and'.
     * @param string $separator The separator used to join all the other items together. Defaults to ', '.
     * @return string The glued together string.
     * @see \Cake\Utility\Text::toList()
     * @link http://book.cakephp.org/3.0/en/views/helpers/text.html#converting-an-array-to-sentence-form
     */
    public function toList($list, $and = null, $separator = ', ')
    {
        return $this->_engine->toList($list, $and, $separator);
    }

    /**
     * Event listeners.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [];
    }
}
