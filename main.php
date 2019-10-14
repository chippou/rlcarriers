<?php

include "RLCarriersRateQuoteAPIHandler.php";

$handler = new RLCarriersRateQuoteAPIHandler();
$result = $handler->getRateQuote(
	[
		'QuoteType' => "Domestic",
		'DeclaredValue' => 1.0000,
		'Origin' => [
			'City' => 'East Haven',
			'StateOrProvince' => 'CT',
			'ZipOrPostalCode' => '06512',
			'CountryCode' => 'US'
			// 'City' => 'Margaritaville',
			// 'StateOrProvince' => 'AZ',
			// 'ZipOrPostalCode' => '56789',
			// 'CountryCode' => 'US'
		],
		'Destination' => [
			'City' => 'East Haven',
			'StateOrProvince' => 'CT',
			'ZipOrPostalCode' => '06512',
			'CountryCode' => 'US'
		],
		'Items' => [
			[
				'Class' => 50.0,
				'Weight' => 15.0
			],
			[
				'Class' => 50.0,
				'Weight' => 15.0
			]
		]
	],
	true,
	true
);

echo json_encode($result);

