<?php
/*------------------------------------------------------------------------------
** File:        class.db.php
** Class:       Simply MySQLi
** Description: PHP MySQLi wrapper class to handle common database queries and operations 
** Version:     2.1.4
** Updated:     11-Sep-2014
** Author:      Bennett Stone
** Homepage:    www.phpdevtips.com 
**------------------------------------------------------------------------------
** COPYRIGHT (c) 2012 - 2014 BENNETT STONE
**
** The source code included in this package is free software; you can
** redistribute it and/or modify it under the terms of the GNU General Public
** License as published by the Free Software Foundation. This license can be
** read at:
**
** http://www.opensource.org/licenses/gpl-license.php
**
** This program is distributed in the hope that it will be useful, but WITHOUT 
** ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS 
** FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. 
**------------------------------------------------------------------------------ */

/* ----------------------------------------------------------------------------
** modificaciones:
** 2021-08-20 se corrige error al grabar valor false en insert()
** 2020-11-23 se agrega el parametro logs a insert_safe() OJO por ahora se deja por defecto TRUE
**------------------------------------------------------------------------------ */

/*******************************
 Example initialization:
 
 define( 'DB_HOST', 'localhost' ); // set database host
 define( 'DB_USER', 'root' ); // set database user
 define( 'DB_PASS', 'root' ); // set database password
 define( 'DB_NAME', 'yourdatabasename' ); // set database name
 define( 'SEND_ERRORS_TO', 'you@yourwebsite.com' ); //set email notification email address
 define( 'DISPLAY_DEBUG', true ); //display db errors?
 require_once( 'class.db.php' );

 //Initiate the class
 $database = new DB();

 //OR...
 $database = DB::getInstance();
 
 NOTE:
 All examples provided below assume that this class has been initiated
 Examples below assume the class has been iniated using $database = DB::getInstance();
********************************/
/*-------------------------------
List Of Function Avalilable:

disconnect()                        Disconnect from db server
tx( $action )                       Transaction Management @return bool  
                                    @param action (start = begin // end = commit  // abort = rollback)

log_db_errors( $error, $query )     Allow the class to send admins a message alerting them to errors
filter( $data )                     Sanitize user data
escape( $data )                     Extra function to filter when only mysqli_real_escape_string is needed
clean( $data )                      Normalize sanitized data for display (reverse $database->filter cleaning)
db_common( $value = '' )            Determine if common non-encapsulated fields are being used

query( $query )                     Perform queries @return bool

display( $variable, $echo = true )  Output results of queries @return string
total_queries()                     Output the total number of queries @return int

table_exists( $name )               Determine if database table exists @return bool
count_rows( $pTable )               Count absolute number of rows @return int
num_rows( $query )                  Count number of rows found matching a specific query @return int
exists( $table = '', $check_val = '', $params = array() )
                                    Run check to see if value exists, returns true or false
num_fields( $query )                Get number of fields @return int
list_fields( $query )               Get field names associated with a table @return array

create_tmp_like($origin = false , $newTable = false , $copyData = false , $filter = false)
                                    Create Temporary table like other table 
                                    @return array 
                                    ['status' => false , 'created' => false ,'msg' => '' , 'inserted' => 0 , 'newTable' => $newTable]

create_like($origin = false , $newTable = false , $copyData = false , $filter = false)
                                    Create table like other table 
                                    @return array 
                                    ['status' => false , 'created' => false ,'msg' => '' , 'inserted' => 0 , 'newTable' => $newTable]                               

truncate( $tables = array() )       Truncate entire tables @return int number of tables truncated

get_row( $query, $object = false )  Return specific row based on db query @return array
get_results( $query, $object = false )
                                    Perform query to retrieve array of associated results @return array

insert( $table, $variables = array() ) Insert data into database table @return bool
insert_safe( $table, $variables = array() ) Insert data KNOWN TO BE SECURE into database table @return bool
insert_multi( $table, $columns = array(), $records = array() , $pFilter = false)
                                    Insert multiple records in a single query into a database table 
                                    @return int number of records inserted
insert_select( $origin , $table, $filter = false)
                                    Insert record into $table, selecting data from $origin with $filter (optional)
                                    @return int number of records inserted

lastid()                            Get last auto-incrementing ID associated with an insertion @return int

update( $table, $variables = array(), $where = array(), $limit = '' )
                                    Update data in database table @return bool
delete( $table, $where = array(), $limit = '' )
                                    Delete data from table  @return bool  
delete_wherein($origin , $table , $field , $quick = true , $optimize = true)
                                    Delete records from $table, filtering $field from $origin
                                    $quick => For MyISAM tables, if you use the QUICK modifier, the storage engine does not merge index leaves during delete, which may speed up some kinds of delete operations.  
                                    $optimize => to reclaim the unused space and to defragment the data file. After extensive changes to a table, this statement may also improve performance of statements that use the table, sometimes significantly


affected()                          Return the number of rows affected by a given query @return int

http://php.net/manual/en/mysqlinfo.concepts.buffering.php
Unbuffered query example: mysqli
$uresult = $mysqli->query("SELECT Name FROM City", MYSQLI_USE_RESULT);

if ($uresult) {
   while ($row = $uresult->fetch_assoc()) {
       echo $row['Name'] . PHP_EOL;
   }
}
$uresult->close();

----------------------------------*/
// namespace Wikired;
// use \Wikired\wrCommonFoundation as wr;
// require_once CONFIG_DB;

class wrException extends \Exception {};

class wrDB
{
    private $link = null;
    public $filter;
    static $inst = null;
    public static $counter = 0;
    // public $caller = '';

    public function __construct($dbHost,$dbUser,$dbPass,$dbName,$dbPort = false) {
        mb_internal_encoding( 'UTF-8' );
        mb_regex_encoding( 'UTF-8' );
        mysqli_report( MYSQLI_REPORT_STRICT );
        try {

            if ( !empty($dbPort) ){
                $this->link = new \mysqli( $dbHost,$dbUser,$dbPass,$dbName,$dbPort);
            } else {
                $this->link = new \mysqli( $dbHost,$dbUser,$dbPass,$dbName);
            }
            $this->link->set_charset( "utf8" );
            mysqli_set_charset($this->link, 'utf8');
            // agregado el 2020-11-26 default timezone
            $foo = $this->link->query( 'SET time_zone = "-03:00"' );
        } catch ( Exception $e ) {
            $this->log_db_errors( 'Unable to connect to database', '' ); 
            die( 'Unable to connect to database' );
        }

        // $this->caller = $_SERVER['SCRIPT_NAME'];
    }
    
    /**
     * Allow the class to send admins a message alerting them to errors
     * on production sites
     *
     * @access public
     * @param string $error
     * @param string $query
     * @return mixed
     */
    public function log_db_errors( $error, $query ) {
        $message = '<p>Error at '. date('Y-m-d H:i:s').':</p>';
        $message .= '<p>Query: '. htmlentities( $query ).'<br />';
        $message .= 'DB Error: ' . $error;
        $message .= '</p>';

        if( defined( 'SEND_ERRORS_TO' ) )
        {

            // // cofigo original
            // $headers  = 'MIME-Version: 1.0' . "\r\n";
            // $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            // $headers .= 'To: Admin <'.SEND_ERRORS_TO.'>' . "\r\n";
            // $headers .= 'From: Yoursite <system@'.$_SERVER['SERVER_NAME'].'.com>' . "\r\n";

            // mail( SEND_ERRORS_TO, 'Database Error', $message, $headers ); 
            // Modificado por Luis para que envie el error al log  
            error_log("DB Error  " . print_r($error , true), 0 );
            error_log("DB Error  " . print_r($query , true), 0 );
            error_log(str_repeat("-", 100) , 0);

            // wr::log($error);
            // wr::log($query);
            
            //static public function log($pObject , $pBreak = true, $pFormato = "print" , $pDetalle = false , $pLogFile = false)

        }
        else
        {
            trigger_error( $message );
        }

        if( !defined( 'DISPLAY_DEBUG' ) || ( defined( 'DISPLAY_DEBUG' ) && DISPLAY_DEBUG ) )
        {
            echo $message;   
        }
    }
       

    public function __destruct(){
        if( $this->link)
        {
            $this->disconnect();
        }
    }


    /**
     * Sanitize user data
     *
     * Example usage:
     * $user_name = $database->filter( $_POST['user_name'] );
     * 
     * Or to filter an entire array:
     * $data = array( 'name' => $_POST['name'], 'email' => 'email@address.com' );
     * $data = $database->filter( $data );
     *
     * @access public
     * @param mixed $data
     * @return mixed $data
     */
     public function filter( $data ){
         if( !is_array( $data ) )
         {          
             $data = $this->link->real_escape_string( $data );           
             $data = trim( htmlentities( $data, ENT_QUOTES, 'UTF-8', false ) );         
         }
         else
         {
             //Self call function to sanitize array data
             $data = array_map( array( $this, 'filter' ), $data );
         }
         return $data;
     }
         
     /**
      * Extra function to filter when only mysqli_real_escape_string is needed
      * @access public
      * @param mixed $data
      * @return mixed $data
      */
     public function escape( $data ){
         if( !is_array( $data ) )
         {
             $data = $this->link->real_escape_string( $data );
         }
         else
         {
             //Self call function to sanitize array data
             $data = array_map( array( $this, 'escape' ), $data );
         }
         return $data;
     }
      
    /**
     * Normalize sanitized data for display (reverse $database->filter cleaning)
     *
     * Example usage:
     * echo $database->clean( $data_from_database );
     *
     * @access public
     * @param string $data
     * @return string $data
     */
     public function clean( $data ){
         $data = stripslashes( $data );
         $data = html_entity_decode( $data, ENT_QUOTES, 'UTF-8' );
         $data = nl2br( $data );
         $data = urldecode( $data );
         return $data;
     }
       
    /**
     * Determine if common non-encapsulated fields are being used
     *
     * Example usage:
     * if( $database->db_common( $query ) )
     * {
     *      //Do something
     * }
     * Used by function exists
     *
     * @access public
     * @param string
     * @param array
     * @return bool
     *
     */
    public function db_common( $value = '' ){
        $rv = false;
        if( is_array( $value ) ) {
            foreach( $value as $v ){
                if( preg_match( '/AES_DECRYPT/i', $v ) || preg_match( '/AES_ENCRYPT/i', $v ) || preg_match( '/now()/i', $v ) ) {
                    $rv =  true;
                }
            }
        } else {
            if( preg_match( '/AES_DECRYPT/i', $value ) || preg_match( '/AES_ENCRYPT/i', $value ) || preg_match( '/now()/i', $value ) ) {
                $rv = true;
            }
        }
        return $rv;
    }

    
    /**
     * Perform queries
     * All following functions run through this function
     *
     * @access public
     * @param string
     * @return string
     * @return array
     * @return bool
     *
     */
    public function query( $query , $log = false){
        $this->wrLog($query , $log); 
        $full_query = $this->link->query( $query );
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $query );
            return false; 
        }
        else
        {
            return true;
        }
    }
    
     public function queryAffected( $query , $log = false){
        $this->wrLog($query , $log); 
        $full_query = $this->link->query( $query );
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $query );
            return false; 
        }
        else
        {
            // return true;
            return $this->link->affected_rows;
        }
    }



    /**
     * Determine if database table exists
     * Example usage:
     * if( !$database->table_exists( 'checkingfortable' ) )
     * {
     *      //Install your table or throw error
     * }
     *
     * @access public
     * @param string
     * @return bool
     *
     */
     public function table_exists( $name ){
         self::$counter++;
         $check = $this->link->query( "SELECT 1 FROM $name" );

         return $check;

         /* corregido por Luis porque consideraba que no existe una tabla que esta vacia
         if($check !== false)
         {
             if( $check->num_rows > 0 )
             {
                 return true;
             }
             else
             {
                 return false;
             }
         }
         else
         {
             return false;
         }
         */
     }
    
    /**
     * Count absolute number of rows
     *
     * Example usage:
     * $rows = $database->count_rows( "SELECT count(*) as nCount FROM users " );
     *
     * @access public
     * @param string = table name
     * @return int
     *
     */   
    public function count_rows( $pTable , $where = false ){   
        self::$counter++;
        $sQuery = 'select count(*) as nCount FROM ' . $pTable ;
        if( false !== $where  ) {
            if ( is_string ($where ) ){
                $sQuery .= $where ;
            } else if ( is_array ($where ) ){
                foreach( $where as $field => $value ) {
                    $clause[] = "`$field` = '$value'";
                }
                $sQuery .= ' WHERE '. implode(' AND ', $clause);   
            }
        }
        $xResultado = $this->link->query( $sQuery );
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $sQuery );
            return false;
        }
        else
        {
            $r =  $xResultado->fetch_row();
            return intval($r[0]);   
        }
    }
 
    /**
     * Count number of rows found matching a specific query
     *
     * Example usage:
     * $rows = $database->num_rows( "SELECT id FROM users WHERE user_id = 44" );
     *
     * @access public
     * @param string
     * @return int
     *
     */
    public function num_rows( $query ){
        self::$counter++;
        $num_rows = $this->link->query( $query );
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $query );
            // return $this->link->error;
            // TODO revisar este error
            return 0;
        }
        else
        {
            return $num_rows->num_rows;
        }
    }

    /**
     * Run check to see if value exists, returns true or false
     *
     * Example Usage:
     * $check_user = array(
     *    'user_email' => 'someuser@gmail.com', 
     *    'user_id' => 48
     * );
     * $exists = $database->exists( 'your_table', 'user_id', $check_user );
     *
     * @access public
     * @param string database table name
     * @param string field to check (i.e. 'user_id' or COUNT(user_id))
     * @param array column name => column value to match
     * @return bool
     *
     */
    public function exists( $table = '', $check_val = '', $params = array() ){
        self::$counter++;
        if( empty($table) || empty($check_val) || empty($params) )
        {
            return false;
        }
        $check = array();
        foreach( $params as $field => $value )
        {
            if( !empty( $field ) && !empty( $value ) )
            {
                //Check for frequently used mysql commands and prevent encapsulation of them
                if( $this->db_common( $value ) )
                {
                    $check[] = "$field = $value";   
                }
                else
                {
                    $check[] = "$field = '$value'";   
                }
            }

        }
        $check = implode(' AND ', $check);

        $rs_check = "SELECT $check_val FROM ".$table." WHERE $check";
        $number = $this->num_rows( $rs_check );
        if( $number === 0 )
        {
            return false;
        }
        else
        {
            return true;
        }
    }
       
    /**
     * Return specific row based on db query
     *
     * Example usage:
     * list( $name, $email ) = $database->get_row( "SELECT name, email FROM users WHERE user_id = 44" );
     *
     * @access public
     * @param string
     * @param bool $object (true returns results as objects)
     * @return array
     *
     */
    public function get_row( $query, $object = false , $log = false  ){
        self::$counter++;
        $row = $this->link->query( $query );
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $query );
            return false;
        }
        else
        {
            $this->wrLog($query , $log);
            if ( 3 == $object ){
                $r = $row->fetch_assoc();
            } else {
                $r = ( !$object ) ? $row->fetch_row() : $row->fetch_object();
            }
            
            return $r;   
        }
    }

    /**
     * Perform query to retrieve array of associated results
     *
     * Example usage:
     * $users = $database->get_results( "SELECT name, email FROM users ORDER BY name ASC" );
     * foreach( $users as $user )
     * {
     *      echo $user['name'] . ': '. $user['email'] .'<br />';
     * }
     *
     * @access public
     * @param string
     * @param bool or int $object (true returns object)
     *                0 = assoc    1 = object    2 = array  3 = array unico
     * @return array
     *
     */
    public function get_results( $query, $object = false , $log = false ){
        self::$counter++;
        //Overwrite the $row var to null
        $row = null;
        
        $results = $this->link->query( $query );
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $query );
            return false;
        }
        else
        {
            $this->wrLog($query , $log); 
            $row = array();
            // ori while( $r = ( !$object ) ? $results->fetch_assoc() : $results->fetch_object() )
            // ori { $row[] = $r;   }
            if ($object == 3) { 
                while( $r =  $results->fetch_array(MYSQLI_NUM) ) {
                    $row[] = $r[0];   
                }
            } else if ($object == 2) { 
                while( $r =  $results->fetch_array(MYSQLI_NUM) ) {
                    $row[] = $r;   
                }
            } else {
                 while( $r = ( !$object ) ? $results->fetch_assoc() : $results->fetch_object() )
                    { $row[] = $r;   }
            }
            
            return $row;   
        }
    }
 
    /*
        Perform safe query to retrieve array of associated results

        NOT READY YET
    
        Example usage:
        $users = $database->get_results_safe( 
            ['b.name' => 'product' ,  'a.year' => 'year , 'a.month' => 'month'], 
            [   
                [ 'a.sales' => ['op' => 'sum' ,  'alias' => 'sales'] ], 
                [ 'a.id']   => ['op' => 'count' , 'alias' => 'operations']
            ],
            'a.sales ' ,
            [ 
                [ 'b.products' => ['type' => 'inner' , 'clause' => a.product = b.id' ]
            ] ,
            [
                [ 'a.year' => ['condition' => '=' , 'value'=> 2020 ]]
            ],
            
             , $sort , $result , $log);

    '; delete  from hacked where 0;--  

     *
     * @access public

     * @param  $fields  array()
     * @param  $grouped array()             false => not grouped fields
     * @param  $table   string
     * @param  $join    boolean or string   false => no join
     * @param  $where   boolean or string   false => no filter
     * @param  $sort    boolean or string   false => sort by $fields
     * @param  $result bool or int 
     *          0 or false  = array assoc
     *          1 or true   = object
     *          2           = array
     *          3           0 unique array
     * @param  $log     
     * @return array
     *

    public function get_results_safe( $query, $object = false , $log = false ){
        self::$counter++;
        //Overwrite the $row var to null
        $row = null;
        
        $results = $this->link->query( $query );
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $query );
            return false;
        }
        else
        {
            $this->wrLog($query , $log); 
            $row = array();
            // ori while( $r = ( !$object ) ? $results->fetch_assoc() : $results->fetch_object() )
            // ori { $row[] = $r;   }
            if ($object == 3) { 
                while( $r =  $results->fetch_array(MYSQLI_NUM) ) {
                    $row[] = $r[0];   
                }
            } else if ($object == 2) { 
                while( $r =  $results->fetch_array(MYSQLI_NUM) ) {
                    $row[] = $r;   
                }
            } else {
                 while( $r = ( !$object ) ? $results->fetch_assoc() : $results->fetch_object() )
                    { $row[] = $r;   }
            }
            
            return $row;   
        }
    }    
     */

    /**
     * record audit log
     *
     * Example usage:
     *
     * $database->audit( 'users_table', $user_data );
     *
     * @access public
     * @param string table name
     * @param array table column => column value
     * @return bool
     *
    */

    private function audit($method = 'false', $query = 'false', $msg = '' , $audit = false){
        $log = false;        //  poner false en produccion
        try {
            if ($audit ){
                $sql  = 'INSERT INTO f000311 ';
                $sql .= "( obj , method , uid , query , msg ) VALUES ( '";
                $sql .= $_SERVER['SCRIPT_NAME'] . "' , '";
                $sql .= $method . "' , '";
                $sql .= $_SESSION['wr'][_appName]['uid']   . "' , '";
                $sql .=  str_replace ( "'" , '~' , $query )  . "' , '";
                $sql .=  str_replace ( "'" , '~' , $msg   )  . "' )";

                $this->wrLog($sql , $log);

                $query = $this->link->query( $sql );
            }
        } catch (Exception $e) {
           $this->log_db_errors( 'Error en audit() ' . $e->getMessage() , '' );
        }
        return true;
    }




    /**
     * Insert data into database table
     *
     * Example usage:
     * $user_data = array(
     *      'name' => 'Bennett', 
     *      'email' => 'email@address.com', 
     *      'active' => 1
     * );
     * $database->insert( 'users_table', $user_data );
     *
     * @access public
     * @param string table name
     * @param array table column => column value
     * @return bool
     *
     */
    public function insert( $table, $variables = array() , $log = false , $audit = false , $returnId = false )
    {
        $rv  = false;
        $sql = '';
        $msg = '';
        try {
            self::$counter++;
            //Make sure the array isn't empty
            if( empty( $variables ) ){
                throw new \Exception('Parametros incorrectos');
            }
            
            $sql = "INSERT INTO ". $table;
            $fields = array();
            $values = array();
            foreach( $variables as $field => $value ){
                $fields[] = $field;
                if ( false === $value ){
                    $values[] = "'0'";
                } elseif ( in_array($value , ['@@NOW()' , '@@CURRENT_TIMESTAMP']) ){
                    $values[] = "CURRENT_TIMESTAMP" ; 
                } else {
                    $values[] = "'".$value."'";    
                }
            }
            $fields = ' (' . implode(', ', $fields) . ')';
            $values = '('. implode(', ', $values) .')';           
            $sql .= $fields .' VALUES '. $values;

            // agregado 2020-11-28
            $this->wrLog($sql , $log);

            $query = $this->link->query( $sql );
            
            if( $this->link->error ){
                //return false; 
                $linkError = $this->link->error;
                $this->log_db_errors( $linkError, $sql );
                throw new \Exception($linkError);
                // return false;
            } else {
                $rv  = true;
                $msg = 'ok';
            }

        } catch (\Exception $e) {
           $msg = $e->getMessage();
        }

        $id = $this->link->insert_id;

        $this->audit(  __METHOD__ , $sql ,  $msg , $audit );    //$rv ,

        //agregado el 2021-03-16
        if ( $returnId ) {
            $rv = $id;
        }

        return $rv;
    }
    
    
    /**
    * Insert data KNOWN TO BE SECURE into database table
    * Ensure that this function is only used with safe data
    * No class-side sanitizing is performed on values found to contain common sql commands
    * As dictated by the db_common function
    * All fields are assumed to be properly encapsulated before initiating this function
    *
    * @access public
    * @param string table name
    * @param array table column => column value
    * @param bool  save query to log, for debug
    * @return bool
    */
    public function insert_safe( $table, $variables = array() ,  $log = false , $audit = false  )
    {
        $rv  = false;
        $sql = '';
        $msg = '';
        try {
            self::$counter++;
            //Make sure the array isn't empty
            if( empty( $variables ) ){
                throw new \Exception('Parametros incorrectos');
            }
            
            $sql = "INSERT INTO ". $table;
            $fields = array();
            $values = array();
            foreach( $variables as $field => $value ){
                $fields[] = "`" . $this->filter( $field ) . "`";
                //Check for frequently used mysql commands and prevent encapsulation of them
                $values[] = "'". $this->filter($value) ."'"; 
            }
            $fields = ' (' . implode(', ', $fields) . ')';
            $values = '('. implode(', ', $values) .')';
            
            $sql .= $fields .' VALUES '. $values;   

            // agregado 2020-11-23
            $this->wrLog($sql , $log);
            
            $query = $this->link->query( $sql );
            
            if( $this->link->error ){
                $linkError = $this->link->error;
                $this->log_db_errors( $linkError, $sql );
                throw new \Exception($linkError);
            } else {
                $rv  = true;
                $msg = 'ok';
            }
        } catch (\Exception $e) {
           $msg = $e->getMessage();
        }
        $this->audit(  __METHOD__ , $sql ,  $msg , $audit );    //$rv ,
        return $rv;
    }
    
    
    /**
     * Insert multiple records in a single query into a database table
     *
     * Example usage:
     * $fields = array('name', 'email', 'active'  );
     * $records = array(
     *      array( 'Bennett', 'bennett@email.com', 1 ), 
     *      array( 'Lori', 'lori@email.com', 0       ), 
     *      array( 'Nick', 'nick@nick.com', 1, 'This will not be added'  ), 
     *      array( 'Meghan', 'meghan@email.com', 1   )
     * );
     * 
     *  $database->insert_multi( 'users_table', $fields, $records );
     *
     * @access public
     * @param string table name
     * @param array table columns
     * @param array records
     * @param bool   pFilter [sanitize records if true]
     * @return bool
     * @return int number of records inserted
     *
     */
    public function insert_multi( $table, $columns = array(), $records = array() , $pFilter = false , $log = false , $audit = false)
    {
        $rv  = false;
        $sql = '';
        $msg = '';
        try {        
            self::$counter++;
            // validaciones
            if( empty( $columns ) || empty( $records ) || empty( $table ) ) { 
                throw new \Exception('Parametros incorrectos'); 
            }

            //Count the number of fields to ensure insertion statements do not exceed the same num
            $number_columns = count( $columns );

            //Start a counter for the rows
            $added = 0;

            //Start the query
            $sql = "INSERT INTO ". $table;

            $fields = array();
            $xValues = array();
            //Loop through the columns for insertion preparation
            foreach( $columns as $field )
            {
                $fields[] = '`'.$field.'`';
            }
            $fields = ' (' . implode(", " , $fields) . ')';      
            //Loop through the records to insert
            $values = array();
            //check if array have more than 1 record
            $firstKey = array_keys($records)[0];
            if (is_array($records[$firstKey]) ){
                // have more than 1 record
                foreach( $records as $record ){
                    //Only add a record if the values match the number of columns
                    if( count( $record ) == $number_columns ) {
                        if ($pFilter) {
                            $record = $this->filter($record);    //sanitize data
                        }
                        foreach( $record as $value ) {  
                            if ( is_null($value ) ){
                                $values[] = 'NULL';
                            } elseif (  false === $value ){
                                $values[] = "'0'";
                            } elseif ( in_array($value , ['@@NOW()' , '@@CURRENT_TIMESTAMP']) ){
                                $values[] = "CURRENT_TIMESTAMP" ; 
                            }  else {
                                $values[] = "'".$value."'";
                            }                     
                        }
                        $valuesSTR = '('. implode(', ', $values) .')' . "\n";
                        $xValues[] = $valuesSTR;
                        unset($values);
                        $added++;
                    } else {
                        // OJO revisar esto
                    // wr::log( $columns );
                    // wr::log( $records );
                        throw new \Exception('-1- No coinciden la cantidad de datos con la cantidad de columnas');
                    }
                }
            } else {
                // only have 1 record
                if( count( $records ) == $number_columns ) {
                    if ($pFilter) {
                        $records = $this->filter($records);    //sanitize data
                    }
                    foreach( $records as $value ) {  
                        if ( is_null($value ) ){
                            $values[] = 'NULL';
                        } elseif (  false === $value ){
                            $values[] = "'0'";
                        } elseif ( in_array($value , ['@@NOW()' , '@@CURRENT_TIMESTAMP']) ){
                            $values[] = "CURRENT_TIMESTAMP" ; 
                        }  else {
                            $values[] = "'".$value."'";
                        }                      
                    }
                    $valuesSTR = '('. implode(', ', $values) .')';
                    $xValues[] = $valuesSTR;
                    unset($values);
                    $added++;
                } else {
                    // wr::log( $columns );
                    // wr::log( $records );
                    throw new \Exception('-2- No coinciden la cantidad de datos con la cantidad de columnas'); 
                }
            }

            $valuesSTR = implode( ', ', $xValues );
            $sql .= $fields .' VALUES '. $valuesSTR; 
            $this->wrLog($sql , $log);    
            $this->link->query( $sql );

            if( $this->link->error ) {
                $linkError = $this->link->error;
                $this->log_db_errors( $linkError, $sql );
                $rv = -1;
                throw new \Exception($linkError);
            } else {
                $rv =  $added;
                $msg = 'ok: ' . $added;
            }

        } catch (\Exception $e) {
            $msg = $e->getMessage();
        //    wr::log( $msg );
        }

        $this->audit(  __METHOD__ , $sql ,  $msg , $audit );    //$rv ,

        return $rv;

    }

    /**
     * Update multiple row in database table
     *
     * * $database->update_multi( 'table', $fields, $values , $log );
     * Example usage:
     * $update_multi = array( 'name' => 'Not bennett', 'email' => 'someotheremail@email.com' );
     * $update_where = array( 'user_id' => 44, 'name' => 'Bennett' );
     * $database->update_multi( 'users_table', $update, $update_where, 1 );
     *
     * @access public
     * @param string table name
     * @param array table columns  (first column MUST indicate Primary Key)
     * @param nested array records
     * @param bool indicate if write log with each sql script
     * @return bool ?????
     *
     */
    public function update_multi( $table, $data = array(), $log = false )
    {
        self::$counter++;
        //Make sure the arrays aren't empty
        if( empty( $data ) ) {
            return false; 
        }
        //Start a counter for the rows
        $added = 0;
        //loop for records array

        $sql = '';
        foreach ($data as $row) {
            $sql .=  " UPDATE `". $table . '` SET ' ;
            $id = 0 ;
            $col = 1;
            ++$added;
            foreach ($row as $key => $value) {
                if (0 == $id){
                    $sqlWhere =  " WHERE `$key` = '$value' ; "    ;   
                    $id++ ;
                } else {
                    $sql .= " `$key` = '$value' " ;  
                    $sql .= ( ($col + 1) < count($row)   ) ? ' , ' :'';
                    $col++;
                }
            }
            $sql .= $sqlWhere;
        }
        echo 'update Multi -----------------------------------------------------------------' . _ff;
        echo $sql . _ff;
        $this->wrLog($sql , $log); 
        $query = $this->link->multi_query( $sql );

        if( $this->link->error ) {
            echo 'resultado del update_multi:' . _ff;
            var_dump($this->link->error);            
            $this->log_db_errors( $this->link->error, $sql );
            $ret = -1;
        } else {
            $ret = $added;
        }
        //$this->link->free_result( );
        // flush multi query
        while ($this->link->next_result()) // flush multi_queries
        {
            if (!$this->link->more_results()) break;
        }

        return $ret;
        
    }


    
    
    /**
     * Update data in database table
     *
     * Example usage:
     * $update = array( 'name' => 'Not bennett', 'email' => 'someotheremail@email.com' );
     * $update_where = array( 'user_id' => 44, 'name' => 'Bennett' );
     * $database->update( 'users_table', $update, $update_where, 1 );
     *
     * @access public
     * @param string table name
     * @param array values to update table column => column value
     * @param array where parameters table column => column value
     * @param int limit
     * @return bool
     *
     */
    public function update( $table, $variables = array(), $where = array(), $limit = '' , $safe = true , $audit = false)
    {
        $rv  = false;
        $sql = '';
        $msg = '';
        try {
            self::$counter++;
            //Make sure the required data is passed before continuing
            //This does not include the $where variable as (though infrequently)
            //queries are designated to update entire tables
            if( empty( $variables ) ) {
                throw new \Exception('Parametros incorrectos');
            }
            $sql = "UPDATE ". $table ." SET ";
            foreach( $variables as $field => $value ) {
                // agregado el 2020-12-16 OJO verificar esto en update-multi
                if ( false === $value ){
                    $value = '0';
                } 
                if ( true === $safe ){
                    $updates[] = "`" . $this->filter( $field ) . "` = '" . $this->filter( $value ) . "'";
                } else {
                
                    $updates[] = "`$field` = '$value'";
                }
            }
            $sql .= implode(', ', $updates);
            
            //Add the $where clauses as needed
            if( !empty( $where ) ) {
                foreach( $where as $field => $value ) {
                    // $value = $value; para que???????????
                    if ( true === $safe ){
                        $clause[] = "`" . $this->filter( $field ) . "` = '" . $this->filter( $value ) . "'";
                    } else {
                        $clause[] = "`$field` = '$value'";
                    }                
                }
                $sql .= ' WHERE '. implode(' AND ', $clause);   
            }
            
            if( !empty( $limit ) ) {
                $sql .= ' LIMIT '. $limit;
            }

            $query = $this->link->query( $sql );

            if( $this->link->error ) {
                $linkError = $this->link->error;
                $this->log_db_errors( $linkError, $sql );
                throw new \Exception($linkError);
            } else  {
                $rv  = true;
                $msg = 'ok';
            }
        } catch (\Exception $e) {
           $msg = $e->getMessage();
        }

        $this->audit(  __METHOD__ , $sql ,  $msg , $audit );    //$rv ,
        return $rv;
            
    }

    /**
     * Update data in database table, with extended parammeters
     *
     * Example usage:
     * $update = array( 'name' => 'Not bennett', 'email' => 'someotheremail@email.com' );
     * $extended = array(
            'timestamp' => array ( 'umodi' ),
     )
     * $update_where = array( 'user_id' => 44, 'name' => 'Bennett' );
     * $database->update( 'users_table', $update, $update_where, 1 );
     *
     * @access public
     * @param string table name
     * @param array values to update table column => column value
     * @param array where parameters table column => column value
     * @param int limit
     * @return bool
     *
     */
    public function updateExtended( $table, $variables = array(), $extended = array() , $where = array(), $limit = '' , $safe = true , $audit = false)
    {
        $rv  = false;
        $sql = '';
        $msg = '';
        try {
            self::$counter++;
            //Make sure the required data is passed before continuing
            //This does not include the $where variable as (though infrequently)
            //queries are designated to update entire tables
            if( empty( $variables ) ) {
                throw new \Exception('Parametros incorrectos');
            }
            $sql = "UPDATE ". $table ." SET ";
            foreach( $variables as $field => $value ) {
                // agregado el 2020-12-16 OJO verificar esto en update-multi
                if ( false === $value ){
                    $value = '0';
                } 
                if ( true === $safe ){
                    $updates[] = "`" . $this->filter( $field ) . "` = '" . $this->filter( $value ) . "'";
                } else {
                
                    $updates[] = "`$field` = '$value'";
                }
            }
            $sql .= implode(', ', $updates);
            // agregado el 2020-12-23
            if (false === empty($extended) ){
                if ( array_key_exists('timestamp', $extended ) ){
                    foreach ($extended['timestamp'] as $keyTS => $valueTS) {
                        $sql .= ' , ' . $valueTS . ' =  current_timestamp ';
                    }
                }
            }
            
            //Add the $where clauses as needed
            if( !empty( $where ) ) {
                foreach( $where as $field => $value ) {
                    // $value = $value; para que???????????
                    if ( true === $safe ){
                        $clause[] = "`" . $this->filter( $field ) . "` = '" . $this->filter( $value ) . "'";
                    } else {
                        $clause[] = "`$field` = '$value'";
                    }                
                }
                $sql .= ' WHERE '. implode(' AND ', $clause);   
            }
            
            if( !empty( $limit ) ) {
                $sql .= ' LIMIT '. $limit;
            }

            $query = $this->link->query( $sql );

            if( $this->link->error ) {
                $linkError = $this->link->error;
                $this->log_db_errors( $linkError, $sql );
                throw new \Exception($linkError);
            } else  {
                $rv  = true;
                $msg = 'ok';
            }
        } catch (\Exception $e) {
           $msg = $e->getMessage();
        }

        $this->audit(  __METHOD__ , $sql ,  $msg , $audit );    //$rv ,
        return $rv;
            
    }    
    /**
     * Delete data from table
     *
     * Example usage:
     * $where = array( 'user_id' => 44, 'email' => 'someotheremail@email.com' );
     * $database->delete( 'users_table', $where, 1 );
     *
     * @access public
     * @param string table name
     * @param array where parameters table column => column value
     * @param int max number of rows to remove.
     * @return bool
     *
     */
    public function delete( $table, $where = array(), $limit = '' , $log = false , $audit = false)
    {
        self::$counter++;
        //Delete clauses require a where param, otherwise use "truncate"
        if( empty( $where ) )
        {
            return false;
        }
        
        $sql = "DELETE FROM ". $table;
        foreach( $where as $field => $value )
        {
            //$value = $value;
            $clause[] = "$field = '$value'";
        }
        $sql .= " WHERE ". implode(' AND ', $clause);
        
        if( !empty( $limit ) )
        {
            $sql .= " LIMIT ". $limit;
        }

        // agregado el 2021-03-16
        $this->wrLog($sql , $log);

        $query = $this->link->query( $sql );

        if( $this->link->error )
        {
            //return false; //
            $this->log_db_errors( $this->link->error, $sql );
            $rv =  false;
        }
        else
        {
            $rv =   true;
        }

        $msg = '';  //  to do

        $this->audit(  __METHOD__ , $sql ,  $msg , $audit );

        return $rv;
    }
    
    
    /**
     * Get last auto-incrementing ID associated with an insertion
     *
     * Example usage:
     * $database->insert( 'users_table', $user );
     * $last = $database->lastid();
     *
     * @access public
     * @param none
     * @return int
     *
     */
    public function lastid()
    {
        self::$counter++;
        return $this->link->insert_id;
    }
    
    
    /**
     * Return the number of rows affected by a given query
     * 
     * Example usage:
     * $database->insert( 'users_table', $user );
     * $database->affected();
     *
     * @access public
     * @param none
     * @return int
     */
    public function affected()
    {
        return $this->link->affected_rows;
    }
    
    
    /**
     * Get number of fields
     *
     * Example usage:
     * echo $database->num_fields( "SELECT * FROM users_table" );
     *
     * @access public
     * @param query
     * @return int
     */
    public function num_fields( $query )
    {
        self::$counter++;
        $query = $this->link->query( $query );
        $fields = $query->field_count;
        return $fields;
    }
    
    
    /**
     * Get field names associated with a table
     *
     * Example usage:
     * $fields = $database->list_fields( "SELECT * FROM users_table" );
     * echo '<pre>';
     * print_r( $fields );
     * echo '</pre>';
     *
     * @access public
     * @param query
     * @return array
     */
    public function list_fields( $query )
    {
        self::$counter++;
        $query = $this->link->query( $query );
        $listed_fields = $query->fetch_fields();
        return $listed_fields;
    }
    
    
    /**
     * Truncate entire tables
     *
     * Example usage:
     * $remove_tables = array( 'users_table', 'user_data' );
     * echo $database->truncate( $remove_tables );
     *
     * @access public
     * @param array database table names
     * @return int number of tables truncated
     *
     */
    public function truncate( $tables = array() )
    {
        if( !empty( $tables ) )
        {
            $truncated = 0;
            foreach( $tables as $table )
            {
                $truncate = "TRUNCATE TABLE `".trim($table)."`";
                $this->link->query( $truncate );
                if( !$this->link->error )
                {
                    $truncated++;
                    self::$counter++;
                }
            }
            return $truncated;
        }
    }
    
    
    /**
     * Output results of queries
     *
     * @access public
     * @param string variable
     * @param bool echo [true,false] defaults to true
     * @return string
     *
     */
    public function display( $variable, $echo = true )
    {
        $out = '';
        if( !is_array( $variable ) )
        {
            $out .= $variable;
        }
        else
        {
            $out .= '<pre>';
            $out .= print_r( $variable, TRUE );
            $out .= '</pre>';
        }
        if( $echo === true )
        {
            echo $out;
        }
        else
        {
            return $out;
        }
    }
    
    
    /**
     * Output the total number of queries
     * Generally designed to be used at the bottom of a page after
     * scripts have been run and initialized as needed
     *
     * Example usage:
     * echo 'There were '. $database->total_queries() . ' performed';
     *
     * @access public
     * @param none
     * @return int
     */
    public function total_queries()
    {
        return self::$counter;
    }
    
    
    /**
     * Singleton function
     *
     * Example usage:
     * $database = DB::getInstance();
     *
     * @access private
     * @return self
     */
    static function getInstance()
    {
        if( self::$inst == null )
        {
            self::$inst = new DB();
        }
        return self::$inst;
    }
    
    
    /**
     * Disconnect from db server
     * Called automatically from __destruct function
     */
    public function disconnect()
    {
        $this->link->close();
    }

    /* Custom Functions */

    /**
     * Transaction Management
     * @access public
     * @param action (start = begin // end = commit  // abort = rollback)
     * @return bool
     */
    public function tx( $action )
    {
        if( empty( $action ) ) { return false; }
        switch($action)  {
            case "start":
            case "begin":
                $this->link->query( "SET autocommit = 0" );
                if( $this->link->error ) {
                    return false;
                } else {
                    $this->link->query( "start transaction " );
                    return ( $this->link->error) ? false : true ;
                }
                break;
            case "end":
            case "commit":
                $this->link->query( "commit" );
                return ( $this->link->error) ? false : true ;
                break;
            case "abort":
            case "rollback":
                $this->link->query( "rollback" );
                return ( $this->link->error) ? false : true ;
                break;
            default:
                return false;
        }
    }

    public function create_tmp_like($origin = false , $newTable = false , $copyData = false , $filter = false){
        $xReturn = ['status' => false , 'created' => false ,'msg' => '' , 'inserted' => 0 , 'newTable' => $newTable];
        if( $origin && $this->table_exists( $origin ) ) {
            if (!$newTable) {
                // compone el nombre de la tabla tempoaria
                $newTable = $origin . '_' . wrHash(10);
                $xReturn['newTable'] = $newTable;
            }
            $query = 'CREATE TEMPORARY TABLE `' . $newTable . '` LIKE `' . $origin . '`' ;
            $this->link->query( $query );
            if( !$this->link->error )
            {
                $xReturn['created'] = true;
                if ($copyData){
                    // OJO por ahora falta esto
                    $xReturn['inserted'] = $this->insert_select( $origin , $newTable , $filter );
                }
                $xReturn['status'] = true;
            }
        } else {
            $xReturn['msg'] = 'Error: tabla no existe o no informada';
        }
        return $xReturn;
    }

    public function create_like($origin = false , $newTable = false , $copyData = false , $filter = false){
        $xReturn = array(
            'status' => false , 
            'created' => false ,
            'msg' => '' , 
            'inserted' => 0 , 
            'newTable' => $newTable
        );
        if( $origin && $this->table_exists( $origin ) ) {
            if ($newTable) {
                // drop if exist
                if ( $this->table_exists( $newTable ) ) {
                    $foo = $this->query( 'DROP TABLE IF EXISTS `' . $newTable . '`' ); 
                }
            } else {
                // compone el nombre de la tabla tempoaria
                $newTable = $origin . '_' . wrHash(10);
                $xReturn['newTable'] = $newTable;
            }
            $query = 'CREATE TABLE `' . $newTable . '` LIKE `' . $origin . '`' ;
            $this->link->query( $query );
            if( !$this->link->error )
            {
                $xReturn['created'] = true;
                if ($copyData){
                    $xReturn['inserted'] = $this->insert_select( $origin , $newTable , $filter );
                }
                $xReturn['status'] = true;
            }
        } else {
            $xReturn['msg'] = 'Error: tabla no existe o no informada';
        }
        return $xReturn;
    }


    // Insert record into $table, selecting data from $origin with $filter (optional)
    // @return int number of records inserted or false if error
    public function insert_select( $origin , $table, $filter = false , $log = false){
        if( empty($table) || empty($origin) || !$this->table_exists( $table ) || !$this->table_exists( $origin )  )
        {
            return 'error validacion tablas';       //false
        } else {
            // arma lista de campos de la tabla origen, para evitar conflicto con la PK
            // 
            $query = 'SHOW COLUMNS FROM ' . $origin;
            $resultado = $this->get_results( $query  );
            $columnas = ' 0 ';
            $i = 0;
            foreach ($resultado as $k) {
                $columnas .= ($i++) ? ' , `' . strtolower($k['Field']) . '`' : '';
            };
            //
            $query = 'INSERT INTO `' . $table . '` SELECT ' . $columnas . ' FROM `' . $origin . '`  ' ;
            $query .= ($filter)  ? ' WHERE ' . $filter : ' '; 
            $this->wrLog($query , $log);   
            $resultado = $this->link->query( $query );
            if( $this->link->error ) {
                $this->log_db_errors( $this->link->error , $query);
                return $this->link->error;
            } else {
                return $this->affected()  ;
            }
        }
    }

    private function wrLog($msg , $log = false) {
        if ($log) {
            wr::log($msg);
        }  
    }

    public function delete_wherein($origin , $table , $field , $quick = true , $optimize = true , $pid = false , $log = false) {
        if( empty($table) || empty($origin)  || empty($field) || !$this->table_exists( $table ) || !$this->table_exists( $origin )  )
        {
            return -1;       //false
        } else {
            // delete quick from $table where $field in (select distinct $field from $origin [where pid = $pid]);
            $query  = 'DELETE ' ;
            $query .= ($quick)  ? ' QUICK  ' : ' ';
            $query .= ' FROM ' . $table . ' WHERE ' . $field . ' IN (SELECT DISTINCT ' . $field . ' FROM ' . $origin ;
            if ($pid) {
                $query .= ' WHERE `pid` = ' . $pid  ;
            }
            $query .= ' )';
            $this->wrLog($query , $log);
            $resultado = $this->link->query( $query , $log );
            if( $this->link->error ) {
                $this->log_db_errors( $this->link->error , $query);
                // return $this->link->error;
                return -1;
            } else {
                $affected = $this->affected()  ;
                if ( $optimize ) {
                    $query  = 'OPTIMIZE TABLE ' . $table  ;
                    $resultado = $this->link->query( $query );
                    if( $this->link->error ) {
                        $this->log_db_errors( $this->link->error , $query);
                    }
                }    
                return $affected;
            }

        }
    }
    
    public function _help() {
        _echo ( __FILE__ . ' List of Methods and Properties: ');
        var_dump( get_class_methods($this) );
        _echo();
    }

    public function dbBackup( $folder = false , $maxRows = 500)  {
        $response = [];
        $response['status'] = 'error';
        $response['msg'] = ' ';
        $response['log'] = ' ';
        $eol = "\r\n";

        $tIni = microtime(true);

        try {
            // check parammeter destination folder
            if ($folder === false ) {
                if ( defined('BACKUP') ){
                    $folder = BACKUP;
                }
            }
            $folder = _slash($folder);

            // check if exists destination folder
            if ( !file_exists($folder) || !is_dir($folder)  ){
                throw new wrException('Folder does not exist: ' . $folder);
            }
            // delete all files from destination folder
            $files = glob($folder . '*.sql'); 
            // iterate files
            foreach($files as $file){ 
              if( is_file($file) )
                // delete file
                unlink($file); 
            }

            // statistics
            $stat = [];
            $stat['filesCount']  = 0;
            $stat['filesBytes']  = 0;
            $stat['filesDetail'] = []; 
            $bytes               = 0;

            // header of each file
            $fileHeader  = '-- Generation time: ' . date( 'D Y-m-d H:i:s P') . $eol;
            $fileHeader .= '-- Host: ' . DB_HOST . $eol;
            $fileHeader .= '-- DB Name: ' . DB_NAME  . $eol;

            $response['log'] .= $fileHeader;

            // db creation script
            $fileDescription = '-- DB Creation' ;
            $fileName        = $folder  . DB_NAME . '_1_db_create.sql';
            $h               = $this->fileCreate($fileName, $fileHeader , $fileDescription);

            $query           = 'show create database ' . DB_NAME . ' ;';
            $result          = $this->get_row( $query) ;

            $bytes = $this->fileWrite($h, $result[1] );

            fclose($h);
            unset($result);

            $response['log'] .= $fileDescription . ' - ' . $bytes . ' bytes' . $eol;

            $stat['filesCount']++;
            $stat['filesBytes'] += $bytes;
            $stat['filesDetail'][] = ['name' => $fileName , 'bytes'=>$bytes]; 

            // tables
            // 
            $query = 'SHOW FULL TABLES WHERE Table_Type = "BASE TABLE" AND Tables_in_' . DB_NAME . " LIKE '%' ;";
            // $files =  $this->get_row( $query) ;
            $tables =  $this->get_results( $query , 2) ;

            if ( count($tables) > 0 ){
                // loop table list
                $fileDescription  = '-- Table Creation' . $eol;
                $response['log'] .= $fileDescription  ;
                $response['log'] .= ' - ' . count($tables) . ' tables'  . $eol;

                foreach ($tables as $key => $table) {
                    // table creation
                    // 
                    
                    $fileName        = $folder  . DB_NAME . '_2_table_create_' . $table[0] . '.sql';
                    $h               = $this->fileCreate($fileName, $fileHeader , $fileDescription);

                    $query           = 'SHOW CREATE TABLE `' . $table[0] . '` ;';
                    $result          = $this->get_row( $query) ;
                    $bytes = $this->fileWrite($h, $result[1] );

                    fclose($h);

                    $stat['filesCount']++;
                    $stat['filesBytes'] += $bytes;
                    $stat['filesDetail'][] = ['name' => $fileName , 'bytes'=>$bytes]; 

                    // table data
                    // 
                    // calculate iterations
                    $rowsTotal = $this->count_rows( $table[0] );

                    $iteration = ceil($rowsTotal / $maxRows);
                    // determine order by clause
                    if ( $rowsTotal > $maxRows ){
                        $query = 'SHOW COLUMNS  FROM ' . $table[0] . " WHERE `Key`  = 'PRI' ;";
                        $columns = $this->get_results( $query , 2) ;
                        if ( count($columns) == 0 ) {
                            $orderby = ' ';
                        } else {
                            $orderby = ' ORDER BY ';
                            $pk      = [];
                            foreach ($columns as $column) {
                                $pk[] = $column[0];
                            }
                            $orderby .= implode(',' , $pk);
                        }
                    } else {
                        $orderby =  ' ';
                    }

                    // loop
                    for ($i=0; $i < $iteration ; $i++) { 
                        $index           = $i + 1;
                        $fileName        = $folder  . DB_NAME . '_3_table_data_' . $table[0];
                        $fileName       .= '_' . sprintf('%04d', $index) . '.sql';
                        //str_pad($value, 8, '0', STR_PAD_LEFT);
                        $fileDescription = '-- Table Data: ' . $index . ' / ' . $iteration ;
                        $h               = $this->fileCreate($fileName, $fileHeader , $fileDescription);

                        $query  = 'SELECT * from `' . $table[0] . '` ';
                        $query .= $orderby;
                        $query .= ' LIMIT ' . $maxRows . ' OFFSET ' . $i * $maxRows . ' ; ' ;

                        $bytes = $this->fileWrite($h, 'INSERT INTO `' . $table[0] . '` VALUES ' );

                        $dataRows = $this->get_results( $query , 2) ;
                        $strRows = [];
                        foreach ($dataRows as $dataRow) {
                            $strRow = '(' . implode(',' ,  $this->escapeArray($dataRow) ) . ')';
                            $strRows[] = $strRow;
                        }

                        $bytes += $this->fileWrite($h, implode(",\n", $strRows) . '; '  );

                        fclose($h);

                        $stat['filesCount']++;
                        $stat['filesBytes'] += $bytes;
                        $stat['filesDetail'][] = ['name' => $fileName , 'bytes'=>$bytes];

                        unset( $strRows);
                        unset( $dataRows);
                    }

                    $response['log'] .= $table[0]  ;
                    $response['log'] .= ' - ' . $rowsTotal . ' records'  ;
                    $response['log'] .= ' - ' . $bytes     . ' bytes'  . $eol;
                }
            }
            unset( $tables);
                
            // triggers
            // 
            $query = 'SHOW TRIGGERS in ' . DB_NAME . ' ;';

            $triggers =  $this->get_results( $query , 2) ;

            if ( count( $triggers ) > 0 ){
                $fileDescription = '-- Triggers Creation' . $eol;
                $response['log'] .= $fileDescription  ;
                $response['log'] .= ' - ' . count($triggers) . ' triggers'  . $eol;

                $fileName        = $folder  . DB_NAME . '_4_triggers.sql';
                $h               = $this->fileCreate($fileName, $fileHeader , $fileDescription);
                $bytes           = 0;
                $triggerText     = '-- ' . $eol;

                $bytesAcum = 0;
                foreach ($triggers as $key => $triggerData) {
                  
                    $triggerName    = $triggerData[0];
                    $triggerEvent   = $triggerData[1];
                    $triggerCode    = $triggerData[3];
                    $triggerTiming  = $triggerData[4];
                    $table          = $triggerData[2];

                    $triggerText   .= '-- ' . $triggerName . $eol;

                    $triggerText   .= 'DELIMITER $$ ' .$eol;
                    $triggerText   .= 'LOCK TABLES `' . $table . '` WRITE $$' .$eol;
                    $triggerText   .= 'DROP TRIGGER IF EXISTS ' . $triggerName . ' $$' .$eol;
                    $triggerText   .= 'CREATE TRIGGER ' . $triggerName  .$eol;
                    $triggerText   .= '  ' . $triggerTiming . ' ' . $triggerEvent  . $eol;
                    $triggerText   .= '   ON ' . $table . ' FOR EACH ROW' . $eol;
                    $triggerText   .= $triggerCode . ' $$' . $eol;
                    $triggerText   .= ' UNLOCK TABLES $$' . $eol;
                    $triggerText   .= ' DELIMITER ;' . $eol;

                    $triggerText   .= '-- ' . $eol;

                    $bytesTrigger   = strlen($triggerText);
                    $response['log'] .= $triggerName   ;
                    $response['log'] .= ' - ' . ($bytesTrigger - $bytesAcum)     . ' bytes'  . $eol;

                    $bytesAcum     += $bytesTrigger;

                }

                $bytes += $this->fileWrite($h, $triggerText   );

                fclose($h);
                unset( $triggerText );
                unset( $triggers );

                $stat['filesCount']++;
                $stat['filesBytes'] += $bytes;
                $stat['filesDetail'][] = ['name' => $fileName , 'bytes'=>$bytes];
            }

            // TO DO
            // 
            // views
            // store procedures
            // functions

            // make zip file
            // 
            // set zip name
            // preserve first day of month 
            date_default_timezone_set('America/Argentina/Buenos_Aires');
            setlocale(LC_TIME, "es_ES", "es_ES.utf8", 'Spanish_Spain', 'Spanish' , 'spa' , 'es');
            define("CHARSET", "iso-8859-1");

            if ( '1' == date( 'j') ){
                $fileName = $folder  . DB_NAME . '_' . utf8_encode(strftime("%B")) . '_' .  date( 'A') .  '.zip';
            } else {
                $fileName = $folder  . DB_NAME . '_' . utf8_encode(strftime("%A")) . '_' .  date( 'A') .  '.zip';
            }
            $replaceArray = [ 'á'=>'a' , 'é'=>'e', 'í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n'];
            $fileName = str_replace(array_keys($replaceArray), array_values($replaceArray), $fileName);
 
            // list all files
            $files = glob($folder . '*.sql');            
            // create zip file
            // 
            $zip = new \ZipArchive();
            $zip->open($fileName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            
            // iterate files
            foreach($files as $file){   
                if( is_file($file) & !is_dir($file) ){
                    // Get real and relative path for current file
                    $filePath = realpath($file);
                    // $relativePath = substr($filePath, strlen($rootPath) + 1);
                    $relativePath = substr($filePath, strlen($folder) );          
                    // Add current file to archive
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();

            // delete all .sql files
            array_map('unlink', glob($folder . '*.sql'));
            $response['zipFile'] = $fileName;
            $response['status'] = 'ok';
            $response['stat'] = $stat ;

            $response['log'] .= '** End Zip File: ' . $fileName   . $eol;

        } catch ( wrException $e ) {
            $response['msg'] =  $e->getMessage() ;
        }
        return $response;
    }
    
    /**
     * Clone a specific row
     *
     * Example usage:
     * $rv = $database->cloneRow( 'test' , 123 , 'id'  );
     *
     * @access public
     * @param string    -> table name
     * @param int       -> pk value of the souce row
     * @param string    -> name of the PK field
     * @param bool      -> save audit information
     * @param bool      -> log operation (for debug)
     * @return array
     *
     */

    public function cloneRow( $table = false , $pkValue = false , $pkName = 'id' , $audit = false , $log = false){
        $rv = [
            'status' => false , 'msg' => ' ' , 'log' => ' '
        ];

        
        $tIni = microtime(true);
        try { 
            self::$counter++;
            $eol = "\r\n"; 
            if( empty($table) || empty($pkValue)  ){
                throw new \Exception('Parametros faltantes');
            }
            // verifica que exista la tabla
            if ( !$this->table_exists( $table ) ){
                throw new \Exception('Tabla inexistente');
            }
            // verifica que exista la fila a clonar
            if ( false === $this->exists( $table , $pkName ,  array($pkName => $pkValue) ) ){
                throw new \Exception('Fila Origen  inexistente');
            }

            // lee los nombre columnas
            // 
            $query = 'SHOW COLUMNS FROM ' . $table;
            $resultado = $this->get_results( $query  );
            //crea un array asociativo vacio, con todos los campos de la tabla
            $dbFields = array();
            $i = 0;
            foreach ($resultado as $k) {
                if ( 'timestamp' == strtolower($k['Type']) && 'current_timestamp()' == strtolower($k['Default']) ){
                    continue;
                } 
                if ( strtolower($pkName) == strtolower($k['Field']) ){
                    continue;
                } 
                /*                              
                $colData = array(
                      'name' =>  strtolower($k['Field']) 
                    , 'type'  => strtolower($k['Type'])
                    , 'size'  => getStringBetween($k['Type'],'(',')')

                );
                $dbFields[strtolower( $k['Field'] )] = $colData;
                */
                $dbFields[] =  $k['Field'] ;
                $i++;
            }
            unset($resultado  );

            // busca indices unicos
            $query = 'SHOW INDEX FROM ' . $table;
            $resultado = $this->get_results( $query  ); 
            foreach ($resultado as $k) {
                if ( strtolower($pkName) !== strtolower($k['Column_name']) and empty($k['Non_unique'])){
                    throw new \Exception('Imposible clonar una tabla con indices únicos');
                }                               
            }
            unset($resultado  );

            // compone lista de campos
            $fields = implode(",", $dbFields) ;

            // clona fila
            //INSERT INTO items (name,unit) SELECT name, unit FROM items WHERE id = '9198' 
            $query = 'INSERT INTO ' . $table  ;
            $query .= '(' . $fields . ')';
            $query .= 'SELECT ' . $fields . ' FROM ' . $table ;
            $query .= ' WHERE ' . $pkName . ' = ' . $pkValue; 

            $this->wrLog($query , $log);        //registra log
            $rv['query'] = $query;

            $result = $this->link->query( $query );
            if( $this->link->error ){
                //return false; 
                $linkError = $this->link->error;
                $this->log_db_errors( $linkError, $query );
                throw new \Exception($linkError);
                // return false;
            } else {
                $rv['status']  = true;
                $rv['msg'] = 'ok';
            }

            // get new record id
            $rv['id'] = $this->link->insert_id;

            // record audit info
            $this->audit(  __METHOD__ , $query ,  $rv['msg'] , $audit ); 

        } catch (\Exception $e) {
           $rv['msg'] = $e->getMessage();
        }
        // the end
        return $rv;
    }

    // private functions

    private function fileCreate($fileName , $fileHeader , $fileDescription){
        // open / create file
        $h = fopen($fileName, 'w'); 
        if ( false === $h ) {
            throw new wrException('Error creating file: ' . $fileName);
        }
        // write header
        $this->fileWrite( $h , $fileHeader );
        $this->fileWrite( $h , $fileDescription);
        // return file handler
        return $h;
        
    }

    private function fileWrite($h , $text ){
        $eol = "\r\n";
        $result  = fwrite($h, $text . $eol );
        if ( false === $result ){
            throw new wrException('Error writing to file.');
        }
        return  $result;
    }

    public function escapeArray($data) {
        if( !is_array( $data ) ){
            if ( is_null($data) ){
                return "NULL";
            }
            return "'" . $this->link->real_escape_string($data) . "'";
        } else  {
            //Self call function to sanitize array data
            $data = array_map( array( $this, 'escapeArray' ), $data );
        }
        return $data;
    }


} //end class DB

// class wrException extends Exception {};