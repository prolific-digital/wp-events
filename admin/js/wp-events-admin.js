(function ($) {
	'use strict';
	$(window).load(function () {
		const startDate = $('#acf-field_61906f08deec7');
		const startDateSelector = $('#acf-field_61906f08deec7').next();
		startDateSelector.change(()=>{
			const weekday = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
			let formatDate = startDate[0].defaultValue.split('')
			formatDate.splice(4,0,'-')
			formatDate.splice(7,0,'-')
			const date = new Date(formatDate.join(""));
			date.setUTCHours(6);
			const dayOfWeek = weekday[date.getDay()];
			for (let i = 1; i < 6; i++) {
				const innerHtml = $(`#acf-field_619070e138d40-${i}`).parent().html();
				$(`#acf-field_619070e138d40-${i}`).parent().html(innerHtml.replace(/\s\w+\sof/, ` ${dayOfWeek} of`));
			}
		})
	});


})(jQuery);
