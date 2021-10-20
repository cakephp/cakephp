<?php
declare(strict_types=1);

namespace Cake\Test\test_app\TestApp\Stub;

use Cake\Database\Expression\CaseStatementExpression;

class CaseStatementExpressionStub extends CaseStatementExpression
{
    /**
     * Returns the case value type.
     *
     * @return string|null
     */
    public function getValueType(): ?string
    {
        return $this->valueType;
    }

    /**
     * Returns the type of the `ELSE` result value.
     *
     * @return string|null The result type, or `null` if none has been set yet.
     */
    public function getElseType(): ?string
    {
        return $this->elseType;
    }
}
