<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Buhmann\CmsContent\Model\Setup\Content;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Framework\Api\FilterFactory;
use Magento\Framework\Api\Search\FilterGroupBuilderFactory;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Buhmann\CmsContent\Helper\Setup as Helper;

class Block
{
    const CONTENT_TYPE = 'block';

    const DEFAULTS = [
        'stores' => [0]
    ];

    /** @var BlockRepositoryInterface */
    private $blockRepository;

    /** @var SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var FilterGroupBuilderFactory */
    private $filterGroupBuilderFactory;

    /** @var FilterFactory */
    private $filterFactory;

    /** @var BlockInterfaceFactory */
    private $blockFactory;

    /** @var Helper */
    private $helper;

    /** @var SerializerInterface */
    private $serializer;

    /** @var LoggerInterface */
    private $logger;


    public function __construct
    (
        BlockRepositoryInterface $blockRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterGroupBuilderFactory $filterGroupBuilderFactory,
        FilterFactory $filterFactory,
        BlockInterfaceFactory $blockFactory,
        Helper $helper,
        SerializerInterface $serializer,
        LoggerInterface $logger
    )
    {
        $this->blockRepository = $blockRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterGroupBuilderFactory = $filterGroupBuilderFactory;
        $this->filterFactory = $filterFactory;
        $this->blockFactory = $blockFactory;
        $this->helper = $helper;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @param array $identifiers
     * @return \Magento\Cms\Api\Data\BlockInterface[]
     */
    protected function getItems($identifiers)
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        /** @var \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder */
        $filterGroupBuilder = $this->filterGroupBuilderFactory->create();

        foreach ($identifiers as $identifier) {
            $filter = $this->filterFactory->create();
            $filter->setField(BlockInterface::IDENTIFIER);
            $filter->setValue($identifier);
            $filterGroupBuilder->addFilter($filter);
        }

        $searchCriteriaBuilder->setFilterGroups([$filterGroupBuilder->create()]);

        $items = [];
        try {
            $items = $this->blockRepository->getList(
                $searchCriteriaBuilder->create()
            )->getItems();
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getLogMessage());
        }

        return $items;
    }

    /**
     * Creates or updates the CMS block from the source file
     *
     * @param array $identifiers The file names corresponding to the Block identifiers. If empty all files will be read.
     */
    public function upload($identifiers = [])
    {
        if (empty($identifiers)) {
            $identifiers = $this->helper->getTemplatesNames(self::CONTENT_TYPE);
        }

        $existItems = [];
        $items = $this->getItems($identifiers);
        foreach ($items as $item) {
            $existItems[] = $item->getIdentifier();
            try {
                $this->_update($item->getId());
            } catch (LocalizedException $exception) {
                $this->logger->warning($exception->getLogMessage());
            }
        }
        foreach ($identifiers as $identifier) {
            if (!in_array($identifier, $existItems)) {
                try {
                    $this->_create($identifier);
                } catch (LocalizedException $exception) {
                    $this->logger->warning($exception->getLogMessage());
                }
            }
        }
    }

    /**
     * Remove CMS Blocks
     *
     * @param array $identifiers The file names corresponding to the Block identifiers. If empty all described blocks will be removed.
     */
    public function remove($identifiers = [])
    {
        try {
            $items = $this->getItems($identifiers);
            foreach ($items as $item) {
                $this->blockRepository->delete($item);
            }
        } catch (LocalizedException $exception) {
            $this->logger->warning($exception->getLogMessage());
        }
    }

    /**
     * Get CMS Block Data
     *
     * @param $identifier
     * @return array|null
     */
    private function _getTemplateData($identifier)
    {
        $source = $this->helper->getTemplate(self::CONTENT_TYPE, $identifier);
        if (!$source) return null;

        $sourceData = explode('-->', explode('<!--', $source, 2)[1], 2);
        $data = $this->serializer->unserialize($sourceData[0]);
        $data[BlockInterface::IDENTIFIER] = $identifier;
        $data[BlockInterface::CONTENT]  = trim($sourceData[1]);

        return $data;
    }

    /**
     * Create CMS Block
     *
     * @param string $identifier
     */
    private function _create($identifier)
    {
        $data = $this->_getTemplateData($identifier);
        if ($data === null) return;

        foreach (self::DEFAULTS as $key => $value) {
            if ( !array_key_exists($key, $data) ) {
                $data[$key] = $value;
            }
        }

        $this->blockFactory->create(['data' => $data])->save();
    }

    /**
     * Update CMS Block
     *
     * @param int $id CMS block ID
     * @throws LocalizedException
     */
    private function _update($id)
    {
        $block = $this->blockRepository->getById($id);
        $data = $this->_getTemplateData($block->getIdentifier());
        if ($data === null) return;

        foreach ($data as $key => $value) {
            switch ($key) {
                case BlockInterface::TITLE:
                    $block->setTitle($value);
                    break;
                case BlockInterface::CONTENT:
                    $block->setContent($value);
                    break;
                case BlockInterface::IS_ACTIVE:
                    $block->setIsActive($value);
                    break;
                default: break;
            }
        }

        $this->blockRepository->save($block);
    }
}
