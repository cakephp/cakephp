<?php
declare(strict_types=1);

namespace TestApp\Routing\Route;

use Cake\Routing\Route\InflectedRoute;

class DashedRoute extends InflectedRoute
{
    protected function _underscore(array $url): array
    {
        $url = parent::_underscore($url);

        if (!empty($url['controller'])) {
            $url['controller'] = str_replace('_', '-', $url['controller']);
        }
        if (!empty($url['plugin'])) {
            $url['plugin'] = str_replace('_', '-', $url['plugin']);
        }

        return $url;
    }
}
