$(document).ready(function(){
	// submit du formulaire
	$('.decliphotos_valider').click(function() {
		$(this).parents('form:eq(0)').submit();
		return false;
	});
	
	
	// accordion
	$('.accordion-wrapper-decliphotos').each(function() {
		var $wrapper = $(this);
		var $accordionItems = $wrapper.find('.accordion-item');
		$accordionItems.each(function() {
			var $accordionItem = $(this);
			var $accordionToggle = $accordionItem.find('.accordion-toggle');
			var $accordionDeroulant = $accordionItem.find('.accordion-deroulant');

			$accordionDeroulant.hide();
			$accordionToggle.click(function() {
			
				// s'il n'y a pas de sous-menu, on ne traite pas le clic
				if($accordionDeroulant.length == 0) return true;
			
				if($accordionDeroulant.is(':hidden')) {
					$accordionItems.not($accordionItem).removeClass('opened').find('.accordion-deroulant').stop().slideUp('fast');
					$accordionDeroulant.slideDown('fast');
					$accordionItem.addClass('opened');
				} else {
					$accordionDeroulant.stop().slideUp('fast');
					$accordionItem.removeClass('opened');
				}
				return false;
			});
			
			// sous-menu ouvert au chargement de la page ?
			if($(this).is('.current')) $accordionToggle.click();
		});
	});
});