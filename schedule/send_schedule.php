<?php
# 基本設定
//ファイルの読み込み
//[読込順]function.php > schedule_function.php > send_schedule_program.php
//  > send_schedule.php(現在地)
include('send_schedule_program.php');

//スケジュール保存完了ポップアップ
$popup ="";
if(isset($in["mode"])&&($in["mode"]=="submit")){ 
    $popup = "<script>window.addEventListener('load', popupSubmit());</script>";
}  

// echo "<br>inに保存した情報<br>";
// var_dump($in);

?>


<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>スケジュール管理 スケジュール提出</title>
  <link rel="stylesheet" href="../common/css/send_schedule.css">
  <link rel="stylesheet" href="../common/css/header.css">
</head>
<body >
<header></header>
<main>
    <!-- ******** <m_side> ******** ******** ******** -->
    <div class="m_side">

    
    
    </div>

    <!-- ******** <m_center> ******** ******** ******** -->
    <div class="m_center">

        <!-- パンくずリスト -->
        <div class="bread_crumb">
        <ul>
            <li><a href="">HOME</a></li>
            <li>スケジュール管理</li>
            <li>スケジュール提出</li>
        </ul>
        </div>
    
        <h2>スケジュール提出</h2>
        <h3><?= $_SESSION['employee_name']; ?>さんの 提出スケジュール</h3>

        <!-- 1.セレクトエリア ------------>
        <div class="select_container">
                <div class="select_month">
    
                    <form action="" method="GET">
                        <input type="hidden" name="mode" value="change_befor">
                        <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
                        <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                        <button class="befor">前へ</button>    
                    </form>
    
    
                    <h4 class="select_mon"><?= $display["m_index"]; ?></h4>
    
    
                    <form action="" method="GET">
                        <input type="hidden" name="mode" value="change_next">
                        <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
                        <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                        <button class="next">次へ</button>
                    </form>
    
    
                </div>
                <div class="select_input_menu">
                    
                    <form action="" method="GET">
                        <input type="hidden" name="mode" value="fixed_input">
                        <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
                        <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                        <button class="auto_input">定型入力</button>
                    </form>
    
                    <form action="" method="GET">
                        <input type="hidden" name="mode" value="input_reset">
                        <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
                        <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                        <button class="delete">リセット</button>
                    </form>
        
                </div>
        </div>

        <!-- 2.インプットエリア ------------>
        <div class="table_container">
        <form action="" method="GET">
            <input type="hidden" name="mode" value="submit">
            <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
            <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">

            <!-- モーダル -->
            <section id="modal" class="hidden">
                <p>スケジュールを提出します。</p>
                <p>前回提出分は上書きされます。</p>
                <p>よろしいですか？</p>
                <div class="m_btn_container">
                    <button type="submit" class="btn" >提出</button>
                    <div id="close" class="btn">戻る</div>
                </div>   
            </section>

            <!-- マスク -->
            <div id="mask" class="hidden"></div>

            <!-- ポップアップ -->
            <div id="popup0" class="popup"><?=$display["m_index"];?>分のスケジュールを提出しました。</div>


            <!-- 2-1 -->
            <table class="shift_table">

                <tr>
                    <th>MON</th>
                    <th>TUE</th>
                    <th>WED</th>
                    <th>THU</th>
                    <th>FRI</th>
                    <th class="sat_color">SAT</th>
                    <th class="sun_color">SUN</th>
                </tr>

                <?php foreach ($calendar_weeks as $c_week):?>
                <tr>

                    <!-- 日付の表示エリア -->
                    <?php foreach ($c_week as $c_day):?>
                    <?php $date = $c_day["this_month"] ? $c_day["date"] : "" ;?>
                    <td>

                        <!-- 日付boX -->
                        <div class="date_box  <?= $c_day["week"]== 7 ? sun_color : $c_day["week"]== 6 ? sat_color :"" ;?>" > 
                            <?= $date?>
                        </div>

                        <!-- 入力boX -->
                        <?php if($c_day ["this_month"]):?>
                        <div class="input_box"> 
                            
                            <!-- <div class="input0">
                                <?php $input_name = sprintf("d%02d:%04d:plans", $date, $_SESSION['employee_id']);?>

                                <?php $name_val = array("work", "出勤");?>
                                <?php $input_id = sprintf("d%02d:%04d:%s", $date, $_SESSION['employee_id'], $name_val[0]);?>
                                <input type="radio" name="<?= $input_name;?>" value="<?= $name_val[0];?>"  id="<?= $input_id; ?>" checked="">
                                <label class="work <?= $in[$input_name] == $name_val[0] ? 'culent' : '' ;?>" for="<?= $input_id; ?>"><?= $name_val[1];?></label>

                                <?php $name_val = array("holiday", "公休");?>
                                <?php $input_id = sprintf("d%02d:%04d:%s", $date, $_SESSION['employee_id'], $name_val[0]);?>
                                <input type="radio" name="<?= $input_name;?>" value="<?= $name_val[0];?>"  id="<?= $input_id; ?>" checked="">
                                <label class="holiday <?= $in[$input_name] == $name_val[0] ? 'culent' : '' ;?>" for="<?= $input_id; ?>"><?= $name_val[1];?></label>

                                <?php $name_val = array("paid", "有給");?>
                                <?php $input_id = sprintf("d%02d:%04d:%s", $date, $_SESSION['employee_id'], $name_val[0]);?>
                                <input type="radio" name="<?= $input_name;?>" value="<?= $name_val[0];?>"  id="<?= $input_id; ?>" checked="">
                                <label class="paid <?= $in[$input_name] == $name_val[0] ? 'culent' : '' ;?>" for="<?= $input_id; ?>"><?= $name_val[1];?></label>
                            </div> -->

                            <div class="input1">
                                <?php $name_key = "in1";?>
                                <?php $input_name = sprintf("d%02d:%04d:%s", $date, $_SESSION['employee_id'], $name_key);?>
                                <select name="<?= $input_name;?>">
                                    <?= create_option_erements($in[$input_name])?>
                                </select>
                                -
                                <?php $name_key = "out1";?>
                                <?php $input_name = sprintf("d%02d:%04d:%s", $date, $_SESSION['employee_id'], $name_key);?>
                                <select name="<?= $input_name;?>">
                                    <?= create_option_erements($in[$input_name])?>
                                </select>
                            </div>

                            <div class="input2">
                                <?php $name_key = "in2";?>
                                <?php $input_name = sprintf("d%02d:%04d:%s", $date, $_SESSION['employee_id'], $name_key);?>
                                <select name="<?= $input_name;?>">
                                    <?= create_option_erements($in[$input_name])?>
                                </select>
                                -
                                <?php $name_key = "0ut2";?>
                                <?php $input_name = sprintf("d%02d:%04d:%s", $date, $_SESSION['employee_id'], $name_key);?>
                                <select name="<?= $input_name;?>">
                                    <?= create_option_erements($in[$input_name])?>
                                </select>
                            </div>
                        </div>

                        <?else:?>
                        <div class="input_box"></div>

                        <?php endif;?>
                    </td>
                    <?php endforeach;?>

                </tr>
                <?php endforeach;?>

            </table>

            <!-- 2-2 -->
            <div class="comment_box">
                <h4>コメント</h4>
                <?php $input_name = sprintf("comment:%04d", $_SESSION['employee_id']);;?>
                <textarea class="comment_text" name="<?=$input_name;?>"><?= empty($in[$input_name]) ? "" : $in[$input_name] ;?></textarea>
            </div>

            <!-- 3.送信ボタンコンテナー ------------>
            <div class="submit_container">
                <div id="open" class="btn">提出</div>
            </div>



        </form>
        </div>



    </div>

</main> 
<script src="../common/js/schedule.js"></script>

<!-- ポップアップ -->
<script>
    function popupSubmit(){
      const popup0 = document.getElementById('popup0');
      popup0.classList.add('show');
      window.setTimeout(hiddenpMsg, 5000);
      function hiddenpMsg(){
      popup0.classList.remove('show');
      }
    }
</script>
<?=$popup;?>

</body>
</html>