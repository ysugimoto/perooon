document.addEventListener('DOMContentLoaded', function() {

  var a = document.querySelectorAll('a'),
      l = a.length,
      i = 0, 
     // w = document.querySelectorAll('#mainperooon span')[1],
      svg = document.getElementById('perooon_svg'),
      current = 0;
  
  for ( ; i < l; ++i ) {
    a[i].addEventListener('click', function(evt) {
      evt.preventDefault();
      if ( evt.currentTarget.id === 'regulary_perooon' ) {
        return alert('このぺろーんはまだ準備中です。');
      }
      if ( evt.currentTarget.id === 'perooon_github' ) {
        window.open(evt.currentTarget.href, 'perooon');
      } else {
        window.open(evt.currentTarget.href, 'perooon', 'width=600,height=500');
      }
    }, false);
  }
  
  calculateSVG();
  

  // function wink() {
    // w.className = 'wink';
  // }
// 
  // w.addEventListener('webkitAnimationEnd', function() {
      // w.className = '';
      // setTimeout(wink, 8000);
  // }, false);
  
  
  function calculateSVG() {
    var x = window.innerWidth / 1000;
    
    if ( current !== x ) {
      if ( x > 1 ) {
        x = 1.0;
      }
        
      svg.style.webkitTransform = 'scale(' + x + ', 1.0)';
      svg.style.moztTransform = 'scale(' + x + ', 1.0)';
      svg.style.otTransform = 'scale(' + x + ', 1.0)';
      svg.style.msTransform = 'scale(' + x + ', 1.0)';
      svg.style.transform = 'scale(' + x + ', 1.0)';
      current = x;
    }
        
    
    webkitRequestAnimationFrame(calculateSVG);
  }

  //setTimeout(wink, 3000);
  //wink();
}, false);
