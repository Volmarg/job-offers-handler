<?php

namespace JobSearcher\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use JobSearcher\Entity\Company\Company;
use JobSearcher\Entity\Company\CompanyBranch;
use JobSearcher\Entity\Email\Email;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\JobSearchResult\General\GeneralSearchResult;
use JobSearcher\Entity\Location\Location;
use JobSearcher\Service\JobSearch\Command\Extractor\ExtractorInterface;

class JobOffersFixture extends Fixture
{
    private const SUPPORTED_DOMAINS = [
        'xing.com',
        'gowork.pl',
        'kimeta.de',
        'jobbsafari.se',
        "monster.de",
        "tideri.de",
        "es.indeed.com",
        "pracuj.pl",
        "anzeigen.jobsintown.de",
        "se.talent.com",
        "jobb.blocket.se",
        "thehub.io",
        "monster.fr"
    ];

    private const SUPPORTED_SPOKEN_LANGUAGES = [
        'Bulgarian',
        'Croatian',
        'Czech',
        'Danish',
        'Dutch',
        'English',
        'Estonian',
        'Finnish',
        'French',
        'German',
        'Greek',
        'Hungarian',
        'Irish',
        'Italian',
        'Latvian',
        'Lithuanian',
        'Maltese',
        'Polish',
        'Portuguese',
        'Romanian',
        'Slovak',
        'Slovene',
        'Spanish',
        'Swedish',
    ];

    private readonly Generator $faker;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
        $this->faker = Factory::create();

        /**
         * Using swiss locations because frontend needs VALID locations to display map properly,
         * Managed to find this provider, thus using it: {@link https://github.com/stefanzweifel/faker-swiss-locations}
         * Using some old hash version since it's the latest php8.1 friendly code state.
         */
        $this->faker->addProvider(new \Wnx\FakerSwissCities\Provider\Location($this->faker));

    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $this->purgeExtractionTable();
        $this->saveFailedExtraction($manager);  // id 1
        $this->savePendingExtraction($manager); // id 2

        $doneCount = 20; // must be static number, since these ids are then referred in voltigo-back
        for ($x = 0; $x <= $doneCount; $x++) {
            $this->saveDoneExtractions($manager); // id 3+
        }
    }

    private function saveDoneExtractions(ObjectManager $manager): void
    {
        $offersCount = $this->faker->numberBetween(30, 90);

        $extraction = $this->buildJobOfferExtraction();
        $extraction->setStatus(JobOfferExtraction::STATUS_IMPORTED);
        $extraction->setExtractionCount($offersCount);
        $extraction->setNewOffersCount($offersCount);
        $extraction->setPercentageDone(100);
        $extraction->setSources(ExtractorInterface::ALL_AVAILABLE_EXTRACTION_SOURCES);

        $manager->persist($extraction);
        $manager->flush();

        for ($x = 0; $x <= $offersCount; $x++) {
            $location      = $this->getLocation($manager, $extraction);
            $companyBranch = $this->getCompanyBranch($location, $manager);
            $company       = $this->getCompany($companyBranch, $manager);
            $email         = $this->getEmail();

            $url  = $this->faker->url();
            $host = parse_url($url, PHP_URL_HOST);
            $url  = str_replace($host, self::SUPPORTED_DOMAINS[array_rand(self::SUPPORTED_DOMAINS)], $url);

            $salaryMin = $this->faker->numberBetween(1500, 3000);
            $salaryMax = $this->faker->numberBetween($salaryMin, 5317);

            $jobOffer = new GeneralSearchResult();
            $jobOffer->setExtraction([$extraction]);
            $jobOffer->setJobPostedDateTime($this->faker->dateTimeBetween('-30 days'));
            $jobOffer->setJobTitle($this->faker->jobTitle);
            $jobOffer->setJobDescription($this->getJobDescription());
            $jobOffer->setMentionedHumanLanguages($this->getSpokenLanguages());
            $jobOffer->setJobOfferHost($host ?: $this->faker->domainName);
            $jobOffer->setOfferLanguageIsoCodeThreeDigit($this->faker->countryISOAlpha3);
            $jobOffer->setRemoteJobMentioned($this->faker->boolean);
            $jobOffer->setSalaryMin($salaryMin);
            $jobOffer->setSalaryMax($salaryMax);
            $jobOffer->setEmail($email);
            $jobOffer->setCompany($company);
            $jobOffer->setCompanyBranch($companyBranch);
            $jobOffer->setJobOfferUrl($url);
            $jobOffer->setConfigurationName($this->faker->word);
            $jobOffer->setFirstTimeFoundExtraction($extraction);
            $jobOffer->addLocation($location);
            $manager->persist($jobOffer);
        }

        $manager->flush();
    }

    private function saveFailedExtraction(ObjectManager $manager): void
    {
        $extraction = $this->buildJobOfferExtraction();
        $extraction->setStatus(JobOfferExtraction::STATUS_FAILED);

        $manager->persist($extraction);
        $manager->flush();
    }

    private function savePendingExtraction(ObjectManager $manager): void
    {
        $extraction = $this->buildJobOfferExtraction();
        $extraction->setStatus(JobOfferExtraction::STATUS_IN_PROGRESS);

        $manager->persist($extraction);
        $manager->flush();
    }

    private function buildJobOfferExtraction(): JobOfferExtraction
    {
        $extraction = new JobOfferExtraction();
        $extraction->setType(JobOfferExtraction::TYPE_ALL);
        $extraction->setStatus(JobOfferExtraction::STATUS_IN_PROGRESS);
        $extraction->setKeywords([$this->faker->word]);
        $extraction->setPaginationPagesCount($this->faker->numberBetween(0, 5));
        $extraction->setLocation($this->getCity());
        $extraction->setDistance($this->faker->numberBetween(0, 200));
        $extraction->setConfigurations([]);
        $extraction->setCountry($this->faker->countryISOAlpha3);

        return $extraction;
    }

    /**
     * This is stupid, but it's an issue with doctrine2 / dbal2.
     * By default, doctrine has some transaction handling turned on which results : "There is no active transaction".
     *
     * The problem is that for {@see JobOfferExtraction} the inserted ids must ALWAYS be the same (starting from 1),
     * "--purge-with-truncate" has the same issue.
     *
     * Adding the commit in here ain't working as well.
     * Found out that just opening transaction is working properly.
     *
     * The foreign check is off since it's otherwise impossible to wipe out the
     * extractions, and nobody cares about relations here since fixtures will
     * wipe the database state anyway.
     *
     * @throws Exception
     */
    private function purgeExtractionTable(): void
    {
        $this->entityManager->beginTransaction();
        $this->entityManager->getConnection()->executeQuery("
            SET FOREIGN_KEY_CHECKS = 0;
            TRUNCATE job_offer_extraction;
            SET FOREIGN_KEY_CHECKS = 1;
        ");
    }

    /**
     * @return string
     */
    private function getJobDescription(): string
    {
        $listElements = $this->faker->numberBetween(3, 6);

        $description = "";
        $description .= $this->faker->realTextBetween(100, $this->faker->numberBetween(200, 800)) . '<br>';
        $description .= $this->faker->realTextBetween(100, $this->faker->numberBetween(200, 800)) . '<br>';

        if ($this->faker->boolean) {
            $description .= "<ul>";
            for ($i = 0; $i < $listElements; $i++) {
                $description .= "<li>" . $this->faker->realTextBetween(50, 120) . "</li>";
            }
            $description .= "</ul><br>";
        }

        $description .= $this->faker->realTextBetween(100, $this->faker->numberBetween(200, 800)) . '<br>';

        return $description;
    }

    /**
     * @param ObjectManager      $manager
     * @param JobOfferExtraction $extraction
     *
     * @return Location
     */
    private function getLocation(ObjectManager $manager, JobOfferExtraction $extraction): Location
    {
        $location = new Location();
        $location->setName($extraction->getLocation());
        $location->setCountry($this->faker->country);
        $manager->persist($location);

        return $location;
    }

    /**
     * @param Location      $location
     * @param ObjectManager $manager
     *
     * @return CompanyBranch
     */
    private function getCompanyBranch(Location $location, ObjectManager $manager): CompanyBranch
    {
        $companyBranch = new CompanyBranch();
        $companyBranch->setLocation($location);
        $manager->persist($companyBranch);

        return $companyBranch;
    }

    /**
     * @param CompanyBranch $companyBranch
     * @param ObjectManager $manager
     *
     * @return Company
     */
    private function getCompany(CompanyBranch $companyBranch, ObjectManager $manager): Company
    {
        $company = new Company();
        $company->setWebsite($this->faker->url());
        $company->setName($this->faker->company);
        $company->addCompanyBranch($companyBranch);
        $manager->persist($company);

        return $company;
    }

    /**
     * @return Email|null
     */
    private function getEmail(): ?Email
    {
        $email = null;
        if ($this->faker->boolean(60)) {
            $email = new Email($this->faker->companyEmail);
        }

        return $email;
    }

    /**
     * @return array
     */
    private function getSpokenLanguages(): array
    {
        $languages = [];
        for ($x = 0; $x <= $this->faker->numberBetween(0, 4); $x++) {
            $languages[] = self::SUPPORTED_SPOKEN_LANGUAGES[array_rand(self::SUPPORTED_SPOKEN_LANGUAGES)];
        }

        return $languages;
    }

    /**
     * Returns the city name.
     *
     * This is wrapped into retry logic, reason:
     * - external faker provider {@link https://github.com/stefanzweifel/faker-swiss-locations}
     *   seems to have an error, and can't sometimes return proper data.
     *
     * @return string
     *
     * @throws \Exception
     */
    private function getCity(): string
    {
        $maxRetry = 10;
        for ($x = 0; $x < $maxRetry; $x++) {
            try {
                return $this->faker->city;
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new \Exception("Could not provide city name.");
    }
}