<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Event\EventInterface;
use Cake\ORM\Table;

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
     */
    protected ?string $modelClass = 'Comments';

    /**
     * beforeFilter handler
     *
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(EventInterface $event)
    {
    }

    /**
     * index method
     */
    public function index(mixed $testId, mixed $testTwoId): void
    {
        $this->request = $this->request->withParsedBody([
            'testId' => $testId,
            'test2Id' => $testTwoId,
        ]);
    }

    /**
     * view method
     */
    public function view(mixed $testId, mixed $testTwoId): void
    {
        $this->request = $this->request->withParsedBody([
            'testId' => $testId,
            'test2Id' => $testTwoId,
        ]);
    }

    public function reflection(mixed $passed, Table $table)
    {
    }

    public function returner(): \Cake\Http\Response
    {
        return $this->response->withStringBody('I am from the controller.');
    }

    /**
     * @return \Cake\Http\Response
     */
    public function willCauseException(): string
    {
        return '';
    }

    // phpcs:disable
    protected function protected_m()
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
