<?php
namespace Pirate\Sails\Sponsors\Models;
use Pirate\Wheel\Model;
use Pirate\Sails\Files\Models\File;
use Pirate\Sails\Files\Models\ImageFile;
use Pirate\Sails\Files\Models\Image;

class Sponsor extends Model {
    public $id;
    public $name;
    public $url;
    public $image; //object

    private $new = false;

    function __construct($row = null) {
        if (!isset($row)) {
            $this->new = true;
            return;
        }

        $this->id = $row['sponsor_id'];
        $this->name = $row['sponsor_name'];
        $this->url = $row['sponsor_url'];

        if (isset($row['image_id'])) {
            $this->image = new Image($row);
        }
    }

    static function getSponsors() {
        $sponsors = array();
        $query = 'SELECT * FROM sponsors s
                JOIN images i on s.sponsor_image = i.image_id
                JOIN image_files i_f on i_f.imagefile_image = i.image_id
                JOIN files f on f.file_id = i_f.imagefile_file';


        if ($result = self::getDb()->query($query)){
            $sponsor = null;
            $sponsors = array();

            while($row = $result->fetch_assoc()) {
                 if (count($sponsors) == 0 || $row['sponsor_id'] != $sponsors[count($sponsors) - 1]->id) {
                    $sponsors[] = new Sponsor($row);
                }

                if (isset($sponsors[count($sponsors) - 1]->image)) {
                    $imageFile = new ImageFile($row);
                    $sponsors[count($sponsors) - 1]->image->addSource($imageFile);
                }
                
            }
        }

        return $sponsors;
    }

    static function getSponsor($id) {
        $id = self::getDb()->escape_string($id);

        $query = 'SELECT * FROM sponsors s
        JOIN images i on s.sponsor_image = i.image_id
        WHERE s.sponsor_id = "'.$id.'" 
        ';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows == 1){
                $row = $result->fetch_assoc();
                return new Sponsor($row);
            }
        }

        return null;
    }

    function save() {
        $name = self::getDb()->escape_string($this->name);
        $url = self::getDb()->escape_string($this->url);

        if (!isset($this->image)) {
            $image = 'NULL';
        } else {
            $image = "'".self::getDb()->escape_string($this->image->id)."'";
        }

        // Permissions

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);

            $query = "UPDATE sponsors 
                SET 
                 sponsor_name = '$name',
                 sponsor_url = '$url',
                 sponsor_image = $image
                 where sponsor_id = '$id' 
            ";
        } else {
            $query = "INSERT INTO 
                sponsors (`sponsor_name`, `sponsor_url`, `sponsor_image`)
                VALUES ('$name', '$url', $image)";
        }

        if (self::getDb()->query($query)) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        }

        return false;
    }

    function delete() {
        if (!isset($this->id)) {
            return false;
        }

        $this->image->delete();

        $id = self::getDb()->escape_string($this->id);

        $query = "DELETE FROM sponsors WHERE sponsor_id = '$id'";

        return self::getDb()->query($query);
    }
}