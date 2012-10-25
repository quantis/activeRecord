<?php

    require_once dirname(__FILE__) . "/../idiorm.php";
    require_once dirname(__FILE__) . "/../eager.php";
    require_once dirname(__FILE__) . "/../granada.php";
    require_once dirname(__FILE__) . "/test_classes.php";
    
    class utilisateur extends ModelMapper{
        
        static public $_aliases_fields_map = array(
            'name' => 'nom',
        );
        
        static public $_table = 'user';
    }
    
    ORM::configure('logging', true);

    // Set up the dummy database connection
    $db = new DummyPDO('sqlite::memory:');
    ORM::set_db($db);
    
    ORM::get_last_query();
    
    $utilisateur = new utilisateur();
    
    //echo '<pre>';var_dump($utilisateur);echo '</pre>';
    
    $utilisateur->nom = 'jean-pierre';
    $utilisateur->save();
    
    echo ORM::get_last_query().'<br />';
    
    $utilisateur->find_one(1);
    var_dump($utilisateur->nom);
    echo '<br />';
    
    $utilisateur->nom = 'bernard';
    $utilisateur->save();
    
    echo ORM::get_last_query().'<br />';  
    
?>
