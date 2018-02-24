<?php
namespace Pirate\Model\Files;
use Pirate\Model\Model;
use Pirate\Model\Leiding\Leiding;
//use Pirate\Model\Files\Space;
//use Pirate\Model\Files\SpaceRequest; // todo: if not found -> error file not exists
//use Pirate\Model\Files\SpaceResponse; // todo: if not found -> error file not exists

class File extends Model {
    public $id;
    public $name;
    public $extension;
    public $location;
    public $size; // bytes
    public $upload_date;
    public $author;

    public $object_storage_host;
    public $saved_on_server = true;

    // Datum waarop file werd geupload op object storage (= dient voor cronjobs om het origineel te verwijderen te vertragen om foutieve file requests te voorkomen)
    public $object_storage_date = null;
    public $should_be_saved_on_server = false;
    public $should_be_saved_in_object_storage = true;

    private $new = false;

    static private $restrictedExtensions = array('exe', 'pif', 'application', 'gadget', 'msi', 'jar', 'msc', 'bat', 'cmd', 'vb', 'vbs', 'vbe', 'ps1', 'ps1xml', 'ps2', 'ps2xml', 'psc1', 'psc2', 'msh', 'msh1', 'msh2', 'mshxml', 'msh1xml', 'msh2xml', 'scf', 'lnk', 'inf', 'reg', 'php', 'cgi', 'torrent', 'js', 'app', 'pif', 'vbscript', 'wsf', 'asp', 'cer', 'csr', 'jsp', 'drv', 'sys', 'ade', 'adp', 'htaccess', 'sh');

    static public $max_size = 20000000; // in bytes

    // Delay before a file is deleted from the server after object_storage_date when should_be_saved_on_server turns false
    static public $DELETE_DELAY = 1*60; // Seconds

    function __construct($row = null) {
        if (!isset($row)) {
            $this->new = true;
            return;
        }

        $this->id = $row['file_id'];
        $this->name = $row['file_name'];
        $this->extension = $row['file_extension'];
        $this->location = $row['file_location'];
        $this->size = $row['file_size'];
        $this->upload_date = new \DateTime($row['file_upload_date']);
        $this->author = $row['file_author'];
        $this->saved_on_server = ($row['file_saved_on_server'] == 1);
        $this->should_be_saved_on_server = ($row['file_should_be_saved_on_server'] == 1);
        $this->should_be_saved_in_object_storage = ($row['file_should_be_saved_in_object_storage'] == 1);

        if (isset($row['file_object_storage_date'])) {
            $this->object_storage_date = new \DateTime($row['file_object_storage_date']);
        } else {
            $this->object_storage_date = null;
        }


        if (isset($row['file_object_storage_host'])) {
            $this->object_storage_host = $row['file_object_storage_host'];
        } else {
            $this->object_storage_host = null;
        }

    }

    static function getFile($id) {
        $id = self::getDb()->escape_string($id);
        $query = 'SELECT * FROM files WHERE file_id = "'.$id.'"';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                $row = $result->fetch_assoc();
                return new File($row);
            }
        }

        return null;
    }

    static function getFilesForAlbum($id) {
        $id = self::getDb()->escape_string($id);
        $query = "
            SELECT f.* FROM files f
                inner join image_files i_f on i_f.imagefile_file = f.file_id
                inner join images i on i.image_id = i_f.imagefile_image
        WHERE image_album = '$id'";

        $files = [];
        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while($row = $result->fetch_assoc()) {
                    $files[] = new File($row);
                }
            }
        }

        return $files;
    }


    static function getLatestSourceFileForAlbum($id) {
        $id = self::getDb()->escape_string($id);
        $query = "
            SELECT f.* FROM files f
                inner join image_files i_f on i_f.imagefile_file = f.file_id
                inner join images i on i.image_id = i_f.imagefile_image
        WHERE image_album = '$id' 
        order by f.file_upload_date desc
        limit 1";

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                $row = $result->fetch_assoc();
                return new File($row);
            }
        }

        return null;
    }

    static function generateStatistics($files) {
        $stats = (object) array(
            'count_server' => 0,

            'count_downloading_from_object_storage' => 0,
            'size_downloading_from_object_storage' => 0,
            'count_object_storage' => 0,

            'count_uploading_to_object_storage' => 0,
            'size_uploading_to_object_storage' => 0,
            'count_object_storage' => 0,

            'count_removing_from_server' => 0,
            'size_removing_from_server' => 0,

            'size_server' => 0,
            'size_object_storage' => 0,

            'count' => 0,
            'size' => 0,
        );
        foreach ($files as $file) {
            $stats->count++;
            $stats->size += $file->size;

            if ($file->saved_on_server) {

                $stats->count_server++;
                $stats->size_server += $file->size;

                if ($file->should_be_saved_in_object_storage && !isset($file->object_storage_host)) {
                    $stats->count_uploading_to_object_storage++;
                    $stats->size_uploading_to_object_storage += $file->size;
                }

                if (!$file->should_be_saved_on_server && isset($file->object_storage_host)) {
                    $stats->count_removing_from_server++;
                    $stats->size_removing_from_server += $file->size;
                }

            }

            if (isset($file->object_storage_host)) {
                $stats->count_object_storage++;
                $stats->size_object_storage += $file->size;
                
                if ($file->should_be_saved_on_server && !$file->saved_on_server) {
                    $stats->count_downloading_from_object_storage++;
                    $stats->size_downloading_from_object_storage += $file->size;
                }
            }
        }

        $stats->availability_server = round(($stats->size_server - $stats->size_removing_from_server) / $stats->size * 100, 2);
        $stats->availability_working_server = round(($stats->size_downloading_from_object_storage + $stats->size_removing_from_server) / $stats->size * 100, 2);

        $stats->availability_object_storage = round($stats->size_object_storage / $stats->size * 100, 2);
        $stats->availability_working_object_storage = round($stats->size_uploading_to_object_storage / $stats->size * 100, 2);

        $stats->size = Self::convertSizeToString($stats->size);
        $stats->size_server = Self::convertSizeToString($stats->size_server);
        $stats->size_object_storage = Self::convertSizeToString($stats->size_object_storage);

        return $stats;
    }

    static function convertSizeToString($size) {
        if ($size < 100) {
            return $size.' byte';
        }
        $size /= 1000;
        if ($size < 500) {
            return round($size, 2).' kB';
        }

        $size /= 1000;
        if ($size < 500) {
            return round($size, 2).' MB';
        }

        $size /= 1000;
        if ($size < 500) {
            return round($size, 2).' GB';
        }

        $size /= 1000;
        if ($size < 500) {
            return round($size, 2).' TB';
        }

        $size /= 1000;
        return round($size, 2).' PB';
    }

    // Uploadable
    static function getFilesNotObjectStorage($limit = 100) {
        $limit = intval($limit);
        $query = 'SELECT * FROM files WHERE file_object_storage_host IS NULL AND file_saved_on_server = 1 AND file_should_be_saved_in_object_storage = 1 order by file_size desc LIMIT '.$limit;

        $files = array();

        if ($result = self::getDb()->query($query)){
            while ($row = $result->fetch_assoc()) {
                $files[] = new File($row);
            }
        }

        return $files;
    }

    // Downloadable
    static function getFilesNotSavedOnServer($limit = 60) {
        $limit = intval($limit);
        $query = 'SELECT * FROM files WHERE file_object_storage_host IS NOT NULL AND file_saved_on_server = 0 AND file_should_be_saved_on_server = 1 LIMIT '.$limit;
        $files = array();

        if ($result = self::getDb()->query($query)){
            while ($row = $result->fetch_assoc()) {
                $files[] = new File($row);
            }
        }

        return $files;
    }

    static function getRemoveableFiles($limit = 200) {
        $limit = intval($limit);
        $delay = intval(Self::$DELETE_DELAY);
        $date = (new \DateTime())->format('Y-m-d H:i:s'); // mysql NOW() kan niet gesynchroniseerd zijn met PHP tijd
        $query = "
            SELECT * 
            FROM files 
            WHERE 
            file_object_storage_host IS NOT NULL 
            AND file_saved_on_server = 1 
            AND file_should_be_saved_on_server = 0 
            AND file_should_be_saved_in_object_storage = 1
            AND file_object_storage_date < ('$date' - INTERVAL $delay SECOND) 
            order by file_size desc
            LIMIT $limit";

        $files = array();

        if ($result = self::getDb()->query($query)){
            while ($row = $result->fetch_assoc()) {
                $files[] = new File($row);
            }
        }

        return $files;
    }

    function updateSavedOnServer() {
        // Check and return success
        clearstatcache();
        $new = file_exists($this->getPath());
        if ($new != $this->saved_on_server) {
            $this->saved_on_server = $new;
            return $this->save();
        }
        return true;
    }

    function getPath() {
        global $FILES_DIRECTORY;

        return $FILES_DIRECTORY.'/'.$this->getKey();
    }

    function getPublicPath() {
        if (isset($this->object_storage_host)) {
            return "https://".$this->object_storage_host."/".$this->getKey();
        }

        return "https://".str_replace('www.','files.',$_SERVER['SERVER_NAME'])."/".$this->getKey();
    }

    function getKey() {
        return $this->location.$this->name;
    }

    function deleteFromServer(&$errors) {
        if (unlink(realpath($this->getPath())) === false) {
            $errors[] = 'Deleting file failed.';
            return false;
        }
        clearstatcache();

        $this->saved_on_server = false;

        $album = Album::getAlbumForFile($this->id);
        if (isset($album)) {
            $album->onFileRemovedFromServer($this);
        }

        return $this->save();
    }

    function deleteFromSpace(&$errors) {
        // todo
        $errors[] = 'Not yet implemented.';
        return false;
    }

    function downloadFromSpace(&$errors) {
        if (!isset($this->object_storage_host)) {
            $errors[] = 'File has no object_storage_host';
            return false;
        }

        $f = fopen($this->getPublicPath(), 'r');

        if ($f === false) {
            $errors[] = 'Failed to open stream to '.$this->getPublicPath();
            return false;
        }

        if (file_put_contents($this->getPath(), $f) === false) {
            fclose($f);
            $errors[] = 'Failed to save file from space '.$this->getPublicPath();
            return false;
        }

        fclose($f);
        
        $this->saved_on_server = true;
        if ($this->save()) {
             $album = Album::getAlbumForFile($this->id);
            if ($album) {
                $album->updateSourcesAvailable();
            }
            return true;
        }
        
        return false;
    }

    function uploadToSpace(&$errors) {
        if (!file_exists($this->getPath())) {
            $errors[] = 'Bestand '.$this->id.' staat niet meer op de server opgeslagen!';
            $this->saved_on_server = false;
            $this->save();
            // todo: album -> update_Sources_available aanroepen (mag eigenlijk niet hierin)
            return false;
        }

        $space = Space::getDefault();
        $request = new SpaceRequest('PUT', $space, '/'.$this->getKey());
        $request->setHeaders(
            array(
                'x-amz-acl' => 'public-read',
                'x-amz-storage-class' => 'STANDARD',
            )
        );

        $request->setFile($this->getPath());
        //$request->setText('Hello world');
        $response = $request->send();

        if (!$response->success) {
            $errors[] = 'Er ging iets mis';
            return false;
        }

        if ($response->http_statuscode == 200) {
            // Todo: maak aanpassingen aan mysql object!
            $this->object_storage_host = $space->getHost();
            $this->object_storage_date = new \DateTime();
            return $this->save();
        }

        $errors[] = $response->body;

        return false;
    }

    static function isFileSelected($form_name) {
        if (!isset($_FILES[$form_name])) {
            return false;
        }

        $error = $_FILES[$form_name]['error'];
        if ($error == UPLOAD_ERR_NO_FILE) {
            return false;
        }
        return true;
    }

    static function getUploaded($form_name, &$ext, &$name, &$size, &$errors, $max_size, $file_types = null, $use_name = null) {
        if (!isset($_FILES[$form_name])) {
            $errors[] = 'Er werd geen bestand gevraagd in het formulier.';
            return false;
        }

        $name = $_FILES[$form_name]['name'];
        $name = trim($name);
        $ext = strtolower(substr(strrchr($name,'.'),1));
        $name = strtolower($name);

        if (isset($use_name)) {
            $name = $use_name.'.'.$ext;
        }

        $size = $_FILES[$form_name]['size'];
        $error = $_FILES[$form_name]['error'];

        // documentation: http://php.net/manual/en/features.file-upload.errors.php
        if ($error != UPLOAD_ERR_OK) {
            switch ($error) {
                case UPLOAD_ERR_INI_SIZE:
                    $errors[] = 'Het bestand dat je wilt uploaden is te groot en werd geweigerd door de server.';
                    break;
                
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = 'Het bestand dat je wilt uploaden is te groot en werd onderbroken.';
                    break;

                case UPLOAD_ERR_PARTIAL:
                    $errors[] = 'Het uploaden van het bestand werd onderbroken.';
                    break;

                case UPLOAD_ERR_NO_FILE:
                    $errors[] = 'Er is geen bestand geselecteerd.';
                    break;

                case UPLOAD_ERR_NO_TMP_DIR:
                    $errors[] = 'Er ging iets mis bij het uploaden waardoor we het bestand niet tijdelijk konden opslaan. Contacteer de webmaster.';
                    break;

                case UPLOAD_ERR_CANT_WRITE:
                    $errors[] = 'Er ging iets mis bij het uploaden (toegangsprobleem). Contacteer de webmaster.';
                    break;

                case UPLOAD_ERR_EXTENSION:
                    $errors[] = 'Er ging iets mis bij het uploaden (php extensie verhinderde upload). Contacteer de webmaster.';
                    break;

                default:
                    $errors[] = 'Onbekende fout.';
                break;
            }
            return false;
        }

        if (isset($file_types)) {
            $found = false;
            foreach ($file_types as $value) {
                if ($value == $ext) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $errors[] = 'Bestandstype "'.$ext.'" niet toegelaten.';
                return false;
            }
        }

        if ($ext == '') {
            $errors[] = 'Je kan geen bestand uploaden zonder extensie.';
                return false;
        }

        if (preg_match('/^[A-z]+$/', $ext) !== 1) {
            $errors[] = 'Ongeldig bestandstype.';
            return false;
        }

        if (preg_match('/^[-0-9A-z_\.@ ]+$/', $name) !== 1) {
            $name = preg_replace('/[^0-9A-z_\.@]+/', '-', $name);
        }

        foreach (self::$restrictedExtensions as $value) {
            if ($value == $ext) {
                $errors[] = 'Bestandstype "'.$ext.'" niet toegelaten.';
                return false;
            }
        }

        if($size == 0) {
            $errors[] = 'Geen bestand geselecteerd.';
            return false;
        }

        if ($size > $max_size) {
            $errors[] = 'Het bestand dat je wilt uploaden is te groot.';
            return false;
        }

        $len = mb_strlen($name, "UTF-8");

        if ($len < 1 && $len > 255) {
            $errors[] = 'Ongeldige bestandsnaam.';
            return false;
        }

        return true;
    }

    // Kan zowel nieuw uploaden als bestaand bestand overschrijven
    // use_name = sla op met deze naam (excl extensie)
    function upload($form_name, &$errors, $file_types = null, $use_name = null) {
        if (!$this->new) {
            $errors[] = 'Kan bestand niet overschrijven.';
            return false;
        }

        if (!self::getUploaded($form_name, $ext, $name, $size, $errors, self::$max_size, $file_types, $use_name)) {
            return false;
        }

        // Alles oké
        $this->name = $name;
        $this->extension = $ext;
        $date = new \DateTime();

        if (!isset($this->location)) {
            $this->location = $date->format('Y/m/d/');
        } else {
            if ($this->location != '' && substr($this->location, -1) != '/') {
                $errors[] = 'Ongeldige bestandslocatie (contacteer webmaster).';
                return false;
            }
        }

        if (Leiding::isLoggedIn()) {
            $this->author = Leiding::getUser()->id;
        }
        
        $this->upload_date = $date;
        $this->size = $size;

        // Error reporting tijdelijk uitzetten
        $error_reporting = error_reporting();
        error_reporting(0);

        $prelocation = $this->location;
        $num = 1;
        while (file_exists($this->getPath())) {
            $num++;
            $this->location = $prelocation.'v'.$num.'/';
        }

        // Vanaf nu willen we niet meer stoppen, en gaan we gewoon verder. Tenzij het echt te lang duurt.
        ignore_user_abort(true);

        // Opslaan in mysql en rollback als verplaatsen mislukt
        self::getDb()->autocommit(false);
        if (!$this->save()) {
            self::getDb()->rollback(); // voor als we al in autocommit zaten voor het aanroepen van deze functie
            self::getDb()->autocommit(true);
            $errors[] = 'Opslaan in database mislukt';
            error_reporting($error_reporting);
            return false;
        }

        // Om één of andere reden moet we dit soms 2 keer proberen voor het werkt...?
        $old = umask(0);
        $dir = dirname($this->getPath());

        $try = 0;
        $failed = true;
        while($try < 2) {
            $try++;
            
            if (is_dir($dir) || mkdir($dir, 0777, true)) {
                $failed = false;
                break;
            }
        }
        umask($old);

        if ($failed) {
            $errors[] = 'Kon mapstructuur niet aanmaken: '.$dir;
            error_reporting($error_reporting);
            return false;
        }


        // Verplaatsen
        if (!move_uploaded_file($_FILES[$form_name]['tmp_name'], $this->getPath())) {
            self::getDb()->rollback();
            self::getDb()->autocommit(true);
            $errors[] = 'Opslaan mislukt naar '.$this->getPath();
            error_reporting($error_reporting);
            return false;
        }

        self::getDb()->commit();
        self::getDb()->autocommit(true);
        error_reporting($error_reporting);
        return true;
    }

    // Maakt een file object aan, maar slaat deze nog niet op in de database
    static function createFromFile($location, $name, &$errors) {
        $file = new File();

        $ext = strtolower(substr(strrchr($name,'.'),1));

        if ($location != '' && substr($location, -1) != '/') {
            $errors[] = 'Ongeldige bestandslocatie (contacteer webmaster).';
            return false;
        }
        
        $file->name = $name;
        $file->location = $location;
        $file->extension = $ext;
        $date = new \DateTime();

        if (Leiding::isLoggedIn()) {
            $file->author = Leiding::getUser()->id;
        }
        $file->upload_date = $date;

        if (!file_exists($file->getPath())) {
            $errors[] = 'Bestand bestaat niet';
            return null;
        }
        $file->size = filesize($file->getPath());

        return $file;
    }

    function save() {
        $name = self::getDb()->escape_string($this->name);
        $extension = self::getDb()->escape_string($this->extension);
        $location = self::getDb()->escape_string($this->location);
        $size = self::getDb()->escape_string($this->size);
        $upload_date = self::getDb()->escape_string($this->upload_date->format('Y-m-d H:i:s'));

        $saved_on_server = 0;
        if ($this->saved_on_server) {
            $saved_on_server = 1;
        }

        $should_be_saved_on_server = 0;
        if ($this->should_be_saved_on_server) {
            $should_be_saved_on_server = 1;
        }

        
        $should_be_saved_in_object_storage = 0;
        if ($this->should_be_saved_in_object_storage) {
            $should_be_saved_in_object_storage = 1;
        }

        if (!isset($this->object_storage_host)) {
            $object_storage_host = 'NULL';
        } else {
            $object_storage_host = "'".self::getDb()->escape_string($this->object_storage_host)."'";
        }

        if (!isset($this->object_storage_date)) {
            $object_storage_date = 'NULL';
        } else {
            $object_storage_date = "'".self::getDb()->escape_string($this->object_storage_date->format('Y-m-d H:i:s'))."'";
        }

        if (!isset($this->author)) {
            $author = 'NULL';
        } else {
            $author = "'".self::getDb()->escape_string($this->author)."'";
        }

        // Permissions

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);

            $query = "UPDATE files 
                SET 
                 file_name = '$name',
                 file_extension = '$extension',
                 file_location = '$location',
                 file_size = '$size',
                 file_upload_date = '$upload_date',
                 file_author = $author,
                 file_object_storage_host = $object_storage_host,
                 file_saved_on_server = '$saved_on_server',
                 file_object_storage_date = $object_storage_date,
                 file_should_be_saved_on_server = '$should_be_saved_on_server',
                 file_should_be_saved_in_object_storage = '$should_be_saved_in_object_storage'
                 where file_id = '$id' 
            ";
        } else {
            $query = "INSERT INTO 
                files 
                (
                    `file_name`, 
                    `file_extension`, 
                    `file_location`, 
                    `file_size`, 
                    `file_upload_date`, 
                    `file_author`, 
                    `file_object_storage_host`, 
                    `file_saved_on_server`, 
                    `file_object_storage_date`, 
                    `file_should_be_saved_on_server`,
                    `file_should_be_saved_in_object_storage`
                )
                VALUES 
                (
                    '$name', 
                    '$extension', 
                    '$location', 
                    '$size', 
                    '$upload_date', 
                    $author, 
                    $object_storage_host, 
                    '$saved_on_server', 
                    $object_storage_date, 
                    '$should_be_saved_on_server', 
                    '$should_be_saved_in_object_storage'
                )";
        }

        if (self::getDb()->query($query)) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        }else {
            echo $query;
            echo self::getDb()->error."\n";
        }

        return false;
    }

    function delete() {
        if (!isset($this->id)) {
            return false;
        }

        $id = self::getDb()->escape_string($this->id);

        $query = "DELETE FROM files WHERE file_id = '$id'";

        // Todo errors doorgeven
        $errors = array();
        
        if (!self::getDb()->query($query)) {
            $errors[] = 'Er ging iets mis bij het aanpassen van de database.';
            return false;
        }

        clearstatcache();

        if (file_exists(realpath($this->getPath()))) {
            if (unlink(realpath($this->getPath())) === false) {
                $errors[] = 'De foto is verwijderd uit de database, maar niet volledig uit het bestandssysteem (door een interne fout).';
                return false;
           }
        }

       return true;
    }
}