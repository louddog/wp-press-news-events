// News -----------------------------------------------------------------
jQuery(function($) {
	var datepickerDefaults = {
		date: new Date(),
		current: new Date(),
		flat: true,
		starts: 0
	};
	
	$('.pne_news_options .date').each(function() {
		var input = $(this).hide();
		
		var options = {
			onChange: function(formated, date) {
				input.val(formated);
			}
		};

		if (input.val().length) {
			options.date = input.val();
			options.current = options.date;
		}
		
		$('<div></div>').insertAfter(input).DatePicker(
			$.extend({}, datepickerDefaults, options)
		).change();
	});
});

// Events -----------------------------------------------------------------
jQuery(function($) {
	var datepickerDefaults = {
		date: new Date(),
		current: new Date(),
		flat: true,
		mode: 'range',
		starts: 0
	};
	
	$('.pne_event_options .date_range').each(function() {
		var input = $(this).hide();
		var all_day_checkbox = $(this).closest('.pne_event_options').find('.all_day');
		var times = $(this).closest('.pne_event_options').find('.event_times');
		
		var options = {
			onChange: function(formated, dates) {
				input.val(formated);
				
				// force 'all-day' for multi-day events
				if (dates[1] - dates[0] > 86400000) {
					all_day_checkbox.attr('checked', true).change().attr('disabled', true);
				} else all_day_checkbox.attr('disabled', false);
			}
		};

		// setup with initial value
		if (input.val().length) {
			options.date = input.val().split(',');
			options.current = options.date[0];
		}
		
		$('<div></div>').insertAfter(input).DatePicker(
			$.extend({}, datepickerDefaults, options)
		).change();
		
		// show time inputs when not 'all-day'
		all_day_checkbox.change(function() {
			times.toggle(!$(this).attr('checked') ? true : false);
		}).change();

		// enable 'all-day' again before submitting, so that the value goes through
		$(this).closest('form').submit(function() {
			all_day_checkbox.attr('disabled', false);
		});
	});
});