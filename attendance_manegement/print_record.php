<?php
require_once('../common/TCPDF/tcpdf.php');

$tcpdf = new TCPDF("P", "mm", "A4", true, "UTF-8" );
$tcpdf->setPrintHeader(false);
$tcpdf->setPrintFooter(false);
$tcpdf->AddPage(); // 新しいpdfページを追加
$tcpdf->SetFont("kozgopromedium", "", 10); // 小塚ゴシックを利用

$html = file_get_contents("tmpl/attendance_list_pdf.tmpl"); 
$css = <<<_CSS_
<style>
th{
    text-align: center;
    line-height: 26px;
    border-top:solid 1px #000;
    border-bottom:solid 1px #000;
}
td{
    text-align: center;
    line-height: 26px;
}
.rec_top>td{
    border-top: solid 1px #000;  //レコードのボーダー（上） 
}
tr:last-child>td{
    border-bottom: solid 1px #000;  //レコードのボーダー（下）
}
.ditails{ display: none;}
</style>

_CSS_;


$html .= $css;

$tcpdf->writeHTML($html);
ob_end_clean();
$tcpdf->Output('sample.pdf', 'I');

?>