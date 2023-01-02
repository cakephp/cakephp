<?php
declare(strict_types=1);

/**
 * ExceptionsTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase;

use Cake\Error\FatalErrorException;
use Cake\ORM\Entity;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\TestSuite\TestCase;
use Cake\View\Exception\MissingCellTemplateException;
use Cake\View\Exception\MissingElementException;
use Cake\View\Exception\MissingLayoutException;
use Cake\View\Exception\MissingTemplateException;
use Exception;

class ExceptionsTest extends TestCase
{
    /**
     * Tests simple exceptions work.
     *
     * @dataProvider exceptionProvider
     * @param string $class The exception class name
     * @param int $defaultCode The default exception code
     */
    public function testSimpleException($class, $defaultCode): void
    {
        $previous = new Exception();

        /** @var \Exception $exception */
        $exception = new $class('message', 100, $previous);
        $this->assertSame('message', $exception->getMessage());
        $this->assertSame(100, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());

        $exception = new $class('message', null, $previous);
        $this->assertSame('message', $exception->getMessage());
        $this->assertSame($defaultCode, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * Tests FatalErrorException works.
     */
    public function testFatalErrorException(): void
    {
        $previous = new Exception();

        $exception = new FatalErrorException('message', 100, __FILE__, 1, $previous);
        $this->assertSame('message', $exception->getMessage());
        $this->assertSame(100, $exception->getCode());
        $this->assertSame(__FILE__, $exception->getFile());
        $this->assertSame(1, $exception->getLine());
        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * Tests PersistenceFailedException works.
     */
    public function testPersistenceFailedException(): void
    {
        $previous = new Exception();
        $entity = new Entity();

        $exception = new PersistenceFailedException($entity, 'message', 100, $previous);
        $this->assertSame('message', $exception->getMessage());
        $this->assertSame(100, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame($entity, $exception->getEntity());
    }

    /**
     * Test the template exceptions
     */
    public function testMissingTemplateExceptions(): void
    {
        $previous = new Exception();

        $error = new MissingTemplateException('view.ctp', ['path/a/', 'path/b/'], 100, $previous);
        $this->assertStringContainsString('Template file `view.ctp` could not be found', $error->getMessage());
        $this->assertStringContainsString('- `path/a/view.ctp`', $error->getMessage());
        $this->assertSame($previous, $error->getPrevious());
        $this->assertSame(100, $error->getCode());
        $attributes = $error->getAttributes();
        $this->assertArrayHasKey('file', $attributes);
        $this->assertArrayHasKey('paths', $attributes);

        $error = new MissingLayoutException('default.ctp', ['path/a/', 'path/b/'], 100, $previous);
        $this->assertStringContainsString('Layout file `default.ctp` could not be found', $error->getMessage());
        $this->assertStringContainsString('- `path/a/default.ctp`', $error->getMessage());
        $this->assertSame($previous, $error->getPrevious());
        $this->assertSame(100, $error->getCode());

        $error = new MissingElementException('view.ctp', ['path/a/', 'path/b/'], 100, $previous);
        $this->assertStringContainsString('Element file `view.ctp` could not be found', $error->getMessage());
        $this->assertStringContainsString('- `path/a/view.ctp`', $error->getMessage());
        $this->assertSame($previous, $error->getPrevious());
        $this->assertSame(100, $error->getCode());

        $error = new MissingCellTemplateException('Articles', 'view.ctp', ['path/a/', 'path/b/'], 100, $previous);
        $this->assertStringContainsString('Cell template file `view.ctp` could not be found', $error->getMessage());
        $this->assertStringContainsString('- `path/a/view.ctp`', $error->getMessage());
        $this->assertSame($previous, $error->getPrevious());
        $this->assertSame(100, $error->getCode());
        $attributes = $error->getAttributes();
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('file', $attributes);
        $this->assertArrayHasKey('paths', $attributes);
    }

    /**
     * Provides pairs of exception name and default code.
     *
     * @return array
     */
    public function exceptionProvider(): array
    {
        return [
            ['Cake\Console\Exception\ConsoleException', 1],
            ['Cake\Console\Exception\MissingHelperException', 1],
            ['Cake\Console\Exception\MissingShellException', 1],
            ['Cake\Console\Exception\MissingShellMethodException', 1],
            ['Cake\Console\Exception\MissingTaskException', 1],
            ['Cake\Console\Exception\StopException', 1],
            ['Cake\Controller\Exception\AuthSecurityException', 400],
            ['Cake\Controller\Exception\MissingActionException', 0],
            ['Cake\Controller\Exception\MissingComponentException', 0],
            ['Cake\Controller\Exception\SecurityException', 400],
            ['Cake\Core\Exception\CakeException', 0],
            ['Cake\Core\Exception\MissingPluginException', 0],
            ['Cake\Database\Exception\DatabaseException', 0],
            ['Cake\Database\Exception\MissingConnectionException', 0],
            ['Cake\Database\Exception\MissingDriverException', 0],
            ['Cake\Database\Exception\MissingExtensionException', 0],
            ['Cake\Database\Exception\NestedTransactionRollbackException', 0],
            ['Cake\Datasource\Exception\InvalidPrimaryKeyException', 0],
            ['Cake\Datasource\Exception\MissingDatasourceConfigException', 0],
            ['Cake\Datasource\Exception\MissingDatasourceException', 0],
            ['Cake\Datasource\Exception\MissingModelException', 0],
            ['Cake\Datasource\Exception\RecordNotFoundException', 0],
            ['Cake\Datasource\Paging\Exception\PageOutOfBoundsException', 0],
            ['Cake\Mailer\Exception\MissingActionException', 0],
            ['Cake\Mailer\Exception\MissingMailerException', 0],
            ['Cake\Http\Exception\BadRequestException', 400],
            ['Cake\Http\Exception\ConflictException', 409],
            ['Cake\Http\Exception\ForbiddenException', 403],
            ['Cake\Http\Exception\GoneException', 410],
            ['Cake\Http\Exception\HttpException', 500],
            ['Cake\Http\Exception\InternalErrorException', 500],
            ['Cake\Http\Exception\InvalidCsrfTokenException', 403],
            ['Cake\Http\Exception\MethodNotAllowedException', 405],
            ['Cake\Http\Exception\MissingControllerException', 404],
            ['Cake\Http\Exception\NotAcceptableException', 406],
            ['Cake\Http\Exception\NotFoundException', 404],
            ['Cake\Http\Exception\NotImplementedException', 501],
            ['Cake\Http\Exception\ServiceUnavailableException', 503],
            ['Cake\Http\Exception\UnauthorizedException', 401],
            ['Cake\Http\Exception\UnavailableForLegalReasonsException', 451],
            ['Cake\Network\Exception\SocketException', 0],
            ['Cake\ORM\Exception\MissingBehaviorException', 0],
            ['Cake\ORM\Exception\MissingEntityException', 0],
            ['Cake\ORM\Exception\MissingTableClassException', 0],
            ['Cake\ORM\Exception\RolledbackTransactionException', 0],
            ['Cake\Routing\Exception\DuplicateNamedRouteException', 0],
            ['Cake\Routing\Exception\MissingDispatcherFilterException', 0],
            ['Cake\Routing\Exception\MissingRouteException', 0],
            ['Cake\Routing\Exception\RedirectException', 302],
            ['Cake\Utility\Exception\XmlException', 0],
            ['Cake\View\Exception\MissingCellException', 0],
            ['Cake\View\Exception\MissingHelperException', 0],
            ['Cake\View\Exception\MissingViewException', 0],
        ];
    }
}
