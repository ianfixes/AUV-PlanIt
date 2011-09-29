<?php

require_once("cRequest.php");
include("dbinit.php");

//fetch the state data associated with a particular user profile
class livestate extends cRequest
{
    //feel free to use $this->queryVars;

    public function return_response()
    {
        global $db;

        $pid = $_COOKIE["stored_profile_id"];

        $q = "
        select state.name, 
            profile_state.value 
        from profile_state 
            inner join state using (state_id)
        where profile_id = $pid";

        $rs = $db->query($q);

        $ret = array();
        while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
        {
            $ret[$r->name] = $r->value;
        }
        
        return $ret; 
    }

    public function ajax_subactions()
    {
        return array();
    }

    public function subaction_arglists()
    {
        return array();
    }

    public function default_subaction()
    {
        return "";
    }
    
}

?>
