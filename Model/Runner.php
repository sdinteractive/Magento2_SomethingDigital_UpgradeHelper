<?php

namespace SomethingDigital\UpgradeHelper\Model;

use SomethingDigital\UpgradeHelper\Model\Checker\Preferences as PreferenceChecker;
use SomethingDigital\UpgradeHelper\Model\Checker\Overrides as OverrideChecker;
use SomethingDigital\UpgradeHelper\Model\Checker\EmailTemplate as EmailTemplateChecker;

class Runner
{
    private $lineProcessor;

    private $preferenceChecker;

    private $overrideChecker;

    private $emailTemplateChecker;

    private $checkers;

    public function __construct(
        LineProcessor $lineProcessor,
        PreferenceChecker $preferenceChecker,
        OverrideChecker $overrideChecker,
        EmailTemplateChecker $emailTemplateChecker
    ) {
        $this->lineProcessor = $lineProcessor;
        $this->preferenceChecker = $preferenceChecker;
        $this->overrideChecker = $overrideChecker;
        $this->emailTemplateChecker = $emailTemplateChecker;

        $this->checkers = [
            'preferences' => $this->preferenceChecker,
            'overrides' => $this->overrideChecker,
            'email_template' => $this->emailTemplateChecker,
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
                break;
            }
        }

        return $result;
    }
}
