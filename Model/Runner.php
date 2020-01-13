<?php

namespace SomethingDigital\UpgradeHelper\Model;


use SomethingDigital\UpgradeHelper\Model\Checker\Preferences as PreferenceChecker;

class Runner
{
    private $lineProcessor;

    private $preferenceChecker;

    private $result = [
        'preferences' => [],
        'overrides' => []
    ];

    public function __construct(
        LineProcessor $lineProcessor,
        PreferenceChecker $preferenceChecker
    ) {
        $this->lineProcessor = $lineProcessor;
        $this->preferenceChecker = $preferenceChecker;
    }

    public function run($diff)
    {
        foreach ($diff as $line) {
            if (!$this->lineProcessor->shouldProcess($line)) {
                continue;
            }

            $preferenceResult = $this->preferenceChecker->check($line);
            if ($preferenceResult) {
                $this->result['preferences'][$preferenceResult['patched']] = $preferenceResult['customized'];
                continue;
            }
        }

        return $this->result;
    }
}
