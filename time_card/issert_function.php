<?php
#===========================================================
# 打刻内容を登録する関数ファイル (issert_function.php)
#===========================================================
# 基本設定
#ファイルの読み込み
//[読込順]function.php　> class.php > issert_function.php(現在地)
include('class.php');

//時間設定
$today = [];
$today["DT"] = new DateTime();
$today["day"] = $today["DT"]->format('Y-m-d');
$today["time"] = $today["DT"]->format('H:i:s');

#===========================================================
# "採番の"読込み・書込みの関数
#-----------------------------------------------------------
# 採番読み込み
function read_work_num()
{
    $SQL = "SELECT `work_num` FROM `employee_data` WHERE `id` = ? ";
    $DATA = array($_SESSION["employee_id"]); //プリペアーステートメントの値
    $work_num = select_db($SQL,$DATA);

    return $work_num[0]["work_num"];
}
// $work_num = read_work_num();

#　採番書き込み
function wright_work_num($work_num)
{
    $SQL = "UPDATE `employee_data` SET `work_num` = ? WHERE `id` = ? ";
    $DATA = array($work_num, $_SESSION["employee_id"]);
    insert_db($SQL,$DATA);  
}


#===========================================================
# セッション・DBの出勤状況を更新
#-----------------------------------------------------------
function update_work_state()
{
    global $work_state;

    //----------------------------------
    //セッションの出勤状況を更新
    if($work_state == "outBreak"){$work_state = "inWork";}//"休憩終了"⇒"勤務開始"
    $_SESSION["work_state"] = $work_state;//セッションの値を変更

    // ＤＢの出勤状況を出勤状況を更新
    $SQL = "UPDATE employee_data SET work_state = ? WHERE `employee_data`.`id` = ? ";
    $DATA = array($work_state,$_SESSION["employee_id"]);//プリペアーステートメントの値
    insert_db($SQL,$DATA);  //関数の実行
    //----------------------------------
}


#===========================================================
# "出勤"登録の関数
#-----------------------------------------------------------
function inWork()
{ 
    global $work_num, $today; 

    # 重複挿入のチェック
    # 新しいレコードの登録
    if($_SESSION["work_state"] == "outWork")
    {   
        //採番の更新と保存
        $work_num ++; 
        wright_work_num($work_num); 

        $SQL = "INSERT INTO work_record 
        ( `employee_id`, `employee_name`,`work_num`, `in_day`,`in_time` ) 
        VALUES ( ?,?,?,?,?)";
        $DATA = array(
            $_SESSION["employee_id"], $_SESSION["employee_name"], $work_num, $today["day"], $today["time"] 
        );
        insert_db($SQL,$DATA);
        
        // セッション・DBの出勤状況を更新
        update_work_state();
    }
}


#===========================================================
# "退勤"登録の関数
#-----------------------------------------------------------

function outWork()
{
    global $work_num, $today; 

   
    # 退勤時間を"UPDATE"で登録
    if( $_SESSION["work_state"] == "inWork") //二重投防止チェック
    {
        # --レコードの読み込み--
        $SQL = "SELECT * FROM work_record WHERE `employee_id` = ? 
        AND `work_num` = ?";
        $DATA = array($_SESSION["employee_id"], $work_num); //プリペアーステートメントの値
        $datas_array = select_db($SQL,$DATA); //レコード読込み関数の実行
        $datas_array = $datas_array[0];
    
        # 退勤入力が当日中か、日付を超えるかのチェック
        $DT_in_day = new DateTime( $datas_array["in_day"]);
        $DT_today = new DateTime();
        $diff = $DT_today ->diff($DT_in_day);
        $diff_day = $diff->format('%a'); //出勤入力日と退勤入力日の日付差
    
        if(empty($diff_day))
        {
            # 退勤入力日＝出勤日のとき
            $datas_array["out_time"] = $today["time"]; //配列に退勤時間を追加

            //労働時間の集計
            $aggregates = new Aggregates($datas_array);

            // 集計結果の追加
            $datas_array = array_merge($datas_array, $aggregates->getData());

            $SQL = "UPDATE work_record SET `out_time` = ?,
             `RestrainTime` = ?, `BreakTime` = ?, `WorkingTime` = ?
            WHERE `employee_id` = ? AND `work_num` = ?";

            $DATA = array($datas_array["out_time"], 
            $datas_array["RestrainTime"], 
            $datas_array["BreakTime"], 
            $datas_array["WorkingTime"], 
             $_SESSION["employee_id"], $work_num);

            insert_db($SQL,$DATA);  
        }
        else
        {
            # 日を跨いでの退勤入力の時
            $datas_array["out_time"] = "24:00:00";

            // 1.出勤日の出勤データ登録
            $aggregates = [];
            //労働時間の集計
            $aggregates[0] = new Aggregates($datas_array);
            // 集計結果の追加
            $datas_array = array_merge($datas_array, $aggregates[0]->getData());
    
            $SQL = "UPDATE work_record SET `out_time` = ?,
             `RestrainTime` = ?, `BreakTime` = ?, `WorkingTime` = ?
            WHERE `employee_id` = ? AND `work_num` = ?";

            $DATA = array("24:00:00", 
            $datas_array["RestrainTime"], 
            $datas_array["BreakTime"], 
            $datas_array["WorkingTime"], 
            $_SESSION["employee_id"], $work_num);

            insert_db($SQL,$DATA);  

            $DT_in_day->modify('+1 day');
    

            // 2.出勤日翌日以降の出勤データ登録
            for ($i=1; $i <= $diff_day; $i++) 
            { 
                $work_num ++;

                $datas_array = array(
                    "employee_id" => $_SESSION["employee_id"], 
                    "employee_name" => $_SESSION["employee_name"],
                    "work_num" => $work_num,
                    "in_day" => $DT_in_day->format("Y-m-d"),
                    "in_time" => "00:00:00",
                    "out_time" => "24:00:00",
                    "break" => NULL,
                );  
                
                //最終日には、入力した退勤時間を代入
                if($i == $diff_day){ $datas_array["out_time"] = $today["time"];}
                
                //労働時間の集計
                $aggregates[$i] = new Aggregates($datas_array);

                // 集計結果の追加
                $datas_array = array_merge($datas_array, $aggregates[$i]->getData());

                $DT_in_day->modify('+1 day');
        
                // 新しいレコードの登録
                $SQL = "INSERT INTO work_record 
                ( `employee_id`, `employee_name`,`work_num`, `in_day`,`in_time`, 
                `out_time`, `break`, `RestrainTime`, `BreakTime`, `WorkingTime` ) 
                VALUES (?,?,?,?,?,?,?,?,?,?)";
                $DATA = array_values($datas_array);
                insert_db($SQL,$DATA);  
        
            } //endfor

            //採番の書き込み
            wright_work_num($work_num);

    
        } //endif(empty($diff_day))

        // セッション・DBの出勤状況を更新
        update_work_state();

    }//endif(empty($out_time))

    
}


#===========================================================
# "休憩"登録の関数
#-----------------------------------------------------------
function in_out_break($in_out){
    global $work_num, $today; 

    //二重投稿防止対策
    switch ($in_out) {
        case "in":
            $chake = $_SESSION["work_state"] == "inWork" ? true :false;
        break;
        case "out":
            $chake = $_SESSION["work_state"] == "inBreak" ? true :false;
        break;
        default:
            $chake = false;
        break;
    }
    if($chake)
    {
        # --レコードの読み込み--
        $SQL ="SELECT * FROM work_record
        WHERE `employee_id` = ? AND `work_num` = ?";
        $DATA = array($_SESSION["employee_id"], $work_num);
        $datas_array = select_db($SQL,$DATA);
        $datas_array = $datas_array[0];
    
        # 休憩入力が当日中か、日付を超えるかのチェック
        $DT_in_day = new DateTime($datas_array["in_day"]);
        $DT_today = new DateTime();
        $diff = $DT_today ->diff($DT_in_day);
        $diff_day = $diff->format('%a'); //出勤入力日と退勤入力日の日付差
    
        if(empty($diff_day))
        {
            # 休憩入力日＝出勤日の時
    
            //休憩時間を代入
            $datas_array["break"] .= $today["time"].",";
        
            $SQL = "UPDATE work_record SET `break` = ?
            WHERE `employee_id` = ? AND `work_num` = ? ";
            $DATA = array($datas_array["break"], $_SESSION["employee_id"], $work_num);
            insert_db($SQL,$DATA);
    
        }
        else
        {
            # 日を跨いでの休憩入力の時
            // 1.記録する内容の分岐処理
            $break_datas = [];
    
            switch ($in_out) {
                case "in":
                    $break_datas = array(
                        0 => $datas_array["break"], //出勤入力日の休憩情報
                        1 => NULL, //間日の休憩情報
                        2 => $today["time"].",", //退勤日の休憩情報
                    );
                break;
                case "out":
                    $break_datas = array(
                        0 => $datas_array["break"]."24:00:00,", //出勤入力日の休憩情報
                        1 => "00:00:00,24:00:00,", //間日の休憩情報
                        2 => "00:00:00,".$today["time"].",", //退勤日の休憩情報
                    );
                break;
            }
    
            // 2.出勤日の出勤データ登録
            $aggregates = [];
            $datas_array["break"] = $break_datas[0];
            $datas_array["out_time"] = "24:00:00";
    
            //労働時間の集計
            $aggregates[0] = new Aggregates($datas_array);
            // 集計結果の追加
            $datas_array = array_merge($datas_array, $aggregates[0]->getData());
    
            $SQL = "UPDATE work_record SET `break`= ?,`out_time` = ?,
            `RestrainTime` = ?, `BreakTime` = ?, `WorkingTime` = ?
            WHERE `employee_id` = ? AND `work_num` = ?";
    
            $DATA = array(
            $datas_array["break"], 
            $datas_array["out_time"],
            $datas_array["RestrainTime"], 
            $datas_array["BreakTime"], 
            $datas_array["WorkingTime"], 
            $_SESSION["employee_id"], $work_num);
    
            insert_db($SQL,$DATA);
    
            $DT_in_day->modify('+1 day');
    
            // 3.出勤日翌日以降の出勤データ登録
            for ($i=1; $i <= $diff_day; $i++) 
            { 
                $work_num ++;
    
                $datas_array = array(
                    "employee_id" => $_SESSION["employee_id"], 
                    "employee_name" => $_SESSION["employee_name"],
                    "work_num" => $work_num,
                    "in_day" => $DT_in_day->format("Y-m-d"),
                    "in_time" => "00:00:00",
                    "out_time" => "24:00:00",
                    "break" => $break_datas[1],
                );     
    
                //最終日とそれ以外の日の処理分岐
    
                    //労働時間の集計
                    $aggregates[$i] = new Aggregates($datas_array);
    
                    // 集計結果の追加
                    $datas_array = array_merge($datas_array, $aggregates[$i]->getData());
    
                    $DT_in_day->modify('+1 day');
    
                if($i == $diff_day)
                { 
                    $datas_array["out_time"] = NULL;
                    $datas_array["break"] = $break_datas[2];
                    $datas_array["RestrainTime"] = NULL;
                    $datas_array["BreakTime"] = NULL;
                    $datas_array["WorkingTime"] = NULL;
                }
    
                // 新しいレコードの登録
                $SQL = "INSERT INTO work_record 
                ( `employee_id`, `employee_name`,`work_num`, `in_day`,`in_time`, 
                `out_time`, `break`, `RestrainTime`, `BreakTime`, `WorkingTime` ) 
                VALUES (?,?,?,?,?,?,?,?,?,?)";
                $DATA = array_values($datas_array);
                insert_db($SQL,$DATA);  
    
            } //endfor
    
            //採番の書き込み
            wright_work_num($work_num);
    
        } //endif(empty($diff_day))

        // セッション・DBの出勤状況を更新
        update_work_state();

    } //endif($chake)

}


