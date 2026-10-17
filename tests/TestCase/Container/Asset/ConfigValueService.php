<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

class ConfigValueService
{
    public function __construct(
        #[ConfigValueAttribute('model', ['model' => 'cfg-model'])]
        public readonly string $fromConfig,
        #[ConfigValueAttribute('missing')]
        public readonly string $fromDefault = 'param-default',
    ) {
    }
}
