<?php
namespace Pirate\Sails\Contact\Pages;

use Pirate\Sails\AmazonSes\Models\Mail;
use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Sails\Settings\Models\Setting;
use Pirate\Sails\Validating\Models\Validator;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;
use Pirate\Sails\Leiding\Models\Leiding as LeidingModel;
use Pirate\Sails\Environment\Classes\Environment;

class Contact extends Page
{
    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $data = array(
            'wie' => '',
            'name' => '',
            'email' => '',
            'subject' => '',
            'message' => '',
        );

        $wie = Leiding::getContacts();

        $success = false;
        $errors = array();

        $allSet = true;
        foreach ($data as $key => $value) {
            if (isset($_POST[$key])) {
                $data[$key] = $_POST[$key];
            } else {
                if ($key == 'wie') {
                    continue;
                }
                $allSet = false;
            }
        }

        // Optioneel, maar verplicht indien opgegeven
        if (isset($_POST["phone"])) {
            $data["phone"] = $_POST["phone"];
        }

        // Beveiliging tegen robots
        if (isset($_POST['nickname'])) {
            if (strlen($_POST['nickname']) > 0) {
                $allSet = false;
            }
        }

        if ($allSet) {
            $data['subject'] = trim($data['subject']);

            if (!Leiding::getContactEmail($data['wie'], $contactpersoon_email, $contactpersoon_naam, $send_from)) {
                $errors[] = 'Je moet selecteren wie je wilt contacteren.';
            }

            if (!Validator::isValidName($data['name'])) {
                $errors[] = 'Ongeldige naam. Controleer of je geen fouten hebt gemaakt.';
            }
            if (!Validator::isValidMail($data['email'])) {
                $errors[] = 'Ongeldig e-mailadres. Controleer of je geen fouten hebt gemaakt.';
            }

            if (isset($data['phone']) && !Validator::validatePhone($data['phone'], $data['phone'], $errors)) {
                // done
            }

            if (strlen($data['subject']) < 4) {
                $errors[] = 'Onderwerp te kort';
            }
            if (strlen($data['message']) < 30) {
                $errors[] = 'Uw bericht is te kort. Voorzie voldoende informatie.';
            }
            if (count($errors) == 0) {
                $success = true;
                $mail = Mail::create('Webformulier: ' . $data['subject'], 'contact', array('data' => $data, 'naam' => $contactpersoon_naam));

                $mail->setReplyTo($data['email']);
                $mail->addTo(
                    $contactpersoon_email,
                    array(),
                    $contactpersoon_naam
                );

                if (!$mail->sendOrDelay()) {
                    $errors[] = 'Er ging iets mis bij het versturen van de e-mail. Contacteer de gekozen contactpersoon via ' . $contactpersoon_email . '.';
                } else {
                    $success = true;
                }
            }
        }

        $scoutsjaar = Inschrijving::getScoutsjaar();
        $takkenverdeling = Lid::getTakkenVerdeling($scoutsjaar, 'M');
        $jaar_verdeling = array();
        foreach ($takkenverdeling as $jaar => $tak) {
            if (!isset($jaar_verdeling[$tak])) {
                $jaar_verdeling[$tak] = array();
            }
            $jaar_verdeling[$tak][] = $jaar;
        }

        $verdeling_string = array();
        foreach ($jaar_verdeling as $tak => $jaren) {
            $min = min($jaren);
            $max = max($jaren);
            if ($min == $max) {
                $verdeling_string[$tak] = 'in ' . $min;
            } else {
                $verdeling_string[$tak] = 'in het jaar ' . $min . ' tot ' . $max;
            }
        }

        // Phone only here
        $leiding_data = array();

        // Groepsleiding toeveogen
        $leiding_data['groepsleiding'] = Leiding::getLeiding('groepsleiding');
        shuffle($leiding_data['groepsleiding']);
        $groepsleiding_gsm_zichtbaar = Setting::getSetting('groepsleiding_gsm_zichtbaar', false);

        // Leiding
        $leiding = LeidingModel::getLeiding();
        $takken = Environment::getSetting('scouts.takken');
        $grouped_data = [];

        foreach ($takken as $tak => $data) {
            $filtered = [];
            foreach ($leiding as $lid) {
                if ($lid->tak == $tak) {
                    $filtered[] = $lid;
                }
            }
            $grouped_data[]= [
                'name' => $tak,
                'data' => $data,
                'leiding' => $filtered,
            ];
        }

        return Template::render('pages/contact/contact', array(
            'data' => $data,
            'errors' => $errors,
            'success' => $success,
            'wie' => $wie,
            'takkenverdeling' => $verdeling_string,
            'leiding' => $leiding_data,
            'logged_in' => Leiding::isLoggedIn() || Ouder::isLoggedIn(),
            'groepsleiding_gsm_zichtbaar' => $groepsleiding_gsm_zichtbaar->value,
            'contacts' => Leiding::getContacts(),
            'leiding_verborgen' => !LeidingModel::isLeidingZichtbaar(),
            'takken' => $grouped_data,
        )
        );
    }
}
