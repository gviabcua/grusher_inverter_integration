<?php
	// set variables
	// grusher data
	define("GRUSHER_URL", "http://SET_GRUSHER_IP");
	define("GRUSHER_NOTE_ID", 1); // SET GRUSHER NOTE ID
	// zabbix data
	define("ZABBIX_ALLOW", 1); // 1 or 0
	define("ZABBIX_URL", "SET_GRUSHER_IP"); // zabbix server url
	define("ZABBIX_ITEM_KEY", "zab_key_gr"); // key item in Zabbix
	// GROWATT
	define("GROWATT_TOKEN", "token");
	
	// LOGIC
	$path = __DIR__ ;
	$get_note = @json_decode(file_get_contents(GRUSHER_URL."/api?cat=3rdparty&action=notes&id=".GRUSHER_NOTE_ID));
	if(isset($get_note->result) and isset($get_note->result->description)){
		$description = $get_note->result->description;
		$list = get_all_string_between($description, '<li>', '</li>');
		$array_of_sn = [];
		$array_of_data = [];
		if(!empty($list)){
			foreach ($list as $l) {
				$exploder = explode("|", $l);
				if(isset($exploder[2])){
					$temp_sn = strip_tags(trim($exploder[0]));
					$temp_zabbix_host = str_replace(['&nbsp;'], "", strip_tags(trim($exploder[1])));
					$temp_name = strip_tags(trim($exploder[2]));
					$array_of_sn[$temp_sn] = $temp_sn;
					$array_of_data[$temp_sn]['zabbix_host'] = $temp_zabbix_host;
					$array_of_data[$temp_sn]['name'] = $temp_name;
					$array_of_data[$temp_sn]['sn'] = $temp_sn;
					$array_of_data[$temp_sn]['grusher_device_id'] = getGrusherDeviceId($temp_sn);
				}
			}
			//print_r($array_of_data);
			if(!empty($array_of_sn)){
				$sns = implode(',', $array_of_sn);
				$json = china1($sns);
				$json = @json_decode($json);
				if(isset($json->data) and isset($json->data->storage) and is_array($json->data->storage) and !empty($json->data->storage)){
					foreach($json->data->storage as $d){
						$serialNum = "";
						$zabbix_host = "";
						$name = "";
						$sn = "";
						$grusher_device_id = "";
						if(isset($d->serialNum)){
							$serialNum = $d->serialNum;
							if(isset($array_of_data[$serialNum])){
								$zabbix_host = $array_of_data[$serialNum]['zabbix_host'];
								$name = $array_of_data[$serialNum]['name'];
								$sn = $array_of_data[$serialNum]['sn'];
								$grusher_device_id = $array_of_data[$serialNum]['grusher_device_id'];
							}else{
								echo "No data by serialNum".PHP_EOL;
								continue;
							}
						}else{
							echo "No serialNum".PHP_EOL;
							continue;
						}
						try{
							if(isset($d->time)){
								$value = $d->time;
								$value = strtotime($value);
								echo PHP_EOL.PHP_EOL."Time Diff: ".strtotime(date("Y-m-d H:i:s")) ." - ".$value." = ".(strtotime(date("Y-m-d H:i:s")) - $value). PHP_EOL;
								if((strtotime(date("Y-m-d H:i:s")) - $value) > 720){
									echo "$name SKIPPED. LAST TIME IS ".$d->time.PHP_EOL.PHP_EOL;
									continue;
								}
								$zabbix_key = ZABBIX_ITEM_KEY.".time";
								sendToZabbix($zabbix_host, $zabbix_key, $value);
							}
						} catch (Exception $e) {}
						// sending
						if(isset($d->vBat)){
							$value = $d->vBat;
							$zabbix_key = ZABBIX_ITEM_KEY.".batv";
							$grusher_key = "BATTERY_VOLTAGE";
							sendToGrusher($grusher_device_id, $grusher_key, $value);
							sendToZabbix($zabbix_host, $zabbix_key, $value);
						}
						if(isset($d->capacity)){
							$value = $d->capacity;
							$zabbix_key = ZABBIX_ITEM_KEY.".capacity";
							$grusher_key = "BATTERY_SOC";
							sendToGrusher($grusher_device_id, $grusher_key, $value);
							sendToZabbix($zabbix_host, $zabbix_key, $value);
						}
						if(isset($d->outPutPower)){
							$value = $d->outPutPower;
							$zabbix_key = ZABBIX_ITEM_KEY.".outputpower";
							$grusher_key = "Energy";
							sendToGrusher($grusher_device_id, $grusher_key, $value);
							sendToZabbix($zabbix_host, $zabbix_key, $value);
						}
						if(isset($d->vGrid)){
							$value = $d->vGrid;
							$zabbix_key = ZABBIX_ITEM_KEY.".inv";
							$grusher_key = "GRID_VOLTAGE_INPUT";
							sendToGrusher($grusher_device_id, $grusher_key, $value);
							sendToZabbix($zabbix_host, $zabbix_key, $value);
						}
						if(isset($d->freqGrid)){
							$value = $d->freqGrid;
							$zabbix_key = ZABBIX_ITEM_KEY.".freqgrid";
							$grusher_key = "GRID_FREQUENCY IN";
							sendToGrusher($grusher_device_id, $grusher_key, $value);
							sendToZabbix($zabbix_host, $zabbix_key, $value);
						}
						if(isset($d->freqOutPut)){
							$value = $d->freqOutPut;
							$zabbix_key = ZABBIX_ITEM_KEY.".frequencyoutput";
							$grusher_key = "GRID_FREQUENCY OUT";
							sendToGrusher($grusher_device_id, $grusher_key, $value);
							sendToZabbix($zabbix_host, $zabbix_key, $value);
						}
						if(isset($d->outPutVolt)){
							$value = $d->outPutVolt;
							$zabbix_key = ZABBIX_ITEM_KEY.".outv";
							$grusher_key = "GRID_VOLTAGE_OUTPUT";
							sendToGrusher($grusher_device_id, $grusher_key, $value);
							sendToZabbix($zabbix_host, $zabbix_key, $value);
						}
						if(isset($d->loadPercent)){
							$value = $d->loadPercent;
							$zabbix_key = ZABBIX_ITEM_KEY.".loadpercent";
							$grusher_key = "CPU (LOAD %)";
							sendToGrusher($grusher_device_id, $grusher_key, $value);
							sendToZabbix($zabbix_host, $zabbix_key, $value);
						}
						if(isset($d->pAcInPut)){
							$value = $d->pAcInPut;
							$zabbix_key = ZABBIX_ITEM_KEY.".pacinput";
							$grusher_key = "GRID_Energy";
							sendToGrusher($grusher_device_id, $grusher_key, $value);
							sendToZabbix($zabbix_host, $zabbix_key, $value);
						}
						if(isset($d->invTemperature)){
							$value = $d->invTemperature;
							$zabbix_key = ZABBIX_ITEM_KEY.".invtemperature";
							$grusher_key = "Temp Inverter";
							sendToGrusher($grusher_device_id, $grusher_key, $value);
							sendToZabbix($zabbix_host, $zabbix_key, $value);
						}
						if(isset($d->dcDcTemperature)){
							$value = $d->dcDcTemperature;
							$zabbix_key = ZABBIX_ITEM_KEY.".dcdctemperature";
							$grusher_key = "Temp DC-DC";
							sendToGrusher($grusher_device_id, $grusher_key, $value);
							sendToZabbix($zabbix_host, $zabbix_key, $value);
						}
						if(isset($d->buck1_NTCTemperature)){
							$value = $d->buck1_NTCTemperature;
							$zabbix_key = ZABBIX_ITEM_KEY.".buck1_NTCTemperature";
							$grusher_key = "Temp buck1_NTCTemperature";
							sendToGrusher($grusher_device_id, $grusher_key, $value);
							sendToZabbix($zabbix_host, $zabbix_key, $value);
						}
						if(isset($d->buck2_NTCTemperature)){
							$value = $d->buck2_NTCTemperature;
							$zabbix_key = ZABBIX_ITEM_KEY.".buck2_NTCTemperature";
							$grusher_key = "Temp buck2_NTCTemperature";
							sendToGrusher($grusher_device_id, $grusher_key, $value);
							sendToZabbix($zabbix_host, $zabbix_key, $value);
						}
						if(isset($d->statusText)){
							$value = $d->statusText;
							$zabbix_key = ZABBIX_ITEM_KEY.".statustext";
							sendToZabbix($zabbix_host, $zabbix_key, $value, 1);
						}
						try{
							if(isset($d->time)){
								$value = $d->time;
								$value = strtotime($value);
								$zabbix_key = ZABBIX_ITEM_KEY.".time";
								sendToZabbix($zabbix_host, $zabbix_key, $value);
							}
						} catch (Exception $e) {}

					}
				}else{
					echo print_r($json).PHP_EOL;
					exit("No data from China");
				}
			}else{
				exit("No SNs");
			}
		}else{
			exit('Empty list');
		}
	}else{
		exit("No notes");
	}

	function getGrusherDeviceId($hostname){
		$get = @json_decode(file_get_contents(GRUSHER_URL."/api?cat=db&action=device_id_by_hostname&hostname=$hostname&type=first_like"));
		if(isset($get->result) and isset($get->result->id) and ($get->result->id > 0)){
			return $get->result->id;
		}
		return null;
	}
	function sendToGrusher($device_id, $metric, $value){
		echo PHP_EOL." ================================================= ".PHP_EOL;
		$ctx = stream_context_create(array('http'=>array('timeout' => 2,)));
		$url = GRUSHER_URL."/api?cat=device&action=send_metrics&device_id=" . (int)$device_id . '&metric=' . urlencode($metric) . '&value=' . $value;
		@file_get_contents($url, false, $ctx);
		echo $url.PHP_EOL.PHP_EOL;
		return true;
	}
	function sendToZabbix($host, $metric, $value, $as_text = 0){
		if (ZABBIX_ALLOW == 1){
			if($as_text == 1){
				$command = "zabbix_sender -z ".ZABBIX_URL." L -s ". $host. " -k ". $metric. " -o '" . $value."'";
			}else{
				$command = "zabbix_sender -z ".ZABBIX_URL." -s ". $host. " -k ". $metric. " -o " . $value;
			}
			echo $command.PHP_EOL;
			echo shell_exec($command);
			echo PHP_EOL;
			echo PHP_EOL;
		}
		return true;
	}

	function china1($serials){
		$url = "https://openapi-cn.growatt.com/v4/new-api/queryLastData?deviceType=storage&deviceSn=$serials";
		$headers = [
			"token: ".GROWATT_TOKEN."",
			"Accept: application/json",
			"Content-Type: application/x-www-form-urlencoded"
		];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$response = curl_exec($ch);
		curl_close($ch);
		print_r($response);
		return $response;
	}

	function get_all_string_between($string, $start, $end)
	{
		$result = array();
		$string = " ".$string;
		$offset = 0;
		while(true)
		{
			$ini = strpos($string,$start,$offset);
			if ($ini == 0)
				break;
			$ini += strlen($start);
			$len = strpos($string,$end,$ini) - $ini;
			$result[] = substr($string,$ini,$len);
			$offset = $ini+$len;
		}
		return $result;
	}
?>