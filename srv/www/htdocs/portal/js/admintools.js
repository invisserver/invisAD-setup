/*
 * js/admintools.js v1.2
 * functions for user/group/host administration
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2010-2019 Stefan Schäfer, invis-server.org
 * (C) 2013,2015,2018 Ingo Göppert, invis-server.org
 * License GPLv3
 * Questions: http://wiki.invis-server.org
 */

//**********************************************************************
// page-global vars
//**********************************************************************

var PAGE_SIZE = 8;
var PAGE_SIZE_SERVICE = 5;
var PAGE_CURRENT = 0;
var PAGED_DATA = null;
var PAGED_DATA_UNSORTED = null;
var ACCOUNT_TYPE = new Array('Gast', 'Mailkonto', 'Windows', 'Windows+Unix', 'Windows+Unix+Groupware', 'WinAdmin', ' WinAdmin+Unix', 'WinAdmin+Unix+Groupware', 'MasterAdmin' );
var GROUP_TYPE = new Array('Team', 'Team+Gruppenmail', 'Mail-Verteiler', 'Sonstige');
var USERLIST_FLAG = new Array (true,true,true,true,true,true,true,true,true);
var GROUPLIST_BI_FLAG = false;
var HOST_TYPE = new Array('Client', 'Drucker', 'Server', 'IP-Gerät');
var HOSTLIST_FLAG = new Array(true,true,true,true);
var PINGER_FLAG = false;
var PINGER_REQUEST = [];

//**********************************************************************
// helper-functions and error reporting
//**********************************************************************

// dumps an error into an alert
function show_error(request) {
	alert(request);
}

//
// sort/filter/group paged data
//

// sorting function for an array of JSON user/group objects, grouped by TYPE and sorted by uid (alphabetical)
function mysort(a, b) {
	if (a.TYPE == b.TYPE)
		return (a['uid'] < b['uid']) ? -1 : (a['uid'] > b['uid'])? 1 : 0;
	else
		return a.TYPE - b.TYPE;
} 

// sorting function for an array of JSON group objects, sorted by cn (alphabetical, ignored case)

function groupsort(a, b) {
	return (a['cn'].toUpperCase() < b['cn'].toUpperCase()) ? -1 : (a['cn'].toUpperCase() > b['cn'].toUpperCase())? 1 : 0;
} 

// sorting function for an array of JSON host objects, sorted by IP
function hostsort(a, b) {
	var stA = a['iscdhcpstatements'].split(" ");
	var stB = b['iscdhcpstatements'].split(" ");
	if ((stA.length == 2) && (stB.length == 2))
	{
	    var ipA = stA[1].split(".");
	    var ipB = stB[1].split(".");
	    if ((ipA.length == 4) && (ipB.length == 4))
	    {
		var ipANum = 0;
		var ipBNum = 0;
		ipANum = (parseInt(ipA[0]) * 256 * 256 * 256) + (parseInt(ipA[1]) * 256 * 256) + (parseInt(ipA[2]) * 256) + parseInt(ipA[3]);
		ipBNum = (parseInt(ipB[0]) * 256 * 256 * 256) + (parseInt(ipB[1]) * 256 * 256) + (parseInt(ipB[2]) * 256) + parseInt(ipB[3]);
		return (ipANum < ipBNum) ? -1 : (ipANum > ipBNum) ? 1 : 0;
	    }
	    else
		return (a['iscdhcpstatements'] < b['iscdhcpstatements']) ? -1 : (a['iscdhcpstatements'] > b['iscdhcpstatements'])? 1 : 0;
	}
	else
	    return (a['iscdhcpstatements'] < b['iscdhcpstatements']) ? -1 : (a['iscdhcpstatements'] > b['iscdhcpstatements'])? 1 : 0;
} 

// filter paged data users
function filterData(data) {
	if (data != null)
		PAGED_DATA_UNSORTED = data;
	
	PAGED_DATA = new Array();
	PAGED_DATA_UNSORTED.each(
		function (item) {
		    console.log(item['TYPE']);
		    if (item['TYPE'] < USERLIST_FLAG.length)
		    {
			if (USERLIST_FLAG[item['TYPE']])
			    PAGED_DATA.push(item);
		    }
		}
	);
	
	PAGED_DATA.sort(mysort);
}

// filter paged data groups
function filterGroups(data) {
	if (data != null)
		PAGED_DATA_UNSORTED = data;
	
	PAGED_DATA = new Array();
	PAGED_DATA_UNSORTED.each(
		function (item) {
			if ( ! isNaN(item['rid']) || (isNaN(item['rid']) && GROUPLIST_BI_FLAG))
				PAGED_DATA.push(item);
		}
	);
	
	PAGED_DATA.sort(groupsort);
}

// filter paged data hosts
function filterHosts(data) {
	if (data != null)
		PAGED_DATA_UNSORTED = data;
	
	PAGED_DATA = new Array();
	//PAGED_DATA = data;
	// Hier geht es schief. Das filtern nach "item['TYPE']" funktioniert nicht.
	// Im Moment steht die Typenbezeichnung im Idex, es muss zum Filtern aber eine Nummer sein...
	// ... also Typen uebersetzen.
	PAGED_DATA_UNSORTED.each(
		function (item) {
		//Typenzuordnung Quick'n'Dirty
		if (item['TYPE'] == 'Client')
		    var typnr = 0;
		if (item['TYPE'] == 'Drucker')
		    var typnr = 1;
		if (item['TYPE'] == 'Server')
		    var typnr = 2;
		if (item['TYPE'] == 'IP-Gerät')
		    var typnr = 3;
		    console.log(item['TYPE']);
		    //if (item['TYPE'] < HOSTLIST_FLAG.length)
		    if ( typnr < HOSTLIST_FLAG.length)
		    {
			console.log(HOSTLIST_FLAG[item['TYPE']]);
			if (HOSTLIST_FLAG[typnr])
			    PAGED_DATA.push(item);
		    }
		}
	);
	
	PAGED_DATA.sort(hostsort);
}


//**********************************************************************
// display lists
//**********************************************************************

// build userlist

function userListResponse(request) {
	var title = $('admin-content-title');
	var content = $('admin-content-content');
	content.innerHTML = "";
	
	stopAllPingers();
	
//	PAGED_DATA = request.responseText.evalJSON().sortBy( function (item) {return item['uid'];} );
//	PAGED_DATA = request.responseText.evalJSON().sort(mysort);
	filterData(request.responseText.evalJSON());
	PAGE_CURRENT = 0;
	
	// header
	
	title.update('Benutzerliste:');
	
	var p = new Element('div', {'id': 'result-paging'});
	content.insert(p);
	
	content.insert('<table id="result-table" cellspacing="0" cellpadding="0"><thead><tr><th class="name">Login / UID</th><th class="type">Typ</th><th class="delete">Bearbeiten</th></tr></thead><tbody id="result-body"></tbody></table>');
	
	populateUserList(null, 0);
	
	// add user button
	var node = new Element('table', {'onclick': 'userAdd();', 'style': 'font-size: 0.8em; font-weight: bold; cursor: pointer; padding: 5px;'}).update('<tr><td><img src="images/user.png" /></td><td style="vertical-align: middle;">Benutzer anlegen</td></tr>');
	content.insert(node);
	content.insert('<table id="result-table" cellspacing="0" cellpadding="0"><tfoot><tr><td>Alle mit "*" gekennzeichneten Felder sind Pflichtfelder beim Anlegen eines Benutzers.<br>Sie können eine gültige Email-Adresse angeben. Geben Sie keine Email-Adresse an, wird die interne Adresse, bestehend aus "benutzername@lokale.domain" als Email-Adresse gesetzt.</td></tr></tfoot><tbody id="result-body"></tbody></table>');
}

// populate userlist
function populateUserList(event, page) {
	if (page == null) page = this.firstChild.nodeValue - 1;
	PAGE_CURRENT = page;
	
	$('result-body').innerHTML = "";
	$('result-paging').innerHTML = "";
	
	// table with current page data
	for (var i = page * PAGE_SIZE; i < (page+1) * PAGE_SIZE; i++) {
		if (PAGED_DATA[i] == null) break;
		var user = PAGED_DATA[i];
		
		var tr = new Element('tr');
		var td_name = new Element('td');
		td_name.insert(new Element('span', {'class': 'name'}).update(user['uid']));
		td_name.insert(new Element('span', {'class': 'number'}).update(user['uidnumber']));
		
		var td_type = new Element('td');
		td_type.insert(ACCOUNT_TYPE[user.TYPE]);
		
		var td_delete = new Element('td', {'class': 'delete'});
		td_delete.insert(new Element('a', {'onclick': 'lightbox.show(500, true); lightbox.setWaitStatus(true); invis.request("script/adajax.php", userDetailsResponse, {c: "user_detail", u: "' + user['uid'] + '"});'}).update('<img src="images/edit_img.png" />'));
		td_delete.insert(new Element('br'));
		td_delete.insert(new Element('a', {'onclick': 'userDelete("' + user['uid'] + '");'}).update('<img src="images/delete_img.png" />'));
		
		tr.insert(td_name);
		tr.insert(td_type);
		tr.insert(td_delete);
		
		$('result-body').insert(tr);
	}
	
	var check = new Array (USERLIST_FLAG.length);
	
	for (i = 0; i < check.length; i++)
	{
	    // account type selector
	    check[i] = new Element('input', {'type': 'checkbox'});
	    check[i].checked = USERLIST_FLAG[i];
	    check[i].indexNum = i;
	    
	    check[i].observe('click',
	    	function(e) {
	    		USERLIST_FLAG[this.indexNum] = this.checked;
	    		filterData();
	    		populateUserList(null, 0);
	    	}
	    )
	    
	    $('result-paging').insert(check[i]);
	    $('result-paging').insert(ACCOUNT_TYPE[i] + ' einblenden');
	    if ((i > 0) && ((i%3 == 0) || (i == check.length - 1)))
		$('result-paging').insert('<br/>');
	}

	// paging links
	var n_entries = PAGED_DATA.length;
	var n_pages = Math.ceil(n_entries / PAGE_SIZE);
	for (var i = 0; i < n_pages; i++) {
		var a = new Element('a', {'class': 'page-link'}).update(i + 1);
		if (i == PAGE_CURRENT)
			a.addClassName('page-active');
		else
			a.observe('click', populateUserList);
		$('result-paging').insert(a);
	}
}

// build grouplist

function groupListResponse(request) {
	var title = $('admin-content-title');
	var content = $('admin-content-content');
	content.innerHTML = "";
		stopAllPingers();
	
	//PAGED_DATA = request.responseText.evalJSON();
	//PAGED_DATA.sort(groupsort);
	filterGroups(request.responseText.evalJSON());
	PAGE_CURRENT = 0;
	
	// header
	
	title.update('Gruppenliste:');
	
	var n_entries = PAGED_DATA.length;
	var n_pages = Math.ceil(n_entries / PAGE_SIZE);
	var p = new Element('div', {'id': 'result-paging'});
	content.insert(p);
	
	// 3 colums (name, type, delete)
	//content.insert('<table id="result-table" cellspacing="0" cellpadding="0"><thead><tr><th class="name">Name / GID</th><th class="type">Typ</th><th class="delete">Bearbeiten</th></tr></thead><tbody id="result-body"></tbody></table>');
	
	// 2 colums (name, delete)
	content.insert('<table id="result-table" cellspacing="0" cellpadding="0"><thead><tr><th class="name">Name / RID / POSIX-GID</th><th class="type">Typ</th><th class="delete">Bearbeiten</th></tr></thead><tbody id="result-body"></tbody></table>');
	populateGroupList(null, 0);
	
	// add group button
	var node = new Element('table', {'style': 'font-size: 0.8em; font-weight: bold; cursor: pointer; padding: 5px;'}).update('<tr><td><img src="images/group.png" /></td><td style="vertical-align: middle;">Gruppe anlegen</td></tr>');
	node.observe('click', function(){
			invis.request('script/adajax.php', groupAdd, {c: 'user_template_list'});
		}
	);
	content.insert(node);
}


// populate grouplist
function populateGroupList(event, page) {
	if (page == null) page = this.firstChild.nodeValue - 1;
	PAGE_CURRENT = page;
	
	$('result-body').innerHTML = "";
	$('result-paging').innerHTML = "";

	
	for (var i = page * PAGE_SIZE; i < (page+1) * PAGE_SIZE; i++) {
		if (PAGED_DATA[i] == null) break;
		var item = PAGED_DATA[i];
		
		var tr = new Element('tr');
		
		// name
		var td_name = new Element('td');
		td_name.insert(new Element('span', {'class': 'name'}).update(item.cn));
		td_name.insert(new Element('span', {'class': 'number'}).update(item.rid));
		td_name.insert(new Element('span', {'class': 'number'}).update(item.gidnumber));
		tr.insert(td_name);
		
		// type
		var td_type = new Element('td');
		td_type.insert(new Element('span', {'class': 'name'}).update(GROUP_TYPE[item.TYPE]));
		tr.insert(td_type);
		
		// delete
		var td_delete = new Element('td', {'class': 'delete'});
		td_delete.insert(new Element('a', {'onclick': 'lightbox.show(500, true); lightbox.setWaitStatus(true); invis.request("script/adajax.php", groupDetailsResponse, {c: "group_detail", u: "' + item['cn'] + '"});'}).update('<img src="images/edit_img.png" />'));
		td_delete.insert(new Element('br'));
		td_delete.insert(new Element('a', {'onclick': 'groupDelete("' + item['cn'] + '");'}).update('<img src="images/delete_img.png" />'));
		tr.insert(td_delete);
		
		//td_delete.insert(new Element('a', {'onclick': 'invis.requestGroupDetails(' + item.gidnumber + ', groupDetailsResponse);'}).update('E'));
		//td_delete.insert(new Element('a', {'onclick': 'delete_group(' + item.gidnumber + ');'}).update('X'));
		
		$('result-body').insert(tr);
	}
	// account type selector
	var check = new Element('input', {'type': 'checkbox'});
	check.checked = GROUPLIST_BI_FLAG;
	
	check.observe('click',
		function(e) {
			GROUPLIST_BI_FLAG = this.checked;
			filterGroups();
			populateGroupList(null, 0);
		}
	);

	$('result-paging').insert(check);
	$('result-paging').insert(' BuiltIn-Gruppen einblenden<br/>');

	var n_entries = PAGED_DATA.length;
	var n_pages = Math.ceil(n_entries / PAGE_SIZE);
	for (var i = 0; i < n_pages; i++) {
		var a = new Element('a', {'class': 'page-link'}).update(i + 1);
		if (i == PAGE_CURRENT)
			a.addClassName('page-active');
		else
			a.observe('click', populateGroupList);
		$('result-paging').insert(a);
	}
	
}


//**********************************************************************
// show details
//**********************************************************************

// userdetails

function userDetailsResponse(request) {
	lightbox.setWaitStatus(false);
	var data = request.responseText.evalJSON();
	lightbox.setTitle(new Element('div', {'class': 'section-title'}).update('Benutzerdetails'));
	
	var box = new Element('table', {'id': 'userbox', 'cellpadding': '0', 'cellspacing': '0'});
	lightbox.getContent().insert(box);
	
	var tr_content = new Element('tr');
	tr_content.insert(new Element('td', {'id': 'userbox_content'}));
	box.insert(tr_content);
	
	lightbox.addButton('<button onclick="userMod(\'' + data['uid'] + '\');">Speichern</button><button onclick="lightbox.hide();">Abbrechen</button>');
	//lightbox.addButton('<button onclick="tmpFunction(\'' + data['uid'] + '\', \'userbox_content\');">Speichern</button><button onclick="lightbox.hide();">Abbrechen</button>');
	
	var rows = $H({
					'uid': false,
					'rid': false,
					'email': true,
					'display_name': true,
					'firstname': true,
					'surname': true,
					'description': true,
					'department': true,
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
					'department': 'Abteilung',
					'office': 'Büro',
					'telephone': 'Telefon',
					'firstname': 'Vorname'
				});

	$('userbox_content').insert(new Element('div', {'style': 'display: none;'}).update(data['dn']));
	lightbox.setData(new DetailStorage(data, rows));
	
	rows.each (
		function (item) {
			// attribute description
			var line = new Element('div', {'class': 'line'});
			line.insert(new Element('div', {'class': 'key'}).update(row_names.get(item.key)));
			
			// attribute value
			var value_div = new Element('div');
			value_div.update(data[item.key]);
			// 'key' attribute to identify
			value_div.writeAttribute('key', item.key)
			
			if (item.value == true) {
				value_div.addClassName('value');
				// .bind is neccessary
				value_div.observe('click', lightbox.inputBoxNew.bind(lightbox));
			} else {
				value_div.addClassName('value_disabled');
			}
			
			line.insert(value_div);
			$('userbox_content').insert(line);
		}
	);
	
	lightbox.update();
}



// groupdetails
function groupDetailsResponse(request) {
	lightbox.setWaitStatus(false);
	var data = request.responseText.evalJSON()[0];
	var users_group = request.responseText.evalJSON()[1];
	var users_not = request.responseText.evalJSON()[2];
	
	// in case we get NULL or just 1 entry
	if (!Object.isArray(users_group)) {
		var arr = $A();
		if (Object.isString(users_group)) arr.push(users_group);
		users_group = arr;
	}
		
	if (!Object.isArray(users_not)) {
		var arr = $A();
		if (Object.isString(users_not)) arr.push(users_not);
		users_not = arr;
	}
	
	lightbox.setTitle(new Element('div', {'class': 'section-title'}).update('Grupppendetails'));
	
	var box = new Element('table', {'id': 'groupbox', 'cellpadding': '0', 'cellspacing': '0'});
	lightbox.getContent().insert(box);
	
	var tr_content = new Element('tr');
	tr_content.insert(new Element('td', {'id': 'groupbox_content'}));
	box.insert(tr_content);
	
	lightbox.addButton('<button onclick="groupMod(\'' + data['cn'] + '\');">Speichern</button><button onclick="lightbox.hide();">Abbrechen</button>');
	
	var rows = $H({
					'cn': false,
					'rid': false,
					'description': true
				});
	
	var row_names = $H({
					"cn": "Name",
					"rid": "RID",
					"description": "Beschreibung"
				});
	
	lightbox.setData(new DetailStorage(data, rows));
	
	rows.each (
		function (item) {
			// attribute description
			var line = new Element('div', {'class': 'line'});
			line.insert(new Element('div', {'class': 'key'}).update(row_names.get(item.key)));
			
			// attribute value
			var value_div = new Element('div', {'class': 'value'}).update(data[item.key]);
			// 'key' attribute to identify
			value_div.writeAttribute('key', item.key)
			
			if (item.value == true) {
				value_div.addClassName('value');
				// .bind is neccessary
				value_div.observe('click', lightbox.inputBoxNew.bind(lightbox));
			} else {
				value_div.addClassName('value_disabled');
			}
			line.insert(value_div);
			$('groupbox_content').insert(line);
		}
	);
	
	// grouplists table
	$('groupbox_content').insert('<table id="groupbox_table"><tr class="line"><td colspan="3" class="key">Gruppenmitglieder</td></tr><tr><td id="groupbox_left"></td><td id="groupbox_center"></td><td id="groupbox_right"></td></tr></table>');
	
	// user-in-group box
	var select_in = new Element('select', {'id': 'grouplist_in', 'class': 'listbox', 'size': 2, 'multiple': 'multiple'});
	
	// add group members
	users_group.each(
		function (user) {
			select_in.insert(new Element('option').update(user));
		}
	);
	$('groupbox_left').insert(select_in);
	
	// user-move arrows
	var arrow_in = new Element('img', {'src': 'images/arrow_left.png'});
	var arrow_out = new Element('img', {'src': 'images/arrow_right.png'});
	
	// observer methods for user-move arrows
	arrow_in.observe('click', function(event) {
		while ($('grouplist_out').selectedIndex >= 0) {
			var i = $('grouplist_out').selectedIndex;
			$('grouplist_out').options[i].selected = false;
			$('grouplist_in').insert($('grouplist_out').options[i]);
		}
		listSort($('grouplist_in'));
		updateMemberUID($('grouplist_in'));
	});
	
	arrow_out.observe('click', function(event) {
		while ($('grouplist_in').selectedIndex >= 0) {
			var i = $('grouplist_in').selectedIndex;
			$('grouplist_in').options[i].selected = false;
			$('grouplist_out').insert($('grouplist_in').options[i]);
		}
		listSort($('grouplist_out'));
		updateMemberUID($('grouplist_in'));
	});
	
	$('groupbox_center').insert(arrow_in);
	$('groupbox_center').insert(new Element('br'));
	$('groupbox_center').insert(arrow_out);
	
	
	// user-not-in-group box
	var select_not = new Element('select', {'id': 'grouplist_out', 'class': 'listbox', 'size': 2, 'multiple': 'multiple'});
	
	// add non-group members
	users_not.each(
		function (user) {
			select_not.insert(new Element('option').update(user));
		}
	);
	$('groupbox_right').insert(select_not);
	
	listSort($('grouplist_in'));
	listSort($('grouplist_out'));
	lightbox.update();
}

// sort a nodeList of <option> tags
function listSort(list) {
	var data = list.childNodes;
	var arr = new Array();
	
	for (var i = data.length - 1; i >= 0; i--) arr.push( data[i].remove() );
	
	// by numerical value
	//arr.sort(function (a, b) { return a.value - b.value; });
	
	// by textual representation
	arr.sort(function (a, b) {
		if (a.text == b.text) return 0;
		return (a.text < b.text)?-1:1;
	});
	arr.each(function (item) { list.insert(item); });
}

//
function hostDetailsResponse(request) {
	lightbox.setWaitStatus(false);
	//Ab hier gehts schief
	var data = request.responseText.evalJSON();
	lightbox.setTitle(new Element('div', {'class': 'section-title'}).update('PC-Details'));
	
	var box = new Element('table', {'id': 'userbox', 'cellpadding': '0', 'cellspacing': '0'});
	lightbox.getContent().insert(box);
	
	var tr_content = new Element('tr');
	tr_content.insert(new Element('td', {'id': 'userbox_content'}));
	box.insert(tr_content);
	
	lightbox.addButton('<button onclick="hostMod(\'' + data['cn'] + '\');">Speichern</button><button onclick="lightbox.hide();">Abbrechen</button>');
	
	// editable attributes
	var rows = $H({
					'cn': true,
					'iscdhcpcomments': true,
					'iscdhcphwaddress': false,
					'iscdhcpstatements': false
				});
	
	// attribute description
	var row_names = $H({
					'cn': 'Name',
					'iscdhcpcomments': 'Standort',
					'iscdhcphwaddress': 'MAC',
					'iscdhcpstatements': 'IP'
				});
	
	$('userbox_content').insert(new Element('div', {'style': 'display: none;'}).update(data['dn']));
	lightbox.setData(new DetailStorage(data, rows));
	
	rows.each (
		function (item) {
			// attribute description
			var line = new Element('div', {'class': 'line'});
			line.insert(new Element('div', {'class': 'key'}).update(row_names.get(item.key)));
			
			// attribute value
			var value_div = new Element('div');
			if (item.key == 'iscdhcpstatements') {
				value_div.update(data[item.key].split(' ')[1]);
			} else if (item.key == 'iscdhcphwaddress') {
				var value = data[item.key].split(' ')[1].split(':');
				for (var i = 0; i < 6; i++) {
					var input = new Element('input', {'size': 2, 'maxlength': 2, 'style': 'width: 2em; text-align: center;'});
					input.value = value[i];
					value_div.insert(input);
					input.observe('blur', hostAddMAC);
					if (i < 5) value_div.insert(':');
				}
			} else {
				value_div.update(data[item.key]);
			}
			
			// 'key' attribute to identify
			value_div.writeAttribute('key', item.key)
			
			if (item.value == true) {
				value_div.addClassName('value');
				// .bind is neccessary
				value_div.observe('click', lightbox.inputBoxNew.bind(lightbox));
			} else {
				value_div.addClassName('value_disabled');
			}
			
			line.insert(value_div);
			$('userbox_content').insert(line);
		}
	);
	
	lightbox.update();
}

// update memberuid data

function updateMemberUID(list) {
	var data = $A(list.childNodes);
	var arr = new Array();
	
	data.each(
		function (item) {
			arr.push(item.value);
		}
	);
	
	lightbox.data.set('memberuid', arr);
}



// entry modification request?
function doodat(request) {
	if (request == null) build_user_mod_request();
	else {
		lightbox.hide();
	} 
}

// create entry mod request
function build_user_mod_request() {
	var node = $('userbox_content');
	var dn = node.firstChild.textContent;
	
	var hash = $H();
	for (var i = 1; i < node.childNodes.length; i++) {
		var item = node.childNodes[i];
		var k = item.childNodes[1].textContent;
		var v = item.childNodes[2].textContent;
		hash.set(k, v);
	}

	request_user_mod(dn, hash.toJSON());
}


//
// DHCP/DNS STUFF
// 

function hostListResponse(request) {
	//PAGED_DATA = request.responseText.evalJSON();
	//PAGED_DATA.sort(hostsort);
	
	var title = $('admin-content-title');
	var content = $('admin-content-content');
	content.innerHTML = "";
	
	filterHosts(request.responseText.evalJSON());
	PAGE_CURRENT = 0;
	
	// header
	title.update('Hostliste:');

	var p = new Element('div', {'id': 'result-paging'});
	content.insert(p);
	
	content.insert('<table id="result-table" cellspacing="0" cellpadding="0"><thead><tr><th class="name">Ping</th><th class="name">Host</th><th class="name">MAC</th><th class="name">IP</th><th class="name">Typ</th><th class="name">Standort</th><th class="delete">Bearbeiten</th></tr></thead><tbody id="result-body"></tbody></table>');
	
	populateHostList(null, 0);

	// add host button
	var node = new Element('table', {'onclick': 'hostAdd();', 'style': 'font-size: 0.8em; font-weight: bold; cursor: pointer; padding: 5px;'}).update('<tr><td><img src="images/host.png" /></td><td style="vertical-align: middle;">Gerät hinzufügen</td></tr>');
	content.insert(node);
	
	// discover hosts button
	var node2 = new Element('table', {'onclick': 'hostDiscover();', 'style': 'font-size: 0.8em; font-weight: bold; cursor: pointer; padding: 5px;'}).update('<tr><td><img src="images/host.png" /></td><td style="vertical-align: middle;">Geräte suchen</td></tr>');
	content.insert(node2);
}

function populateHostList(event, page) {
	if (page == null) page = this.firstChild.nodeValue -1;
	PAGE_CURRENT = page;

	$('result-body').innerHTML = "";
	$('result-paging').innerHTML = "";

	// Alle stoppen
	stopAllPingers();
	
	// Hostlist
	
	for (var i = page * PAGE_SIZE; i < (page+1) * PAGE_SIZE; i++) {
		if (PAGED_DATA[i] == null) break;
		var id = "host_list_entry" + i;
		var host = PAGED_DATA[i];
		var ip = host['iscdhcpstatements'].split(' ');
		var mac = host['iscdhcphwaddress'].split(' ');
		// Location hinzugefuegt
		var location = host['location'];
		var tr = new Element('tr');
		
		if (PINGER_FLAG == true) {
		    var td_ping = new Element('td', {'id': id, 'style': 'vertical-align: middle; width: 16px;'}).update('<img src="images/ajax-loader.gif" width="16px" height="16px" />');
		    // Pinger
		    if (PINGER_REQUEST[i] == null)
		    {
			    PINGER_REQUEST[i] = new Ajax.PeriodicalUpdater(
			    id,
			    'script/ping.php',
			    { method: 'post', parameters: { ip: ip[1] }, frequency: 10, decay: 1}
			);
		    }
		} else {
		    var td_ping = new Element('td', {'id': id, 'style': 'vertical-align: middle; width: 16px;'}).update('<img src="images/cross_small.png" width="16px" height="16px" />');
		    if (PINGER_REQUEST[i])
		    {
		    	PINGER_REQUEST[i].stop();
		    	PINGER_REQUEST[i] = null;
		    }
		}
		
		var td_name = new Element('td');
		td_name.insert(new Element('span', {'class': 'name'}).update(host['cn']));
		td_name.insert(host['PING']);
		
		var td_mac = new Element('td');
		td_mac.insert(mac[1]);
		
		var td_ip = new Element('td');
		td_ip.insert(ip[1]);
		
		var td_type = new Element('td');
		// Hier ist der Unterschied zu populateUsersList:
		td_type.insert(host['TYPE']);
		//td_type.insert(HOST_TYPE[host.TYPE]);
		var td_location = new Element('td');
		td_location.insert(host['iscdhcpcomments']);

		var td_delete = new Element('td', {'class': 'delete'});
		var node_edit = new Element('a', {'onclick': 'lightbox.show(500, true); lightbox.setWaitStatus(true); invis.request("script/adajax.php", hostDetailsResponse, {c: "host_detail", u: "' + host['cn'] + '"});'}).update('<img src="images/edit_img.png" />');
		td_delete.insert(node_edit);
		td_delete.insert(new Element('br'));
		td_delete.insert(new Element('span', {'onclick': 'hostDelete(\'' + host['cn'] + '\');'}).update('<img src="images/delete_img.png" />'));
		
		tr.insert(td_ping);
		tr.insert(td_name);
		tr.insert(td_mac);
		tr.insert(td_ip);
		tr.insert(td_type);
		// Spalte Location eingefuegt
		tr.insert(td_location);
		tr.insert(td_delete);
		
		$('result-body').insert(tr);
	}

	// Versuch Checkboxen einzufuegen
	var check2 = new Array (HOSTLIST_FLAG.length);
	
	for (i = 0; i < check2.length; i++)
	{
	    // host type selector
	    check2[i] = new Element('input', {'type': 'checkbox'});
	    check2[i].checked = HOSTLIST_FLAG[i];
	    check2[i].indexNum = i;
	    
	    check2[i].observe('click',
	    	function(e) {
	    		HOSTLIST_FLAG[this.indexNum] = this.checked;
	    		filterHosts();
	    		populateHostList(null, 0);
	    	}
	    )
	
	    $('result-paging').insert(check2[i]);
	    $('result-paging').insert(HOST_TYPE[i] + ' einblenden');
	    if ((i > 0) && ((i%3 == 0) || (i == check2.length - 1)))
		$('result-paging').insert('<br/>');
	}

	// host pinger selector
	var check = new Element('input', {'type': 'checkbox'});
	check.checked = PINGER_FLAG;
	check.observe('click',
		function(e) {
			PINGER_FLAG = this.checked;
			populateHostList(null, 0);
		}
	);

	$('result-paging').insert(check);
	$('result-paging').insert(' Ping-Test aktivieren<br/>');


	// table with current page data
	var n_entries = PAGED_DATA.length;
	var n_pages = Math.ceil(n_entries / PAGE_SIZE);
	for (var i = 0; i < n_pages; i++) {
		var a = new Element('a', {'class': 'page-link'}).update(i + 1);
		if (i == PAGE_CURRENT)
			a.addClassName('page-active');
		else
			a.observe('click', populateHostList);
		$('result-paging').insert(a);
	}

	if (PINGER_FLAG == false) {
	    stopAllPingers();
	}
}

function stopAllPingers() {
    for (var i = 0; i < PINGER_REQUEST.length; i++)
    {
	if (PINGER_REQUEST[i])
	{
	    PINGER_REQUEST[i].stop();
	    PINGER_REQUEST[i] = null;
	}
    }
}

//
// Dienste
//

//
// Dienste anzeigen
//

function serviceListResponse(request) {
	PAGED_DATA = request.responseText.evalJSON();
	
	var title = $('admin-content-title');
	var content = $('admin-content-content');
	content.innerHTML = "";
	
	PAGE_CURRENT = 0;
	
	// header
	title.update('Dienste:');

	var p = new Element('div', {'id': 'result-paging'});
	content.insert(p);
	
	// Dienst | Info | Aktiviert | Status | Aktionen
	content.insert('<table id="result-table" cellspacing="0" cellpadding="0"><thead><tr><th class="name">Dienst</th><th class="name">Info</th><th class="name">Aktiviert</th><th class="name">Status</th><th class="delete">Aktionen</th></tr></thead><tbody id="result-body"></tbody></table>');
	
	populateServiceList(null, 0);

	content.insert(node2);
}

function populateServiceList(event, page) {
	if (page == null) page = this.firstChild.nodeValue -1;
	PAGE_CURRENT = page;

	$('result-body').innerHTML = "";
	$('result-paging').innerHTML = "";

	
	// Dienst | Info | Aktiviert | Status | Aktionen
	for (var i = page * PAGE_SIZE_SERVICE; i < (page+1) * PAGE_SIZE_SERVICE; i++) {
		if (PAGED_DATA[i] == null) break;
		var service = PAGED_DATA[i];
		
		var tr = new Element('tr');
		
		var td_name = new Element('td');
		td_name.insert(service['name']);
		
		var td_info = new Element('td');
		td_info.insert(service['info']);
		
		var td_enabled = new Element('td');
		td_enabled.insert(service['enabled']);
		
		var td_status = new Element('td');
		td_status.insert(service['status']);
		
		var td_action = new Element('td', {'class': 'delete'});
		td_action.insert(new Element('span', {'onclick': 'serviceStart(\'' + service['service'] + '\');'}).update('<img src="images/start_small.png" />'));
		td_action.insert(new Element('br'));
		td_action.insert(new Element('span', {'onclick': 'serviceStopp(\'' + service['service'] + '\');'}).update('<img src="images/stopp_small.png" />'));
		td_action.insert(new Element('br'));
		td_action.insert(new Element('span', {'onclick': 'serviceRestart(\'' + service['service'] + '\');'}).update('<img src="images/restart_small.png" />'));
		td_action.insert(new Element('br'));
		td_action.insert(new Element('span', {'onclick': 'serviceReload(\'' + service['service'] + '\');'}).update('<img src="images/reload_small.png" />'));
		
		tr.insert(td_name);
		tr.insert(td_info);
		tr.insert(td_enabled);
		tr.insert(td_status);
		tr.insert(td_action);
		
		$('result-body').insert(tr);
	}

	// table with current page data
	var n_entries = PAGED_DATA.length;
	var n_pages = Math.ceil(n_entries / PAGE_SIZE_SERVICE);
	for (var i = 0; i < n_pages; i++) {
		var a = new Element('a', {'class': 'page-link'}).update(i + 1);
		if (i == PAGE_CURRENT)
			a.addClassName('page-active');
		else
			a.observe('click', populateServiceList);
		$('result-paging').insert(a);
	}
}


//
// Dienste starten
//

function serviceStart(name) {
	if (confirm('Möchten Sie den Dienst "' + name + '" wirklich starten?')) {
		invis.request("script/adajax.php", serviceStartResponse, {c: 'service_start', u: name});
	}
}

function serviceStartResponse(request) {
	if (request.responseText == '0')
		invis.request('script/adajax.php', serviceListResponse, {c: 'service_list'}); // reload service list
	else
		alert('Dienst konnte nicht gestartet werden!\nMeldung: ' + request.responseText);
}

// Dienste stoppen

function serviceStopp(name) {
	if (confirm('Möchten Sie den Dienst "' + name + '" wirklich stoppen?')) {
		invis.request("script/adajax.php", serviceStoppResponse, {c: 'service_stopp', u: name});
	}
}

function serviceStoppResponse(request) {
	if (request.responseText == '0')
		invis.request('script/adajax.php', serviceListResponse, {c: 'service_list'}); // reload service list
	else
		alert('Dienst konnte nicht gestoppt werden!\nMeldung: ' + request.responseText);
}

// Dienste neustarten

function serviceRestart(name) {
	if (confirm('Möchten Sie den Dienst "' + name + '" wirklich neu starten?')) {
		invis.request("script/adajax.php", serviceRestartResponse, {c: 'service_restart', u: name});
	}
}

function serviceRestartResponse(request) {
	if (request.responseText == '0')
		invis.request('script/adajax.php', serviceListResponse, {c: 'service_list'}); // reload service list
	else
		alert('Dienst konnte nicht neu gestartet werden!\nMeldung: ' + request.responseText);
}


// Dienste neuladen

function serviceReload(name) {
	if (confirm('Möchten Sie den Dienst "' + name + '" wirklich neu laden?')) {
		invis.request("script/adajax.php", serviceReloadResponse, {c: 'service_reload', u: name});
	}
}

function serviceReloadResponse(request) {
	if (request.responseText == '0')
		invis.request('script/adajax.php', serviceListResponse, {c: 'service_list'}); // reload service list
	else
		alert('Dienst konnte nicht neu geladen werden!\nMeldung: ' + request.responseText);
}

//
// DELETE USER / GROUP / HOST
//


function userDelete(uid) {
	if (confirm('Möchten Sie den Benutzer "' + uid + '" wirklich löschen?')) {
		var flag = (confirm('Verzeichnis /home/'+ uid + " löschen?"))?1:0;
		invis.request("script/adajax.php", userDeleteResponse, {c: 'user_delete', u: uid, t: flag});
	}
	
}

function userDeleteResponse(request) {
	if (request.responseText == '0')
		invis.request('script/adajax.php', userListResponse, {c: 'user_list'}); // reload user list
	else
		alert('Benutzer konnte nicht gelöscht werden!' + request.responseText);
}



function groupDelete(cn) {
	if (confirm('Möchten Sie die Gruppe "' + cn + '" wirklich löschen?'))
		invis.request("script/adajax.php", groupDeleteResponse, {c: "group_delete", u: cn});
}



function groupDeleteResponse(request) {
	if (request.responseText == '0')
		invis.request('script/adajax.php', groupListResponse, {c: 'group_list'}); // reload group list
	else
		alert('AD-Standard-Gruppen können nicht gelöscht werden! Fehlercode: ' + request.responseText);
}


function hostDelete(cn) {
	if (confirm('Möchten Sie den PC "' + cn + '" wirklich löschen?'))
		invis.request("script/adajax.php", hostDeleteResponse, {c: 'host_delete', u: cn});
}
function hostDeleteResponse(request) {
	if (request.responseText == '0')
		invis.request('script/adajax.php', hostListResponse, {c: 'host_list'}); // reload host list
	else
		alert('PC konnte nicht gelöscht werden!' + request.responseText);
}

//
// MODIFY USER / GROUP / HOST
//

function userMod(uid) {
	var data = lightbox.data.getHash().toJSON();
	invis.setCookie('invis-request', data);
	invis.request('script/adajax.php', userModResponse, {c: 'user_mod', u: uid});
}



function userModResponse(request) {
	invis.deleteCookie('invis-request');
	if (request.responseText == '"Success"' ) { 
	    lightbox.setStatus('Änderungen wurden gespeichert!',3,true);
	
	} else {
		lightbox.setStatus("Änderungen konnten nicht gespeichert werden!<br>" + request.responseText);
	}
}


function groupMod(cn) {
	var data = lightbox.data.getHash().toJSON();
	invis.setCookie('invis-request', data);
	invis.request('script/adajax.php', groupModResponse, {c: 'group_mod', u: cn});
}


function groupModResponse(request) {
	invis.deleteCookie('invis-request');
	if (request.responseText == '0') lightbox.setStatus('Änderungen wurden gespeichert!',3,true);
	else {
		lightbox.setStatus("Änderungen konnten nicht gespeichert werden!<br>" + request.responseText);
	}
}


function hostMod(cn) {
	var data = lightbox.data.getHash().toJSON();
	invis.setCookie('invis-request', data);
	invis.request('script/adajax.php', hostModResponse, {c: 'host_mod', u: cn});
}

function hostModResponse(request) {
	invis.deleteCookie('invis-request');
	if (request.responseText == '0') {
		lightbox.setStatus('Änderungen wurden gespeichert!');
		window.setTimeout("invis.request('script/adajax.php', hostListResponse, {c: 'host_list'}); lightbox.hide();", 1000);
	}
	else {
		lightbox.setStatus("Änderungen konnten nicht gespeichert werden!<br>" + request.responseText);
	}
}
//
// ADD USER / GROUP / HOST
//

// show user add box

function userAdd() {
	var account_type = 0; // 0: user, 1: admin, 2: gast, 3: mail, 4: groupware
	lightbox.show(500, true);
	//var data = request.responseText.evalJSON();
	lightbox.setTitle(new Element('div', {'class': 'section-title'}).update('Benutzerdetails'));
	
	var box = new Element('table', {'id': 'userbox', 'cellpadding': '0', 'cellspacing': '0'});
	lightbox.getContent().insert(box);
	
	var tr_content = new Element('tr');
	tr_content.insert(new Element('td', {'id': 'userbox_content'}));
	box.insert(tr_content);
	var tmp_btn = new Element('button').update('Speichern');
	tmp_btn.observe('click', function () {
		var uid = lightbox.data.get('uid');
		//change login-names to lower case.
		var uid = uid.toLowerCase();
		invis.setCookie('invis-request', lightbox.data.getHash().toJSON());
		invis.request('script/adajax.php', userAddResponse, {c: 'user_create', u: uid, t: account_type});
	});

	lightbox.addButton(tmp_btn);
	lightbox.addButton('<button onclick="lightbox.hide();">Abbrechen</button>');

//	lightbox.addButton('<button onclick="userAddRequest();">Speichern</button><button onclick="lightbox.hide();">Abbrechen</button>');

	var rows = $H({
					'uid': true,
//					'rid': false,
					'email': true,
					'display_name': true,
					'firstname': true,
					'surname': true,
					'description': true,
					'department': true,
					'office': true,
					'telephone': true,
					'userpassword': true
				});
	
	// attribute description
	var row_names = $H({
					'uid': 'Login*',
//					'rid': 'RID',
					'email': 'Email extern',
					'display_name': 'Anzeigename',
					'userpassword': 'Passwort*',
					'surname': 'Nachname*',
					'description': 'Beschreibung',
					'department': 'Abteilung',
					'office': 'Büro',
					'telephone': 'Telefon',
					'firstname': 'Vorname*'
				});

	
	lightbox.setData(new DetailStorage('{}', rows));
	
	rows.each (
		function (item) {
			// attribute description
			var line = new Element('div', {'class': 'line'});
			line.insert(new Element('div', {'class': 'key'}).update(row_names.get(item.key)));
			
			// attribute key
			line.insert(new Element('div', {'style': 'display: none;'}).update(item.key));
			// attribute editable
			line.insert(new Element('div', {'style': 'display: none;'}).update(item.value));
			
			// attribute value
			var value_div = new Element('div');
			value_div.update('');
			// 'key' attribute to identify
			value_div.writeAttribute('key', item.key)
			
			if (item.value == true) {
				value_div.addClassName('value');
				// .bind is neccessary
				value_div.observe('click', lightbox.inputBoxNew.bind(lightbox));
			} else {
				value_div.addClassName('value_disabled');
			}
			
			line.insert(value_div);
			$('userbox_content').insert(line);
		}
	);
	
	var line = new Element('div', {'class': 'line'});
	line.insert(new Element('div', {'class': 'key'}).update('Konten-Typ'));
	var sel = new Element('select', {'style': 'width: 60%'});
	sel.insert(new Element('option', {'value': 0}).update('Gast'));
	sel.insert(new Element('option', {'value': 1}).update('Mailkonto'));
	sel.insert(new Element('option', {'value': 3}).update('Windows+Unix'));
	sel.insert(new Element('option', {'value': 4}).update('Windows+Unix+Groupware'));
	sel.insert(new Element('option', {'value': 6}).update('WinAdmin+Unix'));
	sel.insert(new Element('option', {'value': 7}).update('WinAdmin+Unix+Groupware'));
	sel.observe('change', function(e) { account_type = this.value; });
	var value_div = new Element('div');
	value_div.insert(sel);
	//value_div.addClassName('value');
	line.insert(value_div);
	$('userbox_content').insert(line);
	
	lightbox.update();
}


function userAddRequest(type) {
	var uid = lightbox.data.get('uid');
	invis.setCookie('invis-request', lightbox.data.getHash().toJSON());
	invis.request('script/adajax.php', userAddResponse, {c: 'user_create', u: uid, t: type});
}



function userAddResponse(request) {
	if (request.responseText == '0') {
		invis.request('script/adajax.php', userListResponse, {c: 'user_list'});
		lightbox.hide();
	} else {
		lightbox.setStatus('Benutzer konnte nicht erstellt werden!<br />' + request.responseText);
	}
}

// show user add box
function groupAdd(request) {
	var group_type = 0;
	var dir_type = 0;
	lightbox.show(500, true);
	var users_not = request.responseText.evalJSON()[0];
	var templates = request.responseText.evalJSON()[1];
	
	lightbox.setTitle(new Element('div', {'class': 'section-title'}).update('Gruppendetails'));
	
	var box = new Element('table', {'id': 'groupbox', 'cellpadding': '0', 'cellspacing': '0'});
	lightbox.getContent().insert(box);
	
	var tr_content = new Element('tr');
	tr_content.insert(new Element('td', {'id': 'groupbox_content'}));
	box.insert(tr_content);

	var tmp_btn = new Element('button').update('Speichern');
	tmp_btn.observe('click', function () {
		var cn = lightbox.data.get('cn');
		invis.setCookie('invis-request', lightbox.data.getHash().toJSON());
		invis.request('script/adajax.php', groupAddResponse, {c: 'group_create', u: cn, t: group_type, d: dir_type});
	});

	lightbox.addButton(tmp_btn);
	lightbox.addButton('<button onclick="lightbox.hide();">Abbrechen</button>');
	
//	lightbox.addButton('<button onclick="groupAddRequest();">Speichern</button><button onclick="lightbox.hide();">Abbrechen</button>');
	
	var rows = $H({
					'cn': true,
					'description': true
				});
	
	var row_names = $H({
					"cn": "Name",
					"description": "Beschreibung"
				});
	
	lightbox.setData(new DetailStorage('{}', rows));
	
	rows.each (
		function (item) {
			// attribute description
			var line = new Element('div', {'class': 'line'});
			line.insert(new Element('div', {'class': 'key'}).update(row_names.get(item.key)));
			
			// attribute value
			var value_div = new Element('div', {'class': 'value'}).update('');
			// 'key' attribute to identify
			value_div.writeAttribute('key', item.key)
			
			if (item.value == true) {
				value_div.addClassName('value');
				// .bind is neccessary
				value_div.observe('click', lightbox.inputBoxNew.bind(lightbox));
			} else {
				value_div.addClassName('value_disabled');
			}
			line.insert(value_div);
			$('groupbox_content').insert(line);
		}
	);
	
	// Hier Pulldown fuer Gruppentyp
	var line = new Element('div', {'class': 'line'});
	line.insert(new Element('div', {'class': 'key'}).update('Gruppen-Typ'));
	var sel = new Element('select', {'style': 'width: 60%'});
	sel.insert(new Element('option', {'value': 0}).update('Team'));
	sel.insert(new Element('option', {'value': 1}).update('Team+Gruppenmail'));
	sel.insert(new Element('option', {'value': 2}).update('Mail-Verteiler'));
	sel.observe('change', function(e) { group_type = this.value; });
	var value_div = new Element('div');
	value_div.insert(sel);
	//value_div.addClassName('value');
	line.insert(value_div);
	$('groupbox_content').insert(line);;

	// Hier Pulldown fuer Gruppenverzeichnis
	var line = new Element('div', {'class': 'line'});
	line.insert(new Element('div', {'class': 'key'}).update('Verzeichnis'));
	var sel1 = new Element('select', {'style': 'width: 60%'});
	sel1.insert(new Element('option', {'value': 0}).update('Leeres Verzeichnis'));
	sel1.insert(new Element('option', {'value': 1}).update('Kein Verzeichnis'));
	var template_index = 2;
	templates.each(
		function (template) {
			sel1.insert(new Element('option', {'value': template_index}).update(template));
			template_index++;
		}
	);
	sel1.observe('change', function(e) { dir_type = this.value; });
	var value_div = new Element('div');
	value_div.insert(sel1);
	//value_div.addClassName('value');
	line.insert(value_div);
	$('groupbox_content').insert(line);;
	
	// grouplists table
	$('groupbox_content').insert('<table id="groupbox_table"><tr class="line"><td colspan="3" class="key">Gruppenmitglieder</td></tr><tr><td id="groupbox_left"></td><td id="groupbox_center"></td><td id="groupbox_right"></td></tr></table>');
	
	// user-in-group box
	var select_in = new Element('select', {'id': 'grouplist_in', 'class': 'listbox', 'size': 2, 'multiple': 'multiple'});
	$('groupbox_left').insert(select_in);
	
	// user-move arrows
	var arrow_in = new Element('img', {'src': 'images/arrow_left.png'});
	var arrow_out = new Element('img', {'src': 'images/arrow_right.png'});
	
	// observer methods for user-move arrows
	arrow_in.observe('click', function(event) {
		while ($('grouplist_out').selectedIndex >= 0) {
			var i = $('grouplist_out').selectedIndex;
			$('grouplist_out').options[i].selected = false;
			$('grouplist_in').insert($('grouplist_out').options[i]);
		}
		listSort($('grouplist_in'));
		updateMemberUID($('grouplist_in'));
	});
	
	arrow_out.observe('click', function(event) {
		while ($('grouplist_in').selectedIndex >= 0) {
			var i = $('grouplist_in').selectedIndex;
			$('grouplist_in').options[i].selected = false;
			$('grouplist_out').insert($('grouplist_in').options[i]);
		}
		listSort($('grouplist_out'));
		updateMemberUID($('grouplist_in'));
	});
	
	$('groupbox_center').insert(arrow_in);
	$('groupbox_center').insert(new Element('br'));
	$('groupbox_center').insert(arrow_out);
	
	
	// user-not-in-group box
	var select_not = new Element('select', {'id': 'grouplist_out', 'class': 'listbox', 'size': 2, 'multiple': 'multiple'});
	
	// add non-group members
	users_not.each(
		function (user) {
			select_not.insert(new Element('option').update(user));
		}
	);
	$('groupbox_right').insert(select_not);
	
	listSort($('grouplist_in'));
	listSort($('grouplist_out'));
	lightbox.update();
}

function groupAddRequest() {
	var cn = lightbox.data.get('cn');
	invis.setCookie('invis-request', lightbox.data.getHash().toJSON());
	invis.request('script/adajax.php', groupAddResponse, {c: 'group_create', u: cn, t: group_type, d: dir_type});
}



function groupAddResponse(request) {
	if (request.responseText == '0') {
		invis.request('script/adajax.php', groupListResponse, {c: 'group_list'});
		lightbox.hide();
	} else {
		lightbox.setStatus('Gruppe konnte nicht erstellt werden!<br />' + request.responseText);
	}
}


// show host add box
function hostAdd() {
	var host_type = 0;
	
	lightbox.show(500, true);
	
	lightbox.setTitle(new Element('div', {'class': 'section-title'}).update('Gerät hinzufügen'));
	
	var box = new Element('table', {'id': 'groupbox', 'cellpadding': '0', 'cellspacing': '0'});
	lightbox.getContent().insert(box);
	
	var tr_content = new Element('tr');
	tr_content.insert(new Element('td', {'id': 'groupbox_content'}));
	box.insert(tr_content);
	
	var tmp_btn = new Element('button').update('Speichern');
	tmp_btn.observe('click', function () {
		var data = lightbox.data.getHash();
		invis.setCookie('invis-request', data.toJSON());
		invis.request('script/adajax.php', hostAddResponse, {c: 'host_create', u: data.get('cn'), t: host_type})
	});
	lightbox.addButton(tmp_btn);
	lightbox.addButton('<button onclick="lightbox.hide();">Abbrechen</button>');
	
	var rows = $H({
					'cn': true,
					// Eingabefeld location editierbar
					'location': true
				});
	
	var row_names = $H({"cn": "Name",
			// Eingabefeld Standort hinzugefuegt
			    "location": "Standort"
				});
	
	lightbox.setData(new DetailStorage('{}', rows));
	
	var line = new Element('div', {'class': 'line'});
	line.insert(new Element('div', {'class': 'key'}).update('Typ'));
	var sel = new Element('select', {'style': 'width: 30%'});
	sel.insert(new Element('option', {'value': 0}).update('PC'));
	sel.insert(new Element('option', {'value': 1}).update('Drucker'));
	sel.insert(new Element('option', {'value': 2}).update('Server'));
	sel.insert(new Element('option', {'value': 3}).update('IP-Gerät'));
	sel.observe('change', function(e) { host_type = this.value; });
	var value_div = new Element('div');
	value_div.insert(sel);
	//value_div.addClassName('value');
	line.insert(value_div);
	$('groupbox_content').insert(line);
	
	rows.each (
		function (item) {
			// attribute description
			var line = new Element('div', {'class': 'line'});
			line.insert(new Element('div', {'class': 'key'}).update(row_names.get(item.key)));
			
			// attribute value
			var value_div = new Element('div', {'class': 'value'}).update('');
			// 'key' attribute to identify
			value_div.writeAttribute('key', item.key)
			
			if (item.value == true) {
				value_div.addClassName('value');
				// .bind is neccessary
				value_div.observe('click', lightbox.inputBoxNew.bind(lightbox));
			} else {
				value_div.addClassName('value_disabled');
			}
			line.insert(value_div);
			$('groupbox_content').insert(line);
		}
	);
	
	var line = new Element('div', {'class': 'line'});
	line.insert(new Element('div', {'class': 'key'}).update('MAC-Adresse'));
	for (var i = 0; i < 6; i++) {
		var input = new Element('input', {'size': 2, 'maxlength': 2, 'style': 'width: 2em; text-align: center;'});
		line.insert(input);
		input.observe('blur', hostAddMAC);
		if (i < 5) line.insert(':');
	}
	$('groupbox_content').insert(line);
	
	lightbox.update();
}

function hostAddMAC(e) {
	var node = e.target.parentNode;
	var mac = $A();
	var str = '';
	
	$A(node.childNodes).each(
		function (item) {
			if (item.tagName == 'INPUT') {
				var str = item.value.toLowerCase();
				value = parseInt(str, 16);
				if ((value >= 0 && value <= 255) || str == '') item.setStyle({backgroundColor: 'white'});
				else item.setStyle({backgroundColor: 'red'});
				mac.push(str);
			}
		}
	);
	
	for (var i = 0; i < mac.length; i++) {
		str += mac[i];
		if (i < mac.length - 1) str += ':';
	}
	lightbox.data.set('iscdhcphwaddress', 'ethernet ' + str);
}

function hostAddRequest() {
	lightbox.setWaitStatus(true);
	var data = lightbox.data.getHash();
	invis.setCookie('invis-request', data.toJSON());
	invis.request('script/adajax.php', hostAddResponse, {c: 'host_create', u: data.get('cn')})
}

function hostAddResponse(request) {
	lightbox.setWaitStatus(false);
	if (request.responseText == 'null') {
		invis.request('script/adajax.php', hostListResponse, {c: 'host_list'});
		lightbox.hide();
	} else {
		lightbox.setStatus('PC konnte nicht erstellt werden!<br />' + request.responseText);
	}
}

function hostDiscover() {
	lightbox.show(500, true);
	lightbox.setWaitStatus(true);
	invis.request('script/dhcpleases.php', hostDiscoverResponse, {});
}

function hostDiscoverResponse(request) {
	lightbox.setWaitStatus(false);
	var data = request.responseText.evalJSON(true);
	
	lightbox.setTitle(new Element('div', {
		'class': 'section-title'
	}).update('Geräte suchen'));
	
	var box = new Element('table', {
		'id': 'host_discover',
		'cellpadding': '0',
		'cellspacing': '0',
		'border': '0'
	});
	lightbox.getContent().insert(box);
	
	box.insert('<tr><th>MAC</th><th>Status</th><th>Hinzufügen</th><th>Name</th><th>Typ</th></tr>');
	for (var i = 0; i < data.length; i++) {
		var item = data[i];
		box.insert('<tr><td>' + item['mac'] + '</td><td valign="middle" id="' + item['mac'] + '"><img src="images/ajax-loader.gif" width="16px" height="16px" /></td>' +
		'<td><input type="checkbox" /></td><td><input size="5" /></td><td><select size="1"><option>PC</option><option>Server</option><option>Drucker</option><option>IP-Gerät</option></select></td></tr>');
		new Ajax.PeriodicalUpdater(item['mac'], 'script/ping.php', {
			method: 'post',
			parameters: {
				ip: item['ip']
			},
			frequency: 10,
			decay: 1
		});
	}
	if (data.length == 0) box.insert('<tr><td colspan="5"><b>Keine Rechner gefunden.</b></td></tr>');
	
	var tmp_btn = new Element('button').update('Hinzufügen');
	tmp_btn.observe('click', function () {
		lightbox.setWaitStatus(true);
		//Stefan
		var data = lightbox.data.getHash();
		invis.setCookie('invis-request', data.toJSON());
		invis.request('script/adajax.php', hostAdd, {c: 'host_create', u: data.get('cn'), t: host_type})
	});
	
	lightbox.addButton(tmp_btn);
	lightbox.addButton('<button onclick="lightbox.hide();">Beenden</button>');
	lightbox.update();
}

function functionListResponse(request) {
	var title = $('admin-content-title');
	var content = $('admin-content-content');
	content.innerHTML = "";

	content.insert('<table id="result-table" cellspacing="0" cellpadding="0"><thead><tr><th class="name">Funktion</th><th class="name">Beschreibung</th></tr></thead><tbody id="result-body"></tbody></table>');

	// Funktionsschaltflaechen einfuegen
	// membermod button
	var node = new Element('table', {'onclick': 'exec_membermod();', 'style': 'font-size: 0.8em; font-weight: bold; cursor: pointer; padding: 5px;'}).update('<tr><td><img src="images/sysadmin.png" /></td><td style="vertical-align: middle;" width="250">Maschinenkonten erweitern</td><td style="vertical-align: middle; font-weight: normal;">Allen Maschinenkonten werden UNIX-Attribute hinzugefügt.</td></tr>');
	content.insert(node);

	var node = new Element('table', {'onclick': 'exec_fixgsacls();', 'style': 'font-size: 0.8em; font-weight: bold; cursor: pointer; padding: 5px;'}).update('<tr><td><img src="images/sysadmin.png" /></td><td style="vertical-align: middle;" width="250">Fix Groupshare ACLs</td><td style="vertical-align: middle; font-weight: normal;">Die Zugriffs- und Besitzrechte der Gruppenverzeichnisse werden zurückgesetzt.</td></tr>');
	content.insert(node);

}