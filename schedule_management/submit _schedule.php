<?php
#===========================================================
# submit_schedule.php スケジュール提出プログラム
#===========================================================
# 基本設定
//ファイルの読み込み
//[読込順]function.php > submit_schedule.php(現在地)
include('../common/app/function.php');



#===========================================================

//1-1 フォーム（表示切替ボタン）の受取り

$in = parse_form(); //フォームの受取り
if(!empty($in)){
    echo $_SESSION['token']."<br>";
    var_dump($in);
    validateToken(); //tokenのチェック
    if($in["mode"]=="submit"){echo "成功";}
}
createToken(); //tokenの発行 $_SESSION['token']



// 出勤時間選択OPTION($select_time_element)の作成                           
$work_times = [];
$work_times[] = "";
$work_time_mins = array(":00",":30",);
for ($i=0; $i < 24; $i++) { 
    $val = $i+8>23 ? $i+8-24 :$i+8;
    foreach ($work_time_mins as $min) {
        $work_times[] = sprintf("%02d",$val).$min."<br>";
    }
}
$select_time_element = "";
foreach($work_times as $time){
    $select_time_element .=<<<_text_
    <option value="$time">$time</option>
    _text_;
}

// tr要素の作成


$text =<<<_text_
<tr class="date!num!">
<td class="dates">
<div class="date"> !num!日(!week!)</div>
</td>

<td class="input">
<div class="input0">
<input type="radio" name="attendance!num!" value="Attendance" id="id_Attendance"　 >
<label for="id_Attendance">出勤</label>
<input type="radio" name="attendance!num!" value="paid" id="id_paid">
<label for="id_paid">有給</label>
</div>

<div class="input1">
シフト1
<select name="in_time!num!-1">
!select_time_element!
</select>
~
<select name="outtime!num!-1">
!select_time_element!
</select>
</div>

<div class="input2"> 
シフト2
<select name="in_time!num!-2">
!select_time_element!
</select>
~
<select name="outtime!num!-2">
!select_time_element!
</select>
</div>
</td>
</tr>
_text_;

$end_day = 31; // 月末日
$week_num = 1; // 曜日
$weeks = array("日","月","火","水","木","金","土",);
$tr_element ="";
for ($i=1; $i <= $end_day; $i++) {
    $num = sprintf("%02d",$i) ;

    $replace_array = array(
        "!num!" => $num , 
        "!week!" => $weeks[$week_num] ,
        "!select_time_element!" => $select_time_element ,
    );
    

    $replace_text = $text;
    foreach ($replace_array as $key => $value) {
        $replace_text =str_replace($key,$value,$replace_text);
    }
    $tr_element .= $replace_text;

    $week_num = $week_num == 6 ? 0 : $week_num+1;
}


?>


<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>スケジュール提出</title>
  <link rel="stylesheet" href="../common/css/submit _schedule.css"
  media="screen and (min-width:441px)">
  <link rel="stylesheet" href="../common/css/submit _schedule_PC.css"
  media="screen and (max-width:440px)">
</head>
<body>
    <main id="submit _schedule">
        <form action="" method="POST">
        <input type="hidden" name="mode" value="submit">
        <input type="hidden" name="token" value="<?= $_SESSION['token'];?>"> 


        <!-- 1.セレクトコンテナー ------------>
        <div class="select_container">
            <div class="select_month">
                <div class="befor_mon">前月</div>
                <div class="select_mon">3月</div>
                <div class="next_mon">翌月</div>    
            </div>
            <div class="select_input_menu">
                <div class="auto_input">定型入力</div>
                <div class="delete">入力リセット</div>    
            </div>
        </div>

        <!-- 2.インプットコンテナー ------------>
        <div id="input_container">
        <!-- 2-1 -->
            <table class="calendar">
                <?= $tr_element;?>
            </table>

            <!-- 2-2 -->
            <div class="comment_box">
                <p>コメント</p>
                <textarea class="comment_text" name="comment" id="" ></textarea>
            </div>

            <!-- 3.集計コンテナー ------------>
            <div class="aggregate">
                <div class="total_time">
                    <p>予定勤務時間</p>
                    <p>100時間</p>
                </div>
                <div class="total_wage">
                    <p>予定獲得賃金</p>
                    <p>100000円</p>
                </div>
            </div>

            <!-- 4.送信ボタンコンテナー ------------>
            <div class="submit_container">
                <button type="submit">提出</button>
            </div>
        </div>



        </form>
    </main>
    <script>
    'use strict';
    {
    // id属性で要素を取得
    var p2 = document.getElementById('p2');
    var p3 = document.getElementById('p3');

    // 新しいHTML要素を作成
    // var new_element = document.createElement('p');
    // new_element.textContent = '追加テキスト';

    // 指定した要素の中の末尾に挿入
    // p3.appendChild(new_element);
    p2.innerHTML = "<?= $p1;?>";
    p3.innerHTML = "<h3>h3タグに変更しました</h3>";
    }
    </script>  
</body>




        
            

            
