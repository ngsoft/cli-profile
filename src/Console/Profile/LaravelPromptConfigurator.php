<?php

/** @noinspection PhpSameParameterValueInspection */

namespace NGSOFT\Console\Profile;

use Laravel\Prompts\ConfirmPrompt;
use Laravel\Prompts\MultiSearchPrompt;
use Laravel\Prompts\MultiSelectPrompt;
use Laravel\Prompts\PasswordPrompt;
use Laravel\Prompts\PausePrompt;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\SearchPrompt;
use Laravel\Prompts\SelectPrompt;
use Laravel\Prompts\SuggestPrompt;
use Laravel\Prompts\TextareaPrompt;
use Laravel\Prompts\TextPrompt;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

readonly class LaravelPromptConfigurator
{
    public function __construct(
        private CommandHelper $commandHelper
    ) {
        $this->configurePrompts();
    }

    /**
     * Configure the prompt fallbacks.
     */
    private function configurePrompts()
    {
        static $once = false;

        if ($once)
        {
            return;
        }
        $once        = true;

        Prompt::setOutput($this->commandHelper->getOutput());
        Prompt::interactive($this->commandHelper->getInput()->isInteractive() && defined('STDIN') && stream_isatty(STDIN));
        Prompt::fallbackWhen('Windows' === PHP_OS_FAMILY);
        TextPrompt::fallbackUsing(fn (TextPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->ask($prompt->label, $prompt->default ?: null) ?? '',
            $prompt->required,
            $prompt->validate
        ));

        TextareaPrompt::fallbackUsing(fn (TextareaPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->ask($prompt->label, $prompt->default ?: null, multiline: true) ?? '',
            $prompt->required,
            $prompt->validate
        ));

        PasswordPrompt::fallbackUsing(fn (PasswordPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->secret($prompt->label) ?? '',
            $prompt->required,
            $prompt->validate
        ));

        PausePrompt::fallbackUsing(fn (PausePrompt $prompt) => $this->promptUntilValid(
            function () use ($prompt)
            {
                $this->ask($prompt->message, $prompt->value());

                return $prompt->value();
            },
            $prompt->required,
            $prompt->validate
        ));

        ConfirmPrompt::fallbackUsing(fn (ConfirmPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->confirm($prompt->label, $prompt->default),
            $prompt->required,
            $prompt->validate
        ));

        SelectPrompt::fallbackUsing(fn (SelectPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->selectFallback($prompt->label, $prompt->options, $prompt->default),
            false,
            $prompt->validate
        ));

        MultiSelectPrompt::fallbackUsing(fn (MultiSelectPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->multiselectFallback($prompt->label, $prompt->options, $prompt->default, $prompt->required),
            $prompt->required,
            $prompt->validate
        ));

        SuggestPrompt::fallbackUsing(fn (SuggestPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->askWithCompletion($prompt->label, $prompt->options, $prompt->default ?: null) ?? '',
            $prompt->required,
            $prompt->validate
        ));

        SearchPrompt::fallbackUsing(fn (SearchPrompt $prompt) => $this->promptUntilValid(
            function () use ($prompt)
            {
                $query   = $this->ask($prompt->label);

                $options = ($prompt->options)($query);

                return $this->selectFallback($prompt->label, $options);
            },
            false,
            $prompt->validate
        ));

        MultiSearchPrompt::fallbackUsing(fn (MultiSearchPrompt $prompt) => $this->promptUntilValid(
            function () use ($prompt)
            {
                $query   = $this->ask($prompt->label);

                $options = ($prompt->options)($query);

                return $this->multiselectFallback($prompt->label, $options, required: $prompt->required);
            },
            $prompt->required,
            $prompt->validate
        ));
    }

    private function getChoiceQuestion($question, $choices, $default): ChoiceQuestion
    {
        return new class($question, $choices, $default) extends ChoiceQuestion
        {
            protected function isAssoc(array $array): bool
            {
                return ! array_is_list($array);
            }
        };
    }

    /**
     * Eventually performs a question using the component's question helper.
     *
     * @param callable $callable
     *
     * @return mixed
     */
    private function usingQuestionHelper($callable)
    {
        $property      = (new \ReflectionClass(CommandHelper::class))
            ->getParentClass()
            ->getProperty('questionHelper');

        $currentHelper = $property->isInitialized($this->commandHelper)
            ? $property->getValue($this->commandHelper)
            : new SymfonyQuestionHelper();

        $property->setValue($this->commandHelper, new SymfonyQuestionHelper());

        try
        {
            return $callable();
        } finally
        {
            $property->setValue($this->commandHelper, $currentHelper);
        }
    }

    /**
     * Prompt the user until the given validation callback passes.
     *
     * @template PResult
     *
     * @param \Closure(): PResult             $prompt
     * @param bool|string                     $required
     * @param null|(\Closure(PResult): mixed) $validate
     *
     * @return PResult
     */
    private function promptUntilValid($prompt, $required, $validate)
    {
        while (true)
        {
            $result = $prompt();

            if ($required && ('' === $result || [] === $result || false === $result))
            {
                $this->commandHelper->error(is_string($required) ? $required : 'Required.');
                continue;
            }

            $error  = is_callable($validate) ? $validate($result) : null;

            if (is_string($error) && strlen($error) > 0)
            {
                $this->commandHelper->error($error);
                continue;
            }

            return $result;
        }
    }

    private function askWithCompletion($question, $choices, $default = null)
    {
        $question = new Question($question, $default);

        is_callable($choices)
            ? $question->setAutocompleterCallback($choices)
            : $question->setAutocompleterValues($choices);

        return $this->usingQuestionHelper(
            fn () => $this->commandHelper->askQuestion($question)
        );
    }

    private function confirm($question, $default = false)
    {
        return $this->usingQuestionHelper(
            fn () => $this->commandHelper->confirm($question, $default),
        );
    }

    /** @noinspection PhpSameParameterValueInspection */
    private function choice($question, $choices, $default = null, $attempts = null, $multiple = false)
    {
        return $this->usingQuestionHelper(
            fn () => $this->commandHelper->askQuestion(
                $this->getChoiceQuestion($question, $choices, $default)
                    ->setMaxAttempts($attempts)
                    ->setMultiselect($multiple)
            ),
        );
    }

    private function secret($question, $fallback = true)
    {
        $question = new Question($question);
        $question->setHidden(true)->setHiddenFallback($fallback);

        return $this->usingQuestionHelper(fn () => $this->commandHelper->askQuestion($question));
    }

    private function ask($question, $default = null, $multiline = false)
    {
        return $this->usingQuestionHelper(
            fn () => $this->commandHelper->askQuestion(
                (new Question($question, $default))
                    ->setMultiline($multiline)
            )
        );
    }

    /**
     * Select fallback.
     *
     * @param string                   $label
     * @param array<array-key, string> $options
     * @param null|int|string          $default
     *
     * @return int|string
     */
    private function selectFallback($label, $options, $default = null)
    {
        $answer = $this->choice($label, $options, $default);

        if ( ! array_is_list($options) && $answer === (string) (int) $answer)
        {
            return (int) $answer;
        }

        return $answer;
    }

    /**
     * Multi-select fallback.
     *
     * @param string      $label
     * @param array       $options
     * @param array       $default
     * @param bool|string $required
     *
     * @return array
     */
    private function multiselectFallback($label, $options, $default = [], $required = false)
    {
        $default = [] !== $default ? implode(',', $default) : null;

        if (false === $required)
        {
            $options = array_is_list($options)
                ? ['None', ...$options]
                : ['' => 'None'] + $options;

            if (null === $default)
            {
                $default = 'None';
            }
        }

        $answers = $this->choice($label, $options, $default, null, true);

        if ( ! array_is_list($options))
        {
            $answers = array_map(fn ($value) => $value === (string) (int) $value ? (int) $value : $value, $answers);
        }

        if (false === $required)
        {
            return array_is_list($options)
                ? array_values(array_filter($answers, fn ($value) => 'None' !== $value))
                : array_filter($answers, fn ($value) => '' !== $value);
        }

        return $answers;
    }
}
