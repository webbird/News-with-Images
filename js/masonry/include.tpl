<script src="<?php echo WB_URL ?>/modules/news_img/js/masonry/masonry.min.js"></script>
<script src="<?php echo WB_URL ?>/modules/news_img/js/masonry/imagesLoaded.min.js"></script>

<script>
    $.include(WB_URL+"/modules/news_img/js/masonry/masonry.css");
    var $grid = $('.grid').imagesLoaded( function() {
      $grid.masonry({
        itemSelector: '.grid-item',
        percentPosition: true,
        columnWidth: '.grid-sizer'
      });
    });
</script>