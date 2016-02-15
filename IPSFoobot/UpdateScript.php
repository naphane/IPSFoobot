<?
$FBInstanceID = IPS_GetParent($_IPS['SELF']);
$children = IPS_GetChildrenIDs($FBInstanceID);

foreach($children as $child) //Loop on Children of FB Service Instance
{
   $childInstance = IPS_GetInstance($child);
   // Check if it is a Dummy Module
   if ($childInstance['ModuleInfo']['ModuleID'] == "{485D0419-BE97-4548-AA9C-C083EB82E61E}")
   {
      $ID = $childInstance['InstanceID'];
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
?>