<?php
namespace Pirate\Model\Maandplanning;
use Pirate\Model\Model;

class Event extends Model {
    public $name;
    public $id;
    public $startdate;
    public $enddate;
    public $location;
    public $endlocation;
    public $group;
    public $in_past;

    static private $groups = array('Kapoenen', 'Wouters', 'Jonggivers', 'Givers', 'Jin', 'Leiding', 'Oudercomité', 'Alle takken', 'Familie en vrienden');

    function __construct($row) {
        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->startdate = new \DateTime($row['startdate']);
        $this->enddate = new \DateTime($row['enddate']);

        $this->location = $row['location'];
        $this->endlocation = $row['endlocation'];
        $this->group = $row['group'];
        $this->in_past = $row['in_past'];
    }

    // Maximaal 30 events! Rest wordt weg geknipt
    static function getEvents($startdate, $enddate) {
        $startdate = self::getDb()->escape_string($startdate);
        $enddate = self::getDb()->escape_string($enddate);

        $events = array();
        $query = 'SELECT *, case when startdate < NOW() then 1 else 0 end as in_past FROM events WHERE (startdate >= "'.$startdate.'" AND startdate < "'.$enddate.'") OR (enddate >= "'.$startdate.'" AND enddate < "'.$enddate.'") ORDER BY startdate, group_order LIMIT 30';
        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $events[] = new Event($row);
                }
            }
        }
        return $events;
    }

    static function searchEvents($needle) {
        $needle = self::getDb()->escape_string($needle);
        
        $events = array();
        $query = 'SELECT *, case when startdate < NOW() then 1 else 0 end as in_past FROM events WHERE `name` like "%'.$needle.'%" OR `group` like "%'.$needle.'%" ORDER BY in_past, startdate, group_order LIMIT 30';
        
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
        $query = 'SELECT * FROM events WHERE startdate >= NOW() AND group = "'.ucfirst($tak).'" OR group = "Familie en vrienden" OR group = "Alle takken" ORDER BY startdate LIMIT 30';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $events[] = new Event($row);
                }
            }
        }
        return $events;
    }
}