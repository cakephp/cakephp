<?php
namespace Cake\Test\Fixture;

use Cake\Network\Response;
use Cake\TestSuite\IntegrationTestCase;

/**
 * This class helps in indirectly testing the functionalities of IntegrationTestCase
 */
class AssertIntegrationTestCase extends IntegrationTestCase
{

    /**
     * testBadAssertNoRedirect
     *
     * @return void
     */
    public function testBadAssertNoRedirect()
    {
        $this->_response = new Response();
        $this->_response->header('Location', 'http://localhost/tasks/index');

        $this->assertNoRedirect();
    }
}
