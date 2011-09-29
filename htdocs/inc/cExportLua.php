<?php

// an example (stubbed) class for rendering a mission file

require_once("cExport.php");

class cExportExample extends cExport
{
    protected $m_ParamCache = array();

    //render a plan
    public function Render($plan_id)
    {
        $db = $this->m_DB;

        //iterate through plan's steps
        // each step has params for 1 or more behaviors; unpack
        //  foreach behavior,
        //   make up settings from params
        //   convert to string
    }

    // return the declarative part of the code
    //  where we create a set of sequential behaviors, each bound with settings
    //
    // primitive_id is which primitive
    // rank         is the rank of this primitive in the overall plan
    // params       is an array of key => value pairs indicating the settings
    // indent       is how many spaces to indent the code
    // var_prefix   is the name of the table where we'll store the output
    public static function stub_declaration($primitive_id, $rank, $params, $indent, $var_prefix)
    {
        switch ($primitive_id)
        {
            case cPrimitive::Altitude:
            case cPrimitive::Depth:
            case cPrimitive::Waypoint:
            case cPrimitive::WaypointAltitude:
            case cPrimitive::WaypointDepth:
            case cPrimitive::SurveyAltitude:
            case cPrimitive::SurveyDepth:
            case cPrimitive::ConstantHeading:
            default:
        }
    }

    // make a nice looking piece of code for a behavior declaration
    //  where we bind and make the settings look nice.
    //
    // rank          is the rank of the parent primitive
    // id            is the unique id of the behavior (one of possibily several) in this primitive
    // behaviorname  is what it says
    // settings      is an array of 
    public function stub_behavior_code($rank, $id) {}

    //actual output function
    //FIXME with indentation and prefixing and all that
    public function primitive_to_behavior($primitive_id) 
    {
        //some of these will become simultaneously() calls, 
        //some will be sequentially(simultaneously()) calls
        // it all depends on whether there are pre_primitives in there

        $pres = self::behaviors_pre_primitive($primitive_id);

        if (0 == count($pres))
        {
            //make settings from params
        }
    }


    //get set of behaviors before primitive
    public static function behaviors_pre_primitive($primitive_id)
    {
        switch ($primitive_id)
        {
            case cPrimitive::Altitude:
            case cPrimitive::WaypointAltitude:
            case cPrimitive::SurveyAltitude:
                return array(Behavior::GotoAltitude)

            case cPrimitive::Depth:
            case cPrimitive::WaypointDepth:
            case cPrimitive::SurveyDepth:
                return array(Behavior::GotoDepth);

            case cPrimitive::Waypoint:
            case cPrimitive::ConstantHeading:
            default: return array();
        }
        
    }

    //get set of behaviors in primitive
    public static function behaviors_in_primitive($primitive_id)
    {
        switch ($primitive_id)
        {
            case cPrimitive::Altitude:
                return array(Behavior::MaintainAltitude);
            case cPrimitive::Depth:
                return array(Behavior::MaintainDepth);
            case cPrimitive::Waypoint:
                return array(Behavior::GotoWaypoint);
            case cPrimitive::WaypointAltitude:
                return array(Behavior::GotoWaypoint, Behavior::MaintainAltitude);
            case cPrimitive::WaypointDepth:
                return array(Behavior::GotoWaypoint, Behavior::MaintainDepth);
            case cPrimitive::SurveyAltitude:
                return array(Behavior::Survey, Behavior::MaintainAltitude);
            case cPrimitive::SurveyDepth:
                return array(Behavior::Survey, Behavior::MaintainDepth);
            case cPrimitive::ConstantHeading:
                return array(Behavior::ConstantHeading);
            default: return array();
        }
        
    }

}
    class Behavior
    {
        const MaintainAltitude = 0;
        const MaintainDepth    = 1;
        const GotoWaypoint     = 2;
        const Survey           = 3;
        const ConstantHeading  = 4;
    
?>
