<?php
#===========================================================
# スケジュール印刷ページ (print_schedule.php)
#===========================================================
# 基本設定
//ファイルの読み込み
//[読込順]function.php > schedule_function.php > create_schedule_program.php > print_schedule_.php(現在地)
include('create_schedule_program.php');

//parts.phpファイルの読み込み
include('../common/parts/parts.php'); 


?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>スケジュール管理 スケジュール印刷</title>
  <link rel="stylesheet" href="../common/css/create_schedule.css">
  <link rel="stylesheet" href="../common/css/header.css">
</head>
<body >

<!-- ヘッダーの読み込み
<header></header>
<body>            -->
<?= get_header(2);?>

<main>

    <!-- ******** <m_side> ******** ******** ******** -->
    <div class="m_side">
    <?= side_menu_list("print_schedule");?>
    </div>

    <!-- ******** <m_center> ******** ******** ******** -->
    <div class="m_center">

        <!-- パンくずリスト -->
        <div class="bread_crumb">
        <ul>
            <li><a href="">HOME</a></li>
            <li>スケジュール管理</li>
            <li>スケジュール印刷</li>
        </ul>
        </div>
    
        <h2>スケジュール印刷</h2>

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
                <form action="print_schedule_pdf.php" method="GET">
                    <input type="hidden" name="mode" value="print">
                    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
                    <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                    <input type="hidden" name="calendar_week" value="<?=$in["calendar_week"];?>">
                    <button class="">スケジュール印刷</button>
                </form>        
            </div>
    

        </div>

        <!-- 2.インプットエリア ------------>
        <div class="table_container print">

            <!-- 2-1 -->
            <p>※【休憩基準　勤務時間:休憩時間】4.5時間以上:30分、 6時間以上:60分、 8.5時間以上:90分、 10時間以上:120分</p>
            <?php foreach ($calendar_weeks as $w => $c_week):?>
            <?php if($w == $in["calendar_week"]):?>
            <table class="shift_table <?= $w == $in["calendar_week"] ? "active" : "" ;?>" id="<?= sprintf("calendar%02d",$w);?>" >
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
                        <?php $name_key = "1";?>
                        <?php $input_name1 = sprintf("d%02d:%04d:in%s", $c_day["date"], $employee["id"], $name_key);?>
                        <?php $input_name2 = sprintf("d%02d:%04d:out%s", $c_day["date"], $employee["id"], $name_key);?>
                        <div class="input1">
                            <?= isset($in[$input_name1])&&empty(!$in[$input_name1]) ? sprintf("%s-%s" ,$in[$input_name1] ,$in[$input_name2]) :"公休";?>
                        </div>

                        <?php $name_key = "2";?>
                        <?php $input_name1 = sprintf("d%02d:%04d:in%s", $c_day["date"], $employee["id"], $name_key);?>
                        <?php $input_name2 = sprintf("d%02d:%04d:out%s", $c_day["date"], $employee["id"], $name_key);?>
                        <div class="input2">
                            <?= isset($in[$input_name1])&&empty(!$in[$input_name1]) ? sprintf("%s-%s" ,$in[$input_name1] ,$in[$input_name2]) :"";?>
                        </div>
                    </div>
                    <?php endif;?>

                    </td>
                    <?php endforeach;?>

                    <td>
                        <div><?= $calcutation->getPrivateWeekWork($employee["id"],$w);?></div>
                        <div>公休<?= $calcutation->getPrivateWeekHolyday($employee["id"],$w);?>日</div>
                    </td>
                    <td>
                        <div><?= $calcutation->getPrivateMonthWork($employee["id"]);?></div>
                        <div>公休<?= $calcutation->getPrivateMonthHolyday($employee["id"]);?>日</div>
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

                    <td><?= $calcutation->getTotalWeek($w);?></td>
                    <td><?= $calcutation->getTotalMonth();?></td>
                

                </tr>

                
            </table>
            <?php endif;?>
            <?php endforeach;?>

        </div>


    </div>

</main> 
<!-- <script src="../common/js/schedule.js"></script> -->
<script>
'use strict';  
{
  //タブメニュー
  const selectWeekBtns = document.querySelectorAll('.selectWeeks button');
  const weekTables = document.querySelectorAll('.table_container table');


  //タブメニュー
  selectWeekBtns.forEach(clickedItem => {
    clickedItem.addEventListener('click', () => {
        selectWeekBtns.forEach(item => {
            item.classList.remove('active');
        });
        clickedItem.classList.add('active');

        weekTables.forEach(weekTable => {
            weekTable.classList.remove('active');
        });
        document.getElementById(clickedItem.dataset.id).classList.add('active');
    });
  });

}
</script>

</body>
</html>

