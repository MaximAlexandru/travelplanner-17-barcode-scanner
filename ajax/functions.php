<?php
	$lang = (defined("LANG") ? LANG : "ro");

	defined("AREA_ID") ? "" : define("AREA_ID", 1);

	function base_url()
	{
		$base_url =  "http://".SERVER_PATH;
	
		if(defined("LANG") && LANG != 'ro' )
		{
			$base_url = $base_url.LANG.'/';
		}
		
		return $base_url;
	}
function get_video_details($url){
 $image_url = parse_url($url);
 $host = $image_url['host'];

 if($image_url['host'] == 'www.youtube.com' || $image_url['host'] == 'youtube.com')
 {
 $id= getPatternFromUrl($url);
 $url = "http://gdata.youtube.com/feeds/api/videos/".$id."?v=2&alt=json";
 $ch = curl_init($url); 
 curl_setopt($ch, CURLOPT_URL, $url); 
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  


 $output = curl_exec($ch); 
 curl_close($ch);  

$result = json_decode($output, true);

$data = array(
	'video_id'=>$id,
	'video_title' => $result['entry']['title'],
	'video_details'=>$result['entry']['media$group']['media$description']['$t'],
	'video_duration'=> gmdate("i:s", $result['entry']['media$group']['media$content']['0']['duration']),
	'video_thumbnail_big'=>$result['entry']['media$group']['media$thumbnail']['1']['url'],
	'video_thumbnail'=>$result['entry']['media$group']['media$thumbnail']['0']['url']
	);
}
elseif ($image_url['host'] == 'www.vimeo.com' || $image_url['host'] == 'vimeo.com') {

	 $hash = unserialize(file_get_contents("http://vimeo.com/api/v2/video/".substr($image_url['path'], 1).".php"));
	$data = array(
	'video_id'=>$hash[0]['id'],
	'video_title' => $hash[0]['title'],
	'video_details'=>$hash[0]['description'],
	'video_duration'=> gmdate("i:s", $hash[0]['duration']),
	'video_thumbnail_big'=>$hash[0]['thumbnail_medium'],
	'video_thumbnail'=>$hash[0]['thumbnail_small']
	);

	
}
return $data;
 
}

function number_of_comments($type,$id){
	global $connection;
	$nr=$connection->num_rows("SELECT id from comments WHERE ".$type."_id = ?",array($id));
	return $nr;

}


function get_vimeo_details($url){
	
	 echo $image_url['host'];
	
	
}

function getPatternFromUrl($url)
{
$url = $url.'&';
$pattern = '/v=(.+?)&+/';
preg_match($pattern, $url, $matches);
//echo $matches[1]; die;
return ($matches[1]);
}


function base_url_scripts($serverPath = false)
{
	if($serverPath == false)
	{
		$serverPath = SERVER_PATH;
	}
	$base_url =  "http://".$serverPath;
	return $base_url;
}
function generate_title ( $string, $additional = "" )
{
	return ucwords($string).' '.$additional.' | brasovtour.com ';
}
function generate_meta ( $string )
{
	$string = htmlentities(trim(strip_tags($string))).", Brasov";
	
	return $string;
}
function generate_keywords($string, $minLetters = 4)
{
	$string = strip_tags($string);
	$string = preg_replace('/[^\da-z]/i', ' ', $string);
	$keyword_arr = explode(" ",$string);
	$keywords = "";
	foreach($keyword_arr as $value)
	{
		$n = strlen($value);
		if($n >= $minLetters)
		{
			$strip_arr = array(",",".",";",":");
			if($keywords != "")
			{
				$keywords .= ",";
			}
			$keywords .= str_replace($strip_arr,"",$value);
		}
	}
	return strtolower($keywords);
}
function getRequestParameter($param)
{
	if(isset($_POST[$param]))
	{
		return $_POST[$param];
	}

	if(isset($_GET[$param]))
	{
		return $_GET[$param];
	}
	
	if(isset($_REQUEST[$param]))
	{
		return $_REQUEST[$param];
	}

	return false;
}

function generate_node_breadcrumbs($node)
{
	$breadcrumbs =  array();

	$categoryURL = base_url();
	if (@$node['cats']['super_cats'][0])
	{
		$categoryURL .= $node['cats']['super_cats'][0]['url_title'];
		$breadcrumbs[] = array(
			"name"=>$node['cats']['super_cats'][0]['name'],
			"link"=>$categoryURL
			);
	}
	if (@$node['cats']['cats'][0] )
	{
		$categoryURL .= "/".$node['cats']['cats'][0]['url_title'];
		$breadcrumbs[] = array(
			"name"=>$node['cats']['cats'][0]['name'],
			"link"=>$categoryURL
			);
	}
	if (@$node['cats']['sub_cats'][0] )
	{
		$subCategoryURL = $categoryURL."/".$node['cats']['sub_cats'][0]['url_title']."/";
		$breadcrumbs[] = array(
			"name"=>$node['cats']['sub_cats'][0]['name'],
			"link"=>$subCategoryURL
			);
	}
	
	$nodeURL = $node["front_end_link"];

	$breadcrumbs[] = array(
			"name"=>$node['name'],
			"link"=>$nodeURL
		);
	return $breadcrumbs;
}

function rand_string($length = 5) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}


function get_client_id()
{
	if (!isset($_SESSION['client_id']))
	{
		$_SESSION['client_id'] = time();
		$_SESSION['temp_client'] = true;
	}

	return $_SESSION['client_id'];
}

function get_client($clientID = false)
{
	global $connection;
	
	$client = false;
	
	if(!$clientID && isset($_SESSION["client_id"]))
	{
		$clientID = $_SESSION["client_id"];
	}
	
	if($clientID)
	{
		$client = $connection->fetch_row("clients", $clientID);
		if($client)
		{
			$client["delivery_addresses"] = $connection->fetch_result("SELECT * FROM client_delivery_addresses WHERE client_id = ?", array($client["id"]));
			
			$client["newsletter_subscriptions"] = getNewsletterSubscriptions($client["email"], $client);
		}
	}
	return $client;
}

function getNewsletterSubscriptions($email, $client)
{
	global $connection;
	
	$newsletter_user = false;
	
	$client_id = $client ? $client["id"] : null;
	$city = $client ? $client["city"] : "";
	
	if($client)
	{
		$newsletter_user = $connection->fetch_one("SELECT * FROM newsletter_subscribers WHERE client_id = ?", array($client["id"]));
	}
	
	if(!$newsletter_user)
	{
		$newsletter_user = $connection->fetch_one("SELECT * FROM newsletter_subscribers WHERE email = ?", array($email));
	}
	
	if(!$newsletter_user)
	{
		$newsletter_user["client_id"] = $client_id;
		$newsletter_user["name"] = $client["name"];
		$newsletter_user["email"] = $client["email"];
		$newsletter_user["status"] = 1;
		$newsletter_user["abonat"] = time();
		$newsletter_user["brasovean"] = (trim(strtolower($client["city"])) == "brasov" ? 1 : 0);
		$subscriberID = $newsletter_user["id"] = $connection->insert("newsletter_subscribers", $newsletter_user);
		
		$superCategories = $connection->fetch_result("SELECT * FROM categories WHERE parent_id = 0 AND id != 6");
		$values = array();
		foreach($superCategories as $category)
		{
			$subCategories = $connection->fetch_result("SELECT * FROM categories WHERE parent_id = ? AND id != 6", array($category["id"]));
			
			$values[] = " ({$subscriberID}, {$category["id"]}) ";
			
			$newsletter_user["subscriptions"][]["category_id"] = $category["id"];
			
			$newsletter_user["subscriptions"][$category["id"]]["category_id"] = $category["id"];
			$newsletter_user["subscriptions"][$category["id"]]["active"] = 1;
			
			foreach($subCategories as $subCat)
			{
				$values[] = " ({$subscriberID}, {$subCat["id"]}) ";
				$newsletter_user["subscriptions"][$subCat["id"]]["category_id"] = $subCat["id"];
				$newsletter_user["subscriptions"][$subCat["id"]]["active"] = 1;
			}
		}
		if(count($values) > 0)
		{
			$command = "INSERT INTO newsletter_subscriber_categories (subscriber_id, category_id) VALUES ";
			$command .= implode(",", $values);

			$connection->execute_query($command, array());
		}
	}
	else
	{
		$subscriberID = $newsletter_user["id"];
		if($client_id != null && $client_id != $newsletter_user["client_id"])
		{
			$upSubscribers["id"] = $newsletter_user["id"];
			$upSubscribers["client_id"] = $newsletter_user["client_id"] = $client_id;
			$connection->update("newsletter_subscribers", $upSubscribers);
		}
		
		$newsletter_user["subscriptions"] = $connection->fetch_result("SELECT category_id, active FROM newsletter_subscriber_categories WHERE subscriber_id = ?", $subscriberID);
	}
	
	return $newsletter_user;
		
}

function set_client_id( $client_id )
{
	global $connection;
	
	$temp_client_id = get_client_id();

	$params[] = $client_id;
	$params[] = $temp_client_id;
	
	$connection->execute_query("UPDATE cart SET client_id = ? WHERE temp_client_id = ?",$params);
	$connection->execute_query("UPDATE seat_reservations SET client_id = ? WHERE temp_client_id = ?",$params);
	$connection->execute_query("UPDATE table_reservations SET client_id = ? WHERE temp_client_id = ?",$params);
	$connection->execute_query("UPDATE comments SET client_id = ? WHERE temp_client_id = ?",$params);
	
	$connection->execute_query("UPDATE messages SET to_client_id = ? WHERE to_temp_client_id = ?",$params);
	
	$_SESSION['temp_client'] = false;
	$_SESSION['client_id'] = $client_id;

	$client = get_client();

	if ( $client['language'] )
	{
		$_SESSION['lang'] = $client['language'];
	}
	
	$up["id"] = $client_id;
	$up["last_login"] = time();
	
	$connection->update("clients", $up);
}

function get_node_by_id($ids, $default_all = false, $lang = false, $show_inactive = false)
{
	global $connection;
	if (!$lang )
	{
		$lang = defined("LANG") ? LANG : (isset($_SESSION["lang"]) ? $_SESSION["lang"] : "ro") ;
	}

	$image_folder = 'http://www.brasovtour.com/pictures/';
	$thumb_image_folder = "http://www.brasovtour.com/picture_thumbs/";
	//$image_folder = base_url().'pictures/';
	//$thumb_image_folder = base_url().'picture_thumbs/';


	if ( !is_array($ids))
	{
		$ids_arr = array($ids);
	}
	else
	{
		$ids_arr = $ids;
	}

	$nodes_where_arr = array();
	$picture_where_arr = array();
	$cats_where_arr = array();

	$params = array();
	
	foreach ($ids_arr as $id )
	{
		$params[] = $id;
		$params[] = $lang;
		$nodes_where_arr[] = " nodes.id = ? and node_details.lang = ? ";

	}


	$nodes_where_string = implode ( " OR ", $nodes_where_arr );
	if ( $default_all and !$nodes_where_string )
	{
		$nodes_where_string = '1 ';
		if ( !$show_inactive )
		{
			$nodes_where_string .= ' and nodes.active = "1"' ;
		}
	}
	if ( !$ids )
	{
		if ($show_inactive)
		{
			$params[] = $lang;
			$nodes_where_string = ' nodes.id > 0 and node_details.lang = ?';
		}
		else
		{
			//$params[] = $lang;
			//$nodes_where_string = ' nodes.id > 0 and node_details.lang = ?';
			//$nodes_where_string .= ' and nodes.active = "1"' ;
			return false;
		}
		
		
		
	}

	$nodes = $connection->fetch_result( "SELECT *, nodes.id as id, nodes.type FROM nodes
								JOIN node_details on node_details.node_id = nodes.id
									WHERE $nodes_where_string", $params);
	if (!$nodes )
	{
		return false;
	}
	
	$pictureParams = array($lang);
	$categoryParams = array();
	
	foreach ($nodes as $l)
	{
		$pictureParams[] = $l["id"];
		$categoryParams[] = $l["id"];
		$categoryParams[] = $lang;
		
		$picture_where_arr[] = " nodes.id = ? ";
		$cats_where_arr[] = " node_categories.node_id = ? AND category_details.lang = ? ";
	}

	$pictures_where_string = implode ( " OR ", $picture_where_arr );
	$cats_where_string = implode ( " OR ", $cats_where_arr );

	if ( $default_all and !$pictures_where_string ) { $pictures_where_string = '1'; }
	if ( $default_all and !$cats_where_string ) 
	{
		$categoryParams[] = $lang;
		 $cats_where_string = ' categories.id > 0 AND category_details.lang = ? '; 
	}



	$pictures = $connection->fetch_result("SELECT picture_files.name, node_pictures.picture_type, node_pictures.id as node_pictures_id, 
													nodes.id, picture_files.id AS picture_file_id, 
													picure_files_alt.alt_text AS alt, picure_files_alt.type AS alt_type
											FROM nodes
											JOIN node_pictures ON nodes.id = node_pictures.node_id
											JOIN picture_files ON node_pictures.picture_id = picture_files.id
											LEFT OUTER JOIN picure_files_alt ON picture_files.id = picure_files_alt.picture_file_id AND picure_files_alt.type != 'carousel' AND picure_files_alt.type != 'facility' AND picure_files_alt.lang = ?
											WHERE $pictures_where_string GROUP BY picture_files.id
											", $pictureParams);
	$pictureCount = count($pictures);

	$cats = $connection->fetch_result("SELECT *, categories.id as id FROM categories
											JOIN category_details on category_details.category_id = categories.id
											JOIN node_categories ON node_categories.category_id = categories.id
											WHERE $cats_where_string ", $categoryParams);


	
	// FACILITATI
	$facilitiesQuery = "SELECT facilities.title,facilities.picture FROM facilities,nodes_facilities WHERE facilities.id=nodes_facilities.facility_id AND nodes_facilities.node_id = ? ORDER BY facilities.position ASC";
	
	// TEMATICI
	$tematicsQuery = "SELECT tematics.name, tematics.id FROM tematics,nodes_tematics WHERE tematics.id=nodes_tematics.tematic_id AND nodes_tematics.node_id = ? GROUP BY tematics.id";
	
	
	// STAY OFFERS
	$stayOfferQuery = "SELECT so.*, c.short_name AS currency
									FROM stay_offers so, currency c
									WHERE so.end > '".( time() + 24 * 60 * 60 )."'
									AND so.currency_id = c.id
									AND so.node_id = ?
									AND so.active = 1
									ORDER BY so.end ASC"; 
	
	// OFFER ROOM PRICE
	$roomQuery = "SELECT *, price_".date("N", time() + 24 * 60 * 60 )." as next_day_price
					FROM offer_rooms
					WHERE active = '1'
					AND stay_offer_id = ?
					AND price_".date("N", time() + 24 * 60 * 60 )." > 0
					ORDER BY price_".date("N", time() + 24 * 60 * 60 )." ASC ";
	
	// ZONE
	$areaQuery = "SELECT *, areas.name as area_name, towns.name as town_name FROM towns 
							JOIN areas ON towns.area_id = areas.id 
							WHERE towns.id = ?";
	
	// NODE TOTAL SCORE
	$scoreQuery = "SELECT SUM(score) as total_score FROM ratings WHERE node_id = ?";
	
	// ARTICOLE
	$articlesQuery = "SELECT * FROM articles 
						JOIN node_articles ON articles.id = node_articles.article_id 
						WHERE node_articles.node_id = ? AND articles.lang = ?";
	
	// POZE ARTICOLE
	$articlesPicturesQuery = "SELECT *, picture_files.id AS picture_file_id FROM article_pictures JOIN picture_files ON article_pictures.picture_id = picture_files.id WHERE article_pictures.article_id = ?";
	// TEXT ALTERNATIV POZE ARTICOLE
	$articlePictureAltQuery = "SELECT * FROM picure_files_alt WHERE id = ? AND type = 'article' AND lang = ?";
	
	// ZILE DE EVENIMENT
	$daysQuery = "SELECT *, event_days.id AS id FROM event_days 
							JOIN event_day_details ON event_days.id = event_day_details.event_day_id 
						WHERE event_days.event_node_id = ? AND lang = ?";
	
	// LOCATII EVENTIMENT
	$eventNodesQuery = "SELECT edn.* FROM event_day_nodes edn 
						LEFT JOIN nodes n ON edn.node_id = n.id 
						WHERE edn.event_day_id = ? AND n.type = 'location'";
	
	$locationQuery = "SELECT * FROM nodes 
								JOIN node_details ON nodes.id = node_details.node_id
								WHERE nodes.id = ? 
								AND node_details.lang = ? AND nodes.type = 'location' ";
								
								
	// LOCATII RELATIONATE ACTIVITATI/OFERTE
	$relatedQuery = "SELECT rn.node_id AS id FROM related_nodes rn 
											WHERE relation_node_id = ?";
											
	// TOUR ITEMS
	$tourItemsQuery = "SELECT *, tour_items.id as tour_item_id FROM tour_items
									LEFT OUTER JOIN tour_item_details ON tour_items.id = tour_item_details.tour_item_id
									WHERE ( tour_item_details.lang = ? OR tour_item_details.lang is null )
									AND tour_items.tour_node_id = ?
									ORDER BY tour_items.order_i DESC";	
	
	$new_nodes = array();

	foreach ($nodes as $node)
	{
		$facilitiesParams = $tematicsParams = array($node['id']);
		
		$node["facilities"] = $connection->fetch_result($facilitiesQuery, $facilitiesParams);
		$node["tematics"] = $connection->fetch_result($facilitiesQuery, $facilitiesParams);
		
		//get pictures
		$node_pictures = array();
		$main_picture_index = false;

		foreach ( $pictures as $k=>$p )
		{

			if ( $p['id'] == $node['id'] )
			{
				$p['url'] = $image_folder.$p['name'];
				$p['thumb_url'] = $thumb_image_folder.$p['name'];
				$node_pictures[] = $p;
			}
		}

		if ( $main_picture_index === false )
		{
			$main_picture_index = 0;
		}

		$car_picture_index = 0;
		if ( $node_pictures )
		{
			foreach ( $node_pictures as $k=>$p )
			{
				if ( $p['picture_type'] == 1 )
				{
					$main_picture_index = $k;
				}
				if ( $p['picture_type'] == 2 )
				{
					$car_picture_index = $k;
				}
				
				if($p["alt"] == null)
				{
					$node_pictures[$k]["alt"] = htmlentities(strip_tags($node["name"]));
				}
				
			}
			$node['main_picture'] = $node_pictures[$main_picture_index];
			$node['car_picture'] = $node_pictures[$car_picture_index];
			$node['pictures'] = $node_pictures;
		}
		else
		{
			$node['main_picture'] = array(	"name"=>"nedefinit", 
											//"url"=>base_url()."images/thumbnail-default.jpg", 
											//"thumb_url"=>base_url()."images/thumbnail-default.jpg",
											"url"=>"http://www.brasovtour.com/images/thumbnail-default.jpg", 
											"thumb_url"=>"http://www.brasovtour.com/images/thumbnail-default.jpg",
											"type"=>0);
			$node['pictures'] = array();
		}
		$node["picture_count"] = $pictureCount;
		
		$node_cats = array();
		$node_cats['super_cats'] = array();
		$node_cats['cats'] = array();
		$node_cats['sub_cats'] = array();
		
		$node["working_hours"] = array();
		
		$working_hours = $connection->fetch_result("SELECT * FROM node_working_hours WHERE node_id = ?", array($node["id"]));
		if($working_hours)
		{
			foreach($working_hours as $hour)
			{
				if($hour["day"] == 1){ $node["working_hours"]["luni"] = $hour; }
				if($hour["day"] == 2){ $node["working_hours"]["marti"] = $hour; }
				if($hour["day"] == 3){ $node["working_hours"]["miercuri"] = $hour; }
				if($hour["day"] == 4){ $node["working_hours"]["joi"] = $hour; }
				if($hour["day"] == 5){ $node["working_hours"]["vineri"] = $hour; }
				if($hour["day"] == 6){ $node["working_hours"]["sambata"] = $hour; }
				if($hour["day"] == 7){ $node["working_hours"]["duminica"] = $hour; }
				
			}
		}

		$after_name = '';
		$posibil_cazare = false;
		foreach ( $cats as $c )
		{
			if ( $node['id'] == $c['node_id'] )
			{
				if ( $c['level'] == 1 )
				{
					$node_cats['super_cats'][] = $c;

					if ( $c['name'] == 'Dorm' )
					{
						$posibil_cazare = true;
					}
				}
				if ( $c['level'] == 2 )
				{
					$node_cats['cats'][] = $c;
				}
				if ( $c['level'] == 3 )
				{
					$node_cats['sub_cats'][] = $c;
					if ( $c['after_name'] ){ $after_name = $c['after_name']; }
				}
			}
		}
		$node['cats'] = $node_cats;
		if(isset($node_cats['cats'][0]['name']))
		{
			$node['gen'] = ucfirst($node_cats['cats'][0]['name']);
		}
		elseif(isset($node_cats['cats']["sub_cat"]['name']))
		{
			$node['gen'] = ucfirst($node_cats['cats']["sub_cat"]['name']);
		}
		
		$node['updated_formated'] = date("d.m.Y H:i", $node['updated'] );

		$node['name_extra'] = $node['name'].' '.$after_name;

		if ( $posibil_cazare )
		{
			
			$stayOfferParams = array($node["id"]);

			$stay_offer = $connection->fetch_one($stayOfferQuery, $stayOfferParams);
			if($stay_offer && !isset($stay_offer["currency"]) || $stay_offer["currency"] == "")
			{
				print_r($stay_offer);
				$stay_offer["currency"] = "RON";
			}
			
			if ( isset($stay_offer["id"]))
			{
				$roomParams = array($stay_offer["id"]);
				
				$offer_room = $connection->fetch_one($roomQuery, $roomParams);

				$node['next_day_price'] = $offer_room['next_day_price'] ? $offer_room['next_day_price'].' '.$stay_offer["currency"] : '';
				$node['rezerva_camera_link'] = base_url()."rezervare_hotel.php?id=".$node['id']."&start=".date("d.m.Y", time() + 24 * 60 * 60 )."&end=".date("d.m.Y", time() + 2 *24 * 60 * 60 );
			}
		}

		if ( $node['town_id'] )
		{
			$areaParams = array($node["town_id"]);
			
			$area_data = $connection->fetch_one($areaQuery, $areaParams);

			$node['area_id'] = $area_data['area_id'];
			$node['area_name'] = $area_data['area_name'];
			$node['town_name'] = $area_data['town_name'];
		}

		//get score

		$scoreParams = array($node["id"]);
		
		
		$score_total = $connection->fetch_one($scoreQuery, $scoreParams);

		$node['score_total'] = $score_total['total_score'];

		$node['score_hits'] = $connection->num_rows("SELECT id FROM ratings WHERE node_id = ? ",array($node["id"]));

		if ( $node['score_total'] > 0 and $node['score_hits'] > 0 )
		{
			$node['score'] = round ( ($node['score_total'] * 100) / $node['score_hits'] ) / 100;

			$node['score'] = number_format($node['score'], 2, '.', '');
			$node['show_score'] = true;

		}
		else
		{
			$node['score'] = 'n/a';
			$node['show_score'] = false;
		}
		
		$superCatURL = "atractii";
		
		if(isset($node['cats']['super_cats'][0]['url_title']))
		{
			$superCatURL = $node['cats']['super_cats'][0]['url_title'];
		}
		
		$catURL = "";
		
		if(isset($node['cats']['cats'][0]['url_title']))
		{
			$catURL = $node['cats']['cats'][0]['url_title'];
		}
		$node['front_end_link'] = base_url_scripts().($lang == 'ro' ? '': $lang.'/').$superCatURL."/".$catURL."/".$node["url_title"];
		
		$articleParams = array($node["node_id"], $lang);
		
		$node_articles = $connection->fetch_result($articlesQuery, $articleParams);
		foreach ($node_articles as $nakey=>$node_article )
		{
			$articlesPicturesParams = array($node_article["article_id"]);
			
			$article_pictures = $connection->fetch_result($articlesPicturesQuery, $articlesPicturesParams);


			foreach ($article_pictures as $pKey => $article_picture )
			{
				if(isset($article_pictures[$pKey]))
				{
					$articlePictureAltParams = array($article_picture["picture_file_id"], $lang);
					
					$altRow = $connection->fetch_one($articlePictureAltQuery, $articlePictureAltParams);
					
					$article_pictures[$pKey]["alt"] = "";
					if($altRow)
					{
						$article_pictures[$pKey]["alt"] = $altRow["alt_text"];
					}
					else
					{
						$article_pictures[$pKey]["alt"] = htmlentities(strip_tags("Articol ".$node["name"]));
					}
					
					if ( $article_picture['main'] )
					{
						//$node_article['picture_url'] = base_url().'pictures/'.$article_picture['name'];
						//$node_article['thumb_picture_url'] = base_url().'picture_thumbs/'.$article_picture['name'];
						
						$node_article['picture_url'] = 'http://www.brasovtour.com/pictures/'.$article_picture['name'];
						$node_article['thumb_picture_url'] = 'http://www.brasovtour.com/picture_thumbs/'.$article_picture['name'];
					}
				}
			}


			if ( !isset($node_article['picture_url'])  || !$node_article['picture_url'])
			{
				if ( $article_pictures )
				{
					$node_article['picture_url'] = 'http://www.brasovtour.com/pictures/'.$article_pictures[0]['name'];
					$node_article['thumb_picture_url'] = 'http://www.brasovtour.com/picture_thumbs/'.$article_pictures[0]['name'];
					
					//$node_article['picture_url'] = base_url().'pictures/'.$article_pictures[0]['name'];
					//$node_article['thumb_picture_url'] = base_url().'picture_thumbs/'.$article_pictures[0]['name'];
				}
				else
				{
					//$node_article['picture_url'] = base_url().'images/thumbnail-default.jpg';
					///$node_article['thumb_picture_url'] = base_url().'images/thumbnail-default.jpg';
					
					$node_article['picture_url'] = 'http://www.brasovtour.com/images/thumbnail-default.jpg';
					$node_article['thumb_picture_url'] = 'http://www.brasovtour.com/images/thumbnail-default.jpg';
				}
			}

			$node_articles[$nakey] = $node_article;
		}

		$node['articles'] = $node_articles ? $node_articles : array();

		if ( $node['type'] == 'event' )
		{
			//trebuie event_days
			
			$daysParams = array($node["id"], $lang);
			
			$daysQuery2 = $daysQuery;
			
			if(getRequestParameter("data") && getRequestParameter("data") > 0 && getRequestParameter("id") == $node["id"])
			{
				$useDate = strtotime(getRequestParameter("data"));
				
				$daysParams[] = $useDate;
				
				$daysQuery2 .= " AND event_days.day = ?";
			}
			
			$daysQuery2 .= " ORDER BY event_days.day ASC";
			
			$event_days = $connection->fetch_result($daysQuery2, $daysParams);

			$node['event_days'] = $event_days;

			$closest_day  = '';
			$next_day  = '';
			foreach ( $event_days as $event_day)
			{
				if ( $event_day['day'] > time() and !$closest_day )
				{
					$closest_day = $event_day;
					$next_day = $event_day;
				}
			}



			if (!$closest_day && count($event_days) > 0)
			{
				$closest_day = $event_days[(count($event_days) - 1 )];
			}
			
			$loc = array();
			
			$event_day_nodes = false;
			
			if(isset($closest_day["event_day_id"]))
			{
				$eventNodesParams = array($closest_day["event_day_id"]);
				$event_day_nodes = $connection->fetch_result($eventNodesQuery, $eventNodesParams);
			}
			
			
			
			
			if($event_day_nodes)
			{
				$loc_id = $event_day_nodes[0]['node_id'];
				$locationParams = array($loc_id, $lang);
									
				$loc = $connection->fetch_one($locationQuery, $locationParams);
				
				
				if(isset($loc["node_id"]))
				{
					$loc = get_node_by_id($loc["node_id"]);
					
				}
			
			
				if($next_day)
				{
					$nextDayParams = array($next_day["event_day_id"]);
					$next_event_day_nodes = $connection->fetch_result($eventNodesQuery, $nextDayParams);
					
					$next_loc_id = $next_event_day_nodes[0]['node_id'];
					
					$nextLocParams = array($next_loc_id, $lang);
					$loc_next = $connection->fetch_one($locationQuery, $locationParams);
					
					if($loc_next)
					{
						$loc_next = get_node_by_id($loc_next["node_id"]);
					}
				}
			


				if ( !$node['geo_lat'] or ! $node['geo_lon'] )
				{
					$node['geo_lat'] = $loc['geo_lat'];
					$node['geo_lon'] = $loc['geo_lon'];
				}
			}
			if($node["url_title"] == "" || $node["url_title"] == "schimba-numele")
			{
				$up = array();
				$up["node_id"] = $node["id"];	
				$up["url_title"] = $node["url_title"] = sanitize($node["name"]);
				
				$connection->update("node_details", $up, "node_id");
			}
			
			$nodeURL = base_url()."evenimente/";
			
			if(isset($loc) && $loc)
			{
				$nodeURL .= $loc["url_title"]."/";
			}
			$nodeURL .= $node["url_title"]."/";
			
			foreach($node["event_days"] as $dKey => $day)
			{
				if($day["day"] > 0)
				{
					$dayLink = $nodeURL . date("d-m-Y", $day["day"])."/".$node["id"];
					$node["event_days"][$dKey]["front_end_link"] = $dayLink;
					if($next_day && $day["id"] == $next_day["id"])
					{
						$next_day["front_end_link"] = $dayLink;
					}
					
					if($closest_day && $day["id"] == $closest_day["id"])
					{
						$closest_day["front_end_link"] = $dayLink;
					}
				}
			}
			
			$nodeURL .= $node["id"];
			
			
			$node['front_end_link'] = $nodeURL; // NO DATE
			$node['closest_day'] = $closest_day;
			if(isset($loc) && $loc)
			{
				$node['closest_day']['node'] = $loc;
			}
			
			if ( isset($next_day) && $next_day )
			{
				$node['next_day'] = $next_day;
				if(isset($loc_next) && $loc_next)
				{
					$node['next_day']['node'] = $loc_next;
				}
				
			}
			$node['tagline'] = substr(strip_tags($node['description']), 0, 160);
			if(isset($closest_day['day']) && isset($closest_day['hour']))
			{
				$event_date = $closest_day['day'];
				$event_hour = $closest_day['hour'];
			}
			
			
			
			$dayURL = "";
			if(isset($closest_day["front_end_link"]))
			{
				$dayURL = $closest_day["front_end_link"];
			}
			
			if ( $next_day ){
				$event_date = $next_day['day'];
				$event_hour = $next_day['hour'];
				
				$dayURL = $next_day["front_end_link"];
			}

			$luni = array();
			$luni[] = '';
			$luni[] = 'ian';
			$luni[] = 'feb';
			$luni[] = 'mar';
			$luni[] = 'apr';
			$luni[] = 'mai';
			$luni[] = 'iun';
			$luni[] = 'iul';
			$luni[] = 'aug';
			$luni[] = 'sept';
			$luni[] = 'oct';
			$luni[] = 'nov';
			$luni[] = 'dec';

			$zile = array();
			$zile[] = '';
			$zile['Mon'] = 'luni';
			$zile['Tue'] = 'marti';
			$zile['Wed'] = 'miercuri';
			$zile['Thu'] = 'joi';
			$zile['Fri'] = 'vineri';
			$zile['Sat'] = 'sambata';
			$zile['Sun'] = 'duminica';
			
			$node['def_text'] = date("d", $event_date ).'.'.date("m", $event_date);
			$node["def_url"] = $dayURL;
			$node['info_text'] = $zile[date("D", $event_date )];
			$node['mnth'] = $luni[intval(date("m",$event_date))];
			if(isset($next_day['hour']) && $next_day['hour'] != "")
			{
				$node['info_text'] = $next_day['hour'];
			}
		}
		
		if($node["type"] == "offer" || $node["type"] == "activity")
		{
			$relatedParams = array($node["id"]);
			
			$relatedNodes = $connection->fetch_result($relatedQuery, $relatedParams);
			
			$related_ids = false;
			
			foreach($relatedNodes as $relatedNode)
			{
				$related_ids[$relatedNode["id"]]  = $relatedNode["id"];
			}
			$node["related_nodes"] = array();
			
			if($related_ids)
			{
				$node["related_nodes"] = get_node_by_id($related_ids);
			}
			
			$dayNode = $connection->fetch_row("node_days", $node["id"], "node_id");
			
			$days = array();
			if($dayNode["days"] != "")
			{
				$days = json_decode($dayNode["days"], true);
				
				$zile['1'] = 'Monday';
				$zile['2'] = 'Tuesday';
				$zile['3'] = 'Wednesday';
				$zile['4'] = 'Thursday';
				$zile['5'] = 'Friday';
				$zile['6'] = 'Saturday';
				$zile['7'] = 'Sunday';
				
				
				$currentDay = date( "w", (time() - 24 * 60 * 60 ));
				if(count($days) > 0)
				{
					$next_day = false;
					foreach($days as $day)
					{
						if(!isset($firstDay))
						{
							$firstDay = $day;
						}
						if($day == $currentDay)
						{
							$next_day = strtotime("today");
						}
						elseif($day > $currentDay && !$next_day)
						{
							$next_day = strtotime("Next ".$zile[$day])- 24 * 60 * 60;
						}
					}
					
					if(!$next_day)
					{
						$next_day = strtotime("Next ".$zile[$firstDay]) - 24 * 60 * 60;
					}
					
					$node["next_day"] = $next_day;
				}
				
				$node["info_text"] = $node["short_description"];
				
			}
			
			$node["days"] = $days;
			
		}
		
		if ($node['type'] == 'tour')
		{
			//trebuie tour_items
			$tourItemsParams = array($lang, $node["node_id"]);
			
			$tour_items = $connection->fetch_result($tourItemsQuery, $tourItemsParams);
			//print_r($node);

			$node['tour_items'] = $tour_items;

			$node['front_end_link'] = base_url()."tur/$node[id]/".url_title($node['url_title']);
			$node['tagline'] = substr(strip_tags($node['description']), 0, 160);

			$node['def_text'] = $node["tour_duration"];
			$node['info_text'] = $node["short_description"];
		}
		$node["micro_data"] = get_node_micro_data($node);
		$new_nodes[] = $node;
	}

	if ( $new_nodes )
	{
		if ( !is_array($ids) )
		{
			if ( $default_all )
			{
				return $new_nodes;
			}
			else
			{
				return $new_nodes[0];
			}
		}
		else
		{
			return $new_nodes;
		}
	}
	else
	{
		return false;
	}
}


function get_node_micro_data($node)
{
	$micro_data = array();
	
	$micro_data["name"] = $node["name"];
	$micro_data["description"] = htmlentities(strip_tags((($node["tagline"] ? $node["tagline"] : $node["description"]))));
	$micro_data["url"] = $node["front_end_link"];
	
	if(isset($node["main_picture"]["url"]))
	{
		$micro_data["image"] = $node["main_picture"]["url"];
	}
	elseif(isset($node["pictures"][0]["url"]) )
	{
		$micro_data["image"] = $node["pictures"][0]["url"];
	}
	
	if($node["address"] != "")
	{
		$micro_data["address"] = $node["address"];
	}
	
	if($node["web"] != "")
	{
		$micro_data["same_as"] = $node["web"];
	}
	if($node["geo_lat"] && $node["geo_lon"])
	{
		$micro_data["geo_coordinates"] = array("geo_lat" => $node["geo_lat"], "geo_long" => $node["geo_lon"]);
	}
	
	if($node["type"] == "location")
	{
		if(isset($node["cats"]["cats"][0]["micro_data_type"]))
		{
			$micro_data["type"] = "http://schema.org/".$node["cats"]["cats"][0]["micro_data_type"];
		}
		elseif(isset($node["cats"]["super_cats"][0]["micro_data_type"]))
		{
			$micro_data["type"] = "http://schema.org/".$node["cats"]["super_cats"][0]["micro_data_type"];
		}
	}
	elseif($node["type"] == "event")
	{
		if(isset($node["next_day"]) && $node["next_day"])
		{
			$nextDay = $node["next_day"];
			if(@$nextDay["node"])
			{
				$micro_data["location"]["name"] = $nextDay["node"]["name"];
				$micro_data["location"]["address"] = $nextDay["node"]["address"];
				
				if(@$nextDay["node"]["geo_lat"] && @$nextDay["node"]["geo_lon"])
				{
					$micro_data["geo_coordinates"] = array("geo_lat" => $nextDay["node"]["geo_lat"], "geo_long" => $nextDay["node"]["geo_lon"]);
				}
			}
		}
		elseif(isset($node["closest_day"]) && $node["closest_day"])
		{
			$nextDay = $node["closest_day"];
		}
		elseif(isset($node["event_days"][0]) && $node["event_days"][0])
		{
			$nextDay = $node["event_days"][0];
		}
		
		$micro_data["start_date"] = "";
		
		if(isset($nextDay["day"]) && $nextDay["day"])
		{
			$micro_data["start_date"] = date("Y-m-d",$nextDay['day']);
		}
		if(isset($nextDay["hour"]) && $nextDay["hour"])
		{
			$micro_data["start_date"] .= "T".$nextDay['hour'];
		}
		
		
		
	}
	elseif ($node["type"] == "offer")
	{
		$micro_data["type"] = "https://schema.org/Event";
		
		$micro_data["startDate"] = date("c",$node['start']);
	}
	elseif($node["type"] == "activity")
	{
		$micro_data["type"] = "http://schema.org/Event";
		
		$micro_data["startDate"] = date("c",$node['start']);
	}
	elseif($node["type"] == "tour")
	{
		$micro_data["type"] = "http://schema.org/Thing";
		
		//$micro_data["startDate"] = date("c",$node['start']);
	}
	
	return $micro_data;
}

function fetch_location_geo( $nume = false, $cats = array(), $options = array())
{
	$nodes = search_nodes(
		$args = array(
			"nume"=>false,
			"cats"=>$cats,
			"town_id"=>false,
			"options"=>$options,
			"page"=>0,
			"sort"=>"rank",
			"rank_list"=>false,
			"sort_direction"=>"desc",
			"results_returned"=>1600,
			"return_sql"=>false
		)
	);

	if ( $nodes )
	{
		return $nodes;
	}
	else
	{
		return false;
	}
}

function get_surrounding_by_cat_id ( $lat, $lon, $cat_id, $all = false )
{
	global $connection;
	
	if ( !$all)
	{
		$lat_degrees = 0.0035;
		$lon_degrees = 0.0055;

		$lat_degrees = 0.0085;
		$lon_degrees = 0.0105;
	}
	else
	{
		$lat_degrees = 20;
		$lon_degrees = 20;
	}
	$max_lat = $lat + $lat_degrees;
	$min_lat = $lat - $lat_degrees;
	$max_lon = $lon + $lon_degrees;
	$min_lon = $lon - $lon_degrees;
	
	$params = array($min_lat, $max_lat, $min_lon, $max_lon, $cat_id);
	
	$sql = "SELECT DISTINCT nodes.id, nodes.geo_lat, nodes.geo_lat, nodes.geo_lon FROM nodes
							JOIN node_categories ON node_categories.node_id = nodes.id
							JOIN categories ON node_categories.category_id = categories.id
										WHERE nodes.geo_lat > ?
										AND nodes.geo_lat < ?
										AND nodes.geo_lon > ?
										AND nodes.geo_lon < ?
										AND categories.id = ?
										";
	$items = $connection->fetch_result($sql, $params);



	$ids = array();

	foreach ( $items as $i )
	{
		$ids[] = $i['id'];
	}

	return $ids;
}

function l( $key )
{
	global $connection;
	$lang = isset($_SESSION["lang"]) ? $_SESSION["lang"] : 'ro' ;
	
	$params = array($key, $lang);
	$langQuery = "SELECT * FROM lang WHERE keyword = ? AND lang = ?";
	
	
	$t = $connection->fetch_one($langQuery, $params);
	
	$base_url = base_url();
	
	if (!$t)
	{
		$ll = $connection->fetch_table("language_list");
		foreach ( $ll as $l)
		{
			$params = array($key, $l["name"]);
			$check = $connection->fetch_one($langQuery, $params);
			if (!$check )
			{
				$in['lang'] = $l['name'];
				$in['keyword'] = $key;
				$in['text'] = '';
				$connection->insert ('lang', $in);
			}
		}
		$params = array($key, $lang);
		$t = $connection->fetch_one($langQuery, $params);
	}

	return $t['text'] ? $t['text'] : '<span style = "color:red; background:yellow; outline:2px solid red; " >'.$key.' nu are text. <a target="_blank" href = "'.base_url_scripts().'admin/lang.php?keyword='.$key.'&red_to='.base_url_scripts().$_SERVER['REQUEST_URI'].'" >adauga din admin</a>. </span>';
}

function check_login()
{
	if ( @$_SESSION['client_id'] )
	{

	}
	else
	{
		header("Location: login.php");
	}
}

function get_setting( $setting_name )
{
	$check = fetch_row("SELECT * FROM settings WHERE name = '$setting_name'");

	return $check['value'];
}
function set_setting( $setting_name, $value )
{
	update("settings", " name = '$setting_name' ", array("value"=>$value));

}

function log2( $content )
{
	$in['date'] = time();
	$in['content'] = $content;

	insert("logs", $in);
}


function generate_pret_rezervare ( $data )
{
	/* defaults */
	/*
	$hotel_id = 2;
	$node = get_node_by_id ( $hotel_id );
	$rooms = array(array("room_id"=>2, "qty"=>"3"), array("room_id"=>3, "qty"=>"1"), array("room_id"=>16, "qty"=>"1"));
	$pay_serv = array("1", "8", "9");
	$start = time();
	$end = time() + 24 * 3 * 60 * 60;
	*/

	$show_table = true;
	$hotel_id = $data['hotel_id'];
	$node = get_node_by_id ( $hotel_id );



	$own_room_types_set = json_decode ( $node['room_types'] );
	$post_rooms = @$data['room_qty'] ? $data['room_qty'] : array();

	$total_rooms = 0;
	$post_rooms = json_decode ( $node['room_types'] );
	$rooms = array();
	foreach ( $post_rooms as $prid=>$pr )
	{
		$rooms[] = array ( "room_id"=>$pr, "qty"=>0 );
		$total_rooms += $pr;
	}

	/*

	if ($total_rooms == 0 )
	{
		echo "<h4>".l("eroare_fara_camere_selectate")."<h4>";
		//exit;
	}
	*/

	//$rooms = array(array("room_id"=>2, "qty"=>"3"), array("room_id"=>3, "qty"=>"1"), array("room_id"=>16, "qty"=>"1"));
	$pay_serv = @$data['pay_serv'] ? $data['pay_serv'] : array();

	$start = strtotime ( @$data['start'] );
	$end = strtotime ( @$data['end'] );
	if ( !$start ){ $start = time(); }
	if ( !$end ){ $end = time() + 48 * 60 * 60; }



	$days = round (($end - $start) / (24 * 60 * 60 ));

	if ( $start > $end )
	{
		echo "<h4>".l("eroare_rezervare_inceput_mai_tarziu_decat_sfarsit")."<h4>";
		$show_table = false;
	}
	if ( $days == 0 )
	{
		echo "<h4>".l("eroare_rezervare_zero_zile")."<h4>";
		$show_table = false;
	}



	$na_day = false;
	$days_arr = array();

	$total_taxa_statiune = 0;
	for ( $d = 0; $d < $days; $d++ )
	{
		$day = array();
		$dtime = $start + ($d * 24 *60 * 60 ) + 12 * 60 * 60;

		$day['date'] = date("d.m.Y H:i", $dtime );
		$day['timestamp'] =  $dtime;

		$stay_offer = fetch_row ( "SELECT * FROM stay_offers WHERE start < '$dtime' AND end > '$dtime' AND node_id = '$hotel_id'");


		$day_total = 0;

		if ( $stay_offer )
		{
			$day['taxa_statiune'] = $stay_offer['taxa_statiune'];
			$total_taxa_statiune += $stay_offer['taxa_statiune'];
			$day_total += $stay_offer['taxa_statiune'];

			$so_rooms = fetch_result ( "SELECT * FROM offer_rooms WHERE stay_offer_id = '$stay_offer[id]'");

			$check_rooms = $rooms;
			$new_rooms = array();
			foreach ( $check_rooms as $room )
			{

				$found = false;
				foreach ( $so_rooms as $so_room )
				{
					$so_room = (array) $so_room;

					if ( $room['room_id'] == $so_room['room_type_id'] )
					{
						$found = $so_room;
					}
				}

				if ( $found )
				{
					$new_rooms[$room['room_id']]['qty'] = $room['qty'];
					$new_rooms[$room['room_id']]['unit_price'] = $found['price'];
					$new_rooms[$room['room_id']]['total_price'] = $found['price'] * $room['qty'];
					$new_rooms[$room['room_id']]['max'] =  $found['max'];
					$day_total += $found['price'] * $room['qty'];
				}
				else
				{

				}
			}



		}
		else
		{
			$new_rooms = array();
			$day['taxa_statiune'] = 0;

			$na_day = true;
		}

		$day['rooms'] = $new_rooms;
		$day['day_total'] = $day_total;

		$days_arr[] = $day;


	}



	$pay_serv_set = json_decode ( $node['pay_services'] );
	$total_serv = 0;
	$new_pay_serv = array();
	foreach ( $pay_serv_set as $pss )
	{
		$pss = (array) $pss;
		$new_pay_serv[] = array("serv_id"=>$pss['serv_id'], "price"=>$pss['price'] );
		$total_serv += $pss['price'];
	}


	$count_days = count ( $days_arr );

	?>

	<?php if ( $na_day ):
		$show_table = false;
	?>
		<h3> Preturile nu au fost setate pentru toata perioada selectata.</h3>
	<?php else: ?>
		<?php if ( $show_table ): ?>
		<table class = 'table table-bordered'>
			<thead>
				<tr>
						<th></th>
						<th>nr. camere</th>
					<?php foreach($days_arr as $dh):?>
						<th style = 'display:none;'><?php echo date("d.m", $dh['timestamp']); ?></th>
					<?php endforeach; ?>
						<th>max. pers</th>
						<th><?php echo date("d.m", $start); ?>-<?php echo date("d.m", $end); ?> (<?php echo $count_days;  ?> zile)</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($rooms as $room):?>
					<tr row_id = '<?php echo $room['room_id']; ?>'>
							<td><?php echo l("room_type_id".$room['room_id']); ?></td>
							<td style = 'width:50px;'>
								<input
									style = 'width:40px; margin:0;'
									name = "room_qty[<?php echo $room['room_id']; ?>]"
									min = '0'
									type = 'number'
									value = '<?php echo $room['qty'] ? $room['qty'] : 0; ?>'
								>

						<?php foreach($days_arr as $day):?>
							<td style = 'display:none;' unit_price = '<?php echo $day['rooms'][$room['room_id']]['unit_price']; ?> '>
								<?php echo $day['rooms'][$room['room_id']]['total_price']; ?>

							</td>
						<?php endforeach; ?>
							<td class = 'max_persoane'><?php echo $days_arr[0]['rooms'][$room['room_id']]['max']; ?> </td>
							<td class = 'total_perioada' >total perioada</td>

					</tr>
				<?php endforeach; ?>
				<?php /*
					<tr>
						<td><?php echo l("taxa_statiune"); ?></td>
						<td></td>
						<?php foreach($days_arr as $day):?>
							<td>
							<?php if ( @$day['taxa_statiune'] ):?>
								<?php echo $day['taxa_statiune']; ?>
							<?php else: ?>
								n/a
							<?php endif; ?>
							</td>
						<?php endforeach; ?>
					</tr>
					<tr class = 'total_zi' >
						<td><?php echo l("cost_zilnic_total"); ?></td>
						<td></td>
						<?php
						$total_total = 0;
						foreach($days_arr as $day):?>
							<td class = 'total_zi_item'>
							<?php if ( @$day['day_total'] ):
								$total_total += $day['day_total'];
							?>
								<?php echo $day['day_total']; ?>
							<?php else: ?>
								n/a
							<?php endif; ?>
							</td>
						<?php endforeach; ?>
					</tr>
					*/ ?>
			</tbody>
		</table>
		<h4>
			<?php echo l("cost_total_cazare"); ?>: <span class = 'pret_total_cazare'><?php echo @$total_total; ?></span>
		</h4>
		<h4>
			<?php echo l("max_persoane"); ?>: <span class = 'total_max_persoane'></span>
		</h4>
		<br>

	<?php /*
		<table class = 'table table-bordered table-compact'>
			<thead>
				<tr>
					<th><?php echo l("denumire_serviciu"); ?></th>
					<th><?php echo l("pret_serviciu"); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($new_pay_serv as $serv):?>
					<tr>
						<td><?php echo l("serv_type_id".$serv['serv_id']); ?></td>
						<td><?php echo $serv['price']; ?></td>
						<td><input serv_price = '<?php echo $serv['price']; ?>' name = 'pay_serv[<?php echo $serv['serv_id']; ?>]' type = 'checkbox' ></td>
					</tr>
				<?php endforeach; ?>

			</tbody>
		</table>
		<h4>
			<?php echo l("cost_total_servicii"); ?>: <span class = 'pret_total_servicii'>0</span>
		</h4>
		<br>


		<h3><?php echo l("cost_total_total"); ?>: <span class = 'super_pret_total' ><?php echo $total_total + $total_serv; ?></span></h3>
	*/ ?>
		<?php endif; ?>
	<?php endif; ?>
	<?php

	return $show_table;
}

function get_carousel_items ( $related_type, $related_id, $lang )
{
	$carousel_items = fetch_result("
		SELECT *, carousel_items.id as id FROM carousel_items
		JOIN carousel_item_details ON carousel_items.id = carousel_item_details.carousel_item_id
			WHERE carousel_items.related_type = '$related_type'
			AND carousel_items.related_id = '$related_id'
			AND carousel_item_details.lang = '$lang'");
	
	foreach($carousel_items as $key => $item)
	{
		$alt = fetch_row("SELECT * FROM picure_files_alt WHERE type = 'carousel' AND picture_file_id = '{$item["id"]}' AND lang = '{$lang}'");
		if($alt)
		{
			$carousel_items[$key]["alt"] = $alt["text_side"];
		}
		else
		{
			$carousel_items[$key]["alt"] = htmlentities(strip_tags($item['text_side']));
		}
		
	}
	
	return $carousel_items;
}

function get_carousel_ready_items ( $related_type, $related_id, $lang)
{
	global $connection;
	
	$params = array($related_type, $related_id, $lang);
	
	$carousel_items = $connection->fetch_result("
		SELECT *, carousel_items.id as car_id FROM carousel_items
		JOIN carousel_item_details ON carousel_items.id = carousel_item_details.carousel_item_id
			WHERE carousel_items.related_type = ?
			AND carousel_items.related_id = ?
			AND carousel_items.start < '".time()."'
			AND carousel_items.end > '".time()."'
			AND carousel_item_details.lang = ?", $params);

	$new_car_items = array();

	$altQuery = "SELECT * FROM picure_files_alt WHERE type = 'carousel' AND picture_file_id = ? AND lang = ?";
	
	foreach ($carousel_items as $cci )
	{
		$altParams = array($cci["car_id"], $lang);
		$alt = $connection->fetch_one($altQuery, $altParams);
		if($alt)
		{
			$altText = $alt["alt_text"];
		}
		else
		{
			$altText = htmlentities(strip_tags((($cci["text_side"] !="") ? $cci['text_side'] : $cci['text_main'] )));
		}
		
		
		$new_car_items[] = array(
			//"src"=>base_url()."pictures/$cci[image_link]",
			"src"=>"http://www.brasovtour.com/pictures/$cci[image_link]",
			"title"=>$cci['text_main'],
			"tagline"=>$cci['text_side'],
			"link"=>$cci['link'],
			"alt"=>$altText
		);
	}

	return $new_car_items;
}

function get_site_page_data($page_name, $lang, $field = "page_name")
{
	global $connection;
	
	$areaID = defined(AREA_ID) ? AREA_ID : 1;
	
	$query = "SELECT *, site_pages.id as id FROM site_pages 
												JOIN site_page_details ON site_pages.id = site_page_details.site_page_id 
												WHERE lang = '{$lang}' AND {$field} = ? 
												AND site_pages.area_id = '{$areaID}'";
	$site_page = $connection->fetch_one($query, array($page_name));
												
	if($site_page)
	{
		$site_page["carousel_data"] = get_carousel_ready_items( "page", $site_page["id"], $lang );
	}
	
	return $site_page;
}

function generate_form_item ( $form_item, $item, $type )
{
	echo "<div class = 'form_item' >";
		echo "<div class = 'form_question'>$form_item[question]</div>";
		echo "<div class = 'form_answer'>";
			switch ( $form_item['answer_type'] )
			{
				case "0":
					echo "<input type = 'text' name = 'answers[$type][$item[id]][$form_item[id]]' >";
					break;
				case "1":
					echo "<input type = 'number' name = 'answers[$type][$item[id]][$form_item[id]]' >";
					break;
				case "2":
					echo "<label><input type = 'radio' value = '1' name = 'answers[$type][$item[id]][$form_item[id]]' > 1 </label>";
					echo "<label><input type = 'radio' value = '2' name = 'answers[$type][$item[id]][$form_item[id]]' > 2 </label>";
					echo "<label><input type = 'radio' value = '3' name = 'answers[$type][$item[id]][$form_item[id]]' > 3 </label>";
					echo "<label><input type = 'radio' value = '4' name = 'answers[$type][$item[id]][$form_item[id]]' > 4 </label>";
					echo "<label><input type = 'radio' value = '5' name = 'answers[$type][$item[id]][$form_item[id]]' > 5 </label>";
					break;
			}
		echo "</div>";
	echo "</div>";
}

function url_title($str, $separator = '-', $lowercase = FALSE)
{
        if ($separator === 'dash')
        {
                $separator = '-';
        }
        elseif ($separator === 'underscore')
        {
                $separator = '_';
        }

        $q_separator = preg_quote($separator, '#');

        $trans = array(
                        '&.+?;'                        => '',
                        '[^a-z0-9 _-]'                => '',
                        '\s+'                        => $separator,
                        '('.$q_separator.')+'        => $separator
                );

        $str = strip_tags($str);
        foreach ($trans as $key => $val)
        {
                $str = preg_replace('#'.$key.'#i', $val, $str);
        }

        if ($lowercase === TRUE)
        {
                $str = strtolower($str);
        }

        return trim(trim($str, $separator));
}
function make_options($args){
	
	global $connection;

	$args['return_sql'] =true;
	$string = search_nodes($args);
	
	$query = "SELECT DISTINCT(facilities.id),facilities.title,facilities.picture FROM facilities,nodes_facilities 
				WHERE  facilities.id = nodes_facilities.facility_id AND node_id IN ({$string} )  
				ORDER BY facilities.position ASC";
	$data = $connection->fetch_result($query);

	return $data;
}

function make_footer_email($lang = 'ro')
{
	global $connection;
	$categorii = $connection->fetch_result("SELECT * FROM categories 
											JOIN category_details ON categories.id = category_details.category_id 
											WHERE lang = ? AND level = '1' ", array($lang));
	
	$categoryQuery = "SELECT * FROM categories 
						JOIN category_details ON categories.id = category_details.category_id 
						WHERE lang = ? AND parent_id = ? "; 
	
	$string =  '

	<tr>
    <td><table width="100%" border="0" cellspacing="6" cellpadding="0">
      <tbody><tr style="font-family:Arial, Helvetica, sans-serif; font-size:14px; font-weight:bold; color:black;">';
	  foreach($categorii as $cat):
        $string .= '<td><a href = "'.base_url().$cat['url_title'].'" >'.$cat['name'].'</a></td>';
	  endforeach;
      $string .= '</tr>
      <tr style="font-family:Arial, Helvetica, sans-serif; font-size:10px; font-weight:normal;">';
		foreach($categorii as $cat):
			$categoryParams = array($lang, $cat["id"]);
			$sub_categorii = $connection->fetch_result($categoryQuery,$categoryParams);

        $string .= '<td valign="top"> ';
			foreach($sub_categorii as $scat ):
				if ( $scat['parent_id'] == '6' ):
					$string .= '<a href="'.base_url().'pagina/'.$scat['name'].'" style="color:#018eac; text-decoration:none">'.$scat['name'].'</a><br>';

				else:
					$string .= '<a href="'.base_url().$cat['url_title'].'/'.$scat['url_title'].'" style="color:#018eac; text-decoration:none">'.$scat['name'].'</a><br>';

				endif;
			endforeach;
			$string .= '</td>';
		endforeach;
      $string .= '</tr>
    </tbody></table></td>
  </tr>';

  return $string;
}

function get_node_meta($node)
{
	$title = "";
	$meta_description = "";
	$meta_keywords = "";
	if(isset($node["cats"]) && count($node["cats"]) > 0)
	{
		if(isset($node["cats"]["sub_cats"]) && count($node["cats"]["sub_cats"]) > 0)
		{
			foreach($node["cats"]["sub_cats"] as $key => $value)
			{
				if($title == "")
				{
					$title = " - " . $value["name"];
				}
				$meta_description .= $value["name"].",";
				$meta_keywords .= $value["name"].",";
			}
		}

		if(isset($node["cats"]["cats"]) && count($node["cats"]["cats"]) > 0)
		{
			$foundTitle = false;
			foreach($node["cats"]["cats"] as $key => $value)
			{
				if($foundTitle == false)
				{
					if($title != "")
					$title = $title . " - " . $value["name"];
					$foundTitle = true;
				}
				$meta_description .= $value["name"].",";
				$meta_keywords .= $value["name"].",";
			}
		}

		if(isset($node["cats"]["super_cats"]) && count($node["cats"]["super_cats"]) > 0)
		{
			$foundTitle = false;
			foreach($node["cats"]["super_cats"] as $key => $value)
			{
				if($foundTitle == false)
				{
					$title = $title . " - " . $value["name"];
					$foundTitle = true;
				}
				$meta_description .= $value["name"].",";
				$meta_keywords .= $value["name"].",";
			}
		}
	}
	$meta = array(
				"title" => htmlentities(ucwords(strip_tags($title))),
				"meta_description" => htmlentities(ucfirst(strip_tags($meta_description))),
				"meta_keywords" => htmlentities(strtolower(strip_tags($meta_keywords))));
	return $meta;
}
	
	function get_tematics_list($categoryID, $selectedID = 0)
	{
		$tematics = $connection->fetch_result("SELECT t.* FROM tematics t WHERE t.id IN 
												(SELECT tematic_id FROM categories_tematics 
													WHERE category_id = ?) 
												ORDER BY t.name", array($categoryID));
		$selectedName = "&nbsp";
		if($selectedID != 0)
		{
			foreach($tematics as $tematic)
			{
				if($selectedID == $tematic["id"])
				{
					$selectedName = $tematic["name"];
				}
			}
		}
		if(count($tematics) > 0):
		?>
		<div class = 'name' ><?php echo l("tematic"); ?></div>
			<div class="styled-select ">
				<div class = 'dummy'
					val = '<?php echo $selectedID; ?>' >
					<?php echo $selectedName; ?>
				</div>
				<select>
					<option style = 'display:none;'></option>
				<?php foreach($tematics as $tematic):
				?>
					<option value = '<?php echo $tematic['id']; ?>'>
						<?php echo $tematic['name']; ?>
					</option>
				<?php
					endforeach; ?>
				</select>
			</div>
		<?php endif;
	}

	function mysqlPrep($string)
	{
		global $connection;
		return $connection->qoute($string);
	}

	function validateEmail($email) {
	  // First, we check that there's one @ symbol,
	  // and that the lengths are right.
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	  		return false;
		}
		return true;
	  
	 /* if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
	    // Email invalid because wrong number of characters
	    // in one section or wrong number of @ symbols.
	    return false;
	  }
	  // Split it into sections to make life easier
	  $email_array = explode("@", $email);
	  $local_array = explode(".", $email_array[0]);
	  for ($i = 0; $i < sizeof($local_array); $i++) {
	    if
	(!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&
	↪'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$",
	$local_array[$i])) {
	      return false;
	    }
	  }
	  // Check if domain is IP. If not,
	  // it should be valid domain name
	  if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
	    $domain_array = explode(".", $email_array[1]);
	    if (sizeof($domain_array) < 2) {
	        return false; // Not enough parts to domain
	    }
	    for ($i = 0; $i < sizeof($domain_array); $i++) {
	      if
	(!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|
	↪([A-Za-z0-9]+))$",
	$domain_array[$i])) {
	        return false;
	      }
	    }
	  }
	  return true;*/
	}

	function has_contract($node_id){
		global $connection;
		$time = time();
		
		$hasContract = false;
		
		$node = $connection->fetch_row("nodes", $node_id);
		if($node)
		{
			if($node["type"] == "event")
			{
				
				$location = $connection->fetch_one("SELECT edn.node_id AS id, n.demo FROM event_day_nodes edn
													LEFT JOIN nodes n ON n.id = edn.node_id
													LEFT JOIN event_days ed ON edn.event_day_id = ed.id
													WHERE ed.event_node_id = ?", array($node_id));
				$node = $location;
			}
			elseif($node["type"] == "activity" || $node["type"] == "offer")
			{
				$location = $connection->fetch_one("SELECT rn.node_id AS id, n.demo FROM related_nodes rn
										LEFT JOIN nodes n ON n.id = rn.node_id 
										WHERE rn.relation_node_id = ?", array($node["id"]));
				
				$node = $location;
			}
			elseif($node["type"] == "tour")
			{
				
			}
			
			$contract = $connection->fetch_one("SELECT c.id FROM contracts c, anexe a 
											WHERE c.id = a.contract_id 
												AND a.node_id = ?  
												AND c.start <= '{$time}' 
												AND c.end >= '{$time}'", array($node["id"]));
			if($contract || $node["demo"] == 1)
			{
				$hasContract = true;
			}
			
		}
		//print_r($node);
		//echo ($hasContract ? "has" : "not");
		return $hasContract;
	}
	
	function sanitize($string, $separator = '-') {
			
		/*$search  = array('ă', 'î', 'ș', 'ț', 'â', 'Ă', 'Î', 'Ș', 'Ț', 'Â', '&#536;', '&#537;', '&#538;', '&#539;','&#350;', '&#351;', '&#354;','&#355;');
		$replace = array('a', 'i', 's', 't', 'a', 'A', 'I', 'S', 'T', 'A', 'S', 's', 'T', 't', 'S', 's', 'T', 't');
		$string = str_replace($search, $replace, $string);*/
		
		/*$rule = 'NFD; [:Nonspacing Mark:] Remove; NFC';
		
		$myTrans = Transliterator::create($rule); 
		
		$string = $myTrans->transliterate($string);
		
 		$string = preg_replace( '/[«»""!?,.!@£$%^&*{};:()]+/', '', $string );
	   	$string = preg_replace( '/[«»""!?,.!@£$%^&*{};:()]+/', '', $string );
	   	$string = strtolower($string);
	   	
	   	$slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
	   	$slug = preg_replace('/[-]+/', '-', $slug);
		
		$slug = trim($slug, "-");
		
	   	return $slug;*/
	   	
		$bad = array(
	    'À','à','Á','á','Â','â','Ã','ã','Ä','ä','Å','å','Ă','ă','Ą','ą',
	    'Ć','ć','Č','č','Ç','ç',
	    'Ď','ď','Đ','đ',
	    'È','è','É','é','Ê','ê','Ë','ë','Ě','ě','Ę','ę',
	    'Ğ','ğ',
	    'Ì','ì','Í','í','Î','î','Ï','ï',
	    'Ĺ','ĺ','Ľ','ľ','Ł','ł',
	    'Ñ','ñ','Ň','ň','Ń','ń',
	    'Ò','ò','Ó','ó','Ô','ô','Õ','õ','Ö','ö','Ø','ø','ő',
	    'Ř','ř','Ŕ','ŕ',
	    'Š','š','Ş','ş','Ś','ś',
	    'Ť','ť','Ť','ť','Ţ','ţ',
	    'Ù','ù','Ú','ú','Û','û','Ü','ü','Ů','ů',
	    'Ÿ','ÿ','ý','Ý',
	    'Ž','ž','Ź','ź','Ż','ż',
	    'Þ','þ','Ð','ð','ß','Œ','œ','Æ','æ','µ',
	    '”','“','‘','’',"'","\n","\r",'_');
	
	    $good = array(
	    'A','a','A','a','A','a','A','a','Ae','ae','A','a','A','a','A','a',
	    'C','c','C','c','C','c',
	    'D','d','D','d',
	    'E','e','E','e','E','e','E','e','E','e','E','e',
	    'G','g',
	    'I','i','I','i','I','i','I','i',
	    'L','l','L','l','L','l',
	    'N','n','N','n','N','n',
	    'O','o','O','o','O','o','O','o','Oe','oe','O','o','o',
	    'R','r','R','r',
	    'S','s','S','s','S','s',
	    'T','t','T','t','T','t',
	    'U','u','U','u','U','u','Ue','ue','U','u',
	    'Y','y','Y','y',
	    'Z','z','Z','z','Z','z',
	    'TH','th','DH','dh','ss','OE','oe','AE','ae','u',
	    '','','','','','','','-');
	
	    // convert special characters
	    $text = str_replace($bad, $good, $string 		);
	      
	    // convert special characters
	    $text = utf8_decode($text);
	    $text = htmlentities($text);
	    $text = preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde);/', '$1', $text);
	    $text = html_entity_decode($text);
	    
	    $text = strtolower($text);
	
	    // strip all non word chars
	    $text = preg_replace('/\W/', ' ', $text);
	
	    // replace all white space sections with a separator
	    $text = preg_replace('/\ +/', $separator, $text);
	
	    // trim separators
	    $text = trim($text, $separator);
	    //$text = preg_replace('/\-$/', '', $text);
	    //$text = preg_replace('/^\-/', '', $text);
	        
	    return $text;
	}

	function checkVisit($ip, $nodeID)
	{
		global $connection;
		$visitCheck = true;
		
		$yesterday = mktime(0, 0, 0, date("m"), date("d")-1, date("Y"));
		$ipCheck = $connection->fetch_one("SELECT * FROM nodes_visits WHERE ip = ? AND node_id = ?", array($ip, $nodeID));
		if(!$ipCheck)
		{
			$visitCheck = false;
			
			$ipCheck["ip"] = $ip;
			$ipCheck["last_visit"] = time();
			$ipCheck["node_id"] = $nodeID;
			$ipCheck["id"] = $connection->insert("nodes_visits", $ipCheck);
		}
		elseif($ipCheck["last_visit"] < $yesterday)
		{
			$visitCheck = false;
		}
		
		if(!$visitCheck)
		{
			$params[] = time();
			$params[] = $ip;
			$params[] = $nodeID;
			
			$connection->execute_query("UPDATE nodes_visits SET last_visit = ? WHERE ip = ? AND node_id = ?", $params);
			
			updateMonthlyVisits($nodeID);
		}
	}
	
	function updateMonthlyVisits($nodeID)
	{
		global $connection;
		
		$month = strtotime(date('Y-m-01'));
		$params = array($nodeID, $month);
		$query = "SELECT * FROM nodes_visits WHERE node_id = ? AND last_visit >= ?";
		$visits = $connection->fetch_result($query, $params);
		
		if(!$visits)
		{
			$visitCount = 0;
		}
		else
		{
			$visitCount = count($visits);
		}
		
		$year = date("Y");
		$month = date("m");
		
		$params = array($month, $year, $nodeID);
		
		$analytics = $connection->fetch_one("SELECT * FROM analytics WHERE month = ? AND year = ? AND node_id = ?", $params);
		if(!$analytics)
		{
			$analytics["month"] = $month;
			$analytics["year"] = $year;
			$analytics["node_id"] = $nodeID;
			$analytics["visitors"] = $visitCount;
			$analytics["pageviews"] = $visitCount;
			
			$connection->insert("analytics", $analytics);
		}
		else
		{
			$up["id"] = $analytics["id"];
			$up["visitors"] = $visitCount;
			$up["pageviews"] = $visitCount;
			
			$connection->update("analytics", $up);
		}
	}

	function summarize_description($description, $character_count)
	{
		$description = trim(strip_tags($description));
		$description = preg_replace('/\s+/', ' ',$description);
		if(strlen($description) > (int)$character_count+1)
		{
			$line=$description;
			if (preg_match('/^.{1,'.$character_count.'}\b/s', $description, $match))
			{
		    	$line=$match[0];
			}
				
			return $line."...";
		}
		
		return $description;
	}
	
	function is_json($string)
	{
		if (is_object(json_decode($string))) 
    	{ return true; } 
	}
	
	function nume_zi($i, $lang = "ro", $short = true)
	{
    	$days["ro"] = array('luni','marti','miercuri','joi','vineri','sambata','duminica');
    	$days["en"] = array('monday','tuesday','thursday','wendesday','friday','saturday','sunday');
		$days["de"] = array('luni','marti','miercuri','joi','vineri','sambata','duminica');
		$days["fr"] = array('luni','marti','miercuri','joi','vineri','sambata','duminica');
    	
    	$day = $days[$lang][$i-1];
    	
    	return ($short ? substr($day, 0, 2) : $day); 

   	}
	
	function nume_luna($i, $lang = "ro", $short = true)
	{
    	$months["ro"] = array('ianuarie','februarie','martie','aprilie','mai','iunie','iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie');
    	$months["en"] = array('january','february','march','april','may','june','july', 'august', 'september', 'october', 'november', 'december');
		$months["de"] = array('ianuarie','februarie','martie','aprilie','mai','iunie','iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie');
		$months["fr"] = array('ianuarie','februarie','martie','aprilie','mai','iunie','iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie');
    	
    	$month = $months[$lang][$i-1];
    	
    	return ($short ? substr($month, 0, 3) : $month); 

   	}

	function full_date_string($date, $lang = "ro", $short = true)
	{
		$fullDate = date("d", $date) . " ";
		$fullDate .= nume_luna(date("n", $date), $lang, $short) . " ";
		$fullDate .= date("Y", $date).", ";
		$fullDate .= nume_zi(date("N", $date), $lang, $short);
		
		return $fullDate;
	}

?>