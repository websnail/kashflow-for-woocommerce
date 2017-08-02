<?php
/*  Copyright 2013  Devicesoftware  (email : support@devicesoftware.com) 

    Author: swicks@devicesoftware.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA    
*/

class KF_Base{    
    
    protected $wrapper = '';
    public $data = array();
    protected $data_fields = array();
        
    public function __set($name, $value)
    {
        if(in_array( $name, $this->data_fields))
        {
            $this->data[$name] = htmlspecialchars($value);
            return true;
        }
        return false;
            
    }
    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
        return null;
    }  
    function __toString()
    {
        $xml = '';
        foreach($this->data as $key => $value)
        {
            $xml.= '<' . $key . '>' . htmlspecialchars($value) . '</' . $key . '>';
        }
        
        if(!empty($this->wrapper))
            $xml = '<' . $this->wrapper . '>' . $xml . '</' . $this->wrapper . '>';
        
        return $xml;
    }
    
    public function populate($data_array = array())
    {
        foreach($data_array as $key => $value)
        {
            $this->$key = $value;
        }
    }
    
}

?>
