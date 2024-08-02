<?php

namespace JobSearcher\Doctrine\Type;

class ArrayType
{
    /**
     * This is enforced by doctrine. This has to be used to check if array type in dby is empty
     */
    public const EMPTY_ARRAY_VALUE = "N;";

    /**
     * This is enforced by doctrine, this means - empty array
     */
    public const SERIALIZED_EMPTY_ARRAY_VALUE = "a:0:{}";
}