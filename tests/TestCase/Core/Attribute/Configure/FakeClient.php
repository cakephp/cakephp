<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Core\Attribute\Configure;

use Cake\Core\Attribute\Configure;

class FakeClient
{
    /**
     * @param string $apiKey
     */
    public function __construct(
        #[Configure('Star.apiKey')]
        public string $apiKey,
    ) {
    }
}
