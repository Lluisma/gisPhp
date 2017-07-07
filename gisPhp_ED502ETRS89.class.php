<?php 
  # _______________________________________________________________________________________________
  # agis_ED502ETRS89.class.php                                   :: Web Àrea Tècnica - Funcions GIS
  # -----------------------------------------------------------------------------------------------
  #  Autor:       lluís martí i garro                                           Aigües de Mataró SA
  #  Data:        07-03-2012
  #  Descripció:  Funció pel canvi de DATUM (ED50 > ETRS89)
  # _______________________________________________________________________________________________
  #
  #  Referències:
  #   * http://www.ikeralbeniz.net/2010/12/15/conversion-de-ed50-a-wgs84-o-etrs89/
  #     > http://foro.gabrielortiz.com/descargas/sped2et_sdk.zip
  #   * Rejilla para cambio de Datum entre ED50 y ETRS89 (en formato NTV2)
  #     > http://www.ign.es/ign/layoutIn/herramientas.do#DATUM
  #     > http://www.ign.es/ign/resources/herramientas/PENR2009.zip
  # _______________________________________________________________________________________________


  class agis_ed502etrs89 {

  	var $arrHEAD1;							      // * Capçalera de l'ARXIU
  	var $arrHEAD2;							      // * Capçalera de la GRAELLA
  	var $arrGRAELLA;					          // * Estructura dels REGISTRES de la GRAELLA
  	
  	var $nomARXIU;                                // * Nom de l'ARXIU 
  	
  	function __construct() {
  		
  	  // * fseek NO FUNCIONA AMB ARXIUS REMOTS (http://...!!!!)
  	  $path_class = dirname(__FILE__);
      //  $reflector  = new ReflectionClass(get_class($this));
      //  $path_class = dirname($reflector->getFileName());
      $this->nomArxiu = $path_class . "/PENR2009.gsb";
      
      $this->arrHEAD1 = array("NUM_OREC" => array("int", 4, "Número de registres que conté la capçalera"),
                              "NUM_SREC" => array("int", 4, "Número de registres que conté la capçalera de les graelles"),
                              "NUM_FILE" => array("int", 4, "Número de graelles"),
                              "GS_TYPE"  => array("str", 8, "Unitats del valor de cada node al fitxer (generalment segons"),
                              "VERSION"  => array("str", 8, "Versió del fitxer"),
                              "SYSTEM_F" => array("str", 8, "Nom de l'el·lipsoide de partida"),
                              "SYSTEM_T" => array("str", 8, "Nom de l'el·lipsoide d'arribada"),
                              "MAJOR_F"  => array("dob", 8, "Semieix major de l'el·lipsoide de partida"),
                              "MINOR_F"  => array("dob", 8, "Semieix menor de partida"),
                              "MAJOR_T"  => array("dob", 8, "Semieix major de l'el·lipsoide d'arribada"),
                              "MINOR_T"  => array("dob", 8, "Semieix menor d'arribada") );

      $this->arrHEAD2 = array("SUB_NAME" => array("str", 8, "Nom de la graella"),
                              "PARENT"   => array("str", 8, "Nom de la graella que la conté, si existís"),
                              "CREATED"  => array("str", 8, "Data de creació"),
                              "UPDATED"  => array("str", 8, "Data d'actualització"),
                              "S_LAT"    => array("dob", 8, "Latitud inferior"),
                              "N_LAT"    => array("dob", 8, "Latitud superior"),
                              "E_LONG"   => array("dob", 8, "Longitud més oriental (criteri positiu oest)"),
                              "W_LONG"   => array("dob", 8, "Longitud més occidental (criteri positiu oest)"),
                              "LAT_INC"  => array("dob", 8, "Increment de la malla en latitud"),
                              "LONG_INC" => array("dob", 8, "Increment de la malla en longitud"),
                              "GS_COUNT" => array("int", 8, "Número de nodes a la malla") );
      $this->arrGRAELLA = array (      1 => array("flo", 4, "Increment a la latitud per a pasar d'ED50 a ETRS89"),
                                       2 => array("flo", 4, "Increment a la longitud"),
                                       3 => array("flo", 4, "Precissió de l'increment de la latitud (-1 si no està disponible"),
                                       4 => array("flo", 4, "Precissió de l'increment de la longitud (-1 si no està disponible") );
  	}
  	               
    // ----------------------------------------------------------------------------------------------
    // llegir_HEAD: llegeix els registres corresponents a la capçalera (de l'arxiu o de la graella)
    // ----------------------------------------------------------------------------------------------
  
    function llegir_HEAD( $arrHEAD ) {
  	  $retorn = array();
      for ($n=0; $n<count($arrHEAD); $n++) {
        $regID   = trim( fread($this->objArxiu, 8) );
        $regTYPE = $arrHEAD[$regID][0];
        $regSIZE = $arrHEAD[$regID][1];
        $valor  = fread($this->objArxiu, $regSIZE);
        if ($regTYPE=="int") {
      	  $arr = unpack("ivalor", $valor);
      	  $valor = $arr["valor"];
      	  if ($regID!="GS_COUNT") { $padding = fread($this->objArxiu, 4);			// GS_COUNT no té padding ¿¿??
      	  }
        } elseif ($regTYPE=="dob") {
          $arr = unpack("dvalor", $valor);
          $valor = $arr["valor"];
        }
        $retorn[ trim($regID) ] = $valor;
      }
      return $retorn;
    }

    // ----------------------------------------------------------------------------------------------
    // print_HEAD: imprimeix els registres corresponents a la capçalera (de l'arxiu o de la graella)
    // ----------------------------------------------------------------------------------------------
  
    function print_HEAD( $arrValors, $arrHEAD ) {
  	  echo "<table>
  	         <tr><th>Id.Registre</th><th>Tipus</th><th>Mida</th><th>valor</th><th>Descripció</th></tr>";
      foreach ($arrHEAD as $regID => $arrDESC) {
        echo "<tr>
                <th>$regID</th>
                <td>" . $arrDESC[0] . "</td>
                <td>" . $arrDESC[1] . "</td>
                <td><b>" . $arrValors[$regID] . "</b></td>
                <td>" . $arrDESC[2] . "</td></tr>"; 
      }
      echo "</table>";
    }

    // ----------------------------------------------------------------------------------------------
    // iniciar: 
    // ----------------------------------------------------------------------------------------------
    
    function iniciar() {
    	
      $this->objArxiu = fopen($this->nomArxiu, "rb");

      $this->arrCAPARX = $this->llegir_HEAD( $this->arrHEAD1 );		// * Llegeix la capçalera de l'arxiu
      //$this->print_HEAD($this->arrCAPARX,$this->arrHEAD1);

      $this->arrCAPGRA = $this->llegir_HEAD( $this->arrHEAD2 );	// * Llegeix la capçalera de cada graella (configurat per una única graella)
      //$this->print_HEAD($this->arrCAPGRA,$this->arrHEAD2);

    }

    // ----------------------------------------------------------------------------------------------
    // tancar: 
    // ----------------------------------------------------------------------------------------------
    
    function tancar() {
      fclose($this->objArxiu);
    }
    
    // ----------------------------------------------------------------------------------------------
    // WAT_GIS_ED502ETRS89: llegeix els registres corresponents a la capçalera de l'arxiu de la graella
    // ----------------------------------------------------------------------------------------------                      
                        
    function convertir ( $lat, $lon ) {

      $msel = 1000000;

      $lat = $lat * 3600;							  // * Pas a segons per entrar a la graella d'interpolació
      $lon = $lon * 3600;
      $lon = -$lon;                                 // * Positive West  
       
      // * DECIDEIX SI LA GRAELLA (ÚNICA) ÉS VÀLIDA -----------------------------------------------
    
      $M = 1 + round(($this->arrCAPGRA["N_LAT"] - $this->arrCAPGRA["S_LAT"]) / $this->arrCAPGRA["LAT_INC"]);		// Num Reixes LAT
      $n = 1 + round(($this->arrCAPGRA["W_LONG"] - $this->arrCAPGRA["E_LONG"]) / $this->arrCAPGRA["LONG_INC"]);	// Num Reixes LON
      $i = 1 + floor(($lat - $this->arrCAPGRA["S_LAT"]) / $this->arrCAPGRA["LAT_INC"]);
      $j = 1 + floor(($lon - $this->arrCAPGRA["E_LONG"]) / $this->arrCAPGRA["LONG_INC"]);
    
      if ( ($i > 0) && ($j > 0) && ($i < $M) && ($j < $n) ) {		// * Graella vàlida
        if ($this->arrCAPGRA["LAT_INC"] < $msel) {
          $msel = $this->arrCAPGRA["LAT_INC"];
          $sel = true;
        }
      }

      // * LECTURA DELS NODES DE LA GRAELLA (SI ÉS VÀLIDA) 
  
      if (isset($sel)) {  	
   
        $offset = ($n * ($i - 1) + $j) - 1;								// Determina la posició en la graella
        $offset = 22 + $offset;										    // Hi afefeix les posicions de la capçalera (11+11)
        fseek($this->objArxiu, $offset*16);								// Es desplaça fins al punt desitjat
        
        $punt    = array();
        $punt[1] = unpack("filat/filon/fplat/fplon", fread($this->objArxiu, 16) );
        $punt[2] = unpack("filat/filon/fplat/fplon", fread($this->objArxiu, 16) );

        fseek($this->objArxiu, ($offset+$n)*16);						// Es desplaça fins a la segona posició de la graella
        
        $punt[3] = unpack("filat/filon/fplat/fplon", fread($this->objArxiu, 16) );
        $punt[4] = unpack("filat/filon/fplat/fplon", fread($this->objArxiu, 16) );

        $lata = $this->arrCAPGRA["S_LAT"] + ($i - 1) * $this->arrCAPGRA["LAT_INC"];
        $lona = $this->arrCAPGRA["E_LONG"] + ($j - 1) * $this->arrCAPGRA["LONG_INC"];
        $y = ($lat - $lata) / $this->arrCAPGRA["LAT_INC"];
        $x = ($lon - $lona) / $this->arrCAPGRA["LONG_INC"];
    
        // * COEFICIENTS
        $a0 = $punt[1]["ilat"];
        $a1 = $punt[2]["ilat"] - $punt[1]["ilat"];
        $a2 = $punt[3]["ilat"] - $punt[1]["ilat"];
        $a3 = $punt[1]["ilat"] + $punt[4]["ilat"] - $punt[2]["ilat"] - $punt[3]["ilat"];
    
        $ip = $a0 + $a1 * $x + $a2 * $y + $a3 * $x * $y;
        $latf = $lat + $ip;
        $latf = $latf / 3600; 
    
        $b0 = $punt[1]["ilon"];
        $b1 = $punt[2]["ilon"] - $punt[1]["ilon"];
        $b2 = $punt[3]["ilon"] - $punt[1]["ilon"];
        $b3 = $punt[1]["ilon"] + $punt[4]["ilon"] - $punt[2]["ilon"] - $punt[3]["ilon"];
        $ib = $b0 + $b1 * $x + $b2 * $y + $b3 * $x * $y;
        $lonf = $lon + $ib;
        $lonf = -$lonf / 3600;					//'POSITIVE WEST;

        return array($latf, $lonf);
      }
      return array(0,0); // ERROR
    }
  
  }
?>