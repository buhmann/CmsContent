<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Buhmann\CmsContent\Model\Setup\Content;

use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterFactory;
use Magento\Framework\Api\Search\FilterGroupBuilderFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Buhmann\CmsContent\Helper\Setup as Helper;

class Page
{
    const CONTENT_TYPE = 'page';

    const DEFAULTS = [
        'stores' => [0]
    ];
    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;
    /**
     * @var FilterGroupBuilderFactory
     */
    private $filterGroupBuilderFactory;
    /**
     * @var FilterFactory
     */
    private $filterFactory;
    /**
     * @var PageInterfaceFactory
     */
    private $pageFactory;
    /**
     * @var Helper
     */
    private $helper;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var LoggerInterface
     */
    private $logger;


    public function __construct(
        PageRepositoryInterface $pageRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterGroupBuilderFactory $filterGroupBuilderFactory,
        FilterFactory $filterFactory,
        PageInterfaceFactory $pageFactory,
        Helper $helper,
        SerializerInterface $serializer,
        LoggerInterface $logger
    )
    {
        $this->pageRepository = $pageRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterGroupBuilderFactory = $filterGroupBuilderFactory;
        $this->filterFactory = $filterFactory;
        $this->pageFactory = $pageFactory;
        $this->helper = $helper;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @param array $identifiers
     * @return PageInterface[]
     */
    protected function getItems($identifiers)
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        /** @var \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder */
        $filterGroupBuilder = $this->filterGroupBuilderFactory->create();

        foreach ($identifiers as $identifier) {
            /** @var Filter $filter */
            $filter = $this->filterFactory->create();
            $filter->setField(PageInterface::IDENTIFIER);
            $filter->setValue($identifier);
            $filterGroupBuilder->addFilter($filter);
        }

        $searchCriteriaBuilder->setFilterGroups([$filterGroupBuilder->create()]);

        $items = [];
        try {
            $items = $this->pageRepository->getList(
                $searchCriteriaBuilder->create()
            )->getItems();
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getLogMessage());
        }

        return $items;
    }


    /**
     * Creates or updates CMS pages from the source files
     *
     * @param array $identifiers The file names corresponding to the Page identifiers. If empty all files will be read.
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
     * Remove CMS Pages
     *
     * @param array $identifiers The file names corresponding to the Page identifiers. If empty all described pages will be removed.
     */
    public function remove($identifiers = [])
    {
        try {
            $items = $this->getItems($identifiers);
            foreach ($items as $item) {
                $this->pageRepository->delete($item);
            }
        } catch (LocalizedException $exception) {
            $this->logger->warning($exception->getLogMessage());
        }
    }


    /**
     * Get CMS Page Data
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
        $data[PageInterface::IDENTIFIER] = $identifier;
        $data[PageInterface::CONTENT]  = trim($sourceData[1]);

        return $data;
    }

    /**
     * Create CMS Page
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

        $this->pageFactory->create(['data' => $data])->save();
    }

    /**
     * Update CMS Page
     *
     * @param int $id CMS page ID
     * @throws LocalizedException
     */
    private function _update($id)
    {
        $page = $this->pageRepository->getById($id);
        $data = $this->_getTemplateData($page->getIdentifier());
        if ($data === null) return;

        foreach ($data as $key => $value) {
            switch ($key) {
                case PageInterface::TITLE:
                    $page->setTitle($value);
                    break;
                case PageInterface::PAGE_LAYOUT:
                    $page->setPageLayout($value);
                    break;
                case PageInterface::META_KEYWORDS:
                    $page->setMetaKeywords($value);
                    break;
                case PageInterface::META_DESCRIPTION:
                    $page->setMetaDescription($value);
                    break;
                case PageInterface::CONTENT_HEADING:
                    $page->setContentHeading($value);
                    break;
                case PageInterface::CONTENT:
                    $page->setContent($value);
                    break;
                case PageInterface::IS_ACTIVE:
                    $page->setIsActive($value);
                    break;
                default: break;
            }
        }

        $this->pageRepository->save($page);
    }
}
