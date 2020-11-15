<?php
namespace Pirate\Sails\Leden;

use Pirate\Sails\Leden\Models\Afrekening;
use Pirate\Sails\Leden\Models\Gezin;
use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Wheel\AdminRoute;
use Pirate\Sails\Environment\Classes\Environment;

class LedenAdminRouter extends AdminRoute
{
    private $lid = null;
    private $gezin = null;
    private $inschakelen = false;
    private $afrekening = null;
    private $tak = null;
    private $jaar = null;

    public static function getAvailablePages()
    {
        if (Environment::getSetting('stamhoofd', false)) {
            return [];
        }
        return [
            'leiding' => array(
                array('priority' => 100, 'name' => 'Leden', 'url' => 'inschrijvingen'),
                array('name' => 'Attesten', 'url' => '/ouders/attesten'),
            ),
            'financieel' => array(
                array('priority' => 2, 'name' => 'Rekeningen', 'url' => 'afrekeningen'),
            ),
        ];
    }

    public function doMatch($url, $parts)
    {
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

            if (!Leiding::hasPermission('financieel') && !Leiding::hasPermission('leiding')) {
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

            } elseif (count($parts) == 2 && ($parts[1] == 'mail' || $parts[1] == 'sms' || $parts[1] == 'exporteren')) {
                return true;

            } elseif (count($parts) == 3 && ($parts[1] == 'lid')) {
                if (!is_numeric($parts[2])) {
                    return false;
                }
                $id = intval($parts[2]);
                $this->lid = Lid::getLid($id);
                if (!empty($this->lid)) {
                    return true;
                }
                return false;
            } elseif (count($parts) == 3 && ($parts[1] == 'lid-uitschrijven' || $parts[1] == 'lid-tak')) {
                if (!is_numeric($parts[2])) {
                    return false;
                }
                $id = intval($parts[2]);
                $this->lid = Lid::getLid($id);
                if (!empty($this->lid) && $this->lid->isIngeschreven()) {
                    return true;
                }
                $this->lid = null;
                return false;

            } elseif (count($parts) == 3 && $parts[1] == 'steekkaart') {
                $takken = Inschrijving::getTakken();
                if (in_array($parts[2], $takken)) {
                    $this->tak = $parts[2];
                    return true;
                }
                return false;

            } elseif (count($parts) == 4 && $parts[1] == 'scouting-op-maat') {
                if (!is_numeric($parts[3])) {
                    return false;
                }
                $id = intval($parts[3]);
                $this->gezin = Gezin::getGezin($id);
                if (!empty($this->gezin)) {
                    if ($parts[2] == 'inschakelen') {
                        $this->inschakelen = true;
                        return true;
                    }
                    if ($parts[2] == 'uitschakelen') {
                        $this->inschakelen = false;
                        return true;
                    }
                }
                return false;
            } elseif (isset($parts[1])) {
                $takken = Inschrijving::getTakken();
                if (in_array($parts[1], $takken)) {
                    $this->tak = $parts[1];

                    if (isset($parts[2])) {
                        if (is_numeric($parts[2])) {
                            $this->jaar = intval($parts[2]);
                            return true;
                        } else {
                            return false;
                        }
                    }
                    return true;
                }
            }
        }

        return false;
    }

    public function getPage($url, $parts)
    {
        if ($url == 'afrekeningen') {
            require __DIR__ . '/admin/afrekeningen.php';
            return new Admin\Afrekeningen();
        }

        if (count($parts) == 3 && $parts[1] == 'steekkaart') {
            require __DIR__ . '/admin/steekkaart-overzicht.php';
            return new Admin\SteekkaartOverzicht($this->tak);
        }

        if (count($parts) == 1 || isset($this->tak)) {
            require __DIR__ . '/admin/overview.php';
            if (is_null($this->tak)) {
                return new Admin\Overview();
            }
            return new Admin\Overview($this->tak, $this->jaar);
        }

        if (!is_null($this->afrekening)) {
            if (isset($parts[2]) && $parts[1] == 'betalen') {
                require __DIR__ . '/admin/betaal-afrekening.php';
                return new Admin\BetaalAfrekening($this->afrekening);
            }
            require __DIR__ . '/admin/afrekening.php';
            return new Admin\ViewAfrekening($this->afrekening);
        }

        if ($parts[1] == 'scouting-op-maat') {
            require __DIR__ . '/admin/scouting-op-maat.php';
            return new Admin\ScoutingOpMaat($this->gezin, $this->inschakelen);
        }

        if ($parts[1] == 'lid') {
            require __DIR__ . '/admin/lid.php';
            return new Admin\ViewLid($this->lid);
        }

        if ($parts[1] == 'lid-uitschrijven') {
            require __DIR__ . '/admin/lid-uitschrijven.php';
            return new Admin\LidUitschrijven($this->lid);
        }

        if ($parts[1] == 'lid-tak') {
            require __DIR__ . '/admin/lid-tak.php';
            return new Admin\LidTak($this->lid);
        }

        if ($parts[1] == 'mail') {
            require __DIR__ . '/admin/mail.php';
            return new Admin\MailPage();
        }

        if ($parts[1] == 'sms') {
            require __DIR__ . '/admin/sms.php';
            return new Admin\SmsPage();
        }

        //if ($parts[1] == 'exporteren') {
        require __DIR__ . '/admin/exporteren.php';
        return new Admin\Exporteren();
        //}

    }
}
