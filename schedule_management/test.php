<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>テスト</title>
  <link rel="stylesheet" href="../common/css/time_card.css">
</head>
<body>
    <main id="">
        <div id="textbox">
            <?php $p1 ="PHP変数の代入";?>
            <p id="p1" class="text_cat"><?= $p1;?></p>
            <p id="p2" class="text_cat">テキスト2</p>
            <p id="p3" class="text_cat">テキスト3</p>
        </div>
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
