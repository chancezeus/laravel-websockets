<?php

namespace BeyondCode\LaravelWebSockets\Server\Logger;

use Illuminate\Support\Facades\App;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

class Logger
{
    /** @var \Symfony\Component\Console\Output\OutputInterface */
    protected $consoleOutput;

    /** @var bool */
    protected $enabled = false;

    /** @var bool */
    protected $verbose = false;

    /**
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return App::make(WebsocketsLogger::class)->enabled;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $consoleOutput
     */
    public function __construct(OutputInterface $consoleOutput)
    {
        $this->consoleOutput = $consoleOutput;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function enable($enabled = true): Logger
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @param bool $verbose
     * @return $this
     */
    public function verbose($verbose = false): Logger
    {
        $this->verbose = $verbose;

        return $this;
    }

    /**
     * @param string $message
     */
    protected function info(string $message): void
    {
        $this->line($message, 'info');
    }

    /**
     * @param string $message
     */
    protected function warn(string $message): void
    {
        if (! $this->consoleOutput->getFormatter()->hasStyle('warning')) {
            $style = new OutputFormatterStyle('yellow');

            $this->consoleOutput->getFormatter()->setStyle('warning', $style);
        }

        $this->line($message, 'warning');
    }

    /**
     * @param string $message
     */
    protected function error(string $message): void
    {
        $this->line($message, 'error');
    }

    /**
     * @param string $message
     * @param string $style
     */
    protected function line(string $message, string $style): void
    {
        $styled = $style ? "<$style>$message</$style>" : $message;

        $this->consoleOutput->writeln($styled);
    }
}
