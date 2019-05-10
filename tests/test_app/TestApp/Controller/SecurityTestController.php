<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Http\Response;
use TestApp\Controller\Component\TestSecurityComponent;

class SecurityTestController extends Controller
{
    /**
     * failed property
     *
     * @var bool
     */
    public $failed = false;

    /**
     * Used for keeping track of headers in test
     *
     * @var array
     */
    public $testHeaders = [];

    public function initialize(): void
    {
        $this->loadComponent('TestSecurity', ['className' => TestSecurityComponent::class]);
    }

    /**
     * fail method
     *
     * @return void
     */
    public function fail(): void
    {
        $this->failed = true;
    }

    /**
     * redirect method
     *
     * @param string|array $url
     * @param int $status
     * @return \Cake\Http\Response|null
     */
    public function redirect($url, ?int $status = null): ?Response
    {
        return $status;
    }

    /**
     * Convenience method for header()
     *
     * @param string $status
     * @return void
     */
    public function header(string $status): void
    {
        $this->testHeaders[] = $status;
    }
}
