var Autocomplete = (function(){

    var pool = [];

    var req;

    function handlePool()
    {
        for(var i = 0, max = pool.length; i<max;i++)
        {
            setup(pool[i]);
        }
        pool = [];
    }

    window.addEventListener('load', handlePool, false);

    function setup(pSelector)
    {
        if(!document.querySelector(pSelector))
        {
            pool.push(pSelector);
            return false;
        }
        document.querySelector(pSelector).classList.add('ac-ready');
        document.querySelector(pSelector).addEventListener('keyup', keyUpHandler, false);
    }

    function keyUpHandler(e)
    {
        var el = e.currentTarget;
        var source_url = el.getAttribute('data-ac_source');
        var min_query_length = Number(el.getAttribute('data-ac_minQueryLength'))||3;
        var results_locator = el.getAttribute('ac_resultsLocator');
        if(el.value.length<min_query_length)
        {
            return;
        }
        el.classList.add('loading');
        console.log(el.getAttribute("data-ac_source"));
        if (req)
        {
            req.cancel();
        }

        source_url = source_url.replace('{query}', el.value);

        req = new Request(source_url);
        req.onComplete(function(pResponse)
        {
            console.log(pResponse);
        });
    }


    return {
        applyTo:setup
    }
})();