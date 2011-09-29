<?php

require_once("cRequest.php");
require_once("cRecordTable.php");
require_once("utils/cFancyForms.php");
include("dbinit.php");


//for editing mission primitives
class mission_primitive extends cRequest
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
                return $this->add_edit($this->queryClean->mission_primitive_id->int);

            global $db;
            if ("Insert" == $this->queryVars->subaction)
            {
                $name = $this->queryClean->name->sql;
                $prid = $this->queryClean->primitive_id->int;
                $rank = $this->queryClean->rank->int;
                $mid =  $_COOKIE["stored_mission_id"];
                $q =  "insert into mission_primitive (mission_id, primitive_id, name, rank) ";
                $q .= "values($mid, $prid, '$name', $rank)";
            }
            if ("Update" == $this->queryVars->subaction)
            {
                $mpid = $this->queryClean->mission_primitive_id->int;
                $name = $this->queryClean->name->sql;
                $prid = $this->queryClean->primitive_id->int;
                $rank = $this->queryClean->rank->int;
                $q = "update mission_primitive set name='$name', rank=$rank, primitive_id=$prid ";
                $q .= "where mission_primitive_id = $mpid";
            }
            if ("Delete" == $this->queryVars->subaction)
            {
                $mpid = $this->queryClean->mission_primitive_id->int;
                $q = "delete from plan_param where mission_primitive_id = $mpid";
                $db->query($q);
                $q = "delete from mission_primitive where mission_primitive_id = $mpid";
            }
            $db->query($q);
        }

        //default behavior is to display
        $p = new cMissionPrimitiveTable;
        $p->setMissionPrimitiveRequest($this);
        return $p->Render(); // . "<br>" . print_r($this->queryVars->raw(), true) . "<br>$q";
    }

    public function ajax_subactions()
    {
        return array("Show", "Add", "Edit", "Insert", "Update", "Delete");
    }

    public function subaction_arglists()
    {
        return array(
            "Show"   => array("mission_id"),
            "Add"    => array("mission_id"),
            "Edit"   => array("mission_primitive_id"),
            "Insert" => array("mission_id", "name"),
            "Update" => array("mission_primitive_id", "name"),
            "Delete" => array("mission_primitive_id")
        );
    }

    public function default_subaction()
    {
        return "Show";
    }

    public function default_subaction_args()
    {
        return array(@$_COOKIE["stored_mission_id"]);
    }

    public function add_edit($mission_primitive_id = NULL)
    {
        global $dbo;

        $ret = "";

        //if we have a mission_primitive id, add that field and include default values
        //if not, blanks
        $prim_inputid = "primitivesel";
        $prim_key = "primitive_id";
        $prim_rs = $dbo->primitive;
        $field = "name";
        if (NULL == $mission_primitive_id)
        {
            $mission_primitive_val = "";
            $name_val = "";
            $rank_val = "";
            $okbutton = "
        <button onclick='{$this->subaction_call("Insert")}'>Add</button>
        ";
            $title = "<h2>Add Mission Component</h2>";
            $pr = cFancyForms::DatabaseComboBox($prim_rs, $prim_key, $field, $prim_inputid, $prim_key);
        }
        else
        {
            $r = $dbo->mission_primitive->ID($mission_primitive_id);
            $mission_primitive_val = "
         <input type='hidden' name='mission_primitive_id' value='$mission_primitive_id' />";
            $name_val = "value='{$r->name}'";
            $rank_val = "value='{$r->rank}'";
            $prim_val = $r->primitive_id;
            $okbutton = "
        <button onclick='{$this->subaction_call("Update")}'>Update</button>
        ";
            $title = "<h2>Edit Mission Component</h2>";
            $pr = cFancyForms::DatabaseComboBox($prim_rs, $prim_key, $field, $prim_inputid, $prim_key, $prim_val);
        }

        $ret .= "
        $title
        <form id='mission_primitiveform'>
         $mission_primitive_val
         Name <input type='text' size='20' name='name' $name_val />
	 <br />
         Rank <input type='text' size='20' name='rank' $rank_val />
         <br />
         Component $pr
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

//class for mission_primitive records
class cMissionPrimitiveTable extends cRecordTable
{
    protected $mission_primitiveRequest = NULL;

    protected function init()
    {
        $this->mission_primitiveRequest = NULL;
        $this->recordtype = "mission_primitive";
    }
 
    //get a mission_primitive request object... we need this for links
    public function setMissionPrimitiveRequest($obj)
    {
        $this->mission_primitiveRequest = $obj;
    }

    //column names array: name => width
    protected function columns()
    {
        return array("Rank" => "5%",
                     "Component" => "20%",
                     "Name" => "*");
    }

    //fill the datacache with records
    protected function cacheData()
    {
        $dbo = self::DBO();
        $this->datacache = array();


        foreach ($dbo->mission_primitive->Records(array("mission_id" => "={$_COOKIE["stored_mission_id"]}"), 
                                        array("rank" => "asc")) as $r)
        {
            $rec = array("Name" => $r->name, 
                         "Rank" => $r->rank,
                         "Component" => $dbo->primitive->name->Of($r->primitive_id), 
                         //non-display fields that we need
                         "mission_primitive_id" => $r->mission_primitive_id
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
        return $this->mission_primitiveRequest->subaction_call("Add");
    }

    protected function linkDelete($record)
    {
        return $this->mission_primitiveRequest->subaction_call("Delete", array($record["mission_primitive_id"]));
    }

    protected function linkEdit($record)
    {
        return $this->mission_primitiveRequest->subaction_call("Edit", array($record["mission_primitive_id"]));
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
