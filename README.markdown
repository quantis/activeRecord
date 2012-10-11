Granada
======

*A mashed idiorm-dakota-eloquent library

A lightweight (with eager loading) Active Record implementation for PHP5.3
Built on top of [Idiorm](http://github.com/j4mie/idiorm/).
Forked from [Dakota](http://github.com/powerpak/dakota/).
Using code from [eloquent](https://github.com/taylorotwell/eloquent/).

Tested on PHP 5.3.0+ - does not work on earlier versions.

Released under a [BSD license](http://en.wikipedia.org/wiki/BSD_licenses).


You can use query builder this way:

<pre><code>$user_list = Model::factory('User')->with('avatar')->find_many();
foreach($user_list as $key=>$user){
     echo $user->name;
     echo $user->avatar->url;
}</code></pre>

It will load all the Users, with its avatars in just 2 queries.


You could load now multiple dependencies:
<pre><code>
     $user_list = Model::factory('User')->with('post','avatar')->find_many();
</code></pre>
will give you all the Users, each one with its avatar and it post. Of course you need to define the relationships in the users Models, as before, using the Idiorm-Paris way:
<pre><code>
   class User extends Model {
         public static $_table = 'user';
          
         public function post(){
               return $this->has_many('Post');
         }
         public function avatar(){
               return $this->has_one('Avatar');
         }
   }
</code></pre>

you can include relationships inside your relationships !
<pre><code>
     $user_list = Model::factory('User')->with(array('post'=>array('with'=>'comments')),'avatar')->find_many();
</code></pre>


it will make 3 querys:
<pre><code>
SELECT * FROM user 
SELECT * FROM avatar WHERE user.id IN (......)
SELECT * FROM post WHERE user.id IN (.....)
</code></pre>

you can use args in your relationships too!
<pre><code>
        // on the user model
        .....
         public function post($post_id = false){
               if($post_id) {
                    return $this->has_one('post')->where('id',$post_id);
               }
               else {
                    return $this->has_many('post');
               }
         }
        .....      

     // and use it this way (the 'with' argument is a associative key, so is reserved, any other is used as arg for the relationship)
     $user_list = Model::factory('User')->with(array('post'=>array('arg1','with'=>'comments')),'avatar')->find_many();
</code></pre>

You can chain relationships ad infinutum:
<pre><code>
     $user_list = Model::factory('User')->with(
              array('post'=>array('arg1',
                      'with'=>array('comments'=>array('with'=>'votes'))
               )),
               'avatar')
               ->find_many();
</code></pre>
all them will use the minimun querys needed.





At this moment primary key is set to "pk" and foreign one are "_fk" it could be changed on 
<pre><code>
class Model extends ORMWrapper {
    
    // Default ID column for all models. Can be overridden by adding
    // a public static _id_column property to your model classes.
    const DEFAULT_ID_COLUMN = 'pk';

    // Default foreign key suffix used by relationship methods
    const DEFAULT_FOREIGN_KEY_SUFFIX = '_fk';
    
    ....
</code></pre>


Some changes are made to the idiorm and dakota implementation, some of them are:


The unique not trasparent one is "select_expr". I changed it to "select_raw" for clarity. I added a "group_by_raw" too.

"where" method will accept now an array of conditions. example: ->where(array('field'=>$condition1, 'field2'=>$condition2)
Still could be called the usual idiorm-paris way.

The "set" method now accepts associative arrays.

"insert" method will save an array of rows using transactions. example: ->insert(array(array1(), array2(), array3()....))
"insert_multiple" will expect variable number of arrays. example: ->insert(array1(), array2(), array3()....)

I added a "SHOW profiles" loggin with the "getLog" method. Will return a table with query and time taken foreach along a first row with the total number of queries and total time. The usual log system for idiorm is still working, since i added this one like a new one. Maybe I must set a new config option to select the loggin feature. Since Idiorm makes its own logging + mine, while loggin is active performance will suffer a bit.

The "save" method now accept a boolean parameter. It's used to "INSERT ON DUPLICATE KEY UPDATE" so, if you use the "save(true)" (false by default). It will try to insert the data and if a duplicated key is present in the insert it will update the database record instead create a new one.

The "delete_many" method accepts a not executed query and performs a delete of the results. I still don't like a lot this method design, but is usefull. It could be used like: Model::factory('model_name')->where_in('field',$condition)->delete_many(); Will delete all results of the query.

