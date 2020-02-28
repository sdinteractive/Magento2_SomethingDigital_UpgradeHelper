<?php

namespace SomethingDigital\UpgradeHelper\Console;

use SomethingDigital\UpgradeHelper\Model\Runner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;

class UpgradeHelperCommand extends Command
{
    private $runner;
    private $csvProcessor;
    private $filesystem;
    private $directoryList;

    public function __construct(
        Runner $runner,
        Csv $csvProcessor,
        DirectoryList $directoryList,
        Filesystem $filesystem
    ) {
        parent::__construct(null);
        $this->runner = $runner;
        $this->csvProcessor = $csvProcessor;
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
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

        $fileDirectoryPath = $this->directoryList->getPath(DirectoryList::ROOT);
        $fileName = 'UpgradeHelperResult_'.date('m-d-Y').'.csv';
        $filePath = $fileDirectoryPath . '/' . $fileName;
        $data['headers'] = ['Patched', 'Customized', 'Reviewed (Yes / No)', 'Notes'];

        foreach ($result as $type => $items) {
            $output->writeln('-------- ' . $type . ' --------');
            foreach ($items as $patched => $customized) {
                $data[] = [$patched, $customized];
                $output->writeln('Patched: ' . $patched);
                $output->writeln('Customized: ' . $customized);
            }
        }
        $this->csvProcessor
            ->setEnclosure('"')
            ->setDelimiter(',')
            ->saveData($filePath, $data);
    }
}
