<?php
include('../common/app/function.php');//function.phpファイルの読み込み
include('../common/parts/parts.php'); //使いまわしパーツの読み込み

$file = "tmpl/schedule_text.tmpl";
$text = file_get_contents($file);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>業務管理アプリ</title>
    <link rel="stylesheet" href="../common/css/style.css">
    <link rel="stylesheet" href="../common/css/header.css"><!--header用css-->
</head>

<!-- ヘッダーの読み込み -->
<?= get_header(0);?>

<!-- 表示テキストの読み込み -->
<?= $text;?>

</body>


