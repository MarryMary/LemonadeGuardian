<?php
namespace Clsk\Lemonade;

use Clsk\Elena\Databases\QueryBuilder;
use Clsk\Elena\Tools\UUIDFactory;
use Clsk\Elena\Tools\FileReader;

class Register
{
    public function PreRegister(string $email, string $token)
    {
        $token = UUIDFactory::generate();
        $builder = new QueryBuilder();
        $insert_array = [
            "Email" => $email,
            "Token" => $token
        ];
        if($this->Check($email, $token) == "pre"){
            $insert_to_pre = $builder::Table("ClMiliLemonadePreUsers")->Insert($insert_array, "assoc");
            return $insert_to_pre;
        }else{
            return false;
        }
    }

    public function Register(string $email, string $token, string $password, string $username)
    {
        $builder = new QueryBuilder();
        if(trim($email) != "" && trim($token) != "" && trim($password) != "" && trim($username) != "" && $this->Check($email, $token) == "main"){
            $insert_array = [
                "Email" => $email,
                "Password" => $password,
                "UserName" => $username,
                "UserPictPath" => "Resource/default.png"
            ];
            $insert_to_main = $builder::Table("ClMiliLemonadeUsers")->Insert($insert_array, "assoc");
            return $insert_to_main;
        }else if(trim($email) != "" && trim($token) != "" && $this->Check($email, $token) == "pre"){
            $token = UUIDFactory::generate();
            $insert_array = [
                "Email" => $email,
                "Token" => $token
            ];
            $insert_to_pre = $builder::Table("ClMiliLemonadePreUsers")->Insert($insert_array, "assoc");
            if($insert_to_pre){
                
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function Check(string $email, string $token)
    {
        $builder = new QueryBuilder();
        $check_on_main = $builder::Table("ClMiliLemonadeUsers")->Where("Email", "=", $email)->CountRow();
        $check_on_pre = $builder::Table("ClMiliLemonadePreUsers")->Where("Email", "=", $email)->And("Token", "=", $token)->CountRow();
        if($check_on_main == 0 && $check_on_pre == 1){
            return "pre";
        }else if($check_on_main == 0 && $check_on_pre == 0){
            return "main";
        }else{
            return false;
        }
    }
}