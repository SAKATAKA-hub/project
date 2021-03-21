<?php
#===========================================================
# スケジュール作成ページプログラム (create_schedule_program.php)
#===========================================================
# 基本設定
// ファイルの読み込み
//[読込順]function.php > schedule_function.php > create_schedule_program.php(現在地)
include('schedule_function.php');

// 労働時間計算クラスファイル
include('calcutation_class.php.php');

#===========================================================
# 表示する従業員リストを取得
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
    $calendar_weeks = get_calendar($display); //週分割にした日付情報を取得



    # 作成済スケジュールを表示に反映
    read_create_shift(); 
}
// 2. フォームからアクセスされたとき ::::::::::::::::::::::
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
        $calendar_weeks = get_calendar($display); //週分割にした日付情報を取得

        # スケジュールをCSVファイルとして保存
        save_create_shift(); 

        # 作成済スケジュールを表示に反映
        read_create_shift(); 



    } 
    # case2. "選択月"の変更 ******************
    elseif(($in["mode"]=="change_next")||($in["mode"]=="change_befor"))
    {
        $displayDT = change_date(); //"月"変更
        $display = display_date($displayDT);
        $calendar_weeks = get_calendar($display); //週分割にした日付情報を取得

        # 作成済スケジュールを表示に反映
        read_create_shift(); 


    }
    # case3. 提出スケジュールの内容を反映 ******************
    elseif($in["mode"]=="read_send_schedule")
    {
        # 表示用日付の取得(表示日付 == フォーム指定日付)
        $displayDT = new DateTime($in["month"]);
        $display = display_date($displayDT);
        $calendar_weeks = get_calendar($display); //週分割にした日付情報を取得

        #　従業員全員の提出スケジュールの読み込み
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
        $calendar_weeks = get_calendar($display); //週分割にした日付情報を取得

        # 作成済スケジュールを表示に反映
        read_create_shift(); 
    
    }
}

# tokenの発行 $_SESSION['token']
createToken(); 

# 労働時間計算クラス
$calcutation = new Calcutation($in,$employees,$calendar_weeks);

#===========================================================
# スケジュール提出ページ用　関数
#-----------------------------------------------------------
# スケジュールをCSVファイルとして保存
function save_create_shift()
{
    global $in, $now, $display, $calendar_weeks, $employees;

    $w_datas = $calendar_weeks[$in["calendar_week"]];
    $w_count = -1; 
    foreach ($w_datas as $key => $w_data) { 
        if($w_data["this_month"]){ $w_count++;}
    }
    
    $first = $w_datas[0]["date"];
    $end = $w_datas[$w_count]["date"];


    $datas = [];
    # 提出スケジュールをCSV用配列に変換
    foreach ($employees as $key => $employee)
    {
        $datas[$key] = save_submission_shift($employee["id"],$first,$end);
    }

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
    global $in, $now, $display, $calendar_weeks, $employees;

    foreach ($calendar_weeks as $w => $c_week){
        // 1.テキストファイルに保存された提出済みスケジュールを取得。
        $directory ="data/create_schedule/";
        // $file = sprintf("%s%s-%02d.csv",$directory,$display["Y-m"],$in["calendar_week"]) ;
        $file = sprintf("%s%s-%02d.csv",$directory,$display["Y-m"],$w) ;
        $datas=[];

        // var_dump($c_week); 
        // echo $c_week[0]["date"]."<br>";
        // echo $c_week[6]["date"]."<br>";
        // echo"<br><br>";


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
        // echo"<br><br>";
    
    
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

                    $day = $c_week[0]["date"];
                    foreach($shift as $vals)
                    {
                        // echo $day."<br>";

                        $vals = explode("-",$vals);
        
                        $name_keys = array("in1","out1","in2","out2",);
                        foreach ($name_keys as $i => $name_key) 
                        {
                            $input_name = sprintf("d%02d:%04d:%s", $day, $employee_id, $name_key);
                            $in[$input_name] = isset($vals[$i]) ? $vals[$i] : "" ;
                        }

                        $day ++;
                    }
                }
                // 2-2　コメント
                $input_name = sprintf("comment:%04d", $employee_id);
                $in[$input_name] = isset($comment) ? $comment : "" ;
    
            }
          
        } //if(!empty($data))end
    

    }


}



