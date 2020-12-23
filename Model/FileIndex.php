<?php

namespace SomethingDigital\UpgradeHelper\Model;

class FileIndex
{
    const THEME_OVERRIDE = 'theme_override';

    const MODULE_OVERRIDE = 'module_override';

    const INTERESTING_EXTENSIONS = [
            'phtml',
            'js',
            'html',
            'less'
    ];

    const WHITELISTED_BASENAMES = [
        'requirejs-config.js'
    ];

    /**
     * @var array[]
     */
    private $index;

    public function __construct()
    {
        $this->index = [
            self::THEME_OVERRIDE => [],
            self::MODULE_OVERRIDE => [],
        ];
    }

    public function populateIndex(): void
    {
        $this->populateIndexForPath(self::THEME_OVERRIDE, 'app/design/');
        $this->populateIndexForPath(self::MODULE_OVERRIDE, 'vendor/');
        $this->populateIndexForPath(self::MODULE_OVERRIDE, 'app/code/');
    }

    /**
     * @param array $pathInfo
     * @return array
     */
    public function getOverrideResults(array $pathInfo): array
    {
        $moduleResults = $this->getResultsByOverrideType($pathInfo, self::MODULE_OVERRIDE);
        $themeResults = $this->getResultsByOverrideType($pathInfo, self::THEME_OVERRIDE);
        return array_merge($moduleResults, $themeResults);
    }

    /**
     * @param string $overrideType
     * @param string $basePath
     */
    private function populateIndexForPath(
        string $overrideType,
        string $basePath
    ): void {
        $filePattern = $this->getFilePattern();
        $directory = new \RecursiveDirectoryIterator($basePath);
        $iterator = new \RecursiveIteratorIterator($directory);
        $regexIterator = new \RegexIterator($iterator, $filePattern, \RecursiveRegexIterator::GET_MATCH);

        foreach ($regexIterator as $dirInfo) {
            $fullPath = $dirInfo[0];
            if (!in_array(basename($fullPath), self::WHITELISTED_BASENAMES)) {
                $this->index[$overrideType][basename($fullPath)][] = $fullPath;
            }
        }
    }

    /**
     * @param array $pathInfo
     * @param string $overrideType
     * @return array
     */
    private function getResultsByOverrideType(array $pathInfo, string $overrideType): array
    {
        $basenameFromDiff = $pathInfo['basename'];
        $fullPathFromDiff = $pathInfo['fullpath'];
        $subPath = $this->getSubPath($basenameFromDiff, $fullPathFromDiff, $overrideType);
        $fullPaths = $this->index[$overrideType][$basenameFromDiff] ?? [];
        return array_filter($fullPaths, function ($fullPath) use ($subPath) {
            return strpos($fullPath, $subPath) !== false;
        });
    }

    /**
     * Example: '/^.+\.(phtml|js|html|less)$/i';
     *
     * @return string
     */
    private function getFilePattern(): string
    {
        $fileExtensionGroup = implode('|', self::INTERESTING_EXTENSIONS);
        return "/^.+\.($fileExtensionGroup)\$/i";
    }

    /**
     * Module override example:
     * vendor/magento/module-sales-rule/view/frontend/web/js/action/set-coupon-code.js => /view/frontend/web/js/action/
     *
     * Theme override example:
     * vendor/magento/module-sales-rule/view/frontend/web/js/action/set-coupon-code.js => /web/js/action/
     *
     * @param string $basename
     * @param string $fullPath
     * @param string $overrideType
     * @return string
     */
    private function getSubPath(string $basename, string $fullPath, string $overrideType): string
    {
        $baseDir = $overrideType === self::THEME_OVERRIDE ? '/frontend/' : '/view/';
        $offset = $overrideType === self::THEME_OVERRIDE ? strlen($baseDir) - 1 : 0;
        $startPos = strpos($fullPath, $baseDir) + $offset;
        $endPos = strpos($fullPath, $basename);
        return substr($fullPath, $startPos, $endPos - $startPos);
    }
}
