<?php 

class GestionDB{

   private $datos = array( // conexion credentials
					      "host" => "localhost", 
					      "user" => "username",
					      "pass" => "password"
					    );
   	private $dbe = ["information_schema","mysql","performance_schema","phpmyadmin"]; //database exclude
   	private $dbs;  // databases select
    private static $_mysqli; // mysqli
    private static $instancia; // singleton
    private $interface; // interface type
    private $msg_errors = ["web" => "<b><p style='color:red;'> %s </p></b><br>", "cli" => "%s\n" ]; // print errors

    private function __construct(){

     try{
     	 $this->interface = php_sapi_name() == 'cli' ? "cli" : "web"; //detect sapi
        self::$_mysqli = new \mysqli($this->datos['host'],$this->datos['user'], $this->datos['pass']); // instance datatabase
     
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

    // function simple query
    public function CS($sql){

      $consulta = self::$_mysqli->query($sql);
      
      if (!$consulta)
          die(sprintf($this->msg_errors[$this->interface],self::$_mysqli->error));

      return true;

    }

    // function multople query!
    public function CM($sql){
    	$consulta = self::$_mysqli->multi_query($sql);
    	if(!$consulta)
    		die(sprintf($this->msg_errors[$this->interface],self::$_mysqli->error));
      return true;
    }

    // function query return
    public function CR($sql){
      $consulta = self::$_mysqli->prepare($sql);

      if (!$consulta)
          die(sprintf($this->msg_errors[$this->interface],self::$_mysqli->error));

      if ($consulta->execute())
        return $consulta->get_result();
      else
        die(sprintf($this->msg_errors[$this->interface],self::$_mysqli->error));

      $consulta->close();
    }

    // restore databasess
    public function Restore($sqlfilename){

    	// check file name
 		self::CD($sqlfilename);

		// load file
		$sql = file_get_contents($sqlfilename);
		//execute file
		return self::CM($sql);
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

    // drop databases only example
    public function DropDB(){

		foreach ($this->dbs as $db) {
			self::CS('DROP DATABASE '.$db.";");
		}
		return true;
    }

    // backup databases;
    public function Backup($dbs = '*', $sqlfilename=false){
      
	      $return="";

	      if($sqlfilename == false){
	      	 $sqlfilename = "dumpsql-".date('ymd').".sql";
	      }

	      //all tables
	      if($dbs == '*'){
	        $dbs = array();
	        $result = self::CR('SHOW DATABASES');
	        while($row = $result->fetch_row()){
	          if(!in_array($row[0], $this->dbe))
	          	$dbs[] = $row[0];
	        }
	      }
	      else{
	        $dbs = is_array($dbs) ? $dbs : explode(',',$dbs);
	      }

	    $this->dbs=$dbs;
	    foreach($dbs as $db){

	    	$tables = array();
		    $return.= "CREATE DATABASE IF NOT EXISTS ". $db.";\n";
		    $return.= "USE ". $db.";\n";
		    $result = self::CR('SHOW TABLES IN '.$db);

		    while($row = $result->fetch_row()){
		    	$tables[]= $row[0];
		    }
		    foreach($tables as $table){
		        $result = self::CR('SELECT * FROM '. $db. '.'.$table);
		        $num_fields = $result->field_count;
		        
		        $return.= 'DROP TABLE IF EXISTS '.$db.'.'.$table.';';
		        $qr=self::CR('SHOW CREATE TABLE '.$db.'.'.$table);
		        $row2 = $qr->fetch_row();
		        $return.= "\n\n".$row2[1].";\n\n";
		      
		        for ($i = 0; $i < $num_fields; $i++) {
		          while($row = $result->fetch_row()){
		            $return.= 'INSERT INTO '.$db.'.'.$table.' VALUES(';
		            for($j=0; $j < $num_fields; $j++) {
		              $row[$j] = addslashes($row[$j]);
		              $row[$j] = preg_replace("#\n#","\\n",$row[$j]);
		              if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
		              if ($j < ($num_fields-1)) { $return.= ','; }
		            }
		            $return.= ");\n";
		          }
		        }
		        $return.="\n\n\n";
		    }
	    }

      //save file
      $handle = fopen($sqlfilename,'w+');
      fwrite($handle,$return);
      fclose($handle);

      return true;
    }

    // destruct mysqli instance
    public function __destruct(){
      self::$_mysqli->close();
    }

  }

$r = php_sapi_name()=="cli" ? "\n" : "<br>";

$conn = GestionDB::getDB();

if($conn->Backup("*","databases.sql")){
	echo "backup creado".$r;
}

if($conn->DropDB()){
	echo "databases eliminadas".$r;
}

if(	$conn->Restore("dumpsql-19012s6.sql")){
	echo "databases recreadas!".$r;
}

?>