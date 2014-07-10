<?php
	function search_nodes( $args = array(
							"nume"=>false,
							"cats"=>array(
										"1"=>false,
										"2"=>false,
										"3"=>false
									),
							"town_id"=>false,
							"options"=>array(),
							"page"=>0,
							"sort"=>"rank",
							"rank_list"=>false,
							"sort_direction"=>"desc",
							"theme_id"=>0,
							"results_returned"=>16,
							"return_sql"=>false,
							"capacity"=>false,
							"age_restriction"=>0,
							"type" => false,
							"is_open" => false,
							"special_filter"=>false
						)
					)
		{
			
			global $connection;
			
			
			
			if($args['cats']['2']==15){
				$order_sup = ", nodes.percent DESC , nodes.tour_price DESC ";
			}else{
				$order_sup='';
			}
		
			$def_args = array(
				"nume"=>false,
				"cats"=>array(
							"1"=>false,
							"2"=>false,
							"3"=>false
						),
				"town_id"=>false,
				"options"=>array(),
				"page"=>0,
				"sort"=>"rank",
				"theme_id"=>0,
				"rank_list"=>false,
				"sort_direction"=>"desc",
				"results_returned"=>8,
				"return_sql"=>false,
				"capacity"=>false,
				"age_restriction"=>0,
				"is_open"=>false,
				"special_filter"=>false
				
			);
					
			foreach ( $args as $key=>$arg )
			{
				if ( $arg )
				{
					$def_args[$key] = $arg;
				}
			}
		
		
			$lang = defined("LANG") ? LANG : 'ro';
			$lang = $connection->qoute($lang);

			$areaID = defined(AREA_ID) ? AREA_ID : 1;
			$areaID = $connection->qoute($areaID);
		
			if(isset($_COOKIE["scripts-enabled"]) &&  $def_args['results_returned'] > 0)
			{
				$offset = $def_args['page'] * $def_args['results_returned'];
				$limit = $def_args['results_returned'];
			}
			else
			{
				$offset = $limit = false;
			}
			
			
		
			$query_arr = array();
		
		
			if ( $def_args['nume'] )
			{
				$name = mysqlPrep("%{$def_args['nume']}%");
		 		$query_arr[] = " node_details.name LIKE {$name} "; 
			}
			
			// get last cat
			$best_cat = '';
			if ( $def_args['cats'] )
			{
				foreach ( $def_args['cats'] as $c )
				{
					if($c){ $best_cat = $c; }
				}
			}
	
			if ( $best_cat )
			{
				$best_cat = $connection->qoute($best_cat);
				$query_arr[] = " categories.id = {$best_cat} ";
			}
			
			if ( isset($def_args["type"]) && $def_args["type"])
			{
				$type = $connection->qoute($def_args["type"]);
				$query_arr[] = " nodes.type = {$type} ";
			}
			
			if($def_args["age_restriction"] == 0)
			{
				$query_arr[] = " nodes.id NOT IN (SELECT nc.node_id AS id FROM node_categories nc, categories c WHERE nc.category_id = c.id AND c.age_restriction = 1) ";
			}
			
			$string ='';
			
			if($def_args['capacity'] == 20 ){
				$query_arr[]= "  nodes.capacitate < 20 ";
			}else if($def_args['capacity'] == 50){
				$query_arr[] = " nodes.capacitate < 50 ";
			}else if($def_args['capacity'] == 100){
				$query_arr[] = " nodes.capacitate < 100 ";
			}else if($def_args['capacity'] == 101){
				$query_arr[] = " nodes.capacitate > 100 ";
			}
			
			$op_string = "";
			
			if (isset($def_args['options']))
			{
				$s1_arr = array();
				$s2_arr = array();
				$jj = 1;
				foreach ($def_args['options'] as $o )
				{
					if($o != "")
					{
						$s1_arr[] = "LEFT OUTER JOIN nodes_facilities t$jj ON t$jj.node_id = nodes.id";
						$s2_arr[] = "t$jj.facility_id = {$o}";
		
						$jj++;
					}
					
				}
	
					$s1_string = implode ( " ", $s1_arr );
					$s2_string = implode ( " AND ", $s2_arr );
					if($s2_string != "")
					{
						$op_string = " AND nodes.id in (SELECT DISTINCT nodes.id
						FROM nodes
						$s1_string
						WHERE
						$s2_string)";
					}
					
	
	
			}
			
			$townID = 1;
			
			if ( $def_args['town_id'] )
			{
				$townID = $connection->qoute($townID);
			}
			
			$query_arr[] = " nodes.town_id = {$townID} ";
			
			if($def_args['theme_id'] > 0)
			{
				$themeID = $connection->qoute($def_args['theme_id']);
				$query_arr[] = " nodes.id IN (SELECT node_id FROM nodes_tematics WHERE tematic_id = {$themeID}) ";
			}
			
			if(isset($def_args['is_open']) && $def_args['is_open'] != false)
			{
				$currentDay = date("N");
				$currentHour = date("H:i");
				$query_arr[] = " nodes.id IN (SELECT node_id FROM node_working_hours WHERE day = '{$currentDay})' AND start <= '{$currentHour}' AND end >= '{$currentHour}') ";
			}
			
			if(isset($def_args['special_filter']) && $def_args['special_filter'] > 0)
			{
				$def_args['special_filter'] = $connection->qoute($def_args['special_filter']);
				$query_arr[] = " nodes.id IN (SELECT id FROM nodes where special_type = {$def_args['special_filter']}) ";
			}
			
	
			if($args['cats']['2'] != 175 
				&& $args['cats']['2'] != 177 
				&& $args['cats']['2'] != 172 
				&& $args['cats']['2'] != 179)
			{
				$query_string[] = " node.type != 'offer'";	
			}
			else
			{
				//$query_string[] = " node.start <= {$time} ";
				//$query_string[] = " node.end >= {$time}";	
			}
			
			if($args['cats']['2'] != 174 
				&& $args['cats']['2'] != 176 
				&& $args['cats']['2'] != 171 
				&& $args['cats']['2'] != 178 )
			{
				$query_arr[] = " nodes.type != 'activitiy' ";	
			}
			else
			{
				$time = time();
				$query_arr[] = " node.start <= {$time} ";
				$query_arr[] = " node.end >= {$time}";	
			}
			
			if(isset($add_some[0]) ||	isset($add_some[1]))
			{
				$query_string1 = implode( " AND ", $add_some );
				if($query_string1 != "")
				{
					$query_arr[]= " nodes.id IN ( SELECT node_id FROM node_categories WHERE node_categories.category_id!=177 AND node_categories.category_id!=174 ) ";
				}
				
			}
			
			$query_string = implode( " AND ", $query_arr );
	
			if ( !$query_string && $query_string == "" ) { $query_string = 1; }
			
			if ( @$args["nume"] == false && count(@$args["options"]) == 0
				&& @$args["sort"] == "rank" && @$args["rank_list"] != false 
				&& @$args["theme_id"] == 0 && @$args["return_sql"] == false 
				&& @$args["capacity"] == false && @$args["is_open"] == false 
				&& @$args["special_filter"] == false)
			{
				$query = "SELECT * FROM rank_lists";
				$queryParams[] = "1";
				if($townID)
				{
					$queryParams[] = " town_id = {$townID} ";
				}
				if($best_cat)
				{
					$queryParams[] = " category_id = {$best_cat} ";
				}
				if($lang)
				{
					$queryParams[] = " lang = {$lang} ";
				}
				
				$query .= " WHERE ".implode(" AND ", $queryParams);
				$rankList = $connection->fetch_one($query);
				if(isset($rankList["id"]) && $rankList["rank_list"] != "")
				{
					$items = array();
					$listItems = array();
					
					$list = json_decode($rankList["rank_list"], true);
					if(count($list) > 0)
					{
						if($offset != false || $limit != false)
						{
							if(count($list) > $offset+1)
							{
								for($i = $offset; $i < ($offset + $limit); $i++)
								{
									if(isset($list[$i]))
									{
										$listItems[] = $list[$i];
									}
								}
							}
						}
						else
						{
							foreach($list as $nodeID)
							{
								$listItems[] = $nodeID;
							}
						}
						if(count($listItems) > 0)
						{
							$items = get_node_by_id($listItems);
						}
					}
					
					return $items;
				}
			}

			$sql = "SELECT DISTINCT nodes.id,
				( select SUM(score) FROM ratings WHERE ratings.node_id = nodes.id ) as ratings_score,
				( select COUNT( id ) FROM ratings WHERE ratings.node_id = nodes.id ) as rating_total
				FROM nodes
				LEFT OUTER JOIN anexe a ON a.node_id = nodes.id 
				JOIN node_categories ON nodes.id = node_categories.node_id
				JOIN node_details ON nodes.id = node_details.node_id
				JOIN categories ON node_categories.category_id = categories.id
				JOIN towns ON towns.id = nodes.town_id
	
				WHERE $query_string ".@$op_string." AND node_details.lang = $lang AND nodes.active = '1' 
						AND towns.area_id = {$areaID} ";
			$additional_sort  = "";
			
			if ( $def_args['sort'] )
			{
				if ( $def_args['sort'] == 'rating' ){ $sort_param = ' ( ratings_score / rating_total ) '; }
				if ( $def_args['sort'] == 'rank' ){ $sort_param = ' rank '; }
				if ( $def_args['sort'] == 'alfabetic' ){ $sort_param = 'node_details.name'; }
	
				$sql .= "ORDER BY $sort_param $def_args[sort_direction] ";
	
				if ( $def_args['sort'] == 'pret' ){
				$sql = "SELECT
						nodes.id,
						( select SUM( score) FROM ratings WHERE ratings.node_id = nodes.id ) as ratings_score,
						( select COUNT( id ) FROM ratings WHERE ratings.node_id = nodes.id ) as rating_total,
						MIN(offer_rooms.price_".date("N", time() + 24 * 60 * 60).") as next_day_price2
						FROM nodes
						LEFT OUTER JOIN anexe a ON a.node_id = nodes.id 
						JOIN node_categories ON nodes.id = node_categories.node_id
						JOIN node_details ON nodes.id = node_details.node_id
						JOIN categories ON node_categories.category_id = categories.id
						JOIN towns ON towns.id = nodes.town_id
						LEFT OUTER JOIN stay_offers ON nodes.id = stay_offers.node_id
						LEFT OUTER JOIN offer_rooms ON offer_rooms.stay_offer_id = stay_offers.id
	
						WHERE $query_string $op_string
	
						AND node_details.lang = {$lang}
						AND nodes.active = '1'
						AND towns.area_id = {$areaID}
						AND
						(
							(stay_offers.active =  1
							AND stay_offers.end > ".(time() + 24 * 60 * 60)."
							AND offer_rooms.active = 1
							AND offer_rooms.price_1 > 0 )
							OR stay_offers.id is null
						)
						{$string}
						GROUP BY nodes.id
						ORDER BY a.id DESC, offer_rooms.active DESC,
						next_day_price2 $def_args[sort_direction]
						";
				}
			}
	
			if ( $def_args['rank_list'] && !$def_args['return_sql'] )
			{
				$sql = "SELECT DISTINCT nodes.id,
					(
						CASE
							WHEN ranking.instance = '$def_args[rank_list]' THEN ranking.rank
							WHEN ranking.instance is null THEN 0
							WHEN ranking.instance != 'Dorm' THEN 0
							ELSE 0
						END
					) as calc_rank
				FROM nodes
				LEFT OUTER JOIN ranking ON nodes.id = ranking.node_id			
				JOIN node_categories ON nodes.id = node_categories.node_id
				JOIN node_details ON nodes.id = node_details.node_id
				JOIN categories ON node_categories.category_id = categories.id
				JOIN towns ON towns.id = nodes.town_id
	
				WHERE $query_string ".@$op_string."  AND node_details.lang = {$lang}
				AND nodes.active = '1'
				AND towns.area_id = {$areaID}
				{$string}
				ORDER BY   nodes.has_stay_offer DESC, calc_rank DESC 
				";
			}else 	if ( $def_args['rank_list'] && $def_args['return_sql'] )
			{
				$sql = "SELECT DISTINCT nodes.id
				FROM nodes
				LEFT OUTER JOIN ranking ON nodes.id = ranking.node_id
				JOIN node_categories ON nodes.id = node_categories.node_id
				JOIN node_details ON nodes.id = node_details.node_id
				JOIN categories ON node_categories.category_id = categories.id
				JOIN towns ON towns.id = nodes.town_id
				WHERE $query_string $op_string  AND node_details.lang = $lang
				AND nodes.active = '1'
				AND towns.area_id = {$areaID}
				{$string}
				";
	
			}
	
			if($def_args['return_sql']){
				return "SELECT DISTINCT nodes.id
				FROM nodes
				JOIN node_categories ON nodes.id = node_categories.node_id
				JOIN node_details ON nodes.id = node_details.node_id
				JOIN categories ON node_categories.category_id = categories.id
				JOIN towns ON towns.id = nodes.town_id
	
				WHERE $query_string $op_string AND node_details.lang = $lang AND nodes.active = '1'
				AND towns.area_id = {$areaID} {$string}  
				";
	
			}
			
			$sql .=$order_sup;
			 // $sql .=' nodes.has_stay_offer DESC ';
			// echo $sql;
			// exit();
			if($offset != false || $limit != false)
			{
				$sql .=	"LIMIT $offset, $limit";
			}
			
			// echo $sql;
			// exit();
			$item_ids = $connection->fetch_result($sql);
			
			if (!$item_ids)
			{
				return array();
			}
	
			$ids = array();
			$items = array();
			
			foreach ( $item_ids as $item_id )
			{
				$ids[$item_id["id"]] = $item_id["id"];	
			}
			
			$items = get_node_by_id($ids);
	
			return $items;
	}
	
	function search_count($nume = false, $cats = array(), $options = array(), $town_id = false, $age_restriction = 0)
	{
		global $connection;
		$lang = defined("LANG") ? LANG: 'ro';
		$lang = $connection->qoute($lang);
		
		$areaID = defined(AREA_ID) ? AREA_ID : 1;
		$areaID = $connection->qoute($areaID);
		
		
		$query_arr = array();
	
		if ( $nume )
		{
			$nume = $connection->qoute("%{$nume}%");
			$query_arr[] = " node_details.name LIKE  $nume"; 
		}
		// get last cat
		$best_cat = '';
		if ( $cats ){
			foreach ( $cats as $c )
			{
				if ( $c ){ $best_cat = $c; }
			}
		}
	
	
		if ( $best_cat )
		{
			$best_cat = $connection->qoute($best_cat);
			$query_arr[] = " categories.id =  $best_cat ";
		}
		
		if($age_restriction == 0)
		{
			$query_arr[] = " nodes.id NOT IN (SELECT nc.node_id AS id FROM node_categories nc, categories c WHERE nc.category_id = c.id AND c.age_restriction = 1) ";
		}
	
		////get options
		//if ( $options )
		//{
		//	foreach ($options as $o )
		//	{
		//			$o = str_replace("option_", '', $o);
		//			$query_arr[] = " nodes_facilities.facility_id ='{$o}' ";
		//	}
		//}
	
		if ( $options )
		{
	
			$s1_arr = array();
			$s2_arr = array();
			$jj = 1;
			foreach ($options as $o )
			{
	
				$o = $connection->qoute(str_replace("option_", '', $o));
				if($o != "")
				{
					$s1_arr[] = "LEFT OUTER JOIN nodes_facilities t$jj ON t$jj.node_id = nodes.id";
					$s2_arr[] = "t$jj.facility_id = $o";
		
					$jj++;
				}
	
				
			}
	
				$s1_string = implode ( " ", $s1_arr );
				$s2_string = implode ( " AND ", $s2_arr );
				if($s2_string != "")
				{
					$op_string = " AND nodes.id in (SELECT DISTINCT nodes.id
					FROM nodes
					$s1_string
					WHERE
					$s2_string)";
				}
				
	
	
		}
	
		if ( $town_id )
		{
			$town_id = $connection->qoute($town_id);
			$query_arr[] = " nodes.town_id = {$town_id} ";
		}
	
		$query_arr[] = " node_details.lang = {$lang} ";
		$query_string = implode( " AND ", $query_arr );
	
		if ( !$query_string ) { $query_string = 1; }
	
		$sql = "SELECT DISTINCT nodes.id
			FROM nodes
			JOIN node_categories ON nodes.id = node_categories.node_id
			JOIN categories ON node_categories.category_id = categories.id
			JOIN node_details ON nodes.id = node_details.node_id
			JOIN towns ON nodes.town_id = towns.id
			WHERE $query_string ".@$op_string."
			AND nodes.active = '1'
			AND towns.area_id = {$areaID}
			";
	
	
		return $connection->num_rows($sql);
	}
?>