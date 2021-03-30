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
# アクセス日の取得
function get_now(){
    $now = [];
    $now["DT"] = new DateTime("");
    $now["Y"] = intval($now["DT"]->format("Y"));
    $now["m"] = intval($now["DT"]->format("m"));
    $now["d"] = intval($now["DT"]->format("d"));
    
    return $now;
}


#--------------------------------------------------------------
# 表示用日付の取得関数
function display_date($displayDT)
{
    $display["Y-m-d"] = $displayDT->format("Y-m-d");
    $display["Y-m"] = $displayDT->format("Y-m");  
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
# 提出スケジュールをテキスト文字列に変換
function save_submission_shift($employee_id,$first,$end)
{
    global $in, $now, $display;

    $shift =[]; 
    for ($i=$first; $i <= $end ; $i++)
    {
        $in_nom = sprintf("d%02d",$i);
        $shift[$in_nom] = [];
        foreach ($in as $in_name => $in_val) 
        {
            $get_nom = substr($in_name,0,3); //行の番号(日付)
            $get_id = substr($in_name,4,4); //従業員ID

            if(($in_nom == $get_nom)&&($get_id == $employee_id)){
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

    // "提出コメント"を変数へ格納
    $input_name = sprintf("comment:%04d",$employee_id);
    $comment = isset($in[$input_name]) ? $in[$input_name] :"";

    $CSV_array = array($employee_id, $shift, $comment);

    return $CSV_array;


    // *【補足コメント　3/17】
    // *　提出スケジュールはDBに保管するつもりだったのですが、
    // *日付をテキストにすると、DBの一つのカラムでは文字数オーバーになるため、
    // *テキスト保存することに途中から変更しました。


}


#--------------------------------------------------------------
# 提出済スケジュールを表示に反映
function read_submission_shift($employee_id)
{
    global $in, $now, $display;
     
    // 1.テキストファイルに保存された提出済みスケジュールを取得。
    $directory ="data/send_schedule/";
    $file = $directory.$display["Y-m-d"]."-".$employee_id.".csv";

    $data = [];
    if(file_exists($file))
    {
        //ファイルの読み込み
        $fh = fopen($file,"r");
        $data = fgetcsv($fh);
        fclose($fh);

        // 文字コードの変更
        mb_convert_variables("UTF-8","SJIS",$datas); //文字コードの変更
    }

    // 2.取得情報を加工
    if(!empty($data))
    {
        //2-1スケジュール
        if(!empty($data[1]))
        {
            $data[1] = explode("=",$data[1]);
    
            foreach($data[1] as $key => $vals)
            {
                $vals = explode("-",$vals);

                $name_keys = array("in1","out1","in2","out2",);
                foreach ($name_keys as $i => $name_key) 
                {
                    $input_name = sprintf("d%02d:%04d:%s", $key+1, $employee_id, $name_key);
                    $in[$input_name] = isset($vals[$i]) ? $vals[$i] : "" ;
                }
            }
        }

        // 2-2　コメント
        $input_name = sprintf("comment:%04d", $employee_id);
        $in[$input_name] = isset($data[2]) ? $data[2] : "" ;
    
    } //if(!empty($data))end


}


#-----------------------------------------------------------
# 契約曜日通りに自動入力する関数
function fixed_input($employee_id)
{
    global $in, $now, $display;

    // 1.DBに保存された契約スケジュールを呼び出す。
    $SQL = <<<_SQL_
    SELECT * FROM contract_working_days 
    WHERE employee_id = ?
    _SQL_;

    $DATA = array($employee_id,);
    $working_datas = select_db($SQL,$DATA); 

    // var_dump($working_datas);
    // echo"<br><br>";

    
    // 2.テーブルに取得情報を埋め込む。
    for ($i = 0; $i < $display["end_d"]; $i++) 
    { 
        $week_num = ($display["w"] + $i) %7;
        $date = $i+1;

        foreach ($working_datas as $working_data){
            if($working_data["working_week"] == $week_num)
            {
                //一つ目の入力のとき
                if(empty($in[in_name($date,$employee_id,"in1")]))
                {
                    $in[in_name($date,$employee_id,"in1")] = isset($working_data["in_time"]) ? substr( $working_data["in_time"],0,5)  : "" ;
                    $in[in_name($date,$employee_id,"out1")] = isset($working_data["out_time"]) ? substr( $working_data["out_time"],0,5) : "" ;    
                }
                //2つ目の入力のとき
                else
                {
                    $in[in_name($date,$employee_id,"in2")] = isset($working_data["in_time"]) ? substr( $working_data["in_time"],0,5) : "" ;
                    $in[in_name($date,$employee_id,"out2")] = isset($working_data["out_time"]) ? substr( $working_data["out_time"],0,5) : "" ;
                }
            }
        }

    }

    
}

//データの埋め込み先の"name"を指定する。
function in_name($date,$employee_id,$name_key){
    return sprintf("d%02d:%04d:%s",$date, $employee_id, $name_key);
}


#=============================================================
# 表示エリアの作成
#===========================================================
# サイドメニューバー作成関数
function side_menu_list($cullent_page)
{
    #1. メニューリスト内容の設定
    $menu_list = array(

        //　スケジュール提出
        array(
            "page_name" => "send_schedule",
            "jp_pag_name" => "スケ提出",
            "href" => "send_schedule.php",
        ),
    
        //　スケジュール作成
        array(
            "page_name" => "create_schedule",
            "jp_pag_name" => "スケ作成",
            "href" => "../login/admin_login.php",
            // "href" => "create_schedule.php",
        ),
    
        //　スケジュール印刷
        array(
            "page_name" => "print_schedule",
            "jp_pag_name" => "スケ印刷",
            "href" => "print_schedule.php",
        ),
    
    );
    

    #2. 表示テキスト
    $menus_text = "";

    foreach ($menu_list as $menu)
    {
        $cullent_class = $cullent_page == $menu["page_name"] ? 'cullent' : '';
        $href = sprintf("location.href='%s'",$menu["href"]);
        $li = sprintf('<li><button class="%s" onclick="%s" >%s</button></li>',$cullent_class, $href, $menu["jp_pag_name"]);
        $menus_text .= $li;
    }

    $menus_text = "<ul class='select_list_group'>$menus_text</ul>";


    // foreach ($menu_list as $menu)
    // {
    //     $cullent_class = $cullent_page == $menu["page_name"] ? 'cullent' : '';
    //     $li = sprintf('<li class="%s"><a href="%s">%s</a></li>',$cullent_class, $menu["href"], $menu["jp_pag_name"]);
    //     $menus_text .= $li;
    // }

    // $menus_text = "<ul class='select_list_group'>$menus_text</ul>";
    return $menus_text;
}


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
            $week_text = array(1=>"MON", 2=>"TUE", 3=>"WED",4=>"THU", 5=>"FRI", 6=>"SAT", 7=>"SUN",);
            $week_class = array(1=>"", 2=>"", 3=>"",4=>"", 5=>"", 6=>"sat_color", 7=>"sun_color",);
            
            $dates[] = array(
                "date" => $d ,
                "week" => $week ,
                "week_text" => $week_text[$week] ,
                "week_class" => $week_class[$week] ,
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
        if($last_w != 0){

            for ($d = $last_d; $d > ($last_d - $last_w); $d--) {

                // $week = empty(($last_w +$d -1)%7) ? 7 : ($last_w +$d -1)%7 ;
                $week = empty(( $last_w - ($last_d - $d) )%7) ? 7 : ( $last_w - ($last_d - $d) )%7 ;
                $week_text = array(1=>"MON", 2=>"TUE", 3=>"WED",4=>"THU", 5=>"FRI", 6=>"SAT", 7=>"SUN",);
                $week_class = array(1=>"", 2=>"", 3=>"",4=>"", 5=>"", 6=>"sat_color", 7=>"sun_color",);
                
                $date = [];
                $date["last$d"] = array(
                    "date" => $d ,
                    "week" => $week ,
                    "week_text" => $week_text[$week] ,
                    "week_class" => $week_class[$week] ,
                    "this_month" => false ,
                );
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
        if($next_w != 1){
            for ($d = 1; $d <= (8 - $next_w); $d++) { 

                $week = empty(($next_w +$d -1)%7) ? 7 : ($next_w +$d -1)%7 ;
                $week_text = array(1=>"MON", 2=>"TUE", 3=>"WED",4=>"THU", 5=>"FRI", 6=>"SAT", 7=>"SUN",);
                $week_class = array(1=>"", 2=>"", 3=>"",4=>"", 5=>"", 6=>"sat_color", 7=>"sun_color",);

                $dates["next$d"] = array(
                    "date" => $d ,
                    "week" => $week ,
                    "week_text" => $week_text[$week] ,
                    "week_class" => $week_class[$week] ,
                    "this_month" => false ,
                );
            }    
        }
        return $dates;
    }


    // カレンダーに挿入する情報配列
    $dates = [];
    $dates = array_merge($dates,get_calendar_head($display));
    $dates = array_merge($dates,get_calendar_body($display));
    $dates = array_merge($dates,get_calendar_tail($display));


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