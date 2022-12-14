<?php
declare(strict_types=1);

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

use Cake\Utility\Text;
use Stringable;

/**
 * Contains the data and behavior for a single
 * part in a Multipart FormData request body.
 *
 * Added to Cake\Http\Client\FormData when sending
 * data to a remote server.
 *
 * @internal
 */
class FormDataPart implements Stringable
{
    /**
     * Content type to use
     *
     * @var string|null
     */
    protected ?string $type = null;

    /**
     * Filename to send if using files.
     *
     * @var string|null
     */
    protected ?string $filename = null;

    /**
     * The encoding used in this part.
     *
     * @var string|null
     */
    protected ?string $transferEncoding = null;

    /**
     * The contentId for the part
     *
     * @var string|null
     */
    protected ?string $contentId = null;

    /**
     * Constructor
     *
     * @param string $name The name of the data.
     * @param string $value The value of the data.
     * @param string $disposition The type of disposition to use, defaults to form-data.
     * @param string|null $charset The charset of the data.
     */
    public function __construct(
        protected string $name,
        protected string $value,
        protected string $disposition = 'form-data',
        protected ?string $charset = null
    ) {
    }

    /**
     * Get/set the disposition type
     *
     * By passing in `false` you can disable the disposition
     * header from being added.
     *
     * @param string|null $disposition Use null to get/string to set.
     * @return string
     */
    public function disposition(?string $disposition = null): string
    {
        if ($disposition === null) {
            return $this->disposition;
        }

        return $this->disposition = $disposition;
    }

    /**
     * Get/set the contentId for a part.
     *
     * @param string|null $id The content id.
     * @return string|null
     */
    public function contentId(?string $id = null): ?string
    {
        if ($id === null) {
            return $this->contentId;
        }

        return $this->contentId = $id;
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
    public function filename(?string $filename = null): ?string
    {
        if ($filename === null) {
            return $this->filename;
        }

        return $this->filename = $filename;
    }

    /**
     * Get/set the content type.
     *
     * @param string|null $type Use null to get/string to set.
     * @return string|null
     */
    public function type(?string $type): ?string
    {
        if ($type === null) {
            return $this->type;
        }

        return $this->type = $type;
    }

    /**
     * Set the transfer-encoding for multipart.
     *
     * Useful when content bodies are in encodings like base64.
     *
     * @param string|null $type The type of encoding the value has.
     * @return string|null
     */
    public function transferEncoding(?string $type): ?string
    {
        if ($type === null) {
            return $this->transferEncoding;
        }

        return $this->transferEncoding = $type;
    }

    /**
     * Get the part name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get the value.
     *
     * @return string
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Convert the part into a string.
     *
     * Creates a string suitable for use in HTTP requests.
     *
     * @return string
     */
    public function __toString(): string
    {
        $out = '';
        if ($this->disposition) {
            $out .= 'Content-Disposition: ' . $this->disposition;
            if ($this->name) {
                $out .= '; ' . $this->_headerParameterToString('name', $this->name);
            }
            if ($this->filename) {
                $out .= '; ' . $this->_headerParameterToString('filename', $this->filename);
            }
            $out .= "\r\n";
        }
        if ($this->type) {
            $out .= 'Content-Type: ' . $this->type . "\r\n";
        }
        if ($this->transferEncoding) {
            $out .= 'Content-Transfer-Encoding: ' . $this->transferEncoding . "\r\n";
        }
        if ($this->contentId) {
            $out .= 'Content-ID: <' . $this->contentId . ">\r\n";
        }
        $out .= "\r\n";
        $out .= $this->value;

        return $out;
    }

    /**
     * Get the string for the header parameter.
     *
     * If the value contains non-ASCII letters an additional header indicating
     * the charset encoding will be set.
     *
     * @param string $name The name of the header parameter
     * @param string $value The value of the header parameter
     * @return string
     */
    protected function _headerParameterToString(string $name, string $value): string
    {
        $transliterated = Text::transliterate(str_replace('"', '', $value));
        $return = sprintf('%s="%s"', $name, $transliterated);
        if ($this->charset !== null && $value !== $transliterated) {
            $return .= sprintf("; %s*=%s''%s", $name, strtolower($this->charset), rawurlencode($value));
        }

        return $return;
    }
}
