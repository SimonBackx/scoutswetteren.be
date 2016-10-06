<?php
namespace Pirate\Mail;
use Pirate\Template\Template;
use SendGrid\Personalization;
use SendGrid\Email;
use SendGrid\ReplyTo;

class Mail {
    private $sendgrid_mail = null;

    function __construct($subject, $template, $data = array()) {
        $this->sendgrid_mail = new \SendGrid\Mail();
        $this->sendgrid_mail->setSubject($subject);

        $text = Template::render('mails/'.$template, $data, 'txt');
        //$html = Template::render('mails/'.$template, $data, 'html');

        $this->sendgrid_mail->addContent(array('type' => 'text/plain', 'value' => $text));
        //$this->sendgrid_mail->addContent(array('type' => 'text/html', 'value' => $html));

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

    function setReplyTo($email) {
        $reply_to = new ReplyTo($email);
        $this->sendgrid_mail->setReplyTo($reply_to);
    }

    function send() {
        global $config;

        $sg = new \SendGrid($config['sendgrid']['key']);
        $response = $sg->client->mail()->send()->post($this->sendgrid_mail);
        $status = intval($response->statusCode());

        echo $response->body();
        return ($status >= 200 && $status < 300);
    }
}