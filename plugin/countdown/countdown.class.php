<?php

/** 
* Server related timer functions
*/
class WebCountdown {
    /**
    * formats a PHP date in javascript format
    * @param {int} date timestamp. Ex: date() 
    * return {array|boolean} array with time keys
    */
    static function buildDate($date) {
       if (empty($date)) return json_encode(Array('year'=>date('Y'),'month'=>(date('n')-1),'day'=>date('d'), 'hour'=>date('G'), 'min'=>date('i'), 'sec'=>date('s') ));
       elseif (is_int($date)) return json_encode(Array('year'=>date('Y',$date),'month'=>(date('n',$date)-1),'day'=>date('d',$date), 'hour'=>date('G',$date), 'min'=>date('i',$date), 'sec'=>date('s',$date) ));
       elseif (is_string($date)) return json_encode(Array('year'=>substr($date,0,4), 'month'=>(int)substr($date,5,2)-1, 'day'=>substr($date,8,2),
	  'hour'=>substr($date,11,2), 'min'=>substr($date,14,2), 'sec'=>substr($date,17,2)));
       else return false;	  
    }
}

/* from PHP manual */
if (!function_exists('json_encode'))
{
  function json_encode($a=false)
  {
    if (is_null($a)) return 'null';
    if ($a === false) return 'false';
    if ($a === true) return 'true';
    if (is_scalar($a))
    {
      if (is_float($a))
      {
        // Always use "." for floats.
        return floatval(str_replace(",", ".", strval($a)));
      }

      if (is_string($a))
      {
        static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
        return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
      }
      else
        return $a;
    }
    $isList = true;
    for ($i = 0, reset($a); $i < count($a); $i++, next($a))
    {
      if (key($a) !== $i)
      {
        $isList = false;
        break;
      }
    }
    $result = array();
    if ($isList)
    {
      foreach ($a as $v) $result[] = json_encode($v);
      return '[' . join(',', $result) . ']';
    }
    else
    {
      foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
      return '{' . join(',', $result) . '}';
    }
  }
}

?>