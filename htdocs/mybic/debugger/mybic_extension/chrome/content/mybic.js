var serverSocket;
var sock_listener;

function mybic_start_listener()
{
  var listener =
  {
    onSocketAccepted : function(socket, transport)
    {
      try {
        /*
		var transprop = '';
		for(var i in transport) {
			transprop += 'Prop: '+i +' = '+transport[i]+'<BR>';	
		}
        */
		var transprop = '';
		transprop = 'received';
		
		var stream = transport.openInputStream(0,0,0);
		var instream = Components.classes["@mozilla.org/scriptableinputstream;1"]
		  .createInstance(Components.interfaces.nsIScriptableInputStream);
		instream.init(stream);
		
		var outstream = transport.openOutputStream(0,0,0);
		outstream.write(transprop,transprop.length);
		outstream.close();
		
	
		var dataListener = {
		  data : "",
		  onStartRequest: function(request, context){},
		  onStopRequest: function(request, context, status){
			instream.close();
			stream.close();
			outstream.close();
			//listener.finished(this.data);
            
            // forward data to mybic to display properly
			mybic.processData(this.data);
		  },
		  onDataAvailable: function(request, context, inputStream, offset, count){
			 //alert('INCOMING: '+instream.read(count));
			 
			this.data += instream.read(count);
			//content.document.getElementById('tester').innerHTML += 'getting stream: '+this.data+'<br>';
		  },
		};
		
		var pump = Components.
		  classes["@mozilla.org/network/input-stream-pump;1"].
			createInstance(Components.interfaces.nsIInputStreamPump);
		pump.init(stream, -1, -1, 0, 0, true);
		pump.asyncRead(dataListener,null);
		
		
		
      } catch(ex2){ dump("::"+ex2); }
    },

    onStopListening : function(socket, status){}
  };

  try {
    serverSocket = Components.classes["@mozilla.org/network/server-socket;1"]
                     .createInstance(Components.interfaces.nsIServerSocket);

    serverSocket.init(90210,false,-1);
    serverSocket.asyncListen(listener);
  } catch(ex){ dump(ex); }

 
}

function stop()
{
  if (serverSocket) serverSocket.close();
}







