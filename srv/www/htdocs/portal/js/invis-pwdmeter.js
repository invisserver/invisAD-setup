/*
Modified by Ingo Göppert from https://github.com/latsku/complexPasswdJS
*/

function chkPass2(password, name, username, nMinPwdLen, sComplex) {

  var quality = 0;
  
  if ( password.length < nMinPwdLen ) {
    return 0;
  }
  
  if ( sComplex == "off" ) {
    return 1;
  }
  
  if ( password.match(/a|ä|b|c|d|e|f|g|h|i|j|k|l|m|n|o|ö|p|q|r|s|ß|t|u|ü|v|w|x|y|z/) ) {
    quality++;
  }
  
  if ( password.match(/A|Ä|B|C|D|E|F|G|H|I|J|K|L|M|N|O|Ö|P|Q|R|S|T|U|Ü|V|W|X|Y|Z/) ) {
    quality++;
  }
  
  if ( password.match(/~|!|@|#|\$|%|\^|&|\*|_|-|\+|\=|`|\||\\|\(|\)|\{|\}|\[|\]|:|\;|\"|\'|<|>|,|\.|\?|\//) ) { 
  // http://msdn.microsoft.com/en-us/subscriptions/cc786468%28v=ws.10%29.aspx
  // ~!@#$%^&*_-+=`|\(){}[]:;"'<>,.?/
    quality++;
  }
  
  if ( password.match(/1|2|3|4|5|6|7|8|9|0/) ) {
    quality++;
  }
  
  if ( quality < 3 ) {
    return 0;
  }
  
  var noName = 0;
  var nameArray = String(name).toLowerCase().replace(/,|\.|-|–|—|_|\$|\t/g, " ").split(" ");
  for (part in nameArray) {
    if ( name.length >0 && nameArray[part].length >= 3 ) {
      if ( String(password).toLowerCase().indexOf(nameArray[part]) !== -1 ) {
        noName++;
      }
    }
  }
  
  if ( username.length > 0 && String(password).toLowerCase().indexOf(username) !== -1 ) {
    noName++;
  }
  
  if ( noName > 0 ) {
    return 0;
  } else {
    return 1;
  }
}

