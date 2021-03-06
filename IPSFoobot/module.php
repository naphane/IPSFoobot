<?

class IPSFoobot extends IPSModule
{
	private $Timeout		= 30;
	private $Host			= "api.foobot.io/v2/";
	private $Token			= "";
	private $APIRemaining 	= "";
	private $debug 			= true;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        
		$this->RegisterPropertyString("Username", "");
        $this->RegisterPropertyString("Password", "");
		$this->RegisterPropertyString("APIKey", ""); 		
        $this->RegisterPropertyInteger("Update", 600);
		
		IPS_SetInfo($this->InstanceID, 'Request an API key at http://api.foobot.io/apidoc/index.html');
		
		// Create Update Script
		$ID = $this->RegisterScript("FoobotUpdate", "Foobot Update", $this->CreateUpdateScript(), -8);
		IPS_SetScriptTimer($ID, $this->ReadPropertyString('Update'));
		IPS_SetHidden($ID, true);
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        if ($this->ReadPropertyString('Username') == '' or $this->ReadPropertyString('Password'))
        {
			// Username and Password can't be empty
			$this->SetStatus(202);
        }
		elseif ($this->ReadPropertyString('APIKey') == '')
        {
			// API Key can't be empty
			$this->SetStatus(204);
        }
		else
		{
			if ($this->Authenticate()) {
				$this->SetStatus(102);
			} else {
				$this->SetStatus(201);
			}
		}
		
		$this->RegisterVariableString("APIAT", "API Auth Token", "~String", 1);
		IPS_SetParent($this->GetIDForIdent('APIAT'), $this->InstanceID);
		IPS_SetHidden($this->GetIDForIdent('APIAT'), true);
		
		$this->RegisterVariableInteger("APILR", "API Limit Remaining", "", 2);
		IPS_SetParent($this->GetIDForIdent('APILR'), $this->InstanceID);
		
		$this->CreateDevices();
		
    }

################## PUBLIC
    /**
     * These functions will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
     */
	
	/**
     * Gets the Devices associated with the User account along with name and uuid.
     * 
     * @return Array of Devices
     */
	public function GetDevices() 
	{
		//$tokenHeader = "X-AUTH-TOKEN: ".$this->Token;
		$tokenHeader = "X-API-KEY-TOKEN: ".$this->ReadPropertyString('APIKey');
		$result = $this->requestFoobotAPI($this->Host."owner/".$this->ReadPropertyString('Username')."/device/", "$tokenHeader");
		//echo "\r\nRESULT RAW:\r\n";
		//print_r($result);

		$match = preg_match('/\[(.+)\]/', $result, $json);
		//$json = str_replace(array("[","]"), array("",""), $json_result);
		//echo "\r\nJSON:\r\n";
		//print_r($json);

		if ($match !== false and $match != 0) {
			$result = json_decode($json[1]);
			//echo "\r\nJSON DECODED:\r\n";
			//print_r($result);
			//$name = $result->name;
			return $result;
		} else {
			return false;
		}
	}
	
	/**
     * Updates Device Instances and Variables.
     * 
     * @return true if success
     */
	public function UpdateDevices()  
	{
		$this->CreateDevices();	// true ($isUpdate)
	}
	
	/**
     * Gets Data points for a specific period.
     *
     * @param string $from 	Time stamp for start of sampling period, e.g. 2014-10-25T00:00:00
	 * @param string $to	Time stamp for end period
	 * @param integer $sampling	Sampling in seconds	 
     * @return Array of Data points
     * 
     * @exception 
     */
	public function GetData(string $uuid, $from, $to, integer $sampling = NULL) 
	{
		if ($sampling == NULL) $sampling = 0;
		//$tokenHeader = "X-AUTH-TOKEN: ".$this->Token;
		$tokenHeader = "X-API-KEY-TOKEN: ".$this->ReadPropertyString('APIKey');
		//echo "\r\n\r\nGET DATA FROM LAST HOUR:\r\n";
		//$from	= "2014-10-25T00:00:00";
		//$to 	= "2014-10-30T00:00:00";
		//$period = 3600;  // Period in Seconds
		$result = $this->requestFoobotAPI($this->Host."device/".$uuid."/datapoint/$from/$to/$sampling/", "$tokenHeader");		
		
		$match = preg_match('/{(.+)}/', $result, $json);
		
		if ($match !== false and $match != 0) {
			$result = json_decode($json[0]);
			return $result;
		} else {
			return false;
		}
	}
	
	/**
     * Gets Data points for last period.
     *
	 * qparam string  $uuid UUID of the Device	  
     * @param integer $period Period in seconds before last point to be sampled
	 * @param integer $sampling	Sampling in seconds	 
     * @return Array of Data points
     * 
     * @exception 
     */
	public function GetDataLast(string $uuid, integer $period, integer $sampling = NULL) 
	{
		if ($sampling == NULL) $sampling = 0;
		//$tokenHeader = "X-AUTH-TOKEN: ".$this->Token;
		$tokenHeader = "X-API-KEY-TOKEN: ".$this->ReadPropertyString('APIKey');

		$path = $this->Host."device/".$uuid."/datapoint/$period/last/$sampling/";
		$result = $this->requestFoobotAPI($path, "$tokenHeader");	
		
		//echo "GetDataLast: path: $path\r\n"; 
		
		$match = preg_match('/{(.+)}/', $result, $json);
		
		if ($match !== false and $match != 0) {
			$result = json_decode($json[0]);
			return $result;
		} else {
			return false;
		}
	}
	

################# PRIVATE
	

	private function CreateDevices()	// $isUpdate = false
	{
	// Create Variables Profiles
		// From Foobot: ["time","pm","tmp","hum","co2","voc","allpollu"],"units":["s","ugm3","C","pc","ppm","ppb","%"] [1445275154,45.449997,25.754375,39.512215,1033.0,286.0,62.60714]
		
		$this->RegisterProfileIntegerEx("Pollutant.Co2", "Gauge", "", " ppm", Array(
            Array(0, 	"%d", "", 0x00FF00),
            Array(1000, "%d", "", 0xFFFF00),
            Array(2000, "%d", "", 0xFF0000)
        ));
		$this->RegisterProfileFloatEx("Pollutant.PM", "Gauge", "", " uG/m3", Array(
            Array(0, 	"%.1f", "", 0x00FF00),
            Array(25, 	"%.1f", "", 0xFF0000)
        ));
		$this->RegisterProfileIntegerEx("Pollutant.VC", "Gauge", "", " ppb", Array(
            Array(0, 	"%d", "", 0x00FF00),
            Array(500, 	"%d", "", 0xFF0000)
        ));
		$this->RegisterProfileFloatEx("Pollutant.GPI", "Gauge", "", " %", Array(
            Array(0, 	"%.1f", "", 0x00FF00),
            Array(50,   "%.1f", "", 0xFFFF00),
            Array(100,  "%.1f", "", 0xFF0000)
        ));
		
		// Get Foobot devices from API and loop on them to create Instances and Variables
		$devices = $this->GetDevices();
						
		if ($devices !== false) {
			//foreach ($devices as $device) {	// Prepared for multiple Foobot devices support - to be tested
				// Create a dummy Instance for each Foobot Sensor if it does not exist already
				if (!$this->deviceInstanceExists($devices->name))	// , $isUpdate (bug?)
				{
					$FBdeviceModuleID	= IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
					IPS_SetName($FBdeviceModuleID, $devices->name);
					IPS_SetParent($FBdeviceModuleID, $this->InstanceID);
				
					$this->RegisterVariableString("Uuid", "Device UUID", "~String", 1);		
					SetValue($this->GetIDForIdent('Uuid'), $devices->uuid);
					IPS_SetHidden($this->GetIDForIdent('Uuid'), true);
					IPS_SetParent($this->GetIDForIdent('Uuid'), $FBdeviceModuleID);
				
					$this->RegisterVariableString("Mac", "Device Mac", "~String", 2);	
					SetValue($this->GetIDForIdent('Mac'),  $devices->mac);
					IPS_SetHidden($this->GetIDForIdent('Mac'), true);
					IPS_SetParent($this->GetIDForIdent('Mac'), $FBdeviceModuleID);
				
					// Create Variables
					$this->RegisterVariableInteger("Co2", "Carbon Dioxide", "Pollutant.Co2", 10);
					IPS_SetParent($this->GetIDForIdent('Co2'), $FBdeviceModuleID);
					$this->RegisterVariableInteger("Voc", "Volatile compounds", "Pollutant.VC", 11);
					IPS_SetParent($this->GetIDForIdent('Voc'), $FBdeviceModuleID);
					$this->RegisterVariableFloat("Pm", "PM2.5", "Pollutant.PM", 12);
					IPS_SetParent($this->GetIDForIdent('Pm'), $FBdeviceModuleID);
					$this->RegisterVariableFloat("Allpollu", "Global Pollution Index", "Pollutant.GPI", 13);
					IPS_SetParent($this->GetIDForIdent('Allpollu'), $FBdeviceModuleID);
				
					$this->RegisterVariableFloat("Tmp", "Temperature", "~Temperature", 14);
					IPS_SetParent($this->GetIDForIdent('Tmp'), $FBdeviceModuleID);
					$this->RegisterVariableFloat("Hum", "Humidity", "~Humidity.F", 15);
					IPS_SetParent($this->GetIDForIdent('Hum'), $FBdeviceModuleID);
				}
			//}	// End of loop on devices
			
			return true;
		} else {
			$this->SetStatus(203);
			if ($this->debug) IPS_LogMessage("FOOBOT Module", "ERROR: No Foobot Devices have been found!");
			return false;
		}
	}

	private function deviceInstanceExists($name)	// , $isUpdate (bug?)
	{
		//if ($isUpdate)
		//{
			$children = IPS_GetChildrenIDs($this->InstanceID);
			foreach($children as $child) //Loop on devices
			{
				if (IPS_InstanceExists($child)) {
					$childInstance = @IPS_GetInstance($child);
					$childInstanceID = $childInstance['InstanceID'];
					$childInstanceName = @IPS_GetName($childInstanceID);
					// Check if it is a Dummy Module and if it has a known device name
					if ($childInstanceName == $name and $childInstance['ModuleInfo']['ModuleID'] == "{485D0419-BE97-4548-AA9C-C083EB82E61E}")
					{
						return true;	
					} 
				}
			}
			return false;
		//} else {
		//	return false;
		//}
	}

	private function requestFoobotAPI($url, $header = "") {
		//global $debug, $timeout, $username, $password;
		$username = $this->ReadPropertyString('Username');
		$password = $this->ReadPropertyString('Password');

		$ch = curl_init($url);
		if ($header != "") {
			$options = array(
				CURLOPT_HTTPHEADER => array($header),
				CURLOPT_HEADER => 1,
				CURLOPT_USERPWD => "$username".":"."$password", // sends base64 String
				CURLOPT_TIMEOUT => $this->Timeout,
				CURLOPT_VERBOSE => 1,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_CAINFO => __DIR__ ."/CACerts/FoobotCertificate.cer",
				CURLOPT_RETURNTRANSFER => true
			);
		} else {
		$options = array(
				CURLOPT_HEADER => 1,
				CURLOPT_USERPWD => "$username".":"."$password", // sends base64 String
				CURLOPT_TIMEOUT => $this->Timeout,
				CURLOPT_VERBOSE => 1,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_CAINFO => __DIR__ ."/CACerts/FoobotCertificate.cer",
				CURLOPT_RETURNTRANSFER => true
			);
		}
		// Setting curl options
		curl_setopt_array($ch, $options);
		// Getting results
		$result =  curl_exec($ch);
		if ($result === FALSE) {
			die(curl_error($ch));
		}
		
		$lr = preg_match('/X-API-KEY-LIMIT-REMAINING:\s([0-9]+)/', $result, $limitRemaining);
		if ($lr === false or $lr == 0) 
		{
			if ($this->debug) IPS_LogMessage("FOOBOT Module", "WARNING: No API KEY LIMIT REMAINING returned");
		}
		else
		{
			$this->APIRemaining = $limitRemaining[1];
			SetValue($this->GetIDForIdent('APILR'), $this->APIRemaining);
		}

		if(!curl_errno($ch)){
			$info = curl_getinfo($ch);
			$isOk = in_array($info['http_code'], array(200, 301, 302)) ? "OK" : "NOK";
			return $result;
		} else {
			return false;
		}

		curl_close($ch);
	}

	
	private	function Authenticate() 
	{
		$tokenHeader = "X-API-KEY-TOKEN: ".$this->ReadPropertyString('APIKey');
		$result = $this->requestFoobotAPI($this->Host."user/".$this->ReadPropertyString('Username')."/login/", "$tokenHeader");
		$auth = preg_match('/X-AUTH-TOKEN:\s([a-zA-Z0-9._]+)/', $result, $token);
		if ($auth === false or $auth == 0) 
		{
			if ($this->debug) IPS_LogMessage("MODULE FOOBOT", "No Authentication Token returned");
			return false;
		}
		else
		{
			$this->Token = $token[1];
			SetValue($this->GetIDForIdent('APIAT'), $token[1]);
			return true;
		}
	}
	
	private function CreateUpdateScript()
    {
        $Script = '<?
		$FBInstanceID = IPS_GetParent($_IPS["SELF"]);
$children = IPS_GetChildrenIDs($FBInstanceID);

foreach($children as $child) //Loop on Children of Foobot Instance
{
   $childInstance = IPS_GetInstance($child);
   // Check if it is a Dummy Module
   if ($childInstance["ModuleInfo"]["ModuleID"] == "{485D0419-BE97-4548-AA9C-C083EB82E61E}")
   {
      $ID = $childInstance["InstanceID"];
		$IDuuid = IPS_GetObjectIDByIdent("Uuid", $ID);

		$result = FOO_GetDataLast($FBInstanceID, GetValue($IDuuid), 300, 300);
	
		$IDpm = IPS_GetObjectIDByIdent("Pm",$ID);
		SetValue($IDpm, $result->datapoints[0][1]);
		$IDco2 = IPS_GetObjectIDByIdent("Co2",$ID);
		SetValue($IDco2, $result->datapoints[0][4]);
		$IDvoc = IPS_GetObjectIDByIdent("Voc",$ID);
		SetValue($IDvoc, $result->datapoints[0][5]);
		$IDallpollu = IPS_GetObjectIDByIdent("Allpollu",$ID);
		SetValue($IDallpollu, $result->datapoints[0][6]);
		
		$IDtmp = IPS_GetObjectIDByIdent("Tmp",$ID);
		SetValue($IDtmp, $result->datapoints[0][2]);
		$IDhum = IPS_GetObjectIDByIdent("Hum",$ID);
		SetValue($IDhum, $result->datapoints[0][3]);
	}
}
?>';
        return $Script;
    }
	

################# PROTECTED (Thanks Nall-Chan)
	
    protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
    {

        if (!IPS_VariableProfileExists($Name))
        {
            IPS_CreateVariableProfile($Name, 1);
        }
        else
        {
            $profile = IPS_GetVariableProfile($Name);
            if ($profile['ProfileType'] != 1)
                throw new Exception("Variable profile type does not match for profile " . $Name);
        }

        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
    }
	
	
	protected function RegisterProfileIntegerEx($Name, $Icon, $Prefix, $Suffix, $Associations)
    {
        if (sizeof($Associations) === 0)
        {
            $MinValue = 0;
            $MaxValue = 0;
        }
        else
        {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations) - 1][0];
        }

        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);

        foreach ($Associations as $Association)
        {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
    }
	
	protected function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
    {

        if (!IPS_VariableProfileExists($Name))
        {
            IPS_CreateVariableProfile($Name, 2);
        }
        else
        {
            $profile = IPS_GetVariableProfile($Name);
            if ($profile['ProfileType'] != 2)
                throw new Exception("Variable profile type does not match for profile " . $Name);
        }

        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
		IPS_SetVariableProfileDigits($Name, $Digits);
    }
	
	
	protected function RegisterProfileFloatEx($Name, $Icon, $Prefix, $Suffix, $Associations)
    {
        if (sizeof($Associations) === 0)
        {
            $MinValue = 0;
            $MaxValue = 0;
        }
        else
        {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations) - 1][0];
        }

        $this->RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0, 1);	// Uses stepsize = 1 and digits = 1

        foreach ($Associations as $Association)
        {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
    }

################# END OF CLASS 
	
}

?>