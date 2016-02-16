# IPSFoobot
IP-Symcon Module for the Foobot Air Sensor from Airboxlab.

**Content**

1. [Functionalities]((#1-funktionsumfang))
2. [Requirements](#2-anforderungen)
3. [Installation and configuration](#3-installation--konfiguration)
4. [Variables](#4-variablen)
5. [Methods](#6-funktionen)
6. [Update Script](#5)

## 1. Functionalities

This Module uses the Open API from Foobot to retrieve information and data from the Air Sensors asssociated to a specific User account. When the instance is created, it automatically sets up a Dummy Module for each Air Sensor associated to the account. Variables for the sensor measurements are added below each Module. The variables are updated by a Script which is copied below the Foobot Module Instance. The script is triggered regularly by a timer with the update interval provided in the settings of the Instance.

The Module supports multiple Foobot Sensors. This has however not been tested. Feedback is welcome on the [IP-Symcon forum thread](http://www.ip-symcon.de/forum/) dedicated to this module.

## 2. Requirements

 - IPS 4.x
 - [Foobot Air Sensor(s)](https://foobot.io/)
 - Foobot [API registration](api.foobot.io/apidoc/)

## 3. Installation and configuration

### Installation in IPS 4.x

![Create Instance](docs/Foobot_Module_Installation.png?raw=true "Create Instance")

![Configure Instance](docs/Foobot_Module_Installation2.png?raw=true "Configure Instance")

| Parameter       | Type   | Default value  |  Description         |
| :-------------: | :----: | :------------: | :------------------: |
| Username        | string |                | Username from Foobot |
| Password        | string |                | Password             |
| Update interval | integer| 600            | Interval in seconds  |

Once the changes have been applied, the "Check Devices" button of the Test Center will be enabled. This button must be clicked when new Air Sensors are added. The new Sensor will be detected and instances and variables will be created automatically.

## 4. Variables

The screenshot below shows the Variables created in IP-Symcon for each sensor along with their types.

![Variables created by the Instance](docs/Foobot_Module_Variables.png?raw=true "Variables created by the Instance")

For each variable, a corresponding profile with the data type and coloring schemes according to limits recommended by WHO is created and associated to the variable.

| Variable                    | Type      | Unit           |  Limits              |
| :-------------------------: | :-------: | :------------: | :------------------: |
| Carbon dioxyde              | integer   | ppm            |  1000, 2000          |
| Volatile compounds          | integer   | ppb            |  500                 |
| Particulate Matters (PM2.5) | float     | ug/m3          |  25.0                |
| Golbal Pollution Index      | float     | %              | -                    |

An example of visualisation of the Foobot Variables in the IP-Symcon Webfront.

![Webfront](docs/Foobot_Module_Webfront.png?raw=true "Webfront")

## 5. Methods

   ```php 
    array FOO_GetDevices(integer $InstanceID);
   ```
   
   ```php 
    array FOO_UpdateDevices(integer $InstanceID);
   ```
   
   ```php 
    array FOO_GetData(integer $InstanceID, string $uuid, $from, $to, integer $sampling = NULL);
   ```
   
   ```php 
    array GetDataLast(integer $InstanceID, string $uuid, $from, $to, integer $sampling = NULL);
   ```
   
