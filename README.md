# GRUSHER API INVERTER
> ### **These scripts are one of the author's visions of how it could work. You can use them as is or rewrite them to suit your needs and logic.**

## DEYE
##### To run need Python3


Move _deye.py_ and _deyekey.py_ to `/opt/inverters/` (or in other folder)


##### Create device in Grusher without IP:
* Hostname: ANY
* Vendor: other
* Model: Power monitor

And *get device ID* in URL

##### Go to https://developer.deyecloud.com/start and create you application

Fill config fields in _deye.py_
```
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
```
> Field 'password' should be in SHA256 encrypted and in lowercase. 

> After first success authorisation change GET_TOKEN_EVERY_REQUEST to 0 . Token will getted periodically



##### Also you need to change device name & device_id
```
# GRUSHER DEVICE_IDS
if data_element == "Inverter_1":
	device_id = 10001
elif data_element == "Inverter_1":
	device_id = 10001
elif data_element == "Inverter_3":
	device_id = 10003
elif data_element == "Inverter_4":
	device_id = 10004
elif data_element == "Inverter_5":
	device_id = 10005
```
> Inverter_1 is inverter name in Deye Cloud site

> 10001 is device ID in Grusher & so on

### Testing
```
python3 /opt/inverters/deye.py
```

### Set up CRON
Add this
```
 */5 * * * *   /usr/bin/python3 /opt/inverters/deye.py  >> /tmp/deye-`/bin/date +\%Y\%m\%d`.log  2>&1
```

**You should run the script no more than once every 5 minutes. Otherwise, you will be banned.**



## Growatt
##### To run need PHP

## Setup

Move _growatt.php_ to `/opt/inverters/` (or in other folder)

##### Create device in Grusher without IP:
* Hostname: ANY, BUT NEED CONTENT GROWATT SERIAL (like "Inverter in Server 1259979")
* Vendor: other
* Model: Power monitor


##### Create note in Grusher with html list in format like this ( | is delimiter):
* SERIAL | NAME_FOR_ZABBIX | Description 

Example
* UYN0000001 | Inverter_growatt_1 | Description 1
* UYN0000002 | Inverter_growatt_2 | Description 2
* UYN0000003 | Inverter_growatt_3 | Description 3

##### Get note ID and write it in _growatt.php_

>If you are not using Zabbix you still have to specify the variable **NAME_FOR_ZABBIX**. In _growatt.php_ you can disabble Zabbix support

##### Then write token in config, previously getted in your Growatt cabinet https://oss-cn.growatt.com/ 

Fill other fields
```
define("GRUSHER_URL", "http://SET_GRUSHER_IP");
define("GRUSHER_NOTE_ID", 1); // SET GRUSHER NOTE ID

// zabbix data
define("ZABBIX_ALLOW", 1); // 1 or 0
define("ZABBIX_URL", "SET_GRUSHER_IP"); // zabbix server url like 125.125.125.125
define("ZABBIX_ITEM_KEY", "zab_key_gr"); // key item in Zabbix

// GROWATT
define("GROWATT_TOKEN", "token");
```
### Testing
```
php /opt/inverters/growatt.php
```
### Set up CRON
Add this to cron file
```
*/5 * * * * /usr/bin/php     /opt/inverters/growatt.php  >> /tmp/growatt-`/bin/date +\%Y\%m\%d`.log  2>&1
```


**You should run the script no more than once every 5 minutes. Otherwise, you will be banned.**


