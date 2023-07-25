<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Buhmann\CmsContent\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context as HelperContext;
use Magento\Framework\Module\Dir\Reader;

/**
 * Class Helper
 * @package Buhmann\CmsContent\Helper
 */
class Setup extends AbstractHelper
{
    /**
     * @var Reader
     */
    protected $moduleReader;

    /**
     * Helper constructor.
     * @param HelperContext $context
     * @param Reader $moduleReader
     */
    public function __construct
    (
        HelperContext $context,
        Reader $moduleReader
    )
    {
        $this->moduleReader = $moduleReader;
        parent::__construct($context);
    }

    /**
     * Returns CMS content path
     *
     * @return string
     */
    public function getContentPath()
    {
        $modulePath = $this->moduleReader->getModuleDir('', $this->_getModuleName());
        $contentPath = $modulePath . DIRECTORY_SEPARATOR . 'Setup' . DIRECTORY_SEPARATOR . 'Content';
        return $contentPath;
    }

    public function getTemplatesNames($contentType)
    {
        $names = [];

        $path = $this->getContentPath() . DIRECTORY_SEPARATOR . $contentType;
        if (!file_exists($path)) return $names;

        $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, $flags));

        foreach ($iterator as $file) {
            $filename = $file->getFileName();
            $isHtml = 'html' === strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!$isHtml) continue;

            $subPath = $iterator->getSubPath();
            $subPath .= $subPath ? DIRECTORY_SEPARATOR : '';

            $name = $subPath . pathinfo($filename, PATHINFO_FILENAME);

            $names[] = $name;
        }

        return $names;
    }

    public function getTemplate($contentType, $identifier)
    {
        $filename = $this->getContentPath()
            . DIRECTORY_SEPARATOR . $contentType
            . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $identifier) . '.html';

        if (!file_exists($filename)) return false;

        return file_get_contents($filename);
    }
}
