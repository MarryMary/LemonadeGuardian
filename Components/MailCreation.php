<?php
namespace Clsk\Lemonade;

require dirname(__FILE__).'\..\autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Clsk\Elena\Tools\FileReader;

class MailCreation
{
  private $mail;
  private $host;
  private $username;
  private $password;
  private $port;
  public function __construct()
  {
    $this->mail = new PHPMailer(true);
    $this->mail->CharSet = "iso-2022-jp";
    $this->mail->Encoding = "7bit";
    $settings = FileReader::SettingGetter();
    if(array_key_exists("MailHost", $settings) && array_key_exists("MailUserName", $settings) && array_key_exists("MailPassword", $settings) && array_key_exists("MailPort", $settings)){
      $this->host = $settings["MailHost"];
      $this->username = $settings["MailUserName"];
      $this->password = $settings["MailPassword"];
      $this->port = $settings["MailPort"];
    }else{
      return false;
    }
  }

  public function MailSetting(string $fromAdr, string $fromName, string $atAdr, string $atName, bool $rep = false, string $repAdr = "", string $repHeader = "")
  {
    $this->mail->isSMTP();
    $this->mail->Host = $this->host;
    $this->mail->SMTPAuth = true;
    $this->mail->Username = $this->username;
    $this->mail->Password = $this->password;
    $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $this->mail->Port = $this->port;
    $this->mail->setFrom($fromAdr, mb_encode_mimeheader($fromName));
    $this->mail->addAddress($atAdr, mb_encode_mimeheader($atName));
    if($rep){
      $this->mail->addReplyTo($repAdr, mb_encode_mimeheader($repHeader));
    }
  }

  public function MailCreation(string $title, string $template, bool $isHtml = true)
  {
    $this->mail->isHTML($isHtml);
    $this->mail->Subject = mb_encode_mimeheader($title);
    $this->mail->Body = mb_convert_encoding($template, "JIS", "UTF-8");
  }

  public function AddAlt(string $message)
  {
    $this->mail->AltBody = mb_convert_encoding($message, "JIS", "UTF-8");
  }

  public function AddCC(array $cc)
  {
    foreach($cc as $ccs){
      $this->mail->addCC($ccs);
    }
  }

  public function Send()
  {
    $this->mail->send();
    return true;
  }
}