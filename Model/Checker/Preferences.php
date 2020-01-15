<?php

namespace SomethingDigital\UpgradeHelper\Model\Checker;

use Magento\Framework\Autoload\AutoloaderRegistry;
use Magento\Framework\ObjectManager\ConfigInterface as ObjectManagerConfig;

class Preferences
{
    private $objectManagerConfig;

    private $preferenceArray = [];

    private $autoloader;

    public function __construct(
        ObjectManagerConfig $objectManagerConfig
    ) {
        $this->objectManagerConfig = $objectManagerConfig;
        $this->autoloader = AutoloaderRegistry::getAutoloader();

        $this->buildPreferenceArray();
    }

    public function check($pathInfo)
    {
        $path = $pathInfo['fullpath'];
        if (array_key_exists($path, $this->preferenceArray)) {
            return [
                'patched' => $path,
                'customized' => $this->preferenceArray[$path]
            ];
        }

        return [];
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
