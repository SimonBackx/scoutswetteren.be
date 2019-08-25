<?php
namespace Pirate\Sails\Environment\Classes;

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

    public static function getDay(int $index)
    {
        return static::$days[$index - 1] ?? '?';
    }
}
