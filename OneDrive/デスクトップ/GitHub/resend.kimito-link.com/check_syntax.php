<?php
// 括弧のバランスを確認するスクリプト
$file = 'public/index.php';
$code = file_get_contents($file);
$lines = explode("\n", $code);

echo "Checking parentheses balance around line 1218...\n\n";

// 1165行目から1245行目までをチェック
$startLine = 1165;
$endLine = min(1245, count($lines));

$totalOpen = 0;
$totalClose = 0;

for ($i = $startLine - 1; $i < $endLine; $i++) {
    $lineNum = $i + 1;
    $line = $lines[$i];
    
    $open = substr_count($line, '(');
    $close = substr_count($line, ')');
    $totalOpen += $open;
    $totalClose += $close;
    
    if ($open != $close) {
        echo sprintf("Line %d: open=%d, close=%d, diff=%d\n", $lineNum, $open, $close, $open - $close);
        echo "  " . trim($line) . "\n\n";
    }
    
    // 1218行目付近を特に詳しく表示
    if ($lineNum >= 1215 && $lineNum <= 1225) {
        echo sprintf("Line %d: open=%d, close=%d\n", $lineNum, $open, $close);
        echo "  " . trim($line) . "\n\n";
    }
}

echo sprintf("\nTotal in range [%d-%d]: open=%d, close=%d, diff=%d\n", 
    $startLine, $endLine, $totalOpen, $totalClose, $totalOpen - $totalClose);

// 全体の括弧バランスも確認
$allOpen = substr_count($code, '(');
$allClose = substr_count($code, ')');
echo sprintf("Total in file: open=%d, close=%d, diff=%d\n", 
    $allOpen, $allClose, $allOpen - $allClose);
