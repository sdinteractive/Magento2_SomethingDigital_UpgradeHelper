<?php

namespace SomethingDigital\UpgradeHelper\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use SomethingDigital\UpgradeHelper\Model\Runner;
use SomethingDigital\UpgradeHelper\Model\LineProcessor;
use SomethingDigital\UpgradeHelper\Model\Checker\Preferences as PreferenceChecker;
use SomethingDigital\UpgradeHelper\Model\Checker\Overrides as OverrideChecker;

class RunnerTest extends TestCase
{
    private $runner;
    private $lineProcessor;
    private $overrideChecker;

    protected function setUp()
    {
        parent::setUp();
        $objectManager = new ObjectManager($this);

        $this->lineProcessor = $objectManager->getObject(LineProcessor::class);
        $this->overrideChecker = $objectManager->getObject(OverrideChecker::class);

        $this->runner = $objectManager->getObject(
            Runner::class,
            [
                'lineProcessor' => $this->lineProcessor,
                'overrideChecker' => $this->overrideChecker
            ]
        );

    }

    public function testRun()
    {
        $diff = [
            'diff -r Magento-EE-2.3.1/vendor/magento/module-sales-rule/view/frontend/web/js/action/set-coupon-code.js Magento-EE-2.3.3/vendor/magento/module-sales-rule/view/frontend/web/js/action/set-coupon-code.js',
            'diff -r Magento-EE-2.3.1/vendor/magento/module-checkout/view/frontend/web/js/view/billing-address.js Magento-EE-2.3.3/vendor/magento/module-checkout/view/frontend/web/js/view/billing-address.js',
            'diff -r Magento-EE-2.3.1/vendor/magento/module-bundle/view/frontend/templates/js/components.phtml Magento-EE-2.3.3/vendor/magento/module-bundle/view/frontend/templates/js/components.phtml',
            'diff -r Magento-EE-2.3.1/vendor/magento/module-checkout/view/frontend/web/template/billing-address/details.html Magento-EE-2.3.3/vendor/magento/module-checkout/view/frontend/web/template/billing-address/details.html'
        ];

        $result = $this->runner->run($diff);

        // Theme (app/design) .js override
        $this->assertEquals(
            $result['overrides']['vendor/magento/module-sales-rule/view/frontend/web/js/action/set-coupon-code.js'],
            'app/design/frontend/SomethingDigitalUpgradeHelper/theme/Magento_SalesRule/web/js/action/set-coupon-code.js'
        );

        // Theme (app/design) .phtml override
        $this->assertEquals(
            $result['overrides']['vendor/magento/module-bundle/view/frontend/templates/js/components.phtml'],
            'app/design/frontend/SomethingDigitalUpgradeHelper/theme/Magento_Catalog/templates/js/components.phtml'
        );

        // Module (app/code) .js override
        $this->assertEquals(
            $result['overrides']['vendor/magento/module-checkout/view/frontend/web/js/view/billing-address.js'],
            'app/code/SomethingDigitalUpgradeHelper/Module/view/frontend/web/js/view/billing-address.js'
        );

        // Module (vendor/) .html override
        $this->assertEquals(
            $result['overrides']['vendor/magento/module-checkout/view/frontend/web/template/billing-address/details.html'],
            'vendor/somethingdigitalupgradehelper/module/src/view/frontend/web/template/billing-address/details.html'
        );

        // TODO
        // - Preference check
        // - Line in diff that doesn't match override OR preference
    }
}
