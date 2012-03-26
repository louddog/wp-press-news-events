jQuery(function($) {
	var datepickerDefaults = {
		date: new Date(),
		current: new Date(),
		flat: true,
		starts: 0
	};
	
	$('.ldwppr_news_date').each(function() {
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