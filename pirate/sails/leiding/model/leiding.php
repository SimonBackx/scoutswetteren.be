<?php
namespace Pirate\Model\Leiding;
use Pirate\Model\Model;

class Leiding extends Model {
    public $id;
    public $firstname;
    public $lastname;
    public $mail;
    private $password;
    public $phone;
    public $totem;
    public $tak;
    private $permissions;

    function __construct($row) {
        $this->id = $row['id'];
        $this->firstname = $row['firstname'];
        $this->lastname = $row['lastname'];
        $this->mail = $row['mail'];
        $this->password = $row['password'];
        $this->phone = $row['phone'];
        $this->totem = $row['totem'];
        $this->tak = $row['tak'];
    }

    static function isLoggedIn() {
        return false;
    }

    static function getPermissions() {

    }

    // Returns true on success
    static function login($mail, $password) {

    }

    private function passwordEncrypt($password){
        // Voor de eerste keer password hash maken
        $salt = '$2y$10$' . strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.'). '$';
        return crypt($password, $salt);
    }


    static function getArticle($date, $slug) {
        $date = self::getDb()->escape_string($date);
        $slug = self::getDb()->escape_string($slug);

        $query = "SELECT * from articles where `published` = '$date' and `slug` = '$slug'";
        if ($result = self::getDb()->query($query)){
            if ($result->num_rows == 1){
                if ($row = $result->fetch_assoc()) {
                    return new Article($row);
                }
            }
        }

        return null;
    }

    // Maximaal 5 artikels, pagina grootte = 4 
    // Detectie of volgende pagina bestaat is dus gewoon nagaan of er 5 zijn meegegeven
    // Als pagina = 0 => laatste 150 artikels tonen (= archief)
    static function getArticles($page = 1) {
        $page = intval($page);

        $limit = 'LIMIT '.(($page-1)*4).', 5';
        if ($page < 1) {
            $limit = 'limit 150';
        }

        $articles = array();
        $query = 'SELECT * from articles order by published desc, edited desc '.$limit;
        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $articles[] = new Article($row);
                }
            }
        }
        return $articles;
    }

    // Maximaal 5 artikels, pagina grootte = 4 
    // Detectie of volgende pagina bestaat is dus gewoon nagaan of er 5 zijn meegegeven
    static function searchArticles($needle) {
        $needle = self::getDb()->escape_string($needle);

        $articles = array();
        $query = 'SELECT * from articles  WHERE MATCH (title,`text`) AGAINST ("'.$needle.'" IN NATURAL LANGUAGE MODE);';
        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $articles[] = new Article($row);
                }
            }
        }
        return $articles;
    }
}