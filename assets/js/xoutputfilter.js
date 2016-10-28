/* Get Parameter from URL */
var getUrlParameter = function getUrlParameter(sParam) {
	var sPageURL = decodeURIComponent(window.location.search.substring(1)),
		sURLVariables = sPageURL.split('&'),
		sParameterName,
		i;

	for (i = 0; i < sURLVariables.length; i++) {
		sParameterName = sURLVariables[i].split('=');

		if (sParameterName[0] === sParam) {
			return sParameterName[1] === undefined ? true : sParameterName[1];
		}
	}
};

/* Display Growl-Message */
function displayGrowl(message, duration, type) {
    type = type || "alert-danger";
	$('body').append('<div class="growl-notice alert '+type+'"></div>');
	$('.growl-notice').html(message).fadeTo(200, 0.8);
	setTimeout(function(){
		$('.growl-notice').fadeOut();
	}, duration);
}

/* Editiermodus in den Übersichten für Felter mit contenteditable=true */
$(document).on('rex:ready', function (event, container) {

	$pageurl = getUrlParameter('page'); // aktuelle Backend-Page

    // Bei Focus - save oldvalue + color
	$('td[contenteditable=true]').on('focus', function(){
		$(this).data('oldvalue', $(this).text());
		$(this).data('color', $(this).css('color'));
	});

    // Handle Escape + Return
	$('td[contenteditable=true]').keypress(function(e){
		if (e.keyCode == 13 || e.keyCode == 27) {
			e.preventDefault();
			$(this).blur();
			window.getSelection().removeAllRanges();
		}
	});

    // Ajax-Request bei FocousOut
	$('td[contenteditable=true]').on('focusout', function(){
		var $sender = $(this);
		$fdata = {};
		$fdata['id'] = $(this).data('id');
		$fdata['lang'] = $(this).data('lang');
		$fdata['field'] = $(this).data('field');
		$fdata['value'] = $(this).text();
		$fdata['oldvalue'] = $(this).data('oldvalue');

		if ($fdata['value'] === $fdata['oldvalue'])
			return false;
		
		$($sender).addClass('xoutputfilter-loader');

		$.ajax({
			type: 'GET',
			url: '?page=' + $pageurl + '&func=setvalue',
			cache: false,
			data: $fdata,
			dataType: 'json',
			success: function(response)
			{
				if (response.error == '0') {
					$($sender).data('oldvalue', $fdata['value']);
					$($sender).text(response.value);
					$($sender).stop().css('color', '#090');
				} else {
					$($sender).data('oldvalue', $fdata['oldvalue']);
					$($sender).text($fdata['oldvalue']);
					$($sender).stop().css('color', '#c00');
					displayGrowl(response.msg, 3000);
				}

				$($sender).removeClass('xoutputfilter-loader');

				$($sender).fadeTo(200, 0.3, function(){
					$($sender).fadeTo(200, 1, function(){
						$($sender).fadeTo(200, 0.3, function(){
							$($sender).fadeTo(200, 1, function(){
								$($sender).css('color', $($sender).data('color'));
							})
						})
					})
				});
			}
		}).fail(function(jqXHR, textStatus) {
			for (i=0; i<3; i++) {
				$($sender).fadeTo(200, 0.3).fadeTo(100, 1.0);
			}
			$($sender).removeClass('xoutputfilter-loader');
			$($sender).text($fdata['oldvalue']);
			displayGrowl('Request failed: ' + textStatus, 10000);
		});

	});

    // Toggle status
	$('a.toggle').on('click', function(){
		var $sender = $(this);
        $($sender).addClass('xoutputfilter-wait');
        $($sender).fadeTo(200, 0.3);
        $fdata = {};
		$fdata['href'] = $($sender).attr('href');
		$fdata['oldvalue'] = $($sender).html();

        $.ajax({
			type: 'GET',
			url: $($sender).attr('href') + '&func=togglestatus',
			cache: false,
            data: $fdata,
			dataType: 'json',
			success: function(response)
			{
				if (response.error == '0') {
                    $($sender).html(response.value);
                    $($sender).attr('href', response.href);
				} else {
                    $($sender).html(response.oldvalue);
					displayGrowl(response.msg, 3000);
				}
                $($sender).removeClass('xoutputfilter-wait');
			}
		}).fail(function(jqXHR, textStatus) {
			displayGrowl('Request failed: ' + textStatus, 10000);
            $($sender).removeClass('xoutputfilter-wait');
		});
        $($sender).fadeTo(200, 1.0);
        return false;
	});

}); // end rex:ready
