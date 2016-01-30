<?php

namespace TestApp\Model\Entity;

use Cake\Datasource\EntityInterface;
use Cake\Datasource\EntityTrait;

/**
 * Tests entity class used for asserting correct loading
 *
 */
class NonExtending implements EntityInterface
{

    use EntityTrait;

    public function __construct(array $properties = [], array $options = [])
    {
        $options += [
            'useSetters' => true,
            'markClean' => false,
            'markNew' => null,
            'guard' => false,
            'source' => null
        ];

        if (!empty($properties)) {
            $this->set($properties, [
                'setter' => $options['useSetters'],
                'guard' => $options['guard']
            ]);
        }

        if ($options['markClean']) {
            $this->clean();
        }

        if ($options['markNew'] !== null) {
            $this->isNew($options['markNew']);
        }

        if (!empty($options['source'])) {
            $this->source($options['source']);
        }
    }
}
