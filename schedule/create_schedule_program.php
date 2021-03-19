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
    $in["calendar_week"] = 0 ;


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

        # スケジュールをCSVファイルとして保存
        save_create_shift(); 


    } 
    # case2. "選択月"の変更 ******************
    elseif(($in["mode"]=="change_next")||($in["mode"]=="change_befor"))
    {
        $displayDT = change_date(); //"月"変更
        $display = display_date($displayDT);

        # 作成済スケジュールを表示に反映
        read_create_shift(); 


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
    # case4. カレンダーの表示"週"を変更 ******************
    elseif($in["mode"]=="change_week")
    {
        # 表示用日付の取得(表示日付 == フォーム指定日付)
        $displayDT = new DateTime($in["month"]);
        $display = display_date($displayDT);
    
    }
}

createToken(); //tokenの発行 $_SESSION['token']

# カレンダー用の週分割にした日付情報を取得
$calendar_weeks = get_calendar($display);


#===========================================================
# スケジュール提出ページ用　関数
#-----------------------------------------------------------
# スケジュールをCSVファイルとして保存
function save_create_shift()
{
    global $in, $now, $display, $employees;

    $datas = [];
    $Agg_datas = [];
    # 提出スケジュールをCSV用配列に変換
    foreach ($employees as $key => $employee)
    {
        $datas[$key] = save_submission_shift($employee["id"]);
        // $Agg_datas[$key] = new Aggregate($datas[$key]);
    }

    // foreach ($datas as $key => $data) {
    //     echo "<br><br>";
    //     var_dump($data);
    // }

    // var_dump($datas);

    # 入力データをテキストファイルに保存
    $directory ="data/create_schedule/";
    $file = sprintf("%s%s-%02d.csv",$directory,$display["Y-m"],$in["calendar_week"]) ;
    // echo $file;
    mb_convert_variables("SJIS","UTF-8",$datas); //文字コードの変更

    // CSVファイルの書き込み
    $fh = fopen($file,"w");
    foreach ($datas as $key => $data) {
        fputcsv($fh,$data);
    }
    fclose($fh);
    

}


#-----------------------------------------------------------
# 作成済スケジュールを表示に反映
function read_create_shift()
{
    global $in, $now, $display, $employees;

    // 1.テキストファイルに保存された提出済みスケジュールを取得。
    $directory ="data/create_schedule/";
    $file = sprintf("%s%s-%02d.csv",$directory,$display["Y-m"],$in["calendar_week"]) ;

    $datas=[];
    if(file_exists($file))
    {
        //ファイルの読み込み
        $fh = fopen($file,"r");
        while ($line = fgetcsv($fh)) {
            array_push($datas,$line);
        }
        fclose($fh);
        
        // 文字コードの変更
        mb_convert_variables("UTF-8","SJIS",$datas); //文字コードの変更
    }

    // echo "提出スケジュールの取得<br>";
    // var_dump($datas);


    // 2.取得情報を加工
    if(!empty($datas))
    {
        foreach ($datas as $key => $data) 
        {
            $employee_id = $data[0];
            $shift = $data[1];
            $comment = $data[2];

            //2-1スケジュール
            if(!empty($shift))
            {
                $shift = explode("=",$shift);
        
                foreach($shift as $key => $vals)
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
            $in[$input_name] = isset($comment) ? $comment : "" ;

        }
      
    } //if(!empty($data))end

}


#-----------------------------------------------------------
# 勤務予定時間の集計クラス ->110
class Aggregate{

}

