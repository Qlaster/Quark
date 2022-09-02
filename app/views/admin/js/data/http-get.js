

	

    function doCallOtherDomain(url, html_element_id)
    {
        var XHR = window.XDomainRequest || window.XMLHttpRequest
        var xhr = new XHR();
        var result;

		
        //~ xhr.open('GET', url, true);
        xhr.open('GET', url, false);

        // замена onreadystatechange
        xhr.onload = function() 
		{
			if (html_element_id === undefined)
			{
				//alert(xhr.responseText);
				result = xhr.responseText;
				return xhr.responseText;
			}
			else
			{
				document.getElementById(html_element_id).innerHTML = xhr.responseText;			 
				document.getElementById(html_element_id).value = xhr.responseText;
            //document.getElementById('response').innerHTML = xhr.responseText;
			}
        }

        xhr.onerror = function() {
            //~ alert("Error")
        }

        xhr.send()
        return result;        
    }    


    function doCallOtherDomainAsync(url, func_name, timeout)
    {
        var XHR = window.XDomainRequest || window.XMLHttpRequest
        var xhr = new XHR();
        var result;

        
        //~ xhr.open('GET', url, true);
        xhr.open('GET', url, true);

        xhr.timeout = timeout;

        xhr.ontimeout = function() 
        {
            // console.log('Превышено максимальное время ожиания запроса');
        }

        // замена onreadystatechange
        xhr.onload = function() 
        {
            if (typeof doCallOtherDomainAsync.buffer == 'undefined') doCallOtherDomainAsync.buffer = '';
            //alert(xhr.responseText);

            if (doCallOtherDomainAsync.buffer != xhr.responseText)
            {
                // console.log(doCallOtherDomainAsync.buffer);
                // console.log(xhr.responseText);
                doCallOtherDomainAsync.buffer = xhr.responseText;   
                window[func_name](xhr.responseText);
            }

            
            // result = xhr.responseText;
            // return xhr.responseText;
        }

        xhr.onerror = function() {
            //~ alert("Error")
        }

        xhr.send()
        return result;        
    }    
    
    function url_get(url) 
    {

        try 
        {
            // console.log(url);
            // console.log(doCallOtherDomain(url));
            return doCallOtherDomain(url)
        } catch (e) {
            //~ alert("Ошибка при вызове http_get")
        }
    }

    function url_get_async(url, func_name, timeout) 
    {

        try 
        {
            doCallOtherDomainAsync(url, func_name, timeout);
            // console.log(url);
            // console.log(doCallOtherDomainAsync(url));
            // console.log(x);
            // return doCallOtherDomainAsync(url, true);
        } catch (e) {
            //~ alert("Ошибка при вызове http_get")
        }
    }

    function url_to_html(url,  html_element_id) 
    {
        try 
        {
            doCallOtherDomain(url, html_element_id)
        } catch (e) {
            //~ alert("Ошибка при вызове http_get")
        }
    }
    
    
    
    function url_post(url, name, data) 
    {
		var xhr = new XMLHttpRequest();

		var body = name + '=' + encodeURIComponent(data);
		  //~ '&data=' + encodeURIComponent(data);

		xhr.open("POST", '/submit', true)
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')

		xhr.onreadystatechange = '';

		xhr.send(body);
	}

