<?php

namespace SomethingDigital\UpgradeHelper\Model\Checker;

use Magento\Framework\Autoload\AutoloaderRegistry;
use Magento\Framework\ObjectManager\ConfigInterface as ObjectManagerConfig;

class Preferences
{
    private $objectManagerConfig;

    private $preferenceArray = [];

    public function __construct(
        ObjectManagerConfig $objectManagerConfig
    ) {
        $this->objectManagerConfig = $objectManagerConfig;
        $this->autoloader = AutoloaderRegistry::getAutoloader();

        $this->buildPreferenceArray();
    }

    public function check($line)
    {
        $start = strpos($line, 'vendor');
        $end = strpos($line, ' ', $start);
        $file = substr($line, $start, $end - $start);
        if (array_key_exists($file, $this->preferenceArray)) {
            return [
                'patched' => $file,
                'customized' => $this->preferenceArray[$file]
            ];
        }
    }

    private function buildPreferenceArray()
    {
        $preferences = $this->objectManagerConfig->getPreferences();
        foreach ($preferences as $type => $preference) {
            if (strpos($type, 'Magento') === 0 && strpos($preference, 'Magento') === false) {
                // A <preference> for a Magento\ type that doesn't contain "Magento" in the class name
                $absoluteFile = realpath($this->autoloader->findFile($type));
                $start = strpos($absoluteFile, 'vendor');
                $file = substr($absoluteFile, $start);
                $this->preferenceArray[$file] = $preference;
            }
        }
    }
}
