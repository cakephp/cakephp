<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\ORM\Query\SelectQuery;
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
     * @param mixed ...$args Arguments that match up to finder-specific parameters
     */
    public function find(string $type = 'all', mixed ...$args): SelectQuery
    {
        $options = &$args[0];
        if (empty($options['conditions'])) {
            $options['conditions'] = [];
        }
        $options['conditions'] = array_merge($options['conditions'], ['Comments.published' => 'Y']);

        return parent::find($type, ...$options);
    }
}
