<?php
error_reporting(E_ALL ^ E_NOTICE);
require "mysql_connect.php";
$search=$_GET["search"];
$type=$_GET["type"];
$year=$_GET["year"];
$kind=$_GET["kind"];

$i=0;
$data=[];

switch ($type) {
	case 'office':
		$sql="SELECT id,name FROM office WHERE year='$year' AND kind='$kind' AND name LIKE '%$search%' ORDER BY id";
		break;
	case 'obj':
		$sql="SELECT id,name FROM obj WHERE year='$year' AND kind='$kind' AND name LIKE '%$search%' ORDER BY id";
		break;
	case 'statcd':
		$sql="SELECT id,name FROM statcd WHERE year='$year' AND kind='$kind' AND name LIKE '%$search%' ORDER BY id";
		break;
	case 'account':
		$sql="SELECT name FROM account WHERE year='$year' AND name LIKE '%$search%' ";
		break;
	case 'use':
		$sql="SELECT name FROM `use` WHERE year='$year' AND name LIKE '%$search%' ";
		break;
}

$result=$db->query($sql);
while ($row=$result->fetch()) {
	if (!$row[1]) {
		$row[1]=$row[0];
	}
	$data[$i]=["id"=>"$row[0]","text"=>"$row[1]"];
	$i++;
}
$json = [
    "data"    => $data
];
echo json_encode($json, JSON_UNESCAPED_UNICODE);