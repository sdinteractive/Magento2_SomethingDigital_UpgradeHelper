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
        $report = [];
        $report['PREFERENCES'] = [];
        $report['TEMPLATES'] = [];

        // Build an array of preferences...
        $preferences = $this->objectManagerConfig->getPreferences();
        $preferenceArray = [];
        foreach ($preferences as $type => $preference) {
            if (strpos($type, 'Magento') === 0 && strpos($preference, 'Magento') === false) {
                // A <preference> for a Magento\ type that doesn't contain "Magento" in the class name
                $absoluteFile = realpath($this->autoloader->findFile($type));
                $start = strpos($absoluteFile, 'vendor');
                $file = substr($absoluteFile, $start);
                $preferenceArray[$file] = $preference;
            }
        }

        $diff = file($input->getArgument('diff'));
        foreach ($diff as $line) {
            if (strpos($line, 'diff -r') !== 0) {
                continue;
            }
            // Is there are preferences?
            $start = strpos($line, 'vendor');
            $end = strpos($line, ' ', $start);
            $file = substr($line, $start, $end - $start);
            if (array_key_exists($file, $preferenceArray)) {
                $report['PREFERENCES'][$file] = $preferenceArray[$file];
            }

            // Is there a template override?
            // todo: Use the framework to detect template override
            if (strpos($file, '.phtml') === false) {
                continue;
            }

            $parts = explode('/', $file);
            $last = end($parts);
            $start = strpos($file, '/view/');
            $end = strpos($file, $last);
            $path = substr($file, $start, $end - $start);
            $cmd = "find . -name " . $last . " -path '*" . $path . "*'";
            $results = explode(PHP_EOL, trim(shell_exec($cmd)));
            foreach ($results as $result) {
                $result = substr($result, 2);
                if (
                    strpos($result, 'vendor/magento') === false &&
                    $result != $file &&
                    strpos($result, 'setup/view/magento') !== 0
            ) {
                    $report['TEMPLATES'][$file] = $result;
                }
            }
        }

        foreach ($report as $type => $items) {
            $output->writeln('-------- ' . $type . ' --------');
            foreach ($items as $patched => $customized) {
                $output->writeln('Patched: ' . $patched);
                $output->writeln('Customized: ' . $customized);
            }
        }
    }
}
