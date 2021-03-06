<?php
#===========================================================
# 使いまわしパーツファイル
#===========================================================
// 注意！：このファイルを読み込む前に、function.phpファイルの読込んだファイルを読込むこと。
// include('../common/app/function.php'); //function.phpファイルの読み込み
// include('../common/parts/parts.php'); //parts.phpファイルの読み込み

#===========================================================
# header要素
// linkをheadにいれること
# <link rel="stylesheet" href="../common/css/header.css"><!--header用css-->
#-----------------------------------------------------------

# 名前情報
$employee_name = (!empty($_SESSION["employee_name"])) 
? $_SESSION["employee_name"] : "";

# body情報
$login_page = "../login/login.php";
$body_element =<<<_element_
<body onload="location.href = '{$login_page}'" > 
_element_;

$body = (!empty($_SESSION["employee_name"])) 
 ? "<body>" : $body_element;

# トップページ
$top = "../top/index.php";

$link =[];
$link[0] = array("text"=>"", "href"=>"../top/index.php",);
$link[1] = array("text"=>"勤怠管理", "href"=>"../time_card/select_record.php",);

 # header本文-----------------------------------------------------------
$header = <<<_header_
{$body}
<header class="">
    <div class="header_container">
        <h1 class="rogo"><a href="{$link[0]["href"]}">ロゴ</a></h1>
        <ul class="nav_menu">
            <li class="menu1"><a href="{$link[1]["href"]}">{$link[1]["text"]}</a></li>
            <li class="menu2"><a href="#">作成中<a></li>
            <li class="menu3"><a href="#">作成中<a></li>
            <li class="menu4"><a href="#">作成中<a></li>
            <li><button onclick = "window.open('../time_card/time_card.php','time_card','width=400px height=550px')">タイムカード</button></li>
        </ul>


        <div class="login_user">
            <div class="photo"></div>
            <div class="name">{$employee_name} さん</div>
            <button class="btn_flat" type="button" onclick="location.href=`../login/login.php`">ログアウト</button>
        </div>
    </div>
</header>

_header_;

?>
