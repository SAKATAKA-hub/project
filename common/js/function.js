"use strict"
{
console.log("読み込みＯＫ");
/******************************************************/
//月指定のセレクトボックスに、日付のセレクトボックスの内容を合せる関数
/******************************************************/
function changeSelectDate(){
    const selectElement = document.querySelectorAll('select');
    const val = selectElement[0].value;//選択された年月の"値"
    // console.log(val+"-01");
    var selectDate = new Date(val+"-01");

    selectDate.setMonth(selectDate.getMonth() + 1 );
    selectDate.setDate(selectDate.getDate()-1);
    const lastDate = selectDate.getDate(); //月末日

    //日付選択'option'要素の削除
    for (let i = 0; i < selectElement[1].length; i+1) {
        selectElement[1].children[i].remove();
    }
    
    console.log(selectElement[1]);

    for (let i = 1; i <= lastDate; i++) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = i+"日";
        selectElement[1].appendChild(option);
        // console.log(option);
    }
}








}