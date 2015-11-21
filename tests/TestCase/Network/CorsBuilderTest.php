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
    public function testAllowOriginNoOrigin()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, '');
        $this->assertSame($builder, $builder->allowOrigin(['*.example.com', '*.foo.com']));
        $this->assertNoHeader($response, 'Access-Control-Origin');
    }

    /**
     * test allowOrigin() setting allow-origin
     *
     * @return void
     */
    public function testAllowOrigin()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, 'http://www.example.com');
        $this->assertSame($builder, $builder->allowOrigin(['*.example.com', '*.foo.com']));
        $this->assertHeader('http://www.example.com', $response, 'Access-Control-Origin');

        $response = new Response();
        $builder = new CorsBuilder($response, 'http://www.example.com');
        $this->assertSame($builder, $builder->allowOrigin('*.example.com'));
        $this->assertHeader('http://www.example.com', $response, 'Access-Control-Origin');
    }

    /**
     * test allowOrigin() with SSL
     *
     * @return void
     */
    public function testAllowOriginSsl()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, 'https://www.example.com', true);
        $this->assertSame($builder, $builder->allowOrigin('http://example.com'));
        $this->assertNoHeader($response, 'Access-Control-Origin');

        $response = new Response();
        $builder = new CorsBuilder($response, 'http://www.example.com', true);
        $this->assertSame($builder, $builder->allowOrigin('https://example.com'));
        $this->assertNoHeader($response, 'Access-Control-Origin');

        $response = new Response();
        $builder = new CorsBuilder($response, 'http://www.example.com');
        $this->assertSame($builder, $builder->allowOrigin('https://example.com'));
        $this->assertNoHeader($response, 'Access-Control-Origin');
    }

    public function testAllowMethodsNoOrigin()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, '');
        $this->assertSame($builder, $builder->allowMethods(['GET', 'POST']));
        $this->assertNoHeader($response, 'Access-Control-Allow-Methods');
    }

    public function testAllowMethods()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, 'http://example.com');
        $this->assertSame($builder, $builder->allowMethods(['GET', 'POST']));
        $this->assertHeader('GET, POST', $response, 'Access-Control-Allow-Methods');
    }

    public function testAllowCredentialsNoOrigin()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, '');
        $this->assertSame($builder, $builder->allowCredentials());
        $this->assertNoHeader($response, 'Access-Control-Allow-Credentials');
    }

    public function testAllowCredentials()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, 'http://example.com');
        $this->assertSame($builder, $builder->allowCredentials());
        $this->assertHeader('true', $response, 'Access-Control-Allow-Credentials');
    }

    public function testAllowHeadersNoOrigin()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, '');
        $this->assertSame($builder, $builder->allowHeaders(['X-THING']));
        $this->assertNoHeader($response, 'Access-Control-Allow-Headers');
    }

    public function testAllowHeaders()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, 'http://example.com');
        $this->assertSame($builder, $builder->allowHeaders(['Content-Type', 'Accept']));
        $this->assertHeader('Content-Type, Accept', $response, 'Access-Control-Allow-Headers');
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

    /**
     * Helper for checking header values.
     *
     * @param \Cake\Network\Response $response The Response object.
     * @params string $header The header key to check
     */
    protected function assertNoHeader(Response $response, $header)
    {
        $headers = $response->header();
        $this->assertArrayNotHasKey($header, $headers, 'Header key was found.');
    }
}
