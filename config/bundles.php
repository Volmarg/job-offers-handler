<?php

use JoobloBridge\JoobloBridgeBundle;
use ProxyProviderBridge\ProxyProviderBridgeBundle;

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class => ['dev' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    KeywordsFinder\KeywordsFinderBundle::class => ['all' => true],
    Lingua\LinguaBundle::class => ['all' => true],
    WebScrapperBundle\WebScrapperBundle::class => ['all' => true],
    GeoTool\GeoToolBundle::class => ['all' => true],
    DataParser\DataParserBundle::class => ['all' => true],
    SearchEngineProvider\SearchEngineProviderBundle::class => ['all' => true],
    CompanyDataProvider\CompanyDataProviderBundle::class => ['all' => true],
    SmtpEmailValidatorBundle\SmtpEmailValidatorBundle::class => ['all' => true],
    OldSound\RabbitMqBundle\OldSoundRabbitMqBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle::class => ['all' => true],
    Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class => ['all' => true],
    JoobloBridgeBundle::class => ["all" => true],
    ProxyProviderBridgeBundle::class => ['all' => true],
    Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class => ['all' => true],
];
