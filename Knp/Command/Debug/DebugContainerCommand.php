<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\Command\Debug;

use Knp\Command\Command;
use Knp\Console\Helper\DescriptorHelper;
use Pimple\Container;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * A console command for retrieving information about services.
 *
 * @author Ryan Weaver <ryan@thatsquality.com>
 */
class DebugContainerCommand extends Command
{
    /**
     * @var ContainerBuilder|null
     */
    protected $containerBuilder;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:container')
            ->setDefinition(array(
                new InputArgument('name', InputArgument::OPTIONAL, 'A service name (foo)'),
                new InputOption('parameter', null, InputOption::VALUE_REQUIRED, 'Displays a specific parameter for an application'),
                new InputOption('parameters', null, InputOption::VALUE_NONE, 'Displays parameters for an application'),
                new InputOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, xml, json, or md)', 'txt'),
                new InputOption('raw', null, InputOption::VALUE_NONE, 'To output raw description'),
            ))
            ->setDescription('Displays current services for an application')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command displays all configured services:

  <info>php %command.full_name%</info>

To get specific information about a service, specify its name:

  <info>php %command.full_name% validator</info>

Use the <info>--parameters</info> option to display all parameters:

  <info>php %command.full_name% --parameters</info>

Display a specific parameter by specifying its name with the <info>--parameter</info> option:

  <info>php %command.full_name% --parameter=kernel.debug</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->validateInput($input);
        $container = $this->getSilexApplication();

        if ($input->getOption('parameters')) {
            $options = array('parameters' => true);
        } elseif ($parameter = $input->getOption('parameter')) {
            $parameter = $this->findProperParameterName($input, $io, $container, $parameter);
            $options = array('parameter' => $parameter);
        } elseif ($name = $input->getArgument('name')) {
            $name = $this->findProperServiceName($input, $io, $container, $name);
            $options = array('id' => $name);
        }

        $helper = new DescriptorHelper();
        $options['format'] = $input->getOption('format');
        $options['raw_text'] = $input->getOption('raw');
        $options['output'] = $io;
        $helper->describe($output, $container, $options);

        if (!$input->getArgument('name') && !$input->getOption('parameter') && $input->isInteractive()) {
            if ($input->getOption('parameters')) {
                $io->comment('To search for a specific parameter, re-run this command with a search term. (e.g. <comment>debug:container --parameter=charset</comment>)');
            } else {
                $io->comment('To search for a specific service, re-run this command with a search term. (e.g. <comment>debug:container logger</comment>)');
            }
        }
    }

    /**
     * Validates input arguments and options.
     *
     * @param InputInterface $input
     *
     * @throws \InvalidArgumentException
     */
    protected function validateInput(InputInterface $input)
    {
        $options = array('parameters', 'parameter');

        $optionsCount = 0;
        foreach ($options as $option) {
            if ($input->getOption($option)) {
                ++$optionsCount;
            }
        }

        $name = $input->getArgument('name');
        if ((null !== $name) && ($optionsCount > 0)) {
            throw new \InvalidArgumentException('The options parameters & parameter cannot be combined with the service name argument.');
        } elseif ((null === $name) && $optionsCount > 1) {
            throw new \InvalidArgumentException('The options parameters & parameter cannot be combined together.');
        }
    }

    private function findProperParameterName(InputInterface $input, SymfonyStyle $io, Container $container, $name)
    {
        if (!$input->isInteractive() || $this->isPimpleParameter($container, $name)) {
            return $name;
        }

        $matchingNames = $this->findEntryIdsContaining($container, $name, false);
        if (empty($matchingNames)) {
            throw new \InvalidArgumentException(sprintf('There are no parameters that match "%s".', $name));
        }

        $default = 1 === count($matchingNames) ? $matchingNames[0] : null;

        return $io->choice('Select one of the following entries to display its information', $matchingNames, $default);
    }

    private function findProperServiceName(InputInterface $input, SymfonyStyle $io, Container $container, $name)
    {
        if (!$input->isInteractive() || $this->isPimpleService($container, $name)) {
            return $name;
        }

        $matchingNames = $this->findEntryIdsContaining($container, $name, true);
        if (empty($matchingNames)) {
            throw new \InvalidArgumentException(sprintf('There are no services that match "%s".', $name));
        }

        $default = 1 === count($matchingNames) ? $matchingNames[0] : null;

        return $io->choice('Select one of the following entries to display its information', $matchingNames, $default);
    }

    private function findEntryIdsContaining(Container $container, $name, $isService)
    {
        $foundIds = array();
        $name = strtolower($name);
        foreach ($container->keys() as $serviceId) {
            if (false === strpos($serviceId, $name)) {
                continue;
            }
            if (($isService && $this->isPimpleService($container, $name)) || $this->isPimpleParameter($container, $name)) {
                $foundIds[] = $serviceId;
            }
        }

        return $foundIds;
    }

    private function isPimpleParameter(Container $container, $name)
    {
        if (!isset($container[$name])) {
            return false;
        }
        try {
            return $container[$name] === $container->raw($name);
        } catch (\Exception $e) {
        }

        return false;
    }

    private function isPimpleService(Container $container, $name)
    {
        if (!isset($container[$name])) {
            return false;
        }
        try {
            return $container[$name] !== $container->raw($name);
        } catch (\Exception $e) {
        }

        return true;
    }
}
