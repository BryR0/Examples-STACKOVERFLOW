# Examples Export databases


Note
============
export all database or specific schema


Usage
=====
database export

database export/import 1 schema
```php
//export 1 schema in file dbtest.sql
$conn = GestionDB::getDB();
if($conn->Backup("dbtest.sql","dbtest")){
	echo "backup create!";
}
//import 1 schema in file dbtest
if(	$conn->Restore("dbtest.sql","dbtest")){
	echo "databases recreate!".$r;
}
```

export/import all database schema

```php
//export all schema
$conn = GestionDB::getDB();
if($conn->Backup("alldb.sql")){
	echo "backup create!";
}

//import all dbs
if(	$conn->Restore("alldb.sql")){
	echo "databases recreate!".$r;
}
```
