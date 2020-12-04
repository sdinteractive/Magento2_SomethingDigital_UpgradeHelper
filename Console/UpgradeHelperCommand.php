<?php

namespace SomethingDigital\UpgradeHelper\Console;

use SomethingDigital\UpgradeHelper\Model\Runner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeHelperCommand extends Command
{
    private $runner;

    public function __construct(
        Runner $runner
    ) {
        parent::__construct(null);
        $this->runner = $runner;
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
        $result = $this->runner->run($diff);

        foreach ($result as $type => $items) {
            $output->writeln('-------- ' . $type . ' --------');
            foreach ($items as $patched => $customized) {
                $output->writeln('Patched: ' . $patched);
                if (is_array($customized)) {
                    foreach ($customized as $customizedFiile) {
                        $output->writeln('Customized: ' . $customizedFile);
                    }
                }
            }
        }
    }
}
