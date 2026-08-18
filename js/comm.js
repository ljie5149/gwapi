function urlParamCore(paramName, paramValue, url) {
    var index_Param = url.indexOf(paramName); // Check if the URL already has parameters
    let regex = new RegExp(`([?&])${paramName}=.*?(&|$)`, "i");
    
    var separator = (url.indexOf('?') !== -1) ? '&' : '?'; // Check if the URL already has parameters
    var newParam = paramName + '=' + encodeURIComponent(paramValue); // Encode the parameter value

    if (url.match(regex)) {
        url = url.replace(regex, `$1${paramName}=${paramValue}$2`);
    } else {
        url +=  separator + newParam;
    }
    return url;
}
function addParamToUrl4Edit(paramName, paramValue) {
    var url = window.location.href; // Get the current URL

    url = urlParamCore(paramName, paramValue, url);
    window.location.href = url;
}
function mysetCookie(key, value, hours) {
    let d = new Date();
    d.setTime(d.getTime() + (hours * 60 * 60 * 1000)); // 1 hour
    let expires = "expires=" + d.toUTCString();
    document.cookie = key + "=" + value + "; " + expires + "; path=/";
}
function mygetCookie(key) {
    let name = key + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let cookieArray = decodedCookie.split(';');
    for(let i = 0; i < cookieArray.length; i++) {
        var cookie = cookieArray[i].trim();
        while (cookie.charAt(0) == ' ') {
            cookie = cookie.substring(1);
        }
        var index = cookie.indexOf(name);
        if (cookie.indexOf(name) == 0) {
            return cookie.substring(name.length, cookie.length);
        }
    }
    return "0";
}
function mydeleteCookie(key) {
    document.cookie = key + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
}
	/***
	 * cookie的操作
	 * @param name
	 * @param value
	 */
	function setCookie(name,value)
	{
		var Days = 300;
		var exp = new Date();
		exp.setTime(exp.getTime() + Days*1000);
		document.cookie = name + "="+ escape (value) + ";expires=" + exp.toGMTString();
	}
	function setCookie1(name,value)
	{
		var Days = 1;
		var exp = new Date();
		exp.setTime(exp.getTime() + Days*24*60*60*1000);
		document.cookie = name + "="+ escape (value) + ";expires=" + exp.toGMTString();
	}
	//读取cookies
	function getCookie(name)
	{
		var arr,reg=new RegExp("(^| )"+name+"=([^;]*)(;|$)");

		if(arr=document.cookie.match(reg))

			return unescape(arr[2]);
		else
			return null;
	}

	//删除cookies
	function delCookie(name)
	{
		var exp = new Date();
		exp.setTime(exp.getTime() - 1);
		var cval=getCookie(name);
		if(cval!=null)
			document.cookie= name + "="+cval+";expires="+exp.toGMTString();
	}		
	var defaultEmptyOK = false;
	var whitespace = " \t\n\r";
	function isEmpty(s)
	{   return ((s == null) || (s.length == 0));}

	function isWhitespace (s)
	{   var i;
		if (isEmpty(s)) return true;
		for (i = 0; i < s.length; i++)
		{   
			var c = s.charAt(i);
			if (whitespace.indexOf(c) == -1) return false;
		}
		return true;
	}
	function warnEmpty (theField, s)
	{   
	if (s != "") {
				theField.focus();
				alert(s);
			}
		return false;
	}
	function checkIP(theField,s){
		var re = /^((\d)|(([1-9])\d)|(1\d\d)|(2(([0-4]\d)|5([0-5]))))\.((\d)|(([1-9])\d)|(1\d\d)|(2(([0-4]\d)|5([0-5]))))\.((\d)|(([1-9])\d)|(1\d\d)|(2(([0-4]\d)|5([0-5]))))\.((\d)|(([1-9])\d)|(1\d\d)|(2(([0-4]\d)|5([0-5]))))$/;
		var ip = theField.value;
		if(!re.test(ip))
			return warnEmpty (theField, s);
		return true;
	}
	function checkString (theField, s)
	{   
		if (checkString.arguments.length == 2) emptyOK = defaultEmptyOK;
		if ((emptyOK == true) && (isEmpty(theField.value))) return true;
		if (isWhitespace(theField.value)) 
		   return warnEmpty (theField, s);
		else return true;
	}
	function jsTrim(s)
	{
		return s.replace(/(^\s+)|(\s+$)/g, "");
	}
	function Checkinno(text1,s)  //0-9
	{
		var t = "";
		var flag=true;  
		for(var i=1;i<=text1.value.length;i++)
		{
				t = text1.value.substring(i-1,i);
				t = jsTrim(t);
				if (t != "") {
				
					if ((!((text1.value.substring(i-1,i) >= "0") && (text1.value.substring(i-1,i) <= "9"))))
					flag=false;    
				}
		}
		if(flag == false)	{
			alert(s);
			text1.focus();
		}
		return(flag);
	}