<?php

class Helper_Common extends Zend_Controller_Action_Helper_Abstract
{

	/*
		     * $status = true / false
		     * $statusCode is number : for easy debugging case by case
		     * $msg can be array or string
		     * $params = array() => additional data to response to user
	*/

	public function sendResponse($status = false, $code = 0, $msg = '', $params = array(), $statusCode = 200, $errorList = array(), $pagination = array())
	{
		$this->getResponse()->setHttpResponseCode($statusCode);
		$responseToBeSend = array(
			'status' => $status,
			'code' => $code,
			'data' => ($params === NULL) ? [] : $params,
			'msg' => $msg,
		);

		if (!empty($errorList)) {
			$responseToBeSend['errorList'] = $errorList;
		}

		if (!empty($pagination)) {
			$responseToBeSend['pagination'] = $pagination;
		}

		echo json_encode($responseToBeSend, JSON_UNESCAPED_SLASHES);
	}

	public function sendResponsePagin($status = false, $code = 0, $msg = '', $params = array(), $statusCode = 200, $errorList = array(), $pagination = array())
	{
		$this->getResponse()->setHttpResponseCode($statusCode);

		$d = array(
			'status' => $status,
			"draw" => null,
			"recordsTotal" => $params['recordsTotal'] ?? "0",
			"recordsFiltered" => $params['recordsFiltered'] ?? "0",
			"data" => $params['list'] ?? [],
			"column_list" => $params['column_list'] ?? [],
			"extra" => $params['extra'] ?? [],
			'code' => $code,
			"msg" => $msg
		);

		echo json_encode($d, JSON_UNESCAPED_SLASHES);
	}


	function get_country_list()
	{
		return array("CN" => "China ", "AF" => "Afghanistan", "AX" => "&#197;land Islands", "AL" => "Albania", "DZ" => "Algeria", "AS" => "American Samoa", "AD" => "Andorra", "AO" => "Angola", "AI" => "Anguilla", "AQ" => "Antarctica", "AG" => "Antigua and Barbuda", "AR" => "Argentina", "AM" => "Armenia", "AW" => "Aruba", "AU" => "Australia", "AT" => "Austria", "AZ" => "Azerbaijan", "BS" => "Bahamas", "BH" => "Bahrain", "BD" => "Bangladesh", "BB" => "Barbados", "BY" => "Belarus", "BE" => "Belgium", "BZ" => "Belize", "BJ" => "Benin", "BM" => "Bermuda", "BT" => "Bhutan", "BO" => "Bolivia", "BA" => "Bosnia and Herzegovina", "BW" => "Botswana", "BV" => "Bouvet Island", "BR" => "Brazil", "IO" => "British Indian Ocean Territory", "BN" => "Brunei Darussalam", "BG" => "Bulgaria", "BF" => "Burkina Faso", "BI" => "Burundi", "KH" => "Cambodia", "CM" => "Cameroon", "CA" => "Canada", "CV" => "Cape Verde", "KY" => "Cayman Islands", "CF" => "Central African Republic", "TD" => "Chad", "CL" => "Chile", "CX" => "Christmas Island", "CC" => "Cocos (Keeling) Islands", "CO" => "Colombia", "KM" => "Comoros", "CG" => "Congo", "CD" => "Congo, The Democratic Republic of The", "CK" => "Cook Islands", "CR" => "Costa Rica", "CI" => "C&#244;te D'ivoire", "HR" => "Croatia", "CU" => "Cuba", "CY" => "Cyprus", "CZ" => "Czech Republic", "DK" => "Denmark", "DJ" => "Djibouti", "DM" => "Dominica", "DO" => "Dominican Republic", "EC" => "Ecuador", "EG" => "Egypt", "SV" => "El Salvador", "GQ" => "Equatorial Guinea", "ER" => "Eritrea", "EE" => "Estonia", "ET" => "Ethiopia", "FK" => "Falkland Islands (Malvinas)", "FO" => "Faroe Islands", "FJ" => "Fiji", "FI" => "Finland", "FR" => "France", "GF" => "French Guiana", "PF" => "French Polynesia", "TF" => "French Southern Territories", "GA" => "Gabon", "GM" => "Gambia", "GE" => "Georgia", "DE" => "Germany", "GH" => "Ghana", "GI" => "Gibraltar", "GR" => "Greece", "GL" => "Greenland", "GD" => "Grenada", "GP" => "Guadeloupe", "GU" => "Guam", "GT" => "Guatemala", "GG" => "Guernsey", "GN" => "Guinea", "GW" => "Guinea-bissau", "GY" => "Guyana", "HT" => "Haiti", "HM" => "Heard Island and Mcdonald Islands", "VA" => "Holy See (Vatican City State)", "HN" => "Honduras", "HK" => "Hong Kong ", "HU" => "Hungary", "IS" => "Iceland", "IN" => "India", "ID" => "Indonesia", "IR" => "Iran, Islamic Republic Of", "IQ" => "Iraq", "IE" => "Ireland", "IM" => "Isle of Man", "IL" => "Israel", "IT" => "Italy", "JM" => "Jamaica", "JP" => "Japan", "JE" => "Jersey", "JO" => "Jordan", "KZ" => "Kazakhstan", "KE" => "Kenya", "KI" => "Kiribati", "KP" => "Korea, Democratic People's Republic Of", "KR" => "Korea, Republic Of", "KW" => "Kuwait", "KG" => "Kyrgyzstan", "LA" => "Lao People's Democratic Republic", "LV" => "Latvia", "LB" => "Lebanon", "LS" => "Lesotho", "LR" => "Liberia", "LY" => "Libyan Arab Jamahiriya", "LI" => "Liechtenstein", "LT" => "Lithuania", "LU" => "Luxembourg", "MO" => "Macao", "MK" => "Macedonia, The Former Yugoslav Republic Of", "MG" => "Madagascar", "MW" => "Malawi", "MY" => "Malaysia ", "MV" => "Maldives", "ML" => "Mali", "MT" => "Malta", "MH" => "Marshall Islands", "MQ" => "Martinique", "MR" => "Mauritania", "MU" => "Mauritius", "YT" => "Mayotte", "MX" => "Mexico", "FM" => "Micronesia, Federated States Of", "MD" => "Moldova, Republic Of", "MC" => "Monaco", "MN" => "Mongolia", "ME" => "Montenegro", "MS" => "Montserrat", "MA" => "Morocco", "MZ" => "Mozambique", "MM" => "Myanmar", "NA" => "Namibia", "NR" => "Nauru", "NP" => "Nepal", "NL" => "Netherlands", "AN" => "Netherlands Antilles", "NC" => "New Caledonia", "NZ" => "New Zealand", "NI" => "Nicaragua", "NE" => "Niger", "NG" => "Nigeria", "NU" => "Niue", "NF" => "Norfolk Island", "MP" => "Northern Mariana Islands", "NO" => "Norway", "OM" => "Oman", "PK" => "Pakistan", "PW" => "Palau", "PS" => "Palestinian Territory, Occupied", "PA" => "Panama", "PG" => "Papua New Guinea", "PY" => "Paraguay", "PE" => "Peru", "PH" => "Philippines", "PN" => "Pitcairn", "PL" => "Poland", "PT" => "Portugal", "PR" => "Puerto Rico", "QA" => "Qatar", "RE" => "R&#233;union", "RO" => "Romania", "RU" => "Russian Federation", "RW" => "Rwanda", "BL" => "Saint Barth&#233;lemy", "SH" => "Saint Helena", "KN" => "Saint Kitts and Nevis", "LC" => "Saint Lucia", "MF" => "Saint Martin", "PM" => "Saint Pierre and Miquelon", "VC" => "Saint Vincent and The Grenadines", "WS" => "Samoa", "SM" => "San Marino", "ST" => "Sao Tome and Principe", "SA" => "Saudi Arabia", "SN" => "Senegal", "RS" => "Serbia", "SC" => "Seychelles", "SL" => "Sierra Leone", "SG" => "Singapore", "SK" => "Slovakia", "SI" => "Slovenia", "SB" => "Solomon Islands", "SO" => "Somalia", "ZA" => "South Africa", "GS" => "South Georgia and The South Sandwich Islands", "ES" => "Spain", "LK" => "Sri Lanka", "SD" => "Sudan", "SR" => "Suriname", "SJ" => "Svalbard and Jan Mayen", "SZ" => "Swaziland", "SE" => "Sweden", "CH" => "Switzerland", "SY" => "Syrian Arab Republic", "TW" => "Taiwan ", "TJ" => "Tajikistan", "TZ" => "Tanzania, United Republic Of", "TH" => "Thailand", "TL" => "Timor-leste", "TG" => "Togo", "TK" => "Tokelau", "TO" => "Tonga", "TT" => "Trinidad and Tobago", "TN" => "Tunisia", "TR" => "Turkey", "TM" => "Turkmenistan", "TC" => "Turks and Caicos Islands", "TV" => "Tuvalu", "UG" => "Uganda", "UA" => "Ukraine", "AE" => "United Arab Emirates", "GB" => "United Kingdom", "US" => "United States", "UM" => "United States Minor Outlying Islands", "UY" => "Uruguay", "UZ" => "Uzbekistan", "VU" => "Vanuatu", "VE" => "Venezuela", "VN" => "Viet Nam", "VG" => "Virgin Islands, British", "VI" => "Virgin Islands, U.s.", "WF" => "Wallis and Futuna", "EH" => "Western Sahara", "YE" => "Yemen", "ZM" => "Zambia", "ZW" => "Zimbabwe");
	}

	function get_mobile_code_list()
	{
		$array = array('CN' => array('name' => 'CHINA', 'code' => '86'), 'AD' => array('name' => 'ANDORRA', 'code' => '376'), 'AE' => array('name' => 'UNITED ARAB EMIRATES', 'code' => '971'), 'AF' => array('name' => 'AFGHANISTAN', 'code' => '93'), 'AG' => array('name' => 'ANTIGUA AND BARBUDA', 'code' => '1268'), 'AI' => array('name' => 'ANGUILLA', 'code' => '1264'), 'AL' => array('name' => 'ALBANIA', 'code' => '355'), 'AM' => array('name' => 'ARMENIA', 'code' => '374'), 'AN' => array('name' => 'NETHERLANDS ANTILLES', 'code' => '599'), 'AO' => array('name' => 'ANGOLA', 'code' => '244'), 'AQ' => array('name' => 'ANTARCTICA', 'code' => '672'), 'AR' => array('name' => 'ARGENTINA', 'code' => '54'), 'AS' => array('name' => 'AMERICAN SAMOA', 'code' => '1684'), 'AT' => array('name' => 'AUSTRIA', 'code' => '43'), 'AU' => array('name' => 'AUSTRALIA', 'code' => '61'), 'AW' => array('name' => 'ARUBA', 'code' => '297'), 'AZ' => array('name' => 'AZERBAIJAN', 'code' => '994'), 'BA' => array('name' => 'BOSNIA AND HERZEGOVINA', 'code' => '387'), 'BB' => array('name' => 'BARBADOS', 'code' => '1246'), 'BD' => array('name' => 'BANGLADESH', 'code' => '880'), 'BE' => array('name' => 'BELGIUM', 'code' => '32'), 'BF' => array('name' => 'BURKINA FASO', 'code' => '226'), 'BG' => array('name' => 'BULGARIA', 'code' => '359'), 'BH' => array('name' => 'BAHRAIN', 'code' => '973'), 'BI' => array('name' => 'BURUNDI', 'code' => '257'), 'BJ' => array('name' => 'BENIN', 'code' => '229'), 'BL' => array('name' => 'SAINT BARTHELEMY', 'code' => '590'), 'BM' => array('name' => 'BERMUDA', 'code' => '1441'), 'BN' => array('name' => 'BRUNEI DARUSSALAM', 'code' => '673'), 'BO' => array('name' => 'BOLIVIA', 'code' => '591'), 'BR' => array('name' => 'BRAZIL', 'code' => '55'), 'BS' => array('name' => 'BAHAMAS', 'code' => '1242'), 'BT' => array('name' => 'BHUTAN', 'code' => '975'), 'BW' => array('name' => 'BOTSWANA', 'code' => '267'), 'BY' => array('name' => 'BELARUS', 'code' => '375'), 'BZ' => array('name' => 'BELIZE', 'code' => '501'), 'CA' => array('name' => 'CANADA', 'code' => '1'), 'CC' => array('name' => 'COCOS (KEELING) ISLANDS', 'code' => '61'), 'CD' => array('name' => 'CONGO, THE DEMOCRATIC REPUBLIC OF THE', 'code' => '243'), 'CF' => array('name' => 'CENTRAL AFRICAN REPUBLIC', 'code' => '236'), 'CG' => array('name' => 'CONGO', 'code' => '242'), 'CH' => array('name' => 'SWITZERLAND', 'code' => '41'), 'CI' => array('name' => 'COTE D IVOIRE', 'code' => '225'), 'CK' => array('name' => 'COOK ISLANDS', 'code' => '682'), 'CM' => array('name' => 'CAMEROON', 'code' => '237'), /* 'CN' => array('name' => 'CHINA', 'code' => '86'), */'CO' => array('name' => 'COLOMBIA', 'code' => '57'), 'CR' => array('name' => 'COSTA RICA', 'code' => '506'), 'CU' => array('name' => 'CUBA', 'code' => '53'), 'CV' => array('name' => 'CAPE VERDE', 'code' => '238'), 'CX' => array('name' => 'CHRISTMAS ISLAND', 'code' => '61'), 'CY' => array('name' => 'CYPRUS', 'code' => '357'), 'CZ' => array('name' => 'CZECH REPUBLIC', 'code' => '420'), 'DE' => array('name' => 'GERMANY', 'code' => '49'), 'DJ' => array('name' => 'DJIBOUTI', 'code' => '253'), 'DK' => array('name' => 'DENMARK', 'code' => '45'), 'DM' => array('name' => 'DOMINICA', 'code' => '1767'), 'DO' => array('name' => 'DOMINICAN REPUBLIC', 'code' => '1809'), 'DZ' => array('name' => 'ALGERIA', 'code' => '213'), 'EC' => array('name' => 'ECUADOR', 'code' => '593'), 'EE' => array('name' => 'ESTONIA', 'code' => '372'), 'EG' => array('name' => 'EGYPT', 'code' => '20'), 'ER' => array('name' => 'ERITREA', 'code' => '291'), 'ES' => array('name' => 'SPAIN', 'code' => '34'), 'ET' => array('name' => 'ETHIOPIA', 'code' => '251'), 'FI' => array('name' => 'FINLAND', 'code' => '358'), 'FJ' => array('name' => 'FIJI', 'code' => '679'), 'FK' => array('name' => 'FALKLAND ISLANDS (MALVINAS)', 'code' => '500'), 'FM' => array('name' => 'MICRONESIA, FEDERATED STATES OF', 'code' => '691'), 'FO' => array('name' => 'FAROE ISLANDS', 'code' => '298'), 'FR' => array('name' => 'FRANCE', 'code' => '33'), 'GA' => array('name' => 'GABON', 'code' => '241'), 'GB' => array('name' => 'UNITED KINGDOM', 'code' => '44'), 'GD' => array('name' => 'GRENADA', 'code' => '1473'), 'GE' => array('name' => 'GEORGIA', 'code' => '995'), 'GH' => array('name' => 'GHANA', 'code' => '233'), 'GI' => array('name' => 'GIBRALTAR', 'code' => '350'), 'GL' => array('name' => 'GREENLAND', 'code' => '299'), 'GM' => array('name' => 'GAMBIA', 'code' => '220'), 'GN' => array('name' => 'GUINEA', 'code' => '224'), 'GQ' => array('name' => 'EQUATORIAL GUINEA', 'code' => '240'), 'GR' => array('name' => 'GREECE', 'code' => '30'), 'GT' => array('name' => 'GUATEMALA', 'code' => '502'), 'GU' => array('name' => 'GUAM', 'code' => '1671'), 'GW' => array('name' => 'GUINEA-BISSAU', 'code' => '245'), 'GY' => array('name' => 'GUYANA', 'code' => '592'), 'HK' => array('name' => 'HONG KONG', 'code' => '852'), 'HN' => array('name' => 'HONDURAS', 'code' => '504'), 'HR' => array('name' => 'CROATIA', 'code' => '385'), 'HT' => array('name' => 'HAITI', 'code' => '509'), 'HU' => array('name' => 'HUNGARY', 'code' => '36'), 'ID' => array('name' => 'INDONESIA', 'code' => '62'), 'IE' => array('name' => 'IRELAND', 'code' => '353'), 'IL' => array('name' => 'ISRAEL', 'code' => '972'), 'IM' => array('name' => 'ISLE OF MAN', 'code' => '44'), 'IN' => array('name' => 'INDIA', 'code' => '91'), 'IQ' => array('name' => 'IRAQ', 'code' => '964'), 'IR' => array('name' => 'IRAN, ISLAMIC REPUBLIC OF', 'code' => '98'), 'IS' => array('name' => 'ICELAND', 'code' => '354'), 'IT' => array('name' => 'ITALY', 'code' => '39'), 'JM' => array('name' => 'JAMAICA', 'code' => '1876'), 'JO' => array('name' => 'JORDAN', 'code' => '962'), 'JP' => array('name' => 'JAPAN', 'code' => '81'), 'KE' => array('name' => 'KENYA', 'code' => '254'), 'KG' => array('name' => 'KYRGYZSTAN', 'code' => '996'), 'KH' => array('name' => 'CAMBODIA', 'code' => '855'), 'KI' => array('name' => 'KIRIBATI', 'code' => '686'), 'KM' => array('name' => 'COMOROS', 'code' => '269'), 'KN' => array('name' => 'SAINT KITTS AND NEVIS', 'code' => '1869'), 'KP' => array('name' => 'KOREA DEMOCRATIC PEOPLES REPUBLIC OF', 'code' => '850'), 'KR' => array('name' => 'KOREA REPUBLIC OF', 'code' => '82'), 'KW' => array('name' => 'KUWAIT', 'code' => '965'), 'KY' => array('name' => 'CAYMAN ISLANDS', 'code' => '1345'), 'KZ' => array('name' => 'KAZAKSTAN', 'code' => '7'), 'LA' => array('name' => 'LAO PEOPLES DEMOCRATIC REPUBLIC', 'code' => '856'), 'LB' => array('name' => 'LEBANON', 'code' => '961'), 'LC' => array('name' => 'SAINT LUCIA', 'code' => '1758'), 'LI' => array('name' => 'LIECHTENSTEIN', 'code' => '423'), 'LK' => array('name' => 'SRI LANKA', 'code' => '94'), 'LR' => array('name' => 'LIBERIA', 'code' => '231'), 'LS' => array('name' => 'LESOTHO', 'code' => '266'), 'LT' => array('name' => 'LITHUANIA', 'code' => '370'), 'LU' => array('name' => 'LUXEMBOURG', 'code' => '352'), 'LV' => array('name' => 'LATVIA', 'code' => '371'), 'LY' => array('name' => 'LIBYAN ARAB JAMAHIRIYA', 'code' => '218'), 'MA' => array('name' => 'MOROCCO', 'code' => '212'), 'MC' => array('name' => 'MONACO', 'code' => '377'), 'MD' => array('name' => 'MOLDOVA, REPUBLIC OF', 'code' => '373'), 'ME' => array('name' => 'MONTENEGRO', 'code' => '382'), 'MF' => array('name' => 'SAINT MARTIN', 'code' => '1599'), 'MG' => array('name' => 'MADAGASCAR', 'code' => '261'), 'MH' => array('name' => 'MARSHALL ISLANDS', 'code' => '692'), 'MK' => array('name' => 'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF', 'code' => '389'), 'ML' => array('name' => 'MALI', 'code' => '223'), 'MM' => array('name' => 'MYANMAR', 'code' => '95'), 'MN' => array('name' => 'MONGOLIA', 'code' => '976'), 'MO' => array('name' => 'MACAU', 'code' => '853'), 'MP' => array('name' => 'NORTHERN MARIANA ISLANDS', 'code' => '1670'), 'MR' => array('name' => 'MAURITANIA', 'code' => '222'), 'MS' => array('name' => 'MONTSERRAT', 'code' => '1664'), 'MT' => array('name' => 'MALTA', 'code' => '356'), 'MU' => array('name' => 'MAURITIUS', 'code' => '230'), 'MV' => array('name' => 'MALDIVES', 'code' => '960'), 'MW' => array('name' => 'MALAWI', 'code' => '265'), 'MX' => array('name' => 'MEXICO', 'code' => '52'), 'MY' => array('name' => 'MALAYSIA', 'code' => '60'), 'MZ' => array('name' => 'MOZAMBIQUE', 'code' => '258'), 'NA' => array('name' => 'NAMIBIA', 'code' => '264'), 'NC' => array('name' => 'NEW CALEDONIA', 'code' => '687'), 'NE' => array('name' => 'NIGER', 'code' => '227'), 'NG' => array('name' => 'NIGERIA', 'code' => '234'), 'NI' => array('name' => 'NICARAGUA', 'code' => '505'), 'NL' => array('name' => 'NETHERLANDS', 'code' => '31'), 'NO' => array('name' => 'NORWAY', 'code' => '47'), 'NP' => array('name' => 'NEPAL', 'code' => '977'), 'NR' => array('name' => 'NAURU', 'code' => '674'), 'NU' => array('name' => 'NIUE', 'code' => '683'), 'NZ' => array('name' => 'NEW ZEALAND', 'code' => '64'), 'OM' => array('name' => 'OMAN', 'code' => '968'), 'PA' => array('name' => 'PANAMA', 'code' => '507'), 'PE' => array('name' => 'PERU', 'code' => '51'), 'PF' => array('name' => 'FRENCH POLYNESIA', 'code' => '689'), 'PG' => array('name' => 'PAPUA NEW GUINEA', 'code' => '675'), 'PH' => array('name' => 'PHILIPPINES', 'code' => '63'), 'PK' => array('name' => 'PAKISTAN', 'code' => '92'), 'PL' => array('name' => 'POLAND', 'code' => '48'), 'PM' => array('name' => 'SAINT PIERRE AND MIQUELON', 'code' => '508'), 'PN' => array('name' => 'PITCAIRN', 'code' => '870'), 'PR' => array('name' => 'PUERTO RICO', 'code' => '1'), 'PT' => array('name' => 'PORTUGAL', 'code' => '351'), 'PW' => array('name' => 'PALAU', 'code' => '680'), 'PY' => array('name' => 'PARAGUAY', 'code' => '595'), 'QA' => array('name' => 'QATAR', 'code' => '974'), 'RO' => array('name' => 'ROMANIA', 'code' => '40'), 'RS' => array('name' => 'SERBIA', 'code' => '381'), 'RU' => array('name' => 'RUSSIAN FEDERATION', 'code' => '7'), 'RW' => array('name' => 'RWANDA', 'code' => '250'), 'SA' => array('name' => 'SAUDI ARABIA', 'code' => '966'), 'SB' => array('name' => 'SOLOMON ISLANDS', 'code' => '677'), 'SC' => array('name' => 'SEYCHELLES', 'code' => '248'), 'SD' => array('name' => 'SUDAN', 'code' => '249'), 'SE' => array('name' => 'SWEDEN', 'code' => '46'), 'SG' => array('name' => 'SINGAPORE', 'code' => '65'), 'SH' => array('name' => 'SAINT HELENA', 'code' => '290'), 'SI' => array('name' => 'SLOVENIA', 'code' => '386'), 'SK' => array('name' => 'SLOVAKIA', 'code' => '421'), 'SL' => array('name' => 'SIERRA LEONE', 'code' => '232'), 'SM' => array('name' => 'SAN MARINO', 'code' => '378'), 'SN' => array('name' => 'SENEGAL', 'code' => '221'), 'SO' => array('name' => 'SOMALIA', 'code' => '252'), 'SR' => array('name' => 'SURINAME', 'code' => '597'), 'ST' => array('name' => 'SAO TOME AND PRINCIPE', 'code' => '239'), 'SV' => array('name' => 'EL SALVADOR', 'code' => '503'), 'SY' => array('name' => 'SYRIAN ARAB REPUBLIC', 'code' => '963'), 'SZ' => array('name' => 'SWAZILAND', 'code' => '268'), 'TC' => array('name' => 'TURKS AND CAICOS ISLANDS', 'code' => '1649'), 'TG' => array('name' => 'TOGO', 'code' => '228'), 'TH' => array('name' => 'THAILAND', 'code' => '66'), 'TJ' => array('name' => 'TAJIKISTAN', 'code' => '992'), 'TK' => array('name' => 'TOKELAU', 'code' => '690'), 'TL' => array('name' => 'TIMOR-LESTE', 'code' => '670'), 'TM' => array('name' => 'TURKMENISTAN', 'code' => '993'), 'TN' => array('name' => 'TUNISIA', 'code' => '216'), 'TO' => array('name' => 'TONGA', 'code' => '676'), 'TR' => array('name' => 'TURKEY', 'code' => '90'), 'TT' => array('name' => 'TRINIDAD AND TOBAGO', 'code' => '1868'), 'TV' => array('name' => 'TUVALU', 'code' => '688'), 'TW' => array('name' => 'TAIWAN, PROVINCE OF CHINA', 'code' => '886'), 'TZ' => array('name' => 'TANZANIA, UNITED REPUBLIC OF', 'code' => '255'), 'UA' => array('name' => 'UKRAINE', 'code' => '380'), 'UG' => array('name' => 'UGANDA', 'code' => '256'), 'US' => array('name' => 'UNITED STATES', 'code' => '1'), 'UY' => array('name' => 'URUGUAY', 'code' => '598'), 'UZ' => array('name' => 'UZBEKISTAN', 'code' => '998'), 'VA' => array('name' => 'HOLY SEE (VATICAN CITY STATE)', 'code' => '39'), 'VC' => array('name' => 'SAINT VINCENT AND THE GRENADINES', 'code' => '1784'), 'VE' => array('name' => 'VENEZUELA', 'code' => '58'), 'VG' => array('name' => 'VIRGIN ISLANDS, BRITISH', 'code' => '1284'), 'VI' => array('name' => 'VIRGIN ISLANDS, U.S.', 'code' => '1340'), 'VN' => array('name' => 'VIET NAM', 'code' => '84'), 'VU' => array('name' => 'VANUATU', 'code' => '678'), 'WF' => array('name' => 'WALLIS AND FUTUNA', 'code' => '681'), 'WS' => array('name' => 'SAMOA', 'code' => '685'), 'XK' => array('name' => 'KOSOVO', 'code' => '381'), 'YE' => array('name' => 'YEMEN', 'code' => '967'), 'YT' => array('name' => 'MAYOTTE', 'code' => '262'), 'ZA' => array('name' => 'SOUTH AFRICA', 'code' => '27'), 'ZM' => array('name' => 'ZAMBIA', 'code' => '260'), 'ZW' => array('name' => 'ZIMBABWE', 'code' => '263'));
		$columns = array_column($array, 'code');
		array_multisort($columns, SORT_ASC, $array);
		return $array;
	}

	function get_mobile_code_list_cn()
	{
		$array = array('CN' => array('name' => '中国', 'code' => '86'), 'AD' => array('name' => '安道爾', 'code' => '376'), 'AE' => array('name' => '阿拉伯聯合大公國', 'code' => '971'), 'AF' => array('name' => '阿富汗', 'code' => '93'), 'AG' => array('name' => '安地卡及巴布達', 'code' => '1268'), 'AI' => array('name' => '安圭拉', 'code' => '1264'), 'AL' => array('name' => '阿爾巴尼亞', 'code' => '355'), 'AM' => array('name' => '亞美尼亞', 'code' => '374'), 'AN' => array('name' => '荷屬安地列斯', 'code' => '599'), 'AO' => array('name' => '安哥拉', 'code' => '244'), 'AQ' => array('name' => '南极洲', 'code' => '672'), 'AR' => array('name' => '阿根廷', 'code' => '54'), 'AS' => array('name' => '美国萨摩亚', 'code' => '1684'), 'AT' => array('name' => '奧地利', 'code' => '43'), 'AU' => array('name' => '澳大利亚', 'code' => '61'), 'AW' => array('name' => '阿魯巴', 'code' => '297'), 'AZ' => array('name' => '阿塞拜疆', 'code' => '994'), 'BA' => array('name' => '波斯尼亚和黑塞哥维那', 'code' => '387'), 'BB' => array('name' => '巴贝多', 'code' => '1246'), 'BD' => array('name' => '孟加拉人民共和国', 'code' => '880'), 'BE' => array('name' => '比利時', 'code' => '32'), 'BF' => array('name' => '布基纳法索', 'code' => '226'), 'BG' => array('name' => '保加利亚共和国', 'code' => '359'), 'BH' => array('name' => '巴林', 'code' => '973'), 'BI' => array('name' => '布隆迪', 'code' => '257'), 'BJ' => array('name' => '贝宁', 'code' => '229'), 'BL' => array('name' => '圣巴泰勒米', 'code' => '590'), 'BM' => array('name' => '百慕大', 'code' => '1441'), 'BN' => array('name' => '汶萊', 'code' => '673'), 'BO' => array('name' => '玻利维亚', 'code' => '591'), 'BR' => array('name' => '巴西', 'code' => '55'), 'BS' => array('name' => '巴哈马', 'code' => '1242'), 'BT' => array('name' => '不丹', 'code' => '975'), 'BW' => array('name' => '博茨瓦纳', 'code' => '267'), 'BY' => array('name' => '白俄罗斯', 'code' => '375'), 'BZ' => array('name' => '伯利兹', 'code' => '501'), 'CA' => array('name' => '加拿大', 'code' => '1'), 'CC' => array('name' => '科科斯（基林）群島', 'code' => '61'), 'CD' => array('name' => '剛果民主共和國', 'code' => '243'), 'CF' => array('name' => '中非共和國', 'code' => '236'), 'CG' => array('name' => '刚果', 'code' => '242'), 'CH' => array('name' => '瑞士', 'code' => '41'), 'CI' => array('name' => '象牙海岸', 'code' => '225'), 'CK' => array('name' => '库克群岛', 'code' => '682'), 'CM' => array('name' => '喀麦隆', 'code' => '237'), 'CO' => array('name' => '哥伦比亚', 'code' => '57'), 'CR' => array('name' => '哥斯达黎加', 'code' => '506'), 'CU' => array('name' => '古巴', 'code' => '53'), 'CV' => array('name' => '佛得角', 'code' => '238'), 'CX' => array('name' => '圣诞岛', 'code' => '61'), 'CY' => array('name' => '塞浦路斯', 'code' => '357'), 'CZ' => array('name' => '捷克共和国', 'code' => '420'), 'DE' => array('name' => '德国', 'code' => '49'), 'DJ' => array('name' => '吉布提', 'code' => '253'), 'DK' => array('name' => '丹麦', 'code' => '45'), 'DM' => array('name' => '多米尼克', 'code' => '1767'), 'DO' => array('name' => '多明尼加共和國', 'code' => '1809'), 'DZ' => array('name' => '阿尔及利亚', 'code' => '213'), 'EC' => array('name' => '厄瓜多尔', 'code' => '593'), 'EE' => array('name' => '爱沙尼亚', 'code' => '372'), 'EG' => array('name' => '埃及', 'code' => '20'), 'ER' => array('name' => '厄立特里亚', 'code' => '291'), 'ES' => array('name' => '西班牙', 'code' => '34'), 'ET' => array('name' => '埃塞俄比亚', 'code' => '251'), 'FI' => array('name' => '芬兰', 'code' => '358'), 'FJ' => array('name' => '斐济', 'code' => '679'), 'FK' => array('name' => '福克兰群岛', 'code' => '500'), 'FM' => array('name' => '密克罗尼西亚联邦', 'code' => '691'), 'FO' => array('name' => '法罗群岛', 'code' => '298'), 'FR' => array('name' => '法国', 'code' => '33'), 'GA' => array('name' => '加蓬', 'code' => '241'), 'GB' => array('name' => '英国', 'code' => '44'), 'GD' => array('name' => '格林纳达', 'code' => '1473'), 'GE' => array('name' => '格鲁吉亚', 'code' => '995'), 'GH' => array('name' => '加纳', 'code' => '233'), 'GI' => array('name' => '直布罗陀', 'code' => '350'), 'GL' => array('name' => '格陵兰', 'code' => '299'), 'GM' => array('name' => '冈比亚', 'code' => '220'), 'GN' => array('name' => '几内亚', 'code' => '224'), 'GQ' => array('name' => '赤道几内亚', 'code' => '240'), 'GR' => array('name' => '希腊', 'code' => '30'), 'GT' => array('name' => '危地马拉', 'code' => '502'), 'GU' => array('name' => '关岛', 'code' => '1671'), 'GW' => array('name' => '几内亚比绍', 'code' => '245'), 'GY' => array('name' => '圭亚那', 'code' => '592'), 'HK' => array('name' => '香港', 'code' => '852'), 'HN' => array('name' => '洪都拉斯', 'code' => '504'), 'HR' => array('name' => '克罗地亚', 'code' => '385'), 'HT' => array('name' => '海地', 'code' => '509'), 'HU' => array('name' => '匈牙利', 'code' => '36'), 'ID' => array('name' => '印度尼西亚', 'code' => '62'), 'IE' => array('name' => '爱尔兰', 'code' => '353'), 'IL' => array('name' => '以色列', 'code' => '972'), 'IM' => array('name' => '马恩岛', 'code' => '44'), 'IN' => array('name' => '印度', 'code' => '91'), 'IQ' => array('name' => '伊拉克', 'code' => '964'), 'IR' => array('name' => '伊朗伊斯兰共和国', 'code' => '98'), 'IS' => array('name' => '冰岛', 'code' => '354'), 'IT' => array('name' => '意大利', 'code' => '39'), 'JM' => array('name' => '牙买加', 'code' => '1876'), 'JO' => array('name' => '约旦', 'code' => '962'), 'JP' => array('name' => '日本', 'code' => '81'), 'KE' => array('name' => '肯尼亚', 'code' => '254'), 'KG' => array('name' => '吉尔吉斯斯坦', 'code' => '996'), 'KH' => array('name' => '柬埔寨', 'code' => '855'), 'KI' => array('name' => '基里巴斯', 'code' => '686'), 'KM' => array('name' => '科摩罗', 'code' => '269'), 'KN' => array('name' => '圣基茨和尼维', 'code' => '1869'), 'KP' => array('name' => '朝鲜民主主义人民共和国', 'code' => '850'), 'KR' => array('name' => '朝鲜共和国', 'code' => '82'), 'KW' => array('name' => '科威特', 'code' => '965'), 'KY' => array('name' => '开曼群岛', 'code' => '1345'), 'KZ' => array('name' => '哈萨克斯坦', 'code' => '7'), 'LA' => array('name' => '老挝人民民主共和国', 'code' => '856'), 'LB' => array('name' => '黎巴嫩', 'code' => '961'), 'LC' => array('name' => '圣卢西亚', 'code' => '1758'), 'LI' => array('name' => '列支敦士登', 'code' => '423'), 'LK' => array('name' => '斯里兰卡', 'code' => '94'), 'LR' => array('name' => '利比里亚', 'code' => '231'), 'LS' => array('name' => '莱索托', 'code' => '266'), 'LT' => array('name' => '立陶宛', 'code' => '370'), 'LU' => array('name' => '卢森堡', 'code' => '352'), 'LV' => array('name' => '拉脱维亚', 'code' => '371'), 'LY' => array('name' => '利比亞', 'code' => '218'), 'MA' => array('name' => '摩洛哥', 'code' => '212'), 'MC' => array('name' => '摩纳哥', 'code' => '377'), 'MD' => array('name' => '摩尔多瓦共和国', 'code' => '373'), 'ME' => array('name' => '黑山', 'code' => '382'), 'MF' => array('name' => '圣马丁岛', 'code' => '1599'), 'MG' => array('name' => '马达加斯加', 'code' => '261'), 'MH' => array('name' => '马绍尔群岛', 'code' => '692'), 'MK' => array('name' => '马其顿前南斯拉夫共和国', 'code' => '389'), 'ML' => array('name' => '马里', 'code' => '223'), 'MM' => array('name' => '缅甸', 'code' => '95'), 'MN' => array('name' => '蒙古', 'code' => '976'), 'MO' => array('name' => '澳门', 'code' => '853'), 'MP' => array('name' => '北马里亚纳群岛', 'code' => '1670'), 'MR' => array('name' => '毛里塔尼亚', 'code' => '222'), 'MS' => array('name' => '蒙特塞拉特', 'code' => '1664'), 'MT' => array('name' => '马耳他', 'code' => '356'), 'MU' => array('name' => '毛里求斯', 'code' => '230'), 'MV' => array('name' => '马尔代夫', 'code' => '960'), 'MW' => array('name' => '马拉维', 'code' => '265'), 'MX' => array('name' => '墨西哥', 'code' => '52'), 'MY' => array('name' => '马来西亚', 'code' => '60'), 'MZ' => array('name' => '莫桑比克', 'code' => '258'), 'NA' => array('name' => '纳米比亚', 'code' => '264'), 'NC' => array('name' => '新喀里多尼亚', 'code' => '687'), 'NE' => array('name' => '尼日尔', 'code' => '227'), 'NG' => array('name' => '尼日利亚', 'code' => '234'), 'NI' => array('name' => '尼加拉瓜', 'code' => '505'), 'NL' => array('name' => '荷兰', 'code' => '31'), 'NO' => array('name' => '挪威', 'code' => '47'), 'NP' => array('name' => '尼泊尔', 'code' => '977'), 'NR' => array('name' => '瑙鲁', 'code' => '674'), 'NU' => array('name' => '纽埃', 'code' => '683'), 'NZ' => array('name' => '紐西兰', 'code' => '64'), 'OM' => array('name' => '阿曼', 'code' => '968'), 'PA' => array('name' => '巴拿马', 'code' => '507'), 'PE' => array('name' => '秘鲁', 'code' => '51'), 'PF' => array('name' => '法属波利尼西亚', 'code' => '689'), 'PG' => array('name' => '巴布亚新几内亚', 'code' => '675'), 'PH' => array('name' => '菲律宾', 'code' => '63'), 'PK' => array('name' => '巴基斯坦', 'code' => '92'), 'PL' => array('name' => '波兰', 'code' => '48'), 'PM' => array('name' => '圣皮埃尔和密克隆', 'code' => '508'), 'PN' => array('name' => '皮特凯恩', 'code' => '870'), 'PR' => array('name' => '波多黎各', 'code' => '1'), 'PT' => array('name' => '葡萄牙L、', 'code' => '351'), 'PW' => array('name' => '帛琉', 'code' => '680'), 'PY' => array('name' => '巴拉圭', 'code' => '595'), 'QA' => array('name' => '卡塔尔', 'code' => '974'), 'RO' => array('name' => '罗马尼亚', 'code' => '40'), 'RS' => array('name' => '塞尔维亚', 'code' => '381'), 'RU' => array('name' => '俄罗斯联邦', 'code' => '7'), 'RW' => array('name' => '卢旺达', 'code' => '250'), 'SA' => array('name' => '沙特阿拉伯', 'code' => '966'), 'SB' => array('name' => '索罗蒙群岛', 'code' => '677'), 'SC' => array('name' => '塞舌尔', 'code' => '248'), 'SD' => array('name' => '苏丹', 'code' => '249'), 'SE' => array('name' => '瑞典', 'code' => '46'), 'SG' => array('name' => '新加坡', 'code' => '65'), 'SH' => array('name' => '圣海伦娜', 'code' => '290'), 'SI' => array('name' => '斯洛文尼亚', 'code' => '386'), 'SK' => array('name' => '斯洛伐克', 'code' => '421'), 'SL' => array('name' => '塞拉利昂', 'code' => '232'), 'SM' => array('name' => '圣马力诺', 'code' => '378'), 'SN' => array('name' => '塞内加尔', 'code' => '221'), 'SO' => array('name' => '索马里', 'code' => '252'), 'SR' => array('name' => '苏里南', 'code' => '597'), 'ST' => array('name' => '圣多美和普林西比', 'code' => '239'), 'SV' => array('name' => '薩爾瓦多', 'code' => '503'), 'SY' => array('name' => '阿拉伯叙利亚共和国', 'code' => '963'), 'SZ' => array('name' => '斯威士兰', 'code' => '268'), 'TC' => array('name' => '特克斯和凯科斯群岛', 'code' => '1649'), 'TG' => array('name' => '多哥', 'code' => '228'), 'TH' => array('name' => '泰国', 'code' => '66'), 'TJ' => array('name' => '塔吉克斯坦', 'code' => '992'), 'TK' => array('name' => '托克劳', 'code' => '690'), 'TL' => array('name' => '东帝汶', 'code' => '670'), 'TM' => array('name' => '土庫曼', 'code' => '993'), 'TN' => array('name' => '突尼斯', 'code' => '216'), 'TO' => array('name' => '汤加', 'code' => '676'), 'TR' => array('name' => '土耳其', 'code' => '90'), 'TT' => array('name' => '特立尼达和多巴哥', 'code' => '1868'), 'TV' => array('name' => '图瓦卢', 'code' => '688'), 'TW' => array('name' => '台湾中华人民共和国', 'code' => '886'), 'TZ' => array('name' => '坦桑尼亚联合共和国', 'code' => '255'), 'UA' => array('name' => '乌克兰', 'code' => '380'), 'UG' => array('name' => '乌干达', 'code' => '256'), 'US' => array('name' => '美国', 'code' => '1'), 'UY' => array('name' => '乌拉圭', 'code' => '598'), 'UZ' => array('name' => '乌兹别克斯坦', 'code' => '998'), 'VA' => array('name' => '圣座 (梵蒂冈城州)', 'code' => '39'), 'VC' => array('name' => '圣文森特和格林纳丁斯', 'code' => '1784'), 'VE' => array('name' => '委内瑞拉', 'code' => '58'), 'VG' => array('name' => '英属维尔京群岛', 'code' => '1284'), 'VI' => array('name' => '美屬維京群島', 'code' => '1340'), 'VN' => array('name' => '越南', 'code' => '84'), 'VU' => array('name' => '瓦努阿图', 'code' => '678'), 'WF' => array('name' => '瓦利斯和富图纳 ', 'code' => '681'), 'WS' => array('name' => '萨摩亚', 'code' => '685'), 'XK' => array('name' => '科索沃', 'code' => '381'), 'YE' => array('name' => '也门共和国', 'code' => '967'), 'YT' => array('name' => '马约特', 'code' => '262'), 'ZA' => array('name' => '南非', 'code' => '27'), 'ZM' => array('name' => '赞比亚', 'code' => '260'), 'ZW' => array('name' => '津巴布韦', 'code' => '263'));
		$columns = array_column($array, 'code');
		array_multisort($columns, SORT_ASC, $array);
		return $array;
	}

	function get_country_list_json()
	{
		$allSettings = json_decode(file_get_contents(AWS_PATH . "/" . AWS_BUCKET . "/json/countryList.json"), true);
		// $allSettings = json_decode(file_get_contents(APPLICATION_PATH . '/modules/api/json/countryList.json'),true);
		$array = array();
		foreach ($allSettings as $key => $row) {
			$array[$key] = $row['code'];
		}
		array_multisort($array, SORT_ASC, $allSettings);

		return $allSettings;
	}

	//Captcha
	function generatePIN($digits = 4)
	{
		$i = 0; //counter
		$pin = ""; //our default pin is blank.
		while ($i < $digits) {
			$pin .= "<span>" . mt_rand(0, 9) . "</span>";
			$i++;
		}
		return $pin;
	}


	public function awsUploadPrivate($params = array())
	{

		//
		/*
         * awsAccessKey = access key
         * awsSecretKey = secret key
         */
		$credentials = new Aws\Credentials\Credentials('', '');

		/*
         * profile
         * region = region of S3 bucket
         * version
         * credentials = access key and secret key
         */
		$s3Client = new Aws\S3\S3Client([
			//            'profile' => 'default',
			'region' => 'ap-southeast-1',
			'version' => '2006-03-01',
			'credentials' => $credentials
			//            'signature_version' => 'v4'
		]);

		$extension = $params["type"] == "image/png" ? ".png" : ".jpg";
		$key = APPLICATION_ENV . "/" . date("Y") . "/" . date("m") . "/" . $params["uid"] . "_" . round(microtime(true) * 1000) . "_" . basename($params["path"]) . $extension;

		/*
         * bucket = bucket of S3
         * Key = name of file retrieved
         */
		// development/2019/08/1000019_1565986730275_phpekWQKS.png
		// $cmd = $s3Client->getCommand('PutObject', [
		//     'Bucket' => '',
		//     'Key' => $key,
		//     'SourceFile' => $params["path"],
		// ]);

		try {

			$result = $s3Client->putObject([
				'Bucket' => '',
				'Key' => $key,
				'SourceFile' => $params["path"],
			]);
		} catch (S3Exception $e) {
			return false;
		}

		return $key;
	}

	public function awsUploadPublic($params = array())
	{

		//
		/*
         * awsAccessKey = access key
         * awsSecretKey = secret key
         */
		$credentials = new Aws\Credentials\Credentials('', '');

		/*
         * profile
         * region = region of S3 bucket
         * version
         * credentials = access key and secret key
         */
		$s3Client = new Aws\S3\S3Client([
			//            'profile' => 'default',
			'region' => 'ap-southeast-1',
			'version' => '2006-03-01',
			'credentials' => $credentials
			//            'signature_version' => 'v4'
		]);

		$extension = $params["type"] == "image/png" ? ".png" : ".jpg";
		$key = "backend/" . APPLICATION_ENV . "/" . date("Y") . "/" . date("m") . "/" . $params["uid"] . "_" . round(microtime(true) * 1000) . "_" . basename($params["path"]) . $extension;

		try {

			$result = $s3Client->putObject([
				'Bucket' => '',
				'Key' => $key,
				'ContentType' => 'image/jpeg',
				'SourceFile' => $params["path"],
			]);
		} catch (S3Exception $e) {
			return false;
		}

		return $key;
	}

	public function awsUploadPublicJson($params = array())
	{

		if (!isset($params["uploadToAwsFilepath"])) {
			return false;
		}
		if (!isset($params["localFilepath"])) { 
			return false;
		}
		// ******** Sample code how to use this 
		// $common = Zend_Controller_Action_HelperBroker::getStaticHelper('Common');
		// $link = $common->awsUploadPublicJson(array("uploadToAwsFilepath" => "backend/".APPLICATION_ENV . "/LatestInfo.json", "localFilepath" => APPLICATION_PATH."/cron/test.json"));

		// ******** How to get file
		// $string = file_get_contents("https://s3-ap-southeast-1.amazonaws.com/qwe/backend/development/LatestInfo.json");
		// $json = json_decode($string, true);
		//

		$credentials = new Aws\Credentials\Credentials('', '');

		$s3Client = new Aws\S3\S3Client([
			'region' => 'ap-southeast-1',
			'version' => '2006-03-01',
			'credentials' => $credentials
		]);

		try {

			$result = $s3Client->putObject([
				'Bucket' => '',
				'Key' => $params["uploadToAwsFilepath"],
				'ContentType' => 'application/json',
				'SourceFile' => $params["localFilepath"],
			]);
		} catch (S3Exception $e) {
			return false;
		}

		return $params["uploadToAwsFilepath"];
	}
}
