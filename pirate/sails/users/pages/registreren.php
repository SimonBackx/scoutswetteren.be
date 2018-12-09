<?php
namespace Pirate\Sail\Users\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Users\User;

// Deze pagina mag enkel getoond worden als de ouder (tijdelijk) ingelogd is
class Registreren extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        if (User::isLoggedIn()) {
            return 'Error!';
        }

        $errors = array();
        $success = false;

        $data = array(
            'firstname' => '',
            'lastname' => '',
            'phone' => '',
            'mail' => '',
        );
        $allset = true;

        foreach ($data as $key => $value) {
            if (!isset($_POST[$key])) {
                $allset = false;
            } else {
                $data[$key] = $_POST[$key];
            }
        }

        if ($allset) {
            // Bestaat er een ouder met dit e-mailadres?
            $user_mail = User::getForEmail($data['mail']);

            // Bestaat er een ouder met dit GSM-nummer?
            $user_phone = User::getForEmail($data['phone']);

            if (isset($user_mail, $user_phone) && $user_mail->id == $user_phone->id) {
                // Je hebt al een account, gebruik de wachtwoord vergeten functie.
                $errors[] = "Je hebt al een account op dit e-mailadres en GSM-nummer. Log in of gebruik de wachtwoord vergeten functie.";
            } elseif (isset($user_mail)) {
                if (trim(clean_special_chars($user_mail->firstname)) == trim(clean_special_chars($data['firstname']))) {
                    // Je hebt al een account, gebruik de wachtwoord vergeten functie.
                    $errors[] = "Je hebt al een account op dit e-mailadres, maar op een ander GSM-nummer (typfout?). Log in of gebruik de wachtwoord vergeten functie.";
                } else {
                    $errors[] = "Er bestaat reeds een ouder met het opgegeven e-mailadres. Je kan niet hetzelfde e-mailadres gebruiken voor twee ouders, gebruik een ander e-mailadres zodat beide ouders toegang hebben tot de inschrijvingen.";
                }
            } elseif (isset($user_phone)) {
                // Je hebt al een account, gebruik de wachtwoord vergeten functie.
                $errors[] = "Je hebt al een account op dit GSM-nummer, maar op een ander e-mailadres (".obfuscateEmail($user_phone->mail)."). Log in of gebruik de wachtwoord vergeten functie.";
            } else {
                // ok
                $errors[] = "wip";
            }

            // todo
            // $errors = $user->setProperties($data);
        }

        return Template::render('users/registreren', array(
            'success' => $success,
            'errors' => $errors,
            'data' => $data,
        ));
    }
}