<?php

namespace JobSearcher\Repository\Company;

use DateTime;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use JobSearcher\Entity\Company\Company;

/**
 * This repository handles the {@see Company} related data,
 * Yes there is also {@see CompanyRepository} but there are certain cases where entity manager
 * cannot be used (for example due to Doctrine being bad with concurrency)
 *
 * Also didn't wanted to mix the clean {@see QueryBuilder} based code with raw sqls
 */
class CompanyRawSqlRepository
{
    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @param int $id
     *
     * @return Company|null
     *
     * @throws Exception
     */
    public function setLastTimeRelatedToOfferAsToday(int $id): ?Company
    {
        $sql = "
            UPDATE company
            SET last_time_related_to_offer = :dateString
            WHERE id = :id
        ";

        $params = [
            "dateString" => (new DateTime())->format("Y-m-d H:i:s"),
            "id"         => $id,
        ];

        $this->entityManager->getConnection()->executeQuery($sql, $params);

        return $this->find($id);
    }

    /**
     * @param int $id
     *
     * @return Company|null
     */
    private function find(int $id): ?Company
    {
        return $this->entityManager->find(Company::class, $id);
    }
}