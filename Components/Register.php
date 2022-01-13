<?php
namespace Clsk\Lemonade;

use Clsk\Elena\Databases\QueryBuilder;
use Clsk\Elena\Session\Session;
use Clsk\Elena\Tools\UUIDFactory;
use Clsk\Elena\Tools\FileReader;
use Clsk\Lemonade\MailCreation;
use Clsk\Lemonade\Tools;

class Register
{
    public function PreRegister(string $from_mail, string $from_name, string $email, string $template="", bool $is_html=true, string $at_name="仮登録ユーザー様", string $mail_title="仮登録のお知らせ")
    {
        $token = UUIDFactory::generate();
        $builder = new QueryBuilder();
        $tool = new Tools();
        if($tool->EmailValid($email)){
            $insert_array = [
                "Email" => $email,
                "Token" => $token
            ];
            if($this->Check($email, $token) == "nothing"){
                $insert_to_pre = $builder::Table("ClMiliLemonadePreUsers")->Insert($insert_array, "assoc");
                if($insert_to_pre){
                    if(trim($template) == ""){
                        $template = file_get_contents(dirname(__FILE__)."/Templates/PreRegisterTemplate.html");
                    }
                    $settings = FileReader::SettingGetter();
                    if(array_key_exists("APPURL", $settings) && array_key_exists("RegisterRoute", $settings)){
                        $url = rtrim(rtrim(rtrim($settings["APPURL"], "/"))."/".rtrim(ltrim(rtrim($settings["RegisterRoute"], "/"), "/")), "?")."?t=".$token;
                    }else{
                        return false;
                    }
                    if(array_key_exists("TokenTimeOver", $settings)){
                        $time = explode(",", $settings["TokenTimeOver"]);
                        if(count($time) == 2){
                            $time_template = $time[0];
                        }else{
                            $time_template = "24";
                        }
                    }else{
                        $time_template = "24";
                    }
                    $template = str_replace("{{TIME}}", $time_template, $template);
                    $template = str_replace("{{URL}}", $url, $template);
                    $mailer = new MailCreation();
                    $mailer->MailSetting($from_mail, $from_name, $email, $at_name)->MailCreation($mail_title, $template, $is_html)->Send();
                    return true;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function Register(string $password, string $username, string $pictpath = "Resource/derfault.png")
    {
        Session::Start();
        $builder = new QueryBuilder();
        $token = Session::SessionReader("PreToken");
        $email = Session::SessionReader("PreEmail");
        $tools = new Tools();
        if(trim($email) != "" && trim($token) != "" && trim($password) != "" && trim($username) != "" && trim($pictpath) != "" && $this->Check($email, $token) == "pre" && $tools->PasswordValid($password)){
            $insert_array = [
                "Email" => $email,
                "Password" => $password,
                "UserName" => $username,
                "UserPictPath" => $pictpath
            ];
            $insert_to_main = $builder::Table("ClMiliLemonadeUsers")->Insert($insert_array, "assoc");
            if($insert_to_main){
                Session::Unset();
                $builder::Table("ClMiliLemonadePreUsers")->Where("Email", "=", $email)->Delete();
            }
            return $insert_to_main;
        }else{
            return false;
        }
    }

    public function Check(string $email)
    {
        $settings = FileReader::SettingGetter();
        if(array_key_exists("TokenTimeOver", $settings)){
            $exploded = explode(",", $settings["TokenTimeOver"]);
            if(count($exploded) == 2 && is_numeric($exploded[0])){
                $times = $exploded[0];
                if($exploded[1] == "h"){
                    $time = "hour";
                }else if($exploded[1] == "m"){
                    $time = "minute";
                }else if($exploded[1] == "s"){
                    $time = "second";
                }else {
                    $time = "hour";
                }
            }else{
                $time = "hour";
                $times = "24";
            }
        }
        $search_token_time_over = QueryBuilder::Table("ClMiliLemonadePreUsers")->Fetch("All");
        foreach($search_token_time_over as $search){
            $date = date("Y-m-d H:i:s",strtotime($search["created_at"]." +".trim(trim(trim($times), "-"), "+")." ".$time));
            $today = date("Y-m-d H:i:s");
            $date = new \DateTime($date);
            $today = new \DateTime($today);
            if($today > $date){
                QueryBuilder::Table("ClMiliLemonadePreUsers")->Where("Id", "=", $search["id"])->Delete();
            }
        }
        $check_on_main = QueryBuilder::Table("ClMiliLemonadeUsers")->Where("Email", "=", $email)->CountRow();
        $check_on_pre = QueryBuilder::Table("ClMiliLemonadePreUsers")->Where("Email", "=", $email)->CountRow();
        if($check_on_main == 0 && $check_on_pre == 1){
            return "pre";
        }else if($check_on_main == 1 && $check_on_pre == 0){
            return "main";
        }else{
            return "nothing";
        }
    }

    public function TokenAuthorizer(string $token)
    {
        $check_token = QueryBuilder::Table("ClMiliLemonadePreUsers")->Where("Token", "=", $token);
        $count = $check_token->CountRow();
        $data = $check_token->Fetch("One");
        if($count != 0){
            Session::Start();
            Session::Insert("PreToken", $token);
            Session::Insert("PreEmail", $data["Email"]);
            return $data;
        }else{
            return false;
        }
    }
}