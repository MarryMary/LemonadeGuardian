<?php
namespace Clsk\Lemonade;

use Clsk\Elena\Databases\QueryBuilder;
use Clsk\Elena\Session\Session;
use Clsk\Elena\Tools\FileUploader;
use Clsk\Elena\Tools\FileReader;
use Clsk\Lemonade\Tools;

class Authorizer
{
    public function Authorize(string $email, string $pass)
    {
        $get_identify = $this->Reference($email);
        if(!is_bool($get_identify)){
            return $this->Check($pass, $get_identify["Password"]);
        }
    }

    protected function Reference(string $email)
    {
        $builder = new QueryBuilder();
        $base = $builder::Table("ClMiliLemonadeUsers")->Where("Email", "=", $email);
        $count = $base;
        $fetch = $base;
        $count = $count->CountRow();
        $fetch = $fetch->Fetch();
        if($count != 0){
            return $fetch;
        }else{
            return false;
        }
    }

    public function Update(string $old_or_new, $password, $mode="email", $new = "")
    {
        if(isset($password) && trim($password) != ""){
            if($mode == "email"){
                $validate = new Tools();
                if($validate->EmailValid($old_or_new) && $validate->EmailValid($new)){
                    $builder = new QueryBuilder();
                    if($builder::Table("ClMiliLemonadeUsers")->Where("Email", "=", $old_or_new)->CountRow() == 1 && $builder::Table("ClMiliLemonadeUsers")->Where("Email", "=", $new)->CountRow() == 0){
                        $update_array = [
                            "Email" => $new
                        ];
                        $builder::Table("ClMiliLemonadeUsers")->Where("Email", "=", $old_or_new)->Update($update_array);
                    }else{
                        return false;
                    }
                }else{
                    return false;
                }
            }else if($mode == "password"){
                $authorizer = new LemonadeCore();
                $tools = new Tools();
                if(Session::SessionReader("isAuth")){
                    $tool = new Tools();
                    $settings = FileReader::SettingGetter();
                    $min = 8;
                    $max = 40;
                    $require = ["upper", "lower", "numeric", "mark"];
                    $allow_mark = [",", ".", "?", "!"];
                    if(array_key_exists("PassMin", $settings) && is_numeric($settings["PassMin"])){
                        $min = $settings["PassMin"];
                    }
                    if(array_key_exists("PassMax", $settings) && is_numeric($settings["PassMax"])){
                        $max = $settings["PassMax"];
                    }
                    if(array_key_exists("PassRequire", $settings) && trim($settings["PassRequire"]) != ""){
                        $require = explode(",", $settings["PassRequire"]);
                    }
                    if(array_key_exists("PassAllowMark", $settings) && trim($settings["PassAllowMark"]) != ""){
                        $allow_mark = explode(",", $settings["PassAllowMark"]);
                    }
                    if($authorizer->Authorize(Session::SessionReader("email"), $password, true) && $tool->PasswordValid($password, $min, $max, $require, $allow_mark)){
                        $builder = new QueryBuilder();
                        $update_array = [
                            "Password" => password_hash($old_or_new, PASSWORD_DEFAULT)
                        ];
                        if($builder::Table("ClMiliLemonadeUsers")->Where("Email", "=", Session::SessionReader("email"))->Update($update_array)){
                            $authorizer->LogOut();
                            return true;
                        }else{
                            return false;
                        }
                    }
                }else{
                    return false;
                }
            }else if($mode == "userpict"){
                $uploader = new FileUploader();
                if($uploader->UploadCore($old_or_new, false, ["picture"])){
                    return true;
                }else{
                    return false;
                }
            }else if($mode == "username"){
                if(Session::SessionReader("isAuth")){
                    $builder = new QueryBuilder();
                    $update_array = [
                        "UserName" => $old_or_new
                    ];
                    if($builder::Table("ClMiliLemonadeUsers")->Where("Email", "=", Session::SessionReader("email"))->Update($update_array)){
                        return true;
                    }else{
                        return false;
                    }

                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    private function Check(string $password, string $saved_password)
    {
        if(password_verify($password, $saved_password)){
            return true;
        }else{
            return false;
        }
    }
}