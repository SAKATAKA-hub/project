<?php
#===========================================================
# select_record.php 勤怠一覧を表示するプログラム
#===========================================================
# 基本設定
//ファイルの読み込み
//[読込順]function.php > time_card_function.php > class.php > select_record.php(現在地)
include('class.php');

//指定した分数（n分）刻みに時間を集計
$cut_min = 15; 

#===========================================================
# 1.フォームの受取り処理と、変数の定義

//1-1 フォーム（表示切替ボタン）の受取り
$in = parse_form();

if(empty($in)){ //フォームを受け取らなかったとき
 $in = array (
    "mode"=> "day_table",
    "select_employee"=> "",
    "modify"=> "false" ,
    "select_Y_m"=> "",
    "select_d"=> "",
 );
}

// 1-2 受け取った値より、変数を定義
$mode = $in["mode"];
$select_employee = empty($in["select_employee"]) ? $_SESSION["employee_id"] : $in["select_employee"]; 
$modify = $in["modify"];
$select_Y_m = empty($in["select_Y_m"]) ? $now_dt->format('Y-m') : $in["select_Y_m"]; 
$select_d = empty($in["select_d"]) ? $now_dt->format('d') : $in["select_d"]; 

//-----------------------------------------------------------
// #テスト用コードエリア
// 変数の表示
$ins = array (
    "mode"=> "mode => ".$mode,
    "select_employee"=> "select_employee => ".$select_employee,
    "modify"=> "modify => ".$modify,
    "select_Y_m"=> "select_Y_m => ".$select_Y_m,
    "select_d"=> "select_d => ".$select_d,
 );

 $test_text = "*****フォームからの取得情報*****<br>";
 foreach ($ins as $key => $val) {$test_text .= $val."<br>";}
 $test_text .= "*******************************<br><br>";
 echo $test_text;
// var_dump($in);
//-----------------------------------------------------------

// 1-3 入力で指定された年月日の定義
$select_dt = new DateTime("{$select_Y_m}-{$select_d}"); //指定日のDateTimeオブジェクト
$select_Y = intval($select_dt->format('Y')); //指定日の"年"
$select_m = intval($select_dt->format('m')); //指定日の"月"
$select_d = intval($select_dt->format('d')); //指定日の"日"

$end_m = $select_m == 12 ? "01" : sprintf("%02d",$select_m +1);
$end_Y = $select_m == 12 ? $select_Y +1 : $select_Y;
$end_dt = new DateTime("{$end_Y}-{$end_m}-01");
$end_dt->modify('-1 day');

$select_end_d = intval($end_dt->format('d')); //指定日の"末日"
$select_month_start_day = $select_dt->format('Y-m-01');//指定年月+"１日"
$select_month_last_day = $end_dt->format('Y-m-d');//指定年月+"末日"

// echo $select_month_start_day."<br>";
// echo $select_month_last_day."<br>";

// echo "{$end_Y}年　{$end_m}月　{$select_d}日";





#===========================================================
# 2.詳細選択セレクトエリアの作成
$option_elements = array( //option要素
    'month' => '',
    'day' => '',
    'employee' => '',
);

$select_elements = array( //option要素
    'month' => '',
    'day' => '',
    'employee' => '',

    'day_hidden' => sprintf('<input type="hidden" name="select_d" value="%02d">',$select_d),
    'employee_hidden' => sprintf('<input type="hidden" name="select_employee" value="%04d">',$select_employee),
);

// 2-1 年月選択のselect要素の作成
//option要素
for ($i=0; $i < 12; $i++) {
    $selected = $i==0 ? "selected" : "";
    $val_Y = $select_m -$i < 1 ? $select_Y -1 : $select_Y;
    $val_m = $select_m -$i < 1 ? $select_m -$i +12 : $select_m -$i;

    $option_elements['month'] .= sprintf(
        '<option %s value="%04d-%02d">%d年%d月</option>'
        , $selected, $val_Y, $val_m, $val_Y, $val_m
    );
}
// select要素
$select_elements['month'] = <<<_ELEMENT_
<select name="select_Y_m">
{$option_elements['month']}
</select>
_ELEMENT_;

// 2-2 日付選択のselect要素の作成
// option要素
for ($i=1; $i <= $select_end_d; $i++) {
    $selected = $i == $select_d ? "selected" : "";

    $option_elements['day'] .= sprintf(
        '<option %s value="%02d">%d日</option>'
        , $selected, $i, $i
    );
}
// select要素
$select_elements['day'] = <<<_ELEMENT_
<select name="select_d">
{$option_elements['day']}
</select>
_ELEMENT_;


// 2-3 従業員名選択のselect要素の作成
// option要素
$SQL = "SELECT `id`, `name` FROM employee_data WHERE `id` >= ?";
$DATA = array( "0000" );
$datas = select_db($SQL,$DATA);  //DBより従業員情報の取得

foreach ($datas as $data) {
    $selected = $data["id"] == $select_employee ? "selected" : "";
    $option_elements['employee'] .= sprintf(
        '<option %s value="%s">%s</option>'
        , $selected, $data["id"], $data["name"]
    );
}
// select要素
$select_elements['employee'] = <<<_ELEMENT_
<p>従業員名：</p>
<select name="select_employee">
{$option_elements['employee']}
</select>
_ELEMENT_;





#===========================================================
# 3.DBデータの受取りとテーブルエリアの作成

# 集計用オブジェクトの生成
# // <1>$input_records : インプット情報のオブジェクト
# // <2>$agg_records   : 集計情報インスタンスのオブジェクト
# // <3>$break_records : 休憩情報のオブジェクト


// 3-1 $modeによるDBレコード取得内容の分岐
$from = "work_record";
switch ($mode) {
    case 'day_table':  //日別勤怠一覧
        $SQL = "SELECT * FROM {$from} WHERE `in_day`= ? ";
        $DATA = array($select_dt->format('Y-m-d'));
        break;

    case 'month_table': //月別勤怠一覧
        $SQL = "SELECT * FROM {$from} WHERE `in_day`>= ? AND `in_day`<= ? ";
        $DATA = array($select_month_start_day, $select_month_last_day);
        break;

    case 'private_table': //個人別勤怠一覧
        $SQL = "SELECT * FROM {$from} WHERE `in_day`>= ? AND `in_day`<= ? AND `employee_id`= ? ";
        $DATA = array($select_month_start_day, $select_month_last_day, $select_employee);
        break;
}
//DBより従業員情報の取得
$input_records = select_db($SQL,$DATA); //<1>$input_records : インプット情報のオブジェクト

//<!>テスト用:DB取得情報の表示
if(isset($input_records)){
// echo "*****DBからの取得情報*****<br>";
// foreach ($input_records as $key => $value) {
//     var_dump($value); echo"<br>";
// }
// echo "*******************************<br><br>";
}


// 3-2 取得したレコード情報より、集計情報・休憩情報の取得
$agg_records = []; // 集計情報
$break_records = []; //休憩情報
$table_elements = array( //table要素
    1 => "",
    2 => "",
    3 => "",
);

//各従業員・1日毎のレコード情報を出力
foreach($input_records as $key => $input_record){
    // <2>$agg_records   : 集計情報インスタンスのオブジェクト
    $agg_records[$key] = new AggregateRecord($input_record,$cut_min);

    // <3>$break_records : 休憩情報のオブジェクト
    $break_records[$key] = $agg_records[$key]->getBreakArray();

    $in_day = new DateTime($input_record["in_day"]);//出勤日
    $in_date = $in_day -> format('d');
    $in_week_nom = $in_day -> format('w');
    $weeks = array("日","月","火","水","木","金","土");
    foreach ($weeks as $wkey => $week) {
        if($in_week_nom == $wkey){$in_week = $week;}
    }
    $in_day = sprintf("%d日(%s)",$in_date,$in_week);

    $employee_id = $input_record["employee_id"]; //従業員名
    $employee_name = $input_record["employee_name"]; //従業員ID

    $in_time = substr($input_record["in_time"],0,5); //出勤時間
    $out_time = substr($input_record["out_time"],0,5); //退勤時間

    if(empty($break_records[$key])){//休憩開始・休憩終了時間
        $in_break = "-";
        $out_break = "-";    
    }else{
        $in_break = substr($break_records[$key][0]["in"],0,5);
        $out_break = substr($break_records[$key][0]["out"],0,5);
    }

    $restrain_time = $agg_records[$key]->getRestrainTime(); //一日出勤時間
    $restrain_time = sprintf("%02d時間%02d分",floor($restrain_time/60),$restrain_time%60);

    $break_time = $agg_records[$key]->getBreakTime(); //一日休憩時間
    $break_time = sprintf("%02d時間%02d分",floor($break_time/60),$break_time%60);

    $working_time = $agg_records[$key]->getWorkingTime(); //一日労働時間
    $working_time = sprintf("%02d時間%02d分",floor($working_time/60),$working_time%60);


    // 3-3 テーブル要素の作成
    // 3-3-1 private_input テーブルテキストの作成
    $table_elements[1] .= <<<_PrivateInputText_
    <tr class="rec_top">
    <td class="in_day">{$in_day}</td>
    <td class="employee_id">{$employee_id}</td>
    <td class="employee_name">{$employee_name}</td>
    <td class="in_time">{$in_time}</td>
    <td class="out_time">{$out_time}</td>
    <td class="in_break">{$in_break}</td>
    <td class="out_break">{$out_break}</td>
    <td class="restrain_time">{$restrain_time}</td>
    <td class="break_time">{$break_time}</td>
    <td class="working_time">{$working_time}</td>
    <td class="modification">修正</td>
    <td class="delete">削除</td>
    </tr>
    _PrivateInputText_;

    //休憩回数が複数ある時の処理
    if(count($break_records[$key]) > 1){
        for ($bi=1; $bi < count($break_records[$key]); $bi++) { 

            $in_break = substr($break_records[$key][1]["in"],0,5);
            $out_break = substr($break_records[$key][1]["out"],0,5);
    
            $table_elements[1] .= <<<_PrivateInputTextB_
            <tr>
            <td class="in_day"></td>
            <td class="employee_id"></td>
            <td class="employee_name"></td>
            <td class="in_time"></td>
            <td class="out_time"></td>
            <td class="in_break">{$in_break}</td>
            <td class="out_break">{$out_break}</td>
            <td class="restrain_time"></td>
            <td class="break_time"></td>
            <td class="working_time"></td>
            <td class="modification"></td>
            <td class="delete"></td>
            </tr>
            _PrivateInputTextB_;
        }
    }

} //end (各従業員・1日毎のレコード情報を出力)

//　テーブルに"カラム名行"を追加
$table_elements[1] = <<<_PrivateInputText_
<table class="private_input">
<tr>
<th class="in_day">日</th>
<th class="employee_id">ID</th>
<th class="employee_name">名　前</th>
<th class="in_time">出　勤</th>
<th class="out_time">退　勤</th>
<th class="in_break">休憩開始</th>
<th class="out_break">休憩終了</th>
<th class="restrain_time">勤務時間</th>
<th class="break_time">休憩時間</th>
<th class="working_time">労働時間</th>
<th class="modification"></th>
<th class="delete"></th>
</tr>
{$table_elements[1]}
</table>
_PrivateInputText_;


// 3-3-2 private_agg テーブルテキストの作成
$private_restrain_times = AggregateRecord::getPrivateRestrainTimes();
$private_break_times = AggregateRecord::getPrivateBreakTimes();
$private_working_times = AggregateRecord::getPrivateWorkingTimes();
ksort($private_restrain_times); //キーの昇順変換

// 詳細ボタン
$ditte_button = <<<_button_
<form action="#" method="post" target=”_blank”>
<input type="hidden" name="mode" value="private_table">
<input type="hidden" name="select_Y_m" value="{$select_Y_m}">
<input type="hidden" name="select_d" value="{$select_d}">
<input type="hidden" name="select_employee" value="{$select_employee}">
<input type="hidden" name="modify" value="{$modify}">
<button>詳細</button>
</form>
_button_;

foreach ($private_restrain_times as $employee_id => $val) {
    foreach($input_records as $key => $input_record){
        if($input_record["employee_id"] == $employee_id){
            $employee_name = $input_record["employee_name"];
        }
    }
    $private_restrain_time = sprintf("%02d時間%02d分",floor($val/60),$val%60);
    $private_break_time = $private_break_times[$employee_id];
    $private_break_time = sprintf("%02d時間%02d分",floor($private_break_time/60),$private_break_time%60);
    $private_working_time = $private_working_times[$employee_id];
    $private_working_time = sprintf("%02d時間%02d分",floor($private_working_time/60),$private_working_time%60);

    $table_elements[2] .= <<<_PrivateAggText_
    <tr>
    <td class="employee_id">{$employee_id}</td>
    <td class="employee_name">{$employee_name}</td>
    <td class="ditails">{$ditte_button}</td>
    <td class="total">{$private_restrain_time}</td>
    <td class="total">{$private_break_time}</td>
    <td class="total">{$private_working_time}</td>
    </tr>
    _PrivateAggText_;
}
//　テーブルに"カラム名行"を追加
$table_elements[2] = <<<_PrivateInputText_
<table class="private_agg">
<tr>
<th class="employee_id">ID</th>
<th class="employee_name">名　前</th>
<th class="ditails">詳　細</th>
<th class="total">合計勤務時間</th>
<th class="total">合計休憩時間</th>
<th class="total">合計労働時間</th>
</tr>
{$table_elements[2]}
</table>
_PrivateInputText_;


// 3-3-3 total_agg テーブルテキストの作成
$total_restrain_time = AggregateRecord::getTotalRestrainTime();
$total_restrain_time = sprintf("%02d時間%02d分",floor($total_restrain_time/60),$total_restrain_time%60);
$total_break_time = AggregateRecord::getTotalBreakTime();
$total_break_time = sprintf("%02d時間%02d分",floor($total_break_time/60),$total_break_time%60);
$total_working_time = AggregateRecord::getTotalWorkingTime();
$total_working_time = sprintf("%02d時間%02d分",floor($total_working_time/60),$total_working_time%60);

$table_elements[3] = <<<_TotalAggText_
<table class="total_agg">
<tr>
<th class="total">総休憩時間</th>
<th class="total">総勤務時間</th>
<th class="total">総労働時間</th>
</tr>
<tr>
<td class="total">{$total_restrain_time}</td>
<td class="total">{$total_break_time}</td>
<td class="total">{$total_working_time}</td>
</tr>
</table>
_TotalAggText_;


#===========================================================
# 4.表示内容の分岐処理

// 4-1 表示に関する変数の定義
$cullent_menu = array( //選択中メニュー表示切換用
    'day' => '',
    'month' => '',
    'private' => '',
);
$cullent_heading_mord = array( //小見出し、パンくずリスト表示切替用
    'day' => '日別勤務一覧',
    'month' => '月別勤務一覧',
    'private' => '個人別勤怠一覧',
);

$select_element_mord = array( //セレクト要素の表示切替用
    'day' => 
    $select_elements['month']. $select_elements['day']. 
    $select_elements['employee_hidden'],
    'month' =>
    $select_elements['month']. $select_elements['day_hidden']. 
    $select_elements['employee_hidden'],
    'private' =>
    $select_elements['month']. $select_elements['day_hidden']. 
    $select_elements['employee'],
);

$table_element_mord = array( //テーブル要素の表示切替用
    'day' => $table_elements[1].$table_elements[3],
    'month' => $table_elements[2].$table_elements[3],
    'private' => $table_elements[1].$table_elements[3],
);

$display_modify = ""; //修正ボタンの表示切替用（表示時 : display）
$display_day_column = ""; //テーブルの日付カラムの非表示切替用（表示時 : hidden）
$display_employee_column = ""; //テーブルの従業員ID・名カラムの非表示切替用（表示時 : hidden）

// 4-2 表示内容の分岐1(メニューボタン別)
switch ($mode) {
    case 'day_table':  //*日別勤怠一覧
        $cullent_menu['day'] = "cullent"; //選択中メニュー表示切換用
        $cullent_heading = $cullent_heading_mord['day']; //小見出し、パンくずリスト表示切替用
        $select_element = $select_element_mord['day'];  //セレクト要素の表示切替用
        $table_element = $table_element_mord['day'];  //テーブル要素の表示切替用
        $display_day_column = "hidden"; //テーブルの日付カラムの非表示切替用
        break;

    case 'month_table': //*月別勤怠一覧
        $cullent_menu['month'] = "cullent"; //選択中メニュー表示切換用
        $cullent_heading = $cullent_heading_mord['month']; //小見出し、パンくずリスト表示切替用
        $select_element = $select_element_mord['month'];  //セレクト要素の表示切替用
        $table_element = $table_element_mord['month'];  //テーブル要素の表示切替用
        break;

    case 'private_table': //*個人別勤怠一覧
        $cullent_menu['private'] = "cullent"; //選択中メニュー表示切換用
        $cullent_heading = $cullent_heading_mord['private']; //小見出し、パンくずリスト表示切替用
        $select_element = $select_element_mord['private'];  //セレクト要素の表示切替用
        $table_element = $table_element_mord['private'];  //テーブル要素の表示切替用
        $display_employee_column = "hidden"; //テーブルの従業員ID・名カラムの非表示切替用
        break;
}

// 4-2 表示内容の分岐2(勤怠修正ボタンが押された時
if($modify == "true"){
    $display_modify = ""; //修正メニューの表示
    $cullent_heading .= "修正";
}





#===========================================================
# 5.テンプレートファイルの読み込みと、入替文字の処理

// 5-1 テンプレートファイルの読み込み
$file = "tmpl/attendance_list.tmpl";//テンプレートファイル
$tmpl = file_get_contents($file);


// 5-2 入替文字一覧 * * * * * * * * * * * * * * * * * * * * * 
$replaces = array(
    // メニューボタン・詳細変更ボタン用
    "!select_Y_m!" => $select_Y_m,
    "!select_d!" => $select_d,
    "!select_employee!" => $select_employee,
    "!mode!" => $mode,
    "!modify!" => $modify,

    // 選択中メニュー表示切換用
    "!cullent_menu['day']!" => $cullent_menu['day'],
    "!cullent_menu['month']!" => $cullent_menu['month'],
    "!cullent_menu['private']!" => $cullent_menu['private'],

    "!cullent_heading!" => $cullent_heading, //小見出し、パンくずリスト表示切替用
    "!select_element!" => $select_element, //セレクト要素
    "!table_element!" => $table_element, //テーブル要素の表示切替用

    "!display_modify!" => $display_modify, //修正ボタンの表示切替用（表示時 : display）
    "!display_day_column!" => $display_day_column, //テーブルの日付カラムの非表示切替用（表示時 : hidden）
    "!display_employee_column!" => $display_employee_column, //テーブルの従業員ID・名カラムの非表示切替用（表示時 : hidden）

); // * * * * * * * * * * * * * * * * * * * * * * * * * * * * *

// 5-3 入替文字の処理とテンプレートの表示
foreach ($replaces as $key => $val) {
    $tmpl = str_replace($key, $val, $tmpl);
}
echo $tmpl; //表示



// ※ cssスタイル編集用　HTMLファイルへの書込み
$file = "attendance_list.html";//テンプレートファイル
file_put_contents($file,$tmpl);

