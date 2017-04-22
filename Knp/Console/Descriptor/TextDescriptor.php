<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\Console\Descriptor;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Jean-François Simon <jeanfrancois.simon@sensiolabs.com>
 *
 * @internal
 */
class TextDescriptor extends AbstractDescriptor
{
    /**
     * {@inheritdoc}
     */
    protected function describeRouteCollection(RouteCollection $routes, array $options = array())
    {
        $showControllers = isset($options['show_controllers']) && $options['show_controllers'];

        $tableHeaders = array('Name', 'Method', 'Scheme', 'Host', 'Path');
        if ($showControllers) {
            $tableHeaders[] = 'Controller';
        }

        $tableRows = array();
        foreach ($routes->all() as $name => $route) {
            $row = array(
                $name,
                $route->getMethods() ? implode('|', $route->getMethods()) : 'ANY',
                $route->getSchemes() ? implode('|', $route->getSchemes()) : 'ANY',
                '' !== $route->getHost() ? $route->getHost() : 'ANY',
                $route->getPath(),
            );

            if ($showControllers) {
                $controller = $route->getDefault('_controller');
                if ($controller instanceof \Closure) {
                    $controller = $this->formatClosure($controller, isset($options['project_dir']) ? $options['project_dir'] : null);
                } elseif (is_object($controller)) {
                    $controller = get_class($controller);
                }
                $row[] = $controller;
            }

            $tableRows[] = $row;
        }

        if (isset($options['output'])) {
            $options['output']->table($tableHeaders, $tableRows);
        } else {
            $table = new Table($this->getOutput());
            $table->setHeaders($tableHeaders)->setRows($tableRows);
            $table->render();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function describeRoute(Route $route, array $options = array())
    {
        $projectDir = isset($options['project_dir']) ? $options['project_dir'] : null;

        $tableHeaders = array('Property', 'Value');
        $tableRows = array(
            array('Route Name', isset($options['name']) ? $options['name'] : ''),
            array('Path', $route->getPath()),
            array('Path Regex', $route->compile()->getRegex()),
            array('Host', ('' !== $route->getHost() ? $route->getHost() : 'ANY')),
            array('Host Regex', ('' !== $route->getHost() ? $route->compile()->getHostRegex() : '')),
            array('Scheme', ($route->getSchemes() ? implode('|', $route->getSchemes()) : 'ANY')),
            array('Method', ($route->getMethods() ? implode('|', $route->getMethods()) : 'ANY')),
            array('Requirements', ($route->getRequirements() ? $this->formatRouterConfig($route->getRequirements()) : 'NO CUSTOM')),
            array('Class', get_class($route)),
            array('Defaults', $this->formatRouterConfig($route->getDefaults(), $projectDir)),
            array('Options', $this->formatRouterConfig($route->getOptions())),
        );

        $table = new Table($this->getOutput());
        $table->setHeaders($tableHeaders)->setRows($tableRows);
        $table->render();
    }

    /**
     * {@inheritdoc}
     */
    protected function describeEventDispatcherListeners(EventDispatcherInterface $eventDispatcher, array $options = array())
    {
        $event = array_key_exists('event', $options) ? $options['event'] : null;

        if (null !== $event) {
            $title = sprintf('Registered Listeners for "%s" Event', $event);
        } else {
            $title = 'Registered Listeners Grouped by Event';
        }

        $options['output']->title($title);

        $registeredListeners = $eventDispatcher->getListeners($event);
        if (null !== $event) {
            $this->renderEventListenerTable($eventDispatcher, $event, $registeredListeners, $options['output']);
        } else {
            ksort($registeredListeners);
            foreach ($registeredListeners as $eventListened => $eventListeners) {
                $options['output']->section(sprintf('"%s" event', $eventListened));
                $this->renderEventListenerTable($eventDispatcher, $eventListened, $eventListeners, $options['output']);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function describeCallable($callable, array $options = array())
    {
        $this->writeText($this->formatCallable($callable), $options);
    }

    private function renderEventListenerTable(EventDispatcherInterface $eventDispatcher, $event, array $eventListeners, SymfonyStyle $io)
    {
        $tableHeaders = array('Order', 'Callable', 'Priority');
        $tableRows = array();

        foreach ($eventListeners as $order => $listener) {
            $tableRows[] = array(sprintf('#%d', $order + 1), $this->formatCallable($listener), $eventDispatcher->getListenerPriority($event, $listener));
        }

        $io->table($tableHeaders, $tableRows);
    }

    private function formatRouterConfig(array $config, $rootDir = null)
    {
        if (empty($config)) {
            return 'NONE';
        }

        ksort($config);

        $configAsString = '';
        foreach ($config as $key => $value) {
            $formattedValue = $value instanceof \Closure ? $this->formatClosure($value, $rootDir) : $this->formatValue($value);
            $configAsString .= sprintf("\n%s: %s", $key, $formattedValue);
        }

        return trim($configAsString);
    }

    private function formatCallable($callable)
    {
        if (is_array($callable)) {
            if (is_object($callable[0])) {
                return sprintf('%s::%s()', get_class($callable[0]), $callable[1]);
            }

            return sprintf('%s::%s()', $callable[0], $callable[1]);
        }

        if (is_string($callable)) {
            return sprintf('%s()', $callable);
        }

        if ($callable instanceof \Closure) {
            return '\Closure()';
        }

        if (method_exists($callable, '__invoke')) {
            return sprintf('%s::__invoke()', get_class($callable));
        }

        throw new \InvalidArgumentException('Callable is not describable.');
    }

    private function writeText($content, array $options = array())
    {
        $this->write(
            isset($options['raw_text']) && $options['raw_text'] ? strip_tags($content) : $content,
            isset($options['raw_output']) ? !$options['raw_output'] : true
        );
    }
}
