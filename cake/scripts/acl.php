#!/usr/bin/php -q
<?php
//BLAH
ini_set('display_errors', '1');
ini_set('error_reporting', '7');

define ('DS', DIRECTORY_SEPARATOR);
define ('ROOT', dirname(dirname(dirname(__FILE__))).DS);
define ('APP_DIR', 'app');

require_once (ROOT.'cake'.DS.'config'.DS.'paths.php');
require_once (CAKE.'basics.php');
require_once (CONFIGS.'core.php');
require_once (CONFIGS.'database.php');

uses ('neat_array');
uses ('model'.DS.'dbo'.DS.'dbo_factory');
uses ('controller'.DS.'controller');
uses ('controller'.DS.'components'.DS.'acl');
uses ('controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'aclnode');
uses ('controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'aco');
uses ('controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'acoaction');
uses ('controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'aro');

//Get and format args: first arg is the name of the script.
$wasted = array_shift($_SERVER['argv']);
$command = array_shift($_SERVER['argv']);
$args = $_SERVER['argv'];

$aclCLI = new AclCLI ($command, $args);

class AclCLI {
   
   var $stdin;
   var $stdout;
   var $stderr;
   
   var $acl;
   var $controller;
   
   var $args;
   
   function AclCLI($command, $args) 
   {
      $this->__construct($command, $args);
   }
   
   function __construct ($command, $args) 
   {
      $acl = new AclComponent();
      $this->acl = $acl->getACL();  
      
      $this->args = $args;
      
      $this->controller =& new Controller();
      $this->controller->constructClasses();

      $this->stdin = fopen('php://stdin', 'r');
      $this->stdout = fopen('php://stdout', 'w');
      $this->stderr = fopen('php://stderr', 'w');
      
      //Check to see if DB ACL is enabled
      if (ACL_CLASSNAME != 'DB_ACL') 
      {
         $out = "--------------------------------------------------\n";
         $out .= "Error: Your current Cake configuration is set to \n";
         $out .= "an ACL implementation other than DB. Please change \n";
         $out .= "your core config to reflect your decision to use \n";
         $out .= "DB_ACL before attempting to use this script.\n";
         $out .= "--------------------------------------------------\n";
         $out .= "Current ACL Classname: " . ACL_CLASSNAME . "\n";
         $out .= "--------------------------------------------------\n";
         
         fwrite($this->stderr, $out);
         exit();
      }

      switch ($command) 
      {
         case 'create':
         $this->create();
            break;            
         case 'delete':
         $this->delete();
            break;
         case 'setParent':
         $this->setParent();
            break;
         case 'getPath':
         $this->getPath();
            break;
         case 'grant':
         $this->grant();
            break;
         case 'deny':
         $this->deny();
            break;
         case 'inherit':
         $this->inherit();
            break;
         case 'view':
            $this->view();
            break;
         case 'help':
            $this->help();
            break;
         default:
            fwrite($this->stderr, "Unknown ACL command '$command'.\nFor usage, try 'php acl.php help'.\n\n");
      }
            
   }

   function create() 
   {
      $this->checkArgNumber(4, 'create');
      $this->checkNodeType();
      extract($this->__dataVars());
      $node = &new $class;
      $parent = $this->nodeExists($this->args[0], $this->args[2]);
      
      if (!$parent)
      {
         fwrite($this->stdout, "Warning: Parent not found. Creating this object with the root node as parent.\n");
         $parent = $node->find(null, "MAX(rght)");
      }
      
      $node->create($this->args[1], $parent[$data_name]['id'], $this->args[3]);
      
      fwrite($this->stdout, "New $class '".$this->args[3]."' created.\n\n");
   }
   
   function delete() 
   {
      $this->checkArgNumber(2, 'delete');
      $this->checkNodeType();
      extract($this->__dataVars());
      $node = &new $class;
      
      //What about children?
      //$node->del($this->args[1]);
      //fwrite($this->stdout, "$class deleted.\n\n");
   }
   
   function setParent() 
   {  
      $this->checkArgNumber(3, 'setParent');
      $this->checkNodeType();
      extract($this->__dataVars());
      $node = &new $class;
      $parent = $this->nodeExists($this->args[0], $this->args[2]);
      
      if (!$parent)
      {
         fwrite($this->stdout, "Warning: Parent not found. Setting this object with the root node as parent.\n");
         $parent = $node->find(null, "MAX(rght)");
      }
      
      $node = &new $class;
      
      $node->setParent($parent[$data_name]['id'], $this->args[1]);
      fwrite($this->stdout, "Node parent set to ".$this->args[2]."\n\n");
   }
   
   function getPath() 
   {
      $this->checkArgNumber(2, 'getPath');
      $this->checkNodeType();
      extract($this->__dataVars());
      
      $suppliedNode = $this->nodeExists($this->args[0], $this->args[1]);
      
      if (!$suppliedNode)
      {
         $this->displayError("Supplied Node '".$args[1]."' not found. No tree returned.");
      }
      
      $node = &new $class;
      
      fwrite($this->stdout, print_r($node->getPath($this->args[1]))); 
   }
   
   function grant() 
   {
      $this->checkArgNumber(3, 'grant');
      //add existence checks for nodes involved
      
      
      $this->acl->allow($this->args[0], $this->args[1], $this->args[2]);
      fwrite($this->stdout, "Permission granted...");
   }
   
   function deny() 
   {
      $this->checkArgNumber(3, 'deny');
      //add existence checks for nodes involved
      
      $this->acl->deny($this->args[0], $this->args[1], $this->args[2]);
      fwrite($this->stdout, "Permission denied...");
   }
   
   function inherit() {}
   
   function view() {}
   
   function help() 
   {
      $out = "Usage: php acl.php <command> <arg1> <arg2>...\n"; 
      $out .= "-----------------------------------------------\n";
      $out .= "Commands:\n";
      $out .= "\n";
      
      $out .= "\tcreate aro|aco <link_id> <parent_id> <alias>\n";
      $out .= "\t\tCreates a new ACL object under the parent specified by parent_id (see\n";
      $out .= "\t\t'view'). The link_id allows you to link a current user object to Cake's\n";
      $out .= "\t\tACL structures. The alias parameter allows you address your object\n";
      $out .= "\t\tusing a non-integer ID. Example: \"\$php acl.php create aro 0 jda57 John\"\n";
      $out .= "\t\twould create a new ARO object at the root of the tree, linked to jda57\n";
      $out .= "\t\tin your users table, with an internal alias 'John'.";
      $out .= "\n";
      $out .= "\n";
      
      $out .= "\tdelete aro|aco <id>\n";
      $out .= "\t\tDeletes the ACL object with the specified ID (see 'view').\n";
      $out .= "\n";
      $out .= "\n";
      
      $out .= "\tsetParent aro|aco <id> <parent_id>\n";
      $out .= "\t\tUsed to set the parent of the ACL object specified by <id> to the ID\n";
      $out .= "\t\tspecified by <parent_id>.\n";
      $out .= "\n";
      $out .= "\n";
      
      $out .= "\tgetPath aro|aco <id>\n";
      $out .= "\t\tReturns the path to the ACL object specified by <id>. This command is\n";
      $out .= "\t\tis useful in determining the inhertiance of permissions for a certain\n";
      $out .= "\t\tobject in the tree.\n";
      $out .= "\n";
      $out .= "\n";
      
      $out .= "\tgrant <aro_id> <aco_id> <aco_action>\n";
      $out .= "\t\tUse this command to grant ACL permissions. Once executed, the ARO\n";
      $out .= "\t\tspecified (and its children, if any) will have ALLOW access to the\n";
      $out .= "\t\tspecified ACO action (and the ACO's children, if any).\n";
      $out .= "\n";
      $out .= "\n";
      
      $out .= "\tdeny <aro_id> <aco_id> <aco_action>\n";
      $out .= "\t\tUse this command to deny ACL permissions. Once executed, the ARO\n";
      $out .= "\t\tspecified (and its children, if any) will have DENY access to the\n";
      $out .= "\t\tspecified ACO action (and the ACO's children, if any).\n";
      $out .= "\n";
      $out .= "\n";
      
      $out .= "\tinherit <aro_id> \n";
      $out .= "\t\tUse this command to force a child ARO object to inherit its\n";
      $out .= "\t\tpermissions settings from its parent.\n";
      $out .= "\n";
      $out .= "\n";
      
      $out .= "\tview aro|aco [id]\n";
      $out .= "\t\tThe view command will return the ARO or ACO tree. The optional\n";
      $out .= "\t\tid/alias parameter allows you to return only a portion of the requested\n";
      $out .= "\t\ttree.\n";
      $out .= "\n";
      $out .= "\n";
      
      $out .= "\thelp\n";
      $out .= "\t\tDisplays this help message.\n";
      $out .= "\n";
      $out .= "\n";
      
      fwrite($this->stdout, $out);
   }
   
   function displayError($title, $msg)
   {
      $out = "\n";
      $out .= "Error: $title\n";
      $out .= "$msg\n";
      $out .= "\n";
      fwrite($this->stdout, $out);
      exit();
   }
   
   function checkArgNumber($expectedNum, $command)
   {
      if (count($this->args) != $expectedNum)
      {
         $this->displayError('Wrong number of parameters: '.count($this->args), 'Please type \'php acl.php help\' for help on usage of the '.$command.' command.');
      }
   }
   
   function checkNodeType()
   {
      if ($this->args[0] != 'aco' && $this->args[0] != 'aro')
      {
         $this->displayError("Missing/Unknown node type: '".$this->args[0]."'", 'Please specify which ACL object type you wish to create.');
      }
   }
   
   function nodeExists($type, $id)
   {
      fwrite($this->stdout, "Check to see if $type with ID = $id exists...\n");
      extract($this->__dataVars($type));
      $node = &new $class;
      
      $possibility = $node->find('id = ' . $id);
      
      if (empty($possibility[$data_name]['id']))
      {
         return false;
      }
      else 
      {
         return $possibility;
      }
   }
   
   function __dataVars($type = null)
   {
      if ($type == null)
      {
         $type = $this->args[0];
      }
      
      $vars = array();
      $class = ucwords($type);
      $vars['secondary_id'] = ($class == 'aro' ? 'user_id' : 'object_id');
      $vars['data_name']    = $type;
      $vars['table_name']   = $class . 's';
      $vars['class']        = $class;
      return $vars;
   }
}
?>
