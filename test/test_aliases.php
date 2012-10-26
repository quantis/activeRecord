<?php

    require_once dirname(__FILE__) . "/../idiorm.php";
    require_once dirname(__FILE__) . "/../eager.php";
    require_once dirname(__FILE__) . "/../granada.php";
    require_once dirname(__FILE__) . "/test_classes.php";
    
    class utilisateur extends Model{
        
        static public $_aliases_fields_map = array(
            'nom' => 'name',
            'prenom' => 'surname'
        );
        
        static public $_table = 'user';
    }
    
    ORM::configure('logging', true);
    ORM::configure('connection_string', 'mysql:dbname=test;host=localhost');
    ORM::configure('username','root');
    ORM::configure('password','');

    ORM::get_last_query();
    
    $utilisateur = new utilisateur();
    
    //echo '<pre>';var_dump($utilisateur);echo '</pre>';
    
    //$utilisateur->name = 'jean-pierre';
    //$utilisateur->save();
    
    echo ORM::get_last_query().'<br />';
    
    $utilisateur->find_one(3);
    var_dump($utilisateur);
    echo '<br />';
    
    $utilisateur->name = 'bernard';
    $utilisateur->surname = 'test';

    $utilisateur->save();
    var_dump($utilisateur->name);
    var_dump($utilisateur->surname);
    echo '<br />';    
    echo ORM::get_last_query().'<br />';  
    
?>
