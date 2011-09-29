<?php

require_once("cRequest.php");
require_once("cRecordTable.php");
require_once("utils/cFancyForms.php");
include("dbinit.php");


//for editing the plan list
class plan extends cRequest
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
                return $this->add_edit($this->queryClean->plan_id->int);

            global $db;
            if ("Insert" == $this->queryVars->subaction)
            {
                $name = $this->queryClean->name->sql;
                $mid = $this->queryClean->mission_id->int;
                $prid =  $_COOKIE["stored_profile_id"];
                $q =  "insert into plan (profile_id, mission_id, name, when_updated, last_exported, hidden, editing_rank) ";
                $q .= "values($prid, $mid, '$name', now(), NULL, 0, NULL)";
            }
            if ("Update" == $this->queryVars->subaction)
            {
                $pid = $this->queryClean->plan_id->int;
                $name = $this->queryClean->name->sql;
                $mid = $this->queryClean->mission_id->int;
                $q = "update plan set name='$name', mission_id=$mid ";
                $q .= "where plan_id = $pid";
            }
            if ("Delete" == $this->queryVars->subaction)
            {
                $pid = $this->queryClean->plan_id->int;
                $q = "update plan set hidden=1 where plan_id = $pid";
            }
            $db->query($q);
        }

        //default behavior is to display
        $p = new cPlanTable;
        $p->setPlanRequest($this);
        return $p->Render(); // . "<br>" . print_r($this->queryVars->raw(), true) . "<br>$q";
    }

    public function ajax_subactions()
    {
        return array("Show", "Add", "Edit", "Insert", "Update", "Delete");
    }

    public function subaction_arglists()
    {
        return array(
            "Show"   => array("profile_id"),
            "Add"    => array("profile_id"),
            "Edit"   => array("plan_id"),
            "Insert" => array("profile_id"),
            "Update" => array("plan_id"),
            "Delete" => array("plan_id")
        );
    }

    public function default_subaction()
    {
        return "Show";
    }

    public function default_subaction_args()
    {
        return array($_COOKIE["stored_profile_id"]);
    }

    public function add_edit($plan_id = NULL)
    {
        global $dbo;

        $ret = "";

        //if we have a plan id, add that field and include default values
        //if not, blanks
        $miss_inputid = "missionsel";
        $miss_key = "mission_id";
        $miss_rs = $dbo->mission;
        $field = "name";
        if (NULL == $plan_id)
        {
            $plan_val = "";
            $name_val = "";
            $okbutton = "
        <button onclick='{$this->subaction_call("Insert")}'>Add</button>
        ";
            $title = "<h2>Add Mission Plan</h2>";
            $mt = cFancyForms::DatabaseComboBox($miss_rs, $miss_key, $field, $miss_inputid, $miss_key);
        }
        else
        {
            $r = $dbo->plan->ID($plan_id);
            $plan_val = "
         <input type='hidden' name='plan_id' value='$plan_id' />";
            $name_val = "value='{$r->name}'";
            $miss_val = $r->mission_id;
            $okbutton = "
        <button onclick='{$this->subaction_call("Update")}'>Update</button>
        ";
            $title = "<h2>Edit Mission Plan</h2>";
            $mt = cFancyForms::DatabaseComboBox($miss_rs, $miss_key, $field, $miss_inputid, $miss_key, $miss_val);
        }

        $ret .= "
        $title
        <form id='planform'>
         $plan_val
         Name <input type='text' size='20' name='name' $name_val />
	 <br />
         Type $mt
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

//class for plan records
class cPlanTable extends cRecordTable
{
    protected $planRequest = NULL;

    protected function init()
    {
        $this->planRequest = NULL;
        $this->recordtype = "plan";
    }
 
    //get a plan request object... we need this for links
    public function setPlanRequest($obj)
    {
        $this->planRequest = $obj;
    }

    //column names array: name => width
    protected function columns()
    {
        return array("ID" => "1%",
                     "Name" => "*",
                     "Type" => "15%",
                     "Updated" => "15%",
                     "Exported" => "15%",
                     "Progress" => "10%"
                     );
    }

    //fill the datacache with records
    protected function cacheData()
    {
        $db = self::DB();
        $dbo = self::DBO();
        $this->datacache = array();


        foreach ($dbo->plan->Records(array("profile_id" => "={$_COOKIE["stored_profile_id"]}", "hidden" => "=0"), 
                                        array("when_updated" => "desc")) as $r)
        {
            $comp = $db->getOne("select count(value) / count(*) from plan_data where plan_id = {$r->plan_id}");
            $pct = round($comp * 100, 2);
            $rec = array("ID" => $r->plan_id,
                         "Name" => $r->name, 
                         "Type" => $dbo->mission->name->Of($r->mission_id),
                         "Updated" => $r->when_updated, 
                         "Exported" => ("" == $r->last_exported ? "Never" : $r->last_exported),
                         "Progress" => "$pct% - <a href='panel_planit.php?plan_id={$r->plan_id}'>Modify</a>",
                         //non-display fields that we need
                         "plan_id" => $r->plan_id,
                         "ratio_complete" => $comp
                         );
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
        return $this->planRequest->subaction_call("Add");
    }

    protected function linkDelete($record)
    {
        return $this->planRequest->subaction_call("Delete", array($record["plan_id"]));
    }

    protected function linkEdit($record)
    {
        return $this->planRequest->subaction_call("Edit", array($record["plan_id"]));
    }

    protected function linkExport($record)
    {
        return "/panel_export.php?plan_id={$record["plan_id"]}"; 
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

    protected function canExport($record)
    {
        return 1 == $record["ratio_complete"];
    }



}

?>
