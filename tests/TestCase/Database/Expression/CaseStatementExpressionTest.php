<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Chronos\Chronos;
use Cake\Chronos\Date as ChronosDate;
use Cake\Chronos\MutableDate as ChronosMutableDate;
use Cake\Database\Expression\CaseStatementExpression;
use Cake\Database\Expression\ComparisonExpression;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\WhenThenExpression;
use Cake\Database\TypeFactory;
use Cake\Database\TypeMap;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Date;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use Cake\I18n\Time;
use Cake\Test\test_app\TestApp\Database\Expression\CustomWhenThenExpression;
use Cake\Test\test_app\TestApp\Stub\CaseStatementExpressionStub;
use Cake\Test\test_app\TestApp\Stub\WhenThenExpressionStub;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use LogicException;
use stdClass;
use TestApp\Database\Type\CustomExpressionType;
use TestApp\View\Object\TestObjectWithToString;
use TypeError;

class CaseStatementExpressionTest extends TestCase
{
    // region Type handling

    public function testExpressionTypeCastingSimpleCase(): void
    {
        TypeFactory::map('custom', CustomExpressionType::class);

        $expression = (new CaseStatementExpression(1, 'custom'))
            ->when(1, 'custom')
            ->then(2, 'custom')
            ->else(3, 'custom');

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE CUSTOM(:param0) WHEN CUSTOM(:param1) THEN CUSTOM(:param2) ELSE CUSTOM(:param3) END',
            $sql
        );
    }

    public function testExpressionTypeCastingNullValues(): void
    {
        TypeFactory::map('custom', CustomExpressionType::class);

        $expression = (new CaseStatementExpression(null, 'custom'))
            ->when(1, 'custom')
            ->then(null, 'custom')
            ->else(null, 'custom');

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE CUSTOM(:param0) WHEN CUSTOM(:param1) THEN CUSTOM(:param2) ELSE CUSTOM(:param3) END',
            $sql
        );
    }

    public function testExpressionTypeCastingSearchedCase(): void
    {
        TypeFactory::map('custom', CustomExpressionType::class);

        $expression = (new CaseStatementExpression())
            ->when(['Table.column' => true], ['Table.column' => 'custom'])
            ->then(1, 'custom')
            ->else(2, 'custom');

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE WHEN Table.column = (CUSTOM(:param0)) THEN CUSTOM(:param1) ELSE CUSTOM(:param2) END',
            $sql
        );
    }

    public function testGetReturnType(): void
    {
        // all provided `then` and `else` types are the same, return
        // type can be inferred
        $expression = (new CaseStatementExpression())
            ->when(['Table.column_a' => true])
            ->then(1, 'integer')
            ->when(['Table.column_b' => true])
            ->then(2, 'integer')
            ->else(3, 'integer');
        $this->assertSame('integer', $expression->getReturnType());

        // all provided `then` an `else` types are the same, one `then`
        // type is `null`, return type can be inferred
        $expression = (new CaseStatementExpression())
            ->when(['Table.column_a' => true])
            ->then(1)
            ->when(['Table.column_b' => true])
            ->then(2, 'integer')
            ->else(3, 'integer');
        $this->assertSame('integer', $expression->getReturnType());

        // all `then` types are null, an `else` type was provided,
        // return type can be inferred
        $expression = (new CaseStatementExpression())
            ->when(['Table.column_a' => true])
            ->then(1)
            ->when(['Table.column_b' => true])
            ->then(2)
            ->else(3, 'integer');
        $this->assertSame('integer', $expression->getReturnType());

        // all provided `then` types are the same, the `else` type is
        // `null`, return type can be inferred
        $expression = (new CaseStatementExpression())
            ->when(['Table.column_a' => true])
            ->then(1, 'integer')
            ->when(['Table.column_b' => true])
            ->then(2, 'integer')
            ->else(3);
        $this->assertSame('integer', $expression->getReturnType());

        // no `then` or `else` types were provided, they are all `null`,
        // and will be derived from the passed value, return type can be
        // inferred
        $expression = (new CaseStatementExpression())
            ->when(['Table.column_a' => true])
            ->then(1)
            ->when(['Table.column_b' => true])
            ->then(2)
            ->else(3);
        $this->assertSame('integer', $expression->getReturnType());

        // all `then` and `else` point to columns of the same type,
        // return type can be inferred
        $typeMap = new TypeMap([
            'Table.column_a' => 'boolean',
            'Table.column_b' => 'boolean',
            'Table.column_c' => 'boolean',
        ]);
        $expression = (new CaseStatementExpression())
            ->setTypeMap($typeMap)
            ->when(['Table.column_a' => true])
            ->then(new IdentifierExpression('Table.column_a'))
            ->when(['Table.column_b' => true])
            ->then(new IdentifierExpression('Table.column_b'))
            ->else(new IdentifierExpression('Table.column_c'));
        $this->assertSame('boolean', $expression->getReturnType());

        // all `then` and `else` use the same custom type, return type
        // can be inferred
        $expression = (new CaseStatementExpression())
            ->when(['Table.column_a' => true])
            ->then(1, 'custom')
            ->when(['Table.column_b' => true])
            ->then(2, 'custom')
            ->else(3, 'custom');
        $this->assertSame('custom', $expression->getReturnType());

        // all `then` and `else` types were provided, but an explicit
        // return type was set, return type will be overwritten
        $expression = (new CaseStatementExpression())
            ->when(['Table.column_a' => true])
            ->then(1, 'integer')
            ->when(['Table.column_b' => true])
            ->then(2, 'integer')
            ->else(3, 'integer')
            ->setReturnType('string');
        $this->assertSame('string', $expression->getReturnType());

        // all `then` and `else` types are different, return type
        // cannot be inferred
        $expression = (new CaseStatementExpression())
            ->when(['Table.column_a' => true])
            ->then(true)
            ->when(['Table.column_b' => true])
            ->then(1)
            ->else(null);
        $this->assertSame('string', $expression->getReturnType());
    }

    public function testSetReturnType(): void
    {
        $expression = (new CaseStatementExpression())->else('1');
        $this->assertSame('string', $expression->getReturnType());

        $expression->setReturnType('float');
        $this->assertSame('float', $expression->getReturnType());
    }

    public function valueTypeInferenceDataProvider(): array
    {
        return [
            // Values that should have their type inferred because
            // they will be bound by the case expression.
            ['1', 'string'],
            [1, 'integer'],
            [1.0, 'float'],
            [true, 'boolean'],
            [ChronosDate::now(), 'date'],
            [Chronos::now(), 'datetime'],

            // Values that should not have a type inferred, either
            // because they are not bound by the case expression,
            // and/or because their type is obtained differently
            // (for example from a type map).
            [new IdentifierExpression('Table.column'), null],
            [new FunctionExpression('SUM', ['Table.column' => 'literal'], [], 'integer'), null],
            [new stdClass(), null],
            [null, null],
        ];
    }

    /**
     * @dataProvider valueTypeInferenceDataProvider
     * @param mixed $value The value from which to infer the type.
     * @param string|null $type The expected type.
     */
    public function testInferValueType($value, ?string $type): void
    {
        $expression = new CaseStatementExpressionStub();

        $this->assertNull($expression->getValueType());

        $expression = (new CaseStatementExpressionStub($value))
            ->setTypeMap(new TypeMap(['Table.column' => 'boolean']))
            ->when(1)
            ->then(2);

        $this->assertSame($type, $expression->getValueType());
    }

    public function whenTypeInferenceDataProvider(): array
    {
        return [
            // Values that should have their type inferred because
            // they will be bound by the case expression.
            ['1', 'string'],
            [1, 'integer'],
            [1.0, 'float'],
            [true, 'boolean'],
            [ChronosDate::now(), 'date'],
            [Chronos::now(), 'datetime'],

            // Values that should not have a type inferred, either
            // because they are not bound by the case expression,
            // and/or because their type is obtained differently
            // (for example from a type map).
            [new IdentifierExpression('Table.column'), null],
            [new FunctionExpression('SUM', ['Table.column' => 'literal'], [], 'integer'), null],
            [['Table.column' => true], null],
            [new stdClass(), null],
        ];
    }

    /**
     * @dataProvider whenTypeInferenceDataProvider
     * @param mixed $value The value from which to infer the type.
     * @param string|null $type The expected type.
     */
    public function testInferWhenType($value, ?string $type): void
    {
        $expression = (new CaseStatementExpressionStub())
            ->setTypeMap(new TypeMap(['Table.column' => 'boolean']));
        $expression->when(new WhenThenExpressionStub($expression->getTypeMap()));

        $this->assertNull($expression->clause('when')[0]->getWhenType());

        $expression->clause('when')[0]
            ->when($value)
            ->then(1);

        $this->assertSame($type, $expression->clause('when')[0]->getWhenType());
    }

    public function resultTypeInferenceDataProvider(): array
    {
        return [
            // Unless a result type has been set manually, values
            // should have their type inferred when possible.
            ['1', 'string'],
            [1, 'integer'],
            [1.0, 'float'],
            [true, 'boolean'],
            [ChronosDate::now(), 'date'],
            [Chronos::now(), 'datetime'],
            [new IdentifierExpression('Table.column'), 'boolean'],
            [new FunctionExpression('SUM', ['Table.column' => 'literal'], [], 'integer'), 'integer'],
            [new stdClass(), null],
            [null, null],
        ];
    }

    /**
     * @dataProvider resultTypeInferenceDataProvider
     * @param mixed $value The value from which to infer the type.
     * @param string|null $type The expected type.
     */
    public function testInferResultType($value, ?string $type): void
    {
        $expression = (new CaseStatementExpressionStub())
            ->setTypeMap(new TypeMap(['Table.column' => 'boolean']))
            ->when(function (WhenThenExpression $whenThen) {
                return $whenThen;
            });

        $this->assertNull($expression->clause('when')[0]->getResultType());

        $expression->clause('when')[0]
            ->when(['Table.column' => true])
            ->then($value);

        $this->assertSame($type, $expression->clause('when')[0]->getResultType());
    }

    /**
     * @dataProvider resultTypeInferenceDataProvider
     * @param mixed $value The value from which to infer the type.
     * @param string|null $type The expected type.
     */
    public function testInferElseType($value, ?string $type): void
    {
        $expression = new CaseStatementExpressionStub();

        $this->assertNull($expression->getElseType());

        $expression = (new CaseStatementExpressionStub())
            ->setTypeMap(new TypeMap(['Table.column' => 'boolean']));

        $this->assertNull($expression->getElseType());

        $expression->else($value);

        $this->assertSame($type, $expression->getElseType());
    }

    public function testWhenArrayValueInheritTypeMap(): void
    {
        $typeMap = new TypeMap([
            'Table.column_a' => 'boolean',
            'Table.column_b' => 'string',
        ]);

        $expression = (new CaseStatementExpression())
            ->setTypeMap($typeMap)
            ->when(['Table.column_a' => true])
            ->then(1)
            ->when(['Table.column_b' => 'foo'])
            ->then(2)
            ->else(3);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE WHEN Table.column_a = :c0 THEN :c1 WHEN Table.column_b = :c2 THEN :c3 ELSE :c4 END',
            $sql
        );
        $this->assertSame(
            [
                ':c0' => [
                    'value' => true,
                    'type' => 'boolean',
                    'placeholder' => 'c0',
                ],
                ':c1' => [
                    'value' => 1,
                    'type' => 'integer',
                    'placeholder' => 'c1',
                ],
                ':c2' => [
                    'value' => 'foo',
                    'type' => 'string',
                    'placeholder' => 'c2',
                ],
                ':c3' => [
                    'value' => 2,
                    'type' => 'integer',
                    'placeholder' => 'c3',
                ],
                ':c4' => [
                    'value' => 3,
                    'type' => 'integer',
                    'placeholder' => 'c4',
                ],
            ],
            $valueBinder->bindings()
        );
    }

    public function testWhenArrayValueWithExplicitTypes(): void
    {
        $typeMap = new TypeMap([
            'Table.column_a' => 'boolean',
            'Table.column_b' => 'string',
        ]);

        $expression = (new CaseStatementExpression())
            ->setTypeMap($typeMap)
            ->when(['Table.column_a' => 123], ['Table.column_a' => 'integer'])
            ->then(1)
            ->when(['Table.column_b' => 'foo'])
            ->then(2)
            ->else(3);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE WHEN Table.column_a = :c0 THEN :c1 WHEN Table.column_b = :c2 THEN :c3 ELSE :c4 END',
            $sql
        );
        $this->assertSame(
            [
                ':c0' => [
                    'value' => 123,
                    'type' => 'integer',
                    'placeholder' => 'c0',
                ],
                ':c1' => [
                    'value' => 1,
                    'type' => 'integer',
                    'placeholder' => 'c1',
                ],
                ':c2' => [
                    'value' => 'foo',
                    'type' => 'string',
                    'placeholder' => 'c2',
                ],
                ':c3' => [
                    'value' => 2,
                    'type' => 'integer',
                    'placeholder' => 'c3',
                ],
                ':c4' => [
                    'value' => 3,
                    'type' => 'integer',
                    'placeholder' => 'c4',
                ],
            ],
            $valueBinder->bindings()
        );
    }

    public function testWhenCallableArrayValueInheritTypeMap(): void
    {
        $typeMap = new TypeMap([
            'Table.column_a' => 'boolean',
            'Table.column_b' => 'string',
        ]);

        $expression = (new CaseStatementExpression())
            ->setTypeMap($typeMap)
            ->when(function (WhenThenExpression $whenThen) {
                return $whenThen
                    ->when(['Table.column_a' => true])
                    ->then(1);
            })
            ->when(function (WhenThenExpression $whenThen) {
                return $whenThen
                    ->when(['Table.column_b' => 'foo'])
                    ->then(2);
            })
            ->else(3);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE WHEN Table.column_a = :c0 THEN :c1 WHEN Table.column_b = :c2 THEN :c3 ELSE :c4 END',
            $sql
        );
        $this->assertSame(
            [
                ':c0' => [
                    'value' => true,
                    'type' => 'boolean',
                    'placeholder' => 'c0',
                ],
                ':c1' => [
                    'value' => 1,
                    'type' => 'integer',
                    'placeholder' => 'c1',
                ],
                ':c2' => [
                    'value' => 'foo',
                    'type' => 'string',
                    'placeholder' => 'c2',
                ],
                ':c3' => [
                    'value' => 2,
                    'type' => 'integer',
                    'placeholder' => 'c3',
                ],
                ':c4' => [
                    'value' => 3,
                    'type' => 'integer',
                    'placeholder' => 'c4',
                ],
            ],
            $valueBinder->bindings()
        );
    }

    public function testWhenCallableArrayValueWithExplicitTypes(): void
    {
        $typeMap = new TypeMap([
            'Table.column_a' => 'boolean',
            'Table.column_b' => 'string',
        ]);

        $expression = (new CaseStatementExpression())
            ->setTypeMap($typeMap)
            ->when(function (WhenThenExpression $whenThen) {
                return $whenThen
                    ->when(['Table.column_a' => 123], ['Table.column_a' => 'integer'])
                    ->then(1);
            })
            ->when(function (WhenThenExpression $whenThen) {
                return $whenThen
                    ->when(['Table.column_b' => 'foo'])
                    ->then(2);
            })
            ->else(3);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE WHEN Table.column_a = :c0 THEN :c1 WHEN Table.column_b = :c2 THEN :c3 ELSE :c4 END',
            $sql
        );
        $this->assertSame(
            [
                ':c0' => [
                    'value' => 123,
                    'type' => 'integer',
                    'placeholder' => 'c0',
                ],
                ':c1' => [
                    'value' => 1,
                    'type' => 'integer',
                    'placeholder' => 'c1',
                ],
                ':c2' => [
                    'value' => 'foo',
                    'type' => 'string',
                    'placeholder' => 'c2',
                ],
                ':c3' => [
                    'value' => 2,
                    'type' => 'integer',
                    'placeholder' => 'c3',
                ],
                ':c4' => [
                    'value' => 3,
                    'type' => 'integer',
                    'placeholder' => 'c4',
                ],
            ],
            $valueBinder->bindings()
        );
    }

    public function testWhenArrayValueRequiresArrayTypeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'When using an array for the `$when` argument, the `$type` ' .
            'argument must be an array too, `string` given.'
        );

        (new CaseStatementExpression())
            ->when(['Table.column' => 123], 'integer')
            ->then(1);
    }

    public function testWhenNonArrayValueRequiresStringTypeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'When using a non-array value for the `$when` argument, ' .
            'the `$type` argument must be a string, `array` given.'
        );

        (new CaseStatementExpression())
            ->when(123, ['Table.column' => 'integer'])
            ->then(1);
    }

    public function testInternalTypeMapChangesAreNonPersistent(): void
    {
        $typeMap = new TypeMap([
            'Table.column' => 'integer',
        ]);

        $expression = (new CaseStatementExpression())
            ->setTypeMap($typeMap)
            ->when(['Table.column' => 123])
            ->then(1)
            ->when(['Table.column' => 'foo'], ['Table.column' => 'string'])
            ->then('bar')
            ->when(['Table.column' => 456])
            ->then(2);

        $valueBinder = new ValueBinder();
        $expression->sql($valueBinder);
        $this->assertSame(
            [
                ':c0' => [
                    'value' => 123,
                    'type' => 'integer',
                    'placeholder' => 'c0',
                ],
                ':c1' => [
                    'value' => 1,
                    'type' => 'integer',
                    'placeholder' => 'c1',
                ],
                ':c2' => [
                    'value' => 'foo',
                    'type' => 'string',
                    'placeholder' => 'c2',
                ],
                ':c3' => [
                    'value' => 'bar',
                    'type' => 'string',
                    'placeholder' => 'c3',
                ],
                ':c4' => [
                    'value' => 456,
                    'type' => 'integer',
                    'placeholder' => 'c4',
                ],
                ':c5' => [
                    'value' => 2,
                    'type' => 'integer',
                    'placeholder' => 'c5',
                ],
            ],
            $valueBinder->bindings()
        );

        $this->assertSame($typeMap, $expression->getTypeMap());
    }

    // endregion

    // region SQL injections

    public function testSqlInjectionViaTypedCaseValueIsNotPossible(): void
    {
        $expression = (new CaseStatementExpression('1 THEN 1 END; DELETE * FROM foo; --', 'integer'))
            ->when(1)
            ->then(2);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE :c0 WHEN :c1 THEN :c2 ELSE NULL END',
            $sql
        );
        $this->assertSame(
            [
                ':c0' => [
                    'value' => '1 THEN 1 END; DELETE * FROM foo; --',
                    'type' => 'integer',
                    'placeholder' => 'c0',
                ],
                ':c1' => [
                    'value' => 1,
                    'type' => 'integer',
                    'placeholder' => 'c1',
                ],
                ':c2' => [
                    'value' => 2,
                    'type' => 'integer',
                    'placeholder' => 'c2',
                ],
            ],
            $valueBinder->bindings()
        );
    }

    public function testSqlInjectionViaUntypedCaseValueIsNotPossible(): void
    {
        $expression = (new CaseStatementExpression('1 THEN 1 END; DELETE * FROM foo; --'))
            ->when(1)
            ->then(2);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE :c0 WHEN :c1 THEN :c2 ELSE NULL END',
            $sql
        );
        $this->assertSame(
            [
                ':c0' => [
                    'value' => '1 THEN 1 END; DELETE * FROM foo; --',
                    'type' => 'string',
                    'placeholder' => 'c0',
                ],
                ':c1' => [
                    'value' => 1,
                    'type' => 'integer',
                    'placeholder' => 'c1',
                ],
                ':c2' => [
                    'value' => 2,
                    'type' => 'integer',
                    'placeholder' => 'c2',
                ],
            ],
            $valueBinder->bindings()
        );
    }

    public function testSqlInjectionViaTypedWhenValueIsNotPossible(): void
    {
        $expression = (new CaseStatementExpression())
            ->when('1 THEN 1 END; DELETE * FROM foo; --', 'integer')
            ->then(1);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE WHEN :c0 THEN :c1 ELSE NULL END',
            $sql
        );
        $this->assertSame(
            [
                ':c0' => [
                    'value' => '1 THEN 1 END; DELETE * FROM foo; --',
                    'type' => 'integer',
                    'placeholder' => 'c0',
                ],
                ':c1' => [
                    'value' => 1,
                    'type' => 'integer',
                    'placeholder' => 'c1',
                ],
            ],
            $valueBinder->bindings()
        );
    }

    public function testSqlInjectionViaTypedWhenArrayValueIsNotPossible(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'When using an array for the `$when` argument, the `$type` ' .
            'argument must be an array too, `string` given.'
        );

        (new CaseStatementExpression())
            ->when(['1 THEN 1 END; DELETE * FROM foo; --' => '123'], 'integer')
            ->then(1);
    }

    public function testSqlInjectionViaUntypedWhenValueIsNotPossible()
    {
        $expression = (new CaseStatementExpression())
            ->when('1 THEN 1 END; DELETE * FROM foo; --')
            ->then(1);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE WHEN :c0 THEN :c1 ELSE NULL END',
            $sql
        );
        $this->assertSame(
            [
                ':c0' => [
                    'value' => '1 THEN 1 END; DELETE * FROM foo; --',
                    'type' => 'string',
                    'placeholder' => 'c0',
                ],
                ':c1' => [
                    'value' => 1,
                    'type' => 'integer',
                    'placeholder' => 'c1',
                ],
            ],
            $valueBinder->bindings()
        );
    }

    public function testSqlInjectionViaUntypedWhenArrayValueIsPossible(): void
    {
        $expression = (new CaseStatementExpression())
            ->when(['1 THEN 1 END; DELETE * FROM foo; --' => '123'])
            ->then(1);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE WHEN 1 THEN 1 END; DELETE * FROM foo; -- :c0 THEN :c1 ELSE NULL END',
            $sql
        );
        $this->assertSame(
            [
                ':c0' => [
                    'value' => '123',
                    'type' => null,
                    'placeholder' => 'c0',
                ],
                ':c1' => [
                    'value' => 1,
                    'type' => 'integer',
                    'placeholder' => 'c1',
                ],
            ],
            $valueBinder->bindings()
        );
    }

    public function testSqlInjectionViaTypedThenValueIsNotPossible(): void
    {
        $expression = (new CaseStatementExpression(1))
            ->when(2)
            ->then('1 THEN 1 END; DELETE * FROM foo; --', 'integer');

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE :c0 WHEN :c1 THEN :c2 ELSE NULL END',
            $sql
        );
        $this->assertSame(
            [
                ':c0' => [
                    'value' => 1,
                    'type' => 'integer',
                    'placeholder' => 'c0',
                ],
                ':c1' => [
                    'value' => 2,
                    'type' => 'integer',
                    'placeholder' => 'c1',
                ],
                ':c2' => [
                    'value' => '1 THEN 1 END; DELETE * FROM foo; --',
                    'type' => 'integer',
                    'placeholder' => 'c2',
                ],
            ],
            $valueBinder->bindings()
        );
    }

    public function testSqlInjectionViaUntypedThenValueIsNotPossible(): void
    {
        $expression = (new CaseStatementExpression(1))
            ->when(2)
            ->then('1 THEN 1 END; DELETE * FROM foo; --');

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE :c0 WHEN :c1 THEN :c2 ELSE NULL END',
            $sql
        );
        $this->assertSame(
            [
                ':c0' => [
                    'value' => 1,
                    'type' => 'integer',
                    'placeholder' => 'c0',
                ],
                ':c1' => [
                    'value' => 2,
                    'type' => 'integer',
                    'placeholder' => 'c1',
                ],
                ':c2' => [
                    'value' => '1 THEN 1 END; DELETE * FROM foo; --',
                    'type' => 'string',
                    'placeholder' => 'c2',
                ],
            ],
            $valueBinder->bindings()
        );
    }

    public function testSqlInjectionViaTypedElseValueIsNotPossible(): void
    {
        $expression = (new CaseStatementExpression(1))
            ->when(2)
            ->then(3)
            ->else('1 THEN 1 END; DELETE * FROM foo; --', 'integer');

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE :c0 WHEN :c1 THEN :c2 ELSE :c3 END',
            $sql
        );
        $this->assertSame(
            [
                ':c0' => [
                    'value' => 1,
                    'type' => 'integer',
                    'placeholder' => 'c0',
                ],
                ':c1' => [
                    'value' => 2,
                    'type' => 'integer',
                    'placeholder' => 'c1',
                ],
                ':c2' => [
                    'value' => 3,
                    'type' => 'integer',
                    'placeholder' => 'c2',
                ],
                ':c3' => [
                    'value' => '1 THEN 1 END; DELETE * FROM foo; --',
                    'type' => 'integer',
                    'placeholder' => 'c3',
                ],
            ],
            $valueBinder->bindings()
        );
    }

    public function testSqlInjectionViaUntypedElseValueIsNotPossible(): void
    {
        $expression = (new CaseStatementExpression(1))
            ->when(2)
            ->then(3)
            ->else('1 THEN 1 END; DELETE * FROM foo; --');

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE :c0 WHEN :c1 THEN :c2 ELSE :c3 END',
            $sql
        );
        $this->assertSame(
            [
                ':c0' => [
                    'value' => 1,
                    'type' => 'integer',
                    'placeholder' => 'c0',
                ],
                ':c1' => [
                    'value' => 2,
                    'type' => 'integer',
                    'placeholder' => 'c1',
                ],
                ':c2' => [
                    'value' => 3,
                    'type' => 'integer',
                    'placeholder' => 'c2',
                ],
                ':c3' => [
                    'value' => '1 THEN 1 END; DELETE * FROM foo; --',
                    'type' => 'string',
                    'placeholder' => 'c3',
                ],
            ],
            $valueBinder->bindings()
        );
    }

    // endregion

    // region Getters

    public function testGetInvalidCaseExpressionClause()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The `$clause` argument must be one of `value`, `when`, `else`, the given value `invalid` is invalid.'
        );

        (new CaseStatementExpression())->clause('invalid');
    }

    public function testGetInvalidWhenThenExpressionClause()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The `$clause` argument must be one of `when`, `then`, the given value `invalid` is invalid.'
        );

        (new WhenThenExpression())->clause('invalid');
    }

    public function testGetValueClause(): void
    {
        $expression = new CaseStatementExpression();

        $this->assertNull($expression->clause('value'));

        $expression = (new CaseStatementExpression(1))
            ->when(1)
            ->then(2);

        $this->assertSame(1, $expression->clause('value'));
    }

    public function testGetWhenClause(): void
    {
        $when = ['Table.column' => true];

        $expression = new CaseStatementExpression();
        $this->assertSame([], $expression->clause('when'));

        $expression
            ->when($when)
            ->then(1);

        $this->assertCount(1, $expression->clause('when'));
        $this->assertInstanceOf(WhenThenExpression::class, $expression->clause('when')[0]);
    }

    public function testWhenArrayValueGetWhenClause(): void
    {
        $when = ['Table.column' => true];

        $expression = new CaseStatementExpression();
        $this->assertSame([], $expression->clause('when'));

        $expression
            ->when($when)
            ->then(1);

        $this->assertEquals(
            new QueryExpression($when),
            $expression->clause('when')[0]->clause('when')
        );
    }

    public function testWhenNonArrayValueGetWhenClause(): void
    {
        $expression = new CaseStatementExpression();
        $this->assertSame([], $expression->clause('when'));

        $expression
            ->when(1)
            ->then(2);

        $this->assertSame(1, $expression->clause('when')[0]->clause('when'));
    }

    public function testWhenGetThenClause(): void
    {
        $expression = (new CaseStatementExpression())
            ->when(function (WhenThenExpression $whenThen) {
                return $whenThen;
            });

        $this->assertNull($expression->clause('when')[0]->clause('then'));

        $expression->clause('when')[0]->then(1);

        $this->assertSame(1, $expression->clause('when')[0]->clause('then'));
    }

    public function testGetElseClause(): void
    {
        $expression = new CaseStatementExpression();

        $this->assertNull($expression->clause('else'));

        $expression
            ->when(['Table.column' => true])
            ->then(1)
            ->else(2);

        $this->assertSame(2, $expression->clause('else'));
    }

    // endregion

    // region Order based syntax

    public function testWhenThenElse(): void
    {
        $expression = (new CaseStatementExpression())
            ->when(['Table.column_a' => true, 'Table.column_b IS' => null])
            ->then(1)
            ->when(['Table.column_c' => true, 'Table.column_d IS NOT' => null])
            ->then(2)
            ->else(3);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE ' .
            'WHEN (Table.column_a = :c0 AND (Table.column_b) IS NULL) THEN :c1 ' .
            'WHEN (Table.column_c = :c2 AND (Table.column_d) IS NOT NULL) THEN :c3 ' .
            'ELSE :c4 ' .
            'END',
            $sql
        );
    }

    public function testWhenBeforeClosingThenFails(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot call `when()` between `when()` and `then()`.');

        (new CaseStatementExpression())
            ->when(['Table.column_a' => true])
            ->when(['Table.column_b' => true]);
    }

    public function testElseBeforeClosingThenFails(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot call `else()` between `when()` and `then()`.');

        (new CaseStatementExpression())
            ->when(['Table.column' => true])
            ->else(1);
    }

    public function testThenBeforeOpeningWhenFails(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot call `then()` before `when()`.');

        (new CaseStatementExpression())
            ->then(1);
    }

    // endregion

    // region Callable syntax

    public function testWhenCallables(): void
    {
        $expression = (new CaseStatementExpression())
            ->when(function (WhenThenExpression $whenThen) {
                return $whenThen
                    ->when([
                        'Table.column_a' => true,
                        'Table.column_b IS' => null,
                    ])
                    ->then(1);
            })
            ->when(function (WhenThenExpression $whenThen) {
                return $whenThen
                    ->when([
                        'Table.column_c' => true,
                        'Table.column_d IS NOT' => null,
                    ])
                    ->then(2);
            })
            ->else(3);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE ' .
            'WHEN (Table.column_a = :c0 AND (Table.column_b) IS NULL) THEN :c1 ' .
            'WHEN (Table.column_c = :c2 AND (Table.column_d) IS NOT NULL) THEN :c3 ' .
            'ELSE :c4 ' .
            'END',
            $sql
        );
    }

    public function testWhenCallablesWithCustomWhenThenExpressions(): void
    {
        $expression = (new CaseStatementExpression())
            ->when(function () {
                return (new CustomWhenThenExpression())
                    ->when([
                        'Table.column_a' => true,
                        'Table.column_b IS' => null,
                    ])
                    ->then(1);
            })
            ->when(function () {
                return (new CustomWhenThenExpression())
                    ->when([
                        'Table.column_c' => true,
                        'Table.column_d IS NOT' => null,
                    ])
                    ->then(2);
            })
            ->else(3);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE ' .
            'WHEN (Table.column_a = :c0 AND (Table.column_b) IS NULL) THEN :c1 ' .
            'WHEN (Table.column_c = :c2 AND (Table.column_d) IS NOT NULL) THEN :c3 ' .
            'ELSE :c4 ' .
            'END',
            $sql
        );
    }

    public function testWhenCallablesWithInvalidReturnTypeFails(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            '`when()` callables must return an instance of ' .
            '`\Cake\Database\Expression\WhenThenExpression`, `NULL` given.'
        );

        $this->deprecated(function () {
            (new CaseStatementExpression())
                ->when(function () {
                    return null;
                });
        });
    }

    // endregion

    // region Self-contained values

    public function testSelfContainedWhenThenExpressions(): void
    {
        $expression = (new CaseStatementExpression())
            ->when(
                (new WhenThenExpression())
                    ->when([
                        'Table.column_a' => true,
                        'Table.column_b IS' => null,
                    ])
                    ->then(1)
            )
            ->when(
                (new WhenThenExpression())
                    ->when([
                        'Table.column_c' => true,
                        'Table.column_d IS NOT' => null,
                    ])
                    ->then(2)
            )
            ->else(3);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE ' .
            'WHEN (Table.column_a = :c0 AND (Table.column_b) IS NULL) THEN :c1 ' .
            'WHEN (Table.column_c = :c2 AND (Table.column_d) IS NOT NULL) THEN :c3 ' .
            'ELSE :c4 ' .
            'END',
            $sql
        );
    }

    public function testSelfContainedCustomWhenThenExpressions(): void
    {
        $expression = (new CaseStatementExpression())
            ->when(
                (new CustomWhenThenExpression())
                    ->when([
                        'Table.column_a' => true,
                        'Table.column_b IS' => null,
                    ])
                    ->then(1)
            )
            ->when(
                (new CustomWhenThenExpression())
                    ->when([
                        'Table.column_c' => true,
                        'Table.column_d IS NOT' => null,
                    ])
                    ->then(2)
            )
            ->else(3);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);
        $this->assertSame(
            'CASE ' .
            'WHEN (Table.column_a = :c0 AND (Table.column_b) IS NULL) THEN :c1 ' .
            'WHEN (Table.column_c = :c2 AND (Table.column_d) IS NOT NULL) THEN :c3 ' .
            'ELSE :c4 ' .
            'END',
            $sql
        );
    }

    // endregion

    // region Incomplete states

    public function testCompilingEmptyCaseExpressionFails(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Case expression must have at least one when statement.');

        $this->deprecated(function () {
            (new CaseStatementExpression())->sql(new ValueBinder());
        });
    }

    public function testCompilingNonClosedWhenFails(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Case expression has incomplete when clause. Missing `then()` after `when()`.');

        $this->deprecated(function () {
            (new CaseStatementExpression())
                ->when(['Table.column' => true])
                ->sql(new ValueBinder());
        });
    }

    public function testCompilingWhenThenExpressionWithMissingWhenFails(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Case expression has incomplete when clause. Missing `when()`.');

        $this->deprecated(function () {
            (new CaseStatementExpression())
                ->when(function (WhenThenExpression $whenThen) {
                    return $whenThen->then(1);
                })
                ->sql(new ValueBinder());
        });
    }

    public function testCompilingWhenThenExpressionWithMissingThenFails(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Case expression has incomplete when clause. Missing `then()` after `when()`.');

        $this->deprecated(function () {
            (new CaseStatementExpression())
                ->when(function (WhenThenExpression $whenThen) {
                    return $whenThen->when(1);
                })
                ->sql(new ValueBinder());
        });
    }

    // endregion

    // region Valid values

    public function validCaseValuesDataProvider(): array
    {
        $values = [];
        $this->deprecated(function () use (&$values) {
            $values = [
                [null, 'NULL', null],
                ['0', null, 'string'],
                [0, null, 'integer'],
                [0.0, null, 'float'],
                ['foo', null, 'string'],
                [true, null, 'boolean'],
                [Date::now(), null, 'date'],
                [FrozenDate::now(), null, 'date'],
                [ChronosDate::now(), null, 'date'],
                [ChronosMutableDate::now(), null, 'date'],
                [Time::now(), null, 'datetime'],
                [FrozenTime::now(), null, 'datetime'],
                [Chronos::now(), null, 'datetime'],
                [new IdentifierExpression('Table.column'), 'Table.column', null],
                [new QueryExpression('Table.column'), 'Table.column', null],
                [ConnectionManager::get('test')->newQuery()->select('a'), '(SELECT a)', null],
                [new TestObjectWithToString(), null, 'string'],
                [new stdClass(), null, null],
            ];
        });

        return $values;
    }

    /**
     * @dataProvider validCaseValuesDataProvider
     * @param mixed $value The case value.
     * @param string|null $sqlValue The expected SQL string value.
     * @param string|null $type The expected bound type.
     */
    public function testValidCaseValue($value, ?string $sqlValue, ?string $type): void
    {
        $expression = (new CaseStatementExpression($value))
            ->when(1)
            ->then(2);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);

        if ($sqlValue) {
            $this->assertEqualsSql(
                "CASE $sqlValue WHEN :c0 THEN :c1 ELSE NULL END",
                $sql
            );

            $this->assertSame(
                [
                    ':c0' => [
                        'value' => 1,
                        'type' => 'integer',
                        'placeholder' => 'c0',
                    ],
                    ':c1' => [
                        'value' => 2,
                        'type' => 'integer',
                        'placeholder' => 'c1',
                    ],
                ],
                $valueBinder->bindings()
            );
        } else {
            $this->assertEqualsSql(
                'CASE :c0 WHEN :c1 THEN :c2 ELSE NULL END',
                $sql
            );

            $this->assertSame(
                [
                    ':c0' => [
                        'value' => $value,
                        'type' => $type,
                        'placeholder' => 'c0',
                    ],
                    ':c1' => [
                        'value' => 1,
                        'type' => 'integer',
                        'placeholder' => 'c1',
                    ],
                    ':c2' => [
                        'value' => 2,
                        'type' => 'integer',
                        'placeholder' => 'c2',
                    ],
                ],
                $valueBinder->bindings()
            );
        }
    }

    public function validWhenValuesSimpleCaseDataProvider(): array
    {
        $values = [];
        $this->deprecated(function () use (&$values) {
            $values = [
                ['0', null, 'string'],
                [0, null, 'integer'],
                [0.0, null, 'float'],
                ['foo', null, 'string'],
                [true, null, 'boolean'],
                [new stdClass(), null, null],
                [new TestObjectWithToString(), null, 'string'],
                [Date::now(), null, 'date'],
                [FrozenDate::now(), null, 'date'],
                [ChronosDate::now(), null, 'date'],
                [ChronosMutableDate::now(), null, 'date'],
                [Time::now(), null, 'datetime'],
                [FrozenTime::now(), null, 'datetime'],
                [Chronos::now(), null, 'datetime'],
                [
                    new IdentifierExpression('Table.column'),
                    'CASE :c0 WHEN Table.column THEN :c1 ELSE NULL END',
                    [
                        ':c0' => [
                            'value' => true,
                            'type' => 'boolean',
                            'placeholder' => 'c0',
                        ],
                        ':c1' => [
                            'value' => 2,
                            'type' => 'integer',
                            'placeholder' => 'c1',
                        ],
                    ],
                ],
                [
                    new QueryExpression('Table.column'),
                    'CASE :c0 WHEN Table.column THEN :c1 ELSE NULL END',
                    [
                        ':c0' => [
                            'value' => true,
                            'type' => 'boolean',
                            'placeholder' => 'c0',
                        ],
                        ':c1' => [
                            'value' => 2,
                            'type' => 'integer',
                            'placeholder' => 'c1',
                        ],
                    ],
                ],
                [
                    ConnectionManager::get('test')->newQuery()->select('a'),
                    'CASE :c0 WHEN (SELECT a) THEN :c1 ELSE NULL END',
                    [
                        ':c0' => [
                            'value' => true,
                            'type' => 'boolean',
                            'placeholder' => 'c0',
                        ],
                        ':c1' => [
                            'value' => 2,
                            'type' => 'integer',
                            'placeholder' => 'c1',
                        ],
                    ],
                ],
                [
                    [
                        'Table.column_a' => 1,
                        'Table.column_b' => 'foo',
                    ],
                    'CASE :c0 WHEN (Table.column_a = :c1 AND Table.column_b = :c2) THEN :c3 ELSE NULL END',
                    [
                        ':c0' => [
                            'value' => true,
                            'type' => 'boolean',
                            'placeholder' => 'c0',
                        ],
                        ':c1' => [
                            'value' => 1,
                            'type' => 'integer',
                            'placeholder' => 'c1',
                        ],
                        ':c2' => [
                            'value' => 'foo',
                            'type' => 'string',
                            'placeholder' => 'c2',
                        ],
                        ':c3' => [
                            'value' => 2,
                            'type' => 'integer',
                            'placeholder' => 'c3',
                        ],
                    ],
                ],
            ];
        });

        return $values;
    }

    /**
     * @dataProvider validWhenValuesSimpleCaseDataProvider
     * @param mixed $value The when value.
     * @param string|null $expectedSql The expected SQL string.
     * @param array|string|null $typeOrBindings The expected bound type(s).
     */
    public function testValidWhenValueSimpleCase($value, ?string $expectedSql, $typeOrBindings = null): void
    {
        $typeMap = new TypeMap([
            'Table.column_a' => 'integer',
            'Table.column_b' => 'string',
        ]);
        $expression = (new CaseStatementExpression(true))
            ->setTypeMap($typeMap)
            ->when($value)
            ->then(2);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);

        if ($expectedSql) {
            $this->assertEqualsSql($expectedSql, $sql);
            $this->assertSame($typeOrBindings, $valueBinder->bindings());
        } else {
            $this->assertEqualsSql('CASE :c0 WHEN :c1 THEN :c2 ELSE NULL END', $sql);
            $this->assertSame(
                [
                    ':c0' => [
                        'value' => true,
                        'type' => 'boolean',
                        'placeholder' => 'c0',
                    ],
                    ':c1' => [
                        'value' => $value,
                        'type' => $typeOrBindings,
                        'placeholder' => 'c1',
                    ],
                    ':c2' => [
                        'value' => 2,
                        'type' => 'integer',
                        'placeholder' => 'c2',
                    ],
                ],
                $valueBinder->bindings()
            );
        }
    }

    public function validWhenValuesSearchedCaseDataProvider(): array
    {
        $values = [];
        $this->deprecated(function () use (&$values) {
            $values = [
                ['0', null, 'string'],
                [0, null, 'integer'],
                [0.0, null, 'float'],
                ['foo', null, 'string'],
                [true, null, 'boolean'],
                [new stdClass(), null, null],
                [new TestObjectWithToString(), null, 'string'],
                [Date::now(), null, 'date'],
                [FrozenDate::now(), null, 'date'],
                [ChronosDate::now(), null, 'date'],
                [ChronosMutableDate::now(), null, 'date'],
                [Time::now(), null, 'datetime'],
                [FrozenTime::now(), null, 'datetime'],
                [Chronos::now(), null, 'datetime'],
                [
                    new IdentifierExpression('Table.column'),
                    'CASE WHEN Table.column THEN :c0 ELSE NULL END',
                    [
                        ':c0' => [
                            'value' => 2,
                            'type' => 'integer',
                            'placeholder' => 'c0',
                        ],
                    ],
                ],
                [
                    new QueryExpression('Table.column'),
                    'CASE WHEN Table.column THEN :c0 ELSE NULL END',
                    [
                        ':c0' => [
                            'value' => 2,
                            'type' => 'integer',
                            'placeholder' => 'c0',
                        ],
                    ],
                ],
                [
                    ConnectionManager::get('test')->newQuery()->select('a'),
                    'CASE WHEN (SELECT a) THEN :c0 ELSE NULL END',
                    [
                        ':c0' => [
                            'value' => 2,
                            'type' => 'integer',
                            'placeholder' => 'c0',
                        ],
                    ],
                ],
                [
                    [
                        'Table.column_a' => 1,
                        'Table.column_b' => 'foo',
                    ],
                    'CASE WHEN (Table.column_a = :c0 AND Table.column_b = :c1) THEN :c2 ELSE NULL END',
                    [
                        ':c0' => [
                            'value' => 1,
                            'type' => 'integer',
                            'placeholder' => 'c0',
                        ],
                        ':c1' => [
                            'value' => 'foo',
                            'type' => 'string',
                            'placeholder' => 'c1',
                        ],
                        ':c2' => [
                            'value' => 2,
                            'type' => 'integer',
                            'placeholder' => 'c2',
                        ],
                    ],
                ],
            ];
        });

        return $values;
    }

    /**
     * @dataProvider validWhenValuesSearchedCaseDataProvider
     * @param mixed $value The when value.
     * @param string|null $expectedSql The expected SQL string.
     * @param array|string|null $typeOrBindings The expected bound type(s).
     */
    public function testValidWhenValueSearchedCase($value, ?string $expectedSql, $typeOrBindings = null): void
    {
        $typeMap = new TypeMap([
            'Table.column_a' => 'integer',
            'Table.column_b' => 'string',
        ]);
        $expression = (new CaseStatementExpression())
            ->setTypeMap($typeMap)
            ->when($value)
            ->then(2);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);

        if ($expectedSql) {
            $this->assertEqualsSql($expectedSql, $sql);
            $this->assertSame($typeOrBindings, $valueBinder->bindings());
        } else {
            $this->assertEqualsSql('CASE WHEN :c0 THEN :c1 ELSE NULL END', $sql);
            $this->assertSame(
                [
                    ':c0' => [
                        'value' => $value,
                        'type' => $typeOrBindings,
                        'placeholder' => 'c0',
                    ],
                    ':c1' => [
                        'value' => 2,
                        'type' => 'integer',
                        'placeholder' => 'c1',
                    ],
                ],
                $valueBinder->bindings()
            );
        }
    }

    public function validThenValuesDataProvider(): array
    {
        $values = [];
        $this->deprecated(function () use (&$values) {
            $values = [
                [null, 'NULL', null],
                ['0', null, 'string'],
                [0, null, 'integer'],
                [0.0, null, 'float'],
                ['foo', null, 'string'],
                [true, null, 'boolean'],
                [Date::now(), null, 'date'],
                [FrozenDate::now(), null, 'date'],
                [ChronosDate::now(), null, 'date'],
                [ChronosMutableDate::now(), null, 'date'],
                [Time::now(), null, 'datetime'],
                [FrozenTime::now(), null, 'datetime'],
                [Chronos::now(), null, 'datetime'],
                [new IdentifierExpression('Table.column'), 'Table.column', null],
                [new QueryExpression('Table.column'), 'Table.column', null],
                [ConnectionManager::get('test')->newQuery()->select('a'), '(SELECT a)', null],
                [new TestObjectWithToString(), null, 'string'],
                [new stdClass(), null, null],
            ];
        });

        return $values;
    }

    /**
     * @dataProvider validThenValuesDataProvider
     * @param mixed $value The then value.
     * @param string|null $sqlValue The expected SQL string value.
     * @param string|null $type The expected bound type.
     */
    public function testValidThenValue($value, ?string $sqlValue, ?string $type): void
    {
        $expression = (new CaseStatementExpression())
            ->when(1)
            ->then($value);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);

        if ($sqlValue) {
            $this->assertEqualsSql(
                "CASE WHEN :c0 THEN $sqlValue ELSE NULL END",
                $sql
            );

            $this->assertSame(
                [
                    ':c0' => [
                        'value' => 1,
                        'type' => 'integer',
                        'placeholder' => 'c0',
                    ],
                ],
                $valueBinder->bindings()
            );
        } else {
            $this->assertEqualsSql(
                'CASE WHEN :c0 THEN :c1 ELSE NULL END',
                $sql
            );

            $this->assertSame(
                [
                    ':c0' => [
                        'value' => 1,
                        'type' => 'integer',
                        'placeholder' => 'c0',
                    ],
                    ':c1' => [
                        'value' => $value,
                        'type' => $type,
                        'placeholder' => 'c1',
                    ],
                ],
                $valueBinder->bindings()
            );
        }
    }

    public function validElseValuesDataProvider(): array
    {
        $values = [];
        $this->deprecated(function () use (&$values) {
            $values = [
                [null, 'NULL', null],
                ['0', null, 'string'],
                [0, null, 'integer'],
                [0.0, null, 'float'],
                ['foo', null, 'string'],
                [true, null, 'boolean'],
                [Date::now(), null, 'date'],
                [FrozenDate::now(), null, 'date'],
                [ChronosDate::now(), null, 'date'],
                [ChronosMutableDate::now(), null, 'date'],
                [Time::now(), null, 'datetime'],
                [FrozenTime::now(), null, 'datetime'],
                [Chronos::now(), null, 'datetime'],
                [new IdentifierExpression('Table.column'), 'Table.column', null],
                [new QueryExpression('Table.column'), 'Table.column', null],
                [ConnectionManager::get('test')->newQuery()->select('a'), '(SELECT a)', null],
                [new TestObjectWithToString(), null, 'string'],
                [new stdClass(), null, null],
            ];
        });

        return $values;
    }

    /**
     * @dataProvider validElseValuesDataProvider
     * @param mixed $value The else value.
     * @param string|null $sqlValue The expected SQL string value.
     * @param string|null $type The expected bound type.
     */
    public function testValidElseValue($value, ?string $sqlValue, ?string $type): void
    {
        $expression = (new CaseStatementExpression())
            ->when(1)
            ->then(2)
            ->else($value);

        $valueBinder = new ValueBinder();
        $sql = $expression->sql($valueBinder);

        if ($sqlValue) {
            $this->assertEqualsSql(
                "CASE WHEN :c0 THEN :c1 ELSE $sqlValue END",
                $sql
            );

            $this->assertSame(
                [
                    ':c0' => [
                        'value' => 1,
                        'type' => 'integer',
                        'placeholder' => 'c0',
                    ],
                    ':c1' => [
                        'value' => 2,
                        'type' => 'integer',
                        'placeholder' => 'c1',
                    ],
                ],
                $valueBinder->bindings()
            );
        } else {
            $this->assertEqualsSql(
                'CASE WHEN :c0 THEN :c1 ELSE :c2 END',
                $sql
            );

            $this->assertSame(
                [
                    ':c0' => [
                        'value' => 1,
                        'type' => 'integer',
                        'placeholder' => 'c0',
                    ],
                    ':c1' => [
                        'value' => 2,
                        'type' => 'integer',
                        'placeholder' => 'c1',
                    ],
                    ':c2' => [
                        'value' => $value,
                        'type' => $type,
                        'placeholder' => 'c2',
                    ],
                ],
                $valueBinder->bindings()
            );
        }
    }

    // endregion

    // region Invalid values

    public function invalidCaseValuesDataProvider(): array
    {
        $res = fopen('data:text/plain,123', 'rb');
        fclose($res);

        return [
            [[], 'array'],
            [
                function () {
                },
                'Closure',
            ],
            [$res, 'resource (closed)'],
        ];
    }

    /**
     * @dataProvider invalidCaseValuesDataProvider
     * @param mixed $value The case value.
     * @param string $typeName The expected error type name.
     */
    public function testInvalidCaseValue($value, string $typeName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The `$value` argument must be either `null`, a scalar value, an object, ' .
            "or an instance of `\\Cake\\Database\\ExpressionInterface`, `$typeName` given."
        );

        new CaseStatementExpression($value);
    }

    public function invalidWhenValueDataProvider(): array
    {
        $res = fopen('data:text/plain,123', 'rb');
        fclose($res);

        return [
            [null, 'NULL'],
            [[], '[]'],
            [$res, 'resource (closed)'],
        ];
    }

    /**
     * @dataProvider invalidWhenValueDataProvider
     * @param mixed $value The when value.
     * @param string $typeName The expected error type name.
     */
    public function testInvalidWhenValue($value, string $typeName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The `$when` argument must be either a non-empty array, a scalar value, an object, ' .
            "or an instance of `\\Cake\\Database\\ExpressionInterface`, `$typeName` given."
        );

        (new CaseStatementExpression())
            ->when($value)
            ->then(1);
    }

    public function invalidWhenTypeDataProvider(): array
    {
        $res = fopen('data:text/plain,123', 'rb');
        fclose($res);

        return [
            [1, 'integer'],
            [1.0, 'double'],
            [new stdClass(), 'stdClass'],
            [
                function () {
                },
                'Closure',
            ],
            [$res, 'resource (closed)'],
        ];
    }

    /**
     * @dataProvider invalidWhenTypeDataProvider
     * @param mixed $type The when type.
     * @param string $typeName The expected error type name.
     */
    public function testInvalidWhenType($type, string $typeName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "The `\$type` argument must be either an array, a string, or `null`, `$typeName` given."
        );

        (new CaseStatementExpression())
            ->when(1, $type)
            ->then(1);
    }

    public function invalidThenValueDataProvider(): array
    {
        $res = fopen('data:text/plain,123', 'rb');
        fclose($res);

        return [
            [[], 'array'],
            [
                function () {
                },
                'Closure',
            ],
            [$res, 'resource (closed)'],
        ];
    }

    /**
     * @dataProvider invalidThenValueDataProvider
     * @param mixed $value The then value.
     * @param string $typeName The expected error type name.
     */
    public function testInvalidThenValue($value, string $typeName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The `$result` argument must be either `null`, a scalar value, an object, ' .
            "or an instance of `\\Cake\\Database\\ExpressionInterface`, `$typeName` given."
        );

        (new CaseStatementExpression())
            ->when(1)
            ->then($value);
    }

    public function invalidThenTypeDataProvider(): array
    {
        $res = fopen('data:text/plain,123', 'rb');
        fclose($res);

        return [
            [1],
            [1.0],
            [new stdClass()],
            [
                function () {
                },
            ],
            [$res, 'resource (closed)'],
        ];
    }

    /**
     * @dataProvider invalidThenTypeDataProvider
     * @param mixed $type The then type.
     */
    public function testInvalidThenType($type): void
    {
        $this->expectException(TypeError::class);

        (new CaseStatementExpression())
            ->when(1)
            ->then(1, $type);
    }

    public function invalidElseValueDataProvider(): array
    {
        $res = fopen('data:text/plain,123', 'rb');
        fclose($res);

        return [
            [[], 'array'],
            [
                function () {
                },
                'Closure',
            ],
            [$res, 'resource (closed)'],
        ];
    }

    /**
     * @dataProvider invalidElseValueDataProvider
     * @param mixed $value The else value.
     * @param string $typeName The expected error type name.
     */
    public function testInvalidElseValue($value, string $typeName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The `$result` argument must be either `null`, a scalar value, an object, ' .
            "or an instance of `\\Cake\\Database\\ExpressionInterface`, `$typeName` given."
        );

        (new CaseStatementExpression())
            ->when(1)
            ->then(1)
            ->else($value);
    }

    public function invalidElseTypeDataProvider(): array
    {
        $res = fopen('data:text/plain,123', 'rb');
        fclose($res);

        return [
            [1],
            [1.0],
            [new stdClass()],
            [
                function () {
                },
                'Closure',
            ],
            [$res, 'resource (closed)'],
        ];
    }

    /**
     * @dataProvider invalidElseTypeDataProvider
     * @param mixed $type The else type.
     */
    public function testInvalidElseType($type): void
    {
        $this->expectException(TypeError::class);

        (new CaseStatementExpression())
            ->when(1)
            ->then(1)
            ->else(1, $type);
    }

    // endregion

    // region Traversal

    public function testTraverse(): void
    {
        $value = new IdentifierExpression('Table.column');
        $conditionsA = ['Table.column_a' => true, 'Table.column_b IS' => null];
        $resultA = new QueryExpression('1');
        $conditionsB = ['Table.column_c' => true, 'Table.column_d IS NOT' => null];
        $resultB = new QueryExpression('2');
        $else = new QueryExpression('3');

        $expression = (new CaseStatementExpression($value))
            ->when($conditionsA)
            ->then($resultA)
            ->when($conditionsB)
            ->then($resultB)
            ->else($else);

        $expressions = [];
        $expression->traverse(function ($expression) use (&$expressions) {
            $expressions[] = $expression;
        });

        $this->assertCount(14, $expressions);
        $this->assertInstanceOf(IdentifierExpression::class, $expressions[0]);
        $this->assertSame($value, $expressions[0]);
        $this->assertInstanceOf(WhenThenExpression::class, $expressions[1]);
        $this->assertEquals(new QueryExpression($conditionsA), $expressions[2]);
        $this->assertEquals(new ComparisonExpression('Table.column_a', true), $expressions[3]);
        $this->assertSame($resultA, $expressions[6]);
        $this->assertInstanceOf(WhenThenExpression::class, $expressions[7]);
        $this->assertEquals(new QueryExpression($conditionsB), $expressions[8]);
        $this->assertEquals(new ComparisonExpression('Table.column_c', true), $expressions[9]);
        $this->assertSame($resultB, $expressions[12]);
        $this->assertSame($else, $expressions[13]);
    }

    public function testTraverseBeforeClosingThenFails(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Case expression has incomplete when clause. Missing `then()` after `when()`.');

        $this->deprecated(function () {
            $expression = (new CaseStatementExpression())
                ->when(['Table.column' => true]);

            $expression->traverse(
                function () {
                }
            );
        });
    }

    // endregion

    // region Cloning

    public function testClone(): void
    {
        $value = new IdentifierExpression('Table.column');
        $conditionsA = ['Table.column_a' => true, 'Table.column_b IS' => null];
        $resultA = new QueryExpression('1');
        $conditionsB = ['Table.column_c' => true, 'Table.column_d IS NOT' => null];
        $resultB = new QueryExpression('2');
        $else = new QueryExpression('3');

        $expression = (new CaseStatementExpression($value))
            ->when($conditionsA)
            ->then($resultA)
            ->when($conditionsB)
            ->then($resultB)
            ->else($else);
        $clone = clone $expression;

        $this->assertEquals($clone, $expression);
        $this->assertNotSame($clone, $expression);
    }

    public function testCloneBeforeClosingThenFails(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Case expression has incomplete when clause. Missing `then()` after `when()`.');

        $this->deprecated(function () {
            $expression = (new CaseStatementExpression())
                ->when(['Table.column' => true]);

            clone $expression;
        });
    }

    // endregion
}
