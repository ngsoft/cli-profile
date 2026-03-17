<?php

/** @noinspection RedundantSuppression */
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace NGSOFT\Console;

use NGSOFT\Console\Profile\CommandHelper;
use NGSOFT\Console\Profile\LaravelPromptConfigurator;
use NGSOFT\Console\Profile\Tailwind\TailwindOutputFormatter;
use NGSOFT\Console\Profile\Tailwind\TailwindPalette;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Service\Attribute\Required;

final class ConsoleApplication implements Version
{
    private ?ContainerInterface $container = null;
    private array $definitions             = [];

    private readonly CommandHelper $commandHelper;

    public function __construct(
        private readonly Application $application,
        private readonly InputInterface $input,
        private readonly OutputInterface $output
    ) {
        $this->commandHelper = new CommandHelper($this->input, $this->output);
    }

    public function __debugInfo(): array
    {
        return [];
    }

    public static function newInstance(
        string $name = 'UNKNOWN',
        string $version = 'UNKNOWN'
    ): self {
        return new self(
            new Application($name, $version),
            new ArgvInput(),
            new ConsoleOutput(formatter: new TailwindOutputFormatter())
        );
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    #[Required]
    public function setContainer(ContainerInterface $container): ?ContainerInterface
    {
        $previous        = $this->container;
        $this->container = $container;
        return $previous;
    }

    public function getCommandHelper(): CommandHelper
    {
        return $this->commandHelper;
    }

    public function getApplication(): Application
    {
        return $this->application;
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * @param class-string<Command>|class-string<Command>[]|Command|Command[] $commands
     *
     * @return $this
     */
    public function add(array|Command|string $commands): static
    {
        if ( ! is_array($commands))
        {
            $commands = [$commands];
        }

        foreach ($commands as $command)
        {
            if ( ! is_subclass_of($command, Command::class, is_string($command)))
            {
                throw new \InvalidArgumentException(sprintf(
                    '$command must be class-string<%s>|%s',
                    Command::class,
                    Command::class
                ));
            }

            $this->definitions[] = $command;
            $this->definitions   = array_values(array_unique($this->definitions));
        }

        return $this;
    }

    public function run(): void
    {
        $_SERVER['VAR_DUMPER_FORMAT'] = 'cli';
        $_SERVER['FORCE_COLOR']       = '1';
        $this->addDefinitions($this->definitions);
        $this->loadPlugins();
        $this->application->run($this->input, $this->output);
    }

    private function loadPlugins(): void
    {
        LaravelPromptConfigurator::applyStyles($this->commandHelper);

        if (false === $this->commandHelper->getOutput()->getFormatter() instanceof TailwindOutputFormatter)
        {
            TailwindPalette::applyStyles($this->commandHelper);
        }
    }

    private function addDefinitions(array $definitions): void
    {
        foreach ($definitions as $definition)
        {
            if (is_string($definition))
            {
                if ( ! is_subclass_of($definition, Command::class))
                {
                    continue;
                }
                $definition = $this->getContainer()?->get($definition) ?? new $definition();
            }

            if (method_exists($this->getApplication(), 'addCommand'))
            {
                $this->getApplication()->addCommand($definition);
            } else
            {
                $this->getApplication()->add($definition);
            }
        }
    }
}
