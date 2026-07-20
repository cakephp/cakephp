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

use Cake\Core\Exception\CakeException;
use Throwable;

/**
 * Used when a template file cannot be found.
 */
class MissingTemplateException extends CakeException
{
    /**
     * @var string
     */
    protected string $type = 'Template';

    /**
     * Constructor
     *
     * @param string $filename The file name.
     * @param array<string> $paths The path list that template could not be found in.
     * @param int|null $code The code of the error.
     * @param \Throwable|null $previous the previous exception.
     */
    public function __construct(
        protected string $filename,
        protected array $paths = [],
        ?int $code = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($this->formatMessage(), $code, $previous);
    }

    /**
     * Get the formatted exception message.
     *
     * @return string
     */
    public function formatMessage(): string
    {
        $message = "{$this->type} file `{$this->displayName()}` could not be found.";
        if ($this->paths) {
            $message .= "\n\nThe following paths were searched:\n\n";
            foreach ($this->paths as $path) {
                $message .= "- `{$path}{$this->filename}`\n";
            }
        }

        return $message;
    }

    /**
     * Get the name to show in the exception message.
     *
     * @return string
     */
    protected function displayName(): string
    {
        return $this->filename;
    }

    /**
     * Get the passed in attributes
     *
     * @return array{file: string, paths: array<string>}
     */
    public function getAttributes(): array
    {
        return [
            'file' => $this->filename,
            'paths' => $this->paths,
        ];
    }
}
