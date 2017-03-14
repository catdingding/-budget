<?php
error_reporting(E_ALL ^ E_NOTICE);
include 'mysql_connect.php';

$list_update = $db->prepare("UPDATE list SET already=1 WHERE year=:year AND name=:name");
$list_update_2 = $db->prepare("UPDATE list SET already=2 WHERE year=:year AND name=:name");

$budget_upload = $db->prepare("INSERT INTO `budget_origin`(`year`,`kind` ,`type`, `sum`, `office`, `unit`, `number`, `unitprice`, `srcid`, `obj`, `statcd`, `note`, `page`,`filename`) VALUES (:year,:kind,:type,:sum,:office,:unit,:number,:unitprice,:srcid,:obj,:statcd,:note,:page,:filename)");
$obj_upload    = $db->prepare("INSERT INTO `obj`(`year`,`kind`,`id`,`name`) SELECT :year,:kind,:id,:name FROM DUAL WHERE NOT EXISTS(SELECT 1 from `obj` WHERE year=:year AND kind=:kind AND id=:id AND name=:name)");
$office_upload = $db->prepare("INSERT INTO `office`(`year`,`kind`,`id`,`name`) SELECT :year,:kind,:id,:name FROM DUAL WHERE NOT EXISTS(SELECT 1 from `office` WHERE year=:year AND kind=:kind AND id=:id AND name=:name)");
$statcd_upload = $db->prepare("INSERT INTO `statcd`(`year`,`kind`,`id`,`name`) SELECT :year,:kind,:id,:name FROM DUAL WHERE NOT EXISTS(SELECT 1 from `statcd` WHERE year=:year AND kind=:kind AND id=:id AND name=:name)");
$srcid_upload = $db->prepare("INSERT INTO `srcid`(`year`,`kind`,`office`,`id`,`name`) SELECT :year,:kind,:office,:id,:name FROM DUAL WHERE NOT EXISTS(SELECT 1 from `srcid` WHERE year=:year AND kind=:kind AND office=:office AND id=:id AND name=:name)");
$attach_budget_upload = $db->prepare("INSERT INTO `budget_origin`(`year`,`kind` ,`type`, `sum`, `office`, `unit`, `number`, `unitprice`, `account`, `use`, `note`, `page`,`filename`) VALUES (:year,:kind,:type,:sum,:office,:unit,:number,:unitprice,:account,:use,:note,:page,:filename)");

$column_list = ['year', 'kind', 'type', 'sum', 'office', 'unit', 'number', 'unitprice', 'srcid', 'obj', 'statcd', 'note', 'page', 'filename'];

$result = $db->query("SELECT * FROM list WHERE already=0 limit 0,40");
while ($row = $result->fetch()) {
    if (!preg_match("/BA055|E|F|J|K|sql|FNBWORK|CDORGMI/", $row['name'])) {
        budget($row['year'], $row['name']);
    } else if (preg_match("/JC210P/", $row['name'])) {
        if ($row['year']==105 && $row['name']=='JC210P.DBF') {
            continue;
        }
        obj($row['year'], $row['name']);
    } else if (preg_match("/JC020P/", $row['name'])) {
        office($row['year'], $row['name']);
    } else if (preg_match("/EC010P/", $row['name'])) {
        if ($row['year']==105 && $row['name']=='EC010P.DBF') {
            $list_update_2->bindValue(':year', $row['year']);
            $list_update_2->bindValue(':name', $row['name']);
            $list_update_2->execute();
            continue;
        }
        statcd($row['year'], $row['name']);
    }else if(preg_match("/JC200|JC201|JC100/", $row['name'])){
        srcid($row['year'], $row['name']);
    } else if(preg_match("/FNBWORK/", $row['name'])){
        attach_budget($row['year'], $row['name']);
    } else if(preg_match("/CDORGMI/", $row['name'])){
        attach_office($row['year'], $row['name']);
    } else{
        $list_update_2->bindValue(':year', $row['year']);
        $list_update_2->bindValue(':name', $row['name']);
        $list_update_2->execute();
        continue;
    }
    $list_update->bindValue(':year', $row['year']);
    $list_update->bindValue(':name', $row['name']);
    $list_update->execute();
}

function budget($fileyear, $filename)
{
    global $budget_upload, $column_list;
    $file = fopen('data/' . $fileyear . '/' . $filename, "r");

    //判斷欄位
    $row      = fgetcsv($file, 0, "\t");
    $rowcount = count($row);

    $budget = new parser('budget', $row);

    while (!feof($file)) {
        $row = fgetcsv($file, 0, "\t");
        if (!$row) {
            continue;
        }

        $year = $budget->get('year', $row);

        if (intval($fileyear) - 1 == intval($year)) {
            $kind = 'plus';
        } else if (intval($fileyear) == intval($year)) {
            $kind = 'normal';
        }

        if (!$budget->get('obj', $row)) {
            $type = 'in';
        } else {
            $type = 'out';
        }

        $sum    = $budget->get('sum', $row);
        $office = $budget->get('office', $row);

        if ($budget->get('unit', $row)) {
            $unit = $budget->get('unit', $row);
        } else {
            $unit = $budget->get('una1', $row);
            if ($budget->get('una2', $row)) {
                $unit .= 'x' . $budget->get('una2', $row);
            }if ($budget->get('una3', $row)) {
                $unit .= 'x' . $budget->get('una3', $row);
            }
        }

        if ($budget->get('number', $row)) {
            $number = $budget->get('number', $row);
        } else {
            $number = $budget->get('cqty1', $row);
            if ($budget->get('cqty2', $row)) {
                $number .= 'x' . $budget->get('cqty2', $row);
            }if ($budget->get('cqty3', $row)) {
                $number .= 'x' . $budget->get('cqty3', $row);
            }
        }

        $unitprice = $budget->get('unitprice', $row);
        $srcid     = $budget->get('srcid', $row);

        if ($budget->get('obj', $row)) {
            $obj = $budget->get('obj', $row);
        } else {
            $obj = '';
        }

        if ($budget->get('statcd', $row)) {
            $statcd = $budget->get('statcd', $row);
        } else {
            $statcd = '';
        }

        if ($budget->get('page', $row)) {
            $page = $budget->get('page', $row);
        } else {
            $page = '';
        }

        $note = $budget->get('note', $row);

        preg_match_all("/[\d\.]+/u", $number, $match);
        for ($i = 0; $i < count($match[0]); $i++) {
            $match[0][$i] = preg_replace('/\.\d*0$/', '', $match[0][$i]);
        }
        $number = implode('x', $match[0]);

        $unit = str_replace("、", 'x', $unit);

        preg_match_all('/[\d\.]+/', $unitprice, $match);
        $unitprice=implode('',$match[0]);

        foreach ($column_list as $value) {
            $$value = preg_replace('/\s|¶/', '', $$value);
            $budget_upload->bindValue(":" . $value, $$value);
        }

        $budget_upload->execute();

    }
    fclose($file);
}

function obj($fileyear, $filename)
{
    global $obj_upload;
    $file = fopen('data/' . $fileyear . '/' . $filename, "r");
    $row  = fgetcsv($file, 0, "\t");

    $obj = new parser('obj', $row);

    while (!feof($file)) {
        $row = fgetcsv($file, 0, "\t");
        if (!$row) {
            continue;
        }

        $year = $obj->get('year', $row);
        if (intval($fileyear) - 1 == intval($year)) {
            $kind = 'plus';
        } else if (intval($fileyear) == intval($year)) {
            $kind = 'normal';
        }
        $id   = $obj->get('id', $row);
        $name = $obj->get('name', $row);

        $obj_upload->bindValue(":year", $year);
        $obj_upload->bindValue(":kind", $kind);
        $obj_upload->bindValue(":id", $id);
        $obj_upload->bindValue(":name", $name);

        $obj_upload->execute();
    }
}

function office($fileyear, $filename)
{
    global $office_upload;
    $file = fopen('data/' . $fileyear . '/' . $filename, "r");
    $row  = fgetcsv($file, 0, "\t");

    $office = new parser('office', $row);

    while (!feof($file)) {
        $row = fgetcsv($file, 0, "\t");
        if (!$row) {
            continue;
        }

        $year = $office->get('year', $row);
        if (intval($fileyear) - 1 == intval($year)) {
            $kind = 'plus';
        } else if (intval($fileyear) == intval($year)) {
            $kind = 'normal';
        }
        $id   = $office->get('id', $row);
        $name = $office->get('name', $row);

        $office_upload->bindValue(":year", $year);
        $office_upload->bindValue(":kind", $kind);
        $office_upload->bindValue(":id", $id);
        $office_upload->bindValue(":name", $name);

        $office_upload->execute();
    }
}

function statcd($fileyear, $filename)
{
    global $statcd_upload;
    $file = fopen('data/' . $fileyear . '/' . $filename, "r");
    $row  = fgetcsv($file, 0, "\t");

    $statcd = new parser('statcd', $row);

    while (!feof($file)) {
        $row = fgetcsv($file, 0, "\t");
        if (!$row) {
            continue;
        }

        $year = $statcd->get('year', $row);
        if (intval($fileyear) - 1 == intval($year)) {
            $kind = 'plus';
        } else if (intval($fileyear) == intval($year)) {
            $kind = 'normal';
        }
        $id   = $statcd->get('id', $row);
        $name = $statcd->get('name', $row);

        $statcd_upload->bindValue(":year", $year);
        $statcd_upload->bindValue(":kind", $kind);
        $statcd_upload->bindValue(":id", $id);
        $statcd_upload->bindValue(":name", $name);

        $statcd_upload->execute();
    }
}

function srcid($fileyear, $filename)
{
    global $srcid_upload;
    $file = fopen('data/' . $fileyear . '/' . $filename, "r");
    $row  = fgetcsv($file, 0, "\t");

    $srcid = new parser('srcid', $row);

    while (!feof($file)) {
        $row = fgetcsv($file, 0, "\t");
        if (!$row) {
            continue;
        }

        $year = $srcid->get('year', $row);
        if (intval($fileyear) - 1 == intval($year)) {
            $kind = 'plus';
        } else if (intval($fileyear) == intval($year)) {
            $kind = 'normal';
        }
        if ($srcid->get('office', $row)) {
            $office = $srcid->get('office', $row);
        }else{
            $office='';
        }
        $id   = $srcid->get('id', $row);
        $name = $srcid->get('name', $row);

        $srcid_upload->bindValue(":year", $year);
        $srcid_upload->bindValue(":kind", $kind);
        $srcid_upload->bindValue(":office", $office);
        $srcid_upload->bindValue(":id", $id);
        $srcid_upload->bindValue(":name", $name);

        $srcid_upload->execute();
    }
}

function attach_budget($fileyear, $filename)
{
    global $attach_budget_upload;
    $file = fopen('data/' . $fileyear . '/' . $filename, "r");

    //判斷欄位
    $row      = fgetcsv($file, 0, "\t");
    $rowcount = count($row);

    $budget = new parser('attach_budget', $row);

    while (!feof($file)) {
        $row = fgetcsv($file, 0, "\t");
        if (!$row) {
            continue;
        }

        $year = $budget->get('year', $row);
        $kind = 'attach';

        if (!$budget->get('use', $row)) {
            $type = 'in';
        } else {
            $type = 'out';
        }

        $sum    = $budget->get('sum', $row);
        $office = $budget->get('office', $row);

        $unit = $budget->get('unit', $row);
        $number = $budget->get('number1', $row);
        if ($budget->get('number2', $row)) {
            $number .= 'x' . $budget->get('number2', $row);
        }if ($budget->get('number3', $row)) {
            $number .= 'x' . $budget->get('number3', $row);
        }
        $unitprice = $budget->get('unitprice', $row);

        $use     = $budget->get('use', $row);
        $account     = $budget->get('account', $row);

        $page = $budget->get('page', $row);
        $note = $budget->get('note', $row);

        preg_match_all("/[\d\.]+/u", $number, $match);
        for ($i = 0; $i < count($match[0]); $i++) {
            $match[0][$i] = preg_replace('/\.\d*0$/', '', $match[0][$i]);
        }
        $number = implode('x', $match[0]);

        $unit = str_replace("、", 'x', $unit);

        $note=preg_replace('/\s/','',$note);

        preg_match_all('/[\d\.]+/', $unitprice, $match);
        $unitprice=implode('',$match[0]);

        $attach_budget_upload->bindValue(":year",$year);
        $attach_budget_upload->bindValue(":kind",$kind);
        $attach_budget_upload->bindValue(":type",$type);
        $attach_budget_upload->bindValue(":sum",$sum);
        $attach_budget_upload->bindValue(":office",$office);
        $attach_budget_upload->bindValue(":unit",$unit);
        $attach_budget_upload->bindValue(":number",$number);
        $attach_budget_upload->bindValue(":unitprice",$unitprice);
        $attach_budget_upload->bindValue(":account",$account);
        $attach_budget_upload->bindValue(":use",$use);
        $attach_budget_upload->bindValue(":note",$note);
        $attach_budget_upload->bindValue(":page",$page);
        $attach_budget_upload->bindValue(":filename",$filename);

        $attach_budget_upload->execute();

    }
    fclose($file);
}

function attach_office($fileyear, $filename)
{
    global $office_upload;
    $file = fopen('data/' . $fileyear . '/' . $filename, "r");
    $row  = fgetcsv($file, 0, "\t");

    $office = new parser('attach_office', $row);

    while (!feof($file)) {
        $row = fgetcsv($file, 0, "\t");
        if (!$row) {
            continue;
        }

        $year = $office->get('year', $row);
        $kind = 'attach';
        $id   = $office->get('id', $row);
        $name = $office->get('name', $row);

        $office_upload->bindValue(":year", $year);
        $office_upload->bindValue(":kind", $kind);
        $office_upload->bindValue(":id", $id);
        $office_upload->bindValue(":name", $name);

        $office_upload->execute();
    }
}

class parser
{
    public function __construct($type, $row)
    {
        $row[0]   = preg_replace('/\x{feff}/u', '', $row[0]);
        $rowcount = count($row);
        if ($type == 'budget') {
            for ($i = 0; $i < $rowcount; $i++) {
                $this->set('year', ['bgtyy', 'yearno'], $row[$i], $i);
                $this->set('sum', ['amt', 'price'], $row[$i], $i);
                $this->set('office', ['org1', 'borg'], $row[$i], $i);
                $this->set('una1', ['una1'], $row[$i], $i);
                $this->set('una2', ['una2'], $row[$i], $i);
                $this->set('una3', ['una3'], $row[$i], $i);
                $this->set('unit', ['unit'], $row[$i], $i);
                $this->set('cqty1', ['cqty1'], $row[$i], $i);
                $this->set('cqty2', ['cqty2'], $row[$i], $i);
                $this->set('cqty3', ['cqty3'], $row[$i], $i);
                $this->set('number', ['quantity'], $row[$i], $i);
                $this->set('unitprice', ['prc', 'unitprice'], $row[$i], $i);
                $this->set('srcid', ['srcid', 'dno'], $row[$i], $i);
                $this->set('obj', ['obj'], $row[$i], $i);
                $this->set('statcd', ['statcd'], $row[$i], $i);
                $this->set('note', ['note', 'notes'], $row[$i], $i);
                $this->set('page', ['pageno'], $row[$i], $i);
            }
        } else if ($type == 'obj') {
            for ($i = 0; $i < $rowcount; $i++) {
                $this->set('year', ['bgtyy'], $row[$i], $i);
                $this->set('id', ['obj'], $row[$i], $i);
                $this->set('name', ['objna'], $row[$i], $i);
            }
        } else if ($type == 'office') {
            for ($i = 0; $i < $rowcount; $i++) {
                $this->set('year', ['bgtyy'], $row[$i], $i);
                $this->set('id', ['org'], $row[$i], $i);
                $this->set('name', ['orgna'], $row[$i], $i);
            }
        } else if ($type == 'statcd') {
            for ($i = 0; $i < $rowcount; $i++) {
                $this->set('year', ['bgtyy'], $row[$i], $i);
                $this->set('id', ['statcd'], $row[$i], $i);
                $this->set('name', ['statna'], $row[$i], $i);
            }
        }else if ($type == 'attach_budget') {
            for ($i = 0; $i < $rowcount; $i++) {
                $this->set('year', ['BWORK_YEAR'], $row[$i], $i);
                $this->set('sum', ['BWORK_BUDG'], $row[$i], $i);
                $this->set('office', ['BWORK_FUND'], $row[$i], $i);
                $this->set('unit', ['BWORK_UNITNAME'], $row[$i], $i);
                $this->set('number1', ['BWORK_AMOUNT1'], $row[$i], $i);
                $this->set('number2', ['BWORK_AMOUNT2'], $row[$i], $i);
                $this->set('number3', ['BWORK_AMOUNT3'], $row[$i], $i);
                $this->set('unitprice', ['BWORK_COST'], $row[$i], $i);
                $this->set('account', ['BWORK_CODE_NA'], $row[$i], $i);
                $this->set('use', ['BWORK_USE_NA'], $row[$i], $i);
                $this->set('note', ['BWORK_COMM'], $row[$i], $i);
                $this->set('page', ['BWORK_IDX_PAGE'], $row[$i], $i);
            }
        }else if ($type == 'attach_office') {
            for ($i = 0; $i < $rowcount; $i++) {
                $this->set('year', ['FY'], $row[$i], $i);
                $this->set('id', ['ORG_NO'], $row[$i], $i);
                $this->set('name', ['ORG_NAME'], $row[$i], $i);
            }
        }else if ($type == 'srcid') {
            for ($i = 0; $i < $rowcount; $i++) {
                $this->set('year', ['bgtyy'], $row[$i], $i);
                $this->set('id', ['srcid'], $row[$i], $i);
                $this->set('name', ['srcna'], $row[$i], $i);
                $this->set('office', ['borg'], $row[$i], $i);
            }
        }
    }

    public function set($var, $target, $now, $i)
    {
        if (in_array($now, $target)) {
            $this->$var = $i;
        }
    }

    public function get($name, $row)
    {
        if ($this->$name != 0 && !$this->$name) {
            return 0;
        }
        return $row[$this->$name];
    }
}
