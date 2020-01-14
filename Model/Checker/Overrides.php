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

    /**
     * todo: This method could use some clean up
     */
    public function check($pathInfo)
    {
        if (!$this->shouldCheck($pathInfo)) {
            return [];
        }

        $path = $pathInfo['fullpath'];

        $pathParts = explode('/', $path);
        $start = strpos($path, '/view/');
        $end = strpos($path, $pathInfo['basename']);

        // Check in modules
        //
        // Command structure:
        // find . -name <<basename>> -path <<*/view/{{PATH-AFTER-VIEW-UP-TO-BASENAME}}
        //
        // Example command:
        // find . -name gift-card-account.js -path '*/view/frontend/web/js/view/cart/totals/*'
        $modulePath = substr($path, $start, $end - $start);
        $moduleCmd = "find . -name " . $pathInfo['basename'] . " -path '*" . $modulePath . "*'";
        $moduleResults = explode(PHP_EOL, trim(shell_exec($moduleCmd)));

        // Check in theme
        //
        // Command structure:
        // find . -name <<basename>> -path */app/design/* -path <<PATH-AFTER-/frontend/>>
        //
        // Example command:
        // find . -name gift-card-account.js -path '*app/design/*' -path '*web/js/view/cart/totals/*
        $themeStartKey = '/frontend/';
        $themeStartPos = strpos($path, $themeStartKey) + strlen($themeStartKey);
        $themePath = substr($path, $themeStartPos, $end - $themeStartPos);
        $themeCmd = "find . -name " . $pathInfo['basename'] . " -path '*app/design/*' -path '*" . $themePath . "*'";
        $themeResults = explode(PHP_EOL, trim(shell_exec($themeCmd)));

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

        return true;
    }

    private function isOverride($result, $path)
    {
        if (!$result) {
            return false;
        }

        if (strpos($result, 'vendor/magento') !== false) {
            return false;
        }

        if (strpos($result, 'dev/tests') !== false) {
            return false;
        }

        if (strpos($result, 'setup/view/magento') !== false) {
            return false;
        }

        if ($result == $path) {
            return false;
        }

        return true;
    }
}
