<?php

declare(strict_types=1);

namespace NGSOFT\Console\Profile;

use NGSOFT\Console\Version;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CommandHelper extends SymfonyStyle implements Version
{
    public function __construct(private readonly InputInterface $input, private readonly OutputInterface $output)
    {
        parent::__construct($input, $output);
    }

    public static function str_format(string $subject, array $replacements)
    {
        if ( ! count($replacements))
        {
            return $subject;
        }
        $replacements = self::parseReplacements($replacements);

        if (array_is_list($replacements) && str_contains($subject, '%'))
        {
            try
            {
                // prevent warnings < PHP 8.0
                return @vsprintf($subject, $replacements) ?: $subject;
            } catch (\ValueError)
            {
                // prevents ValueError >= PHP 8.0
                return $subject;
            }
        }

        // uses named parameters (or indexed {1}, {2})
        return preg_replace_callback(
            '#{\h*([\w-]+)\h*}#',
            function ($matches) use ($replacements)
            {
                $key = $matches[1];

                if (is_numeric($key))
                {
                    $key = (int) $key;
                }

                if (isset($replacements[$key]))
                {
                    return $replacements[$key];
                }
                return $matches[0];
            },
            $subject
        );
    }

    public function out(string|\Stringable $message, mixed ...$replacements): void
    {
        $this->writeln($this::str_format($message, $replacements));
    }

    public function err(string|\Stringable $message, mixed ...$replacements): void
    {
        $this->getErrorOutput()->writeln($this::str_format($message, $replacements));
    }

    public function makeListing(iterable $elements, string $pill = '•')
    {
        $this->startText();
        $lines = [];

        foreach ($elements as $element)
        {
            $lines[] = sprintf(' %s %s', $pill, (string) $element);
        }

        $this->writeln($lines);
    }

    public function info(array|string $message, mixed ...$replacements): void
    {
        $this->laravelStyleBlock(self::str_format(
            (string) is_array($message) ? implode("\n", $message) : $message,
            $replacements
        ), 'INFO', '\\' === DIRECTORY_SEPARATOR ? 'bg=blue;fg=black' : 'bg=blue;fg=white');
    }

    public function warning(array|string $message, mixed ...$replacements): void
    {
        $this->laravelStyleBlock(self::str_format(
            (string) is_array($message) ? implode("\n", $message) : $message,
            $replacements
        ), 'WARNING', 'bg=yellow;fg=black');
    }

    public function error(array|string $message, mixed ...$replacements): void
    {
        $this->laravelStyleBlock(self::str_format(
            (string) is_array($message) ? implode("\n", $message) : $message,
            $replacements
        ), 'ERROR', '\\' === DIRECTORY_SEPARATOR ? 'bg=red;fg=black' : 'bg=red;fg=white');
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
     * @param string|string[] $message
     * @param string          $type
     * @param string          $style
     * @param int             $padding
     */
    public function laravelStyleBlock(array|string $message, string $type, string $style = 'bg:white;fg:black', int $padding = 15)
    {
        if (is_array($message))
        {
            $message = implode("\n", $message);
        }

        $lines = explode("\n", str_replace("\r\n", "\n", $message));

        $type  = mb_strtoupper($type);
        $len   = 2 + mb_strlen($type);
        $free  = $padding - $len;

        if ($free < 4)
        {
            $free    = 4;
            $padding = $len + 4;
        }

        $start = max(1, (int) floor($free / 2));
        $end   = max(1, $free - $start);
        $block = sprintf('<%s> %s </>', $style, $type);

        $this->startBlock();
        $first = (string) array_shift($lines);
        $this->writeln(str_repeat(' ', $start) . $block . str_repeat(' ', $end) . $first);

        foreach ($lines as $line)
        {
            $this->writeln(str_repeat(' ', $padding) . $line);
        }
        $this->newLine();
    }

    protected function startText()
    {
        ((function ()
        {
            $this->autoPrependText();
        })->bindTo($this, SymfonyStyle::class))();
    }

    protected function startBlock()
    {
        ((function ()
        {
            $this->autoPrependBlock();
        })->bindTo($this, SymfonyStyle::class))();
    }

    private static function parseReplacements(array $replacements): array
    {
        foreach ($replacements as $key => $replacement)
        {
            if (is_object($replacement) && ! method_exists($replacement, '__toString'))
            {
                $json = json_encode(
                    $replacement,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );

                if (false !== $json)
                {
                    $replacements[$key] = $json;
                    continue;
                }
            }

            if (is_scalar($replacement) && ! is_string($replacement))
            {
                $replacements[$key] = json_encode($replacement);
                continue;
            }

            $replacements[$key] = (string) $replacement;
        }
        return $replacements;
    }
}
