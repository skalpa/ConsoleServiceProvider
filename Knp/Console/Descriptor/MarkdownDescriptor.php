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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Jean-François Simon <jeanfrancois.simon@sensiolabs.com>
 *
 * @internal
 */
class MarkdownDescriptor extends AbstractDescriptor
{
    /**
     * {@inheritdoc}
     */
    protected function describeRouteCollection(RouteCollection $routes, array $options = array())
    {
        $first = true;
        foreach ($routes->all() as $name => $route) {
            if ($first) {
                $first = false;
            } else {
                $this->write("\n\n");
            }
            $this->describeRoute($route, array('name' => $name));
        }
        $this->write("\n");
    }

    /**
     * {@inheritdoc}
     */
    protected function describeRoute(Route $route, array $options = array())
    {
        $output = '- Path: '.$route->getPath()
            ."\n".'- Path Regex: '.$route->compile()->getRegex()
            ."\n".'- Host: '.('' !== $route->getHost() ? $route->getHost() : 'ANY')
            ."\n".'- Host Regex: '.('' !== $route->getHost() ? $route->compile()->getHostRegex() : '')
            ."\n".'- Scheme: '.($route->getSchemes() ? implode('|', $route->getSchemes()) : 'ANY')
            ."\n".'- Method: '.($route->getMethods() ? implode('|', $route->getMethods()) : 'ANY')
            ."\n".'- Class: '.get_class($route)
            ."\n".'- Defaults: '.$this->formatRouterConfig($route->getDefaults())
            ."\n".'- Requirements: '.($route->getRequirements() ? $this->formatRouterConfig($route->getRequirements()) : 'NO CUSTOM')
            ."\n".'- Options: '.$this->formatRouterConfig($route->getOptions());

        $this->write(
            isset($options['name'])
            ? $options['name']."\n".str_repeat('-', strlen($options['name']))."\n\n".$output
            : $output
        );
        $this->write("\n");
    }

    /**
     * {@inheritdoc}
     */
    protected function describeEventDispatcherListeners(EventDispatcherInterface $eventDispatcher, array $options = array())
    {
        $event = array_key_exists('event', $options) ? $options['event'] : null;

        $title = 'Registered listeners';
        if (null !== $event) {
            $title .= sprintf(' for event `%s` ordered by descending priority', $event);
        }

        $this->write(sprintf('# %s', $title)."\n");

        $registeredListeners = $eventDispatcher->getListeners($event);
        if (null !== $event) {
            foreach ($registeredListeners as $order => $listener) {
                $this->write("\n".sprintf('## Listener %d', $order + 1)."\n");
                $this->describeCallable($listener);
                $this->write(sprintf('- Priority: `%d`', $eventDispatcher->getListenerPriority($event, $listener))."\n");
            }
        } else {
            ksort($registeredListeners);

            foreach ($registeredListeners as $eventListened => $eventListeners) {
                $this->write("\n".sprintf('## %s', $eventListened)."\n");

                foreach ($eventListeners as $order => $eventListener) {
                    $this->write("\n".sprintf('### Listener %d', $order + 1)."\n");
                    $this->describeCallable($eventListener);
                    $this->write(sprintf('- Priority: `%d`', $eventDispatcher->getListenerPriority($eventListened, $eventListener))."\n");
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function describeCallable($callable, array $options = array())
    {
        $string = '';

        if (is_array($callable)) {
            $string .= "\n- Type: `function`";

            if (is_object($callable[0])) {
                $string .= "\n".sprintf('- Name: `%s`', $callable[1]);
                $string .= "\n".sprintf('- Class: `%s`', get_class($callable[0]));
            } else {
                if (0 !== strpos($callable[1], 'parent::')) {
                    $string .= "\n".sprintf('- Name: `%s`', $callable[1]);
                    $string .= "\n".sprintf('- Class: `%s`', $callable[0]);
                    $string .= "\n- Static: yes";
                } else {
                    $string .= "\n".sprintf('- Name: `%s`', substr($callable[1], 8));
                    $string .= "\n".sprintf('- Class: `%s`', $callable[0]);
                    $string .= "\n- Static: yes";
                    $string .= "\n- Parent: yes";
                }
            }

            return $this->write($string."\n");
        }

        if (is_string($callable)) {
            $string .= "\n- Type: `function`";

            if (false === strpos($callable, '::')) {
                $string .= "\n".sprintf('- Name: `%s`', $callable);
            } else {
                $callableParts = explode('::', $callable);

                $string .= "\n".sprintf('- Name: `%s`', $callableParts[1]);
                $string .= "\n".sprintf('- Class: `%s`', $callableParts[0]);
                $string .= "\n- Static: yes";
            }

            return $this->write($string."\n");
        }

        if ($callable instanceof \Closure) {
            $string .= "\n- Type: `closure`";

            return $this->write($string."\n");
        }

        if (method_exists($callable, '__invoke')) {
            $string .= "\n- Type: `object`";
            $string .= "\n".sprintf('- Name: `%s`', get_class($callable));

            return $this->write($string."\n");
        }

        throw new \InvalidArgumentException('Callable is not describable.');
    }

    /**
     * @param array $array
     *
     * @return string
     */
    private function formatRouterConfig(array $array)
    {
        if (!$array) {
            return 'NONE';
        }

        $string = '';
        ksort($array);
        foreach ($array as $name => $value) {
            $string .= "\n".'    - `'.$name.'`: '.$this->formatValue($value);
        }

        return $string;
    }
}
