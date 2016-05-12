jQuery(document).ready(function($) {
    // process the form
    $('.ovh-newsletter-form').submit(function(event) {
				var formProcessingUrl = $(this).prop('action');

        // get the form data
        // there are many ways to get this data using jQuery (you can use the class or id also)
        var formData =  $(this).serializeFormJSON();

        // process the form
        $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : formProcessingUrl, // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
            encode      : true
        }).done(function(data) {
            // log data to the console so we can see
            // console.log(data);
						// here we will handle errors and validation messages
						if ( ! data.success) {
									// handle errors for email ---------------
									if (data.errors.mail) {
											$(this).find('.mail').addClass('error');
									}
							} else {
									// show the success message and hide inputs.
									$('.ovh-newsletter-form').find('input').remove();
									$('.ovh-newsletter-form').append('<div style="color: green;" class="confirmation">' + data.message + '</div>');
							}
        }).fail(function(data) {
						console.log("Fail:");
						console.log(data);
			  });

        // stop the form from submitting the normal way and refreshing the page
        event.preventDefault();
    });

});

// serialize form data to JSON
(function (jQuery) {
    jQuery.fn.serializeFormJSON = function () {
        var o = {};
        var a = this.serializeArray();
        jQuery.each(a, function () {
            if (o[this.name]) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };
})(jQuery);
