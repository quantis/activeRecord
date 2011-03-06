<?php

   /**
    *
    * Dakota 
    *
    * http://github.com/powerpak/dakota/
    *
    * A simple Active Record implementation built on top of Idiorm
    * ( http://github.com/j4mie/idiorm/ ).
    *
    * You should include Idiorm before you include this file:
    * require_once 'your/path/to/idiorm.php';
    *
    * BSD Licensed.
    *
    * Copyright (c) 2010, powerpak
    * Modified from Idiorm by Jamie Matthews
    * All rights reserved.
    *
    * Redistribution and use in source and binary forms, with or without
    * modification, are permitted provided that the following conditions are met:
    *
    * * Redistributions of source code must retain the above copyright notice, this
    * list of conditions and the following disclaimer.
    *
    * * Redistributions in binary form must reproduce the above copyright notice,
    * this list of conditions and the following disclaimer in the documentation
    * and/or other materials provided with the distribution.
    *
    * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
    * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
    * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
    * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE
    * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
    * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
    * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
    * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
    * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
    * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
    *
    */

    /**
     * Subclass of Idiorm's ORM class that supports
     * returning instances of a specified class rather
     * than raw instances of the ORM class.
     *
     * You shouldn't need to interact with this class
     * directly. It is used internally by the Model base
     * class.
     */
    class ORMWrapper extends ORM {

        /**
         * The wrapped find_one and find_many classes will
         * return an instance or instances of this class.
         */
        protected $_class_name;

        /**
         * Constructs a wrapper for a table.  Called by Model instances.
         */
        public function __construct($table_name) {
          self::_setup_db();
          parent::__construct($table_name);
          
          // Until find_one or find_many are called, this object is considered a new row
          $this->create();
        }

        /**
         * Set the name of the class which the wrapped
         * methods should return instances of.
         */
        public function set_class_name($class_name) {
            $this->_class_name = $class_name;
        }
        
        /**
         * Start a transaction on the database (if supported)
         */
        public static function start_transaction() {
          self::$_db->beginTransaction();
        }
        
        /**
         * Commits a transaction on the database (if supported)
         */
        public static function commit() {
          self::$_db->commit();
        }
        
        /**
         * Rolls back a transaction on the database (if supported)
         */
        public static function rollback() {
          self::$_db->rollBack();
        }

        /**
         * Rewrite Idiorm's for_table factory so it returns models of the
         * actual $_class_name
         */
        private function _for_table($table_name) {
            self::_setup_db();
            return new $this->_class_name();
        }

        /**
         * Mostly for convenience in chaining.
         */
        public function not_new() {
            $this->_is_new = FALSE; 
            return $this;
        }

        /**
         * Override Idiorm's find_one method to return
         * the current instance hydrated with the result,
         * or just the current instance if there was no result.
         */
        public function find_one($id=null) {
            if(!is_null($id)) {
                $this->where_id_is($id);
            }
            $this->limit(1);
            $rows = $this->_run();
            return $rows ? $this->hydrate($rows[0])->not_new() : $this;
        }

        /**
         * Override Idiorm's find_many method to return
         * an array of many instances of the current instance's
         * class.
         */
        public function find_many() {
            $rows = $this->_run();
            $instances = array();
            foreach ($rows as $row) {
                $instances[] = $this->_for_table($this->_table_name)
                  ->use_id_column($this->_instance_id_column)
                  ->hydrate($row)
                  ->not_new();
            }
            return $instances;
        }
        
        /**
         * Did we load any rows from the last query?
         * This is because we no longer return false from find_one();
         * the object is always representative of a potential database row.
         */
        public function loaded() {
            return !$this->_is_new;
        }
        
    }

    /**
     * Model base class. Your model objects should extend
     * this class. A minimal subclass would look like:
     *
     * class Widget extends Model {
     * }
     *
     */
    class Model extends ORMWrapper {

        // Default ID column for all models. Can be overridden by adding
        // a public static _id_column property to your model classes.
        const DEFAULT_ID_COLUMN = 'id';

        // Default foreign key suffix used by relationship methods
        const DEFAULT_FOREIGN_KEY_SUFFIX = '_id';
        
        /**
         * Magic function so that new operator works as expected
         */
        public function __construct() {
            $class_name = get_class($this);
            $table_name = self::_get_table_name($class_name);
            parent::__construct($table_name);
            $this->set_class_name($class_name);
            $this->use_id_column(self::_id_column_name($class_name));
        }

        /**
         * Retrieve the value of a static property on a class. If the
         * class or the property does not exist, returns the default
         * value supplied as the third argument (which defaults to null).
         */
        protected static function _get_static_property($class_name, $property, $default=null) {
            if (!class_exists($class_name) || !property_exists($class_name, $property)) {
                return $default;
            }
            $properties = get_class_vars($class_name);
            return $properties[$property];
        }

        /**
         * Static method to get a table name given a class name.
         * If the supplied class has a public static property
         * named $_table, the value of this property will be
         * returned. If not, the class name will be converted using
         * the _class_name_to_table_name method method.
         */
        protected static function _get_table_name($class_name) {
            $specified_table_name = self::_get_static_property($class_name, '_table');
            if (is_null($specified_table_name)) {
                return self::_class_name_to_table_name($class_name);
            }
            return $specified_table_name;
        }

        /**
         * Static method to convert a class name in CapWords
         * to a table name in lowercase_with_underscores.
         * For example, CarTyre would be converted to car_tyre.
         */
        protected static function _class_name_to_table_name($class_name) {
            return strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $class_name));
        }

        /**
         * Return the ID column name to use for this class. If it is
         * not set on the class, returns null.
         */
        protected static function _id_column_name($class_name) {
            return self::_get_static_property($class_name, '_id_column', self::DEFAULT_ID_COLUMN);
        }

        /**
         * Build a foreign key based on a table name. If the first argument
         * (the specified foreign key column name) is null, returns the second
         * argument (the name of the table) with the default foreign key column
         * suffix appended.
         */
        protected static function _build_foreign_key_name($specified_foreign_key_name, $table_name) {
            if (!is_null($specified_foreign_key_name)) {
                return $specified_foreign_key_name;
            }
            return $table_name . self::DEFAULT_FOREIGN_KEY_SUFFIX;
        }

        /**
         * Factory method used to acquire instances of the given class.
         * The class name should be supplied as a string, and the class
         * should already have been loaded by PHP (or a suitable autoloader
         * should exist).  Basically a wrapper for the new operator to facilitate
         * chaining.
         */
        public static function factory($class_name) {
            return new $class_name;
        }

        /**
         * Internal method to construct the queries for both the has_one and
         * has_many methods. These two types of association are identical; the
         * only difference is whether find_one or find_many is used to complete
         * the method chain.
         */
        protected function _has_one_or_many($associated_class_name, $foreign_key_name=null) {
            $base_table_name = self::_get_table_name(get_class($this));
            $foreign_key_name = self::_build_foreign_key_name($foreign_key_name, $base_table_name);
            return self::factory($associated_class_name)->where($foreign_key_name, $this->id());
        }

        /**
         * Helper method to manage one-to-one relations where the foreign
         * key is on the associated table.
         */
        protected function has_one($associated_class_name, $foreign_key_name=null) {
            return $this->_has_one_or_many($associated_class_name, $foreign_key_name);
        }

        /**
         * Helper method to manage one-to-many relations where the foreign
         * key is on the associated table.
         */
        protected function has_many($associated_class_name, $foreign_key_name=null) {
            return $this->_has_one_or_many($associated_class_name, $foreign_key_name);
        }

        /**
         * Helper method to manage one-to-one and one-to-many relations where
         * the foreign key is on the base table.
         */
        protected function belongs_to($associated_class_name, $foreign_key_name=null) {
            $associated_table_name = self::_get_table_name($associated_class_name);
            $foreign_key_name = self::_build_foreign_key_name($foreign_key_name, $associated_table_name);
            $associated_object_id = $this->$foreign_key_name;
            return self::factory($associated_class_name)->where_id_is($associated_object_id);
        }

        /**
         * Helper method to manage many-to-many relationships via an intermediate model. See
         * README for a full explanation of the parameters.
         */
        protected function has_many_through($associated_class_name, $join_class_name=null, $key_to_base_table=null, $key_to_associated_table=null) {
            $base_class_name = get_class($this);

            // The class name of the join model, if not supplied, is
            // formed by concatenating the names of the base class
            // and the associated class, in alphabetical order.
            if (is_null($join_class_name)) {
                $class_names = array($base_class_name, $associated_class_name);
                sort($class_names, SORT_STRING);
                $join_class_name = join("", $class_names);
            }

            // Get table names for each class
            $base_table_name = self::_get_table_name($base_class_name);
            $associated_table_name = self::_get_table_name($associated_class_name);
            $join_table_name = self::_get_table_name($join_class_name);

            // Get ID column names
            $base_table_id_column = self::_id_column_name($base_class_name);
            $associated_table_id_column = self::_id_column_name($associated_class_name);

            // Get the column names for each side of the join table
            $key_to_base_table = self::_build_foreign_key_name($key_to_base_table, $base_table_name);
            $key_to_associated_table = self::_build_foreign_key_name($key_to_associated_table, $associated_table_name);

            return self::factory($associated_class_name)
                ->select("{$associated_table_name}.*")
                ->join($join_table_name, array("{$associated_table_name}.{$associated_table_id_column}", '=', "{$join_table_name}.{$key_to_associated_table}"))
                ->where("{$join_table_name}.{$key_to_base_table}", $this->id());
        }
        
    }
