<?php
namespace Pirate\Model\Maandplanning;
use Pirate\Model\Model;
use Pirate\Model\Leiding\Leiding;

class Event extends Model {
    public $name;
    public $id;
    public $startdate;
    public $enddate;
    public $location;
    public $endlocation;
    public $group;
    public $group_order;
    public $in_past = false;

    static $groups = array('Kapoenen', 'Wouters', 'Jonggivers', 'Givers', 'Jin', 'Leiding', 'Oudercomité', 'Alle takken', 'Familie en vrienden');
    static $defaultLocation = 'Scoutsterrein';

    static private $defaultEndHour = array(
        '' => '17:00',
        'kapoenen' => '17:00',
        'wouters' => '17:00',
        'jonggivers' => '17:30',
        'givers' => '17:30',
        'jin' => '17:30'
    );

    function __construct($row = array()) {
        if (count($row) == 0){
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

        if (isset($row['in_past']))
            $this->in_past = $row['in_past'];
    }

    // Maximaal 30 events! Rest wordt weg geknipt
    static function getEvents($startdate, $enddate) {
        $startdate = self::getDb()->escape_string($startdate);
        $enddate = self::getDb()->escape_string($enddate);

        $events = array();
        $query = 'SELECT *, case when startdate < CURDATE() then 1 else 0 end as in_past FROM events WHERE (startdate >= "'.$startdate.'" AND startdate < "'.$enddate.'") OR (enddate >= "'.$startdate.'" AND enddate < "'.$enddate.'") OR (startdate <= "'.$startdate.'" AND enddate >= "'.$enddate.'") ORDER BY startdate, group_order LIMIT 30';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $events[] = new Event($row);
                }
            }
        }
        return $events;
    }

    // Maximaal 30 events! Rest wordt weg geknipt
    static function getEvent($id) {
        $id = self::getDb()->escape_string($id);
        $query = 'SELECT * FROM events WHERE id = "'.$id.'"';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                $row = $result->fetch_assoc();
                return new Event($row);
            }
        }

        return null;
    }

    static function searchEvents($needle) {
        $needle = self::getDb()->escape_string($needle);
        
        $events = array();
        $query = 'SELECT *, case when startdate < CURDATE() then 1 else 0 end as in_past FROM events WHERE `name` like "%'.$needle.'%" OR `group` like "%'.$needle.'%" ORDER BY in_past, startdate, group_order LIMIT 30';
        
        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $events[] = new Event($row);
                }
            }
        }
        return $events;
    }

    static function getEventsForTak($tak) {
        $tak = self::getDb()->escape_string($tak);

        $events = array();
        $query = 'SELECT * FROM events WHERE startdate >= CURDATE() AND (`group` = "'.ucfirst($tak).'" OR `group` = "Familie en vrienden" OR `group` = "Alle takken") ORDER BY startdate LIMIT 30';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $events[] = new Event($row);
                }
            }
        }
        return $events;
    }

    static function getDefaultEndHour() {
        if (Leiding::isLoggedIn()) {
            $user = Leiding::getUser();
            if (!empty($user->tak) && isset(self::$defaultEndHour[$user->tak])) {
                return self::$defaultEndHour[$user->tak];
            }
        }
        return self::$defaultEndHour[''];
    }

    static function getDefaultStartHour() {
        return '14:00';
    }

    function setProperties(&$data) {
        $errors = array();

        if (strlen($data['name']) > 2) {
            $this->name = ucfirst($data['name']);
            $data['name'] = $this->name;
        } else {
            $errors[] = 'Beschrijving te kort';
        }

        // Startdatum
        $startdate = \DateTime::createFromFormat('d-m-Y H:i', $data['startdate'].' '.$data['starttime']);
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
        $enddate = \DateTime::createFromFormat('d-m-Y H:i', $data['enddate'].' '.$data['endtime']);
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
                $errors[] = 'Ongeldige locatie. Laat leeg voor '.strtolower(self::$defaultLocation).'.';
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
                    $errors[] = 'Ongeldige eindlocatie. Laat leeg voor '.strtolower(self::$defaultLocation).'.';
                } else {
                    $this->endlocation = ucfirst($data['endlocation']);
                    $data['endlocation'] = $this->endlocation;
                }
            }
        }

        $found = false;
        $order = 0;

        for ($i=0; $i < count(self::$groups); $i++) { 
            $group = self::$groups[$i];
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


        return $errors;
    }

    function save() {
        if (is_null($this->location)) {
            $location = "NULL";
        } else {
            $location = "'".self::getDb()->escape_string($this->location)."'";
        }

        if (is_null($this->endlocation)) {
            $endlocation = "NULL";
        } else {
            $endlocation = "'".self::getDb()->escape_string($this->endlocation)."'";
        }


        $name = self::getDb()->escape_string($this->name);
        $startdate = self::getDb()->escape_string($this->startdate->format('Y-m-d H:i:s'));
        $enddate = self::getDb()->escape_string($this->enddate->format('Y-m-d H:i:s'));

        $group = self::getDb()->escape_string($this->group);

        $group_order = self::getDb()->escape_string($this->group_order);
        $leiding_id = self::getDb()->escape_string(Leiding::getUser()->id);

        if (empty($this->id)) {
            $query = "INSERT INTO 
                events (`name`,  `startdate`, `enddate`, `location`, `endlocation`, `group`, `group_order`, `leiding_id`)
                VALUES ('$name', '$startdate', '$enddate', $location, $endlocation, '$group', '$group_order', '$leiding_id')";
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
                 `group_order` = '$group_order'
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
    }

    function delete() {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM 
                events WHERE id = '$id' ";

        return self::getDb()->query($query);
    }
}