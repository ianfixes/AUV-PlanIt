<?php

require_once("mybic_json.php");
require_once("utils/cArrayObject.php");
require_once("utils/cCleanArray.php");

//handle AJAX requests (and appropriate setup) in a hopefully modular way
abstract class cRequest
{
    var $queryVars;

    public function __construct($queryVars = array())
    {
        $this->queryVars  = new cArrayObject($queryVars);
        $this->queryClean = new cCleanArray($queryVars);
    }

    /**
     * Method to return the status of the AJAX transaction
     *
     * @return  string A string of raw HTML fetched from the Server
     */
    abstract public function return_response();
    
    //true by default
    public function is_authorized()
    {
        return true;
    }

    //return array of subactions
    abstract public function ajax_subactions();

    //return array of ajax_subaction => array(arg1, arg2, arg3)
    abstract public function subaction_arglists();

    //look up args for a sub action
    public function subaction_args($subaction)
    {
        $al = $this->subaction_arglists();
        return $al[$subaction];
    }

    //return the proper AJAX call for a subaction
    public function subaction_call($act, $args = array())
    {
        $cl = get_class($this);
        return "javascript:fetch_$cl$act(" . implode(",", $args) . ")";
    }

    public function default_subaction() { return NULL; }

    public function default_subaction_args() { return array(); }

    //an overly simplistic model where there is one content area which we write to
    public function header_javascript($content_id, $form_id = NULL, $debug = false)
    {
        $ret = "
        <script type='text/javascript'>
            var ajaxObj;
        ";

        $dbg = $debug ? "ajaxObj.debug = 1;" : "";
        $cls = get_class($this);
        $frm = "";
        $fv  = "";

        foreach ($this->ajax_subactions() as $a)
        {   
            $arg_arr = array();
            $GET_arg = "";
            foreach ($this->subaction_args($a) as $arg)
            {
                $arg_arr[] = "{$arg}_val";
                $GET_arg .= "&{$arg}='+{$arg}_val+'";
            }
            $arglist = implode($arg_arr, ",");

            if (NULL != $form_id)
            {
               $fv = "+formVars";
               $frm = "
                if (null != document.getElementById('$form_id')) {
                    var formVars = ajaxObj.getForm('$form_id');
                    ajaxObj.call('action=$cls&subaction=$a$GET_arg'$fv, render_$cls$a);
                }
                else";
            }


            $ret .= "
            function fetch_$cls$a($arglist) {
                ajaxObj = new XMLHTTP('mybic_server.php');
                $dbg
                $frm
                    ajaxObj.call('action=$cls&subaction=$a$GET_arg', render_$cls$a);
            }

            function render_$cls$a(resp) {
                document.getElementById('$content_id').innerHTML = resp;
            }
            ";
        }
       $ret .= "
       </script>
       ";

       return $ret;
    }

    public function onload_javascript($full = true)
    {
        if (NULL == $this->default_subaction()) return "";
        $cl = get_class($this);

        $meat = "fetch_$cl{$this->default_subaction()}(" 
                    . implode($this->default_subaction_args(), ",")
                    . "); ";

        return $full ? " onload='$meat' " : $meat;
    }
}

?>
