<?php
namespace Pirate\Sail\Leden;
use Pirate\Page\Page;
use Pirate\Route\Route;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Afrekening;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Leden\Inschrijving;

class LedenAdminRouter extends Route {
    private $lid = null;
    private $afrekening = null;
    private $tak = null;

    function doMatch($url, $parts) {
        if ($url == 'afrekeningen') {
            if (!Leiding::hasPermission('financieel')) {
                return false;
            }
            return true;
        }
        if (isset($parts[1]) && $parts[0] == 'afrekeningen') {
            $controle = $parts[1];
            if (isset($parts[2]) && $parts[1] == 'betalen') {
                if (!Leiding::hasPermission('financieel')) {
                    return false;
                }
                $controle = $parts[2];
            }

            if (!Leiding::hasPermission('financieel') && !(Leiding::hasPermission('leiding') && isset($_GET['inschrijvingen']))) {
                return false;
            }

            if (!is_numeric($controle)) {
                return false;
            }
            $id = intval($controle);
            $this->afrekening = Afrekening::getAfrekening($id);
            if (!empty($this->afrekening)) {
                return true;
            }
            return false;
        }

        if (isset($parts[0]) && $parts[0] == 'inschrijvingen') {
            if (!Leiding::hasPermission('leiding')) {
                return false;
            }

            if (count($parts) == 1) {
                return true;
            } elseif(count($parts) == 2 && ($parts[1] == 'mail' || $parts[1] == 'exporteren')) {
                return true;
            } elseif (isset($parts[1]) && ($parts[1] == 'lid' || $parts[1] == 'betalen') && count($parts) == 3) {
                if (!is_numeric($parts[2])) {
                    return false;
                }
                $id = intval($parts[2]);
                $this->lid = Lid::getLid($id);
                if (!empty($this->lid)) {
                    return true;
                }
                return false;
            } elseif(isset($parts[1])) {
                $takken = Inschrijving::$takken;
                if (in_array($parts[1], $takken)) {
                    $this->tak = $parts[1];
                    return true;
                }

            }
        }

        return false;
    }

    function getPage($url, $parts) {
        if ($url == 'afrekeningen') {
            require(__DIR__.'/admin/afrekeningen.php');
            return new Admin\Afrekeningen();
        }
        
        if (count($parts) == 1 || isset($this->tak)) {
            require(__DIR__.'/admin/overview.php');
            if (is_null($this->tak)) {
                return new Admin\Overview();
            }
            return new Admin\Overview($this->tak);
        }

        if (!is_null($this->afrekening)) {
            if (isset($parts[2]) && $parts[1] == 'betalen') {
                require(__DIR__.'/admin/betaal-afrekening.php');
                return new Admin\BetaalAfrekening($this->afrekening);
            }
            require(__DIR__.'/admin/afrekening.php');
            return new Admin\ViewAfrekening($this->afrekening);
        }

        if ($parts[1] == 'lid') {
            require(__DIR__.'/admin/lid.php');
            return new Admin\ViewLid($this->lid);
        }

        if ($parts[1] == 'mail') {
            require(__DIR__.'/admin/mail.php');
            return new Admin\MailPage();
        }

        if ($parts[1] == 'exporteren') {
            require(__DIR__.'/admin/exporteren.php');
            return new Admin\Exporteren();
        }

        require(__DIR__.'/admin/betaal-inschrijving.php');
        return new Admin\BetaalInschrijving($this->lid);
        
    }
}