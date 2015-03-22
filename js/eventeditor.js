//nogettextization please
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
	__("day") + __("week");
}

/*updates #rpt_freq according to the #rpt_freq_adv_* combos selections*/
function advanced_freq_update() {
	var freq = "";
	var freqs = {
		days: "",
		months: "",
		weekdays: "",
		weeks: ""
	};

	for (var field in freqs) {
		freqs[field] = [];
		$("#rpt_freq_adv_" + field + ">option:selected").each(function (i, elem) {
			var v = $(elem).val();
			freqs[field][i] = (v == "*") ? "*" : parseInt(v, 10);
		});
		freq += " " + freqs[field].join(',');
	}
	freq = $("event_startdt_tpm").val().padZero(2) + " " + $("event_startdt_tph").val().padZero(2) + freq;
	$("#rpt_freq").val(freq).change();
}

function debug(msg) {
	console.log(msg);
}

if (dc_messages == undefined)
	var dc_messages = {};

/*
 * simple xevent editor functions
 *
 * This editor works with some special frequency strings :
 * they can start with + or -. - means an exclusion string.
 * they cannot contain any set except *.
 *
 */

//sxe_compute : computes all the lines to get frequency & exceptions strings
function sxe_compute() {
	//first, we have to dispatch the lines to freq or exc type.
	//if a exc line has only one of the w wd d or M parameter set, it will become
	// a nfreq which will be computed with the freqs to create the freq string.
	var xFrequencyLimits = xFrequencyLimits || {};
	var freq = [];
	var nfreq = [];
	var exc = [];
	$("#rpt-freq-simple>div[id|='sxe-line']").each(function () {
		try {
			var vals = sxe_parse($(this).find('input').val());
			var numjokers = 0;
			for (var i = 3; i <= 6; i++)
				if (vals[i] == '*')
					numjokers++;
			if (vals[0] == '+')
				freq.push(vals);
			else if (numjokers == 3)
				nfreq.push(vals);
			else
				exc.push(vals);
		} catch (e) {
			debug(sprintf("Something bad happended in sxe_compute while working with %s."), $(this).val());
			return false;
		}
	});
	//Now all strings should be splitted between freq & exc
	// we compute a result array for all freqs (creation of sets) working field by
	// field
	var resfreq = [];
	for (var i = 1; i <= 6; i++) {
		var fieldres = [];
		freq.each(function (freq, f) {
			var field = freq[f][i];
			if (field == '*') {
				fieldres = [];
				fieldres.range(xFrequencyLimits[i - 1][0], xFrequencyLimits[i - 1][1], xFrequencyLimits[i - 1][2]);
				return false;
				//break loop
			} else {
				fieldres.push(field);
			}
		});
		if (i > 2)//disabled because hour & minutes can't be masked
			nfreq.each(function (nfreq, f) {
				var field = nfreq[f][i];
				if (field != '*') {
					var idx = fieldres.indexOf(field);
					fieldres.splice(idx, 1);
				}
			});

		fieldres.unique();

		if (fieldres.length == ((xFrequencyLimits[i - 1][1] - xFrequencyLimits[i - 1][0] + 1) / xFrequencyLimits[i - 1][2]))
			fieldres = ['*'];
		resfreq.push(fieldres.join(','));
	}

	exc.each(function (exc, i) {
		exc[i] = exc[i].join(' ');
	});

	//Now we can update rpt_freq
	$("#rpt_freq").val(resfreq.join(' ')).change();
	$("#rpt_exc").val(exc.join('\n')).change();
	return true;
}

function sxe_parse(string) {
	var vals = /([\-\+]?)(\d\d?) (\d\d?) (\*|\d\d?) (\*|\d\d?) (\*|\d) (\*|\d)/.exec(string);
	if (!vals)
		throw sprintf(__("Bad string to parse: %s"), string);
	else {
		vals.shift();
		//remove the whole match, won't need it
		vals.each(function (vals, i) {
			if (vals[i] == '*' || vals[i] == "+" || vals[i] == "-")
				return;
			vals[i] = Number(vals[i]);
		});
		return vals;
	}
}

function sxe_createstr(vals, type) {
	vals.each(function (vals, i) {
		if (vals[i] == '*' || vals[i] == "+" || vals[i] == "-")
			return;
		vals[i] = Number(vals[i]);
	});
	var str = vsprintf("%s %s %s %s %s %s", vals.slice(1));
	if (type)
		str = (vals[0] == '-' ? '-' : '+') + str;
	return str;
}


function rpt_display_validate() {
	/*Computes the whole ruleset*/

	return true;
}

function rpt_display_line_set_message(line) {
	var freqstr = $(line).find("input").val();
	var freq = new xFrequency(freqstr.slice(1));
	var sign = (freqstr.charAt(0) == "+");
	var ret = "";
	if (!freq) {
		/*We have a date*/
		ret = sprintf("%s %s", sign ? __("Include") : __("Exclude"), Date(freqstr.slice(1)).toLocaleString());
	} else {
		ret = sprintf("%s %s", sign ? __("Include") : __("Exclude"), freq.toString());
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

function rpt_display_add_line() {
	var colorClass = "rpt-display-line-" + ($("#rpt-display>p:last").hasClass("rpt-display-line-even") ? "odd" : "even");
	$("#rpt-display>.rpt-display-template").clone().appendTo("#rpt-display");
	$("#rpt-display>p:last")
			.removeClass("rpt-display-template")
			.addClass(colorClass).hide();
	$("#rpt-display>p:last>button").click(function () {
		rpt_display_del_line($(this).parent());
		$("#rpt-rule-add").removeAttr("disabled");
	});

	$("#rpt-rule-add").attr("disabled", "disabled");
}

function xered_validate() {

	if ($("#xered-action").val() == "" || $("#xered-mode").val() == "") {
		return false;
	}
	if($("#xered-action").val()=="date" && $("#xered-ac-date").val()=="")
		return false;
	
	var sign = ($("#xered-mode").val() == "+");
	var value = "";
	var message = "";
	if ($("#xered-action").val() == "date") {
		value = $("#xered-ac-date").val();
	} else {
		value = $("#event_startdt_tpm").val() + " "
				+ $("#event_startdt_tph").val() + " "
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
var xeredModeToggles={
	'':{show:[],hide:["#xered-action+*:not(button)"],disable:["#xered-action"]},
	'+':{on:["#xered-action+*:not(#xered-ac-date)"],off:["#xered-ac-date"],disabled:["#xered-action>option[value='date']"]},
	'-':{on:[],off:[]}
};

/* @var xeredActionToggles
 *
 * @type JSON
 * For each value of #xeredAction, provides a callback.
 * if none is specified, the default callback is called.
 * the * callback is called for any action first
 * to disable the call for default, value can be set to null
 */
var xeredActionToggles={
	'*': function(mode,action){
		$("#xered-action").nextUntil("button").hide();
		if(action!="") $("#xered-action + span").show();
	},
	'': function(){}, //to disable default call
	'week': function (mode,action) {
		if($("#xered-ac-weeks>option:first").html().trim() == ""){
			$("#xered-ac-weeks").change();
		}
		$("#rpt-rules-editor>[id^='xered-ac-week']").show().first().focus();
	},
	'default': function (mode,action) {
		$("#xered-ac-" + action).show().first().focus();
	}	
};

/* @var xeredValChecker
 * @type JSON Object
 * For each value of #xeredAction, provides a callback for value checking.
 * If none specified & default is set, default callback will be used.
 * 
 */
var xeredValChecker={
	'default':function(mode){return ($(this).val()!="");},
	'week':function(mode){return !($("#xered-ac-weeks").val()==$("#xered-ac-weekdays").val()=="*");},
	'all':function(mode){return true;}
};

function xered_init() {
	$("#xered-action").prop("disabled", true);
	$("#xered-mode").change(function () {
		$("#xered-action").prop("disabled", ($(this).val() == "")).focus();
		
	});
	$("#xered-action").change(function () {
		var a=$(this).val();
		var m=$("#xered-mode").val();
		xeredActionToggles['*'](m,a);
		var cb=xeredActionToggles[a] || ((xeredActionToggles!==null) && xeredActionToggles['default']);
		cb && cb(m,a);
	});

	$("#rpt-rules-editor>[id^='xered-ac-']:not([id^='xered-ac-week'])").change(function () {
		$("#rpt-rules-editor>button").focus();
	});

	$("#rpt-rules-editor>[id^='xered-ac-week']").change(function () {
		$(this).next().focus();
	});

	$("#rpt-rule-add").click(function () {
		rpt_display_add_line();
		//reset all controls
		$("#rpt-rules-editor>select")
				.find("option:first")
				.each(function () {
					$(this).attr("selected", "selected");
				});
		$("#rpt-rules-editor>input").val("");
		$("#xered-action").nextUntil("button").hide();
		$("#rpt-rules-editor").slideDown("fast", function () {
			$("#xered-mode").focus();
		});

	});
	$("#xered-validate").click(function () {
		if (xered_validate()) {
			$("#rpt-rules-editor").slideUp();
			$("#rpt-rule-add").removeAttr("disabled");
		}
	});

	/*function to change the option labels for day or week*/
	var switchOpts = null;

	var switchOptions = function (which) {
		/*Do the loading only the first time*/
		switchOpts = switchOpts || {day: [__("Every|week"), __("the 1st|day"), __("the 2nd|day"),
				__("the 3rd|day"), __("the 4th|day"), __("the 5th|day")],
			week: [__("Every|day"), __("the 1st|week"), __("the 2nd|week"),
				__("the 3rd|week"), __("the 4th|week"), __("the 5th|week")]};

		switchOpts[which].each(function (elem, i) {
			$("#xered-ac-weeks>option:nth(" + i + ")").html(elem);
		});
		$("#xered-ac-weekdays>option:first").html(__(which));
	};

	$("#xered-ac-weeks").change(function () {
		switchOptions(this.selectedIndex > 0 ? "week" : "day");
	});

	$("#xered-ac-weekdays").change(function () {
		switchOptions(this.selectedIndex > 0 ? "day" : "week");
	});

	$("select[id^='xered-ac-']").removeAttr("name");

	$("#xered-ac-date").datepicker({
		regional: "fr",
		showButtonPanel: true,
		changeMonth: true,
		changeYear: true,
		dateFormat: "yy-mm-dd"
	});
}

//modifies .rpt-editor-help divs to make them foldable
function helpify() {
	$(".rpt-editor-help").each(function () {
		$(this).wrapInner('<div class="rpt-editor-help-content"></div>');
		$(this).prepend('<div class="rpt-editor-help-button"><a href="#">-</a></div></div>');
		$(this).find("a").click(function () {
			if ($(this).text() == "+") {
				$(this).parent().next().slideDown();
				$(this).text("-");
			} else {
				$(this).parent().next().slideUp();
				$(this).text("+");
			}
			return false;
		});
	});


}

$(document).ready(function () {
	xered_init();

	helpify();
}
);

