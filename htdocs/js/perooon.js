document.addEventListener('DOMContentLoaded', function() {

  var a = document.querySelectorAll('.box_perooon a'),
      l = a.length,
      i = 0, 
      w = document.querySelectorAll('#mainperooon span')[1];
  
  for ( ; i < l; ++i ) {
    a[i].addEventListener('click', function(evt) {
      evt.preventDefault();
      if ( evt.currentTarget.id === 'regulary_perooon' ) {
        return alert('このぺろーんはまだ準備中です。');
      }
      window.open(evt.currentTarget.href, 'perooon', 'width=600,height=500');
    }, false);
  }

  function wink() {
    w.className = 'wink';
  }

  w.addEventListener('webkitAnimationEnd', function() {
      w.className = '';
      setTimeout(wink, 8000);
  }, false);

  //setTimeout(wink, 3000);
  wink();
}, false);
