<?php
namespace TestApp\Http;

use Cake\Http\BaseApplication;

class InvalidMiddlewareApplication extends BaseApplication
{
    /**
     * @param \Cake\Http\MiddlewareQueue $middleware The middleware stack to set in your App Class
     * @return null
     */
    public function middleware($middleware)
    {
        return null;
    }
}
