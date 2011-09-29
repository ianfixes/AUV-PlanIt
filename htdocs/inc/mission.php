<?php

require_once("cRequest.php");
require_once("cRecordTable.php");
include("dbinit.php");


//for displaying and editing missions
class mission extends cRequest
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
                return $this->add_edit($this->queryClean->mission_id->int);

            global $db;
            if ("Insert" == $this->queryVars->subaction)
            {
                $name = $this->queryClean->name->sql;
                $q = "insert into mission (name) values('$name')";
            }
            if ("Update" == $this->queryVars->subaction)
            {
                $pid = $this->queryClean->mission_id->int;
                $name = $this->queryClean->name->sql;
                $q = "update mission set name='$name' where mission_id = $pid";
            }
            if ("Delete" == $this->queryVars->subaction)
            {
                $pid = $this->queryClean->mission_id->int;
                $q = "delete from mission where mission_id = $pid";
            }
            $db->query($q);
        }

        //default behavior is to display
        $p = new cMissionTable;
        $p->setMissionRequest($this);
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
            "Edit"   => array("mission_id"),
            "Insert" => array("name"),
            "Update" => array("mission_id", "name"),
            "Delete" => array("mission_id")
        );
    }

    public function default_subaction()
    {
        return "Show";
    }

    public function add_edit($mission_id = NULL)
    {
        global $dbo;

        $ret = "";

        //if we have a mission id, add that field and include default values
        //if not, blanks
        if (NULL == $mission_id)
        {
            $mission_val = "";
            $name_val = "";
            $okbutton = "
        <button onclick='{$this->subaction_call("Insert")}'>Add</button>
        ";
            $title = "<h2>Add Mission</h2>";
        }
        else
        {
            $r = $dbo->mission->ID($mission_id);
            $mission_val = "
         <input type='hidden' name='mission_id' value='$mission_id' />";
            $name_val = "value='{$r->name}'";
            $okbutton = "
        <button onclick='{$this->subaction_call("Update")}'>Update</button>
        ";
            $title = "<h2>Edit Mission</h2>";
        }

        $ret .= "
        $title
        <form id='missionform'>
         $mission_val
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

//class for mission records
class cMissionTable extends cRecordTable
{
    protected $missionRequest = NULL;

    protected function init()
    {
        $this->missionRequest = NULL;
        $this->recordtype = "mission";
    }
 
    //get a mission request object... we need this for links
    public function setMissionRequest($obj)
    {
        $this->missionRequest = $obj;
    }

    //column names array: name => width
    protected function columns()
    {
        return array("Name" => "*",
                     "Components" => "20%",
                     "Times Used" => "20%");
    }

    //fill the datacache with records
    protected function cacheData()
    {
        $dbo = self::DBO();
        $this->datacache = array();

        foreach ($dbo->mission->Records(array(), 
                                        array("name" => "asc")) as $r)
        {
            $mission_id = $r->mission_id;
            
            $m_prim = " &ndash; <a href='mission_primitives.php?mission_id={$r->mission_id}'>"
                    . "Redefine</a>";

            $rec = array("Name" => $r->name, 
                         "Components" => $dbo->mission_primitive->mission_id->Count(
                                          array("mission_id" => "=$mission_id")) . $m_prim,
                         "Times Used" => $dbo->plan->mission_id->Count(
                                          array("mission_id" => "=$mission_id")),
                         //non-display fields that we need
                         "mission_id" => $mission_id);
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
        return $this->missionRequest->subaction_call("Add");
    }

    protected function linkDelete($record)
    {
        return $this->missionRequest->subaction_call("Delete", array($record["mission_id"]));
    }

    protected function linkEdit($record)
    {
        return $this->missionRequest->subaction_call("Edit", array($record["mission_id"]));
    }

    //can we perform various actions?
    protected function canDelete($record)
    {
        $dbo = self::DBO();
        $mission_id = $record["mission_id"];
        return 0 == $dbo->mission_primitive->mission_id->Count(
                      array("mission_id" => "=$mission_id"))
               &&
               0 == $dbo->plan->mission_id->Count(
                      array("mission_id" => "=$mission_id"));
    }

    protected function canEdit($record)
    {
        return true;
    }



}

?>
