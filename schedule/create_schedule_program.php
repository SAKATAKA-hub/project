<?php
#===========================================================
# スケジュール作成ページプログラム (create_schedule_program.php)
#===========================================================
# 基本設定
//ファイルの読み込み
//[読込順]function.php > schedule_function.php > create_schedule_program.php(現在地)
include('schedule_function.php');

#===========================================================
# 表示する従業員リストを取得

//テスト用
// $SQL = "SELECT `id`, `name` FROM employee_data WHERE `id` = ?";
// $DATA = array( "0001" ); //取得グループ名

$SQL = "SELECT `id`, `name` FROM employee_data WHERE `group` = ?";
$DATA = array( "group1" ); //取得グループ名
$employees = select_db($SQL,$DATA);  //DBより従業員情報の取得

# アクセス日の取得
$now = get_now();

# フォームの受取り
$in = parse_form();

# modeの分岐処理
// 1. リンクボタンからアクセスされた時 ::::::::::::::::::::::::::::
if(!isset($in["mode"]))
{
    # 表示用日付の取得(表示日付 == アクセス日翌月)
    $displayDT = new DateTime(sprintf("%04d-%02d-01",$now["Y"] ,$now["m"]+1 ));
    $display = display_date($displayDT); 

    # 作成済スケジュールを表示に反映
    // read_create_shift(); 
}
// 2. フォームから合アクセスされたとき ::::::::::::::::::::::
else
{
    # tokenのチェック
    validateToken(); 

    # case1. 作成スケジュールの保存 ******************
    if($in["mode"]=="submit")
    {
        # 表示用日付の取得(表示日付 == フォーム指定日付)
        $displayDT = new DateTime($in["month"]);
        $display = display_date($displayDT);

        # スケジュールをテキストファイルとして保存
        save_create_shift(); 


    } 
    # case2. "選択月"の変更 ******************
    elseif(($in["mode"]=="change_next")||($in["mode"]=="change_befor"))
    {
        $displayDT = change_date(); //"月"変更
        $display = display_date($displayDT);

        // read_create_shift(); //DBの保存データを反映する関数


    }
    # case3. 提出スケジュールの内容を反映 ******************
    elseif($in["mode"]=="read_send_schedule")
    {
        # 表示用日付の取得(表示日付 == フォーム指定日付)
        $displayDT = new DateTime($in["month"]);
        $display = display_date($displayDT);

        // #従業員全員の提出スケジュールの読み込み
        foreach ($employees as $key => $employee) {
            read_submission_shift($employee["id"]);
        }
    
    }
}

createToken(); //tokenの発行 $_SESSION['token']

# カレンダー用の週分割にした日付情報を取得
$calendar_weeks = get_calendar($display);


#===========================================================
# スケジュール提出ページ用　関数
#-----------------------------------------------------------
# スケジュールをテキストファイルとして保存
function save_create_shift()
{
    global $in, $employees;

    $data = [];
    foreach ($employees as $key => $employee) 
    {
        $data[] = save_submission_shift($employee["id"]);
    }

    $data = implode(",",$data);
            
    # 入力データをテキストファイルに保存
    $directory ="data/create_schedule/";
    $file = $directory.$in["month"].".text";
    file_put_contents($file,$data);
    

}