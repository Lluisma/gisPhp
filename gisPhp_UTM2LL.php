<?php 
  # _______________________________________________________________________________________________
  # gisPhp_UTM2LL.php                                                                     :: gisPHP
  # -----------------------------------------------------------------------------------------------
  #  Author:       lluís martí i garro                                         
  #  Date:         29-02-2012
  #  Description:  Reprojection UTM to LatLong function
  # _______________________________________________________________________________________________
  #
  #  References:
  #   * Aprende a convertir coordenadas geográficas en UTM y UTM en geográficas (Parte II) 
  #     > http://www.gabrielortiz.com/index.asp?Info=058a
  # _______________________________________________________________________________________________
  
  function gisPhp_UTM2LL ($x, $y, $fus) {
    // * CONSTANTS --------------------------------------------------------------------------------
    $k0 = 0.9996;										// k0 = scale along long0
    $k0 = 0.99960000000000004;	 		// Even though it's a constant, we retain it as a separate symbol to keep the numerical coefficients simpler,
														        // also to allow for systems that might use a different Mercator projection
    $a = 6378388.0;									// Major semi-aixs (basic data of Hayford ellipsoid's geometry)
   	 	                         			// Polar Radius = 6378137;
    $b = 6356911.946130;						// Minor semi-aixis 
  
    // * Càlculs previs, sobre la geometria de l'el·lipsoide --------------------------------------
  
  	//$e = sqrt(pow($a,2) - pow($b,2))/$a;			// Excentricity (not needed in Coticchia-Surace's equations)
  	$ei = sqrt(pow($a,2) - pow($b,2))/$b;				// Second excentricity
  	$ei2 = pow($ei,2);									        // Square of second excentricity
  
  	$c = pow($a,2)/$b;									        // Polar radius or curvature
	 //$alfa = ($a-$b)/$a;								        // Plane (not needed in Coticchia-Surace's equations)
  
    // * Previous treatment of X and Y ------------------------------------------------------------
    $x = $x - 500000.0;									        // (new axis)
    //$y = $y - 10000000.0;								      // Elimination of 'setback' only in the southern hemisphere (this is not the case)
  
    // * Spindle Center Meridian Calculation ------------------------------------------------------
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
?>