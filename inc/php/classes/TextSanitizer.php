<?php

class TextSanitizer
{
    /**
     * Takes a word or phrase and converts it to a url safe and consistent format.
     * Replaces all non alpha numeric characters to dashes (-),
     * with the exception of apostrophes ('), which get removed completely. This prevents weird situations like "Queen Mary's" => "Queen-Mary-s"
     * All dashes are then "de-duplicated" so dashes are never adjacent to other slashes. "Shelves & Racks" => "Shelves---Racks" => "Shelves-Racks"
     * Dashes will never be the first or last character in the resulting string (They are trimmed off)
     *
     * Examples of this logic being used to format url params:
     * Fwe.com/learn
     * Fwe.com/Literature
     *
     * @param $parameter
     * @return String $urlWithDashes
     */
    static function websiteUrlParameterFormatter($parameter){
        $parameterWithoutQuotes = str_replace("'","",$parameter);
        $parameterWithLotsOfDashes = preg_replace('/[^a-zA-Z0-9\-]/',"-",$parameterWithoutQuotes);
        $parameterWithDashes = preg_replace('/-{2,}/',"-",$parameterWithLotsOfDashes);
        $trimmedParameterWithDashes = trim($parameterWithDashes,"-");
        return $trimmedParameterWithDashes;
    }

    static function formatFileName($fileName)
    {
        return preg_replace("/[^a-z0-9-]/","_", strtolower($fileName));
    }

    static function fixBrokenUTF8($text){
        $text = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8' );

        $utf8CharsToReplace = array(
            "\xC2\xAB"      => '<<<' ,       // (U+00AB) left triple angle bracket
            "\xC2\xBB"      => '>>>' ,       // (U+00BB) right triple angle bracket
            "\xE2\x80\xB9"  => '<' ,         // (U+2039) left angle bracket
            "\xE2\x80\xBA"  => '>' ,         // (U+203A) right angle bracket
            "\xE2\x80\x93"  => '-',          // (U+2013) half dash
            "\xE2\x80\x94"  => '-',          // (U+2014) long dash
            "\xE2\x80\xA6"  => '...',        // (U+2026) triple dots
            "\xC2\xBD"      => ' 1/2',       // (U+00BB) half

            "\xC2\xB0"      => '&deg;',      // (U+00B0) degrees
            "\xB0"          => '&deg;',      // (U+00B0) degrees
            "\xC2\xA9"      => '&#169;',     // (U+00A9) Copyright
            "\xA9"          => '&#169;',     // (U+00A9) Copyright
            "\xC2\xAE"      => '&#174;',     // (U+00AE) Registered
            "\xAE"          => '&#174;',     // (U+00AE) Registered
            "\xE2\x84\xA2"  => "&#8482;",    // (U+2018) Trademark
        );
        $search = array_keys($utf8CharsToReplace);
        $replace = array_values($utf8CharsToReplace);
        $text = str_replace($search, $replace, $text);

        $text = TextSanitizer::replaceUtf8Quotes($text);

        //convert weird ms office chars to normal ascii
        //$text = iconv('UTF-8', 'cp1252//TRANSLIT//IGNORE', $text);
        //$text = iconv('CP1252', 'ASCII//TRANSLIT//IGNORE', $text);

        //strip anything not printable lower 128 ascii
        $text = preg_replace('/[^\x09-\x7F]/', '', $text); // force strip anything non lower 128

        return $text;
    }
    static function sanitizeWebsiteText($text)
    {
        $text = TextSanitizer::fixBrokenUTF8($text);
        //normalize newlines, first convert to BR tags, then next convert all to \r\n
        $replace_search = array("\r\n","\r","\n",'&nbsp;');
        $replace_replace = array('<BR>','<BR>','<BR>',' ');
        $text = str_ireplace($replace_search, $replace_replace, $text);
        $text = strip_tags($text,'<br><b><u>');
        $text = str_ireplace('<BR>',"\r\n",$text);


        $text = trim($text);

        return $text;

        //return filter_var($text, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_ENCODE_HIGH);
    }
    static function replaceUtf8Quotes($text)
    {

        $quotes = array(
            // Windows codepage 1252
            "\xC2\x82" => "'", // U+0082⇒U+201A single low-9 quotation mark
            "\xC2\x84" => '"', // U+0084⇒U+201E double low-9 quotation mark
            "\xC2\x8B" => "'", // U+008B⇒U+2039 single left-pointing angle quotation mark
            "\xC2\x91" => "'", // U+0091⇒U+2018 left single quotation mark
            "\xC2\x92" => "'", // U+0092⇒U+2019 right single quotation mark
            "\xC2\x93" => '"', // U+0093⇒U+201C left double quotation mark
            "\xC2\x94" => '"', // U+0094⇒U+201D right double quotation mark
            "\xC2\x9B" => "'", // U+009B⇒U+203A single right-pointing angle quotation mark

            // Regular Unicode     // U+0022 quotation mark (")
            // U+0027 apostrophe     (')
            "\xC2\xAB"     => '"', // U+00AB left-pointing double angle quotation mark
            "\xC2\xBB"     => '"', // U+00BB right-pointing double angle quotation mark
            "\xE2\x80\x98" => "'", // U+2018 left single quotation mark
            "\xE2\x80\x99" => "'", // U+2019 right single quotation mark
            "\xE2\x80\x9A" => "'", // U+201A single low-9 quotation mark
            "\xE2\x80\x9B" => "'", // U+201B single high-reversed-9 quotation mark
            "\xE2\x80\x9C" => '"', // U+201C left double quotation mark
            "\xE2\x80\x9D" => '"', // U+201D right double quotation mark
            "\xE2\x80\x9E" => '"', // U+201E double low-9 quotation mark
            "\xE2\x80\x9F" => '"', // U+201F double high-reversed-9 quotation mark
            "\xE2\x80\xB9" => "'", // U+2039 single left-pointing angle quotation mark
            "\xE2\x80\xBA" => "'", // U+203A single right-pointing angle quotation mark
        );
        $search = array_keys($quotes);
        $replace = array_values($quotes);
        return str_replace($search, $replace, $text);

    }
}

?>