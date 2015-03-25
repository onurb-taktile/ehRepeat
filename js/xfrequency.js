var xFrequencyLimits = {minutes: [0, 59, rpt_minute_step], hours: [0, 23, 1], days: [1, 31, 1], months: [1, 12, 1], weekdays: [0, 6, 1], weeks: [1, 5, 1]};


/*Class xFrequency to handle all frequency editing stuff*/

function xFrequency(s) {
	this.vals = {minutes: [], hours: [], days: [], months: [], weekdays: [], weeks: []};

	//Methods 
	this.set = function (name, val) {
		if (name === undefined)
			throw "xFrequency.set : no name provided";
		if (!this.vals[name])
			throw "xFrequency.set : no field named " + name + ".";
		if (val[0] == '*') {//array ['*'] or string '*'
			this.vals[name] = Array.range(xFrequencyLimits[name]);
			return;
		} else if (typeof val == 'string') {
			val = val.split(',')
			val.each(function (e, i) {
				val[i] = Number(e);
			});
		}
		if (isArray(val)) {
			if (val.inLimits(xFrequencyLimits[name], true)) {
				this.vals[name] = val.unique();
			} else {
				throw "xFrequency.set : bad values " + val + " for field " + name + ".";
			}
		}
	};

	this.get = function (name) {
		if (name === undefined)
			throw "xFrequency.get : no name provided";
		if (!this.vals[name])
			throw "xFrequency.get : no field named " + name + ".";
		return this.vals[name] || [];
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
			if (!matches) {
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

	this.isStar = function (name) {
		if (name === undefined)
			throw "xFrequency.isStar : no name provided";
		if (!xFrequencyLimits[name])
			throw "xFrequency.isStar : no field named " + name + ".";
		return (this.vals[name].isFullRange(xFrequencyLimits[name]));
	};

	this.toString = function () {
		var res = "";
		for (var n in this.vals) {
			if (!this.vals[n])
				return false;
			if (this.vals[n].length == 0)
				res += '-';
			else if (this.isStar(n))
				res += '* ';
			else
				res += this.vals[n].join(',') + " ";
		}
		return res.trim();
	};

	/* Adds a value v to a given field. v is an array of digits*/
	this.addf = function (n, v) {
		if (n == undefined || !this.vals[n] || v == undefined || !isArray(v))
			throw 'xFrequency.addf : wrong parameters.';
		if (!(v.isFullRange(xFrequencyLimits[n]))) {
			if (this.isStar(n))
				this.vals[n].empty();
			this.vals[n] = this.vals[n].concat(v).unique();
		}
	};
	/* Dels the value v from a given field, v is a sorted array of digits - a xFrequency.vals field.*/
	this.delf = function (n, v) {
		if (n == undefined || !this.vals[n] || v == undefined || !isArray(v))
			throw 'xFrequency.addf : wrong parameters.';
		if (!(v.isFullRange(xFrequencyLimits[n]))) {
			v.each(function (e, i, T) {
				var idx = T.vals[n].indexOf(e);
				if (idx != -1) {
					T.vals[n].splice(idx,1);
				}
			}, this);
		}
	};

	/*checks if this.vals[n] contains the values of nsorted array v.
	 * o is a boolean, if set returns true if this.vals[n] has one of the values of v
	 */
	this.has = function (n, v, o) {
		if (n == undefined || !this.vals[n] || v == undefined || !isArray(v))
			throw 'xFrequency.has : wrong parameters.';
		for (var i = 0; i < v.length; i++) {
			var idx = this.vals[n].indexOf(v[i]);
			if (idx == -1 && !o)
				return false;
			else if (idx > -1 && o)
				return true;
		}
		if (o)
			return false;
		return true;
	};

	this.op = function (sign, operand,ignore_time) {
		var ignore_time=ignore_time ||false;
		if (operand === undefined)
			return;
		if (typeof op != 'xFrequency')
			operand = new xFrequency(operand);
		if (!sign) {
			var m = operand.isStar('months'),
					d = operand.isStar('days'),
					wd = operand.isStar('weekdays'),
					w = operand.isStar('weeks');
			var count = (m ? 1 : 0) + (d ? 1 : 0) + (wd ? 1 : 0) + (w ? 1 : 0);
			if (count == 4)
				return;
			else if (!((m || this.has('months', operand.get('months'))) &&
					(d || this.has('days', operand.get('days'))) &&
					(wd || this.has('weekdays', operand.get('weekdays'))) &&
					(w || this.has('weeks', operand.get('weeks')))))
				return;
		}
		for (var field in this.vals) {
			if(ignore_time && (field=='minutes' || field=='hours'))
				continue;
			if (sign)
				this.addf(field, operand.get(field));
			else
				this.delf(field, operand.get(field));
		}
	};

	//Constructor

	if (!s) {
		for (var n in xFrequencyLimits)
			this.set(n, '*');
	}

	if (typeof s == "string") {
		//string constructor
		this.parseStr(s);
	} else {
		//object constructor
		this.parseObj(s);
	}
}

xFrequency.isFreqStr = function (s) { //static method to check a freq string
	var pat = /^\s*(\*|[0-5]?\d) (\*|[0-2]?\d) (\*|[0-3]?\d(?:,[0-3]?\d)*) (\*|[0-1]?\d(?:,[0-1]?\d)*) (\*|[0-6](?:,[0-6])*) (\*|[1-5](?:,[1-5])*)\s*$/;
	return pat.test(s);
};
