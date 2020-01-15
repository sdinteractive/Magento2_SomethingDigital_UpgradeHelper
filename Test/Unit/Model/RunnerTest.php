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
            'diff -r Magento-EE-2.3.1/vendor/magento/module-checkout/view/frontend/web/js/view/billing-address.js Magento-EE-2.3.3/vendor/magento/module-checkout/view/frontend/web/js/view/billing-address.js'
        ];

        $result = $this->runner->run($diff);

        // Theme (app/design) .js override
        $this->assertEquals(
            $result['overrides']['vendor/magento/module-sales-rule/view/frontend/web/js/action/set-coupon-code.js'],
            'app/design/frontend/SomethingDigitalUpgradeHelper/theme/Magento_SalesRule/web/js/action/set-coupon-code.js'
        );

        // Module (app/code) .js override
        $this->assertEquals(
            $result['overrides']['vendor/magento/module-checkout/view/frontend/web/js/view/billing-address.js'],
            'app/code/SomethingDigitalUpgradeHelper/Module/view/frontend/web/js/view/billing-address.js'
        );
    }
}
