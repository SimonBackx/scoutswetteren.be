<?php
namespace Pirate\Sail\Users\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Users\User;
use Pirate\Model\Validating\Validator;

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
            $allfilled = true;

            foreach ($data as $key => $value) {
                if (empty($_POST[$key])) {
                    $allfilled = false;
                    break;
                }
            }

            if (!$allfilled) {
                $errors[] = "Vul alle velden in";
            }

            // Format phone
            Validator::validatePhone($data['phone'], $data['phone'], $errors);

            if (count($errors) == 0) {
                // Bestaat er een ouder met dit e-mailadres?
                $user_mail = User::getForEmail($data['mail']);

                // Bestaat er een ouder met dit GSM-nummer?
                $user_phone = User::getForPhone($data['phone']);

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
                    if (trim(clean_special_chars($user_phone->firstname)) == trim(clean_special_chars($data['firstname']))) {
                        $errors[] = "Je hebt al een account op dit GSM-nummer, maar op een ander e-mailadres (".obfuscateEmail($user_phone->mail)."). Kijk na op typfouten. Log in of gebruik de wachtwoord vergeten functie.";
                    } else {
                        $errors[] = "Er bestaat reeds een ouder met het opgegeven GSM-nummer, maar met een ander e-mailadres. Kijk na op typfouten. Log in of gebruik de wachtwoord vergeten functie.";

                    }
                } else {
                    $user = new User();
                    $errors = $user->setProperties($data);

                    if (count($errors) == 0) {
                        if ($user->save()) {
                            if ($user->sendPasswordEmail()) {
                                $success = true;
                            } else {
                                $errors[] = "Er ging iets mis bij het versturen van de e-mail";
                            }
                        } else {
                            $errors[] = "Er ging iets mis";
                        }
                    }
                    // ok
                    //$errors[] = "wip";
                }

                // todo
                // $errors = $user->setProperties($data);
            }

            
        }

        return Template::render('pages/users/registreren', array(
            'success' => $success,
            'errors' => $errors,
            'data' => $data,
        ));
    }
}