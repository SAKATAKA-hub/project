<?php
#-----------------------------------------------------------
# 基本設定

//ファイルの読み込み
//[読込順]function.php > time_card_function.php > insert_record.php > time_card.php(現在地)
include('insert_record.php');

//ログインページのパス
$login = "../login/login.php";
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