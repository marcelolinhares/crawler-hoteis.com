<?php



require 'vendor/autoload.php';

use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;


$client = new GuzzleHttp\Client();


// parametros
$city 			= "Belo Horizonte";

// data do checkin
$dateCheckin 	= "2020-10-01";

// data do checkout
$dateCheckout 	= "2020-10-02";

$hotelsPerPage = 8;

$urlFinal = "https://www.hoteis.com/search.do?resolved-location=CITY%3A160160%3AUNKNOWN%3AUNKNOWN&destination-id=160160&q-destination=" . $city . "&q-check-in=" . $dateCheckin . "&q-check-out=" . $dateCheckout . "&q-rooms=1&q-room-0-adults=1&q-room-0-children=0";


$hotelResult = [];

$client = new Client(
	HttpClient::create(
		[
			'timeout' => 60
		]			
	)
);


$client->setServerParameter('HTTP_USER_AGENT', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:73.0) Gecko/20100101 Firefox/73.0');

$crawler = $client->request('GET', $urlFinal);

echo "crawleando " . $urlFinal . "\n";



// extraindo o número de resultados
$stringCountResult = $crawler->filter('.filters-summary')->text();

// extrai o numero de resultados da string
preg_match_all('!\d+!', $stringCountResult, $matches);
$intCountResult = $matches[0][0];

echo $intCountResult . " resultados encontrados\n\n";

//pega a primeira página
extractPage($crawler, $hotelResult);

$pages = floor($intCountResult/$hotelsPerPage);

// crawleia todas as páginas 
$contPage = 2;

while ($contPage <=$pages) {

	$urlFinalPaginate = $urlFinal ."&pn=" . $contPage;	
	$crawler = $client->request('GET', $urlFinalPaginate);
	echo "crawleando " . $urlFinalPaginate . "\n";
	extractPage($crawler, $hotelResult);
	$contPage++;
}

// salvando no arquivo de saída

$fp = fopen('hoteis_bh.csv', 'w');

foreach ($hotelResult as $hotel) {
    fputcsv($fp, $hotel);
}

fclose($fp);

function extractPage($crawler, &$hotelResult) {

	$hoteisCrawled = 0;

	$crawler->filter('.hotel-wrap')->each(function ($node) use (&$hotelResult, &$hoteisCrawled){
		
//		echo $hoteisCrawled++ . " - ";
	
		//var_dump($node->filter(".price-link strong"));

		// 1 - pegar nome do hotel
		$nameHotel = $node->filter(".p-name")->text();

		// 2 - hoteis.com mostra o preço de várias formas
		$priceHotel = $node->filter(".price-link strong")->text('sem preco');


		if ($priceHotel=='sem preco') {
			$priceHotel	= $node->filter("ins")->text("sem preco");
		}
		
		// 3 - pegar link
		$linkHotel = $node->filter(".p-name a")->link()->getUri();

		// 4 - pegar bairro

		$regionHotel = $node->filter(".xs-welcome-rewards")->text("sem bairro");

		$hotel = [
			'name' 	=> $nameHotel,	
			'price' => $priceHotel,
			'link'  => $linkHotel,
			'bairro' => $regionHotel

		];
		$hoteisCrawled++;

		array_push($hotelResult, $hotel); 
		
	});

	echo $hoteisCrawled . " hoteis crawleados\n";

}
