<?php


namespace JobSearcher\Response;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Base Response used for each response in the API calls
 * Each response should extend from this class as the fronted will try to build same response on its side
 */
class BaseApiResponse
{
    const KEY_CODE    = "code";
    const KEY_MESSAGE = "message";
    const KEY_SUCCESS = "success";

    const DEFAULT_CODE         = Response::HTTP_BAD_REQUEST;
    const DEFAULT_MESSAGE      = "Bad request";
    const MESSAGE_INVALID_JSON = "INVALID_JSON";
    const MESSAGE_OK           = "OK";
    const MESSAGE_NOT_FOUND    = "NOT_FOUND";
    const MESSAGE_UNAUTHORIZED = "UNAUTHORIZED";

    /**
     * @var int $code
     */
    private int $code = Response::HTTP_BAD_REQUEST;

    /**
     * @var string $message
     */
    private string $message = "";

    /**
     * @var bool $success
     */
    private bool $success = false;

    /**
     * @var array $invalidFields
     */
    private array $invalidFields = [];

    // Muting Php stan - prevent extending and changing construct when calling `new static()`
    final public function __construct(
        ?int    $code    = null,
        ?string $message = null,
        ?bool   $success = null
    ){
        if( !empty($code) ){
            $this->code = $code;
        }

        if( !empty($message) ){
            $this->message = $message;
        }

        if( !empty($success) ){
            $this->success = $success;
        }
    }


    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @param int $code
     */
    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     */
    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    /**
     * @return array
     */
    public function getInvalidFields(): array
    {
        return $this->invalidFields;
    }

    /**
     * @param array $invalidFields
     */
    public function setInvalidFields(array $invalidFields): void
    {
        $this->invalidFields = $invalidFields;
    }

    /**
     * Will set the field of this response to success response so that classes which extend this method will have
     * the base response `set to success`
     */
    public function prefillBaseFieldsForSuccessResponse(): void
    {
        $this->setCode(Response::HTTP_OK);;
        $this->setSuccess(true);
    }

    /**
     * Will set the field of this response to bad request response so that classes which extend this method will have
     * the base response response `set to bad request`
     */
    public function prefillBaseFieldsForBadRequestResponse(): void
    {
        $this->setCode(Response::HTTP_BAD_REQUEST);;
        $this->setSuccess(false);
    }

    /**
     * Will build internal server error response
     *
     * @param string|null $message
     * @return static
     */
    public static function buildInternalServerErrorResponse(?string $message = null): static
    {
        $response = new static();
        $response->setCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        $response->setSuccess(false);

        if( !empty($message) ){
            $response->setMessage($message);
        }

        return $response;
    }

    /**
     * Will build internal server error response
     *
     * @param string $message
     * @return static
     */
    public static function buildBadRequestErrorResponse(string $message = ""): static
    {
        $response = new static();
        $response->setCode(Response::HTTP_BAD_REQUEST);
        $response->setSuccess(false);
        $response->setMessage($message);

        return $response;
    }

    /**
     * Will build ok response
     *
     * @return static
     * @var string $message
     */
    public static function buildOkResponse(string $message = self::MESSAGE_OK): static
    {
        $response = new static();
        $response->setCode(Response::HTTP_OK);
        $response->setSuccess(true);
        $response->setMessage($message);

        return $response;
    }

    /**
     * Will build 404 response
     */
    public static function buildNotFoundResponse(): static
    {
        $response = new static();
        $response->setCode(Response::HTTP_NOT_FOUND);
        $response->setSuccess(false);
        $response->setMessage(self::MESSAGE_NOT_FOUND);

        return $response;
    }

    /**
     * Will build invalid json response
     *
     * @return static
     */
    public static function buildInvalidJsonResponse(): static
    {
        $response = static::buildBadRequestErrorResponse();
        $response->setMessage(self::MESSAGE_INVALID_JSON);
        return $response;
    }

    /**
     * Will build unauthorized json response
     *
     * @param string $message
     * @return static
     */
    public static function buildUnauthorizedResponse(string $message = self::MESSAGE_UNAUTHORIZED): static
    {
        $response = new static();
        $response->setCode(Response::HTTP_UNAUTHORIZED);
        $response->setSuccess(false);
        $response->setMessage($message);
        return $response;
    }

    /**
     * Will build internal server error response
     *
     * @param string $message
     * @param array  $invalidFields
     * @return static
     */
    public static function buildInvalidFieldsRequestErrorResponse(array $invalidFields = [], string $message = ""): static
    {
        $response = new static();
        $response->setCode(Response::HTTP_BAD_REQUEST);
        $response->setSuccess(false);
        $response->setMessage($message);
        $response->setInvalidFields($invalidFields);

        return $response;
    }

    /**
     * @param int $responseCode
     * @return JsonResponse
     */
    public function toJsonResponse(int $responseCode = Response::HTTP_OK): JsonResponse
    {
        $encoder    = new JsonEncoder();
        $normalizer = new ObjectNormalizer();
        $serializer = new Serializer([$normalizer], [$encoder]);

        $json  = $serializer->serialize($this, "json");
        $array = json_decode($json, true);

        return new JsonResponse($array, $responseCode);
    }

    /**
     * Will build the response from json
     *
     * @param string $json
     * @return BaseApiResponse
     */
    public static function fromJson(string $json): BaseApiResponse
    {
        $dataArray = json_decode($json, true);

        $message = AbstractHandler::checkAndGetKey($dataArray, self::KEY_MESSAGE, self::DEFAULT_MESSAGE);
        $code    = AbstractHandler::checkAndGetKey($dataArray, self::KEY_CODE, self:: DEFAULT_CODE);
        $success = AbstractHandler::checkAndGetKey($dataArray, self::KEY_SUCCESS, false);

        $response = new BaseApiResponse();
        $response->setMessage($message);
        $response->setCode($code);
        $response->setSuccess($success);

        return $response;
    }

    /**
     * @param BaseApiResponse $baseApiResponse
     * @return static
     */
    public static function buildFromBaseApiResponse(BaseApiResponse $baseApiResponse): static
    {
        $childResponse = new static();
        $childResponse->setMessage($baseApiResponse->getMessage());
        $childResponse->setSuccess($baseApiResponse->isSuccess());
        $childResponse->setInvalidFields($baseApiResponse->getInvalidFields());
        $childResponse->setCode($baseApiResponse->getCode());

        return $childResponse;
    }

}