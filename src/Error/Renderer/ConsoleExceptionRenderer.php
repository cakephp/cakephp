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
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error\Renderer;

use Cake\Console\ConsoleOutput;
use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Error\Debugger;
use Cake\Error\ExceptionRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Plain text exception rendering with a stack trace.
 *
 * Useful in CI or plain text environments.
 */
class ConsoleExceptionRenderer implements ExceptionRendererInterface
{
    /**
     * @var \Throwable
     */
    private Throwable $error;

    /**
     * @var \Cake\Console\ConsoleOutput
     */
    private ConsoleOutput $output;

    /**
     * @var bool
     */
    private bool $trace;

    /**
     * Constructor.
     *
     * @param \Throwable $error The error to render.
     * @param \Psr\Http\Message\ServerRequestInterface|null $request Not used.
     * @param array $config Error handling configuration.
     */
    public function __construct(Throwable $error, ?ServerRequestInterface $request, array $config)
    {
        $this->error = $error;
        $this->output = $config['stderr'] ?? new ConsoleOutput('php://stderr');
        $this->trace = $config['trace'] ?? true;
    }

    /**
     * Render an exception into a plain text message.
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     */
    public function render(): ResponseInterface|string
    {
        $exceptions = [$this->error];
        $previous = $this->error->getPrevious();
        while ($previous !== null) {
            $exceptions[] = $previous;
            $previous = $previous->getPrevious();
        }
        $out = [];
        foreach ($exceptions as $i => $error) {
            $parent = $i > 0 ? $exceptions[$i - 1] : null;
            $out = array_merge($out, $this->renderException($error, $parent));
        }

        return join("\n", $out);
    }

    /**
     * Render an individual exception
     *
     * @param \Throwable $exception The exception to render.
     * @param ?\Throwable $parent The Exception index in the chain
     * @return array
     */
    protected function renderException(Throwable $exception, ?Throwable $parent): array
    {
        $out = [
            sprintf(
                '<error>%s[%s] %s</error> in %s on line %s',
                $parent ? 'Caused by ' : '',
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ),
        ];

        $debug = Configure::read('debug');
        if ($debug && $exception instanceof CakeException) {
            $attributes = $exception->getAttributes();
            if ($attributes) {
                $out[] = '';
                $out[] = '<info>Exception Attributes</info>';
                $out[] = '';
                $out[] = var_export($exception->getAttributes(), true);
            }
        }

        if ($this->trace) {
            $stacktrace = Debugger::getUniqueFrames($exception, $parent);
            $out[] = '';
            $out[] = '<info>Stack Trace:</info>';
            $out[] = '';
            $out[] = Debugger::formatTrace($stacktrace, ['format' => 'text']);
            $out[] = '';
        }

        return $out;
    }

    /**
     * Write output to the output stream
     *
     * @param \Psr\Http\Message\ResponseInterface|string $output The output to print.
     * @return void
     */
    public function write(ResponseInterface|string $output): void
    {
        if (is_string($output)) {
            $this->output->write($output);
        }
    }
}
