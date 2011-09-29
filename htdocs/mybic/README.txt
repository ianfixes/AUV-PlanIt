My-BIC READ ME


Step 1: You'll have 3 files in the zip file

    mybic.js - Javascript class to create xmlhttprequest object and make server calls

    mybic_server.php - The front controller, all ajax requests will be sent to this page

    mybic_json.php - If JSON encoding is requested, the response from your class will be JSON encoded(Michal Migurski)

    Install those files into your root directory


Step 2: Configure your mybic_server.php file
	
	This step is optional. If you leave mybic server in your root directory then you just need to define where you want to include your 
	php files. 

	(you could change)
    define("SERVER_ROOT", dirname(__FILE__).'/');

	(to below, but it is OPTIONAL)
    define("SERVER_ROOT", "/usr/local/www/"); (OPTIONAL)

	(This should be set to where your php files are kept that you wish to use. For example if you want all  your ajax requests going to 
	inc/ just define your INC PATH like it is below)
    define("INC_PATH", SERVER_ROOT.'inc/');



Change these paths to your server root (where your site lives on disk) and the include path. 
For example if you put your helloworld.php file into the inc directory you'll use the format above for INC_PATH. 
Basically SERVER_ROOT is the root of your website and INC_PATH is where mybic_server is going to be able to find your 
PHP classes to handle these ajax requests.


For example I'm going to put my include path to /usr/local/www/inc which is the location that will hold all of 
my php classes to handle ajax requests. You do not have to define this if you want to keep mybic server in the root directory. 
