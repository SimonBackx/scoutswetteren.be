<?php
namespace Pirate\Model\Files;
use Pirate\Model\Model;
use Pirate\Model\Leiding\Leiding;

class File extends Model {
    public $id;
    public $name;
    public $extension;
    public $location;
    public $size; // bytes
    public $upload_date;
    public $author;

    private $new = false;

    static private $restrictedExtensions = array('exe', 'pif', 'application', 'gadget', 'msi', 'jar', 'msc', 'bat', 'cmd', 'vb', 'vbs', 'vbe', 'ps1', 'ps1xml', 'ps2', 'ps2xml', 'psc1', 'psc2', 'msh', 'msh1', 'msh2', 'mshxml', 'msh1xml', 'msh2xml', 'scf', 'lnk', 'inf', 'reg', 'php', 'cgi', 'torrent', 'js', 'app', 'pif', 'vbscript', 'wsf', 'asp', 'cer', 'csr', 'jsp', 'drv', 'sys', 'ade', 'adp', 'htaccess', 'sh');

    static private $max_size = 20000000; // in bytes

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
    }

    function getPath() {
        global $FILES_DIRECTORY;

        return $FILES_DIRECTORY.'/'.$this->location.$this->name;
    }

    function getPublicPath() {
        return "https://".str_replace('www.','files.',$_SERVER['SERVER_NAME'])."/".$this->location.$this->name;
    }

    // Kan zowel nieuw uploaden als bestaand bestand overschrijven
    function upload($form_name, &$errors) {        
        if (!$this->new) {
            $errors[] = 'Kan bestand niet overschrijven.';
            return false;
        }

        if (!isset($_FILES[$form_name])) {
            $errors[] = 'Er werd geen bestand gevraagd in het formulier.';
            return false;
        }

        $name = $_FILES[$form_name]['name'];
        $ext = strtolower(substr(strrchr($name,'.'),1));
        $name = strtolower($name);

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
                    $errors[] = 'Er ging iets mis bij het uploaden (extensie verhinderde upload). Contacteer de webmaster.';
                    break;

                default:
                    $errors[] = 'Onbekende fout';
                break;
            }
            return false;
        }

        if ($ext == '') {
            $errors[] = 'Je kan geen bestand uploaden zonder extensie.';
                return false;
        }

        if (preg_match('/^[A-z]+$/', $ext) !== 1) {
            $errors[] = 'Ongeldig bestandstype';
            return false;
        }

        if (preg_match('/^[-0-9A-z_\. ]+$/', $name) !== 1) {
            $errors[] = 'Ongeldige bestandsnaam';
            return false;
        }

        foreach (self::$restrictedExtensions as $value) {
            if ($value == $ext) {
                $errors[] = 'Bestandstype "'.$ext.'" niet toegelaten.';
                return false;
            }
        }

        if($size == 0) {
            $errors[] = 'Geen bestand geselecteerd';
            return false;
        }

        if ($size > self::$max_size) {
            $errors[] = 'Het bestand dat je wilt uploaden is te groot.';
            return false;
        }

        $len = mb_strlen($name, "UTF-8");

        if ($len < 1 && $len > 255) {
            $errors[] = 'Ongeldige bestandsnaam';
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


        $prelocation = $this->location;
        $num = 1;
        while (file_exists($this->getPath())) {
            $num++;
            $this->location = $prelocation.'v'.$num.'/';
        }

        // Opslaan in mysql en rollback als verplaatsen mislukt
        self::getDb()->autocommit(false);
        if (!$this->save()) {
            self::getDb()->autocommit(true);
            $errors[] = 'Opslaan in database mislukt';
            return false;
        }

        $dir = dirname($this->getPath());
        if (!is_dir($dir) && !@mkdir($dir, 755, true)) {
            $errors[] = 'Kon mapstructuur niet aanmaken.';
            return false;
        }

        // Verplaatsen
        if (!move_uploaded_file($_FILES[$form_name]['tmp_name'], $this->getPath())) {
            self::getDb()->rollback();
            self::getDb()->autocommit(true);
            $errors[] = 'Opslaan mislukt';
            return false;
        }

        self::getDb()->commit();
        self::getDb()->autocommit(true);
        return true;
    }

    function from_file($location, $name, &$errors) {
        $ext = strtolower(substr(strrchr($name,'.'),1));

        if ($location != '' && substr($location, -1) != '/') {
            $errors[] = 'Ongeldige bestandslocatie (contacteer webmaster).';
            return false;
        }
        
        $this->name = $name;
        $this->location = $location;
        $this->extension = $ext;
        $date = new \DateTime();

        $this->author = Leiding::getUser()->id;
        $this->upload_date = $date;
        $this->size = filesize($this->getPath());

        if (!file_exists($this->getPath())) {
            $errors[] = 'Bestand bestaat niet';
            return false;
        }
     
        // Opslaan in mysql en rollback als verplaatsen mislukt
        if (!$this->save()) {
            $errors[] = 'Opslaan in database mislukt';
            return false;
        }

        return true;
    }

    function save() {
        $name = self::getDb()->escape_string($this->name);
        $extension = self::getDb()->escape_string($this->extension);
        $location = self::getDb()->escape_string($this->location);
        $size = self::getDb()->escape_string($this->size);
        $upload_date = self::getDb()->escape_string($this->upload_date->format('Y-m-d H:i:s'));

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
                 file_location = '$location,
                 file_size = '$size',
                 file_upload_date = '$upload_date',
                 file_author = $author
                 where file_id = '$id' 
            ";
        } else {
            $query = "INSERT INTO 
                files (`file_name`, `file_extension`, `file_location`, `file_size`, `file_upload_date`, `file_author`)
                VALUES ('$name', '$extension', '$location', '$size', '$upload_date', $author)";
        }

        if (self::getDb()->query($query)) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        } else {
            echo $query;
        }

        return false;
    }
}