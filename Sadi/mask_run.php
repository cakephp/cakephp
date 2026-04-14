<?php
   require '/Users/mallikimtiaz.hassan/Desktop/Sadi/cakephp/vendor/autoload.php';
                                                                 
   use Cake\Utility\Text;                               
                          
   var_dump(Text::mask('データベースアクセス & ORM', 0, 5));
   var_dump(Text::mask('データベースアクセス & ORM', -999, 5, '*'));
   var_dump(Text::mask('4111111111111234', 0, 12, '*'));
?>