<?php
declare(strict_types=1);

/**
 * CakePHP(tm) :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakefoundation.org CakePHP(tm) Project
 * @since         1.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log\Engine;

use Cake\Log\Formatter\DefaultFormatter;
use Cake\Utility\Text;
use function Cake\Core\deprecationWarning;

/**
 * File Storage stream for Logging. Writes logs to different files
 * based on the level of log it is.
 */
class FileLog extends BaseLog
{
    /**
     * Default config for this class
     *
     * - `levels` string or array, levels the engine is interested in
     * - `scopes` string or array, scopes the engine is interested in
     * - `file` Log file name
     * - `path` The path to save logs on.
     * - `size` Used to implement basic log file rotation. If log file size
     *   reaches specified size the existing file is renamed by appending timestamp
     *   to filename and new log file is created. Can be integer bytes value or
     *   human readable string values like '10MB', '100KB' etc.
     * - `rotate` Log files are rotated specified times before being removed.
     *   If value is 0, old versions are removed rather then rotated.
     * - `mask` A mask is applied when log files are created. Left empty no chmod
     *   is made.
     * - `dirMask` The mask used for created folders.
     * - `dateFormat` PHP date() format.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'path' => null,
        'file' => null,
        'types' => null,
        'levels' => [],
        'scopes' => [],
        'rotate' => 10,
        'size' => 10485760, // 10MB
        'mask' => null,
        'dirMask' => 0770,
        'formatter' => [
            'className' => DefaultFormatter::class,
        ],
    ];

    /**
     * Path to save log files on.
     *
     * @var string
     */
    protected $_path;

    /**
     * The name of the file to save logs into.
     *
     * @var string|null
     */
    protected $_file;

    /**
     * Max file size, used for log file rotation.
     *
     * @var int|null
     */
    protected $_size;

    /**
     * Sets protected properties based on config provided
     *
     * @param array<string, mixed> $config Configuration array
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->_path = $this->getConfig('path', sys_get_temp_dir() . DIRECTORY_SEPARATOR);
        if (!is_dir($this->_path)) {
            mkdir($this->_path, $this->_config['dirMask'], true);
        }

        if (!empty($this->_config['file'])) {
            $this->_file = $this->_config['file'];
            if (substr($this->_file, -4) !== '.log') {
                $this->_file .= '.log';
            }
        }

        if (!empty($this->_config['size'])) {
            if (is_numeric($this->_config['size'])) {
                $this->_size = (int)$this->_config['size'];
            } else {
                $this->_size = Text::parseFileSize($this->_config['size']);
            }
        }

        if (isset($this->_config['dateFormat'])) {
            deprecationWarning('`dateFormat` option should now be set in the formatter options.', 0);
            $this->formatter->setConfig('dateFormat', $this->_config['dateFormat']);
        }
    }

    /**
     * Implements writing to log files.
     *
     * @param mixed $level The severity level of the message being written.
     * @param string $message The message you want to log.
     * @param array $context Additional information about the logged message
     * @return void
     * @see \Cake\Log\Log::$_levels
     */
    public function log($level, $message, array $context = []): void
    {
        $message = $this->_format($message, $context);
        $message = $this->formatter->format($level, $message, $context);

        $filename = $this->_getFilename($level);
        if ($this->_size) {
            $this->_rotateFile($filename);
        }

        $pathname = $this->_path . $filename;
        $mask = $this->_config['mask'];
        if (!$mask) {
            file_put_contents($pathname, $message . "\n", FILE_APPEND);

            return;
        }

        $exists = is_file($pathname);
        file_put_contents($pathname, $message . "\n", FILE_APPEND);
        static $selfError = false;

        if (!$selfError && !$exists && !chmod($pathname, (int)$mask)) {
            $selfError = true;
            trigger_error(vsprintf(
                'Could not apply permission mask "%s" on log file "%s"',
                [$mask, $pathname]
            ), E_USER_WARNING);
            $selfError = false;
        }
    }

    /**
     * Get filename
     *
     * @param string $level The level of log.
     * @return string File name
     */
    protected function _getFilename(string $level): string
    {
        $debugTypes = ['notice', 'info', 'debug'];

        if ($this->_file) {
            $filename = $this->_file;
        } elseif ($level === 'error' || $level === 'warning') {
            $filename = 'error.log';
        } elseif (in_array($level, $debugTypes, true)) {
            $filename = 'debug.log';
        } else {
            $filename = $level . '.log';
        }

        return $filename;
    }

    /**
     * Rotate log file if size specified in config is reached.
     * Also if `rotate` count is reached oldest file is removed.
     *
     * @param string $filename Log file name
     * @return bool|null True if rotated successfully or false in case of error.
     *   Null if file doesn't need to be rotated.
     */
    protected function _rotateFile(string $filename): ?bool
    {
        $filePath = $this->_path . $filename;
        clearstatcache(true, $filePath);

        if (
            !is_file($filePath) ||
            filesize($filePath) < $this->_size
        ) {
            return null;
        }

        $rotate = $this->_config['rotate'];
        if ($rotate === 0) {
            $result = unlink($filePath);
        } else {
            $result = rename($filePath, $filePath . '.' . time());
        }

        $files = glob($filePath . '.*');
        if ($files) {
            $filesToDelete = count($files) - $rotate;
            while ($filesToDelete > 0) {
                unlink(array_shift($files));
                $filesToDelete--;
            }
        }

        return $result;
    }
}
