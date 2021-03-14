<?php
# プログラムファイルの読み込み
include('send_schedule_program.php');

normal_display_date();
$calendar_weeks = get_calendar($display);

// foreach ($calendar_weeks as $key => $value) {
//     var_dump($value);
//     echo"<br><br>";
// }

$employee_id = "";

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>スケジュール管理 スケジュール作成</title>
  <link rel="stylesheet" href="../common/css/send_schedule.css">
  <link rel="stylesheet" href="../common/css/header.css">
</head>
<body>
<header></header>
<main>
    <!-- ******** <m_side> ******** ******** ******** -->
    <div class="m_side"></div>

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

        <div class="select_container">
                <div class="select_month">
    
                    <form action="" method="GET">
                        <input type="hidden" name="mode" value="change_befor">
                        <!-- <input type="hidden" name="token" value="">  -->
                        <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                        <button class="befor">前へ</button>    
                    </form>
    
    
                    <h4 class="select_mon"><?= $display["m_index"]; ?></h4>
    
    
                    <form action="" method="GET">
                        <input type="hidden" name="mode" value="change_next">
                        <!-- <input type="hidden" name="token" value="">  -->
                        <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                        <button class="next">次へ</button>
                    </form>
    
    
                </div>
                <div class="select_input_menu">
                    
                    <form action="" method="GET">
                        <input type="hidden" name="mode" value="fixed_input">
                        <!-- <input type="hidden" name="token" value="">  -->
                        <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                        <button class="auto_input">定型入力</button>
                    </form>
    
                    <form action="" method="GET">
                        <input type="hidden" name="mode" value="input_reset">
                        <!-- <input type="hidden" name="token" value="">  -->
                        <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                        <button class="delete">リセット</button>
                    </form>
        
                </div>
        </div>

        <div class="table_container">
        <form action="" method="GET">
            <table class="shift_table">

                <tr>
                    <td>MON</td>
                    <td>TUE</td>
                    <td>WED</td>
                    <td>THU</td>
                    <td>FRI</td>
                    <td class="sat_color">SAT</td>
                    <td class="sun_color">SUN</td>
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
                            <div class="input1">
                                <select name="<?= "{$date}[{$employee_id}]['in1']"; ?>">
                                    <?= create_option_erements("08:00")?>
                                </select>
                                -
                                <select name="<?= "{$date}[{$employee_id}]['out1']"; ?>">
                                    <?= create_option_erements("17:00")?>
                                </select>        
                            </div>
                            <div class="input2">
                                <select name="<?= "{$date}[{$employee_id}]['in2']"; ?>">
                                    <?= create_option_erements("")?>
                                </select>
                                -
                                <select name="<?= "{$date}[{$employee_id}]['out2']"; ?>">
                                    <?= create_option_erements("")?>
                                </select>        
                            </div>
                            <div class="input0">
                                <input type="radio" name="<?= $date."[".$employee_id."][plans]"; ?>" value="work"  id="<?= $date."[".$employee_id."][work]"; ?>" checked="checked" >
                                <label class="culent" for="<?= $date."[".$employee_id."][work]"; ?>">出勤</label>
                                <input type="radio" name="<?= $date."[".$employee_id."][plans]"; ?>" value="holiday" id="<?= $date."[".$employee_id."][holiday]"; ?>" >
                                <label for="<?= $date."[".$employee_id."][holiday]"; ?>">公休</label>
                                <input type="radio" name="<?= $date."[".$employee_id."][plans]"; ?>" value="paid" id="<?= $date."[".$employee_id."][paid]"; ?>" >
                                <label for="<?= $date."[".$employee_id."][paid]"; ?>">有給</label>
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
        </form>
        </div>



    </div>

</main> 
<script>
    document..getElementsByClassName('input0').addEventListener('click',e =>{
        e.target.classList.toggle('culent');
        console.log('OK');
    });
</script>   
</body>
</html>