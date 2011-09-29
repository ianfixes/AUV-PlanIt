<?php

// interface for classes that export code

require_once("cPrimitive.php");

abstract class cExport
{
    class Behavior
    {
        const GotoAltitude     = 0;
        const GotoDepth        = 1;
        const MaintainAltitude = 2;
        const MaintainDepth    = 3;
        const GotoWaypoint     = 4;
        const Survey           = 5;
        const ConstantHeading  = 6;
    }

    protected $m_DB;
    protected $m_Lat;
    protected $m_Lon;
    protected $m_MissionName;

    public function __construct()
    {
        $this->m_DB = NULL;
        $this->m_Lat = NULL;
        $this->m_Lon = NULL;
        $this->m_MissionName = "MissionNameNotSet";
    }

    public function setLocation($lat, $lon)
    {
        $this->m_Lat = $lat;
        $this->m_Lon = $lon;
    }

    public function setMissionName($n)
    {
        $this->m_MissionName = $n;
    }

    public function setDB($db)
    {
        $this->m_DB = $db;
    }

    //render a plan to string
    abstract public function Render($plan_id);

}

?>
