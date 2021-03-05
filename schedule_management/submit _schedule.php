<?php
#===========================================================
# submit_schedule.php スケジュール提出プログラム
#===========================================================
# 基本設定
//ファイルの読み込み
//[読込順]function.php > submit_schedule.php(現在地)
include('../common/app/function.php');

//parts.phpファイルの読み込み
include('../common/parts/parts.php'); 

# 日付の定義 今のDT_OB
$nowDT = new DateTime("");
$now = [];
$now["Y"] = intval($nowDT->format("Y"));
$now["m"] = intval($nowDT->format("m"));
$now["d"] = intval($nowDT->format("d"));

$display = []; //表示用日付



#===========================================================
# 1.フォームの受取りとモード切替
//1-1 フォーム（表示切替ボタン）の受取り

$in = parse_form(); //フォームの受取り
if(isset($in["mode"])){
    // echo $_SESSION['token']."<br>";
    // var_dump($in);
    echo "****** in *******"."<br>";
    foreach ($in as $key => $value) {
        if(!empty($value)){echo $key." => ".$value."<br>";}  
    }

    validateToken(); //tokenのチェック

    if($in["mode"]=="submit"){submit();} //送信モード関数
    elseif($in["mode"]=="change"){change();} //選択月変更モード関数

}else{
    normal_processing(); //デフォルトモード関数
}
createToken(); //tokenの発行 $_SESSION['token']

#===========================================================
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
        $display["half"] = "first"; //前期or後期
    }
    //月の前期なら、同月後期を入力画面に表示
    else
    {
        $display["Y"] = $now["Y"]; //表示"年"
        $display["m"] = $now["m"]; //表示"月"
        $display["half"] = "second"; //前期or後期    
    }
    
    $end_Y = $display["m"] == 12 ? $display["Y"] +1 : $display["Y"] ;
    $end_m = $display["m"] == 12 ? 1: $display["m"] +1 ;
    $endDT = new DateTime(sprintf("%04d-%02d-00",$end_Y,$end_m));
    $display["end_d"] = intval($endDT->format("d")); //月末日
    $display["week"] = ($display["end_d"] - intval($endDT->format("w")) +1)%7; //月初日の曜日

    echo sprintf("%04d年%02d月%02d日(%d)%s",$display["Y"] ,$display["m"] ,$display["end_d"], $display["week"], $display["half"]);


        
}


#===========================================================
# $in["mode"]=="submit"のとき、実行する関数
function submit(){
    global $in, $now, $display;

    # 1.表示用日付の定義
    $displayDT = new DateTime($in["month"]);
    $display["Y"] = intval($displayDT->format("Y")); //表示"年"
    $display["m"] = intval($displayDT->format("m"));; //表示"月"
    $display["d"] = intval($displayDT->format("d"));; //表示"日"
    $display["half"] = $display["d"]==1 ? "first" : "second"; //前期or後期 
    
    $end_Y = $display["m"] == 12 ? $display["Y"] +1 : $display["Y"] ;
    $end_m = $display["m"] == 12 ? 1: $display["m"] +1 ;
    $endDT = new DateTime(sprintf("%04d-%02d-00",$end_Y,$end_m));
    $display["end_d"] = intval($endDT->format("d")); //月末日
    $display["week"] = ($display["end_d"] - intval($endDT->format("w")) +1)%7; //月初日の曜日

}

function change(){
    global $in, $now, $display;

}
// 出勤時間選択OPTION($select_time_element)の作成 
// 出勤時間の作成                          

# 2.表示要素の部品作成
// 2-1 出勤時間選択OPTIONの作成
$work_times = [];
$work_times[] = '<option value=""> </option>';
$work_time_mins = array(":00",":30",);
for ($i=0; $i < 24; $i++) { 
    $hour = $i+8>23 ? $i+8-24 :$i+8;
    foreach ($work_time_mins as $min) {
        $time = sprintf("%02d",$hour).$min;
        $work_times[$time] = sprintf('<option value="%s">%s</option><br>',$time,$time);
    }
}
$select_time_element = "";
foreach ($work_times as $key => $option_e) {
    $select_time_element .= $option_e;
}

// 2-2 入力テーブル列(tr要素)の作成
$trdate = [];
switch ($display["half"]) {
    case 'first':
        echo"";
        $trdate["start"] = 1;
        $trdate["end"] = 15;
        $week_num = $display["week"];
        break;
    case 'second':
        $trdate["start"] = 16;
        $trdate["end"] = $display["end_d"];
        $week_num = $display["week"]+(16%7)-1;
        break;
}
$weeks = array("日","月","火","水","木","金","土",);
$tr_element ="";

// テンプレートファイルの読み込み
$file = "tmpl/table_parts.tmpl";
$text = file_get_contents($file);

// 一日毎にtr要素の作成
for ($i = $trdate["start"]; $i <= $trdate["end"]; $i++) {

    //差替え文字の配列
    $num = sprintf("%02d",$i) ;
    $replace_array = array(
        "!num!" => $num , 
        "!week!" => $weeks[$week_num] ,
        "!select_time_element!" => $select_time_element ,
    );

    //テンプレートの文字差替え
    $replace_text = $text;    
    foreach ($replace_array as $key => $val) {
        $replace_text = str_replace($key,$val,$replace_text);
    }
    $tr_element .= $replace_text;

    $week_num = $week_num == 6 ? 0 : $week_num+1; //曜日の更新
}

# 3.ページの表示
// テンプレートファイルの読み込み
$file = "tmpl/submit_schedule.tmpl";
$text = file_get_contents($file);

//差替え文字の設定
switch ($display["half"]) {
    case 'first':
        $display_month = sprintf("%04d年%02d月 前期",$display["Y"],$display["m"]);
        $value_month = sprintf("%04d-%02d-01",$display["Y"],$display["m"]);
        break;
    
    case 'second':
        $display_month = sprintf("%04d年%02d月 後期",$display["Y"],$display["m"]);
        $value_month = sprintf("%04d-%02d-16",$display["Y"],$display["m"]);
        break;
}

//差替え文字の配列
$replace_array = array(
    "!header!" => $header,
    "!token!" => $_SESSION['token'] ,
    "!display_month!" => $display_month,
    "!value_month!" => $value_month,

    //要素
    "!tr_element!" => $tr_element ,
);

//テンプレートの文字差替え
$replace_text = $text;    
foreach ($replace_array as $key => $val) {
    $replace_text = str_replace($key,$val,$replace_text);
}
echo $replace_text;


?>






        
            

            
