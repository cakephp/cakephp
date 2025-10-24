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
use Cake\Controller\Exception\FormProtectionException;
use Cake\Controller\Exception\MissingActionException;
use Cake\Controller\Exception\MissingComponentException;
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
use Cake\Mailer\Exception\MissingActionException as MailerMissingActionException;
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
use Cake\View\Exception\MissingHelperException as ViewMissingHelperException;
use Cake\View\Exception\MissingLayoutException;
use Cake\View\Exception\MissingTemplateException;
use Cake\View\Exception\MissingViewException;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;

class ExceptionsTest extends TestCase
{
    /**
     * Tests simple exceptions work.
     *
     * @param string $class The exception class name
     * @param int $defaultCode The default exception code
     */
    #[DataProvider('exceptionProvider')]
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
    public static function exceptionProvider(): array
    {
        return [
            [ConsoleException::class, 1],
            [MissingHelperException::class, 1],
            [StopException::class, 1],
            [FormProtectionException::class, 400],
            [MissingActionException::class, 404],
            [MissingComponentException::class, 0],
            [CakeException::class, 0],
            [MissingPluginException::class, 0],
            [DatabaseException::class, 0],
            [MissingConnectionException::class, 0],
            [MissingDriverException::class, 0],
            [MissingExtensionException::class, 0],
            [NestedTransactionRollbackException::class, 0],
            [InvalidPrimaryKeyException::class, 0],
            [MissingDatasourceConfigException::class, 0],
            [MissingDatasourceException::class, 0],
            [MissingModelException::class, 0],
            [RecordNotFoundException::class, 404],
            [PageOutOfBoundsException::class, 404],
            [MailerMissingActionException::class, 0],
            [MissingMailerException::class, 0],
            [BadRequestException::class, 400],
            [ConflictException::class, 409],
            [ForbiddenException::class, 403],
            [GoneException::class, 410],
            [HttpException::class, 500],
            [InternalErrorException::class, 500],
            [InvalidCsrfTokenException::class, 403],
            [MethodNotAllowedException::class, 405],
            [MissingControllerException::class, 404],
            [NotAcceptableException::class, 406],
            [NotFoundException::class, 404],
            [NotImplementedException::class, 501],
            [ServiceUnavailableException::class, 503],
            [UnauthorizedException::class, 401],
            [UnavailableForLegalReasonsException::class, 451],
            [SocketException::class, 0],
            [MissingBehaviorException::class, 0],
            [MissingEntityException::class, 0],
            [MissingTableClassException::class, 0],
            [RolledbackTransactionException::class, 0],
            [DuplicateNamedRouteException::class, 0],
            [MissingRouteException::class, 404],
            [XmlException::class, 0],
            [MissingCellException::class, 0],
            [ViewMissingHelperException::class, 0],
            [MissingViewException::class, 0],
        ];
    }
}
