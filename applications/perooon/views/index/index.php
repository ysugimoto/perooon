<!doctype html>
<html lang="ja">
  <head>
    <meta charset="UTF-8">
    <title>Perooon</title>
    <link rel="stylesheet" type="text/css" href="<?php echo base_link('css/perooon.css');?>">
    <script src="<?php echo base_link('js/perooon.js');?>"></script>
    <script type="text/javascript">

      var _gaq = _gaq || [];
        _gaq.push(['_setAccount',  'UA-36816571-1']);
          _gaq.push(['_trackPageview']);

            (function() {
                    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga,  s);
                              })();

                              </script>
  </head>
  <body>
   <div id="mainperooon">
     (๑<span>╹</span>ڡ<span>╹</span>๑)
   </div>
   <div class="perooon_count">現在<span><?php echo $count->times;?></span>ぺろーん</div>
   <div class="col_perooon">
     <div class="box_perooon">
       <a id="regist_perooon" href="<?php echo page_link('index/perooon');?>"><span>今すぐぺろーんする</span></a>
     </div>
     <div class="box_perooon">
       <a id="regulary_perooon" href="<?php echo page_link('index/perooon');?>"><span>定期的にぺろーんする</span></a>
     </div>
   </div>
  </body>
</html>
