<?php

namespace SciloneToolboxBundle\Symfony\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractCommand extends Command
{
    protected const MEMORY_USAGE_WARNING = 70;

    protected ?SymfonyStyle $io = null;
    protected ?InputInterface $input = null;
    protected ?OutputInterface $output = null;
    protected ?ProgressBar $progressBar = null;
    protected int $start = 0;

    public function __construct(protected readonly LoggerInterface $logger)
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->io     = new SymfonyStyle($input, $output);
        $this->input  = $input;
        $this->output = $output;

        $this->start = time();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info(
            'Start script : {command}',
            [
                'command'   => $this->getName(),
                'arguments' => $input->getArguments(),
                'options'   => $input->getOptions(),
            ]
        );

        $exitCode = $this->doExecute();
        $this->finally($exitCode);
        $durationScript = time() - $this->start;

        $this->logger->info(
            'Script ended in ' . $this->convertSecondsToHumanReadableTime($durationScript) .
            ' with peak memory usage to ' . $this->convertToHumanReadableSize(memory_get_peak_usage(true)) .
            ' : {command}',
            ['command' => $this->getName()]
        );

        $percentMemoryUsed = round(memory_get_peak_usage(true)*100/$this->getMemoryLimit());
        if ($percentMemoryUsed > static::MEMORY_USAGE_WARNING) {
            $this->logger->warning(
                'Script {command} use more than 70% of memory allowed {percent_memory_used}%',
                [
                    'command'             => $this->getName(),
                    'percent_memory_used' => $percentMemoryUsed,
                ]
            );
        }

        if ($exitCode !== self::SUCCESS) {
            $this->logger->error(
                'Script exit with an error code({exit_code}) : {command}',
                [
                    'command'   => $this->getName(),
                    'exit_code' => $exitCode,
                ]
            );
        }

        return $exitCode;
    }

    protected function convertSecondsToHumanReadableTime(float $time): string
    {
        if ($time < 1) {
            return sprintf("%.2f secondes", $time);
        }

        $days = floor($time / 86400);
        $time -= $days * 86400;
        $hours = floor($time / 3600);
        $time -= $hours * 3600;
        $minutes = floor($time / 60);
        $seconds = $time % 60;

        $timeFormat = '';
        if ($days > 0) {
            $timeFormat .= sprintf('%d days ', $days);
        }
        if ($hours > 0) {
            $timeFormat .= sprintf('%d hours ', $hours);
        }
        if ($minutes > 0) {
            $timeFormat .= sprintf('%d minutes ', $minutes);
        }
        $timeFormat .= sprintf("%.2f secondes", $seconds);

        return $timeFormat;
    }

    protected function convertToHumanReadableSize(int $size): string
    {
        if ($size < 0) {
            return 'Invalid Size';
        }

        $units = ['b','kb','mb','gb','tb','pb'];
        $power = $size > 0 ? floor(log($size, 1024)) : 0;

        return round($size / (1024 ** $power), 2) . ' ' . $units[$power];
    }

    protected function getMemoryLimit(): int
    {
        $units = array('K' => 1, 'M' => 2, 'G' => 3);
        $memoryLimit = ini_get('memory_limit');

        if (preg_match('/^(\d+)([' . implode('', array_keys($units)) . '])$/i', $memoryLimit, $matches)) {
            return $matches[1] * pow(1024, $units[strtoupper($matches[2])]);
        }

        return $memoryLimit;
    }

    protected function progressStart(int $max = 0): void
    {
        $this->progressBar = new ProgressBar($this->output->section(), $max);
        $this->progressBar->start();
    }

    protected function progressSetMaxSteps(int $max = 0): void
    {
        $this->progressBar->setMaxSteps($max);
    }

    protected function progressAdvance(int $step = 1): void
    {
        $this->progressBar->advance($step);
    }

    protected function progressFinish(int $line = 1): void
    {
        $this->progressBar->finish();
        $this->io->newLine($line);
    }

    protected function isVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    protected function getOption($name)
    {
        return $this->input->getOption($name);
    }

    protected function getArgument($name)
    {
        return $this->input->getArgument($name);
    }

    protected function finally(int $exitCode): void
    {
        //do something
    }

    abstract protected function doExecute(): int;
}
