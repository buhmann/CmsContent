<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Buhmann\CmsContent\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Buhmann\CmsContent\Model\Setup\Content;

class UpgradeContent
{
    /** @var Content */
    private $content;

    public function __construct(
        Content $content
    )
    {
        $this->content = $content;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->content->upload();

        $this->content->remove(Content\Block::CONTENT_TYPE, [
            'test_cms_block_identifier',
        ]);
    }
}
