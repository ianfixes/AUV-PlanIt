<?php 

//sql console for debugging

include("inc/dbinit.php"); 

$q = stripslashes($_POST["q"]);

function striptags($in, $lt, $rt)
{
    $pos = strpos($in, $lt) + strlen($lt);
    $pos2 = strpos($in, $rt, $pos);
    return substr($in, $pos, $pos2 - $pos);
}

?>
<form action="?" method="post">

<textarea name="q" rows="25" cols="135"><?php echo $q; ?></textarea>

<input type="submit" value="go" />

</form>

<table border="1">
<?php

$rs = $db->query($q);

if (MDB2::isError($rs))
{
    $err = striptags($rs->getUserInfo(), "[Native message:", "]");
    echo "<b>$err</b>";
    echo "<br>";
    echo "<br>";
    echo "<br>";
    echo str_replace("\n", "", $q);
    die();
}

$need_headers = true;

while ($row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC))
{
    if ($need_headers)
    {
        echo "\n<tr>";
        foreach ($row as $field => $dontcare)
        {
            echo "<th>$field</th>";
        }
        echo "\n</tr>";
        $need_headers = false;
    }

    echo "\n<tr>";
    foreach ($row as $field => $v)
    {
        echo "<td>$v</td>";
    }
    echo "\n</tr>";
}


?>
</table>
