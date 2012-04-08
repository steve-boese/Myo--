window.onload = window_initialize;

window.onunload = window_shutdown;

function window_initialize() {

  // if an input with tabindex of 1 is found, set focus
  if (document.getElementsByTagName) {
    var inputs = document.getElementsByTagName("input");
    for (var i=0; i<inputs.length; i++) {
      var input = inputs[i]
      if (input.getAttribute("tabindex") == 1) {
        input.focus();
        break;
      }
    }
  }

  // add target=_blank to links with rel=external,
  //     target=_terms to links with rel=terms
  if (document.getElementsByTagName) {
    var anchors = document.getElementsByTagName("a");
    for (var i=0; i<anchors.length; i++) {
      var anchor = anchors[i];
      if (anchor.getAttribute("href") && anchor.getAttribute("rel") == "external")
        anchor.target = "_blank";
      else if (anchor.getAttribute("href") && anchor.getAttribute("rel") == "terms")
        anchor.target = "_terms";
    }
  }

  // if mapload function is available, use it
  if (window.mapload) {
    mapload();
  }
}


function window_shutdown() {
  // if mapload function has been used, unload it
  if (window.mapload) {
    GUnload();
  }
}

function validate_form(thisform) {

  // if an error-message div is found, remove it
  var divs = document.getElementsByTagName("div");
  for (var i=0; i<divs.length; i++) {
    if (divs[i].className == "warning") {
      divs[i].parentNode.removeChild(divs[i]);
    }
  }

  var regInputerror = /\binputerror\b/;
  var regRequired   = /\brequired\b/;
  var regEmail      = /\bemail\b/;

  var focusset = false;
  var errormsg = "";
  var thisvalue;
  var thisclass;

  // use field class info to determine edit criteria
  var elements = thisform.elements;
  for (var i=0; i<elements.length; i++) {
    thisclass = elements[i].className;
    elements[i].className = thisclass.replace(/ inputerror/,"");
    thisvalue = elements[i].value.replace(/\s+/g,"");
    if (regRequired.test(thisclass) && thisvalue == "") {
      if (!focusset) {elements[i].focus(); focusset = true;}
      errormsg += notBlank(elements[i].title,elements[i].name) + " is required<br />";
      elements[i].className += " inputerror";
    } else if (regEmail.test(thisclass) && !valid_email(elements[i].value)) {
      if (!focusset) {elements[i].focus(); focusset = true;}
      errormsg += notBlank(elements[i].title,elements[i].name) + " is not valid<br />";
      elements[i].className += " inputerror";
    }
    if (elements[i].name == "password")
      pwdid = i;
    if (elements[i].name == "loginToken" && pwdid > 0) {
      elements[i].value += '.' + encodeToHex(elements[pwdid].value);
      elements[pwdid].value = '';
    }
  }

  // add error message to the document if needed
  if (!errormsg == "") {
    var errordiv = document.createElement("div");
    errordiv.className = "warning";
    errordiv.innerHTML = errormsg;
    thisform.parentNode.insertBefore(errordiv,thisform);
    return false;
  }

  var regCollectuserinfo = /\bcollectuserinfo\b/;
  if (regCollectuserinfo.test(thisform.className)) {
    adduserfields(thisform);
  }

}

function encodeToHex(str){
  var r="";
  var e=str.length;
  var c=0;
  var h;
  while(c<e){
    h=str.charCodeAt(c++).toString(16);
    while(h.length<2)
      h="0"+h;
    r+=h;
  }
  return r;
}

//    var infodiv = document.createElement("div");
//    infodiv.innerHTML = "<pre>" + dumpObj(navigator.cookieEnabled, "cookie", "  ", 4) + "</pre>";
//    thisform.parentNode.insertBefore(infodiv,thisform);
//    return false;

// add hidden fields for user's screen width, height, js version to form
function adduserfields(thisform) {
  var inputs = thisform.getElementsByTagName("input");
  for (var i=0; i<inputs.length; i++) {
    var input = inputs[i]
    if (input.getAttribute("type") == "submit") {
      var fld1 = document.createElement("input");
      fld1.type = "hidden";
      fld1.name = "screen[width]";
      fld1.value = screen.width;
      input.parentNode.insertBefore(fld1,input);
      var fld2 = document.createElement("input");
      fld2.type = "hidden";
      fld2.name = "screen[height]";
      fld2.value = screen.height;
      input.parentNode.insertBefore(fld2,input);
      var fld3 = document.createElement("input");
      fld3.type = "hidden";
      fld3.name = "screen[cookiesenabled]";
      fld3.value = navigator.cookieEnabled;
      input.parentNode.insertBefore(fld3,input);
      break;
    }
  }
}

  function dumpObj(obj, name, indent, depth) {
    var MAX_DUMP_DEPTH = 10;
    if (depth > MAX_DUMP_DEPTH) {
      return indent + name + ": <Maximum Depth Reached><br />";
    }
    if (typeof obj == "object") {
      var child = null;
      var output = indent + name + "<br />";
      indent += "\t";
      for (var item in obj)
      {
        try {
          child = obj[item];
        } catch (e) {
          child = "<Unable to Evaluate>";
        }
        if (typeof child == "object") {
          output += dumpObj(child, item, indent, depth + 1);
        } else {
          output += indent + item + ": " + child + "<br />";
        }
      }
      return output;
    } else {
      return obj;
    }
  }

// on focus, remove inputerror from class if needed
function clear_error(thisfield) {
  thisfield.className = thisfield.className.replace(/ inputerror/,"");
}

// on blur, test the field using edit criteria from class
function validate_field(thisfield) {
  var regInputerror = /\binputerror\b/;
  var regRequired   = /\brequired\b/;
  var regEmail      = /\bemail\b/;

  var thisvalue;
  var thisclass;

  thisclass = thisfield.className;
  thisclass = thisclass.replace(/ inputerror/,"");
  thisvalue = thisfield.value.replace(/\s+/g,"");
  if (regRequired.test(thisclass) && thisvalue == "") {
    thisclass += " inputerror";
  } else if (regEmail.test(thisclass) && !valid_email(thisvalue)) {
    thisclass += " inputerror";
  }
  thisfield.className = thisclass;
}

function valid_email(thisemail) {
   var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
   return reg.test(thisemail);
}

function notBlank(first,second) {
  if (first.length > 0) {
    return first;
  } else {
    return second;
  }
}

function dumpNavigator() {
  var browser = "BROWSER INFORMATION:\n";
  for(var propname in navigator) {
    browser += propname + ": " + navigator[propname] + "\n"
  }
  return browser;
}

function toggleCheckboxes() {
  var inputlist = document.getElementsByTagName("input");
  for (i = 0; i < inputlist.length; i++) {
    if ( inputlist[i].getAttribute("type") == 'checkbox' ) {
      if (inputlist[i].checked) inputlist[i].checked = false
      else inputlist[i].checked = true;
    }
  }
  return false;
}