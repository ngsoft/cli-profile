<?php

/** @noinspection RedundantSuppression */
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace NGSOFT\Console;

use NGSOFT\Console\Profile\CommandHelper;
use NGSOFT\Console\Profile\LaravelPromptConfigurator;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Service\Attribute\Required;

final class ConsoleApplication implements Version
{
    private ?ContainerInterface $container = null;
    private array $definitions             = [];

    public function __construct(
        private readonly Application $application,
        private readonly InputInterface $input,
        private readonly OutputInterface $output
    ) {}

    public function __debugInfo(): array
    {
        return [];
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
        $output                       = self::addStyles($this->output);
        new LaravelPromptConfigurator(new CommandHelper($this->input, $this->output));
        $this->application->run($this->input, $output);
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

    private static function addStyles(OutputInterface $output): OutputInterface
    {
        $palette   = self::getPalette();

        $formatter = $output->getFormatter();

        foreach ($palette as $name => list($color, $text))
        {
            try
            {
                $formatter->getStyle($name);
            } catch (\Throwable)
            {
                $formatter->setStyle($name, $style = new OutputFormatterStyle($color));
                $formatter->setStyle("fg={$name}", $style);
                $formatter->setStyle("bg-{$name}", $style = new OutputFormatterStyle($text, $color));
                $formatter->setStyle("bg={$name}", $style);
            }
        }

        $output->setDecorated(true);

        return $output;
    }

    /**
     * @see https://tailwindcss.com/docs/colors
     *
     * @return array
     */
    private static function getPalette(): array
    {
        return [
            'slate-50'             => [
                '#f8fafc',
                '#000',
            ],
            'slate-100'            => [
                '#f1f5f9',
                '#000',
            ],
            'slate-200'            => [
                '#e2e8f0',
                '#000',
            ],
            'slate-300'            => [
                '#cbd5e1',
                '#000',
            ],
            'slate-400'            => [
                '#94a3b8',
                '#000',
            ],
            'slate-500'            => [
                '#64748b',
                '#fff',
            ],
            'slate-600'            => [
                '#475569',
                '#fff',
            ],
            'slate-700'            => [
                '#334155',
                '#fff',
            ],
            'slate-800'            => [
                '#1e293b',
                '#fff',
            ],
            'slate-900'            => [
                '#0f172a',
                '#fff',
            ],
            'slate-950'            => [
                '#020617',
                '#fff',
            ],
            'gray-50'              => [
                '#f9fafb',
                '#000',
            ],
            'gray-100'             => [
                '#f3f4f6',
                '#000',
            ],
            'gray-200'             => [
                '#e5e7eb',
                '#000',
            ],
            'gray-300'             => [
                '#d1d5db',
                '#000',
            ],
            'gray-400'             => [
                '#9ca3af',
                '#000',
            ],
            'gray-500'             => [
                '#6b7280',
                '#fff',
            ],
            'gray-600'             => [
                '#4b5563',
                '#fff',
            ],
            'gray-700'             => [
                '#374151',
                '#fff',
            ],
            'gray-800'             => [
                '#1f2937',
                '#fff',
            ],
            'gray-900'             => [
                '#111827',
                '#fff',
            ],
            'gray-950'             => [
                '#030712',
                '#fff',
            ],
            'zinc-50'              => [
                '#fafafa',
                '#000',
            ],
            'zinc-100'             => [
                '#f4f4f5',
                '#000',
            ],
            'zinc-200'             => [
                '#e4e4e7',
                '#000',
            ],
            'zinc-300'             => [
                '#d4d4d8',
                '#000',
            ],
            'zinc-400'             => [
                '#a1a1aa',
                '#000',
            ],
            'zinc-500'             => [
                '#71717a',
                '#fff',
            ],
            'zinc-600'             => [
                '#52525b',
                '#fff',
            ],
            'zinc-700'             => [
                '#3f3f46',
                '#fff',
            ],
            'zinc-800'             => [
                '#27272a',
                '#fff',
            ],
            'zinc-900'             => [
                '#18181b',
                '#fff',
            ],
            'zinc-950'             => [
                '#09090b',
                '#fff',
            ],
            'neutral-50'           => [
                '#fafafa',
                '#000',
            ],
            'neutral-100'          => [
                '#f5f5f5',
                '#000',
            ],
            'neutral-200'          => [
                '#e5e5e5',
                '#000',
            ],
            'neutral-300'          => [
                '#d4d4d4',
                '#000',
            ],
            'neutral-400'          => [
                '#a3a3a3',
                '#000',
            ],
            'neutral-500'          => [
                '#737373',
                '#fff',
            ],
            'neutral-600'          => [
                '#525252',
                '#fff',
            ],
            'neutral-700'          => [
                '#404040',
                '#fff',
            ],
            'neutral-800'          => [
                '#262626',
                '#fff',
            ],
            'neutral-900'          => [
                '#171717',
                '#fff',
            ],
            'neutral-950'          => [
                '#0a0a0a',
                '#fff',
            ],
            'stone-50'             => [
                '#fafaf9',
                '#000',
            ],
            'stone-100'            => [
                '#f5f5f4',
                '#000',
            ],
            'stone-200'            => [
                '#e7e5e4',
                '#000',
            ],
            'stone-300'            => [
                '#d6d3d1',
                '#000',
            ],
            'stone-400'            => [
                '#a8a29e',
                '#000',
            ],
            'stone-500'            => [
                '#78716c',
                '#fff',
            ],
            'stone-600'            => [
                '#57534e',
                '#fff',
            ],
            'stone-700'            => [
                '#44403c',
                '#fff',
            ],
            'stone-800'            => [
                '#292524',
                '#fff',
            ],
            'stone-900'            => [
                '#1c1917',
                '#fff',
            ],
            'stone-950'            => [
                '#0c0a09',
                '#fff',
            ],
            'red-50'               => [
                '#fef2f2',
                '#000',
            ],
            'red-100'              => [
                '#fee2e2',
                '#000',
            ],
            'red-200'              => [
                '#fecaca',
                '#000',
            ],
            'red-300'              => [
                '#fca5a5',
                '#000',
            ],
            'red-400'              => [
                '#f87171',
                '#000',
            ],
            'red-500'              => [
                '#ef4444',
                '#fff',
            ],
            'red-600'              => [
                '#dc2626',
                '#fff',
            ],
            'red-700'              => [
                '#b91c1c',
                '#fff',
            ],
            'red-800'              => [
                '#991b1b',
                '#fff',
            ],
            'red-900'              => [
                '#7f1d1d',
                '#fff',
            ],
            'red-950'              => [
                '#450a0a',
                '#fff',
            ],
            'orange-50'            => [
                '#fff7ed',
                '#000',
            ],
            'orange-100'           => [
                '#ffedd5',
                '#000',
            ],
            'orange-200'           => [
                '#fed7aa',
                '#000',
            ],
            'orange-300'           => [
                '#fdba74',
                '#000',
            ],
            'orange-400'           => [
                '#fb923c',
                '#000',
            ],
            'orange-500'           => [
                '#f97316',
                '#000',
            ],
            'orange-600'           => [
                '#ea580c',
                '#fff',
            ],
            'orange-700'           => [
                '#c2410c',
                '#fff',
            ],
            'orange-800'           => [
                '#9a3412',
                '#fff',
            ],
            'orange-900'           => [
                '#7c2d12',
                '#fff',
            ],
            'orange-950'           => [
                '#431407',
                '#fff',
            ],
            'amber-50'             => [
                '#fffbeb',
                '#000',
            ],
            'amber-100'            => [
                '#fef3c7',
                '#000',
            ],
            'amber-200'            => [
                '#fde68a',
                '#000',
            ],
            'amber-300'            => [
                '#fcd34d',
                '#000',
            ],
            'amber-400'            => [
                '#fbbf24',
                '#000',
            ],
            'amber-500'            => [
                '#f59e0b',
                '#000',
            ],
            'amber-600'            => [
                '#d97706',
                '#000',
            ],
            'amber-700'            => [
                '#b45309',
                '#fff',
            ],
            'amber-800'            => [
                '#92400e',
                '#fff',
            ],
            'amber-900'            => [
                '#78350f',
                '#fff',
            ],
            'amber-950'            => [
                '#451a03',
                '#fff',
            ],
            'yellow-50'            => [
                '#fefce8',
                '#000',
            ],
            'yellow-100'           => [
                '#fef9c3',
                '#000',
            ],
            'yellow-200'           => [
                '#fef08a',
                '#000',
            ],
            'yellow-300'           => [
                '#fde047',
                '#000',
            ],
            'yellow-400'           => [
                '#facc15',
                '#000',
            ],
            'yellow-500'           => [
                '#eab308',
                '#000',
            ],
            'yellow-600'           => [
                '#ca8a04',
                '#000',
            ],
            'yellow-700'           => [
                '#a16207',
                '#fff',
            ],
            'yellow-800'           => [
                '#854d0e',
                '#fff',
            ],
            'yellow-900'           => [
                '#713f12',
                '#fff',
            ],
            'yellow-950'           => [
                '#422006',
                '#fff',
            ],
            'lime-50'              => [
                '#f7fee7',
                '#000',
            ],
            'lime-100'             => [
                '#ecfccb',
                '#000',
            ],
            'lime-200'             => [
                '#d9f99d',
                '#000',
            ],
            'lime-300'             => [
                '#bef264',
                '#000',
            ],
            'lime-400'             => [
                '#a3e635',
                '#000',
            ],
            'lime-500'             => [
                '#84cc16',
                '#000',
            ],
            'lime-600'             => [
                '#65a30d',
                '#fff',
            ],
            'lime-700'             => [
                '#4d7c0f',
                '#fff',
            ],
            'lime-800'             => [
                '#3f6212',
                '#fff',
            ],
            'lime-900'             => [
                '#365314',
                '#fff',
            ],
            'lime-950'             => [
                '#1a2e05',
                '#fff',
            ],
            'green-50'             => [
                '#f0fdf4',
                '#000',
            ],
            'green-100'            => [
                '#dcfce7',
                '#000',
            ],
            'green-200'            => [
                '#bbf7d0',
                '#000',
            ],
            'green-300'            => [
                '#86efac',
                '#000',
            ],
            'green-400'            => [
                '#4ade80',
                '#000',
            ],
            'green-500'            => [
                '#22c55e',
                '#000',
            ],
            'green-600'            => [
                '#16a34a',
                '#fff',
            ],
            'green-700'            => [
                '#15803d',
                '#fff',
            ],
            'green-800'            => [
                '#166534',
                '#fff',
            ],
            'green-900'            => [
                '#14532d',
                '#fff',
            ],
            'green-950'            => [
                '#052e16',
                '#fff',
            ],
            'emerald-50'           => [
                '#ecfdf5',
                '#000',
            ],
            'emerald-100'          => [
                '#d1fae5',
                '#000',
            ],
            'emerald-200'          => [
                '#a7f3d0',
                '#000',
            ],
            'emerald-300'          => [
                '#6ee7b7',
                '#000',
            ],
            'emerald-400'          => [
                '#34d399',
                '#000',
            ],
            'emerald-500'          => [
                '#10b981',
                '#000',
            ],
            'emerald-600'          => [
                '#059669',
                '#fff',
            ],
            'emerald-700'          => [
                '#047857',
                '#fff',
            ],
            'emerald-800'          => [
                '#065f46',
                '#fff',
            ],
            'emerald-900'          => [
                '#064e3b',
                '#fff',
            ],
            'emerald-950'          => [
                '#022c22',
                '#fff',
            ],
            'teal-50'              => [
                '#f0fdfa',
                '#000',
            ],
            'teal-100'             => [
                '#ccfbf1',
                '#000',
            ],
            'teal-200'             => [
                '#99f6e4',
                '#000',
            ],
            'teal-300'             => [
                '#5eead4',
                '#000',
            ],
            'teal-400'             => [
                '#2dd4bf',
                '#000',
            ],
            'teal-500'             => [
                '#14b8a6',
                '#000',
            ],
            'teal-600'             => [
                '#0d9488',
                '#fff',
            ],
            'teal-700'             => [
                '#0f766e',
                '#fff',
            ],
            'teal-800'             => [
                '#115e59',
                '#fff',
            ],
            'teal-900'             => [
                '#134e4a',
                '#fff',
            ],
            'teal-950'             => [
                '#042f2e',
                '#fff',
            ],
            'cyan-50'              => [
                '#ecfeff',
                '#000',
            ],
            'cyan-100'             => [
                '#cffafe',
                '#000',
            ],
            'cyan-200'             => [
                '#a5f3fc',
                '#000',
            ],
            'cyan-300'             => [
                '#67e8f9',
                '#000',
            ],
            'cyan-400'             => [
                '#22d3ee',
                '#000',
            ],
            'cyan-500'             => [
                '#06b6d4',
                '#000',
            ],
            'cyan-600'             => [
                '#0891b2',
                '#fff',
            ],
            'cyan-700'             => [
                '#0e7490',
                '#fff',
            ],
            'cyan-800'             => [
                '#155e75',
                '#fff',
            ],
            'cyan-900'             => [
                '#164e63',
                '#fff',
            ],
            'cyan-950'             => [
                '#083344',
                '#fff',
            ],
            'sky-50'               => [
                '#f0f9ff',
                '#000',
            ],
            'sky-100'              => [
                '#e0f2fe',
                '#000',
            ],
            'sky-200'              => [
                '#bae6fd',
                '#000',
            ],
            'sky-300'              => [
                '#7dd3fc',
                '#000',
            ],
            'sky-400'              => [
                '#38bdf8',
                '#000',
            ],
            'sky-500'              => [
                '#0ea5e9',
                '#fff',
            ],
            'sky-600'              => [
                '#0284c7',
                '#fff',
            ],
            'sky-700'              => [
                '#0369a1',
                '#fff',
            ],
            'sky-800'              => [
                '#075985',
                '#fff',
            ],
            'sky-900'              => [
                '#0c4a6e',
                '#fff',
            ],
            'sky-950'              => [
                '#082f49',
                '#fff',
            ],
            'blue-50'              => [
                '#eff6ff',
                '#000',
            ],
            'blue-100'             => [
                '#dbeafe',
                '#000',
            ],
            'blue-200'             => [
                '#bfdbfe',
                '#000',
            ],
            'blue-300'             => [
                '#93c5fd',
                '#000',
            ],
            'blue-400'             => [
                '#60a5fa',
                '#000',
            ],
            'blue-500'             => [
                '#3b82f6',
                '#fff',
            ],
            'blue-600'             => [
                '#2563eb',
                '#fff',
            ],
            'blue-700'             => [
                '#1d4ed8',
                '#fff',
            ],
            'blue-800'             => [
                '#1e40af',
                '#fff',
            ],
            'blue-900'             => [
                '#1e3a8a',
                '#fff',
            ],
            'blue-950'             => [
                '#172554',
                '#fff',
            ],
            'indigo-50'            => [
                '#eef2ff',
                '#000',
            ],
            'indigo-100'           => [
                '#e0e7ff',
                '#000',
            ],
            'indigo-200'           => [
                '#c7d2fe',
                '#000',
            ],
            'indigo-300'           => [
                '#a5b4fc',
                '#000',
            ],
            'indigo-400'           => [
                '#818cf8',
                '#000',
            ],
            'indigo-500'           => [
                '#6366f1',
                '#fff',
            ],
            'indigo-600'           => [
                '#4f46e5',
                '#fff',
            ],
            'indigo-700'           => [
                '#4338ca',
                '#fff',
            ],
            'indigo-800'           => [
                '#3730a3',
                '#fff',
            ],
            'indigo-900'           => [
                '#312e81',
                '#fff',
            ],
            'indigo-950'           => [
                '#1e1b4b',
                '#fff',
            ],
            'violet-50'            => [
                '#f5f3ff',
                '#000',
            ],
            'violet-100'           => [
                '#ede9fe',
                '#000',
            ],
            'violet-200'           => [
                '#ddd6fe',
                '#000',
            ],
            'violet-300'           => [
                '#c4b5fd',
                '#000',
            ],
            'violet-400'           => [
                '#a78bfa',
                '#000',
            ],
            'violet-500'           => [
                '#8b5cf6',
                '#fff',
            ],
            'violet-600'           => [
                '#7c3aed',
                '#fff',
            ],
            'violet-700'           => [
                '#6d28d9',
                '#fff',
            ],
            'violet-800'           => [
                '#5b21b6',
                '#fff',
            ],
            'violet-900'           => [
                '#4c1d95',
                '#fff',
            ],
            'violet-950'           => [
                '#2e1065',
                '#fff',
            ],
            'purple-50'            => [
                '#faf5ff',
                '#000',
            ],
            'purple-100'           => [
                '#f3e8ff',
                '#000',
            ],
            'purple-200'           => [
                '#e9d5ff',
                '#000',
            ],
            'purple-300'           => [
                '#d8b4fe',
                '#000',
            ],
            'purple-400'           => [
                '#c084fc',
                '#000',
            ],
            'purple-500'           => [
                '#a855f7',
                '#000',
            ],
            'purple-600'           => [
                '#9333ea',
                '#fff',
            ],
            'purple-700'           => [
                '#7e22ce',
                '#fff',
            ],
            'purple-800'           => [
                '#6b21a8',
                '#fff',
            ],
            'purple-900'           => [
                '#581c87',
                '#fff',
            ],
            'purple-950'           => [
                '#3b0764',
                '#fff',
            ],
            'fuchsia-50'           => [
                '#fdf4ff',
                '#000',
            ],
            'fuchsia-100'          => [
                '#fae8ff',
                '#000',
            ],
            'fuchsia-200'          => [
                '#f5d0fe',
                '#000',
            ],
            'fuchsia-300'          => [
                '#f0abfc',
                '#000',
            ],
            'fuchsia-400'          => [
                '#e879f9',
                '#000',
            ],
            'fuchsia-500'          => [
                '#d946ef',
                '#000',
            ],
            'fuchsia-600'          => [
                '#c026d3',
                '#fff',
            ],
            'fuchsia-700'          => [
                '#a21caf',
                '#fff',
            ],
            'fuchsia-800'          => [
                '#86198f',
                '#fff',
            ],
            'fuchsia-900'          => [
                '#701a75',
                '#fff',
            ],
            'fuchsia-950'          => [
                '#4a044e',
                '#fff',
            ],
            'pink-50'              => [
                '#fdf2f8',
                '#000',
            ],
            'pink-100'             => [
                '#fce7f3',
                '#000',
            ],
            'pink-200'             => [
                '#fbcfe8',
                '#000',
            ],
            'pink-300'             => [
                '#f9a8d4',
                '#000',
            ],
            'pink-400'             => [
                '#f472b6',
                '#000',
            ],
            'pink-500'             => [
                '#ec4899',
                '#000',
            ],
            'pink-600'             => [
                '#db2777',
                '#fff',
            ],
            'pink-700'             => [
                '#be185d',
                '#fff',
            ],
            'pink-800'             => [
                '#9d174d',
                '#fff',
            ],
            'pink-900'             => [
                '#831843',
                '#fff',
            ],
            'pink-950'             => [
                '#500724',
                '#fff',
            ],
            'rose-50'              => [
                '#fff1f2',
                '#000',
            ],
            'rose-100'             => [
                '#ffe4e6',
                '#000',
            ],
            'rose-200'             => [
                '#fecdd3',
                '#000',
            ],
            'rose-300'             => [
                '#fda4af',
                '#000',
            ],
            'rose-400'             => [
                '#fb7185',
                '#000',
            ],
            'rose-500'             => [
                '#f43f5e',
                '#fff',
            ],
            'rose-600'             => [
                '#e11d48',
                '#fff',
            ],
            'rose-700'             => [
                '#be123c',
                '#fff',
            ],
            'rose-800'             => [
                '#9f1239',
                '#fff',
            ],
            'rose-900'             => [
                '#881337',
                '#fff',
            ],
            'rose-950'             => [
                '#4c0519',
                '#fff',
            ],
            'aliceblue'            => [
                '#f0f8ff',
                '#000',
            ],
            'antiquewhite'         => [
                '#faebd7',
                '#000',
            ],
            'aqua'                 => [
                '#00ffff',
                '#000',
            ],
            'aquamarine'           => [
                '#7fffd4',
                '#000',
            ],
            'azure'                => [
                '#f0ffff',
                '#000',
            ],
            'beige'                => [
                '#f5f5dc',
                '#000',
            ],
            'bisque'               => [
                '#ffe4c4',
                '#000',
            ],
            'black'                => [
                '#000000',
                '#fff',
            ],
            'blanchedalmond'       => [
                '#ffebcd',
                '#000',
            ],
            'blue'                 => [
                '#0000ff',
                '#fff',
            ],
            'blueviolet'           => [
                '#8a2be2',
                '#fff',
            ],
            'brown'                => [
                '#a52a2a',
                '#fff',
            ],
            'burlywood'            => [
                '#deb887',
                '#000',
            ],
            'cadetblue'            => [
                '#5f9ea0',
                '#000',
            ],
            'chartreuse'           => [
                '#7fff00',
                '#000',
            ],
            'chocolate'            => [
                '#d2691e',
                '#fff',
            ],
            'coral'                => [
                '#ff7f50',
                '#000',
            ],
            'cornflowerblue'       => [
                '#6495ed',
                '#000',
            ],
            'cornsilk'             => [
                '#fff8dc',
                '#000',
            ],
            'crimson'              => [
                '#dc143c',
                '#fff',
            ],
            'cyan'                 => [
                '#00ffff',
                '#000',
            ],
            'darkblue'             => [
                '#00008b',
                '#fff',
            ],
            'darkcyan'             => [
                '#008b8b',
                '#fff',
            ],
            'darkgoldenrod'        => [
                '#b8860b',
                '#000',
            ],
            'darkgray'             => [
                '#a9a9a9',
                '#000',
            ],
            'darkgreen'            => [
                '#006400',
                '#fff',
            ],
            'darkgrey'             => [
                '#a9a9a9',
                '#000',
            ],
            'darkkhaki'            => [
                '#bdb76b',
                '#000',
            ],
            'darkmagenta'          => [
                '#8b008b',
                '#fff',
            ],
            'darkolivegreen'       => [
                '#556b2f',
                '#fff',
            ],
            'darkorange'           => [
                '#ff8c00',
                '#000',
            ],
            'darkorchid'           => [
                '#9932cc',
                '#fff',
            ],
            'darkred'              => [
                '#8b0000',
                '#fff',
            ],
            'darksalmon'           => [
                '#e9967a',
                '#000',
            ],
            'darkseagreen'         => [
                '#8fbc8f',
                '#000',
            ],
            'darkslateblue'        => [
                '#483d8b',
                '#fff',
            ],
            'darkslategray'        => [
                '#2f4f4f',
                '#fff',
            ],
            'darkslategrey'        => [
                '#2f4f4f',
                '#fff',
            ],
            'darkturquoise'        => [
                '#00ced1',
                '#000',
            ],
            'darkviolet'           => [
                '#9400d3',
                '#fff',
            ],
            'deeppink'             => [
                '#ff1493',
                '#fff',
            ],
            'deepskyblue'          => [
                '#00bfff',
                '#000',
            ],
            'dimgray'              => [
                '#696969',
                '#fff',
            ],
            'dimgrey'              => [
                '#696969',
                '#fff',
            ],
            'dodgerblue'           => [
                '#1e90ff',
                '#fff',
            ],
            'firebrick'            => [
                '#b22222',
                '#fff',
            ],
            'floralwhite'          => [
                '#fffaf0',
                '#000',
            ],
            'forestgreen'          => [
                '#228b22',
                '#fff',
            ],
            'fuchsia'              => [
                '#ff00ff',
                '#fff',
            ],
            'gainsboro'            => [
                '#dcdcdc',
                '#000',
            ],
            'ghostwhite'           => [
                '#f8f8ff',
                '#000',
            ],
            'gold'                 => [
                '#ffd700',
                '#000',
            ],
            'goldenrod'            => [
                '#daa520',
                '#000',
            ],
            'gray'                 => [
                '#808080',
                '#000',
            ],
            'green'                => [
                '#008000',
                '#fff',
            ],
            'greenyellow'          => [
                '#adff2f',
                '#000',
            ],
            'grey'                 => [
                '#808080',
                '#000',
            ],
            'honeydew'             => [
                '#f0fff0',
                '#000',
            ],
            'hotpink'              => [
                '#ff69b4',
                '#000',
            ],
            'indianred'            => [
                '#cd5c5c',
                '#fff',
            ],
            'indigo'               => [
                '#4b0082',
                '#fff',
            ],
            'ivory'                => [
                '#fffff0',
                '#000',
            ],
            'khaki'                => [
                '#f0e68c',
                '#000',
            ],
            'lavender'             => [
                '#e6e6fa',
                '#000',
            ],
            'lavenderblush'        => [
                '#fff0f5',
                '#000',
            ],
            'lawngreen'            => [
                '#7cfc00',
                '#000',
            ],
            'lemonchiffon'         => [
                '#fffacd',
                '#000',
            ],
            'lightblue'            => [
                '#add8e6',
                '#000',
            ],
            'lightcoral'           => [
                '#f08080',
                '#000',
            ],
            'lightcyan'            => [
                '#e0ffff',
                '#000',
            ],
            'lightgoldenrodyellow' => [
                '#fafad2',
                '#000',
            ],
            'lightgray'            => [
                '#d3d3d3',
                '#000',
            ],
            'lightgreen'           => [
                '#90ee90',
                '#000',
            ],
            'lightgrey'            => [
                '#d3d3d3',
                '#000',
            ],
            'lightpink'            => [
                '#ffb6c1',
                '#000',
            ],
            'lightsalmon'          => [
                '#ffa07a',
                '#000',
            ],
            'lightseagreen'        => [
                '#20b2aa',
                '#000',
            ],
            'lightskyblue'         => [
                '#87cefa',
                '#000',
            ],
            'lightslategray'       => [
                '#778899',
                '#000',
            ],
            'lightslategrey'       => [
                '#778899',
                '#000',
            ],
            'lightsteelblue'       => [
                '#b0c4de',
                '#000',
            ],
            'lightyellow'          => [
                '#ffffe0',
                '#000',
            ],
            'lime'                 => [
                '#00ff00',
                '#000',
            ],
            'limegreen'            => [
                '#32cd32',
                '#000',
            ],
            'linen'                => [
                '#faf0e6',
                '#000',
            ],
            'magenta'              => [
                '#ff00ff',
                '#fff',
            ],
            'maroon'               => [
                '#800000',
                '#fff',
            ],
            'mediumaquamarine'     => [
                '#66cdaa',
                '#000',
            ],
            'mediumblue'           => [
                '#0000cd',
                '#fff',
            ],
            'mediumorchid'         => [
                '#ba55d3',
                '#000',
            ],
            'mediumpurple'         => [
                '#9370db',
                '#000',
            ],
            'mediumseagreen'       => [
                '#3cb371',
                '#000',
            ],
            'mediumslateblue'      => [
                '#7b68ee',
                '#fff',
            ],
            'mediumspringgreen'    => [
                '#00fa9a',
                '#000',
            ],
            'mediumturquoise'      => [
                '#48d1cc',
                '#000',
            ],
            'mediumvioletred'      => [
                '#c71585',
                '#fff',
            ],
            'midnightblue'         => [
                '#191970',
                '#fff',
            ],
            'mintcream'            => [
                '#f5fffa',
                '#000',
            ],
            'mistyrose'            => [
                '#ffe4e1',
                '#000',
            ],
            'moccasin'             => [
                '#ffe4b5',
                '#000',
            ],
            'navajowhite'          => [
                '#ffdead',
                '#000',
            ],
            'navy'                 => [
                '#000080',
                '#fff',
            ],
            'oldlace'              => [
                '#fdf5e6',
                '#000',
            ],
            'olive'                => [
                '#808000',
                '#fff',
            ],
            'olivedrab'            => [
                '#6b8e23',
                '#fff',
            ],
            'orange'               => [
                '#ffa500',
                '#000',
            ],
            'orangered'            => [
                '#ff4500',
                '#fff',
            ],
            'orchid'               => [
                '#da70d6',
                '#000',
            ],
            'palegoldenrod'        => [
                '#eee8aa',
                '#000',
            ],
            'palegreen'            => [
                '#98fb98',
                '#000',
            ],
            'paleturquoise'        => [
                '#afeeee',
                '#000',
            ],
            'palevioletred'        => [
                '#db7093',
                '#000',
            ],
            'papayawhip'           => [
                '#ffefd5',
                '#000',
            ],
            'peachpuff'            => [
                '#ffdab9',
                '#000',
            ],
            'peru'                 => [
                '#cd853f',
                '#000',
            ],
            'pink'                 => [
                '#ffc0cb',
                '#000',
            ],
            'plum'                 => [
                '#dda0dd',
                '#000',
            ],
            'powderblue'           => [
                '#b0e0e6',
                '#000',
            ],
            'purple'               => [
                '#800080',
                '#fff',
            ],
            'rebeccapurple'        => [
                '#663399',
                '#fff',
            ],
            'red'                  => [
                '#ff0000',
                '#fff',
            ],
            'rosybrown'            => [
                '#bc8f8f',
                '#000',
            ],
            'royalblue'            => [
                '#4169e1',
                '#fff',
            ],
            'saddlebrown'          => [
                '#8b4513',
                '#fff',
            ],
            'salmon'               => [
                '#fa8072',
                '#000',
            ],
            'sandybrown'           => [
                '#f4a460',
                '#000',
            ],
            'seagreen'             => [
                '#2e8b57',
                '#fff',
            ],
            'seashell'             => [
                '#fff5ee',
                '#000',
            ],
            'sienna'               => [
                '#a0522d',
                '#fff',
            ],
            'silver'               => [
                '#c0c0c0',
                '#000',
            ],
            'skyblue'              => [
                '#87ceeb',
                '#000',
            ],
            'slateblue'            => [
                '#6a5acd',
                '#fff',
            ],
            'slategray'            => [
                '#708090',
                '#fff',
            ],
            'slategrey'            => [
                '#708090',
                '#fff',
            ],
            'snow'                 => [
                '#fffafa',
                '#000',
            ],
            'springgreen'          => [
                '#00ff7f',
                '#000',
            ],
            'steelblue'            => [
                '#4682b4',
                '#fff',
            ],
            'tan'                  => [
                '#d2b48c',
                '#000',
            ],
            'teal'                 => [
                '#008080',
                '#fff',
            ],
            'thistle'              => [
                '#d8bfd8',
                '#000',
            ],
            'tomato'               => [
                '#ff6347',
                '#000',
            ],
            'turquoise'            => [
                '#40e0d0',
                '#000',
            ],
            'violet'               => [
                '#ee82ee',
                '#000',
            ],
            'wheat'                => [
                '#f5deb3',
                '#000',
            ],
            'white'                => [
                '#ffffff',
                '#000',
            ],
            'whitesmoke'           => [
                '#f5f5f5',
                '#000',
            ],
            'yellow'               => [
                '#ffff00',
                '#000',
            ],
            'yellowgreen'          => [
                '#9acd32',
                '#000',
            ],
            /*
             * @see https://www.htmlcsscolor.com/hex/3A3A3A
             */
            'eclipse'              => [
                '#3a3a3a',
                '#fff',
            ],
        ];
    }
}
