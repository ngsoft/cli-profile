<?php

declare(strict_types=1);

namespace NGSOFT\Console\Profile\ProgressBar;

use NGSOFT\Console\Profile\ProgressBar;

class ProgressBarDefaultHandler implements ProgressBarHandlerInterface
{
    public const TAG_LABEL_START = '<progress:label:start>';
    public const TAG_LABEL_END   = '<progress:label:end>';
    public const TAG_BAR         = '<progress:bar>';
    public const TAG_INDICATOR   = '<progress:indicator>';
    public const TAG_TIME        = '<progress:time>';
    public const TAG_PERCENT     = '<progress:percent>';
    public const TAG_VALUE       = '<progress:value>';
    public const TAG_TOTAL       = '<progress:total>';

    public function __invoke(string $tag, ProgressBar $progressBar): string
    {
        return match ($tag)
        {
            self::TAG_VALUE       => $this->handleValue($progressBar),
            self::TAG_TOTAL       => (string) $progressBar->getTotal(),
            self::TAG_LABEL_START => $progressBar->getStartLabel(),
            self::TAG_LABEL_END   => $progressBar->getEndLabel(),
            self::TAG_INDICATOR   => $progressBar->getIndicator(),
            self::TAG_TIME        => $progressBar->getElapsedTime(true),
            self::TAG_PERCENT     => $this->handlePercentage($progressBar),
            default               => $this->handleBar($progressBar),
        };
    }

    public function handles(): array
    {
        return [
            self::TAG_LABEL_START,
            self::TAG_LABEL_END,
            self::TAG_BAR,
            self::TAG_INDICATOR,
            self::TAG_TIME,
            self::TAG_PERCENT,
            self::TAG_VALUE,
            self::TAG_TOTAL,
        ];
    }

    private function handleValue(ProgressBar $progressBar): string
    {
        $len = strlen((string) $progressBar->getTotal());
        return sprintf("%{$len}d", $progressBar->getValue());
    }

    private function handleBar(ProgressBar $progressBar): string
    {
        $length    = $progressBar->getLength();
        $theme     = $progressBar->getTheme();
        $parts     = $theme->getBarTheme();

        if ($progressBar->isFinished())
        {
            return $theme->getCompleteStyle()->apply(str_repeat($parts->getCompleteFull(), $length));
        }
        $complete  = (int) floor(intval($length * 2 * $progressBar->getValue() / $progressBar->getTotal()) / 2);
        $half      = 1 === $complete % 2;
        $remaining = $length - $complete - 1;
        $progress  = '';

        if ($complete > 0)
        {
            $progress .= $theme->getProgressStyle()->apply(str_repeat($parts->getCompleteFull(), $complete));
        }

        if ($half)
        {
            $progress .= $theme->getProgressStyle()->apply($parts->getCompleteHalf());
        } else
        {
            $progress .= $theme->getRemainingStyle()->apply($parts->getRemainingHalf());
        }

        if ($remaining > 0)
        {
            $progress .= $theme->getRemainingStyle()->apply(str_repeat($parts->getRemainingFull(), $remaining));
        }

        return $progress;
    }

    private function handlePercentage(ProgressBar $progressBar): string
    {
        return substr(sprintf('  %d', $progressBar->getPercent()), -3);
    }
}
