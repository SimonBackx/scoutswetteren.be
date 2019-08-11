<?php
namespace Pirate\Wheel;

use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Wheel\Template;
use SendGrid\Attachment;
use SendGrid\Email;
use SendGrid\Personalization;
use SendGrid\ReplyTo;

class Mail
{
    private $sendgrid_mail = null;
    private $totalAttachmentSize = 0;
    private $error_message = null;

    public function __construct($subject, $template, $data = array())
    {
        $this->sendgrid_mail = new \SendGrid\Mail();
        $this->sendgrid_mail->setSubject($subject);

        $text = Template::render('mails/txt/' . $template, $data, 'txt');
        $this->sendgrid_mail->addContent(array('type' => 'text/plain', 'value' => $text));

        $file = __DIR__ . '/../templates/mails/html/' . $template . '.html';

        if (file_exists($file)) {
            $html = Template::render('mails/html/' . $template, $data, 'html');
            $this->sendgrid_mail->addContent(array('type' => 'text/html', 'value' => $html));
        }

        $this->setFrom(Environment::getSetting('mail.mail'), 'Scouts Prins Boudewijn');
    }

    public function setFrom($email, $name = null)
    {
        $email = new Email($name, $email);
        $this->sendgrid_mail->setFrom($email);
    }

    public function addTo($email, $substitutions = array(), $name = null, $bcc = array())
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $personalization = new Personalization();
        $email = new Email($name, $email);
        $personalization->addTo($email);

        foreach ($substitutions as $key => $value) {
            $personalization->addSubstitution("%$key%", htmlspecialchars($value));
        }

        foreach ($bcc as $value) {
            $personalization->addBcc(new Email($value['name'], $value['email']));
        }

        $this->sendgrid_mail->addPersonalization($personalization);
    }

    public function addAttachment($fileLocation, $fileName)
    {
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

    public function setReplyTo($email)
    {
        $reply_to = new ReplyTo($email);
        $this->sendgrid_mail->setReplyTo($reply_to);
    }

    public function getErrorMessage()
    {
        return $this->error_message;
    }

    public function send()
    {
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
                $new_substitutions[substr($key, 1, strlen($key) - 2)] = $value;
            }

            $this->sendgrid_mail->personalization = [];
            $this->addTo(Environment::getSetting('development_mail.mail'), $new_substitutions, Environment::getSetting('development_mail.name'));
        }

        $sg = new \SendGrid(Environment::getSetting('sendgrid.key'));
        $response = $sg->client->mail()->send()->post($this->sendgrid_mail);
        $status = intval($response->statusCode());
        $this->error_message = $response->body();

        return ($status >= 200 && $status < 300);
    }
}
