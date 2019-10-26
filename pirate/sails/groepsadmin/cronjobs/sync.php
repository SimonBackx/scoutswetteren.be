<?php
namespace Pirate\Sails\Groepsadmin\Cronjobs;

use Pirate\Sails\Cache\Classes\CacheHelper;
use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Groepsadmin\Models\Groepsadmin;
use Pirate\Sails\Groepsadmin\Models\GroepsadminLid;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Wheel\Cronjob;

class Sync extends Cronjob
{
    public function needsRunning()
    {
        if (date('G') != '18') {
            return false;
        }
        // 's nachts tussen 3:00 en 3:59 uitvoeren
        $synced = CacheHelper::get("groepsadmin-last-sync");
        return !isset($synced);
    }

    public function run()
    {
        // Opslaan dat we één dag (min 1 minuut) lang niet meer gaan synchroniseren
        CacheHelper::set("groepsadmin-last-sync", true, 60 * 60 * 24 - 60);

        if (!Environment::getSetting('groepsadmin.enabled', false)) {
            // Not allowed to make changes
            Leiding::sendErrorMail("Groepsadministratie sync is uitgeschakeld", "groepsadmin.enabled staat op false", "");
            return;
        }

        echo "Syncing groepsadministratie...\n\n";

        $groepsadmin = new Groepsadmin();

        echo "Logging in...\n";
        if ($groepsadmin->login()) {

            echo "Fetching all members from SGV...\n";
            if ($groepsadmin->getLedenlijst()) {
                // Leden ophalen

                echo "Fetching all members from database...\n";
                $leden = Lid::getLedenFull();
                $ledenlijst = $groepsadmin->ledenlijst;

                $not_equal_leden = [];

                echo "Comparing members from database to SGV...\n";
                foreach ($leden as $lid) {
                    $found = false;
                    foreach ($ledenlijst as $groepadminLid) {
                        if (!$groepadminLid->found && $groepadminLid->isEqual($lid)) {
                            $groepadminLid->markFound($lid);
                            $found = true;

                            // Break is belangrijk voor dubbele leden te voorkomen
                            break;
                        }
                    }
                    if (!$found) {
                        $not_equal_leden[] = $lid;
                    }
                }

                $not_found = [];

                foreach ($not_equal_leden as $lid) {
                    // Ook probably equals toelaten als er een lidnummer gekend is => kan aangepast zijn en in dat geval krijgen we een rare bug dat we leden dubbel gaan aanmaken in de groepsadmin
                    $found = false;
                    $fff = null;
                    foreach ($ledenlijst as $groepadminLid) {
                        if (!$groepadminLid->found && $groepadminLid->isProbablyEqual($lid)) {
                            $groepadminLid->markFound($lid);
                            $found = true;
                            $fff = $groepadminLid;

                            break;
                        }
                    }
                    if ($found) {
                        $gstr = $lid->geboortedatum->format('d/m/Y');
                        echo "WARNING: Member $lid->id ($lid->voornaam $lid->achternaam $gstr) does not exactly match <=> $fff->voornaam $fff->achternaam $fff->geboortedatum\n";
                    } else {
                        $not_found[] = $lid;
                    }
                }

                $leden_to_create = [];

                if (count($not_found) > 0) {
                    echo "Not all members could be found.\n";
                    echo "Fetching all old members from SGV...\n";

                    if (!$groepsadmin->getOudLedenlijst()) {
                        echo "Failed to get old memberlist.\n\n";
                        Leiding::sendErrorMail("De server kon de oude ledenlijst niet ophalen", "De server kon de oude ledenlijst niet ophalen", "");
                        return false;
                    }

                    $oud_ledenlijst = $groepsadmin->ledenlijst;

                    foreach ($not_found as $lid) {
                        $found = false;
                        foreach ($oud_ledenlijst as $groepadminLid) {
                            // Require lidnummer match = false (lden hebben wrs geen lidnummer, en lidnummer in onze database kan out of date zijn)
                            if (!$groepadminLid->found && $groepadminLid->isEqual($lid, false)) {
                                $groepadminLid->markFound($lid);

                                // Forceer herinschrijven
                                $groepadminLid->needsManualSync = true;
                                $found = true;

                                $ledenlijst[] = $groepadminLid;
                                break;
                            }
                        }

                        if ($found) {
                            $gstr = $lid->geboortedatum->format('d/m/Y');
                            echo "Member $lid->id ($lid->voornaam $lid->achternaam $gstr) is found in old members\n";
                        } else {
                            $leden_to_create[] = $lid;
                        }
                    }
                }

                $geschrapte_leden = [];
                $aangepaste_leden = [];
                $toegevoegde_leden = [];

                // Todo: check new scoutsjaar begin + 1 month
                $schrappen = (intval(date('n')) != 9 && intval(date('n')) != 8);
                $failed = false;

                foreach ($ledenlijst as $groepadminLid) {
                    if (!$groepadminLid->found) {
                        // Datum checken:

                        if ($schrappen) {
                            if (!$failed && $groepadminLid->remove($groepsadmin)) {
                                echo "Member $groepadminLid->voornaam $groepadminLid->achternaam is removed\n";
                                $geschrapte_leden[] = $groepadminLid->voornaam . ' ' . $groepadminLid->achternaam;
                            } else {
                                echo "Failed to remove $groepadminLid->voornaam $groepadminLid->achternaam\n";
                                $failed = true;
                                // Debug mail wordt door functie zelf als verstuurd
                            }
                        } else {
                            echo "Member $groepadminLid->voornaam $groepadminLid->achternaam will get removed from SGV in October\n";
                        }

                    } else {
                        if ($groepadminLid->needsSync()) {
                            if (!$failed && $groepadminLid->sync($groepsadmin)) {
                                echo "Member $groepadminLid->voornaam $groepadminLid->achternaam has been synced\n";
                                $aangepaste_leden[] = $groepadminLid->voornaam . ' ' . $groepadminLid->achternaam;
                            } else {
                                $failed = true;
                                echo "Failed to sync member $groepadminLid->voornaam $groepadminLid->achternaam\n";
                                // Debug mail wordt door functie zelf als verstuurd
                            }
                        }
                    }
                }

                foreach ($leden_to_create as $lid) {
                    if (!$failed && GroepsadminLid::createNew($lid, $groepsadmin)) {
                        echo "Member $lid->id ($lid->voornaam $lid->achternaam) has been created\n";
                        $toegevoegde_leden[] = $lid->voornaam . ' ' . $lid->achternaam;
                    } else {
                        $failed = true;
                        echo "Failed to create member $lid->id ($lid->voornaam $lid->achternaam)\n";
                        // Debug mail wordt door functie zelf als verstuurd
                    }
                }

                if (count($geschrapte_leden) > 0) {
                    Leiding::sendErrorMail("Overzicht van geschrapte leden", "Deze leden zijn geschrapt uit de groepsadministratie:", implode("\n", $geschrapte_leden));
                }

                if (count($aangepaste_leden) > 0) {
                    Leiding::sendErrorMail("Overzicht van aangepaste leden", "Deze leden zijn aangepast in de groepsadministratie:", implode("\n", $aangepaste_leden));
                }

                if (count($toegevoegde_leden) > 0) {
                    Leiding::sendErrorMail("Overzicht van toegevoegde leden", "Deze leden zijn toegevoegd in de groepsadministratie:", implode("\n", $toegevoegde_leden));
                }
            } else {
                echo "Failed to fetch ledenlijst!\n\n";
                Leiding::sendErrorMail("Ledenlijst van Groepsadministratie ophalen is mislukt", "Ledenlijst van Groepsadministratie ophalen is mislukt", "");
            }
        } else {
            echo "Failed to login to groepsadmin!\n\n";
            Leiding::sendErrorMail("Inloggen op groepsadministratie is mislukt", "De server kon niet inloggen op de groepsadministratie.", "");
        }
    }
}
