<?php
/*------------------------------------------------*\
	create sample flat file for terillium
\*------------------------------------------------*/
global $wpdb;

/*------------------------------------------------*\
	add header row to our master array of data

	hoff 9/3/14
	they no longer want a header row
\*------------------------------------------------*/
$arrData = array(
	/*array(
		'record_type',
		'ID',
		'Company_Name',
		'Address_Line_1',
		'Address_Line_2',
		'State',
		'County',
		'City',
		'Zip_Code',
		'Phone_Work',
		'Phone_24_Hour',
		'Phone_Mobile',
		'Fax',
		'Web_Address_URL',
		'Email_Address',
		'Year_Business_Established',
		'Is_Woman_Owned_Business',
		'Is_Minority_Owned_Business',
		'Is_Small_Business_Owned_Business',
		'Is_Veteran_Owned_Business',
		'Is_Headquarter_Location',
		'Annual_Revenue',
		'Has_General_Liability_Insurance',
		'Has_Auto_Insurance',
		'Has_Workers_Compensation_Insurance',
		'States_Serviced',
		'Counties_Serviced',
		'Zip_Codes_Serviced',
		'Contact_First_Name',
		'Contact_Last_Name',
		'Contact_Phone',
		'Contact_Phone_Mobile',
		'Contact_Email_Address',
		'Services_Performed',
		'Other_Services',
		'Qty_Plowing_Trucks',
		'Qty_Salt_Trucks',
		'Qty_Skid_Steers',
		'Qty_Backhoes',
		'Qty_Pushers',
		'Qty_Rubber_Tire_Loaders',
		'Qty_Landscape_Trucks',
		'Qty_Sweeper_Trucks',
		'Qty_Snow_Ice_Laborers',
		'Qty_Property_Laborers'
	)*/
);

/*------------------------------------------------*\
	get applicable service partner records
\*------------------------------------------------*/
$objSPs = new WP_Query(array(
	'post_type'	=> 'mss_service_partner',
	'meta_query' => array(
		'relation' => 'AND',
		array(
			'key'     => 'sp_vetted',
			'value'   => '1',
			'compare' => '=',
		),
		array(
			'key'     => 'sp_batched',
			'compare' => 'NOT EXISTS',
		),
	)
));

/*------------------------------------------------*\
	loop through sp's adding them to master array
\*------------------------------------------------*/
if ($objSPs->have_posts()) :
	while ($objSPs->have_posts()):
		$objSPs->the_post();

		// modifications necessary for Other_Services field
		$sOtherServices	= preg_replace("/&#?[a-z0-9]+;/i","", strip_tags($arrFields['sp_capabilities_other']));
		$sOtherServices	= str_replace(array("\n", "\r"), '', $sOtherServices);

		$arrRow		= array();
		$arrFields	= get_fields($objSPs->post->ID);
		$arrRow[]	= 1;
		$arrRow[]	= 'BOOM'.$objSPs->post->ID;
		$arrRow[]	= $arrFields['sp_company_name'];
		$arrRow[]	= $arrFields['sp_address_1'];
		$arrRow[]	= $arrFields['sp_address_2'];
		$arrRow[]	= $arrFields['sp_state'][0];
		$arrRow[]	= $arrFields['sp_county'][0];
		$arrRow[]	= $wpdb->get_var('SELECT city FROM _zipcodes WHERE zipcode_id = '.$arrFields['sp_city_zip'][0]);
		$arrRow[]	= $arrFields['sp_city_zip'][0];
		$arrRow[]	= $arrFields['sp_phone_work'];
		$arrRow[]	= $arrFields['sp_phone_24_hour'];
		$arrRow[]	= $arrFields['sp_phone_mobile'];
		$arrRow[]	= $arrFields['sp_fax'];
		$arrRow[]	= $arrFields['sp_url'];
		$arrRow[]	= $arrFields['sp_contact_main_email'];
		$arrRow[]	= $arrFields['sp_year_est'];
		$arrRow[]	= $arrFields['sp_is_woman_owned'] ? 1 : 0;
		$arrRow[]	= $arrFields['sp_is_minority_owned'] ? 1 : 0;
		$arrRow[]	= 0;
		$arrRow[]	= 0;
		$arrRow[]	= $arrFields['sp_is_headquarters'] ? 1 : 0;
		$arrRow[]	= $arrFields['sp_annual_revenue'];
		$arrRow[]	= $arrFields['sp_has_comprehensive_liability'] ? 1 : 0;
		$arrRow[]	= $arrFields['sp_has_automobile_liability'] ? 1 : 0;
		$arrRow[]	= $arrFields['sp_has_workers_compensation'] ? 1 : 0;
		$arrRow[]	= is_array($arrFields['sp_states_serviced']) ? implode(':', $arrFields['sp_states_serviced']) : '';
		$arrRow[]	= is_array($arrFields['sp_counties_serviced']) ? implode(':', $arrFields['sp_counties_serviced']) : '';
		$arrRow[]	= is_array($arrFields['sp_cities_zips_serviced']) ? implode(':', $arrFields['sp_cities_zips_serviced']) : '';
		$arrRow[]	= $arrFields['sp_contact_main_first'];
		$arrRow[]	= $arrFields['sp_contact_main_last'];
		$arrRow[]	= $arrFields['sp_contact_main_phone'];
		$arrRow[]	= $arrFields['sp_contact_main_phonemobile'];
		$arrRow[]	= $arrFields['sp_contact_main_email'];
		$arrRow[]	= is_array($arrFields['sp_capabilities']) ? implode(':', $arrFields['sp_capabilities']) : '';
		$arrRow[]	= $sOtherServices;
		$arrRow[]	= $arrFields['sp_qty_plowing_trucks'];
		$arrRow[]	= $arrFields['sp_qty_salt_trucks'];
		$arrRow[]	= $arrFields['sp_qty_skid_steers'];
		$arrRow[]	= $arrFields['sp_qty_backhoes'];
		$arrRow[]	= $arrFields['sp_qty_pushers'];
		$arrRow[]	= $arrFields['sp_qty_rubber_tire_loaders'];
		$arrRow[]	= $arrFields['sp_qty_landscape_trucks'];
		$arrRow[]	= $arrFields['sp_qty_sweeper_trucks'];
		$arrRow[]	= $arrFields['sp_qty_snowice_laborers'];
		$arrRow[]	= $arrFields['sp_qty_prop_laborers'];

		/*--------------------------------------------------------------------------------------------------*\
			loop through this row appending some bogus characters onto the end of each field so we can later
			force double quotes around empty fields in the resulting flat file
		\*--------------------------------------------------------------------------------------------------*/
		foreach ($arrRow as $key => $value):
			$arrRow[$key] = $value.'#@ @#';
		endforeach;

		$arrData[]	= $arrRow;
	endwhile;
else :
	// response output
	echo json_encode(
		array(
			'status' => 1,
			'message' => 'No vetted Service Partners waiting to be batched to remote server...',
			)
		);

	exit;
endif;


/*------------------------------------------------*\
	get the remote version of the flat file
\*------------------------------------------------*/
// source file path
$sFilePath = plugin_dir_path(__DIR__) . plugin_basename(__DIR__) . '/output/BTInbound.txt';

try {
	if (
		!($spHost = get_option('sp_host')) ||
		!($spPort = get_option('sp_port')) ||
		!($spUsername = get_option('sp_username')) ||
		!($spPassword = get_option('sp_password')) ||
		!($dFilePath = get_option('sp_dfilepath') . '/BTInbound.txt')
		) {
		throw new Exception('Missing connection information...', 0);
	}

	// create an connection resource
	if (!$ssh2_connect = @ssh2_connect($spHost, $spPort)) {
		throw new Exception('Connection to remote server failed...', 0);
	}

	// cauthenticate with the connection resource
	if (!@ssh2_auth_password($ssh2_connect, $spUsername, $spPassword)) {
		throw new Exception('Authentication with remote server failed...', 0);
	}

	// open the SSH connection
	$ssh2_sftp = ssh2_sftp($ssh2_connect);

	// if the remote file exists, copy it
	if (@ssh2_sftp_stat($ssh2_sftp, $dFilePath)) {
		if (!@ssh2_scp_recv($ssh2_connect, $dFilePath, $sFilePath)) {
			throw new Exception('Receiving file from remote server failed...', 0);
		}
	}

	/*------------------------------------------------*\
		save flat file
	\*------------------------------------------------*/
	$file = fopen($sFilePath, 'a');
	foreach ($arrData as $row):
		fputcsv($file, $row);
	endforeach;
	fclose($file);

	/*--------------------------------------------------------------------------------------------------*\
		grab the contents of our flat file, replace our bogus characters, and then get outta there
	\*--------------------------------------------------------------------------------------------------*/
	$contents = file_get_contents($sFilePath);
	$contents = str_replace('#@ @#', '', $contents);
	file_put_contents($sFilePath, $contents);

	// upload the source file to the destination
	if (!@ssh2_scp_send($ssh2_connect, $sFilePath, $dFilePath)) {
		throw new Exception('Sending file to remote server failed...', 0);
	}

	unlink($sFilePath);

	// response output
	echo json_encode(
		array(
			'status' => 1,
			'message' => count($arrData) . ' vetted Service Partners batched to remote server...',
			)
		);

	while ($objSPs->have_posts()):
		$objSPs->the_post();
		update_post_meta($objSPs->post->ID, 'sp_batched', '1');
	endwhile;

	// close the ssh2 connection
	if (!@ssh2_exec($ssh2_connect, 'exit')) {
		throw new Exception('Closing session with remote server failed...', 0);
	}

} catch (Exception $e) {
	error_log('Exception: ' . $e->getMessage());

	// response output
	echo json_encode(
		array(
			'status' => $e->getCode(),
			'message' => $e->getMessage(),
			)
		);
}
