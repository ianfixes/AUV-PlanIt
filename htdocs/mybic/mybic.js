// using version of bind from prototype for scoping purposes
Function.prototype.mybicBind = function(object) {  
  var __method = this;
  return function() {
    return __method.apply(object, arguments);
  }
};

/**
* Constructor for MyBIC's ajax object
* This only needs to be instantiated once in your script and you'll be able to re-use the same object for all your requests
*@constructor
*@author Jim Plush jiminoc@gmail.com
*@param {string} server_url The URL of the page you're going to connect to on the server, by default mybic_server.php
*@param {string} readyStateFunction (OPTIONAL)The function that will be called when ajax responses come back from the server, by default mybic handles this for you
*/
function XMLHTTP(server_url, readyStateFunction)
{
	/** 
	* the SERVER page URL to connect to IE ajax_server.php 
	*@type String
	*/
	this.server_url = server_url;
	 
	/**
	*  whether we're in syncronous mode or async (default), syncronous means the UI will lock up until the response is received - you rarely would want this.
	*@type String
	*/	
	this.async = true;	
	
	/** 
	* debug turned off by default, to see your request/response do ajaxObj.debug=1, useful for seeing what's going on with your ajax requests
	*@type int
	*/			
	this.debug=0;
					
	/**
	* this enables throttling by default so all your requests are in the proper order
	*@type int
	*/		
	this.throttle=1;				
	
	/**
	* by default all requests are sent via POST to override just use ajaxObj.method="GET"; before your call
	*@type String
	*/
	this.method = "POST";
	
	/**
	* by default JSON encoding is the expected format, to override: ajaxObj.format = "XML"; or ajaxObj.format="TEXT";
	* if you're planning to just load HTML snippets for innerHTML use, just set this to TEXT
	*@type String
	*/
	this.format = "JSON";				
					
	/**
	* array of optional headers you may pass in
	*@type Array
	*/
	this.headers = new Array();			
	
	/**
	* the number of seconds to wait before calling network down function, set to -1  disable feature, defaults to 5 seconds
	*@type int
	*/
	this.abort_timeout = 5000;	
			
	/** 
	* the number of failed requests before mybic is disabled -> prevents repeated error notifications, no more ajax requests will fire after this amount of failures
	*@type int
	*/
	this.failed_threshold = 3;		
	
	/**
	*  set this to 1 if you have a polling object and you don't want to see debug msgs every second or activity indicators, nice to keep out of firebug
	*@type
	*/	
	this.ignoreCall = 0;
					
	/**
	* set this to one in your client code if you want to make sure your callback functions completes before making another ajax call in the queue
	*@type int
	*/
	this.stopRequest = 0;				

	/** 
	* ERROR HANDLERS YOU CAN SET, TO OVERRIDE OR COMPLIMENT EXISTING FUNCTIONALITY
	*/
	
	/**
	* Set this to a function in your website that will handle pop up errors in your call back function more gracefully DO FOR LIVE SITES!
	* this is used when your callback function experiences a fatal error, mybic can execute a nicer function for you and pass you the error object
	* note: do not assign with parens, do this ajaxObj.jsErrorHandler = myFunction;
	*@type function
	*/
	this.jsErrorHandler = '';       
	   
	/**
	* set this to a function in your website that will handle pop up errors in your call back function more gracefully
	*/
	this.notAuthorizedHandler = '';    
	
	/**
	* the function that will be called if mybic cannot make a request to the server (server down)
	* if 'disabled' is passed to your function, then no more ajax calls can be made, you might want to reload the whole page at this point
	*/ 
	this.net_down_func = this.down;		
	
	
	/*-- SYSTEM PROPERTIES - no need to change the properites below --*/
	/**
	*@private
	*/
	this.version = '1.0.1';
	
	/**
	* the xmlhttprequest variable, starts off as null
	*@private
	*/
	this.req = null;	
				
	/**
	* used for showing expandable visual debug data
	*@private
	*/
	this.debugID = 0;
					
	/**
	* array of errors generated
	*@private
	*/
	this.errors = new Array();
	
	/**
	* the queue that will handle the throttling requests to keep your stuff in sync
	*@private
	*/	
	this.queue = new Array();	
	
	/**
	* the current index for the throttling array
	*@private
	*/			
	this.queue_in_process = 0;
	
	/**
	* system uses this to see what calls to ignore from the queue
	*@private
	*/				
	this.currentCallIgnore=0;
	
	/**
	* the timer used to check the readystate of the xmlhttprequest to prevent memory leaks
	*@private
	*/				
	this.readySateTimer = '';
	
	/**
	* the callback function, when the ajax request is sent, this is the function that will be called
	*@private
	*/				
	this.callBack = '';	
	
	/**
	* cache which version of msxml to use on IE to avoid using the loop every time
	*@private
	*/	
	this.IEObjCache = 0;
	
	/**
	* number of failed requests in a row
	*@private
	*/					
	this.failed_requests=0	
	
	/**
	* holds the callback function for ajax calls
	*@private
	*/		
	this.readyStateFunction = (readyStateFunction) ? readyStateFunction : this.responseHandler;
	
	/**
	* holds the current queue of msgs to try to match up request/responses
	*@private
	*/	
	this._msgQueueInfo = new Array();	
	
	/**
	* the timer used to check the readystate of the xmlhttprequest to prevent memory leaks
	*@private
	*/	
	this.readyStateTimer   = '';   
	
	/**
	* the time interval used to check the readystate of the xmlhttprequest to prevent memory leaks
	*@private
	*/	
	this.poolTimerInterval = 50;    
	
}

/**
* Method to create an XMLHTTP object
*@access private
*/
XMLHTTP.prototype = {
	getXMLHTTP:function() {
	
		// moz-IE7 XMLHTTPRequest object
		if (window.XMLHttpRequest) { this.req = new XMLHttpRequest(); }
		else if (this.IEObjCache != 0) { alert(this.IEObjCache);try {this.req = new ActiveXObject(this.IEObjCache);}catch(e){} }
		else if (window.ActiveXObject){
			// 6.0 is recommended for all vista + users, 3.0 is recommended over all 4 & 5 versions
			var progIDs = [ 'MSXML2.XMLHTTP.6.0','MSXML2.XMLHTTP.3.0','Microsoft.XMLHTTP'];
	        for (var i = 0; i < progIDs.length; i++) {
	            try {
	                this.req = new ActiveXObject(progIDs[i]);
	      			this.IEObjCache = progIDs[i];
	                break;
	            }
	            catch (ex) {}
	        }
	   } else {
			if(this.debug == 1) { this.showDebug("<BR>FATAL ERROR: Could not create XMLHTTPRequest Object!<BR>");	}
	   }
		return this.req;
	},
	/**
	* Main API method to use for AJAX requests
	* example: ajaxObj.call("action=loadComments&id=1", myCallBackFunction);
	*@access public
	*@param string A url encoded string of data to send to the server
	*@param string A callback function that the server will launch when the response is generated
	*@param string Used by the response handler function to send back throttled requests, you won't need to worry about this param
	*/
	call:function(queryVars, userCallback, queue_request) {
	
		// test for too many failed requests
		if(this.failed_requests >= this.failed_threshold) {
			// call network down method with instruction to notify of mybic being disabled
			this.net_down_func('disable');
			return false;
		} else {
			var currentVars;
			var callback;
			this.fullUrl = '';
	
			if(this.throttle == 1 && queue_request != 'queue' || this.stopRequest == 1) {		// throttling keeps your requests in sync, so things aren't out of order
				this.add2Queue(queryVars, userCallback);	
			}
		
			if(this.queue_in_process == 0) {
				if(!this.getXMLHTTP()) {
					return false;
				}
			
				if(this.throttle == 1) {
					this.queue_in_process = 1;
					var currentCall = this.queue.shift();	// get the current call to make
					currentVars = currentCall.queryVars;
					callback = currentCall.userCallback;
					this.format = currentCall.format;
					this.method = currentCall.method;
					this.abort_timeout = currentCall.abortTimeout;
					this.currentCallIgnore = currentCall.ignoreCall;
					this.async = currentCall.async;
				} else {
					currentVars = queryVars;
					callback = userCallback;
					var ignoreCall=0;
				
				}
				this.callBack = callback;
		
				// check for JSON encoding
				if(this.format != 'JSON') {
					currentVars = currentVars+'&json=false';
				}
				
				// if get is used, append the query variables to the url string 
				this.full_url = (this.method == "POST") ? this.server_url : this.server_url + '?'+ currentVars;
			
				if(this.debug == 1 && this.currentCallIgnore != 1) {
					try {
					var matches = currentVars.match(/action=(\w+)&?/);
					this.showDebug('new', 'MYBIC - CALLING: '+matches[1]);
					this.showDebug("Server Page: "+this.server_url+"<BR>HTTP Method: "+this.method+"<BR>Encoding Format: "+this.format+"<BR>Query String: "+currentVars+"<BR>");
					}catch(e){}
				}
			
				// open connection
				this.req.open(this.method, this.full_url, this.async);
			
				// set any optional headers
				if(this.headers){
					for(var i in this.headers) {
						if(i != '' && (this.headers[i] instanceof String)) {
							try {
								this.req.setRequestHeader( i, this.headers[i]);
								if(this.debug == 1) { this.showDebug('Setting Custom Header: '+this.headers[i]+'<br>');}
							} catch(e) {}
						}
					}
				}
				// START TIMER TO ABORT REQUEST
				if(this.abort_timeout != -1) {
					this.end_timer = setInterval(this.endCall.mybicBind(this), this.abort_timeout);
				}
				// send request
				if(this.method == 'POST') {
					this.req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
					this.request = currentVars;
					this.req.send(currentVars);
				} else {
					this.req.send(null);
				}
			
				// START POLLING FOR READYSTATECHANGE
				if (this.readyStateFunction) {
						this.readyStateTimer = window.setInterval(
				        this.readyPoolFunc.mybicBind(this),
				        this.poolTimerInterval      // poll every 50 milliseconds for ready state
				    );
				 }
			
			}
		}
	
	},
	
	/**
	*
	*@private
	*/	
	readyPoolFunc:function() {
		if(this.req && this.req.readyState == 4) {
			window.clearInterval(this.readyStateTimer);
			this.readyStateTimer = null;
			// call the current response handlet - responseHandler by default 
			this.readyStateFunction();
		}
	},

/**
* Default method for parsing the response from the server. It will try to eval the obj.method_to_call property and pass the native JS object
*private
*/
	responseHandler:function() {
		if(this.req) {
			try {
			// only if req shows "complete"
			if (this.req.readyState == 4) {
				// only if "OK"	
				if (this.req.status && this.req.status == 200) {
					if(this.req.responseText.indexOf('ajax_msg_failed') != -1) {
						this.showDebug("Fatal Error: mybic_server sent back ajax_msg_failed! - MSG: "+this.req.responseText+"<br/>");
						if(this.req.responseText.indexOf('notauth') != -1) { 
							if(this.abort_timeout != -1) { clearInterval(this.end_timer); }
							if(this.notAuthorizedHandler == '') {
								this.callBack('notauthorized'); 
							} else {
								try {
									this.notAuthorizedHandler();
								}catch(e) {
								
								}
							}
						
						} else {
							this.callBack(false); 
						}
					} else {
						// clear out network down timer
						if(this.abort_timeout != -1) {
							clearInterval(this.end_timer);
						}
						if(this.throttle == 1) {
							var req = this._msgQueueInfo.shift();
							var format = req.format;
						} else {
							var format = this.format;	
						}
					
						if(format == "JSON") {
						try {
							var myObject = JSON.parse(this.req.responseText);
						// callback function we passed to the server to process the results
					
						if(document.getElementById(this.callBack)) {
							document.getElementById(this.callBack).innerHTML = myObject;
						} else {
							this.callBack(myObject);
						}
					
						} catch(e) {
						
								if(this.jsErrorHandler == '') {
									alert('An error occurred in your response function, NOT mybic related. Error Name: ' + e.name + '  Message:' + e.message);
								} else {
									// try to call the users javascript error handling function, have to show a popup if that sucker has en error in it too
									try { this.jsErrorHandler(e); } catch(err) { alert('Error: your errorhandling function has an error - name: '+err.name + ' message: '+err.message)}
								}
								
						}
						} else if(this.format == "XML") {
							// send the raw xml data to the callback function
							this.callBack(this.req.responseXML);	
						} else {
							try {
								if(document.getElementById(this.callBack)) {
								document.getElementById(this.callBack).innerHTML = this.req.responseText;
								} else {
									this.callBack(this.req.responseText);
								}
							} catch(e) {
								if(this.jsErrorHandler == '') {
									alert('An error occurred in your response function, NOT mybic related. Error Name: ' + e.name + '  Message:' + e.message);
								} else {
									// try to call the users javascript error handling function, have to show a popup if that sucker has en error in it too
									try { this.jsErrorHandler(e); } catch(err) { alert('Error: your errorhandling function has an error - name: '+err.name + ' message: '+err.message)}
								}
							}

						}
				
					}
				
					this.failed_requests = 0; // reset failed requests back to 0
				} else {
					// server is came back with a bad status
					try{
					this.showDebug("Fatal Error: MSG: "+this.req.responseText+" StatusText: "+this.req.statusText+"<br/>");
					} catch(e){}
					this.endCall();	
				}
				try {
					if(this.debug == 1 && this.currentCallIgnore != 1) {
							// STRIP HTML
							var str = this.req.responseText.replace(/(\<)/gi, '&lt;');
							var str = str.replace(/(\>)/gi, '&gt;');
							this.showDebug("HTTP Server Response:<br/> "+str+"<br>");
						}
				} catch(e) { }
				
				// reset the method, format, etc back to class defaults
				this.restoreDefaults();
			
				// reset our queue and call
				this.queue_in_process = 0;
				this.req = null;
				if(this.queue.length > 0) {
				
					this.call('','','queue');
				}
			}
			} catch(e) { 
			/*network is down*/}
		}
	
	},


	/**
	* This method will allow you to lazy load javascript source files for on-demand javascript processing
	* Just pass in the url of the javascript file you wish to pull in and you're all set, its not ajaxified so you 
	* can pull in scripts from any URL you want! You can also use this function to remove scripts from the DOM as well
	* Just pass in 'remove' as the 2nd parameter with the 1st parameter being the file you want to remove
	* Removal is good for memory heavy desktop type apps you might be creating
	*@param string The URL of the script you wish to load
	*@param string OPTIONAL If you wish to remove a javascript file from the DOM then just pass in 'remove' as the 2nd parameter
	*/
	loadScript:function(url, remove) {
		try{
			// lets check to see if the script is already loaded, if so lets remove it and add a new one
			var scripts = document.getElementsByTagName('script');
			s_len = scripts.length;
			for(var i=0;i<s_len;i++){
				var reg = new RegExp(url+"$");
				if (reg.test(scripts[i].src)) {
					var p2 = scripts[i];
					p2.parentNode.removeChild(p2);
					break;
				}	
			}
			if(remove != 'remove') {
				newScript = document.createElement("script");
				newScript.setAttribute("type", "text/javascript");
				newScript.setAttribute("src", url);
				document.getElementsByTagName('head')[0].appendChild(newScript);
			}
		} catch(e) {
			this.showDebug("MyBIC - loadScript failed URL: "+url+" ErrName: "+e.name+" Msg: "+e.message);	
		}

	},

	/**
	* This method will allow you to create a "command queue" so ajax requests are sent in order they were fired. 
	* You will be able to keep your request/responses in order they were sent
	*@private
	*/
	add2Queue:function(queryVars, userCallback) {
		var addAjax = new Array();
		addAjax['queryVars'] = queryVars;
		addAjax['userCallback'] = userCallback;
		addAjax['ignoreCall'] = this.ignoreCall;
		addAjax['abortTimeout'] = this.abort_timeout;
		addAjax['format'] = this.format;
		addAjax['method'] = this.method;
		addAjax['async'] = this.async;
		var opts = new Object();
		opts.format = this.format;
		this._msgQueueInfo.push(opts);
		this.ignoreCall=0;	// reset back to original state
		this.queue.push(addAjax);
	},


	/**
	* Method called after callback function is called to return the class to a default state
	*@private
	*/
	restoreDefaults:function()
	{
		this.method = "POST";
		this.format = "JSON";	
		this.callback = "";
		this.abort_timeout = 5000;
		this.failed_threshold = 3;	
		this.async = true;
	
	},

	/**
	* This method will allow you to get all your form fields in ONE magical step!
	* right before your ajaxObj.call statement all you have to do is: var form_vars = ajaxObj.getForm('formid');
	* that will loop through the form id you pass, and put all the form variables into your query string!
	*@param string The ID of the form you wish to submit
	*@return string An encoded query string, ready to send to the server
	*/

	getForm:function(formid) {
		var formobj = document.getElementById(formid);
		var fields = new Array();
		var form_len = formobj.elements.length;
		for (var x = 0; x < form_len; x++) {
		switch(formobj.elements[x].type) {
		   case 'select-one':
	
			fields.push(encodeURIComponent(formobj.elements[x].name)+'='+encodeURIComponent(formobj.elements[x].options[formobj.elements[x].selectedIndex].value));
			break;
			case 'select-multiple':
			var obj = formobj.elements[x];
				for(var y=0; y < formobj.elements[x].options.length; y++) {
				   if(formobj.elements[x].options[y].selected) {
							if(formobj.elements[x].options[y].value == ''){
								fields.push(encodeURIComponent(formobj.elements[x].name)+'='+encodeURIComponent(formobj.elements[x].options[y].text));
							} else {
								fields.push(encodeURIComponent(formobj.elements[x].name)+'='+encodeURIComponent(formobj.elements[x].options[y].value));
							}
				   }
				}
			break;
			case 'radio':
				   if(formobj.elements[x].checked) {
						   fields.push(encodeURIComponent(formobj.elements[x].name)+'='+encodeURIComponent(formobj.elements[x].value));
				   }
	        break;
			case 'checkbox':
				if(formobj.elements[x].checked) {
					fields.push(encodeURIComponent(formobj.elements[x].name)+'='+encodeURIComponent(formobj.elements[x].value));
				}
			break;
			default:
			// text, password, textarea, etc
			fields.push(encodeURIComponent(formobj.elements[x].name)+'='+encodeURIComponent(formobj.elements[x].value));
			break;
		}
		}
		var new_qstring = '&' + fields.join('&');
		return new_qstring;
	},

	/**
	* This method will check the request call after x number of seconds to see if the network call is still hanging
	* You will want to use this when your server might be down and after 5 seconds you'll want to call a function that lets the 
	* user know you're having server issues
	*@private
	*/
	endCall:function() {
		try{
			this.net_down_func();
			this.req.abort();
			this.req = null;
			clearInterval(this.end_timer);
			clearInterval(this.readyStateTimer);
			// increase failed requests variable
			this.failed_requests++;
			// reset our queue and call
			this.queue_in_process = 0;
			if(this.queue.length > 0) {
			
				this.call('','','queue');
			}
		
			if(this.debug == 1) { this.showDebug("Request Failed - Network Down! Current Failed Attempts: "+this.failed_requests+"<br>");}
		} catch(e) {
			// server is completely down, call netdown function
			clearInterval(this.end_timer);
			this.net_down_func('disable');
		}
		this.req = null;
	},
	
	/**
	* Default method that will be called when mybic experiences a network down situation, or is disabled
	* This method will pop up a div alerting the user of a general network error
	* You should define your own method to properly fit in with your page layout
	*@private
	*/
	down:function(status) {
		var notif_div = '<div id="mybic_notification" style="text-align:center;padding:20px;position:absolute;top:100px;left:100px;width:300px;border:thin solid black;background-color:#F8F021;">';
		notif_div 	+= 	'<span id="mybic_notif_msg"> MSGHERE </span> <br><br><input type="button" value="OK" onclick="document.getElementById(\'mybic_notification\').style.display=\'none\';"></div>';
		if(status == 'disable') {
			var notif = 'A network issue has disabled network connections for this page. Please reload this page or contact the site administrator';
		} else {
			var notif = 'A network issue has occurred which canceled your last request';
		}
		// lets try and find an existing error message or use the current one in the DOM
		try{
			if(document.getElementById('mybic_notification')) {
				document.getElementById('mybic_notification').style.display='block';
			} else {
				var new_div = document.createElement('div');
				new_div.innerHTML = notif_div;
				document.body.appendChild(new_div);
			}
			document.getElementById('mybic_notif_msg').innerHTML = notif;
		} catch(e) { 
			alert('Network Unavailable: Please re-load page or contact the site administrator');
		}
	},

	/**
	* This method will allow you to lazy load stylesheet source files for on-demand css
	* Just pass in the url of the css file you wish to pull in and you're all set, its not ajaxified so you 
	* can pull in scripts from any URL you want. 
	*@param string The URL of the css file you wish to load /lib/css/myfile.css
	*/
	loadCSS:function(url)
	{
		try{
			// lets check to see if the script is already loaded, if so lets remove it and add a new one
			var scripts = document.getElementsByTagName('link');
			s_len = scripts.length;
			if(s_len > 0) {
				for(var i=0;i<s_len;i++){
					var reg = new RegExp(url+"$");
					if (reg.test(scripts[i].href)) {
						var p2 = scripts[i];
						p2.parentNode.removeChild(p2);
						break;
					}	
				}
			}
				newScript = document.createElement("link");
				newScript.setAttribute("type", "text/css");
				newScript.setAttribute("rel", "stylesheet");
				newScript.setAttribute("href", url);
				document.getElementsByTagName('head')[0].appendChild(newScript);
			                                                 
		} catch(e) {
			if(this.debug==1) {this.showDebug("MyBIC - loadCSS failed URL: "+url+" ErrName: "+e.name+" Msg: "+e.message)};	
		}

	},
	
	/**
	* This method will let developers view debug information on the screen from not only the system calls but also let them tap into it as well
	*@access public
	*@param string The Message you wish to push to debugging or pass in 'break' and the UI will break a new expandable column for you
	*@param string OPTIONAL: If you pass in break, also send in a string that will show what the break label should be
	*@param int OPTIONAL: If you pass in break, if you pass in a 1 to the function it will allow you have your section autoexpanded
	*/
	showDebug:function(msg, label, expand) {
	  if(this.debug == 1)
	  {
	    if(!document.getElementById('mybic_debug')) {
	      var errs = document.createElement('div');
	      errs.id = 'mybic_errs';
	      var deb = document.createElement('div');
	      deb.id = 'mybic_debug';
	      deb.style.border = "thick solid black";
	      deb.style.backgroundColor = "#eeeeee";
	      deb.style.padding = "10px"; 
	      deb.style.margin = '75px 10px 10px 10px';
	      deb.style.width = '90%';
	      deb.style.position = 'absolute';
	      deb.style.zIndex = '999';
	      deb.innerHTML += 'MyBic Debugger: <a href="#" onclick="document.getElementById(\'mybic_errs\').style.display = (document.getElementById(\'mybic_errs\').style.display==\'none\') ? \'\':\'none\'; return false;" >hide/show me!</a>';
	      deb.innerHTML += '&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" onclick="document.getElementById(\'mybic_errs\').innerHTML = \'\'; return false;">Clear</a>';
	      deb.innerHTML += '&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" onclick="XMLHTTP.prototype.debug_expand(\'block\');return false;">Expand All</a>'; //TODO:
	      deb.innerHTML += '&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" onclick="XMLHTTP.prototype.debug_expand(\'none\'); return false;">Contract All</a><br><br>';//TODO:
	      deb.appendChild(errs);
	      if(document.body) {
	        document.body.appendChild(deb);
	      } else {
	        document.lastChild.appendChild(deb);
	      }
	    }
	    var deb = document.getElementById('mybic_errs');
	    if(msg == 'new') {
	      this.debugID++;
	      var dimg = '<a style="color:white;font-size:1.1em;text-decoration:none" href="#" onclick="XMLHTTP.prototype.debug_expand(this);return false;">+</a>';
	      deb.innerHTML += '<div id="mybiclabel_'+this.debugID+'" style="display:block;border:thin solid #999999;padding:2px;background-color:#cccccc;">'+dimg+' label'+this.debugID+': '+label+'</div>';
	    } else {
	      deb.innerHTML +='<div class="mybic_debug'+this.debugID+'" style="padding:5px;display:none; border:thin solid white;">'+msg+'</div>';
	    } 
	  }   
	},


	/**
	* This method is used by MyBic to visually show the debug data you've passed in
	*@private
	*@param object The image element that triggers the show/hide functionality
	*/
	debug_expand:function(el) {
		var deb = document.getElementById('mybic_errs');
		var deb_len = deb.childNodes.length;
		if(el == 'none' || el == 'block') {
			var label = "mybic_debug";
			var links = deb.getElementsByTagName('a');
			var links_len = links.length;
			for(var q=0;q<links_len;q++) {
				links[q].innerHTML = (el == 'none') ? '+' : '>';	
			}
		} else {
			var label = el.parentNode.id;
			label = label.split('_');
			label = "mybic_debug"+label[1];
		}
		for(var i=0; i<deb_len; i++) {
			// loop through and show the elements with the right classname
			try {
			if(deb.childNodes[i].className.match(new RegExp("(^"+ label + ".*$)"))) {
				if(el == 'none' || el == 'block') {
					deb.childNodes[i].style.display = el;
				} else {
					if(deb.childNodes[i].style.display == 'block') {
						el.innerHTML = '+';
						deb.childNodes[i].style.display = 'none';
					} else {
						el.innerHTML = '>';
						deb.childNodes[i].style.display = 'block';
					}
				}
			}
			} catch(e) {}
		}
	},

	/**
	* This method is a wrapper for when you have a callback funciton that has to finish executing before any other ajax calls are made
	* You may be in this situation when ajax calls rely on each other IE don't update this data until I finish processing the other data
	*/
	restart:function() {
		this.stopRequest=0;
		this.call('','','queue');
	}
	
};
	
	
/****************************************************/
// INCLUDE JSON.ORG's JSON CLIENT SIDE SERIALIZER
/*
Copyright (c) 2005 JSON.org
*/

/**
*    The global object JSON contains two methods.
*
*    JSON.stringify(value) takes a JavaScript value and produces a JSON text.
*    The value must not be cyclical.
*
*    JSON.parse(text) takes a JSON text and produces a JavaScript value. It will
*    return false if there is an error.
*/
var JSON = function () {
    var m = {
            '\b': '\\b',
            '\t': '\\t',
            '\n': '\\n',
            '\f': '\\f',
            '\r': '\\r',
            '"' : '\\"',
            '\\': '\\\\'
        },
        s = {
            'boolean': function (x) {
                return String(x);
            },
            number: function (x) {
                return isFinite(x) ? String(x) : 'null';
            },
            string: function (x) {
                if (/["\\\x00-\x1f]/.test(x)) {
                    x = x.replace(/([\x00-\x1f\\"])/g, function(a, b) {
                        var c = m[b];
                        if (c) {
                            return c;
                        }
                        c = b.charCodeAt();
                        return '\\u00' +
                            Math.floor(c / 16).toString(16) +
                            (c % 16).toString(16);
                    });
                }
                return '"' + x + '"';
            },
            object: function (x) {
                if (x) {
                    var a = [], b, f, i, l, v;
                    if (x instanceof Array) {
                        a[0] = '[';
                        l = x.length;
                        for (i = 0; i < l; i += 1) {
                            v = x[i];
                            f = s[typeof v];
                            if (f) {
                                v = f(v);
                                if (typeof v == 'string') {
                                    if (b) {
                                        a[a.length] = ',';
                                    }
                                    a[a.length] = v;
                                    b = true;
                                }
                            }
                        }
                        a[a.length] = ']';
                    } else if (x instanceof Object) {
                        a[0] = '{';
                        for (i in x) {
                            v = x[i];
                            f = s[typeof v];
                            if (f) {
                                v = f(v);
                                if (typeof v == 'string') {
                                    if (b) {
                                        a[a.length] = ',';
                                    }
                                    a.push(s.string(i), ':', v);
                                    b = true;
                                }
                            }
                        }
                        a[a.length] = '}';
                    } else {
                        return;
                    }
                    return a.join('');
                }
                return 'null';
            }
        };
    return {
        copyright: '(c)2005 JSON.org',
        license: 'http://www.JSON.org/license.html',
/**
*    Stringify a JavaScript value, producing a JSON text.
*/
        stringify: function (v) {
            var f = s[typeof v];
            if (f) {
                v = f(v);
                if (typeof v == 'string') {
                    return v;
                }
            }
            return null;
        },
/*
    Parse a JSON text, producing a JavaScript value.
    It returns false if there is a syntax error.
*/
        parse: function (text) {
            try {
                return !(/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(
                        text.replace(/"(\\.|[^"\\])*"/g, ''))) &&
                    eval('(' + text + ')');
            } catch (e) {
                return false;
            }
        }
    };
}();
