<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Event\EventInterface;

/**
 * TestController class
 */
class TestController extends ControllerTestAppController
{
    /**
     * Theme property
     *
     * @var string
     */
    public $theme = 'Foo';

    /**
     * modelClass property
     *
     * @var string
     */
    public $modelClass = 'Comments';

    /**
     * beforeFilter handler
     *
     * @param \Cake\Event\EventInterface $event
     * @return \Cake\Http\Response|null
     */
    public function beforeFilter(EventInterface $event): ?Response
    {
    }

    /**
     * index method
     *
     * @param mixed $testId
     * @param mixed $testTwoId
     * @return void
     */
    public function index($testId, $testTwoId): void
    {
        $this->request = $this->request->withParsedBody([
            'testId' => $testId,
            'test2Id' => $testTwoId,
        ]);
    }

    /**
     * view method
     *
     * @param mixed $testId
     * @param mixed $testTwoId
     * @return void
     */
    public function view($testId, $testTwoId): void
    {
        $this->request = $this->request->withParsedBody([
            'testId' => $testId,
            'test2Id' => $testTwoId,
        ]);
    }

    public function returner()
    {
        return $this->response->withStringBody('I am from the controller.');
    }

    public function willCauseException()
    {
        return '';
    }

    // phpcs:disable
    protected function protected_m()
    {
    }

    private function private_m()
    {
    }

    public function _hidden()
    {
    }
    // phpcs:enable

    public function admin_add(): void
    {
    }
}
