<?php 

class GestionDB{

   // conexion credentials
   private $datos = array( 
                "host" => "localhost", 
                "user" => "username",
                "pass" => "password"
              );

    //esclude databases squema
    private $dbe = ["information_schema","mysql","performance_schema","phpmyadmin"];
    
    // databases select
    private $dbs;

    // mysqli instance
    private static $_mysqli;

    // singleton instance
    private static $instancia;

    // sapi interface type
    private $interface; 

    // print format errors
    private $msg_errors = ["web" => "<b><h1 style='color:red;'> %s </h1></b><br>", "cli" => "%s\n" ]; 

    private function __construct($database=false){
      //detect sapi
      $this->interface = php_sapi_name() == 'cli' ? "cli" : "web";

     try{
      // instance datatabase
      self::$_mysqli = new \mysqli($this->datos['host'],$this->datos['user'], $this->datos['pass']); 
     
      if (self::$_mysqli->connect_error) {
            throw new Exception(sprintf($this->msg_errors[$this->interface],'Connect Error ' . self::$_mysqli->connect_errno . ': ' . self::$_mysqli->connect_error, self::$_mysqli->connect_errno));
        }

      }catch(MysqliEXception $e){}
    }

    //instance singleto 
     public static function getDB(){
      if (!isset(self::$instancia)) {
            $miclase = __CLASS__;
            self::$instancia = new $miclase;
        } 
      return self::$instancia;
    }

    // function simple query no return -> insert,delete,upgrade
    public function CS($sql){

      $consulta = self::$_mysqli->query($sql);
      
      if (!$consulta)
          die(sprintf($this->msg_errors[$this->interface],self::$_mysqli->error));

      return true;

    }

    // function multiple query -> insert
    public function CM($sql){
      $consulta = self::$_mysqli->multi_query($sql);
      if(!$consulta)
        die(sprintf($this->msg_errors[$this->interface],self::$_mysqli->error));
      return true;
    }

    // function query return -> select
    public function CR($sql){
      $consulta = self::$_mysqli->prepare($sql);

      if (!$consulta)
          die(sprintf($this->msg_errors[$this->interface],self::$_mysqli->error));

      if ($consulta->execute())
        return $consulta->get_result();
      else
        die(sprintf($this->msg_errors[$this->interface],self::$_mysqli->error));
    }


    //validate file
    private function CD($file){
    
    //validate exists
    if (!file_exists($file)) {
      die(sprintf($this->msg_errors[$this->interface],"no existe la ruta o el archivo!"));
    }

    // validate Permissions
    if (!is_readable($file)) {
      die(sprintf($this->msg_errors[$this->interface],"no se puede leer la ruta o el archivo!"));
    }

    return true;
    }

    // drop databases only test script
    public function DropDB($databases=false){
      if(!$databases){
      foreach ($this->dbs as $db) {
        self::CS('DROP DATABASE '.$db.";");
      }
    }else{

      $dbs = is_array($databases) ? $databases : explode(',',$databases);
      foreach ($dbs as $db) {
        self::CS('DROP DATABASE '.$db.";");
      }
    }
    return true;
    }

    // restore databasess 
    public function Restore($filename,$database=false){

      // restore specific database;
      if($database){
        self::CS("CREATE DATABASE IF NOT EXISTS ".$database);
        self::CS("use ".$database);
      }

      // check file name
    self::CD($sqlfilename);

    // load file
    $sql = file_get_contents($filename);

    //execute file
    return self::CM($sql);
    }

    // backup databases;
    public function Backup($filename=false,$databases = '*'){

        //all databases
        if($databases == '*'){
          $dbs = array();
          $result = self::CR('SHOW DATABASES');
          while($row = $result->fetch_row()){
            if(!in_array($row[0], $this->dbe))
              $dbs[] = $row[0];
          }
        }
        else{
          $dbs = is_array($databases) ? $databases : explode(',',$databases);
        }

        if(empty($dbs))
          die(sprintf($this->msg_errors[$this->interface],"ups! 0 databases select for export!"));

        // filename is set no rewrite file if database is 2 or more 
        if(count($dbs) > 1 && $filename){
            $filename = str_replace(".sql", "-%s.sql",$filename);
        }

        // default export filename
        if($filename== false){
            $filename = "dump-".date('ymd')."-%s.sql";
        }

      $this->dbs=$dbs;

      foreach($dbs as $db){

        // header file
        $ip = $_SERVER['SERVER_ADDR']!='::1' ? $_SERVER['SERVER_ADDR'] : '127.0.0.1';
        $return = "-- php export database\n";
        $return .= "-- version 1.4\n";
        $return .= "-- Servidor: {$ip}\n";
        $return .= "-- Tiempo de generación: ".date('d-m-Y')." a las ".date('H:i:s')."\n";
        $return .= "-- Versión del servidor: ".self::CR('SELECT VERSION()')->fetch_row()[0]."\n";
        $return .= "-- Versión de PHP: ".phpversion()."\n\n\n";
        $return .= "--\n-- Base de datos: ${db}\n--\n";

        $tables = array();

        $result = self::CR("SHOW TABLES IN ${db}");

        //all tables
        while($row = $result->fetch_row()){
          $tables[]= $row[0];
        }

            foreach($tables as $table){
                $result = self::CR("SELECT * FROM ${db}.${table}");
                $num_fields = $result->field_count;
                
                $return.="-- --------------------------------------------------------\n";
                $return.="-- Estructura de tabla para la tabla  ${table}\n";
                $return.="-- --------------------------------------------------------\n\n";

                $return.= "DROP TABLE IF EXISTS ${table};";
                $qr=self::CR("SHOW CREATE TABLE ${db}.${table}");
                $row2 = $qr->fetch_row();
                $return.= "\n\n".$row2[1].";\n\n";
              
                for ($i = 0; $i < $num_fields; $i++) {
                  while($row = $result->fetch_row()){
                    $return.= "INSERT INTO ${table} VALUES(";
                    for($j=0; $j < $num_fields; $j++) {
                      $row[$j] = addslashes($row[$j]);
                      $row[$j] = preg_replace("#\n#","\\n",$row[$j]);
                      if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                      if ($j < ($num_fields-1)) { $return.= ','; }
                    }
                    $return.= ");\n";
                  }
                }

            }

        //save file
        $return.="\n\n\n";
        $handle = fopen(sprintf($filename,$db),'w+');
        fwrite($handle,$return);
        fclose($handle);
      }

      return true;
    }

    // destruct mysqli instance
    public function __destruct(){
      self::$_mysqli->close();
    }

  }

?>