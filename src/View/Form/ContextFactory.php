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
 * @since         3.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Form;

use Cake\Collection\Collection;
use Cake\Datasource\EntityInterface;
use Cake\Form\Form;
use Cake\Http\ServerRequest;
use RuntimeException;

/**
 * Factory for getting form context instance based on provided data.
 */
class ContextFactory
{
    /**
     * Context providers.
     *
     * @var array
     */
    protected $providers = [];

    /**
     * Constructor.
     *
     * @param array $providers Array of provider callables. Each element should
     *   be of form `['type' => 'a-string', 'callable' => ..]`
     */
    public function __construct(array $providers = [])
    {
        foreach ($providers as $provider) {
            $this->addProvider($provider['type'], $provider['callable']);
        }
    }

    /**
     * Create factory instance with providers "array", "form" and "orm".
     *
     * @param array $providers Array of provider callables. Each element should
     *   be of form `['type' => 'a-string', 'callable' => ..]`
     * @return static
     */
    public static function createWithDefaults(array $providers = [])
    {
        $providers = [
            [
                'type' => 'orm',
                'callable' => function ($request, $data) {
                    if ($data['entity'] instanceof EntityInterface) {
                        return new EntityContext($request, $data);
                    }
                    if (isset($data['table'])) {
                        return new EntityContext($request, $data);
                    }
                    if (is_iterable($data['entity'])) {
                        $pass = (new Collection($data['entity']))->first() !== null;
                        if ($pass) {
                            return new EntityContext($request, $data);
                        } else {
                            return new NullContext($request, $data);
                        }
                    }
                },
            ],
            [
                'type' => 'form',
                'callable' => function ($request, $data) {
                    if ($data['entity'] instanceof Form) {
                        return new FormContext($request, $data);
                    }
                },
            ],
            [
                'type' => 'array',
                'callable' => function ($request, $data) {
                    if (is_array($data['entity']) && isset($data['entity']['schema'])) {
                        return new ArrayContext($request, $data['entity']);
                    }
                },
            ],
            [
                'type' => 'null',
                'callable' => function ($request, $data) {
                    if ($data['entity'] === null) {
                        return new NullContext($request, $data);
                    }
                },
            ],
        ] + $providers;

        return new static($providers);
    }

    /**
     * Add a new context type.
     *
     * Form context types allow FormHelper to interact with
     * data providers that come from outside CakePHP. For example
     * if you wanted to use an alternative ORM like Doctrine you could
     * create and connect a new context class to allow FormHelper to
     * read metadata from doctrine.
     *
     * @param string $type The type of context. This key
     *   can be used to overwrite existing providers.
     * @param callable $check A callable that returns an object
     *   when the form context is the correct type.
     * @return $this
     */
    public function addProvider(string $type, callable $check)
    {
        $this->providers = [$type => ['type' => $type, 'callable' => $check]]
            + $this->providers;

        return $this;
    }

    /**
     * Find the matching context for the data.
     *
     * If no type can be matched a NullContext will be returned.
     *
     * @param \Cake\Http\ServerRequest $request Request instance.
     * @param array $data The data to get a context provider for.
     * @return \Cake\View\Form\ContextInterface Context provider.
     * @throws \RuntimeException When a context instace cannot be generated for given entity.
     */
    public function get(ServerRequest $request, array $data = []): ContextInterface
    {
        $data += ['entity' => null];

        foreach ($this->providers as $provider) {
            $check = $provider['callable'];
            $context = $check($request, $data);
            if ($context) {
                break;
            }
        }

        if (!isset($context)) {
            throw new RuntimeException(sprintf(
                'No context provider found for value of type `%s`.'
                . ' Use `null` as 1st argument of FormHelper::create() to create a context-less form.',
                getTypeName($data['entity'])
            ));
        }

        return $context;
    }
}
