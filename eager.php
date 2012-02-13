<?php

class Eager
{
	/**
	 * Look for relations   ( if we called with, look for that methods on this model instance) if ( ! method_exists($model, $include))
	 * foreach method "included" call a "eagerly" function wich
	 *    
	 *     we have the results, hence we have the array keys from the resultset
     *     we ask each relationship "included" against that resultset keys             
	 *           now we will have a looong array without any order, but we must assign it to the key it references 
	 *           so we must ask to the references to return the results grouped by the key it references
     *     we have an array with keys (parent's keys) and it's related references, assign it to a ['reference model table name'] key (and add that key to the ignored for dirty)
	 *     done
	 */ 

	public static function hydrate($model, &$results)
	{
		if (count($results) > 0)
		{
    		foreach ($model->includes as $include)
    		{
    			if ( ! method_exists($model, $include))
    			{
    				throw new \LogicException("Attempting to eager load [$include], but the relationship is not defined.");
    			}
    
    			static::eagerly($model, $results, $include);
    		}
        } 
        
        return $results;
	}
    
	/**
	 * Eagerly load a relationship.
	 *
	 * @param  object  $eloquent
	 * @param  array   $parents
	 * @param  string  $include
	 * @return void 
	 */
	private static function eagerly($model, &$parents, $include)
	{
		$relationship = $model->$include()->reset_relation();

		// Initialize the relationship attribute on the parents. As expected, "many" relationships
		// are initialized to an array and "one" relationships are initialized to null.
		foreach ($parents as &$parent)
		{
			$parent->ignore[$include] = (in_array($model->relating, array('has_many', 'has_and_belongs_to_many'))) ? array() : null;
		}

		if (in_array($relating = $model->relating, array('has_one', 'has_many', 'belongs_to')))
		{
			return static::$relating($relationship, $parents, $model->relating_key, $include);			
		}
		else
		{
			static::has_and_belongs_to_many($relationship, $parents, $model->relating_key, $model->relating_table, $include);
		}
       
	}
    
	/**
	 * Eagerly load a 1:1 relationship.
	 *
	 * @param  object  $relationship
	 * @param  array   $parents
	 * @param  string  $relating_key
	 * @param  string  $relating
	 * @param  string  $include
	 * @return void
	 */
	private static function has_one($relationship, &$parents, $relating_key, $include)
	{
		$parents[$child->$relating_key]->ignore[$include] = $relationship->where_in($relating_key, array_keys($parents))->find_one()->loaded();
	}
     

	/**
	 * Eagerly load a 1:* relationship.
	 *
	 * @param  object  $relationship
	 * @param  array   $parents
	 * @param  string  $relating_key
	 * @param  string  $relating
	 * @param  string  $include
	 * @return void
	 */
	private static function has_many($relationship, &$parents, $relating_key, $include)
	{
	    $related = $relationship->where_in($relating_key, array_keys($parents))->find_many();
		foreach ($related as $key => $child)
		{  
			$parents[$child->$relating_key]->ignore[$include][$child->id()] = $child;
		}
	} 


    // TODO;;
	/**
	 * Eagerly load a 1:1 belonging relationship.
	 *
	 * @param  object  $relationship
	 * @param  array   $parents
	 * @param  string  $relating_key
	 * @param  string  $include
	 * @return void
	 */
	private static function belongs_to($relationship, &$parents, $relating_key, $include)
	{
		$related = $relationship->where_id_in(array_keys($parents))->find_many();

		foreach ($parents as &$parent)
		{
			if (array_key_exists($parent->$relating_key, $related))
			{
				$parent->ignore[$include] = $related[$parent->$relating_key];
			}
		}
	}

	/**
	 * Eagerly load a many-to-many relationship.
	 *
	 * @param  object  $relationship
	 * @param  array   $parents
	 * @param  string  $relating_key
	 * @param  string  $relating_table
	 * @param  string  $include
	 *
	 * @return void	
	 */
	private static function has_and_belongs_to_many($relationship, &$parents, $relating_key, $relating_table, $include)
	{
		$children = $relationship->select($relating_table.".".$relating_key[0])->where_in($relating_table.'.'.$relating_key[0], array_keys($parents))->find_many();

		// The foreign key is added to the select to allow us to easily match the models back to their parents.
		// Otherwise, there would be no apparent connection between the models to allow us to match them.

		foreach ($children as $child)
		{ 
			$parents[$child->$relating_key[0]]->ignore[$include][$child->id()] = $child;
		}
	}    
}