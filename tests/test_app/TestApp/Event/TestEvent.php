<?php
namespace TestApp\Event;

use Cake\Event\EventInterface;

/**
 * TestEvent
 */
class TestEvent implements EventInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getSubject()
    {
    }

    /**
     * @inheritDoc
     */
    public function stopPropagation()
    {
    }

    /**
     * @inheritDoc
     */
    public function isStopped()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getResult()
    {
    }

    /**
     * @inheritDoc
     */
    public function setResult($value = null)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getData($key = null)
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function setData($key, $value = null)
    {
        return $this;
    }
}
