<?php
#-----------------------------------------------------------
# 基本設定

//function.phpファイルの読み込み
include('../common/app/function.php');

//ログインページのパス
$login = "../login/login.php";

//日付の取得
$now_dt = new DateTime();
$now_day = $now_dt -> format('Y-m-d');//年月日
$now_time = $now_dt -> format('H:i:s');//時分秒

#-----------------------------------------------------------
# ログイン後、セッション情報を変数に代入
//id情報
if(!empty($_SESSION["employee_id"])){
    $employee_id = $_SESSION["employee_id"];
}else{$employee_id ="";}
//名前情報
if(!empty($_SESSION["employee_name"])){
    $employee_name = $_SESSION["employee_name"];
}else{$employee_name ="ログインしてください";}
//出勤状況
if(!empty($_SESSION["work_state"])){
    $work_state = $_SESSION["work_state"];
}else{$work_state = "";}

#-----------------------------------------------------------
# 出退勤ボタンの入力（フォームデータの受け取り）とそれに応じた処理
$in = parse_form();

if(!empty($in)){
  //フォームの値を変数へ代入
  $work_state = $in["mode"]; 
  
  //採番ファイルの読み込み
  $num_file = "data/work_nom".$employee_id.".txt";
  if(file_exists($num_file)){
    $f_datas = file_get_contents($num_file);
    $f_datas = explode(" ",$f_datas);     
    $work_num = $f_datas[0]; //総勤務回数    
    $break_num = $f_datas[1]; //一勤務当たりの休憩回数
  }else{
    $work_num = 1;  
    $break_num = 0;
  }

    #----------------------------------
    # ＤＢへ出退勤データの挿入
    // 1)レコードの重複挿入のチェック
    // 2)データの挿入
    switch ($work_state) {
        case 'inWork': //--出勤入力--
            inWork();    
            break;
        case 'outWork': //--退勤入力--
            outWork();    
            break;
        case 'inBreak': //--休憩開始入力--
            in_out_break("in");
            break;
        case 'outBreak': //--休憩終了入力--
            in_out_break("out");
            // out_break();
            break;
        default:
        break;
    }
    
    //採番の更新
    if($work_state == "outWork"){$work_num ++; $break_num = 0;}
    if($work_state == "inBreak"){$break_num ++;}
  
    //セッションの値を変更
    if($work_state == "outBreak"){$work_state = "inWork";}//"休憩終了"⇒"勤務開始"
    $_SESSION["work_state"] = $work_state;//セッションの値を変更
    // echo"セッション".$_SESSION["work_state"]."<br>";


    //----------------------------------
    // ＤＢの出勤状況を書き換え
    $SQL = "UPDATE employee_data SET work_state = ? WHERE `employee_data`.`id` = ? ";
    $DATA = array($work_state,$employee_id);//プリペアーステートメントの値
    insert_db($SQL,$DATA);  //関数の実行
    //----------------------------------

    //採番ファイルの書き込み
    $fh = fopen($num_file,'w');
    $f_data = $work_num." ".$break_num;
    fwrite($fh,$f_data);
    fclose($fh);


}


#-----------------------------------------------------------
# 出勤状況に対応した表示画面の変更処理
$work_state_text = "";
$in_class = "inoperable";//出勤ボタンのスタイル切換え
$break_class = "inoperable";//退勤ボタンのスタイル切り換え
$out_class = "inoperable";//休憩ボタンのスタイル切り換え

$in_action = "";//出勤ボタンのスタイル切換え
$break_action = "";//退勤ボタンのスタイル切換え
$out_action = "";//休憩ボタンのスタイル切換え

$post_break = "inBreak";//休憩ボタンからフォームで送る内容の切り替え（inBreak/outBreak）
$user_info_style = "userInfo outWork";//ユーザー情報表示エリアのスタイル切替え

switch ($work_state) {
    case 'inWork':
        $work_state_text = "出勤中";
        $out_class = "";
        $out_action = "#";
        $break_class = "";
        $break_action = "#";
        $user_info_style = "userInfo";      
        break;
    case 'outWork':
        $work_state_text = "退勤済";
        $in_class = "";
        $in_action = "#";      
        break;
    case 'inBreak':
        $work_state_text = "休憩中";
        $break_class = "";
        $break_action = "#";
        $post_break = "outBreak";
        $user_info_style = "userInfo inBreak";      
        break;
    default:
    break;
}

#-----------------------------------------------------------
# 出勤形態別に実行する関数
#-----------------------------------------------------------
#--出勤入力---------------------------
function inWork(){ 
    global $employee_id, $employee_name, $work_num, $now_day, $now_time; 

    # --重複挿入のチェック--
    //  SQL命令文
    $SQL = <<<_SQL_
    SELECT `in_day` FROM work_record
    WHERE `employee_id` = ? AND `work_num` = ? 
    _SQL_;
    //  プリペアーステートメントの値
    $DATA = array($employee_id, $work_num);
    //  レコード読込み関数の実行
    $chake_d = select_db($SQL,$DATA);

    if(empty($chake_d)){    
        # --新しいレコードの作成--
        //  SQL命令文
        $SQL = <<<_SQL_
        INSERT INTO work_record 
        ( `employee_id`, `employee_name`,`work_num`, `in_day`,`in_time` ) 
        VALUES ( ?,?,?,?,?)
        _SQL_;
        //  プリペアーステートメントの値
        $DATA = array(
            $employee_id, $employee_name, $work_num, $now_day, $now_time 
        );
        //  レコード作成関数の実行
        insert_db($SQL,$DATA);  
    }
}


#--退勤入力---------------------------
function outWork(){
    global $now_day, $now_time, $employee_id, $work_num;
    # --重複挿入のチェック--
    //退勤入力後に採番が変化し、同じ裁判のデータに上書きができないため、チェックの必要なし

    # --レコードの上書き--
    //  SQL命令文
    $SQL = <<<_SQL_
    UPDATE work_record 
    SET `out_day` = ? , `out_time` = ?
    WHERE `employee_id` = ? 
    AND `work_num` = ? 
    _SQL_;
    //  プリペアーステートメントの値
    $DATA = array($now_day, $now_time, $employee_id, $work_num);
    //  レコード上書き関数の実行
    insert_db($SQL,$DATA);  

}


#--休憩開始・終了入力---------------------------
function in_out_break($in_out){
    global $now_day, $now_time, $employee_id, $work_num;

    # --レコードの読み込み--
    //  SQL命令文
    $SQL = <<<_SQL_
    SELECT `break` FROM work_record
    WHERE `employee_id` = ? AND `work_num` = ? 
    _SQL_;

    //  プリペアーステートメントの値
    $DATA = array($employee_id, $work_num);

    //  レコード読込み関数の実行
    $break_d = select_db($SQL,$DATA);
    $break_d = $break_d[0][0];
    $break_d .= $now_day." ".$now_time.",";//休憩時の"日付と時間"を代入

    // if($break_d==NULL){echo "ぬる";}
    // else{echo $break_d."<br>";}

    //保存したデータ数の確認
    
    $sub_break_d = substr($break_d,0,-1);//尾末の","を除く
    $break_d_array = explode(",",$sub_break_d);
    // foreach($break_d_array as $c){
    //     echo $c."<br>";
    // }
    $break_d_count = count($break_d_array);
    // echo $break_d_count;

    switch ($in_out) {
        case "in":
            if($break_d_count %2 == 1){
                // insert_break_record(); //--休憩レコードの上書き--
                $SQL = <<<_SQL_
                UPDATE work_record 
                SET `break` = ?
                WHERE `employee_id` = ? AND `work_num` = ? 
                _SQL_;
                //  プリペアーステートメントの値
                $DATA = array($break_d, $employee_id, $work_num);
                //  レコード上書き関数の実行
                insert_db($SQL,$DATA);
            }
            break;
        case "out":
            if($break_d_count %2 == 0){
                // insert_break_record(); //--休憩レコードの上書き--
                $SQL = <<<_SQL_
                UPDATE work_record 
                SET `break` = ?
                WHERE `employee_id` = ? AND `work_num` = ? 
                _SQL_;
                //  プリペアーステートメントの値
                $DATA = array($break_d, $employee_id, $work_num);
                //  レコード上書き関数の実行
                insert_db($SQL,$DATA);
            }
            break;
    }

    # --休憩レコードの上書き--
    function insert_break_record(){
        global $break_d, $employee_id, $work_num;    
        //  SQL命令文
        $SQL = <<<_SQL_
        UPDATE work_record 
        SET `break` = ?
        WHERE `employee_id` = ? AND `work_num` = ? 
        _SQL_;
        //  プリペアーステートメントの値
        $DATA = array($break_d, $employee_id, $work_num);
        //  レコード上書き関数の実行
        insert_db($SQL,$DATA);
    }
}



function out_break(){
    global $now_day, $now_time, $employee_id, $work_num;

    # --レコードの読み込み--
    //  SQL命令文
    $SQL = <<<_SQL_
    SELECT `break` FROM work_record
    WHERE `employee_id` = ? AND `work_num` = ? 
    _SQL_;

    //  プリペアーステートメントの値
    $DATA = array($employee_id, $work_num);

    //  レコード読込み関数の実行
    $break_d = select_db($SQL,$DATA);
    $break_d = $break_d[0][0];
    $break_d .= $now_day." ".$now_time.",";//休憩時の"日付と時間"を代入
    
    //  SQL命令文
    $SQL = <<<_SQL_
    UPDATE work_record 
    SET `break` = ?
    WHERE `employee_id` = ? AND `work_num` = ? 
    _SQL_;
    //  プリペアーステートメントの値
    $DATA = array($break_d, $employee_id, $work_num);
    //  レコード上書き関数の実行
    insert_db($SQL,$DATA);
        
}
#-----------------------------------------------------------
#　↓　↓　↓　表示エリア　↓　↓　↓

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>タイムカード</title>
  <link rel="stylesheet" href="../common/css/time_card.css">
</head>
<body>
  <main id="timeCord"><!--attendanceManager-->
    <h2>出退勤管理</h2>

    <!--現在時刻の表示領域-->
    <div id="showTime">
      <div id="nowDay"></div>
      <div id="nowTime"></div>
    </div>    

    <!--ユーザー情報-->
    <div id ="" class="<?= $user_info_style;?>">
      <div class="top">
        <p id="userImg"></p>
        <p id="userId"><?= $employee_id;?></p>
        <p id="userName"><?= $employee_name;?></p>  
      </div>
      <div class="bottom">
        <p class="workState"><?= $work_state_text;?></p>
      </div>
    </div>

    <!--出退勤入力ボタン-->
    <div id ="selectMord">
      <!--①出勤ボタン-->
      <form action="<?= $in_action;?>" method="post">
        <input type="hidden" name="mode" value="inWork">
        <button type="submit" class="<?= $in_class;?>">出勤</button>
      </form>
      <!--②休憩ボタン-->
      <form action="<?= $break_action;?>" method="post">
        <input type="hidden" name="mode" value="<?= $post_break;?>">
        <button type="submit" class="<?= $break_class;?>">休憩</button>
      </form>     
      <!--③退勤ボタン-->
      <form action="<?= $out_action;?>" method="post">
        <input type="hidden" name="mode" value="outWork">
        <button type="submit" class="<?= $out_class;?>">退勤</button>
      </form>
    </div>

    <button class="btn_flat" type="button" onclick="location.href=`<?= $login;?>`">ログイン画面に戻る</button>

  </main>
  <script>
    'use strict';
    {
      function showTime(){
        const now = new Date();
        const nowYear = now.getFullYear();
        const nowMonth = String(now.getMonth()+1).padStart(2,'0');
        const nowDate = String(now.getDate()).padStart(2,'0');
        const nowHour = String(now.getHours()).padStart(2,'0');
        const nowMin = String(now.getMinutes()).padStart(2,'0');
        const nowSec = String(now.getSeconds()).padStart(2,'0');
        const dayNum = String(now.getUTCDay());

        const DayArry =["(日)","(月)","(火)","(水)","(木)","(金)","(土)"];

        let ampm = "";
        if(nowHour<12){ampm = "AM";}
        else{ampm = "PM";}
  
        const outputDay = `${nowYear}年${nowMonth}月${nowDate}日${DayArry[dayNum]}`;
        const outputTime = `${ampm} ${nowHour % 12}:${nowMin}:${nowSec}`;
  
        document.getElementById('nowDay').textContent = outputDay;
        document.getElementById('nowTime').textContent = outputTime;
        refresh();
      }
      function refresh(){setTimeout(showTime,1000);}
  
      showTime();

    }
  </script>  
</body>