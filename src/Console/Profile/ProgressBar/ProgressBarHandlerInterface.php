<?php

declare(strict_types=1);

namespace NGSOFT\Console\Profile\ProgressBar;

use NGSOFT\Console\Profile\ProgressBar;

interface ProgressBarHandlerInterface
{
    /**
     * Called by progress bar.
     *
     * @param string      $tag         <tag>
     * @param ProgressBar $progressBar
     *
     * @return string
     */
    public function __invoke(string $tag, ProgressBar $progressBar): string;

    /**
     * @return string[] a list of tags to manage
     */
    public function handles(): array;
}
