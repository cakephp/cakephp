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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Exception;

use Throwable;

/**
 * Used when an element file cannot be found.
 */
class MissingElementException extends MissingTemplateException
{
    /**
     * @var string
     */
    protected string $type = 'Element';

    /**
     * Constructor
     *
     * @param string $name The element name that was requested (e.g. including a plugin prefix).
     * @param string $file The element filename that was searched for.
     * @param array<string> $paths The path list that the element could not be found in.
     * @param int|null $code The code of the error.
     * @param \Throwable|null $previous the previous exception.
     */
    public function __construct(
        protected string $name,
        string $file,
        array $paths = [],
        ?int $code = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($file, $paths, $code, $previous);
    }

    /**
     * @return string
     */
    protected function displayName(): string
    {
        return $this->name;
    }
}
