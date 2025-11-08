<?php

/** @noinspection PhpUnusedPrivateFieldInspection */

declare(strict_types=1);

namespace NGSOFT\Console\Profile;

use DataStructure\Map;
use NGSOFT\Console\Profile\ProgressBar\ProgressBarDefaultHandler;
use NGSOFT\Console\Profile\ProgressBar\ProgressBarEventDetail;
use NGSOFT\Console\Profile\ProgressBar\ProgressBarHandlerInterface;
use NGSOFT\Console\Profile\ProgressBar\Theme;
use Observable\Event;
use Observable\EventDispatcher;
use Symfony\Component\Console\Output\OutputInterface;

class ProgressBar implements \Stringable, \IteratorAggregate
{
    public const EVENT_PROGRESS   = 'progress:progress';
    public const EVENT_START      = 'progress:start';
    public const EVENT_COMPLETE   = 'progress:complete';
    public const DEFAULT_TEMPLATE = "\r<progress:label:start> <progress:bar> <progress:indicator> <progress:label:end>";
    private const CURSOR_HIDE     = "\x1b[?25l";
    private const CURSOR_SHOW     = "\x1b[?25h";

    private const CURSOR_UP       = "\x1b[1A";
    private const CURSOR_DOWN     = "\x1b[1B";

    /** @var ProgressBarHandlerInterface[] */
    private $handlers             = [];

    /** @var Map<string,ProgressBarHandlerInterface> */
    private Map $tags;

    private Theme $theme;

    private EventDispatcher $eventDispatcher;

    private int $total            = 100;
    private int $value            = -1;
    private bool $finished        = false;
    private bool $started         = false;
    private float $startTime      = 0.0;
    private float $endTime        = 0.0;
    private int $length           = 20;
    private string $template      = self::DEFAULT_TEMPLATE;

    private bool $hideOnFinished  = false;

    private string $startLabel    = '';
    private string $endLabel      = '';

    private string $indicator     = '<purple-500><progress:percent>%</> • <sky-500><progress:time></>';
    private string $content       = '';
    private bool $redraw          = false;

    public function __construct(private readonly OutputInterface $output)
    {
        $this->tags            = new Map();
        $this->theme           = new Theme();
        $this->eventDispatcher = new EventDispatcher();
        $this->addProgressHandler(new ProgressBarDefaultHandler());
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Access ConsoleOutput.
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * Listen to start Events.
     *
     * @param callable(Event):void $listener
     *
     * @return $this
     */
    public function onStart(callable $listener): static
    {
        $this->eventDispatcher->addEventListener(self::EVENT_START, $listener);
        return $this;
    }

    /**
     * @param callable(Event):void $listener
     *
     * @return $this
     */
    public function onComplete(callable $listener): static
    {
        $this->eventDispatcher->addEventListener(self::EVENT_COMPLETE, $listener);
        return $this;
    }

    /**
     * @param callable(Event):void $listener
     *
     * @return $this
     */
    public function onProgress(callable $listener): static
    {
        $this->eventDispatcher->addEventListener(self::EVENT_PROGRESS, $listener);
        return $this;
    }

    /**
     * Add Custom progress handler.
     *
     * @param ProgressBarHandlerInterface $handler
     *
     * @return $this
     */
    public function addProgressHandler(ProgressBarHandlerInterface $handler): static
    {
        foreach ($handler->handles() as $tag)
        {
            $this->tags->add(trim($tag, '<>'), $handler);
        }

        return $this;
    }

    /**
     * Theme accessor.
     *
     * @return Theme
     */
    public function getTheme(): Theme
    {
        return $this->theme;
    }

    /**
     * Override Theme.
     *
     * @param Theme $theme
     *
     * @return static
     */
    public function setTheme(Theme $theme): static
    {
        $this->theme = $theme;
        return $this;
    }

    /**
     * Label before bar.
     */
    public function getStartLabel(): string
    {
        return $this->startLabel;
    }

    /**
     * change label before bar.
     */
    public function setStartLabel(string $startLabel, bool $redraw = false): static
    {
        $this->startLabel = $startLabel;

        $redraw && $this->redraw();
        return $this;
    }

    /**
     * label after bar.
     */
    public function getEndLabel(): string
    {
        return $this->endLabel;
    }

    /**
     * set label after bar indicator.
     */
    public function setEndLabel(string $endLabel, bool $redraw = false): static
    {
        $this->endLabel = $endLabel;

        $redraw && $this->redraw();

        return $this;
    }

    /**
     * get indicator template.
     */
    public function getIndicator(): string
    {
        return $this->indicator;
    }

    /**
     * change indicator template.
     */
    public function setIndicator(string $indicator, bool $redraw = false): static
    {
        $this->indicator = $indicator;
        $redraw && $this->redraw();
        return $this;
    }

    /**
     * Change template.
     */
    public function setTemplate(string $template, bool $redraw = false): static
    {
        $this->template = $template;
        $redraw && $this->redraw();
        return $this;
    }

    /**
     * Change bar length (default: 20)
     * Must be an even number.
     */
    public function setLength(int $length): static
    {
        if (0 !== $length % 2)
        {
            ++$length;
        }
        $this->length = max(4, $length);
        return $this;
    }

    /**
     * Set progress new value.
     */
    public function setValue(int $value): static
    {
        if ( ! $this->finished)
        {
            if ($this->total <= $value)
            {
                $this->value = $this->total;
                // we draw a step before drawing complete
                $this->update();
                return $this->setComplete();
            }
            $this->value = $value;
            $this->update();
        }

        return $this;
    }

    /**
     * Override total
     * and resets progress.
     */
    public function setTotal(int $total): static
    {
        if ( ! $this->finished)
        {
            $this->total   = max($total, 1);
            $this->value   = -1;
            $this->started = false;
        }

        return $this;
    }

    public function setHideOnFinished(bool $hideOnFinished): static
    {
        $this->hideOnFinished = $hideOnFinished;
        return $this;
    }

    public function isHiddenOnFinished(): bool
    {
        return $this->hideOnFinished;
    }

    /**
     * get total value.
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    public function increment(int $value = 1): static
    {
        return $this->setValue($this->value + abs($value));
    }

    /**
     * get current value.
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Set the progress bar to finish.
     */
    public function setComplete(): static
    {
        if ( ! $this->finished)
        {
            $this->value = $this->total;
            $this->update(true);

            if ($this->hideOnFinished)
            {
                // 500 ms
                usleep(500000);
                $this->output->write($this->clearLine() . self::CURSOR_UP);
            }
        }

        return $this;
    }

    /**
     * checks if progress is finished.
     */
    public function isFinished(): bool
    {
        return $this->finished;
    }

    /**
     * checks if progress has been started.
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * Bar Length.
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * percentage from 1 to 100.
     *
     * @return int
     */
    public function getPercent(): int
    {
        if ($this->value <= 0)
        {
            return 0;
        }
        return min(intval(($this->value / $this->total) * 100), 100);
    }

    /**
     * Elapsed time.
     */
    public function getElapsedTime(bool $asString = false): float|string
    {
        $value = 0.0;

        if ($this->finished)
        {
            $value = $this->endTime;
        } elseif ($this->started)
        {
            $value = round(microtime(true) - $this->startTime, 6);
        }

        if ($asString)
        {
            if (0.0 === $value)
            {
                return '00:00';
            }
            $sec     = $value;
            $hours   = floor($sec / 3600);
            $minutes = floor(($sec % 3600) / 60);
            $sec     = $sec % 60;
            return ltrim(ltrim(
                sprintf(
                    '%02d:%02d:%02d',
                    $hours,
                    $minutes,
                    $sec
                ),
                '0'
            ), ':');
        }

        return $value;
    }

    /**
     * @return \Traversable<ProgressBarEventDetail>
     */
    public function getIterator(): \Traversable
    {
        while ( ! $this->finished)
        {
            $this->increment();
            yield $this->getEventDetails();
        }
    }

    private function redraw(): void
    {
        if ($this->started)
        {
            $this->redraw = true;
            $this->update();
        }
    }

    private function clearLine(): string
    {
        return "\r\x1b[2K\r";
    }

    private function render(): string
    {
        if ( ! empty($this->content) || ! $this->started)
        {
            return $this->content;
        }
        $instance = $this;
        $handlers = $this->tags;
        $tags     = [...$handlers->keys()];
        $re       = sprintf('#<(%s)>#', implode('|', $tags));

        $result   = $this->template;

        while (0 < preg_match($re, $result))
        {
            $result = preg_replace_callback(
                $re,
                fn (array $matches) => ($handlers->get($matches[1]))($matches[0], $instance),
                $result
            );
        }

        return $result;
    }

    private function update(bool $finished = false): void
    {
        if ($this->finished || -1 === $this->value)
        {
            return;
        }

        $prepend      = $append = $this->content = '';
        $trigger      = null;

        if ( ! $this->redraw)
        {
            $trigger = self::EVENT_PROGRESS;
        }

        $this->redraw = false;

        if ( ! $this->started)
        {
            $this->started   = true;
            $this->startTime = microtime(true);
            $prepend .= "\n" . self::CURSOR_HIDE;
            $trigger         = self::EVENT_START;
        }

        $prepend .= $this->clearLine();

        if ($finished)
        {
            $this->endTime  = $this->getElapsedTime();
            $this->finished = true;
            $trigger        = self::EVENT_COMPLETE;

            if ( ! $this->hideOnFinished)
            {
                $append .= "\n";
            }

            $append .= self::CURSOR_SHOW;
        }

        // render the progress bar
        $this->output->write($prepend . ($this->content = $this->render()) . $append);

        if ($trigger)
        {
            $details = $this->getEventDetails();

            $this->eventDispatcher->dispatchEvent(Event::newEvent($trigger, $details));

            if (self::EVENT_START === $trigger && $this->value > 0)
            {
                $this->eventDispatcher->dispatchEvent(Event::newEvent(self::EVENT_PROGRESS, $details));
            }
        }
    }

    private function getEventDetails(): ProgressBarEventDetail
    {
        return new ProgressBarEventDetail([
            'value'       => $this->value,
            'total'       => $this->total,
            'percent'     => $this->getPercent(),
            'finished'    => $this->finished,
            'elapsed'     => $this->getElapsedTime(),
            'progressBar' => $this,
        ]);
    }
}
