<?php
namespace Clsk\Lemonade;

use Clsk\Elena\Session\Session;
use Clsk\Elena\Databases\QueryBuilder;
use Clsk\Elena\Tools\FileReader;
use Clsk\Elena\Tools\UUIDFactory;
use Clsk\Lemonade\MailCreation;

class LemonadeCore
{
    public function Authorize(string $email, string $password, bool $checker_mode = false, bool $bio = false)
    {
        if(!$bio){
            $authorizer = new Authorizer();
            if($authorizer->Authorize($email, $password)){
                if($checker_mode){
                    return true;
                }else{
                    Session::Start();
                    Session::Insert("isAuth", true);
                    Session::Insert("email", $email);
                }
            };
        }
    }

    public function IsAuth(){
        Session::Start();
        return Session::IsIn("isAuth");
    }

    public function LogOut(bool $all_del = true){
        Session::Start();
        if($all_del){
            Session::Unset();
            return true;
        }else{
            Session::Unset("isAuth");
            return true;
        }
    }

    public function Update(string $old_or_new, string $password, string $mode="email", string $new = "")
    {
        $authorizer = new Authorizer();
        $mode = strtolower($mode);
        if($mode == "email"){
            $authorizer->Update($old_or_new, $password, $mode, $new);
        }else if($mode == "password"){
            $authorizer->Update($old_or_new, $password, $mode);
        }else if($mode == "userpict"){
            $authorizer->Update($old_or_new, "", $mode);
        }else if($mode == "username"){
            $authorizer->Update("", "", $mode);
        }else{
            return false;
        }
    }

    public function UnRegister(string $password)
    {
        $authorizer = new Authorizer();
        $email = Session::SessionReader("email");
        $result = $authorizer->Authorize($email, $password);
        if($result){
            $builder = new QueryBuilder();
            $builder::Table("ClMiliLemonadeUsers")->Where("email", "=", $email)->Delete();
        }else{
            return false;
        }
    }

    public function PassForgetMail(bool $is_debug = false, string $title,string $user_email, string $from_email, string $user_name, string $from_name, bool $givetemplate = false, string $template = "", bool $is_html = true)
    {
        $creator = new MailCreation();
        $creator->MailSetting($from_email, $from_name, $user_email, $user_name);
        $settings = FileReader::SettingGetter();
        $token = UUIDFactory::generate();
        QueryBuilder::Table("ClMiliLemonadePassReset")->Insert(["Email" => $user_email, "Token" => $token]);
        if(!array_key_exists("RESETURL", $settings) || !array_key_exists("APPNAME", $settings) || !array_key_exists("RESETLIMIT", $settings)){
            return false;
        }
        if($givetemplate){
            $template = str_replace("{{URL}}", $settings["RESETURL"]."?t=".$token, $template);
            $template = str_replace("{{TIME}}", $settings["RESETLIMIT"], $template);
            $template = str_replace("{{APPNAME}}", $settings["APPNAME"], $template);
            $creator->MailCreation($title, $template, $is_html);
        }else{
            if($is_debug){
                $default_template = file_get_contents("Templates/PassResetDebug.html");
                $default_template = str_replace("{{URL}}", $settings["RESETURL"]."?t=".$token, $default_template);
                $default_template = str_replace("{{TOKEN}}", $token, $default_template);
                $default_template = str_replace("{{TIME}}", $settings["RESETLIMIT"], $default_template);
                $default_template = str_replace("{{APPNAME}}", $settings["APPNAME"], $default_template);
                $creator->MailCreation($title, $default_template, true);
            }else{
                $default_template = file_get_contents("Templates/PassResetTemplate.html");
                $default_template = str_replace("{{URL}}", $settings["RESETURL"]."?t=".$token, $default_template);
                $default_template = str_replace("{{TIME}}", $settings["RESETLIMIT"], $default_template);
                $default_template = str_replace("{{APPNAME}}", $settings["APPNAME"], $default_template);
                $creator->MailCreation($title, $default_template, true);
            }
        }
    }

    public function PassReset(bool $mode=false, string $token, bool $password_check_level = true, string $new_password1="", string $new_password2="")
    {
        $checker = QueryBuilder::Table("ClMiliLemonadePassReset")->Where("Token", "=", $token);
        $data = $checker->Fetch();
        $counter = $checker->CountRow();
        if($counter == 1){
            if($mode){
                return true;
            }else{
                if($password_check_level){
                    if($new_password1 == $new_password2 && trim($new_password2) != ""){
                        QueryBuilder::Table("ClMiliLemonadeUsers")->Where("Email", "=", $data["Email"])->Update(["Password" => password_hash($new_password1, PASSWORD_DEFAULT)]);
                        QueryBuilder::Table("ClMiliLemonadeUsers")->Where("Email", "=", $data["Email"])->Delete();
                    }else{
                        return false;
                    }
                }else{
                    if(trim($new_password1) != ""){
                        QueryBuilder::Table("ClMiliLemonadeUsers")->Where("Email", "=", $data["Email"])->Update(["Password" => password_hash($new_password1, PASSWORD_DEFAULT)]);
                        QueryBuilder::Table("ClMiliLemonadeUsers")->Where("Email", "=", $data["Email"])->Delete();
                    }else{
                        return false;
                    }
                }
            }
        }
    }
}