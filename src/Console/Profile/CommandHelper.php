<?php

declare(strict_types=1);

namespace NGSOFT\Console\Profile;

use NGSOFT\Console\Version;
use Symfony\Component\Console\Style\SymfonyStyle;

class CommandHelper extends SymfonyStyle implements Version
{
    private $startText  = null;

    private $startBlock = null;

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
        if ( ! empty($replacements))
        {
            $message = $this::str_format($message, $replacements);
        }

        $this->writeln($message);
    }

    public function err(string|\Stringable $message, mixed ...$replacements): void
    {
        if ( ! empty($replacements))
        {
            $message = $this::str_format($message, $replacements);
        }
        $this->getErrorOutput()->writeln($message);
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

    public function infoMessage(string|\Stringable $message, mixed ...$replacements): void
    {
        $this->writeln(
            '  <bg=blue;fg=white> INFO </> '
            . $this::str_format((string) $message, $replacements)
        );
    }

    public function warningMessage(string|\Stringable $message, mixed ...$replacements): void
    {
        $this->writeln(
            '  <bg=yellow;fg=black> WARNING </> '
            . $this::str_format((string) $message, $replacements)
        );
    }

    public function errorMessage(string|\Stringable $message, mixed ...$replacements): void
    {
        $this->writeln(
            '  <bg=red;fg=white> ERROR </> '
            . $this::str_format((string) $message, $replacements)
        );
    }

    public function info(array|string $message): void
    {
        $this->block($message, 'INFO', 'fg=cyan');
    }

    public function warning(array|string $message): void
    {
        $this->block($message, 'WARNING', 'fg=yellow');
    }

    public function error(array|string $message): void
    {
        $this->block($message, 'ERROR', 'fg=red');
    }

    protected function startText()
    {
        ($this->startText ??= (function ()
        {
            $this->autoPrependText();
        })->bindTo($this, SymfonyStyle::class))();
    }

    protected function startBlock()
    {
        ($this->startBlock ??= (function ()
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
                }
            }
        }
        return $replacements;
    }
}
