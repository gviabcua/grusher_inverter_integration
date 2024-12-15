import requests
import json
import subprocess
import time
import random
import os

ZABBIX_URL = '1.1.1.1'
ZABBIX_ALLOW = 0 # 0 or 1
GRUSHER_URL = 'http://192.168.0.1'
GET_TOKEN_EVERY_REQUEST = 1 # change to 0 when get first token. It will getted periodically
AUTH_BY = "email" #  email or phone
# DEYE DATA
# Get data in https://developer.deyecloud.com/start
APPLICATION_ID = "202412130258412"
APPLICATION_SECRET = "1654635hjf38ds6e86135set83vs3e8ee"
PASSWORD = "5fg5sdg834138sfs6f4s3f6s8d7fs6fs43sdf4s8g4r3bs43dae8g4h8k46k843dsf4sg88asfd" # Field 'password' should be in SHA256 encrypted and in lowercase. 
# if auth by email
EMAIL = "email@email.com"
# if auth by phone
COUNTRY_CODE = "380"
MOBILE = "671234567"



# AUTH DATA
if AUTH_BY == 'email':
  auth_data = {
      "appSecret": APPLICATION_SECRET,
      "email": EMAIL,  #email of DeyeCloud account
      "password": PASSWORD #password of DeyeCloud account
  }
elif AUTH_BY == 'phone':
  auth_data = {
      "appSecret": APPLICATION_SECRET,
      "mobile": MOBILE,  #mobile of DeyeCloud account
      "countryCode": COUNTRY_CODE,  #countryCode of DeyeCloud account
      "password": PASSWORD #password of DeyeCloud account
  }
else:
  print("NO AUTH");
  exit;

def send_to_grusher (data_element, item_key, value):
    item_key = item_key.replace("deye.", "")
    print(item_key)
    if item_key == "GridVoltageL1L2":
        item_key = "GRID_VOLTAGE_INPUT"
    elif item_key == "GridCurrentL1L2":
        item_key = "GRID_AMPERAGE"
    elif item_key == "ExternalCTPowerL1L2":
        item_key = "GRID_Energy External"
    elif item_key == "TotalGridPower":
        item_key = "GRID_Energy Total"
    elif item_key == "BatteryVoltage":
        item_key = "BATTERY_VOLTAGE"
    elif item_key == "BatteryCurrent":
        item_key = "BATTERY_AMPERAGE"
    elif item_key == "BatteryPower":
        item_key = "BATTERY_ENERGY"
    elif item_key == "SOC":
        item_key = "BATTERY_SOC"
    elif item_key == "GridFrequency":
        item_key = "GRID_FREQUENCY"
    else:
        print ("no item_key")
        return
    exit;
    # GRUSHER DEVICE_IDS
    if data_element == "Inverter_1":
        device_id = 10001
    elif data_element == "Inverter_1":
        device_id = 10002
    elif data_element == "Inverter_3":
        device_id = 10003
    elif data_element == "Inverter_4":
        device_id = 10004
    elif data_element == "Inverter_5":
        device_id = 10005
    else:
        print ("no data_element")
        return
    try:
        url_gr = GRUSHER_URL + '/api?cat=device&action=send_metrics&device_id=' + str(device_id) + '&metric=' + str(item_key) + '&value=' + str(value)
        print(url_gr)
        a = requests.get(url_gr, timeout=3)
        print(a.content)
    except requests.exceptions.HTTPError as err:
        print(f"47 - HTTP error occurred: {err}")
    except Exception as err:
        print(f"49 - Other error occurred: {err}")


def send_to_zabbix(data_element, item_key, value):
    if ZABBIX_ALLOW == 1:
        print('Sending to Zabbix: ', data_element, item_key, " = ", value)
        print('zabbix_sender', '-z', ZABBIX_URL, '-s', data_element, '-k', item_key, '-o', str(value))
        subprocess.call(['zabbix_sender', '-z', ZABBIX_URL, '-s', data_element, '-k', item_key, '-o', str(value)])
        print()
        print("Sending to Grusher ")
    send_to_grusher (data_element, item_key, value)
    print()

if __name__ == '__main__':
  accessToken = ""
  path = os.path.dirname(os.path.realpath(__file__))
  print("PATH = " + str(path))
  rand = random.randint(1, 250) 
  if GET_TOKEN_EVERY_REQUEST == 1:
    rand = 1

  if rand == 1:
    print('Getting token from API')
    url = 'https://eu1-developer.deyecloud.com/v1.0/account/token?appId=' + str(APPLICATION_ID)
    headers = {
        'Content-Type': 'application/json'
    }
    print("Getting Access Token")
    try:
        # Send POST Request 
        response = requests.post(url, headers=headers, json=auth_data)
        response = response.json()
        response = json.dumps(response)
        get = json.loads(response)
        print("Access Token is:")
        accessToken = get['accessToken']
        print(accessToken)
    except requests.exceptions.HTTPError as err:
        print(f"35 - HTTP error occurred: {err}")
        exit()
    except Exception as err:
        print(f"38 - Other error occurred: {err}")
        exit()

    f = open(path + "/deyekey.py", "w")
    f.write(accessToken)
    f.close()
  else:
    print('Getting token from file')
    f = open(path + "/deyekey.py", "r")
    accessToken = f.read()
    print(accessToken) 
    f.close()

  accessToken = accessToken.strip()
  
  url = 'https://eu1-developer.deyecloud.com/v1.0/station/listWithDevice'
  headers = {
        'Authorization': 'bearer ' + str(accessToken),
        'Content-Type': 'application/json;charset=UTF-8'
  }
  data = {
	  "deviceType": "INVERTER",
	  "page": 1,
	  "size": 20
  }
  print(data)
  time.sleep(1)
  try:
      # Send POST Request 
      response = requests.post(url, headers=headers, json=data)
      response = response.json()
      response = json.dumps(response)
      get = json.loads(response)
      print(get)
      stationList = get['stationList']
      time.sleep(1)
      for station in stationList: 
          print()
          print()
          print("Station: ", station["id"], station["name"], station["locationAddress"], station["gridInterconnectionType"], station["installedCapacity"])
          print("Connected devices:")
          try:
            for devices in station["deviceListItems"]:
              deviceSn = devices['deviceSn']
              deviceType = devices['deviceType']
              stationId = devices['stationId']
              print(' - ', deviceSn, deviceType, str(stationId))
              url = 'https://eu1-developer.deyecloud.com/v1.0/device/latest'
              headers = {
                'Authorization': 'bearer ' + str(accessToken),
                'Content-Type': 'application/json;charset=UTF-8'
              }
              data = {
                "deviceList": [
                  deviceSn
                ]
              }
              try:
                # Send POST Request 
                response = requests.post(url, headers=headers, json=data)
                response = response.json()
                response = json.dumps(response)
                get_devices = json.loads(response)
                time.sleep(1)
                for dl in get_devices['deviceDataList']:
                  for datas in dl['dataList']:
                    if datas['key'] == "GridVoltageL1L2":
                      send_to_zabbix(station["name"], 'deye.' + str(datas['key']), datas['value'])
                    if datas['key'] == "GridCurrentL1L2":
                      send_to_zabbix(station["name"], 'deye.' + str(datas['key']), datas['value'])
                    if datas['key'] == "ExternalCTPowerL1L2":
                      send_to_zabbix(station["name"], 'deye.' + str(datas['key']), datas['value'])
                    if datas['key'] == "GridFrequency":
                      send_to_zabbix(station["name"], 'deye.' + str(datas['key']), datas['value'])
                    if datas['key'] == "TotalGridPower":
                      send_to_zabbix(station["name"], 'deye.' + str(datas['key']), datas['value'])
                    if datas['key'] == "BatteryVoltage":
                      send_to_zabbix(station["name"], 'deye.' + str(datas['key']), datas['value'])
                    if datas['key'] == "BatteryCurrent":
                      send_to_zabbix(station["name"], 'deye.' + str(datas['key']), datas['value'])
                    if datas['key'] == "BatteryPower":
                      send_to_zabbix(station["name"], 'deye.' + str(datas['key']), datas['value'])
                    if datas['key'] == "SOC":
                      send_to_zabbix(station["name"], 'deye.' + str(datas['key']), datas['value'])
                    if datas['key'] == "GeneratorFrequency":
                      send_to_zabbix(station["name"], 'deye.' + str(datas['key']), datas['value'])
                    if datas['key'] == "GenVoltage":
                      send_to_zabbix(station["name"], 'deye.' + str(datas['key']), datas['value'])
                    if datas['key'] == "TotalGeneratorProduction":
                      send_to_zabbix(station["name"], 'deye.' + str(datas['key']), datas['value'])
              except requests.exceptions.HTTPError as err:
                print(f"79 - HTTP error occurred: {err}")
              except Exception as err:
                print(f"80 - Other error occurred: {err}")
                print(get_devices)
          except requests.exceptions.HTTPError as err:
            print(f"71 - HTTP error occurred: {err}")
          except Exception as err:
            print(f"73 - Other error occurred: {err}")
            print(stationList)
  except requests.exceptions.HTTPError as err:
      print(f"95 - HTTP error occurred: {err}")
      exit()
  except Exception as err:
      print(f"97 - Other error occurred: {err}")
      exit()
