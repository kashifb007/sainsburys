<?php
ini_set('display_errors', 1);
error_reporting(-1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';

use \Symfony\Component\DomCrawler\Crawler;

class Webcrawler
{

	private $options = array();

	public function __construct()
    {
    	$user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

    	$options = array(
		    CURLOPT_CUSTOMREQUEST  =>"GET",        
		    CURLOPT_POST           =>false,
		    CURLOPT_USERAGENT      => $user_agent,
		    CURLOPT_COOKIEFILE     =>"cookie.txt",
		    CURLOPT_COOKIEJAR      =>"cookie.txt",
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_HEADER         => false,
		    CURLOPT_FOLLOWLOCATION => true,
		    CURLOPT_ENCODING       => "",
		    CURLOPT_AUTOREFERER    => true,
		    CURLOPT_CONNECTTIMEOUT => 120,
		    CURLOPT_TIMEOUT        => 120,
		    CURLOPT_MAXREDIRS      => 10,
		    CURLOPT_VERBOSE		=> 0,
		    CURLOPT_SSL_VERIFYHOST => 0,
		    CURLOPT_SSL_VERIFYPEER => 0
		);

    	$this->options = $options;
    }


public function extractFirstURL($url) : array
{

        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $this->options );
        $content = curl_exec( $ch );
        curl_close( $ch );

		$crawler = new Crawler($content);

        $total = 0;

        // pick out the a href information
        $titles = $crawler->filter('.productInfo > h3 > a')->extract(['_text']);
        $links = $crawler->filter('.productInfo > h3 > a')->extract(['href']);

        //strip out the nodes at the bottom of the page that are sponsored links
        $crawler->filter('.priceTab.priceTabContainer.activeContainer.addItem')->each(function (Crawler $crawler) {
            foreach ($crawler->children() as $node) {
                $node->parentNode->removeChild($node);
            }
        });

        //traverse the DOM and get the prices
        $prices = $crawler->filter('.addToTrolleytabContainer > .pricingAndTrolleyOptions > .priceTab > .pricing > .pricePerUnit')->extract(['_text']);        

foreach($prices as $price)
		{
			//for each price, strip out the pound sign and unit text
			//also sum up the total price variable
			$price_array_without_pound = explode('£', $price);
			$prices_array_without_unit = explode('/unit', $price_array_without_pound[1]);
			$prices_array[] = $prices_array_without_unit[0];
			$total += $prices_array_without_unit[0];
		}

return [
            'title'        	=> $titles,
            'price'		   	=> $prices_array,
            'link'		   	=> $links,
            'total'			=> $total
        ];
}

public function traverseURLS($links) : array
{
$description = array();
$fileSize = array();

foreach($links as $link)
{
        $ch      = curl_init( $link );
        curl_setopt_array( $ch, $this->options );
        $content = curl_exec( $ch );
        curl_close( $ch );

	$crawler = new Crawler($content);

	$description[] = $crawler->filter('h3.productDataItemHeader + .productText > p')->first()->extract(['_text'])[0] ?? 
	$crawler->filter('.itemTypeGroupContainer > h3 + .memo > p')->first()->extract(['_text'])[0];
	$fileSize[] = number_format(strlen($content)/1000, 1)."kb";

}

return [
            'description'        	=> $description,
            'fileSize'		=> $fileSize
        ];
}

public function returnJSON(array $dataset1, array $dataset2) : array
{
	//return all the data in JSON
	$json_data = array();
	$counter = 0;
	foreach($dataset1['title'] as $title)
	{
		$json_data['results'][$counter]['title'] = strip_tags(trim($dataset1['title'][$counter]));
		$json_data['results'][$counter]['price'] = $dataset1['price'][$counter];
		$json_data['results'][$counter]['link'] = $dataset1['link'][$counter];
		$json_data['results'][$counter]['description'] = $dataset2['description'][$counter];
		$json_data['results'][$counter]['fileSize'] = $dataset2['fileSize'][$counter];
		$counter++;
	}
	return $json_data;
}

public function collateResults($dataset) : boolean
{
	//test to check if the count of results in each array is equal
	//on the products page there should be 17 results

	$title_count = count($dataset['title']);
	$price_count = count($dataset['price']);
	$link_count = count($dataset['link']);

	$count_equal = false;

	if($title_count == $price_count) {
		if($title_count == $link_count) {
			$count_equal = true;
		}
	}

	return $count_equal;
}

public function checkTotalFloat($dataset) : boolean
{
	//test to simply check if the total sum price is float

	$total = $dataset['total'];

	$prices_float = true;

	foreach ($dataset1['price'] as $price) {
		if(!is_float($price)) {
			$prices_float = false;
			break;
		}
	}
	return $prices_float;
}

} //end Class

/// extract part 1
$dataset1 = array();

$webcrawler = new Webcrawler();

//enter a Sainsburys product list URL here to parse
$dataset1 = $webcrawler->extractFirstURL("https://www.sainsburys.co.uk/webapp/wcs/stores/servlet/CategoryDisplay?listView=true&orderBy=FAVOURITES_FIRST&parent_category_rn=12518&top_category=12518&langId=44&beginIndex=0&pageSize=20&catalogId=10137&searchTerm=&categoryId=185749&listId=&storeId=10151&promotionId=#langId=44&storeId=10151&catalogId=10137&categoryId=185749&parent_category_rn=12518&top_category=12518&pageSize=20&orderBy=FAVOURITES_FIRST&searchTerm=&beginIndex=0&hideFilters=true");

/// extract part 2
$dataset2 = array();
$dataset2 = $webcrawler->traverseURLS($dataset1['link']);

$json_array = $webcrawler->returnJSON($dataset1, $dataset2);

$total_cost['total'][] = round($dataset1['total'], 2);
$final_array = array($json_array, $total_cost);
header("Content-type:application/json");
echo json_encode($final_array);