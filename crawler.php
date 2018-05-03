<?php 

$start = "http://localhost/search_eng/sample.html";

$pdo = new PDO('mysql:host=localhost;dbname=mysearch','root','');


//2 global arrays for the link to be crawled
$already_crawled = array(); 
$crawl = array();

function get_details($url)
{
	$options = array('http'=>array('method'=>"GET", 'headers'=>"User_Agent: asBot/0.1"));

	$context = stream_context_create($options);

	$doc = new DOMDocument();
	@$doc->loadHTML(@file_get_contents($url, false, $context));

	//for getting the title of the page
	$title = $doc->getElementsByTagName("title");
	$title = $title->item(0)->nodeValue;
	//echo $title.'<br \>';

	//getting the meta tag
	$description ="";
	$keywords ="";
	$metas = $doc->getElementsByTagName("meta");
	for($i =0; $i < $metas->length; $i++ )
	{
		$meta = $metas->item($i);

		//only if the meta tag is descriptor
		if($meta->getAttribute("name") == strtolower("description"))
		{
			$description = $meta->getAttribute("content");
		}

		if($meta->getAttribute("name") == strtolower("keywords"))
		{ 
			$keywords = $meta->getAttribute("content");
		}
	}

	return '{"Title": "'.str_replace("\n", "", $title).'", "Description": "'.str_replace("\n", "", $description).'", "Keywords": "'.str_replace("\n", "", $keywords).'", "URL": "'.$url.'"}';

}

function follow_links($url)
{

	global $already_crawled;
	global $crawl;
	global $pdo;

	//changing the user agent
	$options = array('http'=>array('method'=>"GET", 'headers'=>"User_Agent: asBot/0.1"));

	$context = stream_context_create($options);

	$doc = new DOMDocument();
	@$doc->loadHTML(@file_get_contents($url,false, $context));

	$linklist =$doc->getElementsByTagName("a");

	foreach($linklist as $link)
	{
		$l = $link->getAttribute("href");


		//processing links

		if(substr($l, 0,1) == "/" && substr($l, 0,2) != "//")
		{
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].$l;
		}
		else if(substr($l, 0,2) == "//")
		{
			$l = parse_url($url)["scheme"].":".$l;
		}

		else if(substr($l, 0,2) == "./")
		{
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].dirname(parse_url($url)["path"]).substr($l,1);
		}
		else if(substr($l, 0,1) == "#")
		{
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].parse_url($url)["path"].$l;
		}
		else if(substr($l,0,3) == "../")
		{
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"]."/".$l;
		}
		else if(substr($l,0,11) == "javascript:")
		{
			continue;
		}
		else if(substr($l, 0,5) != "https" && substr($l,0,4) != "http")
		{
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].dirname(parse_url($url)["path"])."/".$l;
		
		}

		if(!in_array($l, $already_crawled))
		{
			$already_crawled[] = $l;
			$crawl[] = $l;
			//echo get_details($l)."\n" ;

			$details = json_decode(get_details($l));

			//print_r($details)."\n";
			//echo md5($details->URL);

			echo $details->URL."<br />";

			$rows = $pdo->query("SELECT * FROM `search_index` WHERE url_hash='".md5($details->URL)."'");
			$rows = $rows->fetchColumn();
			
			$params = array(':title' => $details->Title, 'description' => $details->Description, ':keywords' => $details->Keywords, ':url' => $details->URL, ':url_hash' => md5($details->URL));

			if($rows > 0)
			{
				if(!is_null($params[':title']) && !is_null($params[':description'])  && $params[':title'] != '')
				{
				$result = $pdo->prepare("UPDATE `search_index` SET title= :title, description= :description, keywords= :keywords, url =:url, url_hash = :url_hash WHERE url_hash = :url_hash");
				$result = $result->execute($params);
				}
			}
			else
			{

				if(!is_null($params[':title']) && !is_null($params[':description'])  && $params[':title'] != '')
				{
				$result = $pdo->prepare("INSERT INTO `search_index` (`id`, `title`, `description`, `keywords`, `url`, `url_hash`) VALUES('', :title, :description,  :keywords, :url, :url_hash)");
				$result = $result->execute($params);
				}
			}
		}
	}

	//to avoid going back to the same link
	//removing the first link from $crawl
	array_shift($crawl);

	//for continue crawling indefinetly
	foreach( $crawl as $site)
	{
		follow_links($site);
	}
}

follow_links($start);


?>