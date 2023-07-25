<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Buhmann\CmsContent\Model\Setup;

use Buhmann\CmsContent\Model\Setup\Content\Block as BlockSetup;
use Buhmann\CmsContent\Model\Setup\Content\Page as PageSetup;
use Buhmann\CmsContent\Model\Setup\Content\Page;

class Content
{
    /** @var BlockSetup */
    private $blockSetup;

    /** @var PageSetup */
    private $pageSetup;

    public function __construct(
        BlockSetup $blockSetup,
        PageSetup $pageSetup
    )
    {
        $this->blockSetup = $blockSetup;
        $this->pageSetup = $pageSetup;
    }

    /**
     * Creates or updates the CMS block/page from the source files
     *
     * @param string $contentType
     * @param array $identifiers
     */
    public function upload($contentType = '', $identifiers = [])
    {
        switch ($contentType) {
            case BlockSetup::CONTENT_TYPE:
                $this->blockSetup->upload($identifiers);
                break;
            case PageSetup::CONTENT_TYPE:
                $this->pageSetup->upload($identifiers);
                break;
            case '':
                $this->blockSetup->upload($identifiers);
                $this->pageSetup->upload($identifiers);
                break;
            default: break;
        }
    }

    /**
     * Remove CMS Block/Page
     *
     * @param string $contentType
     * @param array $identifiers
     */
    public function remove($contentType = '', $identifiers = [])
    {
        switch ($contentType) {
            case BlockSetup::CONTENT_TYPE:
                $this->blockSetup->remove($identifiers);
                break;
            case PageSetup::CONTENT_TYPE:
                $this->pageSetup->remove($identifiers);
                break;
            case '':
                $this->blockSetup->remove($identifiers);
                $this->pageSetup->remove($identifiers);
                break;
            default: break;
        }
    }
}
