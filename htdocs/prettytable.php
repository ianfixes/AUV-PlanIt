<html>
 <head>
  <title>Pretty table demo</title>
 <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="/gui.css" rel="stylesheet" type="text/css">
</head>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">

<table width="100%" border="0" cellpadding="0" cellspacing="0">
 <tr>
  <td class="tabnavtbl">
   <ul id="tabnav">
    <li class="tabact">Inbound</li>
    <li class="tabinact"><a href="firewall_nat_server.php">Server NAT</a></li>
    <li class="tabinact"><a href="firewall_nat_1to1.php">1:1</a></li>
    <li class="tabinact"><a href="firewall_nat_out.php">Outbound</a></li>         
   </ul>
  </td>
 </tr>
 <tr> 
  <td class="tabcont">
   <table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr> 
     <td width="5%" class="listhdrr">If</td>
     <td width="5%" class="listhdrr">Proto</td>
     <td width="20%" class="listhdrr">Ext. port range</td>
     <td width="20%" class="listhdrr">NAT IP</td>

     <td width="20%" class="listhdrr">Int. port range</td>
     <td width="20%" class="listhdr">Description</td>
     <td width="5%" class="list"><a href="firewall_nat_edit.php"><img src="/img/plus.gif" title="add rule" width="17" height="17" border="0"></a></td>
    </tr>

    <tr valign="top"> 
     <td class="listlr">WAN</td>
     <td class="listr">TCP</td>
     <td class="listr">22 (SSH)</td>
     <td class="listr">tank</td>
     <td class="listr">22 (SSH)</td>
     <td class="listbg">SSH to tank&nbsp;</td>

     <td valign="middle" class="list" nowrap> 
      <a href="firewall_nat_edit.php?id=0"><img src="/img/e.gif" title="edit rule" width="17" height="17" border="0"></a>
      <a href="firewall_nat.php?act=del&id=0" onclick="return confirm('Do you really want to delete this rule?')"><img src="/img/x.gif" title="delete rule" width="17" height="17" border="0"></a>
      <a href="firewall_nat.php?act=del&id=0" onclick="return confirm('Do you really want to export this rule?')"><img src="/img/arrow.gif" title="export rule" width="17" height="17" border="0"></a>
     </td>
    </tr>
   </table>
   <br>
   <span class="vexpl">
    <span class="red"><strong>Note:<br></strong></span>
    This is something important
   </span>
  </td>
 </tr>
</table>
 
