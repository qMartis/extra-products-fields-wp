(function($) {
  'use strict';
  $(document).ready(function($) {
    // Remove ":" character of yayextra option edit link
    const yayeOptionEditLinks = $('.yayextra-option-edit-link');
    if (yayeOptionEditLinks.length > 0) {
      $.each(yayeOptionEditLinks, function(_, el) {
        $(el)
          .get(0)
          .nextSibling.remove();
      });
    }
  });
})(jQuery);
