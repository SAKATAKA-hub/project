<?php 
#===========================================================
# 契約曜日の挿入
#===========================================================
# 基本設定
//ファイルの読み込み
//[読込順]function.php > submit_schedule_function.php(現在地)
include('../common/app/function.php');

$in = parse_form(); //フォームの受取り


// case1. フォームを受け取ったとき
if(isset($in["mode"]))
{
    $employee_id = "0009";
    $employees = [];

    // for ($i=1; $i <= 5; $i++) { 
    //     $employees[$employee_id][] = array($i,
    //     "22:00:00", "08:00:00", "01:00:00,02:00:00,03:00:00,04:00:00,",);
    // }   
    $employees[$employee_id][] = array(1,
    "22:00:00", "05:00:00", "02:00:00,03:00:00,",);
    $employees[$employee_id][] = array(2,
    "22:00:00", "05:00:00", "02:00:00,03:00:00,",);
    $employees[$employee_id][] = array(3,
    "22:00:00", "05:00:00", "02:00:00,03:00:00,",);
    $employees[$employee_id][] = array(6,
    "22:00:00", "05:00:00", "02:00:00,03:00:00,",);
    $employees[$employee_id][] = array(0,
    "22:00:00", "05:00:00", "02:00:00,03:00:00,",);




    
    foreach ($employees[$employee_id] as $key => $value) {
        $SQL = <<<_SQL_
        INSERT INTO contract_working_days 
        ( `employee_id`, `working_week`, `in_time`, `out_time`, `break`) 
        VALUES ( ?,?,?,?,?)
        _SQL_;
        $DATA = array($employee_id,$value[0], $value[1], $value[2], $value[3], );
        insert_db($SQL,$DATA);
    }
}




?>
<h2>契約曜日の挿入</h2>
<form action="#" method="GET">
<input type="hidden" name="mode" value="change_befor">
<p>従業員ID</p>
<input type="text" name="employee_id" >
<button >登録</button>    
</form>               
