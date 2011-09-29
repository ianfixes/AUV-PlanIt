<?php

require_once("cRequest.php");
require_once("cRecordTable.php");
include("dbinit.php");


//edit profiles
class profile extends cRequest
{
    //feel free to use $this->queryVars;

    public function return_response()
    {
        if (isset($this->queryVars->subaction) && "Show" != $this->queryVars->subaction)
        {
            //behaviors that don't require the output table
            if ("Add"  == $this->queryVars->subaction) 
                return $this->add_edit();
            if ("Edit" == $this->queryVars->subaction) 
                return $this->add_edit($this->queryClean->profile_id->int);

            global $db;
            if ("Insert" == $this->queryVars->subaction)
            {
                $name = $this->queryClean->name->sql;
                $q = "insert into profile (name, last_used, enabled) values('$name', now(), 1)";
            }
            if ("Update" == $this->queryVars->subaction)
            {
                $pid = $this->queryClean->profile_id->int;
                $name = $this->queryClean->name->sql;
                $q = "update profile set name='$name', last_used=now() where profile_id = $pid";
            }
            if ("Delete" == $this->queryVars->subaction)
            {
                $pid = $this->queryClean->profile_id->int;
                $q = "update profile set enabled = 0 where profile_id = $pid";
            }
            $db->query($q);
        }

        //default behavior is to display
        $p = new cProfileTable;
        $p->setProfileRequest($this);
        return $p->Render(); // . "<br>" . print_r($this->queryVars->raw(), true) . "<br>$q";
    }

    public function ajax_subactions()
    {
        return array("Show", "Add", "Edit", "Insert", "Update", "Delete");
    }

    public function subaction_arglists()
    {
        return array(
            "Show"   => array(),
            "Add"    => array(),
            "Edit"   => array("profile_id"),
            "Insert" => array("name"),
            "Update" => array("profile_id", "name"),
            "Delete" => array("profile_id")
        );
    }

    public function default_subaction()
    {
        return "Show";
    }

    public function add_edit($profile_id = NULL)
    {
        global $dbo;

        $ret = "";

        //if we have a profile id, add that field and include default values
        //if not, blanks
        if (NULL == $profile_id)
        {
            $profile_val = "";
            $name_val = "";
            $okbutton = "
        <button onclick='{$this->subaction_call("Insert")}'>Add</button>
        ";
            $title = "<h2>Add Profile</h2>";
        }
        else
        {
            $r = $dbo->profile->ID($profile_id);
            $profile_val = "
         <input type='hidden' name='profile_id' value='$profile_id' />";
            $name_val = "value='{$r->name}'";
            $okbutton = "
        <button onclick='{$this->subaction_call("Update")}'>Update</button>
        ";
            $title = "<h2>Edit Profile</h2>";
        }

        $ret .= "
        $title
        <form id='profileform'>
         $profile_val
         Name <input type='text' size='20' name='name' $name_val />
        </form>
        <br /><br />
        ";

        $ret .= "$okbutton
        <button onclick='{$this->subaction_call("Show")}'>Cancel</button>
        ";
      
        //$ret .= print_r($this->queryVars->raw(), true);

        return $ret;
    }
    
}

//class for profile records
class cProfileTable extends cRecordTable
{
    protected $profileRequest = NULL;

    protected function init()
    {
        $this->profileRequest = NULL;
        $this->recordtype = "profile";
    }
 
    //get a profile request object... we need this for links
    public function setProfileRequest($obj)
    {
        $this->profileRequest = $obj;
    }

    //column names array: name => width
    protected function columns()
    {
        return array("Name" => "*",
                     "Last Used" => "20%",
                     "Actions" => "20%");
    }

    //fill the datacache with records
    protected function cacheData()
    {
        $dbo = self::DBO();
        $this->datacache = array();

        foreach ($dbo->profile->Records(array("enabled" => "<>0"), 
                                        array("last_used" => "desc")) as $r)
        {
            $popup = "window.open('panel_settings.php?profile_id={$r->profile_id}',"
                   . "'AUVPlanIt_profile_{$r->profile_id}',"
                   . "'status=0,toolbar=0,location=0,directories=0,"
                   . "resizable=1,scrollbars=1,height=500,width=350');";

            $rec = array("Name" => $r->name, 
                         "Last Used" => $r->last_used, 
                         "Actions" => "<a href='#' onclick=\"$popup\">Use Profile</a> (popup)",
                         //non-display fields that we need
                         "profile_id" => $r->profile_id);
            $this->datacache[] = $rec;
        }
        
    }

    //whether to darken a column
    protected function darken($name) 
    {
        return false;
    }
   
    //links to various actions
    protected function linkAdd()
    {
        return $this->profileRequest->subaction_call("Add");
    }

    protected function linkDelete($record)
    {
        return $this->profileRequest->subaction_call("Delete", array($record["profile_id"]));
    }

    protected function linkEdit($record)
    {
        return $this->profileRequest->subaction_call("Edit", array($record["profile_id"]));
    }

    //can we perform various actions?
    protected function canDelete($record)
    {
        return true;
    }

    protected function canEdit($record)
    {
        return true;
    }



}

?>
