<?php
declare(strict_types=1);

namespace TestApp\Routing;

use Cake\Routing\Asset as CakeAsset;

class Asset extends CakeAsset
{
    public static function url(string $path, array $options = []): string
    {
        return parent::url($path, $options) . '?appHash';
    }
}
