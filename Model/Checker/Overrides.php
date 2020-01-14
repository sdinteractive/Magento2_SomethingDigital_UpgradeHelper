<?php

namespace SomethingDigital\UpgradeHelper\Model\Checker;

class Overrides
{
    private $interestingExtensions = [
        'phtml',
        'js',
        'html',
        'less'
    ];

    private $whitelistedBasenames = [
        'requirejs-config.js'
    ];

    private $endPosition;

    /**
     * Command for checking modules
     *
     * Example command:
     * find . -name gift-card-account.js -path '*\/view/frontend/web/js/view/cart/totals/*'
     */
    private function moduleCmd($pathInfo)
    {
        $start = strpos($pathInfo['fullpath'], '/view/');
        $path = substr($pathInfo['fullpath'], $start, $this->endPosition - $start);

        return "find . -name " . $pathInfo['basename'] . " -path '*" . $path . "*'";
    }

    /**
     * Command for checking theme
     *
     * Example command:
     * find . -name gift-card-account.js -path '*app/design/*' -path '*web/js/view/cart/totals/*'
     */
    private function themeCmd($pathInfo)
    {
        $startKey = '/frontend/';
        $startPos = strpos($pathInfo['fullpath'], $startKey) + strlen($startKey);
        $path = substr($pathInfo['fullpath'], $startPos, $this->endPosition - $startPos);

        return "find . -name " . $pathInfo['basename'] . " -path '*app/design/*' -path '*" . $path . "*'";
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

        $this->endPosition = strpos($path, $pathInfo['basename']);

        $moduleResults = explode(PHP_EOL, trim(shell_exec($this->moduleCmd($pathInfo))));
        $themeResults = explode(PHP_EOL, trim(shell_exec($this->themeCmd($pathInfo))));
        $results = array_merge($moduleResults, $themeResults);

        foreach ($results as $result) {
            $result = substr($result, 2);
            if ($this->isOverride($result, $path)) {
                return [
                    'patched' => $path,
                    'customized' => $result
                ];
            }
        }

        return [];
    }

    private function shouldCheck($pathInfo)
    {
        if (!isset($pathInfo['extension'])) {
            return false;
        }

        if (!in_array($pathInfo['extension'], $this->interestingExtensions)) {
            return false;
        }

        if (in_array($pathInfo['basename'], $this->whitelistedBasenames)) {
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
