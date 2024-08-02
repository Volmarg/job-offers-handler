<?php

namespace JobSearcher\Service\Serialization;
use Exception;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Handles serialization of data:
 * - objects
 *
 * Into:
 * - json,
 * - array
 */
class ObjectSerializerService
{

    public function __construct(
        private readonly SerializerInterface $serializer,
    ){}

    /**
     * Will attempt to serialize object into json
     *
     * @param object $object
     * @return string
     */
    public function toJson(object $object): string
    {
        return $this->serializer->serialize($object, "json", [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            }
        ]);
    }

    /**
     * Will attempt to serialize object into array
     *
     * @param object $object
     * @return Array<string>
     */
    public function toArray(object $object): array
    {
        $json  = $this->toJson($object);
        $array = json_decode($json, true);

        return $array;
    }

    /**
     * Will deserialize provided json into target class
     *
     * @param string $json
     * @param string $targetClass
     *
     * @return object
     * @throws Exception
     */
    public function fromJson(string $json, string $targetClass): object
    {
        if (!class_exists($targetClass)) {
            throw new Exception("Tried to deserialize json to non existing class: {$targetClass}");
        }

        $object = $this->serializer->deserialize($json, $targetClass, "json");
        return $object;
    }
}