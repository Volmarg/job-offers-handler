<?php

namespace JobSearcher\Repository\Email;

use DateTime;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JobSearcher\Command\Cleanup\EmailCleanupCommand;
use JobSearcher\Entity\Email\Email;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Email|null find($id, $lockMode = null, $lockVersion = null)
 * @method Email|null findOneBy(array $criteria, array $orderBy = null)
 * @method Email[]    findAll()
 * @method Email[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Email::class);
    }

    /**
     * Will search for Email entity by the E-Mail address,
     * Returns NULL if no Email entity was found, else found entity is returned
     *
     * @param string $emailAddress
     *
     * @return Email|null
     */
    public function findByAddress(string $emailAddress): ?Email
    {
        return $this->findOneBy(['address' => $emailAddress]);
    }

    /**
     * Will return Emails for validation {@see EmailCleanupCommand}
     *
     * @param int $limit
     * @param int $daysOldOffset
     *
     * @return Email[]
     */
    public function getForValidation(int $limit, int $daysOldOffset): array
    {
        $lastValidation = (new DateTime())->modify("-{$daysOldOffset} DAYS");
        $queryBuilder   = $this->_em->createQueryBuilder();

        $queryBuilder->select("e")
            ->from(Email::class, "e")
            ->where("e.lastValidation <= :lastValidation")
            ->orWhere("e.lastValidation IS NULL")
            ->setParameter('lastValidation', $lastValidation)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * Hard delete provided {@see Email} array, handles cascade removal of relations
     *
     * @param Email[] $emails
     *
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeEmails(array $emails): void
    {
        foreach ($emails as $email) {
            $this->_em->remove($email);
        }

        $this->_em->flush();
    }

    /**
     * @param int $maxDaysOffset
     *
     * @return Email[]
     */
    public function findAllCreatedInDaysOffset(int $maxDaysOffset): array
    {
        $minDate = (new DateTime())->modify("-{$maxDaysOffset} DAY")->format("Y-m-d H:i:s");

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("e")
            ->from(Email::class, "e")
            ->where("e.created >= :minDate")
            ->setParameter("minDate", $minDate)
            ->orderBy("e.created", "ASC");

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param string $name
     * @param string $websiteUrl
     *
     * @return string|null
     * @throws NonUniqueResultException
     */
    public function findAddressByCompany(string $name, string $websiteUrl): ?string
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->select("e.address")
            ->from(Email::class, "e")
            ->join("e.email2Company", "e2c")
            ->join("e2c.company", "c")
            ->where("c.name = :companyName")
            ->andWhere("c.website = :websiteUrl")
            ->setParameter('websiteUrl', $websiteUrl)
            ->setParameter('companyName', $name)
            ->orderBy("c.id", "DESC")
            ->setMaxResults(1);

        return $qb->getQuery()->setHydrationMode(AbstractQuery::HYDRATE_SINGLE_SCALAR)->getOneOrNullResult();
    }
}
