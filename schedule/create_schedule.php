<?php
#===========================================================
# スケジュール作成ページ (create_schedule.php)
#===========================================================
# 基本設定
//ファイルの読み込み
//[読込順]function.php > schedule_function.php > create_schedule_program.php
//  > create_schedule.php(現在地)
include('create_schedule_program.php');

//parts.phpファイルの読み込み
include('../common/parts/parts.php'); 


# ポップアップ
$popup ="";
if(isset($in["mode"])&&($in["mode"]=="submit")){ 
    $popup = "<script>window.addEventListener('load', popupSubmit());</script>";
}  

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>スケジュール管理 スケジュール作成</title>
  <link rel="stylesheet" href="../common/css/create_schedule.css">
  <link rel="stylesheet" href="../common/css/header.css">
</head>

<!-- ヘッダーの読み込み
<header></header>
<body>            -->
<?= get_header(2);?>

<main>
    <!-- ******** <m_side> ******** ******** ******** -->
    <div class="m_side">
    <?= side_menu_list("create_schedule");?>
    </div>
    <!-- ******** <m_center> ******** ******** ******** -->
    <div class="m_center">

        <!-- パンくずリスト -->
        <div class="bread_crumb">
        <ul>
            <li><a href="">HOME</a></li>
            <li>スケジュール管理</li>
            <li>スケジュール作成</li>
        </ul>
        </div>
    
        <h2>スケジュール作成</h2>

        <!-- 1.セレクトエリア ------------>
        <div class="select_container">
            <div class="select_month">
    
                <form action="" method="GET">
                    <input type="hidden" name="mode" value="change_befor">
                    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
                    <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                    <input type="hidden" name="calendar_week" value="0">
                    <button class="befor">前へ</button>    
                </form>
    
                <h4 class="select_mon"><?= $display["m_index"]; ?></h4>    
    
                <form action="" method="GET">
                    <input type="hidden" name="mode" value="change_next">
                    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
                    <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                    <input type="hidden" name="calendar_week" value="0">
                    <button class="next">次へ</button>
                </form>
    
            </div>

            <div class="selectWeeks">
                <?php foreach ($calendar_weeks as $w => $c_week):?>
                <form action="" method="GET">
                    <input type="hidden" name="mode" value="change_week">
                    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
                    <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                    <input type="hidden" name="calendar_week" value="<?=$w;?>">
                    <button class="<?= $w == $in["calendar_week"] ? "active" : "" ;?>"><?=$w+1;?></button>
                </form>
                <?php endforeach;?>
            </div>

            <div class="select_input_menu">
                    
                <form action="" method="GET">
                    <input type="hidden" name="mode" value="read_send_schedule">
                    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
                    <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                    <input type="hidden" name="calendar_week" value="<?=$in["calendar_week"];?>">
                    <button class="">提出スケジュール取得</button>
                </form>
        
            </div>
    

        </div>

        <!-- 2.インプットエリア ------------>
        <div class="table_container create">
        <form action="" method="GET">
            <input type="hidden" name="mode" value="submit">
            <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
            <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
            <input type="hidden" name="calendar_week" value="<?=$in["calendar_week"];?>">


            <!-- モーダル -->
            <section id="modal" class="hidden">
                <p>スケジュールを保存します。</p>
                <p>よろしいですか？</p>
                <div class="m_btn_container">
                <button type="submit" class="btn" >保存</button>
                <div id="close" class="btn">戻る</div>
                </div>   
            </section>

            <!-- マスク -->
            <div id="mask" class="hidden"></div>

            <!-- ポップアップ -->
            <div id="popup0" class="popup"><?=$display["m_index"];?>分のスケジュールを保存しました。</div>


            <!-- 2-1 -->
            <p>※【休憩基準　勤務時間:休憩時間】4.5時間以上:30分、 6時間以上:60分、 8.5時間以上:90分、 10時間以上:120分</p>
            <?php foreach ($calendar_weeks as $w => $c_week):?>
            <?php if($w == $in["calendar_week"]):?>
            <table class="shift_table active">
                <!-- 日付 -->
                <tr>
                    <th>STAFF NAME</th>

                    <?php foreach ($c_week as $c_day):?>
                    <th class="<?= $c_day["week_class"];?>">
                        <?= sprintf("%02d日 %s", $c_day["date"], $c_day["week_text"]) ?>
                    </th>
                    <?php endforeach;?>

                    <th>WEEK TOTAL</th>
                    <th>MON TOTAL</th>
                </tr>

                <!-- 勤務時間 -->
                <?php foreach ($employees as $employee):?>
                <tr>
                    <td class="employee_data">
                        <div class="id"><?=$employee["id"];?></div>
                        <div class="name"><?=$employee["name"];?></div>
                    </td>

                    <?php foreach ($c_week as $c_day):?>
                    <td class="work_time_data">

                    <?php if($c_day ["this_month"]):?>
                    <div class="input_box">
                        <div class="input1">
                            <?php $name_key = "in1";?>
                            <?php $input_name = sprintf("d%02d:%04d:%s", $c_day["date"], $employee["id"], $name_key);?>
                            <select name="<?= $input_name;?>">
                                <?= create_option_erements($in[$input_name])?>
                            </select>
                            -
                            <?php $name_key = "out1";?>
                            <?php $input_name = sprintf("d%02d:%04d:%s", $c_day["date"], $employee["id"], $name_key);?>
                            <select name="<?= $input_name;?>">
                                <?= create_option_erements($in[$input_name])?>
                            </select>
                        </div>
                        <div class="input2">
                        <?php $name_key = "in2";?>
                            <?php $input_name = sprintf("d%02d:%04d:%s", $c_day["date"], $employee["id"], $name_key);?>
                            <select name="<?= $input_name;?>">
                                <?= create_option_erements($in[$input_name])?>
                            </select>
                            -
                            <?php $name_key = "out2";?>
                            <?php $input_name = sprintf("d%02d:%04d:%s", $c_day["date"], $employee["id"], $name_key);?>
                            <select name="<?= $input_name;?>">
                                <?= create_option_erements($in[$input_name])?>
                            </select>
                        </div>
                    </div>
                    <?php endif;?>

                    </td>
                    <?php endforeach;?>

                    <td>
                        <div><?= $calcutation->getPrivateWeekWork($employee["id"],$w)?></div>
                        <div>公休<?= $calcutation->getPrivateWeekHolyday($employee["id"],$w)?>日</div>
                    </td>
                    <td>
                        <div><?= $calcutation->getPrivateMonthWork($employee["id"])?></div>
                        <div>公休<?= $calcutation->getPrivateMonthHolyday($employee["id"])?>日</div>
                    </td>

                </tr>
                <?php endforeach;?>

                <!-- 労働時間 -->
                <tr>
                    <td class="total">総労働時間</td>

                    <?php foreach ($c_week as $c_day):?>
                    <td>
                        <?php if($c_day ["this_month"]):?>
                        <?= $calcutation->getTotalDay($c_day["date"]);?>
                        <?php endif;?>
                    </td>
                    <?php endforeach;?>

                    <td><?= $calcutation->getTotalWeek($w)?></td>
                    <td><?= $calcutation->getTotalMonth();?></td>
                

                </tr>

                
            </table>
            <?php endif;?>
            <?php endforeach;?>

            <!-- 2-2 -->
            <div class="all_comment_box">
                <h4>コメント</h4>
                <div class="coments">
                    <?php foreach ($employees as $employee):?>
                    <?php $input_name = sprintf("comment:%04d", $employee["id"]);;?>
                    <input type="hidden" name="<?=$input_name;?>" value="<?= empty($in[$input_name]) ? "" : $in[$input_name] ;?>">
                    <div class="coment">
                        <p><?= $employee["name"];?></p>
                        <p><?= empty($in[$input_name]) ? "＊コメントはありません。" : $in[$input_name] ;?></p>
                    </div>
                <?php endforeach;?>
                </div>
            </div>

            <!-- 3.送信ボタンコンテナー ------------>
            <div class="submit_container">
                <div id="open" class="btn">保存</div>
            </div>



        </form>
        </div>


    </div>

</main> 
<script src="../common/js/schedule.js"></script>


<script>
    //<!-- ポップアップ -->
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

