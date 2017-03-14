<?php
ini_set('memory_limit', '1024M');

require 'jieba/vendor/multi-array/MultiArray.php';
require 'jieba/vendor/multi-array/Factory/MultiArrayFactory.php';
require 'jieba/class/Jieba.php';
require 'jieba/class/Finalseg.php';
use Fukuball\Jieba\Finalseg;
use Fukuball\Jieba\Jieba;

$condition_list = ['year', 'kind','type', 'office', 'srcid', 'obj', 'statcd', 'account', 'use','note','sum_min','sum_max','committee'];
foreach ($condition_list as $value) {
    $$value = $_GET[$value];
}

function bind($nomatch = '')
{
    global $condition_list, $result, $search;
    foreach ($condition_list as $value) {
        global $$value;
        if (!$$value) {
            continue;
        }
        if ($value === 'note') {
            for ($i = 0; $i < count($note); $i++) {
                $result->bindValue(":note" . $i, "%" . $note[$i] . "%");
                if (!$nomatch) {
                    $result->bindValue(":notematch" . $i, $note[$i]);
                }
            }
        } else if ($value === 'office') {
            if ($$value=='00000') {
                
            } else if (preg_match("/^(\d\d)000$/", $$value, $match)) {
                $result->bindValue(":$value", $match[1] . "___");
            } else if (preg_match("/^(\d{2,6})$/", $$value, $match) && $kind=='attach') {
                $result->bindValue(":$value", $match[1] . "%");
            } else {
                $result->bindValue(":$value", $$value);
            }
        }else if($value==='obj'){
            if (preg_match("/(\d\d)00/", $$value, $match)) {
                $result->bindValue(":$value", $match[1] . "__");
            }else{
                $result->bindValue(":$value", $$value);
            }
        } else {
            $result->bindValue(":$value", $$value);
        }
    }
}

$condition  = "";
$match      = "";
$match_sort = '';

if ($_GET['order']==='ASC') {
    $order='ASC';
}else{
    $order='DESC';
}

foreach ($condition_list as $value) {
    if (!$$value) {
        continue;
    }
    if ($value === 'note') {
        $result=$db->prepare("SELECT word FROM search WHERE sentence=:sentence ");
        $result->bindValue(":sentence",$note);
        $result->execute();
        if ($row=$result->fetch()) {
            $note=explode(',', $row['word']);
        }else{
            $origin_note=$note;
            Jieba::init(array('mode' => 'default', 'dict' => 'big'));
            Finalseg::init();
            $note = Jieba::cutForSearch($note);
            $result=$db->prepare("INSERT INTO search(sentence,word) VALUES(:sentence,:word)");
            $result->bindValue(":sentence",$origin_note);
            $result->bindValue(":word",implode(',',$note));
            $result->execute();
        }

        $condition .= "AND( 1=2";
        $match .= ",0";

        for ($i = 0; $i < count($note); $i++) {
            $condition .= " OR budget.note LIKE :note" . $i;
            $match .= " + sign(LOCATE(:notematch" . $i . ",note))";
        }

        $condition .= ")";
        $match .= " as `match`";
        $match_sort = "`match` DESC,";
    } else if ($value === 'office') {
        if ($$value=='00000') {
                
        }else {
            $condition .= " AND budget.$value LIKE :$value ";
        }
    }else if($value === 'obj'){
        if (preg_match("/(\d\d)00/", $$value)) {
            $condition .= " AND budget.$value LIKE :$value ";
        } else {
            $condition .= " AND budget.$value=:$value ";
        }
    } else if ($value==='sum_min') {
        $condition .= " AND budget.sum>=:$value ";
    }else if ($value==='sum_max') {
        $condition .= " AND budget.sum<=:$value ";
    }else if($value==='committee'){
        
    }else {
        $condition .= " AND budget.$value=:$value ";
    }
}

if ($committee) {
    $condition_committee= "AND committee=:$value ";
}else{
    $condition_committee='';
}