/*
Sort <SELECT> field script by Babvailiica (functions added by Karen Ellrick)
www.babailiica.com
*/

function selectall(obj) {
	obj = (typeof obj == "string") ? document.getElementById(obj) : obj;
	if (obj.tagName.toLowerCase() != "select")
		return;
	for (i=0; i<obj.length; i++) {
		obj[i].selected = true;
	}
}

function mousewheel(obj) {
	obj = (typeof obj == "string") ? document.getElementById(obj) : obj;
	if (obj.tagName.toLowerCase() != "select")
		return;
	if (obj.selectedIndex != -1) {
		if (event.wheelDelta > 0) {
			up(obj);
		} else {
			down(obj);
		}
		return false;
	}
}

function sort2d(arrayName, element, num, cs) {
	if (num) {
		for (var i=0; i<(arrayName.length-1); i++) {
			for (var j=i+1; j<arrayName.length; j++) {
				if (parseInt(arrayName[j][element]) < parseInt(arrayName[i][element])) {
					var dummy = arrayName[i];
					arrayName[i] = arrayName[j];
					arrayName[j] = dummy;
				}
			}
		}
	} else {
		for (var i=0; i<(arrayName.length-1); i++) {
			for (var j=i+1; j<arrayName.length; j++) {
				if (cs) {
					if (arrayName[j][element].toLowerCase() < arrayName[i][element].toLowerCase()) {
						var dummy = arrayName[i];
						arrayName[i] = arrayName[j];
						arrayName[j] = dummy;
					}
				} else {
					if (arrayName[j][element] < arrayName[i][element]) {
						var dummy = arrayName[i];
						arrayName[i] = arrayName[j];
						arrayName[j] = dummy;
					}
				}
			}
		}
	}
}

/* sort the list!
by = 0 - order by text (default)
by = 1 - order by value
by = 2 - order by color
by = 3 - order by background color
by = 4 - order by class name
by = 5 - order by id
num = if true sorts numbers e.g. 2 before 10
cs = casesensitive e.g. a before Z*/
function listsort(obj, by, num, cs) {
	obj = (typeof obj == "string") ? document.getElementById(obj) : obj;
	by = (parseInt("0" + by) > 5) ? 0 : parseInt("0" + by);
	if (obj.tagName.toLowerCase() != "select" && obj.length < 2)
		return false;
	var elements = new Array();
	for (i=0; i<obj.length; i++) {
		elements[elements.length] = new Array(obj[i].text, obj[i].value, (obj[i].currentStyle ? obj[i].currentStyle.color : obj[i].style.color), (obj[i].currentStyle ? obj[i].currentStyle.backgroundColor : obj[i].style.backgroundColor), obj[i].className, obj[i].id, obj[i].selected);
	}
	sort2d(elements, by, num, cs);
	for (i=0; i<obj.length; i++) {
		obj[i].text = elements[i][0];
		obj[i].value = elements[i][1];
		obj[i].style.color = elements[i][2];
		obj[i].style.backgroundColor = elements[i][3];
		obj[i].className = elements[i][4];
		obj[i].id = elements[i][5];
		obj[i].selected = elements[i][6];
	}
}

function viceversa(obj) {
	obj = (typeof obj == "string") ? document.getElementById(obj) : obj;
	if (obj.tagName.toLowerCase() != "select" && obj.length < 2)
		return false;
	var elements = new Array();
	for (i=obj.length-1; i>-1; i--) {
		elements[elements.length] = new Array(obj[i].text, obj[i].value, obj[i].style.color, obj[i].style.backgroundColor, obj[i].className, obj[i].id, obj[i].selected);
	}
	for (i=0; i<obj.length; i++) {
		obj[i].text = elements[i][0];
		obj[i].value = elements[i][1];
		obj[i].style.color = elements[i][2];
		obj[i].style.backgroundColor = elements[i][3];
		obj[i].className = elements[i][4];
		obj[i].id = elements[i][5];
		obj[i].selected = elements[i][6];
	}
}

function top(obj) {
	obj = (typeof obj == "string") ? document.getElementById(obj) : obj;
	if (obj.tagName.toLowerCase() != "select" && obj.length < 2)
		return false;
	var elements = new Array();
	for (i=0; i<obj.length; i++) {
		if (obj[i].selected) {
			elements[elements.length] = new Array(obj[i].text, obj[i].value, obj[i].style.color, obj[i].style.backgroundColor, obj[i].className, obj[i].id, obj[i].selected);
		}
	}
	for (i=0; i<obj.length; i++) {
		if (!obj[i].selected) {
			elements[elements.length] = new Array(obj[i].text, obj[i].value, obj[i].style.color, obj[i].style.backgroundColor, obj[i].className, obj[i].id, obj[i].selected);
		}
	}
	for (i=0; i<obj.length; i++) {
		obj[i].text = elements[i][0];
		obj[i].value = elements[i][1];
		obj[i].style.color = elements[i][2];
		obj[i].style.backgroundColor = elements[i][3];
		obj[i].className = elements[i][4];
		obj[i].id = elements[i][5];
		obj[i].selected = elements[i][6];
	}
}

function bottom(obj) {
	obj = (typeof obj == "string") ? document.getElementById(obj) : obj;
	if (obj.tagName.toLowerCase() != "select" && obj.length < 2)
		return false;
	var elements = new Array();
	for (i=0; i<obj.length; i++) {
		if (!obj[i].selected) {
			elements[elements.length] = new Array(obj[i].text, obj[i].value, obj[i].style.color, obj[i].style.backgroundColor, obj[i].className, obj[i].id, obj[i].selected);
		}
	}
	for (i=0; i<obj.length; i++) {
		if (obj[i].selected) {
			elements[elements.length] = new Array(obj[i].text, obj[i].value, obj[i].style.color, obj[i].style.backgroundColor, obj[i].className, obj[i].id, obj[i].selected);
		}
	}
	for (i=obj.length-1; i>-1; i--) {
		obj[i].text = elements[i][0];
		obj[i].value = elements[i][1];
		obj[i].style.color = elements[i][2];
		obj[i].style.backgroundColor = elements[i][3];
		obj[i].className = elements[i][4];
		obj[i].id = elements[i][5];
		obj[i].selected = elements[i][6];
	}
}

function up(obj) {
	obj = (typeof obj == "string") ? document.getElementById(obj) : obj;
	if (obj.tagName.toLowerCase() != "select" && obj.length < 2)
		return false;
	var sel = new Array();
	for (i=0; i<obj.length; i++) {
		if (obj[i].selected == true) {
			sel[sel.length] = i;
		}
	}
	for (i in sel) {
		if (sel[i] != 0 && !obj[sel[i]-1].selected) {
			var tmp = new Array(obj[sel[i]-1].text, obj[sel[i]-1].value, obj[sel[i]-1].style.color, obj[sel[i]-1].style.backgroundColor, obj[sel[i]-1].className, obj[sel[i]-1].id);
			obj[sel[i]-1].text = obj[sel[i]].text;
			obj[sel[i]-1].value = obj[sel[i]].value;
			obj[sel[i]-1].style.color = obj[sel[i]].style.color;
			obj[sel[i]-1].style.backgroundColor = obj[sel[i]].style.backgroundColor;
			obj[sel[i]-1].className = obj[sel[i]].className;
			obj[sel[i]-1].id = obj[sel[i]].id;
			obj[sel[i]].text = tmp[0];
			obj[sel[i]].value = tmp[1];
			obj[sel[i]].style.color = tmp[2];
			obj[sel[i]].style.backgroundColor = tmp[3];
			obj[sel[i]].className = tmp[4];
			obj[sel[i]].id = tmp[5];
			obj[sel[i]-1].selected = true;
			obj[sel[i]].selected = false;
		}
	}
}

function down(obj) {
	obj = (typeof obj == "string") ? document.getElementById(obj) : obj;
	if (obj.tagName.toLowerCase() != "select" && obj.length < 2)
		return false;
	var sel = new Array();
	for (i=obj.length-1; i>-1; i--) {
		if (obj[i].selected == true) {
			sel[sel.length] = i;
		}
	}
	for (i in sel) {
		if (sel[i] != obj.length-1 && !obj[sel[i]+1].selected) {
			var tmp = new Array(obj[sel[i]+1].text, obj[sel[i]+1].value, obj[sel[i]+1].style.color, obj[sel[i]+1].style.backgroundColor, obj[sel[i]+1].className, obj[sel[i]+1].id);
			obj[sel[i]+1].text = obj[sel[i]].text;
			obj[sel[i]+1].value = obj[sel[i]].value;
			obj[sel[i]+1].style.color = obj[sel[i]].style.color;
			obj[sel[i]+1].style.backgroundColor = obj[sel[i]].style.backgroundColor;
			obj[sel[i]+1].className = obj[sel[i]].className;
			obj[sel[i]+1].id = obj[sel[i]].id;
			obj[sel[i]].text = tmp[0];
			obj[sel[i]].value = tmp[1];
			obj[sel[i]].style.color = tmp[2];
			obj[sel[i]].style.backgroundColor = tmp[3];
			obj[sel[i]].className = tmp[4];
			obj[sel[i]].id = tmp[5];
			obj[sel[i]+1].selected = true;
			obj[sel[i]].selected = false;
		}
	}
}

function inarray(v,a) {
	for (i in a) {
		if (a[i] == v) {
			return true;
		}
	}
	return false;
}

function clone(obj) {
	obj = (typeof obj == "string") ? document.getElementById(obj) : obj;
	if (obj.tagName.toLowerCase() != "select" && obj.length < 2)
		return false;
	var elements = new Array();
	for (i=0; i<obj.length; i++) {
		if (obj[i].selected) {
			elements[elements.length] = new Array(obj[i].text, obj[i].value, obj[i].style.color, obj[i].style.backgroundColor, obj[i].className, obj[i].id, obj[i].selected);
		}
	}
	for (i=0; i<elements.length; i++) {
//alert(elements.length+" items to add to current list of "+obj.length);
	     obj.length++;
		obj[obj.length-1].text = elements[i][0];
		obj[obj.length-1].value = elements[i][1];
		obj[obj.length-1].style.color = elements[i][2];
		obj[obj.length-1].style.backgroundColor = elements[i][3];
		obj[obj.length-1].className = elements[i][4];
		obj[obj.length-1].id = elements[i][5];
		obj[obj.length-1].selected = 0;  // don't want the new one selected
	}
}

function remove(obj) {
	obj = (typeof obj == "string") ? document.getElementById(obj) : obj;
	if (obj.tagName.toLowerCase() != "select" && obj.length < 2)
		return false;
	for (i=obj.length-1; i>-1; i--) {
            if (obj[i].selected){
                obj[i] = null;
		}
	}
}