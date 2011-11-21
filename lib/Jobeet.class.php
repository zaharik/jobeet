<?php

class Jobeet{
  static public function slugify($text){
    // replace all non letters or digits by -
    $text = preg_replace('/\W+/','-',$text);
    
    // trim and lowercase
    return $text = strtolower(trim($text));
  }
}

?>
