<?php
//============================================================+
// File name   : tcpdf_filters.php
// Version     : 1.0.000
// Begin       : 2011-05-23
// Last Update : 2012-01-28
// Author      : Nicola Asuni - Tecnick.com LTD - Manor Coach House, Church Hill, Aldershot, Hants, GU12 4RQ, UK - www.tecnick.com - info@tecnick.com
// License     : http://www.tecnick.com/pagefiles/tcpdf/LICENSE.TXT GNU-LGPLv3
// -------------------------------------------------------------------
// Copyright (C) 2011-2012  Nicola Asuni - Tecnick.com LTD
//
// This file is part of TCPDF software library.
//
// TCPDF is free software: you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// TCPDF is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the License
// along with TCPDF. If not, see
// <http://www.tecnick.com/pagefiles/tcpdf/LICENSE.TXT>.
//
// See LICENSE.TXT file for more information.
// -------------------------------------------------------------------
//
// Description : This is a PHP class for decoding common PDF filters (PDF 32000-2008 - 7.4 Filters).
//
//============================================================+

/**
 * @file
 * This is a PHP class for decoding common PDF filters (PDF 32000-2008 - 7.4 Filters).<br>
 * @package com.tecnick.tcpdf
 * @author Nicola Asuni
 * @version 1.0.000
 */

/**
 * @class TCPDF_FILTERS
 * This is a PHP class for decoding common PDF filters (PDF 32000-2008 - 7.4 Filters).<br>
 * @package com.tecnick.tcpdf
 * @brief This is a PHP class for decoding common PDF filters.
 * @version 1.0.000
 * @author Nicola Asuni - info@tecnick.com
 */
class TCPDF_FILTERS {

	/**
	 * Define a list of available filter decoders.
	 * @private
	 */
	private $available_filters = array('ASCIIHexDecode', 'ASCII85Decode', 'LZWDecode', 'FlateDecode', 'RunLengthDecode');

// -----------------------------------------------------------------------------

	/**
	 * Get a list of available decoding filters.
	 * @return (array) Array of available filter decoders.
	 * @public
	 * @since 1.0.000 (2011-05-23)
	 */
	public function getAvailableFilters() {
		return $this->available_filters;
	}

	/**
	 * Decode data using the specified filter type.
	 * @param $filter (string) Filter name.
	 * @param $data (string) Data to decode.
	 * @return Decoded data string.
	 * @public
	 * @since 1.0.000 (2011-05-23)
	 */
	public function decodeFilter($filter, $data) {
		switch ($filter) {
			case 'ASCIIHexDecode': {
				return $this->decodeFilterASCIIHexDecode($data);
				break;
			}
			case 'ASCII85Decode': {
				return $this->decodeFilterASCII85Decode($data);
				break;
			}
			case 'LZWDecode': {
				return $this->decodeFilterLZWDecode($data);
				break;
			}
			case 'FlateDecode': {
				return $this->decodeFilterFlateDecode($data);
				break;
			}
			case 'RunLengthDecode': {
				return $this->decodeFilterRunLengthDecode($data);
				break;
			}
			case 'CCITTFaxDecode': {
				return $this->decodeFilterCCITTFaxDecode($data);
				break;
			}
			case 'JBIG2Decode': {
				return $this->decodeFilterJBIG2Decode($data);
				break;
			}
			case 'DCTDecode': {
				return $this->decodeFilterDCTDecode($data);
				break;
			}
			case 'JPXDecode': {
				return $this->decodeFilterJPXDecode($data);
				break;
			}
			case 'Crypt': {
				return $this->decodeFilterCrypt($data);
				break;
			}
			default: {
				return decodeFilterStandard($data);
				break;
			}
		}
	}

	// --- FILTERS (PDF 32000-2008 - 7.4 Filters) ------------------------------

	/**
	 * Standard
	 * Default decoding filter (leaves data unchanged).
	 * @param $data (string) Data to decode.
	 * @return Decoded data string.
	 * @public
	 * @since 1.0.000 (2011-05-23)
	 */
	public function decodeFilterStandard($data) {
		return $data;
	}

	/**
	 * ASCIIHexDecode
	 * Decodes data encoded in an ASCII hexadecimal representation, reproducing the original binary data.
	 * @param $data (string) Data to decode.
	 * @return Decoded data string.
	 * @public
	 * @since 1.0.000 (2011-05-23)
	 */
	public function decodeFilterASCIIHexDecode($data) {
		// intialize string to return
		$decoded = '';
		// all white-space characters shall be ignored
		$data = preg_replace('/[\s]/', '', $data);
		// check for EOD character: GREATER-THAN SIGN (3Eh)
		$eod = strpos($data, '>');
		if ($eod !== false) {
			// remove EOD and extra data (if any)
			$data = substr($data, 0, $eod);
			$eod = true;
		}
		// get data length
		$data_length = strlen($data);
		if (($data_length % 2) != 0) {
			// odd number of hexadecimal digits
			if ($eod) {
				// EOD shall behave as if a 0 (zero) followed the last digit
				$data = substr($data, 0, -1).'0'.substr($data, -1);
			} else {
				$this->Error('decodeASCIIHex: invalid code');
			}
		}
		// check for invalid characters
		if (preg_match('/[^a-fA-F\d]/', $data) > 0) {
			$this->Error('decodeASCIIHex: invalid code');
		}
		// get one byte of binary data for each pair of ASCII hexadecimal digits
		$decoded = pack('H*', $data);
		return $decoded;
	}

	/**
	 * ASCII85Decode
	 * Decodes data encoded in an ASCII base-85 representation, reproducing the original binary data.
	 * @param $data (string) Data to decode.
	 * @return Decoded data string.
	 * @public
	 * @since 1.0.000 (2011-05-23)
	 */
	public function decodeFilterASCII85Decode($data) {
		// intialize string to return
		$decoded = '';
		// all white-space characters shall be ignored
		$data = preg_replace('/[\s]/', '', $data);
		// remove start sequence 2-character sequence <~ (3Ch)(7Eh)
		if (strpos($data, '<~') !== false) {
			// remove EOD and extra data (if any)
			$data = substr($data, 2);
		}
		// check for EOD: 2-character sequence ~> (7Eh)(3Eh)
		$eod = strpos($data, '~>');
		if ($eod !== false) {
			// remove EOD and extra data (if any)
			$data = substr($data, 0, $eod);
		}
		// data length
		$data_length = strlen($data);
		// check for invalid characters
		if (preg_match('/[^\x21-\x75,\x74]/', $data) > 0) {
			$this->Error('decodeASCII85: invalid code');
		}
		// z sequence
		$zseq = chr(0).chr(0).chr(0).chr(0);
		// position inside a group of 4 bytes (0-3)
		$group_pos = 0;
		$tuple = 0;
		$pow85 = array((85*85*85*85), (85*85*85), (85*85), 85, 1);
		$last_pos = ($data_length - 1);
		// for each byte
		for ($i = 0; $i < $data_length; ++$i) {
			// get char value
			$char = ord($data[$i]);
			if ($char == 122) { // 'z'
				if ($group_pos == 0) {
					$decoded .= $zseq;
				} else {
					$this->Error('decodeASCII85: invalid code');
				}
			} else {
				// the value represented by a group of 5 characters should never be greater than 2^32 - 1
				$tuple += (($char - 33) * $pow85[$group_pos]);
				if ($group_pos == 4) {
					$decoded .= chr($tuple >> 24).chr($tuple >> 16).chr($tuple >> 8).chr($tuple);
					$tuple = 0;
					$group_pos = 0;
				} else {
					++$group_pos;
				}
			}
		}
		if ($group_pos > 1) {
			$tuple += $pow85[($group_pos - 1)];
		}
		// last tuple (if any)
		switch ($group_pos) {
			case 4: {
				$decoded .= chr($tuple >> 24).chr($tuple >> 16).chr($tuple >> 8);
				break;
			}
			case 3: {
				$decoded .= chr($tuple >> 24).chr($tuple >> 16);
				break;
			}
			case 2: {
				$decoded .= chr($tuple >> 24);
				break;
			}
			case 1: {
				$this->Error('decodeASCII85: invalid code');
				break;
			}
		}
		return $decoded;
	}

	/**
	 * LZWDecode
	 * Decompresses data encoded using the LZW (Lempel-Ziv-Welch) adaptive compression method, reproducing the original text or binary data.
	 * @param $data (string) Data to decode.
	 * @return Decoded data string.
	 * @public
	 * @since 1.0.000 (2011-05-23)
	 */
	public function decodeFilterLZWDecode($data) {
		// intialize string to return
		$decoded = '';
		// data length
		$data_length = strlen($data);
		// convert string to binary string
		$bitstring = '';
		for ($i = 0; $i < $data_length; ++$i) {
			$bitstring .= sprintf('%08b', ord($data{$i}));
		}
		// get the number of bits
		$data_length = strlen($bitstring);
		// initialize code length in bits
		$bitlen = 9;
		// initialize dictionary index
		$dix = 258;
		// initialize the dictionary (with the first 256 entries).
		$dictionary = array();
		for ($i = 0; $i < 256; ++$i) {
			$dictionary[$i] = chr($i);
		}
		// previous val
		$prev_index = 0;
		// while we encounter EOD marker (257), read code_length bits
		while (($data_length > 0) AND (($index = bindec(substr($bitstring, 0, $bitlen))) != 257)) {
			// remove read bits from string
			$bitstring = substr($bitstring, $bitlen);
			// update number of bits
			$data_length -= $bitlen;
			if ($index == 256) { // clear-table marker
				// reset code length in bits
				$bitlen = 9;
				// reset dictionary index
				$dix = 258;
				$prev_index = 256;
				// reset the dictionary (with the first 256 entries).
				$dictionary = array();
				for ($i = 0; $i < 256; ++$i) {
					$dictionary[$i] = chr($i);
				}
			} elseif ($prev_index == 256) {
				// first entry
				$decoded .= $dictionary[$index];
				$prev_index = $index;
			} else {
				// check if index exist in the dictionary
				if ($index < $dix) {
					// index exist on dictionary
					$decoded .= $dictionary[$index];
					$dic_val = $dictionary[$prev_index].$dictionary[$index]{0};
					// store current index
					$prev_index = $index;
				} else {
					// index do not exist on dictionary
					$dic_val = $dictionary[$prev_index].$dictionary[$prev_index]{0};
					$decoded .= $dic_val;
				}
				// update dictionary
				$dictionary[$dix] = $dic_val;
				++$dix;
				// change bit length by case
				if ($dix == 2047) {
					$bitlen = 12;
				} elseif ($dix == 1023) {
					$bitlen = 11;
				} elseif ($dix == 511) {
					$bitlen = 10;
				}
			}
		}
		return $decoded;
	}

	/**
	 * FlateDecode
	 * Decompresses data encoded using the zlib/deflate compression method, reproducing the original text or binary data.
	 * @param $data (string) Data to decode.
	 * @return Decoded data string.
	 * @public
	 * @since 1.0.000 (2011-05-23)
	 */
	public function decodeFilterFlateDecode($data) {
		// intialize string to return
		$decoded = gzuncompress($data);
		if ($decoded === false) {
			$this->Error('decodeFlate: invalid code');
		}
		return $decoded;
	}

	/**
	 * RunLengthDecode
	 * Decompresses data encoded using a byte-oriented run-length encoding algorithm.
	 * @param $data (string) Data to decode.
	 * @public
	 * @since 1.0.000 (2011-05-23)
	 */
	public function decodeFilterRunLengthDecode($data) {
		// intialize string to return
		$decoded = '';
		// data length
		$data_length = strlen($data);
		$i = 0;
		while($i < $data_length) {
			// get current byte value
			$byte = ord($data{$i});
			if ($byte == 128) {
				// a length value of 128 denote EOD
				break;
			} elseif ($byte < 128) {
				// if the length byte is in the range 0 to 127
				// the following length + 1 (1 to 128) bytes shall be copied literally during decompression
				$decoded .= substr($data, ($i + 1), ($byte + 1));
				// move to next block
				$i += ($byte + 2);
			} else {
				// if length is in the range 129 to 255,
				// the following single byte shall be copied 257 - length (2 to 128) times during decompression
				$decoded .= str_repeat($data{($i + 1)}, (257 - $byte));
				// move to next block
				$i += 2;
			}
		}
		return $decoded;
	}

	/**
	 * CCITTFaxDecode (NOT IMPLEMETED)
	 * Decompresses data encoded using the CCITT facsimile standard, reproducing the original data (typically monochrome image data at 1 bit per pixel).
	 * @param $data (string) Data to decode.
	 * @return Decoded data string.
	 * @public
	 * @since 1.0.000 (2011-05-23)
	 */
	public function decodeFilterCCITTFaxDecode($data) {
		return $data;
	}

	/**
	 * JBIG2Decode (NOT IMPLEMETED)
	 * Decompresses data encoded using the JBIG2 standard, reproducing the original monochrome (1 bit per pixel) image data (or an approximation of that data).
	 * @param $data (string) Data to decode.
	 * @return Decoded data string.
	 * @public
	 * @since 1.0.000 (2011-05-23)
	 */
	public function decodeFilterJBIG2Decode($data) {
		return $data;
	}

	/**
	 * DCTDecode (NOT IMPLEMETED)
	 * Decompresses data encoded using a DCT (discrete cosine transform) technique based on the JPEG standard, reproducing image sample data that approximates the original data.
	 * @param $data (string) Data to decode.
	 * @return Decoded data string.
	 * @public
	 * @since 1.0.000 (2011-05-23)
	 */
	public function decodeFilterDCTDecode($data) {
		return $data;
	}

	/**
	 * JPXDecode (NOT IMPLEMETED)
	 * Decompresses data encoded using the wavelet-based JPEG2000 standard, reproducing the original image data.
	 * @param $data (string) Data to decode.
	 * @return Decoded data string.
	 * @public
	 * @since 1.0.000 (2011-05-23)
	 */
	public function decodeFilterJPXDecode($data) {
		return $data;
	}

	/**
	 * Crypt (NOT IMPLEMETED)
	 * Decrypts data encrypted by a security handler, reproducing the data as it was before encryption.
	 * @param $data (string) Data to decode.
	 * @return Decoded data string.
	 * @public
	 * @since 1.0.000 (2011-05-23)
	 */
	public function decodeFilterCrypt($data) {
		return $data;
	}

	// --- END FILTERS SECTION -------------------------------------------------

	/**
	 * This method is automatically called in case of fatal error; it simply outputs the message and halts the execution.
	 * @param $msg (string) The error message
	 * @public
	 * @since 1.0.000 (2011-05-23)
	 */
	public function Error($msg) {
		// exit program and print error
		die('<strong>TCPDF_FILTERS ERROR: </strong>'.$msg);
	}

} // END OF TCPDF_FILTERS CLASS

//============================================================+
// END OF FILE
//============================================================+
