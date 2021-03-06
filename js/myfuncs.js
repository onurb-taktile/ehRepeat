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


/*Convenient functions to add 0 in front of a number*/

String.prototype.padZero = function (len, c) {
	var s = '',
			c = c || '0',
			len = (len || 2) - this.length;
	while (s.length < len)
		s += c;
	return s + this;
};

Number.prototype.padZero = function (len, c) {
	return String(this).padZero(len, c);
};

/*Convert a number to english ordinal (from 1 to 31)*/

String.prototype.ordinalize = function () {
	if (isNaN(this))
		return this;
	var n = Number(this);
	n = Math.round(Math.abs(n)); //get sure we have a positive integer
	switch (n % 10) {
		case 1:
			if (n % 100 == 11)
				return n + "th";
			return n + "st";
			break;
		case 2:
			if (n % 100 == 12)
				return n + "th";
			return n + "nd";
			break;
		case 3:
			if (n % 100 == 13)
				return n + "th";
			return n + "rd";
			break;
		default:
			return n + "th";
	}
};

Number.prototype.ordinalize =	function () {
	return String(this).ordinalize();
};


/*array methods to get php like array behaviors*/
Array.prototype.copy = function () {
	var ret = [];
	this.each(function (e, i) {
		ret[i] = e;
	});
	return ret;
}

//static version
Array.copy = function (arr) {
	if (arr == undefined || !isArray(arr))
		return [];
	return arr.copy();
}

//static function
Array.range = function (start, end, step) {
	var ret = [];
	if (isArray(start)) {
		if (start.length >= 2) {
			step = (start[2 !== undefined]) ? start[2] : 1;
			end = Number(start[1]);
			start = Number(start[0]);
		} else {
			start = Number(start[0]);
		}
	}
	if (start === undefined || end === undefined)
		throw "Array.range : can't set a range without limits. usage : Array.range(start,end,step=1) or Array.range([start,end,step=1])";
	if (step === undefined)
		step = 1;
	for (var i = start; i <= end; i += step) {
		ret.push(i);
	}
	return ret;
};

/*
 * checks if an array is a full range as specified with the range arg
 * @param array range : [minval,maxval,step]
 * @returns boolean
 */
Array.prototype.isFullRange = function(range){
	if(!range || !isArray(range) || range.length<2 || range.length>3)
		throw "Array.isFullRange : wrong arguments "+range;
	var step=range[2] || 1;
	var tmp=this.unique();
	return !((tmp.length!=Math.floor((range[1]-range[0])/step)+1) || (tmp[0]!=range[0]) || (tmp[tmp.length-1]!=range[1]));
};

Array.prototype.sortn = function (desc) {
	var desc = desc || false;
	this.sort(function (a, b) {
		if (desc)
			return b - a;
		else
			return a - b;
	});
};

//static version
Array.sortn = function (arr) {
	if (!arr)
		return null;
	return arr.sortn();
};

Array.prototype.unique = function () {
	if (isNaN(this[0]))
		this.sort();
	else
		this.sortn();
	for (var i = this.length; i > 0; i--)
		if (this[i] == this[i - 1])
			this.splice(i);
	return this;
};

Array.unique = function (arr) {
	return (arr && arr.unique()) || null;
};

/*
 * Loops an array, executing a callback for each element.
 * invocation : ["an","array"].each(function(e,i,d1,d2){
 *		... callback body ...
 * },data1,data2);
 * if the callback returns false, the loop stops.
 * in the callback, the this object is the entire array.
 * 
 * @param {type} cb callback : function(e, i, ...data)
 * @param mixed data to pass to callback
 * @returns {Array.prototype|Boolean}
 */
Array.prototype.each = function (cb) {
	var ret = false;
	var args=[].slice.call(arguments,1);
	if (cb !== undefined) {
		ret = true;
		for (var i = 0; i < this.length; i++) {
			if (cb.apply(this,[this[i],i].concat(args)) === false) {
				ret = false;
				break;
			}
		}
	}
	return ret ? this : false;
};




Array.prototype.empty = function () {
	return this.splice(0, this.length);
};

Array.prototype.join2=function(delimiter,delimiter2){
	return this.slice(0,this.length>1?-1:1).join(delimiter)+(this.length>1?delimiter2+this[this.length-1]:'');
};

Array.join2=function(arr,delimiter,delimiter2){
	return (arr && arr.join2(delimiter,delimiter2))|| null;
};

/*format an array with 2 delimiters - one to put between all elements but the last two and one to put between the last two.*/
/*options : JSON {prefix:string, //to put before all elements
									gprefix1:string, //to put before the first length-1 elements 
									gprefix2: string //and before the last one
									}
*/
Array.prototype.join3=function(d1,d2,options){
	var options=options||{};
	if(options.gprefix){
		options.gprefix1=options.gprefix;
		options.gprefix2=options.gprefix;		
	}
	return (options.gprefix1?options.gprefix1:'')+this.slice(0,this.length>1?-1:1).join((options.prefix?options.prefix:'')+d1)+
		(this.length>1?(options.prefix?options.prefix:'')+d2+(options.gprefix2?options.gprefix2:'')+this[this.length-1]:'');
};

/*
 * Checks if an array values are within the limits given as an array [min,max,step]
 * step is optional and defaults to 1
 * if pad===true, pads each elem of this with 0s (only relevant for return_sorted===true)
 */
Array.prototype.inLimits = function (limits, pad) {
	ret = this.copy().unique();

	var step = limits[2] || 1;
	var pad = pad || false;

	if (ret[0] < limits[0] || ret[ret.length - 1] > limits[1]) {
		return false;
	}

	if (step != 1) {
		ret = ret.each(function (r, i) {
			if ((r % step) != 0)
				return false;
		});
		if (ret === false)
			return ret;
	}
	/*everything is fine so we work on this*/
	this.unique();

	if (pad) {
		var len = String(limits[1]).length;
		this.each(function (v, idx) {
			this[idx] = v.padZero(len);
		});
	}
	return true;
};

function isArray(v) {
	if (v == undefined)
		return false;
	return v.constructor.toString().indexOf("Array") > -1;
}



/*
 * A couple of functions for date manipulation
 */

/*
 * String.toDSDate returns a Date Object created from a "yyyy-MM-dd hh:mm" string
 */
String.prototype.toDSDate = function () {
	var dtpat = /(\d\d\d\d)-(\d\d)-(\d\d) (\d\d):(\d\d)/;
	var matches = dtpat.exec(this);
	if (!matches)
		return null;
	return new Date(matches[1], matches[2] - 1, matches[3], matches[4], matches[5], 0, 0);
};

/*
 * Date.toDString returns a "yyyy-MM-dd hh:mm" date string from a Date Object
 */
Date.prototype.toDString = function () {
	return this.getFullYear() + '-' + Number(this.getMonth() + 1).padZero(2) + '-' + this.getDate().padZero(2) + ' ' + this.getHours().padZero(2) + ':' + this.getMinutes().padZero(2);
};


function debug(msg) {
	window.console && console.log(msg);
}

