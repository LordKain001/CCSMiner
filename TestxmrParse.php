
<?php
include "xmrParser.php";

$xmrReport["connection"] = "";

$xmrReport["results"] = "";

$xmrReport["hashreport"] = "HASHRATE REPORT - CPU
| ID |    10s |    60s |    15m |
|  0 |   29.2 |   (na) |   (na) |
Totals (CPU):    29.2    0.0    0.0 H/s
-----------------------------------------------------------------
HASHRATE REPORT - AMD
| ID |    10s |    60s |    15m | ID |    10s |    60s |    15m |
|  0 |  (na) |   (na) |   (na) |  1 |  195.7 |   (na) |   (na) |
|  2 |  (na) |   (na) |   (na) |  3 |  198.8 |   (na) |   (na) |
|  4 |  141.3 |   (na) |   (na) |  5 |  141.3 |   (na) |   (na) |
Totals (AMD):  1071.4    0.0    0.0 H/s
-----------------------------------------------------------------
Totals (ALL):   1100.6    0.0    0.0 H/s
Highest:  1102.2 H/s
-----------------------------------------------------------------";


var_dump(ParsexmrReport($xmrReport));

?>