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

    public function run($line)
    {
        $result = [];
        $result['type'] = '';
        $result['items'] = [];
        $result['path'] = '';

        $pathInfo = $this->lineProcessor->toPathInfo($line);

        if (empty($pathInfo)) {
            return $result;
        }

        foreach ($this->checkers as $type => $checker) {
            $checked = $checker->check($pathInfo);
            if (!empty($checked)) {
                $result['type'] = $type;
                $result['items'] = $checked['customized'];
                $result['path'] = $pathInfo['fullpath'];
                continue;
            }
        }

        return $result;
    }
}
