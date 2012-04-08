
  function cleanupName(field_name) {
    var nameText = String(document.getElementById(field_name).value);
    nameText = nameText.toLowerCase();
    nameText = nameText.replace(" ","-");

    var strCurrentChar;
    for (var iLoop=0; iLoop < nameText.length; iLoop++) {
      strCurrentChar = nameText.substring(iLoop,iLoop+1);
      if (
    // if a-z...
    (strCurrentChar >= 'a' && strCurrentChar <= 'z')
    // or if 0-9...
    || (strCurrentChar >= '0' && strCurrentChar <= '9')
    // or if dash or underscore
    || strCurrentChar == '_' || strCurrentChar == '-'
    || (strCurrentChar == '.' && iLoop == 0)
     );

     // We have a valid string... do nothing.
    else
     if(strCurrentChar == " ")
        nameText = nameText.replace(strCurrentChar,"-");
     else
        nameText = nameText.replace(strCurrentChar,"");
    }
    document.getElementById(field_name).value = nameText;
  }
