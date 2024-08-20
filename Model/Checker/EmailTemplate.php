<?php

namespace SomethingDigital\UpgradeHelper\Model\Checker;

use SomethingDigital\UpgradeHelper\Model\FileIndex;
use Magento\Email\Model\TemplateFactory as CoreEmailTemplateFactory;
use Magento\Email\Model\Template\Config;

class EmailTemplate
{
    private $fileIndex;

    private $emailTemplateFactory;

    private $config;

    private $templateMappings = [];

    public function __construct(
        CoreEmailTemplateFactory $emailTemplateFactory,
        Config $config
    ) {
        $this->emailTemplateFactory = $emailTemplateFactory;
        $this->config = $config;
    }

    public function mapEmailTemplates()
    {
        $emailTemplateCollection = $this->emailTemplateFactory->create()->getCollection();

        foreach ($emailTemplateCollection as $template) {
            $templateOriginalCode = $template->getOrigTemplateCode();
            if (!$templateOriginalCode) {
                continue;
            }
            $templatePath = $this->config->getTemplateFileName($templateOriginalCode);
            $templateCode = $template->getTemplateCode();
            $this->templateMappings[$templateOriginalCode] = ['templatePath' => $templatePath, 'templateCode' => $templateCode];
        }
    }

    public function check($pathInfo)
    {
        if (empty($this->templateMappings)) {
            $this->mapEmailTemplates();
        }
        $path = $pathInfo['fullpath'];

        if (!$this->shouldCheck($pathInfo)) {
            return [];
        }

        $output = [];
        $customized = $this->hasBeenCustomized($path);

        if (!empty($customized)) {
            $output = [
                'patched' => $path,
                'customized' => $customized
            ];
        }

        return $output;
    }

    private function shouldCheck($pathInfo)
    {
        if (!isset($pathInfo['extension'])) {
            return false;
        }

        if (!in_array($pathInfo['extension'], FileIndex::EMAIL_EXTENSIONS)) {
            return false;
        }

        $ignored = ['dev/tests', 'setup/view/magento'];
        foreach ($ignored as $needle) {
            if (strpos($pathInfo['fullpath'], $needle) !== false) {
                return false;
            }
        }

        $mustMatch = ['/email/'];
        foreach ($mustMatch as $needle) {
            if (strpos($pathInfo['fullpath'], $needle) === false) {
                return false;
            }
        }

        return true;
    }

    private function hasBeenCustomized($path)
    {
        foreach ($this->templateMappings as $templateOriginalCode => $templateInfo) {
            $templatePath = $templateInfo['templatePath'];
            $templateCode = $templateInfo['templateCode'];
            if(strpos($templatePath, $path) !== false) {
                return [$templateCode];
            }
        }
        return false;
    }
}
