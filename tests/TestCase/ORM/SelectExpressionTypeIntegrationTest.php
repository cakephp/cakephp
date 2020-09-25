<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\ORM;

use Cake\Database\Driver\Mysql;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\TypeFactory;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use TestApp\Database\Point;
use TestApp\Database\Type\PointType;

class SelectExpressionTypeIntegrationTest extends TestCase
{
    protected $fixtures = [
        'core.Points',
    ];

    public $autoFixtures = false;

    /**
     * @var \Cake\Database\Connection
     */
    protected $connection;

    public function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();

        $this->connection = ConnectionManager::get('test');
        $this->skipIf(!($this->connection->getDriver() instanceof Mysql), 'MySQL only for now.');

        TypeFactory::map('point', PointType::class);

        $this->loadFixtures('Points');
    }

    public function testRead()
    {
        $table = $this->getTableLocator()->get('Points');

        $results = $table
            ->find()
            ->disableHydration()
            ->toArray();
        $this->assertEquals([['id' => 1, 'pt' => new Point(10, 20)]], $results);

        $results = $table
            ->find()
            ->select(['pt'])
            ->disableHydration()
            ->toArray();
        $this->assertEquals([['pt' => new Point(10, 20)]], $results);

        $results = $table
            ->find()
            ->select(['Points.pt'])
            ->disableHydration()
            ->toArray();
        $this->assertEquals([['pt' => new Point(10, 20)]], $results);

        $results = $table
            ->find()
            ->select(['Points__pt' => 'pt'])
            ->disableHydration()
            ->toArray();
        $this->assertEquals([['pt' => new Point(10, 20)]], $results);
    }

    public function testReadWithExpressions()
    {
        $table = $this->getTableLocator()->get('Points');

        $results = $table
            ->find()
            ->disableHydration()
            ->toArray();
        $this->assertEquals([['id' => 1, 'pt' => new Point(10, 20)]], $results);

        $results = $table
            ->find()
            ->select(['pt' => new IdentifierExpression('pt')])
            ->disableHydration()
            ->toArray();
        $this->assertEquals([['pt' => new Point(10, 20)]], $results);

        $results = $table
            ->find()
            ->select(['pt' => new IdentifierExpression('Points.pt')])
            ->disableHydration()
            ->toArray();
        $this->assertEquals([['pt' => new Point(10, 20)]], $results);

        $results = $table
            ->find()
            ->select(['Points__pt' => new IdentifierExpression('pt')])
            ->disableHydration()
            ->toArray();
        $this->assertEquals([['pt' => new Point(10, 20)]], $results);
    }

    public function testReadWithUnsupportedExpressions()
    {
        $table = $this->getTableLocator()->get('Points');

        $results = $table
            ->find()
            ->select(['pt' => new QueryExpression('pt')])
            ->disableHydration()
            ->toArray();
        $this->assertEquals([['pt' => null]], $results);
    }

    public function testReadWithNonExistingType()
    {
        $table = $this->getTableLocator()->get('Points');
        $table->getSchema()->setColumnType('pt', 'nonExistingType');

        $results = $table
            ->find()
            ->select(['pt' => new QueryExpression('pt')])
            ->disableHydration()
            ->toArray();
        $this->assertEquals([['pt' => hex2bin('00000000010100000000000000000024400000000000003440')]], $results);
    }

    public function testReadWithNoMappedTypes()
    {
        $table = $this->getTableLocator()->get('Points');
        $typeMap = new TypeMap();

        $results = $table
            ->find()
            ->select(['pt'])
            ->disableHydration()
            ->setSelectTypeMap($typeMap)
            ->setTypeMap($typeMap)
            ->toArray();
        $this->assertEquals([['pt' => hex2bin('00000000010100000000000000000024400000000000003440')]], $results);
    }
}
