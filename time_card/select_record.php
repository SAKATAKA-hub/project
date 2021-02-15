<?php
#-----------------------------------------------------------
# 基本設定
//ファイルの読み込み
//[読込順]function.php > time_card_function.php > class.php > select_record.php(現在地)
include('class.php');

//指定した分数（n分）刻みに時間を集計
$cut_min = 15; 


#-----------------------------------------------------------
# 勤怠管理一覧の表示切替
$in = parse_form();

if(!empty($in)){
    if($in["list_group"]=="month_group"){}
    elseif($in["list_group"]=="day_group"){}
    elseif($in["list_group"]=="private_group"){}
}
#-----------------------------------------------------------


# 勤怠管理一覧に表示する詳細設定
$select_Y_m = $now_dt -> format('Y-m'); //年月
$select_d = $now_dt -> format('d');//日
$select_employee = $_SESSION["employee_id"]; //従業員ID

//指定された年月日のDateTimeオブジェクトの生成
$select_date = new DateTime("{$select_Y_m}-{$select_d}");
$select_Y = $select_date -> format('Y'); //年
$select_m = $select_date -> format('m'); //月

//月始日
$start_month = new DateTime("{$select_Y_m}-01");
$select_start_month = $start_month -> format('Y-m-d');
//月末日
$select_m = sprintf("%02d",(intval($select_m)+1)) ;
$end_month = new DateTime("{$select_Y}-{$select_m}-01");
$end_month->modify('-1 day');
$select_end_month = $end_month -> format('Y-m-d');
// echo $select_end_month;


# ***************************************************
# 集計用オブジェクトの生成
# // 1. $input_records : インプット情報のオブジェクト
# // 2. $agg_records   : 集計情報インスタンスのオブジェクト
# // 3. $break_records : 休憩情報のオブジェクト
# ***************************************************

# インプット情報の取得
$SQL = <<<_SQL_
SELECT * FROM work_record
WHERE `in_day` >= ? AND `in_day` <= ? 
_SQL_;
$DATA = array($select_start_month, $select_end_month);
//＜1. インプット情報のオブジェクト＞
$input_records = select_db($SQL,$DATA); 
// var_dump($records);

# 集計情報・休憩情報の取得
$agg_records = []; 
$break_records = [];
$PrivateInputText = [];

//各従業員・1日毎のレコード情報を出力
foreach($input_records as $key => $input_record){
    //＜2. 集計情報インスタンスのオブジェクト＞
    $agg_records[$key] = new AggregateRecord($input_record,$cut_min);

    //＜3. 休憩情報のオブジェクト＞
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


    # テーブルテキストの作成
    // #1 表示切替ボタンの作成

    // #2 private_input テーブルテキストの作成
    $PrivateInputText[$key] = <<<_PrivateInputText_
    <tr>
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
    
            $PrivateInputText[$key] .= <<<_PrivateInputTextB_
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


// #3 private_agg テーブルテキストの作成
$PrivateAggText = "";
$private_restrain_times = AggregateRecord::getPrivateRestrainTimes();
$private_break_times = AggregateRecord::getPrivateBreakTimes();
$private_working_times = AggregateRecord::getPrivateWorkingTimes();

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

    $PrivateAggText .= <<<_PrivateAggText_
    <tr>
    <td class="employee_id">{$employee_id}</td>
    <td class="employee_name">{$employee_name}</td>
    <td class="restrain_time">合計勤務時間</td>
    <td class="restrain_time">{$private_restrain_time}</td>
    <td class="break_time">合計休憩時間</td>
    <td class="break_time">{$private_break_time}</td>
    <td class="working_time">合計労働時間</td>
    <td class="working_time">{$private_working_time}</td>
    </tr>
    _PrivateAggText_;
}




// #4 total_agg テーブルテキストの作成
$total_restrain_time = AggregateRecord::getTotalRestrainTime();
$total_restrain_time = sprintf("%02d時間%02d分",floor($total_restrain_time/60),$total_restrain_time%60);
$total_break_time = AggregateRecord::getTotalBreakTime();
$total_break_time = sprintf("%02d時間%02d分",floor($total_break_time/60),$total_break_time%60);
$total_working_time = AggregateRecord::getTotalWorkingTime();
$total_working_time = sprintf("%02d時間%02d分",floor($total_working_time/60),$total_working_time%60);

$TotalAggText = <<<_TotalAggText_
<tr>
<td class="restrain_time">総勤務時間</td>
<td class="restrain_time">{$total_restrain_time}</td>

<td class="break_time">総休憩時間</td>
<td class="break_time">{$total_break_time}</td>

<td class="working_time">総労働時間</td>
<td class="working_time">{$total_working_time}</td>
</tr>
_TotalAggText_;


?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>勤怠一覧表|タイムカード</title>
  <link rel="stylesheet" href="../common/css/style.css">
</head>
<body>
  <main id="attendance_list">
    <h2>個人別勤怠管理</h2>

    <!--<  表示切替コンテナ―  >--------------------------------------->
    <div class="select_container">

        <!-- #1 詳細表示切替 --------------------------------------->
        <div class="select_list_details">
            <form action="" method="POST">
                <!----- value=表示中の勤怠グループ （"month_group","day_group","private_group"）-->
                <input type="hidden" name="list_group" value="">
                
                <p>年月日：</p>
                <select name="select_month">
                    <option value="2021/02">2021年2月</option>
                    <option value="2021/01">2021年1月</option>
                    <option value="2020/12">2020年12月</option>
                    <option value="2020/11">2020年11月</option>
                    <option value="2020/11">2020年10月</option>
                </select>
                <select name="select_day">
                    <option value="1">1日</option>
                    <option value="2">2日</option>
                    <option value="3">3日</option>
                    <option value="4">4日</option>
                    <option value="5">5日</option>
                </select>
                <p>従業員名：</p>
                <select name="select_employee">
                    <option value="0000">鈴木　一郎</option>
                    <option value="0001">鈴木　二郎</option>
                    <option value="0002">鈴木　三郎</option>
                    <option value="0003">鈴木　四郎</option>
                    <option value="0004">鈴木　五郎</option>
                </select>
    
                <button type="submit">選択内容の変更</button>
    
            </form>
        </div>

        <!-- #2 勤怠一覧グループの表示切替ボタン　（"month_group","day_group","private_group"）--------------------------------------->
        <div class="select_list_group">
            <form action="#" method="POST">
                <input type="hidden" name="list_group" value="month_group">
                <input type="hidden" name="select_month" value=""><!-- value=今日の月 -->
                <input type="hidden" name="select_day" value=""><!-- value=今日の日付 -->
                <input type="hidden" name="select_employee" value=""><!-- value=ログイン者 -->
                <button type="submit">月別勤怠一覧</button>
            </form>
            <form action="#" method="POST">
                <input type="hidden" name="list_group" value="day_group">
                <input type="hidden" name="select_day" value=""><!-- value=今日の日付 -->
                <input type="hidden" name="select_employee" value=""><!-- value=ログイン者 -->
                <button type="submit">日別勤怠一覧</button>
            </form>
            <form action="#" method="POST">
                <input type="hidden" name="list_group" value="private_group">
                <input type="hidden" name="select_employee" value=""><!-- value=ログイン者 -->
                <button type="submit">個人別勤怠一覧</button>
            </form>
        </div>    

    </div><!-- end #表示切替コンテナ― -->


    <!--< テーブルの表示 >----------------------------------------->
    <!-- #1 テーブルヘッダー ---------------->
    <table class="attendance_list_head">
        <tr>
            <th class="in_day">日(曜日)</th>
            <th class="employee_id">ID</th>
            <th class="employee_name">名　前</th>
            <th class="in_time">出　勤</th>
            <th class="out_time">退　勤</th>
            <th class="in_break">休憩開始</th>
            <th class="out_break">休憩終了</th>
            <th class="restrain_time">勤務時間</th>
            <th class="break_time">休憩時間</th>
            <th class="working_time">労働時間</th>
            <th class="modification">修正</th>
            <th class="delete">削除</th>
        </tr>
    </table>

    <!-- #2 private_input ---------------->
    <table class="private_input">
        <?PHP foreach ($input_records as $key => $input_record):?>
            <?= $PrivateInputText[$key];?>
        <?php endforeach?>
    </table>

    <!-- #3 private_agg ---------------->
    <table class="private_agg">
        <?= $PrivateAggText;?> 
    </table>


    <!-- #4 total_agg ---------------->
    <table class="total_agg">
        <?= $TotalAggText;?>
    </table>




      
  </main>
</body>
</html>
<style>
    .select_container{
        display: flex;
        align-items: center;
    }
    .select_list_group,.select_list_details>form{
        display: flex;
        /* margin-bottom: 20px; */
    }

    .attendance_list_head,.private_agg,.total_agg{margin-top: .8em;}

    th,td{
        text-align: center;
        font-size: 14px;
        width:60px;
    }
    .employee_id,.employee_name,.restrain_time,.break_time,.working_time{
        width:90px;
    }
</style>