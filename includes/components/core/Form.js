var src_catpha;

var UPLOADS = {}, Uploader = {};
Uploader.updateProgress = function (pIdDiv, pPourcent, pSpeed)
{
    Uploader.bar(pIdDiv).style.width = pPourcent+"%";
	if(pSpeed != "")
		pSpeed = "&nbsp;("+pSpeed+"/s)";
	Uploader.bar(pIdDiv).innerHTML = "<span>"+pPourcent+"%"+pSpeed+"</span>";
	Uploader.bar(pIdDiv).classList.remove('fail');
};
Uploader.bar = function(pIdDiv){return document.querySelector('#'+pIdDiv+' .progress .bar');};
function uploadInit(pFile, pIdDiv)
{
    UPLOADS[pIdDiv] = {fileName:pFile, date:new Date().getTime(), byteLoaded:0, launchDate:new Date().getTime(), bytesTotal:0, dateNoInterval:new Date().getTime() };
   document.querySelector('#'+pIdDiv+' .caption_bot').innerHTML = pFile;
   Uploader.updateProgress(pIdDiv, "0", "0ko");
   if (document.getElementById('img_upload'))
   {
	   document.querySelector('#img_upload').onload = function ()
	   {
		   document.querySelector('#img_upload').style.top = '44%';
		   document.querySelector('#img_upload').style.left = '66%';
	   };
	   document.querySelector('#img_upload').src= '../themes/main/default/back/imgs/loading.gif';
   }
    if (document.getElementById("file_upload"))
    {
        var baseurl = (typeof BASEURL != "undefined") ? BASEURL+"/" : "";
        document.getElementById('file_upload').src= baseurl+'themes/main/default/front/imgs/spacer.gif';
    }
}

function uploadProgress(pBytesTotal, pBytesLoaded, pIdDiv)
{
    var time = new Date().getTime() - UPLOADS[pIdDiv].date;
    var bytes = pBytesLoaded - UPLOADS[pIdDiv].byteLoaded;
    var multiplier = time ? 1000/time : time;
    UPLOADS[pIdDiv].bytesTotal = pBytesTotal;

    var width = Math.floor((pBytesLoaded / pBytesTotal) * 100);
    document.querySelector('#'+pIdDiv+' .button_clicker .text_button').classList.add('disable');

    var fileSize = getConvertedOctet(pBytesTotal);

    document.querySelector('#'+pIdDiv+' .caption_bot').innerHTML = UPLOADS[pIdDiv].fileName+" ("+fileSize+")";

    if(time >= 1000)
    {
        var speed = getConvertedOctet(bytes*multiplier);
		Uploader.updateProgress(pIdDiv, width, speed);
        UPLOADS[pIdDiv].date = new Date().getTime();
        UPLOADS[pIdDiv].byteLoaded = pBytesLoaded;
    }

    UPLOADS[pIdDiv].dateNoInterval = new Date().getTime();
}

function uploadComplete(pIdDiv, pIdUpload, pPathUpload, pFormName, pInputName)
{
    var elapsed = (UPLOADS[pIdDiv].dateNoInterval - UPLOADS[pIdDiv].launchDate) / 1000;

    Uploader.bar(pIdDiv).classList.add('success');

    Uploader.updateProgress(pIdDiv, "100", getConvertedOctet(elapsed > 0 ? UPLOADS[pIdDiv].bytesTotal/ elapsed : 0));
    var d= document.querySelector('#'+pIdDiv+' .delete_file');
    if(d)
    {
        d.setAttribute("href", d.getAttribute("href").replace(/\{id\}/, pIdUpload));
        d.style.display = "inline";
    }

    var img_upload = document.getElementById('img_upload');
    if (img_upload)
    {
        img_upload.onload = function ()
    	{
            img_upload.style.top = '15px';
            img_upload.style.left = '50%';
    	};
        img_upload.src= '../statique/resize/id:' + pIdUpload + '/w:200/h:200/';
    }
    else if (document.getElementById("file_upload"))
    {
        document.getElementById('file_upload').src= (typeof BASEURL != "undefined" ? BASEURL+"/" : "")+pPathUpload;
        document.querySelector('#'+pIdDiv+' .caption_bot').innerHTML = "";
    }
    else
    {
    	document.querySelector('#'+pIdDiv+' .see_file').style.display = "inline";
        document.querySelector('#'+pIdDiv+' .caption_bot').innerHTML = "";
        document.querySelector('#'+pIdDiv+' .see_file').href = pPathUpload;
    }
    
	var inps = document.querySelectorAll('#'+pFormName+'['+pInputName+']"]');
    for(var i = 0 ; i < inps.length ; i++)
    {
        inps[i].parentNode.removeChild(inps[i]);
    }
	var input = document.createElement("input");
	input.setAttribute("name", pFormName+'['+pInputName+']');
	input.setAttribute("type", "hidden");
	input.value = pIdUpload;
	document.getElementById(pIdDiv).appendChild(input);
}

function uploadError(pParam, pIdDiv)
{
    Uploader.bar(pIdDiv).classList.add('fail');
    Uploader.bar(pIdDiv).style.width = "100%";
    Uploader.bar(pIdDiv).innerHTML = "<span>"+pParam+"</span>";//change here
    document.querySelector('#'+pIdDiv+' .button_clicker .text_button').classList.remove('disable');
}

function getConvertedOctet(pBytes)
{
	var units = ["o", "ko", "Mo", "Go"], i = 0;
	while(units[i++] && pBytes>=1024)
		pBytes /= 1024;
	pBytes = Math.round(pBytes*10)/10;
	return pBytes+" "+units[--i];
}

function reloadCaptcha(pTarget)
{
	var i = pTarget.parentNode.parentNode.parentNode.parentNode.getElementsByTagName("label")[0].getElementsByTagName("span")[0].getElementsByTagName("img")[0];
	if(!src_catpha)
		src_catpha = i.src;
	i.setAttribute("src", src_catpha+""+Math.round(Math.random()*9999)+"/");
	return false;
}

function AutoFillPlugin(pTarget)
{
	pTarget.addEventListener("focus", this._focusHandler);
	pTarget.addEventListener("blur", this._blurHandler);
	this._blurHandler({target:pTarget});
}
AutoFillPlugin.className = "autoFillBlur";
AutoFillPlugin.applyTo = function(pTarget, pValue)
{
	pTarget.setAttribute("title", pValue);
	return new AutoFillPlugin(pTarget);
};
AutoFillPlugin.prototype =
{
	_focusHandler:function(e)
	{
		if(e.target.value == e.target.getAttribute("title"))
		{
			e.target.value="";
			e.target.classList.remove(AutoFillPlugin.className);
		}
	},
	_blurHandler:function(e)
	{
		if(e.target.value == "")
		{
			e.target.value=e.target.getAttribute("title");
			e.target.classList.add(AutoFillPlugin.className);
		}
	}
};

function registerCtrlS(pHandler)
{
	if(!pHandler)
		return;
	window.addEventListener("keydown", function(e)
	{
		if(e.ctrlKey && e.keyCode==83)
		{
			document.activeElement.blur();
			Event.stop(e);
			pHandler();
		}
	});
}

function FormValidator(pName, pContext)
{
	this._inputs = [];
	this._name = pName;
	this._context = pContext;
}

FormValidator.REGEXP_TextNoHtml = /^([.^><]*)$/;
FormValidator.REGEXP_Text = /^(.*)$/;
FormValidator.REGEXP_Numeric = /^([0-9]*)$/;
FormValidator.SELECTOR_COMPONENT = "div.component";

FormValidator.prototype =
{
	_inputs:{},
	_name:null,
	_context:null,
	_values:null,
	_extracted:false,

	_inputsRequire:[],
	_inputsIncorrect:[],

	setInput:function(pName, pDatas)
	{
		this._inputs[pName] = pDatas;
	},

	setInputs:function(pInputs)
	{
		for(var i in pInputs)
        {
            if(!pInputs.hasOwnProperty(i))
                continue;
            this.setInput(i, pInputs[i]);
        }
	},

	isValid:function()
	{
		this._inputsIncorrect = [];
		this._inputsRequire = [];
		this.getValues();
		var inp, valid = true;
		for(var i in this._inputs)
		{
            if(!this._inputs.hasOwnProperty(i))
                continue;
			inp = this._inputs[i];
			if(inp["regExp"] && inp["regExp"] != "")
			{
				if(FormValidator["REGEXP_"+inp["regExp"]])
				{
					var reg = FormValidator["REGEXP_"+inp["regExp"]];
					if(!reg.test(this._values[i]))
					{
						this._inputsIncorrect.push(inp["label"]);
						this._values[i] = "";
						valid = false;
					}
				}
				else
				{
//					console.log("regExp inconnue");
				}
			}

			if(inp["require"] && inp["require"]==="1")
			{
				if(!this._values[i] || this._values[i] == "")
				{
					this._inputsRequire.push(inp["label"]);
					valid = false;
				}
			}
		}

		return valid;
	},

	getValues:function()
	{
		if(!this._extracted)
			this._extractValues();
		return this._values;
	},

	_extractValues:function()
	{
		this._extracted = true;

		var param = {}, e, n, r = new RegExp(this._name+"\\[([a-z0-9\\_\\-]+)\\](.*)", "i");
//		var elements = this._context.querySelectorAll("*");
		var elements = this._context.elements;
		for(var i = 0, max = elements.length; i<max; i++)
		{
			e = elements[i];
			if(!e.name)
				continue;
			n = r.exec(e.name);
			if(n&&n.length)
				n = n[1]+n[2];
			else
				n = e.name;
			switch(e.nodeName.toLowerCase())
			{
				case "input":
					switch(e.type.toLowerCase())
					{
						case "hidden":
							var f = document.getElementById(e.name+"___Frame");
							if(!f)
								param[n] = e.value;
							else
							{
								param[n] = FCKeditorAPI.GetInstance(e.name).GetXHTML(true);
							}
						break;
						case "submit":
						case "password":
						case "text":
							param[n] = e.value;
						break;
                        case "radio":
                            if(!e.radiogroup)
                                param[n] = e.value;
                            else
                            {
                                if(!param[n])
                                    param[n] = [];
                                param[n].push(e.value);
                            }
                            break;
						case "checkbox":
							if(e.checked)
							{
								if(n.indexOf("[]")>-1)
								{
									if(!param[n])
										param[n] = [];
									param[n].push(e.value);
								}
								else
									param[n] = e.value;
							}
							else
								param[n] = false;
						break;
					}
				break;
                case "textarea":
                    param[n] = e.value;
                    break;
				case "select":
					try
					{
						param[n] = e.getElementsByTagName("option")[e.selectedIndex].value;
					}
					catch(e){param[n] = "";}
				break;
			}
		}
		this._values = param;
	},

	getError:function()
	{
		var error = "";
		error += this._getErrorFromArray(this._inputsIncorrect, "La valeur du champ {label} est incorrecte.", "Les valeurs des champs {label} sont incorrectes.");
		error += this._getErrorFromArray(this._inputsRequire, "Le champ {label} est obligatoire.", "Les champs {label} sont obligatoires.");
		return error;
	},

	_getErrorFromArray:function(pArray, pLibelle, pLibelles)
	{
		if(!pArray.length)
			return "";
		var i = 0, error = "";
		for(;i<pArray.length;i++)
		{
			if(i>0)
				error += ", ";
			error += "<b>"+pArray[i]+"</b>";
		}
		var message = (pArray.length == 1) ? pLibelle : pLibelles;
		return "<p>"+message.replace('{label}', error)+"</p>";
	}
};