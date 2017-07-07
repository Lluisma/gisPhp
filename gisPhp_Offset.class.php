<?php 
  # _______________________________________________________________________________________________
  # gisPhp_Offset.class.php                                                               :: gisPHP
  # -----------------------------------------------------------------------------------------------
  #  Author:       lluís martí i garro
  #  Date:         29-02-2012
  #  Description:  Polygon's offset generator class
  # _______________________________________________________________________________________________
  #
  #  References:
  #   * Simple Polygon Offset (PYTHON)
  #     > http://pyright.blogspot.com/2011/02/simple-polygon-offset.html
  # _______________________________________________________________________________________________


  class gisPhp_Offset {

    public function __construct( array $points, $dist_offset ) {
      $this->inputPoints  = $points;
      $this->dist_offset  = $dist_offset;
      //$this->offsetPoints = null;
    }

    protected function calcoffsetpoint($pt1, $pt2, $dist_offset) {
      $theta  = atan2($pt2[1] - $pt1[1], $pt2[0] - $pt1[0]);
      $theta += M_PI/2.0; 
      return array($pt1[0] - cos($theta) * $dist_offset, $pt1[1] - sin($theta) * $dist_offset);
    }
    
    protected function getoffsetintercept($pt1, $pt2, $m, $dist_offset) {
      // From points pt1 and pt2 defining a line in the Cartesian plane, the slope of the line m, and an offset distance,
      // calculates the y intercept of the new line offset from the original.
      $arrXY = $this->calcoffsetpoint($pt1, $pt2, $dist_offset);
      return $arrXY[1] - $m * $arrXY[0];
    }
        
    protected function getpt($pt1, $pt2, $pt3, $dist_offset) {
      // Gets intersection point of the two lines defined by pt1, pt2, and pt3;
      // offset is the distance to offset the point from the polygon.
      // get first offset intercept
      $m = ($pt2[1] - $pt1[1])/($pt2[0] - $pt1[0]);
      $boffset = $this->getoffsetintercept($pt1, $pt2, $m, $dist_offset);
      // get second offset intercept
      $mprime = ($pt3[1] - $pt2[1])/($pt3[0] - $pt2[0]);
      $boffsetprime = $this->getoffsetintercept($pt2, $pt3, $mprime, $dist_offset);
      // get intersection of two offset lines
      $newx = ($boffsetprime - $boffset)/($m - $mprime);
      $newy = $m * $newx + $boffset;
      return array($newx, $newy);
    }
        
    function getOffsetPoints() {
      // Offsets a clockwise list of coordinates polyx distance offset to the inside of the polygon.
      // Returns list of offset points.
      $plen  = count($this->inputPoints);
      $polyy = array();
      // need three points at a time
      for ( $counter=0; $counter<$plen-3; $counter++) {
        // get first offset intercept
        $pt = $this->getpt($this->inputPoints[$counter], $this->inputPoints[$counter + 1], $this->inputPoints[$counter + 2], $this->dist_offset);
        // append new point to polyy
        $polyy[] = $pt;
      }
      // last three points
      $pt = $this->getpt($this->inputPoints[$plen-3], $this->inputPoints[$plen-2], $this->inputPoints[$plen-1], $this->dist_offset);
      $polyy[] = $pt;
      $pt = $this->getpt($this->inputPoints[$plen-2], $this->inputPoints[$plen-1], $this->inputPoints[0], $this->dist_offset);
      $polyy[] = $pt;
      $pt = $this->getpt($this->inputPoints[$plen-1], $this->inputPoints[0], $this->inputPoints[1], $this->dist_offset);
      $polyy[] = $pt;
      return $polyy;
    }
      
  }
?>