<?php

namespace JobSearcher\Service\Jwt;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;

/**
 * Provides base logic for jwt tokens handling
 */
class JwtTokenService implements JWTEncoderInterface
{
    // just random high number and anybody will be dead in 100 years anyway, that's enough time to block the mail
    private const TOKEN_LONG_LIFETIME__YEARS = 100;

    public function __construct(
        private readonly JWTEncoderInterface $encoder
    ){}

    /**
     * @param array $data
     * @param bool  $endlessLifetime
     *
     * @return string
     * @throws JWTEncodeFailureException
     */
    public function encode(array $data, bool $endlessLifetime = false): string
    {
        /**
         * Has to be done this way due to external package logic which takes project defined `ttl` and generate low exp from it
         * if no `exp` is provided in payload
         */
        if ($endlessLifetime) {
            $data['exp'] = $this->buildLargeExpirationOffset();
        }

        return $this->encoder->encode($data);
    }

    /**
     * Return payload array or NULL if something is wrong with the token
     *
     * @param string $token
     *
     * @return array|null
     *
     * @throws JWTDecodeFailureException
     */
    public function decode($token): ?array
    {
        return $this->encoder->decode($token);
    }

    /**
     * @return int
     */
    private function buildLargeExpirationOffset(): int
    {
        $expirationStamp = (new \DateTime())->modify("+" . self::TOKEN_LONG_LIFETIME__YEARS . " YEARS")->getTimestamp();

        return $expirationStamp;
    }

}
