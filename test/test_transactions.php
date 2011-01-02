<?php
    /*
     * Transaction testing for Dakota.
     */

    require_once dirname(__FILE__) . "/idiorm.php";
    require_once dirname(__FILE__) . "/../dakota.php";
    require_once dirname(__FILE__) . "/test_classes.php";

    // Enable logging
    ORM::configure('logging', true);

    // Use SQLite in-memory database
    ORM::configure('sqlite::memory:');

    ORM::get_db()->exec('CREATE TABLE user(id INTEGER PRIMARY KEY ASC, name TEXT, age INT)');
    
    class User extends Model {
    }
    
    $user = new User;
    $user->name = "Ted";
    $user->age = 22;
    $user->save();
    
    // Quick sanity check for inserting rows without a transaction
    Tester::check_equal('Insert ID for first insert', (int) $user->id, 1);
    $count = Model::factory('User')->count();
    Tester::check_equal('Row count after first insert', $count, 1);
    
    // Test rolling back a transaction
    Model::start_transaction();
    $user = new User;
    $user->name = "Frank";
    $user->age = 15;
    $user->save();
    Tester::check_equal('Insert ID for insert to rollback', (int) $user->id, 2);
    Model::rollback();
    $count = Model::factory('User')->count();
    Tester::check_equal('Row count after rollback', $count, 1);
    
    // Test committing a transaction
    Model::start_transaction();
    $user = new User;
    $user->name = "Frank";
    $user->age = 15;
    $user->save();
    Tester::check_equal('Insert ID for insert to commit', (int) $user->id, 2);
    Model::commit();
    $count = Model::factory('User')->count();
    Tester::check_equal('Row count after commit', $count, 2);
    
    Tester::report();
?>
