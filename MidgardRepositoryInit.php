<?php

require 'MidgardBootstrap.php';

/* relative path */
$dataDir = "data"; 

$mb = new MidgardBootstrap (__DIR__ . "/" . $dataDir);
$mb->execute ();

?>
