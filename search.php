<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=mysearch', 'root', '');

$search = $_GET['q'];

$searche = explode(" ". $search);


$x = 0;
$construct = "";
foreach($searche as $term){

	$x++;
	if($x ==1){

		$construct .= "title LIKE '%$term%' OR  description LIKE '%$term%'  keywords LIKE '%$term%'"
	}
	else{

		$construct .= " AND title LIKE '%$term%' OR  description LIKE '%$term%'  keywords LIKE '%$term%'";
	}
}

$results = $pdo->query("SELECT * FROM `index` WHERE $construct ");

if($results->rowCount()  == 0){

	echo "0 results found! <hr />";
}
else{

	echo $results->rowCount()." results found! <hr />"
}


foreach ($results->fetchAll() as $result)
{
	echo $result["title"]."<br />";

	if($result["description"] == "")
	{
		echo "No description available";
	}
	else{
	echo $result["description"]."<br />";
	}
	echo $result["url"]."<br />";
	echo "<hr />";
}