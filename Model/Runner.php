<?php

namespace SomethingDigital\UpgradeHelper\Model;

use SomethingDigital\UpgradeHelper\Model\Checker\Preferences as PreferenceChecker;
use SomethingDigital\UpgradeHelper\Model\Checker\Overrides as OverrideChecker;

class Runner
{
    private $lineProcessor;

    private $preferenceChecker;

    private $overrideChecker;

    private $result = [
        'preferences' => [],
        'overrides' => []
    ];

    public function __construct(
        LineProcessor $lineProcessor,
        PreferenceChecker $preferenceChecker,
        OverrideChecker $overrideChecker
    ) {
        $this->lineProcessor = $lineProcessor;
        $this->preferenceChecker = $preferenceChecker;
        $this->overrideChecker = $overrideChecker;
    }

    public function run($diff)
    {
        foreach ($diff as $line) {
            $pathInfo = $this->lineProcessor->toPathInfo($line);
            if (empty($pathInfo)) {
                continue;
            }

            $preferenceResult = $this->preferenceChecker->check($pathInfo);
            if (!empty($preferenceResult)) {
                $this->result['preferences'][$preferenceResult['patched']] = $preferenceResult['customized'];
                continue;
            }

            $overrideResult = $this->overrideChecker->check($pathInfo);
        }

        return $this->result;
    }
}
