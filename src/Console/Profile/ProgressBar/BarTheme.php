<?php

declare(strict_types=1);

namespace NGSOFT\Console\Profile\ProgressBar;

class BarTheme
{
    private string $completeFull  = '━';
    private string $completeHalf  = '╸';
    private string $remainingHalf = '╺';
    private string $remainingFull = '━';

    public function getCompleteFull(): string
    {
        return $this->completeFull;
    }

    public function setCompleteFull(string $completeFull): static
    {
        $this->completeFull = $completeFull;
        return $this;
    }

    public function getCompleteHalf(): string
    {
        return $this->completeHalf;
    }

    public function setCompleteHalf(string $completeHalf): static
    {
        $this->completeHalf = $completeHalf;
        return $this;
    }

    public function getRemainingFull(): string
    {
        return $this->remainingFull;
    }

    public function setRemainingFull(string $remainingFull): static
    {
        $this->remainingFull = $remainingFull;
        return $this;
    }

    public function getRemainingHalf(): string
    {
        return $this->remainingHalf;
    }

    public function setRemainingHalf(string $remainingHalf): static
    {
        $this->remainingHalf = $remainingHalf;
        return $this;
    }
}
