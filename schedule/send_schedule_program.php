<?php
#===========================================================
# スケジュール提出ページプログラム (send_schedule_program.php)
#===========================================================
# 基本設定
//ファイルの読み込み
//[読込順]function.php > schedule_function.php > send_schedule_program.php(現在地)
include('schedule_function.php');

#===========================================================
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

    # 提出済スケジュールを表示に反映
    read_submission_shift($_SESSION["employee_id"]); 
}
// 2. フォームから合アクセスされたとき ::::::::::::::::::::::
else
{
    # tokenのチェック
    validateToken(); 


    # case1. 入力スケジュールの提出 
    if($in["mode"]=="submit")
    {
        # 表示用日付の取得(表示日付 == フォーム指定日付)
        $displayDT = new DateTime($in["month"]);
        $display = display_date($displayDT);

        # 提出スケジュールをCSV用配列に変換
        $CSV_array = save_submission_shift($_SESSION["employee_id"]);

        var_dump($CSV_array);

        # 入力データをテキストファイルに保存
        $directory ="data/send_schedule/";
        $file = $directory.$display["Y-m-d"]."-".$_SESSION["employee_id"].".csv";
        mb_convert_variables("SJIS","UTF-8",$datas); //文字コードの変更
        // CSVファイルの書き込み
        $fh = fopen($file,"w");
        fputcsv($fh,$CSV_array);
        fclose($fh);        

        
    } 
    # case2. "選択月"の変更 
    elseif(($in["mode"]=="change_next")||($in["mode"]=="change_befor"))
    {
        $displayDT = change_date(); //"月"変更
        $display = display_date($displayDT);

        read_submission_shift($_SESSION["employee_id"]); //保存データを反映する関数
    
    
    }
    # case3. 入力内容のリセット 
    elseif($in["mode"]=="input_reset")
    {
        # 表示用日付の取得(表示日付 == フォーム指定日付)
        $displayDT = new DateTime($in["month"]);
        $display = display_date($displayDT);
    
    
    }
    # case4. 契約曜日通りの入力 
    elseif($in["mode"]=="fixed_input")
    {
        $displayDT = new DateTime($in["month"]);
        $display = display_date($displayDT);
        fixed_input();
    
    
    }

}

# tokenの発行 $_SESSION['token']
createToken(); 

# カレンダー用の週分割にした日付情報を取得
$calendar_weeks = get_calendar($display);


?>