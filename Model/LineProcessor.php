<?php

namespace SomethingDigital\UpgradeHelper\Model;

use Magento\Framework\Autoload\AutoloaderRegistry;
use Magento\Framework\ObjectManager\ConfigInterface as ObjectManagerConfig;

class LineProcessor
{
    /**
     * Return array with both pathinfo and fullpath
     *
     * Path is processed as follows...
     *
     * Before:
     *
     * diff -r Magento-EE-2.3.2-2019-06-13-04-50-34/vendor/magento/module-catalog/Controller/Adminhtml/Product/Gallery/Upload.php Magento-EE-2.3.2-p2-2019-10-09-01-47-56/vendor/magento/module-catalog/Controller/Adminhtml/Product/Gallery/Upload.php
     *
     * After:
     *
     * vendor/magento/module-catalog/Controller/Adminhtml/Product/Gallery/Upload.php
     */
    public function toPathInfo($line)
    {
        // Skip lines that don't start with `diff -r`
        if (strpos($line, 'diff -r') !== 0) {
            return [];
        }

        $start = strpos($line, 'vendor');
        if ($start === false) {
            return [];
        }

        $end = strpos($line, ' ', $start);

        $path = substr($line, $start, $end - $start);

        return array_merge(
            pathinfo($path),
            ['fullpath' => $path]
        );
    }
}
