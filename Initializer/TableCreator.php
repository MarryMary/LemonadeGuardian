<?php
use Clsk\Elena\Databases\Migration;
use Clsk\Elena\Databases\Ignition;

class TableCreator extends Migration{
    
    public function Execution()
    {
        Migration::Create("ClMiliLemonadeUsers", function(Ignition $ignition) {
            $ignition->AutoIncrement("Id");
            $ignition->VarChar("Email", 256);
            $ignition->VarChar("Password", 256);
            $ignition->VarChar("UserName");
            $ignition->Text("UserPictPath");
            $ignition->Int("Flag", 1);
        });

        Migration::Create("ClMiliLemonadePreUsers", function(Ignition $ignition) {
            $ignition->AutoIncrement("Id");
            $ignition->VarChar("Email", 256);
            $ignition->VarChar("Token");
            $ignition->Created_At();
        });

        Migration::Create("ClMiliLemonadePassReset", function(Ignition $ignition) {
            $ignition->AutoIncrement("Id");
            $ignition->VarChar("Email", 256);
            $ignition->VarChar("Token");
            $ignition->Created_At();
        });
    }

    public function Rollback()
    {
        Migration::Reverse("ClMiliLemonadeUsers");
        Migration::Reverse("ClMiliLemonadePreUsers");
        Migration::Reverse("ClMiliLemonadePassReset");
    }
}