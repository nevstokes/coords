<?php

/**
 * Part of the PHP coordinate conversion library
 *
 * This library provides functions for handling various co-ordinate systems and
 * converting between them. Currently, OSGB (Ordnance Survey of Great Britain)
 * grid references, UTM (Universal Transverse Mercator) references and
 * latitude/longitude are supported.
 *
 * Originally developed by Jonathan Stott at http://www.jstott.me.uk/phpcoord/
 * Updated and namespaced by Nev Stokes, 2013
 *
 * @author Nev Stokes <mail@nevstokes.com>
 * @author Jonathan Stott <jonathan@jstott.me.uk>
 * @license GNU General Public License, version 2
 */

namespace NevStokes\Coords;

use NevStokes\Maths\Trig;

/**
 * References given with OSRef are accurate to 1m.
 */
class OSRef
{
	/**
	 * [$easting description]
	 * @var [type]
	 */
	public $easting;

	/**
	 * [$northing description]
	 * @var [type]
	 */
	public $northing;

	/**
	 * Create a new OSRef object representing an OSGB grid reference. Note
	 * that the parameters for this constructor require eastings and
	 * northings with 1m accuracy and need to be absolute with respect to
	 * the whole of the British Grid. For example, to create an OSRef
	 * object from the six-figure grid reference TG514131, the easting would
	 * be 651400 and the northing would be 313100.
	 *
	 * Grid references with accuracy greater than 1m can be represented
	 * using floating point values for the easting and northing. For example,
	 * a value representing an easting or northing accurate to 1mm would be
	 * given as 651400.0001.
	 *
	 * @param easting the easting of the reference (with 1m accuracy)
	 * @param northing the northing of the reference (with 1m accuracy)
	 */
	private function __construct($easting, $northing)
	{
		$this->easting	= $easting;
		$this->northing = $northing;
	}

	public static function create($easting, $northing)
	{
		return new self($easting, $northing);
	}

	/**
	 * Take a string formatted as a six-figure OS grid reference (e.g.
	 * "TG514131") and return a reference to an OSRef object that represents
	 * that grid reference. The first character must be H, N, S, O or T.
	 * The second character can be any uppercase character from A through Z
	 * excluding I.
	 *
	 * @param ref
	 * @return
	 * @since 2.1
	 */
	public static function createFromSixFigureReference($ref)
	{
		$char1 = substr($ref, 0, 1);
		$char2 = substr($ref, 1, 1);
		$east  = substr($ref, 2, 3) * 100;
		$north = substr($ref, 5, 3) * 100;

		switch ($char1) {
			case 'H':
				$north += 1000000;
				break;

			case 'N':
				$north += 500000;
				break;

			case 'O':
				$north += 500000;
				$east  += 500000;
				break;

			case 'T':
				$east += 500000;
				break;
		}

		$char2ord = ord($char2);

		if ($char2ord > 73) {
			// Adjust for no I
			$char2ord--;
		}

		$nx = (($char2ord - 65) % 5) * 100000;
		$ny = (4 - floor(($char2ord - 65) / 5)) * 100000;

		return new self(($east + $nx), ($north + $ny));
	}


	/**
	 * Convert this grid reference into a string showing the exact values
	 * of the easting and northing.
	 *
	 * @return
	 */
	public function __toString()
	{
		return '(' . $this->easting . ', ' . $this->northing . ')';
	}

	/**
	 * Convert this grid reference into a string using a standard six-figure
	 * grid reference including the two-character designation for the 100km
	 * square. e.g. TG514131.
	 *
	 * @return
	 */
	public function toSixFigureString()
	{
		$hundredkmE = floor($this->easting / 100000);
		$hundredkmN = floor($this->northing / 100000);

		if ($hundredkmN < 5) {
			$firstLetter = ($hundredkmE < 5) ? 'S' : 'T';
		} else if ($hundredkmN < 10) {
			$firstLetter = ($hundredkmE < 5) ? 'N' : 'O';
		} else {
			$firstLetter = 'H';
		}

		$index = 65 + ((4 - ($hundredkmN % 5)) * 5) + ($hundredkmE % 5);
		$ti = $index;
		if ($index >= 73) {
			$index++;
		}

		$secondLetter = chr($index);

		$e = round(($this->easting - (100000 * $hundredkmE)) / 100);
		$n = round(($this->northing - (100000 * $hundredkmN)) / 100);

		return sprintf('%s%s%03d%03d', $firstLetter, $secondLetter, $e, $n);
	}

	/**
	 * Convert this grid reference into a latitude and longitude
	 *
	 * @return
	 */
	function toLatLng()
	{
		$airy1830 = new ReferenceEllipsoid;
		$OSGB_F0  = 0.9996012717;
		$N0       = -100000.0;
		$E0       = 400000.0;
		$phi0     = deg2rad(49.0);
		$lambda0  = deg2rad(-2.0);
		$a        = $airy1830->maj;
		$b        = $airy1830->min;
		$eSquared = $airy1830->ecc;
		$phi      = 0.0;
		$lambda   = 0.0;
		$E        = $this->easting;
		$N        = $this->northing;
		$n        = ($a - $b) / ($a + $b);
		$M        = 0.0;
		$phiPrime = (($N - $N0) / ($a * $OSGB_F0)) + $phi0;

		$phiPrimeSecant     = Trig::sec($phiPrime);
		$phiPrimeSinSquared = Trig::sinSquared($phiPrime);
		$phiPrimeTanSquared = Trig::tanSquared($phiPrime);

		do {
			$M =
				($b * $OSGB_F0)
				* (((1 + $n + ((5.0 / 4.0) * $n * $n) + ((5.0 / 4.0) * $n * $n * $n))
					* ($phiPrime - $phi0))
					- (((3 * $n) + (3 * $n * $n) + ((21.0 / 8.0) * $n * $n * $n))
					* sin($phiPrime - $phi0)
					* cos($phiPrime + $phi0))
					+ ((((15.0 / 8.0) * $n * $n) + ((15.0 / 8.0) * $n * $n * $n))
					* sin(2.0 * ($phiPrime - $phi0))
					* cos(2.0 * ($phiPrime + $phi0)))
					- (((35.0 / 24.0) * $n * $n * $n)
					* sin(3.0 * ($phiPrime - $phi0))
					* cos(3.0 * ($phiPrime + $phi0))));
			$phiPrime += ($N - $N0 - $M) / ($a * $OSGB_F0);
		} while (($N - $N0 - $M) >= 0.001);

		$v = $a * $OSGB_F0 * pow(1.0 - $eSquared * $phiPrimeSinSquared, -0.5);

		$rho = $a
			* $OSGB_F0
			* (1.0 - $eSquared)
			* pow(1.0 - $eSquared * $phiPrimeSinSquared, -1.5);

		$etaSquared = ($v / $rho) - 1.0;

		$VII = tan($phiPrime) / (2 * $rho * $v);

		$VIII = (tan($phiPrime) / (24.0 * $rho * pow($v, 3.0)))
			* (5.0
			+ (3.0 * $phiPrimeTanSquared)
			+ $etaSquared
			- (9.0 * $phiPrimeTanSquared * $etaSquared));

		$IX = (tan($phiPrime) / (720.0 * $rho * pow($v, 5.0)))
			* (61.0
			+ (90.0 * $phiPrimeTanSquared)
			+ (45.0 * $phiPrimeTanSquared * $phiPrimeTanSquared));

		$X = $phiPrimeSecant / $v;

		$XI = ($phiPrimeSecant / (6.0 * $v * $v * $v))
			* (($v / $rho) + (2 * $phiPrimeTanSquared));

		$XII = ($phiPrimeSecant / (120.0 * pow($v, 5.0)))
			* (5.0
			+ (28.0 * $phiPrimeTanSquared)
			+ (24.0 * $phiPrimeTanSquared * $phiPrimeTanSquared));

		$XIIA = ($phiPrimeSecant / (5040.0 * pow($v, 7.0)))
			* (61.0
			+ (662.0 * $phiPrimeTanSquared)
			+ (1320.0 * $phiPrimeTanSquared * $phiPrimeTanSquared)
			+ (720.0
				* $phiPrimeTanSquared
				* $phiPrimeTanSquared
				* $phiPrimeTanSquared));

		$phi = $phiPrime
			- ($VII * pow($E - $E0, 2.0))
			+ ($VIII * pow($E - $E0, 4.0))
			- ($IX * pow($E - $E0, 6.0));

		$lambda = $lambda0
			+ ($X * ($E - $E0))
			- ($XI * pow($E - $E0, 3.0))
			+ ($XII * pow($E - $E0, 5.0))
			- ($XIIA * pow($E - $E0, 7.0));

		return new LatLng(rad2deg($phi), rad2deg($lambda));
	}
}
