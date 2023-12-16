<!DOCTYPE html>
<html>
<head>
    <title>Mapcode PHP Test</title>
</head>
<body>

<script>
    function progress(id, x, total) {
        var e = document.getElementById(id);
        if (e) e.innerHTML = x + '/' + total + ' ' + Math.floor((x * 100.0) / total);
        // @@@ e.innerHTML = '<A HREF="unittest.php?start=' + 99999 + '&edge=' + x + '">' + x + '</A>';
    }
</script>

<?php

/*
 * Copyright (C) 2014-2020 Stichting Mapcode Foundation (http://www.mapcode.com)
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

$redivar = array();

require_once '../src/mapcode.php';
require_once '../src/mapcode_fast_encode.php';
require_once 'test_territories.php';
require_once 'test_encodes.php';

use function Mapcode\convertToAlphabet;
use function Mapcode\encodeWithPrecision;
use function Mapcode\decode;
use function Mapcode\distanceInMeters;
use function Mapcode\maxErrorinMeters;
use function Mapcode\getParentOf;
use function Mapcode\multipleBordersNearby;
use function Mapcode\getTerritoryNumber;
use function Mapcode\getTerritoryAlphaCode;
use function Mapcode\dataLastRecord;
use function Mapcode\minmaxSetup;

ini_set('max_execution_time', 1200);
set_time_limit(1200);

echo "Mapcode Unittest version 2.2<BR>";
echo "Mapcode PHP version " . mapcode_phpversion . "<BR>";
echo "Mapcode DATA version " . mapcode_dataversion . "<BR>";
if ($redivar) echo "Mapcode fast_encode loaded<BR>";

// globals to count tests, errors and warnings
$nrTests = 0;
$nrErrors = 0;

// test the alphabet conversion routines 
function alphabet_tests()
{
    echo MAPCODE_ALPHABETS_TOTAL . ' alphabets<BR>';

    for ($i = 0; $i < MAPCODE_ALPHABETS_TOTAL; $i++) {

        // see if convertToAlphabet survives empty string
        $GLOBALS['nrTests']++;
        $str = "";
        $enc = convertToAlphabet($str, $i);
        if ($enc != "") {
            $GLOBALS['nrErrors']++;
            echo 'convertToAlphabet("' . $str . '".' . $i . ') != empty<BR>';
        }

        // see if alphabets (re)convert as expected
        $str = "OEUoi OIoi#%?-.abcdfghjklmnpqrstvwxyz0123456789ABCDFGHJKLMNPQRSTVWXYZ";
        $expect = "OEUOI OIOI#%?-.ABCDFGHJKLMNPQRSTVWXYZ0123456789ABCDFGHJKLMNPQRSTVWXYZ";
        $enc = convertToAlphabet($str, $i);
        $dec = convertToAlphabet($enc, 0);
        $GLOBALS['nrTests']++;
        if ($dec != $expect) {
            $GLOBALS['nrErrors']++;
            echo 'convertToAlphabet(convertToAlphabet("' . $str . '".' . $i . '),0)="' . $dec . '". expected "' . $expect . '"<BR>';
        }

        // see if E/U voweled mapcodes (re)convert as expected
        $str = "OMN 112.3EU";
        $dec = convertToAlphabet(convertToAlphabet($str, $i), 0);
        $GLOBALS['nrTests']++;
        if ($dec != $str) {
            $GLOBALS['nrErrors']++;
            echo 'convertToAlphabet(convertToAlphabet("' . $str . '".' . $i . '),0)="' . $dec . '". expected "' . $str . '"<BR>';
        }
    }
}

function printGeneratedMapcodes($r)
{
    $n = count($r);
    echo ' &nbsp; Delivered: ' . $n . ' results:';
    for ($i = 0; $i < $n; $i++)
        echo ' (' . $r[$i] . ')';
    echo '<BR>';
}

// perform an encode/decode test

function test_encode_decode($str, $y, $x, $localsolutions, $globalsolutions)
{
    if ($GLOBALS['nrErrors'] > 20) return;
    $nrt = $GLOBALS['nrTests'];

    $str = trim($str);
    $j = strpos($str, ' ');
    if ($j > 0) $territory = substr($str, 0, $j); else $territory = "AAA";

    // encode globally
    $precision = 2;
    $r = encodeWithPrecision($y, $x, $precision);
    $n = count($r);

    // test if correct nr of global solutions (if requested)
    if ($globalsolutions) {
        $nrt++;
        if ($n != $globalsolutions) {
            $GLOBALS['nrErrors']++;
            echo '*** ERROR *** encode(' . number_format($y, 8) . ' . ' . number_format($x, 8) . ') does not deliver ' . $n . ' solutions<BR>';
            printGeneratedMapcodes($r);
        }
    }

    if ($localsolutions || strlen($str) > 0) {
        // count local solutions, look for expected solution
        $found = 0;
        $nrlocal = 0;
        for ($i = 0; $i < $n; $i++) {
            if (strpos($r[$i], $territory . ' ') === 0) {
                $nrlocal++;
                if (strpos($r[$i], $str) === 0)
                    $found = 1;
            }
        }

        // test that EXPECTED solution is there (if requested)
        if (strlen($str)) {
            $nrt++;
            if ($found == 0) {
                $GLOBALS['nrErrors']++;
                echo '*** ERROR *** encode(' . number_format($y, 14) . ' . ' . number_format($x, 14) . ' . "' . $territory . '" ) does not deliver "' . $str . '"<BR>';
                printGeneratedMapcodes($r);
            }
        }

        // test if correct nr of local solutions (if requested)
        if ($localsolutions) {
            $nrt++;
            if ($nrlocal != $localsolutions) {
                $GLOBALS['nrErrors']++;
                echo '*** ERROR *** encode(' . number_format($y, 14) . ' . ' . number_format($x, 14) . ' . "' . $territory . '" ) does not deliver ' . $localsolutions . ' solutions<BR>';
                printGeneratedMapcodes($r);
            }
        }
    }

    for ($precision = 0; $precision <= 0; $precision++) {
        $r = encodeWithPrecision($y, $x, $precision);
        $n = count($r);
        // check that all global solutions are within 9 milimeters of coordinate
        for ($i = 0; $i < $n; $i++) {
            $nrt++;
            $str = $r[$i];
            // check if every solution decodes
            $p = decode($str);
            if (!$p) {
                $GLOBALS['nrErrors']++;
                echo '*** ERROR *** decode(' . $str . ') = no result. expected ~(' . number_format($y, 14) . ' . ' . number_format($x, 14) . ')<BR>';
            } else {
                // check if decode of $str is sufficiently close to the encoded coordinate
                $dm = distanceInMeters($y, $x, $p->lat, $p->lon);
                $maxerror = maxErrorinMeters($precision);
                if ($dm > $maxerror) {
                    $GLOBALS['nrErrors']++;
                    echo '*** ERROR *** decode(' . $str . ') = (' . number_format($p->lat, 14) . ' , ' . number_format($p->lon, 14) . ') which is ' . number_format($dm * 100, 2) . ' cm away (>' . ($maxerror * 100) . ' cm) from (' . number_format($y, 14) . ', ' . number_format($x, 14) . ')<BR>';
                } else {
                    // see if decode encodes back to the same solution
                    $j = strpos($str, ' ');
                    if ($j > 0) $territory = substr($str, 0, $j); else $territory = "AAA";

                    $r3 = '';
                    $r2 = encodeWithPrecision($p->lat, $p->lon, $precision, $territory);
                    $n2 = count($r2);
                    $found = 0;
                    for ($i2 = 0; $i2 < $n2; $i2++) {
                        if ($r2[$i2] == $str) {
                            $found = 1;
                            break;
                        }
                    }
                    // or, if inherited from parent country: the same parent solution
                    if (!$found) {
                        $parent = getParentOf($territory);
                        if ($parent >= 0) {
                            $proper = substr($str, strpos($str, " "));
                            $r3 = encodeWithPrecision($p->lat, $p->lon, $precision, $parent);
                            $n3 = count($r3);
                            for ($i3 = 0; $i3 < $n3; $i3++) {
                                $r3proper = substr($r3[$i3], strpos($r3[$i3], " "));
                                if ($r3proper == $proper) {
                                    $found = 1;
                                    break;
                                }
                            }
                        }
                    }
                    if (!$found && !multipleBordersNearby($p, $territory)) {
                        echo '*** ERROR *** decode(' . $str . ') = (' . number_format($p->lat, 14) . ', ' . number_format($p->lon, 14) . ', ' . $territory . ') does not re-encode from (' . number_format($y, 14) . ', ' . number_format($x, 14) . ')<BR>';
                        echo 'Globals:';
                        printGeneratedMapcodes($r);
                        echo 'Decoded:';
                        printGeneratedMapcodes($r2);
                        echo 'Parents:';
                        printGeneratedMapcodes($r3);
                        $GLOBALS['nrErrors']++;
                    }
                }
            }
        }
    }

    $GLOBALS['nrTests'] = $nrt;
}

// test strings that are expected to FAIL a decode
function test_failing_decodes()
{
    $badcodes = array(
        "",              // empty
        "NLD 00.00",     // all-digits
        "12345.6789",    // all-digits
        "12345.6789-X",  // all-digits
        "GGG XX.XX",     // unknown country
        "GGG-GG XX.XX",  // unknown country
        "NLDX XX.XX",    // unknown/long country
        "NLDNLDNLD XX.XX", // unknown/long country
        "USAUSA-CA XX.XX", // unknown/long country
        "USA-CACA XX.XX",  // unknown/long state
        "US-CACACA XX.XX", // unknown/long state
        "US-US XX00.XX00",     // parent as state
        "US-RU XX00.XX00",     // parent as state
        "CA-CA XX00.XX00",     // state as country
        "US-GG XX.XX",   // unknown state (anywhere)
        "RU-CA XX.XX",   // unknown state (in RU)
        "RUS-CA XX.XX",  // unknown state (in RUS)
        "NLD-CA XX.XX",  // unknown state (NL has none)
        "NLD X.XXX",     // short prefix
        "NLD XXXXXX.XX", // long prefix
        "NLD XXX.X",     // short postfix
        "NLD XXX.XXXXX", // long postfix
        "NLD XXXXX.XXX", // invalid codex 5+3
        "NLD XXXX.XXXX", // non-existing codex in NLD
        "NLD XXXX",      // no dot
        "NLD XXXXX",     // no dot
        "NLD XXX.",      // no postfix
        "NLD .XXX",      // no prefix
        "AAA x234.6789", // too short for AAA
        "x234.6789",     // too short for AAA

        "NLD XXX..XXX",  // 2 dots
        "NLD XXX.XX.X",  // 2 dots

        "NLD XX.XX-Z",   // Z in extension
        "NLD XX.XX-1Z",  // Z in extension
        "NLD XX.XX-X-",  // 2nd -
        "NLD XX.XX-X-X", // 2nd -

        // "NLD XXX.XXX-",  // empty extension ALLOWED!

        "NLD XX.XX-123456789", // extension too long
        "NLD XXX.#XX",   // invalid char
        "NLD XXX.UXX",   // invalid char
        "NLD 123.A45",   // A in invalid position
        "NLD 123.E45",   // E in invalid position
        "NLD 123.U45",   // U in invalid position
        "NLD 123.1UE",   // UE illegal vowel-encode
        "NLD 123.1UU",   // UU illegal
        "NLD x23.1A0",   // A0 with nondigit
        "NLD 1x3.1A0",   // A0 with nondigit
        "NLD 12x.1A0",   // A0 with nondigit
        "NLD 123.xA0",   // A0 with nondigit
        "NLD 123.1U#",   // U#

        "NLD ZZ.ZZ",     // nameless out of range
        "NLD Q000.000",  // grid out of range
        "NLD ZZZ.ZZZ",   // grid out of range
        "NLD L222.222",  // grid out of range (restricted)
        "end"
    );

    for ($i = 0; ; $i++) {
        $str = $badcodes[$i];
        if ($str == "end")
            break;

        $GLOBALS['nrTests']++;
        $p = decode($str);
        if ($p) {
            $GLOBALS['nrErrors']++;
            echo '*** ERROR *** invalid mapcode "' . $str . '" decodes without error (' . number_format($p->lat, 14) . ',' . number_format($p->lon, 14) . ')<BR>';
        }
    }
}

// perform tests on alphacodes (used from test_territories.php)
function test_territory($alphacode, $tc, $isAlias, $needsParent, $tcParent)
{
    $ccode = $tc - 1;

    // test internal getTerritoryNumber (recognise alphacode as $ccode)
    $GLOBALS['nrTests']++;
    $tn = getTerritoryNumber($alphacode, $needsParent ? ($tcParent - 1) : 0);
    if ($tn != $ccode) {
        $GLOBALS['nrErrors']++;
        echo '*** ERROR *** getTerritoryNumber(' . $alphacode . '.' . ')=' . $tn . ' but expected ' . $ccode . '<BR>';
    }

    // also test that alphacode is generated (unless it is ambigious or an alias)
    if ($needsParent == 0 && $isAlias == 0 && (strlen($alphacode) <= 3 || $alphacode[3] != '-')) {
        $GLOBALS['nrTests']++;
        $nam = getTerritoryAlphaCode($ccode);
        // either perfect match, or "something-alphacode"
        if ($nam != $alphacode && strpos($nam, "-" . $alphacode) === false) {
            $GLOBALS['nrErrors']++;
            echo '*** ERROR *** getTerritoryAlphaCode(' . $ccode . ')="' . $nam . '" which does not equal or contain "' . $alphacode . '"<BR>';
        }
    }
}

// perform encode/decode tests using the encode_testdata array
function test_encodes()
{
    // count nr of tests
    $t = $GLOBALS['encodes_testdata'];
    $n = 0;
    while ($t[$n * 5] !== false) $n++;

    // executed (optionally, from "start" parameter)
    if (array_key_exists("start", $_GET)) {
        $i = intval($_GET["start"]) - 1;
    } else {
        $i = 0;
    }
    
    if ($i < 0) $i = 0;
    $nextlevel = $i;
    while ($i <= $n) {
        if ($i >= $nextlevel) {
            echo '<script>progress("prog2",' . $i . ',' . $n . ');</script>';
            if ($i == $n) break;
            $iterations = 100;
            $nextlevel = $iterations * (1 + floor($i / $iterations));
            if ($nextlevel > $n) $nextlevel = $n;
        }
        test_encode_decode($t[5 * $i], $t[5 * $i + 1], $t[5 * $i + 2], $t[5 * $i + 3], $t[5 * $i + 4]);
        $i++;
    }
}

function distance_tests()
{
    if (mapcode_phpversion >= '2.1.1') {
        $coordpairs = array(
            // lat1, lon1, lat2, lon2, expected distance * 100000
            // lat1, lon1, lat2, lon2, expected distance * 100000
            1, 1, 1, 1, 0,
            0, 0, 0, 1, 11131949079,
            89, 0, 89, 1, 194279300,
            3, 0, 3, 1, 11116693130,
            -3, 0, -3, 1, 11116693130,
            -3, -179.5, -3, 179.5, 11116693130,
            -3, 179.5, -3, -179.5, 11116693130,
            3, 8, 3, 9, 11116693130,
            3, -8, 3, -9, 11116693130,
            3, -0.5, 3, 0.5, 11116693130,
            54, 5, 54.000001, 5, 11095,
            54, 5, 54, 5.000001, 6543,
            54, 5, 54.000001, 5.000001, 12880,
            90, 0, 90, 50, 0,
            0.11, 0.22, 0.12, 0.2333, 185011466,
            -1);

        for ($i = 0; $coordpairs[$i] != -1; $i += 5) {
            $GLOBALS['nrTests']++;
            $distance = distanceInMeters(
                $coordpairs[$i], $coordpairs[$i + 1],
                $coordpairs[$i + 2], $coordpairs[$i + 3]);
            if (floor(0.5 + (100000.0 * $distance)) != $coordpairs[$i + 4]) {
                $GLOBALS['nrErrors']++;
                echo '*** ERROR *** distanceInMeters ' . $i . ' failed: ' . $distance . '<BR>';
            }
        }
    }
}


function territory_code_tests()
{
    $testdata = array(
        // expected answer, context, string
        "BR-AL", "BRA", "AL",
        -1, -1, "",
        -1, -1, "R",
        -1, -1, "RX",
        -1, -1, "RXX",
        "RUS", -1, "RUS",
        -1, -1, "RUSSIA",
        "USA", -1, "US",
        "USA", -1, "USA",
        "USA", -1, "usa",
        "USA", -1, "   usa   ",
        -1, -1, "999",
        -1, -1, "-44 33",
        -1, -1, "666",
        "USA", -1, "410",
        "USA", -1, "  410  ",
        "USA", -1, "410 MORE TEXT",
        "USA", -1, "US CA",
        -1, -1, "US-TEST",
        "USA", -1, "US OTHER TEXT",
        "USA", -1, "   US OTHER TEXT   ",
        "US-CA", -1, "US-CA",
        "US-CA", -1, "US-CA OTHER TEXT",
        "US-CA", -1, "USA-CA",
        "RU-TT", -1, "RUS-TAM",
        -1, -1, "RUS-TAMX",
        "RU-TT", -1, "RUS-TAM X OTHER TEXT",
        "RU-AL", "rus", "AL",
        "RU-AL", "RUS", "AL",
        "RU-AL", "ru-tam", "AL",
        "RU-AL", "RU-TAM", "AL",
        "US-AL", "US", "AL",
        "US-AL", "US-CA", "AL",
        -9);

    for ($i = 0; $testdata[$i] != -9; $i += 3) {
        $tc = getTerritoryAlphaCode(getTerritoryNumber($testdata[$i + 2], $testdata[$i + 1]));
        $GLOBALS['nrTests']++;
        if ($tc != $testdata[$i]) {
            $GLOBALS['nrErrors']++;
            echo '*** ERROR *** getTerritoryNumber("' . $testdata[$i + 2] . '", ' . $testdata[$i + 1] . ')=' . $tc . ', expected ' . $testdata[$i] . '<BR>';
        }
    }
}


$next_corner_to_test = 0;
function test_corner_encodes()
{
    $tests_per_timeslot = 20;
    $last = dataLastRecord(ccode_earth);
    $m = $GLOBALS['next_corner_to_test'];
    echo '<script>progress("prog1",' . $m . ',' . $last . ');</script>';
    for (; $m < $last; $m++) {
        if ($GLOBALS['nrErrors'] > 20) {
            echo 'Too many errors!<BR>';
            return 0;
        }
        if ($tests_per_timeslot-- == 0) {
            $GLOBALS['next_corner_to_test'] = $m;
            return 1;
        }
        $mm = minmaxSetup($m);
        // center
        test_encode_decode("", ($mm->miny + $mm->maxy) / 2000000, ($mm->minx + $mm->maxx) / 2000000, 0, 0);
        // corner just inside
        test_encode_decode("", $mm->miny / 1000000.0, $mm->minx / 1000000.0, 0, 0);
        // corner just outside y
        test_encode_decode("", ($mm->miny - 0.000001) / 1000000.0, ($mm->minx) / 1000000.0, 0, 0);
        // corner just outside x
        test_encode_decode("", ($mm->miny) / 1000000.0, ($mm->minx - 0.000001) / 1000000.0, 0, 0);
        // corner opposite just inside
        test_encode_decode("", ($mm->maxy - 0.000001) / 1000000.0, ($mm->maxx - 0.000001) / 1000000.0, 0, 0);
        // corner opposite just outside
        test_encode_decode("", ($mm->maxy) / 1000000.0, ($mm->maxx) / 1000000.0, 0, 0);
    }
    echo '<script>progress("prog1",' . $last . ',' . $last . ');</script>';
    return 0;
}


///////////////////////////////////////////////
echo '<HR>Character tests<BR>';
alphabet_tests();

echo '<HR>Distance tests<BR>';
distance_tests();

echo '<HR>Territory tests<BR>';
echo MAX_CCODE . " territories<BR>";
territory_code_tests();
test_territories(); // uses test_territory()

echo '<HR>Decode fail tests<BR>';
test_failing_decodes();

echo '<HR>Encode/Decode tests <font id="prog2">0</font>%<BR>';
test_encodes(); // uses test_encode_decode()

echo '<HR>Edge encode/decode tests <font id="prog1">0</font>%<BR>';
{
    if (array_key_exists("edge", $_GET)) {
        $i = intval($_GET["edge"]);
    } else {
        $i = 0;
    }
    if ($i > 0) $GLOBALS['next_corner_to_test'] = $i;
    while (test_corner_encodes()) ;
}

/**/
echo '<HR>Done.<BR>';
echo ' Executed ', $nrTests, ' tests, found ', $nrErrors, ' errors<P>';

?>

</body>
</html> 
