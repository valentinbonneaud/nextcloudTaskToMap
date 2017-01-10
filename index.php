<!DOCTYPE html>

<?php 

include_once("connect.php");

?>

<html>

	<head>
		<meta name="viewport" content="initial-scale=1.0; user-scalable=no; text/html" charset="utf8"/>

		<style type="text/css">
			html { height: 100% }
			body { height: 100%; margin: 0; padding: 0 }
			#map-canvas { width: 100%; } 
		</style>
		<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?libraries=drawing&key=AIzaSyCxyaIp1mOe-MnCtZbtj2AyApHj6hoUJWM&sensor=true"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
	   
		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">

		<!-- Optional theme -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap-theme.min.css">

		<!-- Latest compiled and minified JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>

<?php

function splitString($string, $element) {

	$pos = strpos($string, $element);
	if ($pos === false) {
		return null;
	} else {
		return substr($string, $pos + 1);
	}
}

class Location {
	public $UID;
	public $name;
	public $note;
	public $gps;
	public $parent;
	public $childs;

	public function __construct($data) {

		$this->childs = array();
		$this->UID = "";
		$this->name = "";
		$this->note = "";
		$this->gps = array();
		$this->parent = "";

		foreach ($data as $d) {

			$d = trim(preg_replace('/\s+/', ' ', $d));

			if (strpos($d, 'UID:') !== false) {
				$this->UID = splitString($d, ':');
			}

                        if (strpos($d, 'SUMMARY:') !== false) {
                                $this->name = str_replace('\\,', ',', splitString($d, ':'));
                        }

                        if (strpos($d, 'DESCRIPTION:') !== false) {
				$d = splitString($d, ':');
				$note_splitted = explode("\\n", $d);

				foreach($note_splitted as $c) {
		                        if (strpos($c, 'GPS:') !== false) {
		                                $gps = splitString($c, ':');
						$gps_cleaned = str_replace('\\', '', $gps);
						$gps_a = preg_split("/[\\, ]+/", $gps_cleaned);

						$this->gps['lat'] = $gps_a[0];
						$this->gps['lon'] = $gps_a[1];
                        		} else {
						$this->note .= $c."</br>";
					}
				}

                        }

                        if (strpos($d, 'RELATED-TO:') !== false) {
                                $this->parent = splitString($d, ':');
                        }

		}
	}

	public function printObj() {

		echo $this->UID."</br>";
                echo $this->name."</br>";
                echo $this->note."</br>";
                echo $this->gps['lat'].",".$this->gps['lon']."</br>";
		echo $this->parent."</br>";
                echo "</br>";
		
	}

	public function printJS() {

		if (count($this->childs) == 0 && count($this->gps) > 0) {
			echo 'var contentString = \'<div id="content"><div id="siteNotice"></div><h3>'.$this->name.'</h3><div id="bodyContent"><p>'.$this->note.'</p></div></div>\';';
			echo "var infowindow".$this->UID." = new google.maps.InfoWindow({ content: contentString });\n";
			echo "var marker".$this->UID." = new google.maps.Marker({position: { lat: ".$this->gps['lat'].", lng: ".$this->gps['lon']." }, map: map, title: '".str_replace("'", "\\'", $this->name)."'});\n";
			echo "marker".$this->UID.".addListener('click', function() { infowindow".$this->UID.".open(map, marker".$this->UID.");});\n";
		}

	}

}

$db = new PDO("mysql:host=$server;dbname=$db_name;charset=utf8mb4", $userMysql, $userPass);

$request = "SELECT CONVERT(calendardata USING utf8) AS data FROM `oc_calendarobjects` WHERE `calendarid`=$calendarId";

$locations = array();

foreach($db->query($request) as $row) {
	//echo $row['data']."</br>";
	$array = preg_split ('/$\R?^/m', $row['data']);
	$l = new Location($array);
	//$l->printObj();	
	$locations[] = $l;
}

$parents = array();

foreach ($locations as $l) {
	if ($l->parent == "") {
		if (!array_key_exists($l->UID, $parents)){
			$parents[$l->UID] = $l;
		}
	}
}

foreach ($locations as $l) {
	if ($l->parent != "") {
                if (array_key_exists($l->parent, $parents)){
                        $parents[$l->parent]->childs[] = $l;
                }
        }

}


echo "<script> $(document).ready(function() {";

echo "var map = new google.maps.Map(document.getElementById('map-canvas'), {center: new google.maps.LatLng(25.272242254906,51.610719209717), zoom: 2});";

foreach($locations as $l) {
	$l->printJS();


//echo "$.post('https://maps.googleapis.com/maps/api/geocode/json?address='+encodeURIComponent('".str_replace("'", "\\'", $l->name)."'), {} , function (json) { console.log('".str_replace("'", "\\'", $l->name)."'); console.log(json['results'][0]['geometry']['location']);}, 'json');\n";

}


echo "}); </script>";

?>
	</head>
	<body>

		<div class="row" style="height: 100%;">
			<div class="col-md-3" style="height: 100%; overflow-y: scroll; ">

<?php
foreach($parents as $p) {
        echo "- $p->name</br>";

        foreach($p->childs as $c) {
                echo "<div style='padding: 0px 15px;'>- $c->name</div>";
        }

        echo "</br>";
}
?>

	
			</div>
			<div class="col-md-9" style="height: 100%;">
				<div id="map-canvas" style="height: 100%;"></div>
			</div>
		</div>

	</body>
</html>
