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
    public function testSimpleException(string $class, int $defaultCode): void
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
    public static function exceptionProvider(): \Iterator
    {
        yield [\Cake\Console\Exception\ConsoleException::class, 1];
        yield [\Cake\Console\Exception\MissingHelperException::class, 1];
        yield [\Cake\Console\Exception\StopException::class, 1];
        yield [\Cake\Controller\Exception\AuthSecurityException::class, 400];
        yield [\Cake\Controller\Exception\MissingActionException::class, 0];
        yield [\Cake\Controller\Exception\MissingComponentException::class, 0];
        yield [\Cake\Controller\Exception\SecurityException::class, 400];
        yield [\Cake\Core\Exception\CakeException::class, 0];
        yield [\Cake\Core\Exception\MissingPluginException::class, 0];
        yield [\Cake\Database\Exception\DatabaseException::class, 0];
        yield [\Cake\Database\Exception\MissingConnectionException::class, 0];
        yield [\Cake\Database\Exception\MissingDriverException::class, 0];
        yield [\Cake\Database\Exception\MissingExtensionException::class, 0];
        yield [\Cake\Database\Exception\NestedTransactionRollbackException::class, 0];
        yield [\Cake\Datasource\Exception\InvalidPrimaryKeyException::class, 0];
        yield [\Cake\Datasource\Exception\MissingDatasourceConfigException::class, 0];
        yield [\Cake\Datasource\Exception\MissingDatasourceException::class, 0];
        yield [\Cake\Datasource\Exception\MissingModelException::class, 0];
        yield [\Cake\Datasource\Exception\RecordNotFoundException::class, 0];
        yield [\Cake\Datasource\Paging\Exception\PageOutOfBoundsException::class, 0];
        yield [\Cake\Mailer\Exception\MissingActionException::class, 0];
        yield [\Cake\Mailer\Exception\MissingMailerException::class, 0];
        yield [\Cake\Http\Exception\BadRequestException::class, 400];
        yield [\Cake\Http\Exception\ConflictException::class, 409];
        yield [\Cake\Http\Exception\ForbiddenException::class, 403];
        yield [\Cake\Http\Exception\GoneException::class, 410];
        yield [\Cake\Http\Exception\HttpException::class, 500];
        yield [\Cake\Http\Exception\InternalErrorException::class, 500];
        yield [\Cake\Http\Exception\InvalidCsrfTokenException::class, 403];
        yield [\Cake\Http\Exception\MethodNotAllowedException::class, 405];
        yield [\Cake\Http\Exception\MissingControllerException::class, 404];
        yield [\Cake\Http\Exception\NotAcceptableException::class, 406];
        yield [\Cake\Http\Exception\NotFoundException::class, 404];
        yield [\Cake\Http\Exception\NotImplementedException::class, 501];
        yield [\Cake\Http\Exception\ServiceUnavailableException::class, 503];
        yield [\Cake\Http\Exception\UnauthorizedException::class, 401];
        yield [\Cake\Http\Exception\UnavailableForLegalReasonsException::class, 451];
        yield [\Cake\Network\Exception\SocketException::class, 0];
        yield [\Cake\ORM\Exception\MissingBehaviorException::class, 0];
        yield [\Cake\ORM\Exception\MissingEntityException::class, 0];
        yield [\Cake\ORM\Exception\MissingTableClassException::class, 0];
        yield [\Cake\ORM\Exception\RolledbackTransactionException::class, 0];
        yield [\Cake\Routing\Exception\DuplicateNamedRouteException::class, 0];
        yield [\Cake\Routing\Exception\MissingRouteException::class, 0];
        yield [\Cake\Utility\Exception\XmlException::class, 0];
        yield [\Cake\View\Exception\MissingCellException::class, 0];
        yield [\Cake\View\Exception\MissingHelperException::class, 0];
        yield [\Cake\View\Exception\MissingViewException::class, 0];
    }
}
