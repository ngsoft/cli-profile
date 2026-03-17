<?php

namespace NGSOFT\Console\Profile\Tailwind;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyleStack;
use Symfony\Component\Console\Formatter\WrappableOutputFormatterInterface;
use Symfony\Component\Console\Helper\Helper;

use function Symfony\Component\String\b;

/**
 * Formatter class for console output.
 * Improved from the symfony/console project to be able to use hexa palette.
 *
 * @example <fg=emerald-500;bg=gainsboro>text</> <bg-emerald-500>text</> <text-sky-500>text</>
 *
 * @see TailwindPalette for available colors
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 * @author Roland Franssen <franssen.roland@gmail.com>
 * @author Aymeric Anger <https://github.com/ngsoft>
 */
final class TailwindOutputFormatter implements WrappableOutputFormatterInterface
{
    private array $styles         = [];
    private OutputFormatterStyleStack $styleStack;

    private static array $palette = TailwindPalette::PALETTE;

    /**
     * Initializes console output formatter.
     *
     * @param OutputFormatterStyleInterface[] $styles Array of "name => FormatterStyle" instances
     */
    public function __construct(
        private bool $decorated = false,
        array $styles = [],
    ) {
        $this->setStyle('error', new OutputFormatterStyle('white', 'red'));
        $this->setStyle('info', new OutputFormatterStyle('green'));
        $this->setStyle('comment', new OutputFormatterStyle('yellow'));
        $this->setStyle('question', new OutputFormatterStyle('black', 'cyan'));

        foreach ($styles as $name => $style)
        {
            $this->setStyle($name, $style);
        }

        $this->styleStack = new OutputFormatterStyleStack();
    }

    public function __clone()
    {
        $this->styleStack = clone $this->styleStack;

        foreach ($this->styles as $key => $value)
        {
            $this->styles[$key] = clone $value;
        }
    }

    /**
     * @param string $name                Color tag
     * @param string $color               Hexa color
     * @param string $backgroundTextColor text color match when using $color as background
     */
    public static function addPalette(string $name, string $color, string $backgroundTextColor = '#fff')
    {
        static $re                        = '~^#[0-9a-f]{3}([0-9a-f]{3}|)$~';

        if ( ! preg_match($re, strtolower($color)))
        {
            throw new \InvalidArgumentException("Invalid color '{$color}'");
        }

        if ( ! preg_match($re, strtolower($backgroundTextColor)))
        {
            throw new \InvalidArgumentException("Invalid color '{$backgroundTextColor}'");
        }

        if (isset(self::$palette[strtolower($name)]))
        {
            throw new \RuntimeException("The color '{$name}' is already defined.");
        }

        self::$palette[strtolower($name)] = [strtolower($color), strtolower($backgroundTextColor)];
    }

    /**
     * Escapes "<" and ">" special chars in given text.
     */
    public static function escape(string $text): string
    {
        $text = preg_replace('/([^\\\]|^)([<>])/', '$1\\\$2', $text);

        return self::escapeTrailingBackslash($text);
    }

    /**
     * Escapes trailing "\" in given text.
     *
     * @internal
     */
    public static function escapeTrailingBackslash(string $text): string
    {
        if (str_ends_with($text, '\\'))
        {
            $len  = \strlen($text);
            $text = rtrim($text, '\\');
            $text = str_replace("\0", '', $text);
            $text .= str_repeat("\0", $len - \strlen($text));
        }

        return $text;
    }

    public function setDecorated(bool $decorated): void
    {
        $this->decorated = $decorated;
    }

    public function isDecorated(): bool
    {
        return $this->decorated;
    }

    public function setStyle(string $name, OutputFormatterStyleInterface $style): void
    {
        $this->styles[strtolower($name)] = $style;
    }

    public function hasStyle(string $name): bool
    {
        return isset($this->styles[strtolower($name)]);
    }

    public function getStyle(string $name): OutputFormatterStyleInterface
    {
        if ( ! $this->hasStyle($name))
        {
            throw new InvalidArgumentException(\sprintf('Undefined style: "%s".', $name));
        }

        return $this->styles[strtolower($name)];
    }

    public function format(?string $message): ?string
    {
        return $this->formatAndWrap($message, 0);
    }

    public function formatAndWrap(?string $message, int $width): string
    {
        if (null === $message)
        {
            return '';
        }

        $offset            = 0;
        $output            = '';
        $openTagRegex      = '[a-z](?:[^\\\<>]*+ | \\\.)*';
        $closeTagRegex     = '[a-z][^<>]*+';
        $currentLineLength = 0;
        preg_match_all("#<(({$openTagRegex}) | /({$closeTagRegex})?)>#ix", $message, $matches, \PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $i => $match)
        {
            $pos    = $match[1];
            $text   = $match[0];

            if (0 != $pos && '\\' == $message[$pos - 1])
            {
                continue;
            }

            // convert byte position to character position.
            $pos    = Helper::length(substr($message, 0, $pos));
            // add the text up to the next tag
            $output .= $this->applyCurrentStyle(Helper::substr($message, $offset, $pos - $offset), $output, $width, $currentLineLength);
            $offset = $pos + Helper::length($text);

            // opening tag?
            if ($open = '/' !== $text[1])
            {
                $tag = $matches[1][$i][0];
            } else
            {
                $tag = $matches[3][$i][0] ?? '';
            }

            if ( ! $open && ! $tag)
            {
                // </>
                $this->styleStack->pop();
            } elseif (null === $style = $this->createStyleFromString($tag))
            {
                $output .= $this->applyCurrentStyle($text, $output, $width, $currentLineLength);
            } elseif ($open)
            {
                $this->styleStack->push($style);
            } else
            {
                $this->styleStack->pop($style);
            }
        }

        $output .= $this->applyCurrentStyle(Helper::substr($message, $offset), $output, $width, $currentLineLength);

        return strtr($output, ["\0" => '\\', '\<' => '<', '\>' => '>']);
    }

    public function getStyleStack(): OutputFormatterStyleStack
    {
        return $this->styleStack;
    }

    /**
     * Tries to create new style instance from string.
     */
    private function createStyleFromString(string $string): ?OutputFormatterStyleInterface
    {
        if (isset($this->styles[$string]))
        {
            return $this->styles[$string];
        }

        $style = new OutputFormatterStyle();

        if (
            preg_match('#^(text-|bg-|)([\w-]+)$#', strtolower($string), $matches)
            && isset(self::$palette[$matches[2]])
        ) {
            if ('bg-' === $matches[1])
            {
                $style->setBackground(self::$palette[$matches[2]][0]);
                $style->setForeground(self::$palette[$matches[2]][1]);
                return $style;
            }

            $style->setForeground(self::$palette[$matches[2]][0]);
            return $style;
        }

        if ( ! preg_match_all('/([^=]+)=([^;]+)(;|$)/', $string, $matches, \PREG_SET_ORDER))
        {
            return null;
        }

        $fg    = null;
        $bg    = null;

        foreach ($matches as $match)
        {
            array_shift($match);
            $match[0] = strtolower($match[0]);

            if ('fg' == $match[0])
            {
                $fg = strtolower($match[1]);

                if (isset(self::$palette[$fg]))
                {
                    $fg = self::$palette[$fg][0];
                }
            } elseif ('bg' == $match[0])
            {
                $bg = strtolower($match[1]);

                if (isset(self::$palette[$bg]))
                {
                    $fg ??= self::$palette[$bg][1];
                    $bg = self::$palette[$bg][0];
                }
            } elseif ('href' === $match[0])
            {
                $url = preg_replace('{\\\([<>])}', '$1', $match[1]);
                $style->setHref($url);
            } elseif ('options' === $match[0])
            {
                preg_match_all('([^,;]+)', strtolower($match[1]), $options);
                $options = array_shift($options);

                foreach ($options as $option)
                {
                    $style->setOption($option);
                }
            } else
            {
                return null;
            }
        }

        if ($fg)
        {
            $style->setForeground($fg);
        }

        if ($bg)
        {
            $style->setBackground($bg);
        }

        return $style;
    }

    /**
     * Applies current style from stack to text, if must be applied.
     */
    private function applyCurrentStyle(string $text, string $current, int $width, int &$currentLineLength): string
    {
        if ('' === $text)
        {
            return '';
        }

        if ( ! $width)
        {
            return $this->isDecorated() ? $this->styleStack->getCurrent()->apply($text) : $text;
        }

        if ( ! $currentLineLength && '' !== $current)
        {
            $text = ltrim($text);
        }

        if ($currentLineLength)
        {
            $lines  = explode("\n", $text, 2);
            $prefix = Helper::substr($lines[0], 0, $i = $width - $currentLineLength) . "\n";
            $text   = Helper::substr($lines[0], $i);

            if (isset($lines[1]))
            {
                // $prefix may contain the full first line in which the \n is already a part of $prefix.
                if ('' !== $text)
                {
                    $text .= "\n";
                }

                $text .= $lines[1];
            }
        } else
        {
            $prefix = '';
        }

        preg_match('~(\n)$~', $text, $matches);
        $text  = $prefix . $this->addLineBreaks($text, $width);
        $text  = rtrim($text, "\n") . ($matches[1] ?? '');

        if ( ! $currentLineLength && '' !== $current && ! str_ends_with($current, "\n"))
        {
            $text = "\n" . $text;
        }

        $lines = explode("\n", $text);

        foreach ($lines as $i => $line)
        {
            $currentLineLength = 0 === $i ? $currentLineLength + Helper::length($line) : Helper::length($line);

            if ($width <= $currentLineLength)
            {
                $currentLineLength = 0;
            }
        }

        if ($this->isDecorated())
        {
            foreach ($lines as $i => $line)
            {
                $lines[$i] = $this->styleStack->getCurrent()->apply($line);
            }
        }

        return implode("\n", $lines);
    }

    private function addLineBreaks(string $text, int $width): string
    {
        $encoding = mb_detect_encoding($text, null, true) ?: 'UTF-8';

        return b($text)->toUnicodeString($encoding)->wordwrap($width, "\n", true)->toByteString($encoding);
    }
}
