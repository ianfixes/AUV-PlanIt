<?php

require_once("utils/cGeodesy.php");
require_once("utils/cCleanArray.php");

//handles basic stuff for handling mission primitives (display, etc)
class cPrimitive
{

    const Altitude          = 1;
    const Depth             = 2;
    const Waypoint          = 3;
    const WaypointAltitude  = 4;
    const WaypointDepth     = 5;
    const SurveyAltitude    = 6;
    const SurveyDepth       = 7;
    const ConstantHeading   = 8;


    //based on stored parameters of the given primitive, what can we say about
    // its start point?  we use this to draw connecting lines
    //
    // this abstraction makes more sense for drawing a survey pattern
    //
    //return array(LAT => 0, LON => 0, ALT => 0, MODE => 0)
    public static function start_point_params($primitive_id, $param_values) 
    {
        switch ($primitive_id)
        {
            case self::Waypoint:
            case self::WaypointAltitude:
            case self::WaypointDepth:
                return array("LAT" => $param_values[3], 
                             "LON" => $param_values[4]);
            case self::SurveyAltitude:
            case self::SurveyDepth:
                $ret = array();
                list($size_v, $size_h) = explode(",", $param_values[2]);
                list($ret["LON"], $ret["LAT"]) = 
                    explode(",", self::displacement_coordinates($param_values[3], 
                                                                $param_values[4], 
                                                                $param_values[7],
                                                                $size_h / 2, $size_v / 2, -1, -1));
                return $ret;
            case self::ConstantHeading:
            case self::Altitude:
            case self::Depth:
            default:
                return array();
        }
    }

    //based on stored parameters of the given primitive, what can we say about
    // its end point?  we use this to draw connecting lines
    public static function end_point_params($primitive_id, $param_values) 
    { 
        switch ($primitive_id)
        {
            case self::Waypoint:
                return array("LAT" => $param_values[3], 
                             "LON" => $param_values[4], 
                             "ALT" => 0, 
                             "MODE" => 5);

            case self::WaypointAltitude:
                return array("LAT" => $param_values[3], 
                             "LON" => $param_values[4], 
                             "ALT" => $param_values[5], 
                             "MODE" => 3);

            case self::WaypointDepth:
                return array("LAT" => $param_values[3], 
                             "LON" => $param_values[4], 
                             "ALT" => 0 - $param_values[6], 
                             "MODE" => 1);

            case self::SurveyAltitude:
                $ret = array("ALT" => $param_values[5], "MODE" => 3);
                list($size_v, $size_h) = explode(",", $param_values[2]);
                $spacing = max($param_values[9], 0.001);
                $tracks = floor($size_h / $spacing);
                $even = 0 == $tracks % 2;
                list($ret["LON"], $ret["LAT"]) = 
                    explode(",", self::displacement_coordinates($param_values[3], 
                                                                $param_values[4], 
                                                                $param_values[7],
                                                                ($tracks - 1) * $spacing / 2, 
                                                                $size_v / 2, 1, $even ? 1 : -1));
                return $ret;

            case self::SurveyDepth:
                $ret = array("ALT" => 0 - $param_values[6], "MODE" => 1);
                list($size_v, $size_h) = explode(",", $param_values[2]);
                $even = 0 == floor($size_h / $param_values[10]) % 2;
                list($ret["LON"], $ret["LAT"]) = 
                    explode(",", self::displacement_coordinates($param_values[3], 
                                                                $param_values[4], 
                                                                $param_values[7],
                                                                //FIXME, same as above??
                                                                $size_h / 2, 
                                                                $size_v / 2, 1, $even ? 1 : -1));
                return $ret;

            case self::ConstantHeading:
            case self::Altitude:
            case self::Depth:
            default:
                return self::start_point_params($primitive_id, $value); 
 
        }
    }

    //for the given primitive and the current state variables, what can say
    // about the start point of the given primitive?
    // we use this for connecting lines
    //return array(LAT => 0, LON => 0, ALT => 0, MODE => 0)
    public static function start_point_state($primitive_id, $state_values) 
    {
        $b = self::boxify($state_values);
        switch ($primitive_id)
        {
            case self::Waypoint:
            case self::WaypointAltitude:
            case self::WaypointDepth:
                return array("LAT" => $state_values["lookatTerrainLat"], 
                             "LON" => $state_values["lookatTerrainLon"]);
            case self::SurveyAltitude:
            case self::SurveyDepth:
                $ret = array();
                list($ret["LON"], $ret["LAT"]) = 
                    explode(",", self::displacement_coordinates($state_values["lookatTerrainLat"], 
                                                                $state_values["lookatTerrainLon"], 
                                                                $state_values["lookatHeading"],
                                                                $b["size_h"], $b["size_v"], -1, -1));
                return $ret;

            case self::ConstantHeading:
            case self::Altitude:
            case self::Depth:
            default:
                return array();
        }
    }

    //for the given primitive and the current state variables, what can say
    // about the end point of the given primitive?
    // we use this for connecting lines.
    // altitude modes are all "5" (clamp to ground) because we don't have this info from state
    public static function end_point_state($primitive_id, $state_values) 
    { 
        $b = self::boxify($state_values);
        switch ($primitive_id)
        {
            case self::Waypoint:
            case self::WaypointAltitude:
            case self::WaypointDepth:
                return array("LAT" => $state_values["lookatTerrainLat"], 
                             "LON" => $state_values["lookatTerrainLon"], 
                             "ALT" => 0, 
                             "MODE" => 5);
            case self::SurveyAltitude:
            case self::SurveyDepth:
                $ret = array("ALT" => 0, "MODE" => 5);
                list($ret["LON"], $ret["LAT"]) = 
                    explode(",", self::displacement_coordinates($state_values["lookatTerrainLat"], 
                                                                $state_values["lookatTerrainLon"], 
                                                                $state_values["lookatHeading"],
                                                                $b["size_h"], $b["size_v"], 1, 1));
                return $ret;

            case self::ConstantHeading:
            case self::Altitude:
            case self::Depth:
            default:
                return self::start_point_state($primitive_id, $value); 
 
        }
    }

    //placemarks for the plan in progress
    public static function kml_representation($primitive_id, $params, $label)
    {
        $s = $_SERVER['SERVER_NAME'];
        switch ($primitive_id)
        {
            case self::Altitude:
            case self::Depth:
                return "";
            case self::Waypoint:
                return self::kml_rep_placemark($label, $params[3], $params[4], "/img/google_marker_yellow.png");
            case self::WaypointAltitude:
                return self::kml_rep_placemark($label, $params[3], $params[4], "/img/google_marker_yellow_a.png");
            case self::WaypointDepth:
                return self::kml_rep_placemark($label, $params[3], $params[4], "/img/google_marker_yellow_d.png");

            case self::SurveyAltitude:
                return self::kml_rep_survey($label, $params, "relativeToSeaFloor", $params[5]);

            case self::SurveyDepth:
                return self::kml_rep_survey($label, $params, "absolute", 0 - $params[6]);

            case self::ConstantHeading:
            default:
                return "";
        }
    }

    //dump out a simple icon-based placemark 
    protected static function kml_rep_placemark($label, $lat, $lng, $marker, $description = NULL)
    {
        $d = NULL == $description ? "" : "<description>$description</description>";
        $s = $_SERVER['SERVER_NAME'];
        return "
                 <Placemark>
                  <name>$label</name>
                  $d
                  <Style>
                   <IconStyle>
                    <Icon>
                     <href>http://$s$marker</href>
                    </Icon>
                    <hotSpot x='0.5' y='0.5' xunits='fraction' yunits='fraction' />
                   </IconStyle>
                  </Style>    
                  <Point>
                   <coordinates>$lng,$lat</coordinates>
                  </Point>
                 </Placemark>
                ";

    }
 
    //placemarks for the primitive being edited  
    public static function kml_ui($primitive_id, $params, $user_prefs, $label)
    {
        switch ($primitive_id)
        {
            case self::Altitude:
            case self::Depth:
            case self::Waypoint:
            case self::WaypointAltitude:
            case self::WaypointDepth:
                return self::ui_center_placemark($params, $user_prefs, $label);
            case self::SurveyAltitude:
            case self::SurveyDepth:
                return self::ui_center_box($params, $user_prefs, $label);
            case self::ConstantHeading:
            default:
                return "";
        }
    }

    //HTML for a checkbox based on a param
    protected static function param_checkbox($param_id, $id, $v)
    {
        $chk = NULL == "$v" ? " checked='checked' " : "";
        return "&nbsp;<input type='checkbox' name='chk_$param_id' id='{$id}_chk' $chk/>
                <label for='{$id}_chk'>Use map</label>";

    }

    //HTML for a form entry for a given param
    public static function param_form($param_id, $value = NULL)
    {
        //javascript will have to look up the value of box and translate it on iniit
        //javascript will have to translate the state into the separate vars
        //javascript will have to translate the box corners into the muxed var

        $v = NULL == $value ? "" : "value='$value' ";

        //pick up value
        switch ($param_id)
        {
            case 2:
                // vertical before horizontal... vertical is the major axis of the survey
                $v_v = "";
                $v_h = "";
 
                if (NULL != $value)
                {
                    list($v1, $v2) = explode(",", $value);
                    $v_v = "value='$v1' ";
                    $v_h = "value='$v2' ";
                    $v = "PLACEHOLDER";
                }
                break;

            default:
        }


        $name = "param_$param_id";
        switch ($param_id)
        {
            case 1: //thrust ratio
                return "<input type='text' name='$name' id='ap_thrust_ratio' $v/>";
 
            case 5: //altitude
                return "<input type='text' name='$name' id='ap_altitude' $v/>";
 
            case 6: //depth
                return "<input type='text' name='$name' id='ap_depth' $v/>";
 
            case 8: //timeout
                return "<input type='text' name='$name' id='ap_timeout' $v/>";
 
            case 9: //trackline spacing
                return "<input type='text' name='$name' id='ap_trackline_spacing' $v/>";

            case 2: //box
                //need 2 hidden vars, one to take input from javscript and one to provide
                // "output" from javascript, that will get logged to the DB
                $id = "ap_box";
                return "
                        <input type='hidden' name='ap_box_input' id='$id' />
                        <input type='hidden' name='$name' id='{$id}_output' />
                        <input type='text' name='ap_box_v' id='ap_box_v' $v_v/>
                  <br /><input type='text' name='ap_box_h' id='ap_box_h' $v_h/>" . 
                    self::param_checkbox($param_id, $id, $v);
 
            case 3: //latitude
                $id = "ap_lookatTerrainLat";
                return "<input type='text' name='$name' id='$id' $v/>" . 
                    self::param_checkbox($param_id, $id, $v);
 
            case 4: //longitude
                $id = "ap_lookatTerrainLon";
                return "<input type='text' name='$name' id='$id' $v/>" .
                    self::param_checkbox($param_id, $id, $v);
 
            case 7: //heading
                $id = "ap_lookatHeading";
                return "<input type='text' name='$name' id='$id' $v/>" .
                    self::param_checkbox($param_id, $id, $v);
 
            default:
                return "[unknown param '$param_id']";
 
        }

    }

    //prepare a param (with values received from a form submission) for insertion into DB
    protected static function param_to_db($param_id, $queryvars)
    {
        switch ($param_id)
        {
            case 2:
                $corners = array();
                for ($i = 1; $i <= 4; $i++)
                {
                   $idx = "box_corner_$i";
                   $lat = "{$idx}_lat";
                   $lon = "{$idx}_lon";
                   $corners[] = "{$queryvars[$lat]},{$queryvars[$lon]}";
                }
                return implode($corners, ";");

            default:
                return $queryvars[$param_id];
            
        }
    }

    //return the google earth (lng, lat) coordinates of a point that is displaced from the 
    //  current viewpoint center toward the edge of the viewable area by some ratio
    protected static function displacement_coordinates($ctr_lat, $ctr_lng, 
                                                    $heading, 
                                                    $radius_h, $radius_v, 
                                                    $ratio_x, $ratio_y)
    {
        $radhead = deg2rad($heading);
        $er = cGeodesy::EarthRadius($ctr_lng);

        $box_h = $radius_h * $ratio_x;
        $box_v = $radius_v * $ratio_y;
        
        //calculate displacements and convert to lon/lat
        $disp_x =  $box_v * sin($radhead) + $box_h * cos($radhead);
        $disp_y = -$box_h * sin($radhead) + $box_v * cos($radhead);
        return xy2lonlat($ctr_lat, $ctr_lng, $er, $disp_x, $disp_y);
        
    }

    //based on values representing the current camera position, find the viewable area and range
    protected static function boxify($db_vars)
    {
        $GE = new cCleanArray($db_vars);

        $lng = $GE->lookatTerrainLon->float;
        $lat = $GE->lookatTerrainLat->float;
        
        //pythag distance of camera altitude and distance from center point
        $slantrange = sqrt(pow($GE->cameraAlt->float, 2) +
                           pow(cGeodesy::distVincenty($lat, 
                                                      $lng, 
                                                      $GE->cameraLat->float, 
                                                      $GE->cameraLon->float), 2));
        //maybe they use haversine?  there is a slight change in size as the tilt increases
        
        //half the width and height of the box (we are starting from center)
        $fov_h = deg2rad($GE->horizFov->float);
        $fov_v = deg2rad($GE->vertFov->float);
        $box_h = $slantrange * tan($fov_h / 2);
        $box_v = $slantrange * tan($fov_v / 2);

        return array("size_v" => $box_v, "size_h" => $box_h, "slantrange" => $slantrange);
    }

    //draw a survey pattern
    // param_values is an array of param_id => value
    protected static function kml_rep_survey($label, $param_values, $altmode, $altitude)
    {
        list($size_v, $size_h) = explode(",", $param_values[2]);
        $lat = $param_values[3];
        $lng = $param_values[4];
        $heading = $param_values[7];
        $spacing = max($param_values[9], 0.001);
        $box_v = $size_v / 2;
        $box_h = $size_h / 2;
        
        $box_kml = "
         <Placemark>
          <name>$label</name>
          <Style>
           <LineStyle>
            <color>ff00ffff</color>
            <width>2</width>
           </LineStyle>
          </Style>
          <LineString>
           <tessellate>1</tessellate>
           <extrude>1</extrude>
           <altitudeMode>$altmode</altitudeMode>
           <coordinates>
         ";

        $first = true;
        $up = true;
        $offset = 0;
        $lastoffset = 0;
        while ($offset < $size_h)
        {
            //ratios run from 0 to 2, displacement from -1 to 1
            $ratio = ($offset / $box_h) - 1;
            $lastratio = ($lastoffset / $box_h) - 1;

            //don't draw the turnaround mark on the first track
            if ($first)
            {
                list($new_lng, $new_lat) = explode(",", 
                    self::displacement_coordinates($lat, $lng, $heading, $box_h, $box_v, -1, -1));
                $mark = "absolute" == $altmode ? "google_marker_yellow_d.png" : "google_marker_yellow_a.png";
                $start_kml = self::kml_rep_placemark($label, $new_lat, $new_lng, "/img/$mark");

            }
            else
            {
                $box_kml .= "
                ". self::displacement_coordinates($lat, $lng, $heading, $box_h, $box_v, $lastratio, $up ? -1 : 1) 
                  . ",$altitude"; 
            }

            if ($up)
            {
                $beg = self::displacement_coordinates($lat, $lng, $heading, $box_h, $box_v, $ratio, -1);
                $end = self::displacement_coordinates($lat, $lng, $heading, $box_h, $box_v, $ratio,  1);
            }
            else
            {
                $beg = self::displacement_coordinates($lat, $lng, $heading, $box_h, $box_v, $ratio,  1);
                $end = self::displacement_coordinates($lat, $lng, $heading, $box_h, $box_v, $ratio, -1);
            }

            $box_kml .= "
              $beg,$altitude
              $end,$altitude";
         
            $up = !$up;
            $first = false;
            $lastoffset = $offset;
            $offset = $offset + $spacing;   
        }
         

         $box_kml .= "
           </coordinates>
          </LineString>
         </Placemark>

        ";
        
        list($h_lng, $h_lat) = 
            explode(",", self::displacement_coordinates($lat, $lng, $heading, $box_h, $box_v, 0, 0.85));

        list($v_lng, $v_lat) = 
            explode(",", self::displacement_coordinates($lat, $lng, $heading, $box_h, $box_v, -0.85, 0));

        $h_kml = self::kml_rep_placemark(round($box_h * 2, 1) . " m", $h_lat, $h_lng, "/img/measure.png", $label . ", width");
        $v_kml = self::kml_rep_placemark(round($box_v * 2, 1) . " m", $v_lat, $v_lng, "/img/measure.png", $label . ", length");

        return $box_kml . $h_kml . $v_kml . $start_kml;
    }

    //draw a box under the current viewpoint
    // db_vars is an array of state.name => value
    protected static function ui_center_box($db_vars, $user_prefs, $label)
    {
        $GE = new cCleanArray($db_vars);
        $b = self::boxify($db_vars);
        $box_h = $b["size_h"];
        $box_v = $b["size_v"];

        $lng = $GE->lookatTerrainLon->float;
        $lat = $GE->lookatTerrainLat->float;

        //try to pick a smart height for the box walls
        $h = min($b["slantrange"] / 2, max(10, $GE->lookatTerrainAlt->float / -2));
        
        $heading = $GE->lookatHeading->float;
        
        //calculate displacements and convert to lon/lat
        $toplt = self::displacement_coordinates($lat, $lng, $heading, $box_h, $box_v, -1,  1);
        $toprt = self::displacement_coordinates($lat, $lng, $heading, $box_h, $box_v,  1,  1);
        $botlt = self::displacement_coordinates($lat, $lng, $heading, $box_h, $box_v, -1, -1);
        $botrt = self::displacement_coordinates($lat, $lng, $heading, $box_h, $box_v,  1, -1);
        
        $box_kml = "
         <Placemark>
          <name>Select Region: $label</name>
          <Style>
           <LineStyle>
            <color>{$user_prefs["Box Color"]}</color>
            <width>3</width>
           </LineStyle>
          </Style>
          <LineString>
           <tessellate>1</tessellate>
           <extrude>1</extrude>
           <altitudeMode>relativeToSeaFloor</altitudeMode>
           <coordinates>
            $toplt,$h
            $toprt,$h
            $botrt,$h
            $toplt,$h
            $botlt,$h
            $toprt,$h
            $botrt,$h
            $botlt,$h
           </coordinates>
          </LineString>
         </Placemark>

        ";
        
        list($h_lng, $h_lat) = 
            explode(",", self::displacement_coordinates($lat, $lng, $heading, $box_h, $box_v, 0, 0.85));

        list($v_lng, $v_lat) = 
            explode(",", self::displacement_coordinates($lat, $lng, $heading, $box_h, $box_v, -0.85, 0));

        $h_kml = self::kml_rep_placemark(round($box_h * 2, 1) . " m", $h_lat, $h_lng, "/img/measure.png");
        $v_kml = self::kml_rep_placemark(round($box_v * 2, 1) . " m", $v_lat, $v_lng, "/img/measure.png");

        return $box_kml . $h_kml . $v_kml;
    }

    //KML for a placemark based on the current view
    protected static function ui_center_placemark($db_vars, $user_prefs, $label)
    {
        $GE = new cCleanArray($db_vars);

        $s = $_SERVER['SERVER_NAME'];
 
        return self::kml_rep_placemark($label, 
                                   $GE->lookatTerrainLat->float,  
                                   $GE->lookatTerrainLon->float,
                                   $user_prefs["Reticle"]);
    }

    //draw an arrow on the map to represent some direction
    // db_vars is an array of state.name => value
    public static function ui_arrow($lat, $lng, $heading, $length_ratio, $db_vars)
    {
        $GE = new cCleanArray($db_vars);

        $lng2 = $GE->lookatTerrainLon->float;
        $lat2 = $GE->lookatTerrainLat->float;
    
        $len1 = $length_ratio;
        $len2 = $length_ratio * 0.79;

        $b = self::boxify($db_vars);
        $box_h = $b["size_h"];
        $box_v = $b["size_v"];
        
        //calculate displacements and convert to lon/lat
        $arrb = self::displacement_coordinates($lat, $lng, $heading, $box_h, $box_v,  0,  0);
        $arrp = self::displacement_coordinates($lat, $lng, $heading, $box_h, $box_v,  0, $len1);
        $arrl = self::displacement_coordinates($lat, $lng, $heading - 5, $box_h, $box_v,  0, $len2);
        $arrr = self::displacement_coordinates($lat, $lng, $heading + 5, $box_h, $box_v,  0, $len2);

        return "
         <Placemark>
          <name>Arrow</name>
          <Style>
           <LineStyle>
            <color>FFFFFFFF</color>
            <width>1</width>
           </LineStyle>
          </Style>
          <LineString>
           <tessellate>1</tessellate>
           <extrude>1</extrude>
           <altitudeMode>clampToGround</altitudeMode>
           <coordinates>
            $arrl
            $arrp
            $arrb
            $arrp
            $arrr
           </coordinates>
          </LineString>
         </Placemark>
        ";
    }


}


?>
