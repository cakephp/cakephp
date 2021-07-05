<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\Http\Response;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * This class helps in indirectly testing the functionality of IntegrationTestCase
 */
class AssertIntegrationTestCase extends TestCase
{
    use IntegrationTestTrait;

    /**
     * testBadAssertNoRedirect
     */
    public function testBadAssertNoRedirect(): void
    {
        $this->_response = new Response();
        $this->_response = $this->_response->withLocation('http://localhost/tasks/index');

        $this->assertNoRedirect();
    }
}
