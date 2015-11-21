<?php
namespace Cake\Test\TestCase\Network;

use Cake\Network\CorsBuilder;
use Cake\Network\Response;
use Cake\TestSuite\TestCase;

class CorsBuilderTest extends TestCase
{
    /**
     * test allowOrigin() setting allow-origin
     *
     * @return void
     */
    public function testAllowOriginArray()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, 'http://www.example.com');
        $this->assertSame($builder, $builder->allowOrigin(['*.example.com', '*.foo.com']));
        $this->assertHeader('http://www.example.com', $response, 'Access-Control-Origin');
    }

    /**
     * test allowOrigin() setting allow-origin
     *
     * @return void
     */
    public function testOriginString()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, 'http://www.example.com');
        $this->assertSame($builder, $builder->allowOrigin('*.example.com'));
        $this->assertHeader('http://www.example.com', $response, 'Access-Control-Origin');
    }

    /**
     * Helper for checking header values.
     *
     * @param string $expected The expected value
     * @param \Cake\Network\Response $response The Response object.
     * @params string $header The header key to check
     */
    protected function assertHeader($expected, Response $response, $header)
    {
        $headers = $response->header();
        $this->assertArrayHasKey($header, $headers, 'Header key not found.');
        $this->assertEquals($expected, $headers[$header], 'Header value not found.');
    }
}
