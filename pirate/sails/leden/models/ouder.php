<?php
namespace Pirate\Model\Leden;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Model\Leden\Gezin;

class Ouder extends Model {
    public $id;
    public $gezin;
    public $titel;
    public $voornaam;
    public $achternaam;
    public $adres;
    public $postcode;
    public $gemeente;
    public $telefoon;
    public $gsm;
    public $email;
    private $password;
    private $set_password_key;

    static $titels = array('Mama', 'Papa', 'Voogd', 'Stiefmoeder', 'Stiefvader');

    // als didCheckLogin == false, dan is currentToken en user nog niet op de juiste waarde
    private static $didCheckLogin = false;
    private static $currentToken = null;
    private static $user = null;

    function __construct($row = array()) {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['id'];
        $this->gezin = $row['gezin'];
        $this->titel = $row['titel'];
        $this->voornaam = $row['voornaam'];
        $this->achternaam = $row['achternaam'];
        $this->adres = $row['adres'];
        $this->gemeente = $row['gemeente'];
        $this->postcode = $row['postcode'];

        $this->email = $row['email'];
        $this->password = $row['password'];
        $this->gsm = $row['gsm'];
        $this->telefoon = $row['telefoon'];

        $this->set_password_key = $row['set_password_key'];
    }

    // empty array on success
    // array of errors on failure
    function setProperties(&$data) {
        $errors = array();

        if (!in_array($data['titel'], self::$titels)) {
            $errors[] = 'Ongeldige titel';
        } else {
            $this->titel = $data['titel'];
        }

        if (Validator::isValidFirstname($data['voornaam'])) {
            $this->voornaam = ucwords($data['voornaam']);
            $data['voornaam'] = $this->voornaam;
        } else {
            $errors[] = 'Ongeldige voornaam';
        }

        if (Validator::isValidLastname($data['achternaam'])) {
            $this->achternaam = ucwords($data['achternaam']);
            $data['achternaam'] = $this->achternaam;
        } else {
            $errors[] = 'Ongeldige achternaam';
        }

        Validator::validatePhone($data['gsm'], $this->gsm, $errors);

        if (Validator::isValidMail($data['email'])) {
            $this->email = strtolower($data['email']);
            $data['email'] = $this->email;
        } else {
            $errors[] = 'Ongeldig e-mailadres';
        }


        if (Validator::isValidAddress($data['adres'])) {
            $this->adres = ucwords($data['adres']);
            $data['adres'] = $this->adres;
        } else {
            $errors[] = 'Ongeldig adres';
        }

        Validator::validateGemeente($data['gemeente'], $data['postcode'], $this->gemeente, $this->postcode, $errors);

        if (!empty($data['telefoon'])) {
            Validator::validateNetPhone($data['telefoon'], $this->telefoon, $errors);
        } else {
            $this->telefoon = null;
        }

        return $errors;
    }

    function setGezin(Gezin $gezin) {
        $this->gezin = $gezin->id;
    }

    function getSetPasswordUrl() {
        return "https://".$_SERVER['SERVER_NAME']."/ouders/account-aanmaken/".$this->set_password_key;
    }

    function hasPassword() {
        return !empty($this->password);
    }

    function save() {
        if (is_null($this->telefoon)) {
            $telefoon = "NULL";
        } else {
            $telefoon = "'".self::getDb()->escape_string($this->telefoon)."'";
        }

        $voornaam = self::getDb()->escape_string($this->voornaam);
        $achternaam = self::getDb()->escape_string($this->achternaam);
        $adres = self::getDb()->escape_string($this->adres);
        $gemeente = self::getDb()->escape_string($this->gemeente);
        $postcode = self::getDb()->escape_string($this->postcode);
        $gsm = self::getDb()->escape_string($this->gsm);
        $email = self::getDb()->escape_string($this->email);
        $titel = self::getDb()->escape_string($this->titel);

        if (empty($this->id)) {
            if (empty($this->gezin)) {
                return false;
            }
            $gezin = self::getDb()->escape_string($this->gezin);
            $key = self::generateKey();
            $this->set_password_key = $key;

            $query = "INSERT INTO 
                ouders (`gezin`, `titel`, `voornaam`, `achternaam`, `adres`, `gemeente`,`postcode`, `gsm`, `email`, `telefoon`, `set_password_key`)
                VALUES ('$gezin', '$titel', '$voornaam', '$achternaam', '$adres', '$gemeente', '$postcode', '$gsm', '$email', $telefoon, '$key')";
        } else {
            $id = self::getDb()->escape_string($this->id);
            $query = "UPDATE ouders 
                SET 
                 `titel` = '$titel',
                 `voornaam` = '$voornaam',
                 `achternaam` = '$achternaam',
                 `adres` = '$adres',
                 `gemeente` = '$gemeente',
                 `postcode` = '$postcode',
                 `email` = '$email',
                 `telefoon` = $telefoon,
                 `gsm` = $gsm
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

    private function passwordEncrypt($password){
        // Voor de eerste keer password hash maken
        $salt = '$2y$10$' . strtr(base64_encode(\mcrypt_create_iv(16, MCRYPT_DEV_RANDOM)), '+', '.'). '$';
        return crypt($password, $salt);
    }

    private static function generateToken() {
        $bytes = openssl_random_pseudo_bytes(32);
        return base64_encode($bytes);
    }

    private static function generateKey() {
        $bytes = openssl_random_pseudo_bytes(16);
        return bin2hex($bytes);
    }

    static function temporaryLoginWithPasswordKey($key) {
        $key = self::getDb()->escape_string($key);
        $query = "SELECT *
        from ouders
        where set_password_key = '$key'";

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows == 1){
                $row = $result->fetch_assoc();
                self::$user = new Ouder($row);
                self::$didCheckLogin = true;
                return true;
            }
        }
        return false;
    }

    static function login($mail, $password) {
        $mail = self::getDb()->escape_string($mail);
        $query = "SELECT *
        from ouders
        where email = '$mail'";

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
                    self::$user = new Ouder($row);
                    self::$didCheckLogin = true;

                    // Token aanmaken, dan zijn we ingelogd

                    // Indien gelukt:  redirecten naar steekkaart-controle indien nodig!
                    return self::createToken();
                }
            }
        }
        return false;
    }

    // returns if password is correct
    function confirmPassword($password) {
        if (hash_equals(crypt($password, $this->password), $this->password)) {
            return true;
        }
        return false;
    }

    //
    function changePassword($new) {
        // check if logged in as same account
        if (!self::isLoggedIn()) {
            return false;
        }

        if (self::$user->id != $this->id) {
            return false;
        }

        // Geldigheid controleren
        
        if (strlen($new) < 10) {
            return false;
        }
        
        // Alle tokens wissen en huidige token opnieuw aanmaken
        $client = intval($this->id);
        $query = "DELETE FROM ouder_tokens WHERE client = '$client'";

        if (!self::getDb()->query($query)) {
            return false;
        }
        self::$currentToken = null;
        self::createToken();

        return $this->setPassword($new);
    }

    private function setPassword($new) {
        $id = self::getDb()->escape_string($this->id);
        $encrypted = $this->passwordEncrypt($new);
        $password = self::getDb()->escape_string($encrypted);

        $query = "UPDATE ouders 
            SET 
             password = '$password',
             set_password_key = NULL
             where id = '$id' 
        ";

        if (self::getDb()->query($query)) {
            $this->password = $encrypted;
            return true;
        }
        return false;
    }

    static function logout() {
        self::deleteToken(self::$currentToken);
        self::$currentToken = null;
        self::$user = null;
        self::$didCheckLogin = true;
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
        $query = "INSERT INTO ouder_tokens (client, token) VALUES ($client, '$token')";

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

        $query = "DELETE FROM ouder_tokens WHERE token = '$token'";

        if (self::getDb()->query($query)) {
            if ($removeCookies)
                self::removeCookies();
            return true;
        }
        return false;
    }

    private static function setCookies($id, $token){
        // We slaan ook de client id op, omdat we hierdoor een time safe operatie kunnen doen
        setcookie('ouder_client', $id, time()+604800,'/', '', true, true); 
        setcookie('ouder_token', $token, time()+604800,'/', '', true, true); 
    }

    private static function removeCookies(){
        setcookie('ouder_client', '', time()-604800,'/');
        setcookie('ouder_token', '', time()-604800,'/');
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

        if (!isset($_COOKIE['ouder_client'], $_COOKIE['ouder_token'])) {
            return null;
        }

        $client = intval($_COOKIE['ouder_client']);
        $token = self::getDb()->escape_string($_COOKIE['ouder_token']);
        $query = "SELECT o.*, t.token, t.time
        from ouders o
        join ouder_tokens t on t.client = o.id
        where t.client = $client and t.token = '$token'";

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

                self::$currentToken = $row['token'];
                self::$user = new Ouder($row);

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

    static function getUser() {
        return self::$user;
    }
    
}