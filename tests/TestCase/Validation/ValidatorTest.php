<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Validation;

use Cake\TestSuite\TestCase;
use Cake\Validation\ValidationSet;
use Cake\Validation\Validator;

/**
 * Tests Validator class
 *
 */
class ValidatorTest extends TestCase
{

    /**
     * Testing you can dynamically add rules to a field
     *
     * @return void
     */
    public function testAddingRulesToField()
    {
        $validator = new Validator;
        $validator->add('title', 'not-blank', ['rule' => 'notBlank']);
        $set = $validator->field('title');
        $this->assertInstanceOf('Cake\Validation\ValidationSet', $set);
        $this->assertCount(1, $set);

        $validator->add('title', 'another', ['rule' => 'alphanumeric']);
        $this->assertCount(2, $set);

        $validator->add('body', 'another', ['rule' => 'crazy']);
        $this->assertCount(1, $validator->field('body'));
        $this->assertCount(2, $validator);
    }

    /**
     * Testing addNested field rules
     *
     * @return void
     */
    public function testAddNestedSingle()
    {
        $validator = new Validator();
        $inner = new Validator();
        $inner->add('username', 'not-blank', ['rule' => 'notBlank']);
        $this->assertSame($validator, $validator->addNested('user', $inner));

        $this->assertCount(1, $validator->field('user'));
    }

    /**
     * Testing addNested connects providers
     *
     * @return void
     */
    public function testAddNestedSingleProviders()
    {
        $validator = new Validator();
        $validator->provider('test', $this);

        $inner = new Validator();
        $inner->add('username', 'not-blank', ['rule' => function () use ($inner, $validator) {
            $this->assertSame($validator->providers(), $inner->providers(), 'Providers should match');
            return false;
        }]);
        $validator->addNested('user', $inner);

        $result = $validator->errors(['user' => ['username' => 'example']]);
        $this->assertNotEmpty($result, 'Validation should fail');
    }

    /**
     * Testing addNestedMany field rules
     *
     * @return void
     */
    public function testAddNestedMany()
    {
        $validator = new Validator();
        $inner = new Validator();
        $inner->add('comment', 'not-blank', ['rule' => 'notBlank']);
        $this->assertSame($validator, $validator->addNestedMany('comments', $inner));

        $this->assertCount(1, $validator->field('comments'));
    }

    /**
     * Testing addNestedMany connects providers
     *
     * @return void
     */
    public function testAddNestedManyProviders()
    {
        $validator = new Validator();
        $validator->provider('test', $this);

        $inner = new Validator();
        $inner->add('comment', 'not-blank', ['rule' => function () use ($inner, $validator) {
            $this->assertSame($validator->providers(), $inner->providers(), 'Providers should match');
            return false;
        }]);
        $validator->addNestedMany('comments', $inner);

        $result = $validator->errors(['comments' => [['comment' => 'example']]]);
        $this->assertNotEmpty($result, 'Validation should fail');
    }

    /**
     * Tests that calling field will create a default validation set for it
     *
     * @return void
     */
    public function testFieldDefault()
    {
        $validator = new Validator;
        $this->assertFalse($validator->hasField('foo'));

        $field = $validator->field('foo');
        $this->assertInstanceOf('Cake\Validation\ValidationSet', $field);
        $this->assertCount(0, $field);
        $this->assertTrue($validator->hasField('foo'));
    }

    /**
     * Tests that field method can be used as a setter
     *
     * @return void
     */
    public function testFieldSetter()
    {
        $validator = new Validator;
        $validationSet = new ValidationSet;
        $validator->field('thing', $validationSet);
        $this->assertSame($validationSet, $validator->field('thing'));
    }

    /**
     * Tests the remove method
     *
     * @return void
     */
    public function testRemove()
    {
        $validator = new Validator;
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
     *
     * @return void
     */
    public function testRequirePresence()
    {
        $validator = new Validator;
        $this->assertSame($validator, $validator->requirePresence('title'));
        $this->assertTrue($validator->field('title')->isPresenceRequired());

        $validator->requirePresence('title', false);
        $this->assertFalse($validator->field('title')->isPresenceRequired());

        $validator->requirePresence('title', 'create');
        $this->assertEquals('create', $validator->field('title')->isPresenceRequired());

        $validator->requirePresence('title', 'update');
        $this->assertEquals('update', $validator->field('title')->isPresenceRequired());
    }

    /**
     * Tests the requirePresence method when passing a callback
     *
     * @return void
     */
    public function testRequirePresenceCallback()
    {
        $validator = new Validator;
        $require = true;
        $validator->requirePresence('title', function ($context) use (&$require) {
            $this->assertEquals([], $context['data']);
            $this->assertEquals([], $context['providers']);
            $this->assertEquals('title', $context['field']);
            $this->assertTrue($context['newRecord']);
            return $require;
        });
        $this->assertTrue($validator->isPresenceRequired('title', true));

        $require = false;
        $this->assertFalse($validator->isPresenceRequired('title', true));
    }

    /**
     * Tests the isPresenceRequired method
     *
     * @return void
     */
    public function testIsPresenceRequired()
    {
        $validator = new Validator;
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
     *
     * @return void
     */
    public function testErrorsWithPresenceRequired()
    {
        $validator = new Validator;
        $validator->requirePresence('title');
        $errors = $validator->errors(['foo' => 'something']);
        $expected = ['title' => ['_required' => 'This field is required']];
        $this->assertEquals($expected, $errors);

        $this->assertEmpty($validator->errors(['title' => 'bar']));

        $validator->requirePresence('title', false);
        $this->assertEmpty($validator->errors(['foo' => 'bar']));
    }

    /**
     * Test that errors() can work with nested data.
     *
     * @return void
     */
    public function testErrorsWithNestedFields()
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
                'username' => 'is wrong'
            ],
            'comments' => [
                ['comment' => 'is wrong']
            ]
        ];
        $errors = $validator->errors($data);
        $expected = [
            'user' => [
                'username' => ['letter' => 'The provided value is invalid']
            ],
            'comments' => [
                0 => ['comment' => ['letter' => 'The provided value is invalid']]
            ]
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Test nested fields with many, but invalid data.
     *
     * @return void
     */
    public function testErrorsWithNestedSingleInvalidType()
    {
        $validator = new Validator();

        $user = new Validator();
        $user->add('user', 'letter', ['rule' => 'alphanumeric']);
        $validator->addNested('user', $user);

        $data = [
            'user' => 'a string',
        ];
        $errors = $validator->errors($data);
        $expected = [
            'user' => ['_nested' => 'The provided value is invalid'],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Test nested fields with many, but invalid data.
     *
     * @return void
     */
    public function testErrorsWithNestedManyInvalidType()
    {
        $validator = new Validator();

        $comments = new Validator();
        $comments->add('comment', 'letter', ['rule' => 'alphanumeric']);
        $validator->addNestedMany('comments', $comments);

        $data = [
            'comments' => 'a string',
        ];
        $errors = $validator->errors($data);
        $expected = [
            'comments' => ['_nested' => 'The provided value is invalid'],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Test nested fields with many, but invalid data.
     *
     * @return void
     */
    public function testErrorsWithNestedManySomeInvalid()
    {
        $validator = new Validator();

        $comments = new Validator();
        $comments->add('comment', 'letter', ['rule' => 'alphanumeric']);
        $validator->addNestedMany('comments', $comments);

        $data = [
            'comments' => [
                'a string',
                ['comment' => 'letters'],
                ['comment' => 'more invalid']
            ]
        ];
        $errors = $validator->errors($data);
        $expected = [
            'comments' => [
                '_nested' => 'The provided value is invalid',
            ],
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests custom error messages generated when a field presence is required
     *
     * @return void
     */
    public function testCustomErrorsWithPresenceRequired()
    {
        $validator = new Validator;
        $validator->requirePresence('title', true, 'Custom message');
        $errors = $validator->errors(['foo' => 'something']);
        $expected = ['title' => ['_required' => 'Custom message']];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests the allowEmpty method
     *
     * @return void
     */
    public function testAllowEmpty()
    {
        $validator = new Validator;
        $this->assertSame($validator, $validator->allowEmpty('title'));
        $this->assertTrue($validator->field('title')->isEmptyAllowed());

        $validator->allowEmpty('title', 'create');
        $this->assertEquals('create', $validator->field('title')->isEmptyAllowed());

        $validator->allowEmpty('title', 'update');
        $this->assertEquals('update', $validator->field('title')->isEmptyAllowed());
    }

    /**
     * Tests the allowEmpty method with date/time fields.
     *
     * @return void
     */
    public function testAllowEmptyDateTime()
    {
        $validator = new Validator;
        $validator->allowEmpty('created')
            ->add('created', 'date', ['rule' => 'date']);

        $data = [
            'created' => [
                'year' => '',
                'month' => '',
                'day' => ''
            ]
        ];
        $result = $validator->errors($data);
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
            ]
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result, 'No errors on empty datetime');

        $data = [
            'created' => [
                'hour' => '',
                'minute' => '',
                'meridian' => '',
            ]
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result, 'No errors on empty time');
    }

    /**
     * Tests the allowEmpty method with file fields.
     *
     * @return void
     */
    public function testAllowEmptyFileFields()
    {
        $validator = new Validator;
        $validator->allowEmpty('picture')
            ->add('picture', 'file', ['rule' => 'uploadedFile']);

        $data = [
            'picture' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
            ]
        ];
        $result = $validator->errors($data);
        $this->assertEmpty($result, 'No errors on empty date');

        $data = [
            'picture' => [
                'name' => 'fake.png',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_OK,
            ]
        ];
        $result = $validator->errors($data);
        $this->assertNotEmpty($result, 'Invalid file should be caught still.');
    }

    /**
     * Test the notEmpty() method.
     *
     * @return void
     */
    public function testNotEmpty()
    {
        $validator = new Validator;
        $validator->notEmpty('title');
        $this->assertFalse($validator->field('title')->isEmptyAllowed());

        $validator->allowEmpty('title');
        $this->assertTrue($validator->field('title')->isEmptyAllowed());
    }

    /**
     * Test the notEmpty() method.
     *
     * @return void
     */
    public function testNotEmptyModes()
    {
        $validator = new Validator;
        $validator->notEmpty('title', 'Need a title', 'create');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertTrue($validator->isEmptyAllowed('title', false));

        $validator->notEmpty('title', 'Need a title', 'update');
        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->notEmpty('title', 'Need a title');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->notEmpty('title');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));
    }

    /**
     * Test interactions between notEmpty() and isAllowed().
     *
     * @return void
     */
    public function testNotEmptyAndIsAllowed()
    {
        $validator = new Validator;
        $validator->allowEmpty('title')
            ->notEmpty('title', 'Need it', 'update');
        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->allowEmpty('title')
            ->notEmpty('title');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->notEmpty('title')
            ->allowEmpty('title', 'create');
        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));
    }

    /**
     * Tests the allowEmpty method when passing a callback
     *
     * @return void
     */
    public function testAllowEmptyCallback()
    {
        $validator = new Validator;
        $allow = true;
        $validator->allowEmpty('title', function ($context) use (&$allow) {
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
     *
     * @return void
     */
    public function testNotEmptyCallback()
    {
        $validator = new Validator;
        $prevent = true;
        $validator->notEmpty('title', 'error message', function ($context) use (&$prevent) {
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
     *
     * @return void
     */
    public function testIsEmptyAllowed()
    {
        $validator = new Validator;
        $this->assertSame($validator, $validator->allowEmpty('title'));
        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertTrue($validator->isEmptyAllowed('title', false));

        $validator->notEmpty('title');
        $this->assertFalse($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->allowEmpty('title', 'create');
        $this->assertTrue($validator->isEmptyAllowed('title', true));
        $this->assertFalse($validator->isEmptyAllowed('title', false));

        $validator->allowEmpty('title', 'update');
        $this->assertTrue($validator->isEmptyAllowed('title', false));
        $this->assertFalse($validator->isEmptyAllowed('title', true));
    }

    /**
     * Tests errors generated when a field is not allowed to be empty
     *
     * @return void
     */
    public function testErrorsWithEmptyNotAllowed()
    {
        $validator = new Validator;
        $validator->notEmpty('title');
        $errors = $validator->errors(['title' => '']);
        $expected = ['title' => ['_empty' => 'This field cannot be left empty']];
        $this->assertEquals($expected, $errors);

        $errors = $validator->errors(['title' => []]);
        $expected = ['title' => ['_empty' => 'This field cannot be left empty']];
        $this->assertEquals($expected, $errors);

        $errors = $validator->errors(['title' => null]);
        $expected = ['title' => ['_empty' => 'This field cannot be left empty']];
        $this->assertEquals($expected, $errors);

        $errors = $validator->errors(['title' => 0]);
        $this->assertEmpty($errors);

        $errors = $validator->errors(['title' => '0']);
        $this->assertEmpty($errors);

        $errors = $validator->errors(['title' => false]);
        $this->assertEmpty($errors);
    }

    /**
     * Tests custom error mesages generated when a field is not allowed to be empty
     *
     * @return void
     */
    public function testCustomErrorsWithEmptyNotAllowed()
    {
        $validator = new Validator;
        $validator->notEmpty('title', 'Custom message');
        $errors = $validator->errors(['title' => '']);
        $expected = ['title' => ['_empty' => 'Custom message']];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests errors generated when a field is allowed to be empty
     *
     * @return void
     */
    public function testErrorsWithEmptyAllowed()
    {
        $validator = new Validator;
        $validator->allowEmpty('title');
        $errors = $validator->errors(['title' => '']);
        $this->assertEmpty($errors);

        $errors = $validator->errors(['title' => []]);
        $this->assertEmpty($errors);

        $errors = $validator->errors(['title' => null]);
        $this->assertEmpty($errors);

        $errors = $validator->errors(['title' => 0]);
        $this->assertEmpty($errors);

        $errors = $validator->errors(['title' => 0.0]);
        $this->assertEmpty($errors);

        $errors = $validator->errors(['title' => '0']);
        $this->assertEmpty($errors);

        $errors = $validator->errors(['title' => false]);
        $this->assertEmpty($errors);
    }

    /**
     * Test the provider() method
     *
     * @return void
     */
    public function testProvider()
    {
        $validator = new Validator;
        $object = new \stdClass;
        $this->assertSame($validator, $validator->provider('foo', $object));
        $this->assertSame($object, $validator->provider('foo'));
        $this->assertNull($validator->provider('bar'));

        $another = new \stdClass;
        $this->assertSame($validator, $validator->provider('bar', $another));
        $this->assertSame($another, $validator->provider('bar'));

        $this->assertEquals(new \Cake\Validation\RulesProvider, $validator->provider('default'));
    }

    /**
     * Tests errors() method when using validators from the default provider, this proves
     * that it returns a default validation message and the custom one set in the rule
     *
     * @return void
     */
    public function testErrorsFromDefaultProvider()
    {
        $validator = new Validator;
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('email', 'notBlank', ['rule' => 'notBlank'])
            ->add('email', 'email', ['rule' => 'email', 'message' => 'Y u no write email?']);
        $errors = $validator->errors(['email' => 'not an email!']);
        $expected = [
            'email' => [
                'alpha' => 'The provided value is invalid',
                'email' => 'Y u no write email?'
            ]
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests using validation methods from different providers and returning the error
     * as a string
     *
     * @return void
     */
    public function testErrorsFromCustomProvider()
    {
        $validator = new Validator;
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);

        $thing = $this->getMock('\stdClass', ['isCool']);
        $thing->expects($this->once())->method('isCool')
            ->will($this->returnCallback(function ($data, $context) use ($thing) {
                $this->assertEquals('bar', $data);
                $expected = [
                    'default' => new \Cake\Validation\RulesProvider,
                    'thing' => $thing
                ];
                $expected = [
                    'newRecord' => true,
                    'providers' => $expected,
                    'data' => [
                        'email' => '!',
                        'title' => 'bar'
                    ],
                    'field' => 'title'
                ];
                $this->assertEquals($expected, $context);
                return "That ain't cool, yo";
            }));

        $validator->provider('thing', $thing);
        $errors = $validator->errors(['email' => '!', 'title' => 'bar']);
        $expected = [
            'email' => ['alpha' => 'The provided value is invalid'],
            'title' => ['cool' => "That ain't cool, yo"]
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests that it is possible to pass extra arguments to the validation function
     * and it still gets the providers as last argument
     *
     * @return void
     */
    public function testMethodsWithExtraArguments()
    {
        $validator = new Validator;
        $validator->add('title', 'cool', [
            'rule' => ['isCool', 'and', 'awesome'],
            'provider' => 'thing'
        ]);
        $thing = $this->getMock('\stdClass', ['isCool']);
        $thing->expects($this->once())->method('isCool')
            ->will($this->returnCallback(function ($data, $a, $b, $context) use ($thing) {
                $this->assertEquals('bar', $data);
                $this->assertEquals('and', $a);
                $this->assertEquals('awesome', $b);
                $expected = [
                    'default' => new \Cake\Validation\RulesProvider,
                    'thing' => $thing
                ];
                $expected = [
                    'newRecord' => true,
                    'providers' => $expected,
                    'data' => [
                        'email' => '!',
                        'title' => 'bar'
                    ],
                    'field' => 'title'
                ];
                $this->assertEquals($expected, $context);
                return "That ain't cool, yo";
            }));
        $validator->provider('thing', $thing);
        $errors = $validator->errors(['email' => '!', 'title' => 'bar']);
        $expected = [
            'title' => ['cool' => "That ain't cool, yo"]
        ];
        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests that it is possible to use a closure as a rule
     *
     * @return void
     */
    public function testUsingClosureAsRule()
    {
        $validator = new Validator;
        $validator->add('name', 'myRule', [
            'rule' => function ($data, $provider) {
                $this->assertEquals('foo', $data);
                return 'You fail';
            }
        ]);
        $expected = ['name' => ['myRule' => 'You fail']];
        $this->assertEquals($expected, $validator->errors(['name' => 'foo']));
    }

    /**
     * Tests that setting last to a rule will stop validating the rest of the rules
     *
     * @return void
     */
    public function testErrorsWithLastRule()
    {
        $validator = new Validator;
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric', 'last' => true])
            ->add('email', 'email', ['rule' => 'email', 'message' => 'Y u no write email?']);
        $errors = $validator->errors(['email' => 'not an email!']);
        $expected = [
            'email' => [
                'alpha' => 'The provided value is invalid'
            ]
        ];

        $this->assertEquals($expected, $errors);
    }

    /**
     * Tests it is possible to get validation sets for a field using an array interface
     *
     * @return void
     */
    public function testArrayAccessGet()
    {
        $validator = new Validator;
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
        $this->assertSame($validator['email'], $validator->field('email'));
        $this->assertSame($validator['title'], $validator->field('title'));
    }

    /**
     * Tests it is possible to check for validation sets for a field using an array inteface
     *
     * @return void
     */
    public function testArrayAccessExists()
    {
        $validator = new Validator;
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
        $this->assertTrue(isset($validator['email']));
        $this->assertTrue(isset($validator['title']));
        $this->assertFalse(isset($validator['foo']));
    }

    /**
     * Tests it is possible to set validation rules for a field using an array inteface
     *
     * @return void
     */
    public function testArrayAccessSet()
    {
        $validator = new Validator;
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
     *
     * @return void
     */
    public function testArrayAccessUset()
    {
        $validator = new Validator;
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
        $this->assertTrue(isset($validator['title']));
        unset($validator['title']);
        $this->assertFalse(isset($validator['title']));
    }

    /**
     * Tests the countable interface
     *
     * @return void
     */
    public function testCount()
    {
        $validator = new Validator;
        $validator
            ->add('email', 'alpha', ['rule' => 'alphanumeric'])
            ->add('title', 'cool', ['rule' => 'isCool', 'provider' => 'thing']);
        $this->assertCount(2, $validator);
    }

    /**
     * Tests adding rules via alternative syntax
     *
     * @return void
     */
    public function testAddMulitple()
    {
        $validator = new Validator;
        $validator->add('title', [
            'notBlank' => [
                'rule' => 'notBlank'
            ],
            'length' => [
                'rule' => ['minLength', 10],
                'message' => 'Titles need to be at least 10 characters long'
            ]
        ]);
        $set = $validator->field('title');
        $this->assertInstanceOf('Cake\Validation\ValidationSet', $set);
        $this->assertCount(2, $set);
    }

    /**
     * Integration test for compareWith validator.
     *
     * @return void
     */
    public function testCompareWithIntegration()
    {
        $validator = new Validator;
        $validator->add('password', [
            'compare' => [
                'rule' => ['compareWith', 'password_compare']
            ],
        ]);
        $data = [
            'password' => 'test',
            'password_compare' => 'not the same'
        ];
        $this->assertNotEmpty($validator->errors($data), 'Validation should fail.');
    }

    /**
     * Test debugInfo helper method.
     *
     * @return void
     */
    public function testDebugInfo()
    {
        $validator = new Validator();
        $validator->provider('test', $this);
        $validator->add('title', 'not-empty', ['rule' => 'notEmpty']);
        $validator->requirePresence('body');
        $validator->allowEmpty('published');

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
            '_useI18n' => true,
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests that the 'create' and 'update' modes are preserved when using
     * nested validators
     *
     * @return void
     */
    public function testNestedValidatorCreate()
    {
        $validator = new Validator();
        $inner = new Validator();
        $inner->add('username', 'email', ['rule' => 'email', 'on' => 'create']);
        $validator->addNested('user', $inner);
        $this->assertNotEmpty($validator->errors(['user' => ['username' => 'example']], true));
        $this->assertEmpty($validator->errors(['user' => ['username' => 'a']], false));
    }

    /**
     * Tests that the 'create' and 'update' modes are preserved when using
     * nested validators
     *
     * @return void
     */
    public function testNestedManyValidatorCreate()
    {
        $validator = new Validator();
        $inner = new Validator();
        $inner->add('username', 'email', ['rule' => 'email', 'on' => 'create']);
        $validator->addNestedMany('user', $inner);
        $this->assertNotEmpty($validator->errors(['user' => [['username' => 'example']]], true));
        $this->assertEmpty($validator->errors(['user' => [['username' => 'a']]], false));
    }
}
