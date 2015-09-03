<!DOCTYPE html>
<!--
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
-->

<html>
<head>
    <meta charset="utf-8">
    <title>Mapcode PHP Test</title>
</head>
<body>

<CENTER>

    <?php
    // ----------------------- start of PHP ---------------------
    include 'mapcode_data.php';
    include 'mapcode_api.php';
    include 'mapcode_countrynames.php'; // so we can show full country names (in english)

    echo '<H1>Mapcode PHP version ' . mapcode_phpversion . ' example</H1>';

    $input = trim($_REQUEST["s"]);
    if ($input != NULL) {
        //$input = utf8_decode(mb_convert_encoding($input,"utf-8" ));
        if (strpos($input, ',') !== false) // contains a comma, so assume coordinates
        {
            $p = strpos($input, ',');
            $lat = substr($input, 0, $p);
            $lon = substr($input, $p + 1);
            $precision = 0;
            $r = encodeShortestWithPrecision($lat, $lon, $precision);
            $n = count($r);
            echo 'Coordinate ' . $lat . ' , ' . $lon . ' has mapcodes in ' . $n . ' territories: <BR>';
            for ($i = 0; $i < $n; $i++) {
                $p = strpos($r[$i], ' ');
                $territory = ($p === false ? "AAA" : substr($r[$i], 0, $p));
                echo ' &nbsp; <B>' . $r[$i] . '</B> (' . getTerritoryFullname($territory) . ')<BR>';
            }
            echo '<hr>';
        } else // assume mapcode
        {
            $asc = convertToAlphabet($input, 0);
            echo 'Input<BR>"' . $input . '"';
            if ($asc != $input) {
                echo ' (romanized as ' . $asc . ')';
            }
            $d = decode($input);
            if ($d == 0) {
                echo '<BR>is not a valid mapcode<BR>';
                if (strpos($input, ' ') === false) {
                    echo '(did you include the territory?)<BR>';
                }

            } else {
                echo '<BR>decodes to coordinate<BR>' . number_format($d->lat, 9) . ',' . number_format($d->lon, 8) . '<BR>';
            }
            echo '<hr>';
        }
    }
    // ----------------------- end of PHP ---------------------
    ?>

    Enter a mapcode
    <BR>
    (e.g. <A HREF="sample.php?s=NLD 49.4V"><B>NLD 49.4V</B></A>
    or <A HREF="sample.php?s=MOW+%D0%9F%D0%96.%D0%A3%D0%91"><B>MOW &#1055;&#1046;.&#1059;&#1041;</B></A>
    or <A HREF="sample.php?s=MH+%E0%A5%AB%E0%A4%97.%E0%A4%B8%E0%A4%A1%E0%A4%9C"><B>MH
            &#2411;&#2327;.&#2360;&#2337;&#2332;</B></A>)

    <BR>
    or a coordinate
    <BR>
    (e.g. <A HREF="sample.php?s=52.3765,4.9085"><B>52.3765, 4.908</B></A>)
    <BR>

    and press OK
    <BR>
    <BR>

    <form action="sample.php"><input type="text" name="s" value=""><input type="submit" value="OK"></form>

</body>
</html>
