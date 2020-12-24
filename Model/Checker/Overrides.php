<?php

namespace SomethingDigital\UpgradeHelper\Model\Checker;

use SomethingDigital\UpgradeHelper\Model\FileIndex;

class Overrides
{
    private $fileIndex;

    public function __construct(
        FileIndex $fileIndex
    ) {
        $this->fileIndex = $fileIndex;
    }

    /**
     * todo: This method could use some clean up
     */
    public function check($pathInfo)
    {
        $path = $pathInfo['fullpath'];
        if (!$this->shouldCheck($pathInfo)) {
            return [];
        }
        
        $results = $this->fileIndex->getOverrideResults($pathInfo);
        $output = [];

        foreach ($results as $result) {
            if ($this->isOverride($result, $path)) {
                $customized[] = $result;
            }
        }

        if (!empty($customized)) {
            $output = [
                'patched' => $path,
                'customized' => $customized
            ];
        }

        return $output;
    }

    private function shouldCheck($pathInfo)
    {
        if (!isset($pathInfo['extension'])) {
            return false;
        }

        if (!in_array($pathInfo['extension'], FileIndex::INTERESTING_EXTENSIONS)) {
            return false;
        }

        if (in_array($pathInfo['basename'], FileIndex::WHITELISTED_BASENAMES)) {
            return false;
        }

        $ignored = ['dev/tests', 'setup/view/magento'];
        foreach ($ignored as $needle) {
            if (strpos($pathInfo['fullpath'], $needle) !== false) {
                return false;
            }
        }

        $mustMatch = ['/view/'];
        foreach ($mustMatch as $needle) {
            if (strpos($pathInfo['fullpath'], $needle) === false) {
                return false;
            }
        }

        return true;
    }

    private function isOverride($result, $path)
    {
        if (!$result) {
            return false;
        }

        $ignored = ['vendor/magento', 'dev/tests', 'setup/view/magento'];
        foreach ($ignored as $needle) {
            if (strpos($result, $needle) !== false) {
                return false;
            }
        }

        if ($result == $path) {
            return false;
        }

        return true;
    }
}
