<?php
namespace Pirate\Sails\Maandplanning\Models;

use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Environment\Classes\Localization;
use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Sails\Webshop\Models\OrderSheet;
use Pirate\Wheel\Model;

class Event extends Model
{
    public $name;
    public $id;
    public $startdate;
    public $enddate;
    public $location;
    public $endlocation;
    public $group;
    public $group_order;
    public $in_past = false;

    public $order_sheet_id;

    /// Only filled if requesting by id$
    public $order_sheet; /// object

    static $defaultLocation = 'Scoutsterrein';

    public static function getGroups()
    {
        $groups = [];
        foreach (Environment::getSetting('scouts.takken') as $tak => $data) {
            $groups[] = ucfirst($tak);
        }
        $groups[] = 'Leiding';
        $groups[] = 'Oudercomité';

        if (Environment::getSetting('theme') == 'sint-jan') {
            $groups[] = 'VZW';
            $groups[] = 'Stam';
        }

        $groups[] = 'Alle takken';
        $groups[] = 'Familie en vrienden';

        if (Environment::getSetting('theme') == 'prins-boudewijn') {
            $groups[] = '(Jong)givers';
        }
        return $groups;
    }

    public function isTak()
    {
        $takken = Environment::getSetting('scouts.takken');
        return isset($takken[strtolower($this->group)]);
    }

    public static function getDefaultEndHourList()
    {
        $defaultEndHour = [
            '' => '17:00',
        ];

        foreach (Environment::getSetting('scouts.takken') as $tak => $data) {
            if (isset($data['default_end_hour'])) {
                $defaultEndHour[$tak] = $data['default_end_hour'];
            }
        }

        return $defaultEndHour;
    }

    // bv. zondag 1 augustauts, 1:00 - 12:00
    public function getTimeDescriptionHuman($with_time = true)
    {
        if ($this->isSingleDay()) {
            return ucfirst($this->getStartDate()) . ($with_time ? (', ' . $this->startdate->format('G:i') . ' - ' . $this->enddate->format('G:i')) : '');
        }

        return ucfirst($this->getStartDate()) . ' tot ' . datetimeToDayMonth($this->enddate);

    }

    public function getStartDate()
    {
        return datetimeToWeekday($this->startdate) . ' ' . datetimeToDayMonth($this->startdate);
    }

    public function getEndDate()
    {
        return datetimeToWeekday($this->enddate) . ' ' . datetimeToDayMonth($this->enddate);
    }

    // bv. zondag 1 augustauts, 1:00 - 12:00
    public function getTimeString()
    {
        if ($this->isSingleDay()) {
            return ', ' . $this->startdate->format('G:i') . ' - ' . $this->enddate->format('G:i');
        }

        return ' om ' . $this->startdate->format('G:i');
    }

    public function isSingleDay()
    {
        return $this->startdate->format('Y-m-d') == $this->enddate->format('Y-m-d');
    }

    public function getMonthString()
    {
        return Localization::getMonth($this->startdate->format('n') + 0);
    }

    public function isImportantActivity()
    {
        if ($this->startdate->format('Y-m-d') != $this->enddate->format('Y-m-d')) {
            return true;
        }
        if ($this->group == "Familie en vrienden") {
            return true;
        }
        if ($this->group == "Oudercomité") {
            return true;
        }
        if ($this->group == "VZW") {
            return true;
        }
        if ($this->group == "Leiding") {
            return true;
        }
        if ($this->group == "Alle takken") {
            return true;
        }
        return false;
    }

    public function __construct($row = array())
    {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->startdate = new \DateTime($row['startdate']);
        $this->enddate = new \DateTime($row['enddate']);

        $this->location = $row['location'];
        $this->endlocation = $row['endlocation'];
        $this->group = $row['group'];
        $this->group_order = $row['group_order'];

        $this->order_sheet_id = $row['order_sheet_id'];
        if (isset($row['sheet_id'])) {
            $this->order_sheet = new OrderSheet($row);
        }

        if (isset($row['in_past'])) {
            $this->in_past = $row['in_past'];
        }

    }

    // Maximaal 30 events! Rest wordt weg geknipt
    public static function getEvents($startdate, $enddate)
    {
        $startdate = self::getDb()->escape_string($startdate);
        $enddate = self::getDb()->escape_string($enddate);

        $events = array();
        $query = 'SELECT e.*, o.*, b.*, case when startdate < CURDATE() then 1 else 0 end as in_past FROM events e
        left join order_sheets o on e.order_sheet_id = o.sheet_id
        left join bank_accounts b on b.account_id = o.sheet_bank_account

        WHERE (startdate >= "' . $startdate . '" AND startdate < "' . $enddate . '") OR (enddate >= "' . $startdate . '" AND enddate < "' . $enddate . '") OR (startdate <= "' . $startdate . '" AND enddate >= "' . $enddate . '") ORDER BY startdate, group_order LIMIT 30';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $events[] = new Event($row);
                }
            }
        }
        return $events;
    }

    // Maximaal 30 events! Rest wordt weg geknipt
    public static function getEvent($id)
    {
        $id = self::getDb()->escape_string($id);
        $query = 'SELECT e.*, o.*, b.* FROM events e
        left join order_sheets o on e.order_sheet_id = o.sheet_id
        left join bank_accounts b on b.account_id = o.sheet_bank_account
        WHERE id = "' . $id . '"';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return new Event($row);
            }
        }

        return null;
    }

    public static function searchEvents($needle)
    {
        $needle = self::getDb()->escape_string($needle);

        $events = array();
        $query = 'SELECT e.*, o.*, b.*, case when startdate < CURDATE() then 1 else 0 end as in_past FROM events e
        left join order_sheets o on e.order_sheet_id = o.sheet_id
        left join bank_accounts b on b.account_id = o.sheet_bank_account
        WHERE `name` like "%' . $needle . '%" OR `group` like "%' . $needle . '%" ORDER BY in_past, startdate, group_order LIMIT 30';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $events[] = new Event($row);
                }
            }
        }
        return $events;
    }

    public static function getEventsForTak($tak)
    {
        $tak = self::getDb()->escape_string($tak);

        $events = array();
        $query = 'SELECT e.*, o.*, b.* FROM events e
        left join order_sheets o on e.order_sheet_id = o.sheet_id
        left join bank_accounts b on b.account_id = o.sheet_bank_account
        WHERE startdate >= CURDATE() AND (`group` = "' . ucfirst($tak) . '" OR `group` = "Familie en vrienden" ' . (Inschrijving::isGeldigeTak($tak) ? 'OR `group` = "Alle takken"' : '') . ($tak == 'givers' || $tak == 'jonggivers' ? 'OR `group` = "(Jong)givers"' : '') . ') ORDER BY startdate LIMIT 30';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $events[] = new Event($row);
                }
            }
        }
        return $events;
    }

    public static function getDefaultEndHour()
    {
        $defaultEndHour = static::getDefaultEndHourList();
        if (Leiding::isLoggedIn()) {
            $user = Leiding::getUser();
            if (!empty($user->tak) && isset($defaultEndHour[$user->tak])) {
                return $defaultEndHour[$user->tak];
            }
        }
        return $defaultEndHour[''];
    }

    public static function getDefaultStartHour()
    {
        return '14:00';
    }

    public function setProperties(&$data)
    {
        $errors = array();

        if (strlen($data['name']) > 2) {
            $this->name = ucfirst($data['name']);
            $data['name'] = $this->name;
        } else {
            $errors[] = 'Beschrijving te kort';
        }

        // Startdatum
        $startdate = \DateTime::createFromFormat('d-m-Y H:i', $data['startdate'] . ' ' . $data['starttime']);
        if ($startdate !== false) {
            $this->startdate = clone $startdate;
            $data['startdate'] = $startdate->format('d-m-Y');
            $data['starttime'] = $startdate->format('H:i');
        } else {
            $errors[] = 'Ongeldige begin datum/tijdstip';
        }

        // Als geen overnachting: enddate overzetten
        if (!$data['overnachting']) {
            $data['enddate'] = $data['startdate'];
            $data['endlocation'] = '';
            $this->endlocation = null;
        }

        // Enddate
        $enddate = \DateTime::createFromFormat('d-m-Y H:i', $data['enddate'] . ' ' . $data['endtime']);
        if ($enddate !== false) {
            $this->enddate = clone $enddate;
            $data['enddate'] = $enddate->format('d-m-Y');
            $data['endtime'] = $enddate->format('H:i');
        } else {
            $errors[] = 'Ongeldige einddatum/tijdstip';
        }

        if ($data['enddate'] == $data['startdate']) {
            $data['overnachting'] = false;
        }
        if ($enddate < $startdate) {
            $errors[] = 'Einde van de activiteit ligt voor het begin';
        }

        if (strlen($data['location']) == 0 || $data['location'] == self::$defaultLocation) {
            $this->location = null;
            $data['location'] = '';
        } else {
            if (strlen($data['location']) < 4) {
                $errors[] = 'Ongeldige locatie. Laat leeg voor ' . strtolower(self::$defaultLocation) . '.';
            } else {
                $this->location = ucfirst($data['location']);
                $data['location'] = $this->location;
            }
        }

        if ($data['overnachting']) {
            if (strlen($data['endlocation']) == 0 || $data['endlocation'] == self::$defaultLocation) {
                $this->endlocation = null;
                $data['endlocation'] = '';
            } else {
                if (strlen($data['endlocation']) < 4) {
                    $errors[] = 'Ongeldige eindlocatie. Laat leeg voor ' . strtolower(self::$defaultLocation) . '.';
                } else {
                    $this->endlocation = ucfirst($data['endlocation']);
                    $data['endlocation'] = $this->endlocation;
                }
            }
        }

        $found = false;
        $order = 0;

        for ($i = 0; $i < count(self::getGroups()); $i++) {
            $group = self::getGroups()[$i];
            if ($group == $data['group']) {
                $found = true;
                $order = $i;
                break;
            }
        }

        if (!$found) {
            $errors[] = 'Ongeldig doelpubliek';
        } else {
            $this->group_order = $order;
            $this->group = $data['group'];
        }

        if (isset($data['order_sheet']) && $data['order_sheet']) {
            if (!isset($this->order_sheet)) {
                $this->order_sheet = new OrderSheet();
            }

            try {
                $this->order_sheet->setProperties($data);

                if ($this->order_sheet->type == 'registrations') {
                    $this->order_sheet->name = "Inschrijven voor $this->name";
                } else {
                    $this->order_sheet->name = "Bestellingen voor $this->name";
                }

                if (isset($this->startdate)) {
                    $this->order_sheet->subtitle = datetimeToDateString($this->startdate) . " om " . $this->startdate->format('H:i');
                }
            } catch (\Exception $ex) {
                $errors[] = $ex->getMessage();
            }

        } else {
            if (isset($this->order_sheet) && count($errors) == 0) {
                if (!$this->order_sheet->delete()) {
                    $errors[] = 'Er ging iets mis bij het opslaan';
                } else {
                    $this->order_sheet = null;
                }
            }
        }

        return $errors;
    }

    public function save()
    {
        if (is_null($this->location)) {
            $location = "NULL";
        } else {
            $location = "'" . self::getDb()->escape_string($this->location) . "'";
        }

        if (is_null($this->endlocation)) {
            $endlocation = "NULL";
        } else {
            $endlocation = "'" . self::getDb()->escape_string($this->endlocation) . "'";
        }

        if (is_null($this->order_sheet)) {
            $order_sheet = "NULL";
        } else {
            if (!$this->order_sheet->save()) {
                return false;
            }
            $order_sheet = "'" . self::getDb()->escape_string($this->order_sheet->id) . "'";
        }

        $name = self::getDb()->escape_string($this->name);
        $startdate = self::getDb()->escape_string($this->startdate->format('Y-m-d H:i:s'));
        $enddate = self::getDb()->escape_string($this->enddate->format('Y-m-d H:i:s'));

        $group = self::getDb()->escape_string($this->group);

        $group_order = self::getDb()->escape_string($this->group_order);
        $leiding_id = self::getDb()->escape_string(Leiding::getUser()->id);

        if (empty($this->id)) {
            $query = "INSERT INTO
                events (`name`,  `startdate`, `enddate`, `location`, `endlocation`, `group`, `group_order`, `leiding_id`, `order_sheet_id`)
                VALUES ('$name', '$startdate', '$enddate', $location, $endlocation, '$group', '$group_order', '$leiding_id', $order_sheet)";
        } else {
            $id = self::getDb()->escape_string($this->id);
            $query = "UPDATE events
                SET
                 `name` = '$name',
                 `startdate` = '$startdate',
                 `enddate` = '$enddate',
                 `location` = $location,
                 `endlocation` = $endlocation,
                 `group` = '$group',
                 `group_order` = '$group_order',
                 `order_sheet_id` = $order_sheet
                 where id = '$id'
            ";
        }

        if (self::getDb()->query($query)) {
            if (empty($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        }

        return false;
        //throw new \Exception(self::getDb()->error);
    }

    public function delete()
    {
        if (isset($this->order_sheet)) {
            if (!$this->order_sheet->delete()) {
                return false;
            }
        }
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM
                events WHERE id = '$id' ";

        return self::getDb()->query($query);
    }
}
