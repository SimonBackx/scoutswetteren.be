<?php
namespace Pirate\Sails\AmazonSes\Classes;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Wheel\Template;

class Mail
{
    public $subject;
    public $text;
    public $html;
    public $sender;
    public $recipients = [];
    public $attachments = [];
    public $replyTo;

    private $error_message = null;

    public function __construct($subject, $template, $data = array())
    {
        $this->subject = $subject;

        $this->text = Template::render('mails/txt/' . $template, $data, 'txt');

        $file = __DIR__ . '/../themes/' . Environment::getSetting('theme', 'shared') . '/templates/mails/html/' . $template . '.html';
        $file_alt = __DIR__ . '/../themes/' . 'shared' . '/templates/mails/html/' . $template . '.html';

        if (file_exists($file) || file_exists($file_alt)) {
            $this->html = Template::render('mails/html/' . $template, $data, 'html');
        }

        $this->sender = new Email(Environment::getSetting('mail.mail'), Environment::getSetting('mail.name'));
    }

    public function setFrom($email, $name = null)
    {
        $this->sender = new Email($email, $name);
    }

    public function addTo($email, $substitutions = array(), $name = null, $bcc = array())
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $recipient = new Recipient(new Email($email, $name), $substitutions);

        foreach ($bcc as $value) {
            $recipient->addBcc(new Email($value['name'], $value['email']));
        }

        $this->recipients[] = $recipient;
    }

    public function addAttachment($fileLocation, $fileName)
    {
        $this->attachments[] = new Attachment($fileLocation, $fileName);
    }

    public function setReplyTo($email)
    {
        $this->replyTo = new Email($email);
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
            $first = $this->recipients[0];
            $this->recipients = [
                new Recipient(new Email(Environment::getSetting('development_mail.mail'), Environment::getSetting('development_mail.name')), $first->substitutions),
            ];
        }

        //Passing `true` enables PHPMailer exceptions

        $mail = new PHPMailer(true);
        //Server settings
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
        $mail->isSMTP(); // Send using SMTP

        $mail->Host = Environment::getSetting('smtp.host'); // Set the SMTP server to send through
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = Environment::getSetting('smtp.username'); // SMTP username
        $mail->Password = Environment::getSetting('smtp.password'); // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mail->Port = Environment::getSetting('smtp.port'); // TCP port to connect to
        $mail->SMTPKeepAlive = true; // SMTP connection will not close after each email sent, reduces SMTP overhead

        // Attachments
        foreach ($this->attachments as $attachment) {
            $mail->addAttachment($attachment->path, $attachment->filename); // Add attachments
        }

        //Recipients
        $mail->setFrom($this->sender->email, $this->sender->name);

        if (isset($this->replyTo)) {
            $mail->addReplyTo($this->replyTo->email, $this->replyTo->name);
        }

        set_time_limit(60);

        foreach ($this->recipients as $index => $recipient) {
            try {
                $mail->addAddress($recipient->email->email, $recipient->email->name);

                foreach ($recipient->bcc as $bcc) {
                    $mail->addBCC($bcc->email, $bcc->name);
                }

                // Content
                $mail->Subject = $recipient->replace($this->subject);
                if (!empty($this->html)) {
                    $mail->isHTML(true); // Set email format to HTML
                    $mail->Body = $recipient->replace($this->html, true);
                    $mail->AltBody = $recipient->replace($this->text);
                } else {
                    $mail->Body = $recipient->replace($this->text);
                }

                $mail->send();
            } catch (\Exception $e) {
                error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
                $this->error_message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                return false;
            }

            //Clear all addresses for the next iteration
            $mail->clearAllRecipients();

            if ($index % 5 == 4) {
                // Comply with rate limit of 14 per second
                sleep(0.5);
            }
        }

        return true;

        /*$mj = new \Mailjet\Client(Environment::getSetting('mailjet.username'), Environment::getSetting('mailjet.secret'), true, ['version' => 'v3.1']);
    $body = [];

    foreach ($this->attachments as $attachment) {
    $body['Globals']['Attachments'][] = $attachment->toArray();
    }

    $body['Globals']['From'] = $this->sender->toArray();

    foreach ($this->recipients as $recipient) {
    $message = [
    "To" => [$recipient->email->toArray()],
    "Subject" => $recipient->replace($this->subject),
    "TextPart" => $recipient->replace($this->text),
    ];

    foreach ($recipient->bcc as $bcc) {
    $message["Bcc"][] = $bcc->toArray();
    }

    if (!empty($this->html)) {
    $message["HTMLPart"] = $recipient->replace($this->html, true);
    }
    $body['Messages'][] = $message;
    }

    $response = $mj->post(Resources::$Email, ['body' => $body]);

    if (!$response->success()) {
    $this->error_message = 'Failed';
    error_log('Failed to send mail: ' . json_encode($response->getData(), JSON_PRETTY_PRINT));
    return false;
    }

    foreach ($response->getData()['Messages'] as $message) {
    if ($message['Status'] != 'success') {
    error_log('Failed to send mail: ' . $message['Errors'][0]['ErrorMessage']);
    $this->error_message = $message['Errors'][0]['ErrorMessage'];
    return false;
    }
    }

    return true;*/
    }
}
