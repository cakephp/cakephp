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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Validation;

use Cake\TestSuite\TestCase;
use Cake\Validation\RulesProvider;
use Cake\Validation\Validation;
use Cake\Validation\ValidationRule;
use Cake\Validation\ValidationSet;
use Cake\Validation\Validator;
use InvalidArgumentException;
use Laminas\Diactoros\UploadedFile;
use stdClass;

/**
 * Tests Validator class
 */
class ValidatorTest extends TestCase
{
    /**
     * tests getRequiredMessage
     */
    public function testGetRequiredMessage(): void
    {
        $validator = new Validator();
        $this->assertNull($validator->getRequiredMessage('field'));

        $validator = new Validator();
        $validator->requirePresence('field');
        $this->assertSame('This field is required', $validator->getRequiredMessage('field'));

        $validator = new Validator();
        $validator->requirePresence('field', true, 'Custom message');
        $this->assertSame('Custom message', $validator->getRequiredMessage('field'));
    }

    /**
     * tests getNotEmptyMessage
     */
    public function testGetNotEmptyMessage(): void
    {
        $validator = new Validator();
        $this->assertNull($validator->getNotEmptyMessage('field'));

        $validator = new Validator();
        $validator->requirePresence('field');
        $this->assertSame('This field cannot be left empty', $validator->getNotEmptyMessage('field'));

        $validator = new Validator();
        $validator->notEmptyString('field', 'Custom message');
        $this->assertSame('Custom message', $validator->getNotEmptyMessage('field'));

        $validator = new Validator();
        $validator->notBlank('field', 'Cannot be blank');
        $this->assertSame('Cannot be blank', $validator->getNotEmptyMessage('field'));

        $validator = new Validator();
        $validator->notEmptyString('field', 'Cannot be empty');
        $validator->notBlank('field', 'Cannot be blank');
        $this->assertSame('Cannot be blank', $validator->getNotEmptyMessage('field'));
    }

    /**
     * Testing you can dynamically add rules to a field
     */
    public function testAddingRulesToField(): void
    {
        $validator = new Validator();
        $validator->add('title', 'not-blank', ['rule' => 'notBlank']);
        $set = $validator->field('title');
        $this->assertInstanceOf(ValidationSet::class, $set);
        $this->assertCount(1, $set);

        $validator->add('title', 'another', ['rule' => 'alphanumeric']);
        $this->assertCount(2, $set);

        $validator->add('body', 'another', ['rule' => 'crazy']);
        $this->assertCount(1, $validator->field('body'));
        $this->assertCount(2, $validator);

        $validator->add('email', 'notBlank');
        $result = $validator->field('email')->rule('notBlank')->get('rule');
        $this->assertSame('notBlank', $result);

        $rule = new ValidationRule();
        $validator->add('field', 'myrule', $rule);
        $result = $validator->field('field')->rule('myrule');
        $this->assertSame($rule, $result);
    }

    /**
     * Testing addNested field rules
     */
    public function testAddNestedSingle(): void
    {
        $validator = new Validator();
        $inner = new Validator();
        $inner->add('username', 'not-blank', ['rule' => 'notBlank']);
        $this->assertSame($validator, $validator->addNested('user', $inner));

        $this->assertCount(1, $validator->field('user'));
    }

    /**
     * Testing addNested connects providers
     */
    public function testAddNestedSingleProviders(): void
    {
        $validator = new Validator();
        $validator->setProvider('test', $this);

        $inner = new Validator();
        $inner->add('username', 'not-blank', ['rule' => function () use ($inner, $validator) {
            $this->assertSame($validator->providers(), $inner->providers(), 'Providers should match');

            return false;
        }]);
        $validator->addNested('user', $inner);

        $result = $validator->validate(['user' => ['username' => 'example']]);
        $this->assertNotEmpty($result, 'Validation should fail');
    }

    /**
     * Testing addNested with extra `$message` and `$when` params
     */
    public function testAddNestedWithExtra(): void
    {
        $inner = new Validator();
        $inner->requirePresence('username');

        $validator = new Validator();
        $validator->addNested('user', $inner, 'errors found', 'create');

        $this->assertCount(1, $validator->field('user'));

        $rule = $validator->field('user')->rule(Validator::NESTED);
        $this->assertSame('create', $rule->get('on'));

        $errors = $validator->validate(['user' => 'string']);
        $this->assertArrayHasKey('user', $errors);
        $this->assertArrayHasKey(Validator::NESTED, $errors['user']);
        $this->assertSame('errors found', $errors['user'][Validator::NESTED]);

        $errors = $validator->validate(['user' => ['key' => 'value']]);
        $this->assertArrayHasKey('user', $errors);
        $this->assertArrayHasKey(Validator::NESTED, $errors['user']);

        $this->assertEmpty($validator->validate(['user' => ['key' => 'value']], false));
    }

    /**
     * Testing addNestedMany field rules
     */
    public function testAddNestedMany(): void
    {
        $validator = new Validator();
        $inner = new Validator();
        $inner->add('comment', 'not-blank', ['rule' => 'notBlank']);
        $this->assertSame($validator, $validator->addNestedMany('comments', $inner));

        $this->assertCount(1, $validator->field('comments'));
    }

    /**
     * Testing addNestedMany connects providers
     */
    public function testAddNestedManyProviders(): void
    {
        $validator = new Validator();
        $validator->setProvider('test', $this);

        $inner = new Validator();
        $inner->add('comment', 'not-blank', ['rule' => function () use ($inner, $validator) {
            $this->assertSame($validator->providers(), $inner->providers(), 'Providers should match');

            return false;
        }]);
        $validator->addNestedMany('comments', $inner);

        $result = $validator->validate(['comments' => [['comment' => 'example']]]);
        $this->assertNotEmpty($result, 'Validation should fail');
    }

    /**
     * Testing addNestedMany with extra `$message` and `$when` params
     */
    public function testAddNestedManyWithExtra(): void
    {
        $inner = new Validator();
        $inner->requirePresence('body');

        $validator = new Validator();
        $validator->addNestedMany('comments', $inner, 'errors found', 'create');

        $this->assertCount(1, $validator->field('comments'));

        $rule = $validator->field('comments')->rule(Validator::NESTED);
        $this->assertSame('create', $rule->get('on'));

        $errors = $validator->validate(['comments' => 'string']);
        $this->assertArrayHasKey('comments', $errors);
        $this->assertArrayHasKey(Validator::NESTED, $errors['comments']);
        $this->assertSame('errors found', $errors['comments'][Validator::NESTED]);

        $errors = $validator->validate(['comments' => ['string']]);
        $this->assertArrayHasKey('comments', $errors);
        $this->assertArrayHasKey(Validator::NESTED, $errors['comments']);
        $this->assertSame('errors found', $errors['comments'][Validator::NESTED]);

        $errors = $validator->validate(['comments' => [['body' => null]]]);
        $this->assertArrayHasKey('comments', $errors);
        $this->assertArrayHasKey(Validator::NESTED, $errors['comments']);

        $this->assertEmpty($validator->validate(['comments' => [['body' => null]]], false));
    }

    /**
     * Tests that calling field will create a default validation set for it
     */
    public function testFieldDefault(): void
    {
        $validator = new Validator();
        $this->assertFalse($validator->hasField('foo'));

        $field = $validator->field('foo');
        $this->assertInstanceOf(ValidationSet::class, $field);
        $this->assertCount(0, $field);
        $this->assertTrue($validator->hasField('foo'));
    }

    /**
     * Tests that field method can be used as a setter
     */
    public function testFieldSetter(): void
    {
        $validator = new Validator();
        $validationSet = new ValidationSet();
        $validator->field('thing', $validationSet);
        $this->assertSame($validationSet, $validator->field('thing'));
    }

    /**
     * Tests the remove method
     */
    public function testRemove(): void
    {
        $validator = new Validator();
        $validator->add('title', 'not-blank', ['rule' => 'notBlank']);
        $validator->add('title', 'foo', ['rule' => 'bar']);
        $this->assertCount(2, $validator->field('title'));
        $validator->remove('title');
        $this->assertCount(0, $validator->field('title'));
        $validator->remove('title');

        $validator->add('title', 'not-blank', ['rule' => 'notBlank']);
        $validator->add('title', 'foo', ['rule' => 'bar']);
        $this->assertCount(2, $validator->field('title'));
        $validator->remove('title', 'foo');
        $this->assertCount(1, $validator->field('title'));
        $this->assertNull($validator->field('title')->rule('foo'));
    }

    /**
     * Tests the requirePresence method
     */
    public function testRequirePresence(): void
    {
        $validator = new Validator();
        $this->assertSame($validator, $validator->requirePresence('title'));
        $this->assertTrue($validator->field('title')->isPresenceRequired());

        $validator->requirePresence('title', false);
        $this->assertFalse($validator->field('title')->isPresenceRequired());

        $validator->requirePresence('title', 'create');
        $this->assertSame('create', $validator->field('title')->isPresenceRequired());

        $validator->requirePresence('title', 'update');
        $this->assertSame('update', $validator->field('title')->isPresenceRequired());
    }

    /**
     * Tests the requirePresence method
     */
    public function testRequirePresenceAsArray(): void
    {
        $validator = new Validator();
        $validator->requirePresence(['title', 'created']);
        $this->assertTrue($validator->field('title')->isPresenceRequired());
        $this->assertTrue($validator->field('created')->isPresenceRequired());

        $validator->requirePresence([
            'title' => [
                'mode' => false,
            ],
            'content' => [
                'mode' => 'update',
            ],
            'subject',
        ], true);
        $this->assertFalse($validator->field('title')->isPresenceRequired());
        $this->assertSame('update', $validator->field('content')->isPresenceRequired());
        $this->assertTrue($validator->field('subject')->isPresenceRequired());
    }

    /**
     * Tests the requirePresence failure case
     */
    public function testRequirePresenceAsArrayFailure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $validator = new Validator();
        $validator->requirePresence(['title' => 'derp', 'created' => false]);
    }

    /**
     * Tests the requirePresence method when passing a callback
     */
    public function testRequirePresenceCallback(): void
    {
        $validator = new Validator();
        $require = true;
        $validator->requirePresence('title', function ($context) use (&$require) {
            $this->assertEquals([], $context['data']);
            $this->assertEquals([], $context['providers']);
            $this->assertSame('title', $context['field']);
            $this->assertTrue($context['newRecord']);

            return $require;
        });
        $this->assertTrue($validator->isPresenceRequired('title', true));

        $require = false;
        $this->assertFalse($validator->isPresenceRequired('title', true));
    }

    /**
     * Tests the isPresenceRequired method
     */
    public function testIsPresenceRequired(): void
    {
        $validator = new Validator();
        $this->assertSame($validator, $validator->requirePresence('title'));
        $this->assertTrue($validator->isPresenceRequired('title', true));
        $this->assertTrue($validator->isPresenceRequired('title', false));

        $validator->requirePresence('title', false);
        $this->assertFalse($validator->isPresenceRequired('title', true));
        $this->assertFalse($validator->isPresenceRequired('title', false));

        $validator->requirePresence('title', 'create');
        $this->assertTrue($validator->isPresenceRequired('title', true));
        $this->assertFalse($validator->isPresenceRequired('title', false));

        $validator->requirePresence('title', 'update');
        $this->assertTrue($validator->isPresenceRequired('title', false));
        $this->assertFalse($validator->isPresenceRequired('title', true));
    }

    /**
     * Tests errors generated when a field presence is required
     */
    public function testErrorsDeprecated(): void
    {
        $validator = new Validator();
        $validator->requirePresence('title');
        $this->deprecated(function () use ($validator): void {
            $errors = $validator->errors(['foo' => 'something']);
            $expected = ['title' => ['_required' => 'This field is required']];
            $this->assertEquals($expected, $errors);
        });
    }

    /**
     * Tests errors generated when a field presence is required
     */
    public function testErrorsWithPresenceRequired(): void
    {
        $validator = new Validator();
        $validator->requirePresence('title');
        $errors = $validator->validate(['foo' => 'something']);
        $expected = ['title' => ['_required' => 'This field is required']];
        $this->assertEquals($expected, $errors);

        $this->assertEmpty($validator->validate(['title' => 'bar']));

        $validator->requirePresence('title', false);
        $this->assertEmpty($validator->validate(['foo' => 'bar']));
    }

    /**
     * Test that validation on a certain condition generate errors
     */
    public function testErrorsWithPresenceRequiredOnCreate(): void
    {
        $validator = new Validator();
        $validator->requirePresence('id', 'update');
        $validator->allowEmptyString('id', 'create');
        $validator->requirePresence('title');

        $data = [
            'title' => 'Example title',
        ];

        $expected = [];
        $result = $validator->validate($data);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test that validate() can work with nested data.
     */
    public function testErrorsWithNestedFields(): void
    {
        $validator = new Validator();
        $user = new Validator();
        $user->add('username', 'letter', ['rule' => 'alphanumeric']);

        $comments = new Validator();
        $comments->add('comment', 'letter', ['rule' => 'alphanumeric']);

        $validator->addNested('user', $user);
        $validator->addNestedMany('comments', $comments);

        $data = [
            'user' => [
                'username' => 'is wrong',
            ],
            'comments' => [
                ['comment' => 'is wrong'],
            ],
        ];
        $errors = $validator->validate($data);
        $expected = [
            'user' => [
                'username' => ['letter' => 'The provided value is invalid'],
            ],
            'comments' => [
                0 => ['comment' => ['letter' => 'The provided value is invalid']],
            ],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Test nested fields with many, but invalid data.
     */
    public function testErrorsWithNestedSingleInvalidType(): void
    {
        $validator = new Validator();

        $user = new Validator();
        $user->add('user', 'letter', ['rule' => 'alphanumeric']);
        $validator->addNested('user', $user);

        $data = [
            'user' => 'a string',
        ];
        $errors = $validator->validate($data);
        $expected = [
            'user' => ['_nested' => 'The provided value is invalid'],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Test nested fields with many, but invalid data.
     */
    public function testErrorsWithNestedManyInvalidType(): void
    {
        $validator = new Validator();

        $comments = new Validator();
        $comments->add('comment', 'letter', ['rule' => 'alphanumeric']);
        $validator->addNestedMany('comments', $comments);

        $data = [
            'comments' => 'a string',
        ];
        $errors = $validator->validate($data);
        $expected = [
            'comments' => ['_nested' => 'The provided value is invalid'],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Test nested fields with many, but invalid data.
     */
    public function testErrorsWithNestedManySomeInvalid(): void
    {
        $validator = new Validator();

        $comments = new Validator();
        $comments->add('comment', 'letter', ['rule' => 'alphanumeric']);
        $validator->addNestedMany('comments', $comments);

        $data = [
            'comments' => [
                'a string',
                ['comment' => 'letters'],
                ['comment' => 'more invalid'],
            ],
        ];
        $errors = $validator->validate($data);
        $expected = [
            'comments' => [
                '_nested' => 'The provided value is invalid',
            ],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests custom error messages generated when a field presence is required
     */
    public function testCustomErrorsWithPresenceRequired(): void
    {
        $validator = new Validator();
        $validator->requirePresence('title', true, 'Custom message');
        $errors = $validator->validate(['foo' => 'something']);
        $expected = ['title' => ['_required' => 'Custom message']];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests custom error messages generated when a field presence is required
     */
    public function testCustomErrorsWithPresenceRequiredAsArray(): void
    {
        $validator = new Validator();
        $validator->requirePresence(['title', 'content'], true, 'Custom message');
        $errors = $validator->validate(['foo' => 'something']);
        $expected = [
            'title' => ['_required' => 'Custom message'],
            'content' => ['_required' => 'Custom message'],
        ];
        $this->assertEquals($expected, $errors);

        $validator->requirePresence([
            'title' => [
                'message' => 'Test message',
            ],
            'content',
        ], true, 'Custom message');
        $errors = $validator->validate(['foo' => 'something']);
        $expected = [
            'title' => ['_required' => 'Test message'],
            'content' => ['_required' => 'Custom message'],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests the testAllowEmptyFor method
     */
    public function testAllowEmptyFor(): void
    {
        $validator = new Validator();
        $validator
            ->allowEmptyFor('title')
            ->minLength('title', 5, 'Min. length 5 chars');

        $results = $validator->validate(['title' => null]);
        $this->assertSame([], $results);

        $results = $validator->validate(['title' => '']);
        $this->assertSame(['title' => ['minLength' => 'Min. length 5 chars']], $results);

        $results = $validator->validate(['title' => 0]);
        $this->assertSame(['title' => ['minLength' => 'Min. length 5 chars']], $results);

        $results = $validator->validate(['title' => []]);
        $this->assertSame(['title' => ['minLength' => 'Min. length 5 chars']], $results);

        $validator
            ->allowEmptyFor('name', Validator::EMPTY_STRING)
            ->minLength('name', 5, 'Min. length 5 chars');

        $results = $validator->validate(['name' => null]);
        $this->assertSame([], $results);

        $results = $validator->validate(['name' => '']);
        $this->assertSame([], $results);

        $results = $validator->validate(['name' => 0]);
        $this->assertSame(['name' => ['minLength' => 'Min. length 5 chars']], $results);

        $results = $validator->validate(['name' => []]);
        $this->assertSame(['name' => ['minLength' => 'Min. length 5 chars']], $results);
    }

    /**
     * Tests the allowEmpty method
     */
    public function testAllowEmpty(): void
    {
        $validator = new Validator();
        $this->assertSame($validator, $validator->allowEmptyString('title'));
        $this->assertTrue($validator->field('title')->isEmptyAllowed());

        $validator->allowEmptyString('title', null, 'create');
        $this->assertSame('create', $validator->field('title')->isEmptyAllowed());

        $validator->allowEmptyString('title', null, 'update');
        $this->assertSame('update', $validator->field('title')->isEmptyAllowed());
    }

    /**
     * Tests the allowEmpty method with date/time fields.
     */
    public function testAllowEmptyWithDateTimeFields(): void
    {
        $validator = new Validator();
        $validator->allowEmptyDate('created')
            ->add('created', 'date', ['rule' => 'date']);

        $data = [
            'created' => [
                'year' => '',
                'month' => '',
                'day' => '',
            ],
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result, 'No errors on empty date');

        $data = [
            'created' => [
                'year' => '',
                'month' => '',
                'day' => '',
                'hour' => '',
                'minute' => '',
                'second' => '',
                'meridian' => '',
            ],
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result, 'No errors on empty datetime');

        $validator->allowEmptyTime('created');
        $data = [
            'created' => [
                'hour' => '',
                'minute' => '',
                'meridian' => '',
            ],
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result, 'No errors on empty time');
    }

    /**
     * Tests the allowEmpty method with file fields.
     */
    public function testAllowEmptyWithFileFields(): void
    {
        $validator = new Validator();
        $validator->allowEmptyFile('picture')
            ->add('picture', 'file', ['rule' => 'uploadedFile']);

        $data = [
            'picture' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
            ],
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result, 'No errors on empty file');

        $data = [
            'picture' => new UploadedFile(
                '',
                0,
                UPLOAD_ERR_NO_FILE
            ),
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result, 'No errors on empty file');

        $data = [
            'picture' => [
                'name' => 'fake.png',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_OK,
            ],
        ];
        $result = $validator->validate($data);
        $this->assertNotEmpty($result, 'Invalid file should be caught still.');
    }

    /**
     * Tests the allowEmpty as array method
     */
    public function testAllowEmptyAsArray(): void
    {
        $validator = new Validator();

        $this->deprecated(function () use ($validator): void {
            $validator->allowEmpty([
                'title',
                'subject',
                'posted_at' => [
                    'when' => false,
                    'message' => 'Post time cannot be empty',
                ],
                'updated_at' => [
                    'when' => true,
                ],
                'show_at' => [
                    'when' => Validator::WHEN_UPDATE,
                ],
            ], 'create', 'Cannot be empty');
        });

        $this->assertSame('create', $validator->field('title')->isEmptyAllowed());
        $this->assertSame('create', $validator->field('subject')->isEmptyAllowed());
        $this->assertFalse($validator->field('posted_at')->isEmptyAllowed());
        $this->assertTrue($validator->field('updated_at')->isEmptyAllowed());
        $this->assertSame('update', $validator->field('show_at')->isEmptyAllowed());

        $errors = $validator->validate([
            'title' => '',
            'subject' => null,
            'posted_at' => null,
            'updated_at' => null,
            'show_at' => '',
        ], false);

        $expected = [
            'title' => ['_empty' => 'Cannot be empty'],
            'subject' => ['_empty' => 'Cannot be empty'],
            'posted_at' => ['_empty' => 'Post time cannot be empty'],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests the allowEmpty failure case
     */
    public function testAllowEmptyAsArrayFailure(): void
    {
        $this->deprecated(function (): void {
            $this->expectException(\InvalidArgumentException::class);
            $validator = new Validator();
            $validator->allowEmpty(['title' => 'derp', 'created' => false]);
        });
    }

    /**
     * Tests the allowEmptyString method
     */
    public function testAllowEmptyString(): void
    {
        $validator = new Validator();
        $validator->allowEmptyString('title')
            ->scalar('title');

        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertTrue($validator->isEmptyAllowed('title', false));

        $data = [
            'title' => '',
        ];
        $this->assertEmpty($validator->validate($data));

        $data = [
            'title' => null,
        ];
        $this->assertEmpty($validator->validate($data));

        $data = [
            'title' => [],
        ];
        $this->assertNotEmpty($validator->validate($data));

        $validator = new Validator();
        $validator->allowEmptyString('title', 'message', 'update');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertTrue($validator->isEmptyAllowed('title', false));

        $data = [
            'title' => null,
        ];
        $expected = [
            'title' => ['_empty' => 'message'],
        ];
        $this->assertSame($expected, $validator->validate($data, true));
        $this->assertEmpty($validator->validate($data, false));
    }

    /**
     * Test allowEmptyString with callback
     */
    public function testAllowEmptyStringCallbackWhen(): void
    {
        $validator = new Validator();
        $validator->allowEmptyString(
            'title',
            'very required',
            function ($context) {
                return $context['data']['otherField'] === true;
            }
        )
            ->scalar('title');

        $data = [
            'title' => '',
            'otherField' => false,
        ];
        $this->assertNotEmpty($validator->validate($data));

        $data = [
            'title' => '',
            'otherField' => true,
        ];
        $this->assertEmpty($validator->validate($data));
    }

    /**
     * Tests the notEmptyArray method
     */
    public function testNotEmptyArray(): void
    {
        $validator = new Validator();
        $validator->notEmptyArray('items', 'not empty');

        $this->assertFalse($validator->field('items')->isEmptyAllowed());

        $error = [
            'items' => ['_empty' => 'not empty'],
        ];
        $data = ['items' => ''];
        $result = $validator->validate($data);
        $this->assertSame($error, $result);

        $data = ['items' => null];
        $result = $validator->validate($data);
        $this->assertSame($error, $result);

        $data = ['items' => []];
        $result = $validator->validate($data);
        $this->assertSame($error, $result);

        $data = [
            'items' => [1],
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result);
    }

    /**
     * Tests the allowEmptyFile method
     */
    public function testAllowEmptyFile(): void
    {
        $validator = new Validator();
        $validator->allowEmptyFile('photo')
            ->uploadedFile('photo', []);

        $this->assertTrue($validator->field('photo')->isEmptyAllowed());

        $data = [
            'photo' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
            ],
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result);

        $data = [
            'photo' => null,
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result);

        $data = [
            'photo' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_FORM_SIZE,
            ],
        ];
        $expected = [
            'photo' => [
                'uploadedFile' => 'The provided value is invalid',
            ],
        ];
        $result = $validator->validate($data);
        $this->assertSame($expected, $result);

        $data = [
            'photo' => '',
        ];
        $expected = [
            'photo' => [
                'uploadedFile' => 'The provided value is invalid',
            ],
        ];
        $result = $validator->validate($data);
        $this->assertSame($expected, $result);

        $data = ['photo' => []];
        $result = $validator->validate($data);
        $this->assertSame($expected, $result);

        $validator = new Validator();
        $validator->allowEmptyFile('photo', 'message', 'update');
        $this->assertFalse($validator->isEmptyAllowed('photo', true));
        $this->assertTrue($validator->isEmptyAllowed('photo', false));

        $data = [
            'photo' => null,
        ];
        $expected = [
            'photo' => ['_empty' => 'message'],
        ];
        $this->assertSame($expected, $validator->validate($data, true));
        $this->assertEmpty($validator->validate($data, false));
    }

    /**
     * Tests the notEmptyFile method
     */
    public function testNotEmptyFile(): void
    {
        $validator = new Validator();
        $validator->notEmptyFile('photo', 'required field');

        $this->assertFalse($validator->isEmptyAllowed('photo', true));
        $this->assertFalse($validator->isEmptyAllowed('photo', false));

        $data = [
            'photo' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
            ],
        ];
        $error = ['photo' => ['_empty' => 'required field']];
        $this->assertSame($error, $validator->validate($data));

        $data = ['photo' => null];
        $this->assertSame($error, $validator->validate($data));

        // Empty string and empty array don't trigger errors
        // as rejecting them here would mean accepting them in
        // allowEmptyFile() which is not desirable.
        $data = ['photo' => ''];
        $this->assertEmpty($validator->validate($data));

        $data = ['photo' => []];
        $this->assertEmpty($validator->validate($data));

        $data = [
            'photo' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_FORM_SIZE,
            ],
        ];
        $this->assertEmpty($validator->validate($data));
    }

    /**
     * Test notEmptyFile with update mode.
     *
     * @retrn void
     */
    public function testNotEmptyFileUpdate(): void
    {
        $validator = new Validator();
        $validator->notEmptyArray('photo', 'message', 'update');
        $this->assertTrue($validator->isEmptyAllowed('photo', true));
        $this->assertFalse($validator->isEmptyAllowed('photo', false));

        $data = ['photo' => null];
        $expected = [
            'photo' => ['_empty' => 'message'],
        ];
        $this->assertEmpty($validator->validate($data, true));
        $this->assertSame($expected, $validator->validate($data, false));
    }

    /**
     * Tests the allowEmptyDate method
     */
    public function testAllowEmptyDate(): void
    {
        $validator = new Validator();
        $validator->allowEmptyDate('date')
            ->date('date');

        $this->assertTrue($validator->field('date')->isEmptyAllowed());

        $data = [
            'date' => [
                'year' => '',
                'month' => '',
                'day' => '',
            ],
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result);

        $data = [
            'date' => '',
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result);

        $data = [
            'date' => null,
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result);

        $data = ['date' => []];
        $result = $validator->validate($data);
        $this->assertEmpty($result);
    }

    /**
     * test allowEmptyDate() with an update condition
     */
    public function testAllowEmptyDateUpdate(): void
    {
        $validator = new Validator();
        $validator->allowEmptyArray('date', 'be valid', 'update');
        $this->assertFalse($validator->isEmptyAllowed('date', true));
        $this->assertTrue($validator->isEmptyAllowed('date', false));

        $data = [
            'date' => null,
        ];
        $expected = [
            'date' => ['_empty' => 'be valid'],
        ];
        $this->assertSame($expected, $validator->validate($data, true));
        $this->assertEmpty($validator->validate($data, false));
    }

    /**
     * Tests the notEmptyDate method
     */
    public function testNotEmptyDate(): void
    {
        $validator = new Validator();
        $validator->notEmptyDate('date', 'required field');

        $this->assertFalse($validator->isEmptyAllowed('date', true));
        $this->assertFalse($validator->isEmptyAllowed('date', false));

        $error = ['date' => ['_empty' => 'required field']];
        $data = [
            'date' => [
                'year' => '',
                'month' => '',
                'day' => '',
            ],
        ];
        $result = $validator->validate($data);
        $this->assertSame($error, $result);

        $data = ['date' => ''];
        $result = $validator->validate($data);
        $this->assertSame($error, $result);

        $data = ['date' => null];
        $result = $validator->validate($data);
        $this->assertSame($error, $result);

        $data = ['date' => []];
        $result = $validator->validate($data);
        $this->assertSame($error, $result);

        $data = [
            'date' => [
                'year' => 2019,
                'month' => 2,
                'day' => 17,
            ],
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result);
    }

    /**
     * Test notEmptyDate with update mode
     */
    public function testNotEmptyDateUpdate(): void
    {
        $validator = new Validator();
        $validator->notEmptyDate('date', 'message', 'update');
        $this->assertTrue($validator->isEmptyAllowed('date', true));
        $this->assertFalse($validator->isEmptyAllowed('date', false));

        $data = ['date' => null];
        $expected = ['date' => ['_empty' => 'message']];
        $this->assertSame($expected, $validator->validate($data, false));
        $this->assertEmpty($validator->validate($data, true));
    }

    /**
     * Tests the allowEmptyTime method
     */
    public function testAllowEmptyTime(): void
    {
        $validator = new Validator();
        $validator->allowEmptyTime('time')
            ->time('time');

        $this->assertTrue($validator->field('time')->isEmptyAllowed());

        $data = [
            'time' => [
                'hour' => '',
                'minute' => '',
                'second' => '',
            ],
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result);

        $data = [
            'time' => '',
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result);

        $data = [
            'time' => null,
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result);

        $data = ['time' => []];
        $result = $validator->validate($data);
        $this->assertEmpty($result);
    }

    /**
     * test allowEmptyTime with condition
     */
    public function testAllowEmptyTimeCondition(): void
    {
        $validator = new Validator();
        $validator->allowEmptyTime('time', 'valid time', 'update');
        $this->assertFalse($validator->isEmptyAllowed('time', true));
        $this->assertTrue($validator->isEmptyAllowed('time', false));

        $data = [
            'time' => null,
        ];
        $expected = [
            'time' => ['_empty' => 'valid time'],
        ];
        $this->assertSame($expected, $validator->validate($data, true));
        $this->assertEmpty($validator->validate($data, false));
    }

    /**
     * Tests the notEmptyTime method
     */
    public function testNotEmptyTime(): void
    {
        $validator = new Validator();
        $validator->notEmptyTime('time', 'required field');

        $this->assertFalse($validator->isEmptyAllowed('time', true));
        $this->assertFalse($validator->isEmptyAllowed('time', false));

        $error = ['time' => ['_empty' => 'required field']];
        $data = [
            'time' => [
                'hour' => '',
                'minute' => '',
                'second' => '',
            ],
        ];
        $result = $validator->validate($data);
        $this->assertSame($error, $result);

        $data = ['time' => ''];
        $result = $validator->validate($data);
        $this->assertSame($error, $result);

        $data = ['time' => null];
        $result = $validator->validate($data);
        $this->assertSame($error, $result);

        $data = ['time' => []];
        $result = $validator->validate($data);
        $this->assertSame($error, $result);

        $data = ['time' => ['hour' => 12, 'minute' => 12, 'second' => 12]];
        $result = $validator->validate($data);
        $this->assertEmpty($result);
    }

    /**
     * Test notEmptyTime with update mode
     */
    public function testNotEmptyTimeUpdate(): void
    {
        $validator = new Validator();
        $validator->notEmptyTime('time', 'message', 'update');
        $this->assertTrue($validator->isEmptyAllowed('time', true));
        $this->assertFalse($validator->isEmptyAllowed('time', false));

        $data = ['time' => null];
        $expected = ['time' => ['_empty' => 'message']];
        $this->assertEmpty($validator->validate($data, true));
        $this->assertSame($expected, $validator->validate($data, false));
    }

    /**
     * Tests the allowEmptyDateTime method
     */
    public function testAllowEmptyDateTime(): void
    {
        $validator = new Validator();
        $validator->allowEmptyDate('published')
            ->dateTime('published');

        $this->assertTrue($validator->field('published')->isEmptyAllowed());

        $data = [
            'published' => [
                'year' => '',
                'month' => '',
                'day' => '',
                'hour' => '',
                'minute' => '',
                'second' => '',
            ],
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result);

        $data = [
            'published' => '',
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result);

        $data = [
            'published' => null,
        ];
        $result = $validator->validate($data);
        $this->assertEmpty($result);

        $data = ['published' => []];
        $this->assertEmpty($validator->validate($data));
    }

    /**
     * test allowEmptyDateTime with a condition
     */
    public function testAllowEmptyDateTimeCondition(): void
    {
        $validator = new Validator();
        $validator->allowEmptyDateTime('published', 'datetime required', 'update');
        $this->assertFalse($validator->isEmptyAllowed('published', true));
        $this->assertTrue($validator->isEmptyAllowed('published', false));

        $data = [
            'published' => null,
        ];
        $expected = [
            'published' => ['_empty' => 'datetime required'],
        ];
        $this->assertSame($expected, $validator->validate($data, true));
        $this->assertEmpty($validator->validate($data, false));
    }

    /**
     * Tests the notEmptyDateTime method
     */
    public function testNotEmptyDateTime(): void
    {
        $validator = new Validator();
        $validator->notEmptyDateTime('published', 'required field');

        $this->assertFalse($validator->isEmptyAllowed('published', true));
        $this->assertFalse($validator->isEmptyAllowed('published', false));

        $error = ['published' => ['_empty' => 'required field']];
        $data = [
            'published' => [
                'year' => '',
                'month' => '',
                'day' => '',
                'hour' => '',
                'minute' => '',
                'second' => '',
            ],
        ];
        $result = $validator->validate($data);
        $this->assertSame($error, $result);

        $data = ['published' => ''];
        $result = $validator->validate($data);
        $this->assertSame($error, $result);

        $data = ['published' => null];
        $result = $validator->validate($data);
        $this->assertSame($error, $result);

        $data = ['published' => []];
        $this->assertSame($error, $validator->validate($data));

        $data = [
            'published' => [
                'year' => '2018',
                'month' => '2',
                'day' => '17',
                'hour' => '14',
                'minute' => '32',
                'second' => '33',
            ],
        ];
        $this->assertEmpty($validator->validate($data));
    }

    /**
     * Test notEmptyDateTime with update mode
     */
    public function testNotEmptyDateTimeUpdate(): void
    {
        $validator = new Validator();
        $validator->notEmptyDatetime('published', 'message', 'update');
        $this->assertTrue($validator->isEmptyAllowed('published', true));
        $this->assertFalse($validator->isEmptyAllowed('published', false));

        $data = ['published' => null];
        $expected = ['published' => ['_empty' => 'message']];
        $this->assertSame($expected, $validator->validate($data, false));
        $this->assertEmpty($validator->validate($data, true));
    }

    /**
     * Test the notEmpty() method.
     */
    public function testNotEmpty(): void
    {
        $validator = new Validator();
        $validator->notEmptyString('title');
        $this->assertFalse($validator->field('title')->isEmptyAllowed());

        $validator->allowEmptyString('title');
        $this->assertTrue($validator->field('title')->isEmptyAllowed());
    }

    /**
     * Tests the notEmpty as array method
     */
    public function testNotEmptyAsArray(): void
    {
        $validator = new Validator();
        $validator->notEmptyString('title')->notEmptyString('created');
        $this->assertFalse($validator->field('title')->isEmptyAllowed());
        $this->assertFalse($validator->field('created')->isEmptyAllowed());

        $this->deprecated(function () use ($validator): void {
            $validator->notEmpty([
                'title' => [
                    'when' => false,
                ],
                'content' => [
                    'when' => Validator::WHEN_UPDATE,
                ],
                'posted_at' => [
                    'when' => Validator::WHEN_CREATE,
                ],
                'show_at' => [
                    'message' => 'Show date cannot be empty',
                    'when' => false,
                ],
                'subject',
            ], 'Not empty', true);
        });

        $this->assertFalse($validator->field('title')->isEmptyAllowed());
        $this->assertTrue($validator->isEmptyAllowed('content', true));
        $this->assertFalse($validator->isEmptyAllowed('content', false));
        $this->assertFalse($validator->isEmptyAllowed('posted_at', true));
        $this->assertTrue($validator->isEmptyAllowed('posted_at', false));
        $this->assertTrue($validator->field('subject')->isEmptyAllowed());

        $errors = $validator->validate([
            'title' => '',
            'content' => '',
            'posted_at' => null,
            'show_at' => null,
            'subject' => '',
        ], false);

        $expected = [
            'title' => ['_empty' => 'Not empty'],
            'content' => ['_empty' => 'Not empty'],
            'show_at' => ['_empty' => 'Show date cannot be empty'],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests the notEmpty failure case
     */
    public function testNotEmptyAsArrayFailure(): void
    {
        $this->deprecated(function (): void {
            $this->expectException(\InvalidArgumentException::class);
            $validator = new Validator();
            $validator->notEmpty(['title' => 'derp', 'created' => false]);
        });
    }

    /**
     * Test the notEmpty() method.
     */
    public function testNotEmptyModes(): void
    {
        $validator = new Validator();
        $validator->notEmptyString('title', 'Need a title', 'create');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertTrue($validator->isEmptyAllowed('title', false));

        $validator->notEmptyString('title', 'Need a title', 'update');
        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->notEmptyString('title', 'Need a title');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->notEmptyString('title');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));
    }

    /**
     * Test interactions between notEmpty() and isAllowed().
     */
    public function testNotEmptyAndIsAllowed(): void
    {
        $validator = new Validator();
        $validator->allowEmptyString('title')
            ->notEmptyString('title', 'Need it', 'update');
        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->allowEmptyString('title')
            ->notEmptyString('title');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->notEmptyString('title')
            ->allowEmptyString('title', null, 'create');
        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));
    }

    /**
     * Tests the allowEmpty method when passing a callback
     */
    public function testAllowEmptyCallback(): void
    {
        $validator = new Validator();
        $allow = true;
        $validator->allowEmptyString('title', null, function ($context) use (&$allow) {
            $this->assertEquals([], $context['data']);
            $this->assertEquals([], $context['providers']);
            $this->assertTrue($context['newRecord']);

            return $allow;
        });
        $this->assertTrue($validator->isEmptyAllowed('title', true));

        $allow = false;
        $this->assertFalse($validator->isEmptyAllowed('title', true));
    }

    /**
     * Tests the notEmpty method when passing a callback
     */
    public function testNotEmptyCallback(): void
    {
        $validator = new Validator();
        $prevent = true;
        $validator->notEmptyString('title', 'error message', function ($context) use (&$prevent) {
            $this->assertEquals([], $context['data']);
            $this->assertEquals([], $context['providers']);
            $this->assertFalse($context['newRecord']);

            return $prevent;
        });
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $prevent = false;
        $this->assertTrue($validator->isEmptyAllowed('title', false));
    }

    /**
     * Tests the isEmptyAllowed method
     */
    public function testIsEmptyAllowed(): void
    {
        $validator = new Validator();
        $this->assertSame($validator, $validator->allowEmptyString('title'));
        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertTrue($validator->isEmptyAllowed('title', false));

        $validator->notEmptyString('title');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->allowEmptyString('title', null, 'create');
        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->allowEmptyString('title', null, 'update');
        $this->assertTrue($validator->isEmptyAllowed('title', false));
        $this->assertFalse($validator->isEmptyAllowed('title', true));
    }

    /**
     * Tests errors generated when a field is not allowed to be empty
     */
    public function testErrorsWithEmptyNotAllowed(): void
    {
        $validator = new Validator();
        $validator->notEmptyString('title');
        $errors = $validator->validate(['title' => '']);
        $expected = ['title' => ['_empty' => 'This field cannot be left empty']];
        $this->assertEquals($expected, $errors);

        $errors = $validator->validate(['title' => null]);
        $expected = ['title' => ['_empty' => 'This field cannot be left empty']];
        $this->assertEquals($expected, $errors);

        $errors = $validator->validate(['title' => 0]);
        $this->assertEmpty($errors);

        $errors = $validator->validate(['title' => '0']);
        $this->assertEmpty($errors);

        $errors = $validator->validate(['title' => false]);
        $this->assertEmpty($errors);
    }

    /**
     * Tests custom error messages generated when a field is allowed to be empty
     */
    public function testCustomErrorsWithAllowedEmpty(): void
    {
        $validator = new Validator();
        $validator->allowEmptyString('title', 'Custom message', false);

        $errors = $validator->validate(['title' => null]);
        $expected = ['title' => ['_empty' => 'Custom message']];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests custom error messages generated when a field is not allowed to be empty
     */
    public function testCustomErrorsWithEmptyNotAllowed(): void
    {
        $validator = new Validator();
        $validator->notEmptyString('title', 'Custom message');
        $errors = $validator->validate(['title' => '']);
        $expected = ['title' => ['_empty' => 'Custom message']];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests errors generated when a field is allowed to be empty
     */
    public function testErrorsWithEmptyAllowed(): void
    {
        $validator = new Validator();
        $validator->allowEmptyString('title');
        $errors = $validator->validate(['title' => '']);
        $this->assertEmpty($errors);

        $errors = $validator->validate(['title' => []]);
        $this->assertEmpty($errors);

        $errors = $validator->validate(['title' => null]);
        $this->assertEmpty($errors);

        $errors = $validator->validate(['title' => 0]);
        $this->assertEmpty($errors);

        $errors = $validator->validate(['title' => 0.0]);
        $this->assertEmpty($errors);

        $errors = $validator->validate(['title' => '0']);
        $this->assertEmpty($errors);

        $errors = $validator->validate(['title' => false]);
        $this->assertEmpty($errors);
    }

    /**
     * Test the provider() method
     */
    public function testProvider(): void
    {
        $validator = new Validator();
        $object = new stdClass();
        $this->assertSame($validator, $validator->setProvider('foo', $object));
        $this->assertSame($object, $validator->getProvider('foo'));
        $this->assertNull($validator->getProvider('bar'));

        $another = new stdClass();
        $this->assertSame($validator, $validator->setProvider('bar', $another));
        $this->assertSame($another, $validator->getProvider('bar'));

        $this->assertEquals(new RulesProvider(), $validator->getProvider('default'));
    }

    public function testProviderWarning(): void
    {
        $this->expectError();
        $this->expectErrorMessage('The provider must be an object or class name string. Got `array` instead.');

        $validator = new Validator();
        $validator->setProvider('test', []);
    }

    /**
     * Tests validate() method when using validators from the default provider, this proves
     * that it returns a default validation message and the custom one set in the rule
     */
    public function testErrorsFromDefaultProvider(): void
    {
        $validator = new Validator();
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('email', 'notBlank', ['rule' => 'notBlank'])
            ->add('email', 'email', ['rule' => 'email', 'message' => 'Y u no write email?']);
        $errors = $validator->validate(['email' => 'not an email!']);
        $expected = [
            'email' => [
                'alpha' => 'The provided value is invalid',
                'email' => 'Y u no write email?',
            ],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests using validation methods from different providers and returning the error
     * as a string
     */
    public function testErrorsFromCustomProvider(): void
    {
        $validator = new Validator();
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);

        $thing = $this->getMockBuilder('\stdClass')
            ->addMethods(['isCool'])
            ->getMock();
        $thing->expects($this->once())->method('isCool')
            ->will($this->returnCallback(function ($data, $context) use ($thing) {
                $this->assertSame('bar', $data);
                $expected = [
                    'default' => new RulesProvider(),
                    'thing' => $thing,
                ];
                $expected = [
                    'newRecord' => true,
                    'providers' => $expected,
                    'data' => [
                        'email' => '!',
                        'title' => 'bar',
                    ],
                    'field' => 'title',
                ];
                $this->assertEquals($expected, $context);

                return "That ain't cool, yo";
            }));

        $validator->setProvider('thing', $thing);
        $errors = $validator->validate(['email' => '!', 'title' => 'bar']);
        $expected = [
            'email' => ['alpha' => 'The provided value is invalid'],
            'title' => ['cool' => "That ain't cool, yo"],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests that it is possible to pass extra arguments to the validation function
     * and it still gets the providers as last argument
     */
    public function testMethodsWithExtraArguments(): void
    {
        $validator = new Validator();
        $validator->add('title', 'cool', [
            'rule' => ['isCool', 'and', 'awesome'],
            'provider' => 'thing',
        ]);
        $thing = $this->getMockBuilder('\stdClass')
            ->addMethods(['isCool'])
            ->getMock();
        $thing->expects($this->once())->method('isCool')
            ->will($this->returnCallback(function ($data, $a, $b, $context) use ($thing) {
                $this->assertSame('bar', $data);
                $this->assertSame('and', $a);
                $this->assertSame('awesome', $b);
                $expected = [
                    'default' => new RulesProvider(),
                    'thing' => $thing,
                ];
                $expected = [
                    'newRecord' => true,
                    'providers' => $expected,
                    'data' => [
                        'email' => '!',
                        'title' => 'bar',
                    ],
                    'field' => 'title',
                ];
                $this->assertEquals($expected, $context);

                return "That ain't cool, yo";
            }));
        $validator->setProvider('thing', $thing);
        $errors = $validator->validate(['email' => '!', 'title' => 'bar']);
        $expected = [
            'title' => ['cool' => "That ain't cool, yo"],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests that it is possible to use a closure as a rule
     */
    public function testUsingClosureAsRule(): void
    {
        $validator = new Validator();
        $validator->add('name', 'myRule', [
            'rule' => function ($data, $provider) {
                $this->assertSame('foo', $data);

                return 'You fail';
            },
        ]);
        $expected = ['name' => ['myRule' => 'You fail']];
        $this->assertEquals($expected, $validator->validate(['name' => 'foo']));
    }

    /**
     * Tests that setting last globally will stop validating the rest of the rules
     */
    public function testErrorsWithLastRuleGlobal(): void
    {
        $validator = new Validator();
        $validator->setStopOnFailure()
            ->notBlank('email', 'Fill something in!')
            ->email('email', false, 'Y u no write email?');
        $errors = $validator->validate(['email' => '']);
        $expected = [
            'email' => [
                'notBlank' => 'Fill something in!',
            ],
        ];

        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests that setting last to a rule will stop validating the rest of the rules
     */
    public function testErrorsWithLastRule(): void
    {
        $validator = new Validator();
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric', 'last' => true])
            ->add('email', 'email', ['rule' => 'email', 'message' => 'Y u no write email?']);
        $errors = $validator->validate(['email' => 'not an email!']);
        $expected = [
            'email' => [
                'alpha' => 'The provided value is invalid',
            ],
        ];

        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests it is possible to get validation sets for a field using an array interface
     */
    public function testArrayAccessGet(): void
    {
        $validator = new Validator();
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
        $this->assertSame($validator['email'], $validator->field('email'));
        $this->assertSame($validator['title'], $validator->field('title'));
    }

    /**
     * Tests it is possible to check for validation sets for a field using an array interface
     */
    public function testArrayAccessExists(): void
    {
        $validator = new Validator();
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
        $this->assertArrayHasKey('email', $validator);
        $this->assertArrayHasKey('title', $validator);
        $this->assertArrayNotHasKey('foo', $validator);
    }

    /**
     * Tests it is possible to set validation rules for a field using an array interface
     */
    public function testArrayAccessSet(): void
    {
        $validator = new Validator();
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
        $validator['name'] = $validator->field('title');
        $this->assertSame($validator->field('title'), $validator->field('name'));
        $validator['name'] = ['alpha' => ['rule' => 'alphanumeric']];
        $this->assertEquals($validator->field('email'), $validator->field('email'));
    }

    /**
     * Tests it is possible to unset validation rules
     */
    public function testArrayAccessUnset(): void
    {
        $validator = new Validator();
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
        $this->assertArrayHasKey('title', $validator);
        unset($validator['title']);
        $this->assertArrayNotHasKey('title', $validator);
    }

    /**
     * Tests the countable interface
     */
    public function testCount(): void
    {
        $validator = new Validator();
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
        $this->assertCount(2, $validator);
    }

    /**
     * Tests adding rules via alternative syntax
     */
    public function testAddMultiple(): void
    {
        $validator = new Validator();
        $validator->add('title', [
            'notBlank' => [
                'rule' => 'notBlank',
            ],
            'length' => [
                'rule' => ['minLength', 10],
                'message' => 'Titles need to be at least 10 characters long',
            ],
        ]);
        $set = $validator->field('title');
        $this->assertInstanceOf(ValidationSet::class, $set);
        $this->assertCount(2, $set);
    }

    /**
     * Tests adding rules via alternative syntax and numeric keys
     */
    public function testAddMultipleNumericKeyArrays(): void
    {
        $validator = new Validator();

        $this->deprecated(function () use ($validator): void {
            $validator->add('title', [
                [
                    'rule' => 'notBlank',
                ],
                [
                    'rule' => ['minLength', 10],
                    'message' => 'Titles need to be at least 10 characters long',
                ],
            ]);
        });

        $set = $validator->field('title');
        $this->assertInstanceOf(ValidationSet::class, $set);
        $this->assertCount(2, $set);
    }

    /**
     * Tests adding rules via alternative syntax and numeric keys
     */
    public function testAddMultipleNumericKeyArraysInvalid(): void
    {
        $validator = new Validator();
        $validator->add('title', 'notBlank', ['rule' => 'notBlank']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You cannot add a rule without a unique name, already existing rule found: notBlank');

        $validator->add('title', [
            [
                'rule' => 'notBlank',
            ],
            [
                'rule' => ['minLength', 10],
                'message' => 'Titles need to be at least 10 characters long',
            ],
        ]);
    }

    /**
     * Integration test for compareWith validator.
     */
    public function testCompareWithIntegration(): void
    {
        $validator = new Validator();
        $validator->add('password', [
            'compare' => [
                'rule' => ['compareWith', 'password_compare'],
            ],
        ]);
        $data = [
            'password' => 'test',
            'password_compare' => 'not the same',
        ];
        $this->assertNotEmpty($validator->validate($data), 'Validation should fail.');
    }

    /**
     * Test debugInfo helper method.
     */
    public function testDebugInfo(): void
    {
        $validator = new Validator();
        $validator->setProvider('test', $this);
        $validator->add('title', 'not-empty', ['rule' => 'notBlank']);
        $validator->requirePresence('body');
        $validator->allowEmptyString('published');

        $result = $validator->__debugInfo();
        $expected = [
            '_providers' => ['test'],
            '_fields' => [
                'title' => [
                    'isPresenceRequired' => false,
                    'isEmptyAllowed' => false,
                    'rules' => ['not-empty'],
                ],
                'body' => [
                    'isPresenceRequired' => true,
                    'isEmptyAllowed' => false,
                    'rules' => [],
                ],
                'published' => [
                    'isPresenceRequired' => false,
                    'isEmptyAllowed' => true,
                    'rules' => [],
                ],
            ],
            '_presenceMessages' => [],
            '_allowEmptyMessages' => [],
            '_allowEmptyFlags' => [
                'published' => Validator::EMPTY_STRING,
            ],
            '_useI18n' => true,
            '_stopOnFailure' => false,
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests that the 'create' and 'update' modes are preserved when using
     * nested validators
     */
    public function testNestedValidatorCreate(): void
    {
        $validator = new Validator();
        $inner = new Validator();
        $inner->add('username', 'email', ['rule' => 'email', 'on' => 'create']);
        $validator->addNested('user', $inner);
        $this->assertNotEmpty($validator->validate(['user' => ['username' => 'example']], true));
        $this->assertEmpty($validator->validate(['user' => ['username' => 'a']], false));
    }

    /**
     * Tests that the 'create' and 'update' modes are preserved when using
     * nested validators
     */
    public function testNestedManyValidatorCreate(): void
    {
        $validator = new Validator();
        $inner = new Validator();
        $inner->add('username', 'email', ['rule' => 'email', 'on' => 'create']);
        $validator->addNestedMany('user', $inner);
        $this->assertNotEmpty($validator->validate(['user' => [['username' => 'example']]], true));
        $this->assertEmpty($validator->validate(['user' => [['username' => 'a']]], false));
    }

    /**
     * Tests the notBlank proxy method
     */
    public function testNotBlank(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'notBlank');
        $this->assertNotEmpty($validator->validate(['username' => '  ']));
    }

    /**
     * Tests the alphanumeric proxy method
     */
    public function testAlphanumeric(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'alphaNumeric');
        $this->assertNotEmpty($validator->validate(['username' => '$']));
    }

    /**
     * Tests the notalphanumeric proxy method
     */
    public function testNotAlphanumeric(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'notAlphaNumeric');
        $this->assertEmpty($validator->validate(['username' => '$']));
    }

    /**
     * Tests the asciialphanumeric proxy method
     */
    public function testAsciiAlphanumeric(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'asciiAlphaNumeric');
        $this->assertNotEmpty($validator->validate(['username' => '$']));
    }

    /**
     * Tests the notalphanumeric proxy method
     */
    public function testNotAsciiAlphanumeric(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'notAsciiAlphaNumeric');
        $this->assertEmpty($validator->validate(['username' => '$']));
    }

    /**
     * Tests the lengthBetween proxy method
     */
    public function testLengthBetween(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'lengthBetween', [5, 7], [5, 7]);
        $this->assertNotEmpty($validator->validate(['username' => 'foo']));
    }

    /**
     * Tests the lengthBetween proxy method
     */
    public function testLengthBetweenFailure(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $validator = new Validator();
        $validator->lengthBetween('username', [7]);
    }

    /**
     * Tests the creditCard proxy method
     */
    public function testCreditCard(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'creditCard', 'all', ['all', true], 'creditCard');
        $this->assertNotEmpty($validator->validate(['username' => 'foo']));
    }

    /**
     * Tests the greaterThan proxy method
     */
    public function testGreaterThan(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'greaterThan', 5, [Validation::COMPARE_GREATER, 5], 'comparison');
        $this->assertNotEmpty($validator->validate(['username' => 2]));
    }

    /**
     * Tests the greaterThanOrEqual proxy method
     */
    public function testGreaterThanOrEqual(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'greaterThanOrEqual', 5, [Validation::COMPARE_GREATER_OR_EQUAL, 5], 'comparison');
        $this->assertNotEmpty($validator->validate(['username' => 2]));
    }

    /**
     * Tests the lessThan proxy method
     */
    public function testLessThan(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'lessThan', 5, [Validation::COMPARE_LESS, 5], 'comparison');
        $this->assertNotEmpty($validator->validate(['username' => 5]));
    }

    /**
     * Tests the lessThanOrEqual proxy method
     */
    public function testLessThanOrEqual(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'lessThanOrEqual', 5, [Validation::COMPARE_LESS_OR_EQUAL, 5], 'comparison');
        $this->assertNotEmpty($validator->validate(['username' => 6]));
    }

    /**
     * Tests the equals proxy method
     */
    public function testEquals(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'equals', 5, [Validation::COMPARE_EQUAL, 5], 'comparison');
        $this->assertEmpty($validator->validate(['username' => 5]));
        $this->assertNotEmpty($validator->validate(['username' => 6]));
    }

    /**
     * Tests the notEquals proxy method
     */
    public function testNotEquals(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'notEquals', 5, [Validation::COMPARE_NOT_EQUAL, 5], 'comparison');
        $this->assertNotEmpty($validator->validate(['username' => 5]));
    }

    /**
     * Tests the sameAs proxy method
     */
    public function testSameAs(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'sameAs', 'other', ['other', Validation::COMPARE_SAME], 'compareFields');
        $this->assertNotEmpty($validator->validate(['username' => 'foo']));
        $this->assertNotEmpty($validator->validate(['username' => 1, 'other' => '1']));
    }

    /**
     * Tests the notSameAs proxy method
     */
    public function testNotSameAs(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'notSameAs', 'other', ['other', Validation::COMPARE_NOT_SAME], 'compareFields');
        $this->assertNotEmpty($validator->validate(['username' => 'foo', 'other' => 'foo']));
    }

    /**
     * Tests the equalToField proxy method
     */
    public function testEqualToField(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'equalToField', 'other', ['other', Validation::COMPARE_EQUAL], 'compareFields');
        $this->assertNotEmpty($validator->validate(['username' => 'foo']));
        $this->assertNotEmpty($validator->validate(['username' => 'foo', 'other' => 'bar']));
    }

    /**
     * Tests the notEqualToField proxy method
     */
    public function testNotEqualToField(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'notEqualToField', 'other', ['other', Validation::COMPARE_NOT_EQUAL], 'compareFields');
        $this->assertNotEmpty($validator->validate(['username' => 'foo', 'other' => 'foo']));
    }

    /**
     * Tests the greaterThanField proxy method
     */
    public function testGreaterThanField(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'greaterThanField', 'other', ['other', Validation::COMPARE_GREATER], 'compareFields');
        $this->assertNotEmpty($validator->validate(['username' => 1, 'other' => 1]));
        $this->assertNotEmpty($validator->validate(['username' => 1, 'other' => 2]));
    }

    /**
     * Tests the greaterThanOrEqualToField proxy method
     */
    public function testGreaterThanOrEqualToField(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'greaterThanOrEqualToField', 'other', ['other', Validation::COMPARE_GREATER_OR_EQUAL], 'compareFields');
        $this->assertNotEmpty($validator->validate(['username' => 1, 'other' => 2]));
    }

    /**
     * Tests the lessThanField proxy method
     */
    public function testLessThanField(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'lessThanField', 'other', ['other', Validation::COMPARE_LESS], 'compareFields');
        $this->assertNotEmpty($validator->validate(['username' => 1, 'other' => 1]));
        $this->assertNotEmpty($validator->validate(['username' => 2, 'other' => 1]));
    }

    /**
     * Tests the lessThanOrEqualToField proxy method
     */
    public function testLessThanOrEqualToField(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'lessThanOrEqualToField', 'other', ['other', Validation::COMPARE_LESS_OR_EQUAL], 'compareFields');
        $this->assertNotEmpty($validator->validate(['username' => 2, 'other' => 1]));
    }

    /**
     * Tests the containsNonAlphaNumeric proxy method
     */
    public function testContainsNonAlphaNumeric(): void
    {
        $this->deprecated(function (): void {
            $validator = new Validator();
            $this->assertProxyMethod($validator, 'containsNonAlphaNumeric', 2, [2]);
            $this->assertNotEmpty($validator->validate(['username' => '$']));
        });
    }

    /**
     * Tests the date proxy method
     */
    public function testDate(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'date', ['ymd'], [['ymd']]);
        $this->assertNotEmpty($validator->validate(['username' => 'not a date']));
    }

    /**
     * Tests the dateTime proxy method
     */
    public function testDateTime(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'dateTime', ['ymd'], [['ymd']], 'datetime');
        $this->assertNotEmpty($validator->validate(['username' => 'not a date']));

        $validator = (new Validator())->dateTime('thedate', ['iso8601']);
        $this->assertEmpty($validator->validate(['thedate' => '2020-05-01T12:34:56Z']));
    }

    /**
     * Tests the time proxy method
     */
    public function testTime(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'time');
        $this->assertNotEmpty($validator->validate(['username' => 'not a time']));
    }

    /**
     * Tests the localizedTime proxy method
     */
    public function testLocalizedTime(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'localizedTime', 'date', ['date']);
        $this->assertNotEmpty($validator->validate(['username' => 'not a date']));
    }

    /**
     * Tests the boolean proxy method
     */
    public function testBoolean(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'boolean');
        $this->assertNotEmpty($validator->validate(['username' => 'not a boolean']));
    }

    /**
     * Tests the decimal proxy method
     */
    public function testDecimal(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'decimal', 2, [2]);
        $this->assertNotEmpty($validator->validate(['username' => 10.1]));
    }

    /**
     * Tests the IP proxy methods
     */
    public function testIps(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'ip');
        $this->assertNotEmpty($validator->validate(['username' => 'not ip']));

        $this->assertProxyMethod($validator, 'ipv4', null, ['ipv4'], 'ip');
        $this->assertNotEmpty($validator->validate(['username' => 'not ip']));

        $this->assertProxyMethod($validator, 'ipv6', null, ['ipv6'], 'ip');
        $this->assertNotEmpty($validator->validate(['username' => 'not ip']));
    }

    /**
     * Tests the minLength proxy method
     */
    public function testMinLength(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'minLength', 2, [2]);
        $this->assertNotEmpty($validator->validate(['username' => 'a']));
    }

    /**
     * Tests the minLengthBytes proxy method
     */
    public function testMinLengthBytes(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'minLengthBytes', 11, [11]);
        $this->assertNotEmpty($validator->validate(['username' => 'ÆΔΩЖÇ']));
    }

    /**
     * Tests the maxLength proxy method
     */
    public function testMaxLength(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'maxLength', 2, [2]);
        $this->assertNotEmpty($validator->validate(['username' => 'aaa']));
    }

    /**
     * Tests the maxLengthBytes proxy method
     */
    public function testMaxLengthBytes(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'maxLengthBytes', 9, [9]);
        $this->assertNotEmpty($validator->validate(['username' => 'ÆΔΩЖÇ']));
    }

    /**
     * Tests the numeric proxy method
     */
    public function testNumeric(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'numeric');
        $this->assertEmpty($validator->validate(['username' => '22']));
        $this->assertNotEmpty($validator->validate(['username' => 'a']));
    }

    /**
     * Tests the naturalNumber proxy method
     */
    public function testNaturalNumber(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'naturalNumber', null, [false]);
        $this->assertNotEmpty($validator->validate(['username' => 0]));
    }

    /**
     * Tests the nonNegativeInteger proxy method
     */
    public function testNonNegativeInteger(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'nonNegativeInteger', null, [true], 'naturalNumber');
        $this->assertNotEmpty($validator->validate(['username' => -1]));
    }

    /**
     * Tests the range proxy method
     */
    public function testRange(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'range', [1, 4], [1, 4]);
        $this->assertNotEmpty($validator->validate(['username' => 5]));
    }

    /**
     * Tests the range failure case
     */
    public function testRangeFailure(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $validator = new Validator();
        $validator->range('username', [1]);
    }

    /**
     * Tests the url proxy method
     */
    public function testUrl(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'url', null, [false]);
        $this->assertNotEmpty($validator->validate(['username' => 'not url']));
    }

    /**
     * Tests the urlWithProtocol proxy method
     */
    public function testUrlWithProtocol(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'urlWithProtocol', null, [true], 'url');
        $this->assertNotEmpty($validator->validate(['username' => 'google.com']));
    }

    /**
     * Tests the inList proxy method
     */
    public function testInList(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'inList', ['a', 'b'], [['a', 'b']]);
        $this->assertNotEmpty($validator->validate(['username' => 'c']));
    }

    /**
     * Tests the uuid proxy method
     */
    public function testUuid(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'uuid');
        $this->assertNotEmpty($validator->validate(['username' => 'not uuid']));
    }

    /**
     * Tests the uploadedFile proxy method
     */
    public function testUploadedFile(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'uploadedFile', ['foo' => 'bar'], [['foo' => 'bar']]);
        $this->assertNotEmpty($validator->validate(['username' => []]));
    }

    /**
     * Tests the latlog proxy methods
     */
    public function testLatLong(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'latLong', null, [], 'geoCoordinate');
        $this->assertNotEmpty($validator->validate(['username' => 2000]));

        $this->assertProxyMethod($validator, 'latitude');
        $this->assertNotEmpty($validator->validate(['username' => 2000]));

        $this->assertProxyMethod($validator, 'longitude');
        $this->assertNotEmpty($validator->validate(['username' => 2000]));
    }

    /**
     * Tests the ascii proxy method
     */
    public function testAscii(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'ascii');
        $this->assertNotEmpty($validator->validate(['username' => 'ü']));
    }

    /**
     * Tests the utf8 proxy methods
     */
    public function testUtf8(): void
    {
        // Grinning face
        $extended = 'some' . "\xf0\x9f\x98\x80" . 'value';
        $validator = new Validator();

        $this->assertProxyMethod($validator, 'utf8', null, [['extended' => false]]);
        $this->assertEmpty($validator->validate(['username' => 'ü']));
        $this->assertNotEmpty($validator->validate(['username' => $extended]));
    }

    /**
     * Test utf8extended proxy method.
     */
    public function testUtf8Extended(): void
    {
        // Grinning face
        $extended = 'some' . "\xf0\x9f\x98\x80" . 'value';
        $validator = new Validator();

        $this->assertProxyMethod($validator, 'utf8Extended', null, [['extended' => true]], 'utf8');
        $this->assertEmpty($validator->validate(['username' => 'ü']));
        $this->assertEmpty($validator->validate(['username' => $extended]));
    }

    /**
     * Tests the email proxy method
     */
    public function testEmail(): void
    {
        $validator = new Validator();
        $validator->email('username');
        $this->assertEmpty($validator->validate(['username' => 'test@example.com']));
        $this->assertNotEmpty($validator->validate(['username' => 'not an email']));
    }

    /**
     * Tests the integer proxy method
     */
    public function testInteger(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'integer', null, [], 'isInteger');
        $this->assertNotEmpty($validator->validate(['username' => 'not integer']));
    }

    /**
     * Tests the isArray proxy method
     */
    public function testIsArray(): void
    {
        $validator = new Validator();
        $validator->isArray('username');
        $this->assertEmpty($validator->validate(['username' => [1, 2, 3]]));
        $this->assertNotEmpty($validator->validate(['username' => 'is not an array']));
    }

    /**
     * Tests the scalar proxy method
     */
    public function testScalar(): void
    {
        $validator = new Validator();
        $validator->scalar('username');
        $this->assertEmpty($validator->validate(['username' => 'scalar']));
        $this->assertNotEmpty($validator->validate(['username' => ['array']]));
    }

    /**
     * Tests the hexColor proxy method
     */
    public function testHexColor(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'hexColor');
        $this->assertEmpty($validator->validate(['username' => '#FFFFFF']));
        $this->assertNotEmpty($validator->validate(['username' => 'FFFFFF']));
    }

    /**
     * Tests the multiple proxy method
     */
    public function testMultiple(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod(
            $validator,
            'multipleOptions',
            ['min' => 1, 'caseInsensitive' => true],
            [['min' => 1], true],
            'multiple'
        );

        $this->assertProxyMethod(
            $validator,
            'multipleOptions',
            ['min' => 1, 'caseInsensitive' => false],
            [['min' => 1], false],
            'multiple'
        );

        $this->assertNotEmpty($validator->validate(['username' => '']));
    }

    /**
     * Tests the hasAtLeast method
     */
    public function testHasAtLeast(): void
    {
        $validator = new Validator();
        $validator->hasAtLeast('things', 3);
        $this->assertEmpty($validator->validate(['things' => [1, 2, 3]]));
        $this->assertEmpty($validator->validate(['things' => [1, 2, 3, 4]]));
        $this->assertNotEmpty($validator->validate(['things' => [1, 2]]));
        $this->assertNotEmpty($validator->validate(['things' => []]));
        $this->assertNotEmpty($validator->validate(['things' => 'string']));

        $this->assertEmpty($validator->validate(['things' => ['_ids' => [1, 2, 3]]]));
        $this->assertEmpty($validator->validate(['things' => ['_ids' => [1, 2, 3, 4]]]));
        $this->assertNotEmpty($validator->validate(['things' => ['_ids' => [1, 2]]]));
        $this->assertNotEmpty($validator->validate(['things' => ['_ids' => []]]));
        $this->assertNotEmpty($validator->validate(['things' => ['_ids' => 'string']]));
    }

    /**
     * Tests the hasAtMost method
     */
    public function testHasAtMost(): void
    {
        $validator = new Validator();
        $validator->hasAtMost('things', 3);
        $this->assertEmpty($validator->validate(['things' => [1, 2, 3]]));
        $this->assertEmpty($validator->validate(['things' => [1]]));
        $this->assertNotEmpty($validator->validate(['things' => [1, 2, 3, 4]]));

        $this->assertEmpty($validator->validate(['things' => ['_ids' => [1, 2, 3]]]));
        $this->assertEmpty($validator->validate(['things' => ['_ids' => [1, 2]]]));
        $this->assertNotEmpty($validator->validate(['things' => ['_ids' => [1, 2, 3, 4]]]));
    }

    /**
     * Tests the regex proxy method
     */
    public function testRegex(): void
    {
        $validator = new Validator();
        $this->assertProxyMethod($validator, 'regex', '/(?<!\\S)\\d++(?!\\S)/', ['/(?<!\\S)\\d++(?!\\S)/'], 'custom');
        $this->assertEmpty($validator->validate(['username' => '123']));
        $this->assertNotEmpty($validator->validate(['username' => 'Foo']));
    }

    /**
     * Tests that a rule in the Validator class exists and was configured as expected.
     *
     * @param Validator $validator
     * @param string $method
     * @param mixed $extra
     * @param array $pass
     * @param string|null $name
     */
    protected function assertProxyMethod($validator, $method, $extra = null, $pass = [], $name = null): void
    {
        $name = $name ?: $method;
        if ($extra !== null) {
            $this->assertSame($validator, $validator->{$method}('username', $extra));
        } else {
            $this->assertSame($validator, $validator->{$method}('username'));
        }

        $rule = $validator->field('username')->rule($method);
        $this->assertNotEmpty($rule, "Rule was not found for $method");
        $this->assertNull($rule->get('message'), 'Message is present when it should not be');
        $this->assertNull($rule->get('on'), 'On clause is present when it should not be');
        $this->assertSame($name, $rule->get('rule'), 'Rule name does not match');
        $this->assertEquals($pass, $rule->get('pass'), 'Passed options are different');
        $this->assertSame('default', $rule->get('provider'), 'Provider does not match');

        if ($extra !== null) {
            $validator->{$method}('username', $extra, 'the message', 'create');
        } else {
            $validator->{$method}('username', 'the message', 'create');
        }

        $rule = $validator->field('username')->rule($method);
        $this->assertSame('the message', $rule->get('message'), 'Error messages are not the same');
        $this->assertSame('create', $rule->get('on'), 'On clause is wrong');
    }

    /**
     * Testing adding DefaultProvider
     */
    public function testAddingDefaultProvider(): void
    {
        $validator = new Validator();
        $this->assertEmpty($validator->providers(), 'Providers should be empty');

        Validator::addDefaultProvider('test-provider', 'MyNameSpace\Validation\MyProvider');
        $validator = new Validator();
        $this->assertEquals($validator->providers(), ['test-provider'], 'Default provider `test-provider` is missing');
    }

    /**
     * Testing getting DefaultProvider(s)
     */
    public function testGetDefaultProvider(): void
    {
        Validator::addDefaultProvider('test-provider', 'MyNameSpace\Validation\MyProvider');
        $this->assertEquals(Validator::getDefaultProvider('test-provider'), 'MyNameSpace\Validation\MyProvider', 'Default provider `test-provider` is missing');

        $this->assertNull(Validator::getDefaultProvider('invalid-provider'), 'Default provider (`invalid-provider`) should be missing');

        Validator::addDefaultProvider('test-provider2', 'MyNameSpace\Validation\MySecondProvider');
        $this->assertEquals(Validator::getDefaultProviders(), ['test-provider', 'test-provider2'], 'Default providers incorrect');
    }
}
