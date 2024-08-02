<?php

namespace JobSearcher\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use JobSearcher\Entity\Security\ApiUser;

class ApiUserFixture extends Fixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $admin = new ApiUser();
        $admin->setUsername('admin');
        $admin->setRoles(['ROLE_USER']);

        $jooblo = new ApiUser();
        $jooblo->setUsername('jooblo');
        $jooblo->setRoles(['ROLE_USER']);

        $webhook = new ApiUser();
        $webhook->setUsername('webhook');
        $webhook->setRoles(['ROLE_USER']);

        $manager->persist($admin);
        $manager->persist($jooblo);
        $manager->persist($webhook);

        $manager->flush();
    }
}