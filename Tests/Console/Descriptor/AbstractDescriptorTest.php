<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\Tests\Console\Descriptor;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

abstract class AbstractDescriptorTest extends TestCase
{
    /** @dataProvider getDescribeRouteCollectionTestData */
    public function testDescribeRouteCollection(RouteCollection $routes, $expectedDescription)
    {
        $this->assertDescription($expectedDescription, $routes);
    }

    public function getDescribeRouteCollectionTestData()
    {
        return $this->getDescriptionTestData(ObjectsProvider::getRouteCollections());
    }

    /** @dataProvider getDescribeRouteTestData */
    public function testDescribeRoute(Route $route, $expectedDescription)
    {
        $this->assertDescription($expectedDescription, $route);
    }

    public function getDescribeRouteTestData()
    {
        return $this->getDescriptionTestData(ObjectsProvider::getRoutes());
    }

    /** @dataProvider getDescribeEventDispatcherTestData */
    public function testDescribeEventDispatcher(EventDispatcher $eventDispatcher, $expectedDescription, array $options)
    {
        $this->assertDescription($expectedDescription, $eventDispatcher, $options);
    }

    public function getDescribeEventDispatcherTestData()
    {
        return $this->getEventDispatcherDescriptionTestData(ObjectsProvider::getEventDispatchers());
    }

    /** @dataProvider getDescribeCallableTestData */
    public function testDescribeCallable($callable, $expectedDescription)
    {
        $this->assertDescription($expectedDescription, $callable);
    }

    public function getDescribeCallableTestData()
    {
        return $this->getDescriptionTestData(ObjectsProvider::getCallables());
    }

    abstract protected function getDescriptor();

    abstract protected function getFormat();

    private function assertDescription($expectedDescription, $describedObject, array $options = array())
    {
        $options['raw_output'] = true;
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);

        if ('txt' === $this->getFormat()) {
            $options['output'] = new SymfonyStyle(new ArrayInput(array()), $output);
        }

        $this->getDescriptor()->describe($output, $describedObject, $options);

        if ('json' === $this->getFormat()) {
            $this->assertEquals(json_decode($expectedDescription), json_decode($output->fetch()));
        } else {
            $this->assertEquals(trim($expectedDescription), trim(str_replace(PHP_EOL, "\n", $output->fetch())));
        }
    }

    private function getDescriptionTestData(array $objects)
    {
        $data = array();
        foreach ($objects as $name => $object) {
            $description = file_get_contents(sprintf('%s/../../Fixtures/Descriptor/%s.%s', __DIR__, $name, $this->getFormat()));
            $data[] = array($object, $description);
        }

        return $data;
    }

    private function getEventDispatcherDescriptionTestData(array $objects)
    {
        $variations = array(
            'events' => array(),
            'event1' => array('event' => 'event1'),
        );

        $data = array();
        foreach ($objects as $name => $object) {
            foreach ($variations as $suffix => $options) {
                $description = file_get_contents(sprintf('%s/../../Fixtures/Descriptor/%s_%s.%s', __DIR__, $name, $suffix, $this->getFormat()));
                $data[] = array($object, $description, $options);
            }
        }

        return $data;
    }
}
