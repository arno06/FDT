"use strict";
/**
 * @author Arnaud NICOLAS <arno06@gmail.com>
 * @repo https://github.com/arno06/Request
 */
class Request
{
    constructor(pUrl, pParameters = null, pMethod = 'get') {
        this.parameters = pParameters;
        this.error = false;
        this.xhr_object = new XMLHttpRequest();
        this.xhr_object.addEventListener(Request.READY_STATE_CHANGE, this.readyStateChangeHandler.bind(this), false);
        this.xhr_object.open(pMethod, pUrl, true);
        this.xhr_object.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;charset:'+Request.CHARSET);
        this.xhr_object.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        this.xhr_object.send(Request.serializeParams(this.parameters));
    }

    readyStateChangeHandler(e)
    {
        if(this.xhr_object.readyState!==4)
        {
            return;
        }
        let contentType = this.xhr_object.getResponseHeader("Content-type");
        if(contentType.indexOf("json")>-1)
            e.currentTarget.responseJSON = JSON.parse(this.xhr_object.responseText);
        let type = (this.xhr_object.status < 200 || this.xhr_object.status > 300)?Request.ERROR:Request.COMPLETE;
        this.xhr_object.dispatchEvent(new Event(type));
    }

    addEventListener(pType, pHandler)
    {
        this.xhr_object.addEventListener(pType, pHandler, false);
    }

    onComplete(pHandler)
    {
        this.xhr_object.addEventListener(Request.COMPLETE, pHandler, false);
        return this;
    }

    onProgress(pHandler)
    {
        this.xhr_object.addEventListener(Request.PROGRESS, pHandler, false);
        return this;
    }

    onError(pHandler)
    {
        this.xhr_object.addEventListener(Request.ERROR, pHandler, false);
        return this;
    }

    onAbort(pHandler)
    {
        this.xhr_object.addEventListener(Request.ABORT, pHandler, false);
        return this;
    }

    abort()
    {
        this.xhr_object.abort();
        return this;
    }

    static serializeParams(pObject, pParent = null)
    {
        var params = [], value, name;
        for(let i in pObject)
        {
            if(!pObject.hasOwnProperty(i))
                continue;
            name = i;
            if(pParent !== null)
                name = pParent+'['+i+']';
            switch((typeof pObject[i]).toLowerCase())
            {
                case "object":
                    value = Request.serializeParams(pObject[i], name);
                    break;
                default:
                    value = name+"="+pObject[i];
                    break;
            }
            params.push(value);
        }
        return params.join("&");
    }

    static load(pUrl, pParameters = null)
    {
        return new Request(pUrl, pParameters, pParameters===null?'get':'post');
    }

    static update(pSelector, pUrl, pParameters)
    {
        Request.load(pUrl, pParameters).onComplete(function(e){document.querySelector(pSelector).innerHTML = e.responseText;});
    }
}

Request.ERROR = "error";
Request.LOAD_START = "loadstart";
Request.LOAD = "load";
Request.LOAD_END = "loadend";
Request.PROGRESS = "progress";
Request.ABORT = "abort";
Request.TIME_OUT = "timeout";
Request.READY_STATE_CHANGE = "readystatechange";
Request.COMPLETE = "complete";
Request.CHARSET = "UTF-8";