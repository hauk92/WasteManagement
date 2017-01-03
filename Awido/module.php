<?

class Awido extends IPSModule
{
  /**
   * (bekannte) Client IDs - Array
   *
   * @access private
   * @var array Key ist die clientID, Value ist der Name
   */
  static $Clients = array(
    "awld"              => "Lahn-Dill-Kreis",
    "awb-ak"            => "Landkreis Altenkirchen",
    "awb-duerkheim"     => "Landkreis Bad Dürkheim",
    "wgv"               => "Landkreis Bad Tölz-Wolfratshausen",
    "awv-nordschwaben"  => "Landkreis Dillingen a.d. Donau und Donau-Ries",
    "Erding"            => "Landkreis Erding",
    "kaw-guenzburg"     => "Landkreis Günzburg",
    "azv-hef-rof"       => "Landkreis Hersfeld-Rotenburg",
    "kelheim"           => "Landkreis Kelheim",
    "landkreisbetriebe" => "Landkreis Neuburg-Schrobenhausen",
    "eww-suew"          => "Landkreis Südliche Weinstraße",
    "lra-dah"           => "Landratsamt Dachau",
    "neustadt"          => "Neustadt a.d. Waldnaab",
    "rmk"               => "Rems-Murr-Kreis",
    "memmingen"         => "Stadt Memmingen"
    //"???"             => "Landratsamt Aichach-Friedberg"
  );

  /**
   * Create.
   *
   * @access public
   */
  public function Create()
  {
    //Never delete this line!
    parent::Create();

    $this->RegisterPropertyString("clientID", "null");
    // Places
    $this->RegisterPropertyString("placeGUID", "null");
    // Street
    $this->RegisterPropertyString("streetGUID", "null");
    // Addon
    $this->RegisterPropertyString("addonGUID", "null");
    // FractionIDs
    $this->RegisterPropertyString("fractionIDs", "null");
    // Fractions
    for ($i=1; $i<=10; $i++)
		{
			$this->RegisterPropertyBoolean("fractionID".$i, false);
		}

    // Update daily timer
    $this->RegisterTimer("UpdateTimer",0,"AWIDO_Update(\$_IPS['TARGET']);");
  }

  /**
   * Configuration Form.
   *
   * @access public
   * @return JSON configuration string.
   */
  public function GetConfigurationForm()
  {
    $clientId = $this->ReadPropertyString("clientID");
    $placeId  = $this->ReadPropertyString("placeGUID");
    $streetId = $this->ReadPropertyString("streetGUID");
    $addonId  = $this->ReadPropertyString("addonGUID");
    $fractIds = $this->ReadPropertyString("fractionIDs");
    $this->SendDebug("GetConfigurationForm", "clientID=".$clientId.", placeId=".$placeId.", streetId=".$streetId.", addonId=".$addonId.", fractIds=".$fractIds, 0);

    $formclient = $this->FormClient($clientId);
    $formplaces = $this->FormPlaces($clientId, $placeId);
    $formstreet = $this->FormStreet($clientId, $placeId, $streetId);
    $formaddons = $this->FormAddons($clientId, $placeId, $streetId, $addonId);
    $formfracts = $this->FormFractions($clientId, $addonId);
    $formstatus = $this->FormStatus();

    return '{ "elements": [' . $formclient . $formplaces . $formstreet . $formaddons . $formfracts . '], "status": [' . $formstatus . ']}';
  }

  public function ApplyChanges()
  {
    //Never delete this line!
    parent::ApplyChanges();

    $clientId = $this->ReadPropertyString("clientID");
    $placeId  = $this->ReadPropertyString("placeGUID");
    $streetId = $this->ReadPropertyString("streetGUID");
    $addonId  = $this->ReadPropertyString("addonGUID");
    $fractIds = $this->ReadPropertyString("fractionIDs");
    $this->SendDebug("ApplyChanges", "clientID=".$clientId.", placeId=".$placeId.", streetId=".$streetId.", addonId=".$addonId.", fractIds=".$fractIds, 0);

    $status = 102;
    if($clientId == "null") {
      $status = 201;
      IPS_SetProperty($this->InstanceID, "placeGUID", "null");
      IPS_SetProperty($this->InstanceID, "streetGUID", "null");
      IPS_SetProperty($this->InstanceID, "addonGUID", "null");
      IPS_SetProperty($this->InstanceID, "fractionIDs", "null");
      for ($i=1; $i<=10; $i++)
  		{
  			IPS_SetProperty($this->InstanceID, "fractionID".$i, false);
  		}
    }
    else if($placeId == "null") {
      $status = 202;
      IPS_SetProperty($this->InstanceID, "streetGUID", "null");
      IPS_SetProperty($this->InstanceID, "addonGUID", "null");
      IPS_SetProperty($this->InstanceID, "fractionIDs", "null");
    }
    else if($streetId == "null") {
      $status = 203;
      IPS_SetProperty($this->InstanceID, "addonGUID", "null");
      IPS_SetProperty($this->InstanceID, "fractionIDs", "null");
    }
    else if($addonId == "null") {
      $status = 204;
      IPS_SetProperty($this->InstanceID, "fractionIDs", "null");
    }
    else if($fractIds == "null") {
      $status = 102;
    }

    $this->SetStatus($status);
    //$this->SetTimerInterval("UpdateTimer", 0);

  }

  /**
  * This function will be available automatically after the module is imported with the module control.
  * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
  *
  * AWIDO_Update($id);
  *
  */
  public function Update()
  {
  }

  /**
   * Erstellt ein DropDown-Menü mit den auswählbaren Client IDs (Abfallwirtschaften).
   *
   * @access protected
   * @param  string $cId Client ID .
   * @return string Client ID Elemente.
   */
  protected function FormClient($cId)
  {
    $form = '{ "type": "Select", "name": "clientID", "caption": "Refuse management:", "options": [';
    $line = array();

    // Reset key
    $line[] = '{"label": "Please select ...","value": "null"}';

    foreach (static::$Clients as $Client => $Name)
    {
      if ($cId == "null") {
        $line[] = '{"label": "' . $Name . '","value": "' . $Client . '"}';
      }
      else if ($Client == $cId) {
          $line[] = '{"label": "' . $Name . '","value": "' . $Client . '"}';
      }
    }
    return $form . implode(',', $line) . ']}';
  }

  /**
   * Erstellt ein DropDown-Menü mit den auswählbaren Orte im Entsorkungsgebiet.
   *
   * @access protected
   * @param  string $cId Client ID .
   * @param  string $pId Place GUID  .
   * @return string Places Elemente.
   */
  protected function FormPlaces($cId, $pId)
  {
    $url = "http://awido.cubefour.de/WebServices/Awido.Service.svc/getPlaces/client=".$cId;

    if($cId == "null") {
      return '';
    }

    $json = file_get_contents($url);
    $data = json_decode($json);

    $form = ',{ "type": "Select", "name": "placeGUID", "caption": "Location:", "options": [';
    $line = array();
    // Reset key
    $line[] = '{"label": "Please select ...","value": "null"}';

    foreach($data as $place) {
      if($pId == "null") {
        $line[] = '{"label": "' . $place->value . '","value": "' . $place->key . '"}';
      }
      else if ($pId == $place->key) {
        $line[] = '{"label": "' . $place->value . '","value": "' . $place->key . '"}';
      }
    }
    return $form . implode(',', $line) . ']}';
  }

  /**
   * Erstellt ein DropDown-Menü mit den auswählbaren OT/Strassen im Entsorkungsgebiet.
   *
   * @access protected
   * @param  string $cId Client ID.
   * @param  string $pId Place GUID.
   * @param  string $sId Street GUID.
   * @return string Ortsteil/Strasse Elemente.
   */
  protected function FormStreet($cId, $pId, $sId)
  {
    $url = "http://awido.cubefour.de/WebServices/Awido.Service.svc/getGroupedStreets/".$pId."?selectedOTId=null&client=".$cId;

    if($cId == "null" || $pId == "null") {
      return '';
    }

    $json = file_get_contents($url);
    $data = json_decode($json);

    $form = ',{ "type": "Select", "name": "streetGUID", "caption": "District/Street:", "options": [';
    $line = array();
    // Reset key
    $line[] = '{"label": "Please select ...","value": "null"}';

    foreach($data as $street) {
      if($sId == "null") {
        $line[] = '{"label": "' . $street->value . '","value": "' . $street->key . '"}';
      }
      else if ($sId == $street->key) {
        $line[] = '{"label": "' . $street->value . '","value": "' . $street->key . '"}';
      }
    }
    return $form . implode(',', $line) . ']}';
  }

  /**
   * Erstellt ein DropDown-Menü mit den auswählbaren Hausnummern im Entsorkungsgebiet.
   *
   * @access protected
   * @param  string $cId Client ID .
   * @param  string $pId Place GUID.
   * @param  string $sId Street GUID .
   * @param  string $aId Addon GUID .
   * @return string Client ID Elements.
   */
  protected function FormAddons($cId, $pId, $sId, $aId)
  {
    $url = "http://awido.cubefour.de/WebServices/Awido.Service.svc/getStreetAddons/".$sId."?client=".$cId;

    if($cId == "null" || $pId == "null" || $sId == "null") {
      return '';
    }

    $json = file_get_contents($url);
    $data = json_decode($json);

    $form = ',{ "type": "Select", "name": "addonGUID", "caption": "Street number:", "options": [';
    $line = array();
    // Reset key
    $line[] = '{"label": "Please select ...","value": "null"}';

    foreach($data as $addon) {
      if($addon->value == "") {
        $addon->value = "All";
      }
      if($aId == "null") {
        $line[] = '{"label": "' . $addon->value . '","value": "' . $addon->key . '"}';
      }
      else if ($aId == $addon->key) {
        $line[] = '{"label": "' . $addon->value . '","value": "' . $addon->key . '"}';
      }
    }
    return $form . implode(',', $line) . ']}';
  }

  /**
   * Erstellt für die angebotenen Entsorgungen Auswahlboxen.
   *
   * @access protected
   * @param  string $cId Client ID .
   * @param  string $aId Addon GUID .
   * @return string Client ID Elements.
   */
  protected function FormFractions($cId, $aId)
  {
    $url = "http://awido.cubefour.de/WebServices/Awido.Service.svc/getFractions/client=".$cId;

    if($cId == "null" || $aId == "null") {
      return '';
    }

    $json = file_get_contents($url);
    $data = json_decode($json);

    $form = ',{ "type": "Label", "label": "The following disposals are offered:" } ,';
    $line = array();
    $ids  = array();

    foreach($data as $fract) {
        $ids[]  = $fract->id;
  			IPS_SetProperty($this->InstanceID, "fractionID".$fract->id, $fract->vb);
        $line[] = '{ "type": "CheckBox", "name": "fractionID' . $fract->id .'", "caption": "' . $fract->nm . ' (' . $fract->snm .')" }';
    }
    IPS_SetProperty($this->InstanceID, "fractionIDs", implode(',', $ids));
    return $form . implode(',', $line);
  }

  /**
   * Prüft den Parent auf vorhandensein und Status.
   *
   * @access protected
   * @return string Status Elemente.
   */
  protected function FormStatus()
  {
    $form =  '{"code": 101, "icon": "inactive", "caption": "Creating instance."},
              {"code": 102, "icon": "active",   "caption": "AWIDO active."},
              {"code": 104, "icon": "inactive", "caption": "AWIDO inactive."},
              {"code": 201, "icon": "inactive", "caption": "Select a valid refuse management!"},
              {"code": 202, "icon": "inactive", "caption": "Select a valid place!"},
              {"code": 203, "icon": "inactive", "caption": "Select a valid location/street!"},
              {"code": 204, "icon": "inactive", "caption": "Select a valid street number!"},
              {"code": 205, "icon": "inactive", "caption": "Select a offered disapsal!"}';
    return $form;
  }

}

?>