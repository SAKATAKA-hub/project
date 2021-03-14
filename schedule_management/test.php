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
    $SQL = <<<_SQL_
    INSERT INTO contract_working_days 
    ( `employee_id`, `working_week`, `in_time`, `out_time`, `break`) 
    VALUES ( ?,?,?,?,?)
    _SQL_;
    $DATA = array(
        $_SESSION["employee_id"], $in["month"], $shift, 
        $in["comment"], $nowDT->format("Y-m-d")
    );

    insert_db($SQL,$DATA);
}




?>
<h2>契約曜日の挿入</h2>
<form action="#" method="GET">
<input type="hidden" name="mode" value="change_befor">
<p>従業員ID</p>
<input type="text" name="employee_id" >
<button >登録</button>    
</form>               
