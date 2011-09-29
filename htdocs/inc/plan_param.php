<?php

require_once("cRequest.php");
require_once("cRecordTable.php");
require_once("inc/cPrimitive.php");
include("dbinit.php");


//this class handles stuff for building plans
class plan_param extends cRequest
{
    public function ajax_subactions()
    {
        return array("Show", "Focus", "Update", "Reset", "Done");
    }

    public function subaction_arglists()
    {
        return array(
            "Show"   => array(),
            "Focus"  => array("rank_id"),
            "Update" => array("mission_primitive_id"),
            "Reset"  => array("mission_primitive_id"),
            "Done"   => array()
        );
    }

    public function default_subaction()
    {
        return "Show";
    }

    public function default_subaction_args()
    {
        return array();
    }

    //feel free to use $this->queryVars;
    public function return_response()
    {
        global $db;
        global $dbo;
        $plid = $_COOKIE["stored_plan_id"];

        $debug = "";
        if (isset($this->queryVars->subaction) && "Show" != $this->queryVars->subaction)
        {

            if ("Done" == $this->queryVars->subaction)
            {
                $db->query("update plan set editing_rank=null where plan_id=$plid");
            }
            if ("Focus" == $this->queryVars->subaction)
            {
                $rank = $this->queryClean->rank_id->int;
                $q =  "update plan set editing_rank = $rank where plan_id = $plid";
                $db->query($q);
            }
            if ("Update" == $this->queryVars->subaction)
            {
                //delete existing values and re-enter

                $mpid = $this->queryClean->mission_primitive_id->int;
                $db->query("delete from plan_param where plan_id=$plid and mission_primitive_id=$mpid");
                //our HTML form is sending variables named as "param_x" where x = param_id
                foreach ($this->queryVars->raw() as $k => $unclean)
                {
                    @list($l, $param_id) = explode("_", $k);
                    //extract param_id from form field name
                    if ("param" == $l && is_numeric($param_id))
                    {
                        $v = $this->queryClean->__get($k)->sql;
                        if ("" != $v)
                        {
                            $q = "insert into plan_param(plan_id, mission_primitive_id, param_id, value) ";
                            $q .= "values($plid, $mpid, $param_id, '$v')";
                            $db->query($q);
                        }
                    }
                }
                //$debug = print_r($this->queryVars->raw(), true);

                //after we update, change focus... pick the next higher un-entered, then try lower
                $rank = $dbo->plan->editing_rank->Of($plid);

                $rnext = $db->getOne("select min(mission_primitive_rank) from plan_data 
                                      where plan_id=$plid and mission_primitive_rank > $rank and value is null");

                $rprev = $db->getOne("select max(mission_primitive_rank) from plan_data 
                                      where plan_id=$plid and value is null");

                if ("" != $rnext)
                {
                    $db->query("update plan set editing_rank=$rnext where plan_id=$plid");
                }
                else if ("" != $rprev)
                {
                    $db->query("update plan set editing_rank=$rprev where plan_id=$plid");
                }
                else
                {
                    //$db->query("update plan set editing_rank=null where plan_id=$plid");
                }
                $db->query("update plan set when_updated=now() where plan_id=$plid");

            }
            if ("Reset" == $this->queryVars->subaction)
            {
                $mpid = $this->queryClean->mission_primitive_id->int;
                $q = "delete from plan_param where plan_id = $plid and mission_primitive_id = $mpid";
                $db->query($q);
                
                //jump to edit this step if we were not already on something
                if ("" == $dbo->plan->editing_rank->Of($plid))
                {
                    $current = $db->getOne("select mission_primitive_rank from plan_data 
                                            where plan_id=$plid and mission_primitive_id=$mpid");
                    $db->query("update plan set editing_rank=$current where plan_id=$plid");
                }
                $db->query("update plan set when_updated=now() where plan_id=$plid");
            }
        }

        //default behavior is to display
     
        //done button to stop showing a plna
        $done = $this->subaction_call("Done", array());
        $donebutton = "";
        if ("" != $dbo->plan->editing_rank->Of($plid))
        {
            $donebutton = "<br /><br /><button style='float:right;' onclick='$done'>Done</button>
                           <br style='clear:both;' />";
        }

        $p = new cPlanParamTable;
        $p->setPlanParamRequest($this);
        return $p->Render() . $donebutton . $debug;
    }

}

//class for plan records
class cPlanParamTable extends cRecordTable
{
    protected $planParamRequest = NULL;

    protected function init()
    {
        $this->planParamRequest = NULL;
        $this->recordtype = "parameter set";
    }
 
    //get a plan request object... we need this for links
    public function setPlanParamRequest($obj)
    {
        $this->planParamRequest = $obj;
    }

    //column names array: name => width
    protected function columns()
    {
        return array("Step" => "1%",
                     "Name" => "*",
                     "Complete" => "15%"
                     );
    }

    //fill the datacache with records
    protected function cacheData()
    {
        $db = self::DB();
        $dbo = self::DBO();
        $this->datacache = array();

        $plan_id = $_COOKIE["stored_plan_id"];

        $q = "
        select mission_primitive_rank, 
            mission_primitive_id, 
            mission_primitive_name, 
            count(value) / count(*) ratio_complete 
        from plan_data 
        where plan_id = $plan_id
        group by mission_primitive_rank,
            mission_primitive_name
        order by mission_primitive_rank asc";
        $rs = $db->query($q);

        $current = $dbo->plan->editing_rank->Of($_COOKIE["stored_plan_id"]);

        while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
        {
            if ($current == $r->mission_primitive_rank)
            {
                $namelink = $r->mission_primitive_name;
            }
            else
            {
                $href = $this->planParamRequest->subaction_call("Focus", 
                                   array($r->mission_primitive_rank));
                $namelink = "<a href=\"$href\">{$r->mission_primitive_name}</a>";
            }
            $comp = $r->ratio_complete;
            $pct = round($comp * 100, 2);
            $rec = array("Step" => $r->mission_primitive_rank,
                         "Name" => $namelink,
                         "Complete" => 1 > $comp ? "<span style='color:red'>No</span>" : "Yes",
                         //non-display fields that we need
                         "ratio_complete" => $comp,
                         "mission_primitive_id" => $r->mission_primitive_id
                         );
            $this->datacache[] = $rec;

            if ($current == $r->mission_primitive_rank)
            {
                $q2 = "
                select param_id, param_name, value
                from plan_data
                where plan_id = $plan_id
                  and mission_primitive_rank = $current
                ";


                $wr = "";
                $wr .= "<form id='plan_paramform'>\n";

                $wr .= "<table border='0'>\n";
                $rs2 = $db->query($q2);
                while ($r2 = $rs2->fetchRow(MDB2_FETCHMODE_OBJECT))
                {
                    $wr .= "<tr>\n";
                    $wr .= "<td>\n{$r2->param_name}</td>\n";
                    $wr .= "<td>\n" . cPrimitive::param_form($r2->param_id, $r2->value) . "</td>\n";
                    $wr .= "</tr>\n";
                }
                $wr .= "</table>\n";
                $update = $this->planParamRequest->subaction_call("Update", array($r->mission_primitive_id));
                $wr .= "</form>";
                $wr .= "<button onclick='$update'>Update</button>";
                 
                $this->datacache[] = array("__wholerow__" => $wr);
                
            }
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
        return "";
    }

    protected function linkDelete($record)
    {
        return $this->planParamRequest->subaction_call("Reset", array($record["mission_primitive_id"]));
    }

    //can we perform various actions?
    protected function canDelete($record)
    {
        return isset($record["ratio_complete"]) && 0 < $record["ratio_complete"];
    }

    protected function canEdit($record)
    {
        return false;
    }




}

?>
