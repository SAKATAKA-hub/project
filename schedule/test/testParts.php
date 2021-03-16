<select name="" id="">
    <?php for ($i=0; $i < 10; $i++) :?>
    <option value="<?= $i;?>"><?= $i;?></option>
    <?php endfor;?>
</select>


<?php
function pats(){
    $text = <<<_text_
    _text_;
    echo $text;
}
