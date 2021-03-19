<?php
# 基本設定
//ファイルの読み込み
//[読込順]function.php > schedule_function.php > create_schedule_program.php
//  > create_schedule.php(現在地)
include('create_schedule_program.php');

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
<header></header>
<main>
    <!-- ******** <m_side> ******** ******** ******** -->

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
                    <input type="hidden" name="calendar_week" value="<?=$in["calendar_week"];?>">
                    <button class="befor">前へ</button>    
                </form>
    
                <h4 class="select_mon"><?= $display["m_index"]; ?></h4>    
    
                <form action="" method="GET">
                    <input type="hidden" name="mode" value="change_next">
                    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
                    <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                    <input type="hidden" name="calendar_week" value="<?=$in["calendar_week"];?>">
                    <button class="next">次へ</button>
                </form>
    
            </div>

            <div class="selectWeeks">
                <?php foreach ($calendar_weeks as $key => $c_week):?>
                <form action="" method="GET">
                    <input type="hidden" name="mode" value="change_week">
                    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
                    <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                    <input type="hidden" name="calendar_week" value="<?=$key;?>">
                    <button class="<?= $key == $in["calendar_week"] ? "active" : "" ;?>"><?=$key+1;?></button>
                </form>
                <?php endforeach;?>
            </div>

            <div class="select_input_menu">     
                <button class="">印刷ページ</button>        
            </div>
    

        </div>

        <!-- 2.インプットエリア ------------>
        <div class="table_container create">
        <form action="" method="GET">
            <input type="hidden" name="mode" value="submit">
            <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
            <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
            <input type="hidden" name="calendar_week" value="<?=$in["calendar_week"];?>">

            <?php for ($i=0; $i <2 ; $i++):?>
            <h3><?= $i<1 ? sprintf("%s 前期",$display["m_index"]) : sprintf("%s 後期",$display["m_index"]) ;?></h3>
            <table class="shift_table">
                <!-- 日付 -->
                <tr>
                    <th>STAFF NAME</th>

                    <?php foreach ($calendar_weeks as $key => $c_week):?>
                    <?php foreach ($c_week as $c_day):?>
                    <?php if( ($c_day["this_month"])&&($c_day["date"] > $i*15) && ($c_day["date"] <= ($i+1)*16-1) ):?>
                        <th>
                            <div class="date_box  <?= $c_day["week"]== 7 ? sun_color : $c_day["week"]== 6 ? sat_color :"" ;?>" > 
                                <?= $c_day["date"]?>
                            </div>
                            <div class="date_box  <?= $c_day["week"]== 7 ? sun_color : $c_day["week"]== 6 ? sat_color :"" ;?>" > 
                                <?= $c_day["week_text"]?>
                            </div>

                        </th>
                    <?php endif;?>
                    <?php endforeach;?>
                    <?php endforeach;?>

                    <th>TOTAL</th>
                </tr>

                <!-- 勤務時間 -->
                <?php foreach ($employees as $key => $employee):?>
                <tr>
                    <td class="employee_data">
                        <div class="id"><?=$employee["id"];?></div>
                        <div class="name"><?=$employee["name"];?></div>
                    </td>

                    <?php foreach ($calendar_weeks as $key => $c_week):?>
                    <?php foreach ($c_week as $c_day):?>
                    <?php if( ($c_day["this_month"])&&($c_day["date"] > $i*15) && ($c_day["date"] <= ($i+1)*16-1) ):?>
                    <td>

                    </td>
                    <?php endif;?>
                    <?php endforeach;?>
                    <?php endforeach;?>

                    <td>
                        <div>180:00</div>
                        <div>公休 10</div>
                    </td>

                </tr>
                <?php endforeach;?>

                <!-- 労働時間 -->
                <tr>
                    <td>労働時間合計</td>

                    <?php foreach ($calendar_weeks as $key => $c_week):?>
                    <?php foreach ($c_week as $c_day):?>
                    <?php if( ($c_day["this_month"])&&($c_day["date"] > $i*15) && ($c_day["date"] <= ($i+1)*16-1) ):?>
                    <td>

                    </td>
                    <?php endif;?>
                    <?php endforeach;?>
                    <?php endforeach;?>

                    <td>
                        <div>180:00</div>
                        <div>公休 10</div>
                    </td>

                </tr>



            </table>
            <?php endfor;?>
        </form>
        </div>


    </div>

</main> 
<script src="../common/js/schedule.js"></script>

</body>
</html>






