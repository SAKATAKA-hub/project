<?php
#===========================================================
# タイムカード表示エリアファイル (time_card.php)
#===========================================================
# 基本設定
//ファイルの読み込み
//[読込順]function.php　> class.php　> issert_function.php > program.php > time_card.php(現在地)
include('program.php');

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
    <h2>出退勤入力</h2>

    <!--現在時刻の表示領域-->
    <div id="showTime">
      <div id="nowDay"></div>
      <div id="nowTime"></div>
    </div>    

    <!--ユーザー情報-->
    <div id ="" class="userInfo <?= $work_state;?>">
      <div class="top">
        <p id="userImg"></p>
        <p id="userId"><?= $_SESSION["employee_id"];?></p>
        <p id="userName"><?= $_SESSION["employee_name"];?> さん</p>  
      </div>
      <div class="bottom">
        <p class="workState"><?= $user_info_text;?></p>
      </div>
    </div>

    <!--出勤・退勤・休憩ボタン-->
    <div id ="selectMord">
      <?php foreach ($work_buttons as $w_button):?>

      <form action="#" method="post">
        <input type="hidden" name="mode" value="<?= $w_button["mode"];?>">
        <button type="submit" class="<?= $w_button["class"];?>">
        <!-- style="pointer-events:none" -->
        <?= $w_button["text"];?>
        </button>
      </form>

      <?php endforeach;?>
    </div>

    <button class="btn_flat" type="button" onclick="window.close();">閉じる</button>

  </main>
  <script src="js/nowTime.js"></script>

</body>