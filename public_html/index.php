<?php
function U(string $str) { return rawurlencode($str); }
# as per 'nocache' @ https://www.php.net/manual/en/function.session-cache-limiter.php
header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');
function config_hint_dev_p() : bool { return strpos($_SERVER['SCRIPT_URI'], 'dev', 7) !== false; }
?>
<!DOCTYPE html>
<html>
<head>
<title>Precise Clock # Sub-1 frame precision</title>
<meta name="description" content="Digital Clock. Sub-1 frame precision on 120 Hz displays achieved thanks to simplified variant of NTP algorithm. An installable web app."
<meta name="viewport" content="width=device-width, initial-scale=1.0,  minimum-scale=1.0">
<meta name="theme-color" content="#000">
<link rel="manifest" href="manifest-phone-clock.webmanifest?<?= U(filemtime('manifest-phone-clock.webmanifest')) ?>">
<style>
* { box-sizing: border-box; }

:root {
	--theme-red-main-color: #f00;
	--theme-red-subdued-color: #300;
	--theme-vfd-main-color: #06cf93;
	--theme-dev-main-color: #00f;
	--theme-main-color: <?= config_hint_dev_p() ? 'var(--theme-dev-main-color)' : 'var(--theme-red-main-color)' ?>;
	--main-color: var(--theme-red-main-color);
}

#the-phone-clock {
	font-family: monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
	line-height: 53%;
	font-size: 800%;
	font-size: 20vw;
}
#the-phone-clock .tpc-time-display {
	padding: 10px;
	border-width: 0;
	white-space: nowrap;
	font-style: italic;
	font-variant-numeric: tabular-nums lining-nums;
	color: #f00 !important;
	color: var(--main-color) !important;
	text-decoration: none !important;
	text-shadow: 1vw 1vw 1.33px var(--theme-red-subdued-color);
}
#the-phone-clock .tpc-time-display a {
	color: inherit;
	text-decoration: inherit;
}

#the-phone-clock .tpc-time-display-shadow {
	padding: 10px;
	border-width: 0;
	white-space: nowrap;
	font-style: italic;
	font-variant-numeric: tabular-nums lining-nums;
	color: #f00 !important;
	color: var(--main-color) !important;
	position: absolute;
	z-index: -33;
	filter: blur(12px) drop-shadow(6px 6px 12px #f00);
	filter: blur(12px) drop-shadow(6px 6px 12px var(--main-color));
}

#the-phone-clock .tpc-date-display {
	font-size: 22%;
	padding: .33ex .66ex;
	padding: 10px;
	border-width: 0;
	white-space: nowrap;
	font-style: italic;
	color: #f00 !important;
	color: var(--main-color) !important;
}
#the-phone-clock .tpc-date-display a {
	color: inherit;
	text-decoration: none;
}

#the-phone-clock .tpc-debug-display {
	font-size: 22%;
	padding: .33ex .66ex;
	padding: 10px;
	border-width: 0;
	white-space: nowrap;
	font-style: italic;
	color: #f00 !important;
	color: var(--main-color) !important;
}
#the-phone-clock .tpc-debug-display .tpc-serverdiff-value {
	white-space: pre;
}
#the-phone-clock .tpc-debug-display a {
	color: inherit;
	text-decoration: none;
}
#the-phone-clock .tpc-battery-display {
	font-size: 22%;
	padding: .33ex .66ex;
	padding: 10px;
	border-width: 0;
	white-space: nowrap;
	font-style: italic;
	color: #f00 !important;
	color: var(--main-color) !important;
	border-color: #0f0 !important;
	text-align: right;
}

#the-phone-clock .tpc-battery-battery-fill {
	background-color: #f00 !important;
	background-color: var(--main-color) !important;
	color: black;
}

html, body {
	height: 100%;
	margin: 0;
	padding: 0;
}
body {
	display: flex;
	align-items: center;
	justify-content: center;
	background: black;
}
</style>
</head>
<body>
<center id="the-phone-clock">
	<fieldset class="tpc-battery-display" style="visibility: hidden">
		<span class="tpc-battery-value">-?%</span>
		<div class="tpc-battery-battery-box" style="width: 3ex; Xheight: 1.5ex; border-width: .21ex; border-style: solid; display: inline-block; margin-left: .0ex; border-left-style: dashed; box-sizing: initial; vertical-align: middle; margin-bottom: .2ex; border-radius: .18ex">
			<div class="tpc-battery-battery-fill" style="position: relative; width: 0ex; height: 1ex; margin: .1ex; margin-left: auto;"></div>
		</div>
		
	</fieldset>
	<fieldset class="tpc-time-display">
		<a href="qr.php" class="tpc-time-value">-1:-2:-3</a>
	</fieldset>
	<fieldset class="tpc-date-display" style="display: none">
		<a href="#" onclick="tristateSecondaryDisplay(); return false;" style="display: flex; width: 100%">
			<div class="tpc-date-value" style="text-align: left; flex: 1; margin-left: .33ex;">--</div>
			<div class="tpc-day-name-value" style="text-align: right; flex: 2; margin-right: .33ex;">--</div>
		</a>
	</fieldset>
	<fieldset class="tpc-debug-display" style="display: block;">
		<a href="#" onclick="tristateSecondaryDisplay(); return false;">
		<span class="tpc-display-jitter-value" style="display: none;">-222</span>
		<span style="display: none;">;;</span>
		<span class="tpc-heartbeat-jitter-value" style="display: none;">-333</span>
		<span style="display: none;">//</span>
		<span class="tpc-serverdiff-value">-444</span>
		</a>
	</fieldset>
</center>

<script>
"use strict";

var cfg_theme = localStorage.getItem('cfg_theme') || 0;
cfg_theme = Number(cfg_theme) || 0;

function toggleTheme(increment) {
	var xtheme = ['--theme-main-color', '--theme-vfd-main-color'];
	cfg_theme = (cfg_theme+increment)%xtheme.length;
	localStorage.setItem('cfg_theme', cfg_theme);
	var r = document.querySelector(':root');
	var vv = window.getComputedStyle(r).getPropertyValue(xtheme[cfg_theme]);
	r.style.setProperty('--main-color', vv);
};
toggleTheme(+0);
function advanceTheme() { toggleTheme(+1); }

var cfg_tristate_secondary_display = localStorage.getItem('cfg_tristate_secondary_display');
if (cfg_tristate_secondary_display === null)
	cfg_tristate_secondary_display = 1;
cfg_tristate_secondary_display = Number(cfg_tristate_secondary_display);

function tristateSecondaryDisplay() {
	cfg_tristate_secondary_display = ((cfg_tristate_secondary_display+1+1)%3)-1;
	localStorage.setItem('cfg_tristate_secondary_display', cfg_tristate_secondary_display);
	window.reconfigureSecondaryDisplay();
	window.updateDisplay();
};

(function(thePhoneClockEl) {
	"use strict";

	try {
		navigator.wakeLock.request("screen"); }
	catch (err) {
console.log('could not obtain wake lock');
	}

	document.addEventListener("visibilitychange", async () => {
		if (wakeLock !== null && document.visibilityState === "visible")
			await navigator.wakeLock.request("screen");
	});

	function timeAsText(theDatetime) {
		var ret = '';
		var h = theDatetime.getHours();
		if (h < 10)
			h = '0' + h;
		var m = theDatetime.getMinutes();
		if (m < 10)
			m = '0' + m;
		var s = theDatetime.getSeconds();
		if (s < 10)
			s = '0' + s;
		return h + ':' + m + ':' + s;
	};

	function dateAsText(theDatetime) {
		var d = theDatetime;
		var y = d.getFullYear();
		var m = d.getMonth()+1;
		if (m<10)
			m = '0' + m;
		var d = d.getDate();
		if (d<10)
			d = '0' + d;
		return y + '-' + m + '-' + d;
	};

	var theTimeValueEl = thePhoneClockEl.getElementsByClassName('tpc-time-value')[0];
	var theDebugEl = thePhoneClockEl.getElementsByClassName('tpc-debug-display')[0];
	var theDisplayJitterValueEl = theDebugEl.getElementsByClassName('tpc-display-jitter-value')[0];
	var theHeartbeatJitterValueEl = theDebugEl.getElementsByClassName('tpc-heartbeat-jitter-value')[0];
	var theServerdiffEl = theDebugEl.getElementsByClassName('tpc-serverdiff-value')[0];
	var theBatteryDisplayEl = thePhoneClockEl.getElementsByClassName('tpc-battery-display')[0];
	var theBatteryLevelEl = thePhoneClockEl.getElementsByClassName('tpc-battery-value')[0];
	var theBatteryBatteryBoxEl = thePhoneClockEl.getElementsByClassName('tpc-battery-battery-box')[0];
	var theBatteryBatteryFillEl = thePhoneClockEl.getElementsByClassName('tpc-battery-battery-fill')[0];
	var theDateEl = thePhoneClockEl.getElementsByClassName('tpc-date-display')[0];
	var theDateDayNameEl = theDateEl.getElementsByClassName('tpc-day-name-value')[0];
	var theDateValueEl = theDateEl.getElementsByClassName('tpc-date-value')[0];

	var heartbeatTimer = null;

	theTimeValueEl.innerHTML = -1; // '-2:-3:-4';

	var offsetsamples = [];
	var jittersamples = [];
	var THE_CORRECTION = 0;

/// DELETEME
function updateCalcRms(samples, nsamples, newValue)
{
	while (samples.length >= nsamples)
		samples.shift();
	samples.push(newValue);

	var acc = 0;
	for (var n = samples.length-1; n>=0; --n)
		acc = acc + samples[n]*samples[n];

	return Math.sqrt((1/samples.length) * acc);
};


function calcRms(averageValue, samples)
{
	var acc = 0;
	for (var n = samples.length-1; n>=0; --n) {
		var v = samples[n]-averageValue;
		acc = acc + v*v;
	}

	return Math.sqrt((1/samples.length) * acc);
}

/// DELETEME
function updateCalcAvg(samples, nsamples, newValue)
{
	while (samples.length >= nsamples)
		samples.shift();
	samples.push(newValue);

	var acc = 0;
	for (var n = samples.length-1; n>=0; --n)
		acc = acc + samples[n];

	return acc/samples.length;
};

function calcAvg(samples)
{
	var acc = 0;
	for (var n = samples.length-1; n>=0; --n)
		acc = acc + samples[n];

	return acc/samples.length;
}

var POORMANSNTP = {
	OffsetSampling : {
		_history: [],
		_stats: {discarded_request_timeout: 0, },
		NSAMPLES: 63,
		NDISCARDPERCENT: 42,
		MAX_ROUNDTRIP_MS: 999,
		recencyIndicator: '#',
		//_recencyIndicatorSeries: ['d', 'b', 'q', 'p' ],
		//_recencyIndicatorSeries: ['|', '/', '-', '\\', ],
		//_recencyIndicatorSeries: ['&lt;', '^', '&gt', 'v', ],
		//_recencyIndicatorSeries: [ '🌍', '🌎', '🌏', ],
		//_recencyIndicatorSeries: [ '˥', '˦', '˧', '˨', '˩', '˨', '˧', '˦', ],
		// courtesy of https://symbl.cc/en/unicode/blocks/block-elements/
		_recencyIndicatorSeries: [ '▙', '▚', '▞', '▛', '▞', '▚', '▜', '▚', '▞', '▟', '▞', '▚', ],

		addSample: function(roundtrip_ms, offset) {
			while (this._history.length >= this.NSAMPLES)
				this._history.shift();
			this._history.push({roundtrip_ms: roundtrip_ms, offset: offset, jitter: undefined});
		},

		addRequestResults: function(roundtrip_ms, offset) {
			if (roundtrip_ms >= this.MAX_ROUNDTRIP_MS) {
				++this._stats.discarded_request_timeout;
				return; }
			this.addSample(roundtrip_ms, offset);
			this.recencyIndicator = this._recencyIndicatorSeries.shift();
			this._recencyIndicatorSeries.push(this.recencyIndicator);
		},

		fixupJitter: function(jitter) {
			if (this._history.length <= 0)
				throw new Error('trying to fix up jitter on empty history');
			if (this._history[this._history.length-1].jitter !== undefined)
				throw new Error('trying to fix up jitter on already fixed up jitter');
			this._history[this._history.length-1].jitter = jitter;
		},
		offsetSamples: function() {
			var ret = [];
			for (var n = 0; n < this._history.length; ++n)
				ret.push(this._history[n].offset);
			return ret;
		},

		offsetSamplesBest: function() {
			var a = [];
			if (this._history.length === 0)
				return ret;
			if (this._history.length === 1)
				return [ this._history[0].offset ];
			if (this._history.length === 2)
				return [
					this._history[0].offset,
					this._history[1].offset,
				];
			const NDISCARD_COUNT = Math.ceil(this._history.length*this.NDISCARDPERCENT/100);
			const TARGET_COUNT = this._history.length - NDISCARD_COUNT;

			for (var n = 0; n < this._history.length; ++n)
				a.push(this._history[n]);
			a.sort((a,b)=>a.roundtrip_ms-b.roundtrip_ms);

			var ret = [];

			for (var n = 0; n < TARGET_COUNT; ++n)
				ret.push(a[n].offset);

			return ret;
		},
		jitterSamples: function() {
		},
		jitterSamplesBest: function() {
		},
	},
};

function numConstWidth3d2(num)
{
	var str = String(num.toFixed(2));
	return str.padStart(6);
}

function numConstWidth5(num)
{
	var str = String(num.toFixed(0));
	if (str === '-0')
		str = '0';
	return str.padStart(5);
}

function POORMANSNTP2(Ev)
{
	var NSAMPLES = 63;
	var rqend = Ev.timeStamp;
	var rqroundtrip = rqend - this.rqstart;
	var rqmidpoint = rqroundtrip/2;
	var response = this.rq.responseText;
	var servertimestamp = new Number(response);
	var localtimestamp = Date.now();
	const offset = servertimestamp - localtimestamp + rqmidpoint;

	POORMANSNTP.OffsetSampling.addRequestResults(rqroundtrip, offset);

	//var oaALL = calcAvg(POORMANSNTP.OffsetSampling.offsetSamples());
	var oaBEST = calcAvg(POORMANSNTP.OffsetSampling.offsetSamplesBest());

	//var offsetaverage = calcAvg(POORMANSNTP.OffsetSampling.offsetSamples());
/// FIXME - use jitter calculated from *best* samples, rather than from all
	//const jitter = offset-offsetaverage;
	//var RMS = updateCalcRms(jittersamples, NSAMPLES, jitter);
	//POORMANSNTP.OffsetSampling.fixupJitter(jitter);
	var RMS = calcRms(oaBEST, POORMANSNTP.OffsetSampling.offsetSamplesBest());

	THE_CORRECTION = 0;
	if (cfg_tristate_secondary_display) {
		THE_CORRECTION = oaBEST;
		var note = 'correction: ' + numConstWidth5(oaBEST) + ' ' + POORMANSNTP.OffsetSampling.recencyIndicator + ' jitter: ' + numConstWidth3d2(RMS); }
	else if (oaBEST >= 0)
		var note = 'LATE: ' + numConstWidth5(oaBEST) + ' ' + POORMANSNTP.OffsetSampling.recencyIndicator + ' jitter: ' + numConstWidth3d2(RMS);
	else
		var note = 'EARLY: ' + numConstWidth5(-oaBEST) + ' ' + POORMANSNTP.OffsetSampling.recencyIndicator + ' jitter: ' + numConstWidth3d2(RMS);
	theServerdiffEl.innerHTML = note;
};

function POORMANSNTP_TO()
{
	var rq = new XMLHttpRequest;
	rq.timeout = POORMANSNTP.OffsetSampling.MAX_ROUNDTRIP_MS;
	rq.open("GET", "./server-time.php", true);
	rq.addEventListener('load', { rq: rq, rqstart: performance.now(), handleEvent: POORMANSNTP2 });
	rq.send();
};

	function displayTime() {
		var theDatetime = new Date();
		theDatetime.setMilliseconds(theDatetime.getMilliseconds()+THE_CORRECTION);
		theDisplayJitterValueEl.innerHTML = theDatetime.getMilliseconds() % 100;
		theTimeValueEl.innerHTML = timeAsText(theDatetime);

		var Fmt = new Intl.DateTimeFormat(undefined, {weekday: 'long'});
		theDateDayNameEl.innerHTML = Fmt.format(theDatetime);
		theDateValueEl.innerHTML = dateAsText(theDatetime);

		window.setTimeout(POORMANSNTP_TO, 11);
	};
	function heartbeat() {
		var theDatetime = new Date();
		theDatetime.setMilliseconds(theDatetime.getMilliseconds()+THE_CORRECTION);
		var xjitter = theDatetime.getMilliseconds() % 100;
		if (xjitter > 60) {
			window.clearInterval(heartbeatTimer);
			heartbeatTimer = window.setInterval(heartbeat, 98);
		}
		else if (xjitter < 40) {
			window.clearInterval(heartbeatTimer);
			heartbeatTimer = window.setInterval(heartbeat, 100);
		}
		theHeartbeatJitterValueEl.innerHTML = xjitter;
		if (navigator.getBattery) {
/// FIXME: use levelchange event instead
			theBatteryDisplayEl.style.visibility = 'visible';
			navigator.getBattery().then((battery) => {
				if (battery.chargingTime == 0)
					theBatteryDisplayEl.style.visibility = 'hidden';
				theBatteryLevelEl.innerHTML = Math.round(battery.level * 100) + '%';
				theBatteryBatteryFillEl.style.width = (battery.level * 2.77) + 'ex';
				if (battery.charging)
					theBatteryBatteryFillEl.innerHTML = '<div style="position: absolute; top: -2.63ex; right: .2ex; font-weight: bold; font-size: 66%">+</div>';
				else
					theBatteryBatteryFillEl.innerHTML = null;
			}); }
		if (theDatetime.getMilliseconds() > 900)
			window.setTimeout(
				displayTime,
				1001-theDatetime.getMilliseconds() );
	};

	window.reconfigureSecondaryDisplay = function() {
		if (cfg_tristate_secondary_display>=0) {
			theDateEl.style.display = 'none';
			theDebugEl.style.display = 'unset'; }
		else {
			theDateEl.style.display = 'unset';
			theDebugEl.style.display = 'none'; }
	};
	window.updateDisplay = function() {
		displayTime();
	};

	reconfigureSecondaryDisplay();
	displayTime();

	var touchstartX; var touchstartY; var touchendX; var touchendY;
	const threshold = 72; // px
	function handleLeftRightGesture(Event) {
		var diffX = Math.abs(touchstartX - touchendX);
		var diffY = Math.abs(touchstartY - touchendY);
		if (diffY > (diffX*2))
			return; /* don't care about vertical scrolls */
		if ((touchendX+threshold) < touchstartX)
//			alert('swiped left!');
			advanceTheme();
			;
		if ((touchendX-threshold) > touchstartX)
//			alert('swiped right!');
			advanceTheme();
			;
	};

	document.documentElement.addEventListener('touchstart', e => {
		touchstartX = e.changedTouches[0].screenX;
		touchstartY = e.changedTouches[0].screenY;
	}, {passive: true});

	document.documentElement.addEventListener('touchend', e => {
		touchendX = e.changedTouches[0].screenX;
		touchendY = e.changedTouches[0].screenY;
		handleLeftRightGesture(e);
	});


	heartbeatTimer = window.setInterval(heartbeat, 1);
})(document.getElementById('the-phone-clock'));
</script>
