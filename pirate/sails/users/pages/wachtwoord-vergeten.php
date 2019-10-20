<?php
namespace Pirate\Sails\Users\Pages;

use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Users\Models\User;
use Pirate\Sails\Mailjet\Classes\Mail;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class WachtwoordVergeten extends Page
{
    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $errors = array();
        $success = false;
        $email = "";

        if (isset($_POST['email'])) {
            $email = $_POST['email'];
            $user = User::getForEmail($email);

            if (!isset($user)) {
                $errors[] = "Het opgegeven e-mailadres staat niet geregistreerd in ons systeem. Gebruik het e-mailadres waarop je e-mails van ons ontvangt.";
            } else {
                if ($user->generatePasswordRecoveryKey()) {
                    // Mail versturen enzo
                    // TODO
                    $mail = new Mail(
                        'Wachtwoord opnieuw instellen - ' . Environment::getSetting('name'),
                        'wachtwoord-vergeten',
                        array('url' => $user->getSetPasswordUrl(), 'naam' => $user->firstname)
                    );

                    $mail->addTo(
                        $user->mail,
                        array(),
                        $user->firstname . ' ' . $user->lastname
                    );

                    if ($mail->send()) {
                        $success = true;
                    } else {
                        $errors[] = "Er ging iets mis bij het versturen van de e-mail. Neem contact met ons op als dit zich blijft voordoen.";
                    }

                } else {
                    $errors[] = "Er ging iets mis bij het aanmaken van de e-mail. Neem contact met ons op.";
                }

            }

        }

        return Template::render('pages/users/wachtwoord-vergeten', array(
            'errors' => $errors,
            'email' => $email,
            'success' => $success,
        ));
    }
}
