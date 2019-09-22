<?php
$errMessage = '';
/*if ($db = sqlite_open('testdb', 0755, $errMessage)) {
    sqlite_query($db, 'CREATE TABLE test (id integer, `name` varchar(50), family varchar(50), country varchar(50), town varchar(50))');
    
    $arData = json_decode(file_get_contents('base.json'));
    foreach ($arData as $arLine) {
        //sqlite_query($db, "INSERT INTO test VALUES ()");

    }
    
    
    
    /*sqlite_query($db, "INSERT INTO foo VALUES ('fnord')");
    $result = sqlite_query($db, 'select bar from foo');
    var_dump(sqlite_fetch_array($result));*/
/*} else {
    die($sqliteerror);
}*/

//echo sqlite_libversion();

$dbf = new \PDO('sqlite:'.__DIR__.'/testdb.sq3');
echo '<pre>';
if ($dbf) {
    $arData = json_decode(file_get_contents('base.json'), true);

    var_dump($dbf->exec('CREATE TABLE IF NOT EXISTS test (id integer, `name` varchar(50), family varchar(50), country varchar(50), town varchar(50))'));

    $statement = $dbf->prepare("INSERT INTO test VALUES (:id, :name, :family, :country, :town)");    

    foreach ($arData as $arLine) {
        print_r(array_values($arLine));
        
        var_dump($statement->execute($arLine));
        
        $statement->debugDumpParams();


        
    }
}


//$db = new SQLite3('analytics.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);


phpinfo();
