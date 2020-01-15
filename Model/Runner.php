<?php

namespace SomethingDigital\UpgradeHelper\Model;

use SomethingDigital\UpgradeHelper\Model\Checker\Preferences as PreferenceChecker;
use SomethingDigital\UpgradeHelper\Model\Checker\Overrides as OverrideChecker;

class Runner
{
    private $lineProcessor;

    private $preferenceChecker;

    private $overrideChecker;

    private $checkers;

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

        $this->checkers = [
            'preferences' => $this->preferenceChecker,
            'overrides' => $this->overrideChecker
        ];
    }

    public function run($diff)
    {
        foreach ($diff as $line) {
            $pathInfo = $this->lineProcessor->toPathInfo($line);

            if (empty($pathInfo)) {
                continue;
            }

            foreach ($this->checkers as $type => $checker) {
                $result = $checker->check($pathInfo);
                if (!empty($result)) {
                    $this->result[$type][$result['patched']] = $result['customized'];
                    continue 2;
                }
            }
        }

        return $this->result;
    }
}
