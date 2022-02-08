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
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Plain text exception rendering with a stack trace.
 *
 * Useful in CI or plain text environments.
 *
 * @todo 5.0 Implement \Cake\Error\ExceptionRendererInterface. This implementation can't implement
 *  the concrete interface because the return types are not compatible.
 */
class ConsoleExceptionRenderer
{
    /**
     * @var \Throwable
     */
    private $error;

    /**
     * @var \Cake\Console\ConsoleOutput
     */
    private $output;

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
    }

    /**
     * Render an exception into a plain text message.
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     */
    public function render()
    {
        return sprintf(
            "<error>%s : %s</error> on line %s of %s\n<info>Trace:</info>\n%s",
            $this->error->getCode(),
            $this->error->getMessage(),
            $this->error->getLine(),
            $this->error->getFile(),
            $this->error->getTraceAsString(),
        );
    }

    /**
     * Write output to the output stream
     *
     * @param string $output The output to print.
     * @return void
     */
    public function write($output): void
    {
        $this->output->write($output);
    }
}
