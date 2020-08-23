<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Http;

use Cake\Http\CorsBuilder;
use Cake\Http\Response;
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
        $this->assertNoHeader($builder->build(), 'Access-Control-Origin');
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
        $this->assertSame($builder, $builder->allowOrigin('*'));
        $this->assertHeader('*', $builder->build(), 'Access-Control-Allow-Origin');

        $response = new Response();
        $builder = new CorsBuilder($response, 'http://www.example.com');
        $this->assertSame($builder, $builder->allowOrigin(['*.example.com', '*.foo.com']));
        $builder->build();
        $this->assertHeader('http://www.example.com', $builder->build(), 'Access-Control-Allow-Origin');

        $response = new Response();
        $builder = new CorsBuilder($response, 'http://www.example.com');
        $this->assertSame($builder, $builder->allowOrigin('*.example.com'));
        $this->assertHeader('http://www.example.com', $builder->build(), 'Access-Control-Allow-Origin');
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
        $this->assertNoHeader($response, 'Access-Control-Allow-Origin');

        $response = new Response();
        $builder = new CorsBuilder($response, 'http://www.example.com', true);
        $this->assertSame($builder, $builder->allowOrigin('https://example.com'));
        $this->assertNoHeader($builder->build(), 'Access-Control-Allow-Origin');

        $response = new Response();
        $builder = new CorsBuilder($response, 'http://www.example.com');
        $this->assertSame($builder, $builder->allowOrigin('https://example.com'));
        $this->assertNoHeader($builder->build(), 'Access-Control-Allow-Origin');
    }

    public function testAllowMethods()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, 'http://example.com');
        $builder->allowOrigin('*');
        $this->assertSame($builder, $builder->allowMethods(['GET', 'POST']));
        $this->assertHeader('GET, POST', $builder->build(), 'Access-Control-Allow-Methods');
    }

    public function testAllowCredentials()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, 'http://example.com');
        $builder->allowOrigin('*');
        $this->assertSame($builder, $builder->allowCredentials());
        $this->assertHeader('true', $builder->build(), 'Access-Control-Allow-Credentials');
    }

    public function testAllowHeaders()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, 'http://example.com');
        $builder->allowOrigin('*');
        $this->assertSame($builder, $builder->allowHeaders(['Content-Type', 'Accept']));
        $this->assertHeader('Content-Type, Accept', $builder->build(), 'Access-Control-Allow-Headers');
    }

    public function testExposeHeaders()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, 'http://example.com');
        $builder->allowOrigin('*');
        $this->assertSame($builder, $builder->exposeHeaders(['Content-Type', 'Accept']));
        $this->assertHeader('Content-Type, Accept', $builder->build(), 'Access-Control-Expose-Headers');
    }

    public function testMaxAge()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, 'http://example.com');
        $builder->allowOrigin('*');
        $this->assertSame($builder, $builder->maxAge(300));
        $this->assertHeader('300', $builder->build(), 'Access-Control-Max-Age');
    }

    /**
     * When no origin is allowed, none of the other headers should be applied.
     *
     * @return void
     */
    public function testNoAllowedOriginNoHeadersSet()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, 'http://example.com');
        $response = $builder->allowCredentials()
            ->allowMethods(['GET', 'POST'])
            ->allowHeaders(['Content-Type'])
            ->exposeHeaders(['X-CSRF-Token'])
            ->maxAge(300)
            ->build();
        $this->assertNoHeader($response, 'Access-Control-Allow-Origin');
        $this->assertNoHeader($response, 'Access-Control-Allow-Headers');
        $this->assertNoHeader($response, 'Access-Control-Expose-Headers');
        $this->assertNoHeader($response, 'Access-Control-Allow-Methods');
        $this->assertNoHeader($response, 'Access-Control-Allow-Authentication');
        $this->assertNoHeader($response, 'Access-Control-Max-Age');
    }

    /**
     * When an invalid origin is used, none of the other headers should be applied.
     *
     * @return void
     */
    public function testInvalidAllowedOriginNoHeadersSet()
    {
        $response = new Response();
        $builder = new CorsBuilder($response, 'http://example.com');
        $response = $builder->allowOrigin(['http://google.com'])
            ->allowCredentials()
            ->allowMethods(['GET', 'POST'])
            ->allowHeaders(['Content-Type'])
            ->exposeHeaders(['X-CSRF-Token'])
            ->maxAge(300)
            ->build();
        $this->assertNoHeader($response, 'Access-Control-Allow-Origin');
        $this->assertNoHeader($response, 'Access-Control-Allow-Headers');
        $this->assertNoHeader($response, 'Access-Control-Expose-Headers');
        $this->assertNoHeader($response, 'Access-Control-Allow-Methods');
        $this->assertNoHeader($response, 'Access-Control-Allow-Authentication');
        $this->assertNoHeader($response, 'Access-Control-Max-Age');
    }

    /**
     * Helper for checking header values.
     *
     * @param string $expected The expected value
     * @param \Cake\Http\Response $response The Response object.
     * @params string $header The header key to check
     */
    protected function assertHeader($expected, Response $response, $header)
    {
        $this->assertTrue($response->hasHeader($header), 'Header key not found.');
        $this->assertSame($expected, $response->getHeaderLine($header), 'Header value not found.');
    }

    /**
     * Helper for checking header values.
     *
     * @param \Cake\Http\Response $response The Response object.
     * @params string $header The header key to check
     */
    protected function assertNoHeader(Response $response, $header)
    {
        $this->assertFalse($response->hasHeader($header), 'Header key was found.');
    }
}
