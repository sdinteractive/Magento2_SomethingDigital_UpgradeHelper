<?php

namespace SomethingDigital\UpgradeHelper\Model;

use Magento\Framework\Autoload\AutoloaderRegistry;
use Magento\Framework\ObjectManager\ConfigInterface as ObjectManagerConfig;

class LineProcessor
{
    /**
     * In diffs that are generated correctly (per documentation) we can always
     * expect the lines to process to start with `diff -r`
     */
    public function shouldProcess($line)
    {
        return strpos($line, 'diff -r') === 0;
    }
}
