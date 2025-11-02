(function($){
  $(function(){
    if (typeof pagenow === 'undefined' || pagenow !== 'product_page_imtowoopro-images-products') return;

    var $wrap = $('.wrap').first();
    if (!$wrap.length) return;
    $wrap.addClass('imtowoo-wrap');

    // Find rows container by locating first .itpwc-row and its parent
    var $firstRow = $wrap.find('.itpwc-row').first();
    if ($firstRow.length){
      var $rowsContainer = $firstRow.parent();
      // Prepend decorative blue header bar just before rows
      if (!$rowsContainer.prev().is('.imtw-header')){
        var header = [
          '<div class="imtw-header">',
          '  <div>Title</div>',
          '  <div>Price</div>',
          '  <div>Visibility</div>',
          '  <div>SKU</div>',
          '</div>'
        ].join('');
        $rowsContainer.before(header);
      }
      // Add skin class ONLY to our rows
      $wrap.find('.itpwc-row').addClass('imtowoo-row-skin');
      // Add check icon to any element that looks like SKU block (non-destructive)
      $wrap.find('.itpwc-row .sku, .itpwc-row [name*=\"sku\"], .itpwc-row .itpwc-sku').each(function(){
        var $blk = $(this).closest('div, td, span');
        if ($blk.find('svg.imtowoo-check').length) return;
        $blk.append('<svg class=\"imtowoo-check\" viewBox=\"0 0 24 24\" aria-hidden=\"true\"><path d=\"M20.3 5.7a1 1 0 0 1 0 1.4l-9.6 9.6a1 1 0 0 1-1.4 0L3.7 11a1 1 0 1 1 1.4-1.4l4.2 4.2L18.9 5.7a1 1 0 0 1 1.4 0z\"/></svg>');
      });
    }

    // Turn main container into a card without wrapping Settings markup
    if (!$wrap.find('.imtowoo-card').length){
      var $card = $('<div class=\"imtowoo-card\"></div>');
      // Move header bar + rows into the card if present
      var $header = $wrap.find('.imtw-header').first();
      if ($header.length){
        $header.add($rowsContainer).wrapAll($card);
      } else {
        // If no rows found, don't alter layout
      }
    }

    // Style primary action button
    $('#imtowoo-make-products, #itpwc-make-products, .make-products').addClass('imtowoo-primary');
  });
})(jQuery);
