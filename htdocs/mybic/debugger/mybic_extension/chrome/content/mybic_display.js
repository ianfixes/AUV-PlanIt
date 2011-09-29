/**
 * The mybic display class will process the incoming data and turn it into viewable data in the browser window
*/

function MyBicDisplay()
{
    
}


MyBicDisplay.prototype = {
  
  processData:function(input) {
        //console.log(input);
        try {
            var FB = new FirebugConsole();
            var JSON_data = JSON.parse(input);
            
            FB.group("MYBIC");
            
                // log static data
                if(!JSON_data) {
                    FB.log(input);
                } else {
                     // log profile data
                    FB.group("PROFILER");
                    var pr = JSON_data.PROFILER;
                    for(var i in pr) {
                        FB.log(pr[i].time + " " + pr[i].perc + ' ' + i);
                    }
                    FB.groupEnd();
                    FB.dir(JSON_data.VARIABLE_DUMPS);
                }
            
            FB.groupEnd();
        } catch (e) {
            // firebug probably not installed -> todo: alert the user somehow   
        }
  },
    
    
};


var mybic = new MyBicDisplay();