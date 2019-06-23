<?php
namespace Pirate\Classes\Environment;

class Localization
{
    private static $days = array('maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag');
    private static $months = array('januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december');

    public static function getMonths()
    {
        return static::$months;
    }

    public static function getMonth(int $index)
    {
        return static::$months[$index - 1] ?? '?';
    }
}
