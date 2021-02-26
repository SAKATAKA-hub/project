<?php
include('../common/app/function.php');//function.phpファイルの読み込み
include('../common/parts/parts.php');

// ※ cssスタイル編集用
$file = "test.html";//テンプレートファイル
file_put_contents($file,$header);
?>

<link rel="stylesheet" href="../common/css/header.css"><!--header用css-->
<?= $header; ?>
</body>