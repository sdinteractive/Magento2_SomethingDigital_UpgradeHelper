<?php

namespace SomethingDigital\UpgradeHelper\Console;

use SomethingDigital\UpgradeHelper\Model\FileIndex;
use SomethingDigital\UpgradeHelper\Model\Runner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeHelperCommand extends Command
{
    private $runner;

    private $fileIndex;

    public function __construct(
        Runner $runner,
        FileIndex $fileIndex
    ) {
        parent::__construct(null);
        $this->runner = $runner;
        $this->fileIndex = $fileIndex;
    }

    protected function configure()
    {
        $this->setName('sd:dev:upgrade-helper');
        $this->setDescription('Helps make the upgrade process more smooth');

        $this->addArgument('diff', InputArgument::REQUIRED, 'Path to diff file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $diff = file($input->getArgument('diff'));
        $lines = count($diff);

        $result = [];
        $result['preferences'] = [];
        $result['overrides'] = [];
        $result['email_template'] = [];

        $output->writeln('Populating file override index...');
        $this->fileIndex->populateIndex();

        $progressBar = new ProgressBar($output, $lines);
        $progressBar->setFormat('Lines in diff processed: %current%/%max% [%bar%] %percent:3s%% (elapsed: %elapsed% | remaining: ~%remaining%)');

        foreach ($diff as $line) {
            $progressBar->advance();

            // Extract will populate: $type, $path, $items
            extract($this->runner->run($line));
            if ($type === '') {
                continue;
            }
            $result[$type][$path] = $items;
        }
        $progressBar->finish();
        echo PHP_EOL;

        foreach ($result as $type => $items) {
            $output->writeln('-------- ' . $type . ' --------');
            foreach ($items as $patched => $customized) {
                $output->writeln('Patched: ' . $patched);
                if (is_array($customized)) {
                    foreach ($customized as $customizedFile) {
                        $output->writeln('Customized: ' . $customizedFile);
                    }
                }
            }
        }
        return Command::SUCCESS;
    }
}
