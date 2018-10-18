<?php

namespace SomethingDigital\UpgradeHelper\Console;

use Magento\Framework\Autoload\AutoloaderRegistry;
use Magento\Framework\ObjectManager\ConfigInterface as ObjectManagerConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeHelperCommand extends Command
{
    /**
     * @var ObjectManagerConfig
     */
    private $objectManagerConfig;

    /**
     * @var AutoloaderInterface
     */
    private $autoloader;

    public function __construct(
        ObjectManagerConfig $objectManagerConfig
    ) {
        parent::__construct(null);

        $this->objectManagerConfig = $objectManagerConfig;
        $this->autoloader = AutoloaderRegistry::getAutoloader();
    }

    protected function configure()
    {
        $this->setName('sd:dev:upgrade-helper');
        $this->setDescription('Helps make the upgrade process more smooth');

        $this->addArgument('diff', InputArgument::REQUIRED, 'Path to diff file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $changedFiles = file($input->getArgument('diff'));
        var_dump($changedFiles);
        $preferences = $this->objectManagerConfig->getPreferences();
        foreach ($preferences as $type => $preference) {
            if (strpos($type, 'Magento') === 0 && strpos($preference, 'Magento') === false) {
                // A <preference> for a Magento\ type that doesn't contain "Magento" in the class name
                echo 'Type: ' . $type . PHP_EOL;
                echo 'Preference: ' . $preference . PHP_EOL;
                echo 'File: ' . $this->autoloader->findFile($type) . PHP_EOL;
                echo '--------' . PHP_EOL;
            }
        }
    }
}
