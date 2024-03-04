NodeList.prototype.forEach = Array.prototype.forEach;

var Debugger =
{
    error:false,
	current:"sortie",
	__init:function()
	{
		if(document.querySelector("#debug .debugselected"))
			Debugger.current = document.querySelector("#debug .debugselected a");
		document.querySelectorAll("#debug .debug_buttons div").forEach(function(div)
		{
			var listener = Debugger.__controlConsoleClickHandler;
			if(div.classList.contains("vars"))
				listener = Debugger.__controlVarsClickHandler;
			div.addEventListener("click", listener);
		});
        if(Debugger.error)
        {
            var el = document.querySelector("#debug .debug_console");
            el.scrollTop = el.scrollHeight;
        }
		document.querySelector("#debug .debug_toggle").addEventListener("click", Debugger.toggle);
        document.querySelector("#debug .debug_fullscreen").addEventListener("click", Debugger.fullscreen);
		document.querySelector("#debug .debug_close").addEventListener("click", Debugger.close);
		document.querySelectorAll("#debug .opcache .invalidate").forEach(function(pEl){
			pEl.addEventListener('click', Debugger.invalidateHandler);
		});
		window.addEventListener("keydown", Debugger.keyDownHandler);
	},
	invalidateHandler:function(e){
		let url = document.querySelector('base').getAttribute("href")+'statique/opcache-invalidate/';
		url += '?script='+encodeURIComponent(e.currentTarget.getAttribute("data-script"));
		let target = e.currentTarget;
		fetch(url).then((pResponse)=>pResponse.text()).then(function(pResult){
			console.log(pResult);
			if(pResult!=='true'){
				console.error('Debugger.js - OPCache invalidate error');
				return;
			}
			let parent = target.parentNode.parentNode;
			parent.removeChild(target.parentNode);
			while(!parent.querySelectorAll('.opcache-file').length){
				let p = parent.parentNode;
				parent = p.parentNode;
				parent.removeChild(p);
			}
		});
	},
	keyDownHandler:function(e)
	{
		switch(e.keyCode)
		{
			case 192:
				e.preventDefault();
				Debugger.fullscreen();
				break;
			case 113:
				e.preventDefault();
                if(e.shiftKey)
                    Debugger.fullscreen();
                else
				    Debugger.toggle();
				break;
		}
	},
	toggle:function(e)
	{
        if(e)
            e.preventDefault();
        if(document.querySelector("#debug").classList.contains("fullscreen"))
            document.querySelector("#debug").classList.remove("fullscreen");
        else
            document.querySelector("#debug").classList.toggle("maximized");
	},
    fullscreen:function(e)
    {
        if(e)
            e.preventDefault();
        if(document.querySelector("#debug").classList.contains("maximized"))
            document.querySelector("#debug").classList.remove("maximized");
        document.querySelector("#debug").classList.toggle("fullscreen");
    },
	close:function(e)
	{
		document.querySelector("#debug").style.display = "none";
		e.preventDefault();
	},
	updateConsole:function()
	{
		document.querySelectorAll("#debug .debug_buttons div").forEach(function(button)
		{
			var display = "table-row";
			if(button.classList.contains("disabled"))
				display = "none";
			document.querySelectorAll("#debug .debug_console table.console tr."+button.getAttribute("rel")).forEach(function(tr)
			{
				tr.style.display = display;
			});
		});
	},
	__controlVarsClickHandler:function(e)
	{
		e.preventDefault();
		var t = e.target.nodeName.toLowerCase()!="div" ? e.target.parentNode : e.target;
		document.querySelectorAll("#debug .debug_buttons div.vars").forEach(function(div)
		{
			if(!div.classList.contains("disabled"))
				div.classList.add("disabled");
		});
		t.classList.remove("disabled");
		document.querySelectorAll(".debug_vars pre").forEach(function(pre)
		{
			if(pre.getAttribute("rel") == t.getAttribute("rel"))
				pre.style.display = "block";
			else
				pre.style.display = "none";
		});
	},
	__controlConsoleClickHandler:function(e)
	{
		e.preventDefault();
		var t = e.target.nodeName.toLowerCase()!="div" ? e.target.parentNode : e.target;
		if (!t.toggle_alone) {
			t.toggle_alone = false;
		}

		if (e.altKey) {
			if (!t.toggle_alone) {
				t.parentElement.querySelectorAll('.messages').forEach(function(el){
					el.classList.add('disabled');
				});
				t.toggle_alone = true;
			} else {
				t.parentElement.querySelectorAll('.messages').forEach(function(el){
					el.classList.remove('disabled');
				});
				t.toggle_alone = false;
			}

			t.classList.remove('disabled');
		} else {
			t.classList.toggle("disabled");
		}
		Debugger.updateConsole();
	}};
NodeList.prototype.forEach = Array.prototype.forEach;
window.addEventListener("load", Debugger.__init);