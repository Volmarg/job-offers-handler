<?php

namespace JobSearcher\Service;

class EncodingService
{
    /**
     * Turns "Ó" into "O" etc.
     *
     * @param string $text
     *
     * @return string
     */
    public static function polishCharsToStandardChars(string $text): string
    {
        $mapping = [
            "Ą" => "A",
            "Ć" => "C",
            "Ę" => "E",
            "Ł" => "L",
            "Ń" => "N",
            "Ó" => "O",
            "Ś" => "S",
            "Ź" => "Z",
            "Ż" => "Z",
        ];

        foreach ($mapping as $from => $to) {
            $text = str_replace($from, $to, $text);
            $text = str_replace(mb_strtolower($from), mb_strtolower($to), $text);
        }

        return $text;
    }

}
