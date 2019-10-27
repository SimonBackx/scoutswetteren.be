<?php
namespace Pirate\Sails\AmazonSes\Models;

use PHPMailer\PHPMailer\PHPMailer;
use Pirate\Sails\AmazonSes\Classes\Attachment;
use Pirate\Sails\AmazonSes\Classes\Email;
use Pirate\Sails\AmazonSes\Classes\Recipient;
use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Validating\Classes\DatabaseError;
use Pirate\Wheel\Model;
use Pirate\Wheel\Template;

class Mail extends Model
{
    public $id;

    public $subject;

    // blob
    public $html = null;

    // blob
    public $text = null;

    // Json encoded field
    public $sender;

    // Json encoded field
    public $replyTo;

    // Json encoded field
    public $recipients = [];

    // Json field with all the attachments
    public $attachments = [];

    public static function create($subject, $template, $data = array())
    {
        $mail = new Mail();
        $mail->subject = $subject;

        $mail->text = Template::render('mails/txt/' . $template, $data, 'txt');

        $file = __DIR__ . '/../../../themes/' . Environment::getSetting('theme', 'shared') . '/templates/mails/html/' . $template . '.html';
        $file_alt = __DIR__ . '/../../../themes/' . 'shared' . '/templates/mails/html/' . $template . '.html';

        if (file_exists($file) || file_exists($file_alt)) {
            $mail->html = Template::render('mails/html/' . $template, $data, 'html');
        }

        $mail->sender = new Email(Environment::getSetting('mail.mail'), Environment::getSetting('mail.name'));
        return $mail;
    }

    public static function getScheduledMails()
    {
        $query = 'SELECT * from mails order by mail_id asc LIMIT 5';

        $mails = [];
        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $mails[] = new Mail($row);
                }
            }
        }
        return $mails;
    }

    public function sendOrDelay()
    {
        if (count($this->recipients) > 2) {
            $this->save();
            // Cronjob
            return true;
        }
        return $this->send();
    }

    public function __construct($row = null)
    {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['mail_id'];
        $this->subject = $row['mail_subject'];
        $this->html = $row['mail_html'];
        $this->text = $row['mail_text'];
        $this->sender = Email::fromJson(json_decode($row['mail_sender']));
        $this->replyTo = Email::fromJson(json_decode($row['mail_reply_to']));

        $this->recipients = [];
        foreach (json_decode($row['mail_recipients']) as $receiver) {
            $this->recipients[] = Recipient::fromJson($receiver);
        }

        $this->attachments = [];
        foreach (json_decode($row['mail_attachments']) as $attachment) {
            $this->attachments[] = Attachment::fromJson($attachment);
        }
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
        global $FILES_DIRECTORY;
        $destination = $FILES_DIRECTORY . '/mails/' . static::generateLongKey() . '/' . $fileName;

        $dir = dirname($destination);

        $try = 0;
        $failed = true;
        while ($try < 2) {
            $try++;

            if (is_dir($dir) || mkdir($dir, 0777, true)) {
                $failed = false;
                break;
            }
        }

        copy($fileLocation, $destination);
        $this->attachments[] = new Attachment($destination, $fileName);
        return true;
    }

    public function deleteAttachments()
    {
        foreach ($this->attachments as $attachment) {
            $attachment->delete();
        }
        $this->attachments = [];
    }

    private static function generateLongKey()
    {
        $bytes = openssl_random_pseudo_bytes(32);
        return bin2hex($bytes);
    }

    public function setReplyTo($email)
    {
        $this->replyTo = new Email($email);
    }

    public function save()
    {

        $subject = self::getDb()->escape_string($this->subject);

        if (!is_null($this->html)) {
            $html = "'" . self::getDb()->escape_string($this->html) . "'";
        } else {
            $html = "NULL";
        }
        if (!is_null($this->text)) {
            $text = "'" . self::getDb()->escape_string($this->text) . "'";
        } else {
            $text = "NULL";
        }
        $sender = self::getDb()->escape_string(json_encode($this->sender));
        $replyTo = self::getDb()->escape_string(json_encode($this->replyTo));
        $recipients = self::getDb()->escape_string(json_encode($this->recipients));
        $attachments = self::getDb()->escape_string(json_encode($this->attachments));

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);

            $query = "UPDATE mails
                SET
                mail_subject = '$subject',
                mail_html = $html,
                mail_text = $text,
                mail_sender = '$sender',
                mail_reply_to = '$replyTo',
                mail_recipients = '$recipients',
                mail_attachments = '$attachments'
                 where `mail_id` = '$id'
            ";
        } else {
            $query = "INSERT INTO
                mails (`mail_subject`, `mail_html`, `mail_text`, `mail_sender`, `mail_reply_to`, `mail_recipients`, `mail_attachments`)
                VALUES ('$subject', $html, $text, '$sender', '$replyTo', '$recipients', '$attachments')";
        }

        $result = self::getDb()->query($query);

        if ($result) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }

            return true;
        }

        throw new DatabaseError(self::getDb()->error);
    }

    public function delete()
    {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM
                mails WHERE `mail_id` = '$id' ";

        return self::getDb()->query($query);
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

        // Disable debug headers
        $mail->SMTPDebug = false;

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

        set_time_limit(count($this->recipients) / 5 + 60);

        foreach ($this->recipients as $index => $recipient) {
            if ($index % 5 == 4) {
                // Comply with rate limit of 14 per second
                sleep(0.5);
            }

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
            }

            //Clear all addresses for the next iteration
            $mail->clearAllRecipients();

        }

        error_log("Mail succeeded");

        $this->deleteAttachments();

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
