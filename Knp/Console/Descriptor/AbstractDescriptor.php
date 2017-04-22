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

use Symfony\Component\Console\Descriptor\DescriptorInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Jean-François Simon <jeanfrancois.simon@sensiolabs.com>
 *
 * @internal
 */
abstract class AbstractDescriptor implements DescriptorInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * {@inheritdoc}
     */
    public function describe(OutputInterface $output, $object, array $options = array())
    {
        $this->output = $output;

        switch (true) {
            case $object instanceof RouteCollection:
                $this->describeRouteCollection($object, $options);
                break;
            case $object instanceof Route:
                $this->describeRoute($object, $options);
                break;
            //case $object instanceof ContainerBuilder && isset($options['id']):
            //    $this->describeContainerService($this->resolveServiceDefinition($object, $options['id']), $options);
            //    break;
            case $object instanceof EventDispatcherInterface:
                $this->describeEventDispatcherListeners($object, $options);
                break;
            case is_callable($object):
                $this->describeCallable($object, $options);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Object of type "%s" is not describable.', get_class($object)));
        }
    }

    /**
     * Returns the output.
     *
     * @return OutputInterface The output
     */
    protected function getOutput()
    {
        return $this->output;
    }

    /**
     * Writes content to output.
     *
     * @param string $content
     * @param bool   $decorated
     */
    protected function write($content, $decorated = false)
    {
        $this->output->write($content, false, $decorated ? OutputInterface::OUTPUT_NORMAL : OutputInterface::OUTPUT_RAW);
    }

    /**
     * Describes an InputArgument instance.
     *
     * @param RouteCollection $routes
     * @param array           $options
     */
    abstract protected function describeRouteCollection(RouteCollection $routes, array $options = array());

    /**
     * Describes an InputOption instance.
     *
     * @param Route $route
     * @param array $options
     */
    abstract protected function describeRoute(Route $route, array $options = array());

    /**
     * Describes event dispatcher listeners.
     *
     * Common options are:
     * * name: name of listened event
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param array                    $options
     */
    abstract protected function describeEventDispatcherListeners(EventDispatcherInterface $eventDispatcher, array $options = array());

    /**
     * Describes a callable.
     *
     * @param callable $callable
     * @param array    $options
     */
    abstract protected function describeCallable($callable, array $options = array());

    /**
     * Formats a value as string.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function formatValue($value)
    {
        if (is_object($value)) {
            return sprintf('object(%s)', get_class($value));
        }

        if (is_string($value)) {
            return $value;
        }

        return preg_replace("/\n\s*/s", '', var_export($value, true));
    }


    /**
     * Converts a closure to a "Closure(file@line)" string.
     *
     * @param \Closure    $closure The closure to convert
     * @param string|null $rootDir If specified, make the file path relative to this directory
     *
     * @return string
     */
    protected function formatClosure(\Closure $closure, $rootDir = null)
    {
        $reflection = new \ReflectionFunction($closure);
        $closureFile = $reflection->getFileName();
        $closureLine = $reflection->getStartLine();
        if (null !== $rootDir) {
            $closureFile = rtrim((new Filesystem())->makePathRelative($closureFile, $rootDir), '/');
        }

        return sprintf('Closure(%s@%d)', $closureFile, $closureLine);
    }
}
