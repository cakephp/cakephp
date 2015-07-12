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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility;

use InvalidArgumentException;

/**
 * Text handling methods.
 *
 */
class Text
{

    /**
     * Generate a random UUID version 4
     *
     * Warning: This method should not be used as a random seed for any cryptographic operations.
     * Instead you should use the openssl or mcrypt extensions.
     *
     * @see http://www.ietf.org/rfc/rfc4122.txt
     * @return string RFC 4122 UUID
     * @copyright Matt Farina MIT License https://github.com/lootils/uuid/blob/master/LICENSE
     */
    public static function uuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            // 16 bits for "time_mid"
            mt_rand(0, 65535),
            // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
            mt_rand(0, 4095) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535)
        );
    }

    /**
     * Tokenizes a string using $separator, ignoring any instance of $separator that appears between
     * $leftBound and $rightBound.
     *
     * @param string $data The data to tokenize.
     * @param string $separator The token to split the data on.
     * @param string $leftBound The left boundary to ignore separators in.
     * @param string $rightBound The right boundary to ignore separators in.
     * @return mixed Array of tokens in $data or original input if empty.
     */
    public static function tokenize($data, $separator = ',', $leftBound = '(', $rightBound = ')')
    {
        if (empty($data)) {
            return [];
        }

        $depth = 0;
        $offset = 0;
        $buffer = '';
        $results = [];
        $length = mb_strlen($data);
        $open = false;

        while ($offset <= $length) {
            $tmpOffset = -1;
            $offsets = [
                mb_strpos($data, $separator, $offset),
                mb_strpos($data, $leftBound, $offset),
                mb_strpos($data, $rightBound, $offset)
            ];
            for ($i = 0; $i < 3; $i++) {
                if ($offsets[$i] !== false && ($offsets[$i] < $tmpOffset || $tmpOffset == -1)) {
                    $tmpOffset = $offsets[$i];
                }
            }
            if ($tmpOffset !== -1) {
                $buffer .= mb_substr($data, $offset, ($tmpOffset - $offset));
                $char = mb_substr($data, $tmpOffset, 1);
                if (!$depth && $char === $separator) {
                    $results[] = $buffer;
                    $buffer = '';
                } else {
                    $buffer .= $char;
                }
                if ($leftBound !== $rightBound) {
                    if ($char === $leftBound) {
                        $depth++;
                    }
                    if ($char === $rightBound) {
                        $depth--;
                    }
                } else {
                    if ($char === $leftBound) {
                        if (!$open) {
                            $depth++;
                            $open = true;
                        } else {
                            $depth--;
                        }
                    }
                }
                $offset = ++$tmpOffset;
            } else {
                $results[] = $buffer . mb_substr($data, $offset);
                $offset = $length + 1;
            }
        }
        if (empty($results) && !empty($buffer)) {
            $results[] = $buffer;
        }

        if (!empty($results)) {
            return array_map('trim', $results);
        }

        return [];
    }

    /**
     * Replaces variable placeholders inside a $str with any given $data. Each key in the $data array
     * corresponds to a variable placeholder name in $str.
     * Example:
     * ```
     * Text::insert(':name is :age years old.', ['name' => 'Bob', '65']);
     * ```
     * Returns: Bob is 65 years old.
     *
     * Available $options are:
     *
     * - before: The character or string in front of the name of the variable placeholder (Defaults to `:`)
     * - after: The character or string after the name of the variable placeholder (Defaults to null)
     * - escape: The character or string used to escape the before character / string (Defaults to `\`)
     * - format: A regex to use for matching variable placeholders. Default is: `/(?<!\\)\:%s/`
     *   (Overwrites before, after, breaks escape / clean)
     * - clean: A boolean or array with instructions for Text::cleanInsert
     *
     * @param string $str A string containing variable placeholders
     * @param array $data A key => val array where each key stands for a placeholder variable name
     *     to be replaced with val
     * @param array $options An array of options, see description above
     * @return string
     */
    public static function insert($str, $data, array $options = [])
    {
        $defaults = [
            'before' => ':', 'after' => null, 'escape' => '\\', 'format' => null, 'clean' => false
        ];
        $options += $defaults;
        $format = $options['format'];
        $data = (array)$data;
        if (empty($data)) {
            return ($options['clean']) ? static::cleanInsert($str, $options) : $str;
        }

        if (!isset($format)) {
            $format = sprintf(
                '/(?<!%s)%s%%s%s/',
                preg_quote($options['escape'], '/'),
                str_replace('%', '%%', preg_quote($options['before'], '/')),
                str_replace('%', '%%', preg_quote($options['after'], '/'))
            );
        }

        if (strpos($str, '?') !== false && is_numeric(key($data))) {
            $offset = 0;
            while (($pos = strpos($str, '?', $offset)) !== false) {
                $val = array_shift($data);
                $offset = $pos + strlen($val);
                $str = substr_replace($str, $val, $pos, 1);
            }
            return ($options['clean']) ? static::cleanInsert($str, $options) : $str;
        }

        asort($data);

        $dataKeys = array_keys($data);
        $hashKeys = array_map('crc32', $dataKeys);
        $tempData = array_combine($dataKeys, $hashKeys);
        krsort($tempData);

        foreach ($tempData as $key => $hashVal) {
            $key = sprintf($format, preg_quote($key, '/'));
            $str = preg_replace($key, $hashVal, $str);
        }
        $dataReplacements = array_combine($hashKeys, array_values($data));
        foreach ($dataReplacements as $tmpHash => $tmpValue) {
            $tmpValue = (is_array($tmpValue)) ? '' : $tmpValue;
            $str = str_replace($tmpHash, $tmpValue, $str);
        }

        if (!isset($options['format']) && isset($options['before'])) {
            $str = str_replace($options['escape'] . $options['before'], $options['before'], $str);
        }
        return ($options['clean']) ? static::cleanInsert($str, $options) : $str;
    }

    /**
     * Cleans up a Text::insert() formatted string with given $options depending on the 'clean' key in
     * $options. The default method used is text but html is also available. The goal of this function
     * is to replace all whitespace and unneeded markup around placeholders that did not get replaced
     * by Text::insert().
     *
     * @param string $str String to clean.
     * @param array $options Options list.
     * @return string
     * @see \Cake\Utility\Text::insert()
     */
    public static function cleanInsert($str, array $options)
    {
        $clean = $options['clean'];
        if (!$clean) {
            return $str;
        }
        if ($clean === true) {
            $clean = ['method' => 'text'];
        }
        if (!is_array($clean)) {
            $clean = ['method' => $options['clean']];
        }
        switch ($clean['method']) {
            case 'html':
                $clean += [
                    'word' => '[\w,.]+',
                    'andText' => true,
                    'replacement' => '',
                ];
                $kleenex = sprintf(
                    '/[\s]*[a-z]+=(")(%s%s%s[\s]*)+\\1/i',
                    preg_quote($options['before'], '/'),
                    $clean['word'],
                    preg_quote($options['after'], '/')
                );
                $str = preg_replace($kleenex, $clean['replacement'], $str);
                if ($clean['andText']) {
                    $options['clean'] = ['method' => 'text'];
                    $str = static::cleanInsert($str, $options);
                }
                break;
            case 'text':
                $clean += [
                    'word' => '[\w,.]+',
                    'gap' => '[\s]*(?:(?:and|or)[\s]*)?',
                    'replacement' => '',
                ];

                $kleenex = sprintf(
                    '/(%s%s%s%s|%s%s%s%s)/',
                    preg_quote($options['before'], '/'),
                    $clean['word'],
                    preg_quote($options['after'], '/'),
                    $clean['gap'],
                    $clean['gap'],
                    preg_quote($options['before'], '/'),
                    $clean['word'],
                    preg_quote($options['after'], '/')
                );
                $str = preg_replace($kleenex, $clean['replacement'], $str);
                break;
        }
        return $str;
    }

    /**
     * Wraps text to a specific width, can optionally wrap at word breaks.
     *
     * ### Options
     *
     * - `width` The width to wrap to. Defaults to 72.
     * - `wordWrap` Only wrap on words breaks (spaces) Defaults to true.
     * - `indent` String to indent with. Defaults to null.
     * - `indentAt` 0 based index to start indenting at. Defaults to 0.
     *
     * @param string $text The text to format.
     * @param array|int $options Array of options to use, or an integer to wrap the text to.
     * @return string Formatted text.
     */
    public static function wrap($text, $options = [])
    {
        if (is_numeric($options)) {
            $options = ['width' => $options];
        }
        $options += ['width' => 72, 'wordWrap' => true, 'indent' => null, 'indentAt' => 0];
        if ($options['wordWrap']) {
            $wrapped = self::wordWrap($text, $options['width'], "\n");
        } else {
            $wrapped = trim(chunk_split($text, $options['width'] - 1, "\n"));
        }
        if (!empty($options['indent'])) {
            $chunks = explode("\n", $wrapped);
            for ($i = $options['indentAt'], $len = count($chunks); $i < $len; $i++) {
                $chunks[$i] = $options['indent'] . $chunks[$i];
            }
            $wrapped = implode("\n", $chunks);
        }
        return $wrapped;
    }

    /**
     * Wraps a complete block of text to a specific width, can optionally wrap
     * at word breaks.
     *
     * ### Options
     *
     * - `width` The width to wrap to. Defaults to 72.
     * - `wordWrap` Only wrap on words breaks (spaces) Defaults to true.
     * - `indent` String to indent with. Defaults to null.
     * - `indentAt` 0 based index to start indenting at. Defaults to 0.
     *
     * @param string $text The text to format.
     * @param array|int $options Array of options to use, or an integer to wrap the text to.
     * @return string Formatted text.
     */
    public static function wrapBlock($text, $options = [])
    {
        if (is_numeric($options)) {
            $options = ['width' => $options];
        }
        $options += ['width' => 72, 'wordWrap' => true, 'indent' => null, 'indentAt' => 0];

        if (!empty($options['indentAt']) && $options['indentAt'] === 0) {
            $indentLength = !empty($options['indent']) ? strlen($options['indent']) : 0;
            $options['width'] = $options['width'] - $indentLength;
            return self::wrap($text, $options);
        }

        $wrapped = self::wrap($text, $options);

        if (!empty($options['indent'])) {
            $indentationLength = mb_strlen($options['indent']);
            $chunks = explode("\n", $wrapped);
            $count = count($chunks);
            if ($count < 2) {
                return $wrapped;
            }
            $toRewrap = '';
            for ($i = $options['indentAt']; $i < $count; $i++) {
                $toRewrap .= mb_substr($chunks[$i], $indentationLength) . ' ';
                unset($chunks[$i]);
            }
            $options['width'] -= $indentationLength;
            $options['indentAt'] = 0;
            $rewrapped = self::wrap($toRewrap, $options);
            $newChunks = explode("\n", $rewrapped);

            $chunks = array_merge($chunks, $newChunks);
            $wrapped = implode("\n", $chunks);
        }
        return $wrapped;
    }

    /**
     * Unicode and newline aware version of wordwrap.
     *
     * @param string $text The text to format.
     * @param int $width The width to wrap to. Defaults to 72.
     * @param string $break The line is broken using the optional break parameter. Defaults to '\n'.
     * @param bool $cut If the cut is set to true, the string is always wrapped at the specified width.
     * @return string Formatted text.
     */
    public static function wordWrap($text, $width = 72, $break = "\n", $cut = false)
    {
        $paragraphs = explode($break, $text);
        foreach ($paragraphs as &$paragraph) {
            $paragraph = static::_wordWrap($paragraph, $width, $break, $cut);
        }
        return implode($break, $paragraphs);
    }

    /**
     * Unicode aware version of wordwrap as helper method.
     *
     * @param string $text The text to format.
     * @param int $width The width to wrap to. Defaults to 72.
     * @param string $break The line is broken using the optional break parameter. Defaults to '\n'.
     * @param bool $cut If the cut is set to true, the string is always wrapped at the specified width.
     * @return string Formatted text.
     */
    protected static function _wordWrap($text, $width = 72, $break = "\n", $cut = false)
    {
        if ($cut) {
            $parts = [];
            while (mb_strlen($text) > 0) {
                $part = mb_substr($text, 0, $width);
                $parts[] = trim($part);
                $text = trim(mb_substr($text, mb_strlen($part)));
            }
            return implode($break, $parts);
        }

        $parts = [];
        while (mb_strlen($text) > 0) {
            if ($width >= mb_strlen($text)) {
                $parts[] = trim($text);
                break;
            }

            $part = mb_substr($text, 0, $width);
            $nextChar = mb_substr($text, $width, 1);
            if ($nextChar !== ' ') {
                $breakAt = mb_strrpos($part, ' ');
                if ($breakAt === false) {
                    $breakAt = mb_strpos($text, ' ', $width);
                }
                if ($breakAt === false) {
                    $parts[] = trim($text);
                    break;
                }
                $part = mb_substr($text, 0, $breakAt);
            }

            $part = trim($part);
            $parts[] = $part;
            $text = trim(mb_substr($text, mb_strlen($part)));
        }

        return implode($break, $parts);
    }

    /**
     * Highlights a given phrase in a text. You can specify any expression in highlighter that
     * may include the \1 expression to include the $phrase found.
     *
     * ### Options:
     *
     * - `format` The piece of HTML with that the phrase will be highlighted
     * - `html` If true, will ignore any HTML tags, ensuring that only the correct text is highlighted
     * - `regex` a custom regex rule that is used to match words, default is '|$tag|iu'
     *
     * @param string $text Text to search the phrase in.
     * @param string|array $phrase The phrase or phrases that will be searched.
     * @param array $options An array of HTML attributes and options.
     * @return string The highlighted text
     * @link http://book.cakephp.org/3.0/en/core-libraries/string.html#highlighting-substrings
     */
    public static function highlight($text, $phrase, array $options = [])
    {
        if (empty($phrase)) {
            return $text;
        }

        $defaults = [
            'format' => '<span class="highlight">\1</span>',
            'html' => false,
            'regex' => "|%s|iu"
        ];
        $options += $defaults;
        extract($options);

        if (is_array($phrase)) {
            $replace = [];
            $with = [];

            foreach ($phrase as $key => $segment) {
                $segment = '(' . preg_quote($segment, '|') . ')';
                if ($html) {
                    $segment = "(?![^<]+>)$segment(?![^<]+>)";
                }

                $with[] = (is_array($format)) ? $format[$key] : $format;
                $replace[] = sprintf($options['regex'], $segment);
            }

            return preg_replace($replace, $with, $text);
        }

        $phrase = '(' . preg_quote($phrase, '|') . ')';
        if ($html) {
            $phrase = "(?![^<]+>)$phrase(?![^<]+>)";
        }

        return preg_replace(sprintf($options['regex'], $phrase), $format, $text);
    }

    /**
     * Strips given text of all links (<a href=....).
     *
     * @param string $text Text
     * @return string The text without links
     */
    public static function stripLinks($text)
    {
        return preg_replace('|<a\s+[^>]+>|im', '', preg_replace('|<\/a>|im', '', $text));
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
     * @param array $options An array of options.
     * @return string Trimmed string.
     */
    public static function tail($text, $length = 100, array $options = [])
    {
        $default = [
            'ellipsis' => '...', 'exact' => true
        ];
        $options += $default;
        extract($options);

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        $truncate = mb_substr($text, mb_strlen($text) - $length + mb_strlen($ellipsis));
        if (!$exact) {
            $spacepos = mb_strpos($truncate, ' ');
            $truncate = $spacepos === false ? '' : trim(mb_substr($truncate, $spacepos));
        }

        return $ellipsis . $truncate;
    }

    /**
     * Truncates text.
     *
     * Cuts a string to the length of $length and replaces the last characters
     * with the ellipsis if the text is longer than length.
     *
     * ### Options:
     *
     * - `ellipsis` Will be used as ending and appended to the trimmed string
     * - `exact` If false, $text will not be cut mid-word
     * - `html` If true, HTML tags would be handled correctly
     *
     * @param string $text String to truncate.
     * @param int $length Length of returned string, including ellipsis.
     * @param array $options An array of HTML attributes and options.
     * @return string Trimmed string.
     * @link http://book.cakephp.org/3.0/en/core-libraries/string.html#truncating-text
     */
    public static function truncate($text, $length = 100, array $options = [])
    {
        $default = [
            'ellipsis' => '...', 'exact' => true, 'html' => false
        ];
        if (!empty($options['html']) && strtolower(mb_internal_encoding()) === 'utf-8') {
            $default['ellipsis'] = "\xe2\x80\xa6";
        }
        $options += $default;
        extract($options);

        if ($html) {
            if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            $totalLength = mb_strlen(strip_tags($ellipsis));
            $openTags = [];
            $truncate = '';

            preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
            foreach ($tags as $tag) {
                if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
                    if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
                        array_unshift($openTags, $tag[2]);
                    } elseif (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
                        $pos = array_search($closeTag[1], $openTags);
                        if ($pos !== false) {
                            array_splice($openTags, $pos, 1);
                        }
                    }
                }
                $truncate .= $tag[1];

                $contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
                if ($contentLength + $totalLength > $length) {
                    $left = $length - $totalLength;
                    $entitiesLength = 0;
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entitiesLength <= $left) {
                                $left--;
                                $entitiesLength += mb_strlen($entity[0]);
                            } else {
                                break;
                            }
                        }
                    }

                    $truncate .= mb_substr($tag[3], 0, $left + $entitiesLength);
                    break;
                } else {
                    $truncate .= $tag[3];
                    $totalLength += $contentLength;
                }
                if ($totalLength >= $length) {
                    break;
                }
            }
        } else {
            if (mb_strlen($text) <= $length) {
                return $text;
            }
            $truncate = mb_substr($text, 0, $length - mb_strlen($ellipsis));
        }
        if (!$exact) {
            $spacepos = mb_strrpos($truncate, ' ');
            if ($html) {
                $truncateCheck = mb_substr($truncate, 0, $spacepos);
                $lastOpenTag = mb_strrpos($truncateCheck, '<');
                $lastCloseTag = mb_strrpos($truncateCheck, '>');
                if ($lastOpenTag > $lastCloseTag) {
                    preg_match_all('/<[\w]+[^>]*>/s', $truncate, $lastTagMatches);
                    $lastTag = array_pop($lastTagMatches[0]);
                    $spacepos = mb_strrpos($truncate, $lastTag) + mb_strlen($lastTag);
                }
                $bits = mb_substr($truncate, $spacepos);
                preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
                if (!empty($droppedTags)) {
                    if (!empty($openTags)) {
                        foreach ($droppedTags as $closingTag) {
                            if (!in_array($closingTag[1], $openTags)) {
                                array_unshift($openTags, $closingTag[1]);
                            }
                        }
                    } else {
                        foreach ($droppedTags as $closingTag) {
                            $openTags[] = $closingTag[1];
                        }
                    }
                }
            }
            $truncate = mb_substr($truncate, 0, $spacepos);

            // If truncate still empty, then we don't need to count ellipsis in the cut.
            if (mb_strlen($truncate) === 0) {
                $truncate = mb_substr($text, 0, $length);
            }
        }

        $truncate .= $ellipsis;

        if ($html) {
            foreach ($openTags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }

        return $truncate;
    }

    /**
     * Extracts an excerpt from the text surrounding the phrase with a number of characters on each side
     * determined by radius.
     *
     * @param string $text String to search the phrase in
     * @param string $phrase Phrase that will be searched for
     * @param int $radius The amount of characters that will be returned on each side of the founded phrase
     * @param string $ellipsis Ending that will be appended
     * @return string Modified string
     * @link http://book.cakephp.org/3.0/en/core-libraries/string.html#extracting-an-excerpt
     */
    public static function excerpt($text, $phrase, $radius = 100, $ellipsis = '...')
    {
        if (empty($text) || empty($phrase)) {
            return static::truncate($text, $radius * 2, ['ellipsis' => $ellipsis]);
        }

        $append = $prepend = $ellipsis;

        $phraseLen = mb_strlen($phrase);
        $textLen = mb_strlen($text);

        $pos = mb_strpos(mb_strtolower($text), mb_strtolower($phrase));
        if ($pos === false) {
            return mb_substr($text, 0, $radius) . $ellipsis;
        }

        $startPos = $pos - $radius;
        if ($startPos <= 0) {
            $startPos = 0;
            $prepend = '';
        }

        $endPos = $pos + $phraseLen + $radius;
        if ($endPos >= $textLen) {
            $endPos = $textLen;
            $append = '';
        }

        $excerpt = mb_substr($text, $startPos, $endPos - $startPos);
        $excerpt = $prepend . $excerpt . $append;

        return $excerpt;
    }

    /**
     * Creates a comma separated list where the last two items are joined with 'and', forming natural language.
     *
     * @param array $list The list to be joined.
     * @param string $and The word used to join the last and second last items together with. Defaults to 'and'.
     * @param string $separator The separator used to join all the other items together. Defaults to ', '.
     * @return string The glued together string.
     * @link http://book.cakephp.org/3.0/en/core-libraries/string.html#converting-an-array-to-sentence-form
     */
    public static function toList(array $list, $and = null, $separator = ', ')
    {
        if ($and === null) {
            $and = __d('cake', 'and');
        }
        if (count($list) > 1) {
            return implode($separator, array_slice($list, null, -1)) . ' ' . $and . ' ' . array_pop($list);
        }

        return array_pop($list);
    }

    /**
     * Check if the string contain multibyte characters
     *
     * @param string $string value to test
     * @return bool
     */
    public static function isMultibyte($string)
    {
        $length = strlen($string);

        for ($i = 0; $i < $length; $i++) {
            $value = ord(($string[$i]));
            if ($value > 128) {
                return true;
            }
        }
        return false;
    }

    /**
     * Converts a multibyte character string
     * to the decimal value of the character
     *
     * @param string $string String to convert.
     * @return array
     */
    public static function utf8($string)
    {
        $map = [];

        $values = [];
        $find = 1;
        $length = strlen($string);

        for ($i = 0; $i < $length; $i++) {
            $value = ord($string[$i]);

            if ($value < 128) {
                $map[] = $value;
            } else {
                if (empty($values)) {
                    $find = ($value < 224) ? 2 : 3;
                }
                $values[] = $value;

                if (count($values) === $find) {
                    if ($find == 3) {
                        $map[] = (($values[0] % 16) * 4096) + (($values[1] % 64) * 64) + ($values[2] % 64);
                    } else {
                        $map[] = (($values[0] % 32) * 64) + ($values[1] % 64);
                    }
                    $values = [];
                    $find = 1;
                }
            }
        }
        return $map;
    }

    /**
     * Converts the decimal value of a multibyte character string
     * to a string
     *
     * @param array $array Array
     * @return string
     */
    public static function ascii(array $array)
    {
        $ascii = '';

        foreach ($array as $utf8) {
            if ($utf8 < 128) {
                $ascii .= chr($utf8);
            } elseif ($utf8 < 2048) {
                $ascii .= chr(192 + (($utf8 - ($utf8 % 64)) / 64));
                $ascii .= chr(128 + ($utf8 % 64));
            } else {
                $ascii .= chr(224 + (($utf8 - ($utf8 % 4096)) / 4096));
                $ascii .= chr(128 + ((($utf8 % 4096) - ($utf8 % 64)) / 64));
                $ascii .= chr(128 + ($utf8 % 64));
            }
        }
        return $ascii;
    }

    /**
     * Converts filesize from human readable string to bytes
     *
     * @param string $size Size in human readable string like '5MB', '5M', '500B', '50kb' etc.
     * @param mixed $default Value to be returned when invalid size was used, for example 'Unknown type'
     * @return mixed Number of bytes as integer on success, `$default` on failure if not false
     * @throws \InvalidArgumentException On invalid Unit type.
     * @link http://book.cakephp.org/3.0/en/core-libraries/helpers/text.html
     */
    public static function parseFileSize($size, $default = false)
    {
        if (ctype_digit($size)) {
            return (int)$size;
        }
        $size = strtoupper($size);

        $l = -2;
        $i = array_search(substr($size, -2), ['KB', 'MB', 'GB', 'TB', 'PB']);
        if ($i === false) {
            $l = -1;
            $i = array_search(substr($size, -1), ['K', 'M', 'G', 'T', 'P']);
        }
        if ($i !== false) {
            $size = substr($size, 0, $l);
            return $size * pow(1024, $i + 1);
        }

        if (substr($size, -1) === 'B' && ctype_digit(substr($size, 0, -1))) {
            $size = substr($size, 0, -1);
            return (int)$size;
        }

        if ($default !== false) {
            return $default;
        }
        throw new InvalidArgumentException('No unit type.');
    }
}
