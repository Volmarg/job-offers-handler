<?php

namespace JobSearcher\Service\JobSearch\Extractor\Api;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JobSearcher\DTO\JobSearch\Api\HeaderDto;
use JobSearcher\DTO\JobSearch\Api\RawBodyParametersDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Api\MainConfigurationDto;
use JobSearcher\Exception\MissingResponseDataException;
use JobSearcher\Service\Env\EnvReader;
use JobSearcher\Service\JobService\ConfigurationBuilder\Api\ApiConfigurationBuilderInterface;
use Psr\Http\Message\ResponseInterface;
use WebScrapperBundle\Service\Request\Guzzle\GuzzleService;
use WebScrapperBundle\Service\ScrapEngine\CliCurlScrapEngine;
use WebScrapperBundle\Service\ScrapEngine\ScrapEngineInterface;

/**
 * Handles the calls for the {@see ExtractorService}
 */
class CallHandlerService
{
    private MainConfigurationDto $mainConfigurationDto;

    public function getMainConfigurationDto(): MainConfigurationDto
    {
        return $this->mainConfigurationDto;
    }

    public function setMainConfigurationDto(MainConfigurationDto $mainConfigurationDto): void
    {
        $this->mainConfigurationDto = $mainConfigurationDto;
    }

    public function __construct(
        private readonly GuzzleService $guzzleService,
        private readonly CliCurlScrapEngine $cliCurlScrapEngine
    ){}

    /**
     * Will make call for given uri and data
     *
     * @param string $calledMethod
     * @param string $calledUri
     * @param array  $headers
     * @param array  $bodyParameters
     * @param array  $guzzleBody
     *
     * @return array
     * @throws MissingResponseDataException
     * @throws GuzzleException
     * @throws Exception
     */
    public function makeCall(
        string $calledMethod,
        string $calledUri,
        array $headers        = [],
        array $bodyParameters = [],
        array $guzzleBody     = []
    ): array
    {
        $usedEngine = $this->getMainConfigurationDto()->getSearchUriConfigurationDto()->getScrapEngine() ?? ApiConfigurationBuilderInterface::SCRAP_ENGINE_GUZZLE;
        return match($usedEngine){
            ApiConfigurationBuilderInterface::SCRAP_ENGINE_GUZZLE   => $this->makeGuzzleCall(...func_get_args()),
            ApiConfigurationBuilderInterface::SCRAP_ENGINE_CLI_CURL => $this->makeCliCurlCall(...func_get_args()),
            default                                                 => throw new Exception("Unsupported api scrap engine: {$usedEngine}"),
        };
    }

    /**
     * Will make guzzle call for given url with headers & body
     *
     * @param string                 $calledMethod
     * @param string                 $calledUri
     * @param HeaderDto[]            $headers
     * @param RawBodyParametersDto[] $bodyParameters
     * @param array                  $guzzleBody
     *
     * @return array - array containing parsed response
     * @throws MissingResponseDataException
     * @throws Exception
     */
    private function makeGuzzleCall(
        string $calledMethod,
        string $calledUri,
        array $headers        = [],
        array $bodyParameters = [],
        array $guzzleBody     = []
    ): array
    {
        if (empty($guzzleBody)) {
            $guzzleBody = $this->buildBodyParametersArray($bodyParameters);
        }

        $guzzleHeaders = [];
        foreach($headers as $headerDto){
            $guzzleHeaders[$headerDto->getName()] = $headerDto->getValue();
        }

        $method = strtolower($calledMethod);

        $this->guzzleService->setIsWithProxy(EnvReader::isProxyEnabled());
        $this->guzzleService->setJsonBody($guzzleBody);
        $this->guzzleService->setHeaders($guzzleHeaders);

        /**@var ResponseInterface $result */
        $result      = $this->guzzleService->{$method}($calledUri);
        $content     = $result->getBody()->getContents();
        $arrayOfData = json_decode($content, true);

        if (empty($arrayOfData)) {
            $message ="
                Something went wrong while trying to fetch data via " . self::class . ". 
                Array is empty! Called url: {$calledUri}.
                Maybe the page dom changed or it's no longer using api calls.
            ";

            throw new MissingResponseDataException($message);
        }

        return $arrayOfData;
    }

    /**
     * Will make a call via {@see CliCurlScrapEngine} for given url with headers & body
     *
     * @param string                 $calledMethod
     * @param string                 $calledUri
     * @param HeaderDto[]            $headers
     * @param RawBodyParametersDto[] $bodyParameters
     * @param array                  $guzzleBody
     *
     * @return array - array containing parsed response
     *
     * @throws Exception
     * @throws GuzzleException
     */
    private function makeCliCurlCall(
        string $calledMethod,
        string $calledUri,
        array $headers        = [],
        array $bodyParameters = [],
        array $guzzleBody     = []
    ): array
    {
        if (empty($guzzleBody)) {
            $guzzleBody = $this->buildBodyParametersArray($bodyParameters);
        }

        $usedHeaders = [];
        foreach($headers as $headerDto){
            $usedHeaders[$headerDto->getName()] = $headerDto->getValue();
        }

        $content = $this->cliCurlScrapEngine->scrap($calledUri, [
            ScrapEngineInterface::CONFIGURATION_USE_PROXY => true,
            ScrapEngineInterface::CONFIGURATION_HEADERS   => $usedHeaders,
            ScrapEngineInterface::CONFIGURATION_BODY      => $guzzleBody,
            ScrapEngineInterface::CONFIGURATION_METHOD    => $calledMethod,
        ]);

        $arrayOfData = json_decode($content, true);
        if (empty($arrayOfData)) {
            $message ="
                Something went wrong while trying to fetch data via " . self::class . ". 
                Array is empty! Called url: {$calledUri}.
                Maybe the page dom changed or it's no longer using api calls.
            ";

            throw new MissingResponseDataException($message);
        }

        return $arrayOfData;
    }

    /**
     * Will build array with raw body parameters that can be sent with request
     *
     * @param RawBodyParametersDto[] $rawBodyArray
     *
     * @return array
     * @throws Exception
     */
    public function buildBodyParametersArray(array $rawBodyArray): array
    {
        $bodyParams = [];
        foreach($rawBodyArray as $bodyDto){
            $bodyParams = array_merge($bodyParams, $this->buildRawBodyFromSingleRawBodyParameterDto($bodyDto, []));
        }

        return $bodyParams;
    }

    /**
     * Will return single array that can be inserted for api call (as `body`)
     *
     * @param RawBodyParametersDto $rawBodyParametersDto
     * @param array                $bodyParameters
     *
     * @return array
     * @throws Exception
     */
    private function buildRawBodyFromSingleRawBodyParameterDto(
        RawBodyParametersDto $rawBodyParametersDto,
        array                $bodyParameters = [],
    ): array
    {
        if( $rawBodyParametersDto->hasChildren() ){
            $allChildrenParameters = [];
            foreach($rawBodyParametersDto->getChildren() as $rawBodyParametersChildDto){
                $childBodyParameters = $this->buildRawBodyFromSingleRawBodyParameterDto(
                    $rawBodyParametersChildDto,
                    $allChildrenParameters,
                );
                $allChildrenParameters = array_merge($allChildrenParameters, $childBodyParameters);
            }
            $bodyParameters[$rawBodyParametersDto->getName()] = $allChildrenParameters;

            return $bodyParameters;
        }

        $bodyParameters[$rawBodyParametersDto->getName()] = $rawBodyParametersDto->getValue();

        return $bodyParameters;
    }

}