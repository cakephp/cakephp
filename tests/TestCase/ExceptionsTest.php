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

use Cake\Console\Exception\ConsoleException;
use Cake\Console\Exception\MissingHelperException;
use Cake\Console\Exception\StopException;
use Cake\Controller\Exception\AuthSecurityException;
use Cake\Controller\Exception\MissingActionException;
use Cake\Controller\Exception\MissingComponentException;
use Cake\Controller\Exception\SecurityException;
use Cake\Core\Exception\CakeException;
use Cake\Core\Exception\MissingPluginException;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Exception\MissingDriverException;
use Cake\Database\Exception\MissingExtensionException;
use Cake\Database\Exception\NestedTransactionRollbackException;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use Cake\Datasource\Exception\MissingDatasourceException;
use Cake\Datasource\Exception\MissingModelException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\Paging\Exception\PageOutOfBoundsException;
use Cake\Error\FatalErrorException;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ConflictException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\GoneException;
use Cake\Http\Exception\HttpException;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\InvalidCsrfTokenException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\MissingControllerException;
use Cake\Http\Exception\NotAcceptableException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\NotImplementedException;
use Cake\Http\Exception\ServiceUnavailableException;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Http\Exception\UnavailableForLegalReasonsException;
use Cake\Mailer\Exception\MissingMailerException;
use Cake\Network\Exception\SocketException;
use Cake\ORM\Entity;
use Cake\ORM\Exception\MissingBehaviorException;
use Cake\ORM\Exception\MissingEntityException;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Exception\RolledbackTransactionException;
use Cake\Routing\Exception\DuplicateNamedRouteException;
use Cake\Routing\Exception\MissingRouteException;
use Cake\TestSuite\TestCase;
use Cake\Utility\Exception\XmlException;
use Cake\View\Exception\MissingCellException;
use Cake\View\Exception\MissingCellTemplateException;
use Cake\View\Exception\MissingElementException;
use Cake\View\Exception\MissingLayoutException;
use Cake\View\Exception\MissingTemplateException;
use Cake\View\Exception\MissingViewException;
use Exception;
use Iterator;

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
    public static function exceptionProvider(): Iterator
    {
        yield [ConsoleException::class, 1];
        yield [MissingHelperException::class, 1];
        yield [StopException::class, 1];
        yield [AuthSecurityException::class, 400];
        yield [MissingActionException::class, 0];
        yield [MissingComponentException::class, 0];
        yield [SecurityException::class, 400];
        yield [CakeException::class, 0];
        yield [MissingPluginException::class, 0];
        yield [DatabaseException::class, 0];
        yield [MissingConnectionException::class, 0];
        yield [MissingDriverException::class, 0];
        yield [MissingExtensionException::class, 0];
        yield [NestedTransactionRollbackException::class, 0];
        yield [InvalidPrimaryKeyException::class, 0];
        yield [MissingDatasourceConfigException::class, 0];
        yield [MissingDatasourceException::class, 0];
        yield [MissingModelException::class, 0];
        yield [RecordNotFoundException::class, 0];
        yield [PageOutOfBoundsException::class, 0];
        yield [\Cake\Mailer\Exception\MissingActionException::class, 0];
        yield [MissingMailerException::class, 0];
        yield [BadRequestException::class, 400];
        yield [ConflictException::class, 409];
        yield [ForbiddenException::class, 403];
        yield [GoneException::class, 410];
        yield [HttpException::class, 500];
        yield [InternalErrorException::class, 500];
        yield [InvalidCsrfTokenException::class, 403];
        yield [MethodNotAllowedException::class, 405];
        yield [MissingControllerException::class, 404];
        yield [NotAcceptableException::class, 406];
        yield [NotFoundException::class, 404];
        yield [NotImplementedException::class, 501];
        yield [ServiceUnavailableException::class, 503];
        yield [UnauthorizedException::class, 401];
        yield [UnavailableForLegalReasonsException::class, 451];
        yield [SocketException::class, 0];
        yield [MissingBehaviorException::class, 0];
        yield [MissingEntityException::class, 0];
        yield [MissingTableClassException::class, 0];
        yield [RolledbackTransactionException::class, 0];
        yield [DuplicateNamedRouteException::class, 0];
        yield [MissingRouteException::class, 0];
        yield [XmlException::class, 0];
        yield [MissingCellException::class, 0];
        yield [\Cake\View\Exception\MissingHelperException::class, 0];
        yield [MissingViewException::class, 0];
    }
}
