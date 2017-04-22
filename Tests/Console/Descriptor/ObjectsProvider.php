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

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ObjectsProvider
{
    public static function getRouteCollections()
    {
        $collection1 = new RouteCollection();
        foreach (self::getRoutes() as $name => $route) {
            $collection1->add($name, $route);
        }

        return array('route_collection_1' => $collection1);
    }

    public static function getRoutes()
    {
        return array(
            'route_1' => new Route(
                '/hello/{name}',
                array('name' => 'Joseph'),
                array('name' => '[a-z]+'),
                array('opt1' => 'val1', 'opt2' => 'val2'),
                'localhost',
                array('http', 'https'),
                array('get', 'head')
            ),
            'route_2' => new Route(
                '/name/add',
                array(),
                array(),
                array('opt1' => 'val1', 'opt2' => 'val2'),
                'localhost',
                array('http', 'https'),
                array('put', 'post')
            ),
        );
    }

    public static function getEventDispatchers()
    {
        $eventDispatcher = new EventDispatcher();

        $eventDispatcher->addListener('event1', 'global_function', 255);
        $eventDispatcher->addListener('event1', function () {
            return 'Closure';
        }, -1);
        $eventDispatcher->addListener('event2', new CallableClass());

        return array('event_dispatcher_1' => $eventDispatcher);
    }

    public static function getCallables()
    {
        return array(
            'callable_1' => 'array_key_exists',
            'callable_2' => array('Knp\\Tests\\Console\\Descriptor\\CallableClass', 'staticMethod'),
            'callable_3' => array(new CallableClass(), 'method'),
            'callable_4' => 'Knp\\Tests\\Console\\Descriptor\\CallableClass::staticMethod',
            'callable_5' => array('Knp\\Tests\\Console\\Descriptor\\ExtendedCallableClass', 'parent::staticMethod'),
            'callable_6' => function () {
                return 'Closure';
            },
            'callable_7' => new CallableClass(),
        );
    }
}

class CallableClass
{
    public function __invoke()
    {
    }

    public static function staticMethod()
    {
    }

    public function method()
    {
    }
}

class ExtendedCallableClass extends CallableClass
{
    public static function staticMethod()
    {
    }
}
