<?php 
//http://en.wikipedia.org/w/api.php?format=xml&action=query&prop=revisions&titles=Portal:Current_events/2014_February_1&rvprop=timestamp|user|comment|content
//http://en.wikipedia.org/w/api.php?format=txt&action=query&prop=revisions&titles=Events%20by%20month|2011|2012&rvprop=content|timestamp|user|comment|content
ini_set('memory_limit', '1024M'); 
ini_set('max_execution_time', '3000'); 

//die;
$timeline = new history_timeline_genertor();
$timeline->init();
die;
class  history_timeline_genertor {
	
	
	public $url ;
	public $_data ;
	public $_segments ;
	public $_dataContent = '' ;
	public $_data_for_prepare = array();
	public $_data_prepared  = array() ;
	public $yearsQueryString = '';
	
	
	
	public function fetch(){
		$data=array();
		for($i=1;$i<=2014;$i++){
			$this->yearsQueryString .= "Events%20by%20month|$i|";
		
		$url = "http://en.wikipedia.org/w/api.php?action=query&format=json&titles=Events%20by%20month|$i&prop=revisions&rvprop=content";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, 'MyBot/1.0 (http://www.mysite.com/)');
		
		$result = curl_exec($ch);
		
		if ($result) {
		 // exit('cURL Error: '.curl_error($ch));
			print "$i<br>";;
			$result = json_decode( $result ,true);
			$data[]=  array_values($result['query']['pages']);
			//die;
		}else{
			print "Bayez $i <br>";
		}
		}
	 		
		// for($i=1951;$i<=date('Y');$i++){
			// $this->yearsQueryString .= "Events%20by%20month|$i|";
// 		
		// $url = "http://en.wikipedia.org/w/api.php?action=query&format=json&titles=Events%20by%20month|$i&prop=revisions&rvprop=content";
		// $ch = curl_init();
		// curl_setopt($ch, CURLOPT_URL, $url);
		// curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		// curl_setopt($ch, CURLOPT_USERAGENT, 'MyBot/1.0 (http://www.mysite.com/)');
// 		
		// $result = curl_exec($ch);
// 		
		// if ($result) {
		 // // exit('cURL Error: '.curl_error($ch));
			// //print '<pre>';
			// $result = json_decode( $result ,true);
			// $data[]=  array_values($result['query']['pages']);
			// }
		// }
		$this->setData($data);
		//print_r($data);
		
	}
	
	
	public function getData(){
		return $this->_data ;
	}
	public function setData($data){
		$this->_data = $data ;	
	}
	function getDataContent(){
		$data = $this->getData();
		$dataContent = array();
		
		for ($i=1; $i < count($data) ; $i++) {
			for ($d=0; $d < count($data[$i])  ; $d++) {
				if(isset($data[$i][$d]['revisions'])){
					$dataContent[$data[$i][$d]['title']] =  $data[$i][$d]['revisions'][0]['*'];
				} 
			}		
		}
		
		$this->_dataContent = $dataContent;
		//return $str;
	}
	public function init(){
		$this->fetch();
		$this->removeBracletsData();
		//print $this->getData();
		$this->categorize();
		$this->getElementsFromSegments();
	}
	public function clean_me($matches){
		foreach ($matches as $key => $value) {
			$matches[$key]='' ;
		}
		return $matches ;
	}
	public function removeBracletsData(){
		
		$this->getDataContent();
		$data = $this->_dataContent ;
		$data = preg_replace("/\{{(.+)\}}/", '' , $data);
		$this->setData($data);
	}
	//public function segmentizeByDate()
	public function categorize(){
		$segments = array();
		$dataContent =  $this->_dataContent;
		foreach ($dataContent as $title => $data) {
			//$regex = '/(?:(?Jms)^={2,2}\\s{1,1}\\D{0,}\\s{1,1}={2,2}$)/';
			$regex = '/(?:(?Jms)^={2,2}[\\w\\s]{0,}={2,2}$)/';
			preg_match_all($regex, $data , $keys);
			$contents = preg_split($regex , $data);
			foreach ($keys[0] as $key => $value) {
				if(isset($value) && !empty($contents[$key+1])){
					if(strpos($keys[0][$key] , '== References ==') === FALSE){
						$segments["$title"][trim(str_replace(' ', '_',  str_replace('=', '',$keys[0][$key]) ),'_')] = $contents[$key+1];	
					}	
				}
			}
		}
		$this->_segments = $segments;
		
		//	print_r($segments);
	}
	public function getElementsFromSegments(){
		$segments = $this->_segments;
		$arr = array();
		$finalArray =  array() ;
		$finalArray['id']='the_story_of_history' ;
		$finalArray['title'] ="the story of history";
		$finalArray['focus_date'] ="1540-01-01 12:00:00";
		$finalArray['initial_zoom'] ="45";
		$finalArray['color'] = "#82530d";
		$finalArray['size_importance'] = 'true' ;
		$finalArray['initial_zoom'] = '45' ; 
		$finalArray['description'] = "the story of history";; 
		$beforePrepareEventPart = array() ;
		$legend=array();
		foreach ($segments as $k => $seg) {
			
			foreach ($seg as $cKey => $category){
				
			$arr["$k"][$cKey] = explode('* ', $category)	;
			}
		}
		foreach ($arr as $k => $seg) {
			$re1='((?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Sept|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?))';	# Month 1
			$re2='( )';	# White Space 1
			$re3='(\\d+)';	# Integer Number 1
			foreach ($seg as $key => $eventArr) {
				unset($arr[$k][$key][0]);	
				foreach ($eventArr as $Ekey => $event) {
					preg_match_all("/".$re1.$re2.$re3."/is", $event, $matches);
					//print_r($matches);
					if(isset($matches[1][0]) && isset($matches[2][0]) && isset($matches[3][0]) ){
						//=== word ===	
						$WSe1='(=)';	# Any Single Character 1
						$WSe2='(=)';	# Any Single Character 2
						$WSe3='(=)';	# Any Single Character 3
						$WSe4='( )';	# Any Single Character 4
						$WSe5='((?:[A-Z][a-z]+))';	# Word 1$Mre5='(=)';
						$WSe6='( )';	# Any Single Character 4	
						$WSe7='(=)';	# Any Single Character 5
						$WSe8='(=)';	# Any Single Character 6	
						$WSe9='(=)';	# Any Single Character 6	
						
						//===word===	
						$Wre1='(=)';	# Any Single Character 1
						$Wre2='(=)';	# Any Single Character 2
						$Wre3='(=)';	# Any Single Character 3
						$Wre4='((?:[A-Z][a-z]+))';	# Word 1$Mre5='(=)';	
						$Wre5='(=)';	# Any Single Character 5
						$Wre6='(=)';	# Any Single Character 6	
						$Wre7='(=)';	# Any Single Character 6	
						//==== word ====
						$WEqe1='(=)';	# Any Single Character 1
						$WEqe2='(=)';	# Any Single Character 2
						$WEqe3='(=)';	# Any Single Character 3
						$WEqe4='(=)';	# Any Single Character 3
						$WEqe5='( )';	# Any Single Character 4
						$WEqe6='((?:[A-Z][a-z]+))';	# Word 1
						$WEqe7='( )';	# Any Single Character 5
						$WEqe8='(=)';	# Any Single Character 6
						$WEqe9='(=)';	# Any Single Character 7
						$WEqe10='(=)';	# Any Single Character 8
						$WEqe11='(=)';	# Any Single Character 8
						
						
						
						$month1=$matches[1][0];
						$ws1=$matches[2][0];
						$int1=$matches[3][0];
						$str =  preg_replace("/".$WEqe1.$WEqe2.$WEqe3.$WEqe4.$WEqe5.$WEqe6.$WEqe7.$WEqe8.$WEqe9.$WEqe10.$WEqe11."/is", '', str_replace('[['.$month1.$ws1.$int1.']]', '' , $event)); 
						$str =  preg_replace("/".$Wre1.$Wre2.$Wre3.$Wre4.$Wre5.$Wre6.$Wre7."/is", '',$str);
						$str =  preg_replace("/".$WEqe1.$WEqe2.$WEqe3.$WEqe4.$WEqe5.$WEqe6.$WEqe7.$WEqe8.$WEqe9.$WEqe10.$WEqe11."/is", '',$str);
						
						//$finalArray[$k][$key][$month1][$int1] = $str;
						
						//URL 
						$URlre1='(.)';	# Any Single Character 1
						$URlre2='(url)';	# Word 1
						$URlre3='(=)';	# Any Single Character 2
						$URlre4='((?:http|https)(?::\\/{2}[\\w]+)(?:[\\/|\\.]?)(?:[^\\s"]*))';	# HTTP URL 1
						$url = '';
						if ($c=preg_match_all ("/".$URlre1.$URlre2.$URlre3.$URlre4."/is", $str, $matches)){
							$httpurl1=$matches[4][0];
							$url =  "$httpurl1";
						}
						//date
						$mdate = "$k-$month1-$int1" ;
						try {
							$month = date('m',strtotime($mdate));
							$date = new DateTime("$k-$month-$int1 12:00:00");
						} catch (Exception $e) {
							$month = date('m',strtotime($mdate));
							$date = new DateTime("$k-$month-1 12:00:00");
							// exit(1);
						}
						if($date!=''){
							$title =  (strlen($str) > 23) ? substr($str,0,88).'...' : $str ;
							$title = str_replace(' &ndash;', '', $title);
							$title =  htmlentities( ltrim($title,' '));
							$strdate =$date->format('Y-m-d H:i:s');
							
							$desc = str_replace(' &ndash;', '', $str);
							$desc = ltrim($desc,' ');
							
							$beforePrepareEventPart['id']= "$Ekey" ;
							
							$beforePrepareEventPart['title']= "$title";
							
							
							$beforePrepareEventPart['description'] = "$desc";
							
							$beforePrepareEventPart['startdate'] = "$strdate";
							$beforePrepareEventPart['enddate'] = "$strdate";
							$beforePrepareEventPart['date_display'] = "mo";
							$beforePrepareEventPart['link']= "$url";
							$beforePrepareEventPart['importance'] = "47";
							$beforePrepareEventPart['icon'] = "$key.png"; 
							
							$finalArray['events'][] = $beforePrepareEventPart;
							
						}
						
						
					}
				}
			}
		}
		$legendsKeys = array_keys($seg);
		foreach ($legendsKeys as $key => $value) {
			array_push($legend,array('title'=>"$value",'icon'=>"$value.png") );
		}
		
		$finalArray['legend'] = $legend;
		//print_r($beforePrepareEventPart);
		//print_r(json_encode($finalArray));
		//ob_start('ob_gzhandler');
		@file_put_contents('story_of_history2.json', '['.json_encode($finalArray).']');
		//print_r($arr);
	}

}

?>