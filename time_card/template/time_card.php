<?php
#-----------------------------------------------------------
#基本設定

#function.phpファイルの読み込み
include('../common/app/function.php');

#ログインページのパス
$login = "../login/login.php";

#日付の取得
$now_dt = new DateTime();
$now_day = $now_dt -> format('Y-m-d');//年月日
$now_time = $now_dt -> format('H:i:s');//時分秒

#-----------------------------------------------------------
#ログイン後、セッション情報を変数に代入
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
//出退勤ボタンの入力（フォームデータの受け取り）とそれに応じた処理
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

    
  //----------------------------------
  //ＤＢへ出退勤データの挿入
  // 1)二重挿入のチェック
  $SQL = "SELECT * FROM worked_record WHERE employee_id = ?";//SQL命令文
  $DATA = array($employee_id);//プリペアーステートメントの値
  $worked_records = select_db($SQL,$DATA);  //関数の実行（取得データをリターンする） 
    
  $check_only_reccord = false;//重複
  foreach ($worked_records as $record) {    
    if($work_state == "inWork"){//勤務開始入力のとき----------
      if(
      ($record["work_num"] == $work_num)
      &&($record["work_state"] == "inWork")
      ){
        $check_only_reccord = false;//重複
      }else{
        $check_only_reccord = true;
      }
    }
    if($work_state == "outWork"){//勤務終了入力のとき----------
      if(
      ($record["work_num"] == $work_num)
      &&($record["work_state"] == "inWork")
      ){
        $check_only_reccord = true;
      }
    }
    if($work_state == "inBreak"){//休憩開始入力のとき----------
      if($break_num == 0){
        $check_only_reccord = true;
      }elseif(
      ($record["work_num"] == $work_num)
      &&($record["break_num"] == $break_num)
      &&($record["work_state"] == "outBreak")
      ){
        $check_only_reccord = true;
      }
    }
    if($work_state == "outBreak"){//休憩終了入力のとき----------
      if(
      ($record["work_num"] == $work_num)
      &&($record["break_num"] == $break_num)
      &&($record["work_state"] == "outBreak")
      ){
        $check_only_reccord = false;//重複
      }else{
        $check_only_reccord = true;
      }
    }
  }

  // if(!$check_only_reccord){echo"不合格<br>";}//再読み込み時の重複アラート

  // 2)二重挿入ではないとき
  if($check_only_reccord){
    //採番の更新
    if($work_state == "outWork"){$work_num ++; $break_num = 0;}
    if($work_state == "inBreak"){$break_num ++;}
      
    

    //出退勤データの挿入
    $SQL = <<<_SQL_
    INSERT INTO worked_record 
    ( `employee_id`, `employee_name`, `work_state`, `day`, `time`, `work_num`, `break_num`) 
    VALUES ( ?,?,?,?,?,?,?)
    _SQL_;//SQL命令文
    $DATA = array($employee_id, $employee_name, $work_state, $now_day, $now_time, $work_num, $break_num );//プリペアーステートメントの値
    insert_db($SQL,$DATA);  //SQL関数の実行
    //----------------------------------

  }
  
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
//出勤状況に対応した表示画面の変更処理
$work_state_text = "";
$in_class = "inoperable";
$break_class = "inoperable";
$out_class = "inoperable";

$in_action = "";
$break_action = "";
$out_action = "";

$post_break = "inBreak";
$user_info_style = "userInfo outWork";

if($work_state == "inWork"){
  $work_state_text = "出勤中";
  $out_class = "";
  $out_action = "#";
  $break_class = "";
  $break_action = "#";
  $user_info_style = "userInfo";
  // $post_break = "inBreak";

}elseif($work_state == "outWork"){
  $work_state_text = "退勤済";
  $in_class = "";
  $in_action = "#";

}elseif($work_state == "inBreak"){
  $work_state_text = "休憩中";
  $break_class = "";
  $break_action = "#";
  $post_break = "outBreak";
  $user_info_style = "userInfo inBreak";
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