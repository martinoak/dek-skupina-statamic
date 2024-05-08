(function(){

	function setCookie(cname, cvalue, exsec) {
		var d = new Date();
		d.setTime(d.getTime() + (exsec*1000));
		var expires = "expires="+ d.toUTCString();
		document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
	}

	function getCookie(cname, defVal) {
		var dv = typeof defVal !== 'undefined' ? defVal : null;
		var name = cname + "=";
		var decodedCookie = decodeURIComponent(document.cookie);
		var ca = decodedCookie.split(';');
		for(var i = 0; i <ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) == ' ') {
				c = c.substring(1);
			}
			if (c.indexOf(name) == 0) {
				return c.substring(name.length, c.length);
			}
		}
		return dv;
	}
	
	function detectActivity() {
		var date = new Date();
		var last = parseInt(getCookie('statamic_dekdev_last', 0));
		if (date.getTime() > last + 60000) {
			console.info('DekDev: activity detected');
			var xhttp = new XMLHttpRequest();
			xhttp.open("GET", "/!/DekDev/logActivity", true);
			xhttp.send();
			setCookie('statamic_dekdev_last', date.getTime(), 3600);
		}
	}

	document.addEventListener('DOMContentLoaded', detectActivity);
	document.addEventListener('mousemove', detectActivity);
	document.addEventListener('keyup', detectActivity);
	
	// Messages
	
	var appended = {};
	
	function getWrapper(name) {
		if (!appended[name]) {
			var el = document.createElement('div');
			el.setAttribute('id', name);
			document.body.appendChild(el);
			appended[name] = true;
		} else {
		 	var el = document.getElementById(name);
		}
		return el;
	}
		
	function getMessage() {
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				var el = getWrapper('deploy-message-wrapper');
				el.innerHTML = this.responseText;
			}
		};
		xhttp.open("GET", "/!/DekDev/message", true);
		xhttp.send();
	}
	
	function getMaintenance() {
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				var el = getWrapper('deploy-maintenance-wrapper');
				if (this.responseText == '0') {
					document.body.style.backgroundColor = '#f1f5f9';
					el.innerHTML = '';
				} else if (this.responseText == '2') {
				 	document.body.style.backgroundColor = '#f4c8c8';
				} else {
					el.innerHTML = this.responseText;
				}
			}
		};
		xhttp.open("GET", "/!/DekDev/maintenanceMode", true);
		xhttp.send();
	}
	
	setInterval(function(){
		getMessage();
		getMaintenance();
	}, 15000);
	
	document.addEventListener('DOMContentLoaded', getMessage);
	document.addEventListener('DOMContentLoaded', getMaintenance);

})();
