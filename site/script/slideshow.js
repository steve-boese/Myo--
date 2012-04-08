  CSInit = new Array;
  
  function CSScriptInit() {
    if (typeof(skipPage) != "undefined") {
      if(skipPage) return;
    }
    idxArray = new Array;
    for (var i=0;i<CSInit.length;i++)
      idxArray[i] = i;
    CSAction2(CSInit, idxArray);
  }
  
  function newImage(arg) {
    if (document.images) {
      rslt = new Image();
      rslt.src = arg;
      return rslt;
    }
  }
  
  userAgent = window.navigator.userAgent;
  browserVers = parseInt(userAgent.charAt(userAgent.indexOf("/")+1),10);
  mustInitImg = true;
  function initImgID() {
    di = document.images;
    if (mustInitImg && di) {
      for (var i=0; i<di.length; i++) {
        if (!di[i].id) di[i].id=di[i].id;
      }
      mustInitImg = false;
    }
  }
  
  function findElement(n,ly) {
    d = document;
    if (browserVers < 4)
      return d[n];
    if ((browserVers >= 6) && (d.getElementById)) {
      initImgID;
      return(d.getElementById(n))
    }
    var cd = ly ? ly.document : d;
    var elem = cd[n];
    if (!elem) {
      for (var i=0;i<cd.layers.length;i++) {
        elem = findElement(n,cd.layers[i]);
        if (elem)
          return elem;
      }
    }
    return elem;
  }

  function changeImages() {
    d = document;
    if (d.images) {
      var img;
      for (var i=0; i<changeImages.arguments.length; i+=2) {
        img = null;
        if (d.layers) {
          img = findElement(changeImages.arguments[i],0);
        } else {
          img = d.images[changeImages.arguments[i]];
        }
        if (img)
          img.src = changeImages.arguments[i+1];
      }
    }
  }

  CSStopExecution=false;
  function CSAction(array) {
    return CSAction2(CSAct, array);
  }
  
  function CSAction2(fct, array) {
    var result;
    for (var i=0;i<array.length;i++) {
      if(CSStopExecution)
        return false;
      var aa = fct[array[i]];
      if (aa == null)
        return false;
      var ta = new Array;
      for(var j=1;j<aa.length;j++) {
        if ((aa[j]!=null)&&(typeof(aa[j])=="object")&&(aa[j].length==2)) {
          if (aa[j][0]=="VAR")
            ta[j]=CSStateArray[aa[j][1]];
          else {
            if (aa[j][0]=="ACT")
              ta[j]=CSAction(new Array(new String(aa[j][1])));
            else
              ta[j]=aa[j];
          }
        } else
          ta[j]=aa[j];
      }
      result=aa[0](ta);
    }
    return result;
  }
  
  CSAct = new Object;
  CSAg = window.navigator.userAgent;
  CSBVers = parseInt(CSAg.charAt(CSAg.indexOf("/")+1),10);
  CSIsW3CDOM = ((document.getElementById) && !(IsIE()&&CSBVers<6)) ? true : false;
  function IsIE() {
    return CSAg.indexOf("MSIE") > 0;
  }

  function CSIEStyl(s) {
    return document.all.tags("div")[s].style;
  }

  function CSNSStyl(s) {
    if (CSIsW3CDOM)
      return document.getElementById(s).style;
    else
      return CSFindElement(s,0);
  }
  
  CSIImg=false;
  function CSInitImgID() {
    if (!CSIImg && document.images) {
      for (var i=0; i<document.images.length; i++) {
        if (!document.images[i].id)
          document.images[i].id=document.images[i].id;
      }
      CSIImg = true;
    }
  }
  
  function CSFindElement(n,ly) {
    if (CSBVers<4)
      return document[n];
    if (CSIsW3CDOM) {
      CSInitImgID();
      return(document.getElementById(n));
    }
    var curDoc = ly?ly.document:document; var elem = curDoc[n];
    if (!elem) {
      for (var i=0;i<curDoc.layers.length;i++) {
        elem=CSFindElement(n,curDoc.layers[i]);
        if (elem)
          return elem;
        }
      }
    return elem;
  }
  
  function CSGetImage(n) {
    if(document.images) {
      return ((!IsIE()&&CSBVers<5)?CSFindElement(n,0):document.images[n]);}
    else {
      return null;
    }
  }
  
  CSDInit=false;
  function CSIDOM() {
    if (CSDInit)
      return;
    CSDInit=true;
    if (document.getElementsByTagName) {
      var n = document.getElementsByTagName('DIV');
      for (var i=0;i<n.length;i++) {
        CSICSS2Prop(n[i].id);
      }
    }
  }
  
  function CSICSS2Prop(id) {
    var n = document.getElementsByTagName('STYLE');
    for (var i=0;i<n.length;i++) {
      var cn = n[i].childNodes;
      for (var j=0;j<cn.length;j++) {
        CSSetCSS2Props(CSFetchStyle(cn[j].data, id),id);
      }
    }
  }
  
  function CSFetchStyle(sc, id) {
    var s=sc;
    while(s.indexOf("#")!=-1) {
      s=s.substring(s.indexOf("#")+1,sc.length);
      if (s.substring(0,s.indexOf("{")).toUpperCase().indexOf(id.toUpperCase())!=-1)
        return(s.substring(s.indexOf("{")+1,s.indexOf("}")));
    }
    return "";
  }
  
  function CSGetStyleAttrValue (si, id) {
    var s=si.toUpperCase();
    var myID=id.toUpperCase()+":";
    var id1=s.indexOf(myID);
    if (id1==-1)
      return "";
    s=s.substring(id1+myID.length+1,si.length);
    var id2=s.indexOf(";");
    return ((id2==-1)?s:s.substring(0,id2));
  }
  
  function CSSetCSS2Props(si, id) {
    var el=document.getElementById(id);
    if (el==null)
      return;
    var style=document.getElementById(id).style;
    if (style) {
      if (style.left=="") style.left=CSGetStyleAttrValue(si,"left");
      if (style.top=="") style.top=CSGetStyleAttrValue(si,"top");
      if (style.width=="") style.width=CSGetStyleAttrValue(si,"width");
      if (style.height=="") style.height=CSGetStyleAttrValue(si,"height");
      if (style.visibility=="") style.visibility=CSGetStyleAttrValue(si,"visibility");
      if (style.zIndex=="") style.zIndex=CSGetStyleAttrValue(si,"z-index");
    }
  }
  
  function CSSlideShowAuto(action) {
    SSAfini=0;
    SSAnumimg=0;
    SSAmax=action[2];
    SSAimgNom=action[1];
    SSAtemps=action[3]*1000;
    if (action[4]==true) {
      SSAstop=true;
    } else
      SSAstop=false;
    var SSAimg = null;
    if (document.images) {
      if (!IsIE()&&CSBVers<5)
        SSAimg = CSFindElement(SSAimgNom,0);
      else
        SSAimg = document.images[SSAimgNom];
      str=SSAimg.src;
      n=str.length;
      p=n-6;
      SSApstr=str.substring(0,p);
      SSAnimg=str.substring(p,p+2);
      SSAformat=str.substring(p+2,n);
      if (SSAformat==".jpg" || SSAformat==".JPG" || SSAformat==".gif" || SSAformat==".GIF") {
      } else {
        alert("Images must be ##.jpg or ##.gif where ## is 2 digits 01, 02, 03 etc.");
      }
      if (SSAnimg.substring(0,1)=="0")
        SSAnumimg=Number(SSAnimg.substring(1,2));
      else
        SSAnumimg=Number(SSAnimg);
      SSAtempo(SSAmax,SSAimgNom,SSAtemps,SSAstop,SSApstr,SSAnimg,SSAformat);
    }
  }
  
  function SSAtempo(SSAmax,SSAimgNom,SSAtemps,SSAstop,SSApstr,SSAnimg,SSAformat) {
    setTimeout("slideAuto(SSAmax,SSAimgNom,SSAstop,SSApstr,SSAnimg,SSAformat)",SSAtemps);
  }
  
  function slideAuto(SSAmax,SSAimgNom,SSAstop,SSApstr,SSAnimg,SSAformat) {
    if (SSAfini==1) {
      SSAnumimg = SSAnumimg-2;
      CSSlideShowAutoPause();
    } else {
      SSAmax=SSAmax-1;
      SSAsuite=SSAnumimg+1;
      if (SSAnumimg>SSAmax) {
        SSAsuite=1;
        if (SSAstop==true)
          SSAfini=1;
        else
          SSAfini=0;
      }
      if (SSAnumimg<1)
        SSAsuite=1;
      SSAnumimg=SSAsuite;
      if (SSAsuite<10)
        SSAaller="0"+SSAsuite;
      else
        SSAaller=SSAsuite;
      SSAsource=SSApstr+SSAaller+SSAformat;
      var SSAimg = null;
      if (document.images) {
        if (!IsIE()&&CSBVers<5)
          SSAimg = CSFindElement(SSAimgNom,0);
        else
          SSAimg = document.images[SSAimgNom];
        if (SSAimg)
          SSAimg.src = SSAsource;
      }
      SSAtempo(SSAmax,SSAimgNom,SSAtemps,SSAstop,SSApstr,SSAnimg,SSAformat);
    }
  }

  function CSSlideShowAutoPause() {}

  var preloadFlag = false;
  function preloadImages() {
    if (document.images) {
      preloadFlag = true;
    }
  }
  
  CSInit[CSInit.length] = new Array(CSSlideShowAuto,'_01',13,3,true);
