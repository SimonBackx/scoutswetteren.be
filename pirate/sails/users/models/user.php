<?php
namespace Pirate\Model\Users;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Mail\Mail;
use Pirate\Model\Settings\Setting;

class User extends Model {
    public $id;
    public $firstname;
    public $lastname;
    public $mail;
    public $phone;
    private $password;
    public $set_password_key;

    // als didCheckLogin == false, dan is currentToken en user nog niet op de juiste waarde
    private static $didCheckLogin = false;
    private static $currentToken = null;

    /// Current authenticated user
    private static $currentUser = null;

    private static $login_days = 60;

    function __construct($row = null) {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['user_id'];
        $this->firstname = $row['user_firstname'];
        $this->lastname = $row['user_lastname'];
        $this->mail = $row['user_mail'];
        $this->phone = $row['user_phone'];
        $this->password = $row['user_password'];
        $this->set_password_key = $row['user_set_password_key'];
    }

    static function temporaryLoginWithPasswordKey($key) {
        $key = self::getDb()->escape_string($key);
        $query = "SELECT l.*
        from users l
        where l.user_set_password_key = '$key'";

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows == 1){
                $row = $result->fetch_assoc();
                self::$currentUser = new User($row);
                self::$didCheckLogin = true;
                return true;
            }
        }
        return false;
    }

    // Returns true on success
    // Sets cookies if succeeded
    // isLoggedIn() etc kan gebruikt worden hierna
    static function login($mail, $password) {
        $mail = self::getDb()->escape_string($mail);
        $query = "SELECT l.*
        from users l
        where user_mail = '$mail'";

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows == 1){
                $row = $result->fetch_assoc();
                $hash = $row['user_password'];

                // hash_equals kijkt of beide argumenten gelijk zijn
                // Maar hash_equals is time safe, het duurt dus even lang om gelijke 
                // en ongelijke argumenten te vergelijken
                // Meer info: http://blog.ircmaxell.com/2014/11/its-all-about-time.html
                if (password_verify($password, $hash)) {

                    // Inloggen is gelukt, dat stellen we in zodat
                    // volgende calls dit object kunnen gebruiken
                    self::$currentUser = new User($row);
                    self::$didCheckLogin = true;

                    // Token aanmaken, dan zijn we ingelogd bij de volgende page load
                    return self::createToken();
                }
            }
        }
        return false;
    }

    // returns if password is correct
    function confirmPassword($password) {
        if (password_verify($password, $this->password)) {
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
        if (self::$currentUser->id != $this->id) {
            return false;
        }

        // Geldigheid controleren
        
        if (strlen($new) < 8) {
            return false;
        }
        
        // Alle tokens wissen en huidige token opnieuw aanmaken
        $client = intval($this->id);
        $query = "DELETE FROM tokens WHERE client = '$client'";

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

        $query = "UPDATE users 
            SET 
            user_password = '$password',
            user_set_password_key = NULL
             where `user_id` = '$id' 
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
        self::$currentUser = null;
        self::$didCheckLogin = true;
    }

    static function getAdminMenu() {
        if (isset(self::$adminMenu)) {
            return self::$adminMenu;
        }

        include(__DIR__.'/../../_bindings/admin.php');

        $allButtons = [];
        $urls = [];
        $ignoreButtons = [];

        foreach ($admin_pages as $permission => $buttons) {
            if ($permission == '' || self::hasPermission($permission)) {
                foreach ($buttons as $button) {
                    $priority = isset($button['priority']) ? $button['priority'] : 0;
                    $button['priority'] = $priority;

                    if (isset($urls[$button['url']])) {
                        $o = $urls[$button['url']];
                        if ($priority <= $o->priority) {
                            continue;
                        }
                        
                        // Remove old button
                        array_splice($allButtons[$o->priority], $o->index, 1);
                        
                        // Nu alle oude priority indexen updaten
                        foreach ($urls as $key => $value) {
                            if ($value->priority == $o->priority && $value->index >= $o->index) {
                                $value->index--;
                            }
                        }
                    } 

                    if (!isset($allButtons[$priority])) {
                        $allButtons[$priority] = [];
                    }

                    $urls[$button['url']] = (object) [
                        'priority' => $priority,
                        'index' => count($allButtons[$priority])
                    ];

                    $allButtons[$priority][] = $button;
                }
            }
        }
        ksort($allButtons);

        $sortedButtons = [];

        foreach ($allButtons as $priority => $buttons) {
            $sortedButtons = array_merge($buttons, $sortedButtons);
        }

           

        return $sortedButtons;
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
        $now = new \DateTime();
        $time = self::getDb()->escape_string($now->format('Y-m-d H:i:s'));
        $client = intval(self::$currentUser->id);
        $query = "INSERT INTO tokens (client, token, `time`) VALUES ($client, '$token', '$time')";

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

        $now = new \DateTime();
        $now->sub(new \DateInterval('P'.Self::$login_days.'D'));
        $time = self::getDb()->escape_string($now->format('Y-m-d H:i:s'));
        $query = "DELETE FROM tokens WHERE token = '$token' OR `time` < '$time'";

        if (self::getDb()->query($query)) {
            if ($removeCookies)
                self::removeCookies();
            return true;
        }
        return false;
    }

    private static function setCookies($id, $token){
        // We slaan ook de client id op, omdat we hierdoor een time safe operatie kunnen doen
        setcookie('client', $id, time()+5184000,'/', '', true, true); 
        setcookie('token', $token, time()+5184000,'/', '', true, true); 

        // Old: migration
        setcookie('ouder_client', '', time()-604800,'/');
        setcookie('ouder_token', '', time()-604800,'/');
    }

    private static function removeCookies(){
        setcookie('client', '', time()-604800,'/');
        setcookie('token', '', time()-604800,'/');
        
        // Old: migration
        setcookie('ouder_client', '', time()-604800,'/');
        setcookie('ouder_token', '', time()-604800,'/');
    }

    // 256 bit, 44 characters long met speciale characters!!
    private static function generateToken() {
        $bytes = openssl_random_pseudo_bytes(32);
        return base64_encode($bytes);
    }
    private static function generateKey() {
        $bytes = openssl_random_pseudo_bytes(16);
        return bin2hex($bytes);
    }

    /**
     * Controleert of de huidige bezoeker ingelogd is
     * @return User Geeft leiding object van bezoeker terug indien de gebruiker ingelogd is. NULL indien niet ingelogd
     */
    private static function checkLogin() {
        if (Self::$didCheckLogin) {
            return Self::$currentUser;
        }
        
        // Usertoken controleren in cookies
        // en als succesvol ingelogd: self::$currentUser setten!
        Self::$didCheckLogin = true;
        Self::$currentUser = null;

        if (!isset($_COOKIE['client'], $_COOKIE['token'])) {
            return null;
        }

        $client = self::getDb()->escape_string($_COOKIE['client']);
        $token = self::getDb()->escape_string($_COOKIE['token']);
        $query = "SELECT l.*, t.token, t.time
        from users l
        join tokens t on t.client = l.user_id
        where t.client = $client and t.token = '$token'
        group by l.user_id, t.token";

        // Momenteel niet helemaal time safe, maar performance primeert hier
        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();

                $date = new \DateTime($row['time']);
                $now = new \DateTime();
                $interval = $date->diff($now);

                // Als het vervallen is: verwijderen
                if ($interval->days > self::$login_days) {
                    self::deleteToken($token, true);
                    return null;
                }

                self::$currentToken = $row['token'];
                self::$currentUser = new User($row);

                if ($interval->days >= 1) {
                    // Token vernieuwen als hij al een dag oud is
                    self::createToken();
                }
                
            } else {
                // ?
                return null;
            }
        }

        return self::$currentUser;
    }

    static function isLoggedIn() {
        return !is_null(self::checkLogin());
    }

    static function getPermissions() {
        if (!self::isLoggedIn()) {
            return array();
        }
        return self::$currentUser->permissions;
    }

    static function getUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return self::$currentUser;
    }

    // TODO: private maken
    private function passwordEncrypt($password){
        // Voor de eerste keer password hash maken
        return password_hash($password, PASSWORD_BCRYPT);
    }

    // empty array on success
    // array of errors on failure
    function setProperties(&$data, $admin = false) {
        $errors = array();

        if (isset($data['firstname'], $data['lastname'])) {
            if (Validator::isValidFirstname($data['firstname'])) {
                $this->firstname = ucwords($data['firstname']);
                $data['firstname'] = $this->firstname;
            } else {
                $errors[] = 'Ongeldige voornaam';
            }

            if (Validator::isValidLastname($data['lastname'])) {
                $this->lastname = ucwords($data['lastname']);
                $data['lastname'] = $this->lastname;
            } else {
                $errors[] = 'Ongeldige achternaam';
            }
        }

        if (Validator::isValidMail($data['mail'])) {
            $this->mail = strtolower($data['mail']);
            $data['mail'] = $this->mail;
        }  else {
            $errors[] = 'Ongeldige e-mailadres';
        }

        // Als admin een user aanpast hoeft hij geen telefoon nummer op te geven
        // Anders moet hij wel altijd een telefoonnummer opgeven
        if (strlen($data['phone']) > 0 || !$admin) {
            Validator::validatePhone($data['phone'], $this->phone, $errors);
        } else {
            $this->phone = null;
        }

        return $errors;
    }

    function getSetPasswordUrl() {
        if ($this->hasPassword()) {
            return "https://".$_SERVER['SERVER_NAME']."/gebruikers/wachtwoord-vergeten/".$this->set_password_key;
        }
        return "https://".$_SERVER['SERVER_NAME']."/gebruikers/account-aanmaken/".$this->set_password_key;
    }

    function hasPassword() {
        return !empty($this->password);
    }

    /*function sendPasswordEmail() {
        $mail = new Mail('Account scoutswebsite', 'leiding-new', array('leiding' => $this));
        $mail->addTo(
            $this->mail, 
            array(),
            $this->firstname.' '.$this->lastname
        );
        return $mail->send();
    }*/

    function save(){
        $firstname = self::getDb()->escape_string($this->firstname);
        $lastname = self::getDb()->escape_string($this->lastname);
        $mail = self::getDb()->escape_string($this->mail);

        if (!isset($this->phone)) {
            $phone = 'NULL';
        } else {
            $phone = "'".self::getDb()->escape_string($this->phone)."'";
        }

        self::getDb()->autocommit(false);

        // Permissions
        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);

            $query = "UPDATE users 
                SET 
                user_firstname = '$firstname',
                user_lastname = '$lastname',
                user_mail = '$mail',
                user_phone = $phone
                 where `user_id` = '$id' 
            ";
        } else {
            $key = self::generateKey();
            $this->set_password_key = $key;

            $query = "INSERT INTO 
                users (`user_firstname`, `user_lastname`, `user_mail`, `user_phone`, `user_set_password_key`)
                VALUES ('$firstname', '$lastname', '$mail', $phone,  '$key')";
        }

        $result = self::getDb()->query($query);

        if ($result) {
            $new = false;
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
                $id = self::getDb()->escape_string($this->id);
                $new = true;
            }

            /*if ($new) {
                $this->sendPasswordEmail();
            }*/

            self::getDb()->commit();
        } else {
            self::getDb()->rollback();
        }

        self::getDb()->autocommit(true);

        return $result;
    }

    function delete() {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM 
                users WHERE `user_id` = '$id' ";

        return self::getDb()->query($query);
    }
}