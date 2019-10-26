<?php
namespace Pirate\Wheel;

class AdminRoute extends Route
{
    /**
     * Geef een lijst van alle available pages terug per permission.
     * Permission '' is voor iedereen
     */
    public static function getAvailablePages()
    {
        return [];
    }
}
