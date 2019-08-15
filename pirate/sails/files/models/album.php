<?php
namespace Pirate\Sails\Files\Models;

use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Files\Models\File;
use Pirate\Sails\Files\Models\Image;
use Pirate\Sails\Files\Models\ImageFile;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Sails\Maandplanning\Models\Event;
use Pirate\Wheel\Model;

class Album extends Model
{
    public $id;
    public $name;
    public $date;
    public $date_taken;

    public $author;
    public $hidden;

    public $group;
    public $slug;

    public $zip_file; // id van file of null
    // upload_date van zip_file bevat de datum van de laaste aanpassing
    public $sources_available; // true / false

    public $cover = null;
    private $cover_id = null;

    public $image_count = 0;

    public $latest_upload_date = null;
    public $zip_last_updated = null;

    public static $QUEUE_ID = 0;

    public static $groups = array('kapoenen', 'wouters', 'jonggivers', 'givers', 'jin', 'algemeen');

    public static function getGroups()
    {
        $groups = array_keys(Environment::getSetting('scouts.takken'));
        $groups[] = 'algemeen';
        return $groups;
    }

    public function __construct($row = null)
    {
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
        if (isset($row['album_cover'])) {
            $this->cover_id = $row['album_cover'];
        }

        if (isset($row['album_image_count'])) {
            $this->image_count = intval($row['album_image_count']);
        }

        if (isset($row['latest_upload_date'])) {
            $this->latest_upload_date = new \DateTime($row['album_latest_upload_date']);
        }

        if (isset($row['zip_last_updated'])) {
            $this->zip_last_updated = new \DateTime($row['album_zip_last_updated']);
        }
    }

    public static function getQueueAlbum()
    {
        $album = new Album();
        $album->id = Self::$QUEUE_ID;
        $album->name = 'queue';
        return $album;
    }

    public function isQueue()
    {
        return $this->id == Self::$QUEUE_ID;
    }

    // Return true on successful update
    public function updateSourcesAvailable()
    {
        // Alle Files ophalen -> file_exists checken -> sources_available updaten
        $images = Image::getImagesFromAlbum($this->id);
        if (!isset($images)) {
            return false;
        }

        $all_sources = true;

        foreach ($images as $image) {
            foreach ($image->sources as $imagefile) {
                if ($imagefile->is_source) {
                    // double check here
                    $imagefile->file->updateSavedOnServer();
                    if (!$imagefile->file->saved_on_server) {
                        $all_sources = false;
                        break (2);
                    }
                }
            }
        }

        if ($all_sources != $this->sources_available) {
            $this->sources_available = $all_sources;
            return $this->save();
        }

        return true;
    }

    public function getFileStatistics()
    {
        $files = File::getFilesForAlbum($this->id);
        if (isset($this->zip_file)) {
            $file = File::getFile($this->zip_file);
            if (isset($file)) {
                $files[] = $file;
            }
        }
        return File::generateStatistics($files);
    }

    // Pas aan of de sources van dit album op de server moeten staan
    // bij elke nieuwe upload -> oproepen met true
    // zal automatisch terug op false gezet worden nadat de zip file is aangemaakt
    public function setSourcesShouldBeSavedOnServer($bool)
    {
        $b = 0;
        if ($bool) {
            $b = 1;
        }
        $id = self::getDb()->escape_string($this->id);

        $query = "UPDATE files f
                    inner join image_files i_f on i_f.imagefile_file = f.file_id
                    inner join images i on i.image_id = i_f.imagefile_image
                SET
                 f.file_should_be_saved_on_server = '$b'
                 where i.image_album = '$id' AND i_f.imagefile_is_source = 1";

        if (self::getDb()->query($query)) {
            return true;
        }
        return false;
    }

    public function delayDeletionOfImages()
    {
        $id = self::getDb()->escape_string($this->id);

        $d = (new \DateTime())->format('Y-m-d H:i:s');

        $query = "UPDATE files f
                    inner join image_files i_f on i_f.imagefile_file = f.file_id
                    inner join images i on i.image_id = i_f.imagefile_image
                SET
                 f.file_object_storage_date = '$d'
                 where i.image_album = '$id' AND i_f.imagefile_is_source = 1 AND f.file_object_storage_host is not null and f.file_saved_on_server = 1";

        if (self::getDb()->query($query)) {
            return true;
        }
        return false;
    }

    public function canDownload()
    {
        return isset($this->zip_file);
    }

    public function generateSlug()
    {
        $string = $this->name;

        $string = iconv("utf-8", "us-ascii//translit//ignore", $string); // transliterate
        $string = str_replace("'", "", $string);
        $string = preg_replace("~[^\pL\d]+~u", "-", $string); // replace non letter or non digits by "-"
        $string = preg_replace("~[^-\w]+~", "", $string); // remove unwanted characters
        $string = preg_replace("~-+~", "-", $string); // remove duplicate "-"
        $string = trim($string, "-"); // trim "-"
        $string = trim($string); // trim
        $string = mb_strtolower($string, "utf-8"); // lowercase
        $string = urlencode($string); // safe
        return $string;
    }

    public function getSlug()
    {
        if (!isset($this->slug)) {
            return $this->generateSlug();
        }
        return $this->slug;
    }

    public function getUrl()
    {
        return '/fotos/album/' . $this->date->format('Y/m/d') . '/' . $this->getSlug();
    }

    public static function getAlbum($id)
    {
        $id = self::getDb()->escape_string($id);

        $albums = array();
        $query = "SELECT a.*, c.*, count(i.image_id) as album_image_count
        from albums a
        left join images i on i.image_album = a.album_id
        left join images c on c.image_id = a.album_cover
        WHERE a.album_id = '$id'
        group by a.album_id, c.image_id";

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                return new Album($row);
            }
        }

        return null;
    }

    public static function getAlbumForFile($file_id)
    {
        $file_id = self::getDb()->escape_string($file_id);

        $albums = array();
        $query = "SELECT a.*
        from albums a
         join images i on i.image_album = a.album_id
         join image_files i_f on i_f.imagefile_image = i.image_id
         join files f on f.file_id = i_f.imagefile_file
        WHERE f.file_id = '$file_id'";

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                return new Album($row);
            }
        }

        return null;
    }

    public static function getHiddenAlbum($name)
    {
        $name = self::getDb()->escape_string($name);

        $albums = array();
        $query = "SELECT a.*, c.*, count(i.image_id) as album_image_count
        from albums a
        left join images i on i.image_album = a.album_id
        left join images c on c.image_id = a.album_cover
        WHERE a.album_slug = '$name' AND a.album_hidden = 1
        group by a.album_id, c.image_id";

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                return new Album($row);
            }
        }

        return null;
    }

    public static function getAlbumBySlug($year, $month, $day, $slug)
    {
        $slug = self::getDb()->escape_string($slug);
        $day = self::getDb()->escape_string($day);
        $month = self::getDb()->escape_string($month);
        $year = self::getDb()->escape_string($year);

        $query = 'SELECT a.*, c.*, i_f.*, f.*
        from albums a
        left join images c on c.image_id = a.album_cover
        join image_files i_f on i_f.imagefile_image = c.image_id
        join files f on f.file_id = i_f.imagefile_file
        WHERE a.album_slug = "' . $slug . '"
        AND MONTH(a.album_date) = "' . $month . '"
         AND YEAR(a.album_date) = "' . $year . '"
          AND DAY(a.album_date) = "' . $day . '"';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
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

    public static function getAlbums($group = null, $page = 1, $with_cover = false, $limit = null)
    {
        $page = intval($page);

        /*$limit = 'LIMIT '.(($page-1)*4).', 50';
        if ($page < 1) {
        $limit = '';
        }*/

        if (isset($limit)) {
            $limit = 'LIMIT ' . intval($limit);
        } else {
            $limit = '';

        }

        $where = 'WHERE a.album_hidden = 0 ';
        if (isset($group)) {
            $where .= 'AND a.album_group = "' . self::getDb()->escape_string($group) . '"';
        }

        $albums = array();

        if (!$with_cover) {
            $query = 'SELECT a.*, c.*, count(i.image_id) as album_image_count
                    from albums a
                    left join images i on i.image_album = a.album_id
                    left join images c on c.image_id = a.album_cover
                    ' . $where . '
                    group by a.album_id, c.image_id
                    order by a.album_date desc ' . $limit;

            if ($result = self::getDb()->query($query)) {
                if ($result->num_rows > 0) {
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
                left join images c on c.image_id = a.album_cover
                join image_files i_f on i_f.imagefile_image = c.image_id
                join files f on f.file_id = i_f.imagefile_file
                 ' . $where . '
                order by YEAR(a.album_date_taken) desc, a.album_date_taken desc, a.album_id desc ' . $limit;

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
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

    public static function getZippableAlbums($limit = 5)
    {
        // Get albums with zip files that can and should be updated
        // + is not the queue -> never zip!
        $limit = intval($limit);
        $albums = array();
        $query = 'SELECT a.*, zip_file.file_upload_date as album_zip_last_updated, max(f.file_upload_date) as album_latest_upload_date
                from albums a
                 join images i on i.image_album = a.album_id
                 join image_files i_f on i_f.imagefile_image = i.image_id
                 join files f on f.file_id = i_f.imagefile_file

                left join files zip_file on zip_file.file_id = a.album_zip_file

                where a.album_sources_available = 1 and album_id != ' . Self::$QUEUE_ID . '
                group by a.album_id
                having zip_file.file_upload_date is null or album_zip_last_updated < album_latest_upload_date
                limit ' . $limit;

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $albums[] = new Album($row);
                }
            }
        }
        return $albums;

    }

    // Get name suggestion for images
    public static function getNameSuggestion($group, $images)
    {
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
                if (strtolower($event->group) != $group && !($group == 'algemeen' && !in_array($event->group, self::getGroups()))) {
                    continue;
                }

                // Overlap berekenen
                $overlap = $event->enddate->getTimestamp() - $event->startdate->getTimestamp();

                if ($overlap >= $overlap_max || (isset($event_max) && ($event_max->enddate < $maxdate || $event_max->startdate > $mindate))) {
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

            return $date . ' tot ' . $date2;
        }

        return '';
    }

    public function save()
    {
        $name = self::getDb()->escape_string($this->name);
        $date = self::getDb()->escape_string($this->date->format('Y-m-d H:i:s'));
        $date_taken = self::getDb()->escape_string($this->date_taken->format('Y-m-d'));

        $group = self::getDb()->escape_string($this->group);

        $author = "NULL";
        if (isset($this->author)) {
            $author = "'" . self::getDb()->escape_string($this->author) . "'";
        }

        $cover = "NULL";
        if (isset($this->cover)) {
            $cover = "'" . self::getDb()->escape_string($this->cover->id) . "'";
        } else {
            if (isset($this->cover_id)) {
                // Voor als de cover niet kan worden meegegeven als object -> prevent deletion
                $cover = "'" . self::getDb()->escape_string($this->cover_id) . "'";
            }
        }

        $zip_file = "NULL";
        if (isset($this->zip_file)) {
            $zip_file = "'" . self::getDb()->escape_string($this->zip_file) . "'";
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

    public static function isValidGroup($group)
    {
        foreach (self::getGroups() as $value) {
            if ($value == $group) {
                return true;
            }
        }
        return false;
    }

    public function setProperties(&$data, &$errors)
    {
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

    public static function getPathForAlbum($album = null, $deletePath = false)
    {
        // Todo: remove this function
        $path = Image::getLonelyImagePath();

        if (isset($album) && isset($album->id)) {
            if ($album->id == Self::$QUEUE_ID) {
                $leiding_id = Leiding::getUser()->id;
                $path = 'albums/queue/' . $leiding_id . '/';
            } else {
                if ($deletePath) {
                    $path = 'albums/' . $album->id . '/';
                } else {
                    return $album->getPath();
                }
            }
        }

        return $path;
    }

    public function getPath($delete = false)
    {
        if ($this->id == Self::$QUEUE_ID) {
            $leiding_id = Leiding::getUser()->id;
            return 'albums/queue/' . $leiding_id . '/';
        }
        if ($delete) {
            // Hele directory verwijderen met id
            return 'albums/' . $this->id . '/';
        }
        return 'albums/' . $this->id . '/' . $this->getSlug() . '/';
    }

    public function createFromImageQueue()
    {
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
    public function addImageQueue()
    {
        global $FILES_DIRECTORY;

        if (!isset($this->author)) {
            return false;
        }
        $author = self::getDb()->escape_string($this->author);
        $id = self::getDb()->escape_string($this->id);
        $slug = self::getDb()->escape_string($this->getSlug());

        $queue_dir = self::getDb()->escape_string(Self::getQueueAlbum()->getPath());
        $new_dir = self::getDb()->escape_string($this->getPath());

        $query = "UPDATE images i
            join image_files i_f on i_f.imagefile_image = i.image_id
            join files f on f.file_id = i_f.imagefile_file
            SET
             i.image_album = '$id',
             f.file_location = REPLACE(f.file_location, '" . $queue_dir . "', '" . $new_dir . "'),
             f.file_should_be_saved_in_object_storage = 1
            where i.image_album = '" . Self::$QUEUE_ID . "' and f.file_author = '$author'";

        if (self::getDb()->query($query)) {
            $error_reporting = error_reporting();
            //error_reporting(0);
            $dir = $FILES_DIRECTORY . '/' . $this->getPath();

            $old = umask(0);
            if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                umask($old);
                error_reporting($error_reporting);
                return false;
            }
            umask($old);

            rename($FILES_DIRECTORY . '/' . Self::getQueueAlbum()->getPath(), $dir);
            error_reporting($error_reporting);

            $this->updateSourcesAvailable();
            return true;
        }

        return false;
    }

    public function onImageAdded($image)
    {
        // Triggered on upload of a picture
        if (!$this->isQueue()) {
            $this->deleteZip();
        }

        // Set sources should be available
        $available = false;

        $source = $image->getRealSource();
        if (isset($source)) {
            if ($source->file->saved_on_server) {
                // Oef, staat nog op de server
                $available = true;
            }
        }

        if (!$available) {
            $this->sources_available = false;
        }
        $this->setSourcesShouldBeSavedOnServer(true);
    }

    public function onImageDeleted($image)
    {
        if (!$this->isQueue()) {
            $this->deleteZip();
        }
        $this->setSourcesShouldBeSavedOnServer(true);
    }

    public function onFileRemovedFromServer($file)
    {
        // Weet nooit zeker of het om een source gaat

        if ($this->sources_available) {
            // Enkel doen als ze beschikbaar waren,
            // want zou enkel onbeschikbaar kunnen worden
            $this->updateSourcesAvailable();
        }

    }

    public function delete()
    {
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

            // Verwijder de images
            $query = "DELETE images FROM images
                join albums on albums.album_id = images.image_album
                left join image_files on image_files.imagefile_image = images.image_id
                WHERE albums.album_id = '$id' and image_files.imagefile_id is null";

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

        $path = $FILES_DIRECTORY . '/' . $this->getPath(true);
        exec("rm -rf \"$path\"", $output, $response);

        return ($response === 0);
    }

    public function deleteZip()
    {
        if (!isset($this->zip_file)) {
            return true;
        }

        $file = File::getFile($this->zip_file);
        if (isset($file)) {
            return $file->delete();
        }

        return false;
    }

    public function isZipUpToDate()
    {
        // Opgelet: zware functie indien album niet werd opgehaald met zip_last_updated en latest_upload_date attributes
        if (!isset($this->zip_file)) {
            return false;
        }

        if (!isset($this->zip_last_updated)) {
            // todo vergelijking

            $file = File::getFile($this->zip_file);
            if (!isset($file)) {
                $this->zip_file = null;
                $this->save();
                return false;
            }
            $this->zip_last_updated = $file->upload_date;
        }

        if (!isset($this->latest_upload_date)) {
            // Get latest image from this album
            $latest_image = File::getLatestSourceFileForAlbum($this->id);
            if (!isset($latest_image)) {
                return true;
            }
            $this->latest_upload_date = $latest_image->upload_date;
        }

        return $this->latest_upload_date < $this->zip_last_updated;
    }

    public function zip(&$errors)
    {
        if ($this->isQueue()) {
            // Queue mag nooit gezipped worden
            $errors[] = 'Queue is not zippable';
            return false;
        }

        // Zippen is enkel mogelijk als alle sources available zijn
        if (!$this->updateSourcesAvailable(true)) {
            $errors[] = 'UpdateSourcesAvailable failed';
            return false;
        }

        if (!$this->sources_available) {
            // Kan performance probleem teweeg brengen als er sources verdwenen zijn
            // Omdat dit in de cron job dan onnodige queries teweeg brengt
            // Dus misschien toch volgende lijn niet uitvoeren?
            $this->setSourcesShouldBeSavedOnServer(true);

            $errors[] = 'Sources are not available on server';
            return false;
        }

        // Hebben we al een ZIP file?
        if (isset($this->zip_file)) {
            //Is deze up to date?
            if ($this->isZipUpToDate()) {
                $this->setSourcesShouldBeSavedOnServer(false);
                return true;
            }

            // Niet up to date
            if ($this->updateZip()) {
                $this->zip_file->saved_on_server = true;
                $this->zip_file->object_storage_date = null;
                $this->zip_file->object_storage_host = null;
                $this->zip_file->upload_date = new \DateTime();

                if ($this->zip_file->save()) {
                    $this->zip_last_updated = new \DateTime();
                    $this->setSourcesShouldBeSavedOnServer(false);
                    $this->delayDeletionOfImages();
                    return true;
                }
            }

            $errors[] = 'Zip update failed';
            return false;

        }

        // We hebben nog geen zip file
        if ($this->createZip()) {
            $this->zip_last_updated = new \DateTime();
            $this->setSourcesShouldBeSavedOnServer(false);
            $this->delayDeletionOfImages();
            return true;
        }

        $errors[] = 'Zip creation failed';
        return false;
    }

    private function updateZip()
    {
        global $FILES_DIRECTORY;

        // Todo: fix object storage here
        //
        // Zip updaten
        if (!isset($this->zip_file)) {
            // Updaten natuurlijk niet mogelijk
            return false;
        }

        $name = $this->getSlug() . '.zip';
        $album_path = $this->getPath();
        $file_path = $FILES_DIRECTORY . '/' . $album_path . $name;
        $path = $FILES_DIRECTORY . '/' . $album_path . 'sources';

        // File sync here (= -FS)
        exec("zip -FSjr \"$file_path\" \"$path\"", $output, $response);

        echo implode("\n", $output);
        return ($response === 0);
    }

    private function createZip()
    {
        // Todo: fix object storage here
        //
        global $FILES_DIRECTORY;
        if (isset($this->zip_file)) {
            return true;
        }

        $album_path = $this->getPath();
        $name = $this->getSlug() . '.zip';
        $file_path = $FILES_DIRECTORY . '/' . $album_path . $name;
        $path = $FILES_DIRECTORY . '/' . $album_path . 'sources';
        exec("zip -FSjr \"$file_path\" \"$path\"", $output, $response);

        if ($response === 0) {
            $errors = array();
            $file = File::createFromFile($album_path, $name, $errors);
            if (!isset($file)) {
                echo "createFromFile error\n";
                return false;
            }
            if (!$file->save()) {
                echo "file->save error\n";
                return false;
            }
            $this->zip_file = $file->id;
            return $this->save();
        }
        echo "zip error:\n";
        echo implode("\n", $output);
        return false;
    }

}
