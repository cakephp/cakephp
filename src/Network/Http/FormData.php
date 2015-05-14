<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Network\Http;

use Cake\Network\Http\FormData\Part;
use Countable;

/**
 * Provides an interface for building
 * multipart/form-encoded message bodies.
 *
 * Used by Http\Client to upload POST/PUT data
 * and files.
 *
 */
class FormData implements Countable
{

    /**
     * Boundary marker.
     *
     * @var string
     */
    protected $_boundary;

    /**
     * The parts in the form data.
     *
     * @var array
     */
    protected $_parts = [];

    /**
     * Get the boundary marker
     *
     * @return string
     */
    public function boundary()
    {
        if ($this->_boundary) {
            return $this->_boundary;
        }
        $this->_boundary = md5(uniqid(time()));
        return $this->_boundary;
    }

    /**
     * Method for creating new instances of Part
     *
     * @param string $name The name of the part.
     * @param string $value The value to add.
     * @return \Cake\Network\Http\FormData\Part
     */
    public function newPart($name, $value)
    {
        return new Part($name, $value);
    }

    /**
     * Add a new part to the data.
     *
     * The value for a part can be a string, array, int,
     * float, filehandle, or object implementing __toString()
     *
     * If the $value is an array, multiple parts will be added.
     * Files will be read from their current position and saved in memory.
     *
     * @param string $name The name of the part.
     * @param mixed $value The value for the part.
     * @return $this
     */
    public function add($name, $value)
    {
        if (is_array($value)) {
            $this->addRecursive($name, $value);
        } elseif (is_resource($value)) {
            $this->_parts[] = $this->addFile($name, $value);
        } elseif (is_string($value) && strlen($value) && $value[0] === '@') {
            trigger_error(
                'Using the @ syntax for file uploads is not safe and is deprecated. ' .
                'Instead you should use file handles.',
                E_USER_DEPRECATED
            );
            $this->_parts[] = $this->addFile($name, $value);
        } else {
            $this->_parts[] = $this->newPart($name, $value);
        }
        return $this;
    }

    /**
     * Add multiple parts at once.
     *
     * Iterates the parameter and adds all the key/values.
     *
     * @param array $data Array of data to add.
     * @return $this
     */
    public function addMany(array $data)
    {
        foreach ($data as $name => $value) {
            $this->add($name, $value);
        }
        return $this;
    }

    /**
     * Add either a file reference (string starting with @)
     * or a file handle.
     *
     * @param string $name The name to use.
     * @param mixed $value Either a string filename, or a filehandle.
     * @return \Cake\Network\Http\FormData\Part
     */
    public function addFile($name, $value)
    {
        $filename = false;
        $contentType = 'application/octet-stream';
        if (is_resource($value)) {
            $content = stream_get_contents($value);
            if (stream_is_local($value)) {
                $finfo = new \finfo(FILEINFO_MIME);
                $metadata = stream_get_meta_data($value);
                $contentType = $finfo->file($metadata['uri']);
                $filename = basename($metadata['uri']);
            }
        } else {
            $finfo = new \finfo(FILEINFO_MIME);
            $value = substr($value, 1);
            $filename = basename($value);
            $content = file_get_contents($value);
            $contentType = $finfo->file($value);
        }
        $part = $this->newPart($name, $content);
        $part->type($contentType);
        if ($filename) {
            $part->filename($filename);
        }
        return $part;
    }

    /**
     * Recursively add data.
     *
     * @param string $name The name to use.
     * @param mixed $value The value to add.
     * @return void
     */
    public function addRecursive($name, $value)
    {
        foreach ($value as $key => $value) {
            $key = $name . '[' . $key . ']';
            $this->add($key, $value);
        }
    }

    /**
     * Returns the count of parts inside this object.
     *
     * @return int
     */
    public function count()
    {
        return count($this->_parts);
    }

    /**
     * Converts the FormData and its parts into a string suitable
     * for use in an HTTP request.
     *
     * @return string
     */
    public function __toString()
    {
        $boundary = $this->boundary();
        $out = '';
        foreach ($this->_parts as $part) {
            $out .= "--$boundary\r\n";
            $out .= (string)$part;
            $out .= "\r\n";
        }
        $out .= "--$boundary--\r\n\r\n";
        return $out;
    }
}
