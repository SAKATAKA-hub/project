<?php
#===========================================================
# submit_schedule_function.php スケジュール提出プログラム用関数
#===========================================================
# 基本設定
//ファイルの読み込み
//[読込順]function.php > submit_schedule_function.php(現在地)
include('../common/app/function.php');

# 日付の定義 今のDT_OB
$nowDT = new DateTime("");
$now = [];
$now["Y"] = intval($nowDT->format("Y"));
$now["m"] = intval($nowDT->format("m"));
$now["d"] = intval($nowDT->format("d"));

$display = []; //表示用日付


#===========================================================
# 関数の定義
#-----------------------------------------------------------
# デフォルトモード関数
function normal_processing(){
    global $in, $now, $display;

    var_dump($now);

    # 1.表示用日付の定義
    //月の後期なら、翌月前期を入力画面に表示
    if($now["d"]>15) 
    {
        $display["Y"] = $now["m"] == 12 ? $now["Y"] +1 : $now["Y"] ; //表示"年"
        $display["m"] = $now["m"] == 12 ? 1: $now["m"] +1 ; //表示"月"
        $display["d"] = 1;
        $display["half"] = "first"; //前期
    }
    //月の前期なら、同月後期を入力画面に表示
    else
    {
        $display["Y"] = $now["Y"]; //表示"年"
        $display["m"] = $now["m"]; //表示"月"
        $display["d"] = 16;
        $display["half"] = "second"; //後期    
    }

    $displayDT_key = sprintf("%04d-%02d-%02d",$display["Y"] ,$display["m"] ,$display["d"]);
    $displayDT = new DateTime($displayDT_key);
    $display["week"] = $displayDT->format("w"); //月初日の曜日

    
    $end_Y = $display["m"] == 12 ? $display["Y"] +1 : $display["Y"] ;
    $end_m = $display["m"] == 12 ? 1: $display["m"] +1 ;
    $endDT = new DateTime(sprintf("%04d-%02d-00",$end_Y,$end_m));
    $display["end_d"] = intval($endDT->format("d")); //月末日

        
}


#-----------------------------------------------------------
# スケジュール提出($in["mode"]=="submit")を受け取ったとき実行する関数
function submit(){
    global $nowDT, $in, $now, $display;

    # sub1.入力スケジュールを一つの文字列に変換する
    $shift =[]; //提出スケジュール時間を格納
    for ($i=1; $i <= 31 ; $i++)
    {
        $shift[$i] = [];
        foreach ($in as $form_name => $form_val) 
        {
            $form_nom = substr($form_name,-2); //行の番号(日付)
            if($form_nom == $i){
                $shift[$i][] = $form_val;
            }
        }

        //空要素の削除
        foreach ($shift[$i] as $key => $value) {
            if(empty($value)){unset($shift[$i][$key]);}
        }
        $shift[$i] = implode(" ",$shift[$i]);

        //日付情報の追加
        $shift[$i] = empty($shift[$i]) ? "" : sprintf("%02d",$i)." ".$shift[$i] ;

    }

    //空要素の削除
    foreach ($shift as $key => $value) {
        if(empty($value)){unset($shift[$key]);}
    }
    $shift = implode(",",$shift); ////提出スケジュール時間文字列
    // echo $shift ."<br>";

    # sub2.入力データをデータベースに保存
    $SQL = <<<_SQL_
    INSERT INTO submission_shift 
    ( `employee_id`, `month`, `shift`, `comment`, `update_date`) 
    VALUES ( ?,?,?,?,?)
    _SQL_;
    $DATA = array(
        $_SESSION["employee_id"], $in["month"], $shift, 
        $in["comment"], $nowDT->format("Y-m-d")
    );

    insert_db($SQL,$DATA);


}


#-----------------------------------------------------------
# 選択月変更($in["mode"]=="change_next"or"change_befor")を
// 受け取ったとき実行する関数
function change(){
    global $in, $now, $display;

    $changeDT = new DateTime($in["month"]);
    $change = [];
    $change["Y"] = intval($changeDT->format("Y"));
    $change["m"] = intval($changeDT->format("m"));
    $change["d"] = intval($changeDT->format("d"));

    # change1.表示用日付の定義
    switch ($in["mode"])
    {
        // change1-1 $in["mode"]=="change_next"のとき
        case "change_next": 

            //月の後期なら、翌月前期を入力画面に表示
            if($change["d"]>15) 
            {
                $display["Y"] = $change["m"] == 12 ? $change["Y"] +1 : $change["Y"] ; //表示"年"
                $display["m"] = $change["m"] == 12 ? 1: $change["m"] +1 ; //表示"月"
                $display["d"] = 1;
                $display["half"] = "first"; //前期or後期
            }
            //月の前期なら、同月後期を入力画面に表示
            else
            {
                $display["Y"] = $change["Y"]; //表示"年"
                $display["m"] = $change["m"]; //表示"月"
                $display["d"] = 16;
                $display["half"] = "second"; //前期or後期    
            }
            break;

        // change1-2 $in["mode"]=="change_befor"のとき
        case "change_befor": 

            //月の前期なら、前月後期を入力画面に表示
            if($change["d"]<16) 
            {
                $display["Y"] = $change["m"] == 1 ? $change["Y"] -1 : $change["Y"] ; //表示"年"
                $display["m"] = $change["m"] == 1 ? 12: $change["m"] -1 ; //表示"月"
                $display["d"] = 16;
                $display["half"] = "second"; //前期or後期
            }
            //月の後期なら、同月前期を入力画面に表示
            else
            {
                $display["Y"] = $change["Y"]; //表示"年"
                $display["m"] = $change["m"]; //表示"月"
                $display["d"] = 1;
                $display["half"] = "first"; //前期or後期    
            }
            break;

    }

    //月末日と週の定義
    $displayDT_key = sprintf("%04d-%02d-%02d",$display["Y"] ,$display["m"] ,$display["d"]);
    $displayDT = new DateTime($displayDT_key);
    $display["week"] = $displayDT->format("w"); //月初日の曜日

    $end_Y = $display["m"] == 12 ? $display["Y"] +1 : $display["Y"] ;
    $end_m = $display["m"] == 12 ? 1: $display["m"] +1 ;
    $endDT = new DateTime(sprintf("%04d-%02d-00",$end_Y,$end_m));
    $display["end_d"] = intval($endDT->format("d")); //月末日

    // echo sprintf("%04d年%02d月%02d日(%d)%s",$display["Y"] ,$display["m"] ,$display["d"], $display["week"], $display["half"]);


}

#-----------------------------------------------------------
# 提出済スケジュールを反映する関数
function read_save_shift(){
    global $in, $now, $display;
     
    // 1.DBに保存された提出済みスケジュールを呼び出す。
    $value_month = 
    $display["half"] == "first"
    ? sprintf("%04d-%02d-01",$display["Y"],$display["m"])
    : sprintf("%04d-%02d-16",$display["Y"],$display["m"]) ;

    $SQL = <<<_SQL_
    SELECT shift, comment FROM submission_shift 
    WHERE employee_id = ? AND month = ?
    ORDER BY ss_id DESC
    _SQL_;

    $DATA = array($_SESSION["employee_id"], $value_month,);
    $data = select_db($SQL,$DATA);  //関数の実行（取得データをリターン） 

    // 2.DBより受け取った情報を加工
    if(!empty($data))
    {
        $data = $data[0];
    
        // 2-1　コメント
        $in["comment"] = isset($data["comment"]) ? $data["comment"] : "" ;
        
        //2-2スケジュール
        if(!empty($data["shift"])){
            $data["shift"] = explode(",",$data["shift"]);
    
            foreach($data["shift"] as $vals){
                $vals = explode(" ",$vals);
                $num = $vals[0];
                // 有給入力の時
                if($vals[1] == "paid_holiday")
                {
                    $in["paid_holiday".$num] = $vals[1];
                }
                // 出勤入力の時
                else
                {
                    $in["in1-".$num] = isset($vals[1]) ? $vals[1] : "" ;
                    $in["out1-".$num] = isset($vals[2]) ? $vals[2] : "" ;
                    $in["in2-".$num] = isset($vals[3]) ? $vals[3] : "" ;
                    $in["out2-".$num] = isset($vals[4]) ? $vals[4] : "" ;
                }
    
            }
    
        }
    
    } //if(!empty($data))end


}


#-----------------------------------------------------------
# 入力内容の表示をリセットする関数
function input_reset(){
    global $in, $now, $display;
    # sub3.表示用日付の定義
    $displayDT = new DateTime($in["month"]);
    $display["Y"] = intval($displayDT->format("Y")); //表示"年"
    $display["m"] = intval($displayDT->format("m"));; //表示"月"
    $display["d"] = intval($displayDT->format("d"));; //表示"日"
    $display["half"] = $display["d"]==1 ? "first" : "second"; //前期or後期 
    
    //月末日と週の定義
    $display["week"] = $displayDT->format("w"); //月初日の曜日

    $end_Y = $display["m"] == 12 ? $display["Y"] +1 : $display["Y"] ;
    $end_m = $display["m"] == 12 ? 1: $display["m"] +1 ;
    $endDT = new DateTime(sprintf("%04d-%02d-00",$end_Y,$end_m));
    $display["end_d"] = intval($endDT->format("d")); //月末日


}


#-----------------------------------------------------------
# 契約曜日通りに自動入力する関数
function fixed_input(){
    global $in, $now, $display;

    // 1.DBに保存された契約スケジュールを呼び出す。
    $employee_id = $_SESSION["employee_id"];

    $SQL = <<<_SQL_
    SELECT * FROM contract_working_days 
    WHERE employee_id = ?
    _SQL_;

    $DATA = array($employee_id,);
    $working_datas = select_db($SQL,$DATA);  //関数の実行（取得データをリターン）
    
    // 2.テーブルに取得情報を埋め込む。
    for ($i = 0; $i < ($display["end_d"] - $display["d"] +1); $i++) 
    { 
        $week_num = ($display["week"] + $i) %7;
        $date = $display["d"] + $i;
        $date = sprintf("%02d",$date);
        foreach ($working_datas as $working_data){
            if($working_data["working_week"] == $week_num)
            {
                //一つ目の入力のとき
                if(empty($in["in1-".$date]))
                {
                    $in["in1-".$date] = isset($working_data["in_time"]) ? substr( $working_data["in_time"],0,5)  : "" ;
                    $in["out1-".$date] = isset($working_data["out_time"]) ? substr( $working_data["out_time"],0,5) : "" ;    
                }
                //2つ目の入力のとき
                else
                {
                    $in["in2-".$date] = isset($working_data["in_time"]) ? substr( $working_data["in_time"],0,5) : "" ;
                    $in["out2-".$date] = isset($working_data["out_time"]) ? substr( $working_data["out_time"],0,5) : "" ;
                }


            }
        }
    }

    
}


#===========================================================
?>