<!DOCTYPE html>
<html>
<head>
<title>Mapcode PHP Test</title>
</head>
<body>

<script>
function progress(id,x,total) {
  var e = document.getElementById(id);
  if (e) e.innerHTML = x+'/'+total+' '+Math.floor((x*100.0)/total);
}
</script>

<?php

include '../mapcode_data.php'; // data
include '../mapcode_fast_encode.php';
include '../mapcode_api.php';

include 'test_territories.php';
include 'test_encodes.php';

echo "Mapcode Unittest version 2.1.0<BR>";
echo "Mapcode PHP version "  . mapcode_phpversion . "<BR>";
echo "Mapcode DATA version " . mapcode_dataversion . "<BR>";
if ($redivar) echo "Mapcode fast_encode loaded<BR>";

// globals to count tests, errors and warnings
$nrTests=0;
$nrErrors=0;
$nrWarnings=0;

// test the alphabet conversion routines 
function alphabet_tests()
{
  echo MAPCODE_ALPHABETS_TOTAL . ' alphabets<BR>';

  for($i=0;$i<MAPCODE_ALPHABETS_TOTAL;$i++) {

    // see if convertToAlphabet survives empty string
    $GLOBALS['nrTests']++;
    $str = "";
    $enc = convertToAlphabet($str,$i);
    if ($enc!="") {
      $GLOBALS['nrErrors']++;
      echo 'convertToAlphabet("'.$str.'".'.$i.') != empty<BR>';
    }

    // see if alphabets (re)convert as expected
    $str    = "OEUoi OIoi#%?-.abcdfghjklmnpqrstvwxyz0123456789ABCDFGHJKLMNPQRSTVWXYZ";
    $expect = "OEUOI OIOI#%?-.ABCDFGHJKLMNPQRSTVWXYZ0123456789ABCDFGHJKLMNPQRSTVWXYZ";
    $enc = convertToAlphabet($str,$i);
    $dec = convertToAlphabet($enc,0);
    $GLOBALS['nrTests']++;
    if ($dec!=$expect) 
    {
      $GLOBALS['nrErrors']++;
      echo 'convertToAlphabet(convertToAlphabet("'.$str.'".'.$i.'),0)="'.$dec.'". expected "'.$expect.'"<BR>';
    }

    // see if E/U voweled mapcodes (re)convert as expected
    $str="OMN 112.3EU";
    $dec = convertToAlphabet(convertToAlphabet($str,$i),0);
    $GLOBALS['nrTests']++;
    if ($dec!=$str) 
    {
      $GLOBALS['nrErrors']++;
      echo 'convertToAlphabet(convertToAlphabet("'.$str.'".'.$i.'),0)="'.$dec.'". expected "'.$str.'"<BR>';
    }
  }
}

// maximum error in meters for a certain nr of high-precision digits
$maxErrorForPrecision = array(
    7.49,
    1.45,
    0.2502,
    0.0462,
    0.00837,
    0.00154,
    0.00028,
    0.000052,
    0.0000093);

function printGeneratedMapcodes($r)
{
  $n = count($r);
  echo ' &nbsp; Delivered: '.$n.' results:';
  for ($i=0;$i<$n;$i++) 
    echo ' (' . $r[$i] . ')';
  echo '<BR>';
}

// perform an encode/decode test

function test_encode_decode( $str, $y, $x, $localsolutions, $globalsolutions ) 
{
  if ($GLOBALS['nrErrors']>20) return;
  $nrt = $GLOBALS['nrTests'];

  $str=trim($str);
  $j = strpos($str,' '); 
  if ($j>0) $territory=substr($str,0,$j); else $territory="AAA";

  // encode globally
  $precision = 2;
  $r = encodeWithPrecision( $y,$x,$precision );
  $n = count($r);

  // test if correct nr of global solutions (if requested)
  if ($globalsolutions) {
    $nrt++;
    if ($n!=$globalsolutions) {
      $GLOBALS['nrErrors']++;
      echo '*** ERROR *** encode('.number_format($y,8).' . '.number_format($x,8).') does not deliver '.$n.' solutions<BR>';
      printGeneratedMapcodes($r);
    }
  }

  if ($localsolutions || strlen($str)>0) 
  {
    // count local solutions, look for expected solution
    $found = 0;
    $nrlocal = 0;
    for ($i=0;$i<$n;$i++) {
      if (strpos($r[$i],$territory.' ')===0) {
        $nrlocal++;
        if (strpos($r[$i],$str)===0)
          $found = 1;
      }
    }

    // test that EXPECTED solution is there (if requested)
    if (strlen($str)) {
      $nrt++;
      if ($found == 0) {
        $GLOBALS['nrErrors']++;
        echo '*** ERROR *** encode('.number_format($y,14).' . '.number_format($x,14).' . "'.$territory.'" ) does not deliver "'.$str.'"<BR>';
        printGeneratedMapcodes($r);
      }
    }

    // test if correct nr of local solutions (if requested)
    if ($localsolutions) {
      $nrt++;
      if ($nrlocal!=$localsolutions) {
        $GLOBALS['nrErrors']++;
        echo '*** ERROR *** encode('.number_format($y,14).' . '.number_format($x,14).' . "'.$territory.'" ) does not deliver '.$localsolutions.' solutions<BR>';
        printGeneratedMapcodes($r);
      }
    }
  }

  for ($precision=0; $precision<=0; $precision++)
  {
    $r = encodeWithPrecision( $y,$x,$precision );
    $n = count($r);
    // check that all global solutions are within 9 milimeters of coordinate
    for ($i = 0; $i < $n; $i++) {
      $nrt++;
      $str = $r[$i];
      // check if every solution decodes
      $p = decode($str);
      if ($p == 0) {
          $GLOBALS['nrErrors']++;
          echo '*** ERROR *** decode('.$str.') = no result. expected ~('.number_format($y,14).' . '.number_format($x,14).')<BR>';
      }
      else {
        // check if decode of $str is sufficiently close to the encoded coordinate
        $dm = distanceInMeters($y, $x, $p->lat, $p->lon);
        $maxerror = $GLOBALS['maxErrorForPrecision'][$precision];
        if ($dm>$maxerror) {
          $GLOBALS['nrErrors']++;
          echo '*** ERROR *** decode('.$str.') = ('.number_format($p->lat,14).' , '.number_format($p->lon,14).') which is '. number_format($dm*100,2).' cm away (>'.($maxerror*100).' cm) from (' . number_format($y,14) . ', ' . number_format($x,14) . ')<BR>';
        }
        else if ($GLOBALS['nrWarnings']<20) {
          // see if decode encodes back to the same solution
          $j = strpos($str,' '); 
          if ($j>0) $territory=substr($str,0,$j); else $territory="AAA";

          $r3 = false;
          $r2 = encodeWithPrecision( $p->lat,$p->lon,$precision, $territory ); 
          $n2 = count($r);
          $found=0;
          for($i2=0; $i2<$n2; $i2++) {
            if ($r2[$i2]==$str) { 
              $found=1; 
              break; 
            }
          }
          // or, if inherited from parent country: the same parent solution
          if (!$found) {
            $parent = getParentOf($territory);
            if ($parent>=0) {
              $proper = substr($str,strpos($str," "));
              $r3 = encodeWithPrecision( $p->lat,$p->lon,$precision, $parent ); 
              $n3 = count($r);
              for($i3=0; $i3<$n3; $i3++) {
                $r3proper = substr($r3[$i3],strpos($r3[$i3]," "));
                if ($r3proper==$proper) { 
                  $found=1; 
                  break; 
                }
              }
            }
          }
          if (!$found) {
            echo '*** WARNING *** decode(' . $str . ') = (' . number_format($p->lat,14) . ', ' . number_format($p->lon,14) . ', '.$territory.') does not re-encode from (' . number_format($y,14) . ', ' . number_format($x,14) . ')<BR>';
            printGeneratedMapcodes($r);
            printGeneratedMapcodes($r2);
            if ($r3!==false) printGeneratedMapcodes($r3);
            $GLOBALS['nrWarnings']++;
            if ($GLOBALS['nrWarnings'] == 20) echo "*** ERROR *** too many warnings<BR>";
          }
        }
      }
    }
  }

  $GLOBALS['nrTests'] = $nrt;
}

// test strings that are expected to FAIL a decode
function test_failing_decodes() {
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

    for ($i=0;;$i++)
    {
      $str = $badcodes[$i];
      if ($str=="end")
        break;

      $GLOBALS['nrTests']++;
      $p = decode($str);
      if ($p) {
        $GLOBALS['nrErrors']++;
        echo '*** ERROR *** invalid mapcode "'.$str.'" decodes without error ('.number_format($p->lat,14).','.number_format($p->lon,14).')<BR>';
      }
    }
}

// perform tests on alphacodes (used from test_territories.php)
function test_territory($alphacode,$tc,$isAlias,$needsParent,$tcParent)
{
  $ccode = $tc-1;
  
  // test internal getTerritoryNumber (recognise alphacode as $ccode)
  $GLOBALS['nrTests']++;
  $tn = getTerritoryNumber($alphacode,needsParent ? ($tcParent - 1) : 0);
  if ($tn!=$ccode) {
    $GLOBALS['nrErrors']++;
    echo '*** ERROR *** getTerritoryNumber('.$alphacode.'.'.($p ? getTerritoryAlphaCode($p) : "").')=' .$tn. ' but expected ' . $ccode . '<BR>';
  }

  // also test that alphacode is generated (unless it is ambigious or an alias)
  if ($needsParent==0 && $isAlias==0 && (strlen($alphacode)<=3 || $alphacode[3]!='-')) {
    $GLOBALS['nrTests']++;
    $nam = getTerritoryAlphaCode($ccode);
    // either perfect match, or "something-alphacode"
    if ( $nam!=$alphacode && strpos($nam,"-".$alphacode)===false ) {
      $GLOBALS['nrErrors']++;
      echo '*** ERROR *** getTerritoryAlphaCode('.$ccode.')="'.$nam.'" which does not equal or contain "'.$alphacode.'"<BR>';
    }
  }
}

// perform encode/decode tests using the encode_testdata array
function test_encodes()
{
  // count nr of tests
  $t = $GLOBALS['encodes_testdata'];
  $n=0;
  while ( $t[$n*5]!==false ) $n++;

  // executed (optionally, from "start" parameter)
  $i = intval($_GET["start"])-1;
  if ($i<0) $i=0;  
  $nextlevel = $i;
  while ($i<$n)
  {
    test_encode_decode($t[5*$i],$t[5*$i+1],$t[5*$i+2],$t[5*$i+3],$t[5*$i+4]);
    $i++;
    // show progress
    if ($i >= $nextlevel) {
      echo '<script>progress("prog2",'.$i.','.$n.');</script>';
      $nextlevel = 20 + 20 * floor($i/20);
      if ( $nextlevel > $n ) $nextlevel = $n;
    }
  }
}


$next_corner_to_test = 0;
function test_corner_encodes()
{
	$tests_per_timeslot = 5;
	$last = dataLastRecord(ccode_earth);
	for ($m=$GLOBALS['next_corner_to_test']; $m<$last; $m++) {
		if ($GLOBALS['nrErrors']>20) {
			echo 'Too many errors!<BR>';
			return 0;
		}
		if ($tests_per_timeslot-- == 0) {
			$GLOBALS['next_corner_to_test'] = $m;
      echo '<script>progress("prog1",'.$m.','.$last.');</script>';
			return 1;
		}
		$mm = minmaxSetup($m);
    // center
		test_encode_decode( "", ($mm->miny+$mm->maxy)/2000000, ($mm->minx+$mm->maxx)/2000000, 0,0 );
    // corner just inside
		test_encode_decode( "", $mm->miny/1000000.0, $mm->minx/1000000.0, 0,0 );
    // corner just outside y
		test_encode_decode( "", ($mm->miny-0.000001)/1000000.0, ($mm->minx)/1000000.0, 0,0 );
    // corner just outside x
		test_encode_decode( "", ($mm->miny)/1000000.0, ($mm->minx-0.000001)/1000000.0, 0,0 );
		// corner opposite just inside
		test_encode_decode( "", ($mm->maxy-0.000001)/1000000.0, ($mm->maxx-0.000001)/1000000.0, 0,0 );
		// corner opposite just outside
		test_encode_decode( "", ($mm->maxy)/1000000.0, ($mm->maxx)/1000000.0, 0,0 );
	}
	return 0;
}


///////////////////////////////////////////////

  echo '<HR>Character tests<BR>';
  alphabet_tests();

  echo '<HR>Territory tests<BR>';
  echo MAX_CCODE . " territories<BR>";
  test_territories(); // uses test_territory()

  echo '<HR>Decode fail tests<BR>';
  test_failing_decodes();

  echo '<HR>Encode/Decode tests <font id="prog2">0</font>%<BR>';
  test_encodes(); // uses test_encode_decode()

  echo '<HR>Edge encode/decode tests <font id="prog1">0</font>%<BR>';
  {
    $i = intval($_GET["edge"])-1;
    if ($i>0) $GLOBALS['next_corner_to_test'] = $i;
    while (test_corner_encodes()) ;
  }

  echo '<HR>Done.<BR>';
  echo ' Executed ',$nrTests,' tests, found ', $nrErrors,' errors<P>';

 ?>

</body>
</html> 
