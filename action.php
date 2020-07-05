<?php
/**  
 * Action Plugin to limit logins to selected ip addresses
 * @author  Myron Turner
 * 
 */

if (!defined('DOKU_INC')) 
{    
    die();
}

class action_plugin_abortlogin extends DokuWiki_Action_Plugin
{

    function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'dw_start');
    }
    
    function dw_start(&$event, $param)
    {
      global $ACT, $INPUT, $USERINFO;
      $ip = $_SERVER['REMOTE_ADDR'];
      if(!$this->getConf('enable_test')) {          
         return;
      }    
     if(file_exists(DOKU_PLUGIN . 'abortlogin/disabled')) {
          msg("Remove the disabled file from the plugin directory when you are finished setting up. Your current IP is $ip",2);
         return;
     }   
      if($ACT != 'login') return;      
     
      $u = $INPUT->str('u'); $p=$INPUT->str('p');  $action = $INPUT->post->str('do');
      $test = $this->getConf('test');
      $allowed = $this->getConf('allowed');
    
      if($_REQUEST['do'] =='admin' && empty($_REQUEST['http_credentials']) && empty($USERINFO)) {               
             header("HTTP/1.0 403 Forbidden");           
             exit("<div style='text-align:center; padding-top:2em;'><h1>403: Login Forbidden</h1></div>");
       }  
       
      if( !empty($u) && !empty($p) && $action != 'login'  ) {
              header("HTTP/1.0 403 Forbidden");           
              exit("<div style='text-align:center; padding-top:2em;'><h1>403: Login Forbidden</h1></div>");
      }
      
      
       if( empty($u) && empty($p) && empty($_REQUEST['http_credentials']) && !empty($USERINFO) && !$this->is_allowed($allowed, $ip)){
             unset($USERINFO) ;
             global $ACT;  $ACT = 'logout';          
      }   
      
      if($test && isset($USERINFO) && in_array('admin', $USERINFO['grps'])) {         
          $tests = explode(',',$test);
          foreach ($tests as $test) {           
              $test = trim($test);  
              if(!$this->is_allowed($allowed, $test)) {
                  msg("$test is not a valid IP");
              }    
               else  msg("$test is a valid IP",2);         
          }           
          return;          
      } 
    
      if($ACT == 'login' && !$this->is_allowed($allowed, $ip)) {
              if($this->getConf('log')) {
              $this->log($ip); 
             }              
              header("HTTP/1.0 403 Forbidden");           
              exit("<div style='text-align:center; padding-top:2em;'><h1>403: Login Not Available</h1></div>");
            
        } 
    } 
    
     function is_allowed($allowed, $ip) {
         static $cache = '';     
         
         if($cache) {
              $allowed = $cache;              
         }
         else {
         $allowed = trim($allowed,', ');       
         $allowed = preg_quote($allowed);
         $allowed=str_replace(array(' ', ','), array("",'|'),$allowed);                   
             $cache = $allowed;                   
         }
          
         if(!$allowed ) return false;  // if allowed string is empty then all ips are allowed 
         if( preg_match("/" . $allowed . "/", $ip) ) {                  
               return true;
        }
        return false;  
     }
     
     function log($ip) {         
        $log = metaFN('abortlogin:aborted_ip','.log');   
        io_saveFile($log,"$ip\n",1);
     }
}
?>
