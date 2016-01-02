<?php
if(count($_GET) > 0)
{
    require_once("../query.class.php");
    echo "<pre>";
    $API = new SC_Query($_GET["r"]);
    echo"</pre>";
}
else
{
    include_once('templates/front_page.php');
}
?>
