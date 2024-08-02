<?php

namespace JobSearcher\Service\Finance;

/**
 * Defines common logic or consist of data to prevent from bloating
 */
interface MoneyParserInterface
{
    const REGEX_VAR_MONEY  = "MONEY";
    const REGEX_VAR_SYMBOL = "SYMBOL";

    const REGEX_MONEY_MATCH    = "#(?<=[ 0-9])?(?<MONEY>[\d\.]+)#";
    const REGEX_CURRENCY_MATCH = "#(?<SYMBOL>[€$]+)#";
}