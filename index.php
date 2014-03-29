<?php 
//http://en.wikipedia.org/w/api.php?format=xml&action=query&prop=revisions&titles=Portal:Current_events/2014_February_1&rvprop=timestamp|user|comment|content
//http://en.wikipedia.org/w/api.php?format=txt&action=query&prop=revisions&titles=Events%20by%20month|2011|2012&rvprop=content|timestamp|user|comment|content

$timeline = new history_timeline_genertor();
$timeline->init();


class  history_timeline_genertor {
	
	
	public $url ;
	public $_data ;
	public $_segments ;
	public $_dataContent = '' ;
	public function fetch(){
		$url = 'http://en.wikipedia.org/w/api.php?action=query&format=json&titles=Events%20by%20month|1011|Events%20by%20month|2012&prop=revisions&rvprop=content';
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, 'MyBot/1.0 (http://www.mysite.com/)');
		
		$result = curl_exec($ch);
		
		if (!$result) {
		  exit('cURL Error: '.curl_error($ch));
		}
		
		print '<pre>';
		$result = json_decode( $result ,true);
		$data =  array_values($result['query']['pages']);
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
			$dataContent[$data[$i]['title']] =  $data[$i]['revisions'][0]['*'];
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
		$arr =array();
		foreach ($segments as $k => $seg) {
			
			foreach ($seg as $cKey => $category){
			$arr["$k"][$cKey] = explode('* ', $category)	;
			}
		}
		foreach ($arr as $k => $seg) {
			foreach ($seg as $key => $value) {
				unset($arr[$k][$key][0]);	;
			}
		}
		print_r($arr);
	}

}

?>