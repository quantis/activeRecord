<?php

    /**
     *
     * Mock version of the PDOStatement class.
     *
     */
    class DummyPDOStatement extends PDOStatement {

        private $current_row = 0;
        /**
         * Return some dummy data
         */
        public function fetch($fetch_style=PDO::FETCH_BOTH, $cursor_orientation=PDO::FETCH_ORI_NEXT, $cursor_offset=0) {
            if ($this->current_row == 5) {
                return false;
            } else {
                $this->current_row++;
                return array('name' => 'Fred', 'age' => 10, 'id' => '1');
            }
        }
    }

    /**
     *
     * Mock database class implementing a subset
     * of the PDO API.
     *
     */
    class DummyPDO extends PDO {

        /**
         * Return a dummy PDO statement
         */
        public function prepare($statement, $driver_options=array()) {
            $this->last_query = new DummyPDOStatement($statement);
            return $this->last_query;
        }
    }

    /**
     *
     * Class to provide simple testing functionality
     *
     */
    class Tester {

        private static $passed_tests = array();
        private static $failed_tests = array();
        private static $db;

        private static $term_colours = array(
            'BLACK' => "30",
            'RED' => "31",
            'GREEN' => "32",
            'DEFAULT' => "00",
        );

        /**
         * Format a line for printing. Detects
         * if the script is being run from the command
         * line or from a browser.
         *
         * Colouring code loosely based on
         * http://www.zend.com//code/codex.php?ozid=1112&single=1
         */
        private static function format_line($line, $colour='DEFAULT') {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $colour = strtolower($colour);
                return "<p style=\"color: $colour;\">$line</p>\n";
            } else {
                $colour = self::$term_colours[$colour];
                return chr(27) . "[0;{$colour}m{$line}" . chr(27) . "[00m\n";
            }
        }

        /**
         * Report a passed test
         */
        private static function report_pass($test_name) {
            echo self::format_line("PASS: $test_name", 'GREEN');
            self::$passed_tests[] = $test_name;
        }

        /**
         * Report a failed test
         */
        private static function report_failure($test_name, $expected, $actual) {
            echo self::format_line("FAIL: $test_name", 'RED');
            echo self::format_line("Expected: $expected", 'RED');
            echo self::format_line("Actual: $actual", 'RED');
            self::$failed_tests[] = $test_name;
        }

        /**
         * Print a summary of passed and failed test counts
         */
        public static function report() {
            $passed_count = count(self::$passed_tests);
            $failed_count = count(self::$failed_tests);
            echo self::format_line('');
            echo self::format_line("$passed_count tests passed. $failed_count tests failed.");

            if ($failed_count != 0) {
                echo self::format_line("Failed tests: " . join(", ", self::$failed_tests));
            }
        }

        /**
         * Check the provided string is equal to the last
         * query generated by the dummy database class.
         */
        public static function check_query($test_name, $query) {
            $last_query = ORM::get_last_query();
            if ($query === $last_query) {
                self::report_pass($test_name);
            } else {
                self::report_failure($test_name, $query, $last_query);
            }
        }
        
        /**
         * Check that the provided values are equal.
         */
        public static function check_equal($test_name, $actual, $expected) {
            if ($actual === $expected) {
                self::report_pass($test_name);
            } else {
                self::report_failure($test_name, $expected, $actual);
            }
        }
    }
