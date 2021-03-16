<?php
#=============================================================
# スケジュール関連関数 (schedule_function.php)
#=============================================================
# 基本設定
//ファイルの読み込み
//[読込順]function.php > schedule_function.php(現在地)
include('../common/app/function.php');


#=============================================================
# フォームの分岐処理関数
#--------------------------------------------------------------
# 表示用日付の取得関数
function display_date($displayDT)
{
    $display["Y-m-d"] = $displayDT->format("Y-m-d"); //年
    $display["Y"] = intval($displayDT->format("Y")); //年
    $display["m"] = intval($displayDT->format("m")); //月
    $display["d"] = intval($displayDT->format("d")); //日
    $display["w"] = $displayDT->format("w"); //曜日

    $make_Y = $display["m"] == 12 ? $display["Y"] +1 : $display["Y"] ;
    $make_m = $display["m"] == 12 ? 1: $display["m"] +1 ;
    $display["end_DT"] = new DateTime(sprintf("%04d-%02d-00",$make_Y,$make_m));
    $display["end_d"] = intval($display["end_DT"]->format("d")); //月末日  
    
    $display["m_index"] = sprintf("%04d年%02d月",$display["Y"] ,$display["m"] ); // "0000年00月"
    $display["m_text"] = sprintf("%04d-%02d-01",$display["Y"] ,$display["m"] ); // "0000-00-00"
    
    return $display;
}


#--------------------------------------------------------------
# 表示用日付の変更
function change_date()
{
    global $in, $now;
    $changeDT = new DateTime($in["month"]);

    if($in["mode"]=="change_next")
    {
        $make_Y = $changeDT->format("m") == 12 ? $changeDT->format("Y") +1 : $changeDT->format("Y") ;
        $make_m = $changeDT->format("m") == 12 ? 1 : intval($changeDT->format("m"))+1  ;
    }
    else
    {
        $make_Y = $changeDT->format("m") == 1 ? $changeDT->format("Y") -1 : $changeDT->format("Y") ;
        $make_m = $changeDT->format("m") == 1 ? 12 : intval($changeDT->format("m"))-1  ;
    }
    $displayDT = new DateTime(sprintf("%04d-%02d-01",$make_Y,$make_m));


    return $displayDT;

}


#--------------------------------------------------------------
# スケジュール提出内容をDBへ保存
function save_submission_shift()
{
    global $in, $now, $display;

    # sub1.入力スケジュールを一つの文字列に変換する
    $shift =[]; //提出スケジュール時間を格納
    for ($i=1; $i <= $display["end_d"] ; $i++)
    {
        $in_nom = sprintf("d%02d",$i);
        $shift[$in_nom] = [];
        foreach ($in as $in_name => $in_val) 
        {
            $get_nom = substr($in_name,0,3); //行の番号(日付)

            if($in_nom == $get_nom){
                $shift[$in_nom][] = $in_val;
            }
        }
    }
    $data =[];
    foreach ($shift as $key => $val) {   
        $data[] = implode("-",$val);
    }
    // var_dump($shift);
    $shift = implode("=",$data);
    $shift = str_replace("--","",$shift);

    // echo $shift."<br>";


    # 2.以前のデータを削除
    $SQL ="DELETE FROM submission_shift WHERE";
    $SQL = <<<_SQL_
    DELETE FROM submission_shift 
    WHERE `employee_id` = ?
    AND `month` = ?
    _SQL_;
    $DATA = array($_SESSION["employee_id"], $in["month"],);

    insert_db($SQL,$DATA);


    # 3.入力データをデータベースに保存
    $SQL = <<<_SQL_
    INSERT INTO submission_shift 
    ( `employee_id`, `month`, `shift`, `comment`, `update_date`) 
    VALUES ( ?,?,?,?,?)
    _SQL_;
    $DATA = array(
        $_SESSION["employee_id"], $in["month"], $shift, 
        $in["comment"], $now["DT"]->format("Y-m-d")
    );

    insert_db($SQL,$DATA);



}


#--------------------------------------------------------------
# 提出済スケジュールを表示に反映
function read_submission_shift()
{
    global $in, $now, $display;
     
    // 1.DBに保存された提出済みスケジュールを呼び出す。
    $SQL = <<<_SQL_
    SELECT shift, comment FROM submission_shift 
    WHERE employee_id = ? AND month = ?
    ORDER BY ss_id DESC
    _SQL_;

    $DATA = array($_SESSION["employee_id"], $display["Y-m-d"],);
    $data = select_db($SQL,$DATA);  //関数の実行（取得データをリターン） 

    // 2.DBより受け取った情報を加工
    if(!empty($data))
    {
        $data = $data[0];
    
        // 2-1　コメント
        $in["comment"] = isset($data["comment"]) ? $data["comment"] : "" ;
        
        //2-2スケジュール
        if(!empty($data["shift"])){
            $data["shift"] = explode("=",$data["shift"]);
    
            foreach($data["shift"] as $key => $vals){
                $vals = explode("-",$vals);

                $name_keys = array("in1","out1","in2","out2",);
                foreach ($name_keys as $i => $name_key) {
                    $input_name = sprintf("d%02d:%d:%s", $key+1, $_SESSION['employee_id'], $name_key);
                    $in[$input_name] = isset($vals[$i]) ? $vals[$i] : "" ;
                }

            }
    
        }
    
    } //if(!empty($data))end


}


#-----------------------------------------------------------
# 契約曜日通りに自動入力する関数
function fixed_input(){
    global $in, $now, $display;

    // 1.DBに保存された契約スケジュールを呼び出す。
    $SQL = <<<_SQL_
    SELECT * FROM contract_working_days 
    WHERE employee_id = ?
    _SQL_;

    $DATA = array($_SESSION["employee_id"],);
    $working_datas = select_db($SQL,$DATA);  //関数の実行（取得データをリターン）
    
    // 2.テーブルに取得情報を埋め込む。
    for ($i = 0; $i < $display["end_d"]; $i++) 
    { 
        $week_num = ($display["w"] + $i) %7;
        $date = $i+1;

        foreach ($working_datas as $working_data){
            if($working_data["working_week"] == $week_num)
            {



                //一つ目の入力のとき
                if(empty($in[in_name($date,"in1")]))
                {
                    $in[in_name($date,"in1")] = isset($working_data["in_time"]) ? substr( $working_data["in_time"],0,5)  : "" ;
                    $in[in_name($date,"out1")] = isset($working_data["out_time"]) ? substr( $working_data["out_time"],0,5) : "" ;    
                }
                //2つ目の入力のとき
                else
                {
                    $in[in_name($date,"in2")] = isset($working_data["in_time"]) ? substr( $working_data["in_time"],0,5) : "" ;
                    $in[in_name($date,"out2")] = isset($working_data["out_time"]) ? substr( $working_data["out_time"],0,5) : "" ;
                }


            }
        }
    }

    
}

//データの埋め込み先の"name"を指定する。
function in_name($date,$name_key){
    return sprintf("d%02d:%d:%s",$date, $_SESSION['employee_id'], $name_key);
}


#=============================================================
# 表示エリアの作成
#--------------------------------------------------------------
# カレンダーに挿入する情報配列作成の関数
function get_calendar($display)
{

    // 今月
    function get_calendar_body($display)
    {
        $dates = [];
        for ($d=1; $d <= $display["end_d"]; $d++) {

            $week = empty(($display["w"] +$d -1)%7) ? 7 : ($display["w"] +$d -1)%7 ;

            $dates["this$d"] = array(
                "date" => $d ,
                "week" => $week ,
                "this_month" => true ,
            );
        }
        return $dates;
    }

    // 先月
    function get_calendar_head($display)
    {
        $dates = [];

        // 先月末のDTオブジェクト生成
        $DT_key = sprintf("%04d-%02d-00",$display["Y"] ,$display["m"] );
        $lastMonDT = new DateTime($DT_key);
        $last_d = intval($lastMonDT->format("d"));
        $last_w = $lastMonDT->format("w");
       

        // ※先月末が日曜の時は、配列を作成しない
        if(!$last_w == 0){
            for ($d = $last_d; $d > ($last_d - $last_w); $d--) {
                $date = [];
                $date["last$d"] = array("date" => $d ,"this_month" => false ,);
                $dates = array_merge($date,$dates);
            }    
        }   
        return $dates;
    }

    // 翌月
    function get_calendar_tail($display)
    {
        $dates = [];

        // 翌月初日のDTオブジェクト生成
        $make_Y = $display["m"] == 12 ? $display["Y"] +1 : $display["Y"] ;
        $make_m = $display["m"] == 12 ? 1: $display["m"]+1  ;

        $DT_key = sprintf("%04d-%02d-01",$make_Y ,$make_m );
        $nextMonDT = new DateTime($DT_key);
        $next_d = intval($nextMonDT->format("d"));
        $next_w = $nextMonDT->format("w") == 0 ? 7 :$nextMonDT->format("w");

        // ※翌月初日が月曜日の時は、配列を作成しない
        if(!$next_w -1){
            for ($d = 1; $d <= (8 - $next_w); $d++) { 
                $dates["next$d"] = array("date" => $d ,"this_month" => false ,);
            }    
        }
        return $dates;
    }


    // カレンダーに挿入する情報配列
    $dates = [];
    $dates = array_merge($dates,get_calendar_head($display));
    $dates = array_merge($dates,get_calendar_body($display));
    $dates = array_merge($dates,get_calendar_tail($display));

    // var_dump($dates);


    // カレンダーの配列情報を保存



    //カレンダー配列を週毎に分割
    $weeks=[];
    $w_count = count($dates)/7;
    for ($w=0; $w < $w_count; $w++) { 
        $n = $w *7;
        $weeks[$w] = array_slice($dates,$n,7); 
    }

    return $weeks;


}


#--------------------------------------------------------------
# option要素作成の関数
function create_option_erements($sel_time)
{
    $o_erements = array('<option value=""> </option>'); //カラ要素
    $work_min = array(":00",":30",);

    //　"8時スタート"、"30分刻み"の選択option 
    for ($h=0; $h < 24; $h++) { 
        $hour = $h+8>23 ? $h+8-24 :$h+8;
        foreach ($work_min as $min) 
        {
            $time = sprintf("%02d",$hour).$min; //　時間($hour)：分($min)
            $selected = $sel_time == $time ? "selected" : "" ;

            $elements_text = 
            <<<_text_
            <option $selected value="$time">$time</option>
            _text_;

            $o_erements[] = $elements_text;

        }
    }

    $o_erements = implode("\n",$o_erements);
    return $o_erements;

}



?>