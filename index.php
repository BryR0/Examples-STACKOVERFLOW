<?php 

class GestionDB{

   private $datos = array(
					      "host" => "localhost", 
					      "user" => "username",
					      "pass" => "password"
					    );
   	private $dbe = ["information_schema","mysql","performance_schema","phpmyadmin"];
   	private $dbs;
    private static $_mysqli;
    private static $instancia;


    private function __construct(){

     try{

        self::$_mysqli = new \mysqli($this->datos['host'],$this->datos['user'], $this->datos['pass']);
     
      if (self::$_mysqli->connect_error) {
            throw new Exception('Connect Error ' . self::$_mysqli->connect_errno . ': ' . self::$_mysqli->connect_error, self::$_mysqli->connect_errno);
        }

      }catch(MysqliEXception $e){}
    }

     public static function getDB(){
      if (!isset(self::$instancia)) {
            $miclase = __CLASS__;
            self::$instancia = new $miclase;
        } 
      return self::$instancia;
    }


    public function CS($sql){

      $consulta = self::$_mysqli->query($sql);
      
      if (!$consulta)
          die(self::$_mysqli->error);

      return true;

    }

    public function CM($sql){
    	$consulta = self::$_mysqli->multi_query($sql);
    	if(!$consulta)
    		die(self::$_mysqli->error);
      return true;
    }

    public function CR($sql){
      $consulta = self::$_mysqli->prepare($sql);

      if (!$consulta)
          die(self::$_mysqli->error);

      if ($consulta->execute())
        return $consulta->get_result();
      else
        die(self::$_mysqli->error);

      $consulta->close();
    }

    public function Restore($sqlfilename){

 		self::CD($sqlfilename);
		// si todo va bien leemos el archivo
		$sql = file_get_contents($sqlfilename);
		return self::CM($sql);
    }

    public function CD($file){
    	// validamos que exista el archivo
		
		if (!file_exists($file)) {
			die("no existe la ruta o el archivo");
		}

		// validamos que sea legible
		if (!is_readable($file)) {
			die("no se puede leer la ruta o el archivo!");
		}

		return true;
    }


    public function DropDB(){

		foreach ($this->dbs as $db) {
			self::CS('DROP DATABASE '.$db.";");
		}
		return true;
    }

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
		    $result = self::CR('SHOW CREATE DATABASE '.$db);
		    $row = $result->fetch_row();
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

    public function __destruct(){
      self::$_mysqli->close();
    }

  }


$conn = GestionDB::getDB();


if($conn->Backup()){
	echo "backup creado!<br>";
}

if($conn->DropDB()){
	echo "databases eliminadas!<br>";

}

if(	$conn->Restore("dumpsql-190126.sql")){
	echo "databases recreadas!<br>";
}

?>