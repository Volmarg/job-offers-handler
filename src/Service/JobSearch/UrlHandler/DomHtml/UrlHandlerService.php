<?php

namespace JobSearcher\Service\JobSearch\UrlHandler\DomHtml;

use JobSearcher\DTO\JobService\SearchConfiguration\DomHtml\MainConfigurationDto;
use JobSearcher\Service\JobSearch\UrlHandler\AbstractUrlHandlerService;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Handles any kind of logic related to building full crawl-able links, pagination urls etc
 */
class UrlHandlerService extends AbstractUrlHandlerService
{

    /**
     * @param MainConfigurationDto $mainConfigurationDto
     * @param KernelInterface      $kernel
     */
    public function __construct(
        MainConfigurationDto $mainConfigurationDto,
        KernelInterface      $kernel
    )
    {
        parent::__construct(
            $mainConfigurationDto,
            $mainConfigurationDto->getDetailPageConfigurationDto(),
            $mainConfigurationDto->getSearchUriConfigurationDto(),
            $kernel
        );
    }

}