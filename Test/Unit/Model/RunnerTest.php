<?php

namespace SomethingDigital\UpgradeHelper\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\Framework\ObjectManager\ConfigInterface as ObjectManagerConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use SomethingDigital\UpgradeHelper\Model\Runner;
use SomethingDigital\UpgradeHelper\Model\LineProcessor;
use SomethingDigital\UpgradeHelper\Model\Checker\Preferences as PreferenceChecker;
use SomethingDigital\UpgradeHelper\Model\Checker\Overrides as OverrideChecker;
use SomethingDigital\UpgradeHelper\Model\FileIndex;

class RunnerTest extends TestCase
{
    private $runner;
    private $lineProcessor;
    private $overrideChecker;
    private $objectManagerConfig;
    private $preferenceChecker;
    private $fileIndex;

    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = new ObjectManager($this);

        $this->lineProcessor = $objectManager->getObject(LineProcessor::class);
        $this->fileIndex = $objectManager->getObject(FileIndex::class);
        $this->overrideChecker = $objectManager->getObject(
            OverrideChecker::class,
            [
                'fileIndex' => $this->fileIndex
            ]
        );

        $this->objectManagerConfig = $this->createMock(ObjectManagerConfig::class);
        $this->objectManagerConfig->method('getPreferences')
            ->willReturn([
                'Magento\CatalogRule\Controller\Adminhtml\Promo\Catalog\Save' =>
                    'SomethingDigitalUpgradeHelper\Module\Controller\Adminhtml\Promo\Catalog\Save'
            ]);

        $this->preferenceChecker= $objectManager->getObject(
            PreferenceChecker::class,
            [
                'objectManagerConfig' => $this->objectManagerConfig
            ]
        );

        $this->runner = $objectManager->getObject(
            Runner::class,
            [
                'lineProcessor' => $this->lineProcessor,
                'overrideChecker' => $this->overrideChecker,
                'preferenceChecker' => $this->preferenceChecker,
            ]
        );

    }

    public function testRun()
    {
        $this->fileIndex->populateIndex();

        $diff = [
            'diff -r Magento-EE-2.3.1/vendor/magento/module-sales-rule/view/frontend/web/js/action/set-coupon-code.js Magento-EE-2.3.3/vendor/magento/module-sales-rule/view/frontend/web/js/action/set-coupon-code.js',
            'diff -r Magento-EE-2.3.1/vendor/magento/module-checkout/view/frontend/web/js/view/billing-address.js Magento-EE-2.3.3/vendor/magento/module-checkout/view/frontend/web/js/view/billing-address.js',
            'diff -r Magento-EE-2.3.1/vendor/magento/module-bundle/view/frontend/templates/js/components.phtml Magento-EE-2.3.3/vendor/magento/module-bundle/view/frontend/templates/js/components.phtml',
            'diff -r Magento-EE-2.3.1/vendor/magento/module-checkout/view/frontend/web/template/billing-address/details.html Magento-EE-2.3.3/vendor/magento/module-checkout/view/frontend/web/template/billing-address/details.html',
            'diff -r Magento-EE-2.3.1/vendor/magento/module-customer/view/frontend/templates/form/forgotpassword.phtml Magento-EE-2.3.3/vendor/magento/module-customer/view/frontend/templates/form/forgotpassword.phtml',
            'diff -r Magento-EE-2.3.1/vendor/magento/module-catalog-rule/Controller/Adminhtml/Promo/Catalog/Save.php Magento-EE-2.3.3/vendor/magento/module-catalog-rule/Controller/Adminhtml/Promo/Catalog/Save.php'
        ];

        $result = [];
        $result['preferences'] = [];
        $result['overrides'] = [];
        foreach ($diff as $line) {
            // Extract will populate: $type, $path, $items
            extract($this->runner->run($line));
            $result[$type][$path] = $items;
        }
        // Theme (app/design) .js override
        $this->assertTrue(
            $this->arrays_are_similar(
                $result['overrides']['vendor/magento/module-sales-rule/view/frontend/web/js/action/set-coupon-code.js'],
                [
                    'app/design/frontend/SomethingDigitalUpgradeHelper/theme/Magento_SalesRule/web/js/action/set-coupon-code.js',
                ]
            )
        );

        // Theme (app/design) .phtml override
        $this->assertTrue(
            $this->arrays_are_similar(
                $result['overrides']['vendor/magento/module-bundle/view/frontend/templates/js/components.phtml'],
                [
                    'app/design/frontend/SomethingDigitalUpgradeHelper/theme/Magento_Catalog/templates/js/components.phtml',
                ]
            )
        );

        // Module (app/code) .js override
        $this->assertTrue(
            $this->arrays_are_similar(
                $result['overrides']['vendor/magento/module-checkout/view/frontend/web/js/view/billing-address.js'],
                [
                    'app/code/SomethingDigital/UpgradeHelper/Test/Fixtures/app/code/SomethingDigitalUpgradeHelper/Module/view/frontend/web/js/view/billing-address.js',
                    'app/code/SomethingDigitalUpgradeHelper/Module/view/frontend/web/js/view/billing-address.js'
                ]
            )
        );

        // Module (vendor/) .html override
        $this->assertTrue(
            $this->arrays_are_similar(
                $result['overrides']['vendor/magento/module-checkout/view/frontend/web/template/billing-address/details.html'],
                [
                    'app/code/SomethingDigital/UpgradeHelper/Test/Fixtures/vendor/somethingdigitalupgradehelper/module/src/view/frontend/web/template/billing-address/details.html',
                    'vendor/somethingdigitalupgradehelper/module/src/view/frontend/web/template/billing-address/details.html'
                ]
            )
        );

        // Preference
        $this->assertEquals(
            $result['preferences']['vendor/magento/module-catalog-rule/Controller/Adminhtml/Promo/Catalog/Save.php'],
            ['SomethingDigitalUpgradeHelper\Module\Controller\Adminhtml\Promo\Catalog\Save']
        );

        // Theme (app/design) .phtml multiple overrides of the same file in different themes
        $this->assertTrue(
            $this->arrays_are_similar(
                $result['overrides']['vendor/magento/module-customer/view/frontend/templates/form/forgotpassword.phtml'],
                [
                    'app/design/frontend/SomethingDigitalUpgradeHelper/theme2/Magento_Customer/templates/form/forgotpassword.phtml',
                    'app/design/frontend/SomethingDigitalUpgradeHelper/theme/Magento_Customer/templates/form/forgotpassword.phtml',
                ]
            )
        );

        // TODO
        // - Line in diff that doesn't match override OR preference
    }

     /**
     * Determine if two associative arrays are similar
     *
     * Both arrays must have the same indexes with identical values
     * without respect to key ordering 
     * 
     * @param array $a
     * @param array $b
     * @return bool
     */
    protected function arrays_are_similar($a, $b) {
        sort($a);
        sort($b);

        // if the indexes don't match, return immediately
        if (count(array_diff_assoc($a, $b))) {
            return false;
        }
        // we know that the indexes, but maybe not values, match.
        // compare the values between the two arrays
        foreach($a as $k => $v) {
            if ($v !== $b[$k]) {
                return false;
            }
        }
        // we have identical indexes, and no unequal values
        return true;
    }
}
