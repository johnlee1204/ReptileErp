<?php
	class WikiHelper{

		public $lastError;
		public $debug = false;
		private $config;
		private $db;

		function __construct($config){

			$defaultConfig = array(
				'wikiUrl' => '',
				'apiUrl' => '',
				'wikiDBHost' => '',
				'wikiDBUser' => '',
				'wikiDBPass' => '',
				'wikiDBTable' => '',
			);
			$this->config = array_merge($defaultConfig,$config);
			$this->db = new mysqli($this->config['wikiDBHost'],$this->config['wikiDBUser'],$this->config['wikiDBPass'],$this->config['wikiDBTable']);
		}

		function determineActualImagesNeeded($imagesInWikiText,$apiImageResults){
			$imagesRequired = array();
			$imagesInWikiTextIndexed = array();
			foreach($imagesInWikiText as $foundImage){
				$imagesInWikiTextIndexed[$foundImage] = true;
			}
			foreach($apiImageResults as $imageUrl){
				if(isset($imagesInWikiTextIndexed[basename($imageUrl)])){
					$imagesRequired[] = $imageUrl;
				}
			}
			return $imagesRequired;
		}

		function readPageWithImagesById($id,$newImageUrl){
			$page = $this->getPageByID($id);
			$regexResult = WikiHelper::regexCleanup($page['content'],$newImageUrl);
			$page['content'] = $regexResult['content'];
			$imageUrls = $this->getImagesInPages(array($id));
			$page['images'] = $this->determineActualImagesNeeded($regexResult['foundImages'],$imageUrls);
			return $page;
		}

		function readPagesInCategory($category,$newImageUrl){
			if(false === $pages = $this->getPagesInCategory('Category:'.$category)){
				return false;
			}

			$pageIds = array();
			$imagesInWikiText = array();
			$wikis = array();
			foreach ($pages as $page) {
				if(FALSE === $wiki = $this->getPageByID($page['pageid'])){
					continue;
				}

				$regexResult = WikiHelper::regexCleanup($wiki['content'],$newImageUrl);
				foreach($regexResult['foundImages'] as $foundImage){
					$imagesInWikiText[$foundImage] = true;
				}
				$wiki['content'] = $regexResult['content'];
				$pageIds[] = $page['pageid'];
				$wikis[] = $wiki;
			}

			$imagesNeeded = array();
			$imageUrls = $this->getImagesInPages($pageIds);
			foreach($imageUrls as $imageUrl){
				if(isset($imagesInWikiText[basename($imageUrl)])){
					$imagesNeeded[] = $imageUrl;
				}
			}

			return array('images'=> $imagesNeeded, 'wikis'=> $wikis);
		}

		function getPageByID($id){
			$params = array(
				'action' => 'parse'
				,'format' => 'json'
				,'prop' => 'displaytitle|text|links|sections' //Which pieces of information to get. A pipe character | separated list containing one or more of the following options (there's a lot of em on the api docs)
 				,'pageid' => $id //Parse the content of this page. Overrides page. Type: string
 			);
			return $this->getPage($params);
		}

		/*
		function readPageByName($title){
			$params = array(
				'action' => 'parse'
				,'format' => 'json'
				,'prop' => 'displaytitle|text|links|sections' //Which pieces of information to get. A pipe character | separated list containing one or more of the following options (there's a lot of em on the api docs)
 				,'page' => $title
 			);
			return $this->getPage($params);
		}
		*/

		/**
		 * @param $catTitle
		 * @return array|bool
		 * @throws Exception
		 */
		function getPagesInCategory($catTitle){
			$params = array(
				'action' => 'query',
				'format' => 'json',
				'list' => 'categorymembers',
				'cmtype' => 'page', //Which pieces of information to get. A pipe character | separated list containing one or more of the following options (there's a lot of em on the api docs)
				'cmtitle' => $catTitle,
				'cmlimit' => 500 //Fetches 500 at a time (api limit), but getCategoryPages will fetch them all
			);
			return $this->getCategoryPages($params);
		}

		function searchTitlesByName($queryName){
			$param = $this->db->real_escape_string($queryName);
			$param = str_replace(' ','_',$param);
			if(FALSE === $results = $this->db->query("	SELECT page_id, page_title
														FROM page
														WHERE CONVERT(page_title USING latin1) COLLATE latin1_swedish_ci
														LIKE ('%{$param}%')
														AND page_namespace = 0
														ORDER BY page_title
														LIMIT 20
				")){
				die($this->db->error);
			}
			$searchResults = array();
			while ($row = $results->fetch_array(MYSQLI_ASSOC)){
				$row['page_title'] = str_replace('_',' ',$row['page_title']);
				$searchResults[] = $row;
			}
			return $searchResults;
		}

		/**
		 * @param $ids
		 * @return array
		 * @throws Exception
		 */
		function getImagesInPages($ids){
			//Split id's up into groups that cap at 50 each.
			$idGroups = array_chunk($ids,20);
			$imageTitles = array();
			foreach($idGroups as $groupNo => $group){

				$rawJSON = $this->curl($this->config['apiUrl'].$this->prepParams(array(
					'action' => 'query',
					'format' => 'json',
					'imlimit' => '500', //must be specified, 500 is max
					'prop' => 'images',
					//'prop' => 'links|images',
					//'plnamespace' => '6', //Namespace for links - only grab files
					'pageids' => implode('|',$group) //Can be seperated by | and limited to 50 at once.
				)));

				$results = json_decode($rawJSON,true);
				//$this->pre($results ,'cyan');
				foreach($results['query']['pages'] as $page){
					if(isset($page['images'])){
						foreach($page['images'] as $image){
							$imageTitles[] = $image['title'];
						}
					}
				}
			}
			if($this->debug){
				$this->pre($imageTitles,'green');
			}

			//split up images into groups
			$imageGroups = array_chunk($imageTitles,50);
			$imageUrls = array();
			foreach($imageGroups as $groupNo => $group){
				$rawJSON = $this->curl($this->config['apiUrl'].$this->prepParams(array(
					'action' => 'query',
					'format' => 'json',
					'prop' => 'imageinfo',
					'iiprop' => 'url|badfile',
					'iilimit' => '500',
					'iimetadataversion' => 'latest',
					'titles' => str_replace(' ','_',implode('|',$group)) //Can be seperated by | and limited to 50 at once.
				)));
				$results = json_decode($rawJSON,true);
				//$this->pre($results,'pink');
				foreach($results['query']['pages'] as $page){
					//$this->pre($page,'orange');
					if(isset($page['imageinfo'])){
						//Only grab latest image version
						$info = $page['imageinfo'][0];
						//foreach($page['imageinfo'] as $info){
							if(isset($info['url'])){
								$imageUrls[] = str_replace($this->config['wikiUrl'],'',$info['url']);
							}
						//}
					}
				}
			}

			$imageUrlGroups = array_chunk($imageUrls,20);
			$foundThumbs = array();
			foreach($imageUrlGroups as $group){
				$rawJSON = $this->curl($this->config['wikiUrl'].'images/getThumbnails.php'.$this->prepParams(array(
					'images' => base64_encode(implode('|',$group))
				)));
				$results = json_decode($rawJSON,true);
				foreach($results as $result){
					$foundThumbs[] = $result;
				}
			}
			foreach($foundThumbs as $foundThumb){
				$imageUrls[] = $foundThumb;
			}

			//$this->pre($results,'purple');

			//$this->pre($imageUrls);
			//die();

			return $imageUrls;
		}

		public static function regexCleanup($content,$newImageUrl){

			$content = TextSanitizer::fixBrokenUTF8($content);

			//echo '<xmp style="margin:10px; border: solid red 3px;">';
			//echo $content;
			//echo '</xmp>';
			//die();

			//Remove [edit] tags
			$searchPattern = '/<span class="mw-editsection"><span class="mw-editsection-bracket">\[(.*)]\<\/span><\/span>/im';
			$replacePattern = '';
			$content = preg_replace($searchPattern, $replacePattern, $content);

			//fix image tags
			$searchPattern = '/<a href[^>]+><img[^>]+?src="[\w\s\-\:\/\.]+\/([\w\s\-\.]+)"[^>]+?><\/a>/m';
			$replacePattern = '<img src="'.$newImageUrl.'$1">';
			$foundImages = array();
			$images = array();
			preg_match_all($searchPattern,$content,$images);
			foreach($images[1] as $image){
				$foundImages[] = $image;
			}
			$content = preg_replace($searchPattern, $replacePattern, $content);

			//Replace file links with a proper url to open in new tab
			$searchPattern = '/<a href="(?:http|https){0}[^>]+?File:([\w\s_\-.]+)"[^>]+>([\w\s_\-.\:]+)<\/a>/m';
			$replacePattern = '<a href="'.$newImageUrl.'$1" target="_blank">$2</a>';
			$content = preg_replace($searchPattern, $replacePattern, $content);

			$searchPattern = '/<a[^>]+?href="(?:http|https){0}(\/.*?)"[^>]+?>(.*?)<\/a>/m';
			$replacePattern = '$2';
			$content = preg_replace($searchPattern, $replacePattern, $content);

			//remove HTML comment blocks
			$searchPattern = '/<!--[\s\S]+-->/im';
			$replacePattern = '';
			$content = preg_replace($searchPattern, $replacePattern, $content);

			//Remove Thumbnail Captions
			$searchPattern = '/<div class="thumbcaption"><div class="magnify">(.*?)<\/div><\/div>/m';
			$replacePattern = '';
			$content = preg_replace($searchPattern, $replacePattern, $content);

			//Un-Jquery Video tags
			$searchPattern = '/(\$\(this\)?)/im';
			$replacePattern = 'this';
			$content = preg_replace($searchPattern, $replacePattern, $content);

			//Move Video Play Image
			$searchPattern = '/\/extensions\/FWEVideo\/img\/play\.png/im';
			$replacePattern = '/ShopFloorWikiLinker/ShopFloorWikiResources/play.png';
			$content = preg_replace($searchPattern, $replacePattern, $content);

			return array(
				'content' => $content,
				'foundImages' => $foundImages
			);
		}


		private function getPage($params){
 			$paramStr = $this->prepParams($params);
			$rawJSON = $this->curl($this->config['apiUrl'].$paramStr);
			$page = json_decode($rawJSON,true);

			if(!isset($page['parse'])){
				if(isset($page['error']['info'])){
					$this->lastError = $page['error']['info'];
					return false;
				}else{
					$e = new Exception('Bad Api Output');
					$e->exceptionData = array('params' => $params,'output' =>$rawJSON);
					throw $e;
				}
			}

			if(!isset($page['parse']['title']) || !isset($page['parse']['text']['*'])){
				$e = new Exception('Bad Api Output');
				$e->exceptionData = array('params' => $params,'output' =>$rawJSON);
				throw $e;
			}

			return array(
				'title' => $page['parse']['title'],
				'content' => $page['parse']['text']['*']
			);
		}


		/**
		 * This returns pageid's not the actual pages
		 *
		 * @param $params
		 * @return array|bool
		 * @throws Exception
		 */
		private function getCategoryPages($params){
			$paramStr = $this->prepParams($params);
			$rawJSON = $this->curl($this->config['apiUrl'].$paramStr);
			$page = json_decode($rawJSON,true);

			//Check to see if we need to fetch more responses to get all the pages and concate them
			while(isset($page['query-continue'])){
				$params['cmcontinue'] = $page['query-continue']['categorymembers']['cmcontinue'];
				$paramStr = $this->prepParams($params);
				$rawJSON = $this->curl($this->config['apiUrl'].$paramStr);
				unset($page['query-continue']);
				$nextPage = json_decode($rawJSON,true);
				$page['query']['categorymembers'] = array_merge($page['query']['categorymembers'], $nextPage['query']['categorymembers']);

			}
			if(!isset($page['query']['categorymembers'])){
				$this->lastError = 'Invalid Category Title';
				return false;
			}

			return $page['query']['categorymembers'];
		}

		private function prepParams($params){
			$paramStr = "";
 			$paramNo = 0;
 			//$params['fweCacheTime'] = date('ymdhis');
 			foreach($params as $name=>$value){
 				$paramStr .= ($paramNo==0?'?':'&').$name.'='.urlencode($value);
 				$paramNo++;
 			}
 			return $paramStr;
		}

		private function curl($url){
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($ch);
			if($result == FALSE){
				throw new Exception('CURL Error '.curl_errno($ch).': '.curl_error($ch));
			}
			curl_close($ch);
			return $result;
		}

		private function pre($var,$color="red",$label=""){
			echo '<h1>'.$label.'</h1><pre style="border: solid '.$color.' 3px; padding:10px; overflow:scroll;">';
			var_dump($var);
			echo '</pre>';
		}

		private function html($html,$color="red",$label=""){
			echo '<h1>'.$label.'</h1><div style="border: solid '.$color.' 3px; padding:10px; overflow:scroll;">';
			echo $html;
			echo '</div>';
		}
	}
