<?php

require_once("cRequest.php");
require_once("cRecordTable.php");
require_once("utils/cFancyForms.php");
include("dbinit.php");


//edit profile settings
class profile_settings extends cRequest
{
    //feel free to use $this->queryVars;

    public static function setting_default($setting_id)
    {
        switch ($setting_id)
        {
            case 1: return "/img/reticle/crosshair-circle-red.png";
            case 2: return "FF0000FF";
            case 3: return 2;
        }
    }

    //make sure user settings are present and sane
    public function check_settings()
    {
        global $db;
        global $dbo;

        $debug = "Going for it\n";

        $p = $_COOKIE['stored_profile_id'];
        foreach ($dbo->setting->Records() as $r)
        {
            $s = $r->setting_id;
            $q = "select count(*) from profile_setting where profile_id=$p and setting_id=$s";
            
            $debug .= "$q\n\n";

            if (0 == $db->getOne($q))
            {
                $v = self::setting_default($s);
                $q  = "insert into profile_setting(profile_id, setting_id, value) values ($p, $s, '$v')";
                $debug .= "$q\n\n";
                $db->query($q);
            }
        }

        return $debug;

    }

    public function return_response()
    {
        if (isset($this->queryVars->subaction) && "Show" != $this->queryVars->subaction)
        {
            global $db;
            global $dbo;
            if ("Update" == $this->queryVars->subaction)
            {
                $p = $_COOKIE['stored_profile_id'];
                foreach ($dbo->setting->Records() as $r)
                {
                    $s = $r->setting_id;
                    $val = $this->queryClean->__get("setting_$s")->sql;
                    $db->query("update profile_setting set value='$val' where profile_id=$p and setting_id=$s");
                }
            }
        }

        //default behavior is to display
        return $this->add_edit();
    }

    public function ajax_subactions()
    {
        return array("Show", "Update");
    }

    public function subaction_arglists()
    {
        return array(
            "Show"   => array("profile_id"),
            "Update" => array("profile_id"),
        );
    }

    public function default_subaction()
    {
        return "Show";
    }

    public function default_subaction_args()
    {
        return array(@$_COOKIE["stored_profile_id"]);
    }

    public function add_edit()
    {
        global $db;
        global $dbo;

        
        $p = $_COOKIE['stored_profile_id'];
        $ret = "";
        
        $debug = $this->check_settings();
        //$ret .= $debug;

        $ret_val = $db->getOne("select value from profile_setting where profile_id=$p and setting_id=1");
        $box_val = $db->getOne("select value from profile_setting where profile_id=$p and setting_id=2");
        $ent_val = $db->getOne("select value from profile_setting where profile_id=$p and setting_id=3");
        $lat_val = $db->getOne("select value from profile_setting where profile_id=$p and setting_id=4");
        $lon_val = $db->getOne("select value from profile_setting where profile_id=$p and setting_id=5");
        $cmd_val = $db->getOne("select value from profile_setting where profile_id=$p and setting_id=6");
        
        $filesystem_path = $_SERVER['DOCUMENT_ROOT'] . "/img/reticle"; 
        $web_path = "/img/reticle/";
        $icon_inputid = "reticle_chooser";
        $icon_inputname = "setting_1";
        $rb = cFancyForms::RadioImageSelector($filesystem_path, $web_path, $icon_inputid, $icon_inputname, $ret_val);
       
        $okbutton = "
        <button onclick='{$this->subaction_call("Update")}'>Update</button>
        ";


        $ret .= "
        <form id='profile_settingsform'>
        <h2>Mission Export</h2>
         Origin Latitude <input type='text' size='20' name='setting_4' value='$lat_val' />
	 <br />
         Origin Longitude <input type='text' size='20' name='setting_5' value='$lon_val' />
	 <br />
         Mission Copy Command <input type='text' size='20' name='setting_6' value='$cmd_val' /> 
          <br />
          ('#' will be replaced by the exported filename)
	 <br />
        <h2>GUI</h2>
         Box Color <input type='text' size='20' name='setting_2' value='$box_val' />
	 <br />
         Entity History (minutes) <input type='text' size='20' name='setting_3' value='$ent_val' /> (max 120)
	 <br />
         <br />
         <h3>Reticle</h3>
         $rb
        </form>
        <br /><br />
        ";

        $ret .= "$okbutton
        <button onclick='{$this->subaction_call("Show")}'>Cancel</button>
        ";
      

        return $ret;
    }
    
}

?>
