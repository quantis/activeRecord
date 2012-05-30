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
               return $this->has_many('post');
         }
         public function post(){
               return $this->has_one('avatar');
         }
   }
</code></pre>

you can include relationships inside your relationships !
<pre><code>
     $user_list = Model::factory('User')->with(array('post'=>array('with'=>'comments')),'avatar')->find_many();
</code></pre>
it will make 3 querys:
SELECT * FROM user 
SELECT * FROM avatar WHERE user.id IN (......)
SELECT * FROM post WHERE user.id IN (.....)

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



