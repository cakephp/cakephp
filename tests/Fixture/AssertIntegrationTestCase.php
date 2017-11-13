<?php
namespace Cake\Test\Fixture;

use Cake\Http\Response;
use Cake\TestSuite\IntegrationTestCase;

/**
 * This class helps in indirectly testing the functionality of IntegrationTestCase
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
        $this->_response = $this->_response->withLocation('http://localhost/tasks/index');

        $this->assertNoRedirect();
    }
}
