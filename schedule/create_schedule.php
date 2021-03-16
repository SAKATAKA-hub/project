<?php
# 基本設定
//ファイルの読み込み
//[読込順]function.php > schedule_function.php > send_schedule_program.php
//  > send_schedule.php(現在地)
include('send_schedule_program.php');

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
                    <button class="befor">前へ</button>    
                </form>
    
    
                <h4 class="select_mon">2021年02月</h4>
    
    
                <form action="" method="GET">
                    <input type="hidden" name="mode" value="change_next">
                    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
                    <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                    <button class="next">次へ</button>
                </form>
    
            </div>

            <div class="select_input_menu">
                    
                <form action="" method="GET">
                    <input type="hidden" name="mode" value="">
                    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
                    <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                    <button class="">提出スケ取得</button>
                </form>
    
                <form action="" method="GET">
                    <input type="hidden" name="mode" value="">
                    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
                    <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                    <button class="">一時保存</button>
                </form>

                <form action="" method="GET">
                    <input type="hidden" name="mode" value="">
                    <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
                    <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">
                    <button class="" id="open">確定保存</button>
                </form>

            </div>

            <div class="selectWeeks">
                <?php for($w=1; $w <= 5; $w++):?>
                <button class="<?= $w == 1 ? "active" : "" ;?>" data-id="week<?=sprintf("%02d",$w);?>"><?=$w;?></button>
                <?php endfor;?>
            </div>

        </div>

        <!-- 2.インプットエリア ------------>
        <div class="table_container create">
        <form action="" method="GET">
            <input type="hidden" name="mode" value="submit">
            <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>"> 
            <input type="hidden" name="month" value="<?= $display["m_text"]; ?>">

            <!-- モーダル -->
            <section id="modal" class="hidden">
                <p>スケジュールを確定します。</p>
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
            <?php for($w=1; $w <= 5; $w++):?>
            <table class="shift_table <?= $w == 1 ? "active" : "" ;?>" id="week<?=sprintf("%02d",$w);?>">
                <tr>
                    <th>STAFF NAME</th>
                    <th><?=sprintf("%02d",($w-1)*7+1);?>日 MON</th>
                    <th><?=sprintf("%02d",($w-1)*7+2);?>日 TUE</th>
                    <th><?=sprintf("%02d",($w-1)*7+3);?>日 WED</th>
                    <th><?=sprintf("%02d",($w-1)*7+4);?>日 THU</th>
                    <th><?=sprintf("%02d",($w-1)*7+5);?>日 FRI</th>
                    <th class="sat_color"><?=sprintf("%02d",($w-1)*7+6);?>日 SAT</th>
                    <th class="sun_color"><?=sprintf("%02d",($w-1)*7+7);?>日 SUN</th>
                    <th>WEEK TOTAL</th>
                    <th>MON TOTAL</th>
                </tr>

                <?php for($m=0; $m < 7; $m++):?>
                <tr>
                    <td>
                    <div class="employee_data">
                    </div>
                        <div class="id">0001</div>
                        <div class="name">山田　金太郎</div>
                    </td>

                    <?php for($i=0; $i < 7; $i++):?>
                    <td>
                    <div class="input_box">
                        <div class="input1">
                            <select name="" id=""></select>
                            -
                            <select name="" id=""></select>
                        </div>
                        <div class="input2">
                            <select name="" id=""></select>
                            -
                            <select name="" id=""></select>
                        </div>
                    </div>
                    </td>
                    <?php endfor;?>

                    <td>
                        <div>40:00</div>
                        <div>公休 2</div>
                    </td>
                    <td>
                        <div>180:00</div>
                        <div>公休 10</div>
                    </td>



                </tr>
                <?php endfor;?>
                
            </table>
            <?php endfor;?>

            <!-- 2-2 -->
            <div class="comment_box">
                <p>コメント</p>
                <textarea class="comment_text" name="comment" id="" ><?= empty($in["comment"]) ? "" : $in["comment"] ;?></textarea>
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

