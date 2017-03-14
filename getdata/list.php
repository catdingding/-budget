<?php
include 'mysql_connect.php';

$pdir = glob('data/*',GLOB_ONLYDIR);
foreach ($pdir as $value) {
	$year=str_replace('data/', '', $value);
	$cdir=glob($value.'/*');
	foreach ($cdir as $value2) {
		$name=str_replace("data/$year/",'',$value2);
		$db->exec("INSERT INTO list(year,name) SELECT '$year','$name' FROM DUAL WHERE NOT EXISTS(SELECT 1 from list WHERE year='$year' AND name='$name')");
	}
}