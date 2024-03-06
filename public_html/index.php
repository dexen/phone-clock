<?php
function U(string $str) { return rawurlencode($str); }
# as per 'nocache' @ https://www.php.net/manual/en/function.session-cache-limiter.php
header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html>
<head>
<title>Phone Clock</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0,  minimum-scale=1.0">
<meta name="theme-color" content="#000">
<link rel="manifest" href="/clock/manifest-phone-clock.webmanifest?<?= U(filemtime('manifest-phone-clock.webmanifest')) ?>">
<style>
* { box-sizing: border-box; }

#the-phone-clock {
	font-family: monospace, sans-serif;
	line-height: 53%;
	font-size: 800%;
font-size: 20vw;
}
#the-phone-clock .tpc-time-display {
	XXwidth: 9.33ex;
	XXpadding: .33ex .66ex;
padding: 10px;
border-width: 0;
	white-space: nowrap;
	font-style: italic;
	font-variant-numeric: tabular-nums lining-nums;
	color: #f00 !important;
	text-decoration: none !important;
}
#the-phone-clock .tpc-time-display a {
	color: inherit;
	text-decoration: inherit;
}

#the-phone-clock .tpc-time-display-shadow {
	XXwidth: 9.33ex;
	XXpadding: .33ex .66ex;
padding: 10px;
border-width: 0;
	white-space: nowrap;
	font-style: italic;
	font-variant-numeric: tabular-nums lining-nums;
	color: #f00;
position: absolute;
	z-index: -33;
filter: blur(12px) drop-shadow(6px 6px 12px #f00);
}

#the-phone-clock .tpc-debug-display {
	font-size: 22%;
	padding: .33ex .66ex;
padding: 10px;
border-width: 0;
	white-space: nowrap;
	font-style: italic;
	color: #f00;
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
	color: #f00;
	text-align: right;
}
#the-phone-clock .tpc-battery-progress,
#the-phone-clock .tpc-battery-progress::-moz-progress-bar,
#the-phone-clock .tpc-battery-progress::-webkit-progress-value
{
	background-color: red !important;
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
		<progress class="tpc-battery-progress" max="100" value="-1" style="width: 3ex; height: 2ex; color: #f00; display: none"></progress>
		<div class="tpc-battery-battery-box" style="width: 3ex; Xheight: 1.5ex; border: .21ex solid #f00; display: inline-block; margin-left: .0ex; border-left-style: dashed; box-sizing: initial; vertical-align: middle; margin-bottom: .2ex; border-radius: .18ex">
			<div class="tpc-battery-battery-fill" style="position: relative; width: 0ex; height: 1ex; background: #f00; margin: .1ex; margin-left: auto;  color: black;"></div>
		</div>
		
	</fieldset>
	<fieldset class="tpc-time-display-shadow">
		<span class="tpc-time-value-shadow">-1:-2:-3</span>
	</fieldset>
	<fieldset class="tpc-time-display">
		<a href="qr.php" class="tpc-time-value">-1:-2:-3</a>
	</fieldset>
	<fieldset class="tpc-debug-display">
		<a href="#" onclick="toggleDisplayConnection(); return false;">
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
var apply_correction =
	(document.cookie
		.split("; ")
		.find((row) => row.startsWith("apply_correction="))
		?.split("=")[1]) || 1;
apply_correction = Number(apply_correction);
document.cookie = 'apply_correction=' + apply_correction;
console.log(document.cookie);
function toggleDisplayConnection() {
	apply_correction = (apply_correction+1)%2;
	document.cookie = 'apply_correction=' + apply_correction;
	window.updateDisplay();
};
(function(thePhoneClockEl) {
	"use strict";

	try {
		navigator.wakeLock.request("screen"); }
	catch (err) {
console.log('could not obtain wake lock');
	}

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

	var theTimeValueEl = thePhoneClockEl.getElementsByClassName('tpc-time-value')[0];
	var theShadowTimeValueEl = thePhoneClockEl.getElementsByClassName('tpc-time-value-shadow')[0];
	var theDebugEl = thePhoneClockEl.getElementsByClassName('tpc-debug-display')[0];
	var theDisplayJitterValueEl = theDebugEl.getElementsByClassName('tpc-display-jitter-value')[0];
	var theHeartbeatJitterValueEl = theDebugEl.getElementsByClassName('tpc-heartbeat-jitter-value')[0];
	var theServerdiffEl = theDebugEl.getElementsByClassName('tpc-serverdiff-value')[0];
	var theBatteryDisplayEl = thePhoneClockEl.getElementsByClassName('tpc-battery-display')[0];
	var theBatteryLevelEl = thePhoneClockEl.getElementsByClassName('tpc-battery-value')[0];
	var theBatteryProgressEl = thePhoneClockEl.getElementsByClassName('tpc-battery-progress')[0];
	var theBatteryBatteryBoxEl = thePhoneClockEl.getElementsByClassName('tpc-battery-battery-box')[0];
	var theBatteryBatteryFillEl = thePhoneClockEl.getElementsByClassName('tpc-battery-battery-fill')[0];

	var heartbeatTimer = null;

	theTimeValueEl.innerHTML = -1; // '-2:-3:-4';
	theShadowTimeValueEl.innerHTML = theTimeValueEl.innerHTML;

	var offsetsamples = [];
	var jittersamples = [];
	var THE_CORRECTION = 0;

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

function POORMANSNTP2(Ev)
{
	var NSAMPLES = 63;

	var rqend = Ev.timeStamp;
	var rqroundtrip = rqend - this.rqstart;
	var rqmidpoint = rqroundtrip/2;
	var response = this.rq.responseText;
	var servertimestamp = new Number(response);
	var localtimestamp = Date.now();
	var offset = servertimestamp - localtimestamp + rqmidpoint;

	var offsetaverage = updateCalcAvg(offsetsamples, NSAMPLES, offset);
	var RMS = updateCalcRms(jittersamples, NSAMPLES, offset-offsetaverage);

	THE_CORRECTION = 0;
	if (apply_correction) {
		THE_CORRECTION = offsetaverage;
		var note = 'correction: ' + Math.round(offsetaverage) + ' # jitter: ' + Math.round(RMS*100)/100; }
	else if (offsetaverage >= 0)
		var note = 'LATE: ' + Math.round(offsetaverage) + ' # jitter: ' + Math.round(RMS*100)/100;
	else
		var note = 'EARLY: ' + Math.round(-offsetaverage) + ' # jitter: ' + Math.round(RMS*100)/100;
	theServerdiffEl.innerHTML = note;
};

function POORMANSNTP()
{
	var rq = new XMLHttpRequest;
	rq.open("GET", "/clock/server-time.php", true);
	rq.addEventListener('load', { rq: rq, rqstart: performance.now(), handleEvent: POORMANSNTP2 });
	rq.send();
};

	function displayTime() {
		var theDatetime = new Date();
		theDatetime.setMilliseconds(theDatetime.getMilliseconds()+THE_CORRECTION);
		theDisplayJitterValueEl.innerHTML = theDatetime.getMilliseconds() % 100;
		theTimeValueEl.innerHTML = timeAsText(theDatetime);
		theShadowTimeValueEl.innerHTML = theTimeValueEl.innerHTML;
		window.setTimeout(POORMANSNTP, 11);
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
				theBatteryProgressEl.value = (battery.level * 100);
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

	displayTime();
	window.updateDisplay = function() {
		displayTime();
	};
	heartbeatTimer = window.setInterval(heartbeat, 1);
})(document.getElementById('the-phone-clock'));
</script>
