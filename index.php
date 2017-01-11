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
		<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?libraries=drawing&key=<?=$API_KEY_MAPS?>&sensor=true"></script>
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

		// regex to find urls
		$re = '/((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[\w]*))?)/';

		for($i=0;$i<count($data);$i++){

			$d = trim($data[$i]);

			// we check if the next line start with a space (if yes, it is the continuation of the current line)
			while( ($i+1) < count($data) && $data[$i+1][0] == " ") {

				$d = preg_replace('/\s+/', ' ', $d) . trim($data[$i+1]);
				$i++;
			}

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

						preg_match($re, $c, $matches);

						if (count($matches) > 0) {
							$new = "<a href='$matches[1]' target='_blank'>$matches[1]</a>";
							$c = str_replace($matches[1], $new, $c);
						}
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

	public function getName($escape=TRUE) {
		if ($escape) {
			return str_replace("'", "\\'", $this->name);
		}

		return $this->name;
	}

        public function getNote($escape=TRUE) {
                if ($escape) {
                        return str_replace("'", "\\'", $this->note);
                }

                return $this->note;
        }


	public function printJS() {

		if (count($this->childs) == 0 && count($this->gps) > 0) {
			echo 'var contentString = \'<div id="content"><div id="siteNotice"></div><h2>'.$this->getName().'</h3><div id="bodyContent"><p>'.$this->getNote().'</p><p><a href="https://duckduckgo.com/?q=\'+encodeURIComponent("'.$this->getName().'")+\'" target="_blank">Search '.$this->getName().'</a></p></div></div>\';'."\n";
			echo "createMarker(map, '".$this->getName()."', { lat: ".$this->gps['lat'].", lng: ".$this->gps['lon']." }, contentString);";
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

?>

<script> 

var infowindow;

function createMarker(map, title, latlng, content) {
    var marker = new google.maps.Marker({
        position: latlng,
        map: map,
	title: title
    });
    google.maps.event.addListener(marker, "click", function() {
        if (infowindow) infowindow.close();
        infowindow = new google.maps.InfoWindow({
            content: content
        });
        infowindow.open(map, marker);
    });
    return marker;
}

$(document).ready(function() {

var map = new google.maps.Map(document.getElementById('map-canvas'), {center: new google.maps.LatLng(25.272242254906,51.610719209717), zoom: 2});

<?php

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
        echo "- ".$p->getName(FALSE)."</br>";

        foreach($p->childs as $c) {
                echo "<div style='padding: 0px 15px;'>- ".$c->getName(FALSE)."</div>";
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
