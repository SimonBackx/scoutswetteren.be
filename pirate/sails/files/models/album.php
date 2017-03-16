<?php
namespace Pirate\Model\Files;
use Pirate\Model\Model;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Files\File;
use Pirate\Model\Files\ImageFile;
use Pirate\Model\Files\Image;
use Pirate\Model\Maandplanning\Event;

class Album extends Model {
    public $id;
    public $name;
    public $date;
    public $date_taken;

    public $author;
    public $hidden;

    public $group;
    public $slug;
    public $zip_file; // id van file of null
    public $sources_available; // true / false

    public $cover = null;
    public $image_count = 0;
    public static $QUEUE_ID = 0;

    public static $groups = array('kapoenen', 'wouters', 'jonggivers', 'givers', 'jin', 'algemeen');

    function __construct($row = null) {
        if (!isset($row)) {
            return;
        }

        $this->id = $row['album_id'];
        $this->slug = $row['album_slug'];
        $this->name = $row['album_name'];
        $this->date = new \DateTime($row['album_date']);
        $this->date_taken = new \DateTime($row['album_date_taken']);

        $this->zip_file = $row['album_zip_file'];
        $this->group = $row['album_group'];
        $this->author = $row['album_author'];
        $this->hidden = ($row['album_hidden'] == 1);
        $this->sources_available = ($row['album_sources_available'] == 1);

        $this->cover = null;
        if (isset($row['image_id'])) {
            $this->cover = new Image($row);
        }

        if (isset($row['album_image_count'])) {
            $this->image_count = intval($row['album_image_count']);
        }
    }

    static function getQueueAlbum() {
        $album = new Album();
        $album->id = Self::$QUEUE_ID;
        $album->name = 'queue';
        return $album;
    }

    function isQueue() {
        return $this->id == Self::$QUEUE_ID;
    }

    function canDownload() {
        return $this->sources_available || isset($this->zip_file);
    }

    function generateSlug() {
        $string = $this->name;

        $string = iconv( "utf-8", "us-ascii//translit//ignore", $string ); // transliterate
        $string = str_replace( "'", "", $string );
        $string = preg_replace( "~[^\pL\d]+~u", "-", $string ); // replace non letter or non digits by "-"
        $string = preg_replace( "~[^-\w]+~", "", $string ); // remove unwanted characters
        $string = preg_replace( "~-+~", "-", $string ); // remove duplicate "-"
        $string = trim( $string, "-" ); // trim "-"
        $string = trim( $string ); // trim
        $string = mb_strtolower( $string, "utf-8" ); // lowercase
        $string = urlencode( $string ); // safe
        return $string;
    }

    function getSlug() {
        if (!isset($this->slug)) {
            return $this->generateSlug();
        }
        return $this->slug;
    }

    function getUrl() {
        return $this->date->format('Y/m/d').'/'.$this->getSlug();
    }

    static function getAlbum($id) {
        $id = self::getDb()->escape_string($id);

        $albums = array();
        $query = 'SELECT a.*, c.*, count(i.image_id) as album_image_count from albums a join images i on i.image_album = a.album_id left join images c on c.image_id = a.album_cover WHERE a.album_id = "'.$id.'" group by a.album_id, c.image_id';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows == 1){
                $row = $result->fetch_assoc();
                return new Album($row);
            }
        }

        return null;
    }

    static function getAlbumBySlug($year, $month, $day, $slug) {
        $slug = self::getDb()->escape_string($slug);
        $day = self::getDb()->escape_string($day);
        $month = self::getDb()->escape_string($month);
        $year = self::getDb()->escape_string($year);

        $query = 'SELECT a.*, c.*, i_f.*, f.*
        from albums a 
        join images c on c.image_id = a.album_cover 
        join image_files i_f on i_f.imagefile_image = c.image_id
        join files f on f.file_id = i_f.imagefile_file
        WHERE a.album_slug = "'.$slug.'" 
        AND MONTH(a.album_date) = "'.$month.'"
         AND YEAR(a.album_date) = "'.$year.'"
          AND DAY(a.album_date) = "'.$day.'"';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows > 0){
                $album = null;
                while ($row = $result->fetch_assoc()) {
                    if (!isset($album)) {
                        $album = new Album($row);
                    }

                    $imagefile = new ImageFile($row);
                    $album->cover->addSource($imagefile);
                }

                return $album;
            }
        }

        return null;
    }

    static function getAlbums($group = null, $page = 1, $with_cover = false) {
        $page = intval($page);

        /*$limit = 'LIMIT '.(($page-1)*4).', 50';
        if ($page < 1) {
            $limit = '';
        }*/
        $limit = '';

        $where = 'WHERE a.album_hidden = 0 ';
        if (isset($group)) {
            $where .= 'AND a.album_group = "'.self::getDb()->escape_string($group).'"';
        }

        $albums = array();

        if (!$with_cover) {
            $query = 'SELECT a.*, c.*, count(i.image_id) as album_image_count 
                    from albums a 
                    join images i on i.image_album = a.album_id 
                    left join images c on c.image_id = a.album_cover
                    '.$where.' 
                    group by a.album_id, c.image_id 
                    order by a.album_date desc '.$limit;

            if ($result = self::getDb()->query($query)){
                if ($result->num_rows>0){
                    while ($row = $result->fetch_assoc()) {
                        $albums[] = new Album($row);
                    }
                }
            }
            return $albums;
        }

        // Ook cover sources uit database halen
        $query = 'SELECT a.*, c.*, i_f.*, f.*
                from albums a 
                join images c on c.image_id = a.album_cover
                join image_files i_f on i_f.imagefile_image = c.image_id
                join files f on f.file_id = i_f.imagefile_file
                 '.$where.' 
                order by YEAR(a.album_date_taken) desc, a.album_date desc, a.album_id desc '.$limit;
        
        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                $last_album = null;
                while ($row = $result->fetch_assoc()) {

                    $a = new Album($row);
                    if (!isset($last_album) || $a->id != $last_album->id) {
                        $albums[] = $a;
                        $last_album = $a;
                    } else {
                        $a = $last_album;
                    }

                    $imagefile = new ImageFile($row);
                    $a->cover->addSource($imagefile);
                }
            }
        }
        
        return $albums;
    }

    // Get name suggestion for images
    static function getNameSuggestion($group, $images) {
        $mindate = null;
        $maxdate = null;
        foreach ($images as $image) {
            if (isset($image->date_taken)) {
                $date = $image->date_taken;
                if (!isset($mindate) || $mindate > $date) {
                    $mindate = $date;
                }
                if (!isset($maxdate) || $maxdate < $date) {
                    $maxdate = $date;
                }
            }
        }

        if (isset($mindate, $maxdate)) {
            $events = Event::getEvents($mindate->format('Y-m-d H:i:s'), $maxdate->format('Y-m-d H:i:s'));

            $event_max = null;
            $overlap_max = 0;

            foreach ($events as $event) {
                if (strtolower($event->group) != $group && !($group == 'algemeen' && !in_array($event->group, self::$groups)) ) {
                    continue;
                }

                // Overlap berekenen
                $overlap = $event->enddate->getTimestamp() - $event->startdate->getTimestamp();

                if ($overlap >= $overlap_max || (isset($event_max) && ($event_max->enddate  < $maxdate || $event_max->startdate > $mindate))) {
                    $overlap_max = $overlap;
                    $event_max = $event;
                }
            }

            if (isset($event_max)) {
                return $event_max->name;
            }

            $date = datetimeToDateString($mindate);
            $date2 = datetimeToDateString($maxdate);

            if ($date == $date2) {
                return $date;
            }

            return $date.' tot '.$date2;
        }

        return '';
    }

    function save() {
        $name = self::getDb()->escape_string($this->name);
        $date = self::getDb()->escape_string($this->date->format('Y-m-d H:i:s'));
        $date_taken = self::getDb()->escape_string($this->date_taken->format('Y-m-d'));

        $group = self::getDb()->escape_string($this->group);

        $author = "NULL";
        if (isset($this->author)) {
            $author = "'".self::getDb()->escape_string($this->author)."'";
        }

        $cover = "NULL";
        if (isset($this->cover)) {
            $cover = "'".self::getDb()->escape_string($this->cover->id)."'";
        }

        $zip_file = "NULL";
        if (isset($this->zip_file)) {
            $zip_file = "'".self::getDb()->escape_string($this->zip_file)."'";
        }

        $sources_available = 0;
        if ($this->sources_available) {
            $sources_available = 1;
        }

        $hidden = 0;
        if ($this->hidden) {
            $hidden = 1;
        }

        if (empty($this->id)) {
            $this->slug = $this->generateSlug();
            $slug = self::getDb()->escape_string($this->slug);

            $query = "INSERT INTO 
                albums (`album_name`, `album_slug`, `album_author`, `album_date`, `album_date_taken`, `album_group`, `album_cover`, `album_hidden`, `album_zip_file`, `album_sources_available`)
                VALUES ('$name', '$slug', $author, '$date', '$date_taken', '$group', $cover, '$hidden', $zip_file, $sources_available)";
        } else {
            $id = self::getDb()->escape_string($this->id);
            $query = "UPDATE albums 
                SET 
                 `album_name` = '$name',
                 `album_author` = $author,
                 `album_date` = '$date',
                 `album_date_taken` = '$date_taken',
                 `album_group` = '$group',
                 `album_cover` = $cover,
                 `album_hidden` = '$hidden',
                 `album_zip_file` = $zip_file,
                 `album_sources_available` = $sources_available
                 where album_id = '$id' 
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

    static function isValidGroup($group) {
        foreach (self::$groups as $value) {
            if ($value == $group) {
                return true;
            }
        }
        return false;
    }

    function setProperties(&$data, &$errors) {
        if (!self::isValidGroup($data['group'])) {
            $errors[] = 'Ongeldige groep geselecteerd';
            return false;
        } else {
            $this->group = $data['group'];
        }

        $data['album_name'] = ucfirst(trim($data['album_name']));
        if (strlen($data['album_name']) < 3) {
            $errors[] = 'Naam te kort';
            return false;
        } else {
            $this->name = $data['album_name'];
        }

        if (!isset($this->author) && Leiding::isLoggedIn()) {
            $this->author = Leiding::getUser()->id;
        }

        if (!isset($this->date)) {
            $this->date = new \DateTime();
        }

        if (!isset($this->id)) {
            $album = Self::getAlbumBySlug(intval($this->date->format('Y')), intval($this->date->format('m')), intval($this->date->format('j')), $this->getSlug());

            if (isset($album)) {
                $errors[] = 'Er bestaat al een album met deze naam, die je op deze dag hebt aangemaakt. Je kan er beter foto\'s aan toevoegen i.p.v. een nieuw album aan te maken.';
                return false;
            }
        }

        return true;
    }

    static function getPathForAlbum($album = null, $deletePath = false) {
        $path = 'images/';

        if (isset($album) && isset($album->id)) {
            if ($album->id == Self::$QUEUE_ID) {
                $leiding_id = Leiding::getUser()->id;
                $path = 'albums/queue/'.$leiding_id.'/';
            } else {
                if ($deletePath) {
                    $path = 'albums/'.$album->id.'/';
                } else {
                    $path = 'albums/'.$album->id.'/'.$album->getSlug().'/';
                }
            }
        }

        return $path;
    }

    function createFromImageQueue() {
        // Cover foto instellen
        if (!isset($this->cover)) {
            $images = Image::getImagesFromAlbum(null);
            if (count($images) == 0) {
                return false; // Geen foto's toe te voegen, en cover niet oke
            }
            $this->cover = $images[0];
            $this->date_taken = $this->date;

            foreach ($images as $image) {
                if (isset($image->date_taken)) {
                    $this->date_taken = $image->date_taken;
                    break;
                }
            }

        }
        if ($this->save() == false) {
            return false;
        }

        return $this->addImageQueue();
    }

    /**
     * Afbeeldingen uit de image queue aan dit album toevoegen
     */
    function addImageQueue() {
        global $FILES_DIRECTORY;

        if (!isset($this->author)) {
            return false;
        }
        $author = self::getDb()->escape_string($this->author);
        $id = self::getDb()->escape_string($this->id);
        $slug = self::getDb()->escape_string($this->getSlug());

        $queue_dir = self::getDb()->escape_string(Self::getPathForAlbum(Self::getQueueAlbum()));
        $new_dir = self::getDb()->escape_string(Self::getPathForAlbum($this));

        $query = "UPDATE images i
            join image_files i_f on i_f.imagefile_image = i.image_id
            join files f on f.file_id = i_f.imagefile_file
            SET 
             i.image_album = '$id',
             f.file_location = REPLACE(f.file_location, '".$queue_dir."', '".$new_dir."')
            where i.image_album = '".Self::$QUEUE_ID."' and f.file_author = '$author'";


        if (self::getDb()->query($query)) {
            $error_reporting = error_reporting();
            //error_reporting(0);
            $dir = $FILES_DIRECTORY.'/'.Self::getPathForAlbum($this);

            $old = umask(0);
            if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                umask($old);
                error_reporting($error_reporting);
                return false;
            }
            umask($old);

            rename($FILES_DIRECTORY.'/'.Self::getPathForAlbum(Self::getQueueAlbum()), $dir);
            error_reporting($error_reporting);

            return true;
        }

        return false;
    }

    function delete() {
        global $FILES_DIRECTORY;

        if (!isset($this->id)) {
            return false;
        }
        if ($this->id != Self::$QUEUE_ID) {

            // Verwijdert alle files + image_files
            // Images blijven bestaan
            $id = self::getDb()->escape_string($this->id);
            $query = "DELETE files FROM albums
                left join images on images.image_album = albums.album_id
                left join image_files on image_files.imagefile_image = images.image_id
                left join files on files.file_id = image_files.imagefile_file
                WHERE albums.album_id = '$id'";

            if (!self::getDb()->query($query)) {
                return false;
            }

            // Verwijder de albums + images
            $query = "DELETE FROM albums
                WHERE albums.album_id = '$id'";

            if (!self::getDb()->query($query)) {
                return false;
            }
        } else {

            // Verwijdert alle files + image_files
            // Images blijven bestaan
            $author = self::getDb()->escape_string(Leiding::getUser()->id);
            $id = self::getDb()->escape_string($this->id);
            $query = "DELETE files FROM albums
                left join images on images.image_album = albums.album_id
                left join image_files on image_files.imagefile_image = images.image_id
                left join files on files.file_id = image_files.imagefile_file
                WHERE albums.album_id = '$id' and files.file_author = '$author'";

            if (!self::getDb()->query($query)) {
                return false;
            }

            // Verwijder de images
            $query = "DELETE images FROM images
                join albums on albums.album_id = images.image_album
                left join image_files on image_files.imagefile_image = images.image_id
                WHERE albums.album_id = '$id' and image_files.imagefile_id is null";

            if (!self::getDb()->query($query)) {
                return false;
            }
        }

        $path = $FILES_DIRECTORY.'/'.Album::getPathForAlbum($this, true);
        exec("rm -rf \"$path\"", $output, $response);

        return ($response === 0);
    }

    function deleteZip() {
        // Zip updaten
        if (!isset($this->zip_file)) {
            return true;
        }

        $file = File::getFile($this->zip_file);
        if (isset($file)) {
            return $file->delete();
        }

        return false;
    }

    function updateZip() {
        // Zip updaten
        if (!isset($this->zip_file)) {
            return true;
        }

        $location = Album::getPathForAlbum($this);
        $name = $this->getSlug().'.zip';
        $file_path = $FILES_DIRECTORY.'/'.$location.$name;
        $path = $FILES_DIRECTORY.'/'.$location.'sources';

        // File sync here (= -FS)
        exec("zip -FSjr \"$file_path\" \"$path\"", $output, $response);

        return ($response === 0);
    }

    function createZip() {
        global $FILES_DIRECTORY;
        if (isset($this->zip_file)) {
            return true;
        }

        if (!$this->canDownload()) {
            return false;
        }

        $location = Album::getPathForAlbum($this);
        $name = $this->getSlug().'.zip';
        $file_path = $FILES_DIRECTORY.'/'.$location.$name;
        $path = $FILES_DIRECTORY.'/'.$location.'sources';
        exec("zip -FSjr \"$file_path\" \"$path\"", $output, $response);

        if ($response === 0) {     
            $errors = array();       
            $file = new File();
            if (!$file->from_file($location, $name, $errors)) {
                return false;
            }
            $this->zip_file = $file->id;
            return $this->save();
        }
        return false;
    }

}