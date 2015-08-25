<?php

/*
 * Copyright (C) 2014-2015 Stichting Mapcode Foundation (http://www.mapcode.com)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

define('mapcode_phpversion', '2.1.0');

$xdivider19 = array(
    360, 360, 360, 360, 360, 360, 361, 361, 361, 361,
    362, 362, 362, 363, 363, 363, 364, 364, 365, 366,
    366, 367, 367, 368, 369, 370, 370, 371, 372, 373,
    374, 375, 376, 377, 378, 379, 380, 382, 383, 384,
    386, 387, 388, 390, 391, 393, 394, 396, 398, 399,
    401, 403, 405, 407, 409, 411, 413, 415, 417, 420,
    422, 424, 427, 429, 432, 435, 437, 440, 443, 446,
    449, 452, 455, 459, 462, 465, 469, 473, 476, 480,
    484, 488, 492, 496, 501, 505, 510, 515, 520, 525,
    530, 535, 540, 546, 552, 558, 564, 570, 577, 583,
    590, 598, 605, 612, 620, 628, 637, 645, 654, 664,
    673, 683, 693, 704, 715, 726, 738, 751, 763, 777,
    791, 805, 820, 836, 852, 869, 887, 906, 925, 946,
    968, 990, 1014, 1039, 1066, 1094, 1123, 1154, 1187, 1223,
    1260, 1300, 1343, 1389, 1438, 1490, 1547, 1609, 1676, 1749,
    1828, 1916, 2012, 2118, 2237, 2370, 2521, 2691, 2887, 3114,
    3380, 3696, 4077, 4547, 5139, 5910, 6952, 8443, 10747, 14784,
    23681, 59485);

$nc = array(1, 31, 961, 29791, 923521, 28629151, 887503681);
$xside = array(0, 5, 31, 168, 961, 168 * 31, 29791, 165869, 923521, 5141947, 28629151);
$yside = array(0, 6, 31, 176, 961, 176 * 31, 29791, 165869, 923521, 5141947, 28629151);

$encode_chars = "0123456789BCDFGHJKLMNPQRSTVWXYZAEU";
function encodeChar($i)
{
    return $GLOBALS['encode_chars'][$i];
}

function xDivider4($miny, $maxy)
{
    if ($miny >= 0) {
        return $GLOBALS['xdivider19'][($miny) >> (19)];
    }
    if ($maxy >= 0) {
        return $GLOBALS['xdivider19'][0];
    }
    return $GLOBALS['xdivider19'][(-($maxy)) >> (19)];
}

function parentname2($disam)
{
    return substr(parents2, ($disam - 1) * 3, 2);
}

function parentletter($isocode)
{
    $p = false;
    $srch = $isocode . ",";
    $len = strlen($srch);
    if ($len == 3) {
        $p = stripos(parents2, $srch);
    } else {
        if ($len == 4) {
            $p = stripos(parents3, $srch);
        }
    }
    if ($p === false) {
        return -2;
    }
    return 1 + ($p / $len);
}

$disambiguate = 1;
function set_disambiguate($isocode)
{
    $p = parentletter($isocode);
    if ($p < 0) {
        return -2;
    }
    $GLOBALS['disambiguate'] = $p;
    return 0;
}

function alias2iso($isocode)
{
    $isocode .= '=';
    $p = stripos($GLOBALS['aliases'], $isocode);
    if ($p === false) {
        return '';
    }
    if (strlen($isocode) == 3) {
        $p--;
        $c = substr($GLOBALS['aliases'], $p, 1);
        if ($c < '0' || $c > '9') {
            return '';
        }
    }
    return substr($GLOBALS['aliases'], $p + 4, 3);
}

/// PUBLIC - return parent country of $territoryNumber (just returns $territoryNumber if $territoryNumber is itself a country)
function getParentOf($territory)
{
    $territoryNumber = getTerritoryNumber($territory);
    if ($territoryNumber >= usa_from && $territoryNumber <= usa_upto) {
        return ccode_usa;
    }
    if ($territoryNumber >= ind_from && $territoryNumber <= ind_upto) {
        return ccode_ind;
    }
    if ($territoryNumber >= can_from && $territoryNumber <= can_upto) {
        return ccode_can;
    }
    if ($territoryNumber >= aus_from && $territoryNumber <= aus_upto) {
        return ccode_aus;
    }
    if ($territoryNumber >= mex_from && $territoryNumber <= mex_upto) {
        return ccode_mex;
    }
    if ($territoryNumber >= bra_from && $territoryNumber <= bra_upto) {
        return ccode_bra;
    }
    if ($territoryNumber >= rus_from && $territoryNumber <= rus_upto) {
        return ccode_rus;
    }
    if ($territoryNumber >= chn_from && $territoryNumber <= chn_upto) {
        return ccode_chn;
    }
    return -199;
}

function iso2ccode($territory)
{

    $isocode = strtoupper(trim($territory));
    if (is_numeric($isocode)) {
        return intval($isocode);
    }
    $sep = strrpos($isocode, '-');
    if ($sep === false) {
        $sep = strrpos($isocode, ' ');
    }
    if ($sep !== false) {
        $prefix = trim(substr($isocode, 0, $sep));
        $isocode = substr($isocode, $sep + 1);
        if (strlen($isocode) != 2 && strlen($isocode) != 3) {
            return -1;
        }
        if (set_disambiguate($prefix)) {
            return -2;
        }

        if (strlen($isocode) == 2) {
            $isocode = $GLOBALS['disambiguate'] . $isocode;
        } 
        
        {
            if (strlen($isocode) == 3) {
                $isoa = alias2iso($isocode);
                if ($isoa != '') {
                    if (substr($isoa, 0, 1) == $GLOBALS['disambiguate']) {
                        $isocode = $isoa;
                    }
                }
            }
        }
    }

    if (strlen($isocode) != 2 && strlen($isocode) != 3) {
        return -1;
    }

    $testiso = $isocode;
    if (strlen($isocode) == 2) {
        $testiso = $GLOBALS['disambiguate'] . $isocode;
    }

    for ($i = 0; $i < MAX_CCODE; $i++) {
        if ($testiso == $GLOBALS['entity_iso'][$i]) {
            return $i;
        }
    }

    $a = alias2iso($testiso);
    if (strlen($a)) {
      return iso2ccode($a);
    }

    if (strlen($isocode) == 2) {
        for ($i = 0; $i < MAX_CCODE; $i++) {
            if (substr($GLOBALS['entity_iso'][$i], 0, 1) <= '9' && $isocode == substr($GLOBALS['entity_iso'][$i], 1)) {
                return $i;
            }
        }
    }

    $isocode = alias2iso($isocode);
    if ($isocode != '') {
        return iso2ccode($isocode);
    }

    return -1;
}

function getTerritoryNumber($territory, $contextTerritoryNumber = -1)
{
    if ($contextTerritoryNumber >= 0) {
        set_disambiguate($GLOBALS['entity_iso'][$contextTerritoryNumber]);
    }
    return iso2ccode($territory);
}

/// PUBLIC - return name of $territory (optional keepindex=1 for bracketed aliases)
function getTerritoryFullname($territory, $keepindex = 0)
{
    $territoryNumber = getTerritoryNumber($territory);
    if ($territoryNumber < 0 || $territoryNumber > ccode_earth) {
        return '';
    }
    if ($keepindex != 1) {
        $idx = strpos($GLOBALS['isofullname'][$territoryNumber], " (");
        if ($idx !== false) {
            return substr($GLOBALS['isofullname'][$territoryNumber], 0, $idx);
        }
    }
    return $GLOBALS['isofullname'][$territoryNumber];
}

/// PUBLIC - returns true iff $territoryNumber is a state
function isSubdivision($territory)
{
    return (getParentOf($territory) >= 0);
}

function isInRange($x, $minx, $maxx)
{
    if ($minx <= $x && $x < $maxx) {
        return true;
    }
    if ($x < $minx) {
        $x += 360000000;
    } else {
        $x -= 360000000;
    }
    if ($minx <= $x && $x < $maxx) {
        return true;
    }
    return false;
}

function fitsInside($p, $mm)
{
    return ($mm->miny <= $p->lat && $p->lat < $mm->maxy && isInRange($p->lon, $mm->minx, $mm->maxx));
}

function fitsInsideWithRoom($p, $mm)
{
    if ((($mm->miny - 45) > $p->lat) || ($p->lat >= ($mm->maxy + 45))) {
        return false;
    }
    $xroom = xDivider4($mm->miny, $mm->maxy) >> 2;
    return isInRange($p->lon, $mm->minx - $xroom, $mm->maxx + $xroom);
}

function startsdigit($n)
{
    $o = ord($n);
    return ($o >= 48 && $o <= 57);
}

function getTerritoryAlphaCode($territory, $international = 1)
{
    $territoryNumber = getTerritoryNumber($territory);
    if ($territoryNumber < 0 || $territoryNumber > ccode_earth) {
      return -1;
    }
    $n = $GLOBALS['entity_iso'][$territoryNumber];
    if (startsdigit($n)) {
        $n = substr($n, 1);
    }
    if ($international) {
        $parent = getParentOf($territoryNumber);
        if ($parent >= 0) {
            if ($international == 2) {
                $count = 0;
                $i = strpos($GLOBALS['aliases'], $n . '=');
                if ($i !== false) {
                    $count = 2;
                } else {
                    if (strlen($n) == 2) {
                        for ($i = 0; $i < MAX_CCODE; $i++) {
                            if (substr($GLOBALS['entity_iso'][$i], 1) == $n) {
                                if (startsdigit($GLOBALS['entity_iso'][$i])) {
                                    $count++;
                                    if ($count > 1) {
                                        break;
                                    }
                                }
                            }
                        }
                    } else {
                        $i;
                        for ($i = 0; $i < MAX_CCODE; $i++) {
                            if ($GLOBALS['entity_iso'][$i] == $n) {
                                $count++;
                                if ($count > 1) {
                                    break;
                                }
                            }
                        }
                    }
                }
                if ($count == 1) {
                    return $n;
                }
            }
            return parentname2(parentletter($GLOBALS['entity_iso'][$parent])) . '-' . $n;
        }
    }
    return $n;
}

/// PUBLIC - returns true iff $territoryNumber is a country that has states
function hasSubdivision($territory)
{
    return (strpos(parents3, getTerritoryAlphaCode($territory, 0)) !== false);
}

class Coord
{
    public $lat, $lon;
    public $minlat,$maxlat,$minlon,$maxlon; // FORCE_RECODE

    public function __construct($lat, $lon)
    {
        $this->lat = $lat;
        $this->lon = $lon;
    }

    public function __toString()
    {
        return sprintf('[%0.9f,%0.9f]', $this->lat, $this->lon);
    }
}

class Rect
{
    public $minx, $maxx, $miny, $maxy;

    public function __construct($minx, $maxx, $miny, $maxy)
    {
        $this->minx = $minx;
        $this->miny = $miny;
        $this->maxx = $maxx;
        $this->maxy = $maxy;
    }

    public function __toString()
    {
        return sprintf('[%d...%d , %d...%d]', $this->miny, $this->maxy, $this->minx, $this->maxx);
    }
}

class EncodeRec
{
    public $coord32, $fraclat, $fraclon;

    public function __construct($lat, $lon)
    {

        if (!is_numeric($lat)) {
            $lat = 0;
        } else {
            $lat += 0;
        }
        if ($lat > 90) {
            $lat = 90;
        } else {
            if ($lat < -90) {
                $lat = -90;
            }
        }
        $lat += 90; // lat now [0..180]
        $lat *= 810000000000;
        $fraclat = floor($lat+0.1);
        $f = $fraclat  / 810000;
        $lat32 = intval($f);
        $fraclat -= ($lat32 * 810000);
        $lat32 -= 90000000;

        $this->fraclat = $fraclat;

        if (!is_numeric($lon)) {
            $lon = 0;
        } else {
            $lon += 0;
        }
        $lon -= (360 * floor($lon / 360)); // lon now in [0..360>
        $lon *= 3240000000000;
        $fraclon = floor($lon+0.1);
        $f = $fraclon / 3240000;
        $lon32 = floor($f);
        $fraclon -= ($lon32 * 3240000);
        $this->fraclon = $fraclon;
        if ($lon32 >= 180000000)
            $lon32 -= 360000000;

        $this->coord32 = new Coord($lat32, $lon32);
    }

    public function __toString()
    {
        return sprintf('[%0.11f,%0.11f]', ($this->coord32->lat + $this->fraclat/810000) / 1000000.0, ($this->coord32->lon + $this->fraclon/3240000) / 1000000.0);
    }
}

function dataFirstRecord($territoryNumber)
{
    return $GLOBALS['data_start'][$territoryNumber];
}

function dataLastRecord($territoryNumber)
{
    return $GLOBALS['data_start'][1 + $territoryNumber] - 1;
}

function minmaxSetup($i)
{
    $i <<= 2;
    return new Rect($GLOBALS['data_mm'][$i++], $GLOBALS['data_mm'][$i++], $GLOBALS['data_mm'][$i++], $GLOBALS['data_mm'][$i]);
}

function recType($i)
{
    return (($GLOBALS['data_flags'][$i] >> 7) & 3);
}

function smartdiv($i)
{
    return $GLOBALS['data_special1'][$i];
}

function isRestricted($i)
{
    return $GLOBALS['data_flags'][$i] & 512;
}

function isNameless($i)
{
    return $GLOBALS['data_flags'][$i] & 64;
}

function CodexLen($i)
{
    $flags = $GLOBALS['data_flags'][$i] & 31;
    return (int)($flags / 5) + ($flags % 5) + 1;
}

function Codex($i)
{
    $flags = $GLOBALS['data_flags'][$i] & 31;
    $codexhi = (int)($flags / 5);
    return (10 * $codexhi) + ($flags % 5) + 1;
}

function isSpecialShape($i)
{
    return $GLOBALS['data_flags'][$i] & 1024;
}

function headerLetter($i)
{
    $flags = $GLOBALS['data_flags'][$i];
    if ((($flags >> 7) & 3) == 1) {
        return encodeChar(($flags >> 11) & 31);
    }
    return '';
}

function decodeChar($c)
{
    return $GLOBALS['decode_chars'][ord($c)];
}

function decodeBase31($str)
{
    $value = 0;
    for ($i = 0; $i < strlen($str); $i++) {
        $c = $str[$i];
        if (ord($c) == 46) {
            return $value;
        }
        if (decodeChar($c) < 0) {
            return -1;
        }
        $value = $value * 31 + decodeChar($c);
    }
    return $value;
}

function decodeTriple($input)
{
    $c1 = decodeChar($input);
    $x = decodeBase31(substr($input, 1));
    if ($x < 0) {
        return 0;
    }
    if ($c1 < 24) {
        return new Coord((int)($c1 / 6) * 34 + (int)($x % 34), ($c1 % 6) * 28 + (int)($x / 34));
    }
    return new Coord(($x % 40) + 136, (int)($x / 40) + 24 * ($c1 - 24));
}

function decodeSixWide($v, $width, $height)
{
    $D = 6;
    $col = (int)($v / ($height * 6));
    $maxcol = (int)(($width - 4) / 6);
    if ($col >= $maxcol) {
        $col = $maxcol;
        $D = $width - $maxcol * 6;
    }
    $w = $v - ($col * $height * 6);
    return new Coord($height - 1 - (int)($w / $D), ($col * 6) + ($w % $D));
}

// returns adjusted coordinate, or 0 if error
function decodeExtension($extensionchars, $coord, $dividerx4, $dividery, $lon_offset4) {
    $dividerx = $dividerx4 / 4.0;
    $processor = 1;
    $lon32 = 0;
    $lat32 = 0;
    $odd = 0;    
    $idx = 0; $len = strlen($extensionchars);
    if ($len > 8) {
        return 0;
    }    
    while ($idx < $len) {
        $c = decodeChar($extensionchars[$idx++]);
        if ($c < 0 || $c == 30) { return 0; } // illegal extension character
        $row1 = (int)($c / 5);
        $column1 = ($c % 5);
        if ($idx < $len) {
            $c = decodeChar($extensionchars[$idx++]);
            if ($c < 0 || $c == 30) { return 0; } // illegal extension character
            $row2 = (int)($c / 6);
            $column2 = ($c % 6);
        }
        else {
            $row2 = 0;
            $odd = 1;
            $column2 = 0;
        }
        $processor *= 30;
        $lon32 = $lon32 * 30 + $column1 * 6 + $column2;
        $lat32 = $lat32 * 30 + $row1 * 5 + $row2;
    }
    $coord->lon += (($lon32 * $dividerx) / $processor) + ( $lon_offset4 / 4.0 );
    $coord->lat += (($lat32 * $dividery) / $processor);

    // FORCE_RECODE - determine potential range for lon/lat
    $coord->minlon = $coord->lon;
    $coord->minlat = $coord->lat;
    if ($odd) {
        $coord->maxlon = $coord->minlon + ($dividerx / ($processor / 6));
        $coord->maxlat = $coord->minlat + ($dividery / ($processor / 5));
    } else {
        $coord->maxlon = $coord->minlon + ($dividerx / $processor);
        $coord->maxlat = $coord->minlat + ($dividery / $processor);
    } // FORCE_RECODE

    if ($odd) {
        $coord->lon += ($dividerx / (2 * ($processor / 6)));
        $coord->lat += ($dividery / (2 * ($processor / 5)));
    } else {
        $coord->lon += ($dividerx / (2 * $processor));
        $coord->lat += ($dividery / (2 * $processor));
    } // not odd

    return $coord;
}

function decodeGrid($input, $extensionchars, $m)
{
    $prefixlength = strpos($input, '.');
    $postfixlength = strlen($input) - 1 - $prefixlength;
    if ($prefixlength == 1 && $postfixlength == 4) {
        $prefixlength++;
        $postfixlength--;
        $input = $input[0] . $input[2] . '.' . substr($input, 3);
    }

    $divy = smartdiv($m);

    if ($divy == 1) {
        $divx = $GLOBALS['xside'][$prefixlength];
        $divy = $GLOBALS['yside'][$prefixlength];
    } else {
        $divx = (int)($GLOBALS['nc'][$prefixlength] / $divy);
    }

    if ($prefixlength == 4 && $divx == 961 && $divy == 961) {
        $input = $input[0] . $input[2] . $input[1] . substr($input, 3);
    }

    $v = decodeBase31($input);
    if ($v < 0) {
        return 0;
    }

    if ($divx != $divy && $prefixlength > 2) {
        $d = decodeSixWide($v, $divx, $divy);
        $relx = $d->lon;
        $rely = $d->lat;
    } else {
        $relx = (int)($v / $divy);
        $rely = $divy - 1 - ($v % $divy);
    }

    $mm = minmaxSetup($m);
    $ygridsize = (int)(($mm->maxy - $mm->miny + $divy - 1) / $divy);
    $xgridsize = (int)(($mm->maxx - $mm->minx + $divx - 1) / $divx);

    $rely = $mm->miny + ($rely * $ygridsize);
    $relx = $mm->minx + ($relx * $xgridsize);

    $xp = $GLOBALS['xside'][$postfixlength];
    $dividerx = (int)(($xgridsize + $xp - 1) / $xp);
    $yp = $GLOBALS['yside'][$postfixlength];
    $dividery = (int)(($ygridsize + $yp - 1) / $yp);

    $rest = substr($input, $prefixlength + 1);

    if ($postfixlength == 3) {
        $d = decodeTriple($rest);
        if ($d == 0) {
            return 0;
        }
        $difx = $d->lon;
        $dify = $d->lat;
    } else {
        if ($postfixlength == 4) {
            $rest = $rest[0] . $rest[2] . $rest[1] . $rest[3];
        }
        $v = decodeBase31($rest);
        if ($v < 0) {
            return 0;
        }
        $difx = (int)($v / $yp);
        $dify = (int)($v % $yp);
    }

    $dify = $yp - 1 - $dify;

    $corner = new Coord($rely + ($dify * $dividery), $relx + ($difx * $dividerx)); // grid

    if (!fitsInside($corner,$mm)) {
      return 0;
    }

    $r = decodeExtension($extensionchars, $corner, ($dividerx) << 2, $dividery, 0);
    if ($r!==0) { // FORCE_RECODE
        if ($r->lon >= $relx + $xgridsize) {
            $r->lon = ($relx + $xgridsize - 0.000001);
        } // keep in inner cell
        if ($r->lat >= $rely + $ygridsize) {
            $r->lat = ($rely + $ygridsize - 0.000001);
        } // keep in inner cell
        if ($r->lon >= $mm->maxx) {
            $r->lon = ($mm->maxx - 0.000001);
        } // keep in territory
        if ($r->lat >= $mm->maxy) {
            $r->lat = ($mm->maxy - 0.000001);
        } // keep in territory
    } // FORCE_RECODE
    return $r;
}

function firstNamelessRecord($index, $firstcode)
{
    $i = $index;
    $codexm = Codex($i);
    while (($i >= $firstcode) && (Codex($i) == $codexm) && isNameless($i)) {
        $i--;
    }
    $i++;
    return $i;
}

function countNamelessRecords($index, $firstcode)
{
    $i = firstNamelessRecord($index, $firstcode);
    $e = $index;
    $codexm = Codex($e);
    while (Codex($e) == $codexm) {
        $e++;
    }
    return ($e - $i);
}

function decodeNameless($input, $extensionchars, $m, $firstindex)
{
    $codexm = Codex($m);
    if ($codexm == 22) {
        $input = substr($input, 0, 3) . substr($input, 4);
    } else {
        $input = substr($input, 0, 2) . substr($input, 3);
    }

    $A = countNamelessRecords($m, $firstindex);
    $F = firstNamelessRecord($m, $firstindex);
    $p = (int)(31 / $A);
    $r = (31 % $A);
    $v = 0;
    $swapletters = 0;

    if ($codexm != 21 && $A <= 31) {
        $offset = decodeChar($input);

        if ($offset < $r * ($p + 1)) {
            $X = (int)($offset / ($p + 1));
        } else {
            $swapletters = ($p == 1 && $codexm == 22);
            $X = $r + (int)(($offset - ($r * ($p + 1))) / $p);
        }
    } else {
        if ($codexm != 21 && $A < 62) {
            $X = decodeChar($input);
            if ($X < (62 - $A)) {
                $swapletters = ($codexm == 22);
            } else {
                $X += ($X - (62 - $A));
            }
        } else {
            $BASEPOWER = ($codexm == 21) ? 961 * 961 : 961 * 961 * 31;
            $BASEPOWERA = (int)($BASEPOWER / $A);
            if ($A == 62) {
                $BASEPOWERA++;
            } else {
                $BASEPOWERA = 961 * (int)($BASEPOWERA / 961);
            }

            $v = decodeBase31($input);
            if ($v < 0) {
                return 0;
            }
            $X = (int)($v / $BASEPOWERA);
            $v %= $BASEPOWERA;
        }
    }

    if ($swapletters) {
        if (!isSpecialShape($m + $X)) {
            $input = $input[0] . $input[1] . $input[3] . $input[2] . $input[4];
        }
    }

    if ($codexm != 21 && $A <= 31) {
        $v = decodeBase31($input);
        if ($v < 0) {
            return 0;
        }
        if ($X > 0) {
            $v -= (($X * $p + ($X < $r ? $X : $r)) * (961 * 961));
        }
    } else {
        if ($codexm != 21 && $A < 62) {
            $v = decodeBase31(substr($input, 1));
            if ($v < 0) {
                return 0;
            }
            if ($X >= (62 - $A)) {
                if ($v >= (16 * 961 * 31)) {
                    $v -= (16 * 961 * 31);
                    $X++;
                }
            }
        }
    }

    if ($X > $A) {
        return 0;
    }
    $m = $F + $X;
    $mm = minmaxSetup($m);

    $SIDE = smartdiv($m);
    $XSIDE = $SIDE;

    if (isSpecialShape($m)) {
        $XSIDE *= $SIDE;
        $SIDE = 1 + (int)(($mm->maxy - $mm->miny) / 90);
        $XSIDE = (int)($XSIDE / $SIDE);
    }

    if (isSpecialShape($m)) {
        $d = decodeSixWide($v, $XSIDE, $SIDE);
        $dx = $d->lon;
        $dy = $SIDE - 1 - $d->lat;
    } else {
        $dy = ($v % $SIDE);
        $dx = (int)($v / $SIDE);
    }

    if ($dx >= $XSIDE) {
        return 0;
    }

    $dividerx4 = xDivider4($mm->miny, $mm->maxy);
    $dividery = 90;

    $corner = new Coord($mm->maxy - ($dy * $dividery), $mm->minx + (int)(($dx * $dividerx4) / 4));
    $r = decodeExtension($extensionchars, $corner, $dividerx4, -$dividery, ($dx * $dividerx4) % 4); // nameless
    if ($r!==0) { // FORCE_RECODE
      // keep within outer rect
      if ($r->lat < $mm->miny) {
          $r->lat = $mm->miny;
      } // keep in territory
      if ($r->lon >= $mm->maxx) {
          $r->lon = ($mm->maxx - 0.000001);
      } // keep in territory
    } // FORCE_RECODE
    return $r;
}

function decodeAutoHeader($input, $extensionchars, $m)
{
    $STORAGE_START = 0;
    $codexm = Codex($m);

    $value = decodeBase31($input);
    if ($value < 0) {
        return 0;
    }
    $value *= (961 * 31);
    $triple = decodeTriple(substr($input, strlen($input) - 3));
    if ($triple == 0) {
        return 0;
    }
    for (; Codex($m) == $codexm; $m++) {
        $mm = minmaxSetup($m);

        $H = (int)(($mm->maxy - $mm->miny + 89) / 90);
        $xdiv = xDivider4($mm->miny, $mm->maxy);
        $W = (int)((($mm->maxx - $mm->minx) * 4 + ($xdiv - 1)) / $xdiv);

        $H = 176 * (int)(($H + 176 - 1) / 176);
        $W = 168 * (int)(($W + 168 - 1) / 168);

        $product = (int)($W / 168) * (int)($H / 176) * 961 * 31;

        if (recType($m) == 2) {
            $GOODROUNDER = $codexm >= 23 ? (961 * 961 * 31) : (961 * 961);
            $product = (int)(($STORAGE_START + $product + $GOODROUNDER - 1) / $GOODROUNDER) * $GOODROUNDER - $STORAGE_START;
        }

        if ($value >= $STORAGE_START && $value < $STORAGE_START + $product) {
            $dividerx = (int)(($mm->maxx - $mm->minx + $W - 1) / $W);
            $dividery = (int)(($mm->maxy - $mm->miny + $H - 1) / $H);

            $value -= $STORAGE_START;
            $value = (int)($value / (961 * 31));

            $vx = $triple->lon + 168 * ((int)($value / (int)($H / 176)));
            $vy = $triple->lat + 176 * ($value % (int)($H / 176));

            $corner = new Coord($mm->maxy - $vy * $dividery, $mm->minx + $vx * $dividerx);

            if ($corner->lon < $mm->minx || $corner->lon >= $mm->maxx || $corner->lat < $mm->miny || $corner->lat > $mm->maxy) {
                return 0;
            }
            $r = decodeExtension($extensionchars, $corner, $dividerx << 2, -$dividery, 0); // autoheader decode
            if ($r!==0) { // FORCE_RECODE
              if ($r->lat < $mm->miny) {
                  $r->lat = $mm->miny;
              } // keep in territory
              if ($r->lon >= $mm->maxx) {
                  $r->lon = ($mm->maxx - 0.000001);
              } // keep in territory
            } // FORCE_RECODE
            return $r;
        }
        $STORAGE_START += $product;
    }
    return 0;
}

// returns unpacked string, or '' in case of error
function aeu_unpack($str)
{
    $voweled = 0;
    $lastpos = strlen($str) - 1;
    $dotpos = strpos($str, '.');
    if ($dotpos < 2 || $lastpos < $dotpos + 2) {
        return '';
    }

    if ($str[0] == 'A') {
        $v1 = decodeChar($str[$lastpos]);
        if ($v1 < 0) {
            $v1 = 31;
        }
        $v2 = decodeChar($str[$lastpos - 1]);
        if ($v2 < 0) {
            $v2 = 31;
        }
        $s = sprintf("%u", (1000 + $v1 + 32 * $v2));
        $str = $s[1] . substr($str, 1, $lastpos - 2) . $s[2] . $s[3];
        $voweled = 1;
    } else {
        if ($str[0] == 'U') {
            $voweled = 1;
            $str = substr($str, 1);
            $dotpos--;
        } else {
            $v = $str[$lastpos - 1];
            if ($v == 'A') {
                $v = 0;
            } else {
                if ($v == 'E') {
                    $v = 34;
                } else {
                    if ($v == 'U') {
                        $v = 68;
                    } else {
                        $v = -1;
                    }
                }
            }
            if ($v >= 0) {
                $e = $str[$lastpos];
                if ($e == 'A') {
                    $v += 31;
                } else {
                    if ($e == 'E') {
                        $v += 32;
                    } else {
                        if ($e == 'U') {
                            $v += 33;
                        } else {
                            $ve = decodeChar($e);
                            if ($ve < 0) {
                                return '';
                            }
                            $v += $ve;
                        }
                    }
                }
                if ($v > 99) {
                    return '';
                }
                $voweled = 1;
                $str = substr($str, 0, $lastpos - 1) . encodeChar((int)($v / 10)) . encodeChar($v % 10);
            }
        }
    }

    if ($dotpos < 2 || $dotpos > 5) {
        return '';
    }

	$nrletters = 0;
	for ($v = 0; $v <= $lastpos; $v++) {
        if ($v != $dotpos) {
            if (decodeChar($str[$v]) < 0) {
                return '';
            } else if (decodeChar($str[$v]) > 9) {
                $nrletters++;
            }
        }
    }
    if (!$voweled && !$nrletters) return '';
    if ($voweled && $nrletters) return '';

    return $str;
}

// packs mapcode $r into not-all-digit form
function aeu_pack($r, $short = 0)
{
    $dotpos = -9;
    $rlen = strlen($r);
    $rest = '';
    for ($d = 0; $d < $rlen; $d++) {
        if (!startsdigit($r[$d])) {
            if ($r[$d] == '.' && $dotpos < 0) {
                $dotpos = $d;
            } else {
                if ($r[$d] == '-') {
                    $rest = substr($r, $d);
                    $r = substr($r, 0, $d);
                    $rlen = $d;
                } else {
                    return $r;
                }
            }
        }
    }

    if ($rlen - 2 > $dotpos) {
        if ($short) {
            $v = ($r[0]) * 100 + ($r[$rlen - 2]) * 10 + ($r[$rlen - 1]);
            $r = 'A' . substr($r, 1, $rlen - 3) . encodeChar((int)($v / 32)) . encodeChar($v % 32);
        } else {
            $v = $r[$rlen - 2] * 10 + $r[$rlen - 1];
            $r = substr($r, 0, $rlen - 2) . encodeChar((int)($v / 34) + 31) . encodeChar($v % 34);
        }
    }
    return $r . $rest;
}

define('MAPCODE_ALPHABETS_TOTAL', 14);

$lannam = array(
    "Roman",
    "Greek",
    "Cyrillic",
    "Hebrew",
    "Hindi",
    "Malai",
    "Georgian",
    "Katakana",
    "Thai",
    "Lao",
    "Armenian",
    "Bengali",
    "Gurmukhi",
    "Tibetan"
);

$lanlannam = array(
    "Roman",
    "&#949;&#955;&#955;&#951;&#957;&#953;&#954;&#940;",
    "&#1082;&#1080;&#1088;&#1080;&#1083;&#1083;&#1080;&#1094;&#1072;",
    "&#1506;&#1460;&#1489;&#1456;&#1512;&#1460;&#1497;&#1514;",
    "&#2361;&#2367;&#2306;&#2342;&#2368;",
    "Melayu",
    "&#4325;&#4304;&#4320;&#4311;&#4323;&#4314;&#4312;",
    "&#12459;&#12479;&#12459;&#12490;",
    "&#3616;&#3634;&#3625;&#3634;&#3652;&#3607;&#3618;",
    "&#3742;&#3762;&#3754;&#3762;&#3749;&#3762;&#3751;",
    "&#1392;&#1377;&#1397;&#1381;&#1408;&#1381;&#1398;",
    "&#2476;&#2494;&#2434;&#2482;&#2494;",
    "&#2583;&#2625;&#2608;&#2606;&#2625;&#2582;&#2624;",
    "&#3921;&#3926;&#3956;&#3851;&#3909;&#3923;&#3851;"
);

$asc2lan = array(
    array(65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57),
    array(913, 914, 926, 916, 63, 917, 915, 919, 921, 928, 922, 923, 924, 925, 927, 929, 920, 936, 931, 932, 63, 934, 937, 935, 933, 918, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57),
    array(1040, 1042, 1057, 1044, 1045, 1046, 1043, 1053, 1048, 1055, 1050, 1051, 1052, 1047, 1054, 1056, 1060, 1071, 1062, 1058, 1069, 1063, 1064, 1061, 1059, 1041, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57),
    array(1488, 1489, 1490, 1491, 1507, 1492, 1494, 1495, 1493, 1496, 1497, 1498, 1499, 1500, 1505, 1501, 1502, 1504, 1506, 1508, 1509, 1510, 1511, 1512, 1513, 1514, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57),
    array(2309, 2325, 2327, 2328, 2319, 2330, 2332, 2335, 73, 2336, 2339, 2340, 2342, 2343, 79, 2344, 2346, 2349, 2350, 2352, 2347, 2354, 2357, 2360, 2361, 2337, 2406, 2407, 2408, 2409, 2410, 2411, 2412, 2413, 2414, 2415),
    array(3346, 3349, 3350, 3351, 3339, 3354, 3356, 3359, 3335, 3361, 3364, 3365, 3366, 3367, 3360, 3368, 3374, 3376, 3377, 3378, 3337, 3380, 3381, 3382, 3384, 3385, 3430, 3431, 3432, 3433, 3434, 3435, 3436, 3437, 3438, 3439),
    array(4256, 4257, 4259, 4262, 4260, 4265, 4267, 4268, 4275, 4270, 4272, 4273, 4274, 4276, 4269, 4277, 4278, 4279, 4280, 4281, 4264, 4282, 4283, 4285, 4286, 4287, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57),
    array(12450, 12459, 12461, 12463, 12458, 12465, 12467, 12469, 12452, 12473, 12481, 12488, 12490, 12492, 12454, 12498, 12501, 12504, 12507, 12513, 12456, 12514, 12520, 12521, 12525, 12530, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57),
    array(3632, 3585, 3586, 3588, 3634, 3591, 3592, 3593, 3633, 3594, 3601, 3604, 3606, 3607, 3597, 3608, 3610, 3612, 3617, 3619, 3628, 3621, 3623, 3629, 3630, 3631, 3664, 3665, 3666, 3667, 3668, 3669, 3670, 3671, 3672, 3673),
    array(3760, 3713, 3714, 3716, 3779, 3719, 3720, 3722, 3780, 3725, 3732, 3735, 3737, 3738, 3782, 3740, 3742, 3745, 3746, 3747, 3773, 3751, 3754, 3755, 3757, 3759, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57),
    array(1366, 1330, 1331, 1332, 1333, 1336, 1337, 1338, 1339, 1341, 1343, 1344, 1345, 1347, 1365, 1351, 1352, 1354, 1357, 1358, 1349, 1359, 1360, 1361, 1362, 1363, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57),
    array(2437, 2444, 2453, 2454, 2447, 2455, 2457, 2458, 73, 2461, 2464, 2465, 2466, 2467, 79, 2468, 2469, 2470, 2472, 2474, 2451, 2476, 2477, 2479, 2482, 2489, 2534, 2535, 2536, 2537, 2538, 2539, 2540, 2541, 2542, 2543),
    array(2565, 2581, 2583, 2584, 2575, 2586, 2588, 2591, 73, 2592, 2595, 2596, 2598, 2599, 79, 2600, 2602, 2605, 2606, 2608, 2603, 2610, 2613, 2616, 2617, 2593, 2662, 2663, 2664, 2665, 2666, 2667, 2668, 2669, 2670, 2671),
    array(3928, 3904, 3905, 3906, 3940, 3908, 3909, 3910, 73, 3911, 3914, 3916, 3918, 3919, 79, 3921, 3923, 3924, 3926, 3934, 3941, 3935, 3937, 3938, 3939, 3942, 3872, 3873, 3874, 3875, 3876, 3877, 3878, 3879, 3880, 3881),

);

function to_ascii($str)
{
    $letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $result = '';
    $str = trim($str);
    $len = strlen($str);
    for ($i = 0; $i < $len;) {
        $c = ord($str[$i++]);

        if ($c == 38 && $str[$i] == '#') {
            $i++;
            $p = strpos(substr($str, $i), ';');
            if ($p !== false) {
                $c = intval(substr($str, $i++, $p));
                $i += $p;
            }
        } else {
            if ($c >= 128) {
                if ($c >= 224) {
                    if ($c >= 240) {
                        $c = '?';
                        $i += 2;
                    } else {
                        $c = ((($c & 15) << 6) + (ord($str[$i++]) & 63) << 6) + (ord($str[$i++]) & 63);
                    }
                } else {
                    $c = (($c & 63) << 6) + (ord($str[$i++]) & 63);
                }
            }
        }

        if ($c > 0 && $c < 127) {
            if ($c >= 97 && $c <= 122) {
                $c -= 32;
            }
            $result .= chr($c);
        } else {
            $found = 0;
            for ($lan = 0; $lan < MAPCODE_ALPHABETS_TOTAL; $lan++) {
                for ($j = 0; $j < 36; $j++) {
                    if ($c == $GLOBALS['asc2lan'][$lan][$j]) {
                        $result .= $letters[$j];
                        $found = 1;
                        break;
                    }
                }
                if ($found) {
                    break;
                }
            }
            if ($found == 0) {
                $result .= '?';
            }
        }
    }
    $p = strrpos($result,' '); if ($p===false) $p=0; else $p++;
    if ($result[$p] == 'A') {
        $result = substr($result,0,$p) . aeu_pack(aeu_unpack(substr($result,$p)));
    }
    return $result;
}

function showinlan($str, $lan, $asHTML)
{
    $str = to_ascii($str);
    if (!$lan) {
        return $str;
    }

    $result = '';

    $i = strpos($str, ' ');
    if ($i !== false) {
        $result = substr($str, 0, ++$i);
        $str = substr($str, $i);
    }

    if ($GLOBALS['asc2lan'][$lan][4] == 63) {
        if (strpos($str, 'E') !== false || strpos($str, 'U') !== false) {
            $str = aeu_pack(aeu_unpack($str), 1);
        }
    }

    {
        for ($i = 0; $i < strlen($str); $i++) {
            $c = ord($str[$i]);
            if ($c >= 65 && $c <= 93) {
                $c = $GLOBALS['asc2lan'][$lan][$c - 65];
            } else {
                if ($c >= 48 && $c <= 57) {
                    $c = $GLOBALS['asc2lan'][$lan][$c + 26 - 48];
                }
            }

            if ($asHTML) {
                $result .= '&#' . $c . ';';
            } else {
                if ($c >= 128) {
                    if ($c >= 2048) {
                        $result .= chr(224 + ($c >> 12)) . chr(128 + (($c >> 6) & 63)) . chr(128 + (($c) & 63));
                    } else {
                        $result .= chr(192 + ($c >> 6)) . chr(128 + ($c & 63));
                    }
                } else {
                    $result .= chr($c);
                }
            }
        }
    }
    return $result;
}

// returns coordinate, or 0
function master_decode($mapcode, $territoryNumber = -1)
{
    $mapcode = to_ascii($mapcode);
    $extensionchars = '';
    $minpos = strpos($mapcode, '-');
    if ($minpos !== false) {
        $extensionchars = substr($mapcode, $minpos + 1);
        $mapcode = trim(substr($mapcode, 0, $minpos));
    }

    $mapcode = aeu_unpack($mapcode);
    if ($mapcode == '') {
        return 0;
    }

    $mclen = strlen($mapcode);

    if ($mclen >= 10) {
        $territoryNumber = ccode_earth;
    }
    $parent = getParentOf($territoryNumber);
    if ($parent >= 0) {
        if ($mclen >= 9 || ($mclen >= 8 && ($parent == ccode_ind || $parent == ccode_mex))) {
            $territoryNumber = $parent;
        }
    }

    $from = dataFirstRecord($territoryNumber);
    $upto = dataLastRecord($territoryNumber);

    $prefixlength = strpos($mapcode, '.');
    $postfixlength = $mclen - 1 - $prefixlength;
    $incodex = $prefixlength * 10 + $postfixlength;

    $result = 0;
    for ($m = $from; $m <= $upto; $m++) {
        $codexm = Codex($m);

        if (($incodex == $codexm || ($incodex == 22 && $codexm == 21)) && recType($m) == 0 && isNameless($m) == 0) {

            $result = decodeGrid($mapcode, $extensionchars, $m);
            if ($result && isRestricted($m)) {
                $fitssomewhere = 0;
                for ($j = $upto - 1; $j >= $from; $j--) {
                    if (!isRestricted($j)) {
                        if (fitsInside($result, minmaxSetup($j))) {
                            $fitssomewhere = 1;
                            break;
                        }
                    }
                }

                if ($fitssomewhere == 0) { // FORCE_RECODE
                    for ($j = $from; $j < $m; $j++) { // try all smaller rectangles j
                      if (!isRestricted($j)) {
                        $plat = $result->lat;
                        $plon = $result->lon;

                        $mm = minmaxSetup($j);
                        $bminx = $mm->minx;
                        $bmaxx = $mm->maxx;                                    
                        if ($bmaxx < 0 && $plon > 0) {
                            $bminx += 360000000;
                            $bmaxx += 360000000;
                        }
                        
                        // force p in range
                        if ($plat < $mm->miny && $mm->miny <= $result->maxlat) { 
                            $plat = $mm->miny; 
                        }
                        if ($plat >= $mm->maxy && $mm->maxy > $result->minlat) { 
                            $plat = $mm->maxy - 0.000001; 
                        }
                        if ($plon < $bminx && $bminx <= $result->maxlon) { 
                            $plon = $bminx; 
                        }
                        if ($plon >= $bmaxx && $bmaxx > $result->minlon) { 
                            $plon = $bmaxx - 0.000001; 
                        }
                        // better?
                        if ( $plat > $result->minlat && $plat < $result->maxlat &&
                             $plon > $result->minlon && $plon < $result->maxlon &&
                                 $mm->miny <= $plat && $plat < $mm->maxy && 
                                 $bminx <= $plon && $plon < $bmaxx ) {
                            $result->lat = $plat;
                            $result->lon = $plon;
                            $fitssomewhere = 1;
                            break;                                        
                        }
                      }
                    }
                } //FORCE_RECODE

                if ($fitssomewhere == 0) {
                    $result = 0;
                }
            }
            break;
        } else {
            if ($codexm + 10 == $incodex && recType($m) == 1 && headerLetter($m) == $mapcode[0]) {
                $result = decodeGrid(substr($mapcode, 1), $extensionchars, $m);
                break;
            } else {
                if (isNameless($m) && (($codexm == 21 && $incodex == 22) || ($codexm == 22 && $incodex == 32) || ($codexm == 13 && $incodex == 23))) {
                    $result = decodeNameless($mapcode, $extensionchars, $m, $from);
                    break;
                } else {
                    if ($postfixlength == 3 && recType($m) > 1 && CodexLen($m) == $prefixlength + 2) {
                        $result = decodeAutoHeader($mapcode, $extensionchars, $m);
                        break;
                    } else {
                        $result = 0;
                    }
                }
            }
        }
    }

    if ($result) {
        if ($result->lon > 180000000) {
            $result->lon -= 360000000;
        } else {
            if ($result->lon < -180000000) {
                $result->lon += 360000000;
            }
        }

        if ($territoryNumber != ccode_earth) {
            if (!(fitsInsideWithRoom($result, minmaxSetup($upto)))) {
                return 0;
            }
            else { // FORCE_RECODE
                $mm = minmaxSetup($upto);
                if ($result->lat < $mm->miny) {
                    $result->lat = $mm->miny;
                }
                if ($result->lat>= $mm->maxy) {
                    $result->lat = $mm->maxy - 0.000001;
                }
                $bminx = $mm->minx;
                $bmaxx = $mm->maxx;
                if ($result->lon < 0 && $bminx > 0) {
                    $bminx -= 360000000;
                    $bmaxx -= 360000000;
                }
                if ($result->lon < $bminx) {
                    $result->lon = $bminx;
                }
                if ($result->lon>= $bmaxx) {
                    $result->lon = $bmaxx - 0.000001;
                }              
            } // FORCE_RECODE
        }
        $result->lon /= 1000000.0;
        $result->lat /= 1000000.0;
        if ($result->lat > 90) {
            $result->lat = 90;
        }
    }
	return $result;
}

function decode($mapcodeString, $territory = -1)
{
    $mapcodeString = trim($mapcodeString);
    $contextTerritoryNumber = getTerritoryNumber($territory);
    if ($contextTerritoryNumber < 0) {
        $contextTerritoryNumber = ccode_earth;
    }
    
    $p = strpos($mapcodeString, ' ');
    if ($p !== false) {
        $territory = substr($mapcodeString, 0, $p);
        if (isSubdivision($contextTerritoryNumber)) {
            $contextTerritoryNumber = getParentOf($contextTerritoryNumber);
        }
        $territoryNumber = getTerritoryNumber($territory, $contextTerritoryNumber);
        if ($territoryNumber >= 0) {
            return master_decode(substr($mapcodeString, $p + 1), $territoryNumber);
        }
    } else {
        return master_decode($mapcodeString, $contextTerritoryNumber);
    }
    return 0;
}

function encodeSixWide($x, $y, $width, $height)
{
    $D = 6;
    $col = (int)($x / 6);
    $maxcol = (int)(($width - 4) / 6);
    if ($col >= $maxcol) {
        $col = $maxcol;
        $D = $width - $maxcol * 6;
    }
    return ($height * 6 * $col) + ($height - 1 - $y) * $D + ($x - $col * 6);
}

function encodeBase31($value, $nrchars)
{
    $result = '';
    while ($nrchars-- > 0) {
        $result = encodeChar($value % 31) . $result;
        $value = (int)($value / 31);
    }
    return $result;
}

function encodeTriple($difx, $dify)
{
    if ($dify < 4 * 34) {
        return encodeChar((int)($difx / 28) + 6 * (int)($dify / 34)) . encodeBase31(($difx % 28) * 34 + ($dify % 34), 2);
    } else {
        return encodeChar((int)($difx / 24) + 24) . encodeBase31(($difx % 24) * 40 + ($dify - 136), 2);
    }
}

function encodeExtension($result, $enc, $extrax4, $extray, $dividerx4, $dividery, $extraDigits, $ydirection)
{
    if ($extraDigits <= 0) {
        return $result;
    }
    if ($extraDigits > 8) {
        $extraDigits = 8;
    }

    $factorx = 810000 * $dividerx4; // perfect integer!
    $factory = 810000 * $dividery; // perfect integer!
    $valx = (810000 * $extrax4) + $enc->fraclon; // perfect integer!
    $valy = (810000 * $extray ) + ($ydirection * $enc->fraclat); // perfect integer!

    // protect against floating point errors
    if ($valx<0) { $valx=0; } else if ($valx>=$factorx) { $valx=$factorx-1; }
    if ($valy<0) { $valy=0; } else if ($valy>=$factory) { $valy=$factory-1; }

    $result .= '-';

    while ($extraDigits-- > 0) {
        $factorx /= 30;
        $gx = (int)($valx / $factorx);
        $valx -= $factorx * $gx;

        $factory /= 30;
        $gy = (int)($valy / $factory);
        $valy -= $factory * $gy;

        $column1 = (int)($gx / 6);
        $column2 = ($gx % 6);
        $row1 = (int)($gy / 5);
        $row2 = ($gy % 5);
        $result .= encodeChar($row1 * 5 + $column1);
        if ($extraDigits-- > 0) {
            $result .= encodeChar($row2 * 6 + $column2);
        }
    }
    return $result;
}

function encodeGrid($enc, $m, $mm, $headerletter, $extraDigits)
{
    $orgcodex = Codex($m);
    $codexm = $orgcodex;
    if ($codexm == 21) {
        $codexm = 22;
    }

    if ($codexm == 14) {
        $codexm = 23;
    }

    $prefixlength = (int)($codexm / 10);
    $postfixlength = ($codexm % 10);

    $divy = smartdiv($m);
    if ($divy == 1) {
        $divx = $GLOBALS['xside'][$prefixlength];
        $divy = $GLOBALS['yside'][$prefixlength];
    } else {
        $divx = (int)($GLOBALS['nc'][$prefixlength] / $divy);
    }
    $ygridsize = (int)(($mm->maxy - $mm->miny + $divy - 1) / $divy);
    $rely = $enc->coord32->lat - $mm->miny;
    $rely = (int)($rely / $ygridsize);
    $xgridsize = (int)(($mm->maxx - $mm->minx + $divx - 1) / $divx);
    $x = $enc->coord32->lon;
    $relx = $x - $mm->minx;
    if ($relx < 0) {
        $x += 360000000;
        $relx += 360000000;
    } else {
        if ($relx >= 360000000) {
            $x -= 360000000;
            $relx -= 360000000;
        }
    }
    if ($relx < 0) {
        return '';
    }
    $relx = (int)($relx / $xgridsize);
    if ($relx >= $divx) {
        return '';
    }

    if ($divx != $divy && $prefixlength > 2) {
        $v = encodeSixWide($relx, $rely, $divx, $divy);
    } else {
        $v = $relx * $divy + ($divy - 1 - $rely);
    }
    $result = encodeBase31($v, $prefixlength);

    if ($prefixlength == 4 && $divx == 961 && $divy == 961) {
        $result = $result[0] . $result[2] . $result[1] . $result[3];
    }

    $rely = $mm->miny + ($rely * $ygridsize);
    $relx = $mm->minx + ($relx * $xgridsize);

    $dividery = (int)(((($ygridsize)) + $GLOBALS['yside'][$postfixlength] - 1) / $GLOBALS['yside'][$postfixlength]);
    $dividerx = (int)(((($xgridsize)) + $GLOBALS['xside'][$postfixlength] - 1) / $GLOBALS['xside'][$postfixlength]);

    $result .= '.';

    $difx = $x - $relx;
    $dify = $enc->coord32->lat - $rely;
    $extrax = $difx % $dividerx;
    $extray = $dify % $dividery;
    $difx = (int)($difx / $dividerx);
    $dify = (int)($dify / $dividery);

    $dify = $GLOBALS['yside'][$postfixlength] - 1 - $dify;

    if ($postfixlength == 3) {
        $result .= encodeTriple($difx, $dify);
    } else {
        $postfix = encodeBase31($difx * $GLOBALS['yside'][$postfixlength] + $dify, $postfixlength);
        if ($postfixlength == 4) {
            $postfix = $postfix[0] . $postfix[2] . $postfix[1] . $postfix[3];
        }
        $result .= $postfix;
    }

    if ($orgcodex == 14) {
        $result = $result[0] . '.' . $result[1] . substr($result, 3);
    }

    return encodeExtension(($headerletter . $result), $enc, $extrax << 2, $extray, $dividerx << 2, $dividery, $extraDigits, 1);
}

function encodeNameless($enc, $m, $firstcode, $extraDigits)
{
    $A = countNamelessRecords($m, $firstcode);
    if ($A < 1) {
        return '';
    }
    $p = (int)(31 / $A);
    $r = (31 % $A);
    $codex = Codex($m);
    $codexlen = CodexLen($m);
    $X = $m - firstNamelessRecord($m, $firstcode);

    if ($codex != 21 && $A <= 31) {
        $storage_offset = ($X * $p + ($X < $r ? $X : $r)) * (961 * 961);
    } else {
        if ($codex != 21 && $A < 62) {
            if ($X < (62 - $A)) {
                $storage_offset = $X * (961 * 961);
            } else {
                $storage_offset = (62 - $A + (int)(($X - 62 + $A) / 2)) * (961 * 961);
                if (($X + $A) & 1) {
                    $storage_offset += (16 * 961 * 31);
                }
            }
        } else {
            $BASEPOWER = ($codex == 21) ? 961 * 961 : 961 * 961 * 31;
            $BASEPOWERA = (int)($BASEPOWER / $A);
            if ($A == 62) {
                $BASEPOWERA++;
            } else {
                $BASEPOWERA = (961) * (int)($BASEPOWERA / 961);
            }

            $storage_offset = $X * $BASEPOWERA;
        }
    }

    $mm = minmaxSetup($m);
    $SIDE = smartdiv($m);
    $orgSIDE = $SIDE;
    $XSIDE = $SIDE;
    if (isSpecialShape($m)) {
        $XSIDE *= $SIDE;
        $SIDE = 1 + (int)(($mm->maxy - $mm->miny) / 90);
        $XSIDE = (int)($XSIDE / $SIDE);
    }

    $dividerx4 = xDivider4($mm->miny, $mm->maxy);
    $xFracture = (int)(4 * $enc->fraclon / 3240000);
    $dx = (int)((4 * ($enc->coord32->lon - $mm->minx) + $xFracture) / $dividerx4);
    $extrax4 = ($enc->coord32->lon - $mm->minx) * 4 - $dx * $dividerx4;

    $dividery = 90;
    $dy = (int)(($mm->maxy - $enc->coord32->lat) / $dividery);
    $extray = ($mm->maxy - $enc->coord32->lat) % $dividery;

    if ($extray == 0 && $enc->fraclat > 0) {
        $dy--;
        $extray += $dividery;
    }

    $v = $storage_offset;
    if (isSpecialShape($m)) {
        $v += encodeSixWide($dx, $SIDE - 1 - $dy, $XSIDE, $SIDE);
    } else {
        $v += ($dx * $SIDE + $dy);
    }

    $result = encodeBase31($v, $codexlen + 1);

    if ($codexlen == 3) {
        $result = substr($result, 0, 2) . '.' . substr($result, 2);
    } else {
        if ($codexlen == 4) {
            if ($codex == 22 && $orgSIDE == 961 && !isSpecialShape($m)) {
                $result = $result[0] . $result[1] . $result[3] . '.' . $result[2] . $result[4];
            } else {
                if ($codex == 13) {
                    $result = substr($result, 0, 2) . '.' . substr($result, 2);
                } else {
                    $result = substr($result, 0, 3) . '.' . substr($result, 3);
                }
            }
        }
    }

    return encodeExtension($result, $enc, $extrax4, $extray, $dividerx4, $dividery, $extraDigits, -1);
}

function encodeAutoHeader($enc, $m, $extraDigits)
{
    $STORAGE_START = 0;

    $codex = Codex($m);
    $codexlen = CodexLen($m);
    $firstindex = $m;
    while (recType($firstindex - 1) > 1 && Codex($firstindex - 1) == $codex) {
        $firstindex--;
    }

    for ($i = $firstindex; Codex($i) == $codex; $i++) {
        $mm = minmaxSetup($i);
        $H = (int)(($mm->maxy - $mm->miny + 89) / 90);
        $xdiv = xDivider4($mm->miny, $mm->maxy);
        $W = (int)((($mm->maxx - $mm->minx) * 4 + ($xdiv - 1)) / $xdiv);

        $H = 176 * (int)(($H + 176 - 1) / 176);
        $W = 168 * (int)(($W + 168 - 1) / 168);

        $product = (int)($W / 168) * (int)($H / 176) * 961 * 31;

        if (recType($i) == 2) {
            $GOODROUNDER = $codex >= 23 ? (961 * 961 * 31) : (961 * 961);
            $product = (int)(($STORAGE_START + $product + $GOODROUNDER - 1) / $GOODROUNDER) * $GOODROUNDER - $STORAGE_START;
        }

        if ($i == $m && fitsInside($enc->coord32, $mm)) {
            $dividerx = (int)(($mm->maxx - $mm->minx + $W - 1) / $W);
            $vx = (int)(($enc->coord32->lon - $mm->minx) / $dividerx);
            $extrax = (($enc->coord32->lon - $mm->minx) % $dividerx);

            $dividery = (int)(($mm->maxy - $mm->miny + $H - 1) / $H);
            $vy = (int)(($mm->maxy - $enc->coord32->lat) / $dividery);
            $extray = (($mm->maxy - $enc->coord32->lat) % $dividery);

            $spx = $vx % 168;            
            $vx = (int)($vx / 168);
            $value = $vx * (int)($H / 176);

            if ($extray == 0 && $enc->fraclat > 0) {
                $vy--;
                $extray += $dividery;
            }

            $spy = $vy % 176;
            $vy = (int)($vy / 176);
            $value += $vy;

            $mapc = encodeBase31((int)($STORAGE_START / (961 * 31)) + $value, $codexlen - 2) . '.' . encodeTriple($spx, $spy);
            return encodeExtension($mapc, $enc, $extrax << 2, $extray, $dividerx << 2, $dividery, $extraDigits, -1);
        }
        $STORAGE_START += $product;
    }
    return '';
}

$debugStopRecord = -1;
function mapcoderEngine($enc, $tn, $getshortest, $isrecursive, $state_override, $extraDigits)
{
    $dsr = $GLOBALS['debugStopRecord'];
    $results = array();
    $use_redivar=0;

    $fromRun = 0;
    $uptoRun = ccode_earth;
    if (is_numeric($tn) && $tn >= 0 && $tn <= $uptoRun) {
        $fromRun = $tn;
        $uptoRun = $tn;
    }
    else if ($GLOBALS[redivar])
    {
      $use_redivar=1;
      $HOR = 1;
      $i = 0; // pointer into redivar
      for (;;) {
          $v2 = $GLOBALS[redivar][$i++];
          $HOR = 1 - $HOR;
          if ($v2 >= 0 && $v2 < 1024) { // leaf?
              $fromRun = $i;
              $uptoRun = $i + $v2;
              break;
          }
          else {
              $coord = ($HOR ? $enc->coord32->lon : $enc->coord32->lat);
              if ($coord > $v2) {
                  $i = $GLOBALS[redivar][$i];
              }
              else {
                  $i++;
              }
          }
      }
    }

    $debugStopFailed = 1;
    for ($run = $fromRun; $run <= $uptoRun; $run++) {        
        if ($use_redivar)
          $territoryNumber = ($run == $uptoRun ? ccode_earth : $GLOBALS[redivar][$run]);
        else 
          $territoryNumber = $run;
        
        $original_length = count($results);
        $from = dataFirstRecord($territoryNumber);
        $upto = dataLastRecord($territoryNumber);

        if (!(fitsInside($enc->coord32, minmaxSetup($upto)))) {
            if ($isrecursive) {
                return $results;
            }
            continue;
        }

        for ($i = $from; $i <= $upto; $i++) {

            if (Codex($i) < 54) {
                $mm = minmaxSetup($i);
                if (fitsInside($enc->coord32, $mm)) {
                    if (isNameless($i)) {
                        $r = encodeNameless($enc, $i, $from, $extraDigits);
                    } else {
                        if (recType($i) > 1) {
                            $r = encodeAutoHeader($enc, $i, $extraDigits);
                        } else {
                            if (isRestricted($i) && $i == $upto && getParentOf($territoryNumber) >= 0) {
                                $results = array_merge($results, mapcoderEngine($enc, getParentOf($territoryNumber), $getshortest, 1/*recursive*/, $territoryNumber, $extraDigits));
                                continue;
                            } else {
                                if (isRestricted($i) && count($results) == $original_length) {
                                    $r = '';
                                } else {
                                    $r = encodeGrid($enc, $i, $mm, headerLetter($i), $extraDigits);
                                }
                            }
                        }
                    }

                    if (strlen($r) > 4) {
                        $storecode = $territoryNumber;
                        if ($state_override >= 0) {
                            $storecode = $state_override;
                        }
                        $name = getTerritoryAlphaCode($storecode);

                        $r = aeu_pack($r);

                        if ($dsr == $i) {
                            $debugStopFailed = 0;
                            $results = array();
                        }

                        array_push($results, ($storecode == ccode_earth ? '' : $name . ' ') . $r);

                        if ($getshortest || $dsr == $i) {
                            break;
                        }
                    } else {
                    }
                }
            }
        }
    }

    if ($dsr >= 0 && $debugStopFailed != 0) {
        $results = array();
    }

    return $results;
}

function distanceInMeters($latDeg1, $lonDeg1, $latDeg2, $lonDeg2)
{
    $worstParallel = 0;
    if ($latDeg1 > $latDeg2) {
        if ($latDeg1 < 0) {
            $worstParallel = $latDeg2;
        } else {
            if ($latDeg2 > 0) {
                $worstParallel = $latDeg1;
            }
        }
    } else {
        if ($latDeg2 < 0) {
            $worstParallel = $latDeg1;
        } else {
            if ($latDeg1 > 0) {
                $worstParallel = $latDeg2;
            }
        }
    }
    $dy = ($latDeg2 - $latDeg1);
    if ($lonDeg1 < 0 && $lonDeg2 > 1) { $lonDeg1 += 360; }
    if ($lonDeg2 < 0 && $lonDeg1 > 1) { $lonDeg2 += 360; }
    $dx = ($lonDeg2 - $lonDeg1) * cos(deg2rad($worstParallel));
    return sqrt($dx * $dx + $dy * $dy) * 1000000.0 / 9.0;
}

/// PUBLIC convert a mapcode (without territory abbreviation) into a particular alphabet
/// targetAlphabet: 0=roman, 1=greek etc.
function convertToAlphabet($mapcode, $targetAlphabet)
{
    return showinlan($mapcode, $targetAlphabet, false);
}

/// PUBLIC convert a mapcode (without territory abbreviation) into an HTML-displayable string in a particular alphabet
/// targetAlphabet: 0=roman, 1=greek etc.
function convertToAlphabetAsHTML($mapcode, $targetAlphabet)
{
    return showinlan($mapcode, $targetAlphabet, true);
}

function encodeWithPrecision($latitudeDegrees, $longitudeDegrees, $precision, $territory = -1)
{
    return mapcoderEngine(new EncodeRec($latitudeDegrees, $longitudeDegrees), getTerritoryNumber($territory), 0/*$getshortest*/, 0/*recursive*/, -1/*override*/, $precision);
}

function encode($latitudeDegrees, $longitudeDegrees, $territory = -1)
{
    return encodeWithPrecision($latitudeDegrees, $longitudeDegrees, 0, $territory);
}

function encodeInternational($latitudeDegrees, $longitudeDegrees)
{
    return encodeWithPrecision($latitudeDegrees, $longitudeDegrees, 0, ccode_earth);
}

function encodeInternationalWithPrecision($latitudeDegrees, $longitudeDegrees, $precision)
{
    return encodeWithPrecision($latitudeDegrees, $longitudeDegrees, $precision, ccode_earth);
}

function encodeShortestWithPrecision($latitudeDegrees, $longitudeDegrees, $precision, $territory = -1)
{
    return mapcoderEngine(new EncodeRec($latitudeDegrees, $longitudeDegrees), getTerritoryNumber($territory), 1/*$getshortest*/, 0/*recursive*/, -1/*override*/, $precision);
}

function encodeShortest($latitudeDegrees, $longitudeDegrees, $territory = -1)
{
    return encodeShortestWithPrecision($latitudeDegrees, $longitudeDegrees, 0, $territory);
}

?>


