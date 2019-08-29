<?php
namespace Pirate\Sails\SintJan;

use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Wheel\Route;

class SintJanRouter extends Route
{
    private $adminPage = null;

    public function doMatch($url, $parts)
    {
        if ($result = $this->match($parts, '', [])) {
            $this->setPage(new Pages\Homepage());
            return true;
        }

        if ($result = $this->match($parts, '/privacy', [])) {
            $this->setPage(new Pages\Privacy());
            return true;
        }

        if ($result = $this->match($parts, '/info', [])) {
            $this->setPage(new Pages\Info\Algemeen());
            return true;
        }
        if ($result = $this->match($parts, '/info/vzw', [])) {
            $this->setPage(new Pages\Info\VZW());
            return true;
        }
        if ($result = $this->match($parts, '/info/oudercomite', [])) {
            $this->setPage(new Pages\Info\Oudercomite());
            return true;
        }
        if ($result = $this->match($parts, '/takken/@tak', ['tak' => 'string'])) {
            if (!Inschrijving::isGeldigeTak($result->params->tak) && $result->params->tak != 'stam') {
                return false;
            }
            $this->setPage(new Pages\Takken($result->params->tak));
            return true;
        }
        return false;
    }

}
