(function($){
	$(document).ready(function(){
		var cfg = window.twoSecondAdminBar || {};
		var ajaxurl = cfg.ajaxurl || (window.ajaxurl || '');
		var texts = cfg.texts || {};
		var nonces = cfg.nonces || {};

		// JS/CSS cache purge
		$(document).on('click', '.twosecond-cache-purge-text, #del_js_css_cache', function(){
			$('.in-progress.bsd-flex.delete_css_js_cache').show();
			$('#del_js_css_cache').attr('disabled', true);
			$('#twosecond_cache_purge').show();
			$('.cache_size').addClass('deleting');
			$('.twosecond-cache').text(texts.deleting || 'Deleting...');
			var data_id = $(this).attr('data-id');
			var data_type = $(this).attr('data-type');
			var cache_type = $(this).attr('data-cache');
			var data = {
				action: 'twosecond_cache_purge',
				_twosecond_nonce: nonces.purge_cache || '',
				data_cache: cache_type,
				data_id: data_id,
				data_type: data_type
			};
			$.get(ajaxurl, data, function(response){
				$('#twosecond_cache_purge').hide();
				$('.cache_size').removeClass('deleting');
				$('.twosecond-cache').text(texts.cacheDeleted || 'Cache Deleted!');
				var size = (typeof response === 'object' && response && typeof response.filesize !== 'undefined') ? response.filesize : response;
				$('.cache_folder_size').text(size + ' MB');
				$('#del_js_css_cache').attr('disabled', false);
				$('.in-progress.bsd-flex.delete_css_js_cache').hide();
                setTimeout(function(){
                    $('.twosecond-cache').text(texts.cacheLabel || 'TwoSecond cache');
                }, 2000);
			}).fail(function(){
				$('#twosecond_cache_purge').hide();
				$('.cache_size').removeClass('deleting');
				$('.twosecond-cache').text(texts.tryAgain || 'try again');
				$('#del_js_css_cache').attr('disabled', false);
				$('.in-progress.bsd-flex.delete_css_js_cache').hide();
                setTimeout(function(){
                    $('.twosecond-cache').text(texts.cacheLabel || 'TwoSecond cache');
                }, 2000);
			});
			
		});

		// Critical CSS cache purge
		var criticalLoader = $('.in-progress.bsd-flex.delete_critical_css_cache');
		$(document).on('click', '#del_critical_css_cache,.twosecond-critical-cache-purge-text,.twosecond-critical-cache-purge-single-text', function(){
			criticalLoader.show();
			$('#twosecond_cache_purge').show();
			$('.cache_size').addClass('deleting');
			$('#del_critical_css_cache').attr('disabled', true);
			$('.twosecond-cache').text(texts.deleting || 'Deleting...');
			var data_id = $(this).attr('data-id');
			var data_type = $(this).attr('data-type');
			var data = {
				action: 'twosecond_critical_cache_purge',
				_twosecond_nonce: nonces.purge_critical_css || '',
				data_id: data_id,
				data_type: data_type
			};
			$.get(ajaxurl, data, function(response){
				$('#del_critical_css_cache').attr('disabled', false);
				$('#twosecond_cache_purge').hide();
				$('.cache_size').removeClass('deleting');
				$('.twosecond-cache').text(texts.cacheDeleted || 'Cache Deleted!');
				window.location.reload();
			}).fail(function(){
				$('#del_critical_css_cache').attr('disabled', false);
				$('#twosecond_cache_purge').hide();
				$('.cache_size').removeClass('deleting');
				$('.twosecond-cache').text(texts.tryAgain || 'try again');
				criticalLoader.hide();
				setTimeout(function(){
					$('.twosecond-cache').text(texts.cacheLabel || 'TwoSecond cache');
				}, 2000);
			});
		});

		// HTML cache purge
		$(document).on('click', '#del_html_cache', function(){
			$('.in-progress.bsd-flex.delete_html_cache').show();
			$('#del_html_cache').attr('disabled', true);
			var data = {
				action: 'twosecond_html_cache_purge',
				_twosecond_nonce: nonces.purge_html_cache || ''
			};
			$.get(ajaxurl, data, function(response){
				$('#del_html_cache').attr('disabled', false);
				$('.in-progress.bsd-flex.delete_html_cache').hide();
			}).fail(function(){
				$('#del_html_cache').attr('disabled', false);
				$('.in-progress.bsd-flex.delete_html_cache').hide();
			});
		});
	});
})(jQuery);
