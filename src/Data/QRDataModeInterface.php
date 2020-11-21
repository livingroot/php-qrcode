<?php
/**
 * Interface QRDataModeInterface
 *
 * @filesource   QRDataModeInterface.php
 * @created      01.12.2015
 * @package      chillerlan\QRCode\Data
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2015 Smiley
 * @license      MIT
 */

namespace chillerlan\QRCode\Data;

use chillerlan\QRCode\Helpers\BitBuffer;

/**
 * Specifies the methods reqired for the data modules (Number, Alphanum, Byte and Kanji)
 * and holds version information in several constants
 */
interface QRDataModeInterface{

	/**
	 * returns the length bits for the given breakpoint [0,1,2]
	 */
	public function getLengthBits(int $k):int;

	/**
	 * retruns the length in bits of the data string
	 */
	public function getLengthInBits():int;

	/**
	 * checks if the given string qualifies for the encoder module
	 */
	public static function validateString(string $string):bool;

	/**
	 * writes the actual data string to the BitBuffer, uses the given version to determine the length bits
	 *
	 * @see \chillerlan\QRCode\Data\QRData::writeBitBuffer()
	 */
	public function write(BitBuffer $bitBuffer, int $version):void;

}
