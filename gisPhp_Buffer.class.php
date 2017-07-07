<?php 
  # _______________________________________________________________________________________________
  # gisPhp_Buffer.class.php                                                               :: gisPHP
  # -----------------------------------------------------------------------------------------------
  #  Author:       lluís martí i garro
  #  Date:         01-03-2012
  #  Description:  Generació de l'offset d'un polígon
  # _______________________________________________________________________________________________


  class gisPHP_Buffer {

    public function __construct( array $points, $dist_offset ) {
      $this->inputPoints = $points;
      $this->dist_offset = $dist_offset;
      //$this->offsetPoints = null;
    }

    function getBufferPoints() {
      $polyy = array();      
      foreach ( $this->inputPoints as $point) {
      	$x = $point[0];
      	$y = $point[1];
        for ($i = 0;  $i < 2*pi(); $i += ( pi()/4)) {
          $x2 = $x -(sin($i) * $this->dist_offset * $this->dist_offset);
          $y2 = $y -(cos($i) * $this->dist_offset * $this->dist_offset);
          $polyy[] = array($x2, $y2);
        }
      }
      return $polyy;
    }
      
  }
?>