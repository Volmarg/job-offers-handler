<?php

namespace JobSearcher\Service\Jwt;

use JobSearcher\Entity\Security\ApiUser;
use JobSearcher\Security\LexitBundleJwtTokenAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provides logic for handling jwt tokens in context of {@see User}
 */
#[Route("/api/external", name: "api_external_")]
class UserJwtTokenService
{
    /**
     * This name is necessary for the {@see LexitBundleJwtTokenAuthenticator} to work properly
     */
    const USER_IDENTIFIER = "UserIdentifier";

    public function __construct(
        private readonly JwtTokenService $jwtTokenService,
    ){}

    /**
     * Will create the jwt token for {@see User}
     *
     * @param ApiUser $user
     * @param bool $endlessLifetime
     *
     * @return string
     *
     * @throws JWTEncodeFailureException
     */
    public function generate(ApiUser $user, bool $endlessLifetime = false): string
    {
        return $this->jwtTokenService->encode([
            self::USER_IDENTIFIER => $user->getUsername(),
        ], $endlessLifetime);
    }

}
