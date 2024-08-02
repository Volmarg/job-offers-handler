<?php

namespace JobSearcher\Service\Finance;

/**
 * Handles all kind of money parsing logic
 */
class MoneyParserService implements MoneyParserInterface
{

    /**
     * Will extract money from the string
     *
     * @param string|null $stringToParse
     * @return string
     */
    public function extractMoneyFromString(?string $stringToParse): string
    {
        if( empty($stringToParse) ){
            return "";
        }

        preg_match(self::REGEX_MONEY_MATCH, $stringToParse, $matches);
        if( empty($matches) ){
            return 0;
        }

        $money = $matches[self::REGEX_VAR_MONEY] ?? "";
        if( empty($money) ){
            return 0;
        }

        $trimmedMoney                  = trim($money);
        $moneyWithoutSpecialCharacters = preg_replace("#[,\.]#", "", $trimmedMoney);
        return $moneyWithoutSpecialCharacters;
    }

    /**
     * Will extract currency/symbol from the string
     *
     * @param string|null $stringToParse
     * @return string
     */
    public function extractSymbolOrCurrencyFromString(?string $stringToParse): string
    {
        if( empty($stringToParse) ){
            return "";
        }

        preg_match(self::REGEX_CURRENCY_MATCH, $stringToParse, $matches);
        if( empty($matches) ){
            return "";
        }

        $money = $matches[self::REGEX_VAR_SYMBOL] ?? "";
        if( empty($money) ){
            return "";
        }

        return trim($money);
    }


}