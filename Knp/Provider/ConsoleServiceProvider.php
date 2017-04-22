<?php

namespace Knp\Provider;

use Knp\Command\Debug\DebugContainerCommand;
use Knp\Command\Debug\DebugEventCommand;
use Knp\Command\Debug\DebugRouterCommand;
use Knp\Console\Application as ConsoleApplication;
use Knp\Console\ConsoleEvent;
use Knp\Console\ConsoleEvents;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Symfony Console service provider for Silex.
 */
class ConsoleServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers the service provider.
     *
     * @param Container $app The Pimple container
     */
    public function register(Container $app)
    {
        $app['console.name'] = 'Silex console';
        $app['console.version'] = 'UNKNOWN';
        // Assume we are in vendor/knplabs/console-service-provider/Knp/Provider
        $app['console.project_directory'] = __DIR__.'/../../../../..';
        $app['console.class'] = ConsoleApplication::class;

        $app['console.command.debug.container'] = function () {
            return new DebugContainerCommand();
        };

        $app['console.command.debug.router'] = function () {
            return new DebugRouterCommand();
        };

        $app['console.command.debug.event_dispatcher'] = function () {
            return new DebugEventCommand();
        };

        $app['console.command.ids'] = ['console.command.debug.container', 'console.command.debug.router', 'console.command.debug.event_dispatcher'];

        $app['console'] = function () use ($app) {
            /** @var ConsoleApplication $console */
            $console = new $app['console.class'](
                $app,
                $app['console.project_directory'],
                $app['console.name'],
                $app['console.version']
            );
            $console->setDispatcher($app['dispatcher']);

            foreach ($app['console.command.ids'] as $id) {
                $console->add($app[$id]);
            }

            if ($app['dispatcher']->hasListeners(ConsoleEvents::INIT)) {
                @trigger_error('Listening to the Knp\Console\ConsoleEvents::INIT event is deprecated and will be removed in v3 of the service provider. You should extend the console service instead.', E_USER_DEPRECATED);

                $app['dispatcher']->dispatch(ConsoleEvents::INIT, new ConsoleEvent($console));
            }

            return $console;
        };
    }
}
