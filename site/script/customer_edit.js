
  // key_handler: enable up- and down-arrows in a grid
  function key_handler (k,fldname,fldid,cols) {
    var unicode = k.keyCode ? k.keyCode : k.charCode;
    if (unicode != 38 && unicode != 40) return;
    
    if (unicode == 38) {  // up-arrow
      var nextfld = fldid - cols;
      if (nextfld < 1)
        return;
    } else {              // down-arrow
      var nextfld = fldid + cols;
      if (!document.getElementById(fldname+nextfld))
        return;
    }
    document.getElementById(fldname+nextfld).focus()
    return;
  }
  