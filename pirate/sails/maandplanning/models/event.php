<?php
namespace Pirate\Model;
use Pirate\Model\Model;

class Event extends Model {
    public $name;
    public $id;
    public $startdate;
    public $enddate;
    public $location;
    public $endlocation;
    public $group;

    function __construct($row) {
        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->startdate = new \DateTime($row['startdate']);
        $this->enddate = new \DateTime($row['enddate']);

        $this->location = $row['location'];
        $this->endlocation = $row['endlocation'];
        $this->group = $row['group'];
    }

    static function getEvents($startdate, $enddate) {
        $events = array();
        $query = 'SELECT * FROM events WHERE (startdate >= "'.$startdate.'" AND startdate < "'.$enddate.'") OR (enddate >= "'.$startdate.'" AND enddate < "'.$enddate.'") ORDER BY startdate, group_order';
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