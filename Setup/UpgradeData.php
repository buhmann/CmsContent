<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Buhmann\CmsContent\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{
    /** @var UpgradeContent */
    private $upgradeContent;

    public function __construct(
        UpgradeContent $upgradeContent
    )
    {
        $this->upgradeContent = $upgradeContent;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if ( version_compare($context->getVersion(), '1.0.0', '<') ) {
            /** Upgrade content code */
        }
        $this->upgradeContent->upgrade($setup, $context);
        $setup->endSetup();
    }
}
