var codeEditor = [];
jQuery(document).ready(function () {
	jQuery('.parent-fields').on('change', function () {
		if (jQuery(this).is(':checked')) {
			jQuery(this).closest('.css_box').find('.child-fields').show();
		} else {
			jQuery(this).closest('.css_box').find('.child-fields').hide();
		}
	});
	jQuery(document).on('click','.activate-key', function () {
		var key = jQuery("[name='license_key']");
		if (key.val() == '') {
			alert("Please enter key");
			return false;
		}
		jQuery('#verify-key-loader').show();
		jQuery(this).prop('disabled', true);
		activateLicenseKey(key);

	});
	function activateLicenseKey(key) {
		var licenseKey = key.val().trim();
		if (!licenseKey) {
			alert("Please Enter License Key"); return false;
		}
		jQuery('.activate-key').prop('disabled', true);
		jQuery.ajax({
			url: adminUrl,
			data: {
				'action': 'twosecond_activate_license_key',
				'key': licenseKey,
				'_twosecond_nonce': twosecond_admin.license_nonce
			},
			success: function (data) {
				data = jQuery.parseJSON(data);
				if (data[1] == 'verified') {
					jQuery('[name="is_activated"]').val(data[2]);
					key.closest('form').submit();
				} else {
					jQuery('#verify-key-loader').hide();
					alert("Invalid key");
				}
				jQuery('.activate-key').prop('disabled', false);
			}
		});
	}
});
function IsJsonString(str) {
	try {
		JSON.parse(str);
	} catch (e) {
		return false;
	}
	return true;
}

jQuery(document).ready(function () {
	var tabBtn = document.querySelector('.mobile_toggle button');
	var tabPanel = document.querySelector('.tab-panel');
	tabBtn.addEventListener('click', function () {
		tabPanel.classList.toggle('menu-open');
	});
	jQuery('.expend-textarea').click(function () {
		var id = jQuery(this).attr('data-id');
		event.preventDefault();
		jQuery("#" + id).toggleClass("fullscreen");
	})

    jQuery('#import_button').click(function () {
        var file = jQuery('#twosecond_import_file').get(0);
        if (!file || !file.files || file.files.length === 0) {
            alert('Please choose an export file to import.');
            return false;
        }
        jQuery('#import_form').submit();
    });
	var hash = window.location.hash;
	if (hash) {
		jQuery(hash).prop("checked", "checked");
	}
	jQuery('[name="tabs"]').click(function () {
		window.location.hash = jQuery(this).attr("id");
	});
	jQuery('.add_more_image').click(function () {
		var index = jQuery(this).parents('#twosecond_opt_img_content').find('.image_src_field').length;

		var $html = '<tr class="image_src_field"><td style="width:70%; padding-left:0px;"><input type="text" name="optimiz_images[' + index + '][src]" placeholder="Please Enter Img Src" value=""></td><td style="padding-left:0px;"><input type="text" name="optimiz_images[' + index + '][width]" placeholder="Please Enter Image Width" value=""></td><td class="remove_image_field" style="width:5%; cursor:pointer;">X</td></tr>';

		jQuery(this).parents('.image_add_more_field').before($html);
	});

	jQuery('.add_more_combine_image').click(function () {

		var index = jQuery(this).parents('#twosecond_opt_img_combin_content').find('.image_src_field').length;
		var $html = '<tr class="image_src_field"><td style="width:70%; padding-left:0px;"><input type="text" name="combine_images[' + index + '][src]" placeholder="Please Enter Img Src" value=""></td><td style="padding-left:0px;"><input type="text" name="combine_images[' + index + '][position]" placeholder="Please Enter Image Width" value=""></td><td class="remove_image_field" style="width:5%; cursor:pointer;">X</td></tr>';

		jQuery(this).parents('.image_add_more_field').before($html);
	});

	//jQuery('.remove_image_field').click(function(){
	jQuery("table").delegate(".remove_image_field", "click", function () {
		jQuery(this).parents('.image_src_field').remove();
	});

	jQuery("ul.twoSecondnav li a").click(function (e) {
		e.preventDefault();
		var url = document.location.href;
		var newTab = jQuery(this).attr('data-section');
		var updatedUrl = updateQueryStringParameter(url, 'tab', newTab);
		history.pushState({}, '', updatedUrl);
		jQuery('.twoSecondnav li').removeClass('active');
		jQuery(this).parent().addClass('active in');
		jQuery('.tab-pane').removeClass('active in');
		jQuery('#' + newTab).addClass('active in');
	});

	var hash = window.location.href.match(/[?&]tab=([^&]+)/);
	if (hash && hash[1] && hash[1].length > 0) {
		jQuery('.tab-pane').removeClass('active in');
		jQuery('#' + hash[1]).addClass('active in');
	}

	function updateQueryStringParameter(uri, key, value) {
		var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
		var separator = uri.indexOf('?') !== -1 ? "&" : "?";

		if (uri.match(re)) {
			return uri.replace(re, '$1' + key + "=" + value + '$2');
		} else {
			return uri + separator + key + "=" + value;
		}
	}

	jQuery('#change-settings').click(function (e) {
		e.preventDefault();
		var url = document.location.href;
		var newTab = 'change_settings';
		var updatedUrl = updateQueryStringParameter(url, 'tab', newTab);
		history.pushState({}, '', updatedUrl);
		jQuery('.tab-pane').removeClass('active in');
		jQuery('.twoSecondnav li').removeClass('active');
		jQuery('#' + newTab).addClass('active in');
	});

	jQuery('.hook_submit').on('click', function () {
		jQuery('.save-changes-loader').show();
		jQuery('.main-form').submit();
	});

	jQuery('.error_hooks_close').click(function () {
		jQuery(this).parent('.error-hook-main').hide();
	});
	jQuery('.error_close_btn').click(function () {
		jQuery(this).parent('.error-div-main').hide();
	});

	jQuery('.add_more_row').click(function () {
		var inputName = jQuery(this).attr('data-name');
		var placeholder = jQuery(this).attr('data-placeholder');
		var html = '<div class="cdn_input_box minus bsd-flex"><input placeholder="' + placeholder + '" type="text" name="' + inputName + '[]""><button type="button" class="bstext-white rem-row bsbg-danger"><i class="fa fa-times"></i></button></div>';
		jQuery(this).closest('.input_box').find('.single-row').append(html);

	});
	jQuery('.input_box').on('click', '.rem-row', function () {
		jQuery(this).closest('.cdn_input_box.minus.bsd-flex').remove();
	});
	// For Hooks functionality

	function get_all_hooks() {
		var search_elementItems = '';
		//jQuery('.entry_search_container').show();
		jQuery('.single-hook').each(function () {
			var searchLabel = jQuery(this).find('span.main-label').html();
			var customClass = searchLabel.toLowerCase().replace(/\s+/g, '');
			jQuery(this).addClass('filter-' + customClass)
			var top = jQuery(this).position().top;
			search_elementItems += '<li><a class="scroll_element_item" data-label="' + searchLabel + '" data-filter="' + customClass + '" data-top="' + top + '" href="javascript:void(0);">' + jQuery(this).find('span.main-label').html() + '</a></li>';
		})
		search_elementItems = '<ul>' + search_elementItems + '</ul>';
		jQuery(".entry_search_contaner").html(search_elementItems);
		jQuery('.all_hooks').removeClass('single_selected');
	}


	jQuery('.pl_search_field').on("focus", function () {
		jQuery('.entry_search_contaner').show();
		var searchTerm = jQuery(this).val();
		if (searchTerm.length == 0) {
			get_all_hooks();
		}

	});
	jQuery('.pl_search_field').focusout(function () {
		var searchTerm = jQuery(this).val();
		setTimeout(function () {
			jQuery('.entry_search_contaner').hide();
		}, 300)


	});
	jQuery('.pl_search_field').on("keyup", function () {
		var search_elementItems = '';
		var searchTerm = jQuery(this).val();
		var entrySearch_sec = jQuery(".entry_search_contaner");

		jQuery('.entry_search_container').show();

		if (searchTerm.length > 0) {
			jQuery('.clear_field').show();
			jQuery('.all_hooks').removeClass('single_selected');
			var element_heading = jQuery('.single-hook');
			element_heading.each(function (index) {

				var ele_str = jQuery(this).text();
				if (ele_str.toLowerCase().indexOf(searchTerm.toLowerCase()) != -1) {
					jQuery(this).show();
					jQuery(this).addClass('active');
					var searchLabel = jQuery(this).find('span.main-label').html();
					var customClass = searchLabel.toLowerCase().replace(/\s+/g, '');
					jQuery(this).addClass('filter-' + customClass)
					if (jQuery(this).parents('a').length > 0) {
						search_elementItems += '<li><a href="' + jQuery(this).parents('a').attr('href') + '">' + jQuery(this).text() + '</a></li>';
					} else {
						var top = jQuery(this).position().top;
						search_elementItems += '<li><a class="scroll_element_item" data-label="' + searchLabel + '"data-filter="' + customClass + '" data-top="' + top + '" href="javascript:void(0);">' + jQuery(this).find('span.main-label').html() + '</a></li>';
					}
				} else {
					jQuery(this).hide();
					jQuery(this).removeClass('active');

				}
			});

			if (null == search_elementItems || "" == search_elementItems) {

				search_elementItems = '<li>No matching.</li>';
			}
			search_elementItems = '<ul>' + search_elementItems + '</ul>';
			jQuery(".entry_search_contaner").html(search_elementItems);

		} else {
			jQuery('.single-hook').show();
			jQuery('.single-hook').removeClass('active');
			get_all_hooks();
			jQuery('.clear_field').hide();
		}

	});

	function scrollElem(dataFilter) {
		jQuery('.single-hook').hide();
		jQuery('.single-hook.filter-' + dataFilter).show();
		jQuery('.all_hooks').addClass('single_selected');
		jQuery('.clear_field').show();
		jQuery('.entry_search_contaner').html('');
		return;
	}

	jQuery("body").delegate(".scroll_element_item", "click", function () {
		var top = jQuery(this).attr('data-top');
		var dataFilter = jQuery(this).attr('data-filter');
		scrollElem(dataFilter);
		jQuery('.pl_search_field').val(jQuery(this).attr('data-label'));
	});

	jQuery("body").delegate(".used_hook_btn", "click", function () {
		var dataFilter = jQuery(this).attr('data-filter');
		scrollElem(dataFilter);
		jQuery('.pl_search_field').val(jQuery(this).attr('data-label'));
	});
	jQuery('body').click(function (e) {
		var container = jQuery(".menu-header-search");
		// If the target of the click isn't the container
		if (!container.is(e.target) && container.has(e.target).length === 0) {
			jQuery('.entry_search_container').hide();
		}
	});

	get_all_hooks();

	jQuery('button.clear_field').click(function () {
		jQuery('.pl_search_field').val('');
		jQuery(this).hide();
		jQuery('.single-hook').show();
		jQuery('.all_hooks').removeClass('single_selected');
	});

	// End
	// For Logs Functionality

	function twoSecondAjaxLoadLog(limit, issueType, urls, startDate, endDate, deviceType, paged, refBy) {
		jQuery('.log-data-table').addClass('loading');
		jQuery.ajax({
			url: adminUrl,
			method: 'POST',
			data: {
				'action': 'twosecond_get_log_data',
				'getBy': 'ajax',
				'limit': limit,
				'issuetype': issueType,
				'url': urls,
				'start_date': startDate,
				'end_date': endDate,
				'paged': paged,
				'deviceType': deviceType,
			},
			success: function (data) {
				jQuery('.log-data-table').html(data);
				jQuery('.log-data-table').removeClass('loading');

			}, error: function (errorThrown) {
				console.log(errorThrown);
			}
		})
	}
	jQuery('.btn-log-delete').on('click', function () {
		var timeValue = jQuery('.log_select').val();
		jQuery('.log-data-table').addClass('loading');
		jQuery.ajax({
			url: adminUrl,
			method: 'POST',
			data: {
				'action': 'twosecond_delete_log_data',
				'time_interval': timeValue,
			},
			success: function (data) {
				jQuery('.log-data-table').html(data);
				jQuery('.log-data-table').removeClass('loading');

			}, error: function (errorThrown) {
				console.log(errorThrown);
			}
		})
	})

	function filterClearDefaultValue() {
		jQuery('.filter_by_issuetype').val('');
		jQuery('.filter_by_deviceType').val('');
		jQuery('#filter_by_url').val('').trigger('change');
		jQuery('.start_date').val('');
		jQuery('.end_date').val('');
		jQuery('.custom_select_inp').val('');
		jQuery('.url_checkbox').prop('checked', false);
		jQuery('span.select2.select2-container.select2-container--default').hide();
		jQuery('.btn_clear_url_inp').hide();
	}

	function getLogData(page = '') {
		var limit = jQuery('.show_log_entry').val();
		var issueType = jQuery('.filter_by_issuetype').val();
		var url = jQuery('#filter_by_url').val();
		var startDate = jQuery('.start_date').val();
		var endDate = jQuery('.end_date').val();
		var deviceType = jQuery('.filter_by_deviceType').val();
		var paged = '';
		if (page > 0) {
			paged = page;
		} else {
			paged = jQuery('.p-num.active').attr('data-page');
		}

		twoSecondAjaxLoadLog(limit, issueType, url, startDate, endDate, deviceType, paged, '');
	}

	jQuery(document).on('click', '.pagination .p-num', function () {
		jQuery('.p-num').removeClass('active');
		jQuery(this).addClass('active')
		getLogData();
	});
	jQuery(document).on('click', '.pagination .page-next', function () {
		jQuery('.p-num').removeClass('active');
		var page = jQuery(this).attr('data-page');
		getLogData((parseInt(page) + 1));
	});

	jQuery(document).on('click', '.pagination .page-next-last', function () {
		jQuery('.p-num').removeClass('active');
		var page = jQuery(this).attr('data-page');
		getLogData(parseInt(page));
	});
	jQuery(document).on('click', '.pagination .page-prev', function () {
		jQuery('.p-num').removeClass('active');
		var page = jQuery(this).attr('data-page');
		var updatedPage = (parseInt(page) - 1);
		if (updatedPage > 1) {
			updatedPage = (parseInt(page) - 1);
		} else {
			updatedPage = 1;
		}
		getLogData(updatedPage);
	});


	jQuery(document).on('click', '.btn-log-refresh, .btn-apply-filter', function () {
		getLogData();
	});

	jQuery('.btn-rem-filter').click(function () {
		var limit = jQuery('.show_log_entry').val();
		filterClearDefaultValue();
		twoSecondAjaxLoadLog(limit, '', '', '', '', '', 1, 'refresh');
	});

	jQuery('.show_log_entry').on('change', function () {
		getLogData();
	})

	jQuery('#enable-webvitals-log, #enable-change-log, #enable-ai-optimization').on('change', function () {
		let inputName = jQuery(this).attr('name');
		if(inputName == 'ai-optimization' && jQuery(this).is(':checked')){
			jQuery('#enable-html-caching, #turn-on-optimization, #enable-css-minification, #load-critical-css, input[name="webp_jpg"], input[name="webp_png"], input[name="lazy_load"], input[name="lazy_load_iframe"], input[name="lazy_load_video"], input[name="lazy_load_audio"], input[name="js"], #localize-google-fonts, #resp-imgs, input[name="lbc"], input[name="gzip"], input[name="remquery"], input[name="load_critical_css_style_tag"]').prop('checked', true);
		}
		if(inputName == 'page_batch'){
			let value = jQuery(this).val();
			if(value > 20){
				jQuery(this).val(0);
				alert('Please Enter value less then or equal to 20');
				return false;
			}
		}
		jQuery('.main-form').submit();
	});

	jQuery('.start_date').datepicker({
		changeMonth: true,
		changeYear: true,
		yearRange: "-100:+0"
	});
	jQuery('.start_date').show();
	jQuery('.end_date').datepicker({
		changeMonth: true,
		changeYear: true,
		yearRange: "-100:+0"
	});
	jQuery('.end_date').show();


	jQuery(document).on('click', '.more_info', function () {
		var id = jQuery(this).attr('data-id');
		var data = jQuery('.data_' + id + ' .log-data').html();
		var html = '<li><strong>Data:</strong><code>' + data + '</code></li>';
		jQuery('.log-info').html(html);
	});


	jQuery('.url-select-multiple').select2();
	jQuery('span.select2.select2-container.select2-container--default').hide();


	jQuery(document).on('keyup', '.custom_select_inp', function () {
		var text = jQuery(this).val();
		if (text.length > 0) {
			jQuery('.btn_clear_url_inp').show();
		}
		if (text.length > 2) {
			jQuery('#custom_select_url').show();
			jQuery.ajax({
				url: adminUrl,
				method: 'POST',
				data: {
					'action': 'twosecond_show_url_suggestions',
					's_text': text,
				},
				success: function (response) {
					var responseData = JSON.parse(response);
					if (responseData.length == 0) {
						jQuery('#custom_select_url').html('No Url Found');
					} else {

						var selectedValues = jQuery('#filter_by_url').val();

						var createdOptions = [];
						jQuery('#filter_by_url').find('option').each(function () {
							createdOptions.push(jQuery(this).val());
						});

						var createdOptionsWithCheckobx = [];
						jQuery('.single-url .url').each(function () {
							createdOptionsWithCheckobx.push(jQuery(this).html());
						})
						var options = '';

						var optionsWithCheckbox = '<ul class="option_checkobx">';


						jQuery.each(responseData, function (index, value) {
							var checkedUrl = '';
							if (jQuery.inArray(value, selectedValues) != -1) {
								checkedUrl = 'checked';
							}
							if (jQuery.inArray(value, createdOptions) == -1) {
								options += '<option value="' + value + '">' + value + '</option>';

							}
							optionsWithCheckbox += '<div class="single-url"><div class="url">' + value + '</div><input type="checkbox" name="temp_input" class="url_checkbox" value="" ' + checkedUrl + '></div>';
						});

						optionsWithCheckbox += '</ul>';

						if (options.length > 0) {
							jQuery('#filter_by_url').append(options);
						}
						jQuery('#custom_select_url').html(optionsWithCheckbox);
					}
				},
				error: function (errorThrown) {
					console.log(errorThrown);
				}
			});
		} else if (text.length == 0) {
			jQuery('#custom_select_url').hide();
			jQuery('.btn_clear_url_inp').hide();
		}
	});

	jQuery('.btn_clear_url_inp').on('click', function () {
		jQuery('.custom_select_inp').val('');
		jQuery(this).hide();
	})
	jQuery(document).on('change', '.url_checkbox', function () {
		var selectedUrls = jQuery('#filter_by_url').val();
		var url = jQuery(this).parent('.single-url').find('.url').html();
		if (jQuery(this).is(":checked")) {
			if (jQuery.inArray(url, selectedUrls) == -1) {
				selectedUrls.push(url);
			}
		} else {
			selectedUrls = selectedUrls.filter(function (item) {
				return item !== url;
			});
		}
		jQuery('#filter_by_url').val(selectedUrls);
		jQuery('#filter_by_url').trigger('change');

		if (selectedUrls.length > 0) {
			jQuery('span.select2.select2-container.select2-container--default').show();
		} else {
			jQuery('span.select2.select2-container.select2-container--default').hide();
		}

	});

	jQuery("#custom_select_url").on("click", function (event) {
		event.stopPropagation();
	});

	jQuery(document).on("click", function (event) {
		if (!jQuery(event.target).closest("#custom_select_url").length) {
			jQuery("#custom_select_url").hide();
		}
	});
	document.addEventListener('scroll', function () {
		const scrollPosition = window.scrollY || window.pageYOffset;
		const images = document.querySelectorAll('.admin-twoSecond .tab-panel')[0];
		if (images.length > 0) {
			images.forEach(image => {
				if (scrollPosition > 50) {
					image.classList.add('fixed');
				} else {
					image.classList.remove('fixed');
				}
			});
		}
	});

	/*** Setting Change Log Table Js | Start */
	jQuery(document).on('click', '.pagination .change-page-prev', function () {
		jQuery('.change-p-num').removeClass('active');
		var page = jQuery(this).attr('data-page');
		var updatedPage = (parseInt(page) - 1);
		if (updatedPage > 1) {
			updatedPage = (parseInt(page) - 1);
		} else {
			updatedPage = 1;
		}
		getChangeLogData(updatedPage);
	});
	jQuery(document).on('click', '.pagination .change-p-num', function () {
		jQuery('.change-p-num').removeClass('active');
		jQuery(this).addClass('active')
		getChangeLogData();
	});
	jQuery(document).on('click', '.pagination .change-page-next', function () {
		jQuery('.change-p-num').removeClass('active');
		var page = jQuery(this).attr('data-page');
		getChangeLogData((parseInt(page) + 1));
	});

	jQuery(document).on('click', '.pagination .change-page-next-last', function () {
		jQuery('.change-p-num').removeClass('active');
		var page = jQuery(this).attr('data-page');
		getChangeLogData(parseInt(page));
	});
	jQuery(document).on('click', '.pagination .change-page-prev', function () {
		jQuery('.change-p-num').removeClass('active');
		var page = jQuery(this).attr('data-page');
		var updatedPage = (parseInt(page) - 1);
		if (updatedPage > 1) {
			updatedPage = (parseInt(page) - 1);
		} else {
			updatedPage = 1;
		}
		getChangeLogData(updatedPage);
	});

	function getChangeLogData(page = '') {
		var limit = jQuery('.show_change_log_entry').val();
		var startDate = jQuery('.changelog_start_date').val();
		var endDate = jQuery('.changelog_end_date').val();
		var paged = '';
		if (page > 0) {
			paged = page;
		} else {
			paged = jQuery('.change-p-num.active').attr('data-page');
		}
		twoSecondAjaxLoadChangeLog(limit, startDate, endDate, paged, '');
	}
	jQuery('.btn-rem-filter').click(function () {
		var limit = jQuery('.show_change_log_entry').val();
		filterClearDefaultValue();
		twoSecondAjaxLoadChangeLog(limit, '', '', '', 'refresh');
	});

	jQuery('.show_change_log_entry').on('change', function () {
		getChangeLogData();
	})

	function twoSecondAjaxLoadChangeLog(limit, startDate, endDate, paged, refBy) {
		jQuery('.change-log-data-table').addClass('loading');
		jQuery.ajax({
			url: adminUrl,
			method: 'POST',
			data: {
				'action': 'twosecond_get_change_log_data',
				'getBy': 'ajax',
				'limit': limit,
				'start_date': startDate,
				'end_date': endDate,
				'paged': paged
			},
			success: function (data) {
				jQuery('.change-log-data-table').html(data);
				jQuery('.change-log-data-table').removeClass('loading');

			}, error: function (errorThrown) {
				console.log(errorThrown);
			}
		})
	}
	jQuery('.changelog_start_date').datepicker({
		changeMonth: true,
		changeYear: true,
		yearRange: "-100:+0"
	});
	jQuery('.changelog_start_date').show();
	jQuery('.changelog_end_date').datepicker({
		changeMonth: true,
		changeYear: true,
		yearRange: "-100:+0"
	});
	jQuery('.changelog_end_date').show();

	jQuery(document).on('click', '.btn-change-log-refresh, .btn-changelog-apply-filter', function () {
		getChangeLogData();
	});
	jQuery('.btn-changelog-rem-filter').click(function () {
		var limit = jQuery('.show_log_entry').val();
		changeLogFilterClearDefaultValue();
		twoSecondAjaxLoadChangeLog(limit, '', '', 1, 'refresh');
	});

	function changeLogFilterClearDefaultValue() {
		jQuery('.changelog_start_date').val('');
		jQuery('.changelog_end_date').val('');
	}
	jQuery('.btn-changelog-delete').on('click', function () {
		var timeValue = jQuery('#changelog_delete_time').val();
		jQuery('.change-log-data-table').addClass('loading');
		jQuery.ajax({
			url: adminUrl,
			method: 'POST',
			data: {
				'action': 'twosecond_delete_change_log_data',
				'time_interval': timeValue,
			},
			success: function (data) {
				jQuery('.change-log-data-table').html(data);
				jQuery('.change-log-data-table').removeClass('loading');

			}, error: function (errorThrown) {
				console.log(errorThrown);
			}
		})
	})
	jQuery(document).on('click', '.show-more', function () {
		const targetId = jQuery(this).data('target');
		const fullText = jQuery('#' + targetId).find('pre').html();
		showPopup(fullText);
	});
	jQuery(document).on('click', '.show-diff', function () {
		const previousContentId = jQuery(this).data('target-old');
		const newContentId = jQuery(this).data('target-new');
		const previousContent = jQuery('#' + previousContentId).find('pre').html();
		const newContent = jQuery('#' + newContentId).find('pre').html();
		const diff = Diff.diffLines(previousContent, newContent);
		let result = '';
		diff.forEach((part) => {
			const color = part.added ? 'ins' : part.removed ? 'del' : 'span';
			result += `<${color}>${part.value}</${color}>`;
		});
		showPopup(result);
	});

	function showPopup(content) {
		const popup = jQuery(`
            <div class="change-log-popup">
                <div class="change-log-popup-content">
                    <span class="change-log-close-popup">&times;</span>
                    <pre>${content}</pre>
                </div>
            </div>
        `);
		jQuery('body .admin-twoSecond').append(popup);
		popup.find('.change-log-close-popup').on('click', () => popup.remove());
		jQuery(window).on('click', (event) => {
			if (jQuery(event.target).is(popup)) {
				popup.remove();
			}
		});
	}

	/*** Setting Change Log Table Js | End */
	jQuery('.main-form').on('submit', function (event) {
		event.preventDefault();
		jQuery('input[name="preload_resources[]"]').each(function () {
			let value = jQuery(this).val();
			value = value.replace('https', '####');
			value = value.replace('http', '###');
			jQuery(this).val(value);
		});
		setTimeout(() => {
			this.submit();
		}, 1000);
	});

	jQuery('input[name="license_key"]').on('keypress', function (e) {
		jQuery('input[name="is_activated"]').val('');
		const icon = jQuery('i.fa.fa-check-circle-o');
		if (icon.length) {
			icon.after('<button class="activate-key btn" type="button">Activate</button>');
			icon.remove();
		}
	});

	jQuery(".togglePass").click(function () {
		const passwordId = jQuery(this).data("password-id");
		const $passwordInput = jQuery("#" + passwordId);
		if ($passwordInput.length) {
			if ($passwordInput.attr("type") === "password") {
				$passwordInput.attr("type", "text");
				jQuery(this).html("<i class='fa fa-eye-slash'></i>");
			} else {
				$passwordInput.attr("type", "password");
				jQuery(this).html("<i class='fa fa-eye'></i>");
			}
		}
	});

	let hooks = jQuery('.used_hook_btn');
	if (hooks.length > 0) {
		hooks.first().trigger('click');
	}

	/********************************************* Optimize With Ai Js Start */
	var optimizeWithAiEnabled = jQuery('#enable-ai-optimization').is(':checked');
	var rows = jQuery('#filter-optai-rows').val();
	var filter_url = jQuery('#filter-optai-url').val();
	var page = 1;
	var tableData;
	var ids = [];
	const count = jQuery('#page-batch').val();
	let active = false;
	var twosecondInterval;
	var reqCount = 0;
	var retry = false;
	var status = '';

	jQuery('#filter-optai-rows, #filter-optai-status').change(function (e) { 
		rows = jQuery('#filter-optai-rows').val();
		status = jQuery('#filter-optai-status').val();
		page = 1;
		optimize(1);
	});

	jQuery('#opt-ai-filter-btn').click(function(){
		filter_url = jQuery('#filter-optai-url').val();
		optimize(0);
	});

    jQuery(document).on('click', '.change-p-num-oi, .change-page-next-oi, .change-page-next-last-oi', function() {
        page = jQuery(this).data('page');
        optimize(1);
    });

	function optimize(table = 0){
		++reqCount;
		jQuery.ajax({
			type: "POST",
			url: adminUrl,
			data: {
				'action' : 'twosecond_optimize_page',
				'page' : page,
                'rows' : rows,
                'status' : status,
				'count' : table ? 0 : count,
				'ids' : ids,
				'url' : filter_url,
				'reqCount' : reqCount
			},
			success: function(response) {
				retry = false;
				if(response.table_data){
					tableData = response.table_data;
					refreshTable();
				}
				if(tableData.optimized_rows == tableData.total_rows){
					clearInterval(twosecondInterval);
				}
				if(!table){
					ids  = response.ids;
					if(ids && ids.length && reqCount < 13){
						setTimeout(() => optimize(), 5000)
					} else {
						retry = true;
					}
				}	
			},
			error: function (){
				--reqCount;
				setTimeout(() => optimize(), 5000)
			}
		});
	}

	function refreshTable(){
		if(tableData.total_rows == 0){
			twosecondInsertSiteUrls();
		}
		jQuery('#optimize-ai-bar').css('width', `${tableData.percentage}%`);
		jQuery('#optimize-pages-count').html(`${tableData.optimized_rows} of ${tableData.total_rows} pages optimized`);
		jQuery('#optimize-remaining-time').html(tableData.percentage == 100 ? null : tableData.time);
		jQuery('#optimize-ai-data-table').html(tableData.html);
	}

	function twosecondInsertSiteUrls(batch_size = 1000, offset = 0){
		jQuery.ajax({
			type: "GET",
			url: adminUrl,
			dataType: 'json',
			data: {
				'action': 'twosecond_insert_site_urls',
				'batch_size': batch_size,
				'offset': offset,

			},
			success: function (response) {
				retry = false;
				active = false;
				runOptimization();
			}
		});
	}

	jQuery('#twosecond_restart_optimization').click(function (e) { 
		jQuery('#restart-optimization-loader').show();
		jQuery.ajax({
			type: "GET",
			url: adminUrl,
			data: {
				'action': 'twosecond_restart_optimization'
			},
			success: function (response) {
				window.location.reload();
			}
		});
	});

	jQuery(document).on('click', '.optimize-with-ai-url-copy', function() {
		var url = jQuery(this).data('url');
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(url).then(function() {
				twosecondShowToast('success', 'URL copied to clipboard!', 1000)
			}).catch(function(err) {
				console.error('Failed to copy: ', err);
			});
		} else {
			var tempInput = jQuery('<input>');
			jQuery('body').append(tempInput);
			tempInput.val(url).select();
			document.execCommand('copy');
			tempInput.remove();
			twosecondShowToast('success', 'URL copied to clipboard!', 1000)
		}

	});

	function runOptimization(){
		if(optimizeWithAiEnabled && !active){
			active = true;
			optimize();
			twosecondInterval = setInterval(function(){
				if(retry){
					reqCount = 0;
					ids = [];
					optimize();
				}
			}, 60000)
		}
	}
	const params = new URLSearchParams(window.location.search);
	const bstab = params.get('tab');
	if(bstab == 'optimizeAi'){
		setTimeout(function(){
			runOptimization();
		}, 1000)
	}
	jQuery('li.twosecond_optimize_ai a').click(function(){
		runOptimization();
	})

	jQuery(document).on('click', '.optimize-with-ai-btn', function(){
		id = jQuery(this).attr('data-id');
		let $row = jQuery(this).closest('tr');
		let $secondTd = $row.find('td').eq(1);
		let $thirdTd = $row.find('td').eq(2);
		$secondTd.html('<span class="badge inProgress"><div class="dots"></div></span>');
		$thirdTd.html('<span class="badge inProgress"><div class="dots"></div></span>');

		jQuery.ajax({
			type: "POST",
			url: adminUrl,
			data: {
				'action': 'twosecond_reset_single_page',
				'id': id,
			},
			success: function (response) {
				optimize(0);
			}
		});
	})

	jQuery(document).on('click', '.view-page-score-now', function(){
		let url = jQuery(this).attr('data-url');
		url = encodeURI(url);
		window.open(`https://pagespeed.web.dev/analysis?url=${url}`, "_blank");
	})

	/********************************************* Optimize With Ai Js End */
});

/********************************************* Multi Cdn Js Start */
const getAllSelectedTypes = () => {
  const values = Array.from(document.querySelectorAll('.bs-cdn input[name$="[type]"]'))
    .flatMap(el => (el.value ? el.value.split(',') : []));
  return [...new Set(values)];
};

const filterOptions = (input, listId) => {
	const filter = input.value.toLowerCase();
	const items = jQuery(`#${listId} .dropdown-item`);
	const allSelectedTypes = getAllSelectedTypes();
	items.each((_, item) => {
		const showItem = (item.textContent.toLowerCase().includes(filter) || item.classList.contains('select-all')) && !allSelectedTypes.includes(item.dataset.value);
		jQuery(item).toggle(showItem);
	});
	jQuery(`#${listId}`).toggle(items.filter(':visible').length > 0);
};
jQuery(() => {
	let index = jQuery('.twosecond-cdn').length || 0;
	jQuery('.add_more_cdnRows').click(() => {
		const newCdnRow = `
	<div class="twosecond-cdn" data-index="${index}">
		<div class="bsd-flex gap10">
			<label>CDN url<span class="twosecondinfo"></span><span class="twosecond-info-display">Enter CDN url with http or https</span></label>
			<div class="cdn_input_box minus">
				<button type="button" class="Remove_twosecond_cdnentries"><i class="fa fa-times"></i></button>
			</div>
			<div class="input_box">
				<label for="cdn-url-${index}">
					<input type="text" name="cdn[${index}][url]" id="cdn-url-${index}" placeholder="Please Enter CDN url here">
				</label>
			</div>
		</div>
		<div class="bsd-flex gap20">
			<label>Select File Types To Include<span class="twosecondinfo"></span></label>
			<div class="input_box custom-dropdown">
				<input type="hidden" name="cdn[${index}][type]" id="exclude-file-extensions-from-cdn-${index}" value="">
				<div class="selected-items" id="selected-extensions-${index}"></div>
				<input type="text" placeholder="Type to filter extensions" class="dropdown-input" data-list-id="extensions-list-${index}">
				<div class="dropdown-list" id="extensions-list-${index}">
					<div class="dropdown-item select-all" data-value="all">Select All</div>
					<div class="dropdown-item" data-value="image">Image</div><div class="dropdown-item" data-value="font">Fonts</div>
					<div class="dropdown-item" data-value="js">Js</div><div class="dropdown-item" data-value="css">Css</div>
					<div class="dropdown-item" data-value="audio">Audio</div><div class="dropdown-item" data-value="video">Video</div>
				</div>
			</div>
		</div>
		<div class="bsd-flex gap20">
			<label>Exclude path from cdn<span class="twosecondinfo"></span><span class="twosecond-info-display">Enter path separated by comma which are to be excluded from CDN. For eg. (/wp-includes/)</span></label>
			<div class="input_box">
				<label for="exclude-path-from-cdn-${index}">
					<input type="text" name="cdn[${index}][exclude_path]" id="exclude-path-from-cdn-${index}" placeholder="Please Enter paths separated by comma">
				</label>
			</div>
		</div>
	</div>`;
		jQuery('.addmore_button').before(newCdnRow).prev().find('.dropdown-item').hide();
		index++;
		getAllSelectedTypes().forEach(type => {
			jQuery(`.bs-cdn:last .dropdown-item[data-value="${type}"]`).hide();
		});
	});
	jQuery(document).on('focus input', '.dropdown-input', function() {
		jQuery(`#${jQuery(this).data('list-id')}`).show();
		filterOptions(this, jQuery(this).data('list-id'));
	});
	jQuery(document).on('click', '.Remove_twosecond_cdnentries', function() {
		const $row = jQuery(this).closest('.twosecond-cdn');
		const multipleRows = jQuery('.twosecond-cdn').length > 1;
		multipleRows ?
			(() => {
				$row.remove();
				jQuery('.twosecond-cdn').each((i, el) => {
					jQuery(el).attr('data-index', i).find('input').each(function() {
						const name = jQuery(this).attr('name')?.replace(/cdn\[\d+\]/, `cdn[${i}]`);
						name && jQuery(this).attr('name', name).attr('id', name.replace(/\[.*?\]/, `-${i}`));
					});
				});
			})() :
			(() => {
				$row.find('input').val('');
				$row.find('.selected-items').empty();
				$row.find('.dropdown-item:not(.select-all)').show();
			})();
	});
	jQuery(document).on('click', event => {
		!jQuery(event.target).closest('.custom-dropdown').length && jQuery('.dropdown-list').hide();
	});
	jQuery(document).on('click', '.dropdown-item', function() {
		const $dropdown = jQuery(this).closest('.custom-dropdown');
		const $hiddenInput = $dropdown.find('input[type="hidden"]');
		const $selectedContainer = $dropdown.find('.selected-items');
		const value = jQuery(this).data('value');
		jQuery(this).hasClass('select-all') ?
			(() => {
				$dropdown.find('.dropdown-item:not(.select-all):visible').each(function() {
					const itemValue = jQuery(this).data('value');
					!$selectedContainer.find(`.selected-item[data-value="${itemValue}"]`).length &&
						(() => {
							$selectedContainer.append(`<span class="selected-item" data-value="${itemValue}">${jQuery(this).text()}<button type="button" class="remove-item" data-value="${itemValue}">x</button></span>`);
							jQuery(this).hide();
						})();
				});
				$hiddenInput.val($selectedContainer.find('.selected-item').map(function() {
					return jQuery(this).data('value');
				}).get().join(','));
			})() :
			!$selectedContainer.find(`.selected-item[data-value="${value}"]`).length && (() => {
				$selectedContainer.append(`<span class="selected-item" data-value="${value}">${jQuery(this).text()}<button type="button" class="remove-item" data-value="${value}">x</button></span>`);
				$hiddenInput.val(($hiddenInput.val() ? $hiddenInput.val().split(',') : []).concat(value).join(','));
				jQuery(this).hide();
			})();
		$dropdown.find('.dropdown-input').val('');
		jQuery(this).closest('.dropdown-list').hide();
	});
	jQuery(document).on('click', '.remove-item', function() {
		const value = jQuery(this).data('value');
		const $selectedItem = jQuery(this).closest('.selected-item');
		const $dropdown = jQuery(this).closest('.custom-dropdown');
		const $hiddenInput = $dropdown.find('input[type="hidden"]');
		$selectedItem.remove();
		$hiddenInput.val($hiddenInput.val().split(',').filter(val => val !== value).join(','));
		jQuery(`.dropdown-item[data-value="${value}"]`).show();
	});
	jQuery(document).on('keydown', '.dropdown-input', function(e) {
		const $dropdown = jQuery(this).siblings('.dropdown-list');
		const $items = $dropdown.find('.dropdown-item:visible');
		const currentIndex = $items.index($items.filter('.selected'));
		e.key === 'ArrowDown' ? (() => {
				e.preventDefault();
				$items.removeClass('selected').eq((currentIndex + 1) % $items.length).addClass('selected');
			})() :
			e.key === 'ArrowUp' ? (() => {
				e.preventDefault();
				$items.removeClass('selected').eq((currentIndex - 1 + $items.length) % $items.length).addClass('selected');
			})() :
			e.key === 'Enter' ? (() => {
				e.preventDefault();
				$items.eq(currentIndex >= 0 ? currentIndex : 0).trigger('click');
			})() :
			e.key === 'Escape' && (() => {
				e.preventDefault();
				$dropdown.hide();
			})();
	});
	const updateAllDropdownsVisibility = () => {
		const allSelectedTypes = getAllSelectedTypes();
		jQuery('.dropdown-item:not(.select-all)').show();
		jQuery('.twosecond-cdn').each(function() {
			const thisRowTypes = jQuery(this).find('input[name$="[type]"]').val()?.split(',') || [];
			jQuery(this).find('.dropdown-item:not(.select-all)').each(function() {
				(allSelectedTypes.includes(jQuery(this).data('value')) && !thisRowTypes.includes(jQuery(this).data('value'))) && jQuery(this).hide();
			});
			thisRowTypes.forEach(type => jQuery(this).find(`.dropdown-item[data-value="${type}"]`).hide());
		});
	}
	updateAllDropdownsVisibility();
	!jQuery('.twosecond-cdn').length && jQuery('.add_more_cdnRows').trigger('click');
});

/********************************************* Multi Cdn Js End */

/********************************************* BS Toaster Js Start */
function twoSecondShowToast(type, message, hide = false) {
	const container = document.querySelector('.twosecond-toast-container');
	const toast = document.createElement('div');
	toast.className = `bs-toast-message bs-toast-${type}`;
	toast.innerHTML = `
		<span>${message}</span>
		<button class="twosecond-toast-close-button" onclick="this.parentElement.remove()">×</button>
	`;
	container.appendChild(toast);
	if(hide){
		setTimeout(() => { toast.classList.add('fade-out');
			setTimeout(() => { toast.remove();}, 500);
		}, hide);
	}
}
/********************************************* BS Toaster Js End */