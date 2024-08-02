<?php

namespace JobSearcher\Repository\Security;

use JobSearcher\Entity\Security\ApiUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ApiUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiUser[]    findAll()
 * @method ApiUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */

class ApiUserRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, ApiUser::class);
    }

    /**
     * Will return one user for given username
     * or if no user was found then null is being returned
     *
     * @param string $username
     * @return ApiUser|null
     */
    public function findOneByName(string $username): ?ApiUser
    {
        $entity = $this->findOneBy([
            ApiUser::USERNAME_FIELD => $username,
        ]);

        return $entity;
    }

}
