<?php
	/**
	 * =================================================
	 * USE models/LabelsPrintersModel Instead!!!!
	 * @deprecated
	 * =================================================
	 */
	class ConfigPrinters{
		/**
		 * =================================================
		 * USE models/LabelsPrintersModel Instead!!!!
		 * @deprecated
		 * =================================================
		 */
		static function getPrinters(){
			$printers = array(
				'wifi4x4' => array('name'=>'Phys Inv Wifi Zebra','size'=>'4x4'),

				'tninv4x4' => array('name'=>'TN INV 4x4 .205','size'=>'4x4'),
				'ilit4x4' => array('name'=>'IL IT 4x4','size'=>'4x75'),
				'ilit4x75' => array('name'=>'IL IT 4x.75','size'=>'4x4'),
				'dyhana' => array('name'=>'Zebra  TLP2844 Dyhana 4x4','size'=>'4x4'),
				'inv' => array('name'=>'Zebra  TLP2844 Dyhana 4x4','size'=>'4x4'),
				'cage' => array('name'=>'Zebra  TLP2844 4x4 Cage 235','size'=>'4x4'),
				'receiving' => array('name'=>'Zebra  TLP2844 4x.75 (00074d2b133b)','size'=>'4x4'),
				'andrew' => array('name'=>'Zebra  TLP2844 .13 4x4','size'=>'4x4'),
				'engineering4' => array('name'=>'Zebra  TLP2844 .42 4x4','size'=>'4x4'),
				'engineering75' => array('name'=>'Zebra  TLP2844 Engineering','size'=>'4x75'),

				'laserbad' => array('name'=>'Laser Labels 4x75','size'=>'4x75'),
				'laserhotjobs' => array('name'=>'Laser HOT Labels 4x75','size'=>'4x75'),
				'laser' => array('name'=>'Laser HOT Labels 4x75','size'=>'4x75'),

				'filler' => array('name'=>'\\\\sv181\\ZebraTLP','size'=>'4x75'),
				'shipping' => array('name'=>'Zebra  TLP2844 .44 Shipping 4x4','size'=>'4x4'),
				'mobile' => array('name'=>'Zebra  QL320 .76','size'=>'mobile'),
				'cart' => array('name'=>'Zebra TLP2844 .189','size'=>'4x4'),
				'lith14x4' => array('name'=>'Zebra  TLP2844 LITH 1 4x4','size'=>'4x4'),
				'lith24x4' => array('name'=>'Zebra  TLP2844 LITH2 4x4','size'=>'4x4'),
				's4' => array('name'=>'Zebra  TLP2844 - 4x.75 - S4 - .19','size'=>'4x75'),

				'tn-inv-4x4' => array('name'=>'TN INV 4x4 .205','size'=>'4x4'),
				'tn-inv-4x75' => array('name'=>'TN INV 4x.75 .207','size'=>'4x75'),
				'tn-inv2-4x4' => array('name'=>'TN INV Phys Inv 4x4 .220','size'=>'4x4'),
				'tn-rcv-4x75' => array('name'=>'TN Receiving 4x.75 .231','size'=>'4x75'),
                'tn-rcv-4x4' => array('name'=>'TN Receiving 4x4 .204','size'=>'4x4')
			);
			return $printers;
		}
	}