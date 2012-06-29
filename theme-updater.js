(function($) {
	$(document).ready(function(){
		$('.theme-updater h3.toggle-button').live('click', function() {
			var div_class = '.theme-updater-' + $(this).attr('rel');
			$(div_class).slideToggle(400);
		});

		$('#theme_updater_dashboard_widget_theme a.close').live('click', function() {
			var data = { action: 'theme_updater_notice_theme', security: themeupdaterAjax.theme_nonce };
			$.post(themeupdaterAjax.ajaxurl, data, function(response) {
				$('#theme_updater_theme').fadeOut(400);
				$('#theme_updater_dashboard_widget_theme').fadeOut(400);
			});
			return false;
		});
	});
})(jQuery);