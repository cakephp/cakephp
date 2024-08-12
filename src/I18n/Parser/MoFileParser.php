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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n\Parser;

use Cake\Core\Exception\CakeException;

/**
 * Parses file in MO format
 *
 * @copyright Copyright (c) 2010, Union of RAD http://union-of-rad.org (http://lithify.me/)
 * @copyright Copyright (c) 2014, Fabien Potencier https://github.com/symfony/Translation/blob/master/LICENSE
 */
class MoFileParser
{
    /**
     * Magic used for validating the format of a MO file as well as
     * detecting if the machine used to create that file was little endian.
     *
     * @var int
     */
    public const MO_LITTLE_ENDIAN_MAGIC = 0x950412de;

    /**
     * Magic used for validating the format of a MO file as well as
     * detecting if the machine used to create that file was big endian.
     *
     * @var int
     */
    public const MO_BIG_ENDIAN_MAGIC = 0xde120495;

    /**
     * The size of the header of a MO file in bytes.
     *
     * @var int
     */
    public const MO_HEADER_SIZE = 28;

    /**
     * Parses machine object (MO) format, independent of the machine's endian it
     * was created on. Both 32bit and 64bit systems are supported.
     *
     * @param string $file The file to be parsed.
     * @return array List of messages extracted from the file
     * @throws \Cake\Core\Exception\CakeException If stream content has an invalid format.
     */
    public function parse(string $file): array
    {
        $stream = fopen($file, 'rb');
        if ($stream === false) {
            throw new CakeException(sprintf('Cannot open resource `%s`', $file));
        }

        $stat = fstat($stream);

        if ($stat === false || $stat['size'] < self::MO_HEADER_SIZE) {
            throw new CakeException('Invalid format for MO translations file');
        }
        /** @var array $magic */
        $magic = unpack('V1', (string)fread($stream, 4));
        $magic = hexdec(substr(dechex(current($magic)), -8));

        if ($magic === self::MO_LITTLE_ENDIAN_MAGIC) {
            $isBigEndian = false;
        } elseif ($magic === self::MO_BIG_ENDIAN_MAGIC) {
            $isBigEndian = true;
        } else {
            throw new CakeException('Invalid format for MO translations file');
        }

        // offset formatRevision
        fread($stream, 4);

        $count = $this->_readLong($stream, $isBigEndian);
        $offsetId = $this->_readLong($stream, $isBigEndian);
        $offsetTranslated = $this->_readLong($stream, $isBigEndian);

        // Offset to start of translations
        fread($stream, 8);
        $messages = [];

        for ($i = 0; $i < $count; $i++) {
            $pluralId = null;
            $context = null;
            $plurals = null;

            fseek($stream, $offsetId + $i * 8);

            $length = $this->_readLong($stream, $isBigEndian);
            $offset = $this->_readLong($stream, $isBigEndian);

            if ($length < 1) {
                continue;
            }

            fseek($stream, $offset);
            $singularId = (string)fread($stream, $length);

            if (str_contains($singularId, "\x04")) {
                [$context, $singularId] = explode("\x04", $singularId);
            }

            if (str_contains($singularId, "\000")) {
                [$singularId, $pluralId] = explode("\000", $singularId);
            }

            fseek($stream, $offsetTranslated + $i * 8);
            $length = $this->_readLong($stream, $isBigEndian);
            if ($length < 1) {
                throw new CakeException('Length must be > 0');
            }

            $offset = $this->_readLong($stream, $isBigEndian);
            fseek($stream, $offset);
            $translated = (string)fread($stream, $length);

            if ($pluralId !== null || str_contains($translated, "\000")) {
                $translated = explode("\000", $translated);
                $plurals = $pluralId !== null ? $translated : null;
                $translated = $translated[0];
            }

            $singular = $translated;
            if ($context !== null) {
                $messages[$singularId]['_context'][$context] = $singular;
                if ($pluralId !== null) {
                    $messages[$pluralId]['_context'][$context] = $plurals;
                }
                continue;
            }

            $messages[$singularId]['_context'][''] = $singular;
            if ($pluralId !== null) {
                $messages[$pluralId]['_context'][''] = $plurals;
            }
        }

        fclose($stream);

        return $messages;
    }

    /**
     * Reads an unsigned long from stream respecting endianness.
     *
     * @param resource $stream The File being read.
     * @param bool $isBigEndian Whether the current platform is Big Endian
     * @return int
     */
    protected function _readLong($stream, bool $isBigEndian): int
    {
        /** @var array $result */
        $result = unpack($isBigEndian ? 'N1' : 'V1', (string)fread($stream, 4));
        $result = current($result);

        return (int)substr((string)$result, -8);
    }
}
