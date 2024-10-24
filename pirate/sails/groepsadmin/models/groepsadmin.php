<?php
namespace Pirate\Sails\Groepsadmin\Models;

use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Groepsadmin\Models\GroepsadminLid;
use Pirate\Wheel\Curl\Curl;
use Pirate\Wheel\Curl\DataType;
use Pirate\Wheel\Curl\Method;

class Groepsadmin
{
    private $access_token = '';
    public $logged_in = false;
    public $ledenlijst = null;

    public function __construct()
    {
    }

    public function authenticatedRequest($method, $url, $headers = [], $data_type = DataType::urlencoded, $data = null)
    {
        $headers[] = 'Authorization: Bearer ' . $this->access_token;
        return Curl::request($method, $url, $headers, $data_type, $data);
    }

    private function getURL()
    {
        if (isset($_ENV["DEBUG"]) && $_ENV["DEBUG"] == 1) {
            // Use development server
            // return 'http://groepsadmin-develop.scoutsengidsenvlaanderen.net/groepsadmin/rest-ga';
        }

        return 'https://groepsadmin.scoutsengidsenvlaanderen.be/groepsadmin/rest-ga';
    }

    private function getOAuthClientId()
    {
        if (isset($_ENV["DEBUG"]) && $_ENV["DEBUG"] == 1) {
            // Use development server
            // return 'groepsadmin-staging-client';
        }

        return 'groepsadmin-production-client';
    }

    public function login()
    {
        $response = Curl::request(Method::POST, 'https://login.scoutsengidsenvlaanderen.be/auth/realms/scouts/protocol/openid-connect/token', [], DataType::urlencoded, [
            'client_id' => $this->getOAuthClientId(),
            'username' => Environment::getSetting('groepsadmin.username'),
            'password' => Environment::getSetting('groepsadmin.password'),
            'grant_type' => 'password',
        ]);

        if (!isset($response)) {
            return false;
        }

        if (isset($response["access_token"])) {
            $this->access_token = $response["access_token"];
            $this->logged_in = true;
            return true;
        }

        return false;
    }

    private function filterHuidigeLeden()
    {
        $columns = GroepsadminLid::getColumns();
        $response = static::authenticatedRequest(Method::PATCH, $this->getURL() . '/ledenlijst/filter/huidige', [], DataType::json, [
            'criteria' => [
                'groepen' => [Environment::getSetting('groepsadmin.groep')],
                'functies' => ["d5f75b320b812440010b812554790354", "d5f75b320b812440010b812555de03a2", "d5f75b320b812440010b8125567703cb", "d5f75b320b812440010b812555db03a1", "d5f75b320b812440010b812555d603a0", "d5f75b320b812440010b812555c7039d", "d5f75b320b812440010b8125565203c1", "d5f75b320b812440010b812555380380", "d5f75b320b812440010b812555c1039b"],
                'oudleden' => false,
                'groepseigen' => [],
            ],
            'kolommen' => $columns,
            'sortering' => [$columns[0]],
            "type" => "lid",
            "groep" => Environment::getSetting('groepsadmin.groep'),
        ]);

        return isset($response);
    }

    private function filterOudLeden()
    {
        $columns = GroepsadminLid::getColumns();
        $response = static::authenticatedRequest(Method::PATCH, $this->getURL() . '/ledenlijst/filter/huidige', [], DataType::json, [
            'criteria' => [
                'groepen' => [Environment::getSetting('groepsadmin.groep')],
                'functies' => ["d5f75b320b812440010b812554790354", "d5f75b320b812440010b812555de03a2", "d5f75b320b812440010b8125567703cb", "d5f75b320b812440010b812555db03a1", "d5f75b320b812440010b812555d603a0", "d5f75b320b812440010b812555c7039d", "d5f75b320b812440010b8125565203c1", "d5f75b320b812440010b812555380380", "d5f75b320b812440010b812555c1039b"],
                'oudleden' => true,
                'groepseigen' => [],
            ],
            'kolommen' => $columns,
            'sortering' => [$columns[0]],
            "type" => "lid",
            "groep" => Environment::getSetting('groepsadmin.groep'),
        ]);

        return isset($response);
    }

    // Return false on fail, array on success
    private function downloadLedenlijst($offset = 0)
    {
        $response = static::authenticatedRequest(Method::GET, $this->getURL() . '/ledenlijst?aantal=100&offset=' . urlencode($offset));
        if (!isset($response)) {
            return null;
        }

        if (isset($response["aantal"])) {
            $aantal = $response["aantal"]; // aantal teruggegeven leden
            $totaal = $response["totaal"]; // totaal aantal leden

            // lees leden in

            $leden = array();
            foreach ($response['leden'] as $lid_data) {
                if (!isset($lid_data['waarden']['be.vvksm.groepsadmin.model.column.AchternaamColumn'], $lid_data['waarden']['be.vvksm.groepsadmin.model.column.VoornaamColumn'], $lid_data['waarden']['be.vvksm.groepsadmin.model.column.GeboorteDatumColumn'], $lid_data['waarden']['be.vvksm.groepsadmin.model.column.LidNummerColumn'])) {
                    // Something broke down
                    return null;
                }
                $lid = new GroepsadminLid($lid_data);
                $leden[] = $lid;
            }

            if ($totaal > count($leden) + $offset) {
                $extra = $this->downloadLedenlijst(count($leden) + $offset);
                if (!isset($extra)) {
                    return null;
                }
                $leden = array_merge($leden, $extra);
            }

            return $leden;
        }

        return null;
    }

    public function downloadLid($id)
    {
        return static::authenticatedRequest(Method::GET, $this->getURL() . '/lid/' . $id);
    }

    public function uploadLid($data, $id = null)
    {
        if (isset($_ENV["DEBUG"]) && $_ENV["DEBUG"] == 1) {
            return true;
        }

        if (!Environment::getSetting('groepsadmin.enabled', false)) {
            // Not allowed to make changes
            return false;
        }

        if (Environment::getSetting('groepsadmin.dry-run', false)) {
            // Fake changes
            return false;
        }

        $response = static::authenticatedRequest(isset($id) ? Method::PATCH : Method::POST, isset($id) ? $this->getURL() . '/lid/' . $id . '?bevestig=true' : $this->getURL() . '/lid', [], DataType::json, $data);
        return $response;
    }

    public function getLedenlijst()
    {
        if (!$this->logged_in) {
            return false;
        }
        if (!$this->filterHuidigeLeden()) {
            return false;
        }

        $this->ledenlijst = $this->downloadLedenlijst();
        if (isset($this->ledenlijst)) {
            return true;
        }

        $this->ledenlijst = null;
        return false;
    }

    public function getOudLedenlijst()
    {
        if (!$this->logged_in) {
            return false;
        }
        if (!$this->filterOudLeden()) {
            return false;
        }

        $this->ledenlijst = $this->downloadLedenlijst();
        if (isset($this->ledenlijst)) {
            return true;
        }

        $this->ledenlijst = null;
        return false;
    }
}
