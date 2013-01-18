<?php


$validators = array(
  'email' => function($str){
    if(!filter_var($str, FILTER_VALIDATE_EMAIL))
      return "invalid email address";
  }
  , 'username' => function($str){
    if( preg_match('/^[0-9A-Za-z_]+$/', $str ) === 0)
      return "invalid username";
  }
  , 'fullname' => function($str){}
  , 'twitter' => function($str){
    if( $str !== '' && preg_match('/^@*([A-Za-z0-9_]+)/', $str) === 0)
      return "invalid twitter username";
  }
  , 'url' => function($str){
    if( preg_match("#((http|https|ftp)://(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|\<|$|\.\s)#ie", $str) === 0)
      return "invalid url";
  }
  , 'homepage' => function($str){
    // homepage can be empty
    if($str !== '') return $validators['url']($str);
  }
);


$validate = function($fields) use($validators){
  return array_filter(array_map(function($field) use($validators){
    foreach( $field as $key => $val ){
      echo "key: $key, val: $val\n<br/>";
      return $validators[$val]($key);
    }
  },$fields));
};

?>