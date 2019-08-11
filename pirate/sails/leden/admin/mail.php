<?php
namespace Pirate\Sails\Leden\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Validating\Models\Validator;
use Pirate\Wheel\Mail;
use Pirate\Sails\Files\Models\File;
use Pirate\Sails\Users\Models\User;

class MailPage extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $user = Leiding::getUser();

        $tak = '';
        if (!empty($user->tak)) {
            $tak = $user->tak;
        } 

        $takken = array('kapoenen', 'wouters', 'jonggivers', 'givers', 'jin', 'alle takken');
        $senders = array();
        $filters = Ouder::$filters;

        $scoutsjaar = Inschrijving::getScoutsjaar();
        $selected_scoutsjaar = $scoutsjaar;

        $data = array(
            'tak' => $tak,
            'sender' => '',
            'filter' => array_keys($filters)[0],
            'subject' => '',
            'message' => '',
            'scoutsjaar' => $scoutsjaar
        );

        $senders[] = $user->user->mail;

        $contacts = Leiding::getContacts();

        $sender_name = null;
        $sender_send_from = false;
        $sender_okay = false;
        $sender_default = false;

        $allSet = true;
        foreach ($data as $key => $value) {
            if (!isset($_POST[$key])) {
                $allSet = false;
            } else {
                $data[$key] = $_POST[$key];
            }
        }

        foreach ($contacts as $key => $value) {
            if (Leiding::getContactEmail($key, $email, $naam, $send_from)) {
                $senders[] = $email;
                if ($key == $tak && $data['sender'] == '') {
                    // Default selectie op tak van gebruiker zetten
                    $data['sender'] = $email;
                    $sender_okay = false;
                    $sender_default = true;
                }

                if ($email == $data['sender'] && !$sender_default) {
                    // Sender alleen goedkeuren als expliciet verzonden werd, 
                    // niet default waarde die niet verzonden werd
                    $sender_okay = true;
                    $sender_name = $naam;
                    $sender_send_from = $send_from;
                }
            }
        }

        if (!$sender_okay && !$sender_default && $data['sender'] == $user->user->mail) {
            $sender_okay = true;
            $sender_name = $user->user->firstname.' '.$user->user->lastname;
            $sender_send_from = false;
        }
        

        $success = false;
        $errors = array();

        if ($allSet) {
            if (!$sender_okay) {
                $errors[] = 'Selecteer een e-mailadres vanaf waar je de e-mail wil versturen.';
            }
            if (!in_array($data['tak'], $takken)) {
                $errors[] = 'Selecteer een tak waar je de e-mail wil naar versturen.';
            }
            
            $selected_scoutsjaar = intval($data['scoutsjaar']);
            if ($selected_scoutsjaar == 0) {
                $errors[] = 'Ongeldig scoutsjaar.';
            }

            if (!isset($filters[$data['filter']])) {
                $errors[] = 'Selecteer een filter.';
            }
            if (strlen($data['subject']) < 5) {
                $errors[] = 'Het onderwerp is te kort.';
            }
            if (strlen($data['message']) < 40) {
                $errors[] = 'Het bericht is te kort. Gebruik a.u.b. een goede opbouw van je e-mail.';
            }

            
            $attachment = null;
            if (count($errors) == 0) {
                $form_name = "attachment";
                if (File::isFileSelected($form_name)) {
                    if (File::getUploaded($form_name, $fileExt, $fileName, $fileSize, $errors, 10000000, array("pdf", "png", "jpg", "jpeg", "gif", "tiff", "bmp", "heif", "heic", "mov", "mp4", "wav", "ppt", "pptx", "xls", "xlsx"))) {
                        $attachment = array(
                            "location" => $_FILES[$form_name]['tmp_name'],
                            "name" => $fileName
                        );
                    } else {
                        $errors[] = 'Converteer Word-documenten eerst naar PDF voor je ze doormailt (opslaan als - onderaan PDF selecteren), die zijn geschikter en vervormen niet. Niet elke smartphone kan een Word-document openen.';
                    }
                }
            }

            if (count($errors) == 0) {
                //$success = true;
                $ouders = array();

                if ($data['tak'] == 'alle takken') {
                    $ouders = Ouder::getOuders($data['filter'], null, false, $selected_scoutsjaar);
                } else {
                    $ouders = Ouder::getOuders($data['filter'], $data['tak'], false, $selected_scoutsjaar);
                }

                if (count($ouders) == 0) {
                    $errors[] = 'Er zijn geen ouders die aan de criteria voldoen.';
                }  else {

                    $mail = new Mail(
                            $data['subject'], 
                            'mail', 
                            array(
                                'message' => $data['message'],
                                'subject' => $data['subject'],
                                'magic_url' => true
                            )
                        );

                    if ($sender_send_from) {
                        $mail->setFrom($data['sender'], $sender_name);
                    } else {
                        $mail->setReplyTo($data['sender']);
                    }

                    if (isset($attachment)) {
                        if (!$mail->addAttachment($attachment["location"], $attachment["name"])) {
                            $errors[] = 'Er ging iets mis bij het versturen van de bijlage. Er werd geen e-mail verzonden.';
                        }
                    }

                    $users = [];
                    foreach($ouders as $ouder) {
                        $users[] = $ouder->user;
                    }

                    if (count($errors) == 0 && !User::createMagicTokensFor($users)) {
                        $errors[] = 'Kon geen link genereren om ouders automatisch in te loggen. Contacteer webmaster.';
                    }

                    if (count($errors) == 0) {
                        foreach ($ouders as $ouder) {
                            $mail->addTo(
                                $ouder->user->mail, 
                                array(
                                    'magic_url' => $ouder->user->getMagicTokenUrl(),
                                    'voornaam' => $ouder->user->firstname,
                                    'reason' => ''
                                ),
                                $ouder->user->firstname.' '.$ouder->user->lastname
                            );
                        }

                        $mail->addTo( 
                            $data['sender'],
                            array(
                                'magic_url' => "https://".$_SERVER['SERVER_NAME'],
                                'voornaam' => '<voornaam van ouder>',
                                'reason' => "\n".'Dit bericht is een kopie van het bericht dat naar ouders ('.$data['tak'].') is verzonden via de website door '.$user->user->firstname.' '.$user->user->lastname
                            )
                        );

                        $success = $mail->send();

                        if (!$success) {
                            $errors[] = 'Er ging iets mis bij het versturen van de e-mails. Probeer het later opnieuw. ('.$mail->getErrorMessage().')';
                        }
                    }
                }
            }
        }


        return Template::render('admin/leden/mail', array(
            'takken' => $takken,
            'senders' => $senders,
            'filters' => $filters,
            'errors' => $errors,
            'data' => $data,
            'scoutsjaar' => $scoutsjaar,
            'success' => $success
        ));
    }
}