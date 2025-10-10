<?php
declare(strict_types=1);

namespace TestApp\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use TestApp\Service\TestService;

/**
 * A test component that accepts an injected service dependency
 */
class InjectedServiceComponent extends Component
{
    /**
     * @var \TestApp\Service\TestService
     */
    protected TestService $service;

    /**
     * Constructor
     *
     * @param \Cake\Controller\ComponentRegistry $registry A ComponentRegistry
     * @param \TestApp\Service\TestService $service The injected service
     * @param array $config Array of configuration settings
     */
    public function __construct(ComponentRegistry $registry, TestService $service, array $config = [])
    {
        $this->service = $service;
        parent::__construct($registry, $config);
    }

    /**
     * Get the injected service
     *
     * @return \TestApp\Service\TestService
     */
    public function getService(): TestService
    {
        return $this->service;
    }
}
