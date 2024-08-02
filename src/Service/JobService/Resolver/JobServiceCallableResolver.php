<?php

namespace JobSearcher\Service\JobService\Resolver;

use JobSearcher\Exception\JobServiceCallableResolverException;

/**
 * Handles checking if provider can/should be used - performs calls to the providers "per job service"
 * This logic is necessary due to some services (see first use case "monster.de") requiring data to be called "on - fly"
 */
class JobServiceCallableResolver
{
    // avoid using "::" because of calling/auto-resolving constants this way in yaml
    private const CLASS_METHOD_SEPARATOR = "->";

    /**
     * @var string $classNamespace
     */
    private string $classNamespace;

    /**
     * @var string $methodName
     */
    private string $methodName;

    /**
     * @var string|null $classMethodString
     */
    private ?string $classMethodString;

    /**
     * String which will be used to extract the called class & method
     *
     * @param string|null $classMethodString
     */
    public function setClassMethodString(?string $classMethodString): void
    {
        $this->classMethodString = $classMethodString;
    }

    /**
     * Will attempt to resolve value for callable,
     * NULL is strictly reserved for a case where there is no callable to call
     *
     * @var array $parameters - parameters to be used in resolved methods
     *
     * @return mixed|null
     *
     * @throws JobServiceCallableResolverException
     */
    public function resolveValue(array $parameters = []): mixed
    {
        $this->resolveCallable();

        if(
                isset($this->classNamespace)
            &&  isset($this->methodName)
        ) {
            $class = $this->initializeCalledClass();
            $value = $class->{$this->methodName}($parameters);

            if (is_null($value)) {
                $message = "Called method: {$this->methodName} of class {$this->classNamespace} returned `null`. "
                         . "This is not allowed as null is strictly reserved for case when callable does not exist";
                throw new JobServiceCallableResolverException($message);
            }

            $this->reset();
            return $value;
        }

        $this->reset();
        return null;
    }

    /**
     * Will try to resolve the called class and method. If successful then will set these on:
     * - {@see JobServiceCallableResolver::$methodName}
     * - {@see JobServiceCallableResolver::$classNamespace}
     *
     * @throws JobServiceCallableResolverException
     */
    private function resolveCallable(): void
    {
        // null or empty string etc. - can happen with old logic of resolving body out of searched keywords
        if (empty($this->classMethodString)) {
            return;
        }

        $classAndMethod     = explode(self::CLASS_METHOD_SEPARATOR, $this->classMethodString);
        $arrayElementsCount = count($classAndMethod);

        // not callable at all - ignore it
        if (!str_contains($this->classMethodString, self::CLASS_METHOD_SEPARATOR)) {
            return;
        }

        if ($arrayElementsCount !== 2) {
            $message = "Provided string seems to be callable but it is malformed as it contains given characters to many times: "
                       . self::CLASS_METHOD_SEPARATOR . ". Expected exactly: 2, got: ". $arrayElementsCount;
            throw new JobServiceCallableResolverException($message);
        }

        $classNamespace = $classAndMethod[0];
        $methodName     = $classAndMethod[1];
        if (!class_exists($classNamespace)) {
            $message = "Tried to resolve callable, but given class does not exists: " . $classNamespace
                     . ". Expected to get FQN";
            throw new JobServiceCallableResolverException($message);
        }

        if (!method_exists($classNamespace, $methodName)) {
            throw new JobServiceCallableResolverException("Tried to resolve callable, but given class has no method named: " . $methodName);
        }

        // everything went fine, only now saving to the static props
        $this->classNamespace = $classNamespace;
        $this->methodName     = $methodName;
    }

    /**
     * Will initialize the job service resolver class, return type points to {@see JobServiceResolver}
     * as all the job service resolvers are supposed to extend from it,
     *
     * All the injections to the constructor etc. happen here
     *
     * @return JobServiceResolver
     */
    private function initializeCalledClass(): JobServiceResolver
    {
        /** @var JobServiceResolver $class **/
        $class = new $this->classNamespace(); // constructor
        $class->init();

        return $class;
    }

    /**
     * Will reset the properties to prevent issues when the resolving logic is called in loop
     * and once property is set then it will return incorrect data even in places where no callable was defined to call
     */
    private function reset(): void
    {
        unset($this->classMethodString);
        unset($this->classNamespace);
        unset($this->methodName);
    }
}