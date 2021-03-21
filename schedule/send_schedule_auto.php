<?php
#===========================================================
# スケジュール提出自動更新プログラム (send_schedule_auto.php)
#===========================================================
# 基本設定
// ファイルの読み込み
//[読込順]function.php > schedule_function.php > send_schedule_auto.php(現在地)
include('schedule_function.php');

#===========================================================
# 表示する従業員リストを取得
$SQL = "SELECT `id`, `name` FROM employee_data WHERE `group` = ?";
$DATA = array( "group1" ); //取得グループ名
$employees = select_db($SQL,$DATA);  //DBより従業員情報の取得

# アクセス日の取得
$now = get_now();

# フォームの受取り
$in = parse_form();

// フォームからアクセスされたとき ::::::::::::::::::::::
if((isset($in["mode"]))&&($in["mode"]=="send_schedule")) 
{
    # 表示用日付の取得(表示日付 == フォーム指定日付)
    $displayDT = new DateTime($in["month"]);
    $display = display_date($displayDT);

    $save = array("month"=>$in["month"], "token"=>$in["token"], "mode"=>$in["mode"]);

    // var_dump($save);

    foreach ($employees as $employee)
    {
        $in = $save;
        #契約曜日通りの入力
        fixed_input($employee["id"]);

        # 指定コメントの挿入
        if($employee["id"] == "0001"){
            $comment = "ゲストさんが出勤する日はお休みで大丈夫です！";
            $input_name = sprintf("comment:%04d", $employee["id"]);
            $in[$input_name] =  $comment ;    
        }

        
        # 提出スケジュールをCSV用配列に変換
        $CSV_array = save_submission_shift($employee["id"],1,$display["end_d"]);

        # 入力データをテキストファイルに保存
        $directory ="data/send_schedule/";
        $file = $directory.$display["Y-m-d"]."-".$employee["id"].".csv";
        mb_convert_variables("SJIS","UTF-8",$datas); //文字コードの変更
        
        // CSVファイルの書き込み
        $fh = fopen($file,"w");
        fputcsv($fh,$CSV_array);
        fclose($fh);        




        # tokenのチェック
        validateToken(); 
    }
}else{
    # 表示用日付の取得(表示日付 == アクセス日翌月)
    $displayDT = new DateTime(sprintf("%04d-%02d-01",$now["Y"] ,$now["m"]+1 ));
    $display = display_date($displayDT); 
}

# tokenの発行 $_SESSION['token']
createToken(); 

?>
<h2>スケジュール一括提出</h2>
<form action="" method="GET">
    <input type="hidden" name="mode" value="send_schedule">
    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">
    <input type="text" name="month" value="<?= $display["m_text"]; ?>">
    <button class="">提出一括提出</button>
</form>
<h3><?= isset($in["mode"])&&$in["mode"]=="send_schedule"? $in["month"]."の処理が完了しました！" : "処理の入力待ち…" ; ?></h3>

