<?php
namespace Pirate\Mail;
use Pirate\Template\Template;
use SendGrid\Personalization;
use SendGrid\Email;
use SendGrid\ReplyTo;
use SendGrid\Attachment;

class Mail {
    private $sendgrid_mail = null;
    private $totalAttachmentSize = 0;
    private $error_message = null;

    function __construct($subject, $template, $data = array()) {
        $this->sendgrid_mail = new \SendGrid\Mail();
        $this->sendgrid_mail->setSubject($subject);

        $text = Template::render('mails/txt/'.$template, $data, 'txt');
        $this->sendgrid_mail->addContent(array('type' => 'text/plain', 'value' => $text));

        $file = __DIR__.'/../templates/mails/html/'.$template.'.html';

        if (file_exists($file)) {
            $html = Template::render('mails/html/'.$template, $data, 'html');
            $this->sendgrid_mail->addContent(array('type' => 'text/html', 'value' => $html));
        }

        $this->setFrom('website@scoutswetteren.be', 'Scouts Prins Boudewijn');
    }

    function setFrom($email, $name = null) {
        $email = new Email($name, $email);
        $this->sendgrid_mail->setFrom($email);
    }

    function addTo($email, $substitutions = array(), $name = null, $bcc = array()) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $personalization = new Personalization();
        $email = new Email($name, $email);
        $personalization->addTo($email);

        foreach ($substitutions as $key => $value) {
            $personalization->addSubstitution("%$key%", $value);
        }

        foreach ($bcc as $value) {
            $personalization->addBcc(new Email($value['name'], $value['email']));
        }


        $this->sendgrid_mail->addPersonalization($personalization);
    }

    function addAttachment($fileLocation, $fileName) {
        $size = @filesize($fileLocation);
        $output = @file_get_contents($fileLocation);

        $this->totalAttachmentSize += $size;
        if ($output === false || $this->totalAttachmentSize > 10000000) {
            return false;
        }

        $file_encoded = base64_encode($output);
        $attachment = new Attachment();
        $attachment->setContent($file_encoded);
        $attachment->setType(mime_content_type($fileLocation));
        $attachment->setDisposition("attachment");
        $attachment->setFilename($fileName);

        $this->sendgrid_mail->addAttachment($attachment);
        return true;
    }

    function setReplyTo($email) {
        $reply_to = new ReplyTo($email);
        $this->sendgrid_mail->setReplyTo($reply_to);
    }

    function getErrorMessage() {
        return $this->error_message;
    }

    function send() {
        global $config;

        if (isset($_ENV["DEBUG"]) && $_ENV["DEBUG"] == 1) {
            // Forceer versturen naar website@scoustwetteren.be
            // + behoud substitutions van eerste email!
            $first_personalization = $this->sendgrid_mail->personalization[0];
            $substitutions = $first_personalization->getSubstitutions();
            if (!isset($substitutions)) {
                $substitutions = [];
            }

            $new_substitutions = [];
            // % tekens terug weghalen uit keys
            foreach ($substitutions as $key => $value) {
                $new_substitutions[substr($key, 1, count($key) - 2)] = $value;
            }

            $this->sendgrid_mail->personalization = [];
            $this->addTo($config['development_mail']['mail'], $new_substitutions, $config['development_mail']['name']);
        }

        $sg = new \SendGrid($config['sendgrid']['key']);
        $response = $sg->client->mail()->send()->post($this->sendgrid_mail);
        $status = intval($response->statusCode());
        $this->error_message = $response->body();

        return ($status >= 200 && $status < 300);
    }
}