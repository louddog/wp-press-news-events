jQuery(function($) {
	var datepickerDefaults = {
		date: new Date(),
		current: new Date(),
		flat: true,
		mode: 'range',
		starts: 0
	};
	
	$('.ldwppr_event_date').each(function() {
		var input = $(this).hide();
		
		var options = {
			onChange: function(formated, dates) {
				input.val(formated);
				
				// force 'all-day' for multi-day events
				if (dates[1] - dates[0] > 86400000) {
					$('#ldwppr_all_day').attr('checked', true).change().attr('disabled', true);
				} else $('#ldwppr_all_day').attr('disabled', false);
			}
		};

		if (input.val().length) {
			options.date = input.val().split(',');
			options.current = options.date[0];
		}
		
		$('<div></div>').insertAfter(input).DatePicker(
			$.extend({}, datepickerDefaults, options)
		).change();
	});
	
	// show time inputs when not 'all-day'
	$('#ldwppr_all_day').change(function() {
		$('#ldwppr_time_options').toggle(!$(this).attr('checked') ? true : false);
	}).change();
	
	// enable 'all-day' again before submitting, so that the value goes through
	$('#ldwppr_options').closest('form').submit(function() {
		$('#ldwppr_all_day').attr('disabled', false);
	});
});