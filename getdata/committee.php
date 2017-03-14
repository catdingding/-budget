<?php
error_reporting(E_ALL);
include 'mysql_connect.php';

$update=$db->prepare("UPDATE office SET committee=:committee WHERE year=:year AND kind=:kind AND id=:id");

$result = $db->query("SELECT * FROM office");
while ($row = $result->fetch()) {
    $kind      = $row['kind'];
    $id        = $row['id'];
    $year      = $row['year'];
    $committee = 0;
    if ($kind=='attach') {
    	$head = str_split($id, 4)[0];
    	switch ($head) {
    		case '2104':
    			$committee = 2;
    			break;
    		case '2108':
    			$committee = 4;
    			break;
    		case '2121':
    			$committee = 2;
    			break;
    		case '2129':
    			$committee = 4;
    			break;
    		case '3202':
    			$committee = 1;
    			break;
    		case '3204':
    			$committee = 2;
    			break;
    		case '3205':
    			$committee = 3;
    			break;
    		case '3206':
    			$committee = 2;
    			break;
    		case '3207':
    			$committee = 6;
    			break;
    		case '3208':
    			$committee = 4;
    			break;
    		case '3209':
    			$committee = 1;
    			break;
    		case '3212':
    			$committee = 5;
    			break;
    		case '3214':
    			$committee = 6;
    			break;
    		case '3215':
    			$committee = 3;
    			break;
    		case '3219':
    			$committee = 1;
    			break;
    		case '4304':
    			$committee = 2;
    			break;
    		case '4404':
    			$committee = 2;
    			break;
    		case '4405':
    			$committee = 3;
    			break;
    		case '4406':
    			$committee = 2;
    			break;
    		case '4407':
    			$committee = 6;
    			break;
    		case '4409':
    			$committee = 1;
    			break;
    		case '4410':
    			$committee = 1;
    			break;
    		case '4413':
    			$committee = 5;
    			break;
    		case '4414':
    			$committee = 6;
    			break;
    		case '4415':
    			$committee = 3;
    			break;
    		case '4429':
    			$committee = 4;
    			break;
    	}
    }else {
        $head = str_split($id, 2)[0];
        switch ($head) {
            case '02':
                if (in_array($id, ['02101', '02102', '02104', '02105', '02106', '02108', '02112', '02113'])) {
                    $committee = 1;
                } else if ($id == '02103') {
                    $committee = 2;
                } else if ($id == '02111') {
                    $committee = 6;
                }else if($id>='02121' && $id<='02132'){
                	$committee = 1;
                }
                break;
            case '03':
            	$committee = 1;
            	break;
            case '04':
            	$committee = 2;
            	break;
            case '05':
            	$committee = 3;
            	break;
            case '06':
            	$committee = 2;
            	break;
            case '07':
            	$committee = 6;
            	break;
            case '08':
            	$committee = 4;
            	break;
            case '09':
            	$committee = 1;
            	break;
            case '10':
            	$committee = 1;
            	break;
            case '11':
            	$committee = 5;
            	break;
            case '12':
            	$committee = 5;
            	break;
            case '13':
            	$committee = 5;
            	break;
            case '14':
            	$committee = 6;
            	break;
            case '15':
            	$committee = 3;
            	break;
            case '16':
            	$committee = 5;
            	break;
            case '17':
            	$committee = 2;
            	break;
            case '18':
            	$committee = 3;
            	break;
            case '19':
            	$committee = 1;
            	break;
            case '20':
            	$committee = 1;
            	break;
            case '21':
            	$committee = 2;
            	break;
            case '22':
            	$committee = 3;
            	break;
            case '23':
            	$committee = 2;
            	break;
            case '24':
            	$committee = 1;
            	break;
            case '29':
            	$committee = 4;
            	break;
        }
    }
    $update->bindValue(":committee",$committee);
$update->bindValue(":year",$row['year']);
$update->bindValue(":kind",$row['kind']);
$update->bindValue(":id",$row['id']);
    $update->execute();
}
