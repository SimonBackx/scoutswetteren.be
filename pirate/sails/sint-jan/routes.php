<?php
namespace Pirate\Sails\SintJan;

use Pirate\Wheel\Route;

class SintJanRouter extends Route
{
    private $adminPage = null;

    public function doMatch($url, $parts)
    {
        if ($result = $this->match($parts, '/info', [])) {
            $this->setPage(new Pages\Info\Algemeen());
            return true;
        }
        if ($result = $this->match($parts, '/takken/@tak', ['tak' => 'string'])) {
            if (!in_array($result->params->tak, ['kapoenen'])) {
                return false;
            }
            $this->setPage(new Pages\Takken($result->params->tak));
            return true;
        }
        return false;
    }

}
