<?php

namespace JobSearcher\Service\CompanyData;

use Doctrine\ORM\NonUniqueResultException;
use JobSearcher\Entity\Company\Company;
use JobSearcher\Repository\Company\CompanyRepository;

/**
 * Handles logic related to {@see Company}
 *
 * Info:
 *  - it can happen that company has no related country,
 *  - if that's the case then all job offers are being bound to company by its name
 */
class CompanyService
{
    public function __construct(
        private CompanyRepository $companyRepository
    ){}

    /**
     * Will find one company by the name or country
     *
     * @param string $companyName
     *
     * @return Company|null
     * @throws NonUniqueResultException
     */
    public function findOneCompany(string $companyName): ?Company
    {
        return $this->companyRepository->findOneCompany($companyName);
    }
}