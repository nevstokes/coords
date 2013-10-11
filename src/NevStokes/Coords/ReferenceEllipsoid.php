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

/**
 *
 */
class ReferenceEllipsoid
{
	const AIRY1830_MAJ = 6377563.396;
	const AIRY1830_MIN = 6356256.909;

	const GRS80_MAJ = 6378137.0;
	const GRS80_MIN = 6356752.3141;

	const WGS84_MAJ = 6378137.0;
	const WGS84_MIN = 6356752.3142;

	/**
	 * [$maj description]
	 * @var [type]
	 */
	public $maj;

	/**
	 * [$min description]
	 * @var [type]
	 */
	public $min;

	/**
	 * [$ecc description]
	 * @var [type]
	 */
	public $ecc;

	/**
	 * Create a new RefEll object to represent a reference ellipsoid
	 *
	 * @param maj the major axis
	 * @param min the minor axis
	 */
	public function __construct(
		$maj = self::AIRY1830_MAJ,
		$min = self::AIRY1830_MIN
	) {
		$this->maj = $maj;
		$this->min = $min;
		$this->ecc = (($maj * $maj) - ($min * $min)) / ($maj * $maj);
	}
}
