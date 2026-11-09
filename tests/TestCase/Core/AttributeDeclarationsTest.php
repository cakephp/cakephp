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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Container\Container;
use Cake\Controller\Attribute\UseComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\ClassDeclaration;
use Cake\Core\Configure;
use Cake\Core\PluginConfig;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\ServerRequest;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Association\HasOne;
use Cake\ORM\Attribute\BelongsTo as BelongsToAttribute;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\HtmlHelper;
use Cake\View\View;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionClass;
use ReflectionFunction;
use stdClass;
use TestApp\Attribute\Declaration\ArticlesController;
use TestApp\Attribute\Declaration\ArticlesTable;
use TestApp\Attribute\Declaration\ArticlesView;
use TestApp\Attribute\Declaration\ChildNestedComponent;
use TestApp\Attribute\Declaration\ChildNestedHelper;
use TestApp\Attribute\Declaration\ChildTable;
use TestApp\Attribute\Declaration\DuplicateTable;
use TestApp\Attribute\Declaration\ExplicitController;
use TestApp\Attribute\Declaration\ExplicitTable;
use TestApp\Attribute\Declaration\ExplicitView;
use TestApp\Attribute\Declaration\InvalidController;
use TestApp\Attribute\Declaration\InvalidTable;
use TestApp\Attribute\Declaration\LinksTable;
use TestApp\Attribute\Declaration\NestedComponent;
use TestApp\Attribute\Declaration\NestedHelper;
use TestApp\Attribute\Declaration\UsersTable;
use TestApp\Controller\Component\AppleComponent;
use TestApp\View\Helper\TestBeforeAfterHelper;
use TestPlugin\Model\Table\AttributeDeclaredTable;
use TypeError;

/**
 * Verifies native declarations across the ORM, controller, and view lifecycles.
 */
class AttributeDeclarationsTest extends TestCase
{
    /**
     * Enables native declarations and configures the test application namespace.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();
        Configure::write('App.attributeDeclarations', true);
    }

    /**
     * Registers every association kind before behaviors and model initialization.
     *
     * @return void
     */
    public function testTableLifecycle(): void
    {
        $events = new EventManager();
        $initialized = false;
        $events->on('Model.initialize', function (Event $event) use (&$initialized): void {
            $table = $event->getSubject();
            $initialized = $table->hasAssociation('Users') && $table->hasBehavior('Observed');
        });
        $table = new ArticlesTable(['eventManager' => $events]);

        $this->assertFalse($table->associationDuringInitialize);
        $this->assertTrue($initialized);
        $this->assertInstanceOf(BelongsTo::class, $table->getAssociation('Users'));
        $this->assertInstanceOf(HasOne::class, $table->getAssociation('Profile'));
        $this->assertInstanceOf(HasMany::class, $table->getAssociation('Children'));
        $this->assertInstanceOf(BelongsToMany::class, $table->getAssociation('Tags'));
        $this->assertSame(['tenant_id', 'user_id'], $table->getAssociation('Users')->getForeignKey());
        $this->assertFalse($table->getAssociation('Children')->getForeignKey());
        $this->assertSame(['Children.active' => true], $table->getAssociation('Children')->getConditions());
        $this->assertSame('declaration_articles', $table->getBehavior('Observed')->getConfig('tableAtInitialization'));
        $this->assertTrue($table->getBehavior('Observed')->getConfig('associationAtInitialization'));
        $this->assertTrue($table->behaviors()->hasFinder('declared'));
    }

    /**
     * Preserves lazy targets and junctions instead of constructing related tables.
     *
     * @return void
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAssociationTargetsRemainLazy(): void
    {
        $this->assertFalse(class_exists(UsersTable::class, false));
        $this->assertFalse(class_exists(LinksTable::class, false));
        $locator = new TableLocator();
        $table = $locator->get('Articles', ['className' => ArticlesTable::class, 'connectionName' => 'test']);

        $this->assertFalse(class_exists(UsersTable::class, false));
        $this->assertFalse(class_exists(LinksTable::class, false));
        $this->assertFalse($locator->exists('Users'));
        $this->assertFalse($locator->exists('Tags'));
        $this->assertFalse($locator->exists('Links'));
        $table->getAssociation('Users')->setTableLocator($locator);
        $this->assertInstanceOf(UsersTable::class, $table->getAssociation('Users')->getTarget());
        $this->assertTrue($locator->exists('Users'));
    }

    /**
     * Replaces inherited options atomically without changing declaration order.
     *
     * @return void
     */
    public function testInheritance(): void
    {
        foreach ([new ChildTable(), new ChildTable()] as $table) {
            $this->assertSame(['Users', 'Reviewers', 'Editors'], $table->associations()->keys());
            $this->assertSame('child_user_id', $table->getAssociation('Users')->getForeignKey());
        }
    }

    /**
     * Retains explicit instances and configuration over declarative defaults.
     *
     * @return void
     */
    public function testExplicitConfigurationWins(): void
    {
        $table = new ExplicitTable();
        $this->assertSame('explicit_id', $table->getAssociation('Users')->getForeignKey());
        $this->assertSame('explicit', $table->getBehavior('Observed')->getConfig('flag'));
        $controller = new ExplicitController(new ServerRequest());
        $this->assertSame('explicit', $controller->components()->get('Observer')->getConfig('flag'));
        $view = new ExplicitView();
        $this->assertSame('explicit', $view->helpers()->get('Observer')->getConfig('flag'));
        $view = new ArticlesView(viewOptions: ['helpers' => [
            'Observer' => ['className' => TestBeforeAfterHelper::class, 'flag' => 'builder'],
        ]]);
        $this->assertSame('builder', $view->helpers()->get('Observer')->getConfig('flag'));
    }

    /**
     * Loads components early enough for normal startup callbacks and keeps instances isolated.
     *
     * @return void
     */
    public function testControllerLifecycle(): void
    {
        $controller = new ArticlesController(new ServerRequest());
        $other = new ArticlesController(new ServerRequest());
        $this->assertFalse($controller->componentDuringInitialize);
        $component = $controller->components()->get('Observer');
        $this->assertSame('declared', $component->getConfig('flag'));
        $this->assertNotSame($component, $other->components()->get('Observer'));
        $controller->dispatchEvent('Controller.startup');
        $listeners = $controller->getEventManager()->listeners('Controller.startup');
        $this->assertCount(1, $listeners);
        $this->assertSame($component, new ReflectionFunction($listeners[0]['callable'])->getClosureThis());
        $this->assertSame('OrangeField', $component->Orange->Banana->testField);
    }

    /**
     * Uses the existing component container resolution path.
     *
     * @return void
     */
    public function testComponentContainerResolution(): void
    {
        $container = new Container();
        $registry = new ComponentRegistry(container: $container);
        $instance = new AppleComponent($registry);
        $container->add(AppleComponent::class, $instance);
        $controller = new ArticlesController(new ServerRequest(), components: $registry);
        $this->assertSame($instance, $controller->components()->get('Observer'));
        $this->assertSame('declared', $instance->getConfig('flag'));
    }

    /**
     * Registers helper callbacks and preserves automatic loading of other helpers.
     *
     * @return void
     */
    public function testViewLifecycle(): void
    {
        $view = new ArticlesView();
        $helper = $view->helpers()->get('Observer');
        $view->dispatchEvent('View.beforeLayout', ['viewFile' => 'example.php']);
        $this->assertSame('Valuation', $helper->property);
        $this->assertInstanceOf(HtmlHelper::class, $view->Html);
    }

    /**
     * Loads nested components on demand and reuses the owning controller's registry.
     *
     * @return void
     */
    public function testNestedComponentLoading(): void
    {
        $controller = new Controller(new ServerRequest());
        $registry = $controller->components();
        $component = new NestedComponent($registry);
        $other = new NestedComponent($registry);
        $this->assertFalse($registry->has('Observer'));

        $dependency = $component->Observer;
        $this->assertInstanceOf(AppleComponent::class, $dependency);
        $this->assertSame('declared', $dependency->getConfig('flag'));
        $this->assertSame($controller, $dependency->getController());
        $this->assertSame($dependency, $other->Observer);
        $this->assertSame([], $controller->getEventManager()->listeners('Controller.startup'));
    }

    /**
     * Loads nested helpers on demand without subscribing their view callbacks.
     *
     * @return void
     */
    public function testNestedHelperLoading(): void
    {
        $view = new View();
        $helper = new NestedHelper($view);
        $other = new NestedHelper($view);
        $this->assertFalse($view->helpers()->has('Observer'));

        $dependency = $helper->Observer;
        $this->assertInstanceOf(TestBeforeAfterHelper::class, $dependency);
        $this->assertSame('declared', $dependency->getConfig('flag'));
        $this->assertSame($view, $dependency->getView());
        $this->assertSame($dependency, $other->Observer);
        $view->dispatchEvent('View.beforeLayout', ['viewFile' => 'example.php']);
        $this->assertSame('', $dependency->property);
    }

    /**
     * Keeps normalized component property definitions ahead of attribute defaults.
     *
     * @return void
     */
    public function testNestedComponentPropertyConfigurationWins(): void
    {
        $registry = new ComponentRegistry(new Controller(new ServerRequest()));
        $component = new class ($registry) extends NestedComponent {
            /**
             * Explicit configuration for the declared dependency alias.
             *
             * @var array<int|string, string|array<string, mixed>>
             */
            protected array $components = [
                'Observer' => ['className' => AppleComponent::class, 'flag' => 'property'],
            ];
        };

        $this->assertFalse($registry->has('Observer'));
        $this->assertSame('property', $component->Observer->getConfig('flag'));
    }

    /**
     * Keeps normalized helper property definitions ahead of attribute defaults.
     *
     * @return void
     */
    public function testNestedHelperPropertyConfigurationWins(): void
    {
        $view = new View();
        $helper = new class ($view) extends NestedHelper {
            /**
             * Explicit configuration for the declared dependency alias.
             *
             * @var array<int|string, string|array<string, mixed>>
             */
            protected array $helpers = [
                'Observer' => ['className' => TestBeforeAfterHelper::class, 'flag' => 'property'],
            ];
        };

        $this->assertFalse($view->helpers()->has('Observer'));
        $this->assertSame('property', $helper->Observer->getConfig('flag'));
    }

    /**
     * Allows component initialization to override and access a declared dependency.
     *
     * @return void
     */
    public function testNestedComponentDuringInitialization(): void
    {
        $registry = new ComponentRegistry(new Controller(new ServerRequest()));
        $component = new class ($registry) extends NestedComponent {
            /**
             * Reconfigures and uses the dependency during initialization.
             *
             * @param array<string, mixed> $config Component configuration.
             * @return void
             */
            public function initialize(array $config): void
            {
                $this->components['Observer'] = ['className' => AppleComponent::class, 'flag' => 'initialize'];
                $this->Observer->setConfig('usedDuringInitialize', true);
            }
        };

        $this->assertTrue($registry->has('Observer'));
        $this->assertSame('initialize', $component->Observer->getConfig('flag'));
        $this->assertTrue($component->Observer->getConfig('usedDuringInitialize'));
    }

    /**
     * Allows helper initialization to override and access a declared dependency.
     *
     * @return void
     */
    public function testNestedHelperDuringInitialization(): void
    {
        $view = new View();
        $helper = new class ($view) extends NestedHelper {
            /**
             * Reconfigures and uses the dependency during initialization.
             *
             * @param array<string, mixed> $config Helper configuration.
             * @return void
             */
            public function initialize(array $config): void
            {
                $this->helpers['Observer'] = ['className' => TestBeforeAfterHelper::class, 'flag' => 'initialize'];
                $this->Observer->setConfig('usedDuringInitialize', true);
            }
        };

        $this->assertTrue($view->helpers()->has('Observer'));
        $this->assertSame('initialize', $helper->Observer->getConfig('flag'));
        $this->assertTrue($helper->Observer->getConfig('usedDuringInitialize'));
    }

    /**
     * Allows child components to replace inherited dependency declarations.
     *
     * @return void
     */
    public function testNestedComponentInheritance(): void
    {
        $registry = new ComponentRegistry(new Controller(new ServerRequest()));
        $component = new ChildNestedComponent($registry);

        $this->assertFalse($registry->has('Observer'));
        $this->assertSame('child', $component->Observer->getConfig('flag'));
    }

    /**
     * Allows child helpers to replace inherited dependency declarations.
     *
     * @return void
     */
    public function testNestedHelperInheritance(): void
    {
        $view = new View();
        $helper = new ChildNestedHelper($view);

        $this->assertFalse($view->helpers()->has('Observer'));
        $this->assertSame('child', $helper->Observer->getConfig('flag'));
    }

    /**
     * Preserves the component loader's explicit callback opt-in for nested dependencies.
     *
     * @return void
     */
    public function testNestedComponentCallbacksCanBeEnabled(): void
    {
        $controller = new Controller(new ServerRequest());
        $component = new ChildNestedComponent($controller->components());
        $this->assertSame([], $controller->getEventManager()->listeners('Controller.startup'));

        $dependency = $component->Observer;
        $listeners = $controller->getEventManager()->listeners('Controller.startup');
        $this->assertCount(1, $listeners);
        $this->assertSame($dependency, new ReflectionFunction($listeners[0]['callable'])->getClosureThis());
    }

    /**
     * Preserves the helper loader's disabled callbacks even with an enabled option.
     *
     * @return void
     */
    public function testNestedHelperCallbacksRemainDisabled(): void
    {
        $view = new View();
        $helper = new ChildNestedHelper($view);
        $dependency = $helper->Observer;

        $this->assertFalse($dependency->getConfig('enabled'));
        $view->dispatchEvent('View.beforeLayout', ['viewFile' => 'example.php']);
        $this->assertSame('', $dependency->property);
    }

    /**
     * Honors the application opt-out even after declaration bindings have been cached.
     *
     * @return void
     */
    public function testDisabledAfterUse(): void
    {
        $this->assertTrue(new ArticlesTable()->hasAssociation('Users'));
        $controller = new ArticlesController(new ServerRequest());
        $view = new ArticlesView();
        new NestedComponent($controller->components());
        new NestedHelper($view);
        Configure::write('App.attributeDeclarations', false);

        $this->assertFalse(new ArticlesTable()->hasAssociation('Users'));
        $this->assertFalse(new ArticlesController(new ServerRequest())->components()->has('Observer'));
        $view = new ArticlesView();
        $this->assertFalse($view->helpers()->has('Observer'));
        $registry = new Controller(new ServerRequest())->components();
        $this->assertNull(new NestedComponent($registry)->Observer);
        $this->assertNull(new NestedHelper($view)->Observer);
    }

    /**
     * Defaults to disabled without loading declaration or resolver classes.
     *
     * @return void
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testDisabledByDefault(): void
    {
        Configure::delete('App.attributeDeclarations');
        $this->assertFalse(class_exists(AttributeResolver::class, false));
        $this->assertFalse(class_exists(ClassDeclaration::class, false));
        $this->assertFalse(class_exists(UseComponent::class, false));
        $this->assertFalse(new ArticlesTable()->hasAssociation('Users'));
        $controller = new ArticlesController(new ServerRequest());
        $this->assertFalse($controller->components()->has('Observer'));
        new NestedComponent($controller->components());
        new NestedHelper(new ArticlesView());
        $this->assertFalse(class_exists(ClassDeclaration::class, false));
        $this->assertFalse(class_exists(UseComponent::class, false));
        $this->assertFalse(class_exists(AttributeResolver::class, false));
    }

    /**
     * Allows individual owners to override either application-level setting.
     *
     * @return void
     */
    public function testOwnerConfiguration(): void
    {
        $disabled = new class extends ArticlesTable {
            /**
             * Disables declarations for this table.
             *
             * @var bool|null
             */
            protected ?bool $attributeDeclarationsEnabled = false;
        };
        $this->assertFalse($disabled->hasAssociation('Users'));
        Configure::write('App.attributeDeclarations', false);
        $enabled = new class extends ArticlesTable {
            /**
             * Enables declarations for this table independently of the application setting.
             *
             * @var bool|null
             */
            protected ?bool $attributeDeclarationsEnabled = true;
        };
        $this->assertTrue($enabled->hasAssociation('Users'));
    }

    /**
     * Reads native declarations without accessing resolver discovery or cache engines.
     *
     * @return void
     */
    public function testDeclarationsWithoutDiscovery(): void
    {
        AttributeResolver::drop('default');
        AttributeResolver::setConfig('default', ['paths' => [], 'cache' => 'declarations_must_not_read_metadata']);
        try {
            $this->assertTrue(new ArticlesTable()->hasAssociation('Users'));
            $this->assertTrue(new ArticlesController(new ServerRequest())->components()->has('Observer'));
            $view = new ArticlesView();
            $this->assertTrue($view->helpers()->has('Observer'));
            $registry = new Controller(new ServerRequest())->components();
            $this->assertInstanceOf(AppleComponent::class, new NestedComponent($registry)->Observer);
            $this->assertInstanceOf(TestBeforeAfterHelper::class, new NestedHelper($view)->Observer);
        } finally {
            AttributeResolver::drop('default');
        }
    }

    /**
     * Keeps object-valued declaration options private to each owner despite metadata caching.
     *
     * @return void
     */
    public function testFreshConfigurationObjects(): void
    {
        $first = new ArticlesController(new ServerRequest())->components()->get('Observer');
        $first->getConfig('state')->modified = true;
        $second = new ArticlesController(new ServerRequest())->components()->get('Observer');
        $this->assertIsObject($first->getConfig('state'));
        $this->assertNotSame($first->getConfig('state'), $second->getConfig('state'));
        $this->assertSame([], get_object_vars($second->getConfig('state')));
    }

    /**
     * Reads declarations on anonymous owners and caches their inherited bindings.
     *
     * @return void
     */
    public function testAnonymousOwnerDeclarations(): void
    {
        $factory = static fn() => new #[BelongsToAttribute(UsersTable::class, alias: 'Reviewers')] class extends ArticlesTable {
        };
        foreach ([$factory(), $factory()] as $table) {
            $this->assertSame(['Users', 'Profile', 'Children', 'Tags', 'Reviewers'], $table->associations()->keys());
            $this->assertSame(UsersTable::class, $table->getAssociation('Reviewers')->getClassName());
        }
    }

    /**
     * Creates fresh object options without imposing serialization requirements.
     *
     * @return void
     */
    public function testNonSerializableConfigurationObjects(): void
    {
        $factory = static fn() => new #[UseComponent(
            AppleComponent::class,
            alias: 'Observer',
            config: ['state' => new ReflectionClass(stdClass::class)],
        )] class (new ServerRequest()) extends Controller {
        };
        $first = $factory()->components()->get('Observer')->getConfig('state');
        $second = $factory()->components()->get('Observer')->getConfig('state');

        $this->assertInstanceOf(ReflectionClass::class, $first);
        $this->assertInstanceOf(ReflectionClass::class, $second);
        $this->assertSame(stdClass::class, $second->getName());
        $this->assertNotSame($first, $second);
    }

    /**
     * Reads plugin declarations directly from the loaded class hierarchy.
     *
     * @return void
     */
    public function testPluginDeclarations(): void
    {
        $this->loadPlugins(['TestPlugin']);
        try {
            $table = new AttributeDeclaredTable();
            $this->assertTrue($table->hasAssociation('Users'));
            $this->assertSame(UsersTable::class, $table->getAssociation('Users')->getClassName());
        } finally {
            $this->clearPlugins();
            PluginConfig::clearCache();
        }
    }

    /**
     * Caches empty native bindings without adding dependencies to ordinary owners.
     *
     * @return void
     */
    public function testWithoutDeclarations(): void
    {
        $this->assertSame([], new Table()->associations()->keys());
        $this->assertSame([], new Table()->associations()->keys());
    }

    /**
     * Detects duplicate aliases even when association types differ.
     *
     * @return void
     */
    public function testDuplicateAlias(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate association declaration for alias `Users`');
        new DuplicateTable();
    }

    /**
     * Defers incompatible association targets to the existing table loader.
     *
     * @return void
     */
    public function testInvalidTarget(): void
    {
        $table = new InvalidTable();
        $this->assertTrue($table->hasAssociation('Invalid'));
        $locator = new TableLocator();
        $locator->setConfig('Invalid', ['connectionName' => 'test']);
        $association = $table->getAssociation('Invalid')->setTableLocator($locator);

        $this->expectException(TypeError::class);
        $association->getTarget();
    }

    /**
     * Rejects declarations placed on an incompatible owner.
     *
     * @return void
     */
    public function testInvalidOwner(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires an owner of type');
        new InvalidController(new ServerRequest());
    }

    /**
     * Rejects conflicting class selection instead of silently ignoring config.
     *
     * @return void
     */
    public function testConflictingClassName(): void
    {
        $attribute = new UseComponent(AppleComponent::class, config: ['className' => 'Other']);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('className conflicts');
        $attribute->validate();
    }

    /**
     * Requires an explicit alias when stripping the suffix yields an empty name.
     *
     * @return void
     */
    public function testEmptyInferredAlias(): void
    {
        $attribute = new BelongsToAttribute(Table::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty alias');
        $attribute->validate();
    }
}
