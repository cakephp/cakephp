<?php
declare(strict_types=1);

namespace TestApp\Routing\Route;

use Cake\Routing\Route\Route;
use Psr\Http\Message\ServerRequestInterface;

class AddQueryParamRoute extends Route
{
    public function parseRequest(ServerRequestInterface $request): ?array
    {
        $params = parent::parseRequest($request);
        if (is_array($params)) {
            $params['?']['x'] = '1';
        }

        return $params;
    }
}
