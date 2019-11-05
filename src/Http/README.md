[![Total Downloads](https://img.shields.io/packagist/dt/cakephp/http.svg?style=flat-square)](https://packagist.org/packages/cakephp/http)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.txt)

# CakePHP Http Library

This library provides a PSR-15 Http middleware server, PSR-7 Request and
Response objects, and a PSR-18 Http Client. Together these classes let you
handle incoming server requests and send outgoing HTTP requests.

## Using the Http Client

Sending requests is straight forward. Doing a GET request looks like

```php
use Cake\Http\Client;

$http = new Client();

// Simple get
$response = $http->get('http://example.com/test.html');

// Simple get with querystring
$response = $http->get('http://example.com/search', ['q' => 'widget']);

// Simple get with querystring & additional headers
$response = $http->get('http://example.com/search', ['q' => 'widget'], [
  'headers' => ['X-Requested-With' => 'XMLHttpRequest']
]);
```

To learn more read the [Http Client documentation](https://book.cakephp.org/4.0/en/core-libraries/httpclient.html).

## Using the Http Server

The Http Server allows an `HttpApplicationInterface` to process requests and
emit responses. To get started first implement the
`Cake\Http\HttpApplicationInterface`, or extend `Cake\Http\BaseApplication` if
you are also using CakePHP's console libraries.  A minimal example would could
look like:

```php
namespace App

use Cake\Http\BaseApplication;

class Application implements HttpApplicationInterface
{
    /**
     * Load all the application configuration and bootstrap logic.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // Load configuration here. This is the first
        // method Cake\Http\Server will call on your application.
    }

    /**
     * Define the HTTP middleware layers for an application.
     *
     * @param \Cake\Http\MiddlewareQueue $middleware The middleware queue to set in your App Class
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middleware): MiddlewareQueue
    {
        // Add middleware for your application.
        return $middleware;
    }
}
```

Once you have an application with some middleware. You can start accepting
requests. In your application's webroot, you can add an `index.php` and process
requests:

```php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Application;
use Cake\Http\Server;

// Bind your application to the server.
$server = new Server(new Application());

// Run the request/response through the application and emit the response.
$server->emit($server->run());
```

For more information on middleware, [consult the
documentation](https://book.cakephp.org/4.0/en/controllers/middleware.html)
