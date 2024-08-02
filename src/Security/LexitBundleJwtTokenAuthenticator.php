<?php

namespace JobSearcher\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\ExpiredTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidPayloadException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Extends the Lexit Bundle authentication logic
 */
class LexitBundleJwtTokenAuthenticator extends JWTAuthenticator
{
    public const API_STRING_MATCH     = "/api/";

    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly TokenExtractorInterface  $tokenExtractor,
        private readonly UserProviderInterface    $userProvider
    ) {
        parent::__construct($jwtManager, $dispatcher, $tokenExtractor, $userProvider);
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->getRequestUri(), self::API_STRING_MATCH);
    }

    /**
     * {@inheritDoc}
     * @param Request $request
     *
     * @return SelfValidatingPassport
     */
    public function doAuthenticate(Request $request)
    {
        $jwtToken = $request->query->get('jwt');
        if (empty($jwtToken)) {
            throw new InvalidTokenException('Jwt token is empty');
        }

        try {
            if (!$payload = $this->jwtManager->parse($jwtToken)) {
                throw new InvalidTokenException('Invalid JWT Token');
            }
        } catch (JWTDecodeFailureException $e) {
            if (JWTDecodeFailureException::EXPIRED_TOKEN === $e->getReason()) {
                throw new ExpiredTokenException();
            }

            throw new InvalidTokenException('Invalid JWT Token', 0, $e);
        }

        $idClaim = $this->jwtManager->getUserIdClaim();
        if (!isset($payload[$idClaim])) {
            throw new InvalidPayloadException($idClaim);
        }

        $passport = new SelfValidatingPassport(
            new UserBadge((string)$payload[$idClaim],
                function ($userIdentifier) use($payload) {
                    return $this->loadUser($payload, $userIdentifier);
                })
        );

        $passport->setAttribute('payload', $payload);
        $passport->setAttribute('token', $jwtToken);

        return $passport;
    }

}
