<?php
namespace Pirate\Model\Leiding;
use Pirate\Model\Model;

class Leiding extends Model {
    public $id;
    public $firstname;
    public $lastname;
    public $mail;
    public $phone;
    public $totem;
    public $tak;
    private $permissions;

    // als didCheckLogin == false, dan is currentToken en user nog niet op de juiste waarde
    private static $didCheckLogin = false;
    private static $currentToken = null;
    private static $user = null;

    function __construct($row) {
        $this->id = $row['id'];
        $this->firstname = $row['firstname'];
        $this->lastname = $row['lastname'];
        $this->mail = $row['mail'];
        $this->phone = $row['phone'];
        $this->totem = $row['totem'];
        $this->tak = $row['tak'];

        // Hier nog permissions opvullen!
        $this->permissions = explode('±', $row['permissions']);
    }

    // Returns true on success
    // Sets cookies if succeeded
    // isLoggedIn() etc kan gebruikt worden hierna
    static function login($mail, $password) {
        $mail = self::getDb()->escape_string($mail);
        $query = "SELECT l.*,
            group_concat(convert(p.permissionCode using utf8) separator '±') as permissions
        from leiding l
        left join _permissions_leiding _pl on _pl._leidingId = l.id
        left join permissions p on p.permissionId = _pl._permissionId
        where mail = '$mail'
        group by l.id";

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows == 1){
                $row = $result->fetch_assoc();
                $hash = $row['password'];

                // hash_equals kijkt of beide argumenten gelijk zijn
                // Maar hash_equals is time safe, het duurt dus even lang om gelijke 
                // en ongelijke argumenten te vergelijken
                // Meer info: http://blog.ircmaxell.com/2014/11/its-all-about-time.html
                if (hash_equals(crypt($password, $hash), $hash)) {

                    // Inloggen is gelukt, dat stellen we in zodat
                    // volgende calls dit object kunnen gebruiken
                    self::$user = new Leiding($row);
                    self::$didCheckLogin = true;

                    // Token aanmaken, dan zijn we ingelogd
                    return self::createToken();
                }
            }
        }
        return false;
    }

    static function logout() {
        self::deleteToken(self::$currentToken);
        self::$currentToken = null;
        self::$user = null;
        self::$didCheckLogin = true;
    }

    static function getAdminMenu() {
        include(__DIR__.'/../../_bindings/admin.php');
        $priorityButtons = array();
        $allButtons = array();
        foreach ($admin_pages as $permission => $buttons) {
            if ($permission == '' || self::hasPermission($permission)) {
                foreach ($buttons as $button) {
                    if (isset($button['priority']) && $button['priority'] == true) {
                        $priorityButtons[] = $button;
                    } else {
                        $allButtons[] = $button;
                    }
                }
            }
        }
        return array_merge($priorityButtons, $allButtons);
    }

    // Maakt nieuwe token voor huidige ingelogde gebruiker en slaat deze op in de cookies
    // Indien al token op huidige sessie, dan verwijdert hij die eerst
    private static function createToken() {
        if (!self::isLoggedIn()) {
            return false;
        }

        // Het is mogelijk om ingelogd te zijn zonder token te hebben
        // namelijk heel even tijdens het inloggen zelf
        if (!is_null(self::$currentToken)){
            self::deleteToken(self::$currentToken, false);
        }

        $token = self::getDb()->escape_string(self::generateToken());
        $client = intval(self::$user->id);
        $query = "INSERT INTO tokens (client, token) VALUES ($client, '$token')";

        if (self::getDb()->query($query)) {
            self::setCookies($client, $token);

            // Token bij de huidige sessie laten horen
            self::$currentToken = $token;
            return true;
        }

        return false;
    }

    // Verwijdert de opgegeven token
    private static function deleteToken($token, $removeCookies = true) {
        // Token die bij de huidige sessie hoort verwijderen
        $token = self::getDb()->escape_string($token);

        $query = "DELETE FROM tokens WHERE token = '$token'";

        if (self::getDb()->query($query)) {
            if ($removeCookies)
                self::removeCookies();
            return true;
        }
        return false;
    }

    private static function setCookies($id, $token){
        // We slaan ook de client id op, omdat we hierdoor een time safe operatie kunnen doen
        setcookie('client', $id, time()+604800,'/', '', true, true); 
        setcookie('token', $token, time()+604800,'/', '', true, true); 
    }

    private static function removeCookies(){
        setcookie('client', '', time()-604800,'/');
        setcookie('token', '', time()-604800,'/');
    }

    // 256 bit, 44 characters long met speciale characters!!
    private static function generateToken() {
        $bytes = openssl_random_pseudo_bytes(32);
        return base64_encode($bytes);
    }

    /**
     * Controleert of de huidige bezoeker ingelogd is
     * @return Leiding Geeft leiding object van bezoeker terug indien de gebruiker ingelogd is. NULL indien niet ingelogd
     */
    private static function checkLogin() {
        if (self::$didCheckLogin) {
            return self::$user;
        }
        // Usertoken controleren in cookies
        // en als succesvol ingelogd: self::$user setten!
        self::$didCheckLogin = true;
        self::$user = null;

        if (!isset($_COOKIE['client'], $_COOKIE['token'])) {
            return null;
        }

        $client = intval($_COOKIE['client']);
        $token = self::getDb()->escape_string($_COOKIE['token']);
        $query = "SELECT l.*, t.token, t.time,
            group_concat(convert(p.permissionCode using utf8) separator '±') as permissions
        from leiding l
        left join _permissions_leiding _pl on _pl._leidingId = l.id
        left join permissions p on p.permissionId = _pl._permissionId
        join tokens t on t.client = l.id
        where t.client = $client and token = '$token'
        group by l.id, t.token";

        // Momenteel niet helemaal time safe, maar performance primeert hier
        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();

                $date = new \DateTime($row['time']);
                $now = new \DateTime();
                $interval = $now->diff($date);

                // Als het vervallen is: verwijderen
                if ($interval->days > 60) {
                    self::deleteToken($token, true);
                    return null;
                }

                self::$currentToken = $token;
                self::$user = new Leiding($row);

                if ($interval->days >= 1 || $interval->h >= 1) {
                    // Token vernieuwen als hij al een uur oud is
                    // Zodat het moeilijk wordt voor mensen om de token 
                    // fysiek op de computer te stelen als de gebruiker
                    // na een uur opnieuw op de website komt.
                    self::createToken();
                }
                
            }
        }
        return self::$user;
    }

    static function isLoggedIn() {
        return !is_null(self::checkLogin());
    }

    static function getPermissions() {
        if (!self::isLoggedIn()) {
            return array();
        }
        return self::$user->permissions;
    }

    static function getUser() {
        return self::$user;
    }

    // Case sensitive
    static function hasPermission($permission) {
        return in_array($permission, self::getPermissions());
    }

    // TODO: private maken
    static function passwordEncrypt($password){
        // Voor de eerste keer password hash maken
        $salt = '$2y$10$' . strtr(base64_encode(\mcrypt_create_iv(16, MCRYPT_DEV_RANDOM)), '+', '.'). '$';
        return crypt($password, $salt);
    }


    
}