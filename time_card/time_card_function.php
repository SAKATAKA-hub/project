<?php
#-----------------------------------------------------------
# 基本設定

//ファイルの読み込み
//[読込順]function.php > time_card_function.php(現在地)
include('../common/app/function.php');

//ログインページのパス
$login = "../login/login.php";

//日付の取得
$now_dt = new DateTime();
$now_day = $now_dt -> format('Y-m-d');//年月日
$now_time = $now_dt -> format('H:i:s');//時分秒

//エラーチェック
$error_note = "";

#-----------------------------------------------------------
# 出勤レコードを一日以内の範囲にする関数　
// record_date_update();
#-----------------------------------------------------------
//【処理内容の説明】
//・出勤継続中のレコードが24:00を経過した場合は、出勤継続中のレコードは一度24:00で締め、
//　翌日0:00から開始する新しい出勤レコードを新規追加して記録を開始する。
//・出勤継続中のレコードとチェック日との日付間について、日付間分の"新しいレコード"を新規追加し、
//　各労働者の最新レコードの判断基準である"採番"を更新する。
//・出勤継続中のレコードが休憩継続中の場合は、その日の休憩終了時間を24:00とし、翌日の新しい
//　出勤レコードの休憩開始時間を0:00から開始する。
//・24:00を経過した判断は、出退勤入力ページと、出退勤閲覧ページの表示時に実施するものとする。
//・二重投稿防止策：レコードが挿入されるのは退勤時間が未入力の時だけなので、
//一度入力登録してしまえば二重登録にならない。

function record_date_update(){
    global $error_note;

    # 基本情報の取得----------------------
    // 日時の取得
    $now_dt = new DateTime();
    $now_day = $now_dt -> format('Y-m-d');//年月日(今日)
    $now_time = $now_dt -> format('H:i:s');//時分秒(今日)

    $yesterday_dt = new DateTime();
    $yesterday_dt->modify('-1 day');
    $yesterday_day = $yesterday_dt->format('Y-m-d');//年月日(昨日)

    // DBの"退勤入力が未入力"かつ"出勤が今日以前"のレコードを取得
    $SQL = <<<_SQL_
    SELECT * FROM work_record
    WHERE `in_day` <= ? AND `out_time` IS NULL 
    _SQL_;
    $DATA = array($yesterday_day);
    $records = select_db($SQL,$DATA); //DBレコード読込み

    if(count($records)==0){$error_note .= "***退勤未入力レコードなし！***<br>";}
    else{$error_note .= "退勤未入力リスト：<br>";}


    foreach($records as $record){

        var_dump($record);
        echo"<br><br>";

        $tb_id = $record["tb_id"]; //出勤レコードID
        $employee_id = $record["employee_id"]; //従業員ID 
        $employee_name = $record["employee_name"]; //従業員名

        $in_day = $record["in_day"];//出勤開始日
        $past_dt = new DateTime($in_day);
        $diff = $now_dt->diff($past_dt);
        $btw_day = $diff->d -1; //日付間の日数

        $num_file = "data/work_nom".$employee_id.".txt";
        $work_num = read_num_file($num_file); //採番

        $break_array = []; //休憩時間を格納する配列
        $break1 = $record["break"];
        if(isset($break1)){
            $break = substr($break1,0,-1);
            $break_array = explode(",",$break);
        }else{
            $break_array[] = NULL;
        }
        $blake_val_count = count($break_array);//休憩の入力回数
        if($break_array[0] == NULL){$blake_val_count =0;}

        $error_note .=
        "***************************************************<br>".
        "名前：".$employee_name."　出勤日：".$in_day."<br>".
        "休憩テキスト：".$break1."<br>".
        "休憩入力回数：".$blake_val_count."<br>"
        ;


        # レコードの書換えと挿入----------------------
        //休憩レコードの挿入の下処理
        switch ($blake_val_count %2) {
            case 1:
                $break1 .= "24:00:00,";
                $break2 = "00:00:00,24:00:00,";
                $break3 = "00:00:00,";
                $error_note .="修正後休憩入力：".$break1."<br><br>";
                break;            
            case 0:
                $break2 = NULL;
                $break3 = NULL;
            break;
        }

        // 1)出勤入力日の締め
        $SQL = <<<_SQL_
        UPDATE work_record 
        SET `out_time` = ? , `break` = ?
        WHERE `tb_id` = ? 
        _SQL_;
        $DATA = array("24:00:00", $break1, $tb_id);
        insert_db($SQL,$DATA); //レコード締め
        $work_num ++; //採番の更新
        
        // 2)出勤日以降のレコード挿入と締め
        for ($i=0; $i <= $btw_day; $i++) {
            $past_dt->modify('+1 day');
            $in_day = $past_dt->format('Y-m-d');//レコード挿入日

            $SQL = <<<_SQL_
            INSERT INTO work_record 
            ( `employee_id`, `employee_name`,`work_num`,
             `in_day`,`in_time`, `out_time`,`break` ) 
            VALUES ( ?,?,?,?,?,?,?)
            _SQL_;

            if($i != $btw_day){ //中間日のレコード挿入と締め
                $DATA = array(
                    $employee_id, $employee_name, $work_num,
                    $in_day, "00:00:00", "24:00:00", $break2
                );     
                insert_db($SQL,$DATA); //レコードの挿入 
                $work_num ++; //採番の更新

            }else{ //チェック日のレコード挿入
                $DATA = array(
                    $employee_id, $employee_name, $work_num,
                    $in_day, "00:00:00", NULL, $break3
                );
                insert_db($SQL,$DATA); //レコードの挿入 
            }

        }
        
        $work_state = "inWork";//出勤状況へ保存する内容

        //セッションの値を変更
        $_SESSION["work_state"] = $work_state;//セッションの値を変更

        //----------------------------------
        // ＤＢの出勤状況を書き換え
        $SQL = "UPDATE employee_data SET work_state = ? WHERE `employee_data`.`id` = ? ";
        $SQL = "UPDATE employee_data SET work_state = ? WHERE id = ? ";
        $DATA = array($work_state,$employee_id);//プリペアーステートメントの値
        insert_db($SQL,$DATA);  //関数の実行
        //----------------------------------

        //採番ファイルの書き込み
        $fh = fopen($num_file,'w');
        $f_data = $work_num;
        fwrite($fh,$f_data);
        fclose($fh);

        echo "ワークステート：".$work_state;
    }
}




// echo $error_note;//エラーチェックの関数

# 採番ファイルの読み込み
//記述
//$num_file = "data/work_nom".$employee_id.".txt";
//$work_num = read_num_file($num_file);
function read_num_file($file){
    if(file_exists($file)){
      $num = file_get_contents($file);
    }else{
      $num = 1;  
    }
    return $num;
}



