<?php 
  # _______________________________________________________________________________________________
  # gisPhp_LL2UTM.php                                                                     :: gisPHP
  # -----------------------------------------------------------------------------------------------
  #  Author:       lluís martí i garro                                         
  #  Date:         29-02-2012
  #  Description:  Reprojection UTM to LatLong class
  # _______________________________________________________________________________________________
  #
  #  Referencis:
  #   * Aprende a convertir coordenadas geográficas en UTM y UTM en geográficas (Parte I) 
  #     > http://www.gabrielortiz.com/index.asp?Info=058a
  #   * Elipsoides de referència
  #		> http://es.wikipedia.org/wiki/Forma_de_la_Tierra#Desarrollo_hist.C3.B3rico
  # _______________________________________________________________________________________________
  
// Relation between ETRS89 and WGS84 ---------------------------------------------------------------------
// > http://www.icc.cat/cat/Home-ICC/Geodesia/ETRS89/Aspectes-geodesics-de-l-ETRS89/Relacio-d-ETRS89-amb-WGS84
// D'acord amb l'IERS/ITRS, les realitzacions més actuals del sistema WGS84 (G730 G873 i G1150) i 
// les del sistema ITRS (ITRFyy) es poden considerar idèntiques al nivell dels 10 cm. Per tant, per a
// relacionar l'ETRS89 i el WGS84 considerarem l'equivalència entre l'ETRS89 i l'ITRS.
// El resultat difereix de l'original en uns 40 cm . Per tant, amb aquest resultat i l'equivalència 
// establerta per l'ITRS de 10 cm podríem concloure que el WGS84 i l'ETRS89 són equivalents en uns 
// 42 cm. Tenint en compte la precisió del WGS84 absolut d'uns 3 m podem emprar el resultat d'un 
// receptor GPS directament sobre els marcs de referència de l'ICC basats en ETRS89 de forma directa. 

  class gisPHP_LL2UTM2LL {

  	public $a;								    // * Major semi-aixs of thre reference ellipsoid
  	public $b;								    // * Minor semi-aixs of thre reference ellipsoid
  	//public $e;								  // * Excentricity (not needed in Coticchia-Surace's equations)
  	public $ei;									  // * Second excentricity
  	public $ei2;								  // * Square of second excentricity
    public $c;  								  // * Polar radius of curvature
    public $alfa;    							// * Plane (not needed in Coticchia-Surace's equations)
  										
  	function __construct( $sref ) {
      // * Sets the semi-axes of reference ellipsoid according the reference system ---------------
  	  switch ($sref) {
  	    case "WGS84":	  /* WGS-84 (1984, Global GPS) */
  	  	  				      $a = 6378137.0;		$b = 6356752.3142; break;
  	    case "ETRS89":	/* GRS-80 (1979, Global ITRS) */
  	  	                $a = 6378137.0;		$b = 6356752.3141; break;
  	    case "ED50":	  /* International (1924, Europa) / Hayford(1920, USA) */
  	                    $a = 6378388.0;		$b = 6356911.946130; break; 
  	  }
  	  // * Previous calculations, over ellipsoid's geometry ---------------------------------------
  	  //$e   = sqrt(pow($a,2) - pow($b,2))/$a;
  	  $ei  = sqrt(pow($a,2) - pow($b,2))/$b;
  	  $ei2 = pow($ei,2);    
  	  $c   = pow($a,2)/$b;
	  //$alfa = ($a-$b)/$a;
  	}

    function ll2utm ($latg, $long ) {
        
  	  // * Previous calculations, over longitude and latitude -------------------------------------
  	  
  	  $lat = deg2rad($latg);								// Latitude in radians
  	  $lon = deg2rad($long);								// Longitude in radians

  	  // * If longitud is referenced in the west of Greenwich Meridian, longitude is negative
  	  // $lon=$lon*-1;  
	    // $long=$long*-1;
 	
 	    // * Previous calculations, over the UTM Zone ----------------------------------------------
  	
      $fus = floor( ($long/6) + 31 );						 // UTM Zone Calculation
      $lambda0 = $fus * 6 - 183;							   // Calculation of Central Meridian UTM Zone
      $inclambda = $lon - deg2rad($lambda0);		 // Angular distance between longitude and central meridian UTM zone

      // * Equacions de Coticchia-Surace : Paràmetres ---------------------------------------------
    
      $A     = cos($lat)*sin($inclambda);
      $xi    = (1/2)*log( (1+$A) / (1-$A));
      $eta   = atan( tan($lat) / cos($inclambda) ) - $lat;
      $nu    = ($c / sqrt(1 + ($ei2 * pow(cos($lat),2)))) * 0.9996;  
      $zeta  = ($ei2/2) * pow($xi,2) * pow(cos($lat),2);
      $A1    = sin(2*$lat);
      $A2    = $A1 * pow(cos($lat),2);
      $J2    = $lat + ($A1/2);
      $J4    = (3*$J2 + $A2) / 4;
      $J6    = (5*$J4 + $A2*pow(cos($lat),2)) / 3;
      $alfa  = (3/4) * $ei2;
      $beta  = (5/3) * pow($alfa,2);
      $gamma = (35/27) * pow($alfa,3);  
      $Bo    = 0.9996 * $c * ($lat - ($alfa*$J2) + ($beta*$J4) - ($gamma*$J6));
    
      // * Final coordinates calculation-----------------------------------------------------------
    
      $x = $xi * $nu * (1 + ($zeta/3)) + 500000;
      $y = $eta * $nu * (1 + $zeta) + $Bo;
      return array($x,$y);
    }

    function utm2ll ($x, $y, $fus) {
  	
      // * CONSTANTS ------------------------------------------------------------------------------
      $k0 = 0.9996;										   // k0 = scale along long0
      $k0 = 0.99960000000000004;	    	 // Allow for systems that might use a different Mercator projection
  
      // * Previous treatment of X and Y  ---------------------------------------------------------
      $x = $x - 500000.0;								 // (new axis)
      //$y = $y - 10000000.0;						 // Eliminació del 'retranqueig' només en l'hemisferi sud (no és el cas)
  
      // * UTM zone Center Meridian Calculation ---------------------------------------------------
      $lambda0 = $fus * 6 - 183;
  
      // * Coticchia-Surace's equations : Parameters ------------------------------------------------
      $rho    = $y / (6366197.724*$k0);
      $nu     = ($c*$k0)/pow((1 + $ei2*cos($rho)*cos($rho)),0.5);
      $a      = $x/$nu;
      $A1     = sin(2*$rho);
      $A2     = $A1*cos($rho)*cos($rho);
      $J2     = $rho+($A1/2);
      $J4     = (3*$J2+$A2)/4;
      $J6     = (5*$J4+$A2*cos($rho)*cos($rho))/3;
      $alfa   = (3*$ei2)/4;
      $beta   = (5*$alfa*$alfa)/3;
      $gamma  = (35*$alfa*$alfa*$alfa)/27;
      $B      = $k0 * $c * ($rho - $alfa*$J2 + $beta*$J4 - $gamma*$J6);
      $b      = ($y - $B)/$nu;
      $zeta   = (($ei2*$a*$a)/2)*cos($rho)*cos($rho);
      $xi     = $a*(1-($zeta/3));
      $eta    = $b*(1-$zeta)+$rho;
      $sinhxi = (pow(M_E,$xi) - pow(M_E,-$xi))/2;
      $inclambda = atan($sinhxi/cos($eta));
      $tau    = atan(cos($inclambda)*tan($eta));
 
      // * Final calculation of coordinates ---------------------------------------------------------
      $lon = (($inclambda*180)/M_PI) + $lambda0;
      $lat = $rho + (1 + $ei2*cos($rho)*cos($rho) - (3/2)*$ei2*sin($rho)*cos($rho)*($tau-$rho) )*($tau-$rho);
      $lat = $lat*180/M_PI;
      return array($lat,$lon);
    }
  
  }
?>