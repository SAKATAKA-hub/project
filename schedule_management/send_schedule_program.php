<?php
# 日付処理
// アクセス日付の取得
$nowDT = new DateTime("");
$now = [];
$now["Y"] = intval($nowDT->format("Y"));
$now["m"] = intval($nowDT->format("m"));
$now["d"] = intval($nowDT->format("d"));

$display = []; //表示用日付


//表示用日付の取得関数１(表示日付->アクセス日翌月)
function normal_display_date()
{
    global $in, $now, $display;

    $DT_key = sprintf("%04d-%02d-01",$now["Y"] ,$now["m"]+1 );
    $display["DT"] = new DateTime($DT_key);

    $display["Y"] = intval($display["DT"]->format("Y")); //年
    $display["m"] = intval($display["DT"]->format("m")); //月
    $display["d"] = intval($display["DT"]->format("d")); //日
    $display["w"] = $display["DT"]->format("w"); //曜日

    $DT_key = sprintf("%04d-%02d-00",$display["Y"] ,$display["m"]+1 );
    $display["end_DT"] = new DateTime($DT_key);
    $display["end_d"] = intval($display["end_DT"]->format("d")); //月末日  
    
    $display["m_index"] = sprintf("%04d年%02d月",$display["Y"] ,$display["m"] ); // "0000年00月"
    $display["m_text"] = sprintf("%04d-%02d-01",$display["Y"] ,$display["m"] ); // "0000-00-00"
    
}


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
        if(!$last_w==0){
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
        $DT_key = sprintf("%04d-%02d-01",$display["Y"] ,$display["m"]+1 );
        $nextMonDT = new DateTime($DT_key);
        $next_d = intval($nextMonDT->format("d"));
        $next_w = $nextMonDT->format("w");

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