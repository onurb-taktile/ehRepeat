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

var xFrequencyLimits={minutes: [0, 59, 5], hours: [0, 23, 1], days: [1, 31, 1], months: [1, 12, 1], weekdays: [0, 6, 1], weeks: [1, 5, 1]};


/*Class xFrequency to handle all frequency editing stuff*/

function xFrequency(s) {
	this.vals = {};

	//Methods 
	this.set = function (name, val) {
		if (name === undefined)
			throw "xFrequency.set : no name provided";
		if (!xFrequencyLimits[name])
			throw "xFrequency.set : no field named " + name + ".";
		this.vals[name] = this.vals[name] || [];
		
		if (val[0] == '*'){//array ['*'] or string '*'
			this.vals[name] = Array.range(xFrequencyLimits[name]);
			return;
		}else if (typeof val == 'string') {
			val = val.split(',');
		}
		if (isArray(val)) {
			if (val.inLimits(xFrequencyLimits[name], true)) {
				this.vals[name] = val.unique();
			} else {
				throw "xFrequency.set : bad values " + val + " for field " + name + ".";
			}
		}
	};

	this.parseObj = function (o) {
		var e;
		try {
			for (var n in o)
				this.set(n, o[n]);
		} catch (e) {
			message = 'xFrequency() : error parsing the frequency object ' + o + '. Reason : \n\t' + e.message;
			window.console && console.log(message);
			throw message;
		}
	};

	this.parseStr = function (s) {
		var pat = /^\s*(\*|[0-5]?\d) (\*|[0-2]?\d) (\*|[0-3]?\d(?:,[0-3]?\d)*) (\*|[0-1]?\d(?:,[0-1]?\d)*) (\*|[0-6](?:,[0-6])*) (\*|[1-5](?:,[1-5])*)\s*$/;
		var e;
		try {
			var matches = pat.exec(s);
			if (!matches){
				throw 'There is an error in the string. Please respect the format.';
			}
			this.set('minutes', matches[1]);
			this.set('hours', matches[2]);
			this.set('days', matches[3]);
			this.set('months', matches[4]);
			this.set('weekdays', matches[5]);
			this.set('weeks', matches[6]);
		} catch (e) {
			var message = 'xFrequency() : error parsing the frequency string ' + s + '. Reason : \n\t' + e.message;
			window.console && console.log(message);
			throw message;
		}
	};

	this.toString = function () {
		var res = "";
		for (var n in xFrequencyLimits) {
			if (!this.vals[n])
				return false;
			if (this.vals[n].length == (xFrequencyLimits[n][1] - xFrequencyLimits[n][0] + 1))
				res += '* ';
			else
				res += this.vals[n].join(',') + " ";
		}
		return res.trim();
	};

	//Constructor

	try {
		if (!s) {
			for (var n in limits)
				this.set(n, '*');
			return this; //empty object constructor
		}

		if (typeof s == "string") {
			//string constructor
			this.parseStr(s);
		} else {
			//object constructor
			this.parseObj(s);
		}
	} catch (e) {
		return null;
	}

}
