<?php
namespace Pirate\Model\Leiding;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Mail\Mail;

class Leiding extends Model {
    public $id;
    public $firstname;
    public $lastname;
    public $mail;
    public $phone;
    public $totem;
    public $tak;
    private $password;
    public $permissions = array();

    private $set_password_key;

    // als didCheckLogin == false, dan is currentToken en user nog niet op de juiste waarde
    private static $didCheckLogin = false;
    private static $currentToken = null;
    private static $user = null;

    public static $takken = array('kapoenen', 'wouters', 'jonggivers', 'givers', 'jin');

    private static $allPermissions;
    private static $adminMenu;

    function __construct($row = null) {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['id'];
        $this->firstname = $row['firstname'];
        $this->lastname = $row['lastname'];
        $this->mail = $row['mail'];
        $this->phone = $row['phone'];
        $this->totem = $row['totem'];
        $this->password = $row['password'];
        $this->tak = $row['tak'];

        $this->set_password_key = $row['set_password_key'];

        // Hier nog permissions opvullen!
        $this->permissions = explode('±', $row['permissions']);
    }

    static function getPossiblePermissions() {
        if (isset(self::$allPermissions)) {
            return self::$allPermissions;
        }

        $permissions = array();
        $query = "SELECT * from permissions";

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $permissions[$row['permissionCode']] = $row['permissionName'];
                }
            }
        }

        self::$allPermissions = $permissions;

        return $permissions;
    }

    static function getLeiding($permission = null) {
        $permission_code = '';
        if (!is_null($permission)) {
            $permission_code = "WHERE p2.permissionCode = '".self::getDb()->escape_string($permission)."'";
        } else {
            $permission_code = "WHERE p2.permissionId = p.permissionId";
            // TODO: Kan versneld worden als persmission = null -> dan dubbele joins weglaten
        }

        $leiding = array();
        $query = "SELECT l.*,
            group_concat(convert(p.permissionCode using utf8) separator '±') as permissions
        from leiding l
        left join _permissions_leiding _pl on _pl._leidingId = l.id
        left join permissions p on p.permissionId = _pl._permissionId

        left join _permissions_leiding _pl2 on _pl2._leidingId = l.id
        left join permissions p2 on p2.permissionId = _pl2._permissionId
        
        $permission_code
        group by l.id";

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $leiding[] = new Leiding($row);
                }
            }
        }

        return $leiding;
    }

    static function getLeidingById($id) {
        if (!is_numeric($id)) {
            return null;
        }

        $id = self::getDb()->escape_string($id);

        $query = "SELECT l.*,
            group_concat(convert(p.permissionCode using utf8) separator '±') as permissions
        from leiding l
        left join _permissions_leiding _pl on _pl._leidingId = l.id
        left join permissions p on p.permissionId = _pl._permissionId
        where l.id = '$id'
        group by l.id";

        if ($result = self::getDb()->query($query)){
            $row = $result->fetch_assoc();
            return new Leiding($row);
        }

        return null;
    }

    static function temporaryLoginWithPasswordKey($key) {
        $key = self::getDb()->escape_string($key);
        $query = "SELECT l.*,
            group_concat(convert(p.permissionCode using utf8) separator '±') as permissions
        from leiding l
        left join _permissions_leiding _pl on _pl._leidingId = l.id
        left join permissions p on p.permissionId = _pl._permissionId
        where l.set_password_key = '$key'
        group by l.id";

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows == 1){
                $row = $result->fetch_assoc();
                self::$user = new Leiding($row);
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

        $query = "UPDATE leiding 
            SET 
             password = '$password'
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

    static function getAdminMenu() {
        if (isset(self::$adminMenu)) {
            return self::$adminMenu;
        }

        include(__DIR__.'/../../_bindings/admin.php');
        $priorityButtons = array();
        $allButtons = array();
        $urls = array();
        foreach ($admin_pages as $permission => $buttons) {
            if ($permission == '' || self::hasPermission($permission)) {
                foreach ($buttons as $button) {
                    if (isset($urls[$button['url']])) {
                        continue;
                    } else {
                        $urls[$button['url']] = true;
                    }

                    if (isset($button['priority']) && $button['priority'] == true) {
                        $priorityButtons[] = $button;
                    } else {
                        $allButtons[] = $button;
                    }
                }
            }
        }
        self::$adminMenu = array_merge($priorityButtons, $allButtons);
        return self::$adminMenu;
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
    private static function generateKey() {
        $bytes = openssl_random_pseudo_bytes(16);
        return bin2hex($bytes);
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

                self::$currentToken = $row['token'];
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
        if ($permission != 'webmaster' && self::hasPermission('webmaster')) {
            return true;
        }
        return in_array($permission, self::getPermissions());
    }

    // TODO: private maken
    private function passwordEncrypt($password){
        // Voor de eerste keer password hash maken
        $salt = '$2y$10$' . strtr(base64_encode(\mcrypt_create_iv(16, MCRYPT_DEV_RANDOM)), '+', '.'). '$';
        return crypt($password, $salt);
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

        if (strlen($data['totem']) == 0) {
            $this->totem = null;
        }
        elseif (Validator::isValidTotem($data['totem'])) {
            $this->totem = ucfirst(strtolower($data['totem']));
            $data['totem'] = $this->totem;
        }  else {
            $errors[] = 'Ongeldige totem';
        }

        if (Validator::isValidMail($data['mail'])) {
            $this->mail = strtolower($data['mail']);
            $data['mail'] = $this->mail;
        }  else {
            $errors[] = 'Ongeldige e-mailadres';
        }

        if (strlen($data['phone']) > 0 || !$admin) {
            Validator::validatePhone($data['phone'], $this->phone, $errors);
        } else {
            $this->phone = null;
        }

        if (self::hasPermission('groepsleiding')) {
            if (isset($data['tak'])) {
                if (empty($data['tak'])) {
                    $this->tak = null;
                } else {
                    if (in_array($data['tak'], self::$takken)) {
                        $this->tak = $data['tak'];
                    } else {
                        $errors[] = 'Ongeldige tak';
                    }
                }
            }

            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $possible = self::getPossiblePermissions();
                $ok = true;
                foreach ($data['permissions'] as $code) {
                    if (!isset($possible[$code])) {
                        $errors[] = 'Ongeldige functie';
                        $ok = false;
                        break;
                    }
                }
                if ($ok) {
                    $this->permissions = $data['permissions'];
                }
            }

            if (!is_null($this->tak)) {
                $key = array_search('leiding', $this->permissions);
                if ($key === false) {
                    $data['permissions'][] = 'leiding';
                    $this->permissions = $data['permissions'];
                }
            } else {
                $key = array_search('leiding', $this->permissions);
                if ($key !== false) {
                    $errors[] = 'Selecteer een tak (of verwijder de functie leiding)';
                }
            }
        }


        return $errors;
    }

    function getSetPasswordUrl() {
        return "https://".$_SERVER['SERVER_NAME']."/leiding/set-password/".$this->set_password_key;
    }

    function hasPassword() {
        return !empty($this->password);
    }

    function sendPasswordEmail() {
        $mail = new Mail('Account scoutswebsite', 'leiding-new', array('leiding' => $this));
        $mail->addTo(
            $this->mail, 
            array(),
            $this->firstname.' '.$this->lastname
        );
        return $mail->send();
    }

    function save(){

        
        $firstname = self::getDb()->escape_string($this->firstname);
        $lastname = self::getDb()->escape_string($this->lastname);
        $mail = self::getDb()->escape_string($this->mail);

        if (!isset($this->tak)) {
            $tak = 'NULL';
        } else {
            $tak = "'".self::getDb()->escape_string($this->tak)."'";
        }

        if (!isset($this->phone)) {
            $phone = 'NULL';
        } else {
            $phone = "'".self::getDb()->escape_string($this->phone)."'";
        }

        if (!isset($this->totem)) {
            $totem = 'NULL';
        } else {
            $totem = "'".self::getDb()->escape_string($this->totem)."'";
        }

        self::getDb()->autocommit(false);

        // Permissions

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);

            $query = "UPDATE leiding 
                SET 
                 firstname = '$firstname',
                 lastname = '$lastname',
                 totem = $totem,
                 mail = '$mail',
                 phone = $phone,
                 tak = $tak
                 where id = '$id' 
            ";
        } else {
            $key = self::generateKey();
            $this->set_password_key = $key;

            $query = "INSERT INTO 
                leiding (`firstname`, `lastname`, `totem`, `mail`, `phone`, `tak`,`set_password_key`)
                VALUES ('$firstname', '$lastname', $totem, '$mail', $phone, $tak,  '$key')";
        }

        $result = self::getDb()->query($query);

        if ($result) {
            $new = false;
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
                $id = self::getDb()->escape_string($this->id);
                $new = true;
            }

            if (self::hasPermission('groepsleiding')) {
                $fail = false;

                $query = "DELETE 
                    FROM _permissions_leiding 
                    WHERE _leidingId = '$id'";

                if (!self::getDb()->query($query)) {
                    $fail = true;
                }

                // Toevoegen:
                if (count($this->permissions) > 0 && !$fail) {
                    $str = '';
                    foreach ($this->permissions as $code) {
                        if ($str != '') {
                            $str .= ",";
                        }
                        $str .= "'".self::getDb()->escape_string($code)."'";
                    }
                    $query = "INSERT INTO _permissions_leiding ( _leidingId, _permissionId )
                        select '$id', permissionId from permissions where permissionCode IN($str)";
                    if (!self::getDb()->query($query)) {
                        $fail = true;
                    }
                }

                if ($fail) {
                    self::getDb()->rollback();
                    self::getDb()->autocommit(true);
                    return false;
                }
            }
            if ($new) {
                $this->sendPasswordEmail();
            }

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
                leiding WHERE id = '$id' ";

        return self::getDb()->query($query);
    }
}