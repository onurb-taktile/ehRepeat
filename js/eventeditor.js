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
"use strict";

/*stupid never called function to allow xgettext string detection*/
/*insert here plural form, undetected by xgettext
 or computed strings which will need translation.*/
function dummy() {
}

var monthnames = ["", __("January"), __("February"), __("March"), __("April"), __("May"), __("June"), __("July"), __("August"), __("September"), __("October"), __("November"), __("December")
];
var wdnames = [__("Sunday"), __("Monday"), __("Tuesday"), __("Wednesday"), __("Thursday"), __("Friday"), __("Satudray")
];
var switchOpts = {day:[__("day"), [__("Every|week"), __("the 1st|day"), __("the 2nd|day"),
		__("the 3rd|day"), __("the 4th|day"), __("the 5th|day")]],
	week: [__("week"), [__("Every|day"), __("the 1st|week"), __("the 2nd|week"),
		__("the 3rd|week"), __("the 4th|week"), __("the 5th|week")]]};


function debug(msg) {
	window.console && console.log(msg);
}


function rpt_display_validate() {
	/*Computes the whole ruleset*/
	var res = new xFrequency($("#event_startdt").val().slice(14) + " " + $("#event_startdt").val().slice(11, 13) + " * * * *");
	var exc = [];
	var lineCursor = -1;

	try {
		$('.rpt-display-line').each(function (i, e) {
			lineCursor = i;
			var freqS = $(this).find('input').val();
			var sign = (freqS.charAt(0) != '-');
			if (xFrequency.isFreqStr(freqS.slice(1)))
				res.op(sign, freqS.slice(1), true);
			else
				exc.push(freqS.slice(1));
		});
		$("#rpt_result").html("<h3>Freq:</h3><dl><dt>" + res.toString() + "</dt><dd>" + res.toHumanString(true) + "</dd></dl>\n<h3>Exc:</h3><dl id='rpt_result_exc'></dl>");
		exc.each(function (e, i) {
			$("#rpt_result_exc").append("<dt>" + e + "</dt><dd>" + e.toDSDate().toLocaleString() + "</dd>");
		});
		if (!$("#rpt_freq").prop('locked')) {
			$("#rpt-display").prop('locked', true);
			$("#rpt_exc").val(exc.join("\n")).change();
			$("#rpt_freq").val(res.toString()).change();
			$("#rpt-display").prop('locked', false);
		}
		return true;
	} catch (e) {
		debug(e.message());
		$(".rpt-display-line:nth(" + lineCursor + ")>button").css('border', 'solid red 2px').focus();
	}
	return false;
}

xFrequency.prototype.toHumanString = function (with_time) {
	var ret = "";
	var sd = this.isStar('days'),
			sm = this.isStar('months'),
			swd = this.isStar('weekdays'),
			sw = this.isStar('weeks');

	var time = (with_time ? sprintf(" at %2d:%2d", this.vals['hours'][0], this.vals['minutes'][0]) : "");

	if (sd && sm && swd && sw) {
		return sprintf("%s%s", __("Every day"), time);
	}

	if (sd) {
		ret = __("every day");
	} else {
		var days = this.get('days').copy();
		days.each(function (e, i) {
			days[i] = e.ordinalize();
		});
		ret = sprintf(__("on %s"), days.join3(', ', __(" & "), {gprefix: __("the |day")}));
	}


	if (!swd || !sw) {
		var which= (!sw && swd)?"week":"day";
		if (sd)
			ret = ""; //reset
		else
			ret += __(" and ");
		if (sw) {
			ret += sprintf(__(" on %s "), switchOpts[which][1][0]);
		} else {
			var weeks = this.get('weeks').copy();
			weeks.each(function (e, i) {
				weeks[i] = switchOpts[which][1][Number(e)];
			});
			ret += sprintf(__(" on %s "), weeks.join2(', ', __(" & ")));
		}
		if (swd) {
			ret += __("week");
		} else {
			var wd = this.get('weekdays').copy();
			wd.each(function (e, i) {
				wd[i] = wdnames[Number(e)];
			});
			ret += sprintf("%s", wd.join2(', ', __(" & ")));
		}
	}
	if (sm) {
		ret += __(" of the month");
	} else {
		var months = this.get('months').copy();
		months.each(function (e, i) {
			months[i] = monthnames[Number(e)];
		});
		ret += sprintf(__(" of %s"), months.join2(', ', __(" & ")));
	}
	return sprintf("%s%s", ret, time);
};


function rpt_display_line_set_message(line) {
	var freqstr = $(line).find("input").val();
	try {
		var freq = new xFrequency(freqstr.slice(1));
	} catch (e) {
		var freq = null;
	}
	var sign = (freqstr.charAt(0) == "+");
	var ret = "";
	if (!freq) {
		/*We have a date*/
		ret = sprintf("%s %s", sign ? __("Include :") : __("Exclude :"), freqstr.slice(1).toDSDate().toLocaleString());
	} else {
		ret = sprintf("%s %s", sign ? __("Include :") : __("Exclude :"), freq.toHumanString(false));
	}
	$(line).find("span").html(ret);
}


function rpt_display_del_line(line) {
	$(line).remove();
	$("#rpt-display>p:not(.rpt-display-template):odd")
			.removeClass("rpt-display-line-even")
			.addClass("rpt-display-line-odd");
	$("#rpt-display>p:not(.rpt-display-template):even")
			.removeClass("rpt-display-line-odd")
			.addClass("rpt-display-line-even");

	rpt_display_validate();
}

function rpt_display_add_line(auto) {
	var colorClass = "rpt-display-line-" + ($("#rpt-display>p:last").hasClass("rpt-display-line-even") ? "odd" : "even");
	$("#rpt-display>.rpt-display-template").clone().appendTo("#rpt-display");
	$("#rpt-display>p:last")
			.removeClass("rpt-display-template")
			.addClass("rpt-display-line")
			.addClass(colorClass).hide();
	$("#rpt-display>p:last>button").click(function () {
		rpt_display_del_line($(this).parent());
		$("#rpt-rule-add").removeAttr("disabled");
	});

	if (!auto)
		$("#rpt-rule-add").attr("disabled", "disabled");
	else {
		$("#rpt-display>p:last>button").removeAttr('disabled');
		$("#rpt-display>p:last").addClass("rpt-display-auto");
	}			
	$("#xered-mode").val("").change();
}

/* @func rpt_display_load
 * 
 * Loads the rpt_freq and rpt_exc into the rpt display.
 * 
 * @returns boolean success
 */
function rpt_display_load(replace_all) {
	try {
		$("#rpt-display>p.rpt-display-auto").remove();
		if (replace_all)
			$("#rpt-display>p.rpt-display-line").remove();

		var freq = new xFrequency($('#rpt_freq').val());
		var exc = $('#rpt_exc').val();
		exc = (exc && exc != "") ? exc.split('\n') : [];

		rpt_display_add_line(true);
		$("#rpt-display>p:last>input").val('+' + freq.toString());
		rpt_display_line_set_message($("#rpt-display>p:last"));
		$("#rpt-display>p:last").slideDown();
		exc.each(function (e, i) {
			rpt_display_add_line(true);
			$("#rpt-display>p:last>input").val("-" + e);
			rpt_display_line_set_message($("#rpt-display>p:last"));
			$("#rpt-display>p:last").slideDown();
		});
	} catch (e) {
		//$("#rpt-display>p.rpt-display-line").remove();
		$('#rpt_freq_error').html('<em>' + e.message + '</em>');
		$('#rpt_freq').addClass('error').focus();
		$('#entry-form').attr("submit-lock", "1");
		$("#rpt-slaves-dates").addClass('error').html(__("bad frequency string"));
		return false;
	}
	$('#rpt_freq_error').empty();
	$('#rpt_freq').removeClass('error');
	$('#entry-form').removeAttr("submit-lock");
	$("#rpt_freq").prop('locked', true);
	rpt_display_validate();
	$("#rpt_freq").prop("locked", false);
	return true;
}

function xered_validate() {

	if ($("#xered-action").val() == "" || $("#xered-mode").val() == "") {
		return false;
	}
	if ($("#xered-action").val() == "date" && $("#xered-ac-date").val() == "")
		return false;

	var sign = ($("#xered-mode").val() == "+");
	var value = "";
	var message = "";
	if ($("#xered-action").val() == "date") {
		value = $("#xered-ac-date").val() + ' ' + $("#event_startdt").val().slice(11);
	} else {
		value = $("#event_startdt").val().slice(14) + " "
				+ $("#event_startdt").val().slice(11, 13) + " "
				+ $("#xered-ac-days").val() + " "
				+ $("#xered-ac-months").val() + " "
				+ $("#xered-ac-weekdays").val() + " "
				+ $("#xered-ac-weeks").val();
	}
	$("#rpt-display>p:last>input").val((sign ? "+" : "-") + value);
	rpt_display_line_set_message($("#rpt-display>p:last"));

	if (rpt_display_validate()) {
		$("#rpt-display>p:last").slideDown();
		$("#rpt-display>p:last>button").removeAttr("disabled");
		return true;
	}
	return false;
}
/* @var xeredModeToggles
 * @type JSON
 * This variable contains some object related to #xered-mode possible values.
 * Each value has an object which contains the selectors to affect on value change
 * show: is an array of selectors to show
 * hide: is an array of selectors to hide
 * disabled: is an array of selectors to disable
 */
var xeredModeToggles = {
	'fn': {enable: function (t) {
			$(t).removeAttr("disabled");
		}, disable: function (t) {
			$(t).attr("disabled", "disabled");
		}},
	'*': {hide: "#xered-mode~*", enable: "#xered-mode~*:not(button)", change: "#xered-action"},
	'': {show: "#xered-action", disable: "#xered-action"},
	'+': {show: ["#xered-action", "#xered-validate","#xered-action>option[value='all']"], hide: "#xered-action>option[value='date']", disable: "#xered-validate"},
	'-': {show: ["#xered-action", "#xered-validate", "#xered-action>option[value='date']"],hide:"#xered-action>option[value='all']", disable: "#xered-validate"}
};

/* @var xeredActionToggles
 *
 * @type JSON
 * For each value of #xeredAction, provides a callback.
 * if none is specified, the default callback is called.
 * the * callback is called for any action first
 * to disable the call for default, value can be set to null
 */
var xeredActionToggles = {
	'*': function (mode, action) {
		$("#xered-action").nextUntil("button").hide();
		if (action != "")
			$("#xered-action + span").show();
		$("#xered-validate").attr("disabled","disabled");
		$("#xered-action~select").find("option:first").each(function(){
			$(this).attr("selected","selected");
		});
		$("#xered-ac-date").val("");
	},
	'': function () {
	}, //to disable default call
	'week': function (mode, action) {
		if ($("#xered-ac-weeks>option:first").html().trim() == "") {
			$("#xered-ac-weeks").change();
		}
		$("#rpt-rules-editor>[id^='xered-ac-week']").show().first().focus();
	},
	'all': function(mode,action) {
		$("#xered-ac-all").show();
		$("#xered-validate").removeAttr("disabled");
	},
	'default': function (mode, action) {
		$("#xered-ac-" + action).show().first().focus();
	}
};

/* @var xeredValChecker
 * @type JSON Object
 * For each value of #xeredAction, provides a callback for value checking.
 * If none specified & default is set, default callback will be used.
 * 
 */
var xeredValChecker = {
	'default': function (mode) {
		return ($(this).val() != "");
	},
	'week': function (mode) {
		return !($("#xered-ac-weeks").val() == $("#xered-ac-weekdays").val() == "*");
	},
	'all': function (mode) {
		return true;
	}
};

function xered_init() {
	$("#xered-action").attr("disabled","disabled");
	$("#xered-mode").change(function () {

		//Use the xeredModeToggles state machine
		["*", $(this).val()].each(function (mode, i, x) {
			for (var action in x[mode]) {
				var target = isArray(x[mode][action]) ? x[mode][action] : [x[mode][action]];
				var fn = x.fn[action] || function (T, I, A) {
					$(T)[A]();
				};
				target.each(function (t, i, f, a) {
					f(t, i, a);
				}, fn, action);
			}
		}, xeredModeToggles);
	});

	$("#xered-action").change(function () {
		var action = $(this).val();
		var mode = $("#xered-mode").val();
		["*",(xeredActionToggles[action]?action:"default")].each(function(e,i,m,a){
			xeredActionToggles[e](m,a);
		},mode,action);
	});


	$("#xered-action~*[id^='xered-ac-']").change(function () {
		var action=$("#xered-action").val();
		var fn=xeredValChecker[action] || xeredValChecker.default;
		if(fn.apply(this,[action])){
			$("#xered-validate").removeAttr("disabled").focus();
			if($(this).attr('id')=='xered-ac-weeks'){
				$("#xered-ac-weekdays").focus();
			}
		}else{
			$("#xered-validate").attr("disabled","disabled");
		}
	});


	$("#rpt-rule-add").click(function () {
		rpt_display_load(true);
		rpt_display_add_line();
		//reset all controls
		$("#rpt-rules-editor").slideDown("fast", function () {
			$("#xered-mode").focus();
		});

	});
	$("#xered-validate").click(function () {
		if (xered_validate()) {
			$("#rpt-rules-editor").slideUp();
			$("#xered-mode").val("").change();
			$("#xered-action").val("").change();
			$("#rpt-rule-add").removeAttr("disabled");
		}
	});

	/*function to change the option labels for day or week*/

	var switchOptions = function (which) {
		switchOpts[which][1].each(function (elem, i) {
			$("#xered-ac-weeks>option:nth(" + i + ")").html(elem);
		});
		$("#xered-ac-weekdays>option:first").html(switchOpts[which][0]);
	};

	$("#xered-ac-weeks,#xered-ac-weekdays").change(function () {
		var w=($("#xered-ac-weeks")[0].selectedIndex > 0);
		var wd=($("#xered-ac-weekdays")[0].selectedIndex > 0);
		switchOptions((w && !wd)?"week":"day");
	});

	$("select[id^='xered-ac-']").removeAttr("name");

	$("#xered-ac-date").datepicker({
		regional: "fr",
		showButtonPanel: true,
		changeMonth: true,
		changeYear: true,
		dateFormat: "yy-mm-dd"
	});

	$("#rpt_freq,#rpt_exc").change(function () {
		if (!$("#rpt_active")[0].checked || $("#rpt-display").prop('locked'))
			return;
		rpt_display_load(true);
	});
	rpt_display_load(true);
}


$(document).ready(function () {
	var start = function () {
		xered_init();
	};
	if ($("#rpt_active")[0].checked) {
		start();
	} else {
		$("#rpt_active").one("change", start);
	}
});

