<?php

// a page that lets you manually enter the location of an entity

include("inc/dbinit.php");
include("report_arginit.php");

if (!isset($GET->entity_id))
{
    die("Entity ID is missing.  <br /><a href='/'>&laquo; Go back and try again</a>");
}

$e = $dbo->entity->ID($GET->entity_id->int);


//process a submit 
if (isset($GET->submit))
{
    $fields = array("entity_id", "lat", "lng");
    $values = array($e->entity_id, $GET->lat->float, $GET->lng->float);

    if ("" != $GET->alt->string)
    {
        $fields[] = "alt";
        $values[] = $GET->alt->float;
    }
   
    if ("" != $GET->ang->string)
    {
        $fields[] = "heading";
        $values[] = $GET->ang->float;
    }

    $q = "insert into entity_location(" . implode(",", $fields) . ") values(" . implode(",", $values) . ")";
    $db->query($q);
}


$q = "
    select lat, lng
    from entity_location
    order by updated desc
    ";

$rs = $db->query($q);

//if we don't get a row, make dummy values
if (!($prev = $rs->fetchRow(MDB2_FETCHMODE_ASSOC)))
{
    $prev["lat"] = "";
    $prev["lng"] = "";
}


?>
<html>
<head>
<title>Ian's AUV PlanIt - Location of <?php echo $e->name; ?></title>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="/gui.css" rel="stylesheet" type="text/css">

<script language="JavaScript" type="text/javascript">
  // regular expression defining valid input format
  var dms_re = /(\d{1,3})\D+([0-5][0-9]|[0-9])\D+([0-5][0-9]|[0-9])\D+ ?(e(ast)?$|w(est)?$|n(orth)?$|s(outh)?$)/i
  var lon_re = /[EW]/i
  var lat_re = /[NS]/i
  var neg_re = /[WS]/i
  var precision = 6;  // maximum numbers after decimal point
  var ten_to_n = Math.pow(10,precision);

  // converts coordinate in degrees, minutes, seconds (with direction) to decimal degrees
  function convertDMStoDec(input_element, output_element_id)
  {
    out = document.getElementById(output_element_id);

    if (!input_element.value.match(dms_re))
    {
      out.value = "invalid or incomplete";
    }
    else
    {
      var deg = parseInt(RegExp.$1, 10);
      var min = parseInt(RegExp.$2, 10);
      var sec = parseInt(RegExp.$3, 10);
      var dir = RegExp.$4;
      var dec = deg + min / 60.0 + sec / 3600.00;
      // dec = dec.toPrecision(precision); // not quite what I wanted. so using ...
      dec = Math.round(dec * ten_to_n)/ten_to_n;
      if (dir.substr(0,1).match(neg_re))
      {
        dec = -dec;
      }

      out.value = dec;
    }
    return true;
  }


</script>
</head>

<body bgcolor="white">


<table width="100%" border="0" cellpadding="0" cellspacing="0">
 <tr>
  <td class="tabnavtbl">
   <ul id="tabnav">
    <li class="tabinact"><a href="/">Home</a></li>
    <li class="tabact">Update Location</li>
   </ul>
  </td>
 </tr>
 <tr> 
  <td class="tabcont">


<h1>
<img src="<?php echo $e->icon_img_url; ?>" style="float:right;height:5ex;" alt="icon for <?php echo $e->name ?>"/>
Manually update location of <?php echo $e->name ?><br /><br /></h1>


<form action="?" method="GET">
 <input type="hidden" name="entity_id" value="<?php echo $e->entity_id; ?>" />
 <fieldset style="float:left; width:20em;font-family:monospace;"><legend>Decimal Postion</legend>
   Lat: <input name="lat" id="dec_lat" type="text" size="20" maxlength="24" value="<?php echo $prev["lat"]; ?>"><br />
   Lng: <input name="lng" id="dec_lng" type="text" size="20" maxlength="24" value="<?php echo $prev["lng"]; ?>"><br />
   Alt: <input name="alt" id="dec_alt" type="text" size="20" maxlength="24" ><br />
   Ang: <input name="ang" id="dec_hed" type="text" size="20" maxlength="24" ><br />

   <input type="submit" name="submit" value="Update" />
 </fieldset>
 <fieldset style="float:left; width:20em;font-family:monospace;"><legend>Or, if you prefer... DegMinSec</legend>

   Lat: <input name="dms_lat" type="text" size="20" maxlength="24" onkeyup="convertDMStoDec(this, 'dec_lat');"><br />
   Lng: <input name="dms_lng" type="text" size="20" maxlength="24" onkeyup="convertDMStoDec(this, 'dec_lng');"><br />

  <h4>Examples</h4>
  <ul>
   <li> 12 34 56 W </li>
   <li> 34° 56′ 42″ S </li>
   <li> 138° 31′ 50″ E </li>
   <li> 41º53’2” n </li>
   <li> 2:17:27 e </li>
   <li> 12^34`56``N </li>
   <li> 120d30m0s E </li>
  </ul>
  
  <h4>Notes</h4>
  <ul>
    <li>Required input: degrees, minutes, seconds and compass direction (N/S/E/W), with delimiters between.</li>
    <li>Any non-numeric characters can act as a separator or delimiter, even spaces.</li>
    <li>Any input before the numbers is ignored, with degrees limited to three digits.</li>
    <li>Decimal points are not supported in the input.</li>
  </ul>
 </fieldset>


</form>


  </td>
 </tr>
</table>
</body>
</html>
