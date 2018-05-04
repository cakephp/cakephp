<?php
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
use Cake\Error\PHP7ErrorException;
use Cake\ORM\Entity;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\TestSuite\TestCase;
use Error;
use Exception;

class ExceptionsTest extends TestCase
{

    /**
     * Tests simple exceptions work.
     *
     * @dataProvider exceptionProvider
     * @param $class The exception class name
     * @param $defaultCode The default exception code
     * @return void
     */
    public function testSimpleException($class, $defaultCode)
    {
        $previous = new Exception();

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
     *
     * @return void
     */
    public function testFatalErrorException()
    {
        $previous = new Exception();

        $exception = new FatalErrorException('message', 100, __FILE__, 1, $previous);
        $this->assertSame('message', $exception->getMessage());
        $this->assertSame(100, $exception->getCode());
        $this->assertSame(__FILE__, $exception->getFile());
        $this->assertSame(1, $exception->getLine());
        $this->assertSame($previous, $exception->getPrevious());

        $exception = new FatalErrorException('message', null, __FILE__, 1, $previous);
        $this->assertSame('message', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertSame(__FILE__, $exception->getFile());
        $this->assertSame(1, $exception->getLine());
        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * Tests PHP7ErrorException works.
     *
     * @return void
     */
    public function testPHP7ErrorException()
    {
        $this->skipIf(version_compare(PHP_VERSION, '7.0.0', '<'));

        $previous = new Exception();
        $error = new Error('message', 100, $previous);
        $line = __LINE__ - 1;

        $exception = new PHP7ErrorException($error);
        $this->assertSame(100, $exception->getCode());
        $this->assertSame(__FILE__, $exception->getFile());
        $this->assertSame($line, $exception->getLine());
        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * Tests PersistenceFailedException works.
     *
     * @return void
     */
    public function testPersistenceFailedException()
    {
        $previous = new Exception();
        $entity = new Entity();

        $exception = new PersistenceFailedException($entity, 'message', 100, $previous);
        $this->assertSame('message', $exception->getMessage());
        $this->assertSame(100, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame($entity, $exception->getEntity());

        $exception = new PersistenceFailedException(new Entity, 'message', null, $previous);
        $this->assertSame('message', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * Provides pairs of exception name and default code.
     *
     * @return array
     */
    public function exceptionProvider()
    {
        return [
            ['Cake\Console\Exception\ConsoleException', 500],
            ['Cake\Console\Exception\MissingHelperException', 500],
            ['Cake\Console\Exception\MissingShellException', 500],
            ['Cake\Console\Exception\MissingShellMethodException', 500],
            ['Cake\Console\Exception\MissingTaskException', 500],
            ['Cake\Console\Exception\StopException', 500],
            ['Cake\Controller\Exception\AuthSecurityException', 400],
            ['Cake\Controller\Exception\MissingActionException', 404],
            ['Cake\Controller\Exception\MissingComponentException', 500],
            ['Cake\Controller\Exception\SecurityException', 400],
            ['Cake\Core\Exception\Exception', 500],
            ['Cake\Core\Exception\MissingPluginException', 500],
            ['Cake\Database\Exception', 500],
            ['Cake\Database\Exception\MissingConnectionException', 500],
            ['Cake\Database\Exception\MissingDriverException', 500],
            ['Cake\Database\Exception\MissingExtensionException', 500],
            ['Cake\Database\Exception\NestedTransactionRollbackException', 500],
            ['Cake\Datasource\Exception\InvalidPrimaryKeyException', 404],
            ['Cake\Datasource\Exception\MissingDatasourceConfigException', 500],
            ['Cake\Datasource\Exception\MissingDatasourceException', 500],
            ['Cake\Datasource\Exception\MissingModelException', 500],
            ['Cake\Datasource\Exception\PageOutOfBoundsException', 404],
            ['Cake\Datasource\Exception\RecordNotFoundException', 404],
            ['Cake\Mailer\Exception\MissingActionException', 404],
            ['Cake\Mailer\Exception\MissingMailerException', 500],
            ['Cake\Http\Exception\BadRequestException', 400],
            ['Cake\Http\Exception\ConflictException', 409],
            ['Cake\Http\Exception\ForbiddenException', 403],
            ['Cake\Http\Exception\GoneException', 410],
            ['Cake\Http\Exception\HttpException', 500],
            ['Cake\Http\Exception\InternalErrorException', 500],
            ['Cake\Http\Exception\InvalidCsrfTokenException', 403],
            ['Cake\Http\Exception\MethodNotAllowedException', 405],
            ['Cake\Http\Exception\NotAcceptableException', 406],
            ['Cake\Http\Exception\NotFoundException', 404],
            ['Cake\Http\Exception\NotImplementedException', 501],
            ['Cake\Http\Exception\ServiceUnavailableException', 503],
            ['Cake\Http\Exception\UnauthorizedException', 401],
            ['Cake\Http\Exception\UnavailableForLegalReasonsException', 451],
            ['Cake\Network\Exception\SocketException', 0],
            ['Cake\ORM\Exception\MissingBehaviorException', 500],
            ['Cake\ORM\Exception\MissingEntityException', 500],
            ['Cake\ORM\Exception\MissingTableClassException', 500],
            ['Cake\ORM\Exception\RolledbackTransactionException', 500],
            ['Cake\Routing\Exception\DuplicateNamedRouteException', 500],
            ['Cake\Routing\Exception\MissingControllerException', 500],
            ['Cake\Routing\Exception\MissingDispatcherFilterException', 500],
            ['Cake\Routing\Exception\MissingRouteException', 500],
            ['Cake\Routing\Exception\RedirectException', 302],
            ['Cake\Utility\Exception\XmlException', 0],
            ['Cake\View\Exception\MissingCellException', 500],
            ['Cake\View\Exception\MissingCellViewException', 500],
            ['Cake\View\Exception\MissingElementException', 500],
            ['Cake\View\Exception\MissingHelperException', 500],
            ['Cake\View\Exception\MissingLayoutException', 500],
            ['Cake\View\Exception\MissingTemplateException', 500],
            ['Cake\View\Exception\MissingViewException', 500],
        ];
    }
}
