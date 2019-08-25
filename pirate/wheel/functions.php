<?php
use Pirate\Sails\Environment\Classes\Localization;

// Todo: these functions should get moved to a formatter class

function datetimeToUrl($datetime)
{
    return $datetime->format('Y') . '/' . $datetime->format('m') . '/' . $datetime->format('d');
}

function datetimeToDayMonth($datetime)
{
    return $datetime->format('j') . ' ' . Localization::getMonth($datetime->format('n') + 0);
}

function clean_special_chars($s, $d = false)
{
    if ($d) {
        $s = utf8_decode($s);
    }

    $chars = array(
        '_' => '/`|´|\^|~|¨|ª|º|©|®/',
        'a' => '/à|á|ả|ạ|ã|â|ầ|ấ|ẩ|ậ|ẫ|ă|ằ|ắ|ẳ|ặ|ẵ|ä|å|æ/',
        'd' => '/đ/',
        'e' => '/è|é|ẻ|ẹ|ẽ|ê|ề|ế|ể|ệ|ễ|ë/',
        'i' => '/ì|í|ỉ|ị|ĩ|î|ï/',
        'o' => '/ò|ó|ỏ|ọ|õ|ô|ồ|ố|ổ|ộ|ỗ|ö|ø/',
        'u' => '/ù|ú|û|ũ|ü|ů|ủ|ụ|ư|ứ|ừ|ữ|ử|ự/',
        'A' => '/À|Á|Ả|Ạ|Ã|Â|Ầ|Ấ|Ẩ|Ậ|Ẫ|Ă|Ằ|Ắ|Ẳ|Ặ|Ẵ|Ä|Å|Æ/',
        'D' => '/Đ/',
        'E' => '/È|É|Ẻ|Ẹ|Ẽ|Ê|Ề|Ế|Ể|Ệ|Ễ|Ê|Ë/',
        'I' => '/Ì|Í|Ỉ|Ị|Ĩ|Î|Ï/',
        'O' => '/Ò|Ó|Ỏ|Ọ|Õ|Ô|Ồ|Ố|Ổ|Ộ|Ỗ|Ö|Ø/',
        'U' => '/Ù|Ú|Û|Ũ|Ü|Ů|Ủ|Ụ|Ư|Ứ|Ừ|Ữ|Ử|Ự/',
        'c' => '/ć|ĉ|ç/',
        'C' => '/Ć|Ĉ|Ç/',
        'n' => '/ñ/',
        'N' => '/Ñ/',
        'y' => '/ý|ỳ|ỷ|ỵ|ỹ|ŷ|ÿ/',
        'Y' => '/Ý|Ỳ|Ỷ|Ỵ|Ỹ|Ŷ|Ÿ/',
    );

    return preg_replace("/[^A-Za-z0-9]/", '', strtolower(trim(preg_replace($chars, array_keys($chars), $s))));
}

function sluggify($s, $d = false)
{
    if ($d) {
        $s = utf8_decode($s);
    }

    $chars = array(
        '_' => '/`|´|\^|~|¨|ª|º|©|®/',
        'a' => '/à|á|ả|ạ|ã|â|ầ|ấ|ẩ|ậ|ẫ|ă|ằ|ắ|ẳ|ặ|ẵ|ä|å|æ/',
        'd' => '/đ/',
        'e' => '/è|é|ẻ|ẹ|ẽ|ê|ề|ế|ể|ệ|ễ|ë/',
        'i' => '/ì|í|ỉ|ị|ĩ|î|ï/',
        'o' => '/ò|ó|ỏ|ọ|õ|ô|ồ|ố|ổ|ộ|ỗ|ö|ø/',
        'u' => '/ù|ú|û|ũ|ü|ů|ủ|ụ|ư|ứ|ừ|ữ|ử|ự/',
        'A' => '/À|Á|Ả|Ạ|Ã|Â|Ầ|Ấ|Ẩ|Ậ|Ẫ|Ă|Ằ|Ắ|Ẳ|Ặ|Ẵ|Ä|Å|Æ/',
        'D' => '/Đ/',
        'E' => '/È|É|Ẻ|Ẹ|Ẽ|Ê|Ề|Ế|Ể|Ệ|Ễ|Ê|Ë/',
        'I' => '/Ì|Í|Ỉ|Ị|Ĩ|Î|Ï/',
        'O' => '/Ò|Ó|Ỏ|Ọ|Õ|Ô|Ồ|Ố|Ổ|Ộ|Ỗ|Ö|Ø/',
        'U' => '/Ù|Ú|Û|Ũ|Ü|Ů|Ủ|Ụ|Ư|Ứ|Ừ|Ữ|Ử|Ự/',
        'c' => '/ć|ĉ|ç/',
        'C' => '/Ć|Ĉ|Ç/',
        'n' => '/ñ/',
        'N' => '/Ñ/',
        'y' => '/ý|ỳ|ỷ|ỵ|ỹ|ŷ|ÿ/',
        'Y' => '/Ý|Ỳ|Ỷ|Ỵ|Ỹ|Ŷ|Ÿ/',
    );

    return trim(preg_replace("/[^A-Za-z0-9]+/", '-', strtolower(trim(preg_replace($chars, array_keys($chars), $s)))), '-');
}

function datetimeToDateString($datetime)
{
    $jaar = $datetime->format('Y');
    $now = new DateTime();
    if ($jaar == date("Y") && $now <= $datetime) {
        $jaar = '';
    } else {
        $jaar = ' ' . $jaar;
    }
    return $datetime->format('j') . ' ' . Localization::getMonth($datetime->format('n') + 0) . $jaar;
}

function datetimeToMonthYear($datetime)
{
    $jaar = $datetime->format('Y');
    return Localization::getMonth($datetime->format('n') + 0) . ' ' . $jaar;
}

function datetimeToWeekday($datetime)
{
    return Localization::getDay($datetime->format('N') + 0);
}

function datetimeToShortWeekday($datetime)
{
    return substr(datetimeToWeekday($datetime), 0, 2);
}

function snippetFromHtml($content)
{
    $snippet = $content;
    $snippet = strip_tags($snippet);

    if (strlen($snippet) > 400) {
        $snippet = substr($snippet, 0, 400) . '...';
    }
    $snippet = str_replace(array("\r", "\n"), ' ', $snippet);
    $snippet = trim(preg_replace('/\s\s+/', ' ', $snippet));

    return $snippet;
}

function ucsentence($text)
{
    return preg_replace_callback('/([.!?])\s*(\w)/', function ($matches) {
        return strtoupper($matches[1] . ' ' . $matches[2]);
    }, ucfirst(trim($text)));
}

function dashesToCamelCase($string, $capitalizeFirstCharacter = false)
{

    $str = str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));

    if (!$capitalizeFirstCharacter) {
        $str[0] = strtolower($str[0]);
    }

    return $str;
}

/// GDImage => gd-image  HelloWorld => hello-world TESTDing => test-ding GD1 => gd-1 Hallo123World => hallo-123-world
function camelCaseToDashes($className)
{
    return strtolower(preg_replace('/([0-9])([a-zA-Z])/', '$1-$2', preg_replace('/([a-zA-Z])([0-9])/', '$1-$2', preg_replace('/([a-zA-Z])([A-Z])(?=[a-z])/', '$1-$2', $className))));
}

function obfuscateEmail($email)
{
    $em = explode("@", $email);
    $name = implode(array_slice($em, 0, count($em) - 1), '@');
    $len = floor(strlen($name) / 2);

    return substr($name, 0, $len) . str_repeat('*', $len) . "@" . end($em);
}

function strposa($haystack, $needles = array(), $offset = 0)
{
    $chr = array();
    foreach ($needles as $needle) {
        $res = strpos($haystack, $needle, $offset);
        if ($res !== false) {
            $chr[$needle] = $res;
        }

    }
    if (empty($chr)) {
        return false;
    }

    return min($chr);
}
