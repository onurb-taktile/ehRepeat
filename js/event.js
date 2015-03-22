////nogettextization please
/* -- BEGIN LICENSE BLOCK ----------------------------------
 *
 * This file is part of xEventHandler, a plugin for Dotclear 2.
 *
 * Copyright(c) 2015 Bruno Avet
 *
 * Licensed under the GPL version 2.0 license.
 * A copy of this license is available in LICENSE file or at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * -- END LICENSE BLOCK ------------------------------------*/

//

/*stupid never called function to allow xgettext string detection*/
/*insert here plural form, undetected by xgettext 
 or computed strings which will need translation.*/
function dummy() {
} 

function replaceEndtByDuration() {
	if (!window.rpt_replace_enddt)
		return;
	$("[id^='endt_duration_']").each(function () {
		$(this).remove();
	});

	var rpt_minute_step = window.rpt_minute_step || 1;

	var enddt_label = $("#event_enddt").parent();
	var enddt = $("#event_enddt").detach();
	enddt_label.wrapInner('<span id="event_enddt_title"></span>').append(enddt);
	var day=__("day");
	$("<div class='onelinedp'><input id='enddt_duration_d' size='2'/> <span id='enddt_duration_d_days'>"+day+"</span> <input id='enddt_duration_h' size='2' /><span id='enddt_duration_h_hours'>"+__(':|hour separator')+"</span><input id='enddt_duration_m' size='2'/></div>").insertBefore("#event_enddt");
	
	var spinnerstop = function (e, ui) {
		$(this).change();
	};
	

	$('#enddt_duration_d').spinner({min: 0, max: 99, step: 1, page: 7, stop: spinnerstop});
	$('#enddt_duration_h').spinner({min: -1, max: 24, step: 1, page: 6, stop: spinnerstop});
	if(rpt_minute_step<60)
		$('#enddt_duration_m').spinner({min: -rpt_minute_step, max: 60, step: rpt_minute_step, stop: spinnerstop});
	else{
		$('#enddt_duration_m').hide();
		$('#enddt_duration_h_hours').text(__('hr|hour symbol'));
	}
	if($('#enddt_duration_h').val()==-1)
		$('#enddt_duration_h').val(0);

	$("#event_enddt_title").text(__("Duration :"));
//	$("#event_enddt,#event_enddt + img").hide();


	$("#event_enddt").change(function () {
		var startdt = $("#event_startdt").val().toDSDate();
		var enddt = $("#event_enddt").val().toDSDate();
		if (!startdt || !enddt){
			$(this).val('');
			return true;
		}
		var duration = enddt - startdt;
		var H = 3600000;
		var d = Math.floor(Number(duration) / (H * 24));
		var h = Math.floor((Number(duration) % (H * 24)) / H);
		var m = Math.floor(Number(duration) % H / (H / (60 / rpt_minute_step))) * rpt_minute_step;

		$(this).prop('locked', 'true');
		$("#enddt_duration_d").val(d<0?0:d).change();
		$("#enddt_duration_h").val(h<0?0:h);
		$("#enddt_duration_m").val(m<0?0:m);
		$(this).removeProp('locked');
	}).change();

	$("#enddt_duration_d").change(function () {
		$("#enddt_duration_d_days").text(__('day', 'days', $(this).val()));
	});

	$("#event_startdt").on('change', startdtChange=function(){
		console.log("dt: chuis toujours là");
		var dt=$(this).val().toDSDate();
		if(dt!==null && $('#event_enddt').val()==''){
			//we initialize enddt with 1h duration.
			var enddt=new Date(Number(dt)+3600000);
			$("#event_enddt").val(enddt.toDString()).change();
			$(this).off('change',startdtChange);
			console.log("dt: je m'casse");
		}
	});


	$("[id^='enddt_duration'],#event_startdt").change(function () {
		if ($("#event_enddt").prop('locked') || $("#event_startdt").val()=='')
			return;
		var startdt = $("#event_startdt").val().toDSDate();
		h = Number($("#enddt_duration_h").val());
		m = Number($("#enddt_duration_m").val());
		d = Number($("#enddt_duration_d").val());
		if((d==0)&&(h<0))
			h=0;
		if((d==0)&&(h==0)&&(m<0))
			m=0;
		var H=3600000;
		var D=24*H;
		var M=H/60;
		var enddt = new Date(Number(startdt) + d * D + h * H + m * M);
		$("#event_enddt").val(enddt.toDString()).change();
	});
}

$(document).ready(function () {

	/*Toggles the xevent editor display*/
	$('#rpt_active').change(function () {
		if (this.checked) {
			$("#ehrepeat-editor-content").slideDown();
		} else {
			$("#ehrepeat-editor-content").slideUp();
		}
	}).change();	

	$("#entry-form").submit(function () {
		debug("#entry-form change");
		if ($(this).attr("submit_lock"))
			return false;
	});

	//Get number of slaves for update / create messages
	if ($("#id").length > 0 && $('#rpt_active').prop('checked'))
		$.get('services.php', {
			f: 'countSlaves',
			master_id: $('#id').val()
		}, function (rsp) {
			if ($(rsp).attr('status') == 'failed')
				return false;
			var nbSlaves = $(rsp).find('value').text();
			if (nbSlaves.length == 0)
				return;
			var message = $('.message').html() + nbSlaves;
			$('.message').html(message);
		});

	//Change the event_enddt to a duration info
	replaceEndtByDuration();

	/*On #event_startdt value change, we update the #rpt_freq string according to the
	 * new hh:mm if it is not protected.*/
	$('#event_startdt').change(function (event) {
		if (!$('#rpt_active').prop('checked'))
			return;
		debug("#event_startdt change");
		var mask = /^([-0-9]{10}) ([^:]+):([^\s]+)$/;
		var matches = mask.exec($(this).val());
		$(this).attr('locked', 'locked');
		if (matches) {
			var pat = /^(?:\*|\d\d?) (?:\*|\d\d?) ((?:[^\s]+ [^\s]+ [^\s]+ [^\s]+))$/;
			var matches2 = pat.exec($("#rpt_freq").val());
			if (matches2) {
				var m = String(Number(matches[3]));
				var h = String(Number(matches[2]));

				$("#rpt_freq:not([protected])").val(m + " " + h + " " + matches2[1]).change();
			}
		}
		
		$(this).removeAttr('locked');
	}).change();


	$('#rpt_freq').change(function (event) {
		if (!$('#rpt_active').prop('checked'))
			return;
		debug('#rpt_freq change');
		var e;
		try {
			this.freq = new xFrequency($(this).val());
		} catch (e) {
			$('#rpt_freq_error').html('<em>' + e.message + '</em>');
			$('#rpt_freq').addClass('error').focus();
			$('#entry-form').attr("submit-lock", "1");
			$("#rpt-slaves-dates").addClass('error').html(__("bad frequency string", "bad frequency strings", 1));
			return false;
		}
		$('#rpt_freq_error').empty();
		$('#rpt_freq').removeClass('eProprror');
		$('#entry-form').removeAttr("submit-lock");

		//Update the slave dates
		$.get('services.php', {
			f: 'computeDates',
			freq: $(this).val(),
			startdt: $('#event_startdt').val()
		}, function (rsp) {
			if ($(rsp).attr('status') == 'failed') {
				$("rpt-slaves-dates").addClass('error').html($(rsp).find('value').txt());
				return;
			}
			$("#rpt-slaves-title>span").text($(rsp).find('date').length);
			var res = "<ol>";
			$(rsp).find('dates').find('date').each(function () {
				res += "<li>" + $(this).text() + "</li>";
			});
			res += "</ol>";
			$("#rpt-slaves-dates").removeClass('error').html(res);
		});
	}).change();
});
