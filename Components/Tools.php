<?php
namespace Clsk\Lemonade;

class Tools
{
    public function PasswordValid(string $pass, int $min = 8, int $max = 40, array $require = ["upper", "lower", "numeric", "mark"], array $allow_mark = [",", ".", "?", "!"])
    {
        $flag = false;
        $regex = true;
        
        if($min == 0){
            $min = 8;
        }

        if($max <= $min || $max >= 2){
            $max = 40;
        }

        if(count($require) == 0){
            $require = ["upper", "lower", "numeric", "mark"];
        }

        if(count($allow_mark) == 0){
            $allow_mark = [",", ".", "?", "!"];
        }

        if(in_array("regex", $allow_mark) && count($allow_mark) == 2){
            $regex = true;
        }

        if(strlen($pass) >= $min && strlen($pass) <= $max){
            $flag = true;
        }else{
            $flag = false;
        }

        foreach($require as $r){
            if($r == "upper"){
                if(preg_match('/[A-Z]/', $pass)){
                    $flag = true;
                }else{
                    $flag = false;
                }
            }

            if($r == "lower"){
                if(preg_match('/[a-z]/', $pass)){
                    $flag = true;
                }else{
                    $flag = false;
                }
            }

            if($r == "numeric"){
                if (preg_match("/[0-9]/", $pass)) {
                    $flag = true;
                } else {
                    $flag = false;
                }
            }

            if($r == "mark"){
                if($regex){
                    if(isset($allow_mark[1]) && is_string($allow_mark[1]) && $allow_mark[1] != "" && $allow_mark[1] != "[]" && $allow_mark[1] != "[" && $allow_mark[1] != "]" && substr($allow_mark[1], 0, 1) == "[" && substr($allow_mark[1], strlen($allow_mark[1]) -1) == "]"){
                        $reg = '/'.$allow_mark[1].'/';
                        if (preg_match($reg, $pass)) {
                            $flag = true;
                        } else {
                            $flag = false;
                        }
                    }else{
                        $reg = '/[!#<>:;&~@%+$"\'\*\^\(\)\[\]\|\/\.,_-]/';
                        if (preg_match($reg, $pass)) {
                            $flag = true;
                        } else {
                            $flag = false;
                        }
                    }
                }else{
                    foreach($allow_mark as $am){
                        if(strpos($pass,$am) !== false){
                            $flag = true;
                        }else{
                            $flag = false;
                            break;
                        }
                    }
                }
            }

            return $flag;
        }
    }

    public function EmailValid(string $email)
    {
        if(filter_var($email, FILTER_VALIDATE_EMAIL)){
            return true;
        }else{
            return false;
        }
    }
}