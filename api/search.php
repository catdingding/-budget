<?php
error_reporting(E_ALL ^ E_NOTICE);

require "mysql_connect.php";
require "condition.php";
$page  = $_GET['page'];
$i     = 0;
$data  = [];
$start = ($page - 1) * 25;

if ($kind=='attach') {
	$sql    = "SELECT sum,COALESCE(name,''),unit,`number`,unitprice,account,`use`,note,page,budget.id FROM budget LEFT JOIN (SELECT * FROM office WHERE 1=1 $condition_committee)as office ON budget.year=office.year AND budget.kind=office.kind AND budget.office=office.id WHERE 1=1 $condition $condition_committee ORDER BY $match_sort sum $order limit $start,500";
}else{
	$sql    = "SELECT sum,COALESCE(budget.name,''),unit,`number`,unitprice,COALESCE(obj.name,''),COALESCE(statcd.name,''),note,page,COALESCE(srcid.name,''),budget.id FROM (SELECT budget.*,office.name $match FROM budget LEFT JOIN (SELECT * FROM office WHERE 1=1 $condition_committee)as office ON budget.year=office.year AND budget.kind=office.kind AND budget.office=office.id WHERE 1=1 $condition $condition_committee ORDER BY $match_sort sum $order limit $start,500) AS budget LEFT JOIN obj ON budget.year=obj.year AND budget.kind=obj.kind AND budget.obj=obj.id LEFT JOIN statcd ON budget.year=statcd.year AND budget.kind=statcd.kind AND budget.statcd=statcd.id  LEFT JOIN srcid ON (budget.year=srcid.year AND budget.kind=srcid.kind AND budget.srcid=srcid.id and (srcid.office='' or budget.office=srcid.office))";
}

$result = $db->prepare("$sql");
bind();
$result->execute();
while ($row = $result->fetch()) {
	if ($kind=='attach') {
		$data[$i] = ["sum" => $row[0], "office" => $row[1], "unit" => $row[2], "number" => $row[3], "unitprice" => $row[4], "account" => $row[5], "use" => $row[6], "note" => $row[7], "page" => $row[8],"id"=>$row['id'],"add"=>false];
	}else{
		$data[$i] = ["sum" => $row[0], "office" => $row[1], "unit" => $row[2], "number" => $row[3], "unitprice" => $row[4], "obj" => $row[5], "statcd" => $row[6], "note" => $row[7], "page" => $row[8], "srcid"=>$row[9],"id"=>$row['id'],"add"=>false];
	}
    $i++;
}

if($condition_committee){
	$sql    = "SELECT 1 FROM budget INNER JOIN (SELECT * FROM office WHERE 1=1 $condition_committee)as office ON budget.year=office.year AND budget.kind=office.kind AND budget.office=office.id WHERE 1=1 $condition";
}else{
	$sql    = "SELECT 1 FROM budget WHERE 1=1 $condition";
}

$result = $db->prepare("$sql");
bind(1);
$result->execute();
$count = count($result->fetchAll());
$max_page = ceil($count / 25);

$json = [
    "data"    => $data,
    "summary" => [
        "page"     => "$page",
        "max_page" => "$max_page",
        "count"    => "$count",
    ],
];
echo json_encode($json, JSON_UNESCAPED_UNICODE);
