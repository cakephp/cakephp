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
namespace Cake\I18n\Loader;

/**
 * Parses file in PO format
 *
 * @copyright Copyright (c) 2010, Union of RAD http://union-of-rad.org (http://lithify.me/)
 * @copyright Copyright (c) 2014, Fabien Potencier https://github.com/symfony/Translation/blob/master/LICENSE
 */
class MoFileLoader {

/**
 * Magic used for validating the format of a MO file as well as
 * detecting if the machine used to create that file was little endian.
 *
 * @var float
 */
	const MO_LITTLE_ENDIAN_MAGIC = 0x950412de;

/**
 * Magic used for validating the format of a MO file as well as
 * detecting if the machine used to create that file was big endian.
 *
 * @var float
 */
	const MO_BIG_ENDIAN_MAGIC = 0xde120495;

/**
 * The size of the header of a MO file in bytes.
 *
 * @var int Number of bytes.
 */
	const MO_HEADER_SIZE = 28;

/**
 * Parses machine object (MO) format, independent of the machine's endian it
 * was created on. Both 32bit and 64bit systems are supported.
 *
 * @param resource $resource
 *
 * @return array
 * @throws RuntimeException If stream content has an invalid format.
 */
	public function parse($resource) {
		$stream = fopen($resource, 'r');

		$stat = fstat($stream);

		if ($stat['size'] < self::MO_HEADER_SIZE) {
			throw new \RuntimeException("Invalid format for MO translations file");
		}
		$magic = unpack('V1', fread($stream, 4));
		$magic = hexdec(substr(dechex(current($magic)), -8));

		if ($magic == self::MO_LITTLE_ENDIAN_MAGIC) {
			$isBigEndian = false;
		} elseif ($magic == self::MO_BIG_ENDIAN_MAGIC) {
			$isBigEndian = true;
		} else {
			throw new \RuntimeException("Invalid format for MO translations file");
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
			$singularId = $pluralId = null;
			$translated = null;

			fseek($stream, $offsetId + $i * 8);

			$length = $this->_readLong($stream, $isBigEndian);
			$offset = $this->_readLong($stream, $isBigEndian);

			if ($length < 1) {
				continue;
			}

			fseek($stream, $offset);
			$singularId = fread($stream, $length);

			if (strpos($singularId, "\000") !== false) {
				list($singularId, $pluralId) = explode("\000", $singularId);
			}

			fseek($stream, $offsetTranslated + $i * 8);
			$length = $this->_readLong($stream, $isBigEndian);
			$offset = $this->_readLong($stream, $isBigEndian);
		
			fseek($stream, $offset);
			$translated = fread($stream, $length);

			if (strpos($translated, "\000") === false) {
				$messages[$singularId] = stripcslashes($translated);
				continue;
			}

			$translated = explode("\000", $translated);
			$messages[$singularId] = stripcslashes($translated[0]);
			if ($pluralId !== null) {
				$messages[$pluralId] = stripcslashes(implode('&&&', $translated));
			}
		}

		fclose($stream);
		return $messages;
	}

/**
 * Reads an unsigned long from stream respecting endianess.
 *
 * @param resource $stream
 * @param boolean $isBigEndian
 * @return int
 */
	protected function _readLong($stream, $isBigEndian) {
		$result = unpack($isBigEndian ? 'N1' : 'V1', fread($stream, 4));
		$result = current($result);

		return (int)substr($result, -8);
	}
}
