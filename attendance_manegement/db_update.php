<?php
#-----------------------------------------------------------
# 基本設定
//ファイルの読み込み
//[読込順]function.php > time_card_function.php > insert_record.php(現在地)
include('time_card_function.php');

#-----------------------------------------------------------
# フォームと受取りと処理の分岐
$in = parse_form();
if(!empty($in)){
    switch ($in["mode"]) {

        case "employee_registration":
            employee_registration();
            break;
        
        case "shift":
            shift();
            break;

        case "delete":
            delete();
            break;
        }
}

#-----------------------------------------------------------
# 従業員登録処理
function employee_registration(){
    //変数定義
    global $in;
    $pass_key = "1234".$in["pass"];
    $pass = password_hash($in["pass"], PASSWORD_DEFAULT);// パスワードの暗号化


    //'employee_data'テーブルへの書込み
    $SQL = <<<_SQL_
    INSERT INTO employee_data 
    ( `id`, `work_state`, `name`,`kana_name`, `pass_key` ) 
    VALUES ( ?,?,?,?,?)
    _SQL_;
    $DATA = array($in["id"], "outWork", $in["name"], $in["kana_name"], $pass_key);
    insert_db($SQL,$DATA);  


    //'pass_data'テーブルへの書込み
    $SQL = <<<_SQL_
    INSERT INTO pass_data ( `pass_id`, `pass`) 
    VALUES ( ?,? )
    _SQL_;
    $DATA = array($pass_key, $pass);
    insert_db($SQL,$DATA);  
    

}

#-----------------------------------------------------------
# シフト登録処理
function shift(){
    $shift_pattern = [];
    $shift_pattern[0] = array("08:00:00", "16:00:00", "11:00:00,11:30:00,14:00:00,14:30:00,",);
    $shift_pattern[1] = array("10:00:00", "15:00:00", "12:00:00,12:30:00,",);
    $shift_pattern[2] = array("12:00:00", "17:00:00", "13:00:00,13:30:00,",);
    $shift_pattern[3] = array("17:00:00", "22:00:00", "19:00:00,19:30:00,",);
    $shift_pattern[4] = array("18:00:00", "22:00:00", "20:00:00,20:30:00,",);
    $shift_pattern[5] = array("22:00:00", "08:00:00", "01:00:00,02:00:00,03:00:00,04:00:00,",);
    $shift_pattern[6] = array("22:00:00", "05:00:00", "02:00:00,03:00:00,",);
    
    $employees = [];
    $employees["0001"]["employee_name"] = "鈴木　一郎";
    $employees["0001"]["job"][]= array("week"=>"1", "shift"=>$shift_pattern[0],);
    $employees["0001"]["job"][]= array("week"=>"2", "shift"=>$shift_pattern[0],);
    $employees["0001"]["job"][]= array("week"=>"3", "shift"=>$shift_pattern[0],);
    $employees["0001"]["job"][]= array("week"=>"4", "shift"=>$shift_pattern[0],);
    $employees["0001"]["job"][]= array("week"=>"5", "shift"=>$shift_pattern[0],);
    
    $employees["0002"]["employee_name"] = "鈴木　二郎";
    $employees["0002"]["job"][]= array("week"=>"1", "shift"=>$shift_pattern[1],);
    $employees["0002"]["job"][]= array("week"=>"2", "shift"=>$shift_pattern[1],);
    $employees["0002"]["job"][]= array("week"=>"3", "shift"=>$shift_pattern[1],);
    $employees["0002"]["job"][]= array("week"=>"4", "shift"=>$shift_pattern[1],);
    $employees["0002"]["job"][]= array("week"=>"5", "shift"=>$shift_pattern[1],);
    
    $employees["0003"]["employee_name"] = "鈴木　三郎";
    $employees["0003"]["job"][]= array("week"=>"1", "shift"=>$shift_pattern[2],);
    $employees["0003"]["job"][]= array("week"=>"2", "shift"=>$shift_pattern[2],);
    $employees["0003"]["job"][]= array("week"=>"3", "shift"=>$shift_pattern[2],);
    $employees["0003"]["job"][]= array("week"=>"4", "shift"=>$shift_pattern[2],);
    $employees["0003"]["job"][]= array("week"=>"5", "shift"=>$shift_pattern[2],);
    
    $employees["0004"]["employee_name"] = "鈴木　四郎";
    $employees["0004"]["job"][]= array("week"=>"0", "shift"=>$shift_pattern[0],);
    $employees["0004"]["job"][]= array("week"=>"0", "shift"=>$shift_pattern[3],);
    $employees["0004"]["job"][]= array("week"=>"1", "shift"=>$shift_pattern[3],);
    $employees["0004"]["job"][]= array("week"=>"2", "shift"=>$shift_pattern[3],);
    $employees["0004"]["job"][]= array("week"=>"3", "shift"=>$shift_pattern[3],);
    $employees["0004"]["job"][]= array("week"=>"6", "shift"=>$shift_pattern[0],);
    $employees["0004"]["job"][]= array("week"=>"6", "shift"=>$shift_pattern[3],);
    
    $employees["0005"]["employee_name"] = "鈴木　五郎";
    $employees["0005"]["job"][]= array("week"=>"0", "shift"=>$shift_pattern[2],);
    $employees["0005"]["job"][]= array("week"=>"0", "shift"=>$shift_pattern[4],);
    $employees["0005"]["job"][]= array("week"=>"1", "shift"=>$shift_pattern[4],);
    $employees["0005"]["job"][]= array("week"=>"4", "shift"=>$shift_pattern[3],);
    $employees["0005"]["job"][]= array("week"=>"5", "shift"=>$shift_pattern[3],);
    $employees["0005"]["job"][]= array("week"=>"6", "shift"=>$shift_pattern[2],);
    $employees["0005"]["job"][]= array("week"=>"6", "shift"=>$shift_pattern[4],);
    
    $employees["0006"]["employee_name"] = "鈴木　六郎";
    $employees["0006"]["job"][]= array("week"=>"0", "shift"=>$shift_pattern[1],);
    $employees["0006"]["job"][]= array("week"=>"3", "shift"=>$shift_pattern[4],);
    $employees["0006"]["job"][]= array("week"=>"4", "shift"=>$shift_pattern[4],);
    $employees["0006"]["job"][]= array("week"=>"5", "shift"=>$shift_pattern[4],);
    $employees["0006"]["job"][]= array("week"=>"6", "shift"=>$shift_pattern[1],);
    
    $employees["0007"]["employee_name"] = "鈴木　七郎";
    $employees["0007"]["job"][]= array("week"=>"0", "shift"=>$shift_pattern[5],);
    $employees["0007"]["job"][]= array("week"=>"2", "shift"=>$shift_pattern[4],);
    $employees["0007"]["job"][]= array("week"=>"4", "shift"=>$shift_pattern[6],);
    $employees["0007"]["job"][]= array("week"=>"5", "shift"=>$shift_pattern[6],);
    $employees["0007"]["job"][]= array("week"=>"6", "shift"=>$shift_pattern[5],);
    
    $employees["0008"]["employee_name"] = "鈴木　八郎";
    $employees["0008"]["job"][]= array("week"=>"1", "shift"=>$shift_pattern[5],);
    $employees["0008"]["job"][]= array("week"=>"2", "shift"=>$shift_pattern[5],);
    $employees["0008"]["job"][]= array("week"=>"3", "shift"=>$shift_pattern[5],);
    $employees["0008"]["job"][]= array("week"=>"4", "shift"=>$shift_pattern[5],);
    $employees["0008"]["job"][]= array("week"=>"5", "shift"=>$shift_pattern[5],);
    
    $employees["0009"]["employee_name"] = "鈴木　九郎";
    $employees["0009"]["job"][]= array("week"=>"1", "shift"=>$shift_pattern[6],);
    $employees["0009"]["job"][]= array("week"=>"2", "shift"=>$shift_pattern[6],);
    $employees["0009"]["job"][]= array("week"=>"3", "shift"=>$shift_pattern[6],);
    $employees["0009"]["job"][]= array("week"=>"6", "shift"=>$shift_pattern[6],);
    $employees["0009"]["job"][]= array("week"=>"0", "shift"=>$shift_pattern[6],);
    

    global $in;
    $select_year = $in["year"];
    $select_month = $in["month"];

    $last_year =  ($select_month+1 == 13) ? $select_year+1 : $select_year;
    $last_month = ($select_month+1 == 13) ? 1 : $select_month+1;
    $last_dt = new DateTime(sprintf("%04d-%02d-01",$last_year,$last_month));
    $last_dt->modify('-1 day');
    $last_date = $last_dt->format('d'); //月末日
    // echo $last_dt->format('Y-m-d');

    // 1.指定月初めから末まで繰り返し
    for ($i=1; $i <= $last_date; $i++) { 
        $dt = new DateTime(sprintf("%04d-%02d-%02d",$select_year,$select_month,$i));
        // echo $dt->format('Y-m-d(w)');
        // echo"<br>";
        $date = $dt->format('Y-m-d');
        $week = $dt->format('w');

        // 2.従業員の数だけ繰り返し
        foreach ($employees as $employee_id => $employee) {

            foreach ($employee["job"] as $key => $val) {
                if($val["week"] == $week){// シフトの曜日が同じ時
                    $in_time = $val["shift"][0];
                    $out_time = $val["shift"][1];
                    $break = $val["shift"][2];

                    $SQL = <<<_SQL_
                    INSERT INTO work_record 
                    ( `employee_id`, `employee_name`, `in_day`, `in_time`, `out_time`, `break`) 
                    VALUES ( ?,?,?,?,?,?)
                    _SQL_;                
                    
                    //日をまたぐ出勤かどうかチェック
                    if($out_time > $in_time){ 
                        $DATA = 
                        array(
                            $employee_id, $employee["employee_name"],  
                            $date, $in_time, $out_time, $break
                        );
                        insert_db($SQL,$DATA); 
                        // echo $employee["employee_name"]."： ".$in_time." ".$out_time." ".$break."<br>";
                    }
                    else{ //日をまたぐ出勤の時
                        $in_time1 = $in_time;
                        $out_time1 = "24:00:00";
                        $break1 = "";
                        $DATA = array(
                            $employee_id, $employee["employee_name"],  
                            $date, $in_time1, $out_time1, $break1
                        );
                        insert_db($SQL,$DATA); 
                        // echo $employee["employee_name"]."： ".$in_time1." ".$out_time1." ".$break1."<br>";
                        
                        if($dt->format('d')+1 > $last_date){ //もし、翌日が月末以降の時、
                            if($dt->format('m') == 12){
                                $date2 = sprintf("%04d-01-01d",$dt->format('Y')+1);
                            }else{
                                $date2 = sprintf("%04d-%02d-01",$dt->format('Y'),intval($dt->format('m'))+1);
                            }
                        }else{
                            $date2 = sprintf("%04d-%02d-%02d",$dt->format('Y'),$dt->format('m'),intval($dt->format('d'))+1);
                        }

                        $in_time2 = "00:00:00";
                        $out_time2 = $out_time;
                        $break2 = $break;
                        $DATA = array(
                            $employee_id, $employee["employee_name"],  
                            $date2, $in_time2, $out_time2, $break2
                        );
                        insert_db($SQL,$DATA); 
                        // echo $employee["employee_name"]."： ".$in_time2." ".$out_time2." ".$break2."<br>";

                    }
                }
            }
        }        
    }
}


function delete(){
    global $in;
    $select_year = $in["year"];
    $select_month = $in["month"];
    $farst_dt = new DateTime(sprintf("%04d-%02d-01",$select_year,$select_month));
    $farst_date = $farst_dt->format('Y-m-d'); //月初め

    $select_year =  ($select_month+1 == 13) ? $select_year +1 : $select_year;
    $select_month = ($select_month+1 == 13) ? 1 : $select_month +1;

    $last_dt = new DateTime(sprintf("%04d-%02d-01",$select_year,$select_month));
    $last_dt->modify('-1 day');
    $last_date = $last_dt->format('Y-m-d'); //月末

    $SQL = <<<_SQL_
    DELETE FROM work_record WHERE in_day >= ?
    AND in_day <= ?
    _SQL_; 
    $DATA = array($farst_date, $last_date);
    insert_db($SQL,$DATA); 

    

}


#-----------------------------------------------------------
# 表示エリア
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>従業員登録</title>
    <!-- <link rel="stylesheet" href="../common/css/style.css"> -->
</head>
<body>

    <h2>従業員登録</h2>
    <form action="#" method="post">
        <input type="hidden" name="mode" value="employee_registration">
        <label for="id">ID（数字4桁）</label>
        <input id="id" type="text" name="id" require>
        <br>
        <label for="name">名前</label>
        <input id="name" type="text" name="name" require>
        <br>
        <label for="kana_name">ふりがな</label>
        <input id="kana_name" type="text" name="kana_name" require>
        <br>
        <label for="pass">PASS</label>
        <input id="pass" type="text" name="pass" require>
        <br>
        <button type="submit">登録</button>
    </form>
    <br>

    <h2>シフト登録</h2>
    <form action="#" method="post">
        <input type="hidden" name="mode" value="shift">
        <input id="date" type="text" name="year" require>
        <label for="date" cols="3">年</label>
        <input id="month" type="text" name="month" require>
        <label for="month" cols="3">月</label>
        <br>
        <button class="shift_input" type="submit">シフト登録</button>
    </form>
    <br>

    <h2>シフト削除</h2>
    <form action="#" method="post">
        <input type="hidden" name="mode" value="delete">
        <input id="date" type="text" name="year" require>
        <label for="date" cols="3">年</label>
        <input id="month" type="text" name="month" require>
        <label for="month" cols="3">月</label>
        <br>
        <button class="shift_input" type="submit">シフト削除</button>
    </form>


    <div class="comment"></div>
</body>
<style>
    #month, #date{width: 3em;}
    input{
        
    }
    .comment{
        width: 300px;
        height: 3em;
        border: solid 2px #000;
    }
</style>
</html>

