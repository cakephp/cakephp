<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Table;

/**
 * Test stub for greedy find operations.
 */
class GreedyCommentsTable extends Table
{
    /**
     * initialize hook
     *
     * @param array $config Config data.
     */
    public function initialize(array $config): void
    {
        $this->setTable('comments');
        $this->setAlias('Comments');
    }

    /**
     * Overload find to cause issues.
     *
     * @param string $type Find type
     * @param array<string, mixed> $options find options
     */
    public function find(string $type = 'all', array $options = []): Query
    {
        if (empty($options['conditions'])) {
            $options['conditions'] = [];
        }
        $options['conditions'] = array_merge($options['conditions'], ['Comments.published' => 'Y']);

        return parent::find($type, $options);
    }
}
