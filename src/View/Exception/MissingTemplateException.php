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
     * @var string|null
     */
    protected ?string $templateName = null;

    /**
     * @var string
     */
    protected string $filename;

    /**
     * @var list<string>
     */
    protected array $paths;

    /**
     * @var string
     */
    protected string $type = 'Template';

    /**
     * Constructor
     *
     * @param list<string>|string $file Either the file name as a string, or in an array for backwards compatibility.
     * @param list<string> $paths The path list that template could not be found in.
     * @param int|null $code The code of the error.
     * @param \Throwable|null $previous the previous exception.
     */
    public function __construct(array|string $file, array $paths = [], ?int $code = null, ?Throwable $previous = null)
    {
        if (is_array($file)) {
            $this->filename = (string)array_pop($file);
            $this->templateName = array_pop($file);
        } else {
            $this->filename = $file;
            $this->templateName = null;
        }
        $this->paths = $paths;

        parent::__construct($this->formatMessage(), $code, $previous);
    }

    /**
     * Get the formatted exception message.
     *
     * @return string
     */
    public function formatMessage(): string
    {
        $name = $this->templateName ?? $this->filename;
        $message = "{$this->type} file `{$name}` could not be found.";
        if ($this->paths) {
            $message .= "\n\nThe following paths were searched:\n\n";
            foreach ($this->paths as $path) {
                $message .= "- `{$path}{$this->filename}`\n";
            }
        }

        return $message;
    }

    /**
     * Get the passed in attributes
     *
     * @return array<string, mixed>
     * @psalm-return array{file: string, paths: list<string>}
     */
    public function getAttributes(): array
    {
        return [
            'file' => $this->filename,
            'paths' => $this->paths,
        ];
    }
}
