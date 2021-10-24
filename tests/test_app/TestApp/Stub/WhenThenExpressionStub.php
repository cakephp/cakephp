<?php
declare(strict_types=1);

namespace Cake\Test\test_app\TestApp\Stub;

use Cake\Database\Expression\WhenThenExpression;

class WhenThenExpressionStub extends WhenThenExpression
{
    /**
     * Returns the `WHEN` value type.
     *
     * @return array|string|null
     * @see when()
     */
    public function getWhenType(): ?string
    {
        return $this->whenType;
    }
}
