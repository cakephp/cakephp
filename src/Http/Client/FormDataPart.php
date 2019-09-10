<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Client;

/**
 * Contains the data and behavior for a single
 * part in a Multipart FormData request body.
 *
 * Added to Cake\Http\Client\FormData when sending
 * data to a remote server.
 *
 * @internal
 */
class FormDataPart
{
    /**
     * Name of the value.
     *
     * @var string
     */
    protected $_name;

    /**
     * Value to send.
     *
     * @var string
     */
    protected $_value;

    /**
     * Content type to use
     *
     * @var string
     */
    protected $_type;

    /**
     * Disposition to send
     *
     * @var string
     */
    protected $_disposition;

    /**
     * Filename to send if using files.
     *
     * @var string
     */
    protected $_filename;

    /**
     * The encoding used in this part.
     *
     * @var string
     */
    protected $_transferEncoding;

    /**
     * The contentId for the part
     *
     * @var string
     */
    protected $_contentId;

    /**
     * Constructor
     *
     * @param string $name The name of the data.
     * @param string $value The value of the data.
     * @param string $disposition The type of disposition to use, defaults to form-data.
     */
    public function __construct($name, $value, $disposition = 'form-data')
    {
        $this->_name = $name;
        $this->_value = $value;
        $this->_disposition = $disposition;
    }

    /**
     * Get/set the disposition type
     *
     * By passing in `false` you can disable the disposition
     * header from being added.
     *
     * @param string|null $disposition Use null to get/string to set.
     * @return string|null
     */
    public function disposition($disposition = null)
    {
        if ($disposition === null) {
            return $this->_disposition;
        }
        $this->_disposition = $disposition;
    }

    /**
     * Get/set the contentId for a part.
     *
     * @param string|null $id The content id.
     * @return string|null
     */
    public function contentId($id = null)
    {
        if ($id === null) {
            return $this->_contentId;
        }
        $this->_contentId = $id;
    }

    /**
     * Get/set the filename.
     *
     * Setting the filename to `false` will exclude it from the
     * generated output.
     *
     * @param string|null $filename Use null to get/string to set.
     * @return string|null
     */
    public function filename($filename = null)
    {
        if ($filename === null) {
            return $this->_filename;
        }
        $this->_filename = $filename;
    }

    /**
     * Get/set the content type.
     *
     * @param string|null $type Use null to get/string to set.
     * @return string|null
     */
    public function type($type)
    {
        if ($type === null) {
            return $this->_type;
        }
        $this->_type = $type;
    }

    /**
     * Set the transfer-encoding for multipart.
     *
     * Useful when content bodies are in encodings like base64.
     *
     * @param string|null $type The type of encoding the value has.
     * @return string|null
     */
    public function transferEncoding($type)
    {
        if ($type === null) {
            return $this->_transferEncoding;
        }
        $this->_transferEncoding = $type;
    }

    /**
     * Get the part name.
     *
     * @return string
     */
    public function name()
    {
        return $this->_name;
    }

    /**
     * Get the value.
     *
     * @return string
     */
    public function value()
    {
        return $this->_value;
    }

    /**
     * Convert the part into a string.
     *
     * Creates a string suitable for use in HTTP requests.
     *
     * @return string
     */
    public function __toString()
    {
        $out = '';
        if ($this->_disposition) {
            $out .= 'Content-Disposition: ' . $this->_disposition;
            if ($this->_name) {
                $out .= '; name="' . $this->_name . '"';
            }
            if ($this->_filename) {
                $out .= '; filename="' . $this->_filename . '"';
            }
            $out .= "\r\n";
        }
        if ($this->_type) {
            $out .= 'Content-Type: ' . $this->_type . "\r\n";
        }
        if ($this->_transferEncoding) {
            $out .= 'Content-Transfer-Encoding: ' . $this->_transferEncoding . "\r\n";
        }
        if ($this->_contentId) {
            $out .= 'Content-ID: <' . $this->_contentId . ">\r\n";
        }
        $out .= "\r\n";
        $out .= (string)$this->_value;

        return $out;
    }
}

// @deprecated 3.4.0 Add backwards compat alias.
class_alias('Cake\Http\Client\FormDataPart', 'Cake\Network\Http\FormData\Part');
