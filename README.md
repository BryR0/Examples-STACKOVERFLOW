# Examples Export databases


Note
============
export all database or specific squema


Usage
=====
database export

database export/import 1 schema
```php
//export 1 schema in file dbtest.sql
$conn = GestionDB::getDB();
if($conn->Backup("dbtest.sql","dbtest")){
	echo "backup creado";
}
//import 1 schema in file dbtest
if(	$conn->Restore("dbtest.sql","dbtest")){
	echo "databases recreadas!".$r;
}
```

export/import all database schema

```php
//export all schema
$conn = GestionDB::getDB();
if($conn->Backup("alldb.sql")){
	echo "backup creado";
}

//import all dbs
if(	$conn->Restore("alldb.sql")){
	echo "databases recreadas!".$r;
}
```
