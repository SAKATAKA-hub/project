'use strict';  

{
  //タブメニュー
  const selectWeekBtns = document.querySelectorAll('.selectWeeks button');
  const weekTables = document.querySelectorAll('.table_container table');

  //タブメニュー
  selectWeekBtns.forEach(clickedItem => {
    clickedItem.addEventListener('click', () => {
        selectWeekBtns.forEach(item => {
            item.classList.remove('active');
        });
        clickedItem.classList.add('active');

        weekTables.forEach(weekTable => {
            weekTable.classList.remove('active');
        });
        document.getElementById(clickedItem.dataset.id).classList.add('active');
    });
  });

}
{
  //モーダルの表示
  const open = document.getElementById('open');
  const close = document.getElementById('close');
  const modal = document.getElementById('modal');
  const mask = document.getElementById('mask');

  open.addEventListener('click', () => {
    modal.classList.remove('hidden');
    mask.classList.remove('hidden');
  });

  close.addEventListener('click', () => {
    modal.classList.add('hidden');
    mask.classList.add('hidden');
  });

  mask.addEventListener('click', () => {
    close.click();
  });
  
}
