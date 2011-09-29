<?php
/**
 * MYBIC DEBUGGER CLASS www.litfuel.net/mybic
 *@author JIM PLUSH jiminoc@gmail.com
 *TODO:
 * add profiler data to text file
 * add ability to set a css file or change the colors of the debugger
 */
class MybicDebugger {
    
    var $_elements = array();
    var $_dumpers = array();
    var $timer = '';
    var $_tagCnt = 0;
    /**
     * if set to true the debugger will try to communicate with the firefox extension to process your
     * data. great for seeing what's happening in your ajax calls
     */
    var $_ff_extension = false;
    /**
     * if a file is passed in, the data will be automatically written to the file
     */
    var $_file = '';
    
    
    function MybicDebugger($file=false) {
        $this->timer =& new MYBIC_Benchmark_Timer(true);
        $this->_file = $file;
    }
    
    function deb($el, $tag="MISC") {
        $tag = $this->_setTag($tag);
        if(is_array($el)) {
            array_push($this->_dumpers, array($tag => $el)); 
        } else if(is_object($el)) {
            $el = $this->_convertObjectToArray($el);
            array_push($this->_dumpers, array($tag => $el)); 
        } else if(strstr($el, '.xml')) {
            array_push($this->_dumpers, array($tag => $this->_xml2array($el))); 
        } else {
            $caller = debug_backtrace();
            $this->_logMsg($el, $caller);
        }
    }
    
    
    function render() {
       $this->timer->stop();

        if($this->_ff_extension) {
            $this->_sendToExtension();
			$this->_dumpers = array();
        } else {
            $profiling = $this->_getProfiling();
            $output .= $this->_getBody();
            foreach($this->_dumpers as $item) {
                $deb_data .= $this->_parseDumpers($item);
            }
            
            $output = str_replace("{DEBUGDATA}", $deb_data, $output);
            $output = str_replace("{PROFILEDATA}", $profiling, $output);
            return $output;
        }
        
       
    
    }
    
    
    /**
     * This method exposes the use of the firefox extension to help you debug server side issues. Pass in true to enable communication
     * with the mybic firefox extension
     * @param {boolean} $on Pass in true to enable communication with the firefox extension for debugging ajax requests, false to disable
     * @param {boolean} $profiling Pass in true to have the profiling data sent with the render() command, false if not needed.
     */
    function enableExtension($on, $profiling=true)
    {
        $this->_ff_extension = $on;
		$this->_ff_profiling = $profiling;
		
    }
    
    /**
     * sends JSON formatted data to the browser to display debugging information
     */
    function _sendToExtension()
    {
        // LOAD UP DUMPERS
        $JSON = new MYBIC_Services_JSON();
        $debug_data['VARIABLE_DUMPS'] = $this->_convertDumpersFF();
        if($this->_ff_profiling) {
        	$debug_data['PROFILER'] = $this->_convertProfileData($this->timer->getProfiling());
		}
        
	    $output =  $JSON->encode($debug_data);
        
        $service_port = 90210;
        $address = 'localhost';
        
        
        /* Create a TCP/IP socket. */
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket !== false) {

            $result = @socket_connect($socket, $address, $service_port);
            if ($result !== false) {
                socket_write($socket, $output, strlen($output));
                socket_close($socket);
            }
        }
    }
    
    /**
     * converts the dumped variables to a format more readable by firebug
     */
    function _convertDumpersFF()
    {
        $results = array();
        if(is_array($this->_dumpers)) {
            foreach($this->_dumpers as $k => $arr) {
                if(is_array($arr)) {
                    foreach($arr as $label => $v) {
                       $results[$label] = $v; 
                    }
                }
            }
        }
        return $results;
    }
    
    /**
     * this method transforms the profiling data into something more useful for firebug
     */
    function _convertProfileData($profile)
    {
        $i=0;
       $total = $this->timer->TimeElapsed();
       
       foreach ($profile as $k => $v) {
            $perc = (($v['diff'] * 100) / $total);
            $results[$v['name']]['time'] = number_format($v['diff'], 10, '.', '').' seconds';
            $results[$v['name']]['perc'] = number_format($perc, 2, '.', '') . "%";
            $i++;
        }
        $results["TOTAL"]['time'] = $total.' seconds';
        $results["TOTAL"]['perc'] = '100%';
        return $results;
    }
    
    
    function _logMsg($msg,$caller)
    {
        // get the file and line number that called us
        
        $msg = basename($caller[0]['file']) .':'.$caller[0]['line']. ' - '.$msg;
        if($this->_file) {
            $this->_writeLine($msg);
        } 
            $this->timer->setMarker($msg);
        
    }
    
    /**
     * if no tag is set we want to append a number at the end so that it is unique still
     */
    function _setTag($tag)
    {
        if($tag == 'MISC') {
            $tag = $tag.$this->_tagCnt;
            $this->_tagCnt++;
        }
        return $tag;
    }
    
    /**
     * writes a line to the currently set file, will write as deb method is called so you can monitor live data
     * @param {string} $msg The messsage that will be sent to the file
     */
    function _writeLine($msg)
    {
        if($f = fopen($this->_file, 'a+')) {
            $time = date('d/m/y h:i:s');
            $msg = $time.": ".$msg."\r\n";
            fwrite($f, $msg);
            fclose($f);
        } 
    }
    
    
    function _getBody()
    {
        $body .= $this->_getStyleJSInfo();
        $body .= '<div id="mybic_debug_container">';
        $body .= '  <div id="mybic_debug_left">';
        $body .= '      <div>MYBIC DEBUGGER | <a href="#" onclick="mybic_debug_showProfiler();">PROFILER TOGGLE</a> | ';
        $body .= '      <a href="#" onclick="mybic_toggleShowAll(\'show\');">EXPAND ALL</a> | ';
        $body .= '      <a href="#" onclick="mybic_toggleShowAll();">HIDE ALL</a> |</div>';
        $body .= '      <ol id="mybic_debug" class="mybic_debug_leaf_open">{DEBUGDATA}</ol>';
        $body .= '  </div>';
        $body .= '  <div id="mybic_debug_right">';
        $body .= '      {PROFILEDATA}';
        $body .= '  </div>';
        $body .= '</div>';
        return $body;
    }
    
    function _parseDumpers($dumpers)
    {
            foreach($dumpers as $key => $item) {
                    if($this->_file) {
                        $predata = "\r\nARRAY DUMP: {$key}\r\n";
                        $this->_writeLine($predata.$this->_logArray($item));
                    } 
                        $out .= '<li class="mybic_debug_branch" onclick="mybic_debug_branch(event);"> '.$key.''."\r\n".' <ol class="mybic_debug_leaf_closed">';
                        $out .= $this->_parseArray($item);
                        $out .= '</ol>';
                        return $out;
                    
            }   
    }
    
    /**
     * This method parses an array and converts it into a readable dump in a text file if you pass in a debug file
     */
    function _logArray($arr, $cnt=1) {
        foreach($arr as $key => $item) {
            
            if(is_array($item)) {
                $out .= $this->_logArray(array("MULTI"=>$key),  ($cnt+1));
                $out .= $this->_logArray($item,  ($cnt+1));
            } else {
                $out .= str_repeat("\t", $cnt)."{$key}: {$item}\r\n";
                
            }
        }
        return $out;
    }
    
    function _parseArray($arr)
    {
        //var_dump($arr);
        foreach($arr as $key => $item) {
            if(is_array($item)) {
                $out .= '<li class="mybic_debug_branch" onclick="mybic_debug_branch(event);"> '.$key.' <ol class="mybic_debug_leaf_closed">';
                $out .= $this->_parseArray($item);
                $out .= '</ol>';
            } else {
                $out .= $this->_buildLeaf($key, $item);
            }
        }
        return $out;
    }
    
    function _buildLeaf($k, $v)
    {
        $str ="<li>{$k}: {$v}</li>\r\n";
        return $str;
    }
    
    function _getProfiling()
    {
        $html =  $this->timer->getOutput();
        return $html;
    }
    
    /**
 	*
 	* This recursive function takes an array of objects and converts all the objects to an array - soap client by default returns an array of objects
 	*
 	*@param mixed An Array of Objects you want to loop through and convert to arrays
 	*@return mixed An Array
 	*/
 	function _convertObjectToArray($array)
 	{
 		if(is_array($array)) {
	 		foreach($array as $k => $v) {
	 			if(is_object($v)) {
	 				$array[$k] = (array)$v;
	 			}	
	 			if(is_array($array[$k])){
	 				$array[$k] =  $this->_convertObjectToArray($array[$k]);
	 			}	
	 		}
 		}
 		return $array;
 	}
    
    function _xml2array($xml) {
        
        $contents = implode("", file($xml));
        if(!$contents) return array();

        if(!function_exists('xml_parser_create')) {
            //print "'xml_parser_create()' function not found!";
            return array();
        }
        //Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
        xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 );
        xml_parse_into_struct( $parser, $contents, $xml_values );
        xml_parser_free( $parser );
    
        if(!$xml_values) return;//Hmm...
    
        //Initializations
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();
    
        $current = &$xml_array;
    
        //Go through the tags.
        foreach($xml_values as $data) {
            unset($attributes,$value);//Remove existing values, or there will be trouble
            extract($data);//We could use the array by itself, but this cooler.
    
            $result = '';
            if($get_attributes) {//The second argument of the function decides this.
                $result = array();
                if(isset($value)) $result['value'] = $value;
    
                //Set the attributes too.
                if(isset($attributes)) {
                    foreach($attributes as $attr => $val) {
                        if($get_attributes == 1) $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                        /**  :TODO: should we change the key name to '_attr'? Someone may use the tagname 'attr'. Same goes for 'value' too */
                    }
                }
            } elseif(isset($value)) {
                $result = $value;
            }
    
            //See tag status and do the needed.
            if($type == "open") {//The starting of the tag '<tag>'
                $parent[$level-1] = &$current;
    
                if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    $current = &$current[$tag];
    
                } else { //There was another element with the same tag name
                    if(isset($current[$tag][0])) {
                        array_push($current[$tag], $result);
                    } else {
                        $current[$tag] = array($current[$tag],$result);
                    }
                    $last = count($current[$tag]) - 1;
                    $current = &$current[$tag][$last];
                }
    
            } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
                //See if the key is already taken.
                if(!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;
    
                } else { //If taken, put all things inside a list(array)
                    if((is_array($current[$tag]) and $get_attributes == 0)//If it is already an array...
                            or (isset($current[$tag][0]) and is_array($current[$tag][0]) and $get_attributes == 1)) {
                        array_push($current[$tag],$result); // ...push the new element into that array.
                    } else { //If it is not an array...
                        $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
                    }
                }
    
            } elseif($type == 'close') { //End of tag '</tag>'
                $current = &$parent[$level-1];
            }
        }
    
        return($xml_array); 
    }
    
    /**
     * holds css and javascript code related to the profiler when used to output on the screen
     */
    function _getStyleJSInfo()
    {
        $css = '<style>
                #mybic_debug_container {clear:both;color:#ffffff;padding:3px;font-family:tahoma,verdana;font-size:.8em;width:100%;border:thin solid #000000;background-color:#3F4C6B;float:left;}
                #mybic_debug_container a{color:orange;}
                #mybic_debug_left {overflow:auto;}
                #mybic_debug_right {padding:5px;background:#3F4C6B;width:90%;}
                ol#mybic_debug li, #mybic_debug_profiler {list-style:none;background-image:url(tree/page.gif);background-repeat:no-repeat;padding-left:13px;}
                ol#mybic_debug ol.mybic_debug_leaf_closed {display:none;list-style:none;padding-left:13px;}
                ol#mybic_debug li.mybic_debug_branch {display:block;list-style:none;padding-left:16px;background-image:url(tree/folder.gif);background-repeat:no-repeat;}
                ol#mybic_debug li.mybic_debug_branch_open {display:block;list-style:none;padding-left:16px;background-image:url(tree/folderopen.gif);background-repeat:no-repeat;}
                </style>
                ';
                
        $js = "<script>
                function mybic_debug_branch(e) {
                    
                    var el = e.target;
                    var nodes = el.getElementsByTagName('ol');
                    if(nodes.length > 0) {
                        if(nodes[0].style.display == 'block') {
                            el.className = 'mybic_debug_branch';
                            nodes[0].style.display = 'none';
                        } else {
                            el.className = 'mybic_debug_branch_open';
                            nodes[0].style.display = 'block';
                        }
                    }
                    if (!e) var e = window.event;
                    e.cancelBubble = true;
                    if (e.stopPropagation) e.stopPropagation();
                }
                
                function mybic_debug_showProfiler() {
                    var el = document.getElementById('mybic_debug_right');
                    if(el.style.display == 'none') {
                        el.style.display = 'block';   
                    } else {
                        el.style.display = 'none';
                    }
                }
                
                function mybic_toggleShowAll(expand) {
                    var nodes = document.getElementById('mybic_debug').getElementsByTagName('ol');
                    for(var i=0;i<nodes.length;i++) {
                        if(expand != 'show') {
                            nodes[i].style.display='none';
                            nodes[i].parentNode.className='mybic_debug_branch';
                        } else {
                            nodes[i].style.display='block';
                            nodes[i].parentNode.className='mybic_debug_branch_open';
                        }
                    }
                }   
                </script>";
        
        return $css.$js;
    }


}

?>
<?php
//
// +----------------------------------------------------------------------+
// | PEAR :: Benchmark                                                    |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Sebastian Bergmann <sb@sebastian-bergmann.de>               |
// +----------------------------------------------------------------------+
//
// $Id: benchmark.php,v 1.1.1.1 2006/12/15 22:07:03 jplush Exp $
//

/**
* Benchmark::Timer
*
* Purpose:
*
*     Timing Script Execution, Generating Profiling Information
*
* Example with automatic profiling start, stop, and output:
*
*     $timer =& new Benchmark_Timer(true);
*     $timer->setMarker('Marker 1');
*
* Example without automatic profiling:
*
*     $timer =& new Benchmark_Timer();
*
*     $timer->start();
*     $timer->setMarker('Marker 1');
*     $timer->stop();
*
*     $profiling = $timer->getProfiling();
*     echo $profiling->getOutput(); // or $timer->display()
*
* Contributors:
* - Ludovico Magnocavallo <ludo@sumatrasolutions.com>
*   auto profiling and get_output() method
*
* @author   Sebastian Bergmann <sb@sebastian-bergmann.de>
* @version  $Revision: 1.1.1.1 $
* @access   public
*/


class MYBIC_Benchmark_Timer
{
    // Contains the markers
    var $markers = array();

    // Auto-start and stop timer
    var $auto   = false;

    // Max marker name length for non-html output
    var $strlen_max = 0;
    
    // log to file?
    var $to_file = false;
    
    // delimiter for text output
    var $delimiter = "\t";
    
	// Constructor, starts profiling recording
    function MYBIC_Benchmark_Timer($auto = false) {
        if ($auto) {
            $this->auto = $auto;
            $this->start();
        }
    }

    // Destructor, stops profiling recording
    function _Benchmark_Timer() {
        if ($this->auto) {
            $this->stop();
            $this->display();
        }
    }

    // Return formatted profiling information.
    function getOutput($type='html'){
    	
        $delimiter = $this->delimiter;
        
        $total = $this->TimeElapsed();
        $result = $this->getProfiling();
        
        // output first row: column headers for html output
        if ($type == 'html') {
        	$out = '<script language="JavaScript" src="/js/sortTable.js"></script>';
            $out .= '<table style="border: 1px solid grey;  font: 10pt Verdana,sans-serif; color: navy;" bgcolor="ffffff">';
            $out .= '<thead><tr><td align=middle onclick="sortOnColumn(this);"><b>Marker Name</b></td><td align="center"><b>Time Index</b></td><td align="center" onclick="sortOnColumn(this);"><b>Ex Time</b></td><td align="center" onclick="sortOnColumn(this);"><b>%</b></td></tr></thead>'."\n";
        }
        
        foreach ($result as $k => $v) {
            $perc = (($v['diff'] * 100) / $total);
            if ($type == 'html') {
                $out .= "<tr><td><b>" . $v['name'] . "</b></td><td align=right>" . $v['time'] . "</td><td align=right>" . $v['diff'] . "</td><td align=right>" . number_format($perc, 2, '.', '') . "%</td></tr>\n";
            } else {
                $out .= $v['name'].$delimiter;
                $out .= $v['time'].$delimiter;
                $out .= $v['diff'].$delimiter;
                $out .= number_format($perc, 2, '.', '') . "%\r\n";
            }
        }
        if ($type == 'html') {
            $out .= "<tr style='background: silver;'><td><b>total</b></td><td>-</td><td>${total}</td><td>100.00%</td></tr>\n";
            $out .= "</table>\n";
        } else {
            $out .= 'total'.$delimiter;
            $out .= '-'.$delimiter;
            $out .= $total.$delimiter;
            $out .= "100.00%\r\n";
        }
        return $out;
    }

    /**
    * Prints the information returned by getOutput
    */
    function display()
    {
        print $this->getOutput();
    }

    /**
     * Set "Start" marker.
     *
     * @see    setMarker(), stop()
     * @access public
     */
    function start() {
        $this->start = $this->setMarker('Start');
    }

    /**
     * Set "Stop" marker.
     *
     * @see    setMarker(), start()
     * @access public
     */
    function stop() {
        $this->stop = $this->setMarker('Stop');
    }

    /**
     * Set marker.
     *
     * @param  string  name of the marker to be set
     * @see    start(), stop()
     * @access public
     */
    function setMarker($name) {
        $microtime = explode(' ', microtime());
        $time = (float)$microtime[1] + (float)$microtime[0];
        //$profileName = ($name != 'Start' && $name != 'Stop') ? $name.'<span style="display:none">'.uniqid('').'</span>' : $name;
		//$this->markers[$profileName] = (float)$microtime[1] + (float)$microtime[0];
		$this->markers[] = array('name' => $name, 'time' => $time );
		
		
		
		// return id of marker
		return count($this->markers)-1;
    }

    /**
     * Returns the time elapsed betweens two markers.
     *
     * @param  string  $start		start marker, defaults to class property start, set by method start()
     * @param  string  $stop		end marker, defaults to class property stop, set by method stop()
     * @return double  $time_elapsed time elapsed between $start and $end
     * @access public
     */
    function timeElapsed($start=false, $stop=false) {
    	
    	$start = !$start ? $this->start: $start;
    	$stop = !$stop ? $this->stop: $stop;
    	
        return $this->markers[$stop]['time'] - $this->markers[$start]['time'];
    }

    /**
     * Returns profiling information.
     *
     * $profiling[x]['name']  = name of marker x
     * $profiling[x]['time']  = time index of marker x
     * $profiling[x]['diff']  = execution time from marker x-1 to this marker x
     * $profiling[x]['total'] = total execution time up to marker x
     *
     * @return array $profiling
     * @access public
     */
    function getProfiling() {
        $i = $total = $temp = 0;
        $result = array();
		
        foreach ($this->markers as $id => $arr) {
        	$marker = $arr['name'];
        	$time = $arr['time'];
        	
            if (extension_loaded('bcmath')) {
                $diff  = bcsub($time, $temp, 6);
                $total = bcadd($total, $diff, 6);
            } else {
                $diff  = $time - $temp;
                $total = $total + $diff;
            }

            $result[$i]['name']  = $marker;
            $result[$i]['time']  = $time;
            $result[$i]['diff']  = $diff;
            $result[$i]['total'] = $total;

            $this->strlen_max = (strlen($marker) > $this->strlen_max ? strlen($marker) + 1 : $this->strlen_max);

            $temp = $time;
            $i++;
        }
        $result[0]['diff'] = '-';
        $this->strlen_max = (strlen('total') > $this->strlen_max ? strlen('total') : $this->strlen_max);
        $this->strlen_max += 4;
        return $result;
    }
}
?>
<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* Converts to and from JSON format.
*
* JSON (JavaScript Object Notation) is a lightweight data-interchange
* format. It is easy for humans to read and write. It is easy for machines
* to parse and generate. It is based on a subset of the JavaScript
* Programming Language, Standard ECMA-262 3rd Edition - December 1999.
* This feature can also be found in  Python. JSON is a text format that is
* completely language independent but uses conventions that are familiar
* to programmers of the C-family of languages, including C, C++, C#, Java,
* JavaScript, Perl, TCL, and many others. These properties make JSON an
* ideal data-interchange language.
*
* This package provides a simple encoder and decoder for JSON notation. It
* is intended for use with client-side Javascript applications that make
* use of HTTPRequest to perform server communication functions - data can
* be encoded into JSON notation for use in a client-side javascript, or
* decoded from incoming Javascript requests. JSON format is native to
* Javascript, and can be directly eval()'ed with no further parsing
* overhead
*
* All strings should be in ASCII or UTF-8 format!
*
* LICENSE: Redistribution and use in source and binary forms, with or
* without modification, are permitted provided that the following
* conditions are met: Redistributions of source code must retain the
* above copyright notice, this list of conditions and the following
* disclaimer. Redistributions in binary form must reproduce the above
* copyright notice, this list of conditions and the following disclaimer
* in the documentation and/or other materials provided with the
* distribution.
*
* THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
* WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
* MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
* NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
* INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
* BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
* OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
* TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
* USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
* DAMAGE.
*
* @category   
* @package     MYBIC_Services_JSON
* @author      Michal Migurski <mike-json@teczno.com>
* @author      Matt Knapp <mdknapp[at]gmail[dot]com>
* @author      Brett Stimmerman <brettstimmerman[at]gmail[dot]com>
* @copyright   2005 Michal Migurski
* @license     http://www.opensource.org/licenses/bsd-license.php
* @link        http://pear.php.net/pepr/pepr-proposal-show.php?id=198
*/

/**
* Marker constant for MYBIC_Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_SLICE',   1);

/**
* Marker constant for MYBIC_Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_IN_STR',  2);

/**
* Marker constant for MYBIC_Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_IN_ARR',  3);

/**
* Marker constant for MYBIC_Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_IN_OBJ',  4);

/**
* Marker constant for MYBIC_Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_IN_CMT', 5);

/**
* Behavior switch for MYBIC_Services_JSON::decode()
*/
define('SERVICES_JSON_LOOSE_TYPE', 16);

/**
* Behavior switch for MYBIC_Services_JSON::decode()
*/
define('SERVICES_JSON_SUPPRESS_ERRORS', 32);

/**
* Converts to and from JSON format.
*
* Brief example of use:
*
* <code>
* // create a new instance of MYBIC_Services_JSON
* $json = new MYBIC_Services_JSON();
*
* // convert a complexe value to JSON notation, and send it to the browser
* $value = array('foo', 'bar', array(1, 2, 'baz'), array(3, array(4)));
* $output = $json->encode($value);
*
* print($output);
* // prints: ["foo","bar",[1,2,"baz"],[3,[4]]]
*
* // accept incoming POST data, assumed to be in JSON notation
* $input = file_get_contents('php://input', 1000000);
* $value = $json->decode($input);
* </code>
*/
class MYBIC_Services_JSON
{
   /**
    * constructs a new JSON instance
    *
    * @param    int     $use    object behavior flags; combine with boolean-OR
    *
    *                           possible values:
    *                           - SERVICES_JSON_LOOSE_TYPE:  loose typing.
    *                                   "{...}" syntax creates associative arrays
    *                                   instead of objects in decode().
    *                           - SERVICES_JSON_SUPPRESS_ERRORS:  error suppression.
    *                                   Values which can't be encoded (e.g. resources)
    *                                   appear as NULL instead of throwing errors.
    *                                   By default, a deeply-nested resource will
    *                                   bubble up with an error, so all return values
    *                                   from encode() should be checked with isError()
    */
    function MYBIC_Services_JSON($use = 0)
    {
        $this->use = $use;
    }

   /**
    * convert a string from one UTF-16 char to one UTF-8 char
    *
    * Normally should be handled by mb_convert_encoding, but
    * provides a slower PHP-only method for installations
    * that lack the multibye string extension.
    *
    * @param    string  $utf16  UTF-16 character
    * @return   string  UTF-8 character
    * @access   private
    */
    function utf162utf8($utf16)
    {
        // oh please oh please oh please oh please oh please
        if(function_exists('mb_convert_encoding'))
            return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16');
        
        $bytes = (ord($utf16{0}) << 8) | ord($utf16{1});

        switch(true) {
            case ((0x7F & $bytes) == $bytes):
                // this case should never be reached, because we are in ASCII range
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x7F & $bytes);

            case (0x07FF & $bytes) == $bytes:
                // return a 2-byte UTF-8 character
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0xC0 | (($bytes >> 6) & 0x1F))
                     . chr(0x80 | ($bytes & 0x3F));

            case (0xFFFF & $bytes) == $bytes:
                // return a 3-byte UTF-8 character
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0xE0 | (($bytes >> 12) & 0x0F))
                     . chr(0x80 | (($bytes >> 6) & 0x3F))
                     . chr(0x80 | ($bytes & 0x3F));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }        

   /**
    * convert a string from one UTF-8 char to one UTF-16 char
    *
    * Normally should be handled by mb_convert_encoding, but
    * provides a slower PHP-only method for installations
    * that lack the multibye string extension.
    *
    * @param    string  $utf8   UTF-8 character
    * @return   string  UTF-16 character
    * @access   private
    */
    function utf82utf16($utf8)
    {
        // oh please oh please oh please oh please oh please
        if(function_exists('mb_convert_encoding'))
            return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
        
        switch(strlen($utf8)) {
            case 1:
                // this case should never be reached, because we are in ASCII range
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return $ut8;

            case 2:
                // return a UTF-16 character from a 2-byte UTF-8 char
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x07 & (ord($utf8{0}) >> 2))
                     . chr((0xC0 & (ord($utf8{0}) << 6))
                         | (0x3F & ord($utf8{1})));
                
            case 3:
                // return a UTF-16 character from a 3-byte UTF-8 char
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr((0xF0 & (ord($utf8{0}) << 4))
                         | (0x0F & (ord($utf8{1}) >> 2)))
                     . chr((0xC0 & (ord($utf8{1}) << 6))
                         | (0x7F & ord($utf8{2})));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }        

   /**
    * encodes an arbitrary variable into JSON format
    *
    * @param    mixed   $var    any number, boolean, string, array, or object to be encoded.
    *                           see argument 1 to Services_JSON() above for array-parsing behavior.
    *                           if var is a strng, note that encode() always expects it
    *                           to be in ASCII or UTF-8 format!
    *
    * @return   mixed   JSON string representation of input var or an error if a problem occurs
    * @access   public
    */
    function encode($var)
    {
        switch (gettype($var)) {
            case 'boolean':
                return $var ? 'true' : 'false';
            
            case 'NULL':
                return 'null';
            
            case 'integer':
                return (int) $var;
                
            case 'double':
            case 'float':
                return (float) $var;
                
            case 'string':
                // STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
                $ascii = '';
                $strlen_var = strlen($var);

               /*
                * Iterate over every character in the string,
                * escaping with a slash or encoding to UTF-8 where necessary
                */
                for ($c = 0; $c < $strlen_var; ++$c) {
                    
                    $ord_var_c = ord($var{$c});
                    
                    switch (true) {
                        case $ord_var_c == 0x08:
                            $ascii .= '\b';
                            break;
                        case $ord_var_c == 0x09:
                            $ascii .= '\t';
                            break;
                        case $ord_var_c == 0x0A:
                            $ascii .= '\n';
                            break;
                        case $ord_var_c == 0x0C:
                            $ascii .= '\f';
                            break;
                        case $ord_var_c == 0x0D:
                            $ascii .= '\r';
                            break;

                        case $ord_var_c == 0x22:
                        case $ord_var_c == 0x2F:
                        case $ord_var_c == 0x5C:
                            // double quote, slash, slosh
                            $ascii .= '\\'.$var{$c};
                            break;
                            
                        case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
                            // characters U-00000000 - U-0000007F (same as ASCII)
                            $ascii .= $var{$c};
                            break;
                        
                        case (($ord_var_c & 0xE0) == 0xC0):
                            // characters U-00000080 - U-000007FF, mask 110XXXXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c, ord($var{$c + 1}));
                            $c += 1;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;
    
                        case (($ord_var_c & 0xF0) == 0xE0):
                            // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}));
                            $c += 2;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;
    
                        case (($ord_var_c & 0xF8) == 0xF0):
                            // characters U-00010000 - U-001FFFFF, mask 11110XXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}));
                            $c += 3;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;
    
                        case (($ord_var_c & 0xFC) == 0xF8):
                            // characters U-00200000 - U-03FFFFFF, mask 111110XX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}),
                                         ord($var{$c + 4}));
                            $c += 4;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;
    
                        case (($ord_var_c & 0xFE) == 0xFC):
                            // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}),
                                         ord($var{$c + 4}),
                                         ord($var{$c + 5}));
                            $c += 5;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;
                    }
                }
                
                return '"'.$ascii.'"';
                
            case 'array':
               /*
                * As per JSON spec if any array key is not an integer
                * we must treat the the whole array as an object. We
                * also try to catch a sparsely populated associative
                * array with numeric keys here because some JS engines
                * will create an array with empty indexes up to
                * max_index which can cause memory issues and because
                * the keys, which may be relevant, will be remapped
                * otherwise.
                *
                * As per the ECMA and JSON specification an object may
                * have any string as a property. Unfortunately due to
                * a hole in the ECMA specification if the key is a
                * ECMA reserved word or starts with a digit the
                * parameter is only accessible using ECMAScript's
                * bracket notation.
                */
                
                // treat as a JSON object  
                if (is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1))) {
                    $properties = array_map(array($this, 'name_value'),
                                            array_keys($var),
                                            array_values($var));
                
                    foreach($properties as $property)
                        if(MYBIC_Services_JSON::isError($property))
                            return $property;
                    
                    return '{' . join(',', $properties) . '}';
                }

                // treat it like a regular array
                $elements = array_map(array($this, 'encode'), $var);
                
                foreach($elements as $element)
                    if(MYBIC_Services_JSON::isError($element))
                        return $element;
                
                return '[' . join(',', $elements) . ']';
                
            case 'object':
                $vars = get_object_vars($var);

                $properties = array_map(array($this, 'name_value'),
                                        array_keys($vars),
                                        array_values($vars));
            
                foreach($properties as $property)
                    if(MYBIC_Services_JSON::isError($property))
                        return $property;
                
                return '{' . join(',', $properties) . '}';

            default:
                return ($this->use & SERVICES_JSON_SUPPRESS_ERRORS)
                    ? 'null'
                    : new MYBIC_Services_JSON_Error(gettype($var)." can not be encoded as JSON string");
        }
    }
    
   /**
    * array-walking function for use in generating JSON-formatted name-value pairs
    *
    * @param    string  $name   name of key to use
    * @param    mixed   $value  reference to an array element to be encoded
    *
    * @return   string  JSON-formatted name-value pair, like '"name":value'
    * @access   private
    */
    function name_value($name, $value)
    {
        $encoded_value = $this->encode($value);
        
        if(MYBIC_Services_JSON::isError($encoded_value))
            return $encoded_value;
    
        return $this->encode(strval($name)) . ':' . $encoded_value;
    }        

   /**
    * reduce a string by removing leading and trailing comments and whitespace
    *
    * @param    $str    string      string value to strip of comments and whitespace
    *
    * @return   string  string value stripped of comments and whitespace
    * @access   private
    */
    function reduce_string($str)
    {
        $str = preg_replace(array(
        
                // eliminate single line comments in '// ...' form
                '#^\s*//(.+)$#m',
    
                // eliminate multi-line comments in '/* ... */' form, at start of string
                '#^\s*/\*(.+)\*/#Us',
    
                // eliminate multi-line comments in '/* ... */' form, at end of string
                '#/\*(.+)\*/\s*$#Us'
    
            ), '', $str);
        
        // eliminate extraneous space
        return trim($str);
    }

   /**
    * decodes a JSON string into appropriate variable
    *
    * @param    string  $str    JSON-formatted string
    *
    * @return   mixed   number, boolean, string, array, or object
    *                   corresponding to given JSON input string.
    *                   See argument 1 to MYBIC_Services_JSON() above for object-output behavior.
    *                   Note that decode() always returns strings
    *                   in ASCII or UTF-8 format!
    * @access   public
    */
    function decode($str)
    {
        $str = $this->reduce_string($str);
    
        switch (strtolower($str)) {
            case 'true':
                return true;

            case 'false':
                return false;
            
            case 'null':
                return null;
            
            default:
                if (is_numeric($str)) {
                    // Lookie-loo, it's a number

                    // This would work on its own, but I'm trying to be
                    // good about returning integers where appropriate:
                    // return (float)$str;

                    // Return float or int, as appropriate
                    return ((float)$str == (integer)$str)
                        ? (integer)$str
                        : (float)$str;
                    
                } elseif (preg_match('/^("|\').*(\1)$/s', $str, $m) && $m[1] == $m[2]) {
                    // STRINGS RETURNED IN UTF-8 FORMAT
                    $delim = substr($str, 0, 1);
                    $chrs = substr($str, 1, -1);
                    $utf8 = '';
                    $strlen_chrs = strlen($chrs);
                    
                    for ($c = 0; $c < $strlen_chrs; ++$c) {
                    
                        $substr_chrs_c_2 = substr($chrs, $c, 2);
                        $ord_chrs_c = ord($chrs{$c});
                        
                        switch (true) {
                            case $substr_chrs_c_2 == '\b':
                                $utf8 .= chr(0x08);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\t':
                                $utf8 .= chr(0x09);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\n':
                                $utf8 .= chr(0x0A);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\f':
                                $utf8 .= chr(0x0C);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\r':
                                $utf8 .= chr(0x0D);
                                ++$c;
                                break;

                            case $substr_chrs_c_2 == '\\"':
                            case $substr_chrs_c_2 == '\\\'':
                            case $substr_chrs_c_2 == '\\\\':
                            case $substr_chrs_c_2 == '\\/':
                                if (($delim == '"' && $substr_chrs_c_2 != '\\\'') ||
                                   ($delim == "'" && $substr_chrs_c_2 != '\\"')) {
                                    $utf8 .= $chrs{++$c};
                                }
                                break;
                                
                            case preg_match('/\\\u[0-9A-F]{4}/i', substr($chrs, $c, 6)):
                                // single, escaped unicode character
                                $utf16 = chr(hexdec(substr($chrs, ($c + 2), 2)))
                                       . chr(hexdec(substr($chrs, ($c + 4), 2)));
                                $utf8 .= $this->utf162utf8($utf16);
                                $c += 5;
                                break;
        
                            case ($ord_chrs_c >= 0x20) && ($ord_chrs_c <= 0x7F):
                                $utf8 .= $chrs{$c};
                                break;
        
                            case ($ord_chrs_c & 0xE0) == 0xC0:
                                // characters U-00000080 - U-000007FF, mask 110XXXXX
                                //see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 2);
                                ++$c;
                                break;
    
                            case ($ord_chrs_c & 0xF0) == 0xE0:
                                // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 3);
                                $c += 2;
                                break;
    
                            case ($ord_chrs_c & 0xF8) == 0xF0:
                                // characters U-00010000 - U-001FFFFF, mask 11110XXX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 4);
                                $c += 3;
                                break;
    
                            case ($ord_chrs_c & 0xFC) == 0xF8:
                                // characters U-00200000 - U-03FFFFFF, mask 111110XX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 5);
                                $c += 4;
                                break;
    
                            case ($ord_chrs_c & 0xFE) == 0xFC:
                                // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 6);
                                $c += 5;
                                break;

                        }

                    }
                    
                    return $utf8;
                
                } elseif (preg_match('/^\[.*\]$/s', $str) || preg_match('/^\{.*\}$/s', $str)) {
                    // array, or object notation

                    if ($str{0} == '[') {
                        $stk = array(SERVICES_JSON_IN_ARR);
                        $arr = array();
                    } else {
                        if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                            $stk = array(SERVICES_JSON_IN_OBJ);
                            $obj = array();
                        } else {
                            $stk = array(SERVICES_JSON_IN_OBJ);
                            $obj = new stdClass();
                        }
                    }
                    
                    array_push($stk, array('what'  => SERVICES_JSON_SLICE,
                                           'where' => 0,
                                           'delim' => false));

                    $chrs = substr($str, 1, -1);
                    $chrs = $this->reduce_string($chrs);
                    
                    if ($chrs == '') {
                        if (reset($stk) == SERVICES_JSON_IN_ARR) {
                            return $arr;

                        } else {
                            return $obj;

                        }
                    }

                    //print("\nparsing {$chrs}\n");
                    
                    $strlen_chrs = strlen($chrs);
                    
                    for ($c = 0; $c <= $strlen_chrs; ++$c) {
                    
                        $top = end($stk);
                        $substr_chrs_c_2 = substr($chrs, $c, 2);
                    
                        if (($c == $strlen_chrs) || (($chrs{$c} == ',') && ($top['what'] == SERVICES_JSON_SLICE))) {
                            // found a comma that is not inside a string, array, etc.,
                            // OR we've reached the end of the character list
                            $slice = substr($chrs, $top['where'], ($c - $top['where']));
                            array_push($stk, array('what' => SERVICES_JSON_SLICE, 'where' => ($c + 1), 'delim' => false));
                            //print("Found split at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                            if (reset($stk) == SERVICES_JSON_IN_ARR) {
                                // we are in an array, so just push an element onto the stack
                                array_push($arr, $this->decode($slice));

                            } elseif (reset($stk) == SERVICES_JSON_IN_OBJ) {
                                // we are in an object, so figure
                                // out the property name and set an
                                // element in an associative array,
                                // for now
                                if (preg_match('/^\s*(["\'].*[^\\\]["\'])\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                                    // "name":value pair
                                    $key = $this->decode($parts[1]);
                                    $val = $this->decode($parts[2]);

                                    if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                                        $obj[$key] = $val;
                                    } else {
                                        $obj->$key = $val;
                                    }
                                } elseif (preg_match('/^\s*(\w+)\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                                    // name:value pair, where name is unquoted
                                    $key = $parts[1];
                                    $val = $this->decode($parts[2]);

                                    if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                                        $obj[$key] = $val;
                                    } else {
                                        $obj->$key = $val;
                                    }
                                }

                            }

                        } elseif ((($chrs{$c} == '"') || ($chrs{$c} == "'")) && ($top['what'] != SERVICES_JSON_IN_STR)) {
                            // found a quote, and we are not inside a string
                            array_push($stk, array('what' => SERVICES_JSON_IN_STR, 'where' => $c, 'delim' => $chrs{$c}));
                            //print("Found start of string at {$c}\n");

                        } elseif (($chrs{$c} == $top['delim']) &&
                                 ($top['what'] == SERVICES_JSON_IN_STR) &&
                                 (($chrs{$c - 1} != '\\') ||
                                 ($chrs{$c - 1} == '\\' && $chrs{$c - 2} == '\\'))) {
                            // found a quote, we're in a string, and it's not escaped
                            array_pop($stk);
                            //print("Found end of string at {$c}: ".substr($chrs, $top['where'], (1 + 1 + $c - $top['where']))."\n");

                        } elseif (($chrs{$c} == '[') &&
                                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                            // found a left-bracket, and we are in an array, object, or slice
                            array_push($stk, array('what' => SERVICES_JSON_IN_ARR, 'where' => $c, 'delim' => false));
                            //print("Found start of array at {$c}\n");

                        } elseif (($chrs{$c} == ']') && ($top['what'] == SERVICES_JSON_IN_ARR)) {
                            // found a right-bracket, and we're in an array
                            array_pop($stk);
                            //print("Found end of array at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        } elseif (($chrs{$c} == '{') &&
                                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                            // found a left-brace, and we are in an array, object, or slice
                            array_push($stk, array('what' => SERVICES_JSON_IN_OBJ, 'where' => $c, 'delim' => false));
                            //print("Found start of object at {$c}\n");

                        } elseif (($chrs{$c} == '}') && ($top['what'] == SERVICES_JSON_IN_OBJ)) {
                            // found a right-brace, and we're in an object
                            array_pop($stk);
                            //print("Found end of object at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        } elseif (($substr_chrs_c_2 == '/*') &&
                                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                            // found a comment start, and we are in an array, object, or slice
                            array_push($stk, array('what' => SERVICES_JSON_IN_CMT, 'where' => $c, 'delim' => false));
                            $c++;
                            //print("Found start of comment at {$c}\n");

                        } elseif (($substr_chrs_c_2 == '*/') && ($top['what'] == SERVICES_JSON_IN_CMT)) {
                            // found a comment end, and we're in one now
                            array_pop($stk);
                            $c++;
                            
                            for ($i = $top['where']; $i <= $c; ++$i)
                                $chrs = substr_replace($chrs, ' ', $i, 1);
                            
                            //print("Found end of comment at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        }
                    
                    }
                    
                    if (reset($stk) == SERVICES_JSON_IN_ARR) {
                        return $arr;

                    } elseif (reset($stk) == SERVICES_JSON_IN_OBJ) {
                        return $obj;

                    }
                
                }
        }
    }
    
    /**
     * @todo Ultimately, this should just call PEAR::isError()
     */
    function isError($data, $code = null)
    {
        if (is_object($data) && (get_class($data) == 'MYBIC_Services_JSON_Error' ||
                                 is_subclass_of($data, 'MYBIC_Services_JSON_Error'))) {
            return true;
        }

        return false;
    }
}
/*
if (class_exists('pear_error')) {

    class MYBIC_Services_JSON_Error extends PEAR_Error
    {
        function MYBIC_Services_JSON_Error($message = 'unknown error', $code = null,
                                     $mode = null, $options = null, $userinfo = null)
        {
            parent::PEAR_Error($message, $code, $mode, $options, $userinfo);
        }
    }

} else {
*/
    /**
     * @todo Ultimately, this class shall be descended from PEAR_Error
     */
    class MYBIC_Services_JSON_Error
    {
        function MYBIC_Services_JSON_Error($message = 'unknown error', $code = null,
                                     $mode = null, $options = null, $userinfo = null)
        {
        
        }
    }

    /*
}
*/  
?>