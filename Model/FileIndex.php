<?php

namespace SomethingDigital\UpgradeHelper\Model;

class FileIndex
{
    const THEME_FILES_KEY = 'theme_override';

    const MODULE_FILES_KEY = 'module_override';

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
     * Maps basenames to an array of file paths for module and theme files
     *
     * Example structure:
     *[
     *    self::MODULE_FILES_KEY => [
     *        'billing-address.js' => [
     *            'app/code/SomethingDigitalUpgradeHelper/Module/view/frontend/web/js/view/billing-address.js',
     *            ...
     *        ],
     *    ],
     *]
     *
     * @var array[]
     */
    private $index;

    /**
     *
     * Example: '/^.+\.(phtml|js|html|less)$/i'
     *
     * @var string
     */
    private $filePattern;

    public function __construct()
    {
        $this->index = [
            self::THEME_FILES_KEY => [],
            self::MODULE_FILES_KEY => [],
        ];

        $fileExtensionGroup = implode('|', self::INTERESTING_EXTENSIONS);
        $this->filePattern = "/^.+\.($fileExtensionGroup)\$/i";
    }

    public function populateIndex(): void
    {
        $this->populateIndexForPath(self::THEME_FILES_KEY, 'app/design/');
        $this->populateIndexForPath(self::MODULE_FILES_KEY, 'vendor/');
        $this->populateIndexForPath(self::MODULE_FILES_KEY, 'app/code/');
    }

    /**
     * @param array $pathInfo
     * @return array
     */
    public function getOverrideResults(array $pathInfo): array
    {
        $moduleResults = $this->getResultsByFileType($pathInfo, self::MODULE_FILES_KEY);
        $themeResults = $this->getResultsByFileType($pathInfo, self::THEME_FILES_KEY);
        return array_merge($moduleResults, $themeResults);
    }

    /**
     * Populates the index for the specified file type
     * Recursively iterates through all files matching the file pattern (.js, .phtml, .less, .html) starting at $basePath
     * Maps each file's basename to its full path
     * 
     * @param string $fileType
     * @param string $basePath
     */
    private function populateIndexForPath(
        string $fileType,
        string $basePath
    ): void {
        $directory = new \RecursiveDirectoryIterator($basePath);
        $iterator = new \RecursiveIteratorIterator($directory);
        $regexIterator = new \RegexIterator($iterator, $this->filePattern, \RecursiveRegexIterator::GET_MATCH);

        foreach ($regexIterator as $dirInfo) {
            $fullPath = $dirInfo[0];
            if (!in_array(basename($fullPath), self::WHITELISTED_BASENAMES)) {
                $this->index[$fileType][basename($fullPath)][] = $fullPath;
            }
        }
    }

    /**
     * @param array $pathInfo
     * @param string $fileType
     * @return array
     */
    private function getResultsByFileType(array $pathInfo, string $fileType): array
    {
        $basenameFromDiff = $pathInfo['basename'];
        $fullPathFromDiff = $pathInfo['fullpath'];
        $subPath = $this->getSubPath($basenameFromDiff, $fullPathFromDiff, $fileType);
        $fullPaths = $this->index[$fileType][$basenameFromDiff] ?? [];
        return array_filter($fullPaths, function ($fullPath) use ($subPath) {
            return strpos($fullPath, $subPath) !== false;
        });
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
     * @param string $fileType
     * @return string
     */
    private function getSubPath(string $basename, string $fullPath, string $fileType): string
    {
        $baseDir = $fileType === self::THEME_FILES_KEY ? '/frontend/' : '/view/';
        $offset = $fileType === self::THEME_FILES_KEY ? strlen($baseDir) - 1 : 0;
        $startPos = strpos($fullPath, $baseDir) + $offset;
        $endPos = strpos($fullPath, $basename);
        return substr($fullPath, $startPos, $endPos - $startPos);
    }
}
