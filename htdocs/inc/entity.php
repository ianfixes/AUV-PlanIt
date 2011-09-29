<?php

require_once("cRequest.php");
require_once("cRecordTable.php");
require_once("utils/cFancyForms.php");
include("dbinit.php");


//table for editing entities
class entity extends cRequest
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
                return $this->add_edit($this->queryClean->entity_id->int);

            global $db;
            if ("Insert" == $this->queryVars->subaction)
            {
                $name = $this->queryClean->name->sql;
                $amid = $this->queryClean->altitudemode_id->int;
                $iurl = $this->queryClean->icon_img_url->sql;
                $q = "insert into entity (name, altitudemode_id, icon_img_url) values('$name', $amid, '$iurl')";
            }
            if ("Update" == $this->queryVars->subaction)
            {
                $eid = $this->queryClean->entity_id->int;
                $name = $this->queryClean->name->sql;
                $amid = $this->queryClean->altitudemode_id->int;
                $iurl = $this->queryClean->icon_img_url->sql;
                $q = "update entity set name='$name', altitudemode_id=$amid, icon_img_url='$iurl' ";
                $q .= "where entity_id = $eid";
            }
            if ("Delete" == $this->queryVars->subaction)
            {
                $eid = $this->queryClean->entity_id->int;
                $q = "delete from entity_location where entity_id = $eid";
                $db->query($q);
                $q = "delete from entity where entity_id = $eid";
            }
            $db->query($q);
        }

        //default behavior is to display
        $p = new cEntityTable;
        $p->setEntityRequest($this);
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
            "Edit"   => array("entity_id"),
            "Insert" => array("name"),
            "Update" => array("entity_id", "name"),
            "Delete" => array("entity_id")
        );
    }

    public function default_subaction()
    {
        return "Show";
    }

    public function add_edit($entity_id = NULL)
    {
        global $dbo;

        $ret = "";

        //if we have a entity id, add that field and include default values
        //if not, blanks
        $web_path = "/img/icon/";
        $icon_inputid = "entity_icon";
        $icon_inputname = "icon_img_url";
        $alt_inputid = "altitudemode";
        $alt_inputname = "altitudemode_id";
        $amrs = $dbo->altitudemode;
        $pkey = "altitudemode_id";
        $field = "name";
        $filesystem_path = $_SERVER['DOCUMENT_ROOT'] . "/img/icon"; 
        if (NULL == $entity_id)
        {
            $entity_val = "";
            $name_val = "";
            $okbutton = "
        <button onclick='{$this->subaction_call("Insert")}'>Add</button>
        ";
            $title = "<h2>Add Entity</h2>";
            $rb = cFancyForms::RadioImageSelector($filesystem_path, $web_path, $icon_inputid, $icon_inputname);
            $ab = cFancyForms::DatabaseComboBox($amrs, $pkey, $field, $alt_inputid, $alt_inputname);
        }
        else
        {
            $r = $dbo->entity->ID($entity_id);
            $entity_val = "
         <input type='hidden' name='entity_id' value='$entity_id' />";
            $name_val = "value='{$r->name}'";
            $icon_val = $r->icon_img_url;
            $amid_val = $r->altitudemode_id;
            $okbutton = "
        <button onclick='{$this->subaction_call("Update")}'>Update</button>
        ";
            $title = "<h2>Edit Entity</h2>";
            $rb = cFancyForms::RadioImageSelector($filesystem_path, $web_path, $icon_inputid, $icon_inputname, $icon_val);
            $ab = cFancyForms::DatabaseComboBox($amrs, $pkey, $field, $alt_inputid, $alt_inputname, $amid_val);
        }

        $ret .= "
        $title
        <form id='entityform'>
         $entity_val
         Name <input type='text' size='20' name='name' $name_val />
         <br />
         Altitude Mode $ab
         $rb
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

//class for entity records
class cEntityTable extends cRecordTable
{
    protected $entityRequest = NULL;

    protected function init()
    {
        $this->entityRequest = NULL;
        $this->recordtype = "entity";
    }
 
    //get a entity request object... we need this for links
    public function setEntityRequest($obj)
    {
        $this->entityRequest = $obj;
    }

    //column names array: name => width
    protected function columns()
    {
        return array("Name" => "*",
                     "Icon" => "5%",
                     "Altitude Mode" => "20%",
                     "Last Update" => "20%");
    }

    //fill the datacache with records
    protected function cacheData()
    {
        $dbo = self::DBO();
        $this->datacache = array();

        foreach ($dbo->entity->Records(array(), 
                                        array("name" => "asc")) as $r)
        {
            $lastupdate = $dbo->entity_location->updated->First(
                             array("entity_id" => "={$r->entity_id}"),
                             array("updated" => "desc"));
            $lu = $lastupdate == "" ? "Never" : $lastupdate;
            $lu_html = "<a href='/entity_location.php?entity_id={$r->entity_id}'>$lu</a>";
            $rec = array("Name" => $r->name, 
                         "Icon" => "<img src='{$r->icon_img_url}' style='height:32px;' alt='{$r->name} icon'/>",
                         "Altitude Mode" => $dbo->altitudemode->name->Of($r->altitudemode_id), 
                         "Last Update" => $lu_html,
                         //non-display fields that we need
                         "entity_id" => $r->entity_id,
                         "altitudemode_id" => $r->altitudemode_id
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
        return $this->entityRequest->subaction_call("Add");
    }

    protected function linkDelete($record)
    {
        return $this->entityRequest->subaction_call("Delete", array($record["entity_id"]));
    }

    protected function linkEdit($record)
    {
        return $this->entityRequest->subaction_call("Edit", array($record["entity_id"]));
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
