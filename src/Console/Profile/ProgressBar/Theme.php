<?php

declare(strict_types=1);

namespace NGSOFT\Console\Profile\ProgressBar;

use Symfony\Component\Console\Color;

class Theme
{
    private BarTheme $barTheme;
    private Color $remainingStyle;
    private Color $progressStyle;
    private Color $completeStyle;

    public function __construct()
    {
        $this->remainingStyle = new Color('#3a3a3a');
        $this->progressStyle  = new Color('#ec4899');
        $this->completeStyle  = new Color('#10b981');
        $this->barTheme       = new BarTheme();
    }

    public function getBarTheme(): BarTheme
    {
        return $this->barTheme;
    }

    public function setBarTheme(BarTheme $barTheme): static
    {
        $this->barTheme = $barTheme;
        return $this;
    }

    public function getRemainingStyle(): Color
    {
        return $this->remainingStyle;
    }

    public function setRemainingStyle(Color $remainingStyle): static
    {
        $this->remainingStyle = $remainingStyle;
        return $this;
    }

    public function getProgressStyle(): Color
    {
        return $this->progressStyle;
    }

    public function setProgressStyle(Color $progressStyle): static
    {
        $this->progressStyle = $progressStyle;
        return $this;
    }

    public function getCompleteStyle(): Color
    {
        return $this->completeStyle;
    }

    public function setCompleteStyle(Color $completeStyle): static
    {
        $this->completeStyle = $completeStyle;
        return $this;
    }
}
