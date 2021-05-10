<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';


class Mailer{

    private $mail;
    public function __construct()
    {
        $this->mail = new PHPMailer(true);
    }

    public function send_recovery_mail(string $mail, string $newPassword){
        try{
            //$this->mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $this->mail->isSMTP();                                            //Send using SMTP
            $this->mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
            $this->mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $this->mail->Username   = self::EMAIL_LOGIN;                     //SMTP username
            $this->mail->Password   = self::EMAIL_PASSWORD;                               //SMTP password
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         //Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
            $this->mail->Port       = 587;                                    //TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

            $this->mail->setFrom(self::EMAIL_LOGIN, 'Password recovery');
            $this->mail->addAddress($mail);
            $this->mail->Subject = 'Password recovery';
            $this->mail->Body    = "New temporary password is: $newPassword.<br>Do not forget to change it";
            $this->mail->AltBody = "New temporary password is: $newPassword.\nDo not forget to change it";

            $this->mail->send();
            return true;
        } catch (Exception) {
            file_put_contents("email.error.txt", $this->mail->ErrorInfo);
            return false;
        }
    }

    const EMAIL_LOGIN = 'todof7319@gmail.com';
    const EMAIL_PASSWORD = '1Z6458123';
}