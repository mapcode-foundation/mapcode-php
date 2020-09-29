# Mapcode Library for PHP

Copyright (C) 2014-2020 Stichting Mapcode Foundation (http://www.mapcode.com)

----

This PHP project contains a library to encode latitude/longitude pairs to mapcodes
and to decode mapcodes back to latitude/longitude pairs.

**Online documentation can be found at: http://mapcode-foundation.github.io/mapcode-php/**

## License

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

## PHP Files for Mapcode Support

    mapcode_data.php         - Data table for mapcode support
    mapcode_func.php         - Key routines for mapcode support
    mapcode_fast_encode.php  - Data for fast encoding of coordinates
    mapcode_ctrynams.php     - Optional array with english territory names

    sample.php - Sample php code to interpret or generate mapcodes
    (upload all 4 files to a server, then open sample.php in a web browser)

    unittest\unittest.php    - Unit test for mapcode library

## Documentation

    mapcode_library_php.doc  - Manual: how to use the PHP Mapcode Library
    LICENSE                  - Apache License, Version 2.0
    NOTICE                   - About this package
    README.md                - This document

## Version History

### 2.2.2

* Fixed PHP errors for new version of PHP.

* Cleaned up/reformatted source code.

### 2.2.0 - 2.2.1

* Solved 1-microdegree gap in a few spots on Earth, noticable now extreme precision is possible.

### 2.1.5

* Reworked high-precision to pure integer math.

* Enforce encode(decode(m))=m except at territory borders.
* Added maxErrorinMeters to API.

### 2.1.1

* Added DistanceInMeters to API.

### 2.1.0

* Rewrote fraction floating points to integer arithmetic.

* Several fixes; extended unit tests.

### 2.0.3

* Added unittest.php, which verifies that the library works as expected.

### 2.0.2

* Ported fast_encode from C library (4x faster global encoding).

* Minor improvements (stricter tests).

### 2.0.0

* Initial open source release. (The release starts at 2.0.0 because the
mapcode algorithms match the 2.0.x releases in Java, C/C++, and other
languages.)

