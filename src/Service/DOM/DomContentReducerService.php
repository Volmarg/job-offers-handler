<?php

namespace JobSearcher\Service\DOM;

use Exception;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Handles reducing dom content - removing elements etc.
 */
class DomContentReducerService
{
    /**
     * Will reduce provided crawler dom elements.
     * So if there is <DIV><P id="x"></P></DIV> then the <P> can be removed from the dom structure in here
     *
     * Keep in mind that order of removed elements DOES MATTER, because it could happen that first selector
     * is supposed to remove something nested, but it could also remove the same TOP element, so there would be nothing
     * more to remove.
     *
     * The provided selectors should be as strict as possible.
     *
     * @param Crawler $crawlerWithHtmlContent
     * @param array   $removedElementSelectors
     *
     * @return Crawler
     * @throws Exception
     */
    public static function handleDomNodeReducing(Crawler $crawlerWithHtmlContent, array $removedElementSelectors): Crawler
    {
        foreach ($removedElementSelectors as $selector) {
            try {
                $crawlerWithHtmlContent->filter($selector)->each(function (Crawler $crawler) {
                    foreach ($crawler as $node) {
                        $node->parentNode->removeChild($node);
                    }
                });
            } catch (Exception $e) {
                if (str_contains($e->getMessage(), "The current node list is empty")) {
                    continue;
                }

                throw $e;
            }
        }

        return $crawlerWithHtmlContent;
    }

}