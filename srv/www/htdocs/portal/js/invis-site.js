/*
 * js/invis-site.js v1.1
 * site building an general portal wide functions
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2013 Ingo Göppert, invis-server.org
 * (C) 2014 Stefan Schaefer, invis-server.org
 * License GPLv3
 * Questions: stefan@invis-server.org
 */

// Global
var minPwdLength = 0; // disabled, enable in config
var pwdComplex = "off"; // disabled, enable in config
 
// initial setup

function init () {
	lightbox.hide();
	window.onresize = lightbox.update;
	initUserblock();
	minPwdLength = $('user_pw_min_length').value;
	pwdComplex = $('user_pw_complex').value;
}

//
// site building and response
//
function initUserblock() {
	$('userblock').innerHTML = "";
	
	var cookie = invis.getCookie('invis');
	if (cookie != null) {
		cookie = cookie.evalJSON();
		var user_string = cookie.uid + " (" + cookie.displayname + ")";
		var a_profil = new Element("a").update("Einstellungen");
		a_profil.setStyle({'cursor': 'pointer'});
		a_profil.observe("click", showProfile);
		$('userblock').insert(a_profil);
		
		$('userblock').insert('<span class="spacer">|</span>');
		
		var a_logout = new Element("a").update("Abmelden");
		a_logout.setStyle({'cursor': 'pointer'});
		a_logout.observe("click", doLogout);
		$('userblock').insert(a_logout);
		
		$('userblock').insert("<br /><b>Benutzer:</b> <i>" + user_string + "</i>");
		if (cookie.PWD_EXPIRE == 'D')
			$('userblock').insert("<br /><span style='font-size: 0.95em;'>Ihr Passwort <b style='color: #04B404;'>läuft nie ab</b></span>");
		else if (cookie.PWD_RLZ < 1)
			$('userblock').insert("<br /><span style='font-size: 0.95em;'>Ihr Passwort <b style='color: #ff0000;'>ist abgelaufen!</b></span>");
		else if (cookie.PWD_RLZ <= 5)
			$('userblock').insert("<br /><span style='font-size: 0.95em;'>Ihr Passwort läuft in <b style='color: #ff0000;'>" + cookie.PWD_RLZ + "</u> Tagen ab</span>");
		else
			$('userblock').insert("<br /><span style='font-size: 0.95em;'>Ihr Passwort ist gültig bis: <b style='color: #04B404;'>" + cookie.PWD_EXPIRE + "</b></span>");
	}
	else {
		var a = new Element("a").update("<span style='font-size: 1.5em;'>Anmelden</span>");
		a.setStyle({'cursor': 'pointer'});
		a.observe("click", showLogin);
		$('userblock').insert(a);
	}

}

function showLogin(event) {
	if (location.protocol == "https:") {
	    lightbox.show(300, true);
	} else {
	    lightbox.show(500, true);
	}
	var div = new Element('div', {'id': 'login-block'});
	var div_cancel = new Element('div', {'class': 'cancel'}).update('x');
	div.insert(div_cancel);
	div_cancel.observe('click',
		function (event) {
			invis.deleteCookie('invis-login');
			lightbox.hide();
		}
	);
	
	div.insert(new Element('div', {'class': 'section-title center'}).update('invis Login'));
	
	if (location.protocol == "https:") {
	    var str = '<form onsubmit="doLogin(); return false;" autocomplete="off"><table cellspacing="0" cellpadding="0">' +
				'<tr><td class="label">login</td><td class="input"><input id="login_user" /></td></tr>' +
				'<tr><td class="label">passwort</td><td class="input"><input type="password" id="login_pwd" /></td></tr>' +
				'<tr><td colspan="2"><button type="submit">Anmelden</button></td></tr>' +
			'</table></form>';
	    div.insert(str);
	
	    div.insert(new Element('div', {'id': 'login-message'}));
	
	    lightbox.getContent().insert(div);
	    lightbox.update();
	    $('login_user').focus();
	} else {
	    div.insert('<p style="text-align:center" class="red">Bitte auf <a href="https://' + location.hostname + '">https://' + location.hostname + '</a> anmelden!</p>');
	    lightbox.getContent().insert(div);
	    lightbox.update();
	}
}

function doLogin() {
	var uid = $('login_user');
	var pwd = $('login_pwd');
	lightbox.setWaitStatus(true);
	//invis.setCookie("invis-login", $H({uid: uid.value, pwd: pwd.value}).toJSON(), 0.1);
	invis.setCookie("invis-login", JSON.stringify({uid: uid.value, pwd: pwd.value}), 0.1);
	var myAjax = new Ajax.Request(
		"script/login.php",
		{
			method: 'post',
			onComplete: userLoginResponse
		}
	);
}

function loginComplete() {
	lightbox.hide();
	location.reload(true);
}

function doLogout(event) {
	invis.deleteCookie('invis');
	location.reload(true);
}

function userLoginResponse(request) {
	lightbox.setWaitStatus(false);
	if (request.status == 200)  {
		lightbox.setStatus("<span class='green'>Anmeldung erfolgreich!</span>");
		invis.deleteCookie('invis-login');
		invis.setCookie('invis', request.responseText);
		window.setTimeout("loginComplete()", 1000);
	} else {
		lightbox.setStatus("<span class='red'>Anmeldung fehlgeschlagen!</span>");
	}
}

function userReLoginResponse(request) {
	lightbox.setWaitStatus(false);
	if (request.status == 200)  {
		invis.deleteCookie('invis-login');
		invis.setCookie('invis', request.responseText);
		initUserblock();
	} else {
		lightbox.setStatus("<span class='red'>Erneute Anmeldung fehlgeschlagen!</span>");
	}
}

function showProfile(event) {
	lightbox.show(600, 400, true);
	lightbox.setWaitStatus(true);
	var data = invis.getCookie('invis').evalJSON();
	invis.request('script/adajax.php', showProfileResponse, {c: 'user_detail', u: data.uid});
}

function showProfileResponse(request) {
	lightbox.setWaitStatus(false);
	lightbox.setTitle(new Element('div', {'class': 'section-title'}).update('Benutzerprofil'));

	var data = request.responseText.evalJSON();
	
	// editable attributes
	var rows = $H({
					'uid': false,
					'rid': false,
					'email': false,
					'display_name': true,
					'firstname': true,
					'surname': true,
					'description': true,
					'office': true,
					'telephone': true,
					'userpassword': true
				});
	
	// attribute description
	var row_names = $H({
					'uid': 'Login',
					'rid': 'RID',
					'email': 'Email',
					'display_name': 'Anzeigename',
					'userpassword': 'Passwort',
					'surname': 'Nachname',
					'description': 'Beschreibung',
					'office': 'Büro',
					'telephone': 'Telefon',
					'firstname': 'Vorname'
				});
	lightbox.setData(new DetailStorage(request.responseText.evalJSON(), rows));
	
	var table = new Element('table', {'id': 'profile-table', 'cellpadding': '2', 'cellspacing': '5'});
	lightbox.getContent().insert(table);
	
	rows.each(
		function (item) {
			var tr = new Element('tr');
			var th = new Element('th');
			
			th.update(row_names.get(item.key));
			th.addClassName('description');
			
			// description
			var td = new Element('td');
			td.writeAttribute('key', item.key)
			if (item.key == 'userpassword') {
				var btn = new Element('button', {'id': 'btn_change_pw'}).update('Passwort ändern');
				btn.observe('click', profileRequestPasswordChange);
				td.insert(btn);
			} else {
				td.update(data[item.key]);
			}
			
			// value
			if (item.value == false)
				td.addClassName('nochange');
			else {
				td.addClassName('editable');
				if (item.key != 'userpassword') td.observe('click', lightbox.inputBoxNew.bind(lightbox));
			}
			
			tr.insert(th);
			tr.insert(td);
			table.insert(tr);
		}
	);
	
	
	var btn_save = new Element('button').update('Speichern');
	btn_save.observe('click', function(e) {
		lightbox.setWaitStatus(true);
		invis.setCookie('invis-request', lightbox.data.getHash().toJSON());
		invis.request('script/adajax.php', profileModResponse, {c: 'user_mod', u: data['uid']});
	});
	lightbox.addButton(btn_save);
	
	var btn_cancel = new Element('button').update('Abbrechen');
	btn_cancel.observe('click', lightbox.hide);
	lightbox.addButton(btn_cancel);
	lightbox.update();
}

function profileModResponse(request) {
	lightbox.setWaitStatus(false);
	if (request.responseText == '"Success"') {
		lightbox.hide();
	} else {
		lightbox.setStatus('Profil konnte nicht geändert werden!<br />' + request.responseText);
	}
}

function profileRequestPasswordChange(event) {
	var node = event.target.parentNode;

	var btn_accept = new Element('button').update('Ändern');
	var btn_cancel = new Element('button').update('Abbrechen');
	
	btn_accept.observe('click',
		function (event) {
			var secret = $('input_change_pw').value;
			var confirm = $('input_change_pw_confirm').value;
			var displayname = invis.getCookie('invis').evalJSON().displayname;
			var uid = invis.getCookie('invis').evalJSON().uid;
			
			if (secret != confirm) {
				lightbox.setStatus("<span class='red'>Passwörter stimmen nicht überein!</span>");
				lightbox.update();
				return;
			}
			
			if (secret.length < minPwdLength) {
				lightbox.setStatus("<span class='red'>Passwort ist zu kurz! Mindestlänge ist " + minPwdLength + " Zeichen!</span>");
				lightbox.update();
				return;
			}
			
			if ((pwdComplex == "on") && (uid == secret)) {
				lightbox.setStatus("<span class='red'>Passwort darf nicht dem Benutzernamen entsprechen!</span>");
				lightbox.update();
				return;
			}
			
			if (chkPass2(secret, uid, displayname, minPwdLength, pwdComplex) < 1) {
				lightbox.setStatus("<span class='red'>Passwort ist zu einfach! Bitte Groß- und Kleinbuchstaben, Zahlen und Sonderzeichen verwenden!</span>");
				lightbox.update();
				return;
			}
			
			lightbox.setWaitStatus(true);
			// Passwort im Klartext an adLDAP uebergeben.
			//invis.setCookie('invis-request', $H({'adpassword': secret}).toJSON());
			invis.setCookie('invis-request', JSON.stringify({'adpassword': secret}));
			invis.request('script/adajax.php', 
				function(request) {
					lightbox.setWaitStatus(false);
					if (request.responseText == '"Success"') {
						table.remove();
						$('btn_change_pw').show();
						lightbox.setStatus("Passwort wurde geändert!");
						lightbox.setWaitStatus(true);
						//invis.setCookie("invis-login", $H({uid: uid, pwd: secret}).toJSON(), 0.1);
						invis.setCookie("invis-login", JSON.stringify({uid: uid, pwd: secret}), 0.1);
						var myAjax = new Ajax.Request(
							"script/login.php",
							{
								method: 'post',
								onComplete: userReLoginResponse
							}
						);
					} else {
						lightbox.setStatus("Passwort konnte nicht geändert werden!<br>" + request.responseText);
					}
					lightbox.update();
				},
				{c: 'user_mod', u: invis.getCookie('invis').evalJSON().uid}
			);
		}
	);
	
	btn_cancel.observe('click', function (event)
		{
			table.remove();
			$('btn_change_pw').show();
		}
	);
	
	var table = new Element('table');
	
	var tr1 = new Element('tr');
	var tr2 = new Element('tr');
	var tr3 = new Element('tr');
	
	var td1_1 = new Element('td');
	var td1_2 = new Element('td', {'class': 'input-description'});
	var td2_1 = new Element('td');
	var td2_2 = new Element('td', {'class': 'input-description'});
	
	var td3 = new Element('td', {'colspan': 2});
	
	td1_1.insert('<input type="password" id="input_change_pw" />');
	td1_2.insert('neues Passwort');
	
	td2_1.insert('<input type="password" id="input_change_pw_confirm" />');
	td2_2.insert('Passwort bestätigen');
	
	td3.insert(btn_accept);
	td3.insert(btn_cancel);
	
	tr1.insert(td1_1);
	tr1.insert(td1_2);
	
	tr2.insert(td2_1);
	tr2.insert(td2_2);
	
	tr3.insert(td3);
	
	table.insert(tr1);
	table.insert(tr2);
	table.insert(tr3);
	
	$('btn_change_pw').hide();
	node.insert(table);
	
	lightbox.update();
}
