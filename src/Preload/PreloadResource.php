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
 * @since         4.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Preload;

use Cake\Preload\Exception\ResourceNotFoundException;
use InvalidArgumentException;

/**
 * Stores a file that will be written to the preload file as a require_once or opcache_compile_file call.
 */
class PreloadResource
{
    /**
     * Preload types.
     *
     * require_once will load additional dependencies in the file, opcache_compile_file will only load the file. The
     * later may lead to the file being unusable by opcache.preloading if not all the dependencies have been preloaded.
     *
     * @see https://www.php.net/manual/en/opcache.preloading.php
     * @var array
     */
    private const TYPES = [
        'require_once',
        'opcache_compile_file',
    ];

    /**
     * How to preload the file. See PreloadResource::TYPES
     *
     * @var string
     */
    private $type;

    /**
     * The absolute file path to be preloaded
     *
     * @var string
     */
    private $file;

    /**
     * @param string $type The preload resource type, see PreloadResource::TYPES
     * @param string $file The preload file path
     */
    public function __construct(string $type, string $file)
    {
        if (!in_array($type, self::TYPES)) {
            throw new InvalidArgumentException(
                'Argument must be on of ' . implode(', ', self::TYPES)
            );
        }

        $this->type = $type;
        $this->file = $file;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * Returns the resource to be preloaded as either a require_once or opcache_compile_file string
     *
     * @return string
     * @throws \Cake\Preload\Exception\ResourceNotFoundException
     */
    public function getResource(): string
    {
        if (!file_exists($this->file)) {
            throw new ResourceNotFoundException(
                'File `' . $this->file . '` does not exist'
            );
        }

        return $this->type . "('" . $this->file . "'); \n";
    }
}
