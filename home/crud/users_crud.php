<?php
declare (strict_types = 1);

use PHPUnit\Framework\TestCase;

include '/docker/home/db/db.php';  
include '/docker/home/functions.php';

$table 	    = $_POST['table'];
$crud_tp 	= $_POST['crud_tp'];
$user_info  = $_POST['user_info'];
$where_map  = $_POST['where_map'];

$conds  = [];
$select = 'rownum';

file_put_contents('debug.txt',"'.input==.','".$table."','".$crud_tp."','".$user_info."','".$where_map."',\n");
file_put_contents('debug',"'.parseRecord.','".$crud_tp."','".$user_info."',\n", FILE_APPEND | LOCK_EX);


if ($crud_tp == 'insert') {
    db()->insert($table, $user_info);
    echo '1';
} elseif($crud_tp == 'update') {
    db()->update($table, $user_info, $where_map);
    $row = db()->row($table, $where_map);
    echo json_encode($row);
} elseif($crud_tp == 'delete') {
    db()->delete($table, $where_map);
    //삭제 후 조회값 리턴
    $rows = db()->rows($table);
    echo json_encode($rows);
    // 아래와 같이 하면 테이블의 타입대로 넘겨준다.
    // echo trim(json_encode($row_set,JSON_NUMERIC_CHECK));
} elseif($crud_tp == 'ranking') {
    // $rows = db()->ranking($table, $where_map);
    $rows = db()->rows($table);
    echo json_encode($rows);
} elseif($crud_tp == 'all') {
    $rows = db()->rows($table);
    echo json_encode($rows);
} elseif($crud_tp == 'mine') {
    $row = db()->row($table, $where_map);
    echo json_encode($row);
} else {
    echo 'fail';
}