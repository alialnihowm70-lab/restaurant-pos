<?php
$ch = curl_init('https://restaurant-pos-e5vb.onrender.com/menu-test');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);

if (preg_match('/class="exception_title"[^>]*>(.*?)<\/h2>/is', $res, $matches)) {
    echo "Exception Title: " . trim(strip_tags($matches[1])) . "\n";
}
if (preg_match('/class="exception_message"[^>]*>(.*?)<\/span>/is', $res, $matches)) {
    echo "Exception Message: " . trim(strip_tags($matches[1])) . "\n";
}
if (preg_match('/<div class="exception-message"[^>]*>(.*?)<\/div>/is', $res, $matches)) {
    echo "Exception Message Div: " . trim(strip_tags($matches[1])) . "\n";
}
if (preg_match('/class="exception_class"[^>]*>(.*?)<\/span>/is', $res, $matches)) {
    echo "Exception Class: " . trim(strip_tags($matches[1])) . "\n";
}
// Find the first file path and line number mentioned
if (preg_match('/(\/[a-zA-Z0-9_\-\.\/]+.php):(\d+)/is', $res, $matches)) {
    echo "Location: " . $matches[1] . " line " . $matches[2] . "\n";
}
if (preg_match('/in ([a-zA-Z0-9_\-\.\/]+.php) on line (\d+)/is', $res, $matches)) {
    echo "Location in: " . $matches[1] . " line " . $matches[2] . "\n";
}
// Print lines around "ParseError" in the HTML text to see context
$lines = explode("\n", strip_tags($res));
foreach ($lines as $i => $line) {
    if (strpos($line, 'syntax error') !== false || strpos($line, 'ParseError') !== false || strpos($line, 'unexpected') !== false) {
        for ($j = max(0, $i - 3); $j <= min(count($lines) - 1, $i + 5); $j++) {
            echo "Line $j: " . trim($lines[$j]) . "\n";
        }
        break;
    }
}
?>
