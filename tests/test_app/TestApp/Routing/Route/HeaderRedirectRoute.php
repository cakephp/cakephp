<?php
declare(strict_types=1);

namespace TestApp\Routing\Route;

use Cake\Http\Exception\RedirectException;
use Cake\Routing\Route\Route as Route;

class HeaderRedirectRoute extends Route
{
    public function parse(string $url, string $method = ''): ?array
    {
        $params = parent::parse($url, $method);
        if (!$params) {
            return null;
        }
        throw new RedirectException('http://localhost/pages', 301, ['Redirect-Exception' => 'yes']);
    }
}
