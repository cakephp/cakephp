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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n\Parser;

/**
 * Parses file in PO format
 *
 * @copyright Copyright (c) 2010, Union of RAD http://union-of-rad.org (http://lithify.me/)
 * @copyright Copyright (c) 2012, Clemens Tolboom
 * @copyright Copyright (c) 2014, Fabien Potencier https://github.com/symfony/Translation/blob/master/LICENSE
 */
class PoFileParser
{

    /**
     * Parses portable object (PO) format.
     *
     * From http://www.gnu.org/software/gettext/manual/gettext.html#PO-Files
     * we should be able to parse files having:
     *
     * white-space
     * #  translator-comments
     * #. extracted-comments
     * #: reference...
     * #, flag...
     * #| msgid previous-untranslated-string
     * msgid untranslated-string
     * msgstr translated-string
     *
     * extra or different lines are:
     *
     * #| msgctxt previous-context
     * #| msgid previous-untranslated-string
     * msgctxt context
     *
     * #| msgid previous-untranslated-string-singular
     * #| msgid_plural previous-untranslated-string-plural
     * msgid untranslated-string-singular
     * msgid_plural untranslated-string-plural
     * msgstr[0] translated-string-case-0
     * ...
     * msgstr[N] translated-string-case-n
     *
     * The definition states:
     * - white-space and comments are optional.
     * - msgid "" that an empty singleline defines a header.
     *
     * This parser sacrifices some features of the reference implementation the
     * differences to that implementation are as follows.
     * - No support for comments spanning multiple lines.
     * - Translator and extracted comments are treated as being the same type.
     * - Message IDs are allowed to have other encodings as just US-ASCII.
     *
     * Items with an empty id are ignored.
     *
     * @param string $resource The file name to parse
     *
     * @return array
     */
    public function parse($resource)
    {
        $stream = fopen($resource, 'r');

        $defaults = [
            'ids' => [],
            'translated' => null
        ];

        $messages = [];
        $item = $defaults;

        while ($line = fgets($stream)) {
            $line = trim($line);

            if ($line === '') {
                // Whitespace indicated current item is done
                $this->_addMessage($messages, $item);
                $item = $defaults;
            } elseif (substr($line, 0, 7) === 'msgid "') {
                // We start a new msg so save previous
                $this->_addMessage($messages, $item);
                $item['ids']['singular'] = substr($line, 7, -1);
            } elseif (substr($line, 0, 8) === 'msgstr "') {
                $item['translated'] = substr($line, 8, -1);
            } elseif (substr($line, 0, 9) === 'msgctxt "') {
                $item['context'] = substr($line, 9, -1);
            } elseif ($line[0] === '"') {
                $continues = isset($item['translated']) ? 'translated' : 'ids';

                if (is_array($item[$continues])) {
                    end($item[$continues]);
                    $item[$continues][key($item[$continues])] .= substr($line, 1, -1);
                } else {
                    $item[$continues] .= substr($line, 1, -1);
                }
            } elseif (substr($line, 0, 14) === 'msgid_plural "') {
                $item['ids']['plural'] = substr($line, 14, -1);
            } elseif (substr($line, 0, 7) === 'msgstr[') {
                $size = strpos($line, ']');
                $item['translated'][(int)substr($line, 7, 1)] = substr($line, $size + 3, -1);
            }
        }
        // save last item
        $this->_addMessage($messages, $item);
        fclose($stream);

        return $messages;
    }

    /**
     * Saves a translation item to the messages.
     *
     * @param array &$messages The messages array being collected from the file
     * @param array $item The current item being inspected
     * @return void
     */
    protected function _addMessage(array &$messages, array $item)
    {
        if (empty($item['ids']['singular']) && empty($item['ids']['plural'])) {
            return;
        }

        $singular = stripcslashes($item['ids']['singular']);
        $context = isset($item['context']) ? $item['context'] : null;
        $translation = $item['translated'];

        if (is_array($translation)) {
            $translation = $translation[0];
        }

        $translation = stripcslashes($translation);

        if ($context) {
            $messages[$singular]['_context'][$context] = $translation;
        } else {
            $messages[$singular] = $translation;
        }

        if (isset($item['ids']['plural'])) {
            $plurals = $item['translated'];
            // PO are by definition indexed so sort by index.
            ksort($plurals);

            // Make sure every index is filled.
            end($plurals);
            $count = key($plurals);

            // Fill missing spots with an empty string.
            $empties = array_fill(0, $count + 1, '');
            $plurals += $empties;
            ksort($plurals);

            $plurals = array_map('stripcslashes', $plurals);
            $key = stripcslashes($item['ids']['plural']);

            if ($context) {
                $messages[$key]['_context'][$context] = $plurals;
            } else {
                $messages[$key] = $plurals;
            }
        }
    }
}
