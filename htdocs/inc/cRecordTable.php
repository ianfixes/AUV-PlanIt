<?php

//to print a nice freeNAS style HTML table based on a DB table
abstract class cRecordTable
{

    public function __construct($recordtype = "record")
    {
        $this->datacache = NULL;
        $this->recordtype = $recordtype;
        $this->init();
    }

    //do any class-specific init
    protected function init() {return;}

    //fill the datacache with records
    abstract protected function cacheData();

    //column names array: name => width
    abstract protected function columns();

    //whether to darken a column
    protected function darken($name) { return false; }
   
    //links to various actions
    protected function linkAdd()           { return ""; }
    protected function linkDelete($record) { return ""; }
    protected function linkEdit($record)   { return ""; }
    protected function linkExport($record) { return ""; }

    //can we perform various actions?
    protected function canDelete($record) { return false; }
    protected function canEdit($record)   { return false; }
    protected function canExport($record) { return false; }

    public static function DBO()
    {
        global $dbo;
        return $dbo;
    }
        
    public static function DB()
    {
        global $db;
        return $db;
    }
        

    public function Render()
    {
        $ret = "";


        if (NULL == $this->datacache)
        {
            $this->cacheData();
        }

        //build table and header
        $ret .= "
           <table width='100%' border='0' cellpadding='0' cellspacing='0'>
            <tr>";

        //iterate column headings
        foreach ($this->columns() as $label => $width)
        {
            $cssclass = $this->darken($label) ? "listhdr" : "listhdrr"; 
            $ret .= "
             <td width='$width' class='$cssclass'>$label</td>";
        }
       
        //put the "add" button if needed
        if ("" == $this->linkAdd())
        {
            $ret .= "
             <td width='5%' class='list'>&nbsp;<!-- linkAdd not set --></td>";
        }
        else
        {
            $ret .= "
             <td width='5%' class='list'><a href='{$this->linkAdd()}'><img 
              src='/img/plus.gif' title='add {$this->recordtype}' 
              width='17' height='17' border='0'></a>
              &nbsp;</td>";
        }
        $ret .= "
            </tr>";
   
        //iterate over records 
        foreach ($this->datacache as $d)
        {
            $ret .= "
            <tr valign='top'>";

            //late-breaking hack to insert a custom row
            if (isset($d["__wholerow__"]))
            {
                $cspn = count($this->columns());
                $ret .= "<td class='listlr' colspan='$cspn'>{$d["__wholerow__"]}</td></tr>";
                continue;
            }

            $first = true;
            foreach ($this->columns() as $label => $width)
            {
                $cssclass = $first ? "listlr" : 
                            ($this->darken($label) ? "listbg" : "listr");
    
                $ret .= "
             <td class='$cssclass'>{$d[$label]}</td>";
                $first = false;
            }

            $ret .= "
             <td valign='middle' class='list' style='white-space:nowrap;'>";

            if ($this->canEdit($d))
            {
                $ret .= $this->actionButton($this->linkEdit($d), "/img/e.gif", "edit", false);
            }
            if ($this->canDelete($d))
            {
                $ret .= $this->actionButton($this->linkDelete($d), "/img/x.gif", "delete", true);
            }
            if ($this->canExport($d))
            {
                $ret .= $this->actionButton($this->linkExport($d), "/img/arrow.gif", "export", false);
            }

            $ret .= "
             &nbsp;
             </td>
            </tr>";
        }
        $ret .= "
           </table>
        ";
        /* 
        $ret .= "
           <br>
           <span class='vexpl'>
            <span class='red'><strong>Note:<br></strong></span>
             " . //print_r($this->datacache, true) . 
             "
           </span>
    
            ";
        */
        return $ret;
    }

    protected function actionButton($link, $img, $verb, $confirm)
    {
        $conf = "";
        $thing = $this->recordtype;
      
        if ($confirm)
        {
            $conf = "onclick=\"return confirm('Do you really want to $verb this $thing?')\"";
        }
        return " 
              <a href='$link' $conf><img src='$img' title='$verb $thing' class='circlebutton'></a>";

    }

}
    
?>
