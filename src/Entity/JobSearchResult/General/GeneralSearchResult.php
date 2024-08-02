<?php

namespace JobSearcher\Entity\JobSearchResult\General;

use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use Doctrine\ORM\Mapping as ORM;

/**
 * This table will be used for storing any job offer that is not matching any specific portal
 *
 * @ORM\Table(name="job_search_result_general")
 * @ORM\Entity()
 */
class GeneralSearchResult extends JobSearchResult
{

    /**
     * @ORM\Column(type="string", length=150)
     */
    private string $configurationName;

    /**
     * @return string
     */
    public function getConfigurationName(): string
    {
        return $this->configurationName;
    }

    /**
     * @param string $configurationName
     */
    public function setConfigurationName(string $configurationName): void
    {
        $this->configurationName = $configurationName;
    }

}
