<?php
namespace Pirate\Model\Leden;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Model\Leden\Gezin;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Inschrijving;

class Ouder extends Model {
    public $id;
    public $gezin; // object
    public $titel;
    public $voornaam;
    public $achternaam;
    public $adres; // object
    public $gsm;
    public $email;
    private $password;
    private $set_password_key;

    static $titels = array('Mama', 'Papa', 'Voogd', 'Stiefmoeder', 'Stiefvader');
    
    static function titelToGroepsadmin($functie) {
        $functie = strtolower($functie);
        $map = array(
            'mama' => 'moeder', 
            'papa' => 'vader', 
            'voogd' => 'voogd', 
            'stiefmoeder' => 'moeder', 
            'stiefvader' => 'vader'
        );

        if (!isset($map[$functie])) {
            return 'moeder';
        }

        return $map[$functie];
    }

    function getGroepsadminRol() {
        return static::titelToGroepsadmin($this->titel);
    }

    // als didCheckLogin == false, dan is currentToken en user nog niet op de juiste waarde
    private static $didCheckLogin = false;
    private static $currentToken = null;
    private static $user = null;
    private static $login_days = 60;

    public static $filters = array(
        'all' => array(
            'name' => 'Alle ouders',
            'where' => ''
        ),
        'inschrijving_onvolledig' => array(
            'name' => 'Inschrijving niet voltooid of niet betaald',
            'where' => 'i.afrekening is null or s.laatst_nagekeken is null or i.afrekening_oke = 0'
        ),
        'verminderd_lidgeld' => array(
            'name' => 'Leden met verminderd lidgeld',
            'where' => 'g.scouting_op_maat = 1'
        ),
        'in_orde' => array(
            'name' => 'Inschrijving volledig in orde',
            'where' => 's.laatst_nagekeken is not null and i.afrekening_oke = 1 and i.afrekening is not null'
        )
    );

    private $temporaryMagicToken = null;


    function __construct($row = array()) {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['id'];

        if (!empty($row['gezin_id'])) {
            $this->gezin = new Gezin($row);
        } else {
            $this->gezin = null;
        }

        $this->titel = $row['titel'];
        $this->voornaam = $row['voornaam'];
        $this->achternaam = $row['achternaam'];

        if (!empty($row['adres_id'])) {
            $this->adres = new Adres($row);
        } else {
            $this->adres = null;
        }

        $this->email = $row['email'];
        $this->password = $row['password'];
        $this->gsm = $row['gsm'];

        $this->set_password_key = $row['set_password_key'];
    }

    function getAdres() {
        if (!isset($this->adres)) {
            return '';
        }
        return $this->adres->toString();
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
            $this->voornaam = ucwords(mb_strtolower(trim($data['voornaam'])));
            $data['voornaam'] = $this->voornaam;
        } else {
            $errors[] = 'Ongeldige voornaam';
        }

        if (Validator::isValidLastname($data['achternaam'])) {
            $this->achternaam = ucwords(mb_strtolower(trim($data['achternaam'])));
            $data['achternaam'] = $this->achternaam;
        } else {
            $errors[] = 'Ongeldige achternaam';
        }

        Validator::validatePhone($data['gsm'], $this->gsm, $errors);

        if (Validator::isValidMail($data['email'])) {
            $email = strtolower(trim($data['email']));
            $data['email'] = $email;

            $escaped = self::getDb()->escape_string($email);

            if (isset($this->id)) {
                $id = self::getDb()->escape_string($this->id);
                 // Zoek andere ouders met dit e-mailadres
                $query = "SELECT o.*
                from ouders o
                where email = '$escaped' and id != '$id'";
            } else {
                 // Zoek andere ouders met dit e-mailadres
                $query = "SELECT o.*
                from ouders o
                where email = '$escaped'";
            }

            if ($result = self::getDb()->query($query)) {
                if ($result->num_rows == 0){
                    $this->email = $email;
                } else {
                    $errors[] = 'Dit e-mailadres is al in gebruik door een andere ouder. Kijk na of je niet hetzelfde e-mailadres gebruikt voor beide ouders, dat is niet toegestaan.';
                }
            } else {
                $errors[] = 'Er ging iets mis';
            }
        } else {
            $errors[] = 'Ongeldig e-mailadres';
        }

        $model = Adres::find($data['adres'], $data['gemeente'], $data['postcode'], $data['telefoon'], $errors);
        if (isset($model)) {
            $this->adres = $model; 
        }

        return $errors;
    }

    static function oudersToFieldArray($original) {
        $arr = array();
        foreach ($original as $key => $value) {
            $arr[] = $value->getProperties();
        }
        return $arr;
    }

    function getProperties() {
        return array(
            'titel' => $this->titel,
            'voornaam' => $this->voornaam,
            'achternaam' => $this->achternaam,
            'adres' => isset($this->adres) ? $this->adres->getAdres() : "",
            'gemeente' => isset($this->adres) ? $this->adres->gemeente : "",
            'postcode' => isset($this->adres) ? $this->adres->postcode : "",
            'telefoon' => isset($this->adres->telefoon) ? $this->adres->telefoon : "",
            'gsm' => $this->gsm,
            'email' => $this->email
        );
    }

    function setGezin(Gezin $gezin) {
        $this->gezin = $gezin;
    }

    function getSetPasswordUrl() {
        if ($this->hasPassword()) {
            return "https://".$_SERVER['SERVER_NAME']."/ouders/wachtwoord-vergeten/".$this->set_password_key;
        }
        return "https://".$_SERVER['SERVER_NAME']."/ouders/account-aanmaken/".$this->set_password_key;
    }

    function hasPassword() {
        return !empty($this->password);
    }

    function generatePasswordRecoveryKey() {
        // Generate and put in $this->set_password_key
        $old = $this->set_password_key;
        $key = self::generateKey();
        $this->set_password_key = $key;

        // Opslaan
        if ($this->save()) {
            return true;
        } else {
            $this->set_password_key = $old;
            return false;
        }
    }

    function save() {
        if (!isset($this->adres)) {
            return false;
        }

        $voornaam = self::getDb()->escape_string($this->voornaam);
        $achternaam = self::getDb()->escape_string($this->achternaam);
        
        $adres = self::getDb()->escape_string($this->adres->id);
        $gsm = self::getDb()->escape_string($this->gsm);
        $email = self::getDb()->escape_string($this->email);
        $titel = self::getDb()->escape_string($this->titel);
        
        if (isset($this->set_password_key)) {
            $key = "'".self::getDb()->escape_string($this->set_password_key)."'";
        } else {
            $key = "NULL";
        }

        if (empty($this->id)) {
            if (empty($this->gezin)) {
                return false;
            }
            $gezin = self::getDb()->escape_string($this->gezin->id);
            $key = self::generateKey();
            $this->set_password_key = $key;

            $query = "INSERT INTO 
                ouders (`gezin`, `titel`, `voornaam`, `achternaam`, `adres`, `gsm`, `email`, `set_password_key`)
                VALUES ('$gezin', '$titel', '$voornaam', '$achternaam', '$adres', '$gsm', '$email', '$key')";
        } else {
            $id = self::getDb()->escape_string($this->id);
            $query = "UPDATE ouders 
                SET 
                 `titel` = '$titel',
                 `voornaam` = '$voornaam',
                 `achternaam` = '$achternaam',
                 `adres` = '$adres',
                 `email` = '$email',
                 `gsm` = '$gsm',
                 `set_password_key` = $key
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

    function delete() {
        if (!isset($this->id)) {
            return false;
        }

        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM 
                ouders WHERE id = '$id' ";

        if (self::getDb()->query($query)) {
            return true;
        }
        
        return false;
    }

    private function passwordEncrypt($password){
        // Voor de eerste keer password hash maken
        return password_hash($password, PASSWORD_BCRYPT);
    }

    private static function generateToken() {
        $bytes = openssl_random_pseudo_bytes(32);
        return base64_encode($bytes);
    }

    private static function generateKey() {
        $bytes = openssl_random_pseudo_bytes(16);
        return bin2hex($bytes);
    }

    private static function generateLongKey() {
        $bytes = openssl_random_pseudo_bytes(32);
        return bin2hex($bytes);
    }

    function getMagicToken() {
        if (isset($this->temporaryMagicToken)) {
            return $this->temporaryMagicToken;
        }

        $token = self::getDb()->escape_string(self::generateLongKey());
        $client = intval($this->id);
        $now = new \DateTime();
        $time = self::getDb()->escape_string($now->format('Y-m-d H:i:s'));
        $query = "INSERT INTO ouder_magic_tokens (client, token, `expires`) VALUES ($client, '$token', '$time')";

        if (self::getDb()->query($query)) {
            $this->temporaryMagicToken = $token;
            return $token;
        }
        return null;
    }

    function getMagicTokenUrl() {
        $mail = $this->email;
        $token = $this->getMagicToken();
        return "https://".$_SERVER['SERVER_NAME']."/ouders/login/$mail/$token";
    }

    // Multiple ouders
    static function createMagicTokensFor($ouders) {
        $query = '';
        $query = "";

        // Bijhouden welke we hebben gegenereerd
        // zodat we weten wanneer het fout loopt
        $ouders_copy = array();
        $now = new \DateTime();
        $time = self::getDb()->escape_string($now->format('Y-m-d H:i:s'));
        
        foreach ($ouders as $ouder) {
            if (!isset($ouder->temporaryMagicToken)) {
                $token = self::getDb()->escape_string(self::generateLongKey());
                $client = intval($ouder->id);
                
                if ($query != '') {
                    $query .= ', ';
                }
                $query .= "($client, '$token', '$time')";

                $ouder->temporaryMagicToken = $token;
                $ouders_copy[] = $ouder;
            }
        }

        if (count($ouders_copy) == 0) {
            return true;
        }
        
        $query = 'INSERT INTO ouder_magic_tokens (client, token, `expires`) VALUES '.$query;

        if (self::getDb()->query($query)) {
            return true;
        } else {
            foreach ($ouders_copy as $ouder) {
                $ouder->temporaryMagicToken = null;
            }
        }
        return false;
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
        $query = "SELECT o.*, g.*, a.*
        from ouders o
        left join gezinnen g on g.gezin_id = o.id
        left join adressen a on a.adres_id = o.adres
        where email = '$mail' and password is not null";

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows == 1){
                $row = $result->fetch_assoc();
                $hash = $row['password'];

                if (!isset($hash)) {
                    return false;
                }

                // hash_equals kijkt of beide argumenten gelijk zijn
                // Maar hash_equals is time safe, het duurt dus even lang om gelijke 
                // en ongelijke argumenten te vergelijken
                // Meer info: http://blog.ircmaxell.com/2014/11/its-all-about-time.html
                if (password_verify($password, $hash)) {

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

    static function loginWithMagicToken($mail, $magicToken) {
        $mail = self::getDb()->escape_string($mail);
        $magicToken = self::getDb()->escape_string($magicToken);

        $query = "SELECT o.*, a.*, g.*, t.expires
        from ouders o
        left join gezinnen g on g.gezin_id = o.gezin
        left join adressen a on a.adres_id = o.adres
        join ouder_magic_tokens t on t.client = o.id
        where o.email = '$mail' and o.password is not null and t.token = '$magicToken'";

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows == 1){
                $row = $result->fetch_assoc();
                
                $expires = $row['expires'];
                // todo: validate magic token
                
                self::$user = new Ouder($row);
                self::$didCheckLogin = true;

                return self::createToken();
            }
        }
        return false;
    }

    static function getOuderForEmail($email) {
        $email = self::getDb()->escape_string($email);

        $query = '
            SELECT o.*, a.*, g.* from ouders o
                left join gezinnen g on g.gezin_id = o.gezin
                left join adressen a on a.adres_id = o.adres
            where o.email = "'.$email.'"';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows == 1){
                $row = $result->fetch_assoc();
                return new Ouder($row);
            }
        }
        return null;
    }

    static function getOuderForId($id) {
        $id = self::getDb()->escape_string($id);

        $query = '
            SELECT o.*, a.*, g.* from ouders o
                left join gezinnen g on g.gezin_id = o.gezin
                left join adressen a on a.adres_id = o.adres
            where o.id = "'.$id.'"';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows == 1){
                $row = $result->fetch_assoc();
                return new Ouder($row);
            }
        }
        return null;
    }

    static function getOudersForGezin($gezin_id) {
        $gezin = self::getDb()->escape_string($gezin_id);

        $ouders = array();
        $query = '
            SELECT o.*, a.*, g.* from ouders o
                left join gezinnen g on g.gezin_id = o.gezin
                left join adressen a on a.adres_id = o.adres
            where o.gezin = "'.$gezin.'"';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $ouders[] = new Ouder($row);
                }
            }
        }
        return $ouders;
    }

    static function getOuders($filter = null, $tak = null, $return_leden = false, $scoutsjaar = null) {
        $where = '';

        if (!is_null($filter)) {
            if (is_array($filter)) {
                // Filter op veldnamen
                $fields = array('gsm', 'email');
                foreach ($fields as $field) {
                    if (isset($filter[$field])) {
                        if (!is_array($filter[$field])) {
                            $filter[$field] = array($filter[$field]);
                        }
                        foreach ($filter[$field] as $value) {
                            if (strlen($where) > 0)
                                $where .= ' OR ';
                            $where .= 'o.'.$field.' = "'.self::getDb()->escape_string($value).'"';
                    
                        }
                    }
                }
            }
            elseif (isset(self::$filters[$filter])) {
                // Filter op premade selectors
                
                $filter = self::$filters[$filter];
                $where = $filter['where'];
            }
        }
        if (!is_null($tak)) {
            if (strlen($where) > 0)
                $where .= ' AND ';
            $where .= 'i.tak = "'.self::getDb()->escape_string($tak).'"';
        }

        if (strlen($where) > 0)
            $where = 'WHERE '.$where;

        if (!isset($scoutsjaar)) {
            $scoutsjaar = intval(Inschrijving::getScoutsjaar());
        } else {
            $scoutsjaar = self::getDb()->escape_string($scoutsjaar);
        }

        $ouders = array();

        if ($return_leden) {
            $query = '
            SELECT l.*, i.*, s.*, g.* from leden l
                left join gezinnen g on g.gezin_id = l.gezin
                join ouders o on l.gezin = o.gezin
                join inschrijvingen i on i.lid = l.id and i.scoutsjaar = '.$scoutsjaar.' and i.tak != ""
                left join steekkaarten s on s.lid = l.id
            '.$where.'
            GROUP BY l.id, g.gezin_id, i.inschrijving_id, s.steekkaart_id
            order by year(l.geboortedatum) desc, l.voornaam;';
        } else {
            $query = '
            SELECT o.*, a.*, g.* from ouders o
                left join gezinnen g on g.gezin_id = o.gezin
                left join adressen a on a.adres_id = o.adres
                join leden l on l.gezin = o.gezin
                join inschrijvingen i on i.lid = l.id and i.scoutsjaar = '.$scoutsjaar.'
                left join steekkaarten s on s.lid = l.id
            '.$where.'
            GROUP BY o.id, g.gezin_id';
        }
        

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                if (!$return_leden) {
                    while ($row = $result->fetch_assoc()) {
                        $ouders[] = new Ouder($row);
                    }
                } else {
                    while ($row = $result->fetch_assoc()) {
                        $ouders[] = new Lid($row);
                    }
                }
            }
        }
        return $ouders;
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

        if (self::$user->id != $this->id) {
            return false;
        }

        // Geldigheid controleren
        
        if (strlen($new) < 8) {
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
        $now = new \DateTime();
        $time = self::getDb()->escape_string($now->format('Y-m-d H:i:s'));
        $query = "INSERT INTO ouder_tokens (client, token, `time`) VALUES ($client, '$token', '$time')";

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
        $query = "DELETE FROM ouder_tokens WHERE token = '$token' OR `time` < '$time'";

        if (self::getDb()->query($query)) {
            if ($removeCookies)
                self::removeCookies();
            return true;
        }
        return false;
    }

    private static function setCookies($id, $token){
        // We slaan ook de client id op, omdat we hierdoor een time safe operatie kunnen doen
        setcookie('ouder_client', $id, time()+51840000,'/', '', true, true); 
        setcookie('ouder_token', $token, time()+51840000,'/', '', true, true); 
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
        $query = "SELECT o.*, a.*, g.*, t.token, t.time
        from ouders o
        left join gezinnen g on g.gezin_id = o.gezin
        left join adressen a on a.adres_id = o.adres
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
                if ($interval->days > Self::$login_days) {
                    self::deleteToken($token, true);
                    return null;
                }

                self::$currentToken = $row['token'];
                self::$user = new Ouder($row);

                if ($interval->days >= 1) {
                    // Token vernieuwen als hij al een dag oud is
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
        return !is_null(Self::checkLogin());
    }

    static function getUser() {
        return Self::$user;
    }
    
}