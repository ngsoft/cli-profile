<?php

declare(strict_types=1);

namespace NGSOFT\Console\Profile\ProgressBar;

use NGSOFT\Console\Profile\ProgressBar;

readonly class ProgressBarEventDetail implements \JsonSerializable
{
    public int $value;
    public int $total;
    public int $percent;
    public float $elapsed;
    public bool $finished;
    public ProgressBar $progressBar;

    public function __construct(array $details)
    {
        foreach ($details as $key => $value)
        {
            if (property_exists($this, $key))
            {
                $this->{$key} = $value;
            }
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPercent(): int
    {
        return $this->percent;
    }

    public function getElapsed(): float
    {
        return $this->elapsed;
    }

    public function getProgressBar(): ProgressBar
    {
        return $this->progressBar;
    }

    public function jsonSerialize(): array
    {
        return [
            'value'    => $this->value,
            'total'    => $this->total,
            'percent'  => $this->percent,
            'elapsed'  => $this->elapsed,
            'finished' => $this->finished,
        ];
    }
}
