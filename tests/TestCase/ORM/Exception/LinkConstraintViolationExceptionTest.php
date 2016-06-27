<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\Exception;

use Cake\ORM\Exception\LinkConstraintViolationException;
use Cake\TestSuite\TestCase;

/**
 * LinkConstraintViolationException class test.
 */
class LinkConstraintViolationExceptionTest extends TestCase
{
    public function testGetDefaults()
    {
        $exception = new LinkConstraintViolationException('message');
        $this->assertNull($exception->getAssociation());
        $this->assertNull($exception->getAssociation());
    }

    public function testGetRepository()
    {
        $exception = new LinkConstraintViolationException([
            'repository' => 'Repository',
            'association' => 'Association'
        ]);
        $this->assertEquals('Repository', $exception->getRepository());
    }

    public function testGetAssociation()
    {
        $exception = new LinkConstraintViolationException([
            'repository' => 'Repository',
            'association' => 'Association'
        ]);
        $this->assertEquals('Association', $exception->getAssociation());
    }
}
