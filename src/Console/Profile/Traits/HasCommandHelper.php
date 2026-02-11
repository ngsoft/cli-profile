<?php

declare(strict_types=1);

namespace NGSOFT\Console\Profile\Traits;

use NGSOFT\Console\Profile\CommandHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait HasCommandHelper
{
    private InputInterface $input;
    private OutputInterface $output;
    private ?CommandHelper $commandHelper = null;

    #[Required]
    public function setInput(ArgvInput $input): void
    {
        $this->input = $input;
    }

    #[Required]
    public function setOutput(ConsoleOutput $output): void
    {
        $this->output = $output;
    }

    public function getCommandHelper(): CommandHelper
    {
        return $this->commandHelper ??= new CommandHelper($this->input, $this->output);
    }
}
