<?php

namespace JobSearcher\Service\Validation;

use Exception;
use JobSearcher\DTO\Validation\ValidationResultDTO;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidatorService
{
    const STANDARD_VIOLATIONS      = "standardViolations";
    const OBJECT_VALUES_VIOLATIONS = "objectValuesViolations";

    /**
     * @var ValidatorInterface $validator
     */
    private ValidatorInterface $validator;

    /**
     * @var ValidatorInterface $objectValuesValidator
     */
    private ValidatorInterface $objectValuesValidator;

    /**
     * ValidationService constructor.
     *
     * @param ValidatorInterface $validator
     * @param LoggerInterface    $logger
     */
    public function __construct(
        ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    )
    {
        $this->validator = $validator;

        $this->objectValuesValidator = Validation::createValidatorBuilder()
                                                 ->addMethodMapping('objectValuesValidator')
                                                 ->getValidator();
    }

    /**
     * Validates the object and returns the array of violations
     *
     * @param object $object
     * @return ValidationResultDTO
     */
    public function validateAndReturnArrayOfInvalidFieldsWithMessages(object $object): ValidationResultDTO
    {
        $validationResultDto    = new ValidationResultDTO();

        $standardViolations     = $this->getViolationsWithMessagesForValidator($object, $this->validator);
        $objectValuesViolations = $this->getViolationsWithMessagesForValidator($object, $this->objectValuesValidator);
        $allViolations          = array_merge($standardViolations, $objectValuesViolations);

        $violationsForViolationType = [
            self::STANDARD_VIOLATIONS      => $standardViolations,
            self::OBJECT_VALUES_VIOLATIONS => $objectValuesViolations,
        ];

        $validationResultDto->setSuccess(true);
        if( !empty($allViolations) ){
            $validationResultDto->setSuccess(false);
            $validationResultDto->setViolationsWithMessages($violationsForViolationType);
        }

        return $validationResultDto;
    }

    /**
     * Will validate the provided json string and return bool value:
     * - true if everything is ok
     * - false if something went wrong
     *
     * @param string $json
     * @return bool
     */
    public function validateJson(string $json): bool
    {
        json_decode($json);
        if( JSON_ERROR_NONE !== json_last_error() ){
            $this->logger->critical("Provided json is not valid", [
                "json"             => $json,
                'jsonLastErrorMsg' => json_last_error_msg(),
                "trace"            => (new Exception())->getTraceAsString(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Will return violations with messages for provided validator
     *
     * @param object $object
     * @param ValidatorInterface $validator
     * @return array
     */
    private function getViolationsWithMessagesForValidator(object $object, ValidatorInterface $validator): array
    {
        $violations             = $validator->validate($object);
        $violationsWithMessages = [];

        /**@var $constraintViolation ConstraintViolation */
        foreach($violations as $constraintViolation){
            $violationsWithMessages[$constraintViolation->getPropertyPath()] = $constraintViolation->getMessage();
        }

        return $violationsWithMessages;
    }

}